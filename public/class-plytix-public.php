<?php
include_once PLYTIX_PLUGIN_PUBLIC . '/includes/class-plytix-integration.php';

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://plytix.com/
 * @since      1.0.0
 *
 * @package    Plytix
 * @subpackage Plytix/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Plytix
 * @subpackage Plytix/public
 * @author     Plytix <plytix.com>
 */
class Plytix_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

        /**
         * Registering Plytix Integration
         */
        new Plytix_Integration();
    }

    /**
     * Add Plytix Integration to Woocommerce
     * @return array
     */
    function add_integration() {
        $integrations[] = 'Plytix_Integration';
        return $integrations;
    }

	/**
	 * Register Plytix Script
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

        $api_settings = get_option('plytix-settings');
        $api_id = $api_settings['api_id'];

        /**
         * Registering Plytix Script
         */
        wp_register_script(
            'plytix_js',
            plugin_dir_url( __FILE__ ) . 'js/plytix.js'
        );

        /**
         * Passing Over Api_key_id to the script
         */
        wp_localize_script(
            'plytix_js',
            'api_id',
            array('api_id' => $api_id)
        );

        /**
         * Loading Plytix into the FrontEnd
         */
        wp_enqueue_script(
            'plytix_js'
        );
	}
}
