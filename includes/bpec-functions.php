<?php
/**
 * BP Events Calender functions
 *
 * @version  1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// require an admin functions file so we can leverage is_active_plugin
include_once ABSPATH . '/wp-admin/includes/plugin.php';

/**
 * Get template part (for templates in loops).
 *
 * @param string $slug
 * @param string $name (default: '')
 * @param string $template_path (default: 'buddypress_events_calendar')
 * @param string|bool $default_path (default: '') False to not load a default
 */
function bpec_get_event_manager_template_part( $slug, $name = '', $template_path = 'buddypress_events_calendar', $default_path = '' ) {
	$template = '';

	if ( $name ) {
		$template = bpec_locate_event_template( "{$slug}-{$name}.php", $template_path, $default_path );
	}

	// If template file doesn't exist, look in yourtheme/slug.php and yourtheme/buddypress_events_calendar/slug.php
	if ( ! $template ) {
		$template = bpec_locate_event_template( "{$slug}.php", $template_path, $default_path );
	}

	if ( $template ) {
		load_template( $template, false );
	}
}

/**
 * Get and include template files.
 *
 * @param mixed $template_name
 * @param array $args (default: array())
 * @param string $template_path (default: '')
 * @param string $default_path (default: '')
 * @return void
 */
function bpec_get_event_template( $template_name, $args = array(), $template_path = 'buddypress_events_calendar', $default_path = '' ) {
	if ( $args && is_array( $args ) ) {
		extract( $args );
	}
	include( bpec_locate_event_template( $template_name, $template_path, $default_path ) );
}

/**
 * Locate a template and return the path for inclusion.
 *
 * This is the load order:
 *
 *		yourtheme		/	$template_path	/	$template_name
 *		yourtheme		/	$template_name
 *		$default_path	/	$template_name
 *
 * @param string $template_name
 * @param string $template_path (default: 'buddypress_events_calendar')
 * @param string|bool $default_path (default: '') False to not load a default
 * @return string
 */
function bpec_locate_event_template( $template_name, $template_path = 'buddypress_events_calendar', $default_path = '' ) {
	// Look within passed path within the theme - this is priority
	$template = locate_template(
		array(
			trailingslashit( $template_path ) . $template_name,
			$template_name
		)
	);

	// Get default template
	if ( ! $template && $default_path !== false ) {
		$default_path = $default_path ? $default_path : BP_EVENTS_CALENDAR_PLUGIN_DIR . '/templates/';
		if ( file_exists( trailingslashit( $default_path ) . $template_name ) ) {
			$template = trailingslashit( $default_path ) . $template_name;
		}
	}

	// Return what we found
	return apply_filters( 'event_manager_locate_template', $template, $template_name, $template_path );
}

/**
 * Filter pagination.
 */
function bpec_pagination( $query, $pages = 0, $range = 3 ) {
	$output    = '';
	$showitems = ( $range * 2 ) + 1;

	global $paged;
	if ( empty( $paged ) )
		$paged = 1;

	if ( $pages == 0 ) {
		//global $wp_query;
		$pages = ceil( $query->found_posts / 10 );

		//echo $pages;

		if ( ! $pages ) {
			$pages = 1;
		}
	}

	if ( $paged > $pages ) {
		bp_core_add_message( __( 'The requested page number was not found.', 'buddypress-event-calendar' ), 'error' );
	}
	if ( 1 != $pages ) {

		$output .= "<div class='pagination'>";
		if ( $paged > 2 && $paged > $range + 1 && $showitems < $pages ) {
			$pagenum_link = get_pagenum_link( 1 );
			$pagenum_link = strpos( $pagenum_link, 'event-lists' ) ? $pagenum_link : str_replace( '/events/', '/events/event-lists/', $pagenum_link );
			$output .= "<a href='" . esc_url($pagenum_link) . "'>&laquo;</a>";
		}

		if ( $paged > 1 && $showitems < $pages ) {
			$pagenum_link = get_pagenum_link( $paged - 1 );
			$pagenum_link = strpos( $pagenum_link, 'event-lists' ) ? $pagenum_link : str_replace( '/events/', '/events/event-lists/', $pagenum_link );
			$output .= "<a href='" . esc_url($pagenum_link) . "'>&lsaquo;</a>";
		}

		for ( $i = 1; $i <= $pages; $i++ ) {
			if ( 1 != $pages && ( ! ( $i >= $paged + $range + 1 || $i <= $paged - $range - 1 ) || $pages <= $showitems ) ) {
				$pagenum_link = get_pagenum_link( $i );
				$pagenum_link = strpos( $pagenum_link, 'event-lists' ) ? $pagenum_link : str_replace( '/events/', '/events/event-lists/', $pagenum_link );
				$output .= ( $paged == $i ) ? '<span class="current">' . $i . '</span>' : '<a href="' . esc_url( $pagenum_link ) . '" class="inactive">' . $i . '</a>';
			}
		}

		if ( $paged < $pages && $showitems < $pages ) {
			$pagenum_link = get_pagenum_link( $paged + 1 );
			$pagenum_link = strpos( $pagenum_link, 'event-lists' ) ? $pagenum_link : str_replace( '/events/', '/events/event-lists/', $pagenum_link );
			$output .= "<a href='" . esc_url($pagenum_link) . "'>&rsaquo;</a>";
		}

		if ( $paged < $pages - 1 && $paged + $range - 1 < $pages && $showitems < $pages ) {
			$pagenum_link = get_pagenum_link( $pages );
			$pagenum_link = strpos( $pagenum_link, 'event-lists' ) ? $pagenum_link : str_replace( '/events/', '/events/event-lists/', $pagenum_link );
			$output .= "<a href='" . esc_url($pagenum_link) . "'>&raquo;</a>";
		}

		$output .= "</div>\n";
	}

	return $output;

}

/**
 * Return the add/edit event url
 * @param int $event_id
 * @return string
 */
function bpec_get_add_event_url( $event_id = 0 ) {
	global $bp;

	$add_event_url = $bp->displayed_user->domain . 'events/add-event/';

	if ( ! empty($event_id) && is_numeric( $event_id ) ) {
		$add_event_url .= $event_id;
	}

	return $add_event_url;
}

/**
 * Return the event attendees url
 * @param int $event_id
 * @return string
 */
function bpec_get_event_attendees_url( $event_id = 0 ) {
	global $bp;

	$add_event_url = $bp->displayed_user->domain . 'events/attendees/';

	if ( ! empty($event_id) && is_numeric( $event_id ) ) {
		$add_event_url .= $event_id;
	}

	return $add_event_url;
}

/**
 * Return the event orders url
 * @param int $event_id
 * @return string
 */
function bpec_get_event_orders_url( $event_id = 0 ) {
	global $bp;

	$add_event_url = $bp->displayed_user->domain . 'events/orders/';

	if ( ! empty($event_id) && is_numeric( $event_id ) ) {
		$add_event_url .= $event_id;
	}

	return $add_event_url;
}

/**
 * Test to see if this is the Venue edit screen
 *
 * @param int|null $venue_id (optional)
 * @return bool
 */
function bpec_is_venue_edit_screen( $venue_id = null ) {
	$venue_id = Tribe__Events__Main::postIdHelper( $venue_id );
	return ( tribe_is_venue( $venue_id ) );
}

/**
 * Test to see if this is the Organizer edit screen
 *
 * @param int|null $organizer_id (optional)
 * @return bool
 */
function bpec_is_organizer_edit_screen( $organizer_id = null ) {
	$organizer_id = Tribe__Events__Main::postIdHelper( $organizer_id );
	$is_organizer = ( $organizer_id ) ? Tribe__Events__Main::instance()->isOrganizer( $organizer_id ) : false;
	return apply_filters( 'tribe_is_organizer', $is_organizer, $organizer_id );
}



/**
 * Record event-related activity to the activity stream.
 *
 * @see bp_activity_add() for description of parameters.
 * @global object $bp The BuddyPress global settings object.
 *
 * @param array $args {
 *     See {@link bp_activity_add()} for complete description of arguments.
 *     The arguments listed here have different default values from
 *     bp_activity_add().
 *     @type string $component Default: 'blogs'.
 * }
 * @return int|bool On success, returns the activity ID. False on failure.
 */
function bpec_event_listing_record_activity( $args = '' ) {
	global $bp;

	// Bail if activity is not active
	if ( ! bp_is_active( 'activity' ) ) {
		return false;
	}

	$defaults = array(
		'user_id'           => bp_loggedin_user_id(),
		'action'            => '',
		'content'           => '',
		'primary_link'      => '',
		'component'         => '',
		'type'              => false,
		'item_id'           => false,
		'secondary_item_id' => false,
		'recorded_time'     => bp_core_current_time(),
		'hide_sitewide'     => false
	);

	$r = wp_parse_args( $args, $defaults );

	// Remove large images and replace them with just one image thumbnail
	if ( ! empty( $r['content'] ) ) {
		$r['content'] = bp_activity_thumbnail_content_images( $r['content'], $r['primary_link'], $r );
	}

	if ( ! empty( $r['action'] ) ) {
		$r['action'] = apply_filters( 'bp_event_listing_record_activity_action', $r['action'] );
	}

	if ( ! empty( $r['content'] ) ) {
		$r['content'] = apply_filters( 'bp_event_listing_record_activity_content', bp_create_excerpt( $r['content'] ), $r['content'], $r );
	}

	// Check for an existing entry and update if one exists.
	$id = bp_activity_get_activity_id( array(
		'user_id'           => $r['user_id'],
		'component'         => $r['component'],
		'type'              => $r['type'],
		'item_id'           => $r['item_id'],
		'secondary_item_id' => $r['secondary_item_id'],
	) );

	return bp_activity_add( array( 'id' => $id, 'user_id' => $r['user_id'], 'action' => $r['action'], 'content' => $r['content'], 'primary_link' => $r['primary_link'], 'component' => $r['component'], 'type' => $r['type'], 'item_id' => $r['item_id'], 'secondary_item_id' => $r['secondary_item_id'], 'recorded_time' => $r['recorded_time'], 'hide_sitewide' => $r['hide_sitewide'] ) );
}

/**
 * Record a new blog post in the BuddyPress activity stream.
 *
 * @param int $post_id ID of the post being recorded.
 * @param object $post The WP post object passed to the 'save_post' action.
 * @param int $user_id Optional. The user to whom the activity item will be
 *        associated. Defaults to the post_author.
 * @return bool|null Returns false on failure.
 */
function bpec_event_listing_record_post( $post_id ) {
	global $bp, $wpdb;

	$post_id 	= (int) $post_id;
	$blog_id 	= (int) $wpdb->blogid;
	$post 		= get_post( $post_id );
	$user_id 	= (int) $post->post_author;
    $group_id   = get_post_meta( $post_id, 'event_group_id', true );

	// Don't record this if it's not a tribe_events
	if ( !in_array( $post->post_type, apply_filters( 'bpec_event_listing_record_post_post_types', array( 'tribe_events' ) ) ) )
		return false;

	if ( 'publish' == $post->post_status ) {

			// Record this in activity streams
			$post_permalink = add_query_arg(
				'p',
				$post_id,
				trailingslashit( get_home_url( $blog_id ) )
			);

			// Make sure there's not an existing entry for this post (prevent bumping)
			if ( bp_is_active( 'activity' ) ) {

			    if ( empty( $group_id ) ) {
                    $existing = bp_activity_get( array(
                        'filter' => array(
                            'action'       => 'new_event_post',
                            'component'    => 'bpec',
                            'primary_id'   => $blog_id,
                            'secondary_id' => $post_id,
                        )
                    ) );
                } else {
                    $existing = bp_activity_get( array(
                        'filter' => array(
                            'action'       => 'new_event_post',
                            'component'    => $bp->groups->id,
                            'primary_id'   => $group_id,
                            'secondary_id' => $post_id,
                        )
                    ) );
                }

				if ( !empty( $existing['activities'] ) ) {
					return;
				}
			}

			$activity_content = $post->post_content;

            if ( empty( $group_id ) ) {
                $args = array(
                    'user_id'           => $user_id,
                    'content'           => apply_filters( 'bpec_event_listing_activity_new_post_content',      $activity_content, $post, $post_permalink ),
                    'primary_link'      => apply_filters( 'bpec_event_listing_activity_new_post_primary_link', $post_permalink,   $post_id               ),
                    'type'              => 'new_event_post',
                    'component'         => 'bpec',
                    'item_id'           => $blog_id,
                    'secondary_item_id' => $post_id,
                    'recorded_time'     => $post->post_date_gmt,
                );
            } else {

                // Set the default for hide_sitewide by checking the status of the group.
                $hide_sitewide  = false;
                $group          = groups_get_group( $group_id );

                if ( isset( $group->status ) && 'public' != $group->status ) {
                    $hide_sitewide = true;
                }

                $args = array(
                    'user_id'           => (int) $user_id,
                    'content'           => apply_filters( 'bpec_event_listing_activity_new_post_content',      $activity_content, $post, $post_permalink ),
                    'primary_link'      => apply_filters( 'bpec_event_listing_activity_new_post_primary_link', $post_permalink,   $post_id               ),
                    'type'              => 'new_event_post',
                    'component'         => $bp->groups->id,
                    'item_id'           => (int) $group_id,
                    'secondary_item_id' => (int) $post_id,
                    'hide_sitewide'     => $hide_sitewide
                );
            }

			$activity_id = bpec_event_listing_record_activity( $args );

			// save post title in activity meta
			if ( bp_is_active( 'activity' ) ) {
				bp_activity_update_meta( $activity_id, 'post_title', $post->post_title );
				bp_activity_update_meta( $activity_id, 'post_url',   $post_permalink );
			}
    }

	do_action( 'bpec_event_listing_record_post', $post_id, $post, $user_id );
}

/**
 * Format 'new_event_post' activity actions.
 *
 * @param string $action Static activity action.
 * @param obj $activity Activity data object.
 */
function bpec_events_listing_format_activity_action_new_event_post( $action, $activity ) {

	$post_url = add_query_arg( 'p', $activity->secondary_item_id, site_url() );

	$post_title = bp_activity_get_meta( $activity->id, 'post_title' );

	// Should only be empty at the time of post creation
	if ( empty( $post_title ) ) {

		$post = get_post( $activity->secondary_item_id );

		if ( is_a( $post, 'WP_Post' ) ) {
			$post_title = $post->post_title;
			bp_activity_update_meta( $activity->id, 'post_title', $post_title );
		}

	}

	$post_link  = '<a href="' . $post_url . '">' . $post_title . '</a>';

	$user_link = bp_core_get_userlink( $activity->user_id );

	$action  = sprintf( __( '%1$s posted a new event, %2$s', 'bp-events-calendar' ), $user_link, $post_link );


	// Legacy filter - requires the post object
	if ( has_filter( 'bpec_event_listing_activity_new_post_action' ) ) {

		$post = get_post( $activity->secondary_item_id );

		if ( ! empty( $post ) && ! is_wp_error( $post ) ) {
			$action = apply_filters( 'bped_event_listing_activity_new_post_action', $action, $post, $post_url );
		}
	}

	return apply_filters( 'bpec_event_listing_format_activity_action_new_event_post', $action, $activity );
}

/**
 * Register activity actions for the events.
 *
 * @return false|null False on failure.
 */
function bpec_register_activity_actions() {
    global $bp;

	// Bail if activity is not active
	if ( ! bp_is_active( 'activity' ) ) {
		return false;
	}

	bp_activity_set_action(
		'bpec',
		'new_event_post',
		__( 'New Event', 'bp-events-calendar' ),
		'bpec_events_listing_format_activity_action_new_event_post',
		__( 'Events', 'bp-events-calendar' ),
		array( 'activity', 'member' )
	);

    bp_activity_set_action(
        $bp->groups->id,
        'new_event_post',
        __( 'New Event', 'bp-events-calendar' ),
        'bpec_events_listing_format_activity_action_new_event_post',
        __( 'Events', 'bp-events-calendar' ),
        array( 'activity', 'member' )
    );

    bp_activity_set_action(
        $bp->groups->id,
        'joined_event',
        __( 'Joined an event', 'bp-events-calendar' ),
        'bpec_format_activity_action_joined_event',
        __( 'Event Join', 'bp-events-calendar' ),
        array( 'activity', 'group', 'member', 'member_groups' )
    );

    do_action( 'bpec_event_listing_register_activity_actions' );
}


/**
 * Returns the attendee ID (or "unique ID" if set).
 *
 * @param array $item
 *
 * @return int|string
 */
function bpec_get_attendee_id( $item ) {
    $attendee_id = empty( $item['attendee_id'] ) ? '' : $item['attendee_id'];
    if ( $attendee_id === '' ) {
        return '';
    }

    $unique_id = get_post_meta( $attendee_id, '_unique_id', true );

    if ( $unique_id === '' ) {
        $unique_id = $attendee_id;
    }

    /**
     * Filters the ticket number; defaults to the ticket unique ID.
     *
     * @param string $unique_id A unique string identifier for the ticket.
     * @param array  $item      The item entry.
     */
    return apply_filters( 'tribe_events_tickets_attendees_table_attendee_id_column', $unique_id, $item );
}

/**
 * Retrieves the order id for the specified table row item.
 *
 * In some cases, such as when the current item belongs to the RSVP provider, an
 * empty string may be returned as there is no order screen that can be linekd to.
 *
 * @param array $item
 *
 * @return string
 */
function bpec_get_order_id_url( array $item ) {
    // Backwards compatibility
    if ( empty( $item['order_id_url'] ) ) {
        $item['order_id_url'] = get_edit_post_link( $item['order_id'], true );
    }

    return $item['order_id_url'];
}

/**
 * Is see Events Tickets is installed and active?
 *
 *
 * @return boolean True if Events Tickets is active, false if not.
 */
function bpec_is_event_tickets_active() {

    // Single site.
    if ( is_plugin_active( 'event-tickets/event-tickets.php' ) )
        return true;

    // Network active.
    if ( is_plugin_active_for_network( 'event-tickets/event-tickets.php' ) )
        return true;

    // Nope.
    return false;
}

/**
 * Is see Events Tickets Plus is installed and active?
 *
 *
 * @return boolean True if Events Tickets Plus is active, false if not.
 */
function bpec_is_event_tickets_plus_active() {

    // Single site.
    if ( is_plugin_active( 'event-tickets-plus/event-tickets-plus.php' ) )
        return true;

    // Network active.
    if ( is_plugin_active_for_network( 'event-tickets-plus/event-tickets-plus.php' ) )
        return true;

    // Nope.
    return false;
}

/**
 * @param $event_id
 * @return array
 */
function bpec_get_orders( $event_id ) {
    if ( ! $event_id ) {
        return array();
    }

    WC()->api->includes();
    WC()->api->register_resources( new WC_API_Server( '/' ) );

    $main = Tribe__Tickets_Plus__Commerce__WooCommerce__Main::get_instance();

    $tickets = $main->get_tickets( $event_id );

    $args = array(
        'post_type'      => 'tribe_wooticket',
        'posts_per_page' => - 1,
        'post_status'    => array(
            'wc-pending',
            'wc-processing',
            'wc-on-hold',
            'wc-completed',
            'publish',
        ),
        'meta_query'     => array(
            array(
                'key'   => Tribe__Tickets_Plus__Commerce__WooCommerce__Main::ATTENDEE_EVENT_KEY,
                'value' => $event_id,
            ),
        ),
    );

    $orders = array();
    $query  = new WP_Query( $args );
    foreach ( $query->posts as &$item ) {
        $order_id = get_post_meta( $item->ID, Tribe__Tickets_Plus__Commerce__WooCommerce__Main::ATTENDEE_ORDER_KEY, true );

        if ( isset( $orders[ $order_id ] ) ) {
            continue;
        }

        $order               = WC()->api->WC_API_Orders->get_order( $order_id );
        //prevent fatal error if no orders
        if ( ! is_wp_error( $order ) ) {
            $orders[ $order_id ] = $order['order'];
        }
    }

    return $orders;
}

/**
 * @param $event_id
 * @param $items
 * @return array
 */
function bpec_get_valid_order_items_for_event( $event_id, $items ) {
    $valid_order_items = array();

    $event_id = absint( $event_id );

    foreach ( $items as $order ) {
        if ( ! isset( $valid_order_items[ $order['id'] ] ) ) {
            $valid_order_items[ $order['id'] ] = array();
        }

        foreach ( $order['line_items'] as $line_item ) {
            $ticket_id       = $line_item['product_id'];
            $ticket_event_id = absint(
                get_post_meta( $ticket_id, Tribe__Tickets_Plus__Commerce__WooCommerce__Main::get_instance()->event_key, true )
            );

            // if the ticket isn't for the currently viewed event, skip it
            if ( $ticket_event_id !== $event_id ) {
                continue;
            }

            $valid_order_items[ $order['id'] ][ $ticket_id ] = $line_item;
        }
    }

    return $valid_order_items;
}

/**
 * @param $product_id
 * @return array|bool
 */
function bpec_get_total_sales_per_productby_status( $product_id ) {
    global $wpdb;

    if ( ! $product_id ) {
        return false;
    }

    $order_items = array();

    $order_statuses = array(
        'wc-completed',
        'wc-pending',
        'wc-processing',
        'wc-cancelled',
    );

    foreach ( $order_statuses as $order_status ) {

        $sql = $wpdb->prepare( "
 						SELECT SUM( order_item_meta.meta_value ) as _qty,
 						SUM( order_item_meta_3.meta_value ) as _line_total
 						FROM {$wpdb->prefix}woocommerce_order_items as order_items

						LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
						LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta_2 ON order_items.order_item_id = order_item_meta_2.order_item_id
						LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta_3 ON order_items.order_item_id = order_item_meta_3.order_item_id
						LEFT JOIN {$wpdb->posts} AS posts ON order_items.order_id = posts.ID

						WHERE posts.post_type = 'shop_order'
						AND posts.post_status IN ( '$order_status' )
						AND order_items.order_item_type = 'line_item'
						AND order_item_meta.meta_key = '_qty'
						AND order_item_meta_2.meta_key = '_product_id'
						AND order_item_meta_2.meta_value = %s
						AND order_item_meta_3.meta_key = '_line_total'

						GROUP BY order_item_meta_2.meta_value
					",
            $product_id
        );

        $order_items[ $order_status ] = $wpdb->get_results( $sql );

    }

    return $order_items;

}

/**
 * Get the IDs of the group of which a specified event is attached.
 *
 * @param $event_id
 * @return bool|mixed|void
 */
function bpec_get_event_group_id( $event_id ) {
    global $wpdb, $bp;

    $query      = $wpdb->prepare("SELECT group_id FROM {$bp->groups->table_name} WHERE meta_key = %s AND meta_value = %s", 'group_event_id', $event_id );
    $group_id   = $wpdb->get_var( $query );

    if ( empty( $group_id ) )
        return false;

    return apply_filters( 'bpec_get_event_group_id', $group_id );
}

/**
 * Get the placeholder image URL for events etc.
 *
 * @access public
 * @return string
 */
function bpec_placeholder_img_src() {
    return apply_filters( 'bpec_placeholder_img_src', BP_EVENTS_CALENDAR_PLUGIN_URL . '/assets/images/placeholder.png' );
}

/**
 * Get group event
 *
 * @param $group_id
 * @return mixed|void
 */
function bpec_get_group_events( $group_id ) {
    global $wpdb, $bp;

    // See if any of the deleted activity IDs were being followed
    $sql  = 'SELECT event_id FROM ' . esc_sql( buddypress()->bpec->table_name ) . ' ';
    $sql .= 'WHERE group_id = ' . $group_id ;

    $event_ids = $GLOBALS['wpdb']->get_col( $sql );

    return apply_filters( 'bpec_get_group_events', $event_ids );
}

/**
 * @param $event_id
 * @return mixed|void
 */
function bpec_delete_group_event($event_id ) {
    global $bp;

    // SQL statement
    $sql  = "DELETE FROM {$bp->bpec->table_name} ";
    $sql .= "WHERE event_id = ".$event_id;

    $result = $GLOBALS['wpdb']->query( $sql );

    return apply_filters( 'bpec_delete_group_event', $result );
}

/**
 * Save group event
 *
 * @param $event_id
 * @param $group_id
 * @return mixed|void
 */
function bpec_save_group_event( $event_id, $group_id ) {
    global $bp;

    $result = $GLOBALS['wpdb']->query( $GLOBALS['wpdb']->prepare(
        "INSERT INTO {$bp->bpec->table_name} ( group_id, event_id, date_mapped ) VALUES ( %d, %d, %s )",
        $group_id,
        $event_id,
        bp_core_current_time()
    ) );

    return apply_filters( 'bpec_save_group_event', $result );
}

/**
 * Get event id by group id
 *
 * @param $event_id
 * @param $group_id
 * @return null|string
 */
function bpec_get_event_by_group( $event_id, $group_id ) {
    global $wpdb, $bp;

    //SELECT SQL statement
    $sql =  "SELECT id FROM {$bp->bpec->table_name}";
    $sql .= "WHERE group_id = %d AND event_id = %d";

    $id = $wpdb->get_var( $wpdb->prepare($sql, $group_id, $event_id ) );

    return apply_filters( 'bpec_save_group_event', $id );
}

/**
 * Return the members event joining status
 *
 * @param array $args {
 *     Array of arguments.
 *     @type int    $event_id
 *     @type string $user_id
 * }
 * @return bool
 */
function bpec_get_members_event_status( $args = '' ) {

    $r = wp_parse_args( $args, array(
        'user_id'   => bp_loggedin_user_id()
    ));

    $event_member = new BPEC_Events_Members( $r['group_id'], $r['event_id'], $r['user_id'] );

    return $event_member->status;
}

/**
 *  AJAX callback when clicking on the event join button
 *
 */
function bpec_join_group_event() {

    check_admin_referer( 'event_join' );

    $event_id   = (int) $_POST['eid'];
    $group_id   = (int) $_POST['gid'];
    $status     = $_POST['eaction'];

    // successful joining
    if ( bpec_join_event( array( 'group_id' => $group_id, 'event_id' => $event_id, 'status' => $status ) ) ) {
        die('done');
    }
}

/**
 * Get the members joining an event
 *
 * @param $sql
 * @param BP_User_Query $query
 * @return mixed
 */
function bpec_bp_user_query_uid_clauses( $sql, BP_User_Query $query ) {
    global $bp;

    if ( ! defined('DOING_AJAX') )
        return $sql;

    if ( ! isset($_GET['action'] ) || 'bpec_event_guests_list' !== $_GET['action'] )
        return $sql;

    $event_id = $_GET['eid'];
    $status   = isset( $_GET['status'] ) ? $_GET['status'] : 'going';

    $sql['select']   .= " INNER JOIN {$bp->bpec->table_name_members} em ON em.user_id = u.ID ";
    $sql['where'][]  = "em.event_id = {$event_id}";
    $sql['where'][]  = "em.status = '{$status}'";

    return $sql;
}

/**
 * Create a new post update in group when user join event
 *
 * @param $args
 */
function bpec_event_join_record_activity( $args ) {

    // Now write the values.
    groups_record_activity( array(
        'user_id'           => $args['user_id'],
        'primary_link'      => get_permalink( $args['group_id'] ),
        'item_id'           => $args['group_id'],
        'secondary_item_id' => $args['event_id'],
        'type'              => 'joined_event',
    ) );
}

/**
 * Format 'joined_event' activity actions.
 *
 * @param string $action   Static activity action.
 * @param object $activity Activity data object.
 * @return string
 */
function bpec_format_activity_action_joined_event( $action, $activity ) {
    $user_link  = bp_core_get_userlink( $activity->user_id );
    $event      = get_post( $activity->secondary_item_id );

    $status = bpec_get_members_event_status( array( 'user_id' => $activity->user_id, 'group_id' => $activity->item_id, 'event_id' => $activity->secondary_item_id ) );

    $event_link = '<a href="' . esc_url( get_permalink( $event->ID ) ) . '">' . esc_html( $event->post_title ) . '</a>';

    if ( 'interested' == $status ) {
        $action = sprintf( __( '%1$s interested in join the event, %2$s', 'bp-events-calendar' ), $user_link, $event_link );
    } elseif ( 'going' == $status ) {
        $action = sprintf( __( '%1$s going to the event, %2$s', 'bp-events-calendar' ), $user_link, $event_link );
    }

    /**
     * Filters the 'joined_event' activity actions.
     *
     * @param string $action   The 'joined_group' activity actions.
     * @param object $activity Activity data object.
     */
    return apply_filters( 'bpec_format_activity_action_joined_event', $action, $activity );
}

/**
 * Deletes join event activity items when user left an event.
 *
 */
function bpec_delete_activity_on_event_leave( $args ) {

    if ( ! bp_is_active( 'activity' ) ) {
        return;
    }

    bp_activity_delete( array(
        'type'              => 'joined_event',
        'user_id'           => $args['user_id'],
        'item_id'           => $args['group_id'],
        'secondary_item_id' => $args['event_id']
    ) );
}
