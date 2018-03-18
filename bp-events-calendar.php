<?php
/*
Plugin Name: BP Events Calendar
Plugin URI: http://wpdrift.com/bp-events-calendar
Description: The Modern Tribe's Events Calendar add-on that integrated into BuddyPress, and allow users to post events directly from their profile to your site.
Version: 1.0.0
Author: WPDrift
Author URI: http://wpdrift.com
Requires at least: 4.4
Tested up to: 4.7
Text Domain: bp-events-calendar
Domain Path: /languages/

Copyright: 2017 WPDrift
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

// I18n
add_action( 'plugins_loaded', 'buddypress_events_calendar_load_textdomain' );
function buddypress_events_calendar_load_textdomain() {
	load_plugin_textdomain( 'bp-events-calendar', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

function bpec_init(){
    include 'bp-events-calendar-core.php';
}

add_action( 'bp_include', 'bpec_init' );