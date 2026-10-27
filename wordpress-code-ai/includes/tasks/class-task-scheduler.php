<?php
namespace wordpress_code_ai\Tasks;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


















class Task_Scheduler {
    const HOOK  = 'wordpress_code_ai_task_tick';
    const GROUP = 'wordpress-code-ai-tasks';

    
    const AS_QUEUE_HOOK    = 'action_scheduler_run_queue';
    const AS_QUEUE_CONTEXT = 'wordpress-code-ai';

    
    public static function backend() {
        if ( ! \wordpress_code_ai\Config::get( 'wordpress_code_ai_tasks_background', true ) ) {
            return 'off';
        }
        




        if ( \apply_filters( 'wordpress_code_ai_tasks_use_action_scheduler', true ) && self::action_scheduler_ready() ) {
            return 'action-scheduler';
        }
        return 'wp-cron';
    }

    
    private static function action_scheduler_ready() {
        foreach ( array( 'as_schedule_single_action', 'as_get_scheduled_actions', 'as_unschedule_all_actions' ) as $function ) {
            if ( ! function_exists( $function ) ) {
                return false;
            }
        }
        return class_exists( '\ActionScheduler', false ) && method_exists( '\ActionScheduler', 'is_initialized' ) && \ActionScheduler::is_initialized();
    }

    





    public static function schedule( $task_id, $when, $kick = false ) {
        $backend = self::backend();
        $args    = array( (string) $task_id );
        if ( 'off' === $backend ) {
            return $backend;
        }
        if ( 'action-scheduler' === $backend ) {
            
            
            
            
            
            
            
            
            if ( ! self::has_pending_action( $args ) ) {
                \as_schedule_single_action( (int) $when, self::HOOK, $args, self::GROUP, false );
            }
            
            
            
            
            
            if ( $kick && ! \wp_next_scheduled( self::AS_QUEUE_HOOK, array( self::AS_QUEUE_CONTEXT ) ) ) {
                \wp_schedule_single_event( time(), self::AS_QUEUE_HOOK, array( self::AS_QUEUE_CONTEXT ) );
            }
        } elseif ( ! \wp_next_scheduled( self::HOOK, $args ) ) {
            \wp_schedule_single_event( (int) $when, self::HOOK, $args );
        }
        if ( $kick && function_exists( 'spawn_cron' ) ) {
            \spawn_cron();
        }
        return $backend;
    }

    private static function has_pending_action( array $args ) {
        $pending = \as_get_scheduled_actions( array(
            'hook'     => self::HOOK,
            'args'     => $args,
            'group'    => self::GROUP,
            'status'   => 'pending', 
            'per_page' => 1,
            'orderby'  => 'none',
        ), 'ids' );
        return ! empty( $pending );
    }

    
    public static function clear( $task_id ) {
        $args = array( (string) $task_id );
        \wp_clear_scheduled_hook( self::HOOK, $args );
        if ( self::action_scheduler_ready() ) {
            \as_unschedule_all_actions( self::HOOK, $args, self::GROUP );
        }
    }

    
    public static function clear_all() {
        if ( function_exists( 'wp_unschedule_hook' ) ) {
            \wp_unschedule_hook( self::HOOK );
        }
        if ( self::action_scheduler_ready() ) {
            
            
            \as_unschedule_all_actions( self::HOOK );
        }
    }
}
