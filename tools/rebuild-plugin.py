#!/usr/bin/env python3
"""Rebuild wordpress-code-ai/ from the Easy MCP AI release it is based on.

Usage:
    python3 tools/rebuild-plugin.py [--upstream VERSION | --zip FILE] [--check]

wordpress-code-ai/ is the output of tools/rename-upstream.py run on an Easy MCP AI
release, plus wordpress-code-ai/fork/ (the code that exists only here) and the
bundled skills release. This script makes that output again:

1. It unzips the Easy MCP AI release named in the credit at the top of
   wordpress-code-ai/wordpress-code-ai.php, or the one --upstream or --zip names.
   Releases are downloaded from downloads.wordpress.org.
2. It copies wordpress-code-ai/fork/ into it and runs tools/rename-upstream.py.
3. It replaces wordpress-code-ai/ with the result, then bundles the newest skills
   release (tools/update-skills-mirror.py --from-plugin wordpress-code-ai --bundle).

With --check nothing is written: the result, with the skills release that
wordpress-code-ai/ bundles now, is compared with wordpress-code-ai/ and every
difference is listed. CI runs it, so a change made directly in wordpress-code-ai/,
outside fork/, fails until it is also made in tools/rename-upstream.py.
"""
import argparse
import os
import re
import shutil
import subprocess
import sys
import tempfile
import urllib.request
import zipfile

TOOLS = os.path.dirname(os.path.abspath(__file__))
PLUGIN = os.path.join(TOOLS, '..', 'wordpress-code-ai')
DOWNLOAD = 'https://downloads.wordpress.org/plugin/easy-mcp-ai.{}.zip'
SKILLS = ('assets/skills/release.json', 'assets/skills/release.sig')


def fail(msg):
    sys.exit(f'error: {msg}')


def current_upstream():
    with open(os.path.join(PLUGIN, 'wordpress-code-ai.php'), encoding='utf-8') as fh:
        m = re.search(r'is a modified version of Easy MCP AI (\S+) by EasyMCPAI', fh.read())
    if not m:
        fail('the credit naming the Easy MCP AI version was not found in wordpress-code-ai/wordpress-code-ai.php')
    return m.group(1)


def download(version, path):
    url = DOWNLOAD.format(version)
    print(f'downloading {url}')
    try:
        with urllib.request.urlopen(url, timeout=60) as resp, open(path, 'wb') as fh:
            shutil.copyfileobj(resp, fh)
    except OSError as e:
        fail(f'could not download {url}: {e}')


def files(root):
    found = set()
    for d, _, names in os.walk(root):
        for name in names:
            found.add(os.path.relpath(os.path.join(d, name), root).replace(os.sep, '/'))
    return found


def differences(built, current):
    a, b = files(built), files(current)
    out = [f'only in the rebuilt plugin: {p}' for p in sorted(a - b)]
    out += [f'only in wordpress-code-ai/: {p}' for p in sorted(b - a)]
    for p in sorted(a & b):
        with open(os.path.join(built, p), 'rb') as x, open(os.path.join(current, p), 'rb') as y:
            if x.read() != y.read():
                out.append(f'differs: {p}')
    return out


def main():
    ap = argparse.ArgumentParser(description=__doc__.split('\n')[0])
    source = ap.add_mutually_exclusive_group()
    source.add_argument('--upstream', metavar='VERSION', help='Easy MCP AI version to download')
    source.add_argument('--zip', metavar='FILE', help='Easy MCP AI ZIP to use instead of downloading one')
    ap.add_argument('--check', action='store_true', help='compare with wordpress-code-ai/ instead of replacing it')
    args = ap.parse_args()

    with tempfile.TemporaryDirectory() as tmp:
        zip_path = os.path.abspath(args.zip) if args.zip else os.path.join(tmp, 'easy-mcp-ai.zip')
        if not args.zip:
            download(args.upstream or current_upstream(), zip_path)
        with zipfile.ZipFile(zip_path) as z:
            z.extractall(os.path.join(tmp, 'unzipped'))
        built = os.path.join(tmp, 'unzipped', 'easy-mcp-ai')
        if not os.path.isfile(os.path.join(built, 'easy-mcp-ai.php')):
            fail('the ZIP has no easy-mcp-ai/easy-mcp-ai.php; is it an Easy MCP AI release?')
        shutil.copytree(os.path.join(PLUGIN, 'fork'), os.path.join(built, 'fork'))
        subprocess.run([sys.executable, os.path.join(TOOLS, 'rename-upstream.py'), built], check=True)

        if args.check:
            for path in SKILLS:
                shutil.copyfile(os.path.join(PLUGIN, path), os.path.join(built, path))
            found = differences(built, PLUGIN)
            if found:
                print('\n'.join(found))
                fail(f'{len(found)} differences: make each change to Easy MCP AI files in tools/rename-upstream.py, '
                     f'then run python3 tools/rebuild-plugin.py')
            print('wordpress-code-ai/ is exactly what tools/rebuild-plugin.py builds')
            return

        shutil.rmtree(PLUGIN)
        shutil.copytree(built, PLUGIN)
    subprocess.run([sys.executable, os.path.join(TOOLS, 'update-skills-mirror.py'),
                    '--from-plugin', PLUGIN, '--bundle'], check=True)
    print('wordpress-code-ai/ rebuilt; review the changes with git diff')


if __name__ == '__main__':
    main()
