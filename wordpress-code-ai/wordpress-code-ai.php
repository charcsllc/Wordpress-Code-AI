<?php
/**
 * Plugin Name: wordpress-code-ai
 * Plugin URI:  https://github.com/charcsllc/wordpress-code-ai
 * Description: Connect Claude, ChatGPT & any AI to WordPress. Manage your entire site by chat — content, media, GA4, Search Console, SEO & more. 280+ tools. Free.
 * Version:     2.3.0.2
 * Author:      charcsllc
 * Author URI:  https://github.com/charcsllc
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wordpress-code-ai
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Update URI:  https://github.com/charcsllc/wordpress-code-ai
 *
 * wordpress-code-ai is a modified version of Easy MCP AI 2.3.0 by EasyMCPAI
 * (https://easymcpai.com), used under the GNU GPL v2 or later. Changed on
 * 2026-10-27: renamed the plugin, removed the Themeisle SDK and the feedback
 * reports sent to Themeisle, replaced the easymcpai.com connection test with
 * one that runs in the browser, pointed the help links at this plugin's own
 * documentation, and added updates from GitHub.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}






require_once __DIR__ . '/includes/class-autoloader.php';
wordpress_code_ai\Autoloader::register();


require_once __DIR__ . '/includes/functions.php';























$GLOBALS['wordpress_code_ai_ini_memory_limit'] = ini_get( 'memory_limit' );












if ( isset( $_SERVER['REQUEST_URI'] ) ) {
    $wordpress_code_ai_req = (string) $_SERVER['REQUEST_URI']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- read-only path match, not stored/output.
    $wordpress_code_ai_own = (
        false !== strpos( $wordpress_code_ai_req, '/.well-known/oauth-' )
        || false !== strpos( $wordpress_code_ai_req, '/.well-known/openid-configuration' )
        
        
        
        
        
        || false !== strpos( $wordpress_code_ai_req, '/wordpress-code-ai/v1/' )
        || false !== strpos( $wordpress_code_ai_req, 'wordpress_code_ai_oauth=' )
        
        
        || false !== strpos( $wordpress_code_ai_req, '/wordpress-code-ai/authorize' )
    );

    







    if ( ! $wordpress_code_ai_own ) {
        $wordpress_code_ai_path   = rtrim( (string) wp_parse_url( $wordpress_code_ai_req, PHP_URL_PATH ), '/' );
        $wordpress_code_ai_method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( (string) $_SERVER['REQUEST_METHOD'] ) : 'GET'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- read-only comparison.
        $wordpress_code_ai_ctype  = isset( $_SERVER['CONTENT_TYPE'] ) ? strtolower( (string) $_SERVER['CONTENT_TYPE'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- read-only comparison.
        $wordpress_code_ai_own    = (
            ( '/authorize' === $wordpress_code_ai_path && 'GET' === $wordpress_code_ai_method && false !== strpos( $wordpress_code_ai_req, 'response_type=code' ) )
            || ( '/register' === $wordpress_code_ai_path && 'POST' === $wordpress_code_ai_method && 0 === strpos( $wordpress_code_ai_ctype, 'application/json' ) )
            || ( '/token' === $wordpress_code_ai_path && 'POST' === $wordpress_code_ai_method )
        );
        unset( $wordpress_code_ai_path, $wordpress_code_ai_method, $wordpress_code_ai_ctype );
    }

    








































    if ( ! $wordpress_code_ai_own
        && (
            false !== strpos( $wordpress_code_ai_req, 'wp-login.php' )
            || false !== strpos( $wordpress_code_ai_req, 'rest_route=' )
        )
    ) {
        $wordpress_code_ai_decoded = rawurldecode( $wordpress_code_ai_req );
        $wordpress_code_ai_own     = (
            false !== strpos( $wordpress_code_ai_decoded, '/wordpress-code-ai/v1/' )
            || false !== strpos( $wordpress_code_ai_decoded, 'wordpress_code_ai_oauth=' )
        );
        unset( $wordpress_code_ai_decoded );
    }

    



























    if ( ! $wordpress_code_ai_own
        && isset( $_SERVER['REQUEST_METHOD'] )
        && 'POST' === strtoupper( (string) $_SERVER['REQUEST_METHOD'] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- read-only comparison.
        && false !== strpos( $wordpress_code_ai_req, 'wp-login.php' )
        && ! empty( $_POST['redirect_to'] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing -- read-only substring match; nothing is acted on or stored.
        && is_string( $_POST['redirect_to'] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing -- see above.
    ) {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput -- read-only substring match; nothing is acted on, stored or output.
        $wordpress_code_ai_rt = (string) $_POST['redirect_to'];

        
        
        
        
        foreach ( array( $wordpress_code_ai_rt, rawurldecode( $wordpress_code_ai_rt ) ) as $wordpress_code_ai_candidate ) {
            if ( false !== strpos( $wordpress_code_ai_candidate, '/wordpress-code-ai/v1/' )
                || false !== strpos( $wordpress_code_ai_candidate, 'wordpress_code_ai_oauth=' ) ) {
                $wordpress_code_ai_own = true;
                break;
            }
        }
        unset( $wordpress_code_ai_rt, $wordpress_code_ai_candidate );
    }

    if ( $wordpress_code_ai_own ) {
        @ini_set( 'display_errors', '0' ); // phpcs:ignore WordPress.PHP.IniSet.display_errors_Disallowed, PluginCheck.CodeAnalysis.PHPErrorReporting.IniDirectiveDisplay_errors, WordPress.PHP.NoSilencedErrors.Discouraged, Squiz.PHP.DiscouragedFunctions.Discouraged -- Intentional & request-scoped: stop other plugins' PHP notices from corrupting our OAuth/MCP JSON responses. Suppresses display only (logging unaffected).
        @ini_set( 'display_startup_errors', '0' ); // phpcs:ignore WordPress.PHP.IniSet.Risky, WordPress.PHP.NoSilencedErrors.Discouraged, Squiz.PHP.DiscouragedFunctions.Discouraged -- See above; display suppression only, scoped to our own JSON endpoints.

        











        








        $GLOBALS['wordpress_code_ai_server_had_auth_header'] = ! empty( $_SERVER['HTTP_AUTHORIZATION'] );

        if ( empty( $_SERVER['HTTP_AUTHORIZATION'] ) ) {
            $wordpress_code_ai_hdrs = wordpress_code_ai\Auth_Header::request_headers();
            if ( null !== $wordpress_code_ai_hdrs ) {
                $wordpress_code_ai_bearer = wordpress_code_ai\Auth_Header::recover_bearer( $wordpress_code_ai_hdrs );
                if ( null !== $wordpress_code_ai_bearer ) {
                    $_SERVER['HTTP_AUTHORIZATION'] = $wordpress_code_ai_bearer;
                } elseif ( ! isset( $_SERVER['PHP_AUTH_USER'] ) && ! isset( $_SERVER['PHP_AUTH_PW'] ) ) {
                    











                    $wordpress_code_ai_basic = wordpress_code_ai\Auth_Header::recover_basic_api_key( $wordpress_code_ai_hdrs );
                    if ( null !== $wordpress_code_ai_basic ) {
                        $_SERVER['HTTP_AUTHORIZATION'] = $wordpress_code_ai_basic;
                    }
                    unset( $wordpress_code_ai_basic );
                }
                unset( $wordpress_code_ai_bearer );
            }
            unset( $wordpress_code_ai_hdrs );
        }
    }
    unset( $wordpress_code_ai_req, $wordpress_code_ai_own );
}

define( 'WORDPRESS_CODE_AI_VERSION', '2.3.0.2' );
define( 'WORDPRESS_CODE_AI_PLUGIN_FILE', __FILE__ );
define( 'WORDPRESS_CODE_AI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WORDPRESS_CODE_AI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WORDPRESS_CODE_AI_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

















$wordpress_code_ai_autoload = WORDPRESS_CODE_AI_PLUGIN_DIR . 'vendor/autoload.php';
if ( is_readable( $wordpress_code_ai_autoload ) ) {
    require_once $wordpress_code_ai_autoload;
}
unset( $wordpress_code_ai_autoload );

register_activation_hook( __FILE__, array( 'wordpress_code_ai\\Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'wordpress_code_ai\\Deactivator', 'deactivate' ) );

wordpress_code_ai\Plugin::instance();

// Updates from GitHub and the browser test, which exist only in wordpress-code-ai.
require_once __DIR__ . '/fork/load.php';

add_filter(
    'plugin_action_links_' . WORDPRESS_CODE_AI_PLUGIN_BASENAME,
    function ( $links ) {
        if ( \wordpress_code_ai\Config::get( 'hide_admin' ) ) {
            return $links;
        }
        $prepend = array(
            'dashboard' => '<a href="' . esc_url( admin_url( 'admin.php?page=wordpress-code-ai' ) ) . '">' . esc_html__( 'Getting Started', 'wordpress-code-ai' ) . '</a>',
            'abilities'      => '<a href="' . esc_url( admin_url( 'admin.php?page=wordpress-code-ai-tools&section=abilities' ) ) . '">' . esc_html__( 'Abilities', 'wordpress-code-ai' ) . '</a>',
            'external_data'  => '<a href="' . esc_url( admin_url( 'admin.php?page=wordpress-code-ai-tools&section=external' ) ) . '">' . esc_html__( 'External Data', 'wordpress-code-ai' ) . '</a>',
        );
        $append = array(
            'settings' => '<a href="' . esc_url( admin_url( 'admin.php?page=wordpress-code-ai-settings' ) ) . '">' . esc_html__( 'Settings', 'wordpress-code-ai' ) . '</a>',
        );
        return array_merge( $prepend, $links, $append );
    }
);

