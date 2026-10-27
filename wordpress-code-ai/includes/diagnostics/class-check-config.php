<?php



























namespace wordpress_code_ai\Diagnostics;

use wordpress_code_ai\Auth\Connect_Policy;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Check_Config {

    
    const MIN_SANE_RATE_LIMIT = 10;

    
    const EXPIRY_WARNING_DAYS = 7;

    


    public static function run() {
        return array(
            self::deployment_configuration(),
            self::evaluate_dcr( self::option_bool( 'wordpress_code_ai_oauth_dcr_enabled', true ) ),
            self::evaluate_client_cap( self::count_active_clients(), (int) \wordpress_code_ai\Config::get( 'wordpress_code_ai_oauth_max_clients', 5000 ) ),
            self::evaluate_min_capability( self::resolved_min_capability(), self::connect_roles() ),
            self::evaluate_rate_limit( (int) \wordpress_code_ai\Config::get( 'wordpress_code_ai_rate_limit_per_minute', 60 ) ),
            self::evaluate_force_draft( self::option_bool( 'wordpress_code_ai_force_draft_on_create', false ) ),
            self::evaluate_token_expiry( self::collect_token_expiries() ),
            self::evaluate_auth_presence( self::count_active_tokens(), self::count_active_grants(), self::auth_ever_existed() ),
            self::evaluate_allow_http(
                \wordpress_code_ai\Config::get( 'oauth_allow_http' ),
                (string) \wp_parse_url( (string) \get_option( 'home' ), PHP_URL_HOST )
            ),
            self::evaluate_oauth_enabled( (bool) \apply_filters( 'wordpress_code_ai_oauth_enabled', \wordpress_code_ai\Config::get( 'oauth_enabled' ) ) ),
            self::evaluate_token_keys( self::token_key_state() ),
            self::evaluate_privileged( self::privileged_state() ),
        );
    }

    



    private static function privileged_state() {
        return array( 'enabled' => \wordpress_code_ai\Privileged\Privileged_Groups::enabled_groups() );
    }

    








    public static function evaluate_privileged( array $state ) {
        $label = __( 'Privileged tools enabled on this site', 'wordpress-code-ai' );
        if ( empty( $state['enabled'] ) ) {
            return Diagnostic_Result::pass( 'f13', Diagnostic_Result::TIER_INFO, $label, __( 'None', 'wordpress-code-ai' ), $state );
        }
        return Diagnostic_Result::pass(
            'f13', Diagnostic_Result::TIER_INFO, $label,
            /* translators: %s: comma-separated privileged group keys */
            sprintf( __( 'Enabled: %s.', 'wordpress-code-ai' ), implode( ', ', $state['enabled'] ) ),
            $state
        );
    }

    
    private static function token_key_state() {
        return array(
            'source'  => \wordpress_code_ai\Auth\Token_Keys::source(),
            'current' => \wordpress_code_ai\Auth\Token_Keys::current_id(),
            'ids'     => \wordpress_code_ai\Auth\Token_Keys::ids(),
            'errors'  => \wordpress_code_ai\Auth\Token_Keys::errors(),
        );
    }

    








    public static function evaluate_token_keys( array $state ) {
        $label = __( 'Credential hashing (token keys)', 'wordpress-code-ai' );
        if ( $state['errors'] ) {
            $reasons = array(
                \wordpress_code_ai\Auth\Token_Keys::REASON_SHORT     => __( 'shorter than 32 bytes', 'wordpress-code-ai' ),
                \wordpress_code_ai\Auth\Token_Keys::REASON_DUPLICATE => __( 'same key ID as an earlier entry', 'wordpress-code-ai' ),
                \wordpress_code_ai\Auth\Token_Keys::REASON_MALFORMED => __( 'not a string', 'wordpress-code-ai' ),
            );
            $rejected = array();
            foreach ( $state['errors'] as $error ) {
                $rejected[] = $error['id'] . ' (' . ( $reasons[ $error['reason'] ] ?? $error['reason'] ) . ')';
            }
            $detail = sprintf(
                /* translators: %s: rejected token key entries, by position in the list, never values. */
                __( 'These token key entries were rejected: %s. Credentials issued under a rejected key are refused.', 'wordpress-code-ai' ),
                implode( ', ', $rejected )
            );
            if ( '' === $state['current'] ) {
                $detail .= ' ' . __( 'The first entry is the one rejected, so no current key is usable and new credentials are hashed with plain sha256.', 'wordpress-code-ai' );
            }
            return Diagnostic_Result::warn(
                'f12', Diagnostic_Result::TIER_WARNING, $label,
                $detail,
                __( 'Correct the WORDPRESS_CODE_AI_TOKEN_KEYS value (wp-config.php, environment or the wordpress_code_ai_token_keys filter): every secret needs at least 32 bytes, and a duplicate means the same secret is listed twice or a new secret has to be generated again.', 'wordpress-code-ai' ),
                $state
            );
        }
        if ( '' === $state['current'] ) {
            return Diagnostic_Result::pass( 'f12', Diagnostic_Result::TIER_INFO, $label, __( 'Credential hashing: sha256 (default)', 'wordpress-code-ai' ), $state );
        }
        /* translators: %s: token key ID. */
        $detail  = sprintf( __( 'Credential hashing: HMAC, current key %s', 'wordpress-code-ai' ), $state['current'] );
        $retired = array_values( array_diff( $state['ids'], array( $state['current'] ) ) );
        if ( $retired ) {
            /* translators: %s: token key IDs. */
            $detail .= sprintf( __( ', still accepting %s', 'wordpress-code-ai' ), implode( ', ', $retired ) );
        }
        return Diagnostic_Result::pass( 'f12', Diagnostic_Result::TIER_INFO, $label, $detail, $state );
    }

    
    public static function deployment_configuration() {
        $controlled = \wordpress_code_ai\Config::controlled();
        $invalid = \wordpress_code_ai\Config_Admin::invalid_settings();
        
        
        $credentials = \wordpress_code_ai\Provider_Credentials::report();
        $label = __( 'Deployment-controlled settings', 'wordpress-code-ai' );
        if ( $credentials['invalid'] ) {
            return Diagnostic_Result::warn(
                'f11', Diagnostic_Result::TIER_INFO, $label,
                implode( ', ', array_merge( $invalid, $credentials['invalid'] ) ),
                $invalid
                    ? __( 'Correct the invalid deployment settings. Safe fallback values are in use for settings, and External Data tools refuse every call while their credential constant is invalid.', 'wordpress-code-ai' )
                    : __( 'Correct the External Data credential constants named above. Their tools refuse every call until then; the reason is shown under Tools › External data.', 'wordpress-code-ai' ),
                array( 'controlled' => $controlled, 'invalid' => $invalid, 'credentials' => $credentials['deployed'], 'invalid_credentials' => $credentials['invalid'] )
            );
        }
        if ( $invalid ) {
            return Diagnostic_Result::warn(
                'f11', Diagnostic_Result::TIER_INFO, $label,
                implode( ', ', $invalid ),
                __( 'Correct the invalid deployment settings. Safe fallback values are in use.', 'wordpress-code-ai' ),
                array( 'controlled' => $controlled, 'invalid' => $invalid )
            );
        }
        $all = array_merge( $controlled, $credentials['deployed'] );
        return Diagnostic_Result::pass( 'f11', Diagnostic_Result::TIER_INFO, $label, implode( ', ', array_keys( $all ) ), $all );
    }

    




    public static function evaluate_dcr( $enabled ) {
        $label = __( 'Dynamic Client Registration', 'wordpress-code-ai' );

        if ( ! $enabled ) {
            return Diagnostic_Result::pass(
                'f1',
                Diagnostic_Result::TIER_INFO,
                $label,
                __( 'Off. New AI clients cannot register themselves, and none can be added by hand; connections that already exist keep working.', 'wordpress-code-ai' ),
                array( 'dcr_enabled' => false )
            );
        }

        return Diagnostic_Result::pass( 'f1', Diagnostic_Result::TIER_INFO, $label, __( 'On.', 'wordpress-code-ai' ), array( 'dcr_enabled' => true ) );
    }

    
    public static function evaluate_client_cap( $active, $max ) {
        $label    = __( 'OAuth client capacity', 'wordpress-code-ai' );

        if ( null === $active ) {
            return Diagnostic_Result::unknown( 'f2', Diagnostic_Result::TIER_WARNING, $label, __( 'Could not count the registered clients.', 'wordpress-code-ai' ) );
        }

        $evidence = array( 'active_clients' => (int) $active, 'max_clients' => (int) $max );

        
        
        
        
        
        if ( 0 === (int) $max ) {
            return Diagnostic_Result::warn(
                'f2',
                Diagnostic_Result::TIER_WARNING,
                $label,
                __( 'The maximum number of OAuth clients is set to 0, so every new client registration is refused with a 503. No AI client can register itself.', 'wordpress-code-ai' ),
                __( 'Set wordpress_code_ai_oauth_max_clients to a positive number — the default is 5000.', 'wordpress-code-ai' ),
                $evidence
            );
        }

        if ( (int) $max > 0 && (int) $active >= (int) $max ) {
            return Diagnostic_Result::warn(
                'f2',
                Diagnostic_Result::TIER_WARNING,
                $label,
                sprintf(
                    /* translators: 1: active client count, 2: configured maximum. */
                    __( '%1$d of %2$d registered clients in use. New client registrations are being refused with a 503.', 'wordpress-code-ai' ),
                    (int) $active,
                    (int) $max
                ),
                __( 'Raise the client cap, or revoke clients you no longer use under wordpress-code-ai → Connections → OAuth → Registered clients.', 'wordpress-code-ai' ),
                $evidence
            );
        }

        return Diagnostic_Result::pass( 'f2', Diagnostic_Result::TIER_WARNING, $label, sprintf( '%d/%d', (int) $active, (int) $max ), $evidence );
    }

    
















    public static function resolved_min_capability() {
        return Connect_Policy::floor_capability();
    }

    





    public static function connect_roles() {
        return Connect_Policy::is_filtered_for( Connect_Policy::CHANNEL_OAUTH ) ? array() : Connect_Policy::extra_roles();
    }

    






    public static function evaluate_min_capability( $cap, array $roles = array() ) {
        $detail = sprintf(
            /* translators: %s: WordPress capability name. */
            __( 'Set to %s. Users below this cannot complete the OAuth consent screen.', 'wordpress-code-ai' ),
            (string) $cap
        );
        if ( $roles ) {
            $detail = sprintf(
                /* translators: 1: WordPress capability name, 2: comma-separated role slugs. */
                __( 'Set to %1$s, and these roles are also allowed: %2$s. Other users cannot complete the OAuth consent screen.', 'wordpress-code-ai' ),
                (string) $cap,
                implode( ', ', $roles )
            );
        }
        return Diagnostic_Result::pass(
            'f3',
            Diagnostic_Result::TIER_INFO,
            __( 'Minimum capability to authorize an AI client', 'wordpress-code-ai' ),
            $detail,
            array(
                'oauth_min_capability' => (string) $cap,
                'connect_roles'        => array_values( $roles ),
            )
        );
    }

    
    public static function evaluate_rate_limit( $per_minute ) {
        $label    = __( 'Per-token rate limit', 'wordpress-code-ai' );
        $evidence = array( 'rate_limit_per_minute' => (int) $per_minute );

        
        
        
        
        
        if ( 0 === (int) $per_minute ) {
            return Diagnostic_Result::warn(
                'f4',
                Diagnostic_Result::TIER_WARNING,
                $label,
                __( 'The rate limit is set to 0 calls per minute, which refuses every call an AI client makes rather than allowing unlimited calls.', 'wordpress-code-ai' ),
                __( 'Set the rate limit under wordpress-code-ai → Settings → Access & safety to a positive number — the default of 60 suits most sites.', 'wordpress-code-ai' ),
                $evidence
            );
        }

        if ( (int) $per_minute > 0 && (int) $per_minute < self::MIN_SANE_RATE_LIMIT ) {
            return Diagnostic_Result::warn(
                'f4',
                Diagnostic_Result::TIER_WARNING,
                $label,
                sprintf(
                    /* translators: %d: configured calls per minute. */
                    __( 'Set to %d calls per minute. AI clients send bursts of calls, so a limit this low makes them fail intermittently for no visible reason.', 'wordpress-code-ai' ),
                    (int) $per_minute
                ),
                __( 'Raise the rate limit under wordpress-code-ai → Settings → Access & safety — the default of 60 suits most sites.', 'wordpress-code-ai' ),
                $evidence
            );
        }

        return Diagnostic_Result::pass( 'f4', Diagnostic_Result::TIER_WARNING, $label, sprintf( '%d/min', (int) $per_minute ), $evidence );
    }

    
    public static function evaluate_force_draft( $enabled ) {
        $label = __( 'Force draft on create', 'wordpress-code-ai' );

        if ( $enabled ) {
            return Diagnostic_Result::pass(
                'f6',
                Diagnostic_Result::TIER_INFO,
                $label,
                __( 'On. Every post or page an AI client creates is saved as a draft, whatever status it asked for.', 'wordpress-code-ai' ),
                array( 'force_draft' => true )
            );
        }

        return Diagnostic_Result::pass( 'f6', Diagnostic_Result::TIER_INFO, $label, __( 'Off.', 'wordpress-code-ai' ), array( 'force_draft' => false ) );
    }

    




    public static function evaluate_token_expiry( $tokens ) {
        $label   = __( 'API token expiry', 'wordpress-code-ai' );

        if ( ! is_array( $tokens ) ) {
            return Diagnostic_Result::unknown( 'f7', Diagnostic_Result::TIER_WARNING, $label, __( 'Could not read the API tokens.', 'wordpress-code-ai' ) );
        }

        $expired = array();
        $soon    = array();

        foreach ( $tokens as $token ) {
            $days = isset( $token['expires_in_days'] ) ? (int) $token['expires_in_days'] : null;
            $name = isset( $token['name'] ) ? (string) $token['name'] : __( 'unnamed', 'wordpress-code-ai' );
            if ( null === $days ) {
                continue;
            }
            if ( $days < 0 ) {
                $expired[] = $name;
            } elseif ( $days <= self::EXPIRY_WARNING_DAYS ) {
                $soon[] = sprintf( '%s (%dd)', $name, $days );
            }
        }

        $evidence = array( 'expired' => $expired, 'expiring_soon' => $soon );

        if ( ! empty( $expired ) || ! empty( $soon ) ) {
            $parts = array();
            if ( ! empty( $expired ) ) {
                /* translators: %s: comma-separated token names. */
                $parts[] = sprintf( __( 'Expired: %s.', 'wordpress-code-ai' ), implode( ', ', $expired ) );
            }
            if ( ! empty( $soon ) ) {
                /* translators: %s: comma-separated token names with days remaining. */
                $parts[] = sprintf( __( 'Expiring soon: %s.', 'wordpress-code-ai' ), implode( ', ', $soon ) );
            }

            return Diagnostic_Result::warn(
                'f7',
                Diagnostic_Result::TIER_WARNING,
                $label,
                implode( ' ', $parts ),
                __( 'Issue a replacement token under wordpress-code-ai → Connections → API tokens and update it in your AI client.', 'wordpress-code-ai' ),
                $evidence
            );
        }

        return Diagnostic_Result::pass( 'f7', Diagnostic_Result::TIER_WARNING, $label, '', $evidence );
    }

    








    public static function evaluate_auth_presence( $active_tokens, $active_grants, $ever_existed ) {
        $label    = __( 'Working authentication present', 'wordpress-code-ai' );

        
        
        if ( null === $active_tokens || null === $active_grants || null === $ever_existed ) {
            return Diagnostic_Result::unknown( 'f8', Diagnostic_Result::TIER_BLOCKER, $label, __( 'Could not read the API tokens or OAuth grants.', 'wordpress-code-ai' ) );
        }

        $evidence = array(
            'active_tokens' => (int) $active_tokens,
            'active_grants' => (int) $active_grants,
            'ever_existed'  => (bool) $ever_existed,
        );

        if ( (int) $active_tokens > 0 || (int) $active_grants > 0 ) {
            return Diagnostic_Result::pass(
                'f8',
                Diagnostic_Result::TIER_BLOCKER,
                $label,
                sprintf(
                    /* translators: 1: active token count, 2: active OAuth grant count. */
                    __( '%1$d API token(s), %2$d OAuth grant(s).', 'wordpress-code-ai' ),
                    (int) $active_tokens,
                    (int) $active_grants
                ),
                $evidence
            );
        }

        if ( ! $ever_existed ) {
            return Diagnostic_Result::pass(
                'f8',
                Diagnostic_Result::TIER_INFO,
                $label,
                __( 'No AI client has been connected yet. Follow the Quick Start on the dashboard to connect one.', 'wordpress-code-ai' ),
                $evidence
            );
        }

        return Diagnostic_Result::fail(
            'f8',
            Diagnostic_Result::TIER_BLOCKER,
            $label,
            __( 'Every API token and OAuth grant on this site has expired or been revoked, so no AI client can connect.', 'wordpress-code-ai' ),
            __( 'Create a new API token under wordpress-code-ai → Connections → API tokens, or reconnect your AI client to issue a fresh OAuth grant.', 'wordpress-code-ai' ),
            $evidence
        );
    }

    



    public static function evaluate_allow_http( $constant_set, $host ) {
        $label    = __( 'OAuth HTTPS requirement', 'wordpress-code-ai' );
        $host     = strtolower( trim( (string) $host ) );
        $loopback = in_array( $host, array( '127.0.0.1', 'localhost', '::1', '[::1]' ), true )
            || ( '' !== $host && ( '.local' === substr( $host, -6 ) || '.test' === substr( $host, -5 ) ) );
        $evidence = array( 'allow_http' => (bool) $constant_set, 'host' => $host );

        if ( $constant_set && ! $loopback ) {
            return Diagnostic_Result::warn(
                'f9',
                Diagnostic_Result::TIER_WARNING,
                $label,
                __( 'WORDPRESS_CODE_AI_OAUTH_ALLOW_HTTP is defined on a public host, so OAuth requests are accepted over unencrypted HTTP. Access tokens can be read in transit.', 'wordpress-code-ai' ),
                __( 'Remove the WORDPRESS_CODE_AI_OAUTH_ALLOW_HTTP line from wp-config.php. It is intended for local development only.', 'wordpress-code-ai' ),
                $evidence
            );
        }

        return Diagnostic_Result::pass( 'f9', Diagnostic_Result::TIER_WARNING, $label, '', $evidence );
    }

    




    public static function evaluate_oauth_enabled( $enabled ) {
        $label = __( 'OAuth layer active', 'wordpress-code-ai' );

        if ( ! $enabled ) {
            return Diagnostic_Result::warn(
                'f10',
                Diagnostic_Result::TIER_WARNING,
                $label,
                __( 'The whole OAuth layer is switched off by code on this site: an wordpress_code_ai_oauth_enabled filter is returning false. Every OAuth endpoint — registration, authorization, discovery — returns 404, which looks identical to the plugin being broken.', 'wordpress-code-ai' ),
                __( 'Find and remove the add_filter( \'wordpress_code_ai_oauth_enabled\', ... ) call, usually in the active theme\'s functions.php or an mu-plugin. API tokens are unaffected and keep working.', 'wordpress-code-ai' ),
                array( 'oauth_enabled' => false )
            );
        }

        return Diagnostic_Result::pass( 'f10', Diagnostic_Result::TIER_WARNING, $label, '', array( 'oauth_enabled' => true ) );
    }

    

    private static function option_bool( $name, $default ) {
        return (bool) \wordpress_code_ai\Config::get( $name, $default );
    }

    













    private static function wpdb( $method = 'get_var' ) {
        global $wpdb;
        if ( ! isset( $wpdb ) || ! is_object( $wpdb ) || ! method_exists( $wpdb, $method ) ) {
            return null;
        }
        return $wpdb;
    }

    













    private static function count_or_null( $wpdb, $sql ) {
        
        
        
        
        if ( property_exists( $wpdb, 'last_error' ) ) {
            $wpdb->last_error = '';
        }
        
        
        
        
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Plugin-owned tables; diagnostics must read live state.
        $value = $wpdb->get_var( $sql );
        if ( null === $value || '' !== (string) $wpdb->last_error ) {
            return null;
        }
        return (int) $value;
    }

    private static function count_active_clients() {
        $wpdb = self::wpdb();
        if ( null === $wpdb ) {
            return null; 
        }
        $table = $wpdb->prefix . 'wordpress_code_ai_oauth_clients';
        return self::count_or_null( $wpdb, "SELECT COUNT(*) FROM `{$table}` WHERE is_active = 1" );
    }

    private static function count_active_tokens() {
        $wpdb = self::wpdb();
        if ( null === $wpdb ) {
            return null; 
        }
        $table = $wpdb->prefix . 'wordpress_code_ai_tokens';
        return self::count_or_null( $wpdb, "SELECT COUNT(*) FROM `{$table}` WHERE is_active = 1 AND (expires_at IS NULL OR expires_at > UTC_TIMESTAMP())" );
    }

    




    private static function count_active_grants() {
        $wpdb = self::wpdb();
        if ( null === $wpdb ) {
            return null; 
        }
        $table = $wpdb->prefix . 'wordpress_code_ai_oauth_access_tokens';
        return self::count_or_null( $wpdb, "SELECT COUNT(*) FROM `{$table}` WHERE is_active = 1 AND COALESCE(refresh_expires_at, expires_at) > UTC_TIMESTAMP()" );
    }

    



    private static function auth_ever_existed() {
        $wpdb = self::wpdb();
        if ( null === $wpdb ) {
            return null; 
        }
        $tokens   = $wpdb->prefix . 'wordpress_code_ai_tokens';
        $consents = $wpdb->prefix . 'wordpress_code_ai_oauth_consents';
        $any_token   = self::count_or_null( $wpdb, "SELECT COUNT(*) FROM `{$tokens}` LIMIT 1" );
        $any_consent = self::count_or_null( $wpdb, "SELECT COUNT(*) FROM `{$consents}` LIMIT 1" );
        if ( null === $any_token || null === $any_consent ) {
            return null; 
        }

        return $any_token > 0 || $any_consent > 0;
    }

    


    private static function collect_token_expiries() {
        $wpdb = self::wpdb( 'get_results' );
        if ( null === $wpdb ) {
            return null; 
        }
        $table = $wpdb->prefix . 'wordpress_code_ai_tokens';
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Plugin-owned table; diagnostics must read live state.
        $rows = $wpdb->get_results( "SELECT name, expires_at FROM `{$table}` WHERE is_active = 1 AND expires_at IS NOT NULL", ARRAY_A );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

        if ( ! is_array( $rows ) ) {
            return null;
        }

        $out = array();
        foreach ( $rows as $row ) {
            $ts = strtotime( $row['expires_at'] . ' UTC' );
            if ( false === $ts ) {
                continue;
            }
            $out[] = array(
                'name'            => isset( $row['name'] ) ? $row['name'] : '',
                'expires_in_days' => (int) floor( ( $ts - time() ) / DAY_IN_SECONDS ),
            );
        }

        return $out;
    }
}
