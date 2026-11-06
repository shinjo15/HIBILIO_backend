"""Real external adapters for the manual coordinator; never run by local simulations."""
import hashlib
import importlib.util
import ipaddress
import json
import os
from pathlib import Path
import subprocess
import tempfile
import time
import urllib.error
import urllib.parse
import urllib.request

from coordinate import (BACKEND, FRONTEND, SELECTION, WORK, WRANGLER, Firewall,
                        dist_digest, image_digest, release_body, release_marker,
                        remote, rule_atoms, successful_run, validate_pages,
                        validate_version, write_json)

INSTANCE = 'hibilio-production-api'
REGION = 'ap-northeast-1'


class ApiError(RuntimeError):
    def __init__(self, status):
        self.status = status
        super().__init__('External API failed; HTTP ' + str(status))


def request_json(url, token=None, method='GET', payload=None):
    headers = {'Accept': 'application/json', 'User-Agent': 'hibilio-release',
               'Content-Type': 'application/json'}
    if urllib.parse.urlsplit(url).hostname in ('api.github.com', 'uploads.github.com'):
        headers['X-GitHub-Api-Version'] = '2022-11-28'
    if token:
        headers['Authorization'] = 'Bearer ' + token
    request = urllib.request.Request(url, method=method, headers=headers,
                                     data=None if payload is None else json.dumps(payload).encode())
    try:
        with urllib.request.build_opener(remote.NoRedirect).open(request, timeout=30) as response:
            return json.load(response)
    except urllib.error.HTTPError as error:
        status = error.code
        error.close()
        raise ApiError(status) from None
    except (OSError, ValueError):
        raise RuntimeError('External API unavailable or invalid JSON') from None


def run(args, *, env=None, cwd=None, data=None, timeout=600):
    result = subprocess.run(args, input=data, stdout=subprocess.PIPE, stderr=subprocess.PIPE,
                            cwd=cwd, env=env, timeout=timeout, check=False)
    if result.returncode:
        raise RuntimeError('External command failed: ' + Path(args[0]).name + ' (output suppressed)')
    return result.stdout.decode()


def clean_env(home):
    return {'PATH': os.environ['PATH'], 'HOME': str(home), 'CI': 'true',
            'VITE_API_ORIGIN': 'https://api.hibilio.com', 'WRANGLER_SEND_METRICS': 'false'}


class Services:
    def __init__(self):
        self.env = os.environ
        self.release_id = None
        self.firewall = Firewall(self.aws, WORK / 'firewall.json')

    def gh(self, path, method='GET', payload=None, repo=BACKEND):
        if repo == FRONTEND and method != 'GET':
            raise ValueError('Frontend repository writes are forbidden')
        return request_json(f'https://api.github.com/repos/{repo}/{path}',
                            self.env['GH_TOKEN'] if repo == BACKEND else None, method, payload)

    def cf(self, suffix=''):
        account = self.env['CLOUDFLARE_ACCOUNT_ID']
        result = request_json(f'https://api.cloudflare.com/client/v4/accounts/{account}/pages/projects/hibilio-web{suffix}',
                              self.env['CLOUDFLARE_API_TOKEN'])
        if result.get('success') is not True:
            raise ValueError('Cloudflare API did not confirm success')
        return result['result']

    def credentials(self):
        names = ('GH_TOKEN', 'CLOUDFLARE_API_TOKEN', 'CLOUDFLARE_ACCOUNT_ID',
                 'DEPLOY_SSH_KEY', 'DEPLOY_KNOWN_HOSTS', 'DEPLOY_HOST', 'DEPLOY_USER', 'AWS_ROLE_ARN')
        if any(not self.env.get(name) for name in names):
            raise ValueError('Missing release credentials/settings')
        if not remote.matches(r'[0-9a-f]{32}', self.env['CLOUDFLARE_ACCOUNT_ID']):
            raise ValueError('Invalid Cloudflare account ID')
        if not remote.matches(r'arn:aws:iam::[0-9]{12}:role/[A-Za-z0-9+=,.@_/-]+', self.env['AWS_ROLE_ARN']):
            raise ValueError('Invalid OIDC role ARN')
        ipaddress.IPv4Address(self.env['DEPLOY_HOST'])
        if self.env['DEPLOY_USER'] != 'hibilio-deploy':
            raise ValueError('Expected dedicated hibilio-deploy SSH user')
        hosts = self.env['DEPLOY_KNOWN_HOSTS'].strip().splitlines()
        if len(hosts) != 1:
            raise ValueError('Pin one independently verified host key')
        fields = hosts[0].split()
        if (len(fields) != 3 or fields[0] != self.env['DEPLOY_HOST']
                or fields[1] != 'ssh-ed25519' or not remote.matches(r'[A-Za-z0-9+/=]+', fields[2])):
            raise ValueError('Invalid pinned known_hosts entry')
        with tempfile.TemporaryDirectory() as directory:
            key = Path(directory) / 'key'
            hosts_file = Path(directory) / 'known_hosts'
            key.write_text(self.env['DEPLOY_SSH_KEY'] + '\n')
            hosts_file.write_text(self.env['DEPLOY_KNOWN_HOSTS'] + '\n')
            key.chmod(0o600)
            run(['ssh-keygen', '-y', '-P', '', '-f', str(key)])
            run(['ssh-keygen', '-l', '-f', str(hosts_file)])
        if (self.env.get('GITHUB_REPOSITORY') != BACKEND
                or self.env.get('GITHUB_REF') != 'refs/heads/main'
                or self.env.get('GITHUB_EVENT_NAME') != 'workflow_dispatch'):
            raise ValueError('Coordinator requires backend main workflow_dispatch')

    def absent_version(self, version):
        for path in ('git/ref/tags/' + version, 'releases/tags/' + version):
            try:
                self.gh(path)
            except ApiError as error:
                if error.status == 404:
                    continue
                raise
            raise ValueError('Version already reserved; never reuse tags/releases')

    def ci(self, repo, sha, workflow, job):
        query = urllib.parse.urlencode({'head_sha': sha, 'branch': 'main', 'per_page': 100})
        runs = self.gh(f'actions/workflows/{workflow}/runs?{query}', repo=repo)['workflow_runs']
        identifier = successful_run(runs, repo, sha)
        jobs = self.gh(f'actions/runs/{identifier}/jobs?filter=latest&per_page=100', repo=repo)
        if jobs['total_count'] != len(jobs['jobs']):
            raise ValueError('Truncated CI job list')
        required = [item for item in jobs['jobs'] if item['name'] == job]
        if len(required) != 1 or required[0]['conclusion'] != 'success':
            raise ValueError('Required exact CI job did not succeed')
        return identifier

    def resolve(self, repo):
        sha = self.gh('git/ref/heads/main', repo=repo)['object']['sha']
        if not remote.matches(remote.SHA, sha):
            raise ValueError('Invalid main SHA')
        return sha

    def images(self, sha):
        spec = importlib.util.spec_from_file_location('package_guard', Path('.github/scripts/check_package_visibility.py'))
        assert spec is not None and spec.loader is not None
        guard = importlib.util.module_from_spec(spec)
        spec.loader.exec_module(guard)
        for package in guard.PACKAGES:
            guard.check_package(package, self.env['GH_TOKEN'], phase='pre')
        digests = {}
        with tempfile.TemporaryDirectory() as directory:
            env = clean_env(directory)
            env['DOCKER_CONFIG'] = directory
            run(['docker', 'login', 'ghcr.io', '--username', 'shinjo15', '--password-stdin'],
                env=env, data=self.env['GH_TOKEN'].encode())
            for service in ('app', 'nginx'):
                image = f'ghcr.io/shinjo15/hibilio-backend-{service}:{sha}'
                run(['docker', 'pull', '--platform', 'linux/amd64', image], env=env)
                metadata = json.loads(run(['docker', 'image', 'inspect', image], env=env))[0]
                digests[service + '_digest'] = image_digest(metadata, service, sha)
        return digests

    def prepare(self):
        self.credentials()
        version = self.env.get('RELEASE_VERSION', '')
        validate_version(version)
        self.absent_version(version)
        backend, frontend = self.resolve(BACKEND), self.resolve(FRONTEND)
        runs = {'backend': self.ci(BACKEND, backend, 'ci.yml', 'Backend checks'),
                'images': self.ci(BACKEND, backend, 'production-images.yml', 'publish'),
                'frontend': self.ci(FRONTEND, frontend, 'frontend.yml', 'Verify frontend')}
        project = self.cf()
        validate_pages(project)
        canonical = project.get('canonical_deployment')
        if not canonical or not canonical.get('id'):
            raise ValueError('Existing production Pages deployment required')
        selection = dict(version=version, backend_sha=backend, frontend_sha=frontend,
                         ci_runs=runs, previous_pages_deployment_id=canonical['id'],
                         compose_hashes={name: hashlib.sha256(remote.fetch(
                             f'https://raw.githubusercontent.com/{BACKEND}/{backend}/{name}')).hexdigest()
                             for name in remote.FILES}, **self.images(backend))
        write_json(SELECTION, selection)
        return selection

    def aws(self, action, port=None):
        if action not in ('get-instance', 'get-instance-port-states',
                          'open-instance-public-ports', 'close-instance-public-ports'):
            raise ValueError('Unapproved Lightsail operation')
        args = ['aws', '--region', REGION, '--no-cli-pager', '--output', 'json', 'lightsail',
                action, '--instance-name', INSTANCE]
        if port is not None:
            args += ['--port-info', json.dumps(port)]
        return json.loads(run(args))

    def preflight(self):
        self.credentials()
        manifest = json.loads(SELECTION.read_text())
        if manifest['version'] != self.env.get('RELEASE_VERSION'):
            raise ValueError('Version changed after preparation')
        validate_version(manifest['version'])
        self.absent_version(manifest['version'])
        # Frozen SHAs remain valid if main advances. Force-pushed-away commits fail closed.
        for repo, key in ((BACKEND, 'backend_sha'), (FRONTEND, 'frontend_sha')):
            sha = manifest[key]
            if not remote.matches(remote.SHA, sha):
                raise ValueError('Invalid frozen SHA')
            comparison = self.gh(f'compare/{sha}...main', repo=repo)
            if (comparison.get('status') not in ('identical', 'ahead')
                    or comparison.get('base_commit', {}).get('sha') != sha
                    or comparison.get('merge_base_commit', {}).get('sha') != sha):
                raise ValueError('Frozen SHA no longer trusted main ancestry')
        self.ci(BACKEND, manifest['backend_sha'], 'ci.yml', 'Backend checks')
        self.ci(BACKEND, manifest['backend_sha'], 'production-images.yml', 'publish')
        self.ci(FRONTEND, manifest['frontend_sha'], 'frontend.yml', 'Verify frontend')
        if self.images(manifest['backend_sha']) != {key: manifest[key] for key in ('app_digest', 'nginx_digest')}:
            raise ValueError('Registry tag changed since prebuild')
        remote.trusted_compose(manifest['backend_sha'], manifest['compose_hashes'])
        project = self.cf()
        validate_pages(project)
        if project['canonical_deployment']['id'] != manifest['previous_pages_deployment_id']:
            raise ValueError('Pages production changed during preflight')
        marker = json.loads((WORK / 'frontend/dist/release.json').read_text())
        if marker != release_marker(manifest):
            raise ValueError('Prebuilt release marker mismatch')
        build = json.loads((WORK / 'build.json').read_text())
        if (build['dist_digest'] != dist_digest(WORK / 'frontend/dist')
                or build['frontend_sha'] != manifest['frontend_sha'] or build['wrangler_version'] != WRANGLER):
            raise ValueError('Prebuilt assets changed')
        manifest.update(build)
        if not (WORK / 'tools/node_modules/wrangler/bin/wrangler.js').is_file():
            raise ValueError('Pinned Wrangler missing')
        if any(not self.env.get(name) for name in ('AWS_ACCESS_KEY_ID', 'AWS_SECRET_ACCESS_KEY', 'AWS_SESSION_TOKEN')):
            raise ValueError('OIDC temporary credentials missing')
        instance = self.aws('get-instance')['instance']
        if (instance['name'] != INSTANCE or instance['publicIpAddress'] != self.env['DEPLOY_HOST']
                or instance['state']['name'] != 'running' or instance['location']['regionName'] != REGION):
            raise ValueError('Unexpected Lightsail instance/state/address')
        rule_atoms(self.firewall.read())
        with urllib.request.urlopen('https://checkip.amazonaws.com', timeout=20) as response:
            self.address = str(ipaddress.IPv4Address(response.read(64).decode().strip()))
        return manifest

    def open_firewall(self):
        self.firewall.open(self.address)

    def cleanup(self):
        self.firewall.cleanup()

    def ssh(self, payload):
        remote.validate_request(payload)
        with tempfile.TemporaryDirectory() as directory:
            directory = Path(directory)
            key, hosts = directory / 'key', directory / 'known_hosts'
            key.write_text(self.env['DEPLOY_SSH_KEY'] + '\n')
            hosts.write_text(self.env['DEPLOY_KNOWN_HOSTS'] + '\n')
            key.chmod(0o600)
            args = ['ssh', '-F', '/dev/null', '-T', '-i', str(key), '-o', 'BatchMode=yes',
                    '-o', 'IdentitiesOnly=yes', '-o', 'StrictHostKeyChecking=yes',
                    '-o', 'GlobalKnownHostsFile=/dev/null', '-o', 'UserKnownHostsFile=' + str(hosts),
                    '-o', 'ConnectTimeout=20', '-o', 'ServerAliveInterval=15', '-o', 'ServerAliveCountMax=4',
                    self.env['DEPLOY_USER'] + '@' + self.env['DEPLOY_HOST']]
            return json.loads(run(args, data=json.dumps(payload).encode(), timeout=960))

    def status(self):
        return self.ssh({'operation': 'status'})

    def reserve(self, manifest):
        self.absent_version(manifest['version'])
        self.gh('git/refs', 'POST', {'ref': 'refs/tags/' + manifest['version'], 'sha': manifest['backend_sha']})
        tag = self.gh('git/ref/tags/' + manifest['version'])
        if tag['object']['sha'] != manifest['backend_sha'] or tag['object']['type'] != 'commit':
            raise ValueError('Reserved tag readback mismatch')
        release = self.gh('releases', 'POST', dict(tag_name=manifest['version'],
            target_commitish=manifest['backend_sha'], name=manifest['version'], body=release_body(manifest),
            draft=True, prerelease=False, generate_release_notes=False))
        self.release_id = release['id']
        readback = self.gh('releases/' + str(self.release_id))
        if (readback.get('draft') is not True or readback.get('immutable') is not False
                or readback.get('id') != self.release_id or readback.get('tag_name') != manifest['version']
                or readback.get('target_commitish') != manifest['backend_sha']):
            raise ValueError('Draft release readback mismatch')

    def record(self, manifest):
        if self.release_id is None:
            return
        readback = self.gh('releases/' + str(self.release_id))
        if (readback.get('draft') is not True or readback.get('immutable') is not False
                or readback.get('id') != self.release_id or readback.get('tag_name') != manifest['version']
                or readback.get('target_commitish') != manifest['backend_sha']):
            raise ValueError('Only the reserved draft identity can be recorded')
        body = release_body(manifest)
        self.gh('releases/' + str(self.release_id), 'PATCH',
                {'tag_name': manifest['version'], 'target_commitish': manifest['backend_sha'],
                 'draft': True, 'body': body})
        readback = self.gh('releases/' + str(self.release_id))
        if (readback.get('draft') is not True or readback.get('immutable') is not False
                or readback.get('id') != self.release_id or readback.get('tag_name') != manifest['version']
                or readback.get('target_commitish') != manifest['backend_sha'] or readback.get('body') != body):
            raise ValueError('Release manifest readback mismatch')

    def backend(self, manifest):
        payload = {key: manifest[key] for key in ('version', 'backend_sha', 'app_digest', 'nginx_digest', 'compose_hashes')}
        payload['operation'] = 'deploy'
        result = self.ssh(payload)
        for key in ('version', 'backend_sha', 'app_digest', 'nginx_digest', 'compose_hashes'):
            if result.get(key) != manifest[key]:
                raise ValueError('Backend deployment readback mismatch')
        readback = self.status()
        if readback != result:
            raise ValueError('Backend status readback changed')
        return result

    def frontend(self, manifest):
        project = self.cf()
        validate_pages(project)
        if project['canonical_deployment']['id'] != manifest['previous_pages_deployment_id']:
            raise ValueError('Pages production changed before publication')
        env = clean_env(WORK.resolve() / 'tools-home')
        for key in ('CLOUDFLARE_ACCOUNT_ID', 'CLOUDFLARE_API_TOKEN'):
            env[key] = self.env[key]
        args = ['node', str((WORK / 'tools/node_modules/wrangler/bin/wrangler.js').resolve()),
                'pages', 'deploy', str((WORK / 'frontend/dist').resolve()), '--project-name', 'hibilio-web',
                '--branch', 'main', '--commit-hash', manifest['frontend_sha'],
                '--commit-message', manifest['version'], '--commit-dirty=false']
        # cwd excludes frontend config/functions; only static prebuilt dist is uploaded.
        run(args, cwd=WORK / 'tools', env=env)
        for attempt in range(12):
            deployment = self.cf().get('canonical_deployment', {})
            metadata = deployment.get('deployment_trigger', {}).get('metadata', {})
            if (deployment.get('id') != manifest['previous_pages_deployment_id']
                    and metadata.get('commit_hash') == manifest['frontend_sha']
                    and metadata.get('branch') == 'main' and deployment.get('environment') == 'production'
                    and deployment.get('latest_stage', {}).get('status') == 'success'):
                return {key: deployment[key] for key in ('id', 'url', 'environment')}
            time.sleep(5)
        raise ValueError('Pages production exact-commit deployment not confirmed')

    def verify_frontend(self, manifest):
        expected = release_marker(manifest)
        urls = [manifest['pages_deployment']['url'], 'https://hibilio-web.pages.dev']
        for base in urls:
            parsed = urllib.parse.urlsplit(base)
            if parsed.scheme != 'https' or not (parsed.hostname == 'hibilio-web.pages.dev'
                    or (parsed.hostname or '').endswith('.hibilio-web.pages.dev')):
                raise ValueError('Unexpected Pages verification URL')
            for attempt in range(12):
                try:
                    marker = request_json(base.rstrip('/') + '/release.json?release=' + manifest['version'])
                    if marker == expected:
                        break
                except (RuntimeError, ValueError):
                    pass
                time.sleep(5)
            else:
                raise ValueError('Public release.json marker readback mismatch')

    def publish(self, manifest):
        if (manifest.get('phase') != 'complete' or manifest.get('backend', {}).get('phase') != 'healthy'
                or manifest.get('frontend_verified') is not True or manifest.get('firewall_cleaned') is not True):
            raise ValueError('Both components and firewall cleanup must be verified before publication')
        readback = self.gh('releases/' + str(self.release_id))
        if (readback.get('draft') is not True or readback.get('immutable') is not False
                or readback.get('id') != self.release_id or readback.get('tag_name') != manifest['version']
                or readback.get('target_commitish') != manifest['backend_sha']):
            raise ValueError('Only the reserved draft identity can be published')
        tag = self.gh('git/ref/tags/' + manifest['version'])
        if tag['object']['type'] != 'commit' or tag['object']['sha'] != manifest['backend_sha']:
            raise ValueError('Release tag changed since reservation')
        asset = request_json(f'https://uploads.github.com/repos/{BACKEND}/releases/{self.release_id}/assets?name=release.json',
                             self.env['GH_TOKEN'], 'POST', manifest)
        readback = self.gh('releases/assets/' + str(asset['id']))
        expected = 'sha256:' + hashlib.sha256(json.dumps(manifest).encode()).hexdigest()
        if readback.get('name') != 'release.json' or readback.get('digest') != expected:
            raise ValueError('Final manifest asset digest readback mismatch')
        readback = self.gh('releases/' + str(self.release_id))
        if (readback.get('draft') is not True or readback.get('immutable') is not False
                or readback.get('id') != self.release_id or readback.get('tag_name') != manifest['version']
                or readback.get('target_commitish') != manifest['backend_sha']):
            raise ValueError('Reserved draft identity changed before publication')
        body = release_body(manifest)
        self.gh('releases/' + str(self.release_id), 'PATCH',
                {'tag_name': manifest['version'], 'target_commitish': manifest['backend_sha'],
                 'draft': False, 'prerelease': False, 'make_latest': 'true', 'body': body})
        result = self.gh('releases/' + str(self.release_id))
        if (result.get('draft') is not False or result.get('immutable') is not True
                or result.get('id') != self.release_id or result.get('body') != body
                or result.get('tag_name') != manifest['version']
                or result.get('target_commitish') != manifest['backend_sha']):
            raise ValueError('Published release readback mismatch')


def build():
    manifest = json.loads(SELECTION.read_text())
    sha = manifest['frontend_sha']
    if not remote.matches(remote.SHA, sha):
        raise ValueError('Invalid frontend SHA')
    home = (WORK / 'build-home').resolve()
    home.mkdir(parents=True)
    env = clean_env(home)
    if not run(['node', '--version'], env=env).startswith('v22.'):
        raise ValueError('Frontend requires Node 22')
    directory = (WORK / 'frontend').resolve()
    run(['git', 'clone', '--filter=blob:none', '--no-checkout', f'https://github.com/{FRONTEND}.git', str(directory)], env=env)
    run(['git', 'fetch', '--depth=1', 'origin', sha], cwd=directory, env=env)
    run(['git', 'checkout', '--detach', sha], cwd=directory, env=env)
    if run(['git', 'rev-parse', 'HEAD'], cwd=directory, env=env).strip() != sha:
        raise ValueError('Frontend checkout mismatch')
    if not (directory / 'package-lock.json').is_file():
        raise ValueError('Frontend package lock is required')
    run(['npm', 'ci', '--no-audit', '--no-fund'], cwd=directory, env=env)
    run(['npm', 'run', 'build'], cwd=directory, env=env)
    dist = directory / 'dist'
    for name in ('_worker.js', '_routes.json', 'functions'):
        if (dist / name).exists():
            raise ValueError('Unexpected executable Pages artifact')
    write_json(dist / 'release.json', release_marker(manifest))
    tools = (WORK / 'tools').resolve()
    tools.mkdir()
    run(['npm', 'install', '--prefix', str(tools), '--no-save', '--package-lock=false',
         '--no-audit', '--no-fund', 'wrangler@' + WRANGLER], env=env)
    run(['node', str(tools / 'node_modules/wrangler/bin/wrangler.js'), '--version'], cwd=tools, env=env)
    write_json(WORK / 'build.json', dict(frontend_sha=sha, dist_digest=dist_digest(dist),
                                        wrangler_version=WRANGLER, node_version=run(['node', '--version'], env=env).strip()))
