<?php







namespace wordpress_code_ai;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


















final class Schema_Guard {

    









    const WAIT_OPTIONS = array(
        'wordpress_code_ai_db_version'            => 'wordpress_code_ai_schema_retry_core',
        'wordpress_code_ai_oauth_db_version'      => 'wordpress_code_ai_schema_retry_oauth',
        'wordpress_code_ai_change_log_db_version' => 'wordpress_code_ai_schema_retry_change_log',
        'wordpress_code_ai_tasks_db_version'      => 'wordpress_code_ai_schema_retry_tasks',
        'wordpress_code_ai_approvals_db_version'  => 'wordpress_code_ai_schema_retry_approvals',
    );

    
    const RETRY_AFTER = 900;

    










    public static function table_found( $found, string $table ): bool {
        return is_string( $found ) && '' !== $found && 0 === strcasecmp( $found, $table );
    }

    











    public static function may_retry( string $option, string $version ): bool {
        $wait = self::wait( $option );
        if ( null === $wait || $wait['version'] !== $version || $wait['plugin'] !== self::plugin_version() ) {
            return true;
        }
        $left = $wait['until'] - time();
        return $left <= 0 || $left > self::RETRY_AFTER;
    }

    









    public static function settle( string $option, string $version, bool $finished ): void {
        if ( ! isset( self::WAIT_OPTIONS[ $option ] ) ) {
            return;
        }
        $name = self::WAIT_OPTIONS[ $option ];
        if ( ! $finished ) {
            \update_option(
                $name,
                array(
                    'version' => $version,
                    'plugin'  => self::plugin_version(),
                    'until'   => time() + self::RETRY_AFTER,
                ),
                false
            );
            return;
        }
        \delete_option( $name );
    }

    
    public static function reset(): void {
        foreach ( self::WAIT_OPTIONS as $name ) {
            \delete_option( $name );
        }
    }

    







    private static function wait( string $option ): ?array {
        if ( ! isset( self::WAIT_OPTIONS[ $option ] ) ) {
            return null;
        }
        $stored = \get_option( self::WAIT_OPTIONS[ $option ], null );
        if ( ! is_array( $stored ) || ! isset( $stored['version'], $stored['plugin'], $stored['until'] ) || ! is_int( $stored['until'] ) ) {
            return null;
        }
        return array(
            'version' => $stored['version'],
            'plugin'  => $stored['plugin'],
            'until'   => $stored['until'],
        );
    }

    
    private static function plugin_version(): string {
        return defined( 'WORDPRESS_CODE_AI_VERSION' ) ? (string) WORDPRESS_CODE_AI_VERSION : '';
    }
}
