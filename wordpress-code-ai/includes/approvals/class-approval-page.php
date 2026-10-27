<?php
namespace wordpress_code_ai\Approvals;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


















class Approval_Page {

    const QUERY_VAR = 'wordpress_code_ai_approve';

    
    private $gate;

    

    public function __construct( Approval_Gate $gate ) {
        $this->gate = $gate;
    }

    





    public function handle( $method, array $query, array $post ) {
        $approval_id = isset( $query[ self::QUERY_VAR ] ) ? (string) $query[ self::QUERY_VAR ] : '';
        if ( '' === $approval_id && isset( $post[ self::QUERY_VAR ] ) ) {
            $approval_id = (string) $post[ self::QUERY_VAR ];
        }
        $approval_id = \sanitize_text_field( $approval_id );
        if ( ! preg_match( '/^[0-9a-f-]{36}$/', $approval_id ) ) {
            return $this->page( 404, __( 'Approval not found', 'wordpress-code-ai' ), __( 'This approval does not exist. It may have been cleaned up, or the link was altered.', 'wordpress-code-ai' ) );
        }
        if ( ! \is_user_logged_in() ) {
            return array(
                'status'   => 302,
                'html'     => '',
                'location' => \wp_login_url( Approval_Gate::approve_url( $approval_id ) ),
            );
        }
        $row = $this->gate->get_store()->find( $approval_id );
        if ( ! $row || ! Approval_Gate::row_signature_valid( $row ) ) {
            return $this->page( 404, __( 'Approval not found', 'wordpress-code-ai' ), __( 'This approval does not exist. It may have been cleaned up, or the link was altered.', 'wordpress-code-ai' ) );
        }
        
        
        if ( (int) $row['wp_user_id'] !== (int) \get_current_user_id() ) {
            return $this->page( 403, __( 'Not your approval', 'wordpress-code-ai' ), __( 'This approval belongs to a different WordPress account. Log in as the user whose AI assistant made the request.', 'wordpress-code-ai' ) );
        }
        if ( Approval_Gate::STATUS_PENDING !== $row['status'] ) {
            return $this->page( 200, $this->status_title( $row ), $this->status_sentence( $row ) );
        }
        if ( $this->gate->row_expired( $row ) ) {
            return $this->page( 200, __( 'Approval expired', 'wordpress-code-ai' ), __( 'This request has expired. Ask your assistant to run the operation again.', 'wordpress-code-ai' ) );
        }
        if ( 'POST' === strtoupper( (string) $method ) ) {
            return $this->handle_post( $row, $post );
        }
        return $this->render_form( $row );
    }

    private function handle_post( array $row, array $post ) {
        $nonce = isset( $post['_wpnonce'] ) ? (string) $post['_wpnonce'] : '';
        if ( ! \wp_verify_nonce( $nonce, self::nonce_action( $row['approval_id'] ) ) ) {
            return $this->render_form( $row, __( 'Security check failed. Please try again.', 'wordpress-code-ai' ), 403 );
        }
        $decision = isset( $post['decision'] ) ? (string) $post['decision'] : '';
        if ( 'approve' !== $decision && 'deny' !== $decision ) {
            return $this->render_form( $row, __( 'Choose Approve or Deny.', 'wordpress-code-ai' ), 400 );
        }
        $approved = ( 'approve' === $decision );
        if ( ! $this->gate->decide( $row, $approved, \get_current_user_id(), 'link' ) ) {
            $fresh = $this->gate->get_store()->find( $row['approval_id'] );
            return $this->page( 409, $fresh ? $this->status_title( $fresh ) : __( 'Already decided', 'wordpress-code-ai' ), $fresh ? $this->status_sentence( $fresh ) : __( 'This request was decided elsewhere.', 'wordpress-code-ai' ) );
        }
        if ( $approved ) {
            return $this->page( 200, __( 'Approved', 'wordpress-code-ai' ), __( 'Approved. Ask your assistant to run the operation again; it will now execute. This approval can be used once and expires with the request.', 'wordpress-code-ai' ), $row );
        }
        return $this->page( 200, __( 'Denied', 'wordpress-code-ai' ), __( 'Denied. Nothing was changed. Your assistant will be told the operation was refused.', 'wordpress-code-ai' ), $row );
    }

    


    






    





    








    








    







    








    





    public static function nonce_action( $approval_id ) {
        return 'wordpress_code_ai_approve_' . $approval_id;
    }

    




    private function status_title( array $row ) {
        return Approval_Gate::STATUS_EXPIRED === $row['status']
            ? __( 'Approval expired', 'wordpress-code-ai' )
            : __( 'Already decided', 'wordpress-code-ai' );
    }

    private function status_sentence( array $row ) {
        switch ( $row['status'] ) {
            case Approval_Gate::STATUS_APPROVED:
                return __( 'Approved. Ask your assistant to run the operation again; it will now execute. This approval can be used once and expires with the request.', 'wordpress-code-ai' );
            case Approval_Gate::STATUS_DENIED:
                return __( 'This request was denied. Nothing was changed.', 'wordpress-code-ai' );
            case Approval_Gate::STATUS_CONSUMED:
                return __( 'This request was approved and has already been carried out.', 'wordpress-code-ai' );
            default:
                return __( 'This request has expired. Ask your assistant to run the operation again.', 'wordpress-code-ai' );
        }
    }

    







    private function render_form( array $row, $notice = '', $status = 200 ) {
        $id   = $row['approval_id'];
        $body = '';
        if ( '' !== $notice ) {
            $body .= '<p class="notice">' . \esc_html( $notice ) . '</p>';
        }
        $body .= '<p class="lead">' . \esc_html__( 'Your AI assistant wants to run an operation that cannot be undone.', 'wordpress-code-ai' ) . '</p>';
        $body .= '<blockquote class="preview">' . \esc_html( $row['preview'] ) . '</blockquote>';
        $body .= '<dl>';
        $body .= '<dt>' . \esc_html__( 'Tool', 'wordpress-code-ai' ) . '</dt><dd><code>' . \esc_html( $row['tool_name'] ) . '</code></dd>';
        $body .= '<dt>' . \esc_html__( 'Requested by', 'wordpress-code-ai' ) . '</dt><dd>' . \esc_html( $this->credential_label( $row ) ) . '</dd>';
        $body .= '<dt>' . \esc_html__( 'Expires', 'wordpress-code-ai' ) . '</dt><dd><span title="' . \esc_attr( $row['expires_at'] . ' UTC' ) . '">' . \esc_html( $this->expires_label( $row ) ) . '</span></dd>';
        $body .= '</dl>';
        $why = Approval_Gate::client_summary( $row );
        if ( '' !== $why ) {
            $body .= '<p class="meta">' . \esc_html( $why ) . '</p>';
        }
        $body .= '<form method="post" action="' . \esc_url( Approval_Gate::approve_url( $id ) ) . '">';
        $body .= '<input type="hidden" name="' . \esc_attr( self::QUERY_VAR ) . '" value="' . \esc_attr( $id ) . '">';
        $body .= \wp_nonce_field( self::nonce_action( $id ), '_wpnonce', false, false );
        $body .= '<button type="submit" name="decision" value="approve" class="approve">' . \esc_html__( 'Approve', 'wordpress-code-ai' ) . '</button> ';
        $body .= '<button type="submit" name="decision" value="deny" class="deny">' . \esc_html__( 'Deny', 'wordpress-code-ai' ) . '</button>';
        $body .= '</form>';
        return $this->page( $status, __( 'Approve this operation?', 'wordpress-code-ai' ), $body, $row, true );
    }

    




    private function credential_label( array $row ) {
        if ( 'oauth' === $row['auth_source'] ) {
            $name = $this->oauth_client_name( (string) $row['oauth_client_id'] );
            return '' !== $name
                ? sprintf( /* translators: %s: OAuth client name */ __( '%s (connected app)', 'wordpress-code-ai' ), $name )
                : sprintf( /* translators: %s: OAuth client id */ __( 'a connected app (client %s)', 'wordpress-code-ai' ), (string) $row['oauth_client_id'] );
        }
        $name = $this->api_key_name( (int) $row['token_id'] );
        return '' !== $name
            ? sprintf( /* translators: %s: API key name */ __( "API key '%s'", 'wordpress-code-ai' ), $name )
            : sprintf( /* translators: %d: API key id */ __( 'API key #%d', 'wordpress-code-ai' ), (int) $row['token_id'] );
    }

    private function oauth_client_name( $client_id ) {
        global $wpdb;
        if ( '' === $client_id || ! is_object( $wpdb ) || ! method_exists( $wpdb, 'get_row' ) ) {
            return '';
        }
        try {
            if ( ! class_exists( '\\wordpress_code_ai\\OAuth\\Client_Registry' ) ) {
                return '';
            }
            $client = ( new \wordpress_code_ai\OAuth\Client_Registry() )->get_client( $client_id );
            return is_array( $client ) && ! empty( $client['client_name'] ) ? (string) $client['client_name'] : '';
        } catch ( \Throwable $e ) {
            return '';
        }
    }

    private function api_key_name( $token_id ) {
        global $wpdb;
        if ( $token_id <= 0 || ! is_object( $wpdb ) || ! method_exists( $wpdb, 'get_row' ) ) {
            return '';
        }
        try {
            if ( ! class_exists( '\\wordpress_code_ai\\Auth\\Token_Manager' ) ) {
                return '';
            }
            $key = ( new \wordpress_code_ai\Auth\Token_Manager() )->get_token_by_id( $token_id );
            return is_array( $key ) && ! empty( $key['name'] ) ? (string) $key['name'] : '';
        } catch ( \Throwable $e ) {
            return '';
        }
    }

    
    private function expires_label( array $row ) {
        $expires = (int) strtotime( (string) $row['expires_at'] . ' UTC' );
        $now     = $this->gate->now();
        if ( $expires <= $now ) {
            return __( 'Expired', 'wordpress-code-ai' );
        }
        /* translators: %s: a human time span such as "12 mins" */
        return sprintf( __( 'in %s', 'wordpress-code-ai' ), \human_time_diff( $now, $expires ) );
    }

    


    private function page( $status, $title, $body, $row = null, $raw = false ) {
        $brand = \wordpress_code_ai\Config::brand( 'wordpress-code-ai' );
        $html  = '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">';
        $html .= '<title>' . \esc_html( $title ) . ' - ' . \esc_html( $brand ) . '</title>';
        $html .= '<style>body{font:16px/1.5 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;background:#f0f0f1;color:#1d2327;margin:0;padding:24px}main{max-width:560px;margin:40px auto;background:#fff;border:1px solid #c3c4c7;border-radius:4px;padding:24px 28px}h1{font-size:20px;margin:0 0 12px}.lead{margin:0 0 12px}.preview{margin:0 0 16px;padding:12px 16px;border-left:4px solid #d63638;background:#fcf0f1}dl{display:grid;grid-template-columns:max-content 1fr;gap:4px 16px;margin:0 0 20px}dt{color:#646970}dd{margin:0}button{font:inherit;padding:8px 18px;border-radius:3px;border:1px solid transparent;cursor:pointer}.approve{background:#2271b1;color:#fff}.deny{background:#fff;border-color:#c3c4c7;color:#1d2327}.notice{padding:8px 12px;border-left:4px solid #dba617;background:#fcf9e8;margin:0 0 12px}.brand{font-size:12px;color:#646970;margin-top:20px}</style></head><body><main>';
        $html .= '<h1>' . \esc_html( $title ) . '</h1>';
        $html .= $raw ? $body : '<p>' . \esc_html( $body ) . '</p>';
        $html .= '<p class="brand">' . \esc_html( $brand ) . '</p></main></body></html>';
        return array( 'status' => (int) $status, 'html' => $html );
    }
}
