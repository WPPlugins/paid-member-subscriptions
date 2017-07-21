jQuery( function($) {

    if( window.history.replaceState ) {

        currentURL = window.location.href;

        currentURL = pms_remove_query_arg( 'pmsscscd', currentURL );
        currentURL = pms_remove_query_arg( 'pmsscsmsg', currentURL );
        currentURL = pms_remove_query_arg( 'pms_gateway_payment_action', currentURL );
        currentURL = pms_remove_query_arg( 'pms_gateway_payment_id', currentURL );

        window.history.replaceState( null, null, currentURL );
    }


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
     * Hide "automatically renew subscription" checkbox for manual payment gateway
     *
     */
    jQuery(document).ready( function() {

        var subscription_plan_selector = 'input[name=subscription_plans]';
        var paygate_selector           = 'input[name=pay_gate]';

        // Field wrappers
        var $auto_renew_field = jQuery( '.pms-subscription-plan-auto-renew' );

        // Checked Subscription
        var $checked_subscription = jQuery( subscription_plan_selector + '[type=radio]' ).length > 0 ? jQuery( subscription_plan_selector + '[type=radio]:checked' ) : jQuery( subscription_plan_selector + '[type=hidden]' );
        var $checked_paygate      = jQuery( paygate_selector + '[type=radio]' ).length > 0 ? jQuery( paygate_selector + '[type=radio]:checked' ) : jQuery( paygate_selector + '[type=hidden]' );


        if( $( paygate_selector ).val() == 'manual' )
            $auto_renew_field.hide();

        /**
         * Handle auto-renew checkbox when clicking on a payment gateway
         *
         */
        $( paygate_selector + '[type=radio]' ).click( function() {

            if( jQuery(this).is(':checked') )
                $checked_paygate = jQuery(this);

            if( $checked_paygate.val() == 'manual' )
                $auto_renew_field.hide();

            else {

                if ( $checked_subscription.data('duration') != 0 && $checked_subscription.data('price') != 0 ) {
                    $auto_renew_field.show();
                }


            }
        });


        if ( $checked_subscription.data('duration') == 0 ) {
            $auto_renew_field.hide();
        }

        /**
         * Handle auto-renew checkbox when clicking on a subscription plan
         *
         */
        jQuery( subscription_plan_selector + '[type=radio]' ).click(function(){

            if( jQuery(this).is(':checked') )
                $checked_subscription = jQuery(this);

            if ( $checked_subscription.data('duration') == 0 || $checked_subscription.data('price') == 0 ) {
                $auto_renew_field.hide();
            }

            else {
                if( $checked_paygate.val() != 'manual' )
                    $auto_renew_field.show();
            }
        });

    });



    /*
     * Add field error for a given element name
     *
     */
    $.pms_add_field_error = function( error, field_name ) {

        if( error == '' || error == 'undefined' || field_name == '' || field_name == 'undefined' )
            return false;

        $field          = $('[name=' + field_name + ']');
        $field_wrapper  = $field.closest('.pms-field');

        error = '<p>' + error + '</p>';

        if( $field_wrapper.find('.pms_field-errors-wrapper').length > 0 )
            $field_wrapper.find('.pms_field-errors-wrapper').html( error );
        else
            $field_wrapper.append('<div class="pms_field-errors-wrapper pms-is-js">' + error + '</div>');

    };

    /*
     * Clear all field errors added with js
     *
     */
    $.pms_clean_field_errors = function() {

        $('.pms_field-errors-wrapper.pms-is-js').remove();

    };

});


/*
 * Profile Builder Compatibility
 *
 */
jQuery( function($) {

    $(document).ready( function() {

        // Handle on document ready
        if ( $('.pms-subscription-plan input[type=radio][data-price="0"]').is(':checked') || $('.pms-subscription-plan input[type=hidden]').attr( 'data-price' ) == '0' ) {
            $('.pms-email-confirmation-payment-message').hide();
        }

        if( $('.pms-subscription-plan input[type=radio]').length > 0 ) {

            var has_paid_subscription = false;

            $('.pms-subscription-plan input[type=radio]').each( function() {
                if( $(this).data('price') != 0 )
                    has_paid_subscription = true;
            });

            if( !has_paid_subscription )
                $('.pms-email-confirmation-payment-message').hide();

        }

        // Handle clicking on the subscription plans
        $('.pms-subscription-plan input[type=radio]').click(function(){

            if ($('.pms-subscription-plan input[type=radio][data-price="0"]').is(':checked')) {
                $('.pms-email-confirmation-payment-message').hide();
            }
            else {
                $('.pms-email-confirmation-payment-message').show();
            }
        });

    });

});