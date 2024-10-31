<?php
require_once PLYTIX_PLUGIN_ADMIN . '/includes/class-plytix-admin-functions.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/wp-config.php';

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Plytix
 * @subpackage Plytix/admin
 * @author     Plytix <plytix.com>
 */
class Plytix_Admin {

	private $plugin_name;
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

        /**
         * Adding Menu to Dashboard
         */
        add_action( 'admin_menu', array( $this, 'register_plytix_dashboard_menu' ), 1 );

        /**
         * Adding Settings Options
         */
        add_action( 'admin_init', array( $this, 'register_plytix_settings' ));

        /**
         *  Hook into POST settings saving to register Site into Plytix
         */
        add_action( 'admin_action_update', array( $this, 'plytix_site_settings_update_hook' ));
        add_action( 'admin_action_update', array( $this, 'plytix_api_settings_update_hook' ));

        add_action( 'admin_enqueue_scripts', array( $this, 'rudr_select2_enqueue' ));
    }

    function rudr_select2_enqueue(){
        wp_enqueue_style('wp-plytix-css', plugin_dir_url( __FILE__ ) . '/css/plytix-admin.css' );
        wp_enqueue_style('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css' );
        wp_enqueue_script('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js', array('jquery') );
        wp_enqueue_script("wp-plytix-js",  plugin_dir_url( __FILE__ ) . '/js/plytix-setting.js' , array( 'jquery', 'select2' ));
    }

    /**
     * Outputs Plytix API Admin Page
     */
    function showAPIAdminSettingsPage() {
        include PLYTIX_PLUGIN_ADMIN . '/partials/plytix-admin-api-settings.php';
    }

    /**
     * Outputs Plytix Admin Page
     */
    function showSiteAdminSettingsPage() {
        include PLYTIX_PLUGIN_ADMIN . '/partials/plytix-admin-site-settings.php';
    }

    /**
     * Registering Settings for Plytix Plugin Configuration
     */
    public function register_plytix_settings() {

        // API Settings
        register_setting(
            'plytix-settings',
            'plytix-settings'
        );

        // Site Setting
        register_setting(
            'plytix-settings-options',
            'plytix-settings-options'
        );

        // Site Setting
        register_setting(
            'plytix-settings-options-use-field-gtin',
            'plytix-settings-options-use-field-gtin'
        );

        // API Settings Section
        add_settings_section(
            'api_keys',
            null,
            null,
            'plytix-settings'
        );

        // Thumbs Settings Section
        add_settings_section(
            'plytix_settings_options',
            null,
            null,
            'plytix-settings-options'
        );


        /**
        * Api Settings
        */
        add_settings_field(
            'api_id',
            __( 'API Key', 'plytix' ),
            array($this, 'api_key_settings_callback'),
            'plytix-settings',
            'api_keys',
            array('api_id')
        );

        add_settings_field(
            'use_field_gtin',
            __( '', 'plytix' ),
            array($this, 'use_field_gtin_callback'),
            'plytix-settings-options',
            'plytix_settings_options'
        );

        add_settings_field(
            'field_gtin',
            __( '', 'plytix' ),
            array($this, 'field_gtin_callback'),
            'plytix-settings-options',
            'plytix_settings_options'
        );
    }

    function use_field_gtin_callback() {
        ?>
        <br/>
        Do you store a GTIN for your products?<br/><br/>
        <div class="plytix-step-radio-wrap">
            <label class="radio-inline">
                <input id="radio_yes" type="radio" name="plytix-settings-options[use_field_gtin]" value="yes" <?php checked("yes", get_option('plytix-settings-options')['use_field_gtin'], true); ?>>Yes
            </label>
            <label class="radio-inline">
                <input id="radio_no" type="radio" name="plytix-settings-options[use_field_gtin]" value="no" <?php checked("no", get_option('plytix-settings-options')['use_field_gtin'], true);?>>No, I will track my products without GTIN
            </label>
        </div>
        <?php
    }

    /**
     * Plytix options callback for Setting HTTP Protocol
     */
    function field_gtin_callback() {
        $arg1 = 'field_gtin';

        $size_options = get_option('plytix-settings-options');
        $current_value=null;
        if (isset($size_options[$arg1])) {
            $current_value = $size_options[$arg1];
        }
        $fields=array();

        $wc = new WC_Product_Attribute();

        $connection = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD);
        mysqli_select_db($connection, DB_NAME);
        $cur=mysqli_query($connection, "select meta_key from wp_postmeta where meta_key not like '\_%' group by meta_key");
        if ($cur) {
            while ($row=mysqli_fetch_assoc($cur)) {
                $fields[$row["meta_key"]]=$row["meta_key"];
            }
        }

        $html = '<div id="options_yes_wrap">';
        $html .= 'Which attribute(s) in this web shop are a GTIN? Select one, or indicate a few in order of preference, and Plytix Analytics will use the first one available.<br/><br/>';

        $html .= '<select id="rudr_select2_tags" name="plytix-settings-options['.$arg1.'][]" multiple="multiple" style="width:99%;max-width:25em;">';
        foreach ($fields as $k=>$v) {
            $selected = ( is_array( $current_value ) && in_array( $k, $current_value ) ) ? ' selected="selected"' : '';
            $html .= '<option value="' . $k . '"' . $selected . '>' . $v . '</option>';
        }
        $html .= '</select></p>';
        $html .= '</div>';

        $html .= '<div id="options_no_wrap">';
        $html .= '    <p>Alright, you can always change this later. There are some great free plugins that can enable GTIN numbers in your store. We recommend this one:';
        $html .= '    <a href="https://wordpress.org/plugins/woo-add-gtin/">WooCommerce UPC, EAN and ISBN</a>.</p>';
        $html .= '</div>';

        echo $html;



    }

    /**
     * Plytix options callback for Setting API Fields
     * @param $arg
     */
    function api_key_settings_callback($arg) {
        $arg = current($arg);
        $options = get_option('plytix-settings');
        echo "<input name='plytix-settings[$arg]' type='text' value='{$options[$arg]}' style='width: 300px'/>";
    }

    /**
    * Registering Dashboard Menu for Plytix
    */
    public function register_plytix_dashboard_menu() {
        add_menu_page(
            __('Plytix API Keys','plytix'),
            'Plytix',
            'manage_options',
            'plytix',
            array($this, 'showAPIAdminSettingsPage'),
            PLYTIX_PLUGIN_BASE_URL .'/admin/images/plytix_icon.png'
        );

        add_submenu_page(
            'plytix',
            __('Step 1: Add your Plytix Analytics API Key','plytix'),
            'API Key',
            'manage_options',
            'plytix',
            array($this, 'showAPIAdminSettingsPage')
        );

        add_submenu_page(
            'plytix',
            __('Step 2: Product Tracking','plytix'),
            'Product Configuration',
            'manage_options',
            'plytix_site',
            array($this, 'showSiteAdminSettingsPage')
        );
    }

	/**
	 * Register the stylesheets for the admin area.
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/plytix-admin.css', array(), $this->version, 'all' );
	}

    /**
     * Hook into POST settings (Plytix Site) saving to register Site into Plytix
     */
    function plytix_site_settings_update_hook() {
        if (isset($_REQUEST['plytix-settings-options'])) {
            try {
                /**
                * On Success, we delete plytix_site_configuration_fail transient
                * And we set a flag to know all the configuration has been done.
                */
                update_option('plytix_site_configuration', "ok");
                delete_transient('plytix_config_first_time');
                set_transient('plytix_show_config_msg_ok', 1);
            } catch (Exception $e) {
                update_option('plytix_site_configuration', "error");
            }
        }
    }

    /**
     * Hook into POST settings (API) saving to register Site into Plytix
     */
    function plytix_api_settings_update_hook() {
        if (isset($_REQUEST['option_page']) && $_REQUEST['option_page'] == 'plytix-settings') {

            $options = get_option('plytix-settings');
            if ($options['api_id']!=$_REQUEST['plytix-settings']['api_id']) {
                update_option('plytix_plugin_folder_id', '');
                global $wpdb;

                $query  = "DELETE FROM `".$wpdb->prefix."postmeta` ";
                $query .= "WHERE " ;
                $query .= "meta_key LIKE 'plytix_%' ";

                $wpdb->get_results($query);
            }
            update_option('plytix_api_credentials', 'ok');
            if (get_transient('plytix_config_first_time')) {
                set_transient('plytix_redirect', 1);
            } else {
                set_transient('plytix_show_api_msg_ok', 1);
            }
        }
    }
}