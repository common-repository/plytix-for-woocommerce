<?php
/**
 * Provide a admin area to setup a site
 * If the API keys are wrong (_transient_plytix_api_credential_fail)
 * You cannot register your site.
 *
 *
 * @link       http://plytix.com/
 * @since      1.0.0
 *
 * @package    Plytix
 * @subpackage Plytix/admin/partials
 */
?>

<?php
?>

<?php $first_time  = get_transient('plytix_config_first_time'); ?>
<?php $js_control  = false; ?>

<div class="wrap plytix-step-2-container">
    <?php
    /**
     * First of All:
     * If We haven't set up Plytix Configuration and is not first time
     * Send user to Wizard
     */
    ?>
    <?php if (get_transient('plytix_show_config_msg_ok')) :?>
        <div class="updated notice is-dismissible">
            <p><?php _e('Your configuration has been saved.', 'plytix'); ?></p>
        </div>
        <?php delete_transient('plytix_show_config_msg_ok'); ?>
    <?php elseif ( (get_option('plytix_api_credentials') === FALSE) || ((get_option('plytix_api_credentials') == 'error') && (get_transient('plytix_config_first_time'))) ) :?>
        <div class="update-nag">
            <p>
                <?php _e('Before setting your Site, you must set up your API Keys ', 'plytix'); ?>
                <a href="?page=plytix"><?php _e('here', 'plytix'); ?></a>
            </p>
        </div>
        <?php /** we make sure nothing else can be performed */ die; ?>
    <?php endif; ?>

    <?php
    /**
     * API Credentials Check
     */
    ?>
    <?php if (get_option('plytix_api_credentials') == "error") :?>
        <div class="error" id="error_api">
            <p>
                <?php _e('API Keys are wrong, please set them up properly ', 'plytix'); ?>
                <a href="?page=plytix"><?php _e('here', 'plytix'); ?></a>
            </p>
        </div>
        <?php /** we make sure nothing else can be performed */ die; ?>
    <?php endif; ?>

    <?php
    /**
     * Site configuration Failure
     */
    ?>
    <?php if (get_option('plytix_site_configuration') == "error") :?>
        <div class="error">
            <?php _e('Something is failing. Please try again.', 'plytix'); ?>
        </div>
    <?php endif; ?>

    <h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
    <?php if($first_time && ($js_control)): ?>
        <h3><?php _e('Step 2: Fill in the information below and click Finish', 'plytix'); ?></h3><hr>
    <?php endif; ?>

    <form id="plytix-setting-page-2" method="post" action="options.php">
        <div class=" updated notice">
            <p>Plytix Analytics tracks your products by an identifying number. While you can just use the IDs which are created by WooCommerce automatically in your database, it is preferable that instead, you track your products by a GTIN (UPC, EAN, JAN, etc.), as it will enable you to track product interactions across all your channels.</p>
        </div>
        <div id="plytix-options">
            <?php
            settings_fields( 'plytix-settings-options' );
            do_settings_sections( 'plytix-settings-options' );
            ?>
        </div>
        <?php

        if ($first_time) {
            echo "<span style='margin-right: 10px;'><a href='" . admin_url('index.php?page=plytix&first_time') . "' class='button' id='pl_cancel'>" . __('Go Back', 'plytix') . "</a></span>";
            submit_button(__('Finish','plytix'), 'primary', 'submit', False);
        } else {
            submit_button();
        }
        ?>
    </form>
</div>

<?php // Little Script to avoid user leaving withouth filling the information ?>
<?php if ($js_control) : ?>
<script>
    var btns = document.querySelectorAll('#submit, #pl_cancel');
    for (i = 0; i < btns.length; i++) {
        btns[i].addEventListener("click", function(){
            window.btn_clicked = true;
        });
    }
    window.onbeforeunload = function(){
        if(!window.btn_clicked){
            return 'Please, click on Finish before leaving, your information needs to be saved.';
        }
    };

    jQuery('document').ready(function(){
        // TimeZone Detection
        var tz        = jstz.determine();
        var time_zone = tz.name();
        document.getElementById('timezone').value = time_zone;
    });
</script>
<?php endif; ?>
<script>
    jQuery('document').ready(function(){
        // If error, dont submit the button
        if (jQuery("#error_api").length == 1) {
            jQuery('#submit').prop('disabled', true);
        }
    });

    jQuery("#radio_yes").change(function(){
        jQuery('#options_yes_wrap').show();
        jQuery('#options_no_wrap').hide();

    });

    jQuery("#radio_no").change(function(){
        jQuery('#options_yes_wrap').hide();
        jQuery('#options_no_wrap').show();
    });

    jQuery('#plytix-setting-page-2').submit(function () {
        if (jQuery("#radio_no:checked").val() === "no") {
            jQuery("#rudr_select2_tags option:selected").prop("selected", false);
        }
        return true;
    });

    <?php
        if (get_option('plytix-settings-options')['use_field_gtin'] == 'yes') {
            print "jQuery('#options_no_wrap').hide();";
        } else {
            print "jQuery('#options_yes_wrap').hide();";
        }

    ?>


</script>
