<?php
/*
 * Extends the payment gateway base class for PayPal Standard
 *
 */

Class PMS_Payment_Gateway_PayPal_Standard extends PMS_Payment_Gateway {


    /*
     * Process for all register payments that are not free
     *
     */
    public function process_sign_up() {

        // Do nothing if the payment id wasn't sent
        if( !$this->payment_id )
            return;

        $settings = get_option( 'pms_settings' );
        $settings = $settings['payments'];

        //Update payment type
        $payment = pms_get_payment( $this->payment_id );
        $payment->update_type( apply_filters( 'pms_paypal_standard_payment_type', 'web_accept_paypal_standard', $this, $settings ) );


        // Set the notify URL
        $notify_url = home_url() . '/?pay_gate_listener=paypal_ipn';

        if( pms_is_payment_test_mode() )
            $paypal_link = 'https://www.sandbox.paypal.com/cgi-bin/webscr/?';
        else
            $paypal_link = 'https://www.paypal.com/cgi-bin/webscr/?';

        $paypal_args = array(
            'cmd'           => '_xclick',
            'business'      => trim( $settings['gateways']['paypal_standard']['email_address'] ),
            'email'         => $this->user_email,
            'item_name'     => $this->subscription_plan->name,
            'item_number'   => $this->subscription_plan->id,
            'currency_code' => $this->currency,
            'amount'        => $this->amount,
            'tax'           => 0,
            'custom'        => $this->payment_id,
            'notify_url'    => $notify_url,
            'return'        => add_query_arg( array( 'pms_gateway_payment_id' => base64_encode($this->payment_id), 'pmsscscd' => base64_encode('subscription_plans') ), $this->redirect_url ),
            'charset'       => 'UTF-8'
        );

        $paypal_link .= http_build_query( apply_filters( 'pms_paypal_standard_args', $paypal_args, $this, $settings ) );


        do_action( 'pms_before_paypal_redirect', $paypal_link, $this, $settings );


        // Redirect only if tkn is set
        if( isset( $_POST['pmstkn'] ) ) {

            header( 'Location:' . $paypal_link );
            exit;
        }

    }


    /*
     * Process IPN sent by PayPal
     *
     */
    public function process_webhooks() {


        if( !isset( $_GET['pay_gate_listener'] ) || $_GET['pay_gate_listener'] != 'paypal_ipn' )
            return;

        // Get settings
        $settings = get_option( 'pms_settings' );

        // Init IPN Verifier
        $ipn_verifier = new PMS_IPN_Verifier();

        if( isset( $settings['payments']['test_mode'] ) )
            $ipn_verifier->is_sandbox = true;


        $verified = false;

        // Process the IPN
        try {
            if( $ipn_verifier->checkRequestPost() )
                $verified = $ipn_verifier->validate();
        } catch ( Exception $e ) {

        }


        if( $verified ) {

            $post_data = $_POST;

            // Get payment id from custom variable sent by IPN
            $payment_id = isset( $post_data['custom'] ) ? $post_data['custom'] : 0;

            // Get the payment
            $payment = pms_get_payment( $payment_id );

            // Get user id from the payment
            $user_id = $payment->user_id;

            $payment_data = apply_filters( 'pms_paypal_ipn_payment_data', array(
                'payment_id'     => $payment_id,
                'user_id'        => $user_id,
                'type'           => $post_data['txn_type'],
                'status'         => strtolower($post_data['payment_status']),
                'transaction_id' => $post_data['txn_id'],
                'amount'         => $post_data['mc_gross'],
                'date'           => $post_data['payment_date'],
                'subscription_id'=> $post_data['item_number']
            ), $post_data );


            // web_accept is returned for A Direct Credit Card (Pro) transaction,
            // A Buy Now, Donation or Smart Logo for eBay auctions button
            if( $payment_data['type'] == 'web_accept' ) {

                // If the payment has already been completed do nothing
                if( $payment->status == 'completed' )
                    return;

                // If the status is completed update the payment and also activate the member subscriptions
                if( $payment_data['status'] == 'completed' ) {

                    // Complete payment
                    $payment->update( array( 'status' => $payment_data['status'], 'transaction_id' => $payment_data['transaction_id'] ) );

                    // Update member subscriptions
                    $member = pms_get_member( $payment_data['user_id'] );


                    // Update status to active for subscriptions that exist both in the user subscriptions and also in the payment info
                    foreach( $member->subscriptions as $member_subscription ) {
                        if( $member_subscription['subscription_plan_id'] == $payment_data['subscription_id'] ) {

                            // If subscription is pending it is a new one
                            if( $member_subscription['status'] == 'pending' ) {
                                $member_subscription_expiration_date = $member_subscription['expiration_date'];

                            // This is an old subscription
                            } else {

                                $subscription_plan = pms_get_subscription_plan( $member_subscription['subscription_plan_id'] );

                                if( strtotime( $member_subscription['expiration_date'] ) < time() || $subscription_plan->duration === 0 )
                                    $member_subscription_expiration_date = $subscription_plan->get_expiration_date();
                                else
                                    $member_subscription_expiration_date = date( 'Y-m-d 23:59:59', strtotime( $member_subscription['expiration_date'] . '+' . $subscription_plan->duration . ' ' . $subscription_plan->duration_unit ) );
                            }

                            // Update subscription
                            $member->update_subscription( $member_subscription['subscription_plan_id'], $member_subscription['start_date'], $member_subscription_expiration_date, 'active' );

                        }
                    }

                    /*
                     * If the subscription plan id sent by the IPN is not found in the members subscriptions
                     * then it could be an update to an existing one
                     *
                     * If one of the member subscriptions is in the same group as the payment subscription id,
                     * the payment subscription id is an upgrade to the member subscription one
                     *
                     */
                    if( !in_array( $payment_data['subscription_id'], $member->get_subscriptions_ids() ) ) {

                        $group_subscription_plans = pms_get_subscription_plans_group( $payment_data['subscription_id'], false );

                        if( count($group_subscription_plans) > 1 ) {

                            // Get current member subscription that will be upgraded
                            foreach( $group_subscription_plans as $subscription_plan ) {
                                if( in_array( $subscription_plan->id, $member->get_subscriptions_ids() ) ) {
                                    $member_subscription = $subscription_plan;
                                    break;
                                }
                            }

                            if( isset($member_subscription) ) {

                                do_action( 'pms_paypal_web_accept_before_upgrade_subscription', $member_subscription->id, $payment_data, $post_data );

                                $member->remove_subscription( $member_subscription->id );

                                $new_subscription_plan = pms_get_subscription_plan( $payment_data['subscription_id'] );

                                $member->add_subscription( $new_subscription_plan->id, date('Y-m-d H:i:s'), $new_subscription_plan->get_expiration_date(), 'active' );

                                do_action( 'pms_paypal_web_accept_after_upgrade_subscription', $member_subscription->id, $payment_data, $post_data );
                            }

                        }

                    }

                // If payment status is not complete, something happened, so log it in the payment
                } else {

                    // Add the transaction ID
                    $payment->update( array( 'transaction_id' => $payment_data['transaction_id'] ) );

                    $log_data = array(
                        'payment_status' => $post_data['payment_status'],
                        'payment_date'   => $post_data['payment_date'],
                        'payer_id'       => $post_data['payer_id'],
                        'payer_email'    => $post_data['payer_email'],
                        'payer_status'   => $post_data['payer_status']
                    );

                    $payment->add_log_entry( 'failure', __( 'The payment could not be completed successfully', 'paid-member-subscription' ), $log_data );

                }

            }

            do_action( 'pms_paypal_ipn_listener_verified', $payment_data, $post_data );

        }

    }

}