#!/usr/bin/env python3
"""Turn an Easy MCP AI release into wordpress-code-ai.

Usage:
    python3 tools/rename-upstream.py <plugin-folder> [--date YYYY-MM-DD] [--dry-run]

<plugin-folder> holds an unzipped Easy MCP AI release (the folder that contains
easy-mcp-ai.php). It is changed in place: the plugin is renamed, the Themeisle SDK
is removed and the credit to the original plugin is added.

Every exact-text edit below must match exactly once. If a new upstream release
changed one of those lines, or two old names would collapse into the same new
name, the script stops before writing anything and says which edit failed.
"""
import argparse
import collections
import datetime
import os
import re
import shutil
import sys

REPO = 'https://github.com/charcsllc/wordpress-code-ai'
LICENSE = os.path.join(os.path.dirname(os.path.abspath(__file__)), '..', 'LICENSE')
TEXT_EXT = {'.php', '.js', '.css', '.html', '.txt', '.pot', '.json', '.svg', '.md'}
SKIP = {'assets/skills/release.json', 'assets/skills/release.sig'}  # signed by upstream
MANUS_REFERRAL = ('signupLink:"https://manus.im/invitation/BOMGVX7BSFBJLX?utm_source=invitation'
                  '&utm_medium=plugin&utm_campaign=easymcpaicom"')

# Exact-text edits made before the generic rename, as (old, new, is_regex).
PRE = {
    'easy-mcp-ai.php': [
        # The Themeisle SDK would advertise the original plugin; it is removed below.
        ("Easy_MCP_AI\\Themeisle_SDK::register( __FILE__ );\n", '', False),
        # The WordPress.org review link would point at a plugin that does not exist.
        ("""add_filter(
    'plugin_row_meta',
    function ( $links, $file ) {
        if ( EASY_MCP_AI_PLUGIN_BASENAME !== $file || \\Easy_MCP_AI\\Config::get( 'hide_admin' ) ) {
            return $links;
        }
        $links[] = '<a href="https://wordpress.org/support/plugin/easy-mcp-ai/reviews/" target="_blank" rel="noopener">' . esc_html__( 'Rate Plugin', 'easy-mcp-ai' ) . '</a>';
        return $links;
    },
    10,
    2
);
""", '', False),
        (r" \* Plugin Name: Easy MCP AI[^\n]*\n \* Plugin URI:  https://easymcpai\.com\n",
         f" * Plugin Name: wordpress-code-ai\n * Plugin URI:  {REPO}\n", True),
        (" * Author:      EasyMCPAI\n * Author URI:  https://easymcpai.com/\n",
         " * Author:      charcsllc\n * Author URI:  https://github.com/charcsllc\n", False),
        # Themeisle SDK headers out, Update URI in so WordPress.org never offers an
        # unrelated plugin with the same slug as an update.
        (" * WordPress Available:  yes\n * Requires License:    no\n */\n",
         f" * Update URI:  {REPO}\n *\n * @@CREDIT@@\n */\n", False),
    ],
    'assets/build/index.js': [
        ('href:"https://wordpress.org/support/plugin/easy-mcp-ai/reviews/#new-post"', f'href:"{REPO}"', False),
        ('href:"https://wordpress.org/support/plugin/easy-mcp-ai/"', f'href:"{REPO}/issues"', False),
        # The "Share anonymous usage data" setting only fed the Themeisle SDK.
        ('function x({api:e}){const{form:t,errors:r,set:o}=e;return(0,v.jsx)(i.a,{"data-testid":"settings-usage"',
         'function x({api:e}){return null;const{form:t,errors:r,set:o}=e;return(0,v.jsx)(i.a,{"data-testid":"settings-usage"', False),
    ],
    'assets/build/page-settings.js': [
        ('"Tool filtering, IP restrictions and usage sharing — most sites never need to change these."',
         '"Tool filtering and IP restrictions — most sites never need to change these."', False),
    ],
    'assets/build/page-connections.js': [(MANUS_REFERRAL, 'signupLink:"https://manus.im/"', False)],
    'assets/build/page-dashboard.js': [(MANUS_REFERRAL, 'signupLink:"https://manus.im/"', False)],
    'assets/build/page-setup.js': [(MANUS_REFERRAL, 'signupLink:"https://manus.im/"', False)],
    'includes/admin/class-support-text.php': [
        ("Its released code is readable at https://plugins.svn.wordpress.org/easy-mcp-ai/ — trunk/ is the current release, and tags/ holds each published version.",
         f"Its released code is readable at {REPO} — the wordpress-code-ai/ folder holds the current release.", False),
    ],
    'includes/admin/rest/class-settings-controller.php': [
        ("""    public static function usage_ask(): bool {
        return 'yes' !== \\get_option( self::LOGGER_FLAG_OPTION, 'no' )
            && ! \\get_option( self::ASKED_OPTION, false )
            && ! Config::is_white_labelled();
    }""", """    public static function usage_ask(): bool {
        // Usage data was sent by the Themeisle SDK, which is no longer bundled.
        return false;
    }""", False),
    ],
    'languages/easy-mcp-ai.pot': [
        ('the same license as the EasyMCP Dev Toolkit plugin.', 'the same license as the wordpress-code-ai plugin.', False),
        (r'"Report-Msgid-Bugs-To: https://github\.com/Codeinwp/[^"\\]*\\n"', f'"Report-Msgid-Bugs-To: {REPO}/issues\\\\n"', True),
        ('"Last-Translator: friends@themeisle.com\\n"', '"Last-Translator: FULL NAME <EMAIL@ADDRESS>\\n"', False),
        # dev-tools.php is upstream's development helper and is not shipped.
        ('#. Plugin Name of the plugin\n#: dev-tools.php\nmsgid "EasyMCP Dev Toolkit"\nmsgstr ""\n\n', '', False),
        (r'#\. Description of the plugin\n#: dev-tools\.php\nmsgid "Development-only helpers[^\n]*"\nmsgstr ""\n\n', '', True),
        ('#. Author of the plugin\n#: dev-tools.php\n', '#. Plugin Name of the plugin\n#. Author of the plugin\n', False),
        (r'#: easy-mcp-ai\.php:\d+\nmsgid "Rate Plugin"\nmsgstr ""\n\n', '', True),
        ("Its released code is readable at https://plugins.svn.wordpress.org/easy-mcp-ai/ — trunk/ is the current release, and tags/ holds each published version.",
         f"Its released code is readable at {REPO} — the wordpress-code-ai/ folder holds the current release.", False),
        ('msgid "Tool filtering, IP restrictions and usage sharing — most sites never need to change these."',
         'msgid "Tool filtering and IP restrictions — most sites never need to change these."', False),
    ],
    'readme.txt': [
        ('Contributors: easymcpai\n', 'Contributors: charcsllc\n', False),
        ('[**Easy MCP AI**](https://easymcpai.com/)', f'[**Easy MCP AI**]({REPO})', False),
        ('1. In **Plugins → Add New Plugin**, search for "Easy MCP AI", click **Install Now**, then **Activate**. For manual installs, upload the ZIP under **Plugins → Add New Plugin → Upload Plugin**.',
         f'1. Download `wordpress-code-ai.zip` from {REPO}, then upload it under **Plugins → Add New Plugin → Upload Plugin**, click **Install Now**, then **Activate**.', False),
        # Upstream history that is no longer true for this plugin.
        ('= 1.7.17 =\nEasy MCP AI is now part of Themeisle. Your setup keeps working as before, with no action needed.\n\n', '', False),
        ('== Changelog ==\n', '@@CHANGELOG@@', False),
    ],
}

# Generic replacements, in this order. The name is lowercase everywhere; PHP constants
# stay uppercase because users set them in wp-config.php and the plugin builds some of
# their names with strtoupper().
GENERIC = [(re.compile(a), b) for a, b in [
    (r'EasyMCPAI', 'wordpress-code-ai'),
    (r'Easy MCP AI', 'wordpress-code-ai'),
    (r'Easy MCP', 'wordpress-code-ai'),
    (r'Easy_MCP_AI', 'wordpress_code_ai'),
    (r'EASY_MCP_AI', 'WORDPRESS_CODE_AI'),
    (r'easy-mcp-ai', 'wordpress-code-ai'),
    (r'easy_mcp_ai', 'wordpress_code_ai'),
    (r'easyMcpAi', 'wordpresscodeai'),
    (r'Easy_Mcp', 'Wordpress_Code_Ai'),
    (r'easy-mcp', 'wordpress-code-ai'),
    (r'easy_mcp', 'wordpress_code_ai'),
    (r'(?<![A-Za-z0-9])emcp-', 'wcai-'),
]]

# Intentional merges: the display name, user agent and slug all become wordpress-code-ai,
# and the namespace becomes the same word as the option/cache prefix (different PHP
# symbol spaces). Any other merge is a collision.
ALLOWED_MERGES = {'wordpress-code-ai', 'wordpress_code_ai'}

RENAMES = [
    ('easy-mcp-ai.php', 'wordpress-code-ai.php'),
    ('includes/tools/site-health/class-get-easy-mcp-diagnostics.php',
     'includes/tools/site-health/class-get-wordpress-code-ai-diagnostics.php'),
    ('languages/easy-mcp-ai.pot', 'languages/wordpress-code-ai.pot'),
]


def fail(msg):
    sys.exit(f'error: {msg}\nNothing was written.')


def apply_edit(text, old, new, is_regex, path):
    found = len(re.findall(old, text)) if is_regex else text.count(old)
    if found != 1:
        fail(f'{path}: expected one match for {old[:70]!r}, found {found}')
    return re.sub(old, new, text) if is_regex else text.replace(old, new)


def generic(text):
    for rx, new in GENERIC:
        text = rx.sub(new, text)
    return text


def main():
    ap = argparse.ArgumentParser(description=__doc__.split('\n')[0])
    ap.add_argument('folder')
    ap.add_argument('--date', default=datetime.date.today().isoformat())
    ap.add_argument('--dry-run', action='store_true')
    args = ap.parse_args()
    root = os.path.abspath(args.folder)

    main_file = os.path.join(root, 'easy-mcp-ai.php')
    if not os.path.isfile(main_file):
        fail(f'{main_file} not found; pass the folder of an unzipped Easy MCP AI release')
    with open(main_file, encoding='utf-8') as fh:
        version = re.search(r'^ \* Version:\s*(\S+)', fh.read(), re.M).group(1)

    contents = {}
    for d, dirs, files in os.walk(root):
        dirs[:] = [x for x in dirs if not (d == root and x == 'vendor')]
        for f in files:
            rel = os.path.relpath(os.path.join(d, f), root).replace(os.sep, '/')
            if os.path.splitext(f)[1] in TEXT_EXT and rel not in SKIP:
                with open(os.path.join(d, f), encoding='utf-8', newline='') as fh:
                    contents[rel] = fh.read()

    for path, edits in PRE.items():
        if path not in contents:
            fail(f'{path} not found')
        for old, new, is_regex in edits:
            contents[path] = apply_edit(contents[path], old, new, is_regex, path)

    token = re.compile(r'[A-Za-z0-9_$-]+')
    old_name = re.compile(r'(?i)easy[-_]?mcp|EasyMCPAI|(?<![A-Za-z0-9])emcp-')
    existing = set()
    mapping = collections.defaultdict(set)
    for text in contents.values():
        for t in token.findall(text):
            existing.add(t)
            if old_name.search(t):
                mapping[generic(t)].add(t)
    collisions = [(n, sorted(o)) for n, o in mapping.items()
                  if n not in ALLOWED_MERGES and (len(o) > 1 or (n in existing and n not in o))]
    if collisions:
        for n, o in collisions[:40]:
            print(f'collision: {n} <- {o}', file=sys.stderr)
        fail(f'{len(collisions)} identifier collisions')

    credit = (f" wordpress-code-ai is a modified version of Easy MCP AI {version} by EasyMCPAI\n"
              f" * (https://easymcpai.com), used under the GNU GPL v2 or later. Changed on\n"
              f" * {args.date}: renamed the plugin and removed the bundled Themeisle SDK.")
    changelog = (f"== Credits ==\n\nwordpress-code-ai is a modified version of Easy MCP AI {version} by EasyMCPAI "
                 f"(https://easymcpai.com), used under the GNU General Public License v2 or later. "
                 f"The changelog below is the history of that original plugin.\n\n== Changelog ==\n\n"
                 f"= {version} (wordpress-code-ai) =\n- Renamed the plugin to wordpress-code-ai.\n"
                 f"- Removed the bundled Themeisle SDK.\n\n")
    post = {
        'easy-mcp-ai.php': [(' @@CREDIT@@', credit)],
        'readme.txt': [('@@CHANGELOG@@', changelog)],
    }

    changed = {}
    for path, text in contents.items():
        new = generic(text)
        for old, rep in post.get(path, []):
            new = apply_edit(new, old, rep, False, path)
        if new != text or path in PRE:
            changed[path] = new
    print(f'Easy MCP AI {version}: {len(mapping)} identifiers renamed in {len(changed)} files, no collisions')
    if args.dry_run:
        return

    for path, text in changed.items():
        with open(os.path.join(root, path), 'w', encoding='utf-8', newline='') as fh:
            fh.write(text)
    for old, new in RENAMES:
        os.rename(os.path.join(root, old), os.path.join(root, new))
    shutil.rmtree(os.path.join(root, 'vendor'), ignore_errors=True)
    shutil.copyfile(LICENSE, os.path.join(root, 'LICENSE'))
    print('done: renamed files, removed vendor/, added LICENSE')


if __name__ == '__main__':
    main()
