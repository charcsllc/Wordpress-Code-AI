<?php







namespace wordpress_code_ai\Compat;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}








































abstract class Compat_Shim {

    





    abstract public function id();

    





    abstract public function applies_to( $tool );

    




    abstract public function is_needed();

    







    public function is_required() {
        return false;
    }

    










    abstract public function enter( array $arguments = array(), $tool = null );

    




    abstract public function leave( $state = null ): void;
}
