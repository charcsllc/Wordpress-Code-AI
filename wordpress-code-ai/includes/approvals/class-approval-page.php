<?php
namespace wordpress_code_ai\Approvals;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


















class Approval_Page {

    const QUERY_VAR = 'wordpress_code_ai_approve';

    
    private $gate;

    
    private $feedback_queue;

    public function __construct( Approval_Gate $gate, ?\wordpress_code_ai\Feedback\Feedback_Queue $feedback_queue = null ) {
        $this->gate           = $gate;
        $this->feedback_queue = $feedback_queue;
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
        
        
        if ( self::is_feedback_row( $row ) ) {
            return $this->handle_feedback( (string) $method, $row, $post );
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

    
    private static function is_feedback_row( array $row ): bool {
        return isset( $row['tool_name'] ) && '_feedback_report' === $row['tool_name'];
    }

    private function feedback_queue(): \wordpress_code_ai\Feedback\Feedback_Queue {
        if ( null === $this->feedback_queue ) {
            $this->feedback_queue = new \wordpress_code_ai\Feedback\Feedback_Queue( $this->gate->get_store(), array( $this->gate, 'now' ) );
        }
        return $this->feedback_queue;
    }

    






    




    private function handle_feedback( string $method, array $row, array $post ): array {
        if ( ! \wordpress_code_ai\Feedback\Feedback_Queue::may_decide( $row ) ) {
            return $this->feedback_result( 403, __( 'Not your report', 'wordpress-code-ai' ), __( 'This report was written for a different WordPress account. Log in as the user whose AI assistant wrote it, or as an administrator.', 'wordpress-code-ai' ), 'error' );
        }
        if ( Approval_Gate::STATUS_PENDING !== $row['status'] ) {
            $titles = array(
                Approval_Gate::STATUS_CONSUMED => array( __( 'Sent', 'wordpress-code-ai' ), 'ok' ),
                Approval_Gate::STATUS_DENIED   => array( __( 'Report discarded', 'wordpress-code-ai' ), 'info' ),
                Approval_Gate::STATUS_APPROVED => array( __( 'Report being sent', 'wordpress-code-ai' ), 'info' ),
            );
            list( $title, $tone ) = isset( $titles[ $row['status'] ] ) ? $titles[ $row['status'] ] : array( __( 'Report not sent', 'wordpress-code-ai' ), 'warn' );
            return $this->feedback_result( 200, $title, \wordpress_code_ai\Feedback\Feedback_Queue::status_sentence( $row ), $tone );
        }
        if ( $this->feedback_queue()->is_expired( $row ) ) {
            return $this->feedback_result( 200, __( 'Report expired', 'wordpress-code-ai' ), __( 'This report expired without being sent.', 'wordpress-code-ai' ), 'warn' );
        }
        if ( 'POST' !== strtoupper( $method ) ) {
            return $this->render_feedback_form( $row );
        }
        return $this->handle_feedback_post( $row, $post );
    }

    







    private function handle_feedback_post( array $row, array $post ): array {
        $nonce = isset( $post['_wpnonce'] ) ? (string) $post['_wpnonce'] : '';
        if ( ! \wp_verify_nonce( $nonce, self::nonce_action( $row['approval_id'] ) ) ) {
            return $this->render_feedback_form( $row, __( 'Security check failed. Please try again.', 'wordpress-code-ai' ), 403 );
        }
        $deny = isset( $post['decision'] ) && 'deny' === (string) $post['decision'];
        $sent = isset( $post['sent'] ) && '1' === (string) $post['sent'];
        if ( ! $deny && ! $sent ) {
            return $this->render_feedback_form( $row, __( 'Choose Send or Discard.', 'wordpress-code-ai' ), 400 );
        }
        $queue   = $this->feedback_queue();
        $outcome = $deny
            ? $queue->deny( $row['approval_id'], \get_current_user_id(), 'link' )
            : $queue->mark_sent( $row['approval_id'], \get_current_user_id(), 'link' );
        if ( 'sent' === $outcome['status'] ) {
            return $this->feedback_result( 200, __( 'Sent', 'wordpress-code-ai' ), $outcome['message'], 'ok' );
        }
        if ( 'denied' === $outcome['status'] ) {
            return $this->feedback_result( 200, __( 'Report discarded', 'wordpress-code-ai' ), $outcome['message'], 'info' );
        }
        return $this->feedback_result( 409, __( 'Already decided', 'wordpress-code-ai' ), $outcome['message'], 'warn' );
    }

    







    private function render_feedback_form( array $row, string $notice = '', int $status = 200 ): array {
        $id      = $row['approval_id'];
        $brand   = \wordpress_code_ai\Config::brand( 'wordpress-code-ai' );
        $payload = \wp_json_encode( \wordpress_code_ai\Feedback\Feedback_Queue::payload( $row ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES );
        $meta    = sprintf(
            /* translators: 1: who queued the report, 2: when it expires, e.g. "in 2 weeks" */
            __( 'Written by %1$s · Expires %2$s', 'wordpress-code-ai' ),
            $this->credential_label( $row ),
            $this->expires_label( $row )
        );

        $html  = '<form method="post" id="wcai-feedback-form" action="' . \esc_url( Approval_Gate::approve_url( $id ) ) . '">';
        $html .= '<div class="wcai-body">';
        if ( '' !== $notice ) {
            $html .= '<div class="wcai-notice wcai-notice--error">' . \esc_html( $notice ) . '</div>';
        }
        $html .= '<p class="wcai-text">' . \esc_html( sprintf(
            /* translators: %s: the plugin's name */
            __( 'Your AI assistant ran into something %s cannot do yet and wrote it up for us. New features usually land within 1-2 weeks. Nothing has been sent yet.', 'wordpress-code-ai' ),
            $brand
        ) ) . '</p>';
        $html .= '<div class="wcai-card wcai-card--subtle">';
        $html .= '<p class="wcai-text wcai-text--pre" data-feedback-report>' . self::report_html( (string) $row['preview'] ) . '</p>';
        $html .= '<p class="wcai-text wcai-text--small wcai-text--muted" title="' . \esc_attr( $row['expires_at'] . ' UTC' ) . '">' . \esc_html( $meta ) . '</p>';
        $html .= '</div>';
        $html .= '<div class="wcai-notice wcai-notice--error" id="wcai-feedback-error" hidden>' . \esc_html__( 'The report could not be sent, so it is still waiting. Please try again in a moment.', 'wordpress-code-ai' ) . '</div>';
        $html .= '<noscript><div class="wcai-notice wcai-notice--warn">' . \esc_html__( 'Sending needs JavaScript in this browser. Discard works without it.', 'wordpress-code-ai' ) . '</div></noscript>';
        $html .= '<p class="wcai-text wcai-text--small wcai-text--muted">' . \esc_html( \wordpress_code_ai\Feedback\Feedback_Queue::privacy_sentence() ) . '</p>';
        $html .= '<input type="hidden" name="' . \esc_attr( self::QUERY_VAR ) . '" value="' . \esc_attr( $id ) . '">';
        $html .= '<input type="hidden" name="sent" value="">';
        $html .= \wp_nonce_field( self::nonce_action( $id ), '_wpnonce', false, false );
        $html .= '</div>';
        $html .= '<div class="wcai-footer">';
        $html .= '<button type="submit" name="decision" value="deny" class="wcai-btn wcai-btn--secondary wcai-btn--lg">' . \esc_html__( 'Discard', 'wordpress-code-ai' ) . '</button>';
        $html .= '<button type="button" id="wcai-feedback-send" class="wcai-btn wcai-btn--accent wcai-btn--lg">' . \esc_html( \wordpress_code_ai\Feedback\Feedback_Queue::send_label( \wordpress_code_ai\Feedback\Feedback_Queue::kind_of( $row ) ) ) . '</button>';
        $html .= '</div>';
        $html .= '</form>';
        $html .= '<script type="application/json" id="wcai-feedback-payload">' . $payload . '</script>';
        $html .= '<script>' . self::feedback_send_script() . '</script>';
        /* translators: %s: the plugin's name */
        return $this->console_page( $status, sprintf( __( 'Send this to the %s team?', 'wordpress-code-ai' ), $brand ), $html );
    }

    






    private function feedback_result( int $status, string $title, string $message, string $tone ): array {
        $html  = '<div class="wcai-body"><div class="wcai-notice wcai-notice--' . \esc_attr( $tone ) . '">';
        $html .= '<p class="wcai-notice__title">' . \esc_html( $title ) . '</p>';
        $html .= '<p>' . \esc_html( $message ) . '</p>';
        $html .= '</div></div>';
        return $this->console_page( $status, $title, $html );
    }

    







    private function console_page( int $status, string $title, string $inner ): array {
        $html  = '<!DOCTYPE html><html lang="' . \esc_attr( \get_locale() ) . '"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex, nofollow">';
        $html .= '<title>' . \esc_html( $title ) . ' &mdash; ' . \esc_html( \wordpress_code_ai\Console_Styles::site_label() ) . '</title>';
        $html .= \wordpress_code_ai\Console_Styles::inline();
        $html .= '</head><body class="wcai-page"><div class="wcai-shell wcai-shell--narrow">';
        $html .= '<div class="wcai-topbar">' . \wordpress_code_ai\Console_Styles::logo() . '<h1 class="wcai-topbar__title">' . \esc_html( $title ) . '</h1>' . \wordpress_code_ai\Console_Styles::site_name() . '</div>';
        $html .= $inner;
        $html .= '</div>';
        $html .= '<p class="wcai-pagenote"><span class="wcai-dot"></span>' . \esc_html( \wordpress_code_ai\Config::brand( __( 'Powered by wordpress-code-ai', 'wordpress-code-ai' ) ) ) . '</p>';
        $html .= '</body></html>';
        return array( 'status' => $status, 'html' => $html );
    }

    




    private static function feedback_send_script(): string {
        $url = \wp_json_encode( \wordpress_code_ai\Feedback\Feedback_Queue::ENDPOINT, JSON_UNESCAPED_SLASHES );
        return '(function(){var f=document.getElementById("wcai-feedback-form"),b=document.getElementById("wcai-feedback-send"),e=document.getElementById("wcai-feedback-error");'
            . 'var p=JSON.parse(document.getElementById("wcai-feedback-payload").textContent);'
            . 'b.addEventListener("click",function(){b.disabled=true;e.hidden=true;'
            . 'fetch(' . $url . ',{method:"POST",headers:{"Content-Type":"application/json",Accept:"application/json, */*;q=0.1","Cache-Control":"no-cache"},body:JSON.stringify(p)})'
            . '.then(function(r){if(!r.ok){throw new Error(String(r.status));}f.elements.sent.value="1";f.submit();})'
            . '.catch(function(){b.disabled=false;e.hidden=false;});});})();';
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

    






    private static function report_html( string $text ): string {
        return htmlspecialchars( $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
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
