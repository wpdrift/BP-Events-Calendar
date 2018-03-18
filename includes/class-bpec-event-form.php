<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Handle frontend event form.
 *
 * @class 		BPEC_Event_Form
 * @version		1.0
 * @package		BPEC/Classes/
 * @category	Class
 * @author 		Kishore
 */

class BPEC_Event_Form {

	/**
	 * event id
	 *
	 * @access protected
	 * @var int
	 */
	public $event_id;
	public $edit;
	public $event;
	public $posted_data;

	/**
	 * Instance
	 *
	 * @access protected
	 * @var BPEC_Event_Form The single instance of the class
	 */
	protected static $_instance = null;

	/**
	 * Main Instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__ );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__ );
	}


	/**
	 * Constructor.
	 */
	public function __construct() {

		$id = bp_action_variable(0);

		do_action( 'tribe_community_before_event_page', $id );
		
		$event     = null;

		if ( $id ) {
			$this->edit = true;
			$this->event_id = $id = intval( $id );
		} else {
			$this->edit = false;
			$this->event_id = null;
		}

		$this->setup_hooks();
	}


	/**
	 * init_fields function.
	 */
	public function setup_hooks() {
		$this->process();

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_action( 'wp_loaded', array( $this, 'process_save_event' ) );
		add_action( 'wp_loaded', array( $this, 'delete_attachments') );
	}

	/**
	 * Validate the posted fields
	 *
	 * @return bool on success, WP_ERROR on failure
	 */
	public function enqueue_scripts() {
        wp_enqueue_media();
		wp_enqueue_style( Tribe__Events__Main::POSTTYPE . '-community' );
		wp_enqueue_script( Tribe__Events__Main::POSTTYPE . '-community' );
	}

	/**
	 * Process the event save
	 */
	public function process_save_event() {

		if ( is_user_logged_in() ) {

			if ( ! empty($this->posted_data) ) {

				if (isset($this->posted_data['post_ID'])) {
					$this->event_id = absint($this->posted_data['post_ID']);
				}

				$this->event_id = $this->save_event();

				//Redirect to event edit page after save
				if ( $this->event_id ) {
					wp_redirect( bpec_get_add_event_url( $this->event_id ) );
					exit;
				}
			}
		}
	}

	/**
	 * Submit Step
	 */
	public function submit() {
		$this->init_fields();
	}

	/**
	 * Submit Step is posted
	 */
	public function process() {

		try {

			if ( empty( $_POST[ 'community-event' ] ) ) {
				return array();
			}

			if ( ! check_admin_referer( 'bpec_event_submission' ) ) {
				return array();
			}

			$this->posted_data = $_POST;

		} catch ( Exception $e ) {

		}
	}

	/**
	 * Update or create a event listing from posted data
	 */
	protected function save_event() {

		$events_label_singular = tribe_get_event_label_singular();
		$events_label_singular_lowercase = tribe_get_event_label_singular_lowercase();
		$this->event = get_post( $this->event_id );

		if ( $this->event_id && 'auto-draft' !== $this->event->post_status ) {
			$saved = Tribe__Events__API::updateEvent( $this->event_id, $this->posted_data );

			if ( $saved ) {
				bp_core_add_message( sprintf( __( '%s updated. ', 'bp-events-calendar' ), $events_label_singular ) . bpec_get_actions_link( $this->event_id ) );
				do_action( 'tribe_community_event_updated', $this->event_id );

			} else {
				bp_core_add_message( sprintf( __( 'There was a problem saving your %s, please try again.', 'bp-events-calendar' ), $events_label_singular_lowercase ), 'error' );
			}

		} else {

			$this->posted_data['post_status'] = 'draft';

			// if we DO have an event ID, then it is an auto-draft, and thus a new post
			if ($this->event_id ) {
				$saved = Tribe__Events__API::updateEvent( $this->event_id, $this->posted_data );
			} else {
				$saved = Tribe__Events__API::createEvent( $this->posted_data );
			}

			if ( $saved ) {
				$this->event_id = $saved;
				bp_core_add_message( sprintf( __( '%s submitted.', 'bp-events-calendar' ), $events_label_singular ) . bpec_get_actions_link( $this->event_id ) );
				do_action( 'tribe_community_event_created', $this->event_id );
			} else {
				bp_core_add_message( sprintf( __( 'There was a problem submitting your %s, please try again.', 'bp-events-calendar' ), $events_label_singular_lowercase ), 'error' );
			}
		}

		// Handles the Upload
		if ( isset( $_FILES['event_image']['name'] ) && ! empty( $_FILES['event_image']['name'] ) ) {
			$this->create_attachments();
		}

		// Group Events
		if ( isset( $_POST['event_group_id'] ) ) {

            $old_event_group_id = get_post_meta( $this->event_id, 'event_group_id', true );
            $new_event_group_id = $_POST['event_group_id'];

            if ( '-1' == $new_event_group_id ) {
                delete_post_meta( $this->event_id, 'event_group_id', $old_event_group_id );
                bpec_delete_group_event( $this->event_id );

            }  elseif ( $old_event_group_id != $new_event_group_id ) {
                update_post_meta( $this->event_id, 'event_group_id', $new_event_group_id );
                bpec_delete_group_event( $this->event_id );
                bpec_save_group_event( $this->event_id, $new_event_group_id);
            }
        }

		// Logged out or underprivileged users will not have terms automatically added during wp_insert_post
		if ( isset( $this->posted_data['tax_input'] ) ) {
			foreach ( (array) $this->posted_data['tax_input'] as $taxonomy => $terms ) {
				$taxonomy_obj = get_taxonomy( $taxonomy );
				if ( ! current_user_can( $taxonomy_obj->cap->assign_terms ) ) {
					wp_set_post_terms($this->event_id, $terms, $taxonomy, true );
				}
			}
		}

		return $this->event_id;
	}

	/**
	 * Call the view handler if set, otherwise call the next handler.
	 */
	public function output( $atts = array() ) {

		if ( $this->edit && $this->event_id ) {
			$this->event = get_post( intval( $this->event_id ) );
		}

		do_action( 'tribe_ce_before_event_submission_page' );
		$output = '<div id="tribe-community-events" class="form">';

		if ( is_user_logged_in() ) {

			if ( isset( $this->event_id ) && $this->edit ) {
				$this->event = get_post( intval( $this->event_id ) );
			} elseif ( empty( $event ) ) {
				$this->event = new stdClass();
			}

			$GLOBALS['post'] = $this->event;

			$tec_template = tribe_get_option( 'tribeEventsTemplate' );

			if ( ! empty( $tec_template ) ) {
				ob_start();
				tribe_events_before_html();
				$output .= ob_get_clean();
			}

			do_action( 'tribe_ce_before_event_submission_page_template' );

			bpec_get_event_template('bpec-event-form.php');

			if ( ! empty( $tec_template ) ) {
				ob_start();
				tribe_events_after_html();
				$output .= ob_get_clean();
			}

		}
		$output .= '</div>';
		
		return $output;
	}

	/**
	 * Upload event featured event
	 *
	 * @param string $file_handler
	 * @return bool|int|WP_Error
	 */
	public function create_attachments( $file_handler = 'event_image' ) {

		// check to make sure its a successful upload
		if ( $_FILES[ $file_handler ]['error'] !== UPLOAD_ERR_OK ) {
			return false;
		}
		$uploaded_file_type = wp_check_filetype( basename( $_FILES[ $file_handler ]['name'] ) );


		if ( ! function_exists( 'media_handle_upload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			require_once( ABSPATH . 'wp-admin/includes/media.php' );
		}

		$allowed_file_types = array( 'image/jpg', 'image/jpeg', 'image/gif', 'image/png' );
		if ( in_array( $uploaded_file_type['type'], $allowed_file_types ) ) {
			$attach_id = media_handle_upload( $file_handler, $this->event_id );
		} else {
			return false;
		}

		if ( false !== $attach_id ) {
			$image_path = get_attached_file( $attach_id );
			$editor = wp_get_image_editor( $image_path );
			$image = @getimagesize( $image_path );
			$status = true;

			if ( is_wp_error( $editor ) ) {
				$status = false;
			} elseif ( false === $image ) {

				$status = false;
			} elseif ( empty( $image[0] ) || ! is_numeric( $image[0] ) || empty( $image[1] ) || ! is_numeric( $image[1] ) ) {
				$status = false;
			} elseif ( empty( $image[2] ) || ! in_array( $image[2], array( IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG ) ) ) {
				$status = false;
			}

			if ( false === $status ) {
				// Purge this weird file!
				wp_delete_attachment( $attach_id, true );
				return false;
			}

			update_post_meta( $this->event_id, '_thumbnail_id', $attach_id );
		}

		return $attach_id;
	}


	/**
	 * Delete event featured image
	 */
	public function delete_attachments() {
		
		// Delete the featured image, if there was a request to do so.
		if ( $this->event_id && isset( $_GET['action'] ) && $_GET['action'] == 'deleteFeaturedImage' && wp_verify_nonce( $_GET['_wpnonce'], 'bp_events_featured_image_delete' ) ) {
			$featured_image_id = get_post_thumbnail_id( $this->event_id );
			if ( $featured_image_id ) {
				delete_post_meta( $this->event_id, '_thumbnail_id' );
				$image_parent = wp_get_post_parent_id( $featured_image_id );
				if ( $image_parent == $this->event_id ) {
					wp_delete_attachment( $featured_image_id, true );
				}
			}
			$redirect = $_SERVER['REQUEST_URI'];
			$redirect = remove_query_arg( '_wpnonce', $redirect );
			$redirect = remove_query_arg( 'action', $redirect );
			wp_safe_redirect( esc_url_raw( $redirect ), 302 );
			bp_core_add_message( __( 'Attachment removed', 'bp-events-calendar' ) );
			exit();
		}
	}

	/**
	 * Return the event start date
	 * @return mixed|void
	 */
	public function event_get_start_date() {

		$date = tribe_get_start_date( $this->event, true, 'Y-m-d' );
		$date = ( $date ) ? Tribe__Date_Utils::date_only( $date ) : date_i18n( 'Y-m-d' );
		return $date;
	}


	/**
	 * Return the event end date string with a default of today.
	 */
	public function event_get_end_date() {

		$date = tribe_get_end_date( $this->event, true, 'Y-m-d' );
		$date = ( $date ) ? Tribe__Date_Utils::date_only( $date ) : date_i18n( 'Y-m-d' );
		return $date;
	}

	/**
	 * Return true if event is an all day event.
	 *
	 */
	public function event_is_all_day() {

		$is_all_day = tribe_event_is_all_day( $this->event_id );
		$is_all_day = ( $is_all_day == 'Yes' || $is_all_day == true );
		return $is_all_day;
	}

    /**
     * Retrive the event categories associated
     *
     * @param mixed $event
     */
    public function get_event_cat_ids( ) {
        return wp_get_object_terms( $this->event_id, Tribe__Events__Main::TAXONOMY, array( 'fields' => 'ids' ) );
    }

}