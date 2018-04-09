<?php
/**
 * My Events List Template
 * The template for a list of a users events.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}
global $bp;

$organizer_label_singular       = tribe_get_organizer_label_singular();
$venue_label_singular           = tribe_get_venue_label_singular();
$events_label_plural            = tribe_get_event_label_plural();
$events_label_plural_lowercase  = tribe_get_event_label_plural_lowercase();

$eventDisplay = isset($_GET['eventDisplay']) ? $_GET['eventDisplay'] : '';

?>
<div class="bp-events event-list">
<?php
// List "Add New" Button
do_action( 'tribe_ce_before_event_list_top_buttons' ); ?>

<?php // list admin link
$current_user = wp_get_current_user(); ?>

<div style="clear:both"></div>

<?php // list pagination

if ( ! $my_events->have_posts() ) {
	if ( empty( $eventDisplay ) || 'past' !== $eventDisplay ) {
		echo ( sprintf( __( 'There are no upcoming %s in your queue.', 'bp-events-calendar' ), $events_label_plural_lowercase ) );
	} else {
		echo ( sprintf( __( 'There are no past %s in your queue.', 'bp-events-calendar' ), $events_label_plural_lowercase ) );
	}
} ?>


<div class="my-events-display-options">
	<?php
//	add_filter( 'get_pagenum_link', array( Tribe__Events__Community__Main::instance(), 'fix_pagenum_link' ) );
	$link = get_pagenum_link( 1 );
	$link = remove_query_arg( 'eventDisplay', $link );

	if ( empty( $eventDisplay ) || 'past' !== $eventDisplay ) {
		?>
		<a href="<?php echo esc_url( $link . '?eventDisplay=past' ); ?>"><?php echo esc_html__( 'View past events', 'bp-events-calendar' ); ?></a>
		<?php
	} else {
		?>
		<a href="<?php echo esc_url( $link . '?eventDisplay=list' ); ?>"><?php echo esc_html__( 'View upcoming events', 'bp-events-calendar' ); ?></a>
		<?php
	}
	?>
</div>
<?php
echo bpec_pagination( $my_events, '' );

do_action( 'tribe_ce_before_event_list_table' );
if ( $my_events->have_posts() ) {
	?>
	<div class="my-events-table-wrapper">
		<table class="events-community my-events" cellspacing="0" cellpadding="4">
			<thead id="my-events-display-headers">
				<tr>
					<th class="essential persist"><?php esc_html_e( 'Status', 'bp-events-calendar' ); ?></th>
					<th class="essential persist"><?php esc_html_e( 'Title', 'bp-events-calendar' ); ?></th>
					<th class="essential"><?php _e( $organizer_label_singular, 'bp-events-calendar' ); ?></th>
					<th class="essential"><?php _e( $venue_label_singular, 'bp-events-calendar' ); ?></th>
					<th class="optional1"><?php esc_html_e( 'Category', 'bp-events-calendar' ); ?></th>
					<?php
					if ( class_exists( 'Tribe__Events__Pro__Main' ) ) {
						echo '<th class="optional2">' . esc_html__( 'Recurring?', 'bp-events-calendar' ) . '</th>';
					}
					?>
					<th class="essential"><?php esc_html_e( 'Start Date', 'bp-events-calendar' ); ?></th>
					<th class="essential"><?php esc_html_e( 'End Date', 'bp-events-calendar' ); ?></th>
				</tr>
			</thead><!-- #my-events-display-headers -->

			<tbody id="the-list">

				<?php while ( $my_events->have_posts() ) : $my_events->the_post(); ?>

					<?php
					/**
					 * bpec_event_list_loop hook.
					 *
					 * @hooked bpec_generate_event_row - 10
					 */
					do_action( 'bpec_event_list_loop' );
					?>

				<?php endwhile; // end of the loop. ?>

			</tbody><!-- #the-list -->

			<?php do_action( 'tribe_ce_after_event_list_table' ); ?>

		</table><!-- .events-community -->

	</div><!-- .my-events-table-wrapper -->

	<?php // list pagination
	echo bpec_pagination( $my_events, '' );

} // if ( $events->have_posts() )
?>
</div><!-- .bp-events ->
<?php
