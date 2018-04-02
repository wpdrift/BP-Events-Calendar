<?php
/**
 * BuddyPress BP_Events_Component Loader
 *
 * @package BuddyPress
 * @subpackage SettingsLoader
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

class BP_Events_Component extends BP_Component {

	public function __construct() {
		parent::start(
			'events',
			__( 'Events', 'buddypress-for-events-calendar' ),
			plugin_dir_path( __FILE__ ),
			array(
				'adminbar_myaccount_order' => 100
			)
		);

		// include our files
		$this->includes();

		// setup hooks
		$this->setup_hooks();

	}

	/**
	 * Include files
	 *
	 * @global BuddyPress $bp The one true BuddyPress instance
	 */
	public function includes( $includes = array() ) {

		include( BP_EVENTS_CALENDAR_PLUGIN_DIR . '/includes/class-bpec-event-form.php' );

		include( BP_EVENTS_CALENDAR_PLUGIN_DIR . '/includes/bpec-functions.php' );
		include( BP_EVENTS_CALENDAR_PLUGIN_DIR . '/includes/bpec-actions.php' );
		include( BP_EVENTS_CALENDAR_PLUGIN_DIR . '/includes/bpec-templates.php' );
		include( BP_EVENTS_CALENDAR_PLUGIN_DIR . '/includes/bpec-screens.php' );

		//groups
        require( BP_EVENTS_CALENDAR_PLUGIN_DIR .'/includes/bpec-groups-extension.php' );
        require( BP_EVENTS_CALENDAR_PLUGIN_DIR .'/includes/class-bpec-events-members.php' );

		//Admin includes
		if ( is_admin() ) {
			/** settings ***************************************************/
			include( BP_EVENTS_CALENDAR_PLUGIN_DIR . '/includes/admin/class-bpec-settings.php' );
		}
	}

	/**
	 * Setup hooks.
	 */
	public function setup_hooks() {

		// javascript hook
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 11 );

		//Admin hooks
		if ( is_admin() ) {
			//Setting page init
			$this->settings_page = new BPEC_Settings();
			add_action( 'admin_menu', array( $this, 'add_menu_page' ), 12 );
		}
	}


	/**
	 * Setup globals
	 *
	 * The BP_EVENTS_CALENDAR_SLUG constant is deprecated, and only used here for
	 * backwards compatibility.
	 *
	 * @since BuddyPress (1.5)
	 */
	public function setup_globals( $args = array() ) {
        global $bp;
		// Define a slug, if necessary
		if ( !defined( 'BP_EVENTS_CALENDAR_SLUG' ) )
			define( 'BP_EVENTS_CALENDAR_SLUG', $this->id );

        /** Core setup globals ************************************************/
		parent::setup_globals( array(
			'slug'          => BP_EVENTS_CALENDAR_SLUG,
			'has_directory' => false,
            'global_tables' => array(
                'table_name'            => $bp->table_prefix . 'bpec_groups_events',
                'table_name_members'    => $bp->table_prefix . 'bpec_events_members',
            )
		) );
	}

	/**
	 * Set up navigation.
	 */
	public function setup_nav( $main_nav = array(), $sub_nav = array() ) {

		if ( get_current_user_id() != bp_displayed_user_id() )
			return;

		// Determine user to use
		if ( bp_displayed_user_domain() ) {
			$user_domain = bp_displayed_user_domain();
		} elseif ( bp_loggedin_user_domain() ) {
			$user_domain = bp_loggedin_user_domain();
		} else {
			return;
		}

		$main_nav = array(
			'name'                    => __( 'Events', 'buddypress-for-events-calendar' ),
			'slug'                    => $this->slug,
			'position'                => 100,
			'show_for_displayed_user' => bp_core_can_edit_settings(),
			'screen_function'         => 'bpec_event_list_screen',
			'default_subnav_slug'     => 'event-lists'
		);

		$events_link = trailingslashit( $user_domain . $this->slug );

		$sub_nav[] = array(
			'name'            => __( 'Events', 'buddypress-for-events-calendar' ),
			'slug'            => 'event-lists',
			'parent_url'      => $events_link,
			'parent_slug'     => $this->slug,
			'screen_function' => 'bpec_event_list_screen',
			'position'        => 10,
		);

		$sub_nav[] = array(
			'name'            => __( 'Add Event', 'buddypress-for-events-calendar' ),
			'slug'            => 'add-event',
			'parent_url'      => $events_link,
			'parent_slug'     => $this->slug,
			'screen_function' => 'bpec_event_add_screen',
			'position'        => 20,
		);

		if ( bp_is_current_component($this->slug) && bp_is_current_action('attendees') && bp_action_variable(0) ) {
            $sub_nav[] = array(
                'name'            => __( 'Attendees', 'buddypress-for-events-calendar' ),
                'slug'            => 'attendees',
                'parent_url'      => $events_link,
                'parent_slug'     => $this->slug,
                'screen_function' => 'bpec_event_attendees_screen',
                'position'        => 30,
            );
        }

        if ( bp_is_current_component($this->slug) && bp_is_current_action('orders') && bp_action_variable(0) ) {
            $sub_nav[] = array(
                'name'            => __( 'Orders', 'buddypress-for-events-calendar' ),
                'slug'            => 'orders',
                'parent_url'      => $events_link,
                'parent_slug'     => $this->slug,
                'screen_function' => 'bpec_event_orders_screen',
                'position'        => 40,
            );
        }

        parent::setup_nav( $main_nav, $sub_nav );
	}

	/**
	 * Set up the Toolbar
	 */
	public function setup_admin_bar( $wp_admin_nav = array() ) {


		// The instance
		$bp = buddypress();

		$tec = Tribe__Events__Main::instance();
		$events_slug = $tec->getRewriteSlug();
		$events_url = home_url() . '/' .$events_slug;

		// Menus for logged in user
		if ( is_user_logged_in() ) {

			// Setup the logged in user variables
			$user_domain   = bp_loggedin_user_domain();
			$events_link = trailingslashit( $user_domain . $this->slug );

			$wp_admin_nav[] = array(
				'parent' => $bp->my_account_menu_id,
				'id'     => 'my-account-' . $this->id,
				'title'  => __( 'Events', 'buddypress-for-events-calendar' ),
				'href'   => trailingslashit( $events_link )
			);

			$wp_admin_nav[] = array(
				'parent' => 'my-account-' . $this->id,
				'id'     => 'my-account-' . $this->id . '-event-lists',
				'title'  => __( 'Event Lists', 'buddypress-for-events-calendar' ),
				'href'   => trailingslashit( $events_link . 'event-lists' )
			);

			$wp_admin_nav[] = array(
				'parent' => 'my-account-' . $this->id,
				'id'     => 'my-account-' . $this->id . '-calendar',
				'title'  => __( 'Calendar', 'buddypress-for-events-calendar' ),
				'href'   => trailingslashit( $events_url )
			);

			$wp_admin_nav[] = array(
				'parent' => 'my-account-' . $this->id,
				'id'     => 'my-account-' . $this->id . '-add-event',
				'title'  => __( 'Add Event', 'buddypress-for-events-calendar' ),
				'href'   => trailingslashit( $events_link . 'add-event' )
			);

		}

		parent::setup_admin_bar( $wp_admin_nav );
	}

	/**
	 * Enqueues the javascript.
	 *
	 * The JS is used to add AJAX functionality when clicking on the follow button.
	 */
	public function enqueue_scripts() {

		/**  STYLE *****************************************************************************/
		wp_enqueue_style('bpec-main', BP_EVENTS_CALENDAR_PLUGIN_URL . '/assets/css/bpec-main.css', array(), BP_EVENTS_CALENDAR_VERSION );
		wp_enqueue_style('magnific-popup', BP_EVENTS_CALENDAR_PLUGIN_URL . '/assets/css/magnific-popup.css', array(), '1.1.0' );

		/**  SCRIPTS *****************************************************************************/
        wp_enqueue_script('bpec-main', BP_EVENTS_CALENDAR_PLUGIN_URL . '/assets/js/bpec-main.js', array('jquery'), BP_EVENTS_CALENDAR_VERSION );
        wp_enqueue_script('tether', BP_EVENTS_CALENDAR_PLUGIN_URL . '/assets/js/tether.min.js', array(), '1.4.0' );
        wp_enqueue_script('drop', BP_EVENTS_CALENDAR_PLUGIN_URL . '/assets/js/drop.min.js', array(), '1.2.2' );
        wp_enqueue_script('jquery.magnific-popup', BP_EVENTS_CALENDAR_PLUGIN_URL . '/assets/js/jquery.magnific-popup.min.js', array('jquery'), '1.1.0' );

		/**  TRIBE EVENT ASSETS *****************************************************************************/

		$tec = Tribe__Events__Main::instance();

		Tribe__Events__Template_Factory::asset_package( 'chosen' );
		Tribe__Events__Template_Factory::asset_package( 'select2' );
		Tribe__Events__Template_Factory::asset_package( 'dropdowns' );
		Tribe__Events__Template_Factory::asset_package( 'admin-ui' );
		Tribe__Events__Template_Factory::asset_package( 'datepicker' );
		Tribe__Events__Template_Factory::asset_package( 'dialogue' );
		Tribe__Events__Template_Factory::asset_package( 'ecp-plugins' );
		Tribe__Events__Template_Factory::asset_package( 'admin' );

		// This comes from Common Lib
		wp_enqueue_style( 'tribe-jquery-ui-datepicker' );

		// calling our own localization because wp_localize_scripts doesn't support arrays or objects for values, which we need.
		add_action( 'admin_footer', array( $tec, 'printLocalizedAdmin' ) );

		// hook for other plugins
		do_action( 'tribe_events_enqueue' );

		add_action( 'wp_footer', array( $tec, 'printLocalizedAdmin' ) );

		// load EC resources
		add_action( 'wp_enqueue_scripts', array( $this, 'addScriptsAndStyles' ) );

		// jquery-resize
		Tribe__Events__Template_Factory::asset_package( 'jquery-resize' );

		// smoothness
		Tribe__Events__Template_Factory::asset_package( 'smoothness' );

		// Tribe Calendar JS
		Tribe__Events__Template_Factory::asset_package( 'calendar-script' );

		Tribe__Events__Template_Factory::asset_package( 'events-css' );

		Tribe__Events__Template_Factory::asset_package( 'tribe-select2' );

		// Admin Legacy Migration
		Tribe__Events__Template_Factory::asset_package( 'admin-migrate-legacy-ignored-events' );

		$data = array(
			'post_types' => array(
				'tribe_organizer' 	=> "organizer",
				'tribe_venue' 		=> "venue"
			),
		);

		wp_localize_script( 'tribe-events-admin', 'tribe_events_linked_posts', $data );

		//Event list page styles and scripts
		if ( bp_is_current_component('events') && bp_is_current_action('event-lists') ) {
			wp_enqueue_style( Tribe__Events__Main::POSTTYPE . '-community' );
			wp_enqueue_script( Tribe__Events__Main::POSTTYPE . '-community' );
		}

		if ( bpec_is_event_tickets_active() ) {

			$resources_url = Tribe__Tickets__Main::instance()->plugin_url . 'src/resources/';

			wp_enqueue_style( 'event-tickets', $resources_url .'/css/tickets.css', array(), Tribe__Tickets__Main::instance()->css_version() );
			wp_enqueue_script( 'event-tickets', $resources_url .'/js/tickets.js', array( 'jquery-ui-datepicker' ), Tribe__Tickets__Main::instance()->js_version(), true );

            wp_enqueue_style( 'tickets-attendees', $resources_url . '/css/tickets-attendees.css', array(), Tribe__Tickets__Main::instance()->css_version() );
			wp_enqueue_style( 'tickets-attendees' . '-print', $resources_url . '/css/tickets-attendees-print.css', array(), Tribe__Tickets__Main::instance()->css_version(), 'print' );
            wp_enqueue_script( 'tickets-attendees', $resources_url . '/js/tickets-attendees.js', array( 'jquery' ), Tribe__Tickets__Main::instance()->js_version() );

			wp_localize_script( 'event-tickets', 'tribe_ticket_notices', array(
				'confirm_alert' => __( 'Are you sure you want to delete this ticket? This cannot be undone.', 'buddypress-for-events-calendar' ),
			) );

			$upload_header_data = array(
				'title'  => esc_html__( 'Ticket header image', 'buddypress-for-events-calendar' ),
				'button' => esc_html__( 'Set as ticket header', 'buddypress-for-events-calendar' ),
			);

			wp_localize_script( 'event-tickets', 'HeaderImageData', $upload_header_data );
			wp_localize_script( 'event-tickets', 'tribe_global_stock_admin_ui', array(
				'nav_away_msg' => __( 'It looks like you have modified your global stock settings but have not saved or updated the post.', 'buddypress-for-events-calendar' ),
			) );

			$nonces = array(
				'add_ticket_nonce'    => wp_create_nonce( 'add_ticket_nonce' ),
				'edit_ticket_nonce'   => wp_create_nonce( 'edit_ticket_nonce' ),
				'remove_ticket_nonce' => wp_create_nonce( 'remove_ticket_nonce' ),
			);

			wp_localize_script( 'event-tickets', 'TribeTickets', $nonces );

            $mail_data = array(
                'nonce'           => wp_create_nonce( 'email-attendee-list' ),
                'required'        => esc_html__( 'You need to select a user or type a valid email address', 'buddypress-for-events-calendar' ),
                'sending'         => esc_html__( 'Sending...', 'buddypress-for-events-calendar' ),
                'checkin_nonce'   => wp_create_nonce( 'checkin' ),
                'uncheckin_nonce' => wp_create_nonce( 'uncheckin' ),
                'cannot_move'     => esc_html__( 'You must first select one or more tickets before you can move them!', 'buddypress-for-events-calendar' ),
                'move_url'        => add_query_arg( array(
                    'dialog'    => Tribe__Tickets__Main::instance()->move_tickets()->dialog_name(),
                    'check'     => wp_create_nonce( 'move_tickets' ),
                    'TB_iframe' => 'true',
                ) ),
            );

            wp_localize_script( 'tickets-attendees', 'Attendees', $mail_data );

			wp_enqueue_script( 'tribe-bumpdown' );
		}

        wp_localize_script( 'bpec-main', 'bpec_global_vars', apply_filters( 'bpec_global_vars', array(
            'join' => __( 'Join', 'buddypress-for-events-calendar' ),
            'going' => __( 'Going', 'buddypress-for-events-calendar' ),
            'not_going'      => __( 'Not Going', 'buddypress-for-events-calendar' ),
            'interested' => __( 'Interested', 'buddypress-for-events-calendar' ),
            'not_interested' => __( 'Not Interested', 'buddypress-for-events-calendar' ),
        ) ) );

    }

	/**
	 * Add Admin Menu page link
	 *
	 * @return void
	 * @return void
	 */
	public function add_menu_page() {
		global $wpdrift_admin_page_hooks;

		if( ! isset( $wpdrift_admin_page_hooks ) ){
			$position = apply_filters( 'wpdrift_panel_menu_item_position', '62.33' );
			//  Plugins text must not be translated
			apply_filters('wpdrift_panel_menu_page_capability', current_user_can( 'manage_options' )) && add_menu_page( 'wpdrift_panel', 'WP Drift', 'manage_options', 'wpdrift_panel', NULL, '', $position );
		}

		add_submenu_page( 'wpdrift_panel', __( 'BP Events Calendar', 'buddypress-for-events-calendar' ), __( 'BP Events Calendar', 'buddypress-for-events-calendar' ), 'manage_options', 'bp-events-calendar-settings', array( $this->settings_page, 'output' ) );
		remove_submenu_page( 'wpdrift_panel', 'wpdrift_panel' ); //Need to remove submenu "wpdrift_panel" created from parent add_menu_page
	}
}


/**
 * Loads the Follow component into the $bp global
 *
 * @package BP-Follow
 * @global obj $bp BuddyPress instance
 * @since 1.2
 */
function bpec_setup_component() {
	global $bp;

	$bp->bpec = new BP_Events_Component();

	do_action( 'bpec_loaded' );
}

add_action( 'bp_loaded', 'bpec_setup_component' );
