<?php
/**
 * BP Events Calender Actions
 *
 * Functions for event actions.
 *
 * @version  1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Init event form
 */
function bpec_new_event_form() {
    if ( bp_is_current_component('events') && bp_is_current_action('add-event') ) {
         BPEC_Event_Form::instance();
    }
}

add_action( 'bp_init', 'bpec_new_event_form' );

/**
 * Event edit form
 *
 * @see bpec_template_title()
 * @see bpec_template_description()
 * @see bpec_template_taxonomy()
 * @see bpec_template_image()
 * @see bpec_template_datepickers()
 * @see bpec_template_venue()
 * @see bpec_template_organizer()
 * @see bpec_template_website()
 * @see bpec_template_custom()
 * @see bpec_template_cost()
 * @see bpec_template_ticket()
 * @see bpec_template_group()
 */
add_action( 'bpec_event_form', 'bpec_template_title' );
add_action( 'bpec_event_form', 'bpec_template_description' );
add_action( 'bpec_event_form', 'bpec_template_taxonomy' );
add_action( 'bpec_event_form', 'bpec_template_image' );
add_action( 'bpec_event_form', 'bpec_template_datepickers' );
add_action( 'bpec_event_form', 'bpec_template_venue' );
add_action( 'bpec_event_form', 'bpec_template_organizer' );
add_action( 'bpec_event_form', 'bpec_template_website' );
add_action( 'bpec_event_form', 'bpec_template_custom' );
add_action( 'bpec_event_form', 'bpec_template_cost' );
// Check event tickets plugin is activated
if ( bpec_is_event_tickets_active() ) {
    add_action( 'bpec_event_form', 'bpec_template_ticket' );
}
// Check whether a group component is active.
if ( bp_is_active('groups') ) {
    add_action( 'bpec_event_form', 'bpec_template_group' );
}

/**
 * Event list loop items.
 *
 * @see bpec_template_event_row()
 */
add_action( 'bpec_event_list_loop', 'bpec_template_event_row', 10 );

/**
 * Record event activity item
 * @see bpec_record_event_activity()
 */
add_action( 'save_post_tribe_events', 'bpec_event_listing_record_post', 10, 1 );

/**
 * Event list loop items.
 *
 * @see bpec_event_attendees_list_row()
 */
add_action( 'bpec_event_attendees_list_loop', 'bpec_event_attendees_list_row', 10, 2 );

/**
 * Event orders loop items.
 *
 * @see bpec_event_orders_list_row()
 */
add_action( 'bpec_event_orders_list_loop', 'bpec_event_orders_list_row', 10, 6 );

// Register the activity stream actions
add_action( 'bp_register_activity_actions', 'bpec_register_activity_actions'  );

/**
 * Group events
 *
 * @see bpec_group_join_event_button()
 * @see bpec_event_guests()
 * @see bpec_guests_popup_inner()
 * @see bpec_event_join_record_activity()
 * @see bpec_delete_activity_on_event_leave()
 */
add_action( 'bpec_directory_event_actions',         'bpec_group_join_event_button');
add_action( 'bpec_directory_event_item',            'bpec_event_guests');
add_action( 'bpec_guests_popup_inner',              'bpec_guests_popup_inner');
add_action( 'bpec_member_joined_event',             'bpec_event_join_record_activity', 10, 1 );
add_action( 'bpec_member_left_event',               'bpec_delete_activity_on_event_leave', 10, 1 );

/** AJAX ACTIONS ***************************************************/

/**
 *
 * @see bpec_join_group_event()
 * @see bpec_event_guests_list()
 * @see bpec_bp_user_query_uid_clauses()
 */
add_action( 'wp_ajax_bpec_join_group_event', 'bpec_join_group_event' );
add_action( 'wp_ajax_bpec_event_guests_list', 'bpec_event_guests_list' );
add_filter( 'bp_user_query_uid_clauses', 'bpec_bp_user_query_uid_clauses', 10, 2 );