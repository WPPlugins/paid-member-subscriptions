<?php

    /*
     * Function that adds the HTML for PayPal Standard in the payments tab from the Settings page
     *
     * @param array $options    - The saved option settings
     *
     */
    function pms_add_settings_content_paypal_standard( $options ) {

        echo '<div class="pms-payment-gateway-wrapper">';

            echo '<h4 class="pms-payment-gateway-title">' . apply_filters( 'pms_settings_page_payment_gateway_paypal_title', __( 'Paypal Standard', 'paid-member-subscriptions' ) ) . '</h4>';

            echo '<div class="pms-form-field-wrapper">';

                echo '<label class="pms-form-field-label" for="paypal-standard-email">' . __( 'PayPal E-mail Address', 'paid-member-subscriptions' ) . '</label>';
                echo '<input id="paypal-standard-email" type="text" name="pms_settings[payments][gateways][paypal_standard][email_address]" value="' . ( isset($options['payments']['gateways']['paypal_standard']['email_address']) ? $options['payments']['gateways']['paypal_standard']['email_address'] : '' ) . '" class="widefat" />';

                echo '<input type="hidden" name="pms_settings[payments][gateways][paypal_standard][name]" value="PayPal" />';

                echo '<p class="description">' . __( 'Enter your PayPal e-mail address', 'paid-member-subscriptions' ) . '</p>';

            echo '</div>';

            do_action( 'pms_settings_page_payment_gateway_paypal_extra_fields', $options );

        echo '</div>';


    }
    add_action( 'pms-settings-page_payment_gateways_content', 'pms_add_settings_content_paypal_standard' );