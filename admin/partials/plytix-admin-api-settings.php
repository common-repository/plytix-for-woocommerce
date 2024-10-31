<?php

/**
 * Provide a admin area to setup Plytix API keys
 *
 * If API are not corrects, it will set up a flag
 * If it is first time it will show the wizard
 *
 * @link       http://plytix.com/
 * @since      1.0.0
 *
 * @package    Plytix
 * @subpackage Plytix/admin/partials
 */
?>

<?php
/**
 * If it is first time, we throw the wizard setup
 * Case 1: Coming through installation link
 * Case 2: Coming from outside but plugin has not been setup
 */
if (get_transient('plytix_redirect')) {
    delete_transient('plytix_redirect');
    $next_url    = 'admin.php?page=plytix_site';
    echo "<script> window.location='$next_url'; </script>";
}
?>

<?php $first_time =  ( (get_option('plytix_api_credentials') == FALSE) || (get_transient('plytix_config_first_time')) ); 
?>

<div class="wrap">

    <?php
    /*
     * SHOW ERROR MESSAGES
     */
    ?>
    <?php if (get_option('plytix_api_credentials') == "error") :?>
        <div class="error">
            <p><?php _e('API Keys are wrong, please C&P them from Plytix Admin Site.', 'plytix'); ?></p>
        </div>
    <?php elseif (get_transient('plytix_show_api_msg_ok')): ?>
        <?php delete_transient('plytix_show_api_msg_ok'); ?>
        <div class="updated notice is-dismissible">
            <p><?php _e('Your API Keys have been saved.', 'plytix'); ?></p>
        </div>
    <?php endif; ?>

    <?php
    /*
     * FORM AND BUTTONS
     */
    ?>
    <h2><?php echo esc_html( get_admin_page_title() );  ?></h2>
    <form method="post" action="options.php">
        <div class="plytix-step-1-container">
            <div class="updated notice">
                <p>This ID uniquely identifies your account to collect data. You can find your API Key in the Admin section of your Plytix account.</p>                
            </div>
            <?php            
                settings_fields( 'plytix-settings' );
                do_settings_sections( 'plytix-settings' );
                if ($first_time) {
                    submit_button(__('Next Step', 'plytix'));
                } else {
                    submit_button();
                }
            ?>
        </div>
    </form>

    <?php if ( (!$first_time) && (get_option('plytix_api_credentials') != "error") ) : ?>
        <script type="text/javascript">
            var keys_saved = JSON.parse('<?php echo(json_encode(get_option("plytix-settings"))); ?>');
            jQuery(document).ready(function() {
                jQuery('#submit').parents('form').submit(function(e) {
                    api_key = jQuery('input[name="plytix-settings[api_id]"]').val();
                    api_pwd = jQuery('input[name="plytix-settings[api_password]"]').val();
                    if ( (api_key != keys_saved.api_id) || (api_pwd != keys_saved.api_password) ) {
                        if (!confirm('<?php _e("If you change your API credentials, your site configuration will be removed. Do you want to continue?", "plytix"); ?>')) {
                            e.preventDefault();
                        }
                    }
                })
            });
        </script>
    <?php endif; ?>

</div>
