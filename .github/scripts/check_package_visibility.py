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
        try:
            if error.code == 404 and phase == 'pre':
                try:
                    missing = json.load(error)
                except (ValueError, OSError):
                    raise RuntimeError(f'{package}: invalid metadata 404 response') from None
                if isinstance(missing, dict) and missing.get('message') in ('Not Found', 'Package not found.'):
                    print(f'{package}: ambiguous 404; only empty bootstrap is permitted')
                    return False
        finally:
            error.close()
        raise RuntimeError(f'{package}: metadata HTTP {error.code}; publication blocked') from None
    except (urllib.error.URLError, OSError, ValueError):
        raise RuntimeError(f'{package}: metadata unavailable or invalid; publication blocked') from None

    if not isinstance(metadata, dict) or (
        metadata.get('name') != package
        or metadata.get('package_type') != 'container'
        or metadata.get('visibility') != 'private'
        or not isinstance(metadata.get('owner'), dict)
        or metadata['owner'].get('login') != 'shinjo15'
        or not isinstance(metadata.get('repository'), dict)
        or metadata['repository'].get('full_name') != REPOSITORY
    ):
        raise RuntimeError(f'{package}: exact private package and source repository not confirmed')
    print(f'{package}: private, linked to {REPOSITORY}')
    return True


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--phase', required=True, choices=('pre', 'post'))
    parser.add_argument('packages', nargs='*', choices=PACKAGES)
    args = parser.parse_args()
    try:
        needs_bootstrap = False
        for package in args.packages or PACKAGES:
            if not check_package(package, os.environ.get('GH_TOKEN', ''), phase=args.phase):
                needs_bootstrap = True
    except RuntimeError as error:
        print(str(error), file=sys.stderr)
        return 1
    return 3 if needs_bootstrap else 0


if __name__ == '__main__':
    sys.exit(main())
