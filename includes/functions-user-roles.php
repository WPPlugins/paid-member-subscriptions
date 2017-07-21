<?php


    /*
     * Function that returns an array with the user roles slugs and names with the exception
     * of the ones created by the subscription plans
     *
     * @return array
     *
     */
    function pms_get_user_role_names() {

        global $wp_roles;

        // This will be returned at the end
        $role_names = array();
        $wp_roles_names = array_reverse( $wp_roles->role_names );

        foreach( $wp_roles_names as $role_slug => $role_name ) {

            // Evade administrators
            if( $role_slug == 'administrator' )
                continue;

            // Escape user roles created from subscription plans
            if( strpos( $role_slug, 'pms_subscription_plan_' ) !== false )
                continue;

            $role_names[ $role_slug ] = $role_name;

        }

        return $role_names;
    }


    /*
     * Return a user role name by its slug
     *
     * @param string $role_slug
     *
     */
    function pms_get_user_role_name( $role_slug = '' ) {

        global $wp_roles;

        return ( isset( $wp_roles->role_names[ $role_slug ] ) ? $wp_roles->role_names[ $role_slug ] : '' );
    }


    /*
     * Function that checks to see if a user role exists
     *
     * @return bool
     *
     */
    function pms_user_role_exists( $role_slug = '' ) {

        global $wp_roles;

        if( isset( $wp_roles->role_names[$role_slug] ) )
            return true;
        else
            return false;

    }


    function pms_get_user_roles_by_plan_ids( $id_or_ids ) {

        if( is_array( $id_or_ids ) ) {

            $return = array();

            foreach( $id_or_ids as $id )
                $return[$id] = get_post_meta( $id, 'pms_subscription_plan_user_role', true );

        } else {

            $return = get_post_meta( $id_or_ids, 'pms_subscription_plan_user_role', true );;

        }

        return $return;

    }


    /*
     * Add a new user role to an existing user
     *
     * @param int $user_id
     * @param string $user_role
     *
     */
    function pms_add_user_role( $user_id = 0, $user_role = '' ) {

        if( $user_id == 0 )
            return;

        if( empty($user_role) )
            return;

        global $wp_roles;

        if( !isset( $wp_roles->role_names[$user_role] ) )
            return;

        $user = new WP_User( $user_id );
        $user->add_role( $user_role );

    }


    /*
     * Remove a new user role to an existing user
     *
     * @param int $user_id
     * @param string $user_role
     *
     */
    function pms_remove_user_role( $user_id = 0, $user_role = '' ) {

        if( $user_id == 0 )
            return;

        if( empty($user_role) )
            return;

        $user = new WP_User( $user_id );
        $user->remove_role( $user_role );

        if( empty($user->roles) )
            $user->add_role( 'subscriber' );

    }


    /*
     * Add user role to a member when the member gets subscribed to a new subscription plan
     *
     */
    function pms_member_add_user_role( $db_action_result, $user_id, $subscription_plan_id, $start_date, $expiration_date, $status ) {

        if( !$db_action_result )
            return;

        if( $status != 'active' )
            return;

        pms_add_user_role( $user_id, get_post_meta( $subscription_plan_id, 'pms_subscription_plan_user_role', true ) );

    }
    add_action( 'pms_member_add_subscription', 'pms_member_add_user_role', 10, 6 );
    add_action( 'pms_member_update_subscription', 'pms_member_add_user_role', 10, 6 );


    /*
     * Remove user role when a members subscription gets removed
     *
     */
    function pms_member_remove_user_role( $delete_result, $user_id, $subscription_plan_id ) {

        if( !$delete_result )
            return;

        $member = pms_get_member($user_id);

        $subscription_plan_user_role = pms_get_user_roles_by_plan_ids( $subscription_plan_id );

        if( in_array( $subscription_plan_user_role, pms_get_user_roles_by_plan_ids( $member->get_subscriptions_ids() ) ) )
            return;

        pms_remove_user_role( $user_id, $subscription_plan_user_role );

    }
    add_action( 'pms_member_remove_subscription', 'pms_member_remove_user_role', 10, 3 );


    /*
     * Removes a user role and adds a new one when the a member gets a subscription replaced with another one
     *
     */
    function pms_member_replace_user_role( $update_result, $user_id, $new_subscription_plan_id, $old_subscription_plan_id ) {

        if( !$update_result )
            return;

        // Remove the member's user role corresponding to the old subscription plan
        pms_member_remove_user_role( $update_result, $user_id, $old_subscription_plan_id );

        // Add new role based on the new subscription plan the user has
        $member       = pms_get_member( $user_id );
        $subscription = $member->get_subscription( $new_subscription_plan_id );

        if( $subscription['status'] == 'active' )
            pms_add_user_role( $user_id, pms_get_user_roles_by_plan_ids( $new_subscription_plan_id ) );

    }
    add_action( 'pms_member_replace_subscription', 'pms_member_replace_user_role', 10, 4 );