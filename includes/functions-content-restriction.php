<?php

    /**
     * Verifies whether the current post or the post with the provided id has any restrictions in place
     *
     * @param int $post_id
     *
     * @return bool
     *
     */
    function pms_is_post_restricted( $post_id = null ) {

        global $post, $pms_show_content, $pms_is_post_restricted_arr;

        /**
         * If we have a cached result, return it
         */
        if( isset( $pms_is_post_restricted_arr[$post_id] ) )
            return $pms_is_post_restricted_arr[$post_id];

        $post_obj = $post;

        if( ! is_null( $post_id ) )
            $post_obj = get_post( $post_id );

        /**
         * This filter was added in order to take advantage of the existing functions that hook to the_content
         * and check to see if the post is restricted or not.
         *
         * We don't need the returned value, just the value of the global $pms_show_content, which is modified
         * in the functions mentioned above
         *
         */
        $t = apply_filters( 'pms_post_restricted_check', '', $post_obj );

        /**
         * Cache the result for further usage
         */
        if( $pms_show_content === false )
            $pms_is_post_restricted_arr[$post_id] = true;
        else
            $pms_is_post_restricted_arr[$post_id] = false;

        // Return
        return $pms_is_post_restricted_arr[$post_id];

    }


    /**
     * Returns the restriction message added by the admin in the settings page or a default message if the first one is missing
     *
     * @param string $type      - whether the message is for logged out users or non-members
     * @param int    $post_id   - optional, the id of the current post
     *
     * @return string
     *
     */
    function pms_get_restriction_content_message( $type = '', $post_id = 0 ) {

        $settings = get_option( 'pms_settings' );
        $message  = '';

        // Set the default message from the Settings page
        if( $type == 'logged_out' ){
            $message = isset( $settings['messages']['logged_out']) ? $settings['messages']['logged_out'] : __( 'You do not have access to this content. You need to create an account.', 'paid-member-subscriptions' );
        } elseif( $type == 'non_members' ){
            $message = isset( $settings['messages']['non_members']) ? $settings['messages']['non_members'] : __( 'You do not have access to this content. You need the proper subscription.', 'paid-member-subscriptions' );
        } else{
            $message = apply_filters('pms_get_restriction_content_message_default', $message, $type, $settings);
        }

        // Overwrite if there is a custom message set for the post
        $custom_message_enabled = get_post_meta( $post_id, 'pms-content-restrict-messages-enabled', true );

        if( ! empty( $post_id ) && ! empty( $custom_message_enabled ) ) {

            $custom_message = get_post_meta( $post_id, 'pms-content-restrict-message-' . $type, true );

            if( ! empty( $custom_message ) )
                $message = $custom_message;

        }

        return wp_kses_post($message);
    }


    /**
     * Returns the restriction message with any tags processed
     *
     * @param string $type
     * @param int    $user_ID
     * @param int    $post_id - optional
     *
     * @return string
     *
     */
    function pms_process_restriction_content_message( $type, $user_ID, $post_id = 0 ) {

        $message    = pms_get_restriction_content_message( $type, $post_id );
        $user_info  = get_userdata( $user_ID );
        $message    = PMS_Merge_Tags::pms_process_merge_tags( $message, $user_info, '' );

        return $message;
    }


    /**
     * Return the restriction message to be displayed to the user. If the current post is not restricted / it was not checked
     * to see if it is restricted an empty string is returned
     *
     * @param int $post_id
     *
     * @return string
     *
     */
    function pms_get_restricted_post_message( $post_id = 0 ) {

        global $post, $user_ID, $pms_show_content;

        if( ! empty( $post_id ) )
            $post = get_post( $post_id );

        /**
         * If the $pms_show_content global is different than false then the post is either
         * not restricted or not processed for restriction
         *
         */
        if( $pms_show_content !== false )
            return '';

        if( ! is_user_logged_in() )
            $message_type = 'logged_out';
        else
            $message_type = 'non_members';

        $message_type = apply_filters('pms_get_restricted_post_message_type', $message_type);


        $message = pms_process_restriction_content_message( $message_type, $user_ID, $post->ID );

        /**
         * Filter the restriction message before returning it
         *
         * @param string $message  - the custom message set by the admin in the Messages tab of the Settings page. If no messages are set there a default is returned
         * @param string $content  - the content of the current $post object
         * @param WP_Post $post    - the current post object
         * @param int $user_ID     - the current user id
         *
         */
        $message = apply_filters( 'pms_restriction_message_' . $message_type, $message, $post->post_content, $post, $user_ID );

        return do_shortcode( $message );

    }


    /**
     * Checks to see if the current post is restricted and if any redirect URLs are in place
     * the user is redirected to the URL with the highest priority
     *
     */
    function pms_restricted_post_redirect() {

        if( ! is_singular() )
            return;

        global $post;


        $redirect_url             = '';
        $post_restriction_type    = get_post_meta( $post->ID, 'pms-content-restrict-type', true );
        $settings                 = get_option( 'pms_settings', array() );
        $general_restriction_type = ( ! empty( $settings['content_restrict_type'] ) ? $settings['content_restrict_type'] : 'message' );

        if( $post_restriction_type !== 'redirect' && $general_restriction_type !== 'redirect' )
            return;

        if( ! in_array( $post_restriction_type, array( 'default', 'redirect' ) ) )
            return;

        if( ! pms_is_post_restricted( $post->ID ) )
            return;

        /**
         * Get the redirect URL from the post meta if enabled
         *
         */
        if( $post_restriction_type === 'redirect' ) {

            $post_redirect_url_enabled = get_post_meta( $post->ID, 'pms-content-restrict-custom-redirect-url-enabled', true );
            $post_redirect_url         = get_post_meta( $post->ID, 'pms-content-restrict-custom-redirect-url', true );

            $redirect_url = ( ! empty( $post_redirect_url_enabled ) && ! empty( $post_redirect_url ) ? $post_redirect_url : '' );

        }
        

        /**
         * If the post doesn't have a custom redirect URL set, get the default from the Settings page
         *
         */
        if( empty( $redirect_url ) ) {

            $redirect_url = ( ! empty( $settings['content_restrict_redirect_url'] ) ? $settings['content_restrict_redirect_url'] : '' );

        }

        if( empty( $redirect_url ) )
            return;

        /**
         * To avoid a redirect loop we break in case the redirect URL is the same as
         * the current page URl
         *
         */
        $current_url = pms_get_current_page_url();

        if( $current_url == $redirect_url )
            return;

        /**
         * Redirect
         *
         */
        wp_redirect( $redirect_url );
        exit;

    }
    add_action( 'wp', 'pms_restricted_post_redirect' );