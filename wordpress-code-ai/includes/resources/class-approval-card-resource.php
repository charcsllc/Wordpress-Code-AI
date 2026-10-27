<?php
namespace wordpress_code_ai\Resources;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}













class Approval_Card_Resource extends Base_Resource {

    const URI       = 'ui://wordpress-code-ai/approval';
    const MIME_TYPE = 'text/html;profile=mcp-app';

    
    const I18N_MARKER = '<!-- wordpress-code-ai:i18n -->';

    public function get_uri() {
        return self::URI;
    }

    public function get_name() {
        return 'Approval card';
    }

    public function get_description() {
        return 'Approve or deny a paused destructive tool call in the chat.';
    }

    public function get_mime_type() {
        return self::MIME_TYPE;
    }

    
    public function get_meta() {
        return array(
            'ui' => array(
                'csp'           => new \stdClass(),
                'prefersBorder' => true,
            ),
        );
    }

    public function get_definition() {
        $definition          = parent::get_definition();
        $definition['_meta'] = $this->get_meta();
        return $definition;
    }

    public function read() {
        $file = dirname( __DIR__, 2 ) . '/assets/approval-card.html';
        $html = is_readable( $file ) ? file_get_contents( $file ) : false; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- plugin-owned static asset.
        if ( ! is_string( $html ) || '' === $html ) {
            throw new \RuntimeException( 'Approval card asset is missing.' );
        }
        $html = str_replace( '<html lang="en">', '<html lang="' . \esc_attr( self::lang() ) . '">', $html );
        return str_replace( self::I18N_MARKER, self::i18n_script( self::labels() ), $html );
    }

    






    public static function labels() {
        return array(
            'connecting'      => __( 'Connecting to the chat host…', 'wordpress-code-ai' ),
            'waiting'         => __( 'Waiting for the tool result…', 'wordpress-code-ai' ),
            'title'           => __( 'Approve this operation?', 'wordpress-code-ai' ),
            /* translators: 1: tool name, 2: expiry date and time (UTC) */
            'meta'            => __( 'Tool: %1$s · expires %2$s UTC', 'wordpress-code-ai' ),
            'working'         => __( 'Working…', 'wordpress-code-ai' ),
            'approve'         => __( 'Approve', 'wordpress-code-ai' ),
            'deny'            => __( 'Deny', 'wordpress-code-ai' ),
            'noSecret'        => __( 'This host did not receive a card secret; use the link in the chat to approve.', 'wordpress-code-ai' ),
            'notRun'          => __( 'Not run', 'wordpress-code-ai' ),
            'decisionRefused' => __( 'The decision was refused.', 'wordpress-code-ai' ),
            'denied'          => __( 'Denied', 'wordpress-code-ai' ),
            'deniedText'      => __( 'Nothing was changed. Your assistant has been told the operation was refused.', 'wordpress-code-ai' ),
            'failedText'      => __( 'The operation was approved but could not be carried out.', 'wordpress-code-ai' ),
            'done'            => __( 'Done', 'wordpress-code-ai' ),
            'doneText'        => __( 'Approved and carried out.', 'wordpress-code-ai' ),
            'hostRefused'     => __( 'The host refused the call.', 'wordpress-code-ai' ),
            'needed'          => __( 'Approval needed', 'wordpress-code-ai' ),
            'noHandshake'     => __( 'This chat host did not complete the card handshake. Use the approval link in the message above, then ask the assistant to run the operation again.', 'wordpress-code-ai' ),
        );
    }

    





    public static function i18n_script( array $labels ) {
        $json = \wp_json_encode( $labels, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE );
        return '<script>window.WORDPRESS_CODE_AI_CARD_I18N = ' . ( is_string( $json ) ? $json : '{}' ) . ';</script>';
    }

    
    private static function lang() {
        $locale = function_exists( 'determine_locale' ) ? \determine_locale() : \get_locale();
        return str_replace( '_', '-', (string) $locale );
    }
}
