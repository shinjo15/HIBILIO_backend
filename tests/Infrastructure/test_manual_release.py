"""Local simulations only: no AWS, SSH, GHCR or Cloudflare success is implied."""
import importlib.util
import io
import hashlib
import json
import re
import subprocess
import tempfile
import os
import http.server
import threading
from pathlib import Path
import unittest
from unittest.mock import patch

ROOT = Path(__file__).resolve().parents[2]


def load(name):
    spec = importlib.util.spec_from_file_location(name, ROOT / 'scripts/release' / f'{name}.py')
    assert spec is not None and spec.loader is not None
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    return module


class RequestTests(unittest.TestCase):
    def test_strict_release_request(self):
        remote = load('remote_deploy')
        request = dict(operation='deploy', version='v1.0.1', backend_sha='a' * 40,
                       app_digest='sha256:' + 'b' * 64, nginx_digest='sha256:' + 'c' * 64,
                       compose_hashes={'compose.yml': 'd' * 64, 'compose.production.yml': 'e' * 64})
        self.assertEqual(remote.validate_request(request), request)
        for field, value in [('version', 'v1.0.1;id'), ('backend_sha', '../bad'),
                             ('app_digest', 'ghcr.io/evil@sha256:' + 'b' * 64),
                             ('operation', 'rollback')]:
            with self.subTest(field=field):
                with self.assertRaises(ValueError):
                    remote.validate_request(dict(request, **{field: value}))
        for extra in ('command', 'path', 'script', 'env'):
            with self.assertRaises(ValueError):
                remote.validate_request(dict(request, **{extra: '/tmp/evil'}))
        with self.assertRaises(ValueError):
            remote.validate_request(dict(request, compose_hashes={'../../etc/passwd': 'd' * 64}))
        self.assertEqual(remote.validate_request({'operation': 'status'}), {'operation': 'status'})

    def test_forced_command_and_bounded_json(self):
        remote = load('remote_deploy')
        for command in ('id', 'scp -t /tmp/x', '$(touch /tmp/pwned)'):
            with self.assertRaises(ValueError):
                remote.read_request(io.BytesIO(b'{"operation":"status"}'), command)
        for payload in (b'x' * 4097, b'{"operation":"status","operation":"deploy"}',
                        b'{"operation":"status","path":"/tmp/a"}'):
            with self.assertRaises(ValueError):
                remote.read_request(io.BytesIO(payload), '')
        self.assertEqual(remote.read_request(io.BytesIO(b'{"operation":"status"}'), ''),
                         {'operation': 'status'})

    def test_root_checks_main_ancestry_not_just_supplied_hashes(self):
        remote = load('remote_deploy')
        sha = 'a' * 40
        calls = []
        def fetch(url):
            calls.append(url)
            return json.dumps({'status': 'diverged', 'base_commit': {'sha': sha},
                               'merge_base_commit': {'sha': 'b' * 40}}).encode()
        with self.assertRaises(ValueError):
            remote.trusted_compose(sha, {'compose.yml': 'd' * 64,
                                        'compose.production.yml': 'e' * 64}, fetch)
        self.assertEqual(calls, [f'https://api.github.com/repos/shinjo15/HIBILIO_backend/compare/{sha}...main'])

    def test_trusted_main_still_requires_exact_raw_compose_checksums(self):
        remote = load('remote_deploy')
        sha = 'a' * 40
        def fetch(url):
            if '/compare/' in url:
                return json.dumps({'status': 'ahead', 'base_commit': {'sha': sha},
                                   'merge_base_commit': {'sha': sha}}).encode()
            return b'services: {}\n'
        digest = hashlib.sha256(b'services: {}\n').hexdigest()
        hashes = {name: digest for name in remote.FILES}
        self.assertEqual(set(remote.trusted_compose(sha, hashes, fetch)), set(remote.FILES))
        hashes['compose.yml'] = 'f' * 64
        with self.assertRaises(ValueError): remote.trusted_compose(sha, hashes, fetch)

    def test_actual_forced_command_entry_rejects_shell_without_evaluation(self):
        with tempfile.TemporaryDirectory() as directory:
            marker = Path(directory) / 'must-not-exist'
            result = subprocess.run(['python3', '-I', str(ROOT / 'scripts/release/remote_deploy.py')],
                input=b'{"operation":"status"}', capture_output=True,
                env=dict(os.environ, SSH_ORIGINAL_COMMAND='touch ' + str(marker)))
            self.assertEqual(result.returncode, 1)
            self.assertFalse(marker.exists())
            self.assertNotIn(str(marker).encode(), result.stdout + result.stderr)

    def test_compose_env_preserves_every_other_byte(self):
        remote = load('remote_deploy')
        original = b'# unchanged\nAPP_IMAGE=old\nNGINX_IMAGE=old\nOTHER="a=b # c"\n\n'
        updated = remote.image_env(original, 'a' * 40, 'sha256:' + 'b' * 64, 'sha256:' + 'c' * 64)
        self.assertIn(b'OTHER="a=b # c"\n\n', updated)
        self.assertTrue(updated.startswith(b'# unchanged\nAPP_IMAGE=ghcr.io/shinjo15/hibilio-backend-app@sha256:'))
        for invalid in (b'APP_IMAGE=x\n', original + b'APP_IMAGE=duplicate\n',
                        original.replace(b'APP_IMAGE=', b'export APP_IMAGE='),
                        original + b'export APP_IMAGE=shadow\n', original + b' NGINX_IMAGE =shadow\n'):
            with self.assertRaises(ValueError):
                remote.image_env(invalid, 'a' * 40, 'sha256:' + 'b' * 64, 'sha256:' + 'c' * 64)


class RemoteEngineTests(unittest.TestCase):
    """Exercise real filesystem state transitions with simulated Docker and GitHub."""
    def setUp(self):
        self.remote = load('remote_deploy')
        self.temp = tempfile.TemporaryDirectory()
        self.addCleanup(self.temp.cleanup)
        self.root = Path(self.temp.name)
        self.env = self.root / 'compose.env'
        self.env.write_bytes(b'APP_IMAGE=old\nNGINX_IMAGE=old\nKEEP=exact value\n')
        self.calls = []
        self.fail_command = ''
        self.files = {'compose.yml': b'services: {}\n', 'compose.production.yml': b'services: {}\n# production\n'}
        self.request = dict(operation='deploy', version='v1.0.1', backend_sha='a' * 40,
                            app_digest='sha256:' + 'b' * 64, nginx_digest='sha256:' + 'c' * 64,
                            compose_hashes={k: hashlib.sha256(v).hexdigest() for k, v in self.files.items()})
        self.old = dict(self.remote.BOOTSTRAP, compose_files=['/opt/hibilio/old/compose.yml',
                                                            '/opt/hibilio/old/compose.production.yml'])
        def run(args):
            self.calls.append(args)
            if self.fail_command and self.fail_command in args:
                raise RuntimeError('simulated external failure')
            if 'inspect' in args:
                return json.dumps({'labels': {'org.opencontainers.image.revision': 'a' * 40},
                                   'arch': 'amd64', 'os': 'linux'})
            return ''
        self.engine = self.remote.Deployer(self.root, self.env, run=run,
                                           source=lambda sha, hashes: self.files,
                                           discover=lambda: dict(self.old),
                                           verify=lambda state: None)

    def test_success_uses_new_digest_migration_and_no_redis_restart(self):
        state = self.engine.deploy(self.request)
        self.assertEqual(state['phase'], 'healthy')
        self.assertEqual(state['previous'], self.old)
        self.assertTrue(state['migration_started'])
        self.assertTrue(state['migration_applied'])
        self.assertEqual(json.loads((self.root / 'current.json').read_text())['backend_sha'], 'a' * 40)
        self.assertEqual((self.root / 'deployments/v1.0.1.compose.env').read_bytes(),
                         b'APP_IMAGE=old\nNGINX_IMAGE=old\nKEEP=exact value\n')
        self.assertEqual((self.root / 'deployments/v1.0.1.compose.env').stat().st_mode & 0o777, 0o600)
        self.assertIn(b'KEEP=exact value\n', self.env.read_bytes())
        migrate = next(args for args in self.calls if 'migrate' in args)
        self.assertIn('--no-deps', migrate)
        self.assertIn('--pull', migrate)
        self.assertIn('never', migrate)
        self.assertIn(str(self.root / ('releases/' + 'a' * 40) / 'compose.yml'), migrate)
        up = next(args for args in self.calls if 'up' in args)
        self.assertEqual(up[-2:], ['app', 'nginx'])
        self.assertIn('--no-deps', up)
        self.assertIn('--wait', up)
        self.assertFalse(any('redis' in args or 'db:seed' in args or 'down' in args for args in self.calls))
        with self.assertRaises(ValueError):
            self.engine.deploy(self.request)

    def test_failed_migration_is_durable_and_no_automatic_rollback(self):
        self.fail_command = 'migrate'
        with self.assertRaises(RuntimeError):
            self.engine.deploy(self.request)
        record = json.loads((self.root / 'deployments/v1.0.1.json').read_text())
        self.assertEqual(record['phase'], 'failed')
        self.assertTrue(record['migration_started'])
        self.assertFalse(record['migration_applied'])
        self.assertFalse((self.root / 'current.json').exists())
        self.assertFalse(any('up' in args for args in self.calls))
        with self.assertRaises(ValueError):
            self.engine.deploy(dict(self.request, version='v1.0.2'))

    def test_untrusted_compose_fails_before_disk_or_docker_writes(self):
        def reject(*args):
            raise ValueError('untrusted SHA')
        self.engine.source = reject
        with self.assertRaises(ValueError):
            self.engine.deploy(self.request)
        self.assertEqual(self.calls, [])
        self.assertFalse((self.root / 'deployments').exists())

    def test_existing_release_directory_mismatch_is_not_overwritten(self):
        directory = self.root / 'releases' / ('a' * 40)
        directory.mkdir(parents=True)
        (directory / 'compose.yml').write_text('malicious or stale')
        with self.assertRaises(ValueError):
            self.engine.deploy(self.request)
        self.assertEqual((directory / 'compose.yml').read_text(), 'malicious or stale')

    def test_image_revision_mismatch_never_updates_env_or_runs_migration(self):
        original_run = self.engine.run
        def wrong_revision(args):
            result = original_run(args)
            if 'inspect' in args:
                return result.replace('a' * 40, 'f' * 40)
            return result
        self.engine.run = wrong_revision
        with self.assertRaises(ValueError): self.engine.deploy(self.request)
        self.assertEqual(self.env.read_bytes(), b'APP_IMAGE=old\nNGINX_IMAGE=old\nKEEP=exact value\n')
        self.assertFalse(any('migrate' in args for args in self.calls))

    def test_only_explicit_root_reconciliation_unblocks_future_version(self):
        self.fail_command = 'migrate'
        with self.assertRaises(RuntimeError): self.engine.deploy(self.request)
        record = self.root / 'deployments/v1.0.1.json'
        failed = json.loads(record.read_text())
        # Simulation of an administrator AFTER separately verifying DB compatibility/health.
        failed.update(phase='recovered', recovery='simulation: previous state manually verified')
        record.write_text(json.dumps(failed))
        (self.root / 'current.json').write_text(json.dumps(self.old))
        self.fail_command = ''
        with self.assertRaises(ValueError): self.engine.deploy(self.request)
        self.assertEqual(self.engine.deploy(dict(self.request, version='v1.0.2'))['phase'], 'healthy')


class FirewallTests(unittest.TestCase):
    def setUp(self):
        self.module = load('coordinate')
        self.temp = tempfile.TemporaryDirectory()
        self.addCleanup(self.temp.cleanup)
        self.journal = Path(self.temp.name) / 'firewall.json'
        self.rules: list[dict] = [dict(fromPort=22, toPort=22, protocol='tcp', state='open',
                           cidrs=['198.51.100.7/32'], ipv6Cidrs=[], cidrListAliases=['lightsail-connect']),
                      dict(fromPort=443, toPort=443, protocol='tcp', state='open',
                           cidrs=['173.245.48.0/20'], ipv6Cidrs=['2400:cb00::/32'], cidrListAliases=[])]
        self.calls = []
        def aws(action, port=None):
            self.calls.append((action, port))
            if action == 'get-instance-port-states':
                return {'portStates': json.loads(json.dumps(self.rules))}
            rule = self.rules[0]
            if action == 'open-instance-public-ports':
                assert port is not None
                rule['cidrs'].extend(port['cidrs'])
            elif action == 'close-instance-public-ports':
                assert port is not None
                rule['cidrs'] = [cidr for cidr in rule['cidrs'] if cidr not in port['cidrs']]
            return {}
        self.firewall = self.module.Firewall(aws, self.journal)

    def test_owned_rule_removed_preserving_browser_ipv6_and_cloudflare(self):
        original = json.loads(json.dumps(self.rules))
        self.firewall.open('203.0.113.8')
        self.assertIn('203.0.113.8/32', self.rules[0]['cidrs'])
        self.firewall.cleanup()
        self.assertEqual(self.rules, original)
        self.firewall.cleanup()  # Always step is idempotent.
        writes = [(action, port) for action, port in self.calls if action != 'get-instance-port-states']
        self.assertEqual([x[0] for x in writes], ['open-instance-public-ports', 'close-instance-public-ports'])
        self.assertEqual(writes[-1][1], {'fromPort': 22, 'toPort': 22, 'protocol': 'tcp',
                                       'cidrs': ['203.0.113.8/32']})

    def test_preexisting_rule_is_not_owned_or_closed(self):
        self.firewall.open('198.51.100.7')
        self.firewall.cleanup()
        self.assertFalse(any(action != 'get-instance-port-states' for action, _ in self.calls))

    def test_interrupted_open_is_cleaned_from_durable_journal(self):
        original = self.firewall.aws
        def lost_response(action, port=None):
            result = original(action, port)
            if action == 'open-instance-public-ports':
                raise RuntimeError('simulated network timeout after acceptance')
            return result
        self.firewall.aws = lost_response
        with self.assertRaises(RuntimeError):
            self.firewall.open('203.0.113.8')
        self.firewall.cleanup()
        self.assertEqual(self.rules[0]['cidrs'], ['198.51.100.7/32'])

    def test_unrelated_change_is_reported_not_reverted(self):
        self.firewall.open('203.0.113.8')
        self.rules[1]['cidrs'].append('192.0.2.0/24')
        with self.assertRaises(ValueError):
            self.firewall.cleanup()
        self.assertIn('192.0.2.0/24', self.rules[1]['cidrs'])
        self.assertNotIn('203.0.113.8/32', self.rules[0]['cidrs'])

    def test_recovery_export_never_exposes_original_sources_or_unowned_runner(self):
        self.rules[0]['ipv6Cidrs'] = ['2001:db8:abcd:1234::7/128']
        baseline = json.loads(json.dumps(self.rules))
        expected_hash = hashlib.sha256(json.dumps(sorted(self.module.rule_atoms(baseline)),
                                                 separators=(',', ':')).encode()).hexdigest()
        recovery = self.journal.with_name('firewall-recovery.json')
        for owned, cleaned in ((True, False), (True, True), (False, False), (False, True)):
            with self.subTest(owned=owned, cleaned=cleaned):
                runner = '203.0.113.8/32' if owned else '198.51.100.7/32'
                state = dict(before=baseline, owned=owned, cleaned=cleaned,
                             port=dict(fromPort=22, toPort=22, protocol='tcp', cidrs=[runner]))
                self.module.write_json(self.journal, state)
                raw = self.journal.read_bytes()
                self.module.export_firewall_recovery(self.journal)
                expected = dict(owned=owned, cleaned=cleaned, baseline_sha256=expected_hash)
                if owned:
                    expected['owned_runner_cidr'] = runner
                self.assertEqual(json.loads(recovery.read_text()), expected)
                for private in ('198.51.100.7', '2001:db8:abcd:1234::7',
                                'lightsail-connect', '173.245.48.0', '2400:cb00::'):
                    self.assertNotIn(private, recovery.read_text())
                self.assertEqual(self.journal.read_bytes(), raw)
                # Group/order differences must not change the normalized baseline hash.
                state['before'] = list(reversed(baseline)) + baseline
                self.module.write_json(self.journal, state)
                self.module.export_firewall_recovery(self.journal)
                self.assertEqual(json.loads(recovery.read_text()), expected)

    def test_main_exports_recovery_after_cleanup_failure_without_hiding_failure(self):
        self.firewall.open('203.0.113.8')
        services = __import__('services')
        for mode in ('cleanup', 'deploy'):
            with self.subTest(mode=mode), patch.object(self.module, 'WORK', self.journal.parent), \
                 patch.object(self.module.sys, 'argv', ['coordinate.py', mode]), \
                 patch.object(services, 'Services') as adapter, \
                 patch.object(self.module, 'coordinate', side_effect=lambda service: service.cleanup()), \
                 patch.object(self.module.sys, 'stderr', io.StringIO()):
                adapter.return_value.cleanup.side_effect = RuntimeError('simulated cleanup failure')
                self.assertEqual(self.module.main(), 1)
                recovery = self.journal.with_name('firewall-recovery.json')
                self.assertTrue(recovery.exists())
                self.assertFalse(json.loads(recovery.read_text())['cleaned'])
                self.assertNotIn('198.51.100.7', recovery.read_text())
                recovery.unlink()

    def test_independent_export_needs_no_credentials_and_missing_journal_is_safe(self):
        services = __import__('services')
        with patch.object(self.module, 'WORK', self.journal.parent), \
             patch.object(self.module.sys, 'argv', ['coordinate.py', 'export-recovery']), \
             patch.object(services, 'Services') as adapter, \
             patch.object(self.module.sys, 'stderr', io.StringIO()):
            self.assertEqual(self.module.main(), 0)
            self.assertFalse(self.journal.with_name('firewall-recovery.json').exists())
            self.firewall.open('203.0.113.8')
            self.assertEqual(self.module.main(), 0)
            self.assertTrue(self.journal.with_name('firewall-recovery.json').exists())
            adapter.assert_not_called()


class CoordinatorTests(unittest.TestCase):
    """Full coordinator control flow, with explicit fake external adapters."""
    def setUp(self):
        self.module = load('coordinate')
        self.temp = tempfile.TemporaryDirectory()
        self.addCleanup(self.temp.cleanup)
        self.path = Path(self.temp.name) / 'manifest.json'
        self.events = []
        self.failure = ''
        outer = self
        class Fake:
            def call(self, event):
                outer.events.append(event)
                if outer.failure == event:
                    raise ValueError('simulation: ' + event)
            def preflight(self):
                self.call('preflight')
                return {'version': 'v1.0.1', 'backend_sha': 'a' * 40, 'frontend_sha': 'b' * 40}
            def open_firewall(self): self.call('firewall-open')
            def cleanup(self): self.call('firewall-cleanup')
            def status(self):
                self.call('remote-status')
                return {'backend_sha': 'c' * 40}
            def reserve(self, manifest): self.call('reserve')
            def record(self, manifest): self.call('record')
            def backend(self, manifest):
                self.call('backend')
                return {'phase': 'healthy', 'backend_sha': manifest['backend_sha']}
            def frontend(self, manifest):
                self.call('frontend')
                return {'id': 'simulation-pages-id'}
            def verify_frontend(self, manifest): self.call('marker-readback')
            def publish(self, manifest): self.call('publish-release')
        self.adapter = Fake()

    def test_missing_credentials_fail_before_any_external_write(self):
        self.failure = 'preflight'
        with self.assertRaises(ValueError):
            self.module.coordinate(self.adapter, self.path)
        self.assertEqual(self.events, ['preflight'])

    def test_backend_failure_prevents_frontend_and_release_publication(self):
        self.failure = 'backend'
        with self.assertRaises(ValueError):
            self.module.coordinate(self.adapter, self.path)
        self.assertNotIn('frontend', self.events)
        self.assertNotIn('publish-release', self.events)
        self.assertIn('firewall-cleanup', self.events)
        state = json.loads(self.path.read_text())
        self.assertEqual(state['phase'], 'failed')
        self.assertEqual(state['failed_step'], 'backend')
        self.assertIn('previous_backend', state)

    def test_marker_or_frontend_failure_keeps_release_draft(self):
        for step in ('frontend', 'marker-readback', 'firewall-cleanup'):
            with self.subTest(step=step):
                self.failure = step
                self.events.clear()
                with self.assertRaises(ValueError):
                    self.module.coordinate(self.adapter, self.path)
                self.assertNotIn('publish-release', self.events)
                state = json.loads(self.path.read_text())
                self.assertEqual(state['backend']['phase'], 'healthy')
                self.assertEqual(state['phase'], 'failed')

    def test_completion_only_after_both_and_firewall_cleanup(self):
        result = self.module.coordinate(self.adapter, self.path)
        self.assertEqual(result['phase'], 'complete')
        self.assertLess(self.events.index('backend'), self.events.index('frontend'))
        self.assertLess(self.events.index('marker-readback'), self.events.index('publish-release'))
        self.assertLess(self.events.index('firewall-cleanup'), self.events.index('publish-release'))
        self.assertEqual(json.loads(self.path.read_text())['phase'], 'complete')

    def test_strict_version_and_main_ci_gate(self):
        for version in ('1.0.1', 'v01.0.1', 'v1.0.1-rc', 'v1.0.1\n', 'v1.0.1;id'):
            with self.assertRaises(ValueError):
                self.module.validate_version(version)
        self.module.validate_version('v1.0.1')
        good = dict(id=9, head_sha='a' * 40, head_branch='main', event='push', status='completed',
                    conclusion='success', head_repository={'full_name': 'shinjo15/HIBILIO_backend'})
        self.assertEqual(self.module.successful_run([good], 'shinjo15/HIBILIO_backend', 'a' * 40), 9)
        for field, bad in [('conclusion', 'failure'), ('event', 'pull_request'),
                           ('head_branch', 'feature/x'), ('head_sha', 'b' * 40), ('status', 'queued')]:
            with self.subTest(field=field), self.assertRaises(ValueError):
                self.module.successful_run([dict(good, **{field: bad})], 'shinjo15/HIBILIO_backend', 'a' * 40)
        with self.assertRaises(ValueError):
            self.module.successful_run([good, dict(good, id=10, conclusion='failure')],
                                       'shinjo15/HIBILIO_backend', 'a' * 40)

    def test_pages_actual_schema_and_digest_revision_gate(self):
        project = {'name': 'hibilio-web', 'production_branch': 'main', 'source': {'type': 'github',
                   'config': {'production_branch': 'main', 'production_deployments_enabled': False,
                              'deployments_enabled': False, 'owner': 'shinjo15', 'repo_name': 'HIBILIO_web_frontend'}}}
        self.module.validate_pages(project)
        project['source']['config']['production_deployments_enabled'] = True
        with self.assertRaises(ValueError): self.module.validate_pages(project)
        image = {'RepoDigests': ['ghcr.io/shinjo15/hibilio-backend-app@sha256:' + 'b' * 64],
                 'Config': {'Labels': {'org.opencontainers.image.revision': 'a' * 40}},
                 'Architecture': 'amd64', 'Os': 'linux'}
        self.assertEqual(self.module.image_digest(image, 'app', 'a' * 40), 'sha256:' + 'b' * 64)
        with self.assertRaises(ValueError): self.module.image_digest(image, 'app', 'c' * 40)
        image['RepoDigests'] = ['ghcr.io/evil/image@sha256:' + 'b' * 64]
        with self.assertRaises(ValueError): self.module.image_digest(image, 'app', 'a' * 40)


class AdapterTests(unittest.TestCase):
    def setUp(self):
        load('coordinate')
        self.module = load('services')
        self.services = self.module.Services()

    def test_actual_adapter_missing_credentials_fails_without_api_or_commands(self):
        self.services.env = {}
        with patch.object(self.module, 'run') as command, patch.object(self.module, 'request_json') as api:
            with self.assertRaises(ValueError):
                self.services.prepare()
            command.assert_not_called()
            api.assert_not_called()

    def test_existing_version_and_permission_error_fail_closed(self):
        self.services.gh = lambda *args: {'object': {'sha': 'a' * 40}}
        with self.assertRaises(ValueError): self.services.absent_version('v1.0.1')
        def forbidden(*args): raise self.module.ApiError(403)
        self.services.gh = forbidden
        with self.assertRaises(self.module.ApiError): self.services.absent_version('v1.0.1')

    def test_ci_required_job_must_succeed_and_be_complete(self):
        run = dict(id=9, head_sha='a' * 40, head_branch='main', event='push', status='completed',
                   conclusion='success', head_repository={'full_name': 'shinjo15/HIBILIO_backend'})
        def api(path, **kwargs):
            if '/runs?' in path:
                return {'workflow_runs': [run]}
            return {'total_count': 1, 'jobs': [{'name': 'publish', 'conclusion': 'skipped'}]}
        self.services.gh = api
        with self.assertRaises(ValueError):
            self.services.ci('shinjo15/HIBILIO_backend', 'a' * 40, 'production-images.yml', 'publish')

    def test_ssh_never_sends_command_and_pins_host_key(self):
        self.services.env = dict(DEPLOY_SSH_KEY='simulation-key', DEPLOY_KNOWN_HOSTS='simulation-host-key',
                                 DEPLOY_USER='hibilio-deploy', DEPLOY_HOST='192.0.2.5')
        calls = []
        def command(args, **kwargs):
            calls.append((args, kwargs))
            return '{"phase":"bootstrap"}'
        with patch.object(self.module, 'run', command):
            self.assertEqual(self.services.status(), {'phase': 'bootstrap'})
        args, kwargs = calls[0]
        self.assertEqual(args[-1], 'hibilio-deploy@192.0.2.5')
        self.assertIn('StrictHostKeyChecking=yes', args)
        self.assertIn('-T', args)
        self.assertEqual(json.loads(kwargs['data']), {'operation': 'status'})
        self.assertNotIn('simulation-key', ' '.join(args))

    def test_final_manifest_asset_is_verified_before_immutable_release_publication(self):
        manifest = dict(version='v1.0.1', backend_sha='a' * 40, phase='complete', backend={'phase': 'healthy'},
                        frontend_verified=True, firewall_cleaned=True)
        self.services.release_id = 123
        self.services.env = {'GH_TOKEN': 'simulation-token'}
        asset = dict(id=42, name='release.json', digest='sha256:' + hashlib.sha256(json.dumps(manifest).encode()).hexdigest())
        events = []
        published = False
        def gh(path, method='GET', payload=None):
            nonlocal published
            events.append((path, method))
            if path == 'releases/assets/42': return asset
            if path == 'git/ref/tags/v1.0.1': return {'object': {'type': 'commit', 'sha': 'a' * 40}}
            if method == 'PATCH':
                published = True
                return {}
            return dict(draft=not published, immutable=published, tag_name='v1.0.1',
                        body=self.module.release_body(manifest))
        self.services.gh = gh
        with patch.object(self.module, 'request_json', return_value=asset) as upload:
            self.services.publish(manifest)
            upload.assert_called_once()
        self.assertLess(events.index(('releases/assets/42', 'GET')), events.index(('releases/123', 'PATCH')))
        with self.assertRaises(ValueError): self.services.publish(dict(manifest, frontend_verified=False))

    def test_moved_tag_prevents_final_publication(self):
        manifest = dict(version='v1.0.1', backend_sha='a' * 40, phase='complete', backend={'phase': 'healthy'},
                        frontend_verified=True, firewall_cleaned=True)
        self.services.release_id = 123
        self.services.env = {'GH_TOKEN': 'simulation-token'}
        self.services.gh = lambda path, *args: {'object': {'sha': 'b' * 40, 'type': 'commit'}, 'draft': True}
        with patch.object(self.module, 'request_json') as upload:
            with self.assertRaises(ValueError): self.services.publish(manifest)
            upload.assert_not_called()

    def test_actual_preflight_ci_or_digest_mismatch_happens_before_firewall(self):
        coordinate = load('coordinate')
        with tempfile.TemporaryDirectory() as directory:
            selection = Path(directory) / 'selection.json'
            selection.write_text(json.dumps(dict(version='v1.0.1', backend_sha='a' * 40,
                frontend_sha='b' * 40, app_digest='sha256:' + 'c' * 64, nginx_digest='sha256:' + 'd' * 64)))
            self.services.credentials = lambda: None
            self.services.env = {'RELEASE_VERSION': 'v1.0.1'}
            self.services.absent_version = lambda version: None
            self.services.gh = lambda path, **kwargs: dict(status='ahead',
                base_commit={'sha': path.split('/')[1].split('...')[0]},
                merge_base_commit={'sha': path.split('/')[1].split('...')[0]})
            for failure in ('ci', 'digest'):
                with self.subTest(failure=failure), patch.object(self.module, 'SELECTION', selection), \
                     patch.object(self.services, 'open_firewall') as firewall, \
                     patch.object(self.services, 'reserve') as reserve:
                    def ci(*args):
                        if failure == 'ci': raise ValueError('simulation: exact CI failure')
                        return 1
                    self.services.ci = ci
                    self.services.images = lambda sha: {'app_digest': 'sha256:' + 'e' * 64,
                                                       'nginx_digest': 'sha256:' + 'd' * 64}
                    with self.assertRaises(ValueError): coordinate.coordinate(self.services, Path(directory) / 'manifest.json')
                    firewall.assert_not_called()
                    reserve.assert_not_called()


class HttpFixtureTests(unittest.TestCase):
    """Local HTTP fixture, not a response from any real cloud account."""
    def test_adapter_rejects_redirect_and_never_prints_error_response_secrets(self):
        load('coordinate')
        module = load('services')
        received = []
        class Handler(http.server.BaseHTTPRequestHandler):
            def log_message(self, format, *args): pass
            def do_GET(self):
                received.append(self.path)
                if self.path == '/redirect':
                    self.send_response(302)
                    self.send_header('Location', '/must-not-follow')
                elif self.path == '/denied': self.send_response(403)
                else: self.send_response(200)
                self.end_headers()
                self.wfile.write(b'{"simulation_secret":"must-not-appear-in-diagnostics"}')
        server = http.server.ThreadingHTTPServer(('127.0.0.1', 0), Handler)
        thread = threading.Thread(target=server.serve_forever, daemon=True)
        thread.start()
        try:
            base = 'http://127.0.0.1:' + str(server.server_port)
            self.assertIn('simulation_secret', module.request_json(base + '/ok'))
            for path, status in [('/redirect', 302), ('/denied', 403)]:
                with self.assertRaises(module.ApiError) as error:
                    module.request_json(base + path, 'simulation-token')
                self.assertEqual(error.exception.status, status)
                self.assertNotIn('must-not-appear', str(error.exception))
            self.assertNotIn('/must-not-follow', received)
        finally:
            server.shutdown()
            server.server_close()
            thread.join()


class WorkflowTests(unittest.TestCase):
    def test_only_sanitized_firewall_recovery_is_uploaded_after_independent_always_export(self):
        workflow = (ROOT / '.github/workflows/manual-release.yml').read_text()
        export = workflow.split('run: python3 -B scripts/release/coordinate.py export-recovery')[0]
        self.assertNotEqual(export, workflow)
        self.assertIn('if: always()', export.rsplit('- name:', 1)[1])
        upload = workflow.split('uses: actions/upload-artifact@', 1)[1]
        self.assertIn('.release-work/firewall-recovery.json', upload)
        paths = upload.split('path: |', 1)[1].split('include-hidden-files:', 1)[0].split()
        self.assertNotIn('.release-work/firewall.json', [path for path in paths if not path.startswith('!')])
        self.assertIn('!.release-work/firewall.json', paths)

    def test_manual_main_environment_pinned_actions_and_cleanup_wiring(self):
        workflow = (ROOT / '.github/workflows/manual-release.yml').read_text()
        triggers = workflow.split('permissions:', 1)[0]
        self.assertIn('workflow_dispatch:', triggers)
        self.assertNotRegex(triggers, r'\n  (?:push|pull_request|schedule):')
        self.assertIn('environment: production', workflow)
        self.assertIn("github.ref == 'refs/heads/main'", workflow)
        self.assertIn('cancel-in-progress: false', workflow)
        self.assertNotIn('self-hosted', workflow)
        for action in re.findall(r'uses: (\S+)', workflow):
            self.assertRegex(action, r'@[0-9a-f]{40}$')
        self.assertLess(workflow.index('coordinate.py build'), workflow.index('id: aws'))
        self.assertIn("if: always() && steps.aws.outcome == 'success'", workflow)
        self.assertIn('coordinate.py cleanup', workflow)
        for line in workflow.splitlines():
            if line.lstrip().startswith('run:'):
                self.assertNotIn('${{', line)

    def test_workflow_shell_syntax(self):
        # Test only authored run blocks; actionlint separately parses the full YAML/schema.
        for filename in ('manual-release.yml', 'ci.yml'):
            lines = (ROOT / '.github/workflows' / filename).read_text().splitlines()
            for index, line in enumerate(lines):
                if not line.lstrip().startswith('run:'):
                    continue
                indent = len(line) - len(line.lstrip())
                script = line.split('run:', 1)[1].strip()
                if script == '|':
                    body = []
                    for following in lines[index + 1:]:
                        if following.strip() and len(following) - len(following.lstrip()) <= indent:
                            break
                        body.append(following[indent + 2:])
                    script = '\n'.join(body)
                result = subprocess.run(['bash', '-n'], input=script.encode(), capture_output=True)
                self.assertEqual(result.returncode, 0, result.stderr.decode())


if __name__ == '__main__':
    unittest.main()
