<?php
/**
 * BP Events Calender Templates
 *
 * Functions for event templating system.
 *
 * @version  1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// require an admin functions file so we can leverage wp_terms_checklist
include_once ABSPATH . '/wp-admin/includes/template.php';

/**
 * Output the <label> field
 * @param $field
 * @param $text
 */
function bpec_field_label( $field, $text ) {
    $label_text = apply_filters( 'bpec_field_label_text', $text, $field );
    //$class = tribe_community_events_field_has_error( $field ) ? 'error' : '';
    $class = apply_filters( 'bpec_field_label_class', '', $field );
    $html = sprintf(
        '<label for="%s" class="%s">%s</label>',
        $field,
        $class,
        $label_text
    );
    $html = apply_filters( 'bpec_field_label', $html, $field, $text );
    echo $html;
}

/**
 * Output the select fields for event start time.
 *
 */
function bpec_event_start_time_selector() {
    $event_form = BPEC_Event_Form::instance();

    $event_id = $event_form->event_id;
    $is_all_day = tribe_event_is_all_day( $event_id );

    $start_date = null;

    if ( $event_id ) {
        $start_date = tribe_get_start_date( $event_id, true, Tribe__Date_Utils::DBDATETIMEFORMAT );
    }

    $start_minutes 	= Tribe__View_Helpers::getMinuteOptions( $start_date, true );
    $start_hours = Tribe__View_Helpers::getHourOptions( $is_all_day == 'yes' ? null : $start_date, true );
    $start_meridian = Tribe__View_Helpers::getMeridianOptions( $start_date, true );

    $output = '';
    $output .= sprintf( '<select name="EventStartHour">%s</select>', $start_hours );
    $output .= sprintf( '<select name="EventStartMinute">%s</select>', $start_minutes );
    if ( ! Tribe__View_Helpers::is_24hr_format() ) {
        $output .= sprintf( '<select name="EventStartMeridian">%s</select>', $start_meridian );
    }
    echo $output;
}

/**
 * Output the select fields for event end time.
 *
 */
function bpec_event_end_time_selector() {
    $event_form = BPEC_Event_Form::instance();

    $event_id = $event_form->event_id;
    $is_all_day = tribe_event_is_all_day( $event_id );
    $end_date = null;

    if ( $event_id ) {
        $end_date = tribe_get_end_date( $event_id, true, Tribe__Date_Utils::DBDATETIMEFORMAT );
    }

    $end_minutes = Tribe__View_Helpers::getMinuteOptions( $end_date );
    $end_hours = Tribe__View_Helpers::getHourOptions( $is_all_day == 'yes' ? null : $end_date );
    $end_meridian = Tribe__View_Helpers::getMeridianOptions( $end_date );

    $output = '';
    $output .= sprintf( '<select name="EventEndHour">%s</select>', $end_hours );
    $output .= sprintf( '<select name="EventEndMinute">%s</select>', $end_minutes );
    if ( ! Tribe__View_Helpers::is_24hr_format() ) {
        $output .= sprintf( '<select name="EventEndMeridian">%s</select>', $end_meridian );
    }

   echo $output;
}

/**
 * Output the event Organizer select menu
 *
 */
function bpec_events_organizer_select_menu( $event_id = null ) {
    if ( ! $event_id ) {
        global $post;
        if ( isset( $post->post_type ) && $post->post_type == Tribe__Events__Main::POSTTYPE ) {
            $event_id = $post->ID;
        } elseif ( isset( $post->post_type ) && $post->post_type == Tribe__Events__Main::ORGANIZER_POST_TYPE ) {
            return;
        }
    }
    do_action( 'tribe_organizer_table_top', $event_id );
}

/**
 * Output the event currency symbol
 *
 */
function bpec_currency_symbol_field() {
    $event_form = BPEC_Event_Form::instance();

    if ( $event_form->event_id ) {
        $EventCurrencySymbol = get_post_meta( $event_form->event_id, '_EventCurrencySymbol', true );
    }

    if ( ! isset( $EventCurrencySymbol ) || ! $EventCurrencySymbol ) {
        $EventCurrencySymbol = isset( $_POST['EventCurrencySymbol'] ) ? $_POST['EventCurrencySymbol'] : tribe_get_option( 'defaultCurrencySymbol', '$' );
    }

    echo esc_attr( $EventCurrencySymbol );
}

/**
 * Get HTML for the event actions link
 * @return string
 */
function bpec_get_actions_link() {
    global $bp;

    $event_form = BPEC_Event_Form::instance();

    $edit_link = $view_link = '';

    if ( get_post_status( $event_form->event_id ) == 'publish' ) {
        $view_link = sprintf( '<a href="%s" class="view-event">%s</a>',
            esc_url( get_permalink( $event_form->event_id ) ),
            __( 'View', 'bp-events-calendar' ) );
    }

    if ( current_user_can( 'edit_post', $event_form->event_id ) ) {
        $edit_link = sprintf( '<a href="%s" class="edit-event">%s</a>',
            esc_url( $bp->displayed_user->domain . 'events/add-event/' . $event_form->event_id ),
            __( 'Edit', 'bp-events-calendar' )
        );
    }

    // If the user isn't allowed to edit and the post wasn't published, return an empty string
    if ( empty( $edit_link ) && empty( $view_link ) ) {
        return '';
    }

    $separator = '<span class="sep"> | </span>';
    return '(' . tribe_separated_field( $view_link, $separator, $edit_link ) . ')';
}

/**
 * Output the event title field
 */
function bpec_event_title_field() {
    $event_form = BPEC_Event_Form::instance();

    $title = get_the_title( $event_form->event_id );
    if ( empty( $title ) && ! empty( $_POST['post_title'] ) ) {
        $title = stripslashes( $_POST['post_title'] );
    }
    ?>
    <input type="text" name="post_title" value="<?php esc_attr_e( $title ); ?>"/>
    <?php
}

/**
 * Output the event description field
 */
function bpec_event_description_field() {
    $event_form = BPEC_Event_Form::instance();


    if ( $event_form->event && ! empty( $event_form->event->ID ) ) {
        $post_content = $event_form->event->post_content;
    } elseif ( ! empty( $_POST['post_content'] ) ) {
        $post_content = stripslashes( $_POST['post_content'] );
    } else {
        $post_content = '';
    }

    // if the admin wants the rich editor, and they are using WP 3.3, show the WYSIWYG, otherwise default to just a text box
    if ( function_exists( 'wp_editor' ) ) {
        $settings = array(
            'wpautop' => true,
            'media_buttons' => false,
            'editor_class' => 'frontend',
            'textarea_rows' => 5,
        );

        wp_editor( $post_content, 'post_content', $settings );
    } else {
        ?><textarea name="post_content"><?php
        echo esc_textarea( $post_content );
        ?></textarea><?php
    }
}

/**
 * Output the event featured image delete button
 */
function bpec_delete_featured_image_link() {
    $event_form = BPEC_Event_Form::instance();
    $event      = $event_form->event;

    if ( ! has_post_thumbnail( $event->ID ) ) {
        return '';
    }

    $url = add_query_arg( 'action', 'deleteFeaturedImage', wp_nonce_url( bpec_get_add_event_url($event->ID), 'bp_events_featured_image_delete' ) );

    if ( class_exists( 'Tribe__Events__Pro__Main' ) && tribe_is_recurring_event( $event_form->event_id ) ) {
        $url = add_query_arg( 'eventDate', date( 'Y-m-d', strtotime( $event->EventStartDate ) ), $url );
    }

    echo '<a rel="nofollow" class="submitdelete" href="' . esc_url( $url ) . '">' . esc_html__( 'Delete Image', 'buddypress-events-calender' ) . '</a>';
}

/**
 * Output the event title field
 */
function bpec_template_title() { ?>

    <!-- Event Title -->
    <div class="events-community-post-title">
        <?php bpec_field_label( 'post_title', sprintf( __( '%s Title:', 'bp-events-calendar' ), tribe_get_event_label_singular() ) ); ?>
        <?php bpec_event_title_field() ?>
    </div><!-- .events-community-post-title -->
    <?php
}

/**
 * Output the event description field
 */
function bpec_template_description() { ?>

    <!-- Event Description -->
	<?php do_action( 'tribe_events_community_before_the_content' ); ?>
    <div class="events-community-post-content">
        <?php bpec_field_label( 'post_content', sprintf( __( '%s Description:', 'bp-events-calendar' ), tribe_get_event_label_singular() ) ); ?>
        <?php bpec_event_description_field(); ?>
    </div><!-- .tribe-events-community-post-content --><?php
}

/**
 * Output the taxonomy field in the submission form.
 *
 */
function bpec_template_taxonomy() {
    $event_cats = get_terms( Tribe__Events__Main::TAXONOMY, array( 'hide_empty' => false ) );
    $event_form = BPEC_Event_Form::instance();
    $event      = $event_form->event;

    // only display categories if there are any
    if ( ! empty( $event_cats ) ) {
        ?>
        <!-- Event Categories -->
        <?php do_action( 'tribe_events_community_before_the_categories' ); ?>
        <div class="buddypress-events-calendar-details eventForm bubble" id="event_taxonomy">
            <table class="tribe-community-event-info" cellspacing="0" cellpadding="0">
                <tr>
                    <td class="tribe_sectionheader">
                        <h4 class="event-time"><?php printf( __( '%s Categories', 'bp-events-calendar' ), tribe_get_event_label_singular() ); ?></h4>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div id="event-categories">
                            <ul class="bpec-categories-with-children">
                                <?php
                                $args = array(
                                    'checked_ontop' => false,
                                    'popular_cats'  => true,
                                    'selected_cats' => ! empty( $_POST['tax_input']['tribe_events_cat'] ) ? $_POST['tax_input']['tribe_events_cat'] : $event_form->get_event_cat_ids(),
                                    'taxonomy'      => Tribe__Events__Main::TAXONOMY,
                                );

                                wp_terms_checklist( empty( $event->ID ) ? 0 : $event->ID, $args );
                                ?>
                            </ul>
                        </div>
                    </td>
                </tr>
            </table><!-- .tribe-community-event-info -->
        </div><!-- .tribe-events-community-details -->
        <?php
        do_action( 'tribe_events_community_after_the_categories' );
    }
}

/**
 * Output the image upload field in the submission form.
 */
function bpec_template_image() {
    $size_format = size_format( wp_max_upload_size() ); ?>

    <!-- Event Featured Image -->
    <?php do_action( 'tribe_events_community_before_the_featured_image' ); ?>

    <div class="buddypress-events-calendar-details eventForm bubble" id="event_image_uploader">
        <table class="tribe-community-event-info" cellspacing="0" cellpadding="0">
            <tr>
                <td colspan="2" class="tribe_sectionheader">
                    <h4 class="event-time"><?php printf( esc_html__( '%s Image', 'bp-events-calendar' ), tribe_get_event_label_singular() ); ?></h4>
                </td>
            </tr>
            <tr>
                <td>
                    <label for="EventImage" class="x">
                        <?php bpec_field_label( 'event_image', __( 'Upload:', 'bp-events-calendar' ) ); ?>
                    </label>
                </td>
                <td>
                    <?php if ( get_post() && has_post_thumbnail() ) { ?>
                        <div class="tribe-community-events-preview-image">
                            <?php the_post_thumbnail( 'medium' ); ?>
                            <?php bpec_delete_featured_image_link(); ?>
                        </div>
                    <?php }	?>

                    <input type="file" name="event_image" id="EventImage">
                    <small class="note"><?php echo esc_html( sprintf( __( 'Images that are not png, jpg, or gif will not be uploaded. Images may not exceed %1$s in size.', 'bp-events-calendar' ), $size_format ) ); ?></small>
                </td>
            </tr>
        </table><!-- .tribe-community-event-info -->
    </div><!-- .tribe-events-community-details -->

    <?php
    do_action( 'tribe_events_community_after_the_featured_image' );
}

/**
 * Output the event start and end time date picker
 *
 */
function bpec_template_datepickers() {
    $has_post = get_post();
    $event_form = BPEC_Event_Form::instance();

    if ( $has_post && 0 !== get_the_ID() && 'auto-draft' !== get_post_status( $has_post ) ) {
        $all_day = $event_form->event_is_all_day();
        $start_date = $event_form->event_get_start_date();
        $end_date = $event_form->event_get_end_date();
    } else {
        $all_day = ! empty( $_POST['EventAllDay'] );
        $start_date = isset( $_POST['EventStartDate'] ) ? $_POST['EventStartDate'] : $event_form->event_get_start_date();
        $end_date = isset( $_POST['EventEndDate'] ) ? $_POST['EventEndDate'] : $event_form->event_get_end_date();
    }

    $events_label_singular = tribe_get_event_label_singular();
    $events_label_singular_lowercase = tribe_get_event_label_singular_lowercase();
    $events_label_plural_lowercase = tribe_get_event_label_plural_lowercase();  ?>

    <!-- Event Date Selection -->
    <?php do_action( 'tribe_events_community_before_the_datepickers' ); ?>

    <div class="buddypress-events-calendar-details eventForm bubble" id="event_datepickers">

        <table class="tribe-community-event-info" cellspacing="0" cellpadding="0">

            <tr>
                <td colspan="2" class="tribe_sectionheader">
                    <h4 class="event-time"><?php printf( __( '%s Time &amp; Date', 'bp-events-calendar' ), $events_label_singular ); ?></h4>
                </td><!-- .tribe_sectionheader -->
            </tr>

            <tr id="recurrence-changed-row">
                <td colspan="2">
                    <?php printf( __( 'You have changed the recurrence rules of this %1$s. Saving the %1$s will update all future %2$s.  If you did not mean to change all %2$s, then please refresh the page.', 'bp-events-calendar' ), $events_label_singular_lowercase, $events_label_plural_lowercase ); ?>
                </td>
            </tr><!-- #recurrence-changed-row -->

            <tr>
                <td><?php printf( __( 'All day %s?', 'bp-events-calendar' ), $events_label_singular_lowercase ); ?></td>
                <td>
                    <input type="checkbox" id="allDayCheckbox" name="EventAllDay" value="yes" <?php echo ( $all_day ) ? 'checked' : ''; ?> />
                </td>
            </tr>

            <tr id="tribe-event-datepickers" data-startofweek="<?php echo esc_attr( get_option( 'start_of_week' ) ); ?>">
                <td>
                    <?php bpec_field_label( 'EventStartDate', __( 'Start Date / Time:', 'bp-events-calendar' ) ); ?>
                </td>
                <td>
                    <input autocomplete="off" type="text" id="EventStartDate" class="tribe-datepicker" name="EventStartDate"  value="<?php echo esc_attr( $start_date ); ?>" />
                    <span class="helper-text hide-if-js"><?php esc_html_e( 'YYYY-MM-DD', 'bp-events-calendar' ); ?></span>
			<span class="timeofdayoptions">
				@ <?php bpec_event_start_time_selector(); ?>
			</span><!-- .timeofdayoptions -->
                </td>
            </tr>

            <tr>
                <td>
                    <?php bpec_field_label( 'EventEndDate', __( 'End Date / Time:', 'bp-events-calendar' ) ); ?>
                </td>
                <td>
                    <input autocomplete="off" type="text" id="EventEndDate" class="tribe-datepicker" name="EventEndDate" value="<?php echo esc_attr( $end_date ); ?>" />
                    <span class="helper-text hide-if-js"><?php esc_html_e( 'YYYY-MM-DD', 'bp-events-calendar' ); ?></span>
			<span class="timeofdayoptions">
				@ <?php echo bpec_event_end_time_selector(); ?>
			</span><!-- .timeofdayoptions -->
                </td>
            </tr>

            <?php if ( class_exists( 'Tribe__Events__Timezones' ) ): ?>
                <tr>
                    <td>
                        <?php bpec_field_label( 'EventTimezone', __( 'Timezone:', 'bp-events-calendar' ) ); ?>
                    </td>
                    <td>
                        <select name="EventTimezone" id="event-timezone" class="chosen">
                            <?php echo wp_timezone_choice( Tribe__Events__Timezones::get_event_timezone_string() ); ?>
                        </select>
                    </td>
                </tr>
            <?php endif ?>

            <?php do_action( 'tribe_events_date_display', null, true ); ?>

        </table><!-- .tribe-community-event-info -->

    </div>

    <?php
    do_action( 'tribe_events_community_after_the_datepickers' );
}

/**
 * Output the event venue field
 *
 */
function bpec_template_venue() {

    if ( ! isset( $event ) ) {
        $event = Tribe__Events__Main::postIdHelper();
    } ?>

    <!-- Venue -->
    <div class="buddypress-events-calendar-details eventForm bubble" id="event_tribe_venue">

        <table class="tribe-community-event-info" cellspacing="0" cellpadding="0">

            <thead> <tr>
                <td colspan="2" class="tribe_sectionheader">
                    <h4> <label class=""> <?php
                            printf( __( '%s Details', 'bp-events-calendar' ), tribe_get_venue_label_singular() );
                            ?> </label> </h4>
                </td><!-- .tribe_sectionheader -->
            </tr> </thead>

            <?php
            // The venue meta box will render everything within a <tbody>
            $venue_meta_box = new Tribe__Events__Linked_Posts__Chooser_Meta_Box( $event, Tribe__Events__Venue::POSTTYPE );
            $venue_meta_box->render();
            ?>

        </table> <!-- #event_venue -->

    </div><?php

}

/**
 * Output the organizer field for user submitted events
 */
function bpec_template_organizer() {

    if ( ! isset( $event ) ) {
        $event = Tribe__Events__Main::postIdHelper();
    } ?>

    <!-- Organizer -->
    <div class="buddypress-events-calendar-details eventForm bubble" id="event_tribe_organizer">

        <table class="tribe-community-event-info" cellspacing="0" cellpadding="0">

            <thead> <tr>
                <td colspan="2" class="tribe_sectionheader">
                    <h4> <label class=""> <?php
                            printf( __( '%s Details', 'bp-events-calendar' ), tribe_get_organizer_label_singular() );
                            ?> </label> </h4>
                </td><!-- .tribe_sectionheader -->
            </tr> </thead>

            <?php
            // The organizer meta box will render everything within a <tbody>
            $organizer_meta_box = new Tribe__Events__Linked_Posts__Chooser_Meta_Box( $event, Tribe__Events__Organizer::POSTTYPE );
            $organizer_meta_box->render();
            ?>

        </table> <!-- #event_organizer -->

    </div><?php

}

/**
 * Output the website fields in the submission form
 *
 */
function bpec_template_website() {

// If posting back, then use $POST values
    if ( ! $_POST ) {
        $event_url = function_exists( 'tribe_get_event_website_url' ) ? tribe_get_event_website_url() : tribe_community_get_event_website_url();
    } else {
        $event_url = isset( $_POST['EventURL'] ) ? esc_attr( $_POST['EventURL'] ) : '';
    }

    ?>

    <!-- Event Website -->
    <?php do_action( 'tribe_events_community_before_the_website' ); ?>

    <div class="buddypress-events-calendar-details eventForm bubble" id="event_website">

        <table class="tribe-community-event-info" cellspacing="0" cellpadding="0">

            <tr>
                <td colspan="2" class="tribe_sectionheader">
                    <h4><?php printf( __( '%s Website', 'bp-events-calendar' ), tribe_get_event_label_singular() ); ?></h4>
                </td><!-- .tribe_sectionheader -->
            </tr>

            <tr class="website">
                <td>
                    <?php bpec_field_label( 'EventURL', __( 'URL:', 'bp-events-calendar' ) ); ?>
                </td>
                <td>
                    <input type="text" id="EventURL" name="EventURL" size="25" value="<?php echo esc_url( $event_url ); ?>" />
                </td>
            </tr><!-- .website -->

        </table><!-- #event_cost -->

    </div><!-- .tribe-events-community-details -->

    <?php
    do_action( 'tribe_events_community_after_the_website' );
}

/**
 * Output the event tickets
 */
function bpec_template_ticket() {

    $event_form = BPEC_Event_Form::instance();
    $event_id = $event_form->event_id;

    $header_id  = get_post_meta( $event_id, '_tribe_ticket_header', true );
    $header_id  = ! empty( $header_id ) ? $header_id : '';
    $header_img = '';
    if ( ! empty( $header_id ) ) {
        $header_img = wp_get_attachment_image( $header_id, 'full' );
    }

    $modules = Tribe__Tickets__Tickets::modules();
    $startMinuteOptions   = Tribe__View_Helpers::getMinuteOptions( null );
    $endMinuteOptions     = Tribe__View_Helpers::getMinuteOptions( null );
    $startHourOptions     = Tribe__View_Helpers::getHourOptions( null, true );
    $endHourOptions       = Tribe__View_Helpers::getHourOptions( null, false );
    $startMeridianOptions = Tribe__View_Helpers::getMeridianOptions( null, true );
    $endMeridianOptions   = Tribe__View_Helpers::getMeridianOptions( null );

    $show_global_stock = Tribe__Tickets__Tickets::global_stock_available();
    $tickets = Tribe__Tickets__Tickets::get_event_tickets( $event_id );
    $global_stock = new Tribe__Tickets__Global_Stock( $event_id );

    ?>

    <div id="tribetickets" class="buddypress-events-calendar-details eventForm bubble">


    <table id="event_tickets" class="eventtable">
        <?php
        wp_nonce_field( 'tribe-tickets-meta-box', 'tribe-tickets-post-settings' );

        if ( get_post_meta( $event_id, '_EventOrigin', true ) === 'community-events' ) {
            ?>
            <tr>
                <td colspan="2" class="tribe_sectionheader updated">
                    <p class="error-message"><?php esc_html_e( 'This event was created using Community Events. Are you sure you want to sell tickets for it?', 'bp-events-calendar' ); ?></p>
                </td>
            </tr>
            <?php
        }
        ?>
        <tr class="event-wide-settings">
            <td colspan="2" class="tribe_sectionheader updated">
                <table class="eventtable ticket_list eventForm">
                    <tr class="tribe-tickets-image-upload">
                        <td>
                            <?php esc_html_e( 'Upload image for the ticket header.', 'bp-events-calendar' ); ?>
                            <p class="description"><?php esc_html_e( 'The maximum image size in the email will be 580px wide by any height, and then scaled for mobile. If you would like "retina" support use an image sized to 1160px wide.', 'bp-events-calendar' ); ?></p>
                        </td>
                        <td>
                            <input type="button" class="button" name="tribe_ticket_header_image" id="tribe_ticket_header_image" value="<?php esc_html_e( 'Select an Image', 'bp-events-calendar' ); ?>" />
                        </td>
                    </tr>
                    <tr class="tribe-tickets-image-preview">
                        <td colspan="2">
                            <div class="tribe_preview" id="tribe_ticket_header_preview">
                                <?php echo $header_img; ?>
                            </div>
                            <p class="description"><a href="#" id="tribe_ticket_header_remove"><?php esc_html_e( 'Remove', 'bp-events-calendar' ); ?></a></p>

                            <input type="hidden" id="tribe_ticket_header_image_id" name="tribe_ticket_header_image_id" value="<?php echo esc_attr( $header_id ); ?>" />
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <?php if ( $show_global_stock ): ?>
            <tr id="tribe-global-stock-settings" class="event-wide-settings">
                <td colspan="2">
                    <table class="eventtable ticket_list eventForm">
                        <tr>
                            <td>
                                <label for="tribe-tickets-enable-global-stock">
                                    <?php esc_html_e( 'Enable global stock', 'bp-events-calendar' ); ?>
                                </label>
                            </td>
                            <td>
                                <input type="checkbox" name="tribe-tickets-enable-global-stock" id="tribe-tickets-enable-global-stock" value="1" <?php checked( $global_stock->is_enabled() ); ?> />
                            </td>
                        </tr>
                        <tr id="tribe-tickets-global-stock-level">
                            <td>
                                <label for="tribe-tickets-global-stock">
                                    <?php esc_html_e( 'Global stock level', 'bp-events-calendar' ); ?>
                                </label>
                            </td>
                            <td>
                                <input type="number" name="tribe-tickets-global-stock" id="tribe-tickets-global-stock" value="<?php echo esc_attr( $global_stock->get_stock_level() ); ?>" />
							<span class="tribe-tickets-global-sales">
								<?php echo esc_html( sprintf( _n( '(%s sold)', '(%s sold)', $global_stock->tickets_sold(), 'bp-events-calendar' ), $global_stock->tickets_sold() ) ); ?>
							</span>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        <?php endif; ?>

        <?php
        /**
         * Fired to allow for the insertion of additional content into the ticket admin form before the tickets listing
         *
         * @param Post ID
         */
        do_action( 'tribe_events_tickets_metabox_pre', get_the_ID() ); ?>

        <tr>
            <td colspan="2" class="tribe_sectionheader ticket_list_container">

                <?php echo Tribe__Tickets__Tickets_Handler::instance()->get_ticket_list_markup( $tickets ); ?>

            </td>
        </tr>
        <tr>
            <td colspan="2" class="tribe_sectionheader">
                <a href="#" class="button-secondary"
                   id="ticket_form_toggle"><?php esc_html_e( 'Add new ticket', 'bp-events-calendar' ); ?></a>
            </td>
        </tr>
        <tr id="ticket_form" class="ticket_form">
            <td colspan="2" class="tribe_sectionheader">
                <div id="tribe-loading"><span></span></div>
                <table id="ticket_form_table" class="eventtable ticket_form">

                    <tr>
                        <td colspan="2">
                            <h4 class="ticket_form_title_add"><?php esc_html_e( 'Add new ticket', 'bp-events-calendar' ); ?></h4>
                            <h4 class="ticket_form_title_edit"><?php esc_html_e( 'Edit ticket', 'bp-events-calendar' ); ?></h4>
                        </td>
                    </tr>

                    <tr class="ticket">
                        <td width="20%"><label for="ticket_provider"><?php esc_html_e( 'Sell using:', 'bp-events-calendar' ); ?></label></td>
                        <td>
                            <?php
                            $checked = true;
                            foreach ( $modules as $class => $module ) {
                                ?>
                                <input <?php checked( $checked ); ?> type="radio" name="ticket_provider" id="ticket_provider"
                                                                     value="<?php echo esc_attr( $class ); ?>"
                                                                     class="ticket_field">
                                <span><?php echo esc_html( apply_filters( 'tribe_events_tickets_module_name', $module ) ); ?></span>
                                <?php
                                $checked = false;
                            }
                            ?>
                        </td>
                    </tr>
                    <tr class="ticket">
                        <td><label for="ticket_name"><?php esc_html_e( 'Ticket Name:', 'bp-events-calendar' ); ?></label></td>
                        <td>
                            <input type='text' id='ticket_name' name='ticket_name' class="ticket_field" size='25' value='' />
                        </td>
                    </tr>
                    <tr class="ticket">
                        <td><label
                                for="ticket_description"><?php esc_html_e( 'Ticket Description:', 'bp-events-calendar' ); ?></label>
                        </td>
                        <td>
						<textarea rows="5" cols="40" name="ticket_description" class="ticket_field"
                                  id="ticket_description"></textarea>
                        </td>
                    </tr>
                    <tr class="ticket">
                        <td><label
                                for="ticket_start_date"><?php esc_html_e( 'Start sale:', 'bp-events-calendar' ); ?></label>
                        </td>
                        <td>
                            <input
                                autocomplete="off"
                                type="text"
                                class="ticket_field"
                                size='10'
                                name="ticket_start_date"
                                id="ticket_start_date"
                                value=""
                            >
						<span class="ticket_start_time ticket_time">
							<?php echo tribe_get_datetime_separator(); ?>
                            <select name="ticket_start_hour" id="ticket_start_hour" class="ticket_field tribe-dropdown">
                                <?php echo $startHourOptions; ?>
                            </select>
							<select name="ticket_start_minute" id="ticket_start_minute" class="ticket_field tribe-dropdown">
                                <?php echo $startMinuteOptions; ?>
                            </select>
                            <?php if ( ! strstr( get_option( 'time_format', Tribe__Date_Utils::TIMEFORMAT ), 'H' ) ) : ?>
                                <select name="ticket_start_meridian" id="ticket_start_meridian" class="ticket_field tribe-dropdown">
                                    <?php echo $startMeridianOptions; ?>
                                </select>
                            <?php endif; ?>
						</span>
                        </td>
                    </tr>

                    <tr class="ticket">
                        <td valign="top"><label
                                for="ticket_end_date"><?php esc_html_e( 'End sale:', 'bp-events-calendar' ); ?></label>
                        </td>
                        <td valign="top">
                            <input autocomplete="off" type="text" class="ticket_field" size='10' name="ticket_end_date"
                                   id="ticket_end_date" value="">

						<span class="ticket_end_time ticket_time">
							<?php echo tribe_get_datetime_separator(); ?>
                            <select name="ticket_end_hour" id="ticket_end_hour" class="ticket_field tribe-dropdown">
                                <?php echo $endHourOptions; ?>
                            </select>
							<select name="ticket_end_minute" id="ticket_end_minute" class="ticket_field tribe-dropdown">
                                <?php echo $endMinuteOptions; ?>
                            </select>
                            <?php if ( ! strstr( get_option( 'time_format', Tribe__Date_Utils::TIMEFORMAT ), 'H' ) ) : ?>
                                <select name="ticket_end_meridian" id="ticket_end_meridian" class="ticket_field tribe-dropdown">
                                    <?php echo $endMeridianOptions; ?>
                                </select>
                            <?php endif; ?>
						</span>


                        </td>
                    </tr>

                    <?php
                    /**
                     * Fired to allow for the insertion of additional content into the ticket admin form
                     *
                     * @var Post ID
                     * @var null Ticket ID
                     */
                    do_action( 'tribe_events_tickets_metabox_advanced', get_the_ID(), null ); ?>

                    <tr class="ticket bottom">
                        <td></td>
                        <td>
                            <input type="hidden" name="ticket_id" id="ticket_id" class="ticket_field" value="" />
                            <input type="button" id="ticket_form_save" name="ticket_form_save" value="<?php esc_attr_e( 'Save this ticket', 'bp-events-calendar' ); ?>" class="button-primary" />
                            <input type="button" id="ticket_form_cancel" name="ticket_form_cancel" value="<?php esc_attr_e( 'Cancel', 'bp-events-calendar' ); ?>" class="button-secondary" />
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    </div>
    <?php
}

/**
 * Output the custom fields in the event form
 *
 */
function bpec_template_custom() {

    $customFields = tribe_get_option( 'custom-fields' );

    if ( empty( $customFields ) || ! is_array( $customFields ) ) {
        return;
    }
    ?>

    <!-- Custom -->
    <div class="buddypress-events-calendar-details eventForm bubble" id="event_custom">
        <table id="event-meta" class="tribe-community-event-info">

            <tbody>

            <tr>
                <td colspan="2" class="tribe_sectionheader">
                    <h4><?php esc_html_e( 'Additional Fields', 'bp-events-calendar' ); ?></h4>
                </td>
            </tr><!-- .snp-sectionheader -->

            <?php foreach ( $customFields as $customField ) :

                $val = '';
                global $post;
                if ( isset( $post->ID ) && get_post_meta( get_the_ID(), $customField['name'], true ) ) {
                    $val = get_post_meta( get_the_ID(), $customField['name'], true );
                }
                $val = apply_filters( 'tribe_community_custom_field_value', $val, $customField['name'], get_the_ID() );

                $field_id = 'tribe_custom_'.sanitize_title( $customField['label'] );
                ?>
                <tr>
                    <td>
                        <?php bpec_field_label( $customField['name'], sprintf( _x( '%s:', 'custom field label', 'bp-events-calendar' ), $customField['label'] ) ); ?>
                    </td>
                    <td>
                        <?php
                        $options = explode( "\n", $customField['values'] );
                        if ( $customField['type'] == 'text' ) {
                            ?>
                            <input type="text" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $customField['name'] ); ?>" value="<?php echo esc_attr( $val ); ?>"/>
                            <?php
                        } elseif ( $customField['type'] == 'url' ) {
                            ?>
                            <input type="url" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $customField['name'] ); ?>" value="<?php echo esc_attr( $val ); ?>"/>
                            <?php
                        } elseif ( 'radio' === $customField['type'] ) {
                            ?>
                            <div>
                                <label>
                                    <input type="radio" name="<?php echo esc_attr( $customField['name'] ) ?>" value="" <?php checked( trim( $val ), '' ) ?>/>
                                    <?php esc_html_e( 'None', 'bp-events-calendar' ); ?>
                                </label>
                            </div>
                            <?php
                            foreach ( $options as $option ) {
                                ?>
                                <div>
                                    <label>
                                        <input type="radio" name="<?php echo esc_attr( stripslashes( $customField['name'] ) ); ?>" value="<?php echo esc_attr( trim( $option ) ); ?>" <?php checked( esc_attr( trim( $val ) ), esc_attr( trim( $option ) ) ); ?>/>
                                        <?php echo esc_html( stripslashes( $option ) ); ?>
                                    </label>
                                </div>
                                <?php
                            }
                        } elseif ( $customField['type'] == 'checkbox' ) {
                            foreach ( $options as $option ) {
                                $values = ! is_array( $val ) ? explode( '|', $val ) : $val;
                                ?>
                                <div>
                                    <label>
                                        <input type="checkbox" value="<?php echo esc_attr( trim( $option ) ); ?>" <?php checked( in_array( esc_attr( trim( $option ) ), $values ) ) ?> name="<?php echo esc_html( stripslashes( $customField['name'] ) ); ?>[]"/>
                                        <?php echo esc_html( stripslashes( $option ) ); ?>
                                    </label>
                                </div>
                                <?php
                            }
                        } elseif ( $customField['type'] == 'dropdown' ) {
                            ?>
                            <select name="<?php echo esc_attr( $customField['name'] ); ?>">
                                <option value="" <?php selected( trim( $val ), '' ) ?>><?php esc_html_e( 'None', 'bp-events-calendar' ); ?></option>
                                <?php
                                $options = explode( "\n", $customField['values'] );
                                foreach ( $options as $option ) {
                                    ?>
                                    <option value="<?php echo esc_attr( trim( $option ) ); ?>" <?php selected( esc_attr( trim( $val ) ), esc_attr( trim( $option ) ) ); ?>><?php echo esc_html( stripslashes( $option ) ); ?></option>
                                    <?php
                                }
                                ?>
                            </select>
                            <?php
                        } elseif ( $customField['type'] == 'textarea' ) {
                            ?>
                            <textarea id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $customField['name'] ); ?>"><?php echo esc_textarea( stripslashes( $val ) ); ?></textarea>
                            <?php
                        }
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>

            </tbody>

        </table>
    </div><!-- #event-meta --><?php
}

/**
 * Output the pricing fields in the submission form.
 */
function bpec_template_cost() {
    global $post;

    $events_label_singular = tribe_get_event_label_singular();
    $events_label_plural_lowercase = tribe_get_event_label_plural_lowercase();

    if ( $post instanceof WP_Post ) {
        $_EventCurrencyPosition = get_post_meta( $post->ID, '_EventCurrencyPosition', true );
    } ?>

    <!-- Event Cost -->
    <?php
    do_action( 'tribe_events_community_before_the_cost' );

    if ( apply_filters( 'tribe_events_community_display_cost_section', true ) ) {
        ?>
        <div class="buddypress-events-calendar-details eventForm bubble" id="event_cost">
            <table class="tribe-community-event-info" cellspacing="0" cellpadding="0">
                <tr>
                    <td colspan="2" class="tribe_sectionheader">
                        <h4><?php printf( esc_html__( '%s Cost', 'bp-events-calendar' ), $events_label_singular ); ?></h4>
                    </td><!-- .tribe_sectionheader -->
                </tr>
                <tr>
                    <td>
                        <?php bpec_field_label( 'EventCurrencySymbol', __( 'Currency Symbol:', 'bp-events-calendar' ) ); ?>
                    </td>
                    <td>
                        <input type="text" id="EventCurrencySymbol" name="EventCurrencySymbol" size="2" value="<?php echo esc_attr( isset( $_POST['EventCurrencySymbol'] ) ? $_POST['EventCurrencySymbol'] : bpec_currency_symbol_field() ); ?>" />
                        <select id="EventCurrencyPosition" name="EventCurrencyPosition">
                            <?php
                            if ( isset( $_EventCurrencyPosition ) && 'suffix' === $_EventCurrencyPosition ) {
                                $suffix = true;
                            } elseif ( isset( $_EventCurrencyPosition ) && 'prefix' === $_EventCurrencyPosition ) {
                                $suffix = false;
                            } elseif ( true === tribe_get_option( 'reverseCurrencyPosition', false ) ) {
                                $suffix = true;
                            } else {
                                $suffix = false;
                            }
                            ?>
                            <option value="prefix"> <?php _ex( 'Before cost', 'Currency symbol position', 'bp-events-calendar' ) ?> </option>
                            <option value="suffix"<?php if ( $suffix ) {
                                echo ' selected="selected"';
                            } ?>><?php _ex( 'After cost', 'Currency symbol position', 'bp-events-calendar' ) ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>
                        <?php bpec_field_label( 'EventCost', __( 'Cost:', 'bp-events-calendar' ) ); ?>
                    </td>
                    <td><input type="text" id="EventCost" name="EventCost" size="6" value="<?php echo esc_attr( isset( $_POST['EventCost'] ) ? $_POST['EventCost'] : tribe_get_cost() ); ?>" /></td>
                </tr>
                <tr>
                    <td></td>
                    <td><small><?php printf( __( 'Leave blank to hide the field. Enter a 0 for %s that are free.', 'bp-events-calendar' ), $events_label_plural_lowercase ); ?></small></td>
                </tr>
            </table><!-- #event_cost -->
        </div><!-- .tribe-events-community-details -->
        <?php
    } //end if
    do_action( 'tribe_events_community_after_the_cost' );
}

/**
 * Output the group dropdown field in the event submission form.
 *
 */
function bpec_template_group() {
    global $post;

    $event_group_id = get_post_meta( $post->ID, 'event_group_id', true );

    $groups_arr = BP_Groups_Group::get( array(
    'type' => 'alphabetical',
    'per_page' => 999
    ) );
    ?>
    <div class="buddypress-events-calendar-details eventForm bubble" id="event_group">

        <table class="tribe-community-event-info" cellspacing="0" cellpadding="0">

            <tr>
                <td colspan="2" class="tribe_sectionheader">
                    <h4><?php printf( __( '%s Group', 'bp-events-calendar' ), tribe_get_event_label_singular() ); ?></h4>
                </td><!-- .tribe_sectionheader -->
            </tr>

            <tr class="group">
                <td>
                    <?php bpec_field_label( 'EventGroup', __( 'Group:', 'bp-events-calendar' ) ); ?>
                </td>
                <td>
                    <select name="event_group_id" id="event-group">
                        <option value="-1"><?php _e( '--Select Group --', 'bp-events-calendar' ); ?></option>
                        <?php foreach ( $groups_arr[ 'groups' ] as $group ): ?>
                            <option value="<?php echo $group->id; ?>" <?php echo (( $event_group_id == $group->id )) ? 'selected' : ''; ?>><?php _e( $group->name, '' ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr><!-- .group -->

        </table><!-- #event_cost -->

    </div><!-- .tribe-events-community-details -->
    <?php
}

/**
 * Display status icon.
 *
 */
function bpec_template_event_status( $status ) {
    $icon = str_replace( ' ', '-', $status ) . '.png';

    if ( $status == 'publish' ) {
        $status = 'Published';
    }

    echo '<img width="16" height="16" src="' . BP_EVENTS_CALENDAR_PLUGIN_URL . '/assets/images/' . $icon . '" alt="' . ucwords( $status ) . ' icon" title="' . ucwords( $status ) . '" class="icon">';

}

/**
 * Output event list row html. e.g <tr>
 *
 * Hooked into `bpec_event_list_loop` action hook.
 */
function bpec_template_event_row() {
    global $post, $bp;  ?>

    <tr>

    <td><?php bpec_template_event_status( $post->post_status ) ?></td>

    <td>
        <?php
        $canView = ( get_post_status( $post->ID ) == 'publish' || current_user_can( 'edit_post', $post->ID ) );
        $canEdit = current_user_can( 'edit_post', $post->ID );
        if ( $canEdit ) {
            ?>
            <span class="title">
								<a href=""><?php echo $post->post_title; ?></a>
            </span>
            <?php
        } else {
            echo $post->post_title;
        }
        ?>
        <div class="row-actions">
            <?php
            if ( $canView ) {
                ?>
                <span class="view">
									<a href="<?php echo esc_url( tribe_get_event_link( $post ) ); ?>"><?php esc_html_e( 'View', 'bp-events-calendar' ); ?></a>
								</span>
                <?php
            }

            if ( current_user_can( 'edit_post', $post->ID ) ) {
                echo ' | ';
                ?>
                <span class="view">
									<a href="<?php echo bpec_get_add_event_url( $post->ID ); ?>"><?php esc_html_e( 'Edit', 'bp-events-calendar' ); ?></a>
								</span>
                <?php
            }

            if ( bpec_is_event_tickets_active() ) {
                echo ' | ';
                ?>
                <span class="attendees">
                    <a href="<?php echo bpec_get_event_attendees_url( $post->ID ); ?>"><?php esc_html_e( 'Attendees', 'bp-events-calendar' ); ?></a>
                </span>
                <?php
            }

            if ( bpec_is_event_tickets_plus_active() ) {
                echo ' | ';
                ?>
                <span class="orders">
                    <a href="<?php echo bpec_get_event_orders_url( $post->ID ); ?>"><?php esc_html_e( 'Orders', 'bp-events-calendar' ); ?></a>
                </span>
                <?php
            }

            do_action( 'tribe_ce_event_list_table_row_actions', $post );
            ?>
        </div><!-- .row-actions -->
    </td>

    <td>
        <?php if ( tribe_has_organizer( $post->ID ) ) {
            echo tribe_get_organizer( $post->ID );
        } ?>
    </td>

    <td>
        <?php
        if ( tribe_has_venue( $post->ID ) ) {
            $venue_id = tribe_get_venue_id( $post->ID );
            if ( current_user_can( 'edit_post', $venue_id ) ) {
                echo '<a href="">'. tribe_get_venue( $post->ID ) .'</a>';
            } else {
                echo tribe_get_venue( $post->ID );
            }
        }
        ?>
    </td>

    <td><?php echo Tribe__Events__Admin_List::custom_columns( 'events-cats', $post->ID, false ); ?></td>

    <?php
    if ( function_exists( 'tribe_is_recurring_event' ) ) {
        ?>
        <td>
            <?php
            if ( tribe_is_recurring_event( $post->ID ) ) {
                esc_html_e( 'Yes', 'bp-events-calendar' );
            } else {
                esc_html_e( 'No', 'bp-events-calendar' );
            }
            ?>
        </td>
        <?php
    } ?>

    <td>
        <?php echo esc_html( tribe_get_start_date( $post->ID ) ) ?>
    </td>

    <td>
        <?php echo esc_html( tribe_get_end_date( $post->ID ) ) ?>
    </td>

    </tr><?php
}

/**
 * Output event attendees list row html. e.g <tr>
 *
 * Hooked into `bpec_event_attendees_list_loop` action hook.
 */
function bpec_event_attendees_list_row( $item, $event ) {
    $checked = '';
    if ( intval( $item['check_in'] ) === 1 ) {
        $checked = ' tickets_checked ';
    }

    echo '<tr class="' . esc_attr( $checked . $item['order_status'] ) . '">';
    ?>
        <td>
            <?php
            $attendee_id = trim( esc_html( bpec_get_attendee_id( $item ) ) );

            if ( ! empty( $attendee_id ) ) {
                $attendee_id .= ' &ndash; ';
            }

            ?>
            <div class="event-tickets-ticket-name">
                <?php echo $attendee_id; ?>
                <?php echo esc_html( $item['ticket'] ); ?>
            </div>

            <?php

            /**
             * Hook to allow for the insertion of additional content in the ticket table cell
             *
             * @var array $item Attendee row item
             */
            do_action( 'event_tickets_attendees_table_ticket_column', $item );
            ?>
        </td>
        <td>
            <?php
            $purchaser_name  = empty( $item[ 'purchaser_name' ] ) ? '' : esc_html( $item[ 'purchaser_name' ] );
            $purchaser_email = empty( $item[ 'purchaser_email' ] ) ? '' : esc_html( $item[ 'purchaser_email' ] );

            echo "
			<div class='purchaser_name'>{$purchaser_name}</div>
			<div class='purchaser_email'>{$purchaser_email}</div>
		";
            ?>
        </td>
        <td>
            <?php
            $security  = empty( $item[ 'security' ] ) ? '' : esc_html( $item[ 'security' ] );
            echo $security;
            ?>
        </td>
        <td>
            <?php
            $icon    = '';
            $warning = false;

            // Check if the order_warning flag has been set (to indicate the order has been cancelled, refunded etc)
            if ( isset( $item['order_warning'] ) && $item['order_warning'] ) {
                $warning = true;
            }

            // If the warning flag is set, add the appropriate icon
            if ( $warning ) {
                $icon = sprintf( "<span class='warning'><img src='%s'/></span> ", esc_url( Tribe__Tickets__Main::instance()->plugin_url . 'src/resources/images/warning.png' ) );
            }

            // Look for an order_status_label, fall back on the actual order_status string @todo remove fallback in 3.4.3
            if ( empty( $item['order_status'] ) ) {
                $item['order_status'] = '';
            }

            $label = isset( $item['order_status_label'] ) ? $item['order_status_label'] : ucwords( $item['order_status'] );

            $order_id_url = bpec_get_order_id_url( $item );

            if ( ! empty( $order_id_url ) && ! empty( $item[ 'order_id' ] ) ) {
                $label = '<a href="' . esc_url( $order_id_url ) . '">#' . esc_html( $item[ 'order_id' ] ) . ' &ndash; ' . $label . '</a>';
            } elseif ( ! empty( $item[ 'order_id' ] ) ) {
                $label = '#' . esc_html( $item[ 'order_id' ] ) . ' &ndash; ' . $label;
            }

            echo $label;
            ?>
        </td>
        <td>
            <?php
            $default_checkin_stati = array();
            $provider              = $item['provider_slug'];
            $order_id = $item['order_id'];

            /**
             * Filters the order stati that will allow for a ticket to be checked in for all commerce providers.
             *
             * @since 4.1
             *
             * @param array  $default_checkin_stati An array of default order stati that will make a ticket eligible for check-in.
             * @param string $provider              The ticket provider slug.
             * @param int    $order_id              The order post ID.
             */
            $check_in_stati = apply_filters( 'event_tickets_attendees_checkin_stati', $default_checkin_stati, $provider, $order_id );

            /**
             * Filters the order stati that will allow for a ticket to be checked in for a specific commerce provider.
             *
             * @since 4.1
             *
             * @param array  $default_checkin_stati An array of default order stati that will make a ticket eligible for check-in.
             * @param int    $order_id              The order post ID.
             */
            $check_in_stati = apply_filters( "event_tickets_attendees_{$provider}_checkin_stati", $check_in_stati, $order_id );

            if (
                ! empty( $item['order_status'] )
                && ! empty( $item['order_id_link_src'] )
                && is_array( $check_in_stati )
                && ! in_array( $item['order_status'], $check_in_stati )
            ) {
                $button_template = '<a href="%s" class="button-secondary tickets-checkin">%s</a>';

                return sprintf( $button_template, $item['order_id_link_src'], __( 'View order', 'bp-events-calendar' ) );
            }

            $button_classes = ! empty( $item['order_status'] ) && in_array( $item['order_status'], $check_in_stati ) ?
                'button-primary' : 'button-primary button-disabled';

            if ( empty( $event ) ) {
                $checkin   = sprintf(
                    '<a href="#" data-attendee-id="%d" data-provider="%s" class="%s tickets_checkin">%s</a>',
                    esc_attr( $item['attendee_id'] ),
                    esc_attr( $item['provider'] ),
                    esc_attr( $button_classes ),
                    esc_html__( 'Check In', 'bp-events-calendar' )
                );
                $uncheckin = sprintf(
                    '<span class="delete"><a href="#" data-attendee-id="%d" data-provider="%s" class="tickets_uncheckin">%s</a></span>',
                    esc_attr( $item['attendee_id'] ),
                    esc_attr( $item['provider'] ),
                    sprintf(
                        '<div>%1$s</div><div>%2$s</div>',
                        esc_html__( 'Undo', 'bp-events-calendar' ),
                        esc_html__( 'Check In', 'bp-events-calendar' )
                    )
                );
            } else {
                // add the additional `data-event-id` attribute if this is an event
                $checkin   = sprintf(
                    '<a href="#" data-attendee-id="%d" data-event-id="%d" data-provider="%s" class="%s tickets_checkin">%s</a>',
                    esc_attr( $item['attendee_id'] ),
                    esc_attr( $this->event->ID ),
                    esc_attr( $item['provider'] ),
                    esc_attr( $button_classes ),
                    esc_html__( 'Check In', 'bp-events-calendar' )
                );
                $uncheckin = sprintf(
                    '<span class="delete"><a href="#" data-attendee-id="%d" data-event-id="%d" data-provider="%s" class="tickets_uncheckin">%s</a></span>',
                    esc_attr( $item['attendee_id'] ),
                    esc_attr( $this->event->ID ), esc_attr( $item['provider'] ),
                    sprintf(
                        '<div>%1$s</div><div>%2$s</div>',
                        esc_html__( 'Undo', 'bp-events-calendar' ),
                        esc_html__( 'Check In', 'bp-events-calendar' )
                    )
                );
            }

            echo $checkin . $uncheckin;
            ?>
        </td>
    <?php
    echo '</tr>';
}

/**
 * Output event order list row html. e.g <tr>
 *
 * Hooked into `bpec_event_orders_list_loop` action hook.
 * @param $item
 * @param $event_id
 * @param $valid_order_items
 * @param $pass_fees_to_user
 * @param $fee_percent
 * @param $fee_flat
 * @return string
 * @internal param $event_ID
 * @internal param $event
 */
function bpec_event_orders_list_row( $item, $event_id, $valid_order_items, $pass_fees_to_user, $fee_percent, $fee_flat ) {
    ?>
    <tr>
        <td>
            <?php

            $order_number = $item['order_number'];

            $order_url = add_query_arg(
                array(
                    'post'   => $order_number,
                    'action' => 'edit',
                ), admin_url( 'post.php' )
            );

            $order_number_link = '<a href="' . esc_url( $order_url ) . '">#' . absint( $order_number ) . '</a>';

            $output = sprintf(
                esc_html__(
                    '%1$s', 'the-events-calendar'
                ), $order_number_link
            );

            if ( 'completed' !== $item['status'] ) {
                $output .= '<div class="order-status order-status-' . esc_attr( $item['status'] ) . '">' . esc_html(
                        ucwords( $item['status'] )
                    ) . '</div>';
            }

            echo $output;
            ?>
        </td>
        <td>
            <?php
            $customer = Tribe__Tickets_Plus__Commerce__WooCommerce__Orders__Customer::make_from_item( $item );
            echo $customer->get_name();
            ?>
        </td>
        <td>
            <?php
            $customer = Tribe__Tickets_Plus__Commerce__WooCommerce__Orders__Customer::make_from_item( $item );
            echo $customer->get_email();
            ?>
        </td>
        <td>
            <?php
            $tickets   = array();
            $num_items = 0;

            foreach ( $item['line_items'] as $line_item ) {

                $num_items += $line_item['quantity'];

                if ( empty( $tickets[ $line_item['name'] ] ) ) {
                    $tickets[ $line_item['name'] ] = 0;
                }

                $tickets[ $line_item['name'] ] += $line_item['quantity'];
            }

            ksort( $tickets );

            $output = '';

            foreach ( $tickets as $name => $quantity ) {

                $output .= "<div class='tribe-line-item'>{$quantity} - {$name}</div>";
            }

            echo $output;
            ?>
        </td>
        <td>
            <?php
            $shipping = $item['shipping_address'];

            if ( empty( $shipping['address_1'] )
                || empty( $shipping['city'] )
            ) {
                return '';
            }

            $address = trim( "{$shipping['first_name']} {$shipping['last_name']}" );

            if ( ! empty( $shipping['company'] ) ) {
                if ( $address ) {
                    $address .= '<br>';
                }

                $address .= $shipping['company'];
            }

            $address .= "<br>{$shipping['address_1']}<br>";

            if ( ! empty( $shipping['address_2'] ) ) {
                $address .= "{$shipping['address_2']}<br>";
            }

            $address .= $shipping['city'];

            if ( ! empty( $shipping['state'] ) ) {
                $address .= ", {$shipping['state']}";
            }

            if ( ! empty( $shipping['country'] ) ) {
                $address .= " {$shipping['country']}";
            }

            if ( ! empty( $shipping['postcode'] ) ) {
                $address .= " {$shipping['postcode']}";
            }

            echo $address;
            ?>
        </td>
        <td>
            <?php echo Tribe__Date_Utils::reformat( $item['completed_at'], Tribe__Date_Utils::DATEONLYFORMAT ); ?>
        </td>
        <td>
            <?php
            $order = wc_get_order( $item['id'] );

            if ( empty( $order ) ) {
                return '';
            }

            echo wc_get_order_status_name( $order->get_status() );
            ?>
        </td>
        <td>
            <?php
            $total = 0;

            foreach ( $valid_order_items[ $item['id'] ] as $line_item ) {
                $total += $line_item['subtotal'];
            }

            if ( ! $pass_fees_to_user ) {
                $total -= round( $total * ( $fee_percent / 100 ), 2 ) + $fee_flat;
            }

            echo tribe_format_currency( number_format( $total, 2 ) );
            ?>
        </td>
    </tr>
    <?php
}

/**
 * Output the group event join dropdown button
 *
 */
function bpec_group_join_event_button() {
    global $post, $groups_template;

    $status = bpec_get_members_event_status( array( 'group_id' => $groups_template->group->id, 'event_id' => $post->ID ) );
   ?>
    <div class="bpec-drop-button">

        <button class="bpec-join-event-button button">
            <?php if ( 'interested' == $status ): ?>
                <span><?php _e( 'Interested', 'bp-events-calendar' ) ?></span>
            <?php elseif ( 'going' == $status ): ?>
                <span><?php _e( 'Going', 'bp-events-calendar' ) ?></span>
            <?php else: ?>
                <span><?php _e( 'Join', 'bp-events-calendar' ) ?></span>
            <?php endif; ?>
        </button>

        <div class="bpec-join-drop-element" data-eid="<?php echo $post->ID ?>" data-gid="<?php echo $groups_template->group->id ?>">
            <ul>

                <li data-eaction="going" data-_wpnonce="<?php echo wp_create_nonce( 'event_join' ) ?>">
                    <a href="#">
                        <svg aria-hidden="true" class="octicon octicon-check drop-element-item-icon <?php echo $status != 'going' ? 'hide' : '' ?>" height="16" version="1.1" viewBox="0 0 12 16" width="12"><path fill-rule="evenodd" d="M12 5l-8 8-4-4 1.5-1.5L4 10l6.5-6.5z"></path></svg>
                        <span><?php _e('Going', 'bp-events-calendar' ) ?></span>
                    </a>
                </li>

                <li data-eaction="interested" data-_wpnonce="<?php echo wp_create_nonce( 'event_join' ) ?>">
                    <a href="#">
                        <svg aria-hidden="true" class="octicon octicon-check drop-element-item-icon <?php echo $status != 'interested' ? 'hide' : '' ?>" height="16" version="1.1" viewBox="0 0 12 16" width="12"><path fill-rule="evenodd" d="M12 5l-8 8-4-4 1.5-1.5L4 10l6.5-6.5z"></path></svg>
                        <span><?php _e( 'Interested', 'bp-events-calendar' ) ?></span>
                    </a>
                </li>

                <?php if ( 'interested' == $status  ): ?>
                <div class="drop-element-divider"></div>
                <li data-eaction="delete" data-_wpnonce="<?php echo wp_create_nonce( 'event_join' ) ?>">
                    <a href="#" >
                        <span><?php _e( 'Not Interested', 'bp-events-calendar' ) ?></span>
                    </a>
                </li>

                <?php elseif ( 'going' == $status  ): ?>
                <div class="drop-element-divider"></div>
                <li data-eaction="delete" data-_wpnonce="<?php echo wp_create_nonce( 'event_join' ) ?>">
                    <a href="#" >
                       <span class="drop-element-item-text"><?php _e( 'Not Going', 'bp-events-calendar' ) ?></span>
                    </a>
                </li>
                <?php else: ?>
                <div class="drop-element-divider hide"></div>
                <li class="hide" data-eaction="delete" data-_wpnonce="<?php echo wp_create_nonce( 'event_join' ) ?>">
                    <a href="#" >
                        <span class="drop-element-item-text"></span>
                    </a>
                </li>
                <?php endif; ?>

            </ul>
        </div><!-- .bpec-join-drop-element -->

    </div> <!-- .bpec-drop-button -->
    <?php
}

/**
 * Output the event guests
 *
 */
function bpec_event_guests() {
    global $post, $groups_template;

    $count = BPEC_Events_Members::get_members_count( $post->ID );
    ?>
    <div class="event-guests meta">
        <span>
            <a href="#guests-popup" class="bpec-guests-popup-btn" data-eid="<?php echo $post->ID ?>" data-gid="<?php echo $groups_template->group->id ?>" data-_wpnonce="<?php echo wp_create_nonce('guests_list') ?>"><?php printf( _n( '%1$s Guest', '%1$s Guests', $count, 'bp-events-calendar' ), $count ); ?></a>
        </span>
    </div>
    <?
}

/**
 * Output the event guests(members) list
 *
 */
function bpec_event_guests_list() {
    ?>
    <div id="buddypress">
        <div id="members-dir-list" class="members dir-list">
            <?php  bpec_get_event_template('bpec-event-guests.php', array() ); ?>
        </div><!-- #members-dir-list -->
    </div>
    <?php
    exit;
}

/**
 * Output the placeholder content for the guests modal poup
 */
function bpec_guests_popup_inner() {
    ?>
    <ul class="tabs-menu">
        <li class="current"><a href="#tab-going"><?php _e( 'Going', 'bp-events-calendar' ) ?></a></li>
        <li><a href="#tab-interested"><?php _e( 'Interested', 'bp-events-calendar' ) ?></a></li>
    </ul>
    <div class="tab">
        <div id="tab-going" class="tab-content">
                <div class="timeline-wrapper">
                    <div class="timeline-item">
                        <div class="animated-background">
                            <div class="background-masker header-top"></div>
                            <div class="background-masker header-left"></div>
                            <div class="background-masker header-right"></div>
                            <div class="background-masker header-bottom"></div>
                            <div class="background-masker subheader-left"></div>
                            <div class="background-masker subheader-right"></div>
                            <div class="background-masker subheader-bottom"></div>
                            <div class="background-masker content-top"></div>
                            <div class="background-masker content-first-end"></div>
                            <div class="background-masker content-second-line"></div>
                            <div class="background-masker content-second-end"></div>
                            <div class="background-masker content-third-line"></div>
                            <div class="background-masker content-third-end"></div>
                        </div>
                    </div>
            </div>
        </div>
        <div id="tab-interested" class="tab-content" style="display: none">
                <div class="timeline-wrapper">
                    <div class="timeline-item">
                        <div class="animated-background">
                            <div class="background-masker header-top"></div>
                            <div class="background-masker header-left"></div>
                            <div class="background-masker header-right"></div>
                            <div class="background-masker header-bottom"></div>
                            <div class="background-masker subheader-left"></div>
                            <div class="background-masker subheader-right"></div>
                            <div class="background-masker subheader-bottom"></div>
                            <div class="background-masker content-top"></div>
                            <div class="background-masker content-first-end"></div>
                            <div class="background-masker content-second-line"></div>
                            <div class="background-masker content-second-end"></div>
                            <div class="background-masker content-third-line"></div>
                            <div class="background-masker content-third-end"></div>
                        </div>
                    </div>
                </div>

        </div>
    </div>
    <?php
}
