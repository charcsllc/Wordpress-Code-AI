<?php






if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;


wp_clear_scheduled_hook( 'wordpress_code_ai_cleanup_audit_log' );
wp_clear_scheduled_hook( 'wordpress_code_ai_cleanup_oauth' );
wp_clear_scheduled_hook( 'wordpress_code_ai_cleanup_new_token_meta' );
wp_clear_scheduled_hook( 'wordpress_code_ai_cleanup_change_log' );
wp_clear_scheduled_hook( 'wordpress_code_ai_cleanup_tasks' );
wp_clear_scheduled_hook( 'wordpress_code_ai_cleanup_approvals' );
wp_clear_scheduled_hook( 'wordpress_code_ai_skills_sync' ); 
wp_clear_scheduled_hook( 'wordpress_code_ai_oauth_cimd_probe' ); 
wp_unschedule_hook( 'wordpress_code_ai_task_tick' ); 
wp_unschedule_hook( 'wordpress_code_ai_search_replace_tick' ); 
if ( function_exists( 'as_unschedule_all_actions' ) && class_exists( 'ActionScheduler', false ) && ActionScheduler::is_initialized() ) {
    as_unschedule_all_actions( 'wordpress_code_ai_task_tick' ); 
}




require_once __DIR__ . '/includes/diagnostics/class-htaccess-auth-rule.php';
wordpress_code_ai\Diagnostics\Htaccess_Auth_Rule::remove( wordpress_code_ai\Diagnostics\Htaccess_Auth_Rule::htaccess_path() );

require_once __DIR__ . '/includes/diagnostics/class-htaccess-plain-routes.php';
wordpress_code_ai\Diagnostics\Htaccess_Plain_Routes::remove( wordpress_code_ai\Diagnostics\Htaccess_Auth_Rule::htaccess_path() );


$options = array( // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
    'wordpress_code_ai_db_version',
    'wordpress_code_ai_token_schema_version',
    'wordpress_code_ai_self_service_keys',
    'wordpress_code_ai_self_service_max_keys',
    'wordpress_code_ai_self_service_max_expiry_days',
    'wordpress_code_ai_paused',                  
    'wordpress_code_ai_api_key_header_only',     
    'wordpress_code_ai_setup_complete',          
    'wordpress_code_ai_rate_limit_per_minute',
    'wordpress_code_ai_session_ttl_minutes',  
    'wordpress_code_ai_cors_origins',         
    'wordpress_code_ai_audit_log_retention',
    'wordpress_code_ai_enabled_categories',
    'wordpress_code_ai_ip_whitelist',
    'wordpress_code_ai_disabled_tools',
    'wordpress_code_ai_enabled_tools',            
    'wordpress_code_ai_rest_disabled_namespaces', 
    'wordpress_code_ai_force_draft_on_create',
    'wordpress_code_ai_max_title_length',
    'wordpress_code_ai_audit_log_enabled',
    'wordpress_code_ai_allowed_tool_patterns',
    'wordpress_code_ai_enabled_abilities',
    'wordpress_code_ai_abilities_seed_pending',   
    'wordpress_code_ai_enabled_hooks',
    'wordpress_code_ai_allowed_plugins',          
    'wordpress_code_ai_admin_language',
    'wordpress_code_ai_enabled_plugin_groups',    
    'wordpress_code_ai_learnpress_enable_pending', 
    'wordpress_code_ai_elementor_enable_pending', 
    'wordpress_code_ai_abilities_tick_lock', 
    'wordpress_code_ai_disabled_plugin_tools',   
    'wordpress_code_ai_oauth_db_version',        
    'wordpress_code_ai_oauth_access_token_ttl',  
    'wordpress_code_ai_oauth_refresh_token_ttl', 
    'wordpress_code_ai_oauth_dcr_enabled',       
    'wordpress_code_ai_oauth_max_clients',       
    'wordpress_code_ai_oauth_client_retention',  
    'wordpress_code_ai_oauth_site_host',         
    'wordpress_code_ai_oauth_cimd',              
    'wordpress_code_ai_oauth_cimd_state',        
    'wordpress_code_ai_gsc_service_account_json', 
    'wordpress_code_ai_gsc_default_site_url',     
    'wordpress_code_ai_disabled_gsc_tools',       
    'wordpress_code_ai_ga_service_account_json',  
    'wordpress_code_ai_ga_default_property_id',   
    'wordpress_code_ai_disabled_ga_tools',        
    'wordpress_code_ai_gsc_sites_cache',          
    'wordpress_code_ai_ga_properties_cache',      
    'wordpress_code_ai_ga_properties_cache_owner', 
    'wordpress_code_ai_gsc_sites_cache_owner',    
    'wordpress_code_ai_dfs_login',                
    'wordpress_code_ai_dfs_api_password',         
    'wordpress_code_ai_disabled_dfs_tools',       
    'wordpress_code_ai_semrush_api_key',          
    'wordpress_code_ai_disabled_semrush_tools',   
    'wordpress_code_ai_seranking_api_key',        
    'wordpress_code_ai_disabled_seranking_tools', 
    'wordpress_code_ai_disabled_ahrefs_tools',    
    'wordpress_code_ai_ahrefs_enabled',           
    'wordpress_code_ai_ahrefs_api_key',           
    'wordpress_code_ai_external_data_balances',   
    'wordpress_code_ai_cache_site_purge_at',      
    'wordpress_code_ai_yoast_settings_lock',      
    'wordpress_code_ai_last_tool_call',           
    'wordpress_code_ai_change_log_db_version',    
    'wordpress_code_ai_tasks_db_version',         
    'wordpress_code_ai_tasks_tick_budget_seconds', 
    'wordpress_code_ai_tasks_retention_days',     
    'wordpress_code_ai_tasks_background',         
    'wordpress_code_ai_tasks_max_per_credential', 
    'wordpress_code_ai_tasks_max_concurrent_ticks', 
    'wordpress_code_ai_approvals_db_version',     
    'wordpress_code_ai_schema_retry_core',        
    'wordpress_code_ai_schema_retry_oauth',       
    'wordpress_code_ai_schema_retry_change_log',  
    'wordpress_code_ai_schema_retry_tasks',       
    'wordpress_code_ai_schema_retry_approvals',   
    'wordpress_code_ai_skills_release',           
    'wordpress_code_ai_skills_state',             
    'wordpress_code_ai_skills_sync',              
    'wordpress_code_ai_approval_required',        
    'wordpress_code_ai_approval_always',          
    'wordpress_code_ai_approval_never',           
    'wordpress_code_ai_change_log_retention',     
    'wordpress_code_ai_change_log_enabled',       
    'wordpress_code_ai_change_log_option_mode',   
    'wordpress_code_ai_change_log_capture_meta',  
    'wordpress_code_ai_change_log_capture_db',    
    'wordpress_code_ai_change_log_db_retention',  
    'wordpress_code_ai_change_log_external_intent', 
    'wordpress_code_ai_oauth_min_capability',     
    'wordpress_code_ai_connect_roles',            
    'wordpress_code_ai_external_data_min_capability', 
    'wordpress_code_ai_diagnostics_last_run',      
    'wordpress_code_ai_oauth_root_discovery',      
    'wordpress_code_ai_oauth_root_fallback',       
    'wordpress_code_ai_header_probe_secret',       
    'wordpress_code_ai_rest_auth_observed',        
    
    
    
    
    'wordpress_code_ai_install',                   
    'wordpress-code-ai_version',                   
    'wordpress_code_ai_logger_flag',               
    'wordpress_code_ai_usage_consent_asked',       
    'wordpress_code_ai_review_flag',               
    'wordpress_code_ai_translate_flag',            
    'wordpress_code_ai_welcome_upsell_flag',       
    'wordpress_code_ai_crash_data',                
    'wordpress_code_ai_ran_migrations',            
    'wordpress_code_ai_migrated_version',          
);

if ( is_multisite() ) {
    
    delete_site_option( 'wordpress_code_ai_skills_release' );
    delete_site_option( 'wordpress_code_ai_skills_state' );
    
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s", $wpdb->esc_like( '_wordpress_code_ai_new_token_' ) . '%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    
    
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->usermeta} WHERE meta_key = %s", '_wordpress_code_ai_token_form_draft' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wordpress_code_ai_site_ids = get_sites( array( 'fields' => 'ids', 'number' => 0 ) );
    foreach ( $wordpress_code_ai_site_ids as $wordpress_code_ai_site_id ) {
        switch_to_blog( $wordpress_code_ai_site_id );
        
        wp_clear_scheduled_hook( 'wordpress_code_ai_cleanup_audit_log' );
        wp_clear_scheduled_hook( 'wordpress_code_ai_cleanup_oauth' );
        wp_clear_scheduled_hook( 'wordpress_code_ai_cleanup_new_token_meta' );
        wp_clear_scheduled_hook( 'wordpress_code_ai_cleanup_change_log' );
        wp_clear_scheduled_hook( 'wordpress_code_ai_cleanup_tasks' );
        wp_clear_scheduled_hook( 'wordpress_code_ai_cleanup_approvals' );
        wp_unschedule_hook( 'wordpress_code_ai_task_tick' );
        wp_unschedule_hook( 'wordpress_code_ai_search_replace_tick' ); 
        wp_clear_scheduled_hook( 'wordpress_code_ai_skills_sync' );
        wp_clear_scheduled_hook( 'wordpress_code_ai_oauth_cimd_probe' );
        
        $wordpress_code_ai_admin_role = get_role( 'administrator' );
        if ( $wordpress_code_ai_admin_role ) {
            $wordpress_code_ai_admin_role->remove_cap( 'wordpress_code_ai_view_all_history' );
        }
        $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wordpress_code_ai_tokens" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Intentional schema drop on uninstall.
        $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wordpress_code_ai_audit_log" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Intentional schema drop on uninstall.
        $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wordpress_code_ai_oauth_clients" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Intentional schema drop on uninstall.
        $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wordpress_code_ai_oauth_codes" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Intentional schema drop on uninstall.
        $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wordpress_code_ai_oauth_access_tokens" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Intentional schema drop on uninstall.
        $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wordpress_code_ai_oauth_consents" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Intentional schema drop on uninstall.
        $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wordpress_code_ai_oauth_device_codes" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Intentional schema drop on uninstall.
        $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wordpress_code_ai_change_log" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Intentional schema drop on uninstall.
        $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wordpress_code_ai_tasks" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Intentional schema drop on uninstall.
        $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wordpress_code_ai_approvals" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Intentional schema drop on uninstall.
        
        
        delete_transient( 'wordpress_code_ai_change_log_object_types' );
        
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_wordpress_code_ai_audit_distinct_%', '_transient_timeout_wordpress_code_ai_audit_distinct_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for transient cleanup on uninstall.

        foreach ( $options as $wordpress_code_ai_option ) {
            delete_option( $wordpress_code_ai_option );
        }
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_wordpress_code_ai_session_%', '_transient_timeout_wordpress_code_ai_session_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for transient cleanup on uninstall.
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_wordpress_code_ai_rate_%', '_transient_timeout_wordpress_code_ai_rate_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for transient cleanup on uninstall.
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_wordpress_code_ai_priv_rate_%', '_transient_timeout_wordpress_code_ai_priv_rate_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for transient cleanup on uninstall.
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_wordpress_code_ai_reflog_%', '_transient_timeout_wordpress_code_ai_reflog_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for transient cleanup on uninstall.
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_wordpress_code_ai_ipreflog_%', '_transient_timeout_wordpress_code_ai_ipreflog_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for transient cleanup on uninstall.
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_wordpress_code_ai_urlkeyreflog_%', '_transient_timeout_wordpress_code_ai_urlkeyreflog_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for transient cleanup on uninstall.
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_wordpress_code_ai_new_token_%', '_transient_timeout_wordpress_code_ai_new_token_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for transient cleanup on uninstall.
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_wordpress_code_ai_dcr_rl_%', '_transient_timeout_wordpress_code_ai_dcr_rl_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for transient cleanup on uninstall.
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_wordpress_code_ai_device_rl_%', '_transient_timeout_wordpress_code_ai_device_rl_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for transient cleanup on uninstall.
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_wordpress_code_ai_device_lookup_rl_%', '_transient_timeout_wordpress_code_ai_device_lookup_rl_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for transient cleanup on uninstall.
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_wordpress_code_ai_revoke_rl_%', '_transient_timeout_wordpress_code_ai_revoke_rl_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for transient cleanup on uninstall.
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_wordpress_code_ai_token_rl_%', '_transient_timeout_wordpress_code_ai_token_rl_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for transient cleanup on uninstall.
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_wordpress_code_ai_oat_lu_%', '_transient_timeout_wordpress_code_ai_oat_lu_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for transient cleanup on uninstall.
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_wordpress_code_ai_oat_srv_%', '_transient_timeout_wordpress_code_ai_oat_srv_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for transient cleanup on uninstall.
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_wordpress_code_ai_auth_fail_%', '_transient_timeout_wordpress_code_ai_auth_fail_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for transient cleanup on uninstall.
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_wordpress_code_ai_step_up_%', '_transient_timeout_wordpress_code_ai_step_up_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for transient cleanup on uninstall.
        
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( 'wordpress_code_ai_sr_' ) . '%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for option cleanup on uninstall.
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( 'wordpress_code_ai_layout_lock_' ) . '%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for option cleanup on uninstall (wp_update_page_layout's per-post lock).
        
        
        
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $wpdb->esc_like( '_transient_wordpress_code_ai_ga_' ) . '%', $wpdb->esc_like( '_transient_timeout_wordpress_code_ai_ga_' ) . '%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $wpdb->esc_like( '_transient_wordpress_code_ai_gsc_' ) . '%', $wpdb->esc_like( '_transient_timeout_wordpress_code_ai_gsc_' ) . '%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        
        
        
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $wpdb->esc_like( '_transient_wordpress_code_ai_dfs_balance' ) . '%', $wpdb->esc_like( '_transient_timeout_wordpress_code_ai_dfs_balance' ) . '%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        restore_current_blog();
    }
} else {
    
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wordpress_code_ai_tokens" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Intentional schema drop on uninstall.
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wordpress_code_ai_audit_log" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Intentional schema drop on uninstall.
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wordpress_code_ai_oauth_clients" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Intentional schema drop on uninstall.
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wordpress_code_ai_oauth_codes" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Intentional schema drop on uninstall.
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wordpress_code_ai_oauth_access_tokens" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Intentional schema drop on uninstall.
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wordpress_code_ai_oauth_consents" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Intentional schema drop on uninstall.
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wordpress_code_ai_oauth_device_codes" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Intentional schema drop on uninstall.
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wordpress_code_ai_change_log" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Intentional schema drop on uninstall.
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wordpress_code_ai_tasks" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Intentional schema drop on uninstall.
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wordpress_code_ai_approvals" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Intentional schema drop on uninstall.

    

    delete_transient( 'wordpress_code_ai_change_log_object_types' );
    
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_wordpress_code_ai_audit_distinct_%', '_transient_timeout_wordpress_code_ai_audit_distinct_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for transient cleanup on uninstall.


    foreach ( $options as $wordpress_code_ai_option ) {
        delete_option( $wordpress_code_ai_option );
    }

    
    $wordpress_code_ai_admin_role = get_role( 'administrator' );
    if ( $wordpress_code_ai_admin_role ) {
        $wordpress_code_ai_admin_role->remove_cap( 'wordpress_code_ai_view_all_history' );
    }

    
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_wordpress_code_ai_session_%', '_transient_timeout_wordpress_code_ai_session_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for transient cleanup on uninstall.
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_wordpress_code_ai_rate_%', '_transient_timeout_wordpress_code_ai_rate_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for transient cleanup on uninstall.
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_wordpress_code_ai_priv_rate_%', '_transient_timeout_wordpress_code_ai_priv_rate_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for transient cleanup on uninstall.
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_wordpress_code_ai_reflog_%', '_transient_timeout_wordpress_code_ai_reflog_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for transient cleanup on uninstall.
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_wordpress_code_ai_ipreflog_%', '_transient_timeout_wordpress_code_ai_ipreflog_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for transient cleanup on uninstall.
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_wordpress_code_ai_urlkeyreflog_%', '_transient_timeout_wordpress_code_ai_urlkeyreflog_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for transient cleanup on uninstall.
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_wordpress_code_ai_new_token_%', '_transient_timeout_wordpress_code_ai_new_token_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for transient cleanup on uninstall.
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_wordpress_code_ai_dcr_rl_%', '_transient_timeout_wordpress_code_ai_dcr_rl_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for transient cleanup on uninstall.
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_wordpress_code_ai_device_rl_%', '_transient_timeout_wordpress_code_ai_device_rl_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for transient cleanup on uninstall.
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_wordpress_code_ai_device_lookup_rl_%', '_transient_timeout_wordpress_code_ai_device_lookup_rl_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for transient cleanup on uninstall.
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_wordpress_code_ai_revoke_rl_%', '_transient_timeout_wordpress_code_ai_revoke_rl_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for transient cleanup on uninstall.
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_wordpress_code_ai_token_rl_%', '_transient_timeout_wordpress_code_ai_token_rl_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for transient cleanup on uninstall.
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_wordpress_code_ai_oat_lu_%', '_transient_timeout_wordpress_code_ai_oat_lu_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for transient cleanup on uninstall.
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_wordpress_code_ai_oat_srv_%', '_transient_timeout_wordpress_code_ai_oat_srv_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for transient cleanup on uninstall.
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_wordpress_code_ai_auth_fail_%', '_transient_timeout_wordpress_code_ai_auth_fail_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for transient cleanup on uninstall.
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_wordpress_code_ai_step_up_%', '_transient_timeout_wordpress_code_ai_step_up_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for transient cleanup on uninstall.
    
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( 'wordpress_code_ai_sr_' ) . '%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for option cleanup on uninstall.
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( 'wordpress_code_ai_layout_lock_' ) . '%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for option cleanup on uninstall (wp_update_page_layout's per-post lock).
    
    
    
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $wpdb->esc_like( '_transient_wordpress_code_ai_ga_' ) . '%', $wpdb->esc_like( '_transient_timeout_wordpress_code_ai_ga_' ) . '%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $wpdb->esc_like( '_transient_wordpress_code_ai_gsc_' ) . '%', $wpdb->esc_like( '_transient_timeout_wordpress_code_ai_gsc_' ) . '%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    
    
    
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $wpdb->esc_like( '_transient_wordpress_code_ai_dfs_balance' ) . '%', $wpdb->esc_like( '_transient_timeout_wordpress_code_ai_dfs_balance' ) . '%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s", $wpdb->esc_like( '_wordpress_code_ai_new_token_' ) . '%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    
    
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->usermeta} WHERE meta_key = %s", '_wordpress_code_ai_token_form_draft' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
}
