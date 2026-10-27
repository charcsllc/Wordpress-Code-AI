<?php









namespace wordpress_code_ai\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Support_Text {

    const SUPPORT_EMAIL = 'info@charcs.net';

    








    public static function external_test_host( $endpoint_url, $home_url ) {
        $host = (string) \wp_parse_url( (string) $endpoint_url, PHP_URL_HOST );
        $port = \wp_parse_url( (string) $endpoint_url, PHP_URL_PORT );
        if ( $port ) {
            $host .= ':' . $port;
        }
        $path = trim( (string) \wp_parse_url( (string) $home_url, PHP_URL_PATH ), '/' );
        if ( '' !== $path ) {
            $host .= '/' . $path;
        }
        return $host;
    }

    




    public static function external_test_url( $endpoint_url, $home_url ) {
        $host = self::external_test_host( $endpoint_url, $home_url );
        return '' === $host ? '' : 'https://easymcpai.com/diagnose?url=' . rawurlencode( $host );
    }

    







    public static function mailto( $home_url, $version ) {
        $subject = \__( 'wordpress-code-ai — support request', 'wordpress-code-ai' );
        $body    = implode( "\n", array(
            /* translators: %s: the site's URL. */
            sprintf( \__( 'Site: %s', 'wordpress-code-ai' ), $home_url ),
            /* translators: %s: the plugin version. */
            sprintf( \__( 'Plugin version: %s', 'wordpress-code-ai' ), $version ),
            '',
            \__( '[1] What I was trying to do:', 'wordpress-code-ai' ),
            '',
            '',
            \__( '[2] What happened instead (please include the exact error your AI client shows):', 'wordpress-code-ai' ),
            '',
            '',
            \__( '[3] When it started, and anything that changed just before:', 'wordpress-code-ai' ),
            '',
            '',
            '--- ' . \__( 'PASTE DIAGNOSTICS BELOW', 'wordpress-code-ai' ) . ' ---',
            \__( '(On the plugin dashboard, Diagnostics card, press "Copy System Info & Diagnostic", then paste here.)', 'wordpress-code-ai' ),
            '',
            '',
            '--- ' . \__( 'PASTE EXTERNAL TEST RESULT BELOW', 'wordpress-code-ai' ) . ' ---',
            \__( '(Press "Test from the internet", then copy the result and paste here.)', 'wordpress-code-ai' ),
            '',
            '',
        ) );
        return 'mailto:' . self::SUPPORT_EMAIL
            . '?subject=' . rawurlencode( $subject )
            . '&body=' . rawurlencode( $body );
    }

    







    public static function ai_prompt( $home_url, $endpoint_url, $version ) {
        $wellknown = rtrim( (string) $home_url, '/' ) . '/.well-known/';
        return implode( "\n", array(
            \__( 'You are helping me diagnose a WordPress plugin called "wordpress-code-ai", which exposes WordPress to AI assistants over the Model Context Protocol (MCP).', 'wordpress-code-ai' ),
            '',
            \__( 'THE SITE', 'wordpress-code-ai' ),
            /* translators: %s: the site's URL. */
            sprintf( \__( 'Site URL: %s', 'wordpress-code-ai' ), $home_url ),
            /* translators: %s: the plugin's MCP endpoint URL. */
            sprintf( \__( 'MCP endpoint: %s', 'wordpress-code-ai' ), $endpoint_url ),
            /* translators: %s: the site's /.well-known/ base URL. */
            sprintf( \__( 'OAuth discovery: %1$soauth-protected-resource and %1$soauth-authorization-server', 'wordpress-code-ai' ), $wellknown ),
            /* translators: %s: the plugin version. */
            sprintf( \__( 'Plugin version: %s', 'wordpress-code-ai' ), $version ),
            '',
            \__( 'WHERE TO READ THE PLUGIN SOURCE', 'wordpress-code-ai' ),
            \__( 'The plugin is open source. Its released code is readable at https://github.com/charcsllc/wordpress-code-ai — the wordpress-code-ai/ folder holds the current release. If you need to confirm how a specific check or endpoint actually behaves, read it there rather than assuming.', 'wordpress-code-ai' ),
            \__( 'The MCP specification is at https://modelcontextprotocol.io.', 'wordpress-code-ai' ),
            '',
            \__( 'MY PROBLEM', 'wordpress-code-ai' ),
            \__( '[Describe what is not working. Include the exact error message your AI client shows, if there is one.]', 'wordpress-code-ai' ),
            '',
            \__( 'WHAT I EXPECTED INSTEAD', 'wordpress-code-ai' ),
            \__( '[Describe what should have happened.]', 'wordpress-code-ai' ),
            '',
            \__( 'PLUGIN DIAGNOSTICS', 'wordpress-code-ai' ),
            \__( '[Paste here. On the plugin dashboard, Diagnostics card, press "Copy System Info & Diagnostic".]', 'wordpress-code-ai' ),
            '',
            \__( 'EXTERNAL CONNECTION TEST', 'wordpress-code-ai' ),
            \__( '[Paste here. On the same card, press "Test from the internet", then copy the result.]', 'wordpress-code-ai' ),
            '',
            \__( 'HOW TO READ THE DATA ABOVE', 'wordpress-code-ai' ),
            \__( '- The "Server" block lists the web server, PHP SAPI and PHP limits. Web server and SAPI together decide whether an Authorization header survives to PHP, which is the most common cause of "it worked yesterday and now the token is rejected".', 'wordpress-code-ai' ),
            \__( '- The "Diagnostics" block lists every check as ID, status and label. FAIL is a directly observed fault. WARN is a sign worth ruling out, not proof. UNKNOWN means the check could not run and proves nothing either way — do not treat it as a pass or a failure.', 'wordpress-code-ai' ),
            \__( '- Some check details read "[details withheld]" because they can identify WordPress users or API tokens. Treat those as status-only.', 'wordpress-code-ai' ),
            '',
            \__( 'WHAT I NEED FROM YOU', 'wordpress-code-ai' ),
            \__( '1. Name the single most likely cause, based only on the data above.', 'wordpress-code-ai' ),
            \__( '2. Quote the exact line or lines from the data that support that conclusion.', 'wordpress-code-ai' ),
            \__( '3. Give me step-by-step instructions to fix it on a WordPress site, in plain language.', 'wordpress-code-ai' ),
            \__( '4. If the data is not enough to reach a conclusion, say exactly what is missing instead of guessing.', 'wordpress-code-ai' ),
            \__( '5. If a check you rely on is UNKNOWN, tell me how to make it answerable rather than working around it.', 'wordpress-code-ai' ),
            '',
            \__( 'THINGS THAT ARE USUALLY THE CAUSE, SO CHECK THEM FIRST', 'wordpress-code-ai' ),
            \__( '- Permalinks set to "Plain": WordPress writes no rewrite rules, so /wp-json/ and /.well-known/ return 404 before PHP runs, and OAuth discovery fails even though the endpoint appears to work.', 'wordpress-code-ai' ),
            \__( '- The Authorization header never reaching PHP: on Apache the RewriteRule WordPress writes into .htaccess passes it, and under FastCGI or PHP-FPM CGIPassAuth On in the server configuration does too; nginx needs the equivalent fastcgi_param. From outside, a stripped header and a wrong token look like the same 401.', 'wordpress-code-ai' ),
            \__( '- A site behind a proxy or CDN that terminates HTTPS: PHP then sees the request as insecure and every OAuth request is refused, while the site is perfectly secure for visitors.', 'wordpress-code-ai' ),
            \__( '- A security or firewall plugin, or a host-level rule, refusing the request before WordPress sees it. This is invisible from inside WordPress, which is what the external test is for.', 'wordpress-code-ai' ),
            \__( '- A token whose permissions, OAuth scope, or WordPress user role leave it able to see no tools at all.', 'wordpress-code-ai' ),
        ) );
    }

    
















    public static function system_info( array $facts, array $diagnostics = array() ) {
        global $wpdb;

        $db_server_info = ( isset( $wpdb ) && is_object( $wpdb ) && method_exists( $wpdb, 'db_server_info' ) ) ? (string) $wpdb->db_server_info() : '';
        $db_version_num = ( isset( $wpdb ) && is_object( $wpdb ) && method_exists( $wpdb, 'db_version' ) ) ? (string) $wpdb->db_version() : '';
        $db_engine      = ( '' !== $db_server_info && false !== stripos( $db_server_info, 'mariadb' ) ) ? 'MariaDB' : 'MySQL';
        $db_display     = ( '' !== $db_version_num )
            ? $db_engine . ' ' . $db_version_num
            : ( '' !== $db_server_info ? $db_server_info : \__( 'Unknown', 'wordpress-code-ai' ) );

        $external    = isset( $facts['external'] ) && is_array( $facts['external'] ) ? $facts['external'] : array();
        $ext_count   = count( array_filter( $external ) );
        $ext_total   = count( $external );

        $set_rate_limit       = (int) \wordpress_code_ai\Config::get( 'wordpress_code_ai_rate_limit_per_minute', 60 );
        $set_audit_enabled    = (bool) \wordpress_code_ai\Config::get( 'wordpress_code_ai_audit_log_enabled', true );
        $set_audit_retention  = (int) \wordpress_code_ai\Config::get( 'wordpress_code_ai_audit_log_retention', 30 );
        $set_change_enabled   = (bool) \wordpress_code_ai\Config::get( 'wordpress_code_ai_change_log_enabled', true );
        $set_change_retention = (int) \wordpress_code_ai\Config::get( 'wordpress_code_ai_change_log_retention', 30 );
        $set_force_draft      = (bool) \wordpress_code_ai\Config::get( 'wordpress_code_ai_force_draft_on_create', false );
        $set_max_title        = (int) \wordpress_code_ai\Config::get( 'wordpress_code_ai_max_title_length', 0 );
        $set_admin_language   = (string) \get_option( 'wordpress_code_ai_admin_language', '' );
        $set_disabled_tools   = count( (array) \wordpress_code_ai\Config::get( 'wordpress_code_ai_disabled_tools', array() ) );
        $set_enabled_tools    = count( (array) \get_option( 'wordpress_code_ai_enabled_tools', array() ) );
        
        
        $set_tool_overrides   = 'Not set';
        if ( defined( 'WORDPRESS_CODE_AI_TOOLS' ) || false !== getenv( 'WORDPRESS_CODE_AI_TOOLS' ) ) {
            $set_tool_overrides = count( \wordpress_code_ai\Tools\Tool_State::overrides() ) . ' (' . \wordpress_code_ai\Tools\Tool_State::override_source()
                . ( \wordpress_code_ai\Tools\Tool_State::is_strict() ? ', strict' : '' ) . ')';
        }
        $set_allowed_patterns = count( (array) \wordpress_code_ai\Config::get( 'wordpress_code_ai_allowed_tool_patterns', array() ) );
        $set_oauth_cap        = \wordpress_code_ai\Auth\Connect_Policy::stored_floor();
        
        $set_connect_roles    = \wordpress_code_ai\Auth\Connect_Policy::is_filtered_for( \wordpress_code_ai\Auth\Connect_Policy::CHANNEL_OAUTH )
            ? array()
            : \wordpress_code_ai\Auth\Connect_Policy::extra_roles();
        $set_connect_roles    = $set_connect_roles ? implode( ', ', $set_connect_roles ) : 'None';
        $set_ext_cap          = (string) \wordpress_code_ai\Config::get( 'wordpress_code_ai_external_data_min_capability', 'manage_options' );
        $set_ip_entries       = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) \wordpress_code_ai\Config::get( 'wordpress_code_ai_ip_whitelist', '' ) ) ) );
        $set_ip_count         = count( $set_ip_entries );
        $set_ip_display       = ( 0 === $set_ip_count )
            ? 'None'
            : 'Configured (' . $set_ip_count . ( 1 === $set_ip_count ? ' entry)' : ' entries)' );

        $srv_software = isset( $_SERVER['SERVER_SOFTWARE'] ) ? \sanitize_text_field( \wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : 'Unknown';
        
        
        $srv_mem_raw    = isset( $GLOBALS['wordpress_code_ai_ini_memory_limit'] ) ? (string) $GLOBALS['wordpress_code_ai_ini_memory_limit'] : (string) ini_get( 'memory_limit' );
        $srv_permalinks = (string) \get_option( 'permalink_structure', '' );
        $srv_dropin     = ( defined( 'WP_CONTENT_DIR' ) && file_exists( WP_CONTENT_DIR . '/object-cache.php' ) );
        $srv_ext_cache  = ( function_exists( 'wp_using_ext_object_cache' ) && \wp_using_ext_object_cache() );
        if ( function_exists( 'is_multisite' ) && \is_multisite() ) {
            $srv_multisite = ( function_exists( 'is_subdomain_install' ) && \is_subdomain_install() ) ? 'Yes (subdomain)' : 'Yes (sub-directory)';
        } else {
            $srv_multisite = 'No';
        }

        $srv_proxy_headers = array();
        foreach ( array(
            'HTTP_X_FORWARDED_PROTO' => 'X-Forwarded-Proto',
            'HTTP_X_FORWARDED_FOR'   => 'X-Forwarded-For',
            'HTTP_X_REAL_IP'         => 'X-Real-IP',
            'HTTP_FORWARDED'         => 'Forwarded',
            'HTTP_CF_CONNECTING_IP'  => 'CF-Connecting-IP',
        ) as $key => $label ) {
            if ( ! empty( $_SERVER[ $key ] ) ) {
                $srv_proxy_headers[] = $label;
            }
        }

        $srv_remote = isset( $_SERVER['REMOTE_ADDR'] ) ? \sanitize_text_field( \wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
        $srv_remote_desc = class_exists( '\wordpress_code_ai\Client_IP' ) ? \wordpress_code_ai\Client_IP::describe( $srv_remote ) : $srv_remote;

        $srv_ext_list = array();
        foreach ( array( 'curl', 'openssl', 'mbstring', 'json', 'redis', 'memcached' ) as $ext ) {
            $srv_ext_list[] = $ext . ( extension_loaded( $ext ) ? '=yes' : '=NO' );
        }

        $srv_theme        = function_exists( 'wp_get_theme' ) ? \wp_get_theme() : null;
        $srv_active_count = count( (array) \get_option( 'active_plugins', array() ) );
        if ( function_exists( 'is_multisite' ) && \is_multisite() && function_exists( 'get_site_option' ) ) {
            $srv_active_count += count( (array) \get_site_option( 'active_sitewide_plugins', array() ) );
        }

        $text = implode( "\n", array(
            '# PRIVATE — describes this site. Send to ' . self::SUPPORT_EMAIL . '; do not post publicly.',
            '',
            'wordpress-code-ai — System Info',
            'Plugin Version:   ' . ( defined( 'WORDPRESS_CODE_AI_VERSION' ) ? WORDPRESS_CODE_AI_VERSION : '' ),
            'WordPress:        ' . \get_bloginfo( 'version' ),
            'PHP Version:      ' . PHP_VERSION,
            'Database:         ' . $db_display,
            'Protocol:         2025-11-25 / 2025-06-18 / 2025-03-26',
            'Active Tokens:    ' . (int) ( $facts['token_count'] ?? 0 ),
            'Active Clients:   ' . (int) ( $facts['oauth_client_count'] ?? 0 ),
            'Registered Tools: ' . (int) ( $facts['tool_count'] ?? 0 ),
            'External Data:    ' . $ext_count . '/' . $ext_total,
            '',
            'Server',
            'Web Server:            ' . $srv_software,
            'PHP SAPI:              ' . PHP_SAPI,
            'Operating System:      ' . PHP_OS,
            'Memory Limit (REST):   ' . $srv_mem_raw,
            'WP_MEMORY_LIMIT:       ' . ( defined( 'WP_MEMORY_LIMIT' ) ? WP_MEMORY_LIMIT : 'not defined' ),
            'WP_MAX_MEMORY_LIMIT:   ' . ( defined( 'WP_MAX_MEMORY_LIMIT' ) ? WP_MAX_MEMORY_LIMIT : 'not defined' ) . '  (ceiling for admin/cron/image work, not the REST path)',
            'Max Execution Time:    ' . ini_get( 'max_execution_time' ) . 's',
            'Max Input Time:        ' . ini_get( 'max_input_time' ) . 's',
            'Post Max Size:         ' . ini_get( 'post_max_size' ),
            'Upload Max Filesize:   ' . ini_get( 'upload_max_filesize' ),
            'Max Input Vars:        ' . ini_get( 'max_input_vars' ),
            'allow_url_fopen:       ' . ( filter_var( ini_get( 'allow_url_fopen' ), FILTER_VALIDATE_BOOLEAN ) ? 'On' : 'Off' ),
            'PHP Extensions:        ' . implode( ' ', $srv_ext_list ),
            'HTTPS (as PHP sees):   ' . ( function_exists( 'is_ssl' ) && \is_ssl() ? 'Yes' : 'No' ),
            'Proxy Headers:         ' . ( empty( $srv_proxy_headers ) ? 'None' : implode( ', ', $srv_proxy_headers ) ),
            'REMOTE_ADDR:           ' . $srv_remote_desc,
            'Permalinks:            ' . ( '' === $srv_permalinks ? 'Plain (breaks discovery)' : $srv_permalinks ),
            'Object Cache Drop-in:  ' . ( $srv_dropin ? ( $srv_ext_cache ? 'Present and in use' : 'Present but NOT in use' ) : 'None (database transients)' ),
            'Multisite:             ' . $srv_multisite,
            'WP_DEBUG:              ' . ( defined( 'WP_DEBUG' ) && WP_DEBUG ? 'On' : 'Off' ),
            'DISABLE_WP_CRON:       ' . ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ? 'Yes (needs a system cron)' : 'No' ),
            'AES-256-GCM:           ' . ( function_exists( 'openssl_get_cipher_methods' ) && in_array( 'aes-256-gcm', array_map( 'strtolower', openssl_get_cipher_methods() ), true ) ? 'Available' : 'MISSING' ),
            'Timezone:              ' . \wp_timezone_string() . ' (server ' . date_default_timezone_get() . ')',
            'Locale:                ' . \get_locale(),
            'Active Theme:          ' . ( $srv_theme && is_object( $srv_theme ) && method_exists( $srv_theme, 'get' ) ? $srv_theme->get( 'Name' ) . ' ' . $srv_theme->get( 'Version' ) : 'Unknown' ),
            'Active Plugins:        ' . $srv_active_count,
            '',
            'Settings',
            'Rate Limit (per min):  ' . $set_rate_limit,
            'Audit Log:             ' . ( $set_audit_enabled ? 'Enabled (' . $set_audit_retention . '-day retention)' : 'Disabled' ),
            'Change History:        ' . ( $set_change_enabled ? 'Enabled (' . $set_change_retention . '-day retention)' : 'Disabled' ),
            'Force Draft on Create: ' . ( $set_force_draft ? 'Yes' : 'No' ),
            'Max Title Length:      ' . ( 0 === $set_max_title ? '0 (unlimited)' : $set_max_title ),
            'Admin Language:        ' . ( '' === $set_admin_language ? 'Site default' : $set_admin_language ),
            'Disabled Tools:        ' . $set_disabled_tools,
            'Enabled Tools:         ' . $set_enabled_tools,
            'Tool Overrides:        ' . $set_tool_overrides,
            'Allowed Tool Patterns: ' . $set_allowed_patterns,
            'OAuth Min Capability:  ' . $set_oauth_cap,
            'Connect Roles:         ' . $set_connect_roles,
            'External Data Min Cap: ' . $set_ext_cap,
            'IP Whitelist:          ' . $set_ip_display,
        ) );

        $results = isset( $diagnostics['results'] ) && is_array( $diagnostics['results'] ) ? $diagnostics['results'] : array();
        if ( $results ) {
            $summary   = isset( $diagnostics['summary'] ) && is_array( $diagnostics['summary'] ) ? $diagnostics['summary'] : array();
            $last_run  = isset( $diagnostics['last_run'] ) ? $diagnostics['last_run'] : null;
            $copy_safe = isset( $diagnostics['copy_safe'] ) && is_callable( $diagnostics['copy_safe'] ) ? $diagnostics['copy_safe'] : '__return_true';
            $age       = $last_run
                ? gmdate( 'Y-m-d H:i:s', (int) $last_run ) . ' UTC (' . \human_time_diff( (int) $last_run, time() ) . ' ago)'
                : 'unknown';
            $lines     = array(
                '',
                'Diagnostics generated: ' . $age,
                'Diagnostics (' . (int) ( $summary['total'] ?? 0 ) . ' checks: '
                    . (int) ( $summary['pass'] ?? 0 ) . ' passed, '
                    . (int) ( $summary['warn'] ?? 0 ) . ' warnings, '
                    . (int) ( $summary['fail'] ?? 0 ) . ' failed, '
                    . (int) ( $summary['unknown'] ?? 0 ) . ' not checked)',
            );
            foreach ( $results as $r ) {
                if ( ! is_object( $r ) || ! method_exists( $r, 'id' ) ) {
                    continue;
                }
                $line   = str_pad( strtoupper( $r->id() ), 4 ) . str_pad( $r->status(), 8 ) . $r->label();
                $detail = trim( (string) $r->detail() );
                if ( '' !== $detail ) {
                    $line .= call_user_func( $copy_safe, $r->id() )
                        ? ' — ' . $detail
                        : ' — [details withheld: sensitive — read them on this site\'s Diagnostics card]';
                }
                $lines[] = $line;
            }
            $text .= "\n" . implode( "\n", $lines );
        }

        return $text;
    }
}
