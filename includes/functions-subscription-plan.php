<?php

/*
 * Functions for subscription plan things
 *
 */


    /*
     * Main function to return a products
     *
     * @param $id_or_post   - post ID or post of the subscription plan
     *
     * @return PMS_Subscription_Plan
     *
     */
    function pms_get_subscription_plan( $id_or_post ) {
        return new PMS_Subscription_Plan( $id_or_post );
    }


    /*
     * Returns all subscription plans into an array of objects
     *
     * @param $only_active   - true to return only active subscription plans, false to return all
     *
     * @return array
     *
     */
    function pms_get_subscription_plans( $only_active = true, $include = array() ) {

        $subscription_plans = array();
        $subscription_plan_post_ids = array();

        if( empty( $include ) ) {

            $subscription_plan_posts = get_posts( array('post_type' => 'pms-subscription', 'numberposts' => -1, 'post_status' => 'any' ) );

            $page_hierarchy_posts = get_page_hierarchy( $subscription_plan_posts );

            foreach( $page_hierarchy_posts as $post_id => $post_name ) {
                $subscription_plan_post_ids[] = $post_id;
            }

        } else {

            $subscription_plan_posts = get_posts( array('post_type' => 'pms-subscription', 'numberposts' => -1, 'include' => $include, 'orderby' => 'post__in', 'post_status' => 'any' ) );
            $subscription_plan_post_ids = $subscription_plan_posts;

        }

        // Return if we don't have any plans by now
        if( empty( $subscription_plan_post_ids ) )
            return $subscription_plans;


        foreach( $subscription_plan_post_ids as $subscription_plan_post_id ) {
            $subscription_plan = pms_get_subscription_plan( $subscription_plan_post_id );

            if( $only_active && !$subscription_plan->is_active() )
                continue;

            $subscription_plans[] = $subscription_plan;
        }

        return $subscription_plans;

    }


    function pms_get_subscription_plan_groups_parent_ids() {

        $parent_ids = array();

        $subscription_plan_posts = get_posts( array( 'post_type' => 'pms-subscription', 'numberposts' => -1, 'post_parent' => 0, 'post_status' => 'any' ) );

        if( !empty( $subscription_plan_posts ) ) {
            foreach( $subscription_plan_posts as $subscription_plan_post ) {
                $parent_ids[] = $subscription_plan_post->ID;
            }
        }

        return $parent_ids;

    }


    function pms_get_subscription_plans_group_parent_id( $subscription_plan_id ) {

        $ancestors_ids = get_post_ancestors( $subscription_plan_id );

        if( !empty( $ancestors_ids ) )
            $top_parent_id = $ancestors_ids[ count( $ancestors_ids ) - 1 ];
        else
            $top_parent_id = $subscription_plan_id;

        return $top_parent_id;

    }


    function pms_get_subscription_plans_group( $subscription_plan_id, $only_active = true, $ascending = false ) {

        $top_parent_id = pms_get_subscription_plans_group_parent_id( $subscription_plan_id );

        // Add top most parent
        $subscription_plan_posts[] = get_post( $top_parent_id );

        // Add all the children in the group
        while( ( $subscription_plan_downgrade = get_posts( array('post_type' => 'pms-subscription', 'numberposts' => -1, 'post_parent' => $top_parent_id, 'order' => 'DESC', 'orderby' => 'parent', 'post_status' => 'any' ) ) ) != null ) {

            $top_parent_id = $subscription_plan_downgrade[0]->ID;
            $subscription_plan_posts[] = $subscription_plan_downgrade[0];

        }

        $subscription_plans = array();

        if( !empty( $subscription_plan_posts ) ) {
            foreach( $subscription_plan_posts as $subscription_plan_post ) {
                $subscription_plan = pms_get_subscription_plan( $subscription_plan_post );

                if( $only_active && !$subscription_plan->is_active() )
                    continue;

                $subscription_plans[] = $subscription_plan;
            }
        }

        if( $ascending == true )
            $subscription_plans = array_reverse( $subscription_plans );

        return $subscription_plans;

    }


    /**
     * Returns an array of PMS_Subscription_Plan objects that are possible upgrades for the given
     * subscription_plan_id
     *
     * @param int  $subscription_plan_id - the id of the subscription plan for which we want to receive the possible upgrades
     * @param bool $only_active          - whether to return only active subscription plans or no
     *
     */
    function pms_get_subscription_plan_upgrades( $subscription_plan_id, $only_active = true ) {

        $current_post   = get_post( $subscription_plan_id );
        $parent_post_id = $current_post->post_parent;

        $subscription_plan_posts = array();

        while( $post_ancestor = get_post( $parent_post_id ) ) {

            $parent_post_id = $post_ancestor->post_parent;
            $subscription_plan_posts[] = $post_ancestor;

            if( empty( $post_ancestor->post_parent ) )
                break;

        }

        $subscription_plans = array();

        if( !empty( $subscription_plan_posts ) ) {
            foreach( $subscription_plan_posts as $subscription_plan_post ) {

                $subscription_plan = pms_get_subscription_plan( $subscription_plan_post );

                if( $only_active && !$subscription_plan->is_active() )
                    continue;

                $subscription_plans[] = $subscription_plan;

            }
        }

        /**
         * Filter the subscription plans available for upgrade just before returning them
         *
         * @param array $subscription_plans
         * @param int   $subscription_plan_id
         * @param bool  $only_active
         *
         */
        return apply_filters( 'pms_get_subscription_plan_upgrades', $subscription_plans, $subscription_plan_id, $only_active );

    }


    /*
     * Returns the user role associated with the subscription plan
     *
     * @param int $subscription_plan_id
     *
     * @return string
     *
     */
    function pms_get_subscription_plan_user_role( $subscription_plan_id ) {

        $user_role = get_post_meta( $subscription_plan_id, 'pms_subscription_plan_user_role', true );

        if( empty($user_role) )
            $user_role = get_option('default_role');

        return $user_role;

    }


    /*
     * Function that outputs the subscription plans
     *
     * Warning: Should not be used by other plugin developers as it is subject to change
     *
     * @param array $include            - return only these subscription plans
     * @param array $exclude_id_group   - exclude the groups that have these ids
     * @param mixed $member             - bool false to display input fields, object PMS_Member to display member information
     * @param int $default_checked      - default subscription plan to be selected
     *
     * @return string
     *
     */
    function pms_output_subscription_plans( $include = array(), $exclude_id_group = array(), $member = false, $default_checked = '' ) {

        $output = '';
        $pms_settings = get_option( 'pms_settings' );


        // Get all subscription plans
        if( empty( $include ) )
            $subscription_plans = pms_get_subscription_plans();
        else {
            if( !is_object( $include[0] ) )
                $subscription_plans = pms_get_subscription_plans( true, $include );
            else
                $subscription_plans = $include;
        }


        /*
         * Group subscription plans
         */
        $subscription_plan_groups = array();

        if( !empty( $subscription_plans ) ) {
            foreach( $subscription_plans as $subscription_plan ) {
                $subscription_plan_groups[ $subscription_plan->top_parent ][] = $subscription_plan;
            }
        }


        /*
         * Exclude certain groups like the ones the member is already subscribed to
         */
        if( !empty( $exclude_id_group ) ) {
            foreach( $exclude_id_group as $exclude_id ) {

                if( !empty( $subscription_plans ) ) {
                    foreach( $subscription_plans as $subscription_plan ) {

                        if( $subscription_plan->id == $exclude_id ) {
                            if( isset( $subscription_plan_groups[ $subscription_plan->top_parent ] ) )
                                unset( $subscription_plan_groups[ $subscription_plan->top_parent ] );
                        }

                    }
                }

            }
        }

        /*
         * Display the information for each plan
         */
        if( !empty( $subscription_plan_groups ) ) {

            if( !$member && count( $subscription_plan_groups ) == 1 && count( $subscription_plan_groups[ key($subscription_plan_groups) ] ) == 1 ) {

                $subscription_plan = $subscription_plan_groups[ key($subscription_plan_groups) ][0];

                // Output subscription plan wrapper
                $subscription_plan_output = '<div class="pms-subscription-plan pms-hidden">';

                // Output subscription plan hidden input and label
                $subscription_plan_output .= '<input type="hidden" name="subscription_plans" data-price="'. esc_attr($subscription_plan->price) .'" data-duration="'. esc_attr($subscription_plan->duration) .'" value="' . esc_attr( $subscription_plan->id ) . '" />';
                $subscription_plan_output .= '<label>' . $subscription_plan->name . '</label>';

                // Output subscription plan price
                $subscription_plan_output .= pms_get_output_subscription_plan_price( $subscription_plan );

                // Description
                if( !empty($subscription_plan->description) )
                    $subscription_plan_output .= '<div class="pms-subscription-plan-description">' . $subscription_plan->description . '</div>';

                $subscription_plan_output .= '</div>';

                // Modify the entire subscription plan output if desired
                $output .= apply_filters( 'pms_subscription_plan_output', $subscription_plan_output, $subscription_plan );

            } else {

                $current_group = 1;
                $group_count = count( $subscription_plan_groups );

                // If there's only one active subscription, select it by default
                if (count($subscription_plans) == 1) {
                    $default_checked = $subscription_plans[0]->id;
                }

                foreach( $subscription_plan_groups as $top_parent_id => $subscription_plans ) {

                    /*
                     * Output subscription plan fields for forms
                     */
                    if( !$member ) {

                        foreach( $subscription_plans as $subscription_plan ) {

                            // Output subscription plan wrapper
                            $subscription_plan_output = '<div class="pms-subscription-plan">';

                            // Output subscription plan radio button and label
                            $subscription_plan_output .= '<label>';
                            $subscription_plan_output .= '<input type="radio" name="subscription_plans" data-price="'. esc_attr( $subscription_plan->price ) .'" data-duration="'. esc_attr( $subscription_plan->duration ) .'" value="' . esc_attr( $subscription_plan->id ) . '" ' .  ( isset( $_REQUEST['subscription_plans'] ) ? checked( $_REQUEST['subscription_plans'], $subscription_plan->id, false ) : ( $default_checked == $subscription_plan->id ? 'checked="checked"' : '' ) ) . ' />';
                            $subscription_plan_output .= apply_filters( 'pms_output_subscription_plan_name', esc_html( $subscription_plan->name ), $subscription_plan ) . '</label>';

                            // Output subscription plan price
                            $subscription_plan_output .= pms_get_output_subscription_plan_price( $subscription_plan );

                            // Description
                            if( !empty($subscription_plan->description) )
                                $subscription_plan_output .= '<div class="pms-subscription-plan-description">' . apply_filters( 'pms_output_subscription_plan_description', esc_html( $subscription_plan->description ), $subscription_plan )  . '</div>';

                            $subscription_plan_output .= '</div>';

                            // Modify the entire subscription plan output if desired
                            $output .= apply_filters( 'pms_subscription_plan_output', $subscription_plan_output, $subscription_plan );

                        }

                        /*
                         * Output subscription plans with different action
                         */
                    } else {

                        foreach( $subscription_plans as $subscription_plan ) {
                            $output .= '<div class="pms-subscription-plan pms-subscription-plan-has-actions' . ($current_group == $group_count ? ' pms-last' : '') . '">';

                            // Get member subscription data
                            $member_subscription = $member->get_subscription( $subscription_plan->id );

                            // Subscription plan name
                            $output .= '<span class="pms-subscription-plan-name">' . $subscription_plan->name . '</span>';

                            // Subscription plan expiration date
                            $date_format = apply_filters( 'pms_output_subscription_plan_date_format', get_option('date_format') );
                            $expiration_time_stamp = strtotime( pms_sanitize_date($member_subscription['expiration_date']) );

                            if( $expiration_time_stamp > time() + 60 * 60 * 24 * 365 * 5 )
                                $expiration_date_output = __( 'Unlimited', 'paid-member-subscriptions' );
                            else
                                $expiration_date_output = date( $date_format, $expiration_time_stamp );

                            // If member subscription has expired display message
                            if( $member_subscription['status'] == 'expired' )
                                $expiration_date_output = __( 'Expired on: ', 'paid-member-subscriptions' ) . esc_html( $expiration_date_output );

                            $output .= '<span class="pms-subscription-plan-expiration">' . apply_filters( 'pms_output_subscription_plan_expiration_date', $expiration_date_output, $subscription_plan, $member_subscription, $member->user_id ) . '</span>';

                            // Subscription plan actions
                            $output .= '<span class="pms-subscription-plan-actions">';

                                // Add extra subscription plan actions at the beginning
                                $output .= apply_filters( 'pms_output_subscription_plan_before_actions', '', $subscription_plan, $member_subscription );

                                if( $member_subscription['status'] != 'pending' ) {

                                    // Get plan upgrades
                                    $plan_upgrades = pms_get_subscription_plan_upgrades( $subscription_plan->id );
                                    if( !empty($plan_upgrades) )
                                        $output .= apply_filters( 'pms_output_subscription_plan_action_upgrade', '<a href="' . esc_url( wp_nonce_url( add_query_arg( array( 'pms-action' => 'upgrade_subscription', 'subscription_plan' => $subscription_plan->id ) ), 'pms_member_nonce', 'pmstkn' ) ) . '">' . __( 'Upgrade', 'paid-member-subscriptions' ) . '</a>', $subscription_plan, $member_subscription, $member->user_id );

                                    // Number of days before expiration to show the renewal action
                                    $renewal_display_time = apply_filters( 'pms_output_subscription_plan_action_renewal_time', 15 );

                                    if( empty( $member_subscription['payment_profile_id'] ) && strtotime( $member_subscription['expiration_date'] ) - time() < $renewal_display_time * 86400 )
                                        $output .= apply_filters( 'pms_output_subscription_plan_action_renewal', '<a href="' . esc_url( wp_nonce_url( add_query_arg( array( 'pms-action' => 'renew_subscription', 'subscription_plan' => $subscription_plan->id ) ), 'pms_member_nonce', 'pmstkn' ) ) . '">' . __( 'Renew', 'paid-member-subscriptions' ) . '</a>', $subscription_plan, $member_subscription, $member->user_id );

                                } else {

                                    $output .= apply_filters( 'pms_output_subscription_plan_pending_message', '<div><i>' . __( 'Pending subscription', 'paid-member-subscriptions' ) . '</i></div>', $subscription_plan, $member_subscription );

                                    if( $subscription_plan->price > 0 )
                                        $output .= apply_filters( 'pms_output_subscription_plan_pending_retry_payment', '<a href="' . esc_url( wp_nonce_url( add_query_arg( array( 'pms-action' => 'retry_payment_subscription', 'subscription_plan' => $subscription_plan->id  ) ), 'pms_member_nonce', 'pmstkn' ) ) . '">' . __( 'Retry payment', 'paid-member-subscriptions' ) . '</a>', $subscription_plan, $member_subscription );

                                }

                                if( ( ! empty( $member_subscription['payment_profile_id'] ) && pms_is_https() ) || empty( $member_subscription['payment_profile_id'] ) )
                                    $output .= apply_filters( 'pms_output_subscription_plan_action_cancel', '<a href="' . esc_url( wp_nonce_url( add_query_arg( array( 'pms-action' => 'cancel_subscription', 'subscription_plan' => $subscription_plan->id  ) ), 'pms_member_nonce', 'pmstkn' ) ) . '">' . __( 'Cancel', 'paid-member-subscriptions' ) . '</a>', $subscription_plan, $member_subscription, $member->user_id );

                                // Add extra subscription plan actions at the end
                                $output .= apply_filters( 'pms_output_subscription_plan_after_actions', '', $subscription_plan, $member_subscription );

                            $output .= '</span>';

                            $output .= '</div>';

                        }

                    }


                    $current_group++;
                }

            }

        }

        // Add error message if no plans have been selected
        if( !$member )
            $output .= pms_display_field_errors( pms_errors()->get_error_messages('subscription_plans'), true );


        // Add header to the plans
        if( $member )
            $output = '<div class="pms-subscription-plans-header"><span class="pms-subscription-plan-name">' . apply_filters( 'pms_subscription_plans_header_plan_name', __( 'Subscription' , 'paid-member-subscriptions' ) ) . '</span><span class="pms-subscription-plan-expiration">' . apply_filters( 'pms_subscription_plans_header_plan_expiration', __( 'Expires', 'paid-member-subscriptions' ) ) . '</span></div>' . $output;

        return apply_filters( 'pms_output_subscription_plans', $output, $include, $exclude_id_group, $member, $pms_settings, $subscription_plans );

    }


    /**
     * Returns the HTML output for the subscription plan price
     *
     * @param object $subscription_plan
     *
     * @return string
     *
     */
    function pms_get_output_subscription_plan_price( $subscription_plan ) {

        // Handle the subscription plan price
        if( $subscription_plan->price == 0 )
            $price_output = '<span class="pms-subscription-plan-price">' . __( 'Free', 'paid-member-subscriptions' ) . '</span>';

        else
            $price_output = pms_format_price( $subscription_plan->price, pms_get_active_currency(), array( 'before_price' => '<span class="pms-subscription-plan-price">', 'after_price' => '</span>', 'before_currency' => '<span class="pms-subscription-plan-currency">', 'after_currency' => '</span>' ) );
        

        $price_output = apply_filters( 'pms_subscription_plan_output_price', '<span class="pms-divider"> - </span>' . $price_output, $subscription_plan );

        // Handle the subscription plan duration
        if( $subscription_plan->duration == 0 )
            $duration_output = apply_filters( 'pms_subscription_plan_output_duration_unlimited', '', $subscription_plan );
        else {
            $duration = '';
            switch ($subscription_plan->duration_unit) {
                case 'day':
                    $duration = sprintf( _n( '%s Day', '%s Days', $subscription_plan->duration, 'paid-member-subscriptions' ), $subscription_plan->duration );
                    break;
                case 'week':
                    $duration = sprintf( _n( '%s Week', '%s Weeks', $subscription_plan->duration, 'paid-member-subscriptions' ), $subscription_plan->duration );
                    break;
                case 'month':
                    $duration = sprintf( _n( '%s Month', '%s Months', $subscription_plan->duration, 'paid-member-subscriptions' ), $subscription_plan->duration );
                    break;
                case 'year':
                    $duration = sprintf( _n( '%s Year', '%s Years', $subscription_plan->duration, 'paid-member-subscriptions' ), $subscription_plan->duration );
                    break;
            }

            $duration_output = apply_filters('pms_subscription_plan_output_duration_limited', '<span class="pms-divider"> / </span>' . $duration, $subscription_plan);
        }

        $duration_output = apply_filters( 'pms_subscription_plan_output_duration', $duration_output, $subscription_plan );

        // Return output
        return $price_output . $duration_output;

    }