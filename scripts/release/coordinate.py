#!/usr/bin/env python3
"""Manual service-release coordinator. External effects are isolated behind adapters."""
import ipaddress
import json
import hashlib
import os
from pathlib import Path
import sys
import time

sys.path.insert(0, str(Path(__file__).resolve().parent))
import remote_deploy as remote

BACKEND = 'shinjo15/HIBILIO_backend'
FRONTEND = 'shinjo15/HIBILIO_web_frontend'
WRANGLER = '4.157.0'
WORK = Path('.release-work')
SELECTION = WORK / 'selection.json'
MANIFEST = WORK / 'manifest.json'


def write_json(path, value):
    path.parent.mkdir(parents=True, exist_ok=True)
    remote.atomic(path, (json.dumps(value, sort_keys=True, indent=2) + '\n').encode())


def rule_atoms(rules):
    """Normalize AWS grouping without dropping IPv6, browser aliases or port ranges."""
    atoms = set()
    for rule in rules:
        if rule.get('state') != 'open':
            raise ValueError('Unknown firewall rule state')
        for field in ('cidrs', 'ipv6Cidrs', 'cidrListAliases'):
            for source in rule.get(field, []):
                atoms.add((rule['protocol'], rule['fromPort'], rule['toPort'], field, source))
    return atoms


class Firewall:
    def __init__(self, aws, journal):
        self.aws, self.journal = aws, journal

    def read(self):
        return self.aws('get-instance-port-states')['portStates']

    def wait_for(self, expected):
        for attempt in range(6):
            if rule_atoms(self.read()) == expected:
                return
            time.sleep(2)
        raise ValueError('Firewall verification mismatch; no mass restore attempted')

    def open(self, address):
        ip = ipaddress.IPv4Address(address)
        if self.journal.exists():
            raise ValueError('Existing firewall journal requires cleanup first')
        before = self.read()
        covered = any(rule['protocol'] in ('tcp', 'all', '-1')
                      and rule['fromPort'] <= 22 <= rule['toPort']
                      and any(ip in ipaddress.IPv4Network(cidr) for cidr in rule.get('cidrs', []))
                      for rule in before)
        port = dict(fromPort=22, toPort=22, protocol='tcp', cidrs=[str(ip) + '/32'])
        state = dict(before=before, port=port, owned=not covered, cleaned=False)
        write_json(self.journal, state)  # Before call: lost response must still be cleaned.
        expected = rule_atoms(before)
        if not covered:
            self.aws('open-instance-public-ports', port)
            expected.add(('tcp', 22, 22, 'cidrs', str(ip) + '/32'))
        self.wait_for(expected)

    def cleanup(self):
        if not self.journal.exists():
            return
        state = json.loads(self.journal.read_text())
        if state['cleaned']:
            self.wait_for(rule_atoms(state['before']))
            return
        if state['owned']:
            own = ('tcp', 22, 22, 'cidrs', state['port']['cidrs'][0])
            if own in rule_atoms(self.read()):
                self.aws('close-instance-public-ports', state['port'])
        self.wait_for(rule_atoms(state['before']))
        state['cleaned'] = True
        write_json(self.journal, state)


def export_firewall_recovery(journal):
    """Export only owned recovery information; the raw baseline stays runner-local."""
    if not journal.exists():
        return
    state = json.loads(journal.read_text())
    baseline = json.dumps(sorted(rule_atoms(state['before'])), separators=(',', ':')).encode()
    recovery = dict(owned=state['owned'], cleaned=state['cleaned'],
                    baseline_sha256=hashlib.sha256(baseline).hexdigest())
    if state['owned'] is True:
        recovery['owned_runner_cidr'] = state['port']['cidrs'][0]
    write_json(journal.with_name('firewall-recovery.json'), recovery)


def validate_version(version):
    if not remote.matches(remote.VERSION, version):
        raise ValueError('Use strict vMAJOR.MINOR.PATCH without leading zeros or suffix')


def successful_run(runs, repository, sha):
    candidates = [run for run in runs if run.get('head_sha') == sha
                  and run.get('head_branch') == 'main'
                  and run.get('event') in ('push', 'workflow_dispatch')
                  and run.get('head_repository', {}).get('full_name') == repository]
    if not candidates:
        raise ValueError('No trusted exact-main CI run')
    latest = max(candidates, key=lambda run: run['id'])
    if latest.get('status') != 'completed' or latest.get('conclusion') != 'success':
        raise ValueError('Latest exact-main CI run is not successful')
    return latest['id']


def validate_pages(project):
    source = project.get('source', {})
    config = source.get('config', {})
    if (project.get('name') != 'hibilio-web' or project.get('production_branch') != 'main'
            or source.get('type') != 'github' or config.get('production_branch') != 'main'
            or config.get('production_deployments_enabled') is not False
            or config.get('owner') != 'shinjo15' or config.get('repo_name') != 'HIBILIO_web_frontend'):
        raise ValueError('Pages source/production branch/auto-production configuration mismatch')
    # deployments_enabled=False is valid: disables all automatic builds, not direct uploads.


def image_digest(image, service, sha):
    prefix = f'ghcr.io/shinjo15/hibilio-backend-{service}@'
    values = [value[len(prefix):] for value in image.get('RepoDigests', []) if value.startswith(prefix)]
    if (len(values) != 1 or not remote.matches(remote.DIGEST, values[0])
            or image.get('Config', {}).get('Labels', {}).get('org.opencontainers.image.revision') != sha
            or image.get('Architecture') != 'amd64' or image.get('Os') != 'linux'):
        raise ValueError('Registry digest/OCI revision/platform mismatch')
    return values[0]


def coordinate(adapter, path=MANIFEST):
    manifest = adapter.preflight()  # All API/credential/build checks before external writes.
    manifest['phase'] = 'preflight'
    write_json(path, manifest)
    step = 'firewall-open'
    reserved = False
    try:
        adapter.open_firewall()
        step = 'remote-status'
        manifest['previous_backend'] = adapter.status()
        step = 'reserve'
        adapter.reserve(manifest)
        reserved = True
        manifest['phase'] = 'reserved'
        write_json(path, manifest)
        adapter.record(manifest)
        step = 'backend'
        manifest['phase'] = 'backend-started'
        write_json(path, manifest)
        adapter.record(manifest)
        manifest['backend'] = adapter.backend(manifest)
        if manifest['backend'].get('phase') != 'healthy':
            raise ValueError('Backend did not return healthy')
        manifest['phase'] = 'backend-healthy'
        write_json(path, manifest)
        adapter.record(manifest)
        step = 'frontend'
        manifest['phase'] = 'frontend-started'
        write_json(path, manifest)
        adapter.record(manifest)
        manifest['pages_deployment'] = adapter.frontend(manifest)
        write_json(path, manifest)
        adapter.record(manifest)
        step = 'marker-readback'
        adapter.verify_frontend(manifest)
        manifest['frontend_verified'] = True
        manifest['phase'] = 'both-verified'
        write_json(path, manifest)
        adapter.record(manifest)
        step = 'firewall-cleanup'
        adapter.cleanup()
        manifest['firewall_cleaned'] = True
        step = 'publish-release'
        manifest['phase'] = 'complete'
        adapter.publish(manifest)
        write_json(path, manifest)
        return manifest
    except BaseException:
        manifest['phase'] = 'failed'
        manifest['failed_step'] = step
        manifest['recovery'] = 'Fail-stop. No automatic DB or service rollback. See docs/release.md.'
        write_json(path, manifest)
        if reserved:
            try:
                adapter.record(manifest)
            except Exception:
                pass
        raise
    finally:
        adapter.cleanup()  # Also invoked by an independent workflow always() step.


def release_body(manifest):
    return 'Coordinated service release (non-atomic).\n\n```json\n' + json.dumps(manifest, sort_keys=True, indent=2) + '\n```\n'


def release_marker(manifest):
    return {key: manifest[key] for key in ('version', 'backend_sha', 'frontend_sha', 'app_digest', 'nginx_digest')}


def dist_digest(directory):
    digest = hashlib.sha256()
    files = sorted(directory.rglob('*'))
    if not files or not (directory / 'index.html').is_file():
        raise ValueError('Missing frontend build')
    for path in files:
        if path.is_symlink():
            raise ValueError('Symlink in frontend build')
        if path.is_file():
            name = path.relative_to(directory).as_posix().encode()
            data = path.read_bytes()
            digest.update(len(name).to_bytes(8, 'big') + name + len(data).to_bytes(8, 'big') + data)
    return 'sha256:' + digest.hexdigest()


def main():
    import signal
    from services import Services, build
    os.umask(0o077)
    def interrupted(signum, frame):
        raise RuntimeError('Release interrupted; cleanup required')
    signal.signal(signal.SIGTERM, interrupted)
    signal.signal(signal.SIGINT, interrupted)
    try:
        try:
            mode = sys.argv[1] if len(sys.argv) == 2 else ''
            if mode == 'build':
                build()
            elif mode == 'prepare':
                Services().prepare()
            elif mode == 'deploy':
                coordinate(Services())
            elif mode == 'cleanup':
                Services().cleanup()
            elif mode != 'export-recovery':
                raise ValueError('Expected prepare/build/deploy/cleanup/export-recovery')
        finally:
            export_firewall_recovery(WORK / 'firewall.json')
        return 0
    except Exception as error:
        print('Release stopped: ' + type(error).__name__ + '; inspect manifest and docs/release.md', file=sys.stderr)
        return 1


if __name__ == '__main__':
    raise SystemExit(main())
