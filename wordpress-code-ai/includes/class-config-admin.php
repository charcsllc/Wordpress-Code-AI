<?php

namespace wordpress_code_ai;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Config_Admin {
    public static function register() {
        \add_action( 'admin_init', array( __CLASS__, 'guard_page' ), 0 );
        
        
        \add_action( 'admin_head', array( __CLASS__, 'hide_menus' ), PHP_INT_MAX );
        \add_action( 'admin_notices', array( __CLASS__, 'invalid_notice' ) );
        \add_filter( 'all_plugins', array( __CLASS__, 'plugin_rows' ) );
        \add_filter( 'gettext_wordpress-code-ai', array( __CLASS__, 'brand_text' ), 10, 2 );
        \add_filter( 'ngettext_wordpress-code-ai', array( __CLASS__, 'brand_text' ), 10, 2 );
        
        foreach ( Config::settings() as $suffix => $spec ) {
            $option = 'wordpress_code_ai_' . $suffix;
            \add_filter( 'pre_update_option_' . $option, static function ( $value, $old ) use ( $option ) {
                
                
                
                
                
                
                if ( \doing_action( 'activate_' . WORDPRESS_CODE_AI_PLUGIN_BASENAME ) ) {
                    return $value;
                }
                return Config::is_locked( $option ) ? $old : $value;
            }, PHP_INT_MAX, 2 );
        }
        if ( defined( 'WP_CLI' ) && WP_CLI ) {
            \WP_CLI::add_command( 'wordpress-code-ai config export', static function () {
                \WP_CLI::line( Config::export() );
            } );
        }
    }

    public static function brand_text( $translated, $original = '' ) {
        return Config::brand( $translated );
    }

    






    public static function is_plugin_page( $slug ) {
        if ( 'wordpress-code-ai' === $slug || 0 === strpos( $slug, 'wordpress-code-ai-' ) ) {
            return true;
        }
        return class_exists( Themeisle_SDK::class ) && Themeisle_SDK::ABOUT_US_MENU_SLUG === $slug;
    }

    public static function guard_page() {
        
        
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only routing.
        $page = isset( $_GET['page'] ) && is_string( $_GET['page'] ) ? \plugin_basename( \wp_unslash( $_GET['page'] ) ) : '';
        if ( true === Config::get( 'hide_admin' ) && self::is_plugin_page( $page ) ) {
            \wp_die( \esc_html__( 'This administration page is disabled by the site configuration.', 'wordpress-code-ai' ), '', array( 'response' => 403 ) );
        }
    }

    public static function hide_menus() {
        if ( ! Config::get( 'hide_admin' ) ) {
            return;
        }
        
        \remove_menu_page( 'wordpress-code-ai' );
        global $submenu;
        foreach ( (array) $submenu as $parent => $items ) {
            foreach ( $items as $item ) {
                if ( isset( $item[2] ) && self::is_plugin_page( $item[2] ) ) {
                    \remove_submenu_page( $parent, $item[2] );
                }
            }
        }
    }

    public static function plugin_rows( $plugins ) {
        if ( Config::get( 'hide_plugin_row' ) && ! ( defined( 'WP_CLI' ) && WP_CLI ) ) {
            unset( $plugins[ WORDPRESS_CODE_AI_PLUGIN_BASENAME ] );
        }
        return $plugins;
    }

    
    public static function field_start( $option ) {
        if ( ! Config::is_locked( $option ) ) {
            return;
        }
        echo '<div class="wordpress-code-ai-config-field"><fieldset disabled>';
    }

    public static function field_end( $option ) {
        if ( ! Config::is_locked( $option ) ) {
            return;
        }
        echo '</fieldset></div>';
    }

    public static function field_lock( $option ) {
        if ( ! Config::is_locked( $option ) ) {
            return;
        }
        $message = 'constant' === Config::source( $option )
            /* translators: %s: configuration constant name. */
            ? __( 'Managed in wp-config.php: %s', 'wordpress-code-ai' )
            /* translators: %s: environment variable name. */
            : __( 'Managed by environment variable: %s', 'wordpress-code-ai' );
        $label = sprintf( $message, Config::constant_name( $option ) );
        echo '<span class="wordpress-code-ai-config-lock" tabindex="0" role="img" aria-label="' . \esc_attr( $label ) . '">';
        echo '<span class="dashicons dashicons-lock" aria-hidden="true"></span></span>';
    }

    public static function summary() {
        $controlled = Config::controlled();
        if ( ! $controlled ) {
            return;
        }
        echo '<details class="wordpress-code-ai-config-summary"><summary>' . \esc_html__( 'Deployment-controlled settings', 'wordpress-code-ai' ) . '</summary><ul>';
        foreach ( $controlled as $name => $source ) {
            echo '<li><code>' . \esc_html( $name ) . '</code> (' . \esc_html( $source ) . ')</li>';
        }
        echo '</ul></details>';
    }

    public static function invalid_settings() {
        $invalid = array();
        foreach ( Config::settings() as $suffix => $spec ) {
            $source = Config::source( $suffix );
            if ( '' === $source ) {
                continue;
            }
            $name = Config::constant_name( $suffix );
            Config::validate( $suffix, 'constant' === $source ? constant( $name ) : getenv( $name ), $valid );
            if ( ! $valid ) {
                $invalid[] = $name;
            }
        }
        return $invalid;
    }

    public static function invalid_notice() {
        if ( Config::get( 'hide_admin' ) || ! \current_user_can( 'manage_options' ) ) {
            return;
        }
        $invalid = self::invalid_settings();
        if ( $invalid ) {
            echo '<div class="notice notice-error"><p>' . \esc_html( sprintf(
                /* translators: %s: deployment setting names, never values. */
                __( 'wordpress-code-ai: Invalid deployment settings use safe fallback values: %s', 'wordpress-code-ai' ),
                implode( ', ', $invalid )
            ) ) . '</p></div>';
        }
    }
}
