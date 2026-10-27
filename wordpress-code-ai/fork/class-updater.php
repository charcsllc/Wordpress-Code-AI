<?php
/**
 * Offers updates from this plugin's GitHub releases.
 *
 * The Update URI header points at github.com, so WordPress does not ask
 * WordPress.org about this plugin and fires update_plugins_github.com for it
 * instead. Every release carries update.json, written by
 * tools/build-release.py, which names the version and its ZIP.
 */

namespace wordpress_code_ai\Fork;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Updater {

    const SLUG = 'wordpress-code-ai';

    // The repository's exact name and case, as in tools/build-release.py.
    const REPOSITORY = 'https://github.com/charcsllc/Wordpress-Code-AI';

    // GitHub redirects this to the update.json of the newest published release.
    const MANIFEST_URL = self::REPOSITORY . '/releases/latest/download/update.json';

    const MAX_BYTES = 65536;

    const TIMEOUT = 10;

    /**
     * The manifest for this request: an array, null when it could not be read,
     * or false before the first read.
     *
     * @var array|null|false
     */
    private static $manifest = false;

    public static function register(): void {
        \add_filter( 'update_plugins_github.com', array( __CLASS__, 'offer' ), 10, 3 );
        \add_filter( 'plugins_api', array( __CLASS__, 'details' ), 10, 3 );
    }

    /**
     * The update WordPress shows on the Plugins and Updates screens. WordPress
     * compares the versions itself, so the newest release is returned even
     * when it is the installed one: that is what lets auto-updates be turned on.
     */
    public static function offer( $update, $plugin_data, $plugin_file ) {
        if ( WORDPRESS_CODE_AI_PLUGIN_BASENAME !== $plugin_file ) {
            return $update;
        }
        $manifest = self::manifest();
        if ( null === $manifest ) {
            return $update;
        }
        $icon = WORDPRESS_CODE_AI_PLUGIN_URL . 'assets/images/icon-256x256.png';
        return array(
            'slug'         => self::SLUG,
            'version'      => $manifest['version'],
            'url'          => $manifest['url'],
            'package'      => $manifest['package'],
            'requires'     => $manifest['requires'],
            'requires_php' => $manifest['requires_php'],
            'tested'       => self::tested( $manifest['tested'] ),
            'icons'        => array(
                '2x' => $icon,
                '1x' => $icon,
            ),
        );
    }

    /**
     * The "View details" window. Without this, WordPress would ask
     * WordPress.org, which does not list this plugin.
     */
    public static function details( $result, $action, $args ) {
        if ( 'plugin_information' !== $action || ! is_object( $args ) || ! isset( $args->slug ) || self::SLUG !== $args->slug ) {
            return $result;
        }
        $manifest = self::manifest();
        if ( null === $manifest ) {
            return new \WP_Error( 'plugins_api_failed', \__( 'The release details could not be loaded from GitHub. Try again in a few minutes.', 'wordpress-code-ai' ) );
        }
        return (object) array(
            'name'          => 'wordpress-code-ai',
            'slug'          => self::SLUG,
            'version'       => $manifest['version'],
            'author'        => '<a href="https://github.com/charcsllc">charcsllc</a>',
            'homepage'      => self::REPOSITORY,
            'requires'      => $manifest['requires'],
            'requires_php'  => $manifest['requires_php'],
            'tested'        => self::tested( $manifest['tested'] ),
            'last_updated'  => $manifest['released'],
            'download_link' => $manifest['package'],
            'sections'      => array(
                'changelog' => $manifest['changelog'],
            ),
        );
    }

    /**
     * "Tested up to: 7.1" covers every 7.1.x, as on WordPress.org. Without this,
     * WordPress 7.1.3 would warn that the plugin was not tested with it.
     */
    private static function tested( string $tested ): string {
        $running = (string) \get_bloginfo( 'version' );
        return preg_match( '/^\d+\.\d+$/', $tested ) && 0 === strpos( $running, $tested . '.' ) ? $running : $tested;
    }

    private static function manifest(): ?array {
        if ( false === self::$manifest ) {
            self::$manifest = self::fetch();
        }
        return self::$manifest;
    }

    private static function fetch(): ?array {
        $response = \wp_safe_remote_get(
            self::MANIFEST_URL,
            array(
                'timeout'             => self::TIMEOUT,
                'limit_response_size' => self::MAX_BYTES,
                'user-agent'          => 'wordpress-code-ai/' . WORDPRESS_CODE_AI_VERSION,
                'headers'             => array( 'Accept' => 'application/json' ),
            )
        );
        if ( \is_wp_error( $response ) || 200 !== (int) \wp_remote_retrieve_response_code( $response ) ) {
            return null;
        }
        return self::parse( json_decode( (string) \wp_remote_retrieve_body( $response ), true ) );
    }

    /**
     * Checks a decoded update.json. Only a ZIP attached to a release of this
     * repository is ever offered.
     */
    public static function parse( $data ): ?array {
        if ( ! is_array( $data ) ) {
            return null;
        }
        $text = static function ( $key ) use ( $data ) {
            return isset( $data[ $key ] ) && is_string( $data[ $key ] ) ? $data[ $key ] : '';
        };
        $version = $text( 'version' );
        $package = $text( 'package' );
        if ( ! preg_match( '/^\d+(?:\.\d+){1,3}$/', $version ) || 0 !== strpos( $package, self::REPOSITORY . '/releases/download/' ) ) {
            return null;
        }
        $url = $text( 'url' );
        return array(
            'version'      => $version,
            'package'      => $package,
            'url'          => 0 === strpos( $url, self::REPOSITORY . '/' ) ? $url : self::REPOSITORY . '/releases',
            'requires'     => $text( 'requires' ),
            'requires_php' => $text( 'requires_php' ),
            'tested'       => $text( 'tested' ),
            'released'     => $text( 'released' ),
            'changelog'    => \wp_kses_post( $text( 'changelog' ) ),
        );
    }
}
