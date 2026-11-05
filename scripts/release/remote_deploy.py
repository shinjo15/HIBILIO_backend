#!/usr/bin/python3 -I
"""Install once, reviewed, root-owned as /usr/local/sbin/hibilio-deploy.

JSON protocol only. Never interprets SSH_ORIGINAL_COMMAND; no rollback protocol.
"""
import json
import re
import hashlib
import os
from pathlib import Path
import subprocess
import urllib.request

FILES = ('compose.yml', 'compose.production.yml')
SHA = r'[0-9a-f]{40}'
DIGEST = r'sha256:[0-9a-f]{64}'
VERSION = r'v(?:0|[1-9][0-9]{0,5})\.(?:0|[1-9][0-9]{0,5})\.(?:0|[1-9][0-9]{0,5})'
BOOTSTRAP = {
    'backend_sha': 'e3facf736b251f25217ab15a4ff95458a0463fa0',
    'app_digest': 'sha256:28947faabf479ff56409e1ed184f986a5b6693dc8373c00e752ef27da0b76af2',
    'nginx_digest': 'sha256:7518fe5434a24d43b7a79e214336397d410fe4167be09db4f573c57516c68a81',
}


def matches(pattern, value):
    return isinstance(value, str) and re.fullmatch(pattern, value) is not None


def validate_request(value):
    if value == {'operation': 'status'}:
        return value
    keys = {'operation', 'version', 'backend_sha', 'app_digest', 'nginx_digest', 'compose_hashes'}
    if not isinstance(value, dict) or set(value) != keys or value['operation'] != 'deploy':
        raise ValueError('Invalid request schema')
    for key, pattern in [('version', VERSION), ('backend_sha', SHA),
                         ('app_digest', DIGEST), ('nginx_digest', DIGEST)]:
        if not matches(pattern, value[key]):
            raise ValueError('Invalid request field: ' + key)
    hashes = value['compose_hashes']
    if not isinstance(hashes, dict) or set(hashes) != set(FILES):
        raise ValueError('Invalid compose paths')
    if not all(matches(r'[0-9a-f]{64}', item) for item in hashes.values()):
        raise ValueError('Invalid compose hashes')
    return value


def unique_object(pairs):
    result = {}
    for key, value in pairs:
        if key in result:
            raise ValueError('Duplicate JSON key')
        result[key] = value
    return result


def read_request(stream, original_command):
    if original_command:
        raise ValueError('SSH commands are forbidden')
    raw = stream.read(4097)
    if len(raw) > 4096:
        raise ValueError('Request too large')
    return validate_request(json.loads(raw, object_pairs_hook=unique_object))


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None


def fetch(url):
    request = urllib.request.Request(url, headers={'Accept': 'application/vnd.github+json',
                                                 'User-Agent': 'hibilio-release'})
    with urllib.request.build_opener(NoRedirect).open(request, timeout=30) as response:
        if response.status != 200:
            raise ValueError('Trusted source unavailable')
        data = response.read(262145)
        if len(data) > 262144:
            raise ValueError('Source too large')
        return data


def trusted_compose(sha, hashes, get=fetch):
    if not matches(SHA, sha) or set(hashes) != set(FILES):
        raise ValueError('Invalid source')
    comparison = json.loads(get(f'https://api.github.com/repos/shinjo15/HIBILIO_backend/compare/{sha}...main'))
    if (comparison.get('status') not in ('ahead', 'identical')
            or comparison.get('base_commit', {}).get('sha') != sha
            or comparison.get('merge_base_commit', {}).get('sha') != sha):
        raise ValueError('Commit is not trusted main ancestry')
    result = {}
    for name in FILES:
        data = get(f'https://raw.githubusercontent.com/shinjo15/HIBILIO_backend/{sha}/{name}')
        if hashlib.sha256(data).hexdigest() != hashes[name]:
            raise ValueError('Trusted compose checksum mismatch')
        result[name] = data
    return result


def image_env(original, sha, app, nginx):
    validate_request(dict(operation='deploy', version='v1.0.1', backend_sha=sha,
                          app_digest=app, nginx_digest=nginx,
                          compose_hashes={name: '0' * 64 for name in FILES}))
    replacements = {b'APP_IMAGE': f'ghcr.io/shinjo15/hibilio-backend-app@{app}'.encode(),
                    b'NGINX_IMAGE': f'ghcr.io/shinjo15/hibilio-backend-nginx@{nginx}'.encode()}
    seen = set()
    output = []
    for line in original.splitlines(keepends=True):
        key = line.partition(b'=')[0]
        setting = re.match(rb'''\s*(?:export\s+)?['"]?(APP_IMAGE|NGINX_IMAGE)['"]?\s*=''', line)
        if setting and key != setting.group(1):
            raise ValueError('Noncanonical image setting')
        if key in replacements:
            if key in seen:
                raise ValueError('Duplicate image setting')
            seen.add(key)
            ending = b'\r\n' if line.endswith(b'\r\n') else b'\n'
            output.append(key + b'=' + replacements[key] + ending)
        else:
            output.append(line)
    if seen != set(replacements):
        raise ValueError('Missing canonical image settings')
    return b''.join(output)


def command(args):
    completed = subprocess.run(args, stdin=subprocess.DEVNULL, stdout=subprocess.PIPE,
                               stderr=subprocess.PIPE, timeout=600, check=False,
                               env={'PATH': '/usr/sbin:/usr/bin:/sbin:/bin', 'HOME': '/root'})
    if completed.returncode:
        raise RuntimeError('External command failed (output suppressed)')
    return completed.stdout.decode()


def atomic(path, data):
    temporary = path.with_name(path.name + '.tmp')
    fd = os.open(temporary, os.O_WRONLY | os.O_CREAT | os.O_EXCL | os.O_NOFOLLOW, 0o600)
    try:
        with os.fdopen(fd, 'wb') as stream:
            stream.write(data)
            stream.flush()
            os.fsync(stream.fileno())
        os.replace(temporary, path)
        directory = os.open(path.parent, os.O_RDONLY | os.O_DIRECTORY)
        try:
            os.fsync(directory)
        finally:
            os.close(directory)
    finally:
        temporary.unlink(missing_ok=True)


DOCKER = ['docker', '--config', '/etc/hibilio/docker']


def safe_root_path(path):
    for part in (path, *path.parents):
        if part.is_symlink():
            raise ValueError('Symlink in protected path')
        if part.exists():
            stat = part.stat()
            if stat.st_uid != 0 or stat.st_mode & 0o022:
                raise ValueError('Protected path must be root-owned and not writable by others')


def containers(run=command):
    result = {}
    for service in ('app', 'nginx'):
        ids = run(DOCKER + ['ps', '-aq', '--filter', 'label=com.docker.compose.project=hibilio-production',
                            '--filter', f'label=com.docker.compose.service={service}']).split()
        if len(ids) != 1 or not matches(r'[0-9a-f]{12,64}', ids[0]):
            raise ValueError('Expected exactly one production container per service')
        template = ('{"image_id":{{json .Image}},"image":{{json .Config.Image}},'
                    '"state":{{json .State}},"labels":{{json .Config.Labels}}}')
        result[service] = json.loads(run(DOCKER + ['inspect', '--format', template, ids[0]]))
    return result


def verify_running(state, run=command):
    for service, item in containers(run).items():
        expected = state['app_digest' if service == 'app' else 'nginx_digest']
        package = 'hibilio-backend-' + service
        image = json.loads(run(DOCKER + ['image', 'inspect', '--format',
                                         '{{json .RepoDigests}}', item['image_id']]))
        if f'ghcr.io/shinjo15/{package}@{expected}' not in image:
            raise ValueError('Running image digest mismatch')
        if (item['state'].get('Status') != 'running'
                or item['state'].get('Health', {}).get('Status') != 'healthy'):
            raise ValueError('Production container not healthy')
        paths = item['labels'].get('com.docker.compose.project.config_files', '').split(',')
        if paths != state['compose_files']:
            raise ValueError('Running compose source mismatch')


def discover_initial():
    """No symlink assumptions and no config writes. Unknown initial state fails closed."""
    items = containers()
    paths = items['app']['labels'].get('com.docker.compose.project.config_files', '').split(',')
    if len(paths) != 2 or [Path(p).name for p in paths] != list(FILES):
        raise ValueError('Unknown initial compose layout; administrator must inspect')
    if Path(paths[0]).parent != Path(paths[1]).parent:
        raise ValueError('Initial compose files must share a directory')
    hashes = {}
    for text in paths:
        path = Path(text)
        if not path.is_absolute() or not path.is_relative_to('/opt/hibilio') or '..' in path.parts:
            raise ValueError('Initial compose path outside protected installation')
        safe_root_path(path)
        hashes[path.name] = hashlib.sha256(path.read_bytes()).hexdigest()
    trusted_compose(BOOTSTRAP['backend_sha'], hashes)
    state = dict(BOOTSTRAP, compose_files=paths, compose_hashes=hashes, phase='bootstrap')
    verify_running(state)
    return state


class Deployer:
    def __init__(self, root=Path('/opt/hibilio'), env=Path('/etc/hibilio/compose.env'),
                 run=command, source=trusted_compose, discover=discover_initial,
                 verify=verify_running):
        self.root, self.env = root, env
        self.run, self.source, self.discover, self.verify = run, source, discover, verify

    def status(self):
        records = self.root / 'deployments'
        if records.exists():
            for record in sorted(records.glob('v*.json')):
                if json.loads(record.read_text()).get('phase') not in ('healthy', 'recovered'):
                    raise ValueError('Unresolved deployment; manual recovery required')
        current = self.root / 'current.json'
        state = json.loads(current.read_text()) if current.exists() else self.discover()
        self.verify(state)
        return state

    def deploy(self, request):
        validate_request(request)
        previous = {key: value for key, value in self.status().items()
                    if key in ('version', 'backend_sha', 'app_digest', 'nginx_digest',
                               'compose_files', 'compose_hashes', 'phase')}
        record = self.root / 'deployments' / (request['version'] + '.json')
        if record.exists():
            raise ValueError('Version is immutable')
        files = self.source(request['backend_sha'], request['compose_hashes'])
        directory = self.root / 'releases' / request['backend_sha']
        if directory.exists():
            if set(p.name for p in directory.iterdir()) != set(FILES):
                raise ValueError('Incomplete release directory')
            if any((directory / name).is_symlink() or (directory / name).read_bytes() != files[name]
                   for name in FILES):
                raise ValueError('Immutable compose directory mismatch')
        original = self.env.read_bytes()
        updated = image_env(original, request['backend_sha'], request['app_digest'], request['nginx_digest'])
        record.parent.mkdir(mode=0o700, parents=True, exist_ok=True)
        state = dict(request, previous=previous, phase='prepared', migration_started=False,
                     migration_applied=False, compose_files=[str(directory / name) for name in FILES])
        def save():
            atomic(record, json.dumps(state, sort_keys=True).encode())
        save()  # Durable reservation precedes all production writes, including pulls.
        try:
            atomic(record.with_suffix('.compose.env'), original)
            directory.mkdir(mode=0o700, parents=True, exist_ok=True)
            for name in FILES:
                if not (directory / name).exists():
                    atomic(directory / name, files[name])
            for service, digest in [('app', request['app_digest']), ('nginx', request['nginx_digest'])]:
                image = f'ghcr.io/shinjo15/hibilio-backend-{service}@{digest}'
                self.run(DOCKER + ['pull', image])
                metadata = json.loads(self.run(DOCKER + ['image', 'inspect', '--format',
                    '{"labels":{{json .Config.Labels}},"arch":{{json .Architecture}},"os":{{json .Os}}}', image]))
                if (metadata.get('labels', {}).get('org.opencontainers.image.revision') != request['backend_sha']
                        or metadata.get('arch') != 'amd64' or metadata.get('os') != 'linux'):
                    raise ValueError('Pulled image revision/platform mismatch')
            # Only these two settings change; all other files under /etc/hibilio are untouched.
            atomic(self.env, updated)
            compose = DOCKER + ['compose', '--project-name', 'hibilio-production', '--env-file', str(self.env)]
            for name in FILES:
                compose += ['-f', str(directory / name)]
            self.run(compose + ['config', '--quiet'])
            state['phase'] = 'migrating'
            state['migration_started'] = True
            save()
            self.run(compose + ['run', '--rm', '--no-deps', '--pull', 'never', '-T',
                                'app', 'php', 'artisan', 'migrate', '--force', '--no-interaction'])
            state['migration_applied'] = True
            state['phase'] = 'recreating'
            save()
            self.run(compose + ['up', '--detach', '--no-build', '--no-deps', '--pull', 'never',
                                '--force-recreate', '--wait', '--wait-timeout', '180', 'app', 'nginx'])
            self.verify(state)
            state['phase'] = 'healthy'
            save()
            atomic(self.root / 'current.json', json.dumps(state, sort_keys=True).encode())
            return state
        except BaseException:
            state['phase'] = 'failed'
            save()
            raise


def main():
    import fcntl
    import signal
    import sys
    os.umask(0o077)
    signal.alarm(900)
    try:
        if len(sys.argv) != 1:
            raise ValueError('Command arguments forbidden')
        request = read_request(sys.stdin.buffer, os.environ.get('SSH_ORIGINAL_COMMAND', ''))
        if os.geteuid() != 0:
            raise ValueError('Root required')
        for path in (Path(__file__), Path('/opt/hibilio'), Path('/etc/hibilio/compose.env'),
                     Path('/etc/hibilio/docker')):
            safe_root_path(path)
        with open('/run/hibilio-deploy.lock', 'a') as lock:
            fcntl.flock(lock, fcntl.LOCK_EX | fcntl.LOCK_NB)
            engine = Deployer()
            result = engine.status() if request['operation'] == 'status' else engine.deploy(request)
            print(json.dumps(result, sort_keys=True))
        return 0
    except BaseException:
        # Never echo Docker/API responses or config. Persistent records contain recovery details.
        print('{"error":"Deployment stopped; inspect root deployment state and release run"}', file=sys.stderr)
        return 1


if __name__ == '__main__':
    raise SystemExit(main())
