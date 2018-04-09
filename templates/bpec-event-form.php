<?php
/**
 * Event Submission Form
 * The wrapper template for the event submission form.
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

$events_label_singular 	= tribe_get_event_label_singular();
$BPEC_Event_Form 		= BPEC_Event_Form::instance();
$tribe_event_id 		= $BPEC_Event_Form->event_id;
?>
<div class="bp-events event-form">
<?php
do_action( 'bpec_before_event_form' ); ?>

<?php do_action( 'tribe_events_community_form_before_template', isset( $tribe_event_id ) ? $tribe_event_id : null ); ?>

<form method="post" class="event-edit-form" enctype="multipart/form-data" data-datepicker_format="<?php echo esc_attr( tribe_get_option( 'datepickerFormat', 0 ) ); ?>">

	<input type="hidden" name="post_ID" id="post_ID" value="<?php echo absint( $tribe_event_id ); ?>"/>

	<?php wp_nonce_field( 'bpec_event_submission' ); ?>

	<?php
	/**
	 * bpec_event_form hook.
	 *
	 * @hooked bpec_template_title
	 * @hooked bpec_template_description
	 * @hooked bpec_template_taxonomy
	 * @hooked bpec_template_image
	 * @hooked bpec_template_datepickers
	 * @hooked bpec_template_venue
	 * @hooked bpec_template_organizer
	 * @hooked bpec_template_website
	 * @hooked bpec_template_custom
	 * @hooked bpec_template_cost
	 */
	do_action( 'bpec_event_form' );
	?>

	<!-- Form Submit -->
	<?php do_action( 'tribe_events_community_before_form_submit' ); ?>

	<div class="buddypress-events-calendar-footer">
		<input type="submit" id="post" class="button submit events-community-submit" value="<?php

			if ( isset( $tribe_event_id ) && $tribe_event_id ) {
				echo apply_filters( 'tribe_ce_event_update_button_text', sprintf( __( 'Update %s', 'bp-events-calendar' ), $events_label_singular ) );
			} else {
				echo apply_filters( 'tribe_ce_event_submit_button_text', sprintf( __( 'Save %s', 'bp-events-calendar' ), $events_label_singular ) );
			}

			?>" name="community-event" />
	</div><!-- .tribe-events-community-footer -->

	<?php do_action( 'tribe_events_community_after_form_submit' ); ?>

</form>
<?php
do_action( 'bpec_after_event_form' );
?>
</div><!-- .bp-events ->
<?php

