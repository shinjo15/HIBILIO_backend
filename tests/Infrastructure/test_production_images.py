"""Production image publication contracts; no GitHub credentials required."""
import importlib.util
import json
import copy
import contextlib
import io
import os
from pathlib import Path
import re
import subprocess
import sys
import tempfile
import textwrap
import threading
import unittest
from unittest.mock import patch
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer

ROOT = Path(__file__).resolve().parents[2]
SCRIPT = ROOT / '.github/scripts/check_package_visibility.py'


class VisibilityTest(unittest.TestCase):
    def setUp(self):
        self.assertTrue(SCRIPT.is_file(), 'visibility guard is required before publication')
        spec = importlib.util.spec_from_file_location('visibility_guard', SCRIPT)
        assert spec is not None and spec.loader is not None
        self.guard = importlib.util.module_from_spec(spec)
        spec.loader.exec_module(self.guard)
        self.payload = {
            'name': 'hibilio-backend-app', 'package_type': 'container',
            'visibility': 'private', 'owner': {'login': 'shinjo15'},
            'repository': {'full_name': 'shinjo15/HIBILIO_backend'},
        }
        self.status = 200
        self.body = json.dumps(self.payload).encode()
        self.seen = []
        fixture = self

        class Handler(BaseHTTPRequestHandler):
            def do_GET(self):
                fixture.seen.append((self.path, self.headers.get('Authorization')))
                self.send_response(fixture.status)
                if fixture.status == 302:
                    self.send_header('Location', '/must-not-follow')
                self.end_headers()
                self.wfile.write(fixture.body)

            def log_message(self, format, *args):
                pass

        self.server = ThreadingHTTPServer(('127.0.0.1', 0), Handler)
        self.thread = threading.Thread(target=self.server.serve_forever, daemon=True)
        self.thread.start()

    def tearDown(self):
        self.server.shutdown()
        self.server.server_close()
        self.thread.join()

    def check(self, package='hibilio-backend-app', token='fixture-token', phase='post'):
        with contextlib.redirect_stdout(io.StringIO()):
            return self.guard.check_package(package, token, f'http://127.0.0.1:{self.server.server_port}', phase=phase)

    def test_pre_404_requires_empty_bootstrap_but_never_confirms_private(self):
        self.status = 404
        self.body = b'{"message":"Not Found"}'
        self.assertFalse(self.check(phase='pre'))
        self.body = b'{"message":"Package not found.","documentation_url":"https://docs.github.com/rest/packages/packages#get-a-package-for-a-user"}'
        self.assertFalse(self.check(phase='pre'))
        with self.assertRaises(RuntimeError):
            self.check(phase='post')
        for body in (b'invalid', b'{}', b'[]', b'{"message":"Unknown"}'):
            self.body = body
            with self.assertRaises(RuntimeError):
                self.check(phase='pre')

    def test_private_exact_package_is_accepted(self):
        self.check()
        self.assertEqual(self.seen, [('/users/shinjo15/packages/container/hibilio-backend-app', 'Bearer fixture-token')])

    def test_both_exact_packages_are_accepted(self):
        self.payload['name'] = 'hibilio-backend-nginx'
        self.body = json.dumps(self.payload).encode()
        self.check('hibilio-backend-nginx')

    def test_missing_and_denied_and_unexpected_status_fail_closed(self):
        for status in (201, 204, 302, 401, 403, 404, 429, 500):
            with self.subTest(status=status):
                self.status = status
                self.seen.clear()
                self.body = b'{"message":"Not Found"}'
                with self.assertRaises(RuntimeError):
                    self.check()
                self.assertEqual(len(self.seen), 1, 'must not follow redirects')

    def test_public_unknown_invalid_or_colliding_metadata_fail_closed(self):
        changes = (
            {'visibility': 'public'}, {'visibility': 'internal'}, {'visibility': None},
            {'name': 'other'}, {'package_type': 'npm'}, {'owner': {'login': 'other'}},
            {'repository': {'full_name': 'shinjo15/other'}}, {'repository': None},
            {'owner': None},
        )
        for changeset in changes:
            with self.subTest(changes=changeset):
                data = copy.deepcopy(self.payload)
                data.update(changeset)
                self.body = json.dumps(data).encode()
                with self.assertRaises(RuntimeError):
                    self.check()
        for body in (b'invalid', b'[]', b'null', b'{}', b'\xff'):
            with self.subTest(body=body):
                self.body = body
                with self.assertRaises(RuntimeError):
                    self.check()

    def test_missing_token_or_unexpected_name_never_requests_api(self):
        for package, token in (('other', 'fixture-token'), ('hibilio-backend-app', '')):
            with self.assertRaises(RuntimeError):
                self.check(package, token)
        self.assertEqual(self.seen, [])

    def test_cli_returns_bootstrap_code_only_for_pre_404(self):
        for phase, results, expected in (
            ('pre', [False, True], 3), ('post', [True, True], 0),
            ('post', [RuntimeError('fixture failure')], 1),
        ):
            with self.subTest(phase=phase, expected=expected):
                with patch.object(sys, 'argv', ['guard', '--phase', phase]), \
                     patch.object(self.guard, 'check_package', side_effect=results), \
                     contextlib.redirect_stderr(io.StringIO()):
                    self.assertEqual(self.guard.main(), expected)


class WorkflowTest(unittest.TestCase):
    def setUp(self):
        self.path = ROOT / '.github/workflows/production-images.yml'
        self.assertTrue(self.path.is_file(), 'production image workflow is required')
        self.workflow = self.path.read_text()

    def test_publish_event_boundary(self):
        publish = self.workflow.split('\n  publish:\n', 1)[1]
        match = re.search(r'    if: >-\n(.*?)\n    runs-on:', publish, re.S)
        assert match is not None
        expression = match.group(1).strip()
        for repository in ('shinjo15/HIBILIO_backend', 'other/HIBILIO_backend'):
            for ref in ('refs/heads/main', 'refs/heads/feature', 'refs/tags/main'):
                for event in ('push', 'workflow_dispatch', 'pull_request', 'pull_request_target', 'schedule'):
                    with self.subTest(repository=repository, ref=ref, event=event):
                        actual = expression
                        for key, value in {'repository': repository, 'ref': ref, 'event_name': event}.items():
                            actual = actual.replace(f'github.{key}', repr(value))
                        actual = ' '.join(actual.replace('&&', ' and ').replace('||', ' or ').split())
                        allowed = eval(actual, {'__builtins__': {}}, {})
                        self.assertEqual(allowed, repository == 'shinjo15/HIBILIO_backend' and ref == 'refs/heads/main' and event in ('push', 'workflow_dispatch'))

    def test_least_privilege_and_pinned_actions(self):
        self.assertIn('\npermissions:\n  contents: read\n', self.workflow)
        self.assertEqual(self.workflow.count('packages: write'), 1)
        build, publish = self.workflow.split('\n  publish:\n', 1)
        self.assertNotIn('packages: write', build)
        self.assertNotIn('secrets.', build)
        self.assertNotIn('pull_request_target:', self.workflow)
        actions = re.findall(r'uses: (\S+)', self.workflow)
        self.assertTrue(actions)
        for action in actions:
            self.assertRegex(action, r'^[^@]+@[a-f0-9]{40}$')
        self.assertEqual(self.workflow.count('persist-credentials: false'), len(actions))
        self.assertNotIn(':latest', self.workflow)
        self.assertNotIn('--build-arg', self.workflow)
        self.assertNotIn('--secret', self.workflow)
        self.assertIn('ubuntu-24.04', build)
        self.assertIn('ubuntu-24.04', publish)
        self.assertIn('timeout-minutes:', build)
        self.assertIn('timeout-minutes:', publish)
        self.assertIn('\nconcurrency:', self.workflow)
        self.assertIn('GH_TOKEN: ${{ secrets.GITHUB_TOKEN }}', publish)

    def test_build_contract_and_visibility_order(self):
        self.assertEqual(self.workflow.count('for target in production nginx; do'), 2)
        self.assertEqual(self.workflow.count('--platform linux/amd64'), 3)
        self.assertEqual(self.workflow.count('org.opencontainers.image.source=https://github.com/shinjo15/HIBILIO_backend'), 3)
        self.assertIn('IMAGE_TAG: ${{ github.sha }}', self.workflow)
        publish = self.workflow.split('\n  publish:\n', 1)[1]
        pre = publish.index('python3 .github/scripts/check_package_visibility.py --phase pre "$package"')
        login = publish.index('docker login ghcr.io')
        bootstrap = publish.index('docker push "$bootstrap_image"')
        verified_both = publish.index('run: python3 .github/scripts/check_package_visibility.py --phase post\n')
        push = publish.index('docker push "ghcr.io/shinjo15/$package:$IMAGE_TAG"')
        post = publish.index('python3 .github/scripts/check_package_visibility.py --phase post "$package"', push)
        self.assertLess(login, pre)
        self.assertLess(pre, bootstrap)
        self.assertLess(bootstrap, verified_both)
        self.assertLess(verified_both, push)
        self.assertLess(push, post)
        self.assertIn('bootstrap-${GITHUB_RUN_ID}-${GITHUB_RUN_ATTEMPT}', publish)
        self.assertIn("printf 'FROM scratch\\nLABEL org.opencontainers.image.source=https://github.com/shinjo15/HIBILIO_backend\\n'", publish)
        self.assertIn('--tag "$bootstrap_image" -', publish)

    def test_publication_shell_never_pushes_source_before_both_private_checks(self):
        names = (
            'Confirm private packages or bootstrap with an empty image only',
            'Verify both exact packages before any source image push',
            'Publish and read back each exact package',
        )
        commands = []
        for name in names:
            step = self.workflow.split(f'      - name: {name}\n', 1)[1].split('      - name:', 1)[0]
            run = step.split('        run: ', 1)[1]
            commands.append(textwrap.dedent(run[2:]) if run.startswith('|\n') else run.strip())
        for statuses, success, source_pushes in (
            ([3, 0, 3, 0, 0, 0, 0, 0, 0], True, 2),
            ([0, 0, 0, 0, 0, 0, 0], True, 2),
            ([1], False, 0), ([3, 1], False, 0), ([0, 1], False, 0),
            ([3, 0, 3, 0, 1], False, 0),
        ):
            with self.subTest(statuses=statuses), tempfile.TemporaryDirectory() as directory:
                root = Path(directory)
                state, log = root / 'state', root / 'log'
                state.write_text(json.dumps(statuses))
                python = root / 'python3'
                python.write_text(f'#!{sys.executable}\nimport json, pathlib, sys\np=pathlib.Path({str(state)!r})\na=json.loads(p.read_text())\ncode=a.pop(0)\np.write_text(json.dumps(a))\nsys.exit(code)\n')
                docker = root / 'docker'
                docker.write_text(f'#!{sys.executable}\nimport json, pathlib, sys\nwith pathlib.Path({str(log)!r}).open("a") as f: f.write(json.dumps(sys.argv[1:])+"\\n")\nif sys.argv[1] == "build":\n data=sys.stdin.read()\n assert data == "FROM scratch\\nLABEL org.opencontainers.image.source=https://github.com/shinjo15/HIBILIO_backend\\n"\n assert sys.argv[-1] == "-"\n')
                env = dict(PATH=os.defpath, IMAGE_TAG='a' * 40,
                           GITHUB_RUN_ID='123', GITHUB_RUN_ATTEMPT='1',
                           FIXTURE_PYTHON=sys.executable, FIXTURE_GUARD=str(python),
                           FIXTURE_DOCKER=str(docker), PYTHONDONTWRITEBYTECODE='1')
                functions = 'python3() { "$FIXTURE_PYTHON" "$FIXTURE_GUARD" "$@"; }\ndocker() { "$FIXTURE_PYTHON" "$FIXTURE_DOCKER" "$@"; }\n'
                result = subprocess.run(['bash', '-e', '-o', 'pipefail', '-c', functions + '\n'.join(commands)],
                                        env=env, capture_output=True, text=True)
                self.assertEqual(result.returncode == 0, success, result.stderr)
                calls = [json.loads(line) for line in log.read_text().splitlines()] if log.exists() else []
                pushed = [call[1] for call in calls if call[0] == 'push']
                self.assertEqual(sum(tag.endswith(':' + 'a' * 40) for tag in pushed), source_pushes)
                self.assertEqual(json.loads(state.read_text()), [], 'every scenario must reach its guard fixture')


if __name__ == '__main__':
    unittest.main()
