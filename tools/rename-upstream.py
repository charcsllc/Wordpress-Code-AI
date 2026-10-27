#!/usr/bin/env python3
"""Turn an Easy MCP AI release into wordpress-code-ai.

Usage:
    python3 tools/rename-upstream.py <plugin-folder> [--dry-run]

<plugin-folder> holds an unzipped Easy MCP AI release (the folder that contains
easy-mcp-ai.php) with a copy of wordpress-code-ai/fork/ in it. It is changed in
place: the plugin is renamed, the Themeisle SDK is removed, the edits below are
made and the credit to the original plugin is added. fork/ is left as it is.
tools/rebuild-plugin.py runs this for you.

Every exact-text edit below must match exactly once. If a new upstream release
changed one of those lines, or two old names would collapse into the same new
name, the script stops before writing anything and says which edit failed.
"""
import argparse
import collections
import os
import re
import shutil
import sys

REPO = 'https://github.com/charcsllc/wordpress-code-ai'
# Skill sync follows no redirects, so this uses the repository's exact name and case.
SKILLS_FEED = 'https://raw.githubusercontent.com/charcsllc/Wordpress-Code-AI/main/skills-feed'
RELEASES = 'https://github.com/charcsllc/Wordpress-Code-AI/releases'
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
        # The "Share feedback" form posts to Themeisle's API.
        ('if((0,y.J)().whiteLabel)return null;const l="help-card"===e;', 'return null;const l="help-card"===e;', False),
    ],
    'includes/tools/class-tool-registry.php': [
        # wp_send_feedback reports to Themeisle, the original plugin's authors.
        (r"""    private static function is_withheld( string $class ): bool {
        return 'Easy_MCP_AI\\Tools\\Feedback\\Send_Feedback' === $class && class_exists( '\\Easy_MCP_AI\\Config' ) && \Easy_MCP_AI\Config::is_white_labelled();
    }""", r"""    private static function is_withheld( string $class ): bool {
        // wp_send_feedback reports to Themeisle, the original plugin's authors.
        return 'Easy_MCP_AI\\Tools\\Feedback\\Send_Feedback' === $class;
    }""", False),
    ],
    'assets/build/page-settings.js': [
        ('"Tool filtering, IP restrictions and usage sharing — most sites never need to change these."',
         '"Tool filtering and IP restrictions — most sites never need to change these."', False),
    ],
    'assets/build/page-connections.js': [(MANUS_REFERRAL, 'signupLink:"https://manus.im/"', False)],
    'assets/build/page-dashboard.js': [(MANUS_REFERRAL, 'signupLink:"https://manus.im/"', False)],
    'assets/build/page-setup.js': [(MANUS_REFERRAL, 'signupLink:"https://manus.im/"', False)],
    'includes/admin/class-support-text.php': [
        ("const SUPPORT_EMAIL = 'support@easymcpai.com';", "const SUPPORT_EMAIL = 'info@charcs.net';", False),
        ("Its released code is readable at https://plugins.svn.wordpress.org/easy-mcp-ai/ — trunk/ is the current release, and tags/ holds each published version.",
         f"Its released code is readable at {REPO} — the wordpress-code-ai/ folder holds the current release.", False),
    ],
    'includes/skills/class-skill-sync.php': [
        # Skills come from this repository's copy of the original signed releases
        # (see tools/update-skills-mirror.py) instead of the original vendor's server.
        ("const BASE_URL = 'https://skills.easymcpai.com';", f"const BASE_URL = '{SKILLS_FEED}';", False),
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
        ('once a day from skills.easymcpai.com.',
         "once a day from the wordpress-code-ai GitHub repository, which keeps a copy of the original plugin's signed skills.", False),
        ('[**Easy MCP AI**](https://easymcpai.com/)', f'[**Easy MCP AI**]({REPO})', False),
        ('1. In **Plugins → Add New Plugin**, search for "Easy MCP AI", click **Install Now**, then **Activate**. For manual installs, upload the ZIP under **Plugins → Add New Plugin → Upload Plugin**.',
         f'1. Download `wordpress-code-ai.zip` from the latest release at {RELEASES}/latest, then upload it under **Plugins → Add New Plugin → Upload Plugin**, click **Install Now**, then **Activate**.', False),
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

# The Diagnostics card's button opened easymcpai.com with the site's address in the
# link. It now opens the browser test in fork/class-browser-test.php.
BROWSER_TEST_BUTTON = [
    ('"Opens easymcpai.com, which tries to reach this site from the public internet"',
     '"Opens a page where your browser tests the addresses AI clients use to reach this site"', False),
    ('"Test from the internet"', '"Test from your browser"', False),
]

# Exact-text edits made after the generic rename, as (old, new, is_regex), keyed by
# the Easy MCP AI path. They match the renamed text, so they can be checked against
# wordpress-code-ai/ directly.
POST = {
    'easy-mcp-ai.php': [
        ("\nwordpress_code_ai\\Plugin::instance();\n",
         "\nwordpress_code_ai\\Plugin::instance();\n\n"
         "// Updates from GitHub and the browser test, which exist only in wordpress-code-ai.\n"
         "require_once __DIR__ . '/fork/load.php';\n", False),
    ],
    'includes/admin/class-support-text.php': [
        (r"(?s)    public static function external_test_host\( \$endpoint_url, \$home_url \) \{\n.*?\n    \}\n"
         r"(?:[ \t]*\n)*    public static function external_test_url\( \$endpoint_url, \$home_url \) \{\n.*?\n    \}\n", '', True),
        ('(Press "Test from the internet", then copy the result and paste here.)',
         '(On the Diagnostics card, press "Test from your browser", then press "Copy result" and paste here.)', False),
        ('[Paste here. On the same card, press "Test from the internet", then copy the result.]',
         '[Paste here. On the same card, press "Test from your browser", then press "Copy result".]', False),
    ],
    'includes/admin/rest/class-status-controller.php': [
        ("        $endpoint = \\rest_url( 'wordpress-code-ai/v1/mcp' );\n", '', False),
        ("'externalTestUrl' => Support_Text::external_test_url( $endpoint, \\home_url() ),",
         "'externalTestUrl' => \\wordpress_code_ai\\Fork\\Browser_Test::url(),", False),
    ],
    'assets/build/page-dashboard.js': BROWSER_TEST_BUTTON,
    'assets/build/page-setup.js': BROWSER_TEST_BUTTON,
    'languages/easy-mcp-ai.pot': [
        ('msgid "(Press \\"Test from the internet\\", then copy the result and paste here.)"',
         'msgid "(On the Diagnostics card, press \\"Test from your browser\\", then press \\"Copy result\\" and paste here.)"', False),
        ('msgid "[Paste here. On the same card, press \\"Test from the internet\\", then copy the result.]"',
         'msgid "[Paste here. On the same card, press \\"Test from your browser\\", then press \\"Copy result\\".]"', False),
        ('msgid "Opens easymcpai.com, which tries to reach this site from the public internet"',
         'msgid "Opens a page where your browser tests the addresses AI clients use to reach this site"', False),
        ('msgid "Test from the internet"', 'msgid "Test from your browser"', False),
    ],
    'readme.txt': [
        ('The optional outside reachability check is a link that opens easymcpai.com in your browser, and it uses only your site address.',
         'The optional test from your browser runs in your own browser and contacts only your own site.', False),
        ("To turn it off, add `define( 'WORDPRESS_CODE_AI_SKILLS_SYNC', false );` to wp-config.php.\n",
         "To turn it off, add `define( 'WORDPRESS_CODE_AI_SKILLS_SYNC', false );` to wp-config.php.\n\n"
         "WordPress checks GitHub for a newer release of the plugin about twice a day, and when you open the Plugins "
         "or Updates screen, the same way it checks WordPress.org for other plugins. The request carries only the "
         "plugin version.\n", False),
    ],
}

# The changes made in wordpress-code-ai, newest first, as (version, date, notes). The
# first entry sets the plugin version and the date in the credit; its version is the
# Easy MCP AI version it is based on plus a fourth number. Add an entry for every
# release (see "Publishing a release" in README.md).
CHANGELOG = [
    ('2.3.0.1', '2026-10-27', [
        "Updates now come from this plugin's GitHub releases. WordPress shows them on the Plugins screen "
        "and can install them automatically.",
        'The "Test from the internet" button is now "Test from your browser": the test runs in your own '
        'browser and contacts only your site, instead of sending its address to easymcpai.com.',
        'The plugin description now gives the same tool count as this readme.',
    ]),
    ('2.3.0', '2026-10-27', [
        'Renamed the plugin to wordpress-code-ai.',
        'Removed the bundled Themeisle SDK.',
        'Removed the feedback form and the wp_send_feedback tool, which reported to Themeisle.',
        'Support reports now go to info@charcs.net.',
    ]),
]

# Translatable strings in fork/, which wp i18n make-pot would find. Each call is on one line.
GETTEXT = re.compile(r"(?<![A-Za-z0-9_])(?:esc_html__|esc_html_e|esc_attr__|esc_attr_e|_e|__)"
                     r"\(\s*'((?:[^'\\]|\\.)*)'\s*,\s*'wordpress-code-ai'\s*\)")
TRANSLATORS = re.compile(r'/\*\s*(translators:.*?)\s*\*/')


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


def po_quote(text):
    return '"' + text.replace('\\', '\\\\').replace('"', '\\"') + '"'


def fork_strings(root):
    """The translatable strings in fork/*.php, as {msgid: (references, translator comment)}."""
    found = {}
    fork = os.path.join(root, 'fork')
    for name in sorted(os.listdir(fork)):
        if not name.endswith('.php'):
            continue
        with open(os.path.join(fork, name), encoding='utf-8') as fh:
            lines = fh.read().split('\n')
        for number, line in enumerate(lines, 1):
            for m in GETTEXT.finditer(line):
                msgid = re.sub(r"\\(['\\])", r'\1', m.group(1))
                comment = TRANSLATORS.search(lines[number - 2]) if number > 1 else None
                refs, old_comment = found.get(msgid, ([], None))
                found[msgid] = (refs + [f'fork/{name}:{number}'], old_comment or (comment.group(1) if comment else None))
    return found


def add_fork_strings(pot, strings):
    """Adds the fork/ strings to the .pot; a string it already has gets the new references."""
    blocks = pot.rstrip('\n').split('\n\n')
    where = {}
    for i, block in enumerate(blocks):
        for line in block.split('\n'):
            if line.startswith('msgid '):
                where[line] = i
    for msgid, (refs, comment) in strings.items():
        key = 'msgid ' + po_quote(msgid)
        ref_lines = [f'#: {ref}' for ref in refs]
        if key in where:
            lines = blocks[where[key]].split('\n')
            at = max([i + 1 for i, line in enumerate(lines) if line.startswith('#:')] or [lines.index(key)])
            blocks[where[key]] = '\n'.join(lines[:at] + ref_lines + lines[at:])
            continue
        lines = ([f'#. {comment}'] if comment else []) + ref_lines
        if re.search(r'%(\d+\$)?[sd]', msgid):
            lines.append('#, php-format')
        blocks.append('\n'.join(lines + [key, 'msgstr ""']))
    return '\n\n'.join(blocks) + '\n'


def main():
    ap = argparse.ArgumentParser(description=__doc__.split('\n')[0])
    ap.add_argument('folder')
    ap.add_argument('--dry-run', action='store_true')
    args = ap.parse_args()
    root = os.path.abspath(args.folder)

    main_file = os.path.join(root, 'easy-mcp-ai.php')
    if not os.path.isfile(main_file):
        fail(f'{main_file} not found; pass the folder of an unzipped Easy MCP AI release')
    with open(main_file, encoding='utf-8') as fh:
        version = re.search(r'^ \* Version:\s*(\S+)', fh.read(), re.M).group(1)
    fork_version, date, _ = CHANGELOG[0]
    if not fork_version.startswith(version + '.'):
        fail(f'the newest CHANGELOG entry is {fork_version}; add one for {version}.1 at the top of CHANGELOG')
    if not os.path.isfile(os.path.join(root, 'fork', 'load.php')):
        fail(f'{root}/fork/load.php not found; copy wordpress-code-ai/fork/ into the folder first')

    contents = {}
    for d, dirs, files in os.walk(root):
        dirs[:] = [x for x in dirs if not (d == root and x in ('vendor', 'fork'))]
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

    tool_count = re.search(r'\*\*(\d+\+?) MCP tools\*\*', contents['readme.txt'])
    if not tool_count:
        fail('readme.txt: the "**NNN+ MCP tools**" line that gives the tool count was not found')
    credit = (f" wordpress-code-ai is a modified version of Easy MCP AI {version} by EasyMCPAI\n"
              f" * (https://easymcpai.com), used under the GNU GPL v2 or later. Changed on\n"
              f" * {date}: renamed the plugin, removed the bundled Themeisle SDK and the\n"
              f" * feedback reports sent to Themeisle, replaced the easymcpai.com connection\n"
              f" * test with one that runs in the browser, and added updates from GitHub.")
    changelog = (f"== Credits ==\n\nwordpress-code-ai is a modified version of Easy MCP AI {version} by EasyMCPAI "
                 f"(https://easymcpai.com), used under the GNU General Public License v2 or later. "
                 f"The changelog below starts with the changes made in wordpress-code-ai, followed by "
                 f"the history of that original plugin.\n\n== Changelog ==\n\n"
                 + ''.join(f"= {v} (wordpress-code-ai) =\n" + ''.join(f"- {note}\n" for note in notes) + "\n"
                           for v, _, notes in CHANGELOG))
    post = {
        'easy-mcp-ai.php': [
            (' @@CREDIT@@', credit, False),
            (r'(?m)^( \* Version:\s+)\S+$', rf'\g<1>{fork_version}', True),
            (r"define\( 'WORDPRESS_CODE_AI_VERSION', '[^']+' \);", f"define( 'WORDPRESS_CODE_AI_VERSION', '{fork_version}' );", True),
            # Easy MCP AI's header gives an older count than its readme.
            (r'(?m)^( \* Description: [^\n]*?)\d+\+? tools\. Free\.$', rf'\g<1>{tool_count.group(1)} tools. Free.', True),
        ],
        'readme.txt': [
            ('@@CHANGELOG@@', changelog, False),
            (r'(?m)^Stable tag: \S+$', f'Stable tag: {fork_version}', True),
        ],
    }
    for path, edits in POST.items():
        post.setdefault(path, []).extend(edits)
    if set(post) - set(contents):
        fail(f'{sorted(set(post) - set(contents))[0]} not found')

    changed = {}
    for path, text in contents.items():
        new = generic(text)
        for old, rep, is_regex in post.get(path, []):
            new = apply_edit(new, old, rep, is_regex, path)
        if path == 'languages/easy-mcp-ai.pot':
            new = add_fork_strings(new, fork_strings(root))
        if new != text or path in PRE:
            changed[path] = new
    print(f'Easy MCP AI {version} -> wordpress-code-ai {fork_version}: {len(mapping)} identifiers renamed '
          f'in {len(changed)} files, no collisions')
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
