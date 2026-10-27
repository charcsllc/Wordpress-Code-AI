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
# The repository's README replaces the original vendor's documentation site.
DOCS = 'https://github.com/charcsllc/Wordpress-Code-AI'
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

# Files removed after the rename: the Themeisle SDK, and the feedback reports, which
# sent text to Themeisle. Feedback_Availability stays: wp_list_disabled_tools uses its
# word matching.
DELETE = [
    'includes/class-themeisle-sdk.php',
    'includes/feedback/class-feedback-queue.php',
    'includes/feedback/class-wpdb-feedback-store.php',
    'includes/feedback/interface-feedback-report-store.php',
    'includes/tools/feedback/class-send-feedback.php',
    'includes/admin/rest/class-feedback-controller.php',
]

# Files whose edits below remove translatable strings; the .pot drops the strings
# that none of their files still contains.
PRUNE = {
    'includes/approvals/class-approval-page.php',
    'assets/build/index.js',
    'assets/build/page-tools.js',
}


def method(name):
    """An edit that removes one method, from its signature to its closing brace."""
    return (rf'(?s)\n    (?:(?:public|protected|private) )?(?:static )?function {name}\(.*?\n    \}}\n', '\n', True)


# Links to the original vendor's documentation now point to this repository's README,
# which has the same instructions. Where the README has no equivalent, the link goes.
WELL_KNOWN_DOCS = f'{DOCS}#well-known-addresses'
PROXY_DOCS = f'{DOCS}#trusted-proxies'

# Exact-text edits made after the generic rename, as (old, new, is_regex[, count]),
# keyed by the Easy MCP AI path. They match the renamed text, so they can be checked
# against wordpress-code-ai/ directly. count says how many matches there must be (1).
POST = {
    'assets/build/index.js': [
        ('{id:"docs",href:"https://docs.easymcpai.com/"', f'{{id:"docs",href:"{DOCS}#readme"', False),
        # There are no reviews to leave for this plugin.
        (',{id:"rate",href:"https://github.com/charcsllc/wordpress-code-ai",icon:u,'
         'label:()=>(0,s.__)("Rate the plugin","wordpress-code-ai")}', '', False),
        # Sent feedback reports to Themeisle. Nothing calls it any more, so it no longer
        # contains the address.
        ('async function o(e,t,r){const o=e.trim();if(o.length<6)return"tooShort";try{return(await fetch('
         '"https://api.themeisle.com/tracking/feedback",{method:"POST",headers:{"Content-Type":"application/json",'
         'Accept:"application/json, */*;q=0.1","Cache-Control":"no-cache"},body:JSON.stringify({slug:"wordpress-code-ai",'
         'version:r,feedback:o,data:{"feedback-area":t}})})).ok?"sent":"error"}catch{return"error"}}',
         'async function o(){return"error"}', False),
        # The banner for reports waiting to be sent, which polled the removed REST route.
        ('function Gr(){const e=(0,fr.J)().whiteLabel,', 'function Gr(){return null;const e=(0,fr.J)().whiteLabel,', False),
    ],
    'assets/build/page-settings.js': [
        ('href:"https://docs.easymcpai.com/trusted-proxies"', f'href:"{PROXY_DOCS}"', False),
    ],
    'assets/build/page-tools.js': [
        # The "Browse plugins with abilities" button opened easymcpai.com's directory.
        ('function he(){return(0,L.jsx)(l.$,{asChild:!0,variant:"secondary",size:"md",children:(0,L.jsxs)("a",'
         '{href:"https://easymcpai.com/abilities-directory",target:"_blank",rel:"noopener noreferrer",children:['
         '(0,i.__)("Browse plugins with abilities","wordpress-code-ai"),(0,L.jsx)(j.I,{as:x.A,size:"sm"})]})})}', '', False),
        ('action:(0,L.jsx)(he,{})', 'action:null', False),
        ('(0,L.jsx)(y.B,{direction:"row",children:(0,L.jsx)(he,{})}),', '', False),
    ],
    'includes/class-client-ip.php': [
        ("const DOC_URL = 'https://docs.easymcpai.com/trusted-proxies';", f"const DOC_URL = '{PROXY_DOCS}';", False),
    ],
    'includes/diagnostics/class-check-transport.php': [
        ('https://docs.easymcpai.com/trusted-proxies', PROXY_DOCS, False, 2),
    ],
    'includes/diagnostics/class-check-discovery.php': [
        ('Full instructions: https://easymcpai.com/well-known', f'Full instructions: {WELL_KNOWN_DOCS}', False, 3),
    ],
    'includes/diagnostics/class-blocker-copy.php': [
        ('Full instructions at easymcpai.com/well-known.', f'Full instructions: {WELL_KNOWN_DOCS}.', False),
    ],
    'includes/admin/rest/class-admin-routes.php': [
        ('        ( new Feedback_Controller() )->register_routes();\n', '', False),
    ],
    'includes/admin/class-app-page.php': [
        ('            if ( class_exists( \\wordpress_code_ai\\Themeisle_SDK::class ) ) {\n'
         '                \\remove_submenu_page( self::SLUG_PREFIX, \\wordpress_code_ai\\Themeisle_SDK::ABOUT_US_MENU_SLUG );\n'
         '            }\n', '', False),
    ],
    'includes/class-config-admin.php': [
        ("        if ( 'wordpress-code-ai' === $slug || 0 === strpos( $slug, 'wordpress-code-ai-' ) ) {\n"
         "            return true;\n"
         "        }\n"
         "        return class_exists( Themeisle_SDK::class ) && Themeisle_SDK::ABOUT_US_MENU_SLUG === $slug;\n",
         "        return 'wordpress-code-ai' === $slug || 0 === strpos( $slug, 'wordpress-code-ai-' );\n", False),
    ],
    'includes/approvals/class-approval-page.php': [
        ('    private $feedback_queue;\n', '', False),
        ('    public function __construct( Approval_Gate $gate, ?\\wordpress_code_ai\\Feedback\\Feedback_Queue $feedback_queue = null ) {\n'
         '        $this->gate           = $gate;\n'
         '        $this->feedback_queue = $feedback_queue;\n'
         '    }',
         '    public function __construct( Approval_Gate $gate ) {\n'
         '        $this->gate = $gate;\n'
         '    }', False),
        ('        if ( self::is_feedback_row( $row ) ) {\n'
         '            return $this->handle_feedback( (string) $method, $row, $post );\n'
         '        }\n', '', False),
    ] + [method(name) for name in ('is_feedback_row', 'feedback_queue', 'handle_feedback', 'handle_feedback_post',
                                   'render_feedback_form', 'feedback_result', 'console_page', 'feedback_send_script',
                                   'report_html')],
    'includes/mcp/class-server.php': [
        ('    private $feedback_queue      = null;\n', '', False),
        method('set_feedback_queue'),
        (r"    const FEEDBACK_INSTRUCTIONS = '[^\n]*';\n", '', True),
        ("            'feedback' => $this->sentence_while_available( $token_id, $allowed_tools, 'wp_send_feedback', self::FEEDBACK_INSTRUCTIONS ),\n"
         "            'waiting'  => $this->feedback_line( $token_id ),\n", '', False),
        method('feedback_line'),
    ],
    'includes/diagnostics/class-check-tool-visibility.php': [
        ('        $names[] = \\wordpress_code_ai\\Tools\\Feedback\\Send_Feedback::NAME;\n', '', False),
    ],
    'includes/tools/class-tool-registry.php': [
        (r"            'wordpress_code_ai\\Tools\\Feedback\\Send_Feedback'," + '\n', '', False),
        ('            if ( self::is_withheld( $class ) ) {\n'
         '                continue;\n'
         '            }\n', '', False),
        method('is_withheld'),
    ],
    'includes/tools/class-tool-directories.php': [
        ("        'feedback',\n", '', False),
    ],
    'includes/auth/class-permission-guard.php': [
        ("array( 'wp_send_feedback', 'wp_get_skill', 'wp_list_disabled_tools' )",
         "array( 'wp_get_skill', 'wp_list_disabled_tools' )", False),
    ],
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
        ('https://docs.easymcpai.com/trusted-proxies', PROXY_DOCS, False, 2),
        ('Full instructions: https://easymcpai.com/well-known', f'Full instructions: {WELL_KNOWN_DOCS}', False, 3),
        ('Full instructions at easymcpai.com/well-known.', f'Full instructions: {WELL_KNOWN_DOCS}.', False),
    ],
    'readme.txt': [
        ('See the [Claude integration guides](https://easymcpai.com/integrations/claude-ai).',
         f'See the [setup steps for Claude]({DOCS}#claude-desktop-oauth--recommended).', False),
        ('See the [ChatGPT guide](https://easymcpai.com/integrations/chatgpt).',
         f'See the [setup steps for ChatGPT]({DOCS}#chatgpt-openai).', False),
        ('follow the [n8n guide](https://easymcpai.com/integrations/n8n)', f'follow the [setup steps for n8n]({DOCS}#n8n)', False),
        ('Browse all [integration guides](https://easymcpai.com/integrations).',
         f'See the [setup steps for every client]({DOCS}#connecting-clients).', False),
        ('The [integration guides](https://easymcpai.com/integrations) show each step.',
         f'The [setup steps for Claude]({DOCS}#claude-desktop-oauth--recommended) show each step.', False),
        (' Find compatible plugins in the [Abilities directory](https://easymcpai.com/abilities-directory).', '', False),
        ('\n[See changelog for all versions](https://easymcpai.com/changelog).\n', '', False),
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
    ('2.3.0.2', '2026-10-27', [
        "Help links and the instructions in Diagnostics now point to this plugin's documentation on GitHub "
        'instead of easymcpai.com. The "Rate the plugin" and "Browse plugins with abilities" links are gone.',
        'Removed the code of the Themeisle SDK and of the feedback reports, which were already switched off.',
    ]),
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


def apply_edit(text, path, old, new, is_regex, count=1):
    found = len(re.findall(old, text)) if is_regex else text.count(old)
    if found != count:
        fail(f'{path}: expected {count} match(es) for {old[:70]!r}, found {found}')
    return re.sub(old, new, text) if is_regex else text.replace(old, new)


def generic(text):
    for rx, new in GENERIC:
        text = rx.sub(new, text)
    return text


def po_quote(text):
    return '"' + text.replace('\\', '\\\\').replace('"', '\\"') + '"'


def po_unquote(lines):
    """The text of a msgid, from its line and any continuation lines."""
    text = ''.join(line[line.index('"') + 1:-1] for line in lines)
    return re.sub(r'\\(.)', lambda m: {'n': '\n', 't': '\t'}.get(m.group(1), m.group(1)), text)


def still_in(source, msgid):
    """Whether a PHP or JS file still has the string, in single or double quotes."""
    single = "'" + msgid.replace('\\', '\\\\').replace("'", "\\'") + "'"
    double = '"' + msgid.replace('\\', '\\\\').replace('"', '\\"') + '"'
    return single in source or double in source


def prune_pot(pot, sources, gone):
    """Drops the references to deleted files, and the strings no edited file still has."""
    kept, dropped = [], []
    for block in pot.rstrip('\n').split('\n\n'):
        lines = block.split('\n')
        refs = [(line, line[3:].rsplit(':', 1)[0]) for line in lines if line.startswith('#: ')]
        start = next((i for i, line in enumerate(lines) if line.startswith('msgid ')), None)
        if start is None or not any(f in gone or f in sources for _, f in refs):
            kept.append(block)
            continue
        end = start + 1
        while end < len(lines) and lines[end].startswith('"'):
            end += 1
        msgid = po_unquote(lines[start:end])
        stale = {line for line, f in refs if f in gone or (f in sources and not still_in(sources[f], msgid))}
        if len(stale) == len(refs):
            dropped.append(msgid)
            continue
        kept.append('\n'.join(line for line in lines if line not in stale))
    return '\n\n'.join(kept) + '\n', dropped


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
        for edit in edits:
            contents[path] = apply_edit(contents[path], path, *edit)
    for path in DELETE:
        if path not in contents:
            fail(f'{path} not found')
        del contents[path]

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
              f" * {date}: renamed the plugin, removed the Themeisle SDK and the feedback\n"
              f" * reports sent to Themeisle, replaced the easymcpai.com connection test with\n"
              f" * one that runs in the browser, pointed the help links at this plugin's own\n"
              f" * documentation, and added updates from GitHub.")
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

    final = {}
    for path, text in contents.items():
        new = generic(text)
        for edit in post.get(path, []):
            new = apply_edit(new, path, *edit)
        final[path] = new
    pot = 'languages/easy-mcp-ai.pot'
    final[pot], dropped = prune_pot(final[pot], {path: final[path] for path in PRUNE}, set(DELETE))
    final[pot] = add_fork_strings(final[pot], fork_strings(root))
    changed = {path: text for path, text in final.items() if text != contents[path] or path in PRE}
    print(f'Easy MCP AI {version} -> wordpress-code-ai {fork_version}: {len(mapping)} identifiers renamed '
          f'in {len(changed)} files, {len(DELETE)} files and {len(dropped)} unused strings removed, no collisions')
    if args.dry_run:
        return

    for path, text in changed.items():
        with open(os.path.join(root, path), 'w', encoding='utf-8', newline='') as fh:
            fh.write(text)
    for path in DELETE:
        os.remove(os.path.join(root, path))
        folder = os.path.dirname(os.path.join(root, path))
        if not os.listdir(folder):
            os.rmdir(folder)
    for old, new in RENAMES:
        os.rename(os.path.join(root, old), os.path.join(root, new))
    shutil.rmtree(os.path.join(root, 'vendor'), ignore_errors=True)
    shutil.copyfile(LICENSE, os.path.join(root, 'LICENSE'))
    print('done: renamed files, removed vendor/, added LICENSE')


if __name__ == '__main__':
    main()
