#!/usr/bin/env python3
"""Copy a signed skills release from Easy MCP AI into skills-feed/.

Usage:
    python3 tools/update-skills-mirror.py [--from-plugin <plugin-folder>] [--source URL]

The plugin downloads its skills once a day from skills-feed/ in this repository
(https://raw.githubusercontent.com/charcsllc/Wordpress-Code-AI/main/skills-feed).
The files carry the original authors' signature, so they are copied byte for byte:
the plugin rejects any file that was edited.

By default the newest release is downloaded from https://skills.easymcpai.com.
With --from-plugin, the release bundled in a plugin folder (assets/skills/) is
copied instead. Before writing, the script checks that release.json matches the
SHA-256 in release.sig; the plugin itself checks the signature.
"""
import argparse
import hashlib
import json
import os
import sys
import urllib.request

FEED = os.path.join(os.path.dirname(os.path.abspath(__file__)), '..', 'skills-feed', 'v1')
MAX_BYTES = 1048576


def fail(msg):
    sys.exit(f'error: {msg}\nNothing was written.')


def download(url):
    req = urllib.request.Request(url, headers={'Accept': 'application/json', 'User-Agent': 'wordpress-code-ai-mirror'})
    with urllib.request.urlopen(req, timeout=30) as resp:
        data = resp.read(MAX_BYTES + 1)
    if len(data) > MAX_BYTES:
        fail(f'{url} is larger than {MAX_BYTES} bytes')
    return data


def read(path):
    with open(path, 'rb') as fh:
        return fh.read()


def serial_of(sig_bytes, where):
    try:
        sig = json.loads(sig_bytes)
        serial = sig['serial']
        sha = sig['sha256']
    except (ValueError, KeyError, TypeError):
        fail(f'{where} is not a skills release.sig file')
    if not isinstance(serial, int) or serial < 1 or not isinstance(sha, str):
        fail(f'{where} has an invalid serial or sha256')
    return serial, sha


def main():
    ap = argparse.ArgumentParser(description=__doc__.split('\n')[0])
    ap.add_argument('--from-plugin', metavar='FOLDER', help='copy assets/skills/ from this plugin folder')
    ap.add_argument('--source', default='https://skills.easymcpai.com', help='skills server to download from')
    args = ap.parse_args()

    if args.from_plugin:
        where = os.path.join(args.from_plugin, 'assets', 'skills')
        sig = read(os.path.join(where, 'release.sig'))
        serial, sha = serial_of(sig, where)
        body = read(os.path.join(where, 'release.json'))
    else:
        base = args.source.rstrip('/')
        sig = download(base + '/v1/release.sig')
        serial, sha = serial_of(sig, base + '/v1/release.sig')
        body = None

    current_path = os.path.join(FEED, 'release.sig')
    if os.path.isfile(current_path):
        current, _ = serial_of(read(current_path), current_path)
        if current >= serial:
            print(f'skills-feed already has release {current}; nothing to do')
            return

    if body is None:
        body = download(f'{base}/v1/releases/{serial}/release.json')
    if hashlib.sha256(body).hexdigest() != sha:
        fail(f'release.json does not match the sha256 in release.sig for release {serial}')

    release_dir = os.path.join(FEED, 'releases', str(serial))
    os.makedirs(release_dir, exist_ok=True)
    with open(os.path.join(release_dir, 'release.json'), 'wb') as fh:
        fh.write(body)
    with open(current_path, 'wb') as fh:
        fh.write(sig)
    print(f'skills-feed now serves release {serial}; commit skills-feed/ to publish it')


if __name__ == '__main__':
    main()
