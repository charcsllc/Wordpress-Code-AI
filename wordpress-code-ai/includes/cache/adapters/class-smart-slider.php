<?php






namespace wordpress_code_ai\Cache\Adapters;

use wordpress_code_ai\Cache\Cache_Adapter;
use wordpress_code_ai\Cache\Layer_Result;
use wordpress_code_ai\Cache\Purge_Request;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

















final class Smart_Slider extends Cache_Adapter {

    const APP   = 'Nextend\\SmartSlider3\\Application\\ApplicationSmartSlider3';
    const MODEL = 'Nextend\\SmartSlider3\\Application\\Model\\ModelSliders';

    public function id(): string {
        return 'smart-slider-3';
    }

    public function label(): string {
        return 'Smart Slider 3';
    }

    public function kind(): string {
        return self::KIND_CONTENT;
    }

    public function tier(): int {
        return self::TIER_DATA;
    }

    public function is_present(): bool {
        return $this->calls()->defined( 'NEXTEND_SMARTSLIDER_3' );
    }

    protected function purge_site( Purge_Request $request ): Layer_Result {
        $model = $this->model();
        if ( null === $model ) {
            return $this->helper_missing( 'ModelSliders::invalidateCache()' );
        }
        $this->calls()->call( array( $model, 'invalidateCache' ) );
        return $this->sent( 'Smart Slider 3 cleared the stored output of every slider.' );
    }

    





    private function model(): ?object {
        $calls = $this->calls();
        if ( ! $calls->method_exists( self::APP, 'getInstance' ) || ! $calls->method_exists( self::MODEL, 'invalidateCache' ) ) {
            return null;
        }
        $app = $calls->call( array( self::APP, 'getInstance' ) );
        if ( ! is_object( $app ) || ! $calls->method_exists( $app, 'getApplicationTypeAdmin' ) ) {
            return null;
        }
        $type = $calls->call( array( $app, 'getApplicationTypeAdmin' ) );
        return is_object( $type ) ? $calls->instantiate( self::MODEL, array( $type ) ) : null;
    }
}
