<?php
namespace wordpress_code_ai\Cli_Router;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}








final class Cli_Parse_Error extends \Exception {

    
    private $reason;

    



    public function __construct( string $reason, string $message ) {
        parent::__construct( $message );
        $this->reason = $reason;
    }

    
    public function get_reason(): string {
        return $this->reason;
    }
}
