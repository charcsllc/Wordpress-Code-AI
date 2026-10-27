<?php
namespace wordpress_code_ai\Resources;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Resource_Registry {

    private $resources = array();

    
    private $providers = array();

    
    private $provided = null;

    public function register( Base_Resource $resource ) {
        $this->resources[ $resource->get_uri() ] = $resource;
    }

    







    public function add_provider( callable $provider ): void {
        $this->providers[] = $provider;
        $this->provided    = null;
    }

    public function get_resource( $uri ) {
        if ( isset( $this->resources[ $uri ] ) ) {
            return $this->resources[ $uri ];
        }
        $provided = $this->provided();
        return isset( $provided[ $uri ] ) ? $provided[ $uri ] : null;
    }

    



    public function get_all_definitions( ?callable $include = null ): array {
        $definitions = array();
        foreach ( $this->resources as $resource ) {
            if ( null === $include || $include( $resource ) ) {
                $definitions[] = $resource->get_definition();
            }
        }
        foreach ( $this->provided() as $uri => $resource ) {
            if ( ! isset( $this->resources[ $uri ] ) && ( null === $include || $include( $resource ) ) ) {
                $definitions[] = $resource->get_definition();
            }
        }
        return $definitions;
    }

    
    private function provided(): array {
        if ( null !== $this->provided ) {
            return $this->provided;
        }
        $this->provided = array();
        foreach ( $this->providers as $provider ) {
            try {
                foreach ( (array) call_user_func( $provider ) as $resource ) {
                    if ( $resource instanceof Base_Resource ) {
                        $this->provided[ $resource->get_uri() ] = $resource;
                    }
                }
            } catch ( \Throwable $e ) {
                continue;
            }
        }
        return $this->provided;
    }

    public function auto_discover() {
        $resource_classes = array(
            
            'wordpress_code_ai\\Resources\\Site_Info_Resource',
            'wordpress_code_ai\\Resources\\Recent_Posts_Resource',
            'wordpress_code_ai\\Resources\\Site_Stats_Resource',
            
            'wordpress_code_ai\\Resources\\Reading_Settings_Resource',
            'wordpress_code_ai\\Resources\\Discussion_Settings_Resource',
            'wordpress_code_ai\\Resources\\Active_Plugins_Resource',
            'wordpress_code_ai\\Resources\\Post_Types_Resource',
            'wordpress_code_ai\\Resources\\Taxonomies_Resource',
            'wordpress_code_ai\\Resources\\Authors_Resource',
            'wordpress_code_ai\\Resources\\Theme_Templates_Resource',
            'wordpress_code_ai\\Resources\\Menus_Resource',
            'wordpress_code_ai\\Resources\\Draft_Posts_Resource',
            'wordpress_code_ai\\Resources\\Scheduled_Posts_Resource',
            'wordpress_code_ai\\Resources\\Recent_Media_Resource',
            
            'wordpress_code_ai\\Resources\\Approval_Card_Resource',
        );
        foreach ( $resource_classes as $class ) {
            if ( class_exists( $class ) ) {
                $this->register( new $class() );
            }
        }
    }
}
