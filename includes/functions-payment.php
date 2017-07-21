<?php

/*
 * Functions for payment things
 *
 */


    /*
     * Wrapper function to return a payment object
     *
     */
    function pms_get_payment( $id = 0 ) {
        return new PMS_Payment( $id );
    }


    /**
     * Return payments filterable by an array of arguments
     *
     * @param array $args
     *
     * @return array
     *
     */
    function pms_get_payments( $args = array() ) {

        global $wpdb;

        $defaults = array(
            'order'         => 'DESC',
            'orderby'       => 'id',
            'number'        => 1000,
            'offset'        => '',
            'status'        => '',
            'type'          => '',
            'user_id'       => '',
            'profile_id'    => '',
            'date'          => '',
            'search'        => ''
        );

        $args = apply_filters( 'pms_get_payments_args', wp_parse_args( $args, $defaults ), $args, $defaults );

        // Start query string
        $query_string       = "SELECT pms_payments.* ";

        // Query string sections
        $query_from         = "FROM {$wpdb->prefix}pms_payments pms_payments ";
        $query_inner_join   = "INNER JOIN {$wpdb->users} users ON pms_payments.user_id = users.id ";
        $query_inner_join   = $query_inner_join . "INNER JOIN {$wpdb->posts} posts ON pms_payments.subscription_plan_id = posts.id ";
        $query_where        = "WHERE 1=%d ";

        // Add search query
        if( !empty($args['search']) ) {
            $search_term    = sanitize_text_field( $args['search'] );
            $query_where    = $query_where . " AND " . " ( pms_payments.transaction_id LIKE '%s' OR users.user_nicename LIKE '%%%s%%' OR posts.post_title LIKE '%%%s%%' ) ". " ";
        }

        // Filter by status
        if( !empty( $args['status'] ) ) {
            $status         = sanitize_text_field( $args['status'] );
            $query_where    = $query_where . " AND " . " pms_payments.status LIKE '{$status}'";
        }

        /*
         * Filter by date
         * Can be filtered by - a single date that will return payments from that date
         *                    - an array with two dates that will return payments between the two dates
         */
        if( !empty( $args['date'] ) ) {

            if( is_array( $args['date'] ) && !empty( $args['date'][0] ) && !empty( $args['date'][1] ) ) {

                $args['date'][0] = sanitize_text_field( $args['date'][0] );
                $args['date'][1] = sanitize_text_field( $args['date'][1] );

                $query_where = $query_where . " AND " . " ( pms_payments.date BETWEEN '{$args['date'][0]}' AND '{$args['date'][1]}' )";

            } elseif( is_string( $args['date'] ) ) {

                $args['date'] = sanitize_text_field( $args['date'] );

                $query_where = $query_where . " AND " . " pms_payments.date LIKE '%%{$args['date']}%%'";

            }

        }

        // Filter by type
        if( !empty( $args['type'] ) ) {
            $type = sanitize_text_field( $args['type'] );
            $query_where    = $query_where . " AND " . " pms_payments.type LIKE '{$type}'";
        }

        // Filter by profile_id
        if( !empty( $args['profile_id'] ) ) {
            $profile_id = sanitize_text_field( $args['profile_id'] );
            $query_where    = $query_where . " AND " . " pms_payments.profile_id LIKE '{$profile_id}'";
        }

        // Filter by profile id
        if( !empty( $args['user_id'] ) ) {
            $user_id = (int)trim( $args['user_id'] );
            $query_where    = $query_where . " AND " . " pms_payments.user_id = {$user_id}";
        }

        $query_order_by = '';
        if ( !empty($args['orderby']) )
            $query_order_by = " ORDER BY pms_payments." . sanitize_text_field( $args['orderby'] ) . ' ';

        $query_order = $args['order'] . ' ';

        $query_limit        = '';
        if( $args['number'] )
            $query_limit    = 'LIMIT ' . (int)trim( $args['number'] ) . ' ';

        $query_offset       = '';
        if( $args['offset'] )
            $query_offset   = 'OFFSET ' . (int)trim( $args['offset'] ) . ' ';

        // Concatenate query string
        $query_string .= $query_from . $query_inner_join . $query_where . $query_order_by . $query_order . $query_limit . $query_offset;


        // Return results
        if (!empty($search_term))
            $data_array = $wpdb->get_results( $wpdb->prepare( $query_string, 1, $wpdb->esc_like( $search_term ) , $wpdb->esc_like( $search_term ), $wpdb->esc_like( $search_term ) ), ARRAY_A );
        else
            $data_array = $wpdb->get_results( $wpdb->prepare( $query_string, 1 ), ARRAY_A );

        $payments = array();

        if( !empty( $data_array ) ) {
            foreach( $data_array as $key => $data ) {

                // Inconsistency fix between the db table row name and
                // the PMS_Payment property
                if( !empty( $data['subscription_plan_id'] ) )
                    $data['subscription_id'] = $data['subscription_plan_id'];

                $payment = new PMS_Payment();
                $payment->set_instance( $data );

                $payments[] = $payment;
            }
        }

        /**
         * Filter payments just before returning them
         *
         * @param array $payments - the array of returned payments from the db
         * @param array $args     - the arguments used to query the payments from the db
         *
         */
        $payments = apply_filters( 'pms_get_payments', $payments, $args );

        return $payments;

    }


    /**
     * Returns the total number of payments from the db
     *
     * @param array $args  - array of arguments to filter the count for
     *
     * @return int
     *
     */
    function pms_get_payments_count( $args = array() ) {

        global $wpdb;

        /**
         * Base query string
         */
        $query_string = "SELECT COUNT(pms_payments.id) FROM {$wpdb->prefix}pms_payments pms_payments ";

        /**
         * Inner join
         */
        $query_inner_join = "";

        if( !empty( $args['search'] ) ) {
            $query_inner_join  = "INNER JOIN {$wpdb->users} users ON pms_payments.user_id = users.id ";
            $query_inner_join .= "INNER JOIN {$wpdb->posts} posts ON pms_payments.subscription_plan_id = posts.id ";
        }

        /**
         * Where clauses
         */
        $query_where  = "WHERE 1=%d ";

        // Filter by search
        if( !empty( $args['search'] ) ) {
            $search = sanitize_text_field( $args['search'] );
            $query_where .= " AND ( pms_payments.transaction_id LIKE '%%{$search}%%' OR users.user_nicename LIKE '%%{$search}%%' OR posts.post_title LIKE '%%{$search}%%' ) ". " ";
        }

        // Filter by status
        if( !empty( $args['status'] ) ) {
            $status = sanitize_text_field( $args['status'] );
            $query_where .= "AND pms_payments.status = '{$status}' ";
        }


        /**
         * Get cached version first
         *
         */
        $key   = md5( 'pms_payments_count_' . serialize( $args ) );
        $count = get_transient( $key );


        /**
         * Make db query if cache is empty and set the cache
         *
         */
        if( false === $count ) {

            $count = $wpdb->get_var( $wpdb->prepare( $query_string . $query_inner_join . $query_where, 1 ) );

            /**
             * The expiration time ( in seconds ) for the cached payments count returned for
             * the given args
             *
             * @param array $args
             *
             */
            $cache_time = apply_filters( 'pms_payments_count_cache_time', 1800, $args );

            set_transient( $key, $count, $cache_time );

        }

        return (int)$count;

    }


    /**
     * Returns the number of payments a user has made
     *
     * @param int $user_id
     *
     * @return int
     *
     */
    function pms_get_member_payments_count( $user_id = 0 ) {

        if( $user_id === 0 )
            return 0;

        global $wpdb;

        $user_id = (int)$user_id;

        $query_string = "SELECT COUNT( DISTINCT id ) FROM {$wpdb->prefix}pms_payments WHERE 1=%d AND user_id LIKE {$user_id}";

        $count = $wpdb->get_var( $wpdb->prepare( $query_string, 1 ) );

        return (int)$count;

    }


    /*
     * Function that returns all possible payment statuses
     *
     * @return array
     *
     */
    function pms_get_payment_statuses() {

        return apply_filters( 'pms_payment_statuses', array(
            'pending'   => __( 'Pending', 'paid-member-subscriptions' ),
            'completed' => __( 'Completed', 'paid-member-subscriptions' ),
            'refunded'  => __( 'Refunded', 'paid-member-subscriptions' )
        ));

    }


    /*
     * Returns an array with the payment types supported
     *
     * @return array
     *
     */
    function pms_get_payment_types() {

        return apply_filters( 'pms_payment_types', array(
            'manual_payment'             => __( 'Manual Payment', 'paid-member-subscriptions' ),
            'web_accept_paypal_standard' => __( 'PayPal Standard - One-Time Payment', 'paid-member-subscriptions' )
        ));

    }


    /**
     * Returns true if the test mode is checked in the payments settings page
     * and false if it is not checked
     *
     * @return bool
     *
     */
    function pms_is_payment_test_mode() {

        $pms_settings = get_option('pms_settings');

        if( isset( $pms_settings['payments']['test_mode'] ) && $pms_settings['payments']['test_mode'] == 1 )
            return true;
        else
            return false;

    }


    /*
     * Returns the name of the payment type given its slug
     *
     * @param string $payment_type_slug
     *
     * @return string
     *
     */
    function pms_get_payment_type_name( $payment_type_slug ) {

        $payment_types = pms_get_payment_types();

        if( isset( $payment_types[$payment_type_slug] ) )
            return $payment_types[$payment_type_slug];
        else
            return '';

    }


    /*
     * Function that outputs the payment gateway options
     *
     * @param array $pms_settings     - the saved settings
     *
     * @return string
     *
     */
    function pms_get_output_payment_gateways( $pms_settings = array() ) {

        if( empty($pms_settings) )
            $pms_settings = get_option( 'pms_settings' );

        $output = '';

        // Output gateways only when we have active subscription plans
        $active_subscriptions = pms_get_subscription_plans();
        if ( empty($active_subscriptions) ) {
            return $output;
        }

        // If there's only one payment gateway saved
        if( count( $pms_settings['payments']['active_pay_gates'] ) == 1 ) {

            $output .= apply_filters( 'pms_output_payment_gateway_input_hidden', '<input type="hidden" name="pay_gate" value="' . ( $pms_settings['payments']['active_pay_gates'][0] != 'paypal_standard' ? $pms_settings['payments']['active_pay_gates'][0] : 'paypal_standard' ) . '" />', $pms_settings['payments']['active_pay_gates'][0] );

        } else {

            $payment_gateways = pms_get_payment_gateways();

            // Set default payment gateway
            $default_gateway  = ( !empty( $pms_settings['payments']['default_payment_gateway'] ) ? ( in_array( $pms_settings['payments']['default_payment_gateway'], $pms_settings['payments']['active_pay_gates'] ) ? $pms_settings['payments']['default_payment_gateway'] : $pms_settings['payments']['active_pay_gates'][0] ) : 'paypal_standard' );
            $default_gateway  = ( !empty( $_POST['pay_gate'] ) ? esc_attr( $_POST['pay_gate'] ) : $default_gateway );

            // Output content for all payment gateways
            $output .= '<div id="pms-paygates-wrapper">';

                $output .= apply_filters( 'pms_get_output_payment_gateways_before', '<h3>' . __( 'Select a Payment Method', 'paid-member-subscriptions' ) . '</h3>', $pms_settings );

                if( !empty( $pms_settings['payments']['active_pay_gates'] ) ) {
                    foreach( $pms_settings['payments']['active_pay_gates'] as $paygate_key ) {

                        // Check to see if the gateway exists
                        if( empty( $payment_gateways[$paygate_key] ) )
                            continue;

                        $output .= '<label>';
                            $output .= apply_filters( 'pms_output_payment_gateway_input_radio', '<input type="radio" name="pay_gate" value="' . $paygate_key . '" ' . checked( $default_gateway, $paygate_key, false ) . ' />', $paygate_key );
                            $output .= '<span class="pms-paygate-name">' . $payment_gateways[$paygate_key]['display_name_user'] . '</span>';
                        $output .= '</label>';

                    }
                }

            $output .= '</div>';

        }

        return apply_filters( 'pms_get_output_payment_gateways', $output, $pms_settings );

    }

    /*
     * Function that outputs the payment gateway options after the subscription plans
     * radio buttons
     *
     * @return string
     *
     */
    function pms_output_subscription_plans_payment_gateways( $output, $include, $exclude_id_group, $member, $pms_settings ) {

        if( is_object( $member ) )
            return $output;

        $output .= pms_get_output_payment_gateways( $pms_settings );

        return $output;

    }
    add_filter( 'pms_output_subscription_plans', 'pms_output_subscription_plans_payment_gateways', 10, 5);