/*
 * JavaScript for Settings Submenu Page
 *
 */
jQuery( function($) {


    /*
     * Strips one query argument from a given URL string
     *
     */
    function pms_remove_query_arg( key, sourceURL ) {

        var rtn = sourceURL.split("?")[0],
            param,
            params_arr = [],
            queryString = (sourceURL.indexOf("?") !== -1) ? sourceURL.split("?")[1] : "";

        if (queryString !== "") {
            params_arr = queryString.split("&");
            for (var i = params_arr.length - 1; i >= 0; i -= 1) {
                param = params_arr[i].split("=")[0];
                if (param === key) {
                    params_arr.splice(i, 1);
                }
            }

            rtn = rtn + "?" + params_arr.join("&");

        }

        if(rtn.split("?")[1] == "") {
            rtn = rtn.split("?")[0];
        }

        return rtn;
    }


    /*
     * Adds a argument name, value pair to a given URL string
     *
     */
    function pms_add_query_arg( key, value, sourceURL ) {

        return sourceURL + '&' + key + '=' + value;

    }


    /*
     * Change settings tabs when clicking on navigation tabs
     */
    $(document).ready( function() {

        $('.nav-tab').click( function(e) {
            e.preventDefault();

            $navTab = $(this);
            $navTab.blur();

            $('.nav-tab').removeClass('nav-tab-active');
            $navTab.addClass('nav-tab-active');

            // Update the http referer with the current tab info
            $_wp_http_referer = $('input[name=_wp_http_referer]');

            var _wp_http_referer = $_wp_http_referer.val();
            _wp_http_referer = pms_remove_query_arg( 'nav_tab', _wp_http_referer );
            _wp_http_referer = pms_add_query_arg( 'message', 1, _wp_http_referer );
            $_wp_http_referer.val( pms_add_query_arg( 'nav_tab', $navTab.data('tab-slug'), _wp_http_referer ) );


            $('.pms-tab').removeClass('tab-active');
            $( '#pms-settings-' + $navTab.data('tab-slug') ).addClass('tab-active');

            if( $navTab.data('tab-slug') == 'emails' ){
                var stickyTop = $('#pms-available-tags').offset().top; // returns number
                $(window).scroll(function(){ // scroll event

                    var windowTop = $(window).scrollTop(); // returns number

                    if (stickyTop < windowTop) {
                        $('#pms-available-tags').addClass('scroll');
                    }
                    else {
                        $('#pms-available-tags').removeClass('scroll');
                    }

                });
            }
        });

        $('.pms-tag').click( function(){ this.select(); });

    });


    /*
     * Handle default payment gateways select options
     *
     */
    $activePaymetGateways = $('.pms-form-field-active-payment-gateways input[type=checkbox]');

    if( $activePaymetGateways.length > 0 ) {

        $(document).ready( function() {
            activateDefaultPaymentGatewayOptions();
        });

        $activePaymetGateways.click( function() {
            activateDefaultPaymentGatewayOptions();
        });

        /*
         * Activates the correct default payment gateway options in the select field
         * based on the active payment gateways
         *
         */
        function activateDefaultPaymentGatewayOptions() {
            var activeGateways = [];

            setTimeout( function() {

                $('.pms-form-field-active-payment-gateways input[type=checkbox]:checked').each( function() {
                    activeGateways.push( $(this).val() );
                });

                $('#default-payment-gateway').find('option').each( function() {
                    if( activeGateways.indexOf( $(this).val() ) == -1 )
                        $(this).attr('disabled', true);
                    else
                        $(this).attr('disabled', false);
                });

            }, 200 );
        }
    }


    /*
     * Position the Available tags div from the e-mail settings tab
     *
     */
    function positionAvailableTags() {
        $availableTags   = $('#pms-available-tags');
        $emailsTabs      = $('#pms-settings-emails');
        $formTabsWrapper = $emailsTabs.closest('form');

        $availableTags.css( 'top', $formTabsWrapper.offset().top + 60 );
        $availableTags.css( 'left', $emailsTabs.closest('.wrap').offset().left + $formTabsWrapper.width() - 280 );
    }

    $(document).ready( function() {
        positionAvailableTags();
        $availableTags.css( 'opacity', 1 );
    });

    $(window).on( 'resize', function() {
        positionAvailableTags();
    });

    $(window).on( 'scroll', function() {
        $formTabsWrapper = $('#pms-settings-emails').closest('form');

        if( $(window).scrollTop() < $formTabsWrapper.offset().top ) {
            $('#pms-available-tags').css( 'top', $formTabsWrapper.offset().top + 60 - $(window).scrollTop() );
        } else {
            $('#pms-available-tags').css( 'top', '60px' );
        }
    });

});