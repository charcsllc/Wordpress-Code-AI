<?php
/**
 * The "Test from your browser" page.
 *
 * It replaces Easy MCP AI's "Test from the internet" link, which sent the
 * site's address to easymcpai.com. Here the administrator's own browser asks
 * the site for the addresses an AI client uses, without the WordPress login,
 * and shows what came back. The button on the Diagnostics card opens it.
 */

namespace wordpress_code_ai\Fork;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Browser_Test {

    const SLUG = 'wordpress-code-ai-browser-test';

    const PARENT = 'wordpress-code-ai';

    const HANDLE = 'wordpress-code-ai-browser-test';

    private static $hook = '';

    public static function register(): void {
        \add_action( 'admin_menu', array( __CLASS__, 'add_page' ) );
        \add_action( 'admin_head', array( __CLASS__, 'hide_menu_entry' ) );
        \add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
    }

    public static function url(): string {
        return \admin_url( 'admin.php?page=' . self::SLUG );
    }

    public static function add_page(): void {
        $title      = \__( 'Test from your browser', 'wordpress-code-ai' );
        self::$hook = (string) \add_submenu_page( self::PARENT, $title, $title, 'manage_options', self::SLUG, array( __CLASS__, 'render' ) );
    }

    // The page is opened from the Diagnostics card, so it needs no menu entry.
    public static function hide_menu_entry(): void {
        \remove_submenu_page( self::PARENT, self::SLUG );
    }

    public static function enqueue( $hook ): void {
        if ( '' === self::$hook || self::$hook !== $hook ) {
            return;
        }
        $base = \plugin_dir_url( __FILE__ );
        \wp_enqueue_style( self::HANDLE, $base . 'browser-test.css', array(), WORDPRESS_CODE_AI_VERSION );
        \wp_enqueue_script( self::HANDLE, $base . 'browser-test.js', array(), WORDPRESS_CODE_AI_VERSION, true );
        \wp_add_inline_script( self::HANDLE, 'window.wordpressCodeAiBrowserTest = ' . \wp_json_encode( self::config(), JSON_HEX_TAG | JSON_HEX_AMP ) . ';', 'before' );
    }

    private static function config(): array {
        return array(
            'endpoint'      => self::endpoint(),
            'home'          => \home_url( '/' ),
            'rootDocuments' => self::root_documents(),
            'version'       => WORDPRESS_CODE_AI_VERSION,
            'strings'       => self::strings(),
        );
    }

    private static function endpoint(): string {
        return \rest_url( 'wordpress-code-ai/v1/mcp' );
    }

    // Where some AI clients look first, whatever the connection address says.
    private static function root_documents(): array {
        return array(
            \home_url( '/.well-known/oauth-protected-resource' ),
            \home_url( '/.well-known/oauth-authorization-server' ),
        );
    }

    private static function is_local_site(): bool {
        $kind = '\\wordpress_code_ai\\Admin\\Site_Kind';
        return method_exists( $kind, 'detect' ) && 'local' === $kind::detect( \home_url() );
    }

    private static function strings(): array {
        return array(
            'running'        => \__( 'Testing…', 'wordpress-code-ai' ),
            'allPassed'      => \__( 'Every check passed: AI clients can reach this site from your network.', 'wordpress-code-ai' ),
            'warnings'       => \__( 'The checks passed with warnings. See the details below.', 'wordpress-code-ai' ),
            'failed'         => \__( 'Some checks failed. See the details below, and include the copied result when you ask for help.', 'wordpress-code-ai' ),
            'copied'         => \__( 'Result copied.', 'wordpress-code-ai' ),
            'copyFailed'     => \__( 'Your browser did not allow copying. Select the table and copy it instead.', 'wordpress-code-ai' ),
            'pass'           => \__( 'Passed', 'wordpress-code-ai' ),
            'warn'           => \__( 'Warning', 'wordpress-code-ai' ),
            'fail'           => \__( 'Failed', 'wordpress-code-ai' ),
            'unknown'        => \__( 'Unknown', 'wordpress-code-ai' ),
            'endpointLabel'  => \__( 'AI connection address asks for sign-in', 'wordpress-code-ai' ),
            'endpointPass'   => \__( 'It refuses a request without a token and says where AI clients sign in, as it should.', 'wordpress-code-ai' ),
            'noChallenge'    => \__( 'The WWW-Authenticate header is missing, so AI clients cannot find where to sign in. A proxy, CDN or security rule may be removing it.', 'wordpress-code-ai' ),
            'headerHidden'   => \__( 'Your browser hides the WWW-Authenticate header from this page because the admin area and the site use different addresses. Run the first command below to see it.', 'wordpress-code-ai' ),
            'noSignIn'       => \__( 'It answered without asking for sign-in. A cache may be serving a stored answer: exclude /wp-json/ from page caching.', 'wordpress-code-ai' ),
            'resourceLabel'  => \__( 'Sign-in document for the connection address', 'wordpress-code-ai' ),
            'resourcePass'   => \__( 'AI clients find the sign-in server here.', 'wordpress-code-ai' ),
            /* translators: %s: the address the document describes. */
            'otherResource'  => \__( 'The document describes a different address (%s), so AI clients will not use it. Check both addresses under Settings → General.', 'wordpress-code-ai' ),
            'noServer'       => \__( 'The document names no sign-in server.', 'wordpress-code-ai' ),
            'guessedServer'  => \__( 'The sign-in document could not be read, so this test assumed the sign-in server is this site.', 'wordpress-code-ai' ),
            'serverLabel'    => \__( 'Sign-in server document', 'wordpress-code-ai' ),
            'serverPass'     => \__( 'AI clients find the sign-in and token addresses here.', 'wordpress-code-ai' ),
            'serverMissing'  => \__( 'None of the addresses AI clients try answered with the sign-in server document.', 'wordpress-code-ai' ),
            'rootLabel'      => \__( 'Sign-in documents at the root of the domain', 'wordpress-code-ai' ),
            'rootPass'       => \__( 'AI clients that look here first find the documents too.', 'wordpress-code-ai' ),
            'rootWarn'       => \__( 'Some AI clients look for the sign-in documents here first and cannot connect without them. Ask your host to let /.well-known/ reach WordPress.', 'wordpress-code-ai' ),
            /* translators: %s: the error message from the browser. */
            'unreachable'    => \__( 'Your browser could not reach this address (%s). The site may be down, its certificate may not be valid, or a browser extension may be blocking the request.', 'wordpress-code-ai' ),
            'htmlPage'       => \__( 'A web page answered instead of the plugin. It is usually a firewall, cache, security or maintenance page.', 'wordpress-code-ai' ),
            'wellKnown'      => \__( 'Your host or a security rule stops addresses starting /.well-known/ before they reach WordPress. Ask your host to let /.well-known/ reach WordPress.', 'wordpress-code-ai' ),
            /* translators: %s: an HTTP status code. */
            'refused'        => \__( 'Refused (HTTP %s): a firewall or security rule blocks this address.', 'wordpress-code-ai' ),
            'notFound'       => \__( 'Not found: the request did not reach the plugin. Open Settings → Permalinks, press Save Changes and test again.', 'wordpress-code-ai' ),
            'unavailable'    => \__( 'Unavailable (HTTP 503). If AI access is paused in wordpress-code-ai, resume it. Otherwise a maintenance mode or the server is refusing requests.', 'wordpress-code-ai' ),
            /* translators: %s: an HTTP status code. */
            'serverError'    => \__( 'Server error (HTTP %s). The PHP error log usually says why.', 'wordpress-code-ai' ),
            /* translators: %s: an HTTP status code. */
            'unexpected'     => \__( 'Unexpected answer (HTTP %s).', 'wordpress-code-ai' ),
            /* translators: %s: the address the request ended at. */
            'redirected'     => \__( 'redirected to %s', 'wordpress-code-ai' ),
            'redirectNote'   => \__( 'The address redirects. Some AI clients do not follow redirects, so make the addresses under Settings → General match the final address.', 'wordpress-code-ai' ),
            'reportTitle'    => \__( 'wordpress-code-ai: Test from your browser', 'wordpress-code-ai' ),
            'reportDate'     => \__( 'Date', 'wordpress-code-ai' ),
            'reportSite'     => \__( 'Site', 'wordpress-code-ai' ),
            'reportEndpoint' => \__( 'MCP endpoint', 'wordpress-code-ai' ),
            'reportVersion'  => \__( 'Plugin version', 'wordpress-code-ai' ),
            'reportBrowser'  => \__( 'Browser', 'wordpress-code-ai' ),
            'reportSeen'     => \__( 'What came back', 'wordpress-code-ai' ),
        );
    }

    public static function render(): void {
        $endpoint = self::endpoint();
        $roots    = self::root_documents();
        ?>
        <div class="wrap wcai-browser-test">
            <h1><?php echo \esc_html( \get_admin_page_title() ); ?></h1>
            <p class="wcai-browser-test-text"><?php \esc_html_e( 'Your browser now asks this site for the addresses an AI client uses to connect, the way the client does and without your WordPress login. The requests travel over the internet and through your CDN and firewall like any visitor’s, so they can find blocks that the Diagnostics card, which tests from the server itself, may not see. Nothing is sent anywhere else.', 'wordpress-code-ai' ); ?></p>
            <?php if ( self::is_local_site() ) : ?>
                <div class="notice notice-warning inline"><p>
                    <?php
                    /* translators: %s: the site's address. */
                    printf( \esc_html__( 'This site’s address (%s) only works on your own computer or network, so AI services on the internet cannot reach it, whatever this test shows.', 'wordpress-code-ai' ), '<code>' . \esc_html( \home_url() ) . '</code>' );
                    ?>
                </p></div>
            <?php endif; ?>
            <noscript><div class="notice notice-error inline"><p><?php \esc_html_e( 'This test needs JavaScript.', 'wordpress-code-ai' ); ?></p></div></noscript>
            <p class="wcai-browser-test-actions">
                <button type="button" class="button button-primary" id="wcai-browser-test-run" disabled><?php \esc_html_e( 'Run the test again', 'wordpress-code-ai' ); ?></button>
                <button type="button" class="button" id="wcai-browser-test-copy" disabled><?php \esc_html_e( 'Copy result', 'wordpress-code-ai' ); ?></button>
                <span id="wcai-browser-test-status" role="status"></span>
            </p>
            <table class="widefat striped wcai-browser-test-results">
                <thead>
                    <tr>
                        <th scope="col"><?php \esc_html_e( 'Check', 'wordpress-code-ai' ); ?></th>
                        <th scope="col"><?php \esc_html_e( 'Result', 'wordpress-code-ai' ); ?></th>
                        <th scope="col"><?php \esc_html_e( 'What came back', 'wordpress-code-ai' ); ?></th>
                    </tr>
                </thead>
                <tbody id="wcai-browser-test-rows"></tbody>
            </table>
            <h2><?php \esc_html_e( 'What this test cannot see', 'wordpress-code-ai' ); ?></h2>
            <p class="wcai-browser-test-text"><?php \esc_html_e( 'The requests come from your network. Blocks that only affect the servers of AI providers, such as a CDN setting that blocks AI bots or a rule that refuses data centre addresses, do not show up here. If Claude or ChatGPT cannot connect while every check passes, look for such a setting at your CDN or host.', 'wordpress-code-ai' ); ?></p>
            <h2><?php \esc_html_e( 'Test from another computer', 'wordpress-code-ai' ); ?></h2>
            <p class="wcai-browser-test-text"><?php \esc_html_e( 'To test from another computer or network, run these commands there. On Windows, type curl.exe instead of curl.', 'wordpress-code-ai' ); ?></p>
            <pre class="wcai-browser-test-commands"><code><?php echo \esc_html( 'curl -i -X POST "' . $endpoint . '"' . "\n" . 'curl -i "' . $roots[0] . '"' ); ?></code></pre>
            <p class="wcai-browser-test-text"><?php \esc_html_e( 'The first should answer 401 with a WWW-Authenticate: Bearer resource_metadata="…" header, and the second 200 with JSON.', 'wordpress-code-ai' ); ?></p>
        </div>
        <?php
    }
}
