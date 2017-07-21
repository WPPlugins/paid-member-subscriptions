<?php
/*
 * Functions that extend PMS to be compatible with PB's e-mail confirmation feature
 *
 */


/*
 * Add the subscription plan into the meta-data array for saving into the signups table
 *
 */
function pms_pb_save_subscription_plan_meta( $meta, $global_request ) {

    if( !empty( $global_request['subscription_plans'] ) )
        $meta['subscription_plans'] = $global_request['subscription_plans'];

    return $meta;

}
add_filter( 'wppb_add_to_user_signup_form_meta', 'pms_pb_save_subscription_plan_meta', 10, 2 );


/*
 * Appends the subscription plans and payment options for the user to select
 * to PB's e-mail confirmation default message
 *
 * @param string $message
 *
 * @return string
 *
 */
function pms_pb_email_confirmation_payment_form( $message ) {

    if( empty( $_GET['activation_key'] ) )
        return $message;

    // Get cached user meta-data
    $signup_data = wppb_get_signup_data( sanitize_text_field( $_GET['activation_key'] ) );

    if( is_null( $signup_data ) )
        return $message;

    if( empty( $signup_data->meta['subscription_plans'] ) )
        return $message;

    // Setup subscription plan
    $subscription_plan_id = (int)$signup_data->meta['subscription_plans'];

    // If the member is already subscribed to the subscription plan don't show the form
    $member = pms_get_member( email_exists( $signup_data->user_email ) );

    if( $member->get_subscription( $subscription_plan_id ) )
        return $message;


    // Form
    $output = '<form id="pms-register-form" action="" method="POST" class="pms-form">';

        $output .= pms_output_subscription_plans( array( $subscription_plan_id ) );

        // Start output buffering
        ob_start();

        wp_nonce_field( 'pms_register_form_email_confirmation_nonce', 'pmstkn' );

        do_action( 'pms_register_form_bottom' );

        // Get contents and stop output buffering
        $output .= ob_get_contents();
        ob_end_clean();

        // Submit button
        $output .= '<input name="pms_register" type="submit" value="' . apply_filters( 'pms_register_form_email_confirmation_submit_text', __( 'Subscribe', 'paid-member-subscriptions' ) ) . '" />';

    $output .= '</form>';


    // Empty $message parameter if we're on the error message filter
    if( 'wppb_register_activate_user_error_message2' == current_filter() )
        $message = '';

    // Return
    return $message . $output;

}
add_filter( 'wppb_success_email_confirmation', 'pms_pb_email_confirmation_payment_form', 20, 1 );
add_filter( 'wppb_register_activate_user_error_message2', 'pms_pb_email_confirmation_payment_form', 20, 1 );


/*
 * If the user is subscribing to a plan, don't add the redirect at this point, as he/she needs to complete
 * the payment forms. We cache it instead and use it when needed
 *
 * @param string $redirect_url
 *
 * @return string
 *
 */
function pms_pb_remove_email_confirmation_redirect( $redirect_url ) {

    if( empty( $_GET['activation_key'] ) )
        return $redirect_url;

    $key = sanitize_text_field( $_GET['activation_key'] );

    // Get user signup data
    $signup = wppb_get_signup_data( $key );

    if( !is_null( $signup ) && !empty( $signup->meta['subscription_plans'] ) ) {

        // Cache the url for further use
        if( false === get_transient( 'wppb_email_confirmation_success_redirect_url_' . $key ) )
            set_transient( 'wppb_email_confirmation_success_redirect_url_' . $key, $redirect_url, 60 * 60 );

        // Remove the url
        $redirect_url = '';

    }

    return $redirect_url;

}
add_filter( 'wppb_success_email_confirmation_redirect_url', 'pms_pb_remove_email_confirmation_redirect' );


/*
 * Add a new form location for the subscription form of e-mail confirmation. This new form location is used
 * to change the redirect url from the form_handler
 *
 * @param string $location
 *
 * @return string
 *
 */
function pms_pb_email_confirmation_form_location( $location = '' ) {

    if( !empty( $_POST['pmstkn'] ) && wp_verify_nonce( $_POST['pmstkn'], 'pms_register_form_email_confirmation_nonce' ) )
        return $location = 'register_email_confirmation';

    return $location;

}
add_filter( 'pms_request_form_location', 'pms_pb_email_confirmation_form_location' );


/*
 * Checks to see if there's a cached redirect_url for the e-mail confirmation register form.
 * If it exists, it returns the cached value instead and destroys the cache
 *
 * @param string $url
 * @param string $location - the location of the form
 *
 * @return string
 *
 */
function pms_pb_email_confirmation_redirect_url( $url = '', $location = '' ) {

    if( empty( $_GET['activation_key'] ) )
        return $url;

    if( $location == 'register_email_confirmation' ) {

        $key = sanitize_text_field( $_GET['activation_key'] );

        // Get cached redirect url
        $cached_url = get_transient( 'wppb_email_confirmation_success_redirect_url_' . $key );

        // If we have a cached redirect from PB's custom redirects
        if( $cached_url ) {
            // Remove cached url
            delete_transient( 'wppb_email_confirmation_success_redirect_url_' . $key );

            // Set new redirect url
            $url = $cached_url;

        // If not, go with PMS's redirect
        } else {

            $pms_settings = get_option('pms_settings');
            $url = ( isset( $pms_settings['general']['register_success_page'] ) && $pms_settings['general']['register_success_page'] != -1 ? get_permalink( trim( $pms_settings['general']['register_success_page'] ) ) : '' );

            // Add success message
            if( empty($url) ) {
                $url = pms_get_current_page_url( true );
            }

        }

    }

    return $url;

}
add_filter( 'pms_get_redirect_url', 'pms_pb_email_confirmation_redirect_url', 10, 2 );


/*
 * Registers the member data and payment data after the user subscribes to the subscription plan on the
 * e-mail confirmation page
 *
 * @return void
 *
 */
function pms_pb_email_confirmation_handle_form_submission() {

    if( empty( $_POST['pmstkn'] ) || !wp_verify_nonce( $_POST['pmstkn'], 'pms_register_form_email_confirmation_nonce' ) )
        return;

    if( empty( $_GET['activation_key'] ) )
        return;

    $user_id = wppb_get_user_id_by_activation_key( sanitize_text_field( $_GET['activation_key'] ) );

    if( false === $user_id )
        return;

    // Prepare user data
    $user_data = PMS_Form_Handler::get_request_member_data( $user_id );

    // Add subscriptions to the user
    $registered = PMS_Form_Handler::register_member_data( $user_data );

    if( $registered ) {
        if( PMS_Form_Handler::register_payment($user_data) ) {
            wp_redirect( PMS_Form_Handler::get_redirect_url() );
            exit;
        }
    }

}
add_action( 'init', 'pms_pb_email_confirmation_handle_form_submission', 10 );


/*
 * Returns the signup data from WordPress's signups table given an activation key
 *
 * @param string $key
 *
 * @return mixed - object|null
 *
 */
if( !function_exists( 'wppb_get_signup_data' ) ) {

    function wppb_get_signup_data( $key = '' ) {

        if( empty( $key ) )
            return NULL;

        global $wpdb;

        $key = sanitize_text_field( $key );

        $signup = ( is_multisite() ? $wpdb->get_row( $wpdb->prepare("SELECT * FROM $wpdb->signups WHERE activation_key = %s", $key) ) : $wpdb->get_row( $wpdb->prepare( "SELECT * FROM ".$wpdb->base_prefix."signups WHERE activation_key = %s", $key ) ) );

        // If meta-data exists and is serialized, unserialize it
        if( !is_null( $signup ) && !empty( $signup->meta ) && is_serialized( $signup->meta ) )
            $signup->meta = unserialize( $signup->meta );

        return $signup;

    }

}


/*
 * Returns the user_id given an activation key
 *
 * @param string $key - the activation key used on e-mail confirmation
 *
 * @return mixed - int|false
 *
 */
if( !function_exists( 'wppb_get_user_id_by_activation_key' ) ) {

    function wppb_get_user_id_by_activation_key( $key = '' ) {

        if( empty( $key ) )
            return false;

        $signup = wppb_get_signup_data( $key );

        if( is_null( $signup ) || $signup->active != 1 )
            $user_id = false;

        else
            $user_id = username_exists( $signup->user_login );

        return $user_id;
    }

}