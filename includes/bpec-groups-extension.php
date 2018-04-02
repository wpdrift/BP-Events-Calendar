<?php
/**
 * The bp_is_active( 'groups' ) check is recommended, to prevent problems
 * during upgrade or when the Groups component is disabled
 */
if ( bp_is_active( 'groups' ) ) :

    class Group_Extension_Events extends BP_Group_Extension {

        /**
         * Here you can see more customization of the config options
         */
        function __construct() {

            $args = apply_filters( 'bpec_group_extension_config', array(
                'slug' => 'events',
                'name' => __( 'Events', 'buddypress-for-events-calendar' ),
                'nav_item_position' => 10,
            ) );

            parent::init( $args );
        }

        /**
         * Single event content
         * @param null $group_id
         */
        function display( $group_id = NULL ) {
            global $post;

            $group_id   = bp_get_group_id();
            $event_ids  = bpec_get_group_events( $group_id );
            ?>
            <ul id="events-list" class="item-list" aria-live="assertive" aria-atomic="true" aria-relevant="all">
            <?php foreach ( $event_ids as $event_id ): ?>
                <?php
                global $post;
                $post = get_post( $event_id );
                setup_postdata( $post );
                ?>
                <li>

                    <div class="item-avatar">
                        <a href="">
                            <?php if ( has_post_thumbnail() ): ?>
                            <?php the_post_thumbnail( array( 120, 120 ) ); ?>
                            <?php else: ?>
                            <?php echo '<img src="' . bpec_placeholder_img_src() . '" alt="Placeholder" width="120" class="bpec-placeholder wp-post-image" height="120" />'; ?>
                            <?php endif; ?>
                        </a>
                    </div>

                    <div class="item">
                        <div class="item-title">
                            <a href="<?php the_permalink() ?>"><?php echo the_title() ?></a>
                        </div>

                        <div class="item-meta"><span class="activity" data-livestamp="">
                                <span><?php echo tribe_get_start_date( $event_id, true); ?></span>
                        </div>
                        <div class="item-desc"><?php the_excerpt() ?></div>
                        <?php

                        /**
                         * Fires inside the listing of an individual event listing item.
                         *
                         * @since 1.1
                         */
                        do_action( 'bpec_directory_event_item' ); ?>

                    </div>

                    <div class="action">

                        <?php

                        /**
                         * Fires inside the action section of an individual event listing item.
                         *
                         * @since 1.1
                         */
                        do_action( 'bpec_directory_event_actions' ); ?>

                        <div class="meta">

                        </div>

                    </div>

                    <div class="clear"></div>
                </li>
            <?php endforeach; ?>
            </ul><!-- #events-list -->

            <!-- Guests popup modal -->
            <div id="guests-popup" class="white-popup mfp-hide">
               <?php do_action( 'bpec_guests_popup_inner' ) ?>
            </div><!-- #guests-popup -->

            <!-- Template: Guests popup placeholder content -->
            <script id="guests-popup-inner" type="text/template">
                <?php do_action( 'bpec_guests_popup_inner' ) ?>
            </script>

            <?php
        }
    }

    bp_register_group_extension( 'Group_Extension_Events' );

endif;
