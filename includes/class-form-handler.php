<?php
/*
 * This class handles all the form submissions in the front-end part of the website
 *
 */

Class PMS_Form_Handler {

    /*
     * Hook data processing methods on init
     *
     */
    public static function init() {

        add_action( 'init', array( __CLASS__, 'register_form' ) );
        add_action( 'init', array( __CLASS__, 'new_subscription_form') );
        add_action( 'init', array( __CLASS__, 'upgrade_subscription' ) );
        add_action( 'init', array( __CLASS__, 'renew_subscription' ) );
        add_action( 'init', array( __CLASS__, 'cancel_subscription') );
        add_action( 'init', array( __CLASS__, 'retry_payment_subscription' ) );
        add_action( 'init', array( __CLASS__, 'recover_password_form') );
        add_action( 'init', array( __CLASS__, 'edit_profile' ) );

        add_action( 'pms_register_payment_free_subscription_plan', array( __CLASS__, 'handle_free_subscriptions' ) );
        add_action( 'pms_register_payment_amount_zero', array( __CLASS__, 'handle_free_subscriptions' ) );

        add_filter( 'login_redirect', array( __CLASS__, 'login_form' ), 10 ,3 );

    }


    /*
     * Handles data sent from the register form
     *
     */
    public static function register_form() {

        // Check nonce
        if (!isset($_POST['pmstkn']) || !wp_verify_nonce($_POST['pmstkn'], 'pms_register_form_nonce'))
            return;

        /*
         * Username
         */
        if (!isset($_POST['user_login']))
            pms_errors()->add('user_login', __('Please enter a username.', 'paid-member-subscriptions'));

        if (isset($_POST['user_login'])) {

            $user_login = sanitize_user(trim($_POST['user_login']));

            if (empty($user_login))
                pms_errors()->add('user_login', __('Please enter a username.', 'paid-member-subscriptions'));
            else {

                $user = get_user_by('login', $user_login);

                if ($user)
                    pms_errors()->add('user_login', __('This username is already taken. Please choose another one.', 'paid-member-subscriptions'));

            }

        }


        /*
         * E-mail
         */
        if (!isset($_POST['user_email']))
            pms_errors()->add('user_email', __('Please enter an e-mail address.', 'paid-member-subscriptions'));

        if (isset($_POST['user_email'])) {

            $user_email = sanitize_email(trim($_POST['user_email']));

            if (empty($user_email))
                pms_errors()->add('user_email', __('Please enter an e-mail address.', 'paid-member-subscriptions'));
            else {

                if (!is_email($user_email))
                    pms_errors()->add('user_email', __('The e-mail address doesn\'t seem to be valid.', 'paid-member-subscriptions'));
                else {

                    $user = get_user_by('email', $user_email);

                    if ($user)
                        pms_errors()->add('user_email', __('This e-mail is already registered. Please choose another one.', 'paid-member-subscriptions'));

                }

            }

        }


        /*
         * Password
         */
        if (!isset($_POST['pass1']) || empty($_POST['pass1']))
            pms_errors()->add('pass1', __('Please enter a password.', 'paid-member-subscriptions'));

        if (!isset($_POST['pass2']) || empty($_POST['pass2']))
            pms_errors()->add('pass2', __('Please repeat the password.', 'paid-member-subscriptions'));

        if (isset($_POST['pass1']) && isset($_POST['pass2'])) {

            $pass1 = trim($_POST['pass1']);
            $pass2 = trim($_POST['pass2']);

            if ($pass1 != $pass2)
                pms_errors()->add('pass2', __('The passwords did not match.', 'paid-member-subscriptions'));

        }


        /*
         * Subscription plans are validated by the "validate_subscription_plans" method from this class
         *
         */
        self::validate_subscription_plans($_POST);


        // Extra validations
        do_action('pms_register_form_validation', $_POST);


        // Stop if there are errors
        if (count(pms_errors()->get_error_codes()) > 0)
            return;


        // Add the subscriptions to an array
        $subscriptions = array();

        if (isset($_POST['subscription_plans'])) {

            if (is_array( $_POST['subscription_plans'] )) {
                foreach ($_POST['subscription_plans'] as $subscription_plan_id)
                    $subscriptions[] = (int)trim( $subscription_plan_id );
            } else {
                $subscriptions[] = (int)trim( $_POST['subscription_plans'] );
            }

        }

        // Set data
        $user_data = array(
            'user_login'    => (isset($user_login) ? $user_login : trim($_POST['user_login'])),
            'user_email'    => (isset($user_email) ? $user_email : trim($_POST['user_email'])),
            'first_name'    => (isset($_POST['first_name']) ? trim($_POST['first_name']) : ''),
            'last_name'     => (isset($_POST['last_name']) ? trim($_POST['last_name']) : ''),
            'user_pass'     => (isset($pass1) ? $pass1 : $_POST['pass1']),
            'role'          => get_option('default_role'),
            'subscriptions' => (isset($subscriptions) ? $subscriptions : array())
        );

        // Can modify user data
        $user_data = apply_filters('pms_register_form_user_data', $user_data, $_POST);

        // Do something before creating the user
        do_action('pms_register_form_before_create_user', $user_data);


        // Register the user and grab the user_id
        $user_id = wp_insert_user($user_data);
        $user_data['user_id'] = $user_id;

        // Do something after creating the user
        do_action('pms_register_form_after_create_user', $user_data);


        // Add member data for the user
        $active_subscriptions = pms_get_subscription_plans();
        if ( (!empty($active_subscriptions)) && ( !isset( $_POST['pmstkn2'] ) || ( isset($_POST['pmstkn2']) && !wp_verify_nonce( $_POST['pmstkn2'], 'pms_register_user_no_subscription_nonce' ) ) ) )  {

            // register member data only if there are active subscriptions and "subscription_plans" register shortcode param != "none"
            $registered = self::register_member_data($user_data);

            // Add payment and redirect
            if ($registered) {
                if (self::register_payment($user_data)) {
                    wp_redirect(self::get_redirect_url());
                    exit;
                }
            }
        }
        else {
            // Redirect to success page even on simple user registration (no subscription plan selected)
            wp_redirect(self::get_redirect_url());
            exit;
        }
    }


    /*
     * Method that validates the subscription plans sent
     *
     */
    public static function validate_subscription_plans( $post_data = array() ) {

        // If there are no active subscriptions return false
        $active_subscriptions = pms_get_subscription_plans();
        if ( empty($active_subscriptions) )
            return true;

        // Check if we need to register the user without him selecting a subscription (becoming a member) - thins happens when "subscription_plans" param in register form is = "none"
        if (isset( $_POST['pmstkn2'] ) && ( wp_verify_nonce( $_POST['pmstkn2'], 'pms_register_user_no_subscription_nonce' ) ) )
            return true;

            // Set post data
        if( empty( $post_data ) )
            $post_data = $_POST;

        // Check to see if any subscription plans where selected
        if( !isset( $post_data['subscription_plans'] ) )
            pms_errors()->add( 'subscription_plans', apply_filters( 'pms_error_subscription_plan_missing', __( 'Please select a subscription plan.', 'paid-member-subscriptions' ) ) );

        // Check to see if the subscription plan exists and is active
        else {

            $subscription_plan = pms_get_subscription_plan( (int)trim( $post_data['subscription_plans'] ) );

            if( !$subscription_plan->is_valid() || !$subscription_plan->is_active() )
                pms_errors()->add( 'subscription_plans', __( 'The selected subscription plan does not exist or is inactive.', 'paid-member-subscriptions' ) );

        }

        if( count( pms_errors()->get_error_messages() ) > 0 )
            return false;
        else
            return true;

    }


    /*
     * Method that adds ths subscription plans to the existing member / new user
     *
     * @param array $user_data  - array with certain information needed to add the subscription plan: user_id and subscriptions are needed
     *
     */
    public static function register_member_data( $user_data = array() ) {

        // Return if no data is returned or if user id doesn't exist
        if( empty( $user_data ) || !isset( $user_data['user_id'] ) ) {
            pms_errors()->add( 'subscription_plans', __( 'Something went wrong.', 'paid-member-subscriptions' ) );
            return false;
        }

        // Get member object
        $member = pms_get_member( $user_data['user_id'] );

        // Add subscription plans array to use later, instead of querying the db again
        $subscription_plan_cache = array();

        foreach( $user_data['subscriptions'] as $new_subscription_id ) {

            // Get subscription plan
            $subscription_plan = pms_get_subscription_plan( $new_subscription_id );
            $subscription_plan_cache[] = $subscription_plan;

            // Check to see if the subscription plan exists,
            // If it doesn't go to the next one
            if( !$subscription_plan->is_valid() ) {
                pms_errors()->add( 'subscription_plans', __( 'Something went wrong.', 'paid-member-subscriptions' ) );
                continue;
            }

            if( !empty( $member->subscriptions ) ) {
                foreach( $member->subscriptions as $member_subscription ) {

                    // Get subscription plan group for the current subscription plan
                    // We need to check if the plan we wish to add to the member isn't already added, or
                    // that another plan from the branch is already attached
                    $subscription_plans_group = pms_get_subscription_plans_group( $new_subscription_id );

                    foreach( $subscription_plans_group as $group_subscription_plan ) {
                        if( $group_subscription_plan->id == $member_subscription['subscription_plan_id'] )
                            pms_errors()->add( 'subscription_plans', sprintf( __( 'You are not eligible to subscribe to: %s', 'paid-member-subscriptions' ), $subscription_plan->name ) );
                    }

                }
            }

        }

        // Do nothing if we have errors
        if( count( pms_errors()->get_error_messages() ) > 0 )
            return false;


        // If we don't have any errors add subscription plans to the user
        foreach( $subscription_plan_cache as $subscription_plan ) {

            if( $subscription_plan->price == 0 ) {
                $status = 'active';
            } else {
                $status = 'pending';
            }

            $member->add_subscription( $subscription_plan->id, date('Y-m-d H:i:s'), $subscription_plan->get_expiration_date(), $status );
        }

        // If all good return true, so we know everything went as planned
        return true;

    }


    /*
     * Method that adds the payment to the db and also sends the user to the payment gateway to process the payment
     *
     * @param array $user_data  - array with certain information needed to add the subscription plan: user_id and subscriptions are needed
     *
     */
    public static function register_payment( $user_data ) {

        // Return if no data is returned or if user id doesn't exist
        if( empty( $user_data ) || !isset( $user_data['user_id'] ) ) {
            return false;
        }

        // Save subscription plans into an array
        $subscription_plan = pms_get_subscription_plan( $user_data['subscriptions'][0] );
        unset( $user_data['subscriptions'] );

        // Send the subscription plan object instead of just the id
        $user_data['subscription'] = $subscription_plan;


        // Get amount
        $amount = $subscription_plan->price;

        // Can modify amount at this point, and even if amount results to zero it will
        // be added to the db
        $amount = apply_filters( 'pms_register_payment_amount', $amount, $user_data, $subscription_plan );


        // If amount is zero by now, the subscription plan is free so
        // no need to save the payment in the db
        if( $amount == 0 ) {
            do_action( 'pms_register_payment_free_subscription_plan', $user_data );
            return true;
        }

        // Get settings
        $settings          = get_option( 'pms_settings' );
        $payments_settings = $settings['payments'];

        // Add payment to the db and also to the payment_data
        $payment    = new PMS_Payment();
        $payment_id = $payment->add( $user_data['user_id'], 'pending', date('Y-m-d H:i:s'), $amount, $user_data['subscription']->id );

        // Prepare payment data
        $payment_data = apply_filters( 'pms_register_payment_data', array(
            'payment_id'     => $payment_id,
            'amount'         => $amount,
            'sign_up_amount' => null,
            'user_data'      => $user_data,
            'currency'       => ( isset( $payments_settings['currency'] ) ? $payments_settings['currency'] : 'USD' ),
            'redirect_url'   => self::get_redirect_url(),
            'form_location'  => self::get_request_form_location()
        ), $payments_settings );


        /*
         * Action that fires just before sending the user to the payment processor
         *
         * @param array $payment_data
         *
         */
        do_action( 'pms_register_payment', $payment_data );


        // Check amount after discount code is applied
        if( $payment_data['amount'] == 0 ) {
            do_action( 'pms_register_payment_amount_zero', $payment_data );
            return true;
        }

        /*
         * Here we will redirect the user to the payment gateway
         */
        if ( isset( $_POST['pay_gate'] ) && !empty( $_POST['pay_gate'] ) ) {

            $payment_gateway = sanitize_text_field( $_POST['pay_gate'] );

            pms_to_gateway( $payment_gateway, $payment_data );

        }

        // Check if there are any errors
        if ( count( pms_errors()->get_error_codes() ) > 0 )
            return false;
        else
            return true;

    }


    /*
     * Validates when a member subscribes to a new plan
     *
     */
    public static function new_subscription_form() {

        // Verify nonce
        if( !isset( $_REQUEST['pmstkn'] ) || !wp_verify_nonce( $_REQUEST['pmstkn'], 'pms_new_subscription_form_nonce' ) )
            return;

        // Just in case, do not let logged out users get here
        if( !is_user_logged_in() )
            return;

        // First of all validate the subscription plans
        if( !self::validate_subscription_plans() )
            return;

        // Get user id
        $member = pms_get_member( pms_get_current_user_id() );

        if( $member->get_subscriptions_count() >= 1 ) {
            pms_errors()->add( 'subscription_plans', __( 'You are already a member.', 'paid-member-subscriptions' ) );
            return;
        }


        // Extra validations
        do_action( 'pms_new_subscription_form_validation', $_POST );

        // Stop if there are errors
        if ( count( pms_errors()->get_error_codes() ) > 0 )
            return;


        $member_data = self::get_request_member_data();

        $register_member_data = self::register_member_data( $member_data );

        if( $register_member_data )
            self::register_payment( $member_data );

    }


    /*
     * Method that validates when a member upgrades to a higher subscription plan
     *
     */
    public static function upgrade_subscription() {

        // Verify nonce
        if( !isset( $_REQUEST['pmstkn'] ) || !wp_verify_nonce( $_REQUEST['pmstkn'], 'pms_upgrade_subscription' ) )
            return;

        // Just in case, do not let logged out users get here
        if( !is_user_logged_in() )
            return;


        // Upgrade subscription
        if( isset( $_POST['pms_upgrade_subscription'] ) ) {

            if( !self::validate_subscription_plans($_POST) )
                return;

            // Extra validations
            do_action('pms_upgrade_subscription_form_validation', $_POST );

            // Stop if there are errors
            if ( count( pms_errors()->get_error_codes() ) > 0 )
                return;


            // Get user id
            $member_data = self::get_request_member_data();

            if( self::register_payment( $member_data ) ) {
                pms_success()->add( 'subscription_plans', apply_filters( 'pms_upgrade_subscription_success', __( 'Your subscription has been successfully upgraded.', 'paid-member-subscriptions' ) ) );
            }

        }

        // Redirect to current page and remove all query arguments
        if( isset( $_POST['pms_redirect_back'] ) ) {
            wp_redirect( esc_url( remove_query_arg( array( 'pms-action', 'subscription_plan', 'pmstkn' ), pms_get_current_page_url()) ));
            exit;
        }

    }


    /*
     * Executes when a member renews a subscription plan
     *
     */
    public static function renew_subscription() {

        // Verify nonce
        if( !isset( $_REQUEST['pmstkn'] ) || !wp_verify_nonce( $_REQUEST['pmstkn'], 'pms_renew_subscription' ) )
            return;

        // Just in case, do not let logged out users get here
        if( !is_user_logged_in() )
            return;

        // Renew subscription
        if( isset( $_POST['pms_renew_subscription'] ) ) {

            if( !self::validate_subscription_plans($_POST) )
                return;

            // Extra validations
            do_action('pms_renew_subscription_form_validation', $_POST );

            // Stop if there are errors
            if ( count( pms_errors()->get_error_codes() ) > 0 )
                return;


            // Get member data
            $member_data = self::get_request_member_data();

            if( self::register_payment( $member_data ) ) {
                pms_success()->add( 'subscription_plans', apply_filters( 'pms_renewed_subscription_success', __( 'Your subscription has been successfully renewed.', 'paid-member-subscriptions' ) ) );
            }
        }

        // Redirect to current page and remove all query arguments
        if( isset( $_POST['pms_redirect_back'] ) ) {
            wp_redirect( esc_url( pms_get_current_page_url( true ) ));
            exit;
        }

    }


    /*
     * Handles manual user subscription cancellation from account shortcode
     *
     */
    public static function cancel_subscription() {

        // Verify nonce
        if( !isset( $_REQUEST['pmstkn'] ) || !wp_verify_nonce( $_REQUEST['pmstkn'], 'pms_cancel_subscription' ) )
            return;

        // Just in case, do not let logged out users get here
        if( !is_user_logged_in() )
            return;

        if( !self::validate_subscription_plans($_POST) )
            return;

        // Remove subscription if confirm button was pressed
        if( isset( $_REQUEST['pms_confirm_cancel_subscription'] ) ) {

            // Extra validations
            do_action('pms_cancel_subscription_form_validation', $_POST );

            // Stop if there are errors
            if ( count( pms_errors()->get_error_codes() ) > 0 )
                return;


            $member_data = self::get_request_member_data();
            $member      = pms_get_member( $member_data['user_id'] );

            $subscription_plan_id = (int)trim( $_POST['subscription_plans'] );

            // Optional checks to confirm cancellation, besides the user driven one
            $confirm_remove_subscription = apply_filters( 'pms_confirm_cancel_subscription', true, $member->user_id, $subscription_plan_id );

            // If all is good remove the subscription, if not send an error
            if( true == $confirm_remove_subscription ) {

                if( $member->remove_subscription( $subscription_plan_id ) ) {
                    pms_success()->add( 'subscription_plans', apply_filters( 'pms_cancel_subscription_success', __( 'Your subscription has been successfully removed.', 'paid-member-subscriptions' ) ) );
                }

                do_action( 'pms_cancel_member_subscription_successful', $member_data, $member );

            } else {

                pms_errors()->add( 'subscription_plans', apply_filters( 'pms_cancel_subscription_error', __( 'Something went wrong. We could not cancel your subscription.', 'paid-member-subscriptions' ), $member_data, $member, $confirm_remove_subscription ));

                do_action( 'pms_cancel_member_subscription_unsuccessful', $member_data, $member, $confirm_remove_subscription );

            }

        }

        // Redirect to current page and remove all query arguments
        if( isset( $_REQUEST['pms_redirect_back'] ) ) {
            wp_redirect( esc_url( remove_query_arg( array( 'pms-action', 'subscription_plan', 'pmstkn' ), pms_get_current_page_url()) ));
            exit;
        }

    }


    /*
     * Executes when a member retries a payment for a pending subscription
     *
     */
    public static function retry_payment_subscription() {

        // Verify nonce
        if( !isset( $_REQUEST['pmstkn'] ) || !wp_verify_nonce( $_REQUEST['pmstkn'], 'pms_retry_payment_subscription' ) )
            return;

        // Just in case, do not let logged out users get here
        if( !is_user_logged_in() )
            return;


        // Renew subscription
        if( isset( $_POST['pms_confirm_retry_payment_subscription'] ) ) {

            if( !self::validate_subscription_plans($_POST) )
                return;

            // Extra validations
            do_action('pms_retry_payment_subscription_form_validation', $_POST );

            // Stop if there are errors
            if ( count( pms_errors()->get_error_codes() ) > 0 )
                return;


            // Get member data
            $member_data = self::get_request_member_data();

            $member = pms_get_member( $member_data['user_id'] );
            $member_subscription = $member->get_subscription( trim($_POST['subscription_plans']) );

            if( $member_subscription['status'] != 'pending' )
                return;

            self::register_payment( $member_data );

        }

        // Redirect to current page and remove all query arguments
        if( isset( $_POST['pms_redirect_back'] ) ) {
            wp_redirect( esc_url( pms_get_current_page_url( true ) ));
            exit;
        }

    }


    /*
     * Hooks to 'pms_register_payment_free_subscription_plan' to handle
     * free subscription plans
     *
     * @param array $payment_data_or_user_data  - array containing either the user_data from the 'pms_register_payment_free_subscription_plan' do_action
     *                                            or the payment_data from the 'pms_register_payment_amount_zero' do_action
     *
     */
    public static function handle_free_subscriptions( $payment_data_or_user_data ) {

        // Exit if the user_id is not set
        if( isset( $payment_data_or_user_data['user_data'] ) )
            $user_data = $payment_data_or_user_data['user_data'];
        else
            $user_data = $payment_data_or_user_data;

        // Exit if user_id or subscription is not present
        if( !isset( $user_data['user_id'] ) || !isset( $user_data['subscription'] ) )
            return;


        $member            = pms_get_member( $user_data['user_id'] );
        $subscription_plan = $user_data['subscription'];

        // Handle each location separately
        switch( self::get_request_form_location() ) {

            // default register form
            case 'register':
            // new subscription
            case 'new_subscription':
            // register form E-mail Confirmation compatibility
            case 'register_email_confirmation':
            // retry payments
            case 'retry_payment':

                // Complete the payment if it exists
                if( isset( $payment_data_or_user_data['payment_id'] ) ) {

                    $payment_id = $payment_data_or_user_data['payment_id'];

                    $payment = pms_get_payment( $payment_id );

                    $payment->update( array( 'status' => 'completed' ) );

                }

                // Activate member subscription
                $member_subscription = $member->get_subscription( $subscription_plan->id );

                if( !empty( $member_subscription ) && $member_subscription['status'] != 'active' ) {
                    $member->update_subscription( $member_subscription['subscription_plan_id'], $member_subscription['start_date'], $subscription_plan->get_expiration_date(), 'active' );
                }

                break;

            // Upgrading a subscription that is free
            case 'upgrade_subscription':

                $group_subscription_plans = pms_get_subscription_plans_group( $subscription_plan->id, false );

                if( count($group_subscription_plans) > 1 ) {

                    // Get current member subscription that will be upgraded
                    foreach( $group_subscription_plans as $group_subscription_plan ) {
                        if( in_array( $group_subscription_plan->id, $member->get_subscriptions_ids() ) ) {
                            $member_subscription = $group_subscription_plan;
                            break;
                        }
                    }

                    if( isset($member_subscription) ) {

                        do_action( 'pms_free_subscription_before_upgrade_subscription', $member_subscription->id );

                        $member->remove_subscription( $member_subscription->id );
                        $member->add_subscription( $subscription_plan->id, date('Y-m-d H:i:s'), $subscription_plan->get_expiration_date(), 'active' );

                        do_action( 'pms_free_subscription_after_upgrade_subscription', $member_subscription->id );
                    }

                }

                break;

            // Renewing a subscription that is free
            case 'renew_subscription':

                // Complete the payment if it exists
                if( isset( $payment_data_or_user_data['payment_id'] ) ) {

                    $payment_id = $payment_data_or_user_data['payment_id'];

                    $payment = pms_get_payment( $payment_id );

                    $payment->update( array( 'status' => 'completed' ) );

                }

                $member_subscription = $member->get_subscription( $subscription_plan->id );

                if( strtotime( $member_subscription['expiration_date'] ) < time() || $subscription_plan->duration === 0 )
                    $member_subscription_expiration_date = $subscription_plan->get_expiration_date();
                else {
                    $member_subscription_expiration_date = date( 'Y-m-d 23:59:59', strtotime( $member_subscription['expiration_date'] . '+' . $subscription_plan->duration . ' ' . $subscription_plan->duration_unit ) );
                }

                $member->update_subscription( $member_subscription['subscription_plan_id'], $member_subscription['start_date'], $member_subscription_expiration_date, 'active' );

                break;

        }

    }


    /*
     * Handles login form validation and redirection
     *
     */
    public static function login_form( $redirect_to, $request, $user ) {

        if( isset( $_POST['pms_login'] ) && $_POST['pms_login'] == 1 ) {

            if( is_wp_error($user) ) {

                $redirect_to   = esc_url( $_POST['pms_redirect'] );

                $error_code    = $user->get_error_code();
                $error_message = $user->get_error_message( $error_code );

                // If there's no error message then neither the user name or password was entered
                if( empty( $error_message ) )
                    $error_message = '<strong>' . __('ERROR', 'paid-member-subscriptions') . '</strong>: ' . __('Both fields are empty.', 'paid-member-subscriptions');

                if( isset($error_message) && !empty($error_message) )
                    $redirect_to = add_query_arg( array( 'login_error' => urlencode(base64_encode($error_message)) ) , $redirect_to );

            } else {

                $redirect_to = remove_query_arg( array('login_error'), $redirect_to );

            }

            wp_safe_redirect( $redirect_to );

        }

        return $redirect_to;
    }


    /*
     * Returns an array with the member data from the request,
     * user_id and subscriptions are required to be present
     *
     */
    public static function get_request_member_data( $user_id = 0 ) {

        $member_id          = ( !empty( $user_id ) ? $user_id : pms_get_current_user_id() );
        $subscription_plans = array( (int)trim( $_POST['subscription_plans'] ) );
        $user_email         = ( isset($_REQUEST['user_email']) ? sanitize_email( trim( $_REQUEST['user_email'] ) ) : '' );

        if( empty($user_email) ) {
            $user_data = get_userdata( $member_id );

            if( !is_wp_error($user_data) )
                $user_email = $user_data->user_email;
        }

        return array(
            'user_id'       => $member_id,
            'subscriptions' => $subscription_plans,
            'user_email'    => $user_email
        );
    }


    /*
     * Returns a slug of the form location from which the user made
     * the request
     *
     * @return string
     *
     */
    public static function get_request_form_location() {

        $location = '';

        if( !isset( $_POST['pmstkn'] ) )
            $location = '';

        else {

            // Register form
            if( wp_verify_nonce( $_REQUEST['pmstkn'], 'pms_register_form_nonce') )
                $location = 'register';

            // Cancel subscription
            if( wp_verify_nonce( $_REQUEST['pmstkn'], 'pms_edit_profile_form_nonce' ) )
                $location = 'edit_profile';

            // Add new subscription
            if( wp_verify_nonce( $_REQUEST['pmstkn'], 'pms_new_subscription_form_nonce' ) )
                $location = 'new_subscription';

            // Upgrade subscription
            if( wp_verify_nonce( $_REQUEST['pmstkn'], 'pms_upgrade_subscription' ) )
                $location = 'upgrade_subscription';

            // Renew subscription
            if( wp_verify_nonce( $_REQUEST['pmstkn'], 'pms_renew_subscription' ) )
                $location = 'renew_subscription';

            // Cancel subscription
            if( wp_verify_nonce( $_REQUEST['pmstkn'], 'pms_cancel_subscription' ) )
                $location = 'cancel_subscription';

            // Retry subscription payment
            if( wp_verify_nonce( $_REQUEST['pmstkn'], 'pms_retry_payment_subscription' ) )
                $location = 'retry_payment';

        }

        return apply_filters( 'pms_request_form_location', $location, $_REQUEST );

    }

    /*
     * Returns the URL where the user should be redirected back to
     * after registering or completing a purchase
     *
     */
    public static function get_redirect_url() {

        $url      = '';
        $location = self::get_request_form_location();

        switch( $location ) {

            case 'register':
                $pms_settings = get_option('pms_settings');
                $url = ( isset( $pms_settings['general']['register_success_page'] ) && $pms_settings['general']['register_success_page'] != -1 ? get_permalink( trim( $pms_settings['general']['register_success_page'] ) ) : '' );

                // Add success message
                if( empty($url) ) {
                    $url = pms_get_current_page_url( true );
                    $url = add_query_arg( array( 'pmsscscd' => base64_encode('subscription_plans'), 'pmsscsmsg' => base64_encode( apply_filters( 'pms_register_subscription_success_message', __( 'Congratulations, you have successfully created an account.', 'paid-member-subscriptions' ) ) ) ), $url );
                }

                break;

            case 'upgrade_subscription':
            case 'renew_subscription':
            case 'new_subscription':
            case 'retry_payment':
                $url = pms_get_current_page_url( true );

                // Add success message
                $url = add_query_arg( array( 'pms_gateway_payment_action' => base64_encode( $location ) ), $url );
                break;

        }

        return apply_filters( 'pms_get_redirect_url', $url, $location );

    }


    /*
     * Handles data sent from the recover password form
     *
     */
    public static function recover_password_form() {

        /*
         * Username or Email
         */
        if( isset( $_POST['pms_username_email'] ) ) {

            //Check recover password form nonce;
            if( !isset( $_POST['pmstkn'] ) || ( !wp_verify_nonce( $_POST['pmstkn'], 'pms_recover_password_form_nonce') ) )
                return;

            $username_email = sanitize_text_field( $_POST['pms_username_email'] );

            if( empty( $username_email ) )
                pms_errors()->add( 'pms_username_email', __( 'Please enter a username or email address.', 'paid-member-subscriptions' ) );
            else {

                $user = '';
                // verify if it's a username and a valid one
                if ( !is_email($username_email) ) {
                    if ( username_exists($username_email) ) {
                        $user = get_user_by('login',$username_email);
                    }
                        else pms_errors()->add('pms_username_email',__( 'The entered username doesn\'t exist. Please try again.', 'paid-member-subscriptions'));
                }

                //verify if it's a valid email
                if ( is_email( $username_email ) ){
                    if ( email_exists($username_email) ) {
                        $user = get_user_by('email', $username_email);
                    }
                    else pms_errors()->add('pms_username_email',__( 'The entered email wasn\'t found in our database. Please try again.', 'paid-member-subscriptions'));
                }

            }

            // Extra validation
            do_action( 'pms_recover_password_form_validation', $_POST );

            //If entered username or email is valid (no errors), email the password reset confirmation link
            if ( count( pms_errors()->get_error_codes() ) == 0 ) {

                if (is_object($user)) {  //user data is set
                    $requestedUserID = $user->ID;
                    $requestedUserLogin = $user->user_login;
                    $requestedUserEmail = $user->user_email;

                    //search if there is already an activation key present, if not create one
                    $key = pms_retrieve_activation_key( $requestedUserLogin );

                    //Confirmation link email content
                    $recoveruserMailMessage1 = sprintf(__('Someone requested that the password be reset for the following account: <b>%1$s</b><br/><br/>If this was a mistake, just ignore this email and nothing will happen.<br/>To reset your password, visit the following link: %2$s', 'paid-member-subscriptions'), $username_email, '<a href="' . esc_url(add_query_arg(array('loginName' => urlencode( $requestedUserLogin ), 'key' => $key), pms_get_current_page_url())) . '">' . esc_url(add_query_arg(array('loginName' => urlencode( $requestedUserLogin ), 'key' => $key), pms_get_current_page_url())) . '</a>');
                    $recoveruserMailMessage1 = apply_filters('pms_recover_password_message_content_sent_to_user1', $recoveruserMailMessage1, $requestedUserID, $requestedUserLogin, $requestedUserEmail);

                    //Confirmation link email title
                    $recoveruserMailMessageTitle1 = sprintf(__('Password Reset from "%s"', 'paid-member-subscriptions'), $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES));
                    $recoveruserMailMessageTitle1 = apply_filters('pms_recover_password_message_title_sent_to_user1', $recoveruserMailMessageTitle1, $requestedUserLogin);

                    //we add this filter to enable html encoding
                    add_filter('wp_mail_content_type', create_function('', 'return "text/html"; '));

                    //send mail to the user notifying him of the reset request
                    if (trim($recoveruserMailMessageTitle1) != '') {
                        $sent = wp_mail($requestedUserEmail, $recoveruserMailMessageTitle1, $recoveruserMailMessage1);
                        if ($sent === false)
                            pms_errors()->add('pms_username_email',__( 'There was an error while trying to send the activation link.', 'paid-member-subscriptions'));
                    }

                    // add option to store all user $id => $key and timestamp values that reset their passwords every 24 hours
                    if ( false === ( $activation_keys = get_option( 'pms_recover_password_activation_keys' ) ) ) {
                        $activation_keys = array();
                    }
                    $activation_keys[$user->ID]['key'] = $key;
                    $activation_keys[$user->ID]['time'] = time();
                    update_option( 'pms_recover_password_activation_keys', $activation_keys );

                }
            }

        } // isset($_POST[pms_username_email])


        // If the user clicked the email confirmation link, make the verifications and change password
        if ( !empty($_GET['loginName']) && !empty($_GET['key']) ) {

            //Check new password form nonce;
            if( !isset( $_POST['pmstkn'] ) || ( !wp_verify_nonce( $_POST['pmstkn'], 'pms_new_password_form_nonce') ) )
                return;

            //check if the new password form was submitted
            if ( !empty($_POST['pms_new_password']) && !empty($_POST['pms_repeat_password']) ) {

                $new_pass    = trim($_POST['pms_new_password']);
                $repeat_pass = trim($_POST['pms_repeat_password']);

                if ($new_pass != $repeat_pass )
                    pms_errors()->add('pms_repeat_password',__( 'The entered passwords don\'t match! Please try again.', 'paid-member-subscriptions'));

                $loginName = sanitize_user( $_GET['loginName'] );
                $key       = sanitize_text_field( $_GET['key'] );
                $user      = get_user_by('login', $loginName);

                if ( ( count( pms_errors()->get_error_codes() ) == 0 ) && is_object($user) && ($user->user_activation_key == $key) ) {
                    do_action( 'pms_password_reset', $user->ID, $new_pass );
                    // update the new password
                    wp_set_password( $new_pass, $user->ID );
                    //delete the user activation key
                    update_user_meta($user->ID, 'user_activation_key', '' );
                }

            }

        }

    }


    /*
     * Handles data received from the edit profile form
     *
     */
    public static function edit_profile() {

        // Verify nonce
        if( !isset( $_REQUEST['pmstkn'] ) || !wp_verify_nonce( $_REQUEST['pmstkn'], 'pms_edit_profile_form_nonce' ) )
            return;

        // Just in case, do not let logged out users get here
        if( !is_user_logged_in() )
            return;

        $user = get_userdata( pms_get_current_user_id() );

        /*
         * E-mail
         */
        if( !isset( $_POST['user_email'] ) )
            pms_errors()->add( 'user_email', __( 'Please enter an e-mail address.', 'paid-member-subscriptions' ) );

        if( isset( $_POST['user_email'] ) ) {

            $user_email = sanitize_email( $_POST['user_email'] );

            if( empty( $user_email ) )
                pms_errors()->add( 'user_email', __( 'Please enter an e-mail address.', 'paid-member-subscriptions' ) );
            else {

                if( !is_email( $user_email ) )
                    pms_errors()->add( 'user_email', __( 'The e-mail address doesn\'t seem to be valid.', 'paid-member-subscriptions' ) );
                elseif( $user->user_email != $user_email ) {

                    $check_user = get_user_by( 'email', $user_email );

                    if( $check_user )
                        pms_errors()->add( 'user_email', __( 'This e-mail is already registered. Please choose another one.', 'paid-member-subscriptions' ) );

                }

            }

        }

        /*
         * First name and last name
         */
        $user_first_name = ( isset( $_POST['first_name'] ) ? sanitize_text_field( $_POST['first_name'] ) : '' );
        $user_last_name  = ( isset( $_POST['last_name'] )  ? sanitize_text_field( $_POST['last_name'] ) : '' );


        /*
         * Password
         */
        if( ( isset( $_POST['pass1'] ) && !empty( $_POST['pass1'] ) ) && ( isset( $_POST['pass2'] ) && !empty( $_POST['pass2'] ) ) ) {

            $pass1 = trim($_POST['pass1']);
            $pass2 = trim($_POST['pass2']);

            // Check for HTML in the fields
            if( strip_tags( $pass1 ) != $pass1 )
                pms_errors()->add( 'pass1', __( 'Some of the characters entered were not valid.', 'paid-member-subscriptions' ) );

            if( strip_tags( $pass2 ) != $pass2 )
                pms_errors()->add( 'pass1', __( 'Some of the characters entered were not valid.', 'paid-member-subscriptions' ) );

            if( (strip_tags( $pass1 ) == $pass1) && (strip_tags( $pass2 ) == $pass2) ) {

                if( $pass1 != $pass2 )
                    pms_errors()->add( 'pass2', __( 'The passwords did not match.', 'paid-member-subscriptions' ) );

            }

        }

        // Extra validation
        do_action( 'pms_edit_profile_form_validation', $_POST );


        // Stop if there are errors
        if( count( pms_errors()->get_error_codes() ) > 0 )
            return;

        /*
         * Update user information
         */
        $user_data = array(
            'ID'          => $user->ID,
            'first_name'  => $user_first_name,
            'last_name'   => $user_last_name
        );

        if( isset($user_email) )
            $user_data['user_email'] = $user_email;

        if( isset( $pass1 ) )
            $user_data['user_pass'] = $pass1;

        $user_id = wp_update_user( $user_data );

        if( !is_wp_error($user_id) )
            pms_success()->add( 'edit_profile', __( 'Profile updated successfully', 'paid-member-subscriptions' ) );
        else
            pms_errors()->add( 'edit_profile', __( 'Something went wrong. We could not update your profile.', 'paid-member-subscriptions' ) );

    }

}

PMS_Form_Handler::init();