<?php






namespace wordpress_code_ai\Cache;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}






final class Cache_Registry {

    const ADAPTERS = array(
        
        'rank-math-sitemap'    => 'wordpress_code_ai\\Cache\\Adapters\\Rank_Math_Sitemap',
        'seo-sitemaps'         => 'wordpress_code_ai\\Cache\\Adapters\\Seo_Sitemaps',
        'woocommerce'          => 'wordpress_code_ai\\Cache\\Adapters\\Woocommerce',
        'the-events-calendar'  => 'wordpress_code_ai\\Cache\\Adapters\\Events_Calendar',
        'smart-slider-3'       => 'wordpress_code_ai\\Cache\\Adapters\\Smart_Slider',
        'tablepress'           => 'wordpress_code_ai\\Cache\\Adapters\\Tablepress',
        'popup-maker'          => 'wordpress_code_ai\\Cache\\Adapters\\Popup_Maker',
        'smash-balloon'        => 'wordpress_code_ai\\Cache\\Adapters\\Smash_Balloon',
        'facetwp'              => 'wordpress_code_ai\\Cache\\Adapters\\Facetwp',
        
        'optimole'             => 'wordpress_code_ai\\Cache\\Adapters\\Optimole',
        'elementor'            => 'wordpress_code_ai\\Cache\\Adapters\\Elementor',
        'autoptimize'          => 'wordpress_code_ai\\Cache\\Adapters\\Autoptimize',
        
        'litespeed-cache'      => 'wordpress_code_ai\\Cache\\Adapters\\Litespeed_Cache',
        'wp-rocket'            => 'wordpress_code_ai\\Cache\\Adapters\\Wp_Rocket',
        'wp-super-cache'       => 'wordpress_code_ai\\Cache\\Adapters\\Wp_Super_Cache',
        'wp-fastest-cache'     => 'wordpress_code_ai\\Cache\\Adapters\\Wp_Fastest_Cache',
        'wp-optimize'          => 'wordpress_code_ai\\Cache\\Adapters\\Wp_Optimize',
        'w3-total-cache'       => 'wordpress_code_ai\\Cache\\Adapters\\W3_Total_Cache',
        'jetpack-boost'        => 'wordpress_code_ai\\Cache\\Adapters\\Jetpack_Boost',
        'breeze'               => 'wordpress_code_ai\\Cache\\Adapters\\Breeze',
        'sg-cachepress'        => 'wordpress_code_ai\\Cache\\Adapters\\Sg_Cachepress',
        'cache-enabler'        => 'wordpress_code_ai\\Cache\\Adapters\\Cache_Enabler',
        'speedycache'          => 'wordpress_code_ai\\Cache\\Adapters\\Speedycache',
        'hummingbird'          => 'wordpress_code_ai\\Cache\\Adapters\\Hummingbird',
        'nitropack'            => 'wordpress_code_ai\\Cache\\Adapters\\Nitropack',
        'clearfy'              => 'wordpress_code_ai\\Cache\\Adapters\\Clearfy',
        'xspeed'               => 'wordpress_code_ai\\Cache\\Adapters\\Xspeed',
        'spinupwp'             => 'wordpress_code_ai\\Cache\\Adapters\\Spinupwp',
        
        'nginx-helper'         => 'wordpress_code_ai\\Cache\\Adapters\\Nginx_Helper',
        'proxy-cache-purge'    => 'wordpress_code_ai\\Cache\\Adapters\\Proxy_Cache_Purge',
        'kinsta'               => 'wordpress_code_ai\\Cache\\Adapters\\Kinsta',
        'wp-engine'            => 'wordpress_code_ai\\Cache\\Adapters\\Wp_Engine',
        'godaddy'              => 'wordpress_code_ai\\Cache\\Adapters\\Godaddy',
        'pressable'            => 'wordpress_code_ai\\Cache\\Adapters\\Pressable',
        'pressidium'           => 'wordpress_code_ai\\Cache\\Adapters\\Pressidium',
        'flywp'                => 'wordpress_code_ai\\Cache\\Adapters\\Flywp',
        'pantheon'             => 'wordpress_code_ai\\Cache\\Adapters\\Pantheon',
        'wordpress-vip'        => 'wordpress_code_ai\\Cache\\Adapters\\Wordpress_Vip',
        'bigscoots'            => 'wordpress_code_ai\\Cache\\Adapters\\Bigscoots',
        
        'super-page-cache'     => 'wordpress_code_ai\\Cache\\Adapters\\Super_Page_Cache',
        'cloudflare'           => 'wordpress_code_ai\\Cache\\Adapters\\Cloudflare',
        
        'object-cache'         => 'wordpress_code_ai\\Cache\\Adapters\\Object_Cache',
    );

    
    const FILTER = 'wordpress_code_ai_cache_adapter_enabled';

    
    private static $adapters = null;

    




    public static function all(): array {
        if ( null === self::$adapters ) {
            $loaded = \wordpress_code_ai\Class_Map_Loader::load( self::ADAPTERS, Cache_Adapter::class );
            uasort(
                $loaded,
                static function ( Cache_Adapter $a, Cache_Adapter $b ): int {
                    return $a->tier() <=> $b->tier();
                }
            );
            self::$adapters = $loaded;
        }
        return self::$adapters;
    }

    




    public static function ids(): array {
        return array_keys( self::ADAPTERS );
    }

    







    public static function is_switched_on( Cache_Adapter $adapter ): bool {
        $default = $adapter->enabled_by_default();
        $value   = \apply_filters( self::FILTER, $default, $adapter->id() );
        return $default ? false !== $value : true === $value;
    }

    




    public static function noise_options(): array {
        return Cache_Noise::OPTIONS;
    }

    




    public static function reset(): void {
        self::$adapters = null;
    }
}
