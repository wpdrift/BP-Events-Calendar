/* global */

if ( typeof jq == "undefined" ) {
    var jq = jQuery;
}

jq( function() {

    if ( jq().select2 && jq('#event-group').length ) {
        jq('#event-group').select2();
    }

    /**
     * GroupEventsHandler class.
     */
    var GroupEventsHandler = function() {

        this.$buttons               = jq( '.bpec-join-event-button' );
        this.$popupLink             = jq( '.bpec-guests-popup-btn');
        this.$tabs                  = jq('.tabs-menu a');
        this.$tabContent            = jq('.tab-content');

        // Methods
        this.initDropElement();
        this.initGuestPopup();
        this.initTabs();

        // Events
        jq( document ).on( 'click', '.bpec-join-drop-element li', this.onJoinEvent );
        this.$popupLink.on('click', this.getEventGuests );
    };

    /**
     * Handle the group event join.
     */
    GroupEventsHandler.prototype.onJoinEvent = function( e ) {
        var $thisli     = jq( this ),
            $ul         = $thisli.parent(),
            $last_li    = $ul.find('li:last-of-type'),
            $joinbutton = jq('.bpec-join-event-button.drop-enabled');

        e.preventDefault();
        e.stopPropagation();

        var data = {};

        jq.each( $thisli.data(), function( key, value ) {
            data[ key ] = value;
        });

        jq.each( $ul.parent().data(), function( key, value ) {
            data[ key ] = value;
        });

        // WP action
        data['action'] = 'bpec_join_group_event';

        // Disable join button
        $joinbutton[0].disabled = true;

        jq('body').trigger('click');

        // Ajax action.
        jq.post( ajaxurl, data, function( response ) {
           if ( response == 'done' ) {

               $ul.find('svg').addClass('hide');

               switch( data.eaction ) {
                   case 'going':
                       $thisli.find('svg').removeClass('hide');
                       $last_li.prev().removeClass('hide');
                       $last_li.removeClass('hide').find('.drop-element-item-text').text(bpec_global_vars.not_going);
                       $joinbutton.text(bpec_global_vars.going);
                       break;
                   case 'interested':
                       $thisli.find('svg').removeClass('hide');
                       $last_li.prev().removeClass('hide');
                       $last_li.removeClass('hide').find('.drop-element-item-text').text(bpec_global_vars.not_interested);
                       $joinbutton.text(bpec_global_vars.interested);
                       break;
                   case 'delete':
                       $joinbutton.text(bpec_global_vars.join);
                       $last_li.addClass('hide');
                       break;
               }
           }

            // Enable join button
            $joinbutton[0].disabled = false;
        });
    };

    /**
     * Init Drops
     */
    GroupEventsHandler.prototype.initDropElement = function() {

        if ( this.$buttons.length > 0 ) {
            this.$buttons.each( function( index ) {
                new Drop({
                    target: jq(this)[0],
                    content: jq(this).next()[0],
                    position: 'bottom right',
                    openOn: 'click',
                    classes: 'drop-example-theme-social-sharing'
                } );
            });
        }
    };

    /**
     * Init Magnific Popup
     */
    GroupEventsHandler.prototype.initGuestPopup = function() {
        if ( this.$popupLink.length > 0 ) {
            this.$popupLink.magnificPopup({
                type:'inline',
                midClick: true,
                callbacks: {
                    afterClose: function () {
                        var $guestsPopupInner  = jq('#guests-popup-inner');
                        var guestInitHTML = $guestsPopupInner[0].innerHTML;
                        jq('#guests-popup').html( guestInitHTML );
                        jq('#guests-popup')[0].removeAttribute('data-eid');
                    }
                }
            });
        }
    };

    /**
     * Populate event guests list element
     */
    GroupEventsHandler.prototype.getEventGuests = function (e) {
        var $thisa     = jq( this );
        var testThing = this;

        e.preventDefault();
        e.stopPropagation();

        var data = {};

        jq.each( $thisa.data(), function( key, value ) {
            if ( typeof value !== 'object' ) {
                data[ key ] = value;
            }
        });

        jq('#guests-popup')[0].setAttribute('data-eid', data['eid'] );

        // Default status going
        data['status'] = 'going';

        getEventGuestsAjax(data);
    };

    /**
     * Switch between tabs
     * @param e
     */
    GroupEventsHandler.prototype.initTabs = function (e) {
        if (this.$tabs.length > 0 ) {
            jq(document.body).on( 'click', '.tabs-menu a', function(event) {
                this.$tabContent    = jq('.tab-content');
                var data            = {},
                    $thistab        = jq(this),
                    ahref           = $thistab[0].getAttribute('href'),
                    status          = ahref.substr(ahref.indexOf('-') + 1),
                    $guestsPopup    = jq('#guests-popup');

                event.preventDefault();

                $thistab.parent().addClass("current");
                $thistab.parent().siblings().removeClass("current");
                var tab = $thistab.attr("href");
                this.$tabContent.not(tab).css("display", "none");

                data['eid']     = $guestsPopup.attr('data-eid');
                data['status']  = status;

                jq(tab).fadeIn({
                    done: function () {
                        getEventGuestsAjax(data);
                    }
                });
            });
        }
    };

    /**
     * Generate ajax request to populate guests list fragment
     *
     * @param data
     */
    function getEventGuestsAjax(data) {

        if ( jq('#guests-popup #tab-'+ data['status'] +' #buddypress').length == 0 ) {

            // WP action
            data['action'] = 'bpec_event_guests_list';

            jq.ajax( {
                type:     'GET',
                url:      ajaxurl,
                data:     data,
                dataType: 'html',
                success: function( response ) {
                    jq('#guests-popup #tab-'+ data['status']).html(response);
                }
            } );
        }
    }

    /**
     * Init GroupEventsHandler :)
     */
    new GroupEventsHandler();
});