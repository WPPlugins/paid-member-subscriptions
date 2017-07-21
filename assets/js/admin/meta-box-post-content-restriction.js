jQuery( function(){
    /* Display custom redirect URL section if type of restriction is "Redirect" */
    jQuery( 'input[type=radio][name=pms-content-restrict-type]' ).click( function() {
        if( jQuery(this).is(':checked') && jQuery(this).val() == 'redirect' )
            jQuery('#pms-meta-box-fields-wrapper-restriction-redirect-url').addClass('pms-enabled');
        else
            jQuery('#pms-meta-box-fields-wrapper-restriction-redirect-url').removeClass('pms-enabled');
    });

    /* Display custom redirect URL field */
    jQuery( '#pms-content-restrict-custom-redirect-url-enabled' ).click( function() {
        if( jQuery(this).is(':checked') )
            jQuery('.pms-meta-box-field-wrapper-custom-redirect-url').addClass('pms-enabled');
        else
            jQuery('.pms-meta-box-field-wrapper-custom-redirect-url').removeClass('pms-enabled');
    });

    /* Display custom messages editors */
    jQuery( '#pms-content-restrict-messages-enabled' ).click( function() {
    	if( jQuery(this).is(':checked') )
    		jQuery('.pms-meta-box-field-wrapper-custom-messages').addClass('pms-enabled');
    	else
    		jQuery('.pms-meta-box-field-wrapper-custom-messages').removeClass('pms-enabled');
    });


    /**
     * Disable / enable the subscription plans from the "Display for" field if the 
     * "Logged in Users" option is checked or not
     *
     */
    jQuery(document).on( 'ready click', 'input[name="pms-content-restrict-user-status"]', function() {
        disable_enable_subscription_plans( jQuery(this) );
    });

    jQuery('input[name="pms-content-restrict-user-status"]').each( function() {
        disable_enable_subscription_plans( jQuery(this) ); 
    });


    function disable_enable_subscription_plans( $element ) {

        $wrapper = $element.closest('.pms-meta-box-field-wrapper');

        if( $element.is(':checked') )
            $wrapper.find('input[name="pms-content-restrict-subscription-plan[]"]').attr('disabled', false );
        else
            $wrapper.find('input[name="pms-content-restrict-subscription-plan[]"]').attr('disabled', true );

    }

});