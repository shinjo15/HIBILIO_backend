"""Fail closed unless exact GHCR package metadata confirms private ownership."""
import argparse
import json
import os
import sys
import urllib.error
import urllib.request

REPOSITORY = 'shinjo15/HIBILIO_backend'
PACKAGES = ('hibilio-backend-app', 'hibilio-backend-nginx')


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None


def check_package(package, token, api_url='https://api.github.com', phase='post'):
    if package not in PACKAGES or not token or phase not in ('pre', 'post'):
        raise RuntimeError('Expected package name and GITHUB_TOKEN are required')
    request = urllib.request.Request(
        f'{api_url}/users/shinjo15/packages/container/{package}',
        headers={
            'Authorization': f'Bearer {token}',
            'Accept': 'application/vnd.github+json',
            'X-GitHub-Api-Version': '2022-11-28',
        },
    )
    try:
        with urllib.request.build_opener(NoRedirect).open(request, timeout=20) as response:
            if response.status != 200:
                raise RuntimeError(f'{package}: unexpected metadata status')
            metadata = json.load(response)
    except urllib.error.HTTPError as error:
        error.close()
        raise RuntimeError(f'{package}: metadata HTTP {error.code}; publication blocked') from None
    except (urllib.error.URLError, OSError, ValueError):
        raise RuntimeError(f'{package}: metadata unavailable or invalid; publication blocked') from None

    if not isinstance(metadata, dict):
        raise RuntimeError('metadata invalid; publication blocked')
    owner = metadata.get('owner')
    repository = metadata.get('repository')
    checks = (
        ('name', metadata.get('name') == package),
        ('package_type', metadata.get('package_type') == 'container'),
        ('visibility', metadata.get('visibility') == 'private'),
        ('owner.login', isinstance(owner, dict) and owner.get('login') == 'shinjo15'),
        ('repository.full_name', isinstance(repository, dict) and repository.get('full_name') == REPOSITORY),
    )
    mismatches = [field for field, matches in checks if not matches]
    if mismatches:
        raise RuntimeError('metadata mismatch: ' + ', '.join(mismatches))
    print(f'{package}: private, linked to {REPOSITORY}')
    return True


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--phase', required=True, choices=('pre', 'post'))
    parser.add_argument('packages', nargs='*', choices=PACKAGES)
    args = parser.parse_args()
    try:
        for package in args.packages or PACKAGES:
            check_package(package, os.environ.get('GH_TOKEN', ''), phase=args.phase)
    except RuntimeError as error:
        print(str(error), file=sys.stderr)
        return 1
    return 0


if __name__ == '__main__':
    sys.exit(main())
