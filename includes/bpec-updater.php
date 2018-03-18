<?php
/**
 *  Allow BP Events Calendar to be updated directly from the dashboard.
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'BPEC_Updater' ) ) :

/**
 * Main BP Events Calendar Updater Class
 *
 * @class BPEC_Updater
 * @version	1.0
 */
class BPEC_Updater {

    /**
     * Constructor.
     *
     */
	public function __construct() {
		// Define constants
		$this->define_constants();

		// Include required files
		$this->includes();

		// Check for updates
		add_action( 'admin_init', array( $this, 'check_for_updates' ), 0 );

		// Activate and Deactivate license
		add_action( 'admin_init', array( $this, 'activate_license' ), 5 );
		add_action( 'admin_init', array( $this, 'deactivate_license' ), 2 );

        /**
         * Only load our updater on certain admin pages only.  This currently includes
         * the "Dashboard", "Dashboard > Updates" and "Plugins" pages.
         */
        add_action( 'load-index.php',       array( $this, '_init' ) );
        add_action( 'load-update-core.php', array( $this, '_init' ) );
        add_action( 'load-plugins.php',     array( $this, '_init' ) );
	}

    /** INSTALL *******************************************************/

    /**
     * Stub initializer.
     *
     * This is designed to prevent access to the main, protected init method.
     */
    public function _init() {
        if ( ! did_action( 'admin_init' ) ) {
            return;
        }

        $this->init();
    }

    /**
     * Update routine.
     *
     * Runs the install DB tables method amongst other things.
     */
    protected function init() {

        if ( ! defined( 'IFRAME_REQUEST' ) && bp_get_option( 'bpec_version' ) != BP_EVENTS_CALENDAR_VERSION ) {
            $this->install();

            self::update_version();

            // bump revision date in DB
            self::bump_revision_date();
        }

    }

    /** DB VERSION *************************************************/

    /**
     * Returns the revision date for the BP Follow install as saved in the DB.
     *
     * @return int|bool Integer of the installed unix timestamp on success.  Boolean false on failure.
     */
    public static function get_installed_revision_date() {
        return strtotime( bp_get_option( '_bpec_revision_date' ) );
    }


    /**
     * Bumps the revision date in the DB
     *
     * @return void|bool Boolean false on failure only.
     */
    protected static function bump_revision_date() {
        bp_update_option( '_bpec_revision_date', BP_EVENTS_CALENDAR_REVISION_DATE );
    }

    /**
     * Installs the BP Events Cal DB tables
     *
     */
    protected function install() {
        global $bp, $wpdb;

        $charset_collate = ! empty( $wpdb->charset ) ? "DEFAULT CHARACTER SET $wpdb->charset" : '';

        if ( ! $table_prefix = $bp->table_prefix ) {
            $table_prefix = apply_filters( 'bp_core_get_table_prefix', $wpdb->base_prefix );
        }

        $tables = "
          CREATE TABLE {$table_prefix}bpec_groups_events (
				    id BIGINT(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				    group_id BIGINT(20) NOT NULL,
				    event_id BIGINT(20) NOT NULL,
				    date_mapped DATETIME NOT NULL default '0000-00-00 00:00:00',
			        KEY group_id (group_id),
			        KEY event_id (event_id),
			        KEY event_group (group_id,event_id)
			) {$charset_collate};
			CREATE TABLE {$table_prefix}bpec_events_members (
                id BIGINT(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                event_id BIGINT(20) NOT NULL,
				group_id BIGINT(20) NULL,
                user_id BIGINT UNSIGNED NOT NULL,
                inviter_id BIGINT UNSIGNED NULL,
                status varchar(20) NOT NULL DEFAULT 'invited',
              KEY group_id (group_id),
              KEY event_id (event_id)
            ) ${charset_collate};
            ";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $tables );
    }

    /**
     * Update BPEC version to current.
     */
    private static function update_version() {
        bp_delete_option( 'bpec_version' );
        bp_add_option( 'bpec_version', BP_EVENTS_CALENDAR_VERSION );
    }

    /** UPDATE *******************************************************/

    /**
	 * Define constants
	*/
	private function define_constants() {
		if ( !defined( 'BPEC_UPDATER_VERSION' ) )
			define( 'BPEC_UPDATER_VERSION', '1.0' );

		if ( !defined( 'BPEC_UPDATER_URL' ) )
			define( 'BPEC_UPDATER_URL', plugin_dir_url( __FILE__ ) );

		if ( !defined( 'BPEC_UPDATER_DIR' ) )
			define( 'BPEC_UPDATER_DIR', plugin_dir_path( __FILE__ ) );

		if ( !defined( 'BPEC_UPDATER_STORE_URL' ) )
			define( 'BPEC_UPDATER_STORE_URL', 'http://wpdrift.com' );

		if ( !defined( 'BPEC_UPDATER_ITEM_NAME' ) )
			define( 'BPEC_UPDATER_ITEM_NAME', 'BP Events Calendar' );
	}

	/**
	 * Include required files
	*/
	private function includes() {
		if ( !class_exists( 'BPEC_Plugin_Updater' ) ) {
			// load our custom updater
			include( BP_EVENTS_CALENDAR_PLUGIN_DIR. '/includes/class-bpec-plugin-updater.php' );
		}
	}

	/**
	 * Check for updates
	 */
	public function check_for_updates() {

		// retrieve our license key from the DB
		$license = trim( get_site_option( 'bpec_license_key' ) );

		// setup the updater
		$edd_updater = new BPEC_Plugin_Updater( BPEC_UPDATER_STORE_URL, BP_EVENTS_CALENDAR_FILE, array(
				'version' 	=> BP_EVENTS_CALENDAR_VERSION, 		// current version number
				'license' 	=> $license, 			// license key (used get_site_option above to retrieve from DB)
				'item_name' => 'BP Events Calendar', 	// name of this plugin
				'author' 	=> 'WPDrift' 				// author of this plugin
			)
		);
	}

	/**
	 * Activate license
	 */
	public function activate_license() {
		$license_status = get_option('bpec_license_status') ;
		// listen for our activate button to be clicked
		if ( isset( $_POST['bpec_license_key'] )  && ! empty( $_POST['bpec_license_key'] ) && in_array( $license_status, array( 'invalid', '' ) ) ) {

			// retrieve the license from the database
			$license = trim( $_POST['bpec_license_key'] );

			// data to send in our API request
			$api_params = array(
				'edd_action'=> 'activate_license',
				'license' 	=> $license,
				'item_name' => urlencode( BPEC_UPDATER_ITEM_NAME ), // the name of our product in EDD
				'url'       => home_url()
			);

			// Call the custom API.
			$response = wp_remote_post( BPEC_UPDATER_STORE_URL, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

			// Make sure the response came back okay
			if ( is_wp_error( $response ) ) {
				BPEC_Settings::add_error( __( 'Sorry, there has been an error.', 'bp-events-calendar' ) );
				return false;
			}

			// Decode the license data
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			// Update license status
			update_site_option( 'bpec_license_status', $license_data->license );

			// Update License or display error
			if ( 'valid' == $license_data->license ) {
				BPEC_Settings::add_override( __( 'License activated.', 'bp-events-calendar' ) );
			} else {
				BPEC_Settings::add_error( __( 'License invalid.', 'bp-events-calendar' ) );
			}
		}
	}

	/**
	 * Deactivate license
	 */
	public function deactivate_license() {

		$license_status = get_option('bpec_license_status');
		$license 		= get_option('bpec_license_key'); // retrieve the license from the database
		// listen for our activate button to be clicked

		if ( isset( $_POST['bpec_license_key'] ) && empty( $_POST['bpec_license_key'] ) && 'valid' === $license_status ) {

			// data to send in our API request
			$api_params = array(
				'edd_action'=> 'deactivate_license',
				'license' 	=> $license,
				'item_name' => urlencode( BPEC_UPDATER_ITEM_NAME ), // the name of our product in EDD
				'url'       => home_url()
			);

			// Call the custom API.
			$response = wp_remote_post( BPEC_UPDATER_STORE_URL, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

			// make sure the response came back okay
			if ( is_wp_error( $response ) ) {
				BPEC_Settings::add_error( __( 'Sorry, there has been an error.', 'bp-events-calendar' ) );
				update_option( 'bpec_license_key', $license ); //restore license key
				return false;
			}

			// decode the license data
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			// $license_data->license will be either "deactivated" or "failed"
			if ( $license_data->license == 'deactivated' ) {
				delete_site_option( 'bpec_license_status' );
				BPEC_Settings::add_override( __( 'License deactivated.', 'bp-events-calendar' ) );
			} else {
				BPEC_Settings::add_error( __( 'Sorry, there has been an error.', 'bp-events-calendar' ) );
				update_option( 'bpec_license_key', $license ); //restore license key
			}
		}
	}

}

endif;

new BPEC_Updater();
