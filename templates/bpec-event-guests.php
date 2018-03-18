<?php
/**
 * BuddyPress - Members Loop
 *
 * Querystring is set via AJAX in _inc/ajax.php - bp_legacy_theme_object_filter()
 *
 * @package BuddyPress
 * @subpackage bp-legacy
 */

?>

<?php if ( bp_has_members( bp_ajax_querystring( 'members' ) . '&type=alphabetical&per_page=9999999999999' ) ) : ?>


    <ul id="members-list" class="item-list" aria-live="assertive" aria-relevant="all">

        <?php while ( bp_members() ) : bp_the_member(); ?>

            <li <?php bp_member_class(); ?>>
                <div class="item-avatar">
                    <a href="<?php bp_member_permalink(); ?>"><?php bp_member_avatar(); ?></a>
                </div>

                <div class="item">

                    <div class="item-title">
                        <a href="<?php bp_member_permalink(); ?>"><?php bp_member_name(); ?></a>
                    </div>

                </div>

                <div class="clear"></div>
            </li>

        <?php endwhile; ?>

    </ul>


<?php else: ?>

    <div id="message" class="info">
        <p><?php _e( "Sorry, no members were found.", 'buddypress' ); ?></p>
    </div>

<?php endif; ?>
