<?php

/*
 * Extends core PMS_Submenu_Page base class to create and add custom functionality
 * for the members section in the admin section
 *
 */
Class PMS_Submenu_Page_Members extends PMS_Submenu_Page {

    /**
     * Request data
     *
     * @access public
     * @var array
     */
    public $request_data = array();


    /*
     * Method that initializes the class
     *
     */
    public function init() {

        $this->request_data = $_REQUEST;

        // Enqueue admin scripts
        add_action( 'pms_submenu_page_enqueue_admin_scripts_' . $this->menu_slug, array( $this, 'admin_scripts' ) );

        // Hook the validation of the data early on the init
        add_action( 'init', array( $this, 'process_data' ) );

        // Hook the output method to the parent's class action for output instead of overwriting the
        // output method
        add_action( 'pms_output_content_submenu_page_' . $this->menu_slug, array( $this, 'output' ) );

        // Add ajax hooks
        add_action( 'wp_ajax_populate_expiration_date', array( $this, 'ajax_populate_expiration_date' ) );

        add_action( 'wp_ajax_member_add_update_subscription', array( $this, 'ajax_member_add_update_subscription' ) );

    }



    /*
     * Method to enqueue admin scripts
     *
     */
    public function admin_scripts() {

        wp_enqueue_script( 'jquery-ui-datepicker' );
        wp_enqueue_style('jquery-style', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');

        global $wp_scripts;

        // Try to detect if chosen has already been loaded
        $found_chosen = false;

        foreach( $wp_scripts as $wp_script ) {
            if( !empty( $wp_script['src'] ) && strpos($wp_script['src'], 'chosen') !== false )
                $found_chosen = true;
        }

        if( !$found_chosen ) {
            wp_enqueue_script( 'pms-chosen', PMS_PLUGIN_DIR_URL . 'assets/libs/chosen/chosen.jquery.min.js', array( 'jquery' ), PMS_VERSION );
            wp_enqueue_style( 'pms-chosen', PMS_PLUGIN_DIR_URL . 'assets/libs/chosen/chosen.css', array(), PMS_VERSION );
        }

    }


    /*
     * Method that processes data on members admin pages
     *
     */
    public function process_data() {

        if( !isset( $_REQUEST['_wpnonce'] ) || !wp_verify_nonce( $_REQUEST['_wpnonce'], 'pms_member_nonce' ) )
            return;

        $request_data = $this->request_data;

        $action = $this->request_data['pms-action'];


        // Get member
        if( isset( $request_data['member_id'] ) )
            $member = pms_get_member( $request_data['member_id'] );



        /*
         * Remove a subscription plan from the member
         */
        if( $action == 'member_remove_subscription_plan' && isset( $member ) && $member->is_member() ) {

            // Get the page we came from
            $redirect_url = wp_get_referer();

            $remove = false;

            if( isset( $request_data['subscription_plan_id'] ) && !empty( $request_data['subscription_plan_id'] ) )
                $remove = $member->remove_subscription( (int)trim( $request_data['subscription_plan_id'] ) );

            if( $member->get_subscriptions_count() == 1 )
                $redirect_url = remove_query_arg( array( 'pms-action', 'member_id' ), $redirect_url );

            wp_safe_redirect( $redirect_url );

        }


        /*
         * Bulk add subscription plan to users
         */
        if( $action == 'add_new_members_bulk' ) {

            if( !empty( $this->request_data['subscription_plan_id'] ) && trim( $this->request_data['subscription_plan_id'] ) != -1 && !empty( $this->request_data['users'] ) ) {

                // Get subscription plan object
                $subscription_plan = pms_get_subscription_plan( trim( $this->request_data['subscription_plan_id'] ) );

                $added_members_count = 0;

                // Loop through every user id from the request
                foreach( $this->request_data['users'] as $user_id ) {

                    // Get member object
                    $member = pms_get_member( $user_id );

                    if( $member->is_member() )
                        continue;

                    $member->add_subscription( $subscription_plan->id, date('Y-m-d H:i:s'), $subscription_plan->get_expiration_date(), 'active' );
                    $added_members_count++;

                }

                if( $added_members_count != 0 )
                    $this->add_admin_notice( sprintf( __( '%d members successfully added.', 'paid-member-subscriptions' ), $added_members_count ), 'updated' );

            }

        }


        // Validate when adding a new member
        if( isset( $request_data['submit_add_new_member'] ) )
            $validation_response = $this->validate_data_add_new_member( $request_data );

        // Validate when editing an existing member
        if( isset( $request_data['submit_edit_member']) )
            $validation_response = $this->validate_data_edit_member( $request_data );


        /*
         * If all is good continue and save data
         *
         */
        if( isset( $validation_response ) && $validation_response ) {

            // Do something before we add/edit the member
            do_action( 'pms_submenu_page_members_before_' . $action, $this->request_data );

            // Init member
            $member = pms_get_member( $this->request_data['pms-member-user-id'] );

            /*
             * Attach the subscription plans to the user to make him a member
             */
            if( $action == 'add_new_member' ) {

                $member_subscriptions = $this->request_data['pms-member-subscriptions'];

                foreach( $member_subscriptions as $subscription_data ) {

                    $subscription_plan = pms_get_subscription_plan( $subscription_data['subscription_plan_id'] );

                    // Check to see if the subscription exists
                    if( $subscription_plan->is_valid() ) {
                        $member->add_subscription( $subscription_data['subscription_plan_id'], $subscription_data['start_date'], $subscription_data['expiration_date'], $subscription_data['status'] );
                    }
                }

            }


            // Do something after we add/edit the member
            do_action( 'pms_submenu_page_members_after_' . $action, $this->request_data );


            // Redirect the admin to the edit member page
            if( $action == 'add_new_member' )
                wp_redirect( admin_url( 'admin.php?page=pms-members-page&pms-action=edit_member&member_id=' . $member->user_id ) );


        }


    }


    /*
     * Method that validates data from the add new member page
     *
     * @param array $request_data   - The HTTP request
     *
     * @return mixed    - false if validation fails somewhere, $request_data array if all is well
     *
     */
    public function validate_data_add_new_member( $request_data ) {

        /*
        * Username validations
        */

        // Set username value
        $user_id = esc_attr( trim( $request_data['pms-member-user-id'] ) );

        // Check to see if the username field is empty
        if( empty( $user_id ) )
            $this->add_admin_notice( __( 'Please select a user.', 'paid-member-subscriptions' ), 'error' );
        else {
            // Check to see if the username exists
            $user = get_user_by( 'id', $user_id );

            if( !$user )
                $this->add_admin_notice( __( 'It seems this user does not exist.', 'paid-member-subscriptions' ), 'error' );
        }


        /*
         * Selected subscription plans validation
         */
        if( !isset( $request_data['pms-member-subscriptions'] ) || empty( $request_data['pms-member-subscriptions'] ) )
            $this->add_admin_notice( __( 'Please add at least one Subscription Plan.', 'paid-member-subscriptions' ), 'error' );

        // Add item count, we need it in the member subscription list table class
        if( isset( $request_data['pms-member-subscriptions'] ) ) {
            foreach( $request_data['pms-member-subscriptions'] as $key => $subscription_item ) {
                $request_data['pms-member-subscriptions'][$key]['item_count'] = $key;
            }
        }


        /*
         * Check to see if any fields are empty and set the errors for that item
         *
         */
        $found_empty = false;

        if( isset( $request_data['pms-member-subscriptions'] ) ) {
            foreach( $request_data['pms-member-subscriptions'] as $key => $subscription_item ) {

                foreach( $subscription_item as $item_data_key => $item_data ) {

                    if( $item_data_key == 'subscription_plan' )
                        continue;

                    if( !isset( $item_data ) || ( empty( $item_data ) && $item_data !== 0 ) ) {
                        $found_empty = true;
                        $request_data['pms-member-subscriptions'][$key]['errors'][] = $item_data_key;
                    }
                }

            }
        }

        if( $found_empty )
            $this->add_admin_notice( __( 'Please fill in all the required fields.', 'paid-member-subscriptions' ), 'error' );


        /*
         * Check to see if user is already a member
         */
        if( isset( $user ) && $user ) {
            $member = pms_get_member( $user->ID );

            if( $member->is_member() )
                $this->add_admin_notice( __( 'This user is already a member.', 'paid-member-subscriptions' ), 'error' );

        }


        /*
         * Other validations from outside
         *
         * Returned array for the filter must contain arrays in the form of 'notice_type' => 'Notice message',
         * thus the returned array should be array( array('notice_type' => 'Notice message 1' ), array( 'notice_type' => 'Notice message 2' ) )
         *
         */
        $this->add_admin_notices( apply_filters( 'pms_validate_data_add_new_member', array(), $request_data ) );

        // Update data if updates were made
        $this->request_data = apply_filters( 'pms_sanitize_request_data_add_new_member' , $request_data );


        if( $this->has_admin_notice( 'error' ) )
            return false;
        else
            return $request_data;

    }


    public function validate_data_edit_member( $request_data ) {

        /*
         * Username validations
         */

        // Check if username isset
        if( !isset( $request_data['pms-member-user-id'] ) )
            return false;


        // Set username value
        $user_id = esc_attr( trim( $request_data['pms-member-user-id'] ) );

        // Check to see if the username field is empty
        if( empty( $user_id ) )
            $this->add_admin_notice( __( 'It seems this user does not exist.', 'paid-member-subscriptions' ), 'error' );


        // Check to see if the username exists
        $user = get_user_by( 'id', $user_id );

        if( !$user )
            $this->add_admin_notice( __( 'It seems this user does not exist.', 'paid-member-subscriptions' ), 'error' );



        /*
         * Other validations from outside
         *
         * Returned array for the filter must contain arrays in the form of 'notice_type' => 'Notice message',
         * thus the returned array should be array( array('notice_type' => 'Notice message 1' ), array( 'notice_type' => 'Notice message 2' ) )
         *
         */
        $this->add_admin_notices( apply_filters( 'pms_validate_data_edit_member', array(), $request_data ) );


        if( $this->has_admin_notice( 'error' ) )
            return false;
        else
            return $request_data;

    }


    /*
     * Method to output content in the custom page
     *
     */
    public function output() {

        // Display the add new member form
        if( isset( $_REQUEST['pms-action'] ) && $_REQUEST['pms-action'] == 'add_new_member' )

            include_once 'views/view-page-members-add-new.php';

        // Display the add new bulk table
        elseif( isset( $_REQUEST['pms-action'] ) && $_REQUEST['pms-action'] == 'add_new_members_bulk' )

            include_once 'views/view-page-members-add-new-bulk.php';

        // Display the edit member form
        elseif( isset( $_REQUEST['pms-action'] ) && $_REQUEST['pms-action'] == 'edit_member' && isset( $_REQUEST['member_id'] ) && !empty( $_REQUEST['member_id'] ) )

            include_once 'views/view-page-members-edit.php';

        // Display a list with all the members
        else
            include_once 'views/view-page-members-list-table.php';

    }


    /**
     * Method that returns the formatted expiration date of a subscription plan
     *
     */
    public function ajax_populate_expiration_date() {

        $subscription_plan_id = (int)trim( $_POST['subscription_plan_id'] );

        if( ! empty( $subscription_plan_id ) ) {

            $subscription_plan = pms_get_subscription_plan( $subscription_plan_id );

            echo pms_sanitize_date( $subscription_plan->get_expiration_date() );

        } else
            echo '';

        wp_die();

    }


    /**
     * Add / update a member subscription plan with ajax
     *
     */
    public function ajax_member_add_update_subscription() {

        if( empty( $_POST['user_id'] ) )
            return;

        if( empty( $_POST['subscription_plan_id'] ) )
            return;

        if( empty( $_POST['status'] ) )
            return;

        // Get post data
        $user_id              = (int)trim($_POST['user_id']);
        $subscription_plan_id = (int)trim($_POST['subscription_plan_id']);
        $start_date           = ( ! empty( $_POST['start_date'] )      ? sanitize_text_field( $_POST['start_date'] ) : '' );
        $expiration_date      = ( ! empty( $_POST['expiration_date'] ) ? sanitize_text_field( $_POST['expiration_date'] ) : '' );
        $status               = sanitize_text_field( $_POST['status'] );

        // Get member
        $member = pms_get_member( $user_id );

        $member_subscriptions = $member->get_subscriptions();

        /*
         * Set an array with only the subscription plan ids
         */
        $member_subscriptions_ids = array();
        foreach( $member_subscriptions as $subscription ) {
            $member_subscriptions_ids[] = $subscription['subscription_plan_id'];
        }

        /*
         * Handle Update existing subscription
         */
        if( in_array( $subscription_plan_id, $member_subscriptions_ids ) )
            $member->update_subscription( $subscription_plan_id, $start_date, $expiration_date, $status );



        /*
         * Handle update of subscription plan id that is not present in the member subscriptions
         */
        if( !in_array( $subscription_plan_id, $member_subscriptions_ids ) ) {

            // Get group of the subscription plan id
            $group_subscriptions = pms_get_subscription_plans_group( $subscription_plan_id );

            /*
             * Check to see if the member is subscribed to one of the subscriptions in the group
             */
            $found_in_group = false;

            if( !empty( $group_subscriptions ) ) {
                foreach( $group_subscriptions as $subscription ) {
                    foreach( $member_subscriptions_ids as $member_subscription_id ) {
                        if( $member_subscription_id == $subscription->id ) {
                            $member->replace_subscription( $subscription->id, $subscription_plan_id );
                            $member->update_subscription( $subscription_plan_id, $start_date, $expiration_date, $status );

                            $found_in_group = true;
                        }
                    }
                }
            }

            /*
             * If the subscription wasn't found in the group add it to the member
             */
            if( !$found_in_group ) {
                $member->add_subscription( $subscription_plan_id, $start_date, $expiration_date, $status );
            }

        }

        wp_die();
    }


	/*
     * Method that adds Screen Options to Members page
     *
     */
	public function add_screen_options() {

        if( isset( $_REQUEST['pms-action'] ) && $_REQUEST['pms-action'] == 'add_new_members_bulk' ) {

            $args = array(
                'label' => 'Users per page',
                'default' => 10,
                'option' => 'pms_users_per_page'
            );

        } else {

            $args = array(
                'label' => 'Members per page',
                'default' => 10,
                'option' => 'pms_members_per_page'
            );

        }

		add_screen_option( 'per_page', $args );

	}

}

$pms_submenu_page_members = new PMS_Submenu_Page_Members( 'paid-member-subscriptions', __( 'Members', 'paid-member-subscriptions' ), __( 'Members', 'paid-member-subscriptions' ), 'manage_options', 'pms-members-page' );
$pms_submenu_page_members->init();