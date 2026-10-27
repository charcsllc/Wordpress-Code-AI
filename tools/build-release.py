#!/usr/bin/env python3
"""Build the files for a GitHub release of wordpress-code-ai.

Usage:
    python3 tools/build-release.py [--check] [--tag vVERSION]

Checks that the version in wordpress-code-ai.php (header and constant), the Stable
tag in readme.txt and the newest changelog entry agree, and with --tag that the tag
names the same version. Without --check it then writes three files to dist/:

- wordpress-code-ai.zip: the plugin folder as committed (git archive HEAD);
- update.json: what installed copies read to find the new version
  (see wordpress-code-ai/fork/class-updater.php);
- notes.md: the release notes.

.github/workflows/release.yml runs this when it is started from the Actions tab or
a tag starting with v is pushed, and attaches the files to the release.
"""
import argparse
import datetime
import html
import json
import os
import re
import subprocess
import sys
import zipfile

ROOT = os.path.join(os.path.dirname(os.path.abspath(__file__)), '..')
PLUGIN = os.path.join(ROOT, 'wordpress-code-ai')
DIST = os.path.join(ROOT, 'dist')
# The repository's exact name and case, as in wordpress-code-ai/fork/class-updater.php.
REPOSITORY = 'https://github.com/charcsllc/Wordpress-Code-AI'


def fail(msg):
    sys.exit(f'error: {msg}')


def read(name):
    with open(os.path.join(PLUGIN, name), encoding='utf-8') as fh:
        return fh.read()


def field(text, pattern, where):
    m = re.search(pattern, text, re.M)
    if not m:
        fail(f'{where}: {pattern!r} not found')
    return m.group(1).strip()


def newest_changes(readme):
    """The version and notes of the first entry under == Changelog ==."""
    changelog = readme.split('== Changelog ==', 1)[-1]
    m = re.search(r'^= (\S+)[^\n]*=\n(.*?)(?=^= |\Z)', changelog, re.M | re.S)
    if not m:
        fail('readme.txt: no entry under == Changelog ==')
    notes = [line[2:].strip() for line in m.group(2).splitlines() if line.startswith(('- ', '* '))]
    return m.group(1), notes


def versions():
    main_file = read('wordpress-code-ai.php')
    readme = read('readme.txt')
    found = {
        'the Version header in wordpress-code-ai.php': field(main_file, r'^ \* Version:\s*(\S+)', 'wordpress-code-ai.php'),
        'WORDPRESS_CODE_AI_VERSION in wordpress-code-ai.php': field(
            main_file, r"define\( 'WORDPRESS_CODE_AI_VERSION', '([^']+)' \);", 'wordpress-code-ai.php'),
        'the Stable tag in readme.txt': field(readme, r'^Stable tag:\s*(\S+)', 'readme.txt'),
        'the newest changelog entry in readme.txt': newest_changes(readme)[0],
    }
    if len(set(found.values())) != 1:
        fail('the versions do not agree:\n' + '\n'.join(f'  {v}: {k}' for k, v in found.items()))
    return found.popitem()[1], readme


def to_html(note):
    return re.sub(r'`([^`]+)`', r'<code>\1</code>', html.escape(note, quote=False))


def build(version, readme):
    os.makedirs(DIST, exist_ok=True)
    zip_path = os.path.join(DIST, 'wordpress-code-ai.zip')
    dirty = subprocess.run(['git', 'status', '--porcelain', '--', 'wordpress-code-ai'], cwd=ROOT,
                           capture_output=True, text=True, check=True).stdout
    if dirty:
        print('warning: wordpress-code-ai/ has uncommitted changes, which are not in the ZIP', file=sys.stderr)
    subprocess.run(['git', 'archive', '--format=zip', f'--output={zip_path}', 'HEAD', 'wordpress-code-ai'],
                   cwd=ROOT, check=True)
    with zipfile.ZipFile(zip_path) as z:
        inside = z.read('wordpress-code-ai/wordpress-code-ai.php').decode('utf-8')
    if field(inside, r'^ \* Version:\s*(\S+)', 'the ZIP') != version:
        fail(f'the committed plugin is not version {version}; commit your changes first')

    tag = f'v{version}'
    _, notes = newest_changes(readme)
    manifest = {
        'version': version,
        'package': f'{REPOSITORY}/releases/download/{tag}/wordpress-code-ai.zip',
        'url': f'{REPOSITORY}/releases/tag/{tag}',
        'requires': field(readme, r'^Requires at least:\s*(\S+)', 'readme.txt'),
        'requires_php': field(readme, r'^Requires PHP:\s*(\S+)', 'readme.txt'),
        'tested': field(readme, r'^Tested up to:\s*(\S+)', 'readme.txt'),
        'released': datetime.datetime.now(datetime.timezone.utc).strftime('%Y-%m-%dT%H:%M:%SZ'),
        'changelog': f'<h4>{version}</h4><ul>' + ''.join(f'<li>{to_html(n)}</li>' for n in notes) + '</ul>',
    }
    with open(os.path.join(DIST, 'update.json'), 'w', encoding='utf-8') as fh:
        json.dump(manifest, fh, indent=2, ensure_ascii=False)
        fh.write('\n')
    with open(os.path.join(DIST, 'notes.md'), 'w', encoding='utf-8') as fh:
        fh.write(''.join(f'- {n}\n' for n in notes))
        fh.write('\nTo install, download `wordpress-code-ai.zip` below, not the Source code files, and upload it under '
                 '**Plugins → Add New Plugin → Upload Plugin**. Sites that run 2.3.0.1 or later '
                 'are offered this version on their Plugins screen.\n')
    print(f'dist/ holds wordpress-code-ai.zip, update.json and notes.md for {tag}')


def main():
    ap = argparse.ArgumentParser(description=__doc__.split('\n')[0])
    ap.add_argument('--check', action='store_true', help='only check that the versions agree')
    ap.add_argument('--tag', help='the release tag, which must be v followed by the plugin version')
    args = ap.parse_args()
    version, readme = versions()
    if args.tag is not None and args.tag != f'v{version}':
        fail(f'the tag {args.tag} does not match the plugin version {version}; the tag must be v{version}')
    if args.check:
        print(f'version {version}: the plugin header, constant, Stable tag and changelog agree')
        return
    build(version, readme)


if __name__ == '__main__':
    main()
