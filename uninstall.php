<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * This file may be updated more in future version of the Boilerplate; however, this is the
 * general skeleton and outline for how the file should work.
 *
 * For more information, see the following discussion:
 * https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/pull/123#issuecomment-28541913
 *
 * @link       http://plytix.com/
 * @since      1.0.0
 *
 * @package    Plytix
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Delete Plytix options
 */
delete_option( 'plytix-settings' );
delete_option( 'plytix-settings-options' );
delete_option( 'plytix_activated' );
delete_option( 'plytix_plugin_folder_id' );
delete_option( 'plytix_api_credentials' );
delete_option( 'plytix_site_configuration' );
/**
 * Delete Transients
 */
delete_transient('_plytix_welcome_screen_activation_redirect');
delete_transient('plytix_config_first_time');
delete_transient('plytix_show_api_msg_ok');
delete_transient('plytix_show_config_msg_ok');
delete_transient('plytix_redirect');


/**
 * Queries to delete plytix info: plytix_
 */

global $wpdb;

$query  = "DELETE FROM `".$wpdb->prefix."options` ";
$query .= "WHERE " ;
$query .= "meta_key LIKE 'plytix%' ";
$wpdb->get_results($query);
