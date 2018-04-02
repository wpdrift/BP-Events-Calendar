<?php
/**
 * Event Attendees List
 * The wrapper template for the event attendees list.
 *
 * Override this template in your own theme by creating a file at
 * [your-theme]/tribe-events/community/edit-event.php
 *
 * @package Tribe__Events__Community__Main
 * @since  3.1
 * @author Modern Tribe Inc.
 *
 * @var object $event
 * @var array $required
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
    die( '-1' );
}

$events_label_plural_lowercase = tribe_get_event_label_plural_lowercase();
$tickets = Tribe__Tickets__Tickets::get_event_tickets( $event_id );
$_GET['event_id'] = $event_id; //@todo: do something here
?>


<?php // list pagination
if ( empty( $event_attendees ) ) {
    echo ( sprintf( __( 'There are %s no attendees yet.', 'buddypress-for-events-calendar' ), $events_label_plural_lowercase ) );
} ?>

<div class="bp-events tribe-attendees-page">

    <div id="tribe-attendees-summary" class="welcome-panel">
        <div class="welcome-panel-content">
            <div class="welcome-panel-column-container">

                <?php
                /**
                 * Fires before the individual panels within the attendee screen summary
                 * are rendered.
                 *
                 * @param int $event_id
                 */

                do_action( 'tribe_events_tickets_attendees_event_details_top', $event_id );
                ?>

                <div class="welcome-panel-column welcome-panel-first">
                    <h3><?php echo esc_html_x( 'Event Details', 'attendee screen summary', 'buddypress-for-events-calendar' ); ?></h3>

                    <ul>
                        <?php
                        /**
                         * Provides an action that allows for the injections of fields at the top of the event details meta ul
                         *
                         * @var $event_id
                         */
                        do_action( 'tribe_tickets_attendees_event_details_list_top', $event_id );

                        /**
                         * Provides an action that allows for the injections of fields at the bottom of the event details meta ul
                         *
                         * @var $event_id
                         */
                        do_action( 'tribe_tickets_attendees_event_details_list_bottom', $event_id );
                        ?>
                    </ul>
                    <?php
                    /**
                     * Provides an opportunity for various action links to be added below
                     * the event name, within the attendee screen.
                     *
                     * @param int $event_id
                     */
                    do_action( 'tribe_tickets_attendees_do_event_action_links', $event_id );

                    /**
                     * Provides an opportunity for various action links to be added below
                     * the action links
                     *
                     * @param int $event_id
                     */
                    do_action( 'tribe_events_tickets_attendees_event_details_bottom', $event_id ); ?>

                </div>
                <div class="welcome-panel-column welcome-panel-middle">
                    <h3><?php echo esc_html_x( 'Attendees By Ticket', 'attendee screen summary', 'buddypress-for-events-calendar' ); ?></h3>
                    <?php do_action( 'tribe_events_tickets_attendees_ticket_sales_top', $event_id ); ?>

                    <ul>
                        <?php foreach ( $tickets as $ticket ) { ?>
                            <li>
                                <strong><?php echo esc_html( $ticket->name ) ?>: </strong>
                                <?php echo tribe_tickets_get_ticket_stock_message( $ticket ); ?>
                            </li>
                        <?php } ?>
                    </ul>
                    <?php do_action( 'tribe_events_tickets_attendees_ticket_sales_bottom', $event_id );  ?>
                </div>
                <div class="welcome-panel-column welcome-panel-last alternate">
                    <?php
                    /**
                     * Fires before the main body of attendee totals are rendered.
                     *
                     * @param int $event_id
                     */
                    do_action( 'tribe_events_tickets_attendees_totals_top', $event_id );

                    /**
                     * Trigger for the creation of attendee totals within the attendee
                     * screen summary box.
                     *
                     * @param int $event_id
                     */
                    do_action( 'tribe_tickets_attendees_totals', $event_id );

                    /**
                     * Fires after the main body of attendee totals are rendered.
                     *
                     * @param int $event_id
                     */
                    do_action( 'tribe_events_tickets_attendees_totals_bottom', $event_id );
                    ?>
                </div>
            </div>
        </div>
    </div>

    <table class="events-community event-attendees" cellspacing="0" cellpadding="4">

        <thead id="attendees-display-headers">
            <tr>
                <th><?php esc_html_e( 'Ticket', 'buddypress-for-events-calendar' ); ?></th>
                <th><?php esc_html_e( 'Primary Information', 'buddypress-for-events-calendar' ); ?></th>
                <th><?php esc_html_e( 'Security Code', 'buddypress-for-events-calendar' ); ?></th>
                <th><?php esc_html_e( 'Status', 'buddypress-for-events-calendar' ); ?></th>
                <th><?php esc_html_e( 'Check in', 'buddypress-for-events-calendar' ); ?></th>
            </tr>
        </thead>

        <tbody id="the-list">
        <?php foreach ( $event_attendees as $item ): ?>
            <?php
            /**
             * bpec_event_list_loop hook.
             *
             * @hooked bpec_event_attendees_list_row - 10
             */
            do_action( 'bpec_event_attendees_list_loop', $item, $event );
            ?>

        <?php endforeach; // end of the loop. ?>
        </tbody><!-- #the-list -->

    </table><!-- .event-attendees -->
</div>

