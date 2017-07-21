<?php
/*
 * Verifies whether the IPN received is coming from PayPal
 *
 */

Class PMS_IPN_Verifier {

    public $is_sandbox = false;

    public $valid = false;

    public $post_data;

    private $endpoint;


    /*
     * Returns the PayPal endpoint
     *
     */
    public function get_endpoint() {

        if( $this->is_sandbox == false )
            $this->endpoint = 'https://ipnpb.paypal.com/cgi-bin/webscr/';
        else
            $this->endpoint = 'https://ipnpb.sandbox.paypal.com/cgi-bin/webscr/';

        return $this->endpoint;

    }


    /*
     * Validate IPN
     *
     */
    public function validate() {

        // Save post for further use
        $this->post_data = $_POST;

        // Get the post data and add the cmd to it
        $body = array( 'cmd' => '_notify-validate' );
        $body += wp_unslash( $this->post_data );

        /**
         * Filter to modify the arguments passed to the remote post function
         *
         * @param array
         *
         */
        $args = apply_filters( 'pms_paypal_ipn_validate_args', array( 'timeout' => 30, 'body' => $body ) );

        // Make the call to PayPal
        $request = wp_remote_post( $this->get_endpoint(), $args );

        // Verify if IPN is valid
        if( !is_wp_error( $request ) && wp_remote_retrieve_response_code( $request ) == 200 && !empty( $request['body'] ) && strstr( $request['body'], 'VERIFIED' ) )
            $this->valid = true;

        return $this->valid;

    }


    /*
     * PayPal sends a POST request. We should check that first, before even trying to do anything
     * Returns true if the request method is POST
     *
     * @return bool
     *
     */
    public function checkRequestPost() {

        if( !isset( $_SERVER['REQUEST_METHOD'] ) || $_SERVER['REQUEST_METHOD'] != "POST" )
            return false;
        else
            return true;

    }

}