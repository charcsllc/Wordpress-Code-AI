<?php
namespace wordpress_code_ai\Privileged;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}







final class Privileged_Admin {

    public static function register_cli() {
        if ( ! defined( 'WP_CLI' ) || ! WP_CLI || ! class_exists( '\\WP_CLI' ) ) {
            return;
        }
        \WP_CLI::add_command( 'wordpress-code-ai privileged status', array( __CLASS__, 'cli_status' ) );
    }

    






    public static function cli_status( $args, $assoc_args ) {
        $status = Privileged_Groups::status();
        $source = '' === $status['source'] ? 'not set' : 'from the ' . $status['source'];
        if ( ! empty( $status['strict'] ) ) {
            $source .= ', strict: tools it does not name are off';
        }
        \WP_CLI::line( sprintf( 'WORDPRESS_CODE_AI_TOOLS: %s', $source ) );
        $rows = array();
        foreach ( $status['groups'] as $group => $state ) {
            $on = array();
            foreach ( $state['tools'] as $tool ) {
                if ( $tool['on'] ) {
                    $on[] = $tool['name'];
                }
            }
            $rows[] = array(
                'group'    => $group,
                'enabled'  => $state['enabled'] ? 'yes' : 'no',
                'tools_on' => implode( ',', $on ),
            );
        }
        
        
        \WP_CLI\Utils\format_items( 'table', $rows, array( 'group', 'enabled', 'tools_on' ) );
    }
}
