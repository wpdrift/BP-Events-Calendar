<?php
/**
 * Event Orders List
 * The wrapper template for the event orders list.
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
$valid_order_items             = bpec_get_valid_order_items_for_event( $event_id, $event_orders );

$tickets            = Tribe__Tickets__Tickets::get_event_tickets( $event_id );
$_GET['event_id']   = $event_id; //@todo: do something here


$event_revenue  = Tribe__Tickets_Plus__Commerce__WooCommerce__Orders__Table::event_revenue( $event_id );
$event_sales    = Tribe__Tickets_Plus__Commerce__WooCommerce__Orders__Table::event_sales( $event_id );
$event_fees     = Tribe__Tickets_Plus__Commerce__WooCommerce__Orders__Table::event_fees( $event_id );

$pass_fees_to_user  = apply_filters( 'tribe_tickets_pass_fees_to_user', true, $event_id );
$fee_percent        = apply_filters( 'tribe_tickets_fee_percent', 0, $event_id );
$fee_flat           = apply_filters( 'tribe_tickets_fee_flat', 0, $event_id );

$tickets_sold       = $tickets_breakdown = array();
$total_sold         = 0;
$total_pending      = 0;
$total_completed    = 0;

//Setup the ticket breakdown
$order_statuses = array(
    'wc-completed',
    'wc-pending',
    'wc-processing',
    'wc-cancelled',
);
foreach ( $order_statuses as $status ) {
    $tickets_breakdown[ $status ]['_qty']        = 0;
    $tickets_breakdown[ $status ]['_line_total'] = 0;
}

foreach ( $tickets as $ticket ) {

    //Only Display if a WooCommerce Ticket otherwise kick out
    if ( 'Tribe__Tickets_Plus__Commerce__WooCommerce__Main' != $ticket->provider_class ) {
        continue;
    }

    if ( empty( $tickets_sold[ $ticket->name ] ) ) {
        $tickets_sold[ $ticket->name ] = array(
            'ticket' => $ticket,
            'has_stock' => ! $ticket->stock(),
            'sku' => get_post_meta( $ticket->ID, '_sku', true ),
            'sold' => 0,
            'pending' => 0,
            'completed' => 0,
        );
    }
    $stock = $ticket->stock();
    $sold = $ticket->qty_sold();
    $cancelled = $ticket->qty_cancelled();

    $net_sold = $sold - $cancelled;
    if ( $net_sold < 0 ) {
        $net_sold = 0;
    }

    $tickets_sold[ $ticket->name ]['sold'] += $net_sold;
    $tickets_sold[ $ticket->name ]['pending'] += absint( $ticket->qty_pending() );
    $tickets_sold[ $ticket->name ]['completed'] += absint( $tickets_sold[ $ticket->name ]['sold'] ) - absint( $tickets_sold[ $ticket->name ]['pending'] );

    $total_sold += $net_sold;
    $total_pending += absint( $ticket->qty_pending() );

    $tickets_sold[ $ticket->name ]['product_sales'] = bpec_get_total_sales_per_productby_status( $ticket->ID );

    //update ticket item counts by order status
    foreach ( $tickets_sold[ $ticket->name ]['product_sales'] as $status => $product ) {
        if ( $status && isset( $product[0] ) && is_object( $product[0] ) ) {
            $tickets_breakdown[ $status ]['_qty'] += $product[0]->_qty;
            $tickets_breakdown[ $status ]['_line_total'] += $product[0]->_line_total;
        }
    }

}

$total_completed += absint( $total_sold ) - absint( $total_pending );

?>

<?php // list pagination
if ( empty( $event_orders ) ) {
    echo ( sprintf( __( 'There are %s no orders yet.', 'bp-events-calendar' ), $events_label_plural_lowercase ) );
} ?>

<div class="bp-events tribe-orders-page">

    <div id="tribe-attendees-summary" class="welcome-panel">
        <div class="welcome-panel-content">
            <div class="welcome-panel-column-container">

                <div class="welcome-panel-column welcome-panel-first">
                    <h3><?php esc_html_e( 'Event Details', 'bp-events-calendar' ); ?></h3>
                    <ul>
                        <?php
                        /**
                         * Provides an action that allows for the injections of fields at the top of the order report details meta ul
                         *
                         * @var $event_id
                         */
                        do_action( 'tribe_tickets_plus_report_event_details_list_top', $event_id );

                        /**
                         * Provides an action that allows for the injections of fields at the bottom of the order report details ul
                         *
                         * @var $event_id
                         */
                        do_action( 'tribe_tickets_plus_report_event_details_list_bottom', $event_id );
                        ?>
                    </ul>

                    <?php
                    /**
                     * Fires after the event details list (in the context of the WooCommerce Orders admin view).
                     *
                     * @param WP_Post      $event
                     * @param bool|WP_User $organizer
                     */
                    do_action( 'tribe_tickets_plus_after_event_details_list', $event, $organizer );
                    ?>

                </div>
                <div class="welcome-panel-column welcome-panel-middle">
                    <h3><?php esc_html_e( 'Sales by Ticket', 'bp-events-calendar' ); ?></h3>

                    <div class="tribe-event-meta tribe-event-meta-tickets-sold">
                        <strong><?php echo esc_html__( 'Tickets sold:', 'bp-events-calendar' ); ?></strong>
                        <?php echo absint( $total_sold ); ?>
                        <?php if ( $total_pending > 0 ) : ?>
                            <div id="sales_breakdown_wrapper" class="tribe-event-meta-note">
                                <div>
                                    <?php esc_html_e( 'Completed:', 'bp-events-calendar' ); ?>
                                    <span id="total_issued"><?php echo esc_html( $total_completed ); ?></span>
                                </div>
                                <div>
                                    <?php esc_html_e( 'Processing:', 'bp-events-calendar' ); ?>
                                    <span id="total_pending"><?php echo esc_html( $total_pending ); ?></span>
                                </div>
                            </div>
                        <?php endif ?>
                    </div>
                    <?php
                    foreach ( $tickets_sold as $ticket_sold ) {

                        //Only Display if a WooCommerce Ticket otherwise kick out
                        if ( 'Tribe__Tickets_Plus__Commerce__WooCommerce__Main' != $ticket_sold['ticket']->provider_class ) {
                            continue;
                        }

                        $price        = '';
                        $pending      = '';
                        $sold_message = '';

                        if ( $ticket_sold['pending'] > 0 ) {
                            $pending = sprintf( _n( '(%d awaiting review)', '(%d awaiting review)', $ticket_sold['pending'], 'bp-events-calendar' ), (int) $ticket_sold['pending'] );
                        }

                        if ( ! $ticket_sold['has_stock'] ) {
                            $sold_message = sprintf( __( 'Sold %d %s', 'bp-events-calendar' ), esc_html( $ticket_sold['sold'] ), $pending );
                        } else {
                            $sold_message = sprintf( __( 'Sold %d of %d %s', 'bp-events-calendar' ), esc_html( $ticket_sold['sold'] ), esc_html( $ticket_sold['sold'] + absint( $ticket_sold['ticket']->stock() ) ), $pending );
                        }

                        if ( $ticket_sold['ticket']->price ) {
                            $price = ' (' . tribe_format_currency( number_format( $ticket_sold['ticket']->price, 2 ), $event_id ) . ')';
                        }
                        ?>
                        <div class="tribe-event-meta tribe-event-meta-tickets-sold-itemized">
                            <strong><?php echo esc_html( $ticket_sold['ticket']->name . $price ); ?>:</strong>
                            <?php
                            echo esc_html( $sold_message );
                            if ( $ticket_sold['sku'] ) {
                                ?>
                                <div class="tribe-event-meta-note tribe-event-ticket-sku">
                                    <?php printf( esc_html__( 'SKU: (%s)', 'bp-events-calendar' ), esc_html( $ticket_sold['sku'] ) ); ?>
                                </div>
                                <?php
                            }
                            ?>
                        </div>
                        <?php
                    }
                    ?>
                </div>
                <div class="welcome-panel-column welcome-panel-last alternate">

                    <?php

                    if ( $total_sold ) {
                        $total_sold = '(' . absint( $total_sold ) . ')';
                    }; ?>

                    <div class="totals-header">
                        <h3><?php echo sprintf( __( 'Total Sales: %s %s', 'bp-events-calendar' ), esc_html( tribe_format_currency( number_format( $event_revenue, 2 ), $event_id ) ), $total_sold ); ?></h3>
                    </div>

                    <div id="sales_breakdown_wrapper" class="tribe-event-meta-note">
                        <div>
                            <strong><?php esc_html_e( 'Completed:', 'bp-events-calendar' ); ?></strong>
                            <?php echo esc_html( tribe_format_currency( number_format( $tickets_breakdown['wc-completed']['_line_total'], 2 ), $event_id ) ); ?>
                            <span id="total_issued">(<?php echo esc_html( $tickets_breakdown['wc-completed']['_qty'] ); ?>)</span>
                        </div>
                        <div>
                            <strong><?php esc_html_e( 'Processing:', 'bp-events-calendar' ); ?></strong>
                            <?php echo esc_html( tribe_format_currency( number_format( $tickets_breakdown['wc-processing']['_line_total'], 2 ), $event_id ) ); ?>
                            <span id="total_pending">(<?php echo esc_html( $tickets_breakdown['wc-processing']['_qty'] ); ?>)</span>
                        </div>
                        <div>
                            <strong><?php esc_html_e( 'Pending Payment:', 'bp-events-calendar' ); ?></strong>
                            <?php echo esc_html( tribe_format_currency( number_format( $tickets_breakdown['wc-pending']['_line_total'], 2 ), $event_id ) ); ?>
                            <span id="total_pending">(<?php echo esc_html( $tickets_breakdown['wc-pending']['_qty'] ); ?>)</span>
                        </div>
                        <div>
                            <strong><?php esc_html_e( 'Canceled:', 'bp-events-calendar' ); ?></strong>
                            <?php echo esc_html( tribe_format_currency( number_format( $tickets_breakdown['wc-cancelled']['_line_total'], 2 ), $event_id ) ); ?>
                            <span id="total_issued">(<?php echo esc_html( $tickets_breakdown['wc-cancelled']['_qty'] ); ?>)</span>
                        </div>
                    </div>

                    <?php
                    if ( $event_fees ) {
                        ?>
                        <div class="tribe-event-meta tribe-event-meta-total-ticket-sales">
                            <strong><?php esc_html_e( 'Total Ticket Sales:', 'bp-events-calendar' ) ?></strong>
                            <?php echo esc_html( tribe_format_currency( number_format( $event_sales, 2 ), $event_id ) ); ?>
                        </div>
                        <div class="tribe-event-meta tribe-event-meta-total-site-fees">
                            <strong><?php esc_html_e( 'Total Site Fees:', 'bp-events-calendar' ) ?></strong>
                            <?php echo esc_html( tribe_format_currency( number_format( $event_fees, 2 ), $event_id ) ); ?>
                            <div class="tribe-event-meta-note">
                                <?php
                                echo apply_filters( 'tribe_events_orders_report_site_fees_note', '', $event, $organizer );
                                ?>
                            </div>
                        </div>
                        <?php
                    }//end if
                    ?>
                </div>
            </div>
        </div>
    </div>


    <table class="events-community event-orders" cellspacing="0" cellpadding="4">

        <thead id="orders-display-headers">
            <tr>
                <td><?php esc_html_e( 'Order', 'bp-events-calendar' ); ?></td>
                <td><?php esc_html_e( 'Purchaser', 'bp-events-calendar' ); ?></td>
                <td><?php esc_html_e( 'Email', 'bp-events-calendar' ); ?></td>
                <td><?php esc_html_e( 'Purchased', 'bp-events-calendar' ); ?></td>
                <td><?php esc_html_e( 'Address', 'bp-events-calendar' ); ?></td>
                <td><?php esc_html_e( 'Date', 'bp-events-calendar' ); ?></td>
                <td><?php esc_html_e( 'Status', 'bp-events-calendar' ); ?></td>
                <td><?php esc_html_e( 'Total', 'bp-events-calendar' ); ?></td>
            </tr>
        </thead>

        <tbody id="the-list">
        <?php foreach ( $event_orders as $item ): ?>
            <?php
            /**
             * bpec_event_orders_list_loop hook.
             *
             * @hooked bpec_event_orders_list_row - 10
             */
            do_action( 'bpec_event_orders_list_loop', $item, $event_id, $valid_order_items, $pass_fees_to_user, $fee_percent, $fee_flat );
            ?>

        <?php endforeach; // end of the loop. ?>
        </tbody><!-- #the-list -->

    </table>
</div>
