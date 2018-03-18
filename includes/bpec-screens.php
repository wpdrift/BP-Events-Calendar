<?php
/**
 * BP Events Calender Screen
 *
 * Functions for buddypress screen system
 *
 * @version  1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Catches any visits to the "Events (X)" tab on a users profile.
 *
 * @uses bp_core_load_template() Loads a template file.
 */
function bpec_event_list_screen() {

	bp_core_load_template( apply_filters( 'bpec_event_list_screen', 'members/single/home' ) );
	add_action('bp_template_content','bpec_load_event_list_template');
}

/**
 * Load "Events (x)" tab template part
 */
function bpec_load_event_list_template() {

	do_action( 'tribe_ce_before_event_list_page' );

	echo '<div id="tribe-community-events" class="list">';

	$displayed_user_id = bp_displayed_user_id();

	global $paged;

	if ( empty( $paged ) && ! empty( $page ) ) {
		$paged = $page;
	}

	add_filter( 'tribe_query_can_inject_date_field', '__return_false' );

	$args = array(
		'posts_per_page' 		=> 10,
		'paged' 				=> $paged,
		'author'				=> $displayed_user_id,
		'post_type' 			=> Tribe__Events__Main::POSTTYPE,
		'post_status' 			=> 'any',
		'eventDisplay' 			=> empty( $_GET['eventDisplay'] ) ? 'list' : $_GET['eventDisplay'],
		'tribeHideRecurrence' 	=> false,
		'orderby' 				=> 'meta_value',
		'order' 				=> 'DESC',
	);

	$args 		= apply_filters( 'tribe_ce_my_events_query', $args );
	$my_events 	= tribe_get_events( $args, true );

	remove_filter( 'tribe_query_can_inject_date_field', '__return_false' );

	do_action( 'tribe_ce_before_event_list_page_template' );

	bpec_get_event_template('bpec-event-list.php', array( 'my_events' => $my_events ) );

	wp_reset_query();

	echo '</div>';

}

/**
 * Catches any visits to the "Add Event" tab on a users profile.
 *
 * @uses bp_core_load_template() Loads a template file.
 */
function bpec_event_add_screen() {
	bp_core_load_template( apply_filters( 'bpec_event_add_screen', 'members/single/home' ) );
	add_action('bp_template_content','bpec_load_event_add_template');
	add_filter( 'tribe_events_tickets_attendees_url', 'bpec_events_tickets_attendees_url', 10, 2 );
}

/**
 * Load "Add Event" tab template part
 */
function bpec_load_event_add_template() {
	$BPEC_Event_Form = BPEC_Event_Form::instance();
	$BPEC_Event_Form->output();
}

/**
 * Attendees URL (See who purchased tickets to this event)
 *
 * @param $attendees_url
 * @param $post_id
 * @return string
 */
function bpec_events_tickets_attendees_url( $attendees_url, $post_id ) {
    global $bp;
    return $bp->displayed_user->domain . 'events/attendees/'.bp_action_variable(0);
}

/**
 * Catches any visits to the "Attendees" tab on a users profile.
 *
 * @uses bp_core_load_template() Loads a template file.
 */
function bpec_event_attendees_screen() {

    bp_core_load_template( apply_filters( 'bpec_event_attendees_screen', 'members/single/home' ) );
    add_action('bp_template_content','bpec_load_event_attendees_template');
    add_filter( 'get_edit_post_link', 'bpec_tickets_edit_post_link', 10, 3 );
}

/**
 * Load "Attendees" tab template part
 */
function bpec_load_event_attendees_template() {

    $event_id   = bp_action_variable(0);
    // Fetch the event Object
    if ( ! empty( $_GET['event_id'] ) ) {
        $event = get_post( $_GET['event_id'] );
    }

    //Get the all the attendees for an event
    $attendees  = Tribe__Tickets__Tickets::get_event_attendees( $event_id );

    bpec_get_event_template('bpec-event-attendees.php', array( 'event_attendees' => $attendees,  'event_id' => $event_id, 'event' => $event ) );

    wp_reset_query();
}

/**
 * Filters the event edit link anchor tag.
 *
 * @param $link
 * @param $post_ID
 * @param $text
 * @return string
 */
function bpec_tickets_edit_post_link( $link, $post_ID, $text ) {
    global $bp;
    return $bp->displayed_user->domain . 'events/add-event/'.$post_ID;
}

/**
 * Catches any visits to the "Orders" tab on a users profile.
 *
 * @uses bp_core_load_template() Loads a template file.
 */
function bpec_event_orders_screen() {

    bp_core_load_template( apply_filters( 'bpec_event_orders_screen', 'members/single/home' ) );
    add_action('bp_template_content','bpec_load_event_orders_template');
    add_filter( 'get_edit_post_link', 'bpec_tickets_edit_post_link', 10, 3 );
}

/**
 * Load "Attendees" tab template part
 */
function bpec_load_event_orders_template() {

    $event_id   = bp_action_variable(0);
    // Fetch the event Object
    if ( ! empty( $_GET['event_id'] ) ) {
        $event = get_post( $_GET['event_id'] );
    }

    //Get the all the attendees for an event
    $orders  = bpec_get_orders( $event_id );

    bpec_get_event_template('bpec-event-orders.php', array( 'event_orders' => $orders,  'event_id' => $event_id, 'event' => $event ) );

    wp_reset_query();
}