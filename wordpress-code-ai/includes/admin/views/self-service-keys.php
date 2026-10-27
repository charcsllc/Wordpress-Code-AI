<?php












if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$max_keys        = (int) self::limit( 'self_service_max_keys' );
$headings        = array(
    __( 'Name', 'wordpress-code-ai' ),
    __( 'Prefix', 'wordpress-code-ai' ),
    __( 'Last used (UTC)', 'wordpress-code-ai' ),
    __( 'Expiry (UTC)', 'wordpress-code-ai' ),
    __( 'Status', 'wordpress-code-ai' ),
    __( 'Actions', 'wordpress-code-ai' ),
);
?>
<h2 id="wordpress-code-ai-keys">
    <?php
    printf(
        /* translators: %s: configured plugin brand name. */
        esc_html__( '%s API keys', 'wordpress-code-ai' ),
        esc_html( \wordpress_code_ai\Config::get( 'brand_name' ) )
    );
    ?>
</h2>
<p><?php esc_html_e( 'Create a key for an automation or script. Keep it private. To change its tools or expiry, revoke it and create another.', 'wordpress-code-ai' ); ?></p>

<?php if ( $raw_token ) : ?>
<div class="notice notice-success inline">
    <p><?php esc_html_e( 'Copy this key now. It will not be shown again.', 'wordpress-code-ai' ); ?></p>
    <p><code class="wordpress-code-ai-new-key"><?php echo esc_html( $raw_token ); ?></code></p>
</div>
<?php endif; ?>

<details id="wordpress-code-ai-key-create" class="wordpress-code-ai-key-create">
    <summary class="button button-secondary"><?php esc_html_e( 'Create API key', 'wordpress-code-ai' ); ?></summary>
    <div class="wordpress-code-ai-key-create-panel">
        <p class="description">
            <?php
            printf(
                /* translators: %d: maximum active keys per user. */
                esc_html__( 'You can have up to %d active, unexpired keys, including keys created by an administrator.', 'wordpress-code-ai' ),
                $max_keys
            );
            ?>
        </p>
        <table class="form-table" role="presentation">
            <tr>
                <th><label for="wordpress-code-ai-key-name"><?php esc_html_e( 'Key name', 'wordpress-code-ai' ); ?></label></th>
                <td>
                    <input id="wordpress-code-ai-key-name" name="key_name" form="wordpress-code-ai-self-create" class="regular-text" maxlength="255" required>
                </td>
            </tr>
            <tr>
                <th><label for="wordpress-code-ai-key-expiry"><?php esc_html_e( 'Expires after', 'wordpress-code-ai' ); ?></label></th>
                <td>
                    <select id="wordpress-code-ai-key-expiry" name="expiry_days" form="wordpress-code-ai-self-create">
                        <?php foreach ( self::expiry_presets() as $days ) : ?>
                        <option value="<?php echo esc_attr( $days ); ?>" <?php selected( (string) $days, \wordpress_code_ai\Admin\Admin_Page::TOKEN_EXPIRY_DEFAULT_PRESET ); ?>>
                            <?php
                            printf(
                                /* translators: %d: key lifetime in days. */
                                esc_html( _n( '%d day', '%d days', $days, 'wordpress-code-ai' ) ),
                                (int) $days
                            );
                            ?>
                        </option>
                        <?php endforeach; ?>
                        <option value="custom"><?php esc_html_e( 'Custom', 'wordpress-code-ai' ); ?></option>
                        <option value="never"><?php esc_html_e( 'No expiration', 'wordpress-code-ai' ); ?></option>
                    </select>
                    <div class="wordpress-code-ai-key-custom-expiry">
                        <p><label for="wordpress-code-ai-key-custom-date"><?php esc_html_e( 'Custom expiry date (UTC)', 'wordpress-code-ai' ); ?></label></p>
                        <input type="date" id="wordpress-code-ai-key-custom-date" name="expiry_custom" form="wordpress-code-ai-self-create"
                               min="<?php echo esc_attr( \wordpress_code_ai\Admin\Admin_Page::token_expiry_preset_date( 1 ) ); ?>">
                    </div>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Allowed tools', 'wordpress-code-ai' ); ?></th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><?php esc_html_e( 'Select at least one tool', 'wordpress-code-ai' ); ?></legend>
                        <p class="wordpress-code-ai-key-selection" hidden>
                            <button type="button" class="button" data-select-tools="all"><?php esc_html_e( 'Select all', 'wordpress-code-ai' ); ?></button>
                            <button type="button" class="button" data-select-tools="none"><?php esc_html_e( 'Clear selection', 'wordpress-code-ai' ); ?></button>
                        </p>
                        <?php ?>
                        <div class="wordpress-code-ai-key-tools wp-mcp-tool-list">
                            <?php
                            foreach ( $tools as $tool ) :
                                
                                
                                $short_description = '';
                                if ( ! empty( $tool['description'] ) ) {
                                    $full_description  = (string) $tool['description'];
                                    $short_description = preg_match( '/^(.+?[.!?])(\s|$)/u', $full_description, $sentence )
                                        ? $sentence[1]
                                        : $full_description;
                                    if ( mb_strlen( $short_description ) > 160 ) {
                                        $short_description = rtrim( mb_substr( $short_description, 0, 157 ) ) . '…';
                                    }
                                }
                                ?>
                            <div class="wp-mcp-tool-row" data-tool-name="<?php echo esc_attr( $tool['name'] ); ?>">
                                <label class="wp-mcp-tool-label">
                                    <input type="checkbox" name="key_tools[]" value="<?php echo esc_attr( $tool['name'] ); ?>" form="wordpress-code-ai-self-create">
                                    <span class="wp-mcp-tool-info">
                                        <code class="wp-mcp-tool-name"><?php echo esc_html( $tool['name'] ); ?></code>
                                        <?php if ( '' !== $short_description ) : ?>
                                        <span class="wp-mcp-tool-description"><?php echo esc_html( $short_description ); ?></span>
                                        <?php endif; ?>
                                    </span>
                                </label>
                            </div>
                            <?php endforeach; ?>
                            <?php if ( ! $tools ) : ?>
                            <p class="wp-mcp-tool-row"><?php esc_html_e( 'No tools are available for your account.', 'wordpress-code-ai' ); ?></p>
                            <?php endif; ?>
                        </div>
                    </fieldset>
                </td>
            </tr>
        </table>
        <p>
            <button type="submit" class="button button-secondary" form="wordpress-code-ai-self-create" <?php disabled( ! $tools ); ?>><?php esc_html_e( 'Generate key', 'wordpress-code-ai' ); ?></button>
        </p>
    </div>
</details>

<table class="widefat striped">
    <thead>
        <tr>
            <?php foreach ( $headings as $heading ) : ?>
            <th scope="col"><?php echo esc_html( $heading ); ?></th>
            <?php endforeach; ?>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach ( $tokens as $token ) :
            $form_id = 'wordpress-code-ai-self-revoke-' . (int) $token['id'];
            
            $this->forms[ $form_id ] = array(
                'action' => 'wordpress_code_ai_self_revoke_key',
                'nonce'  => 'wordpress_code_ai_self_revoke_key_' . $token['id'],
                'key_id' => $token['id'],
            );
            $expired = ! empty( $token['expires_at'] ) && strtotime( $token['expires_at'] . ' UTC' ) < time();
            if ( ! $token['is_active'] ) {
                $status = __( 'Revoked', 'wordpress-code-ai' );
            } elseif ( $expired ) {
                $status = __( 'Expired', 'wordpress-code-ai' );
            } else {
                $status = __( 'Active', 'wordpress-code-ai' );
            }
            
            
            $bound_elsewhere = \wordpress_code_ai\Auth\Token_Manager::is_bound_elsewhere( $token );
            ?>
        <tr>
            <td><?php echo esc_html( $token['name'] ); ?></td>
            <td><code><?php echo esc_html( $token['token_prefix'] ); ?></code></td>
            <td><?php echo esc_html( $token['last_used_at'] ? $token['last_used_at'] : '—' ); ?></td>
            <td><?php echo esc_html( $token['expires_at'] ? $token['expires_at'] : __( 'Never', 'wordpress-code-ai' ) ); ?></td>
            <td>
                <?php echo esc_html( $status ); ?>
                <?php if ( $bound_elsewhere ) : ?>
                <br><span class="description wordpress-code-ai-token-foreign"><?php esc_html_e( 'Issued on another site — ask an administrator to re-bind it.', 'wordpress-code-ai' ); ?></span>
                <?php endif; ?>
            </td>
            <td>
                <?php if ( $token['is_active'] ) : ?>
                <button type="submit" class="button" form="<?php echo esc_attr( $form_id ); ?>"><?php esc_html_e( 'Revoke', 'wordpress-code-ai' ); ?></button>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if ( ! $tokens ) : ?>
        <tr>
            <td colspan="6"><?php esc_html_e( 'You have no API keys.', 'wordpress-code-ai' ); ?></td>
        </tr>
        <?php endif; ?>
    </tbody>
</table>

<p>
    <?php if ( $page > 1 ) : ?>
    <a href="<?php echo esc_url( admin_url( 'profile.php?wordpress_code_ai_keys_page=' . ( $page - 1 ) . '#wordpress-code-ai-keys' ) ); ?>"><?php esc_html_e( 'Previous keys', 'wordpress-code-ai' ); ?></a>
    <?php endif; ?>
    <?php if ( $more ) : ?>
    <a href="<?php echo esc_url( admin_url( 'profile.php?wordpress_code_ai_keys_page=' . ( $page + 1 ) . '#wordpress-code-ai-keys' ) ); ?>"><?php esc_html_e( 'Next keys', 'wordpress-code-ai' ); ?></a>
    <?php endif; ?>
</p>
