<?php
/*
Plugin Name: BuddyPress for Events Calendar
Plugin URI: http://wpdrift.com/bp-events-calendar
Description: The Modern Tribe's Events Calendar add-on that integrated into BuddyPress, and allow users to post events directly from their profile to your site.
Version: 1.0.0
Author: WPDrift
Author URI: http://wpdrift.com
Requires at least: 4.4
Tested up to: 4.7
Text Domain: buddypress-for-events-calendar
Domain Path: /languages/

Copyright: 2018 WPDrift
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/


/**
 * BP Events Calendar
 *
 * @package BPEC
 * @category Core
 * @author WPDrift
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! defined( 'BP_EVENTS_CALENDAR_VERSION' ) ) {
	define( 'BP_EVENTS_CALENDAR_VERSION',  '1.0.0' );
}

if ( ! defined('BP_EVENTS_CALENDAR_FILE') ) {
	define( 'BP_EVENTS_CALENDAR_FILE', __FILE__ );
}

if ( ! defined( 'BP_EVENTS_CALENDAR_PLUGIN_DIR ' ) ) {
	define( 'BP_EVENTS_CALENDAR_PLUGIN_DIR',  untrailingslashit( plugin_dir_path( __FILE__ ) ) );
}

if ( ! defined( 'BP_EVENTS_CALENDAR' ) ) {
	define( 'BP_EVENTS_CALENDAR', plugin_dir_path( __FILE__ ) . 'includes/' );
}

if ( ! defined( 'BP_EVENTS_CALENDAR_REVISION_DATE' ) ) {
	define( 'BP_EVENTS_CALENDAR_REVISION_DATE', '2017-06-25 09:55 UTC' );
}

// Url
if ( !defined( 'BP_EVENTS_CALENDAR_PLUGIN_URL' ) ) {
	$plugin_url = plugin_dir_url( __FILE__ );

	// If we're using https, update the protocol. Workaround for WP13941, WP15928, WP19037.
	if ( is_ssl() )
		$plugin_url = str_replace( 'http://', 'https://', $plugin_url );

	define( 'BP_EVENTS_CALENDAR_PLUGIN_URL', $plugin_url );
}


/**
 * Install db tables.
 */
function bpec_install_db_tables() {
	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();
	$sql = array();

	$sql[] = "CREATE TABLE {$wpdb->prefix}bpec_groups_events (
		id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
		group_id bigint(20) NOT NULL,
		event_id bigint(20) NOT NULL,
		date_mapped datetime DEFAULT '0000-00-00 00:00:00' NOT NULL
	) {$charset_collate};";

	$sql[] = "CREATE TABLE {$wpdb->prefix}bpec_events_members (
		id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
		group_id bigint(20) NOT NULL,
		event_id bigint(20) NOT NULL,
		user_id bigint(20) NOT NULL,
		inviter_id bigint(20) NOT NULL,
		status varchar(255) DEFAULT NULL
	) {$charset_collate};";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

	dbDelta( $sql );
}
register_activation_hook( __FILE__, 'bpec_install_db_tables' );

// I18n
add_action( 'plugins_loaded', 'buddypress_events_calendar_load_textdomain' );
function buddypress_events_calendar_load_textdomain() {
	load_plugin_textdomain( 'buddypress-for-events-calendar', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

/**
 * Show required plugin notice.
 */
function bpec_required_plugins_notice() {
	$required_plugins = array();

	if ( !is_plugin_active( 'buddypress/bp-loader.php' ) ) {
		$required_plugins[] = '<a href="https://wordpress.org/plugins/buddypress/" target="_blank">BuddyPress</a>';
	}

	if ( !is_plugin_active( 'the-events-calendar/the-events-calendar.php' ) ) {
		$required_plugins[] = '<a href="https://wordpress.org/plugins/the-events-calendar/" target="_blank">The Events Calendar</a>';
	}

	if ( !$required_plugins ) {
		return;
	}

	$notice = sprintf( esc_html__( 'BuddyPress for Events Calendar requires you to install %s.', 'buddypress-for-events-calendar' ), implode( ', ', $required_plugins ) );

	echo '<div class="error"><p>' . $notice . '</p></div>';
}
add_action( 'admin_notices', 'bpec_required_plugins_notice' );

function bpec_init() {
	if ( class_exists( 'Tribe__Events__Main' ) ) {
	    include 'bp-events-calendar-core.php';
	}
}

add_action( 'bp_include', 'bpec_init' );
