<?php
/*
 * Emails Class contains the necessary functions for sending emails to users
 *
 */
Class PMS_Emails{

    /* constructor for the class where we hook in the actions to send emails */
    static function init() {
        add_action( 'pms_register_form_after_create_user', array( 'PMS_Emails', 'send_registration_email' ) );

        add_action( 'pms_member_update_subscription', array( 'PMS_Emails', 'send_emails' ), 10, 6 );
        add_action( 'pms_member_add_subscription', array( 'PMS_Emails', 'send_emails' ), 10, 6 );

    }

    /**
     * Function that sends the email based on status
     * @param $update_result boolean if the update action was successfully
     * @param $user_id int the id of the user
     * @param $subscription_plan_id int the subscription id
     * @param $start_date
     * @param $expiration_date
     * @param $status string the new status of thte subscription
     */
    static function send_emails( $update_result, $user_id, $subscription_plan_id, $start_date, $expiration_date, $status ){
        if( $update_result ){
            if( $status == 'active' ){
                PMS_Emails::pms_mail( 'activate', $user_id, $subscription_plan_id, $start_date, $expiration_date );
            }
            else if( $status == 'canceled' ){
                PMS_Emails::pms_mail( 'cancel', $user_id, $subscription_plan_id, $start_date, $expiration_date );
            }
            else if( $status == 'expired' ){
                PMS_Emails::pms_mail( 'expired', $user_id, $subscription_plan_id, $start_date, $expiration_date );
            }
        }
    }

    static function send_registration_email( $user_data ){
        $wppb_addonOptions = get_option('wppb_module_settings');
        if( !( defined( 'PROFILE_BUILDER' ) && PROFILE_BUILDER == 'Profile Builder Pro' && !empty( $wppb_addonOptions['wppb_emailCustomizer'] ) && $wppb_addonOptions['wppb_emailCustomizer'] == 'show' ) )
            PMS_Emails::pms_mail( 'register', $user_data['user_id'], $user_data['subscriptions'][0] );
    }

    /**
     * Function that calls wp_mail after we decide what to send
     * @param $action
     * @param $user_id
     * @param $subscription_plan_id
     */
    static function pms_mail( $action, $user_id, $subscription_plan_id, $start_date = '', $expiration_date = '' ){

        $user_info = get_userdata( $user_id );

        $email_default_subjects = PMS_Emails::get_default_email_subjects();
        $email_default_content = PMS_Emails::get_default_email_content();

        $pms_settings = get_option( 'pms_settings' );
        if( !empty( $pms_settings['emails'][$action.'_sub_subject'] ) )
            $email_subject = $pms_settings['emails'][$action.'_sub_subject'];
        else
            $email_subject = $email_default_subjects[$action];

        if( !empty( $pms_settings['emails'][$action.'_sub'] ) )
            $email_content = $pms_settings['emails'][$action.'_sub'];
        else
            $email_content = $email_default_content[$action];

        $email_subject = PMS_Merge_Tags::pms_process_merge_tags( $email_subject, $user_info, $subscription_plan_id, $start_date, $expiration_date, $action );
        $email_content = nl2br( PMS_Merge_Tags::pms_process_merge_tags( $email_content, $user_info, $subscription_plan_id, $start_date, $expiration_date, $action ) );
        $email_content = do_shortcode( $email_content );

        // Filter the subject and the content before sending the mail
        $email_subject = apply_filters( 'pms_email_subject', $email_subject, $action, $user_info, $subscription_plan_id, $start_date, $expiration_date );
        $email_content = apply_filters( 'pms_email_content', $email_content, $action, $user_info, $subscription_plan_id, $start_date, $expiration_date );

        //we add this filter to enable html encoding
        add_filter( 'wp_mail_content_type', create_function('', 'return "text/html"; ') );

        // Temporary change the from name and from email
        add_filter('wp_mail_from_name', array( 'PMS_Emails', 'pms_email_website_name' ), 20, 1);
        add_filter('wp_mail_from', array( 'PMS_Emails', 'pms_email_website_email' ), 20, 1);

        // Send email
        wp_mail( $user_info->user_email, $email_subject, $email_content );

        // Reset the from name and email
        remove_filter('wp_mail_from_name', array( 'PMS_Emails', 'pms_email_website_name' ), 20 );
        remove_filter('wp_mail_from', array( 'PMS_Emails', 'pms_email_website_email' ), 20 );
    }


    /**
     * Function that returns the possible email actions
     * @return mixed|void
     */
    static function get_email_actions(){
        $email_actions = array( 'register', 'activate', 'cancel', 'expired' );
        return apply_filters( 'pms_email_actions', $email_actions );
    }

    /**
     * Function that returns the general email option defaults
     * @return mixed
     */
    static function get_email_general_options(){
        $email_options = array(
                'email-from-name' => get_bloginfo('name'),
                'email-from-email' => get_bloginfo('admin_email'),
        );
        return apply_filters( 'pms_email_general_options_defaults', $email_options );
    }

    /**
     * The headers fot the emails in the settings page
     */
    static function get_email_headings(){
        $email_headings = array(
            'register' => __( 'Register Email', 'paid-member-subscriptions' ),
            'activate' => __( 'Activate Subscription Email', 'paid-member-subscriptions' ),
            'cancel' => __( 'Cancel Subscription Email', 'paid-member-subscriptions' ),
            'expired' => __( 'Expired Subscription Email', 'paid-member-subscriptions' )
        );
        return apply_filters( 'pms_email_headings', $email_headings );
    }

    /**
     * The function that returns the default email subjects
     */
    static function get_default_email_subjects(){
        $email_subjects = array(
            'register' => __( 'You have a new account', 'paid-member-subscriptions' ),
            'activate' => __( 'Your Subscription is now active', 'paid-member-subscriptions' ),
            'cancel' => __( 'Your Subscription has been canceled', 'paid-member-subscriptions' ),
            'expired' => __( 'Your Subscription has expired', 'paid-member-subscriptions' )
        );
        return apply_filters( 'pms_default_email_subjects', $email_subjects );
    }

    /**
     * The function that returns the default email contents
     */
    static function get_default_email_content(){
        $email_content = array(
            'register' => __( 'Congratulations {{display_name}}! You have successfully created an account!', 'paid-member-subscriptions' ),
            'activate' => __( 'Congratulations {{display_name}}! The "{{subscription_name}}" plan has been successfully activated.', 'paid-member-subscriptions' ),
            'cancel' => __( 'Hello {{display_name}}, The "{{subscription_name}}" plan has been canceled.', 'paid-member-subscriptions' ),
            'expired' => __( 'Hello {{display_name}},The "{{subscription_name}}" plan has expired.', 'paid-member-subscriptions' )
        );
        return apply_filters( 'pms_default_email_content', $email_content );
    }

    // function that filters the From name
    static function pms_email_website_name( $site_name ){
        $pms_settings = get_option( 'pms_settings' );

        if ( !empty( $pms_settings['emails']['email-from-name'] ) ){
            $site_name = $pms_settings['emails']['email-from-name'];
        }
        else{
            $site_name = get_bloginfo('name');
        }

        return $site_name;
    }

    // function that filters the From email address
    static function pms_email_website_email( $sender_email ){
        $pms_settings = get_option( 'pms_settings' );

        if ( !empty( $pms_settings['emails']['email-from-email'] ) ){
                if( is_email( $pms_settings['emails']['email-from-email'] ) )
                    $sender_email = $pms_settings['emails']['email-from-email'];
        }
        else{
            $sender_email = get_bloginfo('admin_email');
        }

        return $sender_email;
    }

}

PMS_Emails::init();
