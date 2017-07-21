jQuery( function($){

    /**
     * Disable / enable the subscription plans from the "Who can purchase?" field depending if the
     * "Logged in Users" option is checked or not
     *
     */
    $('#pms_post_content_restriction').on( 'click', 'input[name="pms-purchase-restrict-user-status"]', function() {
        disable_enable_subscription_plans( $(this) );
    });

    $('input[name="pms-purchase-restrict-user-status"]').each( function() {
        disable_enable_subscription_plans( $(this) );
    });


    function disable_enable_subscription_plans( $element ) {

        $wrapper = $element.closest('.pms-meta-box-field-wrapper');

        if( $element.is(':checked') )
            $wrapper.find('input[name="pms-purchase-restrict-subscription-plan[]"]').attr('disabled', false );
        else
            $wrapper.find('input[name="pms-purchase-restrict-subscription-plan[]"]').attr('disabled', true );

    }

});
