<?php
namespace wordpress_code_ai\Cli_Router\Adapters;

use wordpress_code_ai\Cli_Router\Command_Handler;
use wordpress_code_ai\Cli_Router\Context;
use wordpress_code_ai\Cli_Router\Invocation;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
















class Language_Adapter extends Command_Handler {

    
    const DEPRECATED = 'This command is deprecated. use wp site switch-language instead';

    
    public function core_activate( Invocation $inv, Context $ctx ): void {
        $ctx->out->warning( self::DEPRECATED );
        $this->site_adapter()->switch_language( $inv, $ctx );
    }

    
    protected function site_adapter(): Site_Adapter {
        return new Site_Adapter();
    }
}
