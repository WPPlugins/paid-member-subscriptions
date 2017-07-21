<?php

/*
 * Extend the basic functionality of the main custom post type class to the
 * needs of the subpscription
 *
 */
Class PMS_Custom_Post_Type_Subscription extends PMS_Custom_Post_Type {

    /**
     * Custom post type instance
     *
     * @access private
     * @var object
     */
    private static $instance;

    /*
     * Method to add the needed hooks
     *
     */
    public function init() {

        add_action( 'init', array( $this, 'process_data' ) );
        add_action( 'init', array( $this, 'register_custom_subscription_plan_statuses' ) );

        add_filter( 'page_row_actions', array( $this, 'remove_post_row_actions' ), 10, 2 );
        add_action( 'page_row_actions', array( $this, 'add_post_row_actions' ), 11, 2 );

        add_filter( 'manage_' . $this->post_type . '_posts_columns', array( __CLASS__, 'manage_posts_columns' ) );
        add_action( 'manage_' . $this->post_type . '_posts_custom_column', array( __CLASS__, 'manage_posts_custom_column' ), 10, 2 );

        // Bulk actions
        add_filter( 'bulk_actions-edit-' . $this->post_type, array( $this, 'remove_bulk_actions' ) );

        // Add custom bulk actions through javascript
        add_action( 'admin_footer-edit.php', array( $this, 'add_bulk_actions' ) );

        // Process the data received from the bulk actions
        add_action( 'admin_init', array( $this, 'process_custom_bulk_actions' ) );

        // Add a delete button where the move to trash was
        add_action( 'post_submitbox_start', array( $this, 'submitbox_add_delete_button' ));

        // Add "Add Upgrade" and "Add Downgrade" buttons in the submit box, we will move them
        // next to the "Add new" with js
        add_action( 'post_submitbox_start', array( $this, 'submitbox_add_upgrade_downgrade_buttons' ));

        // Change the default "Enter title here" text
        add_filter( 'enter_title_here', array( $this, 'change_title_prompt_text' ) );

        // Add new subscription plan as a downgrade
        add_action( 'save_post', array( $this, 'add_new_subscription_plan' ) );

        // Update the post as an update / downgrade
        add_action( 'save_post', array( $this, 'upgrade_downgrade' ) );

        // Add the top parent id to the meta data of each subscription plan
        add_action( 'save_post', array( $this, 'update_subscription_plan_top_parent' ) );

		// Set custom updated messages
		add_filter( 'post_updated_messages', array( $this, 'set_custom_messages' ) );

		// Set custom bulk updated messages
		add_filter( 'bulk_post_updated_messages', array( $this, 'set_bulk_custom_messages' ), 10, 2 );

    }


    /*
     * Method that validates data for the subscription plan cpt
     *
     */
    public function process_data() {

        // Verify nonce before anything
        if( !isset( $_REQUEST['_wpnonce'] ) || !wp_verify_nonce( $_REQUEST['_wpnonce'], 'pms_subscription_plan_nonce' ) )
            return;


        // Activate subscription plan
        if( isset( $_REQUEST['pms-action'] ) && $_REQUEST['pms-action'] == 'activate_subscription_plan' && isset( $_REQUEST['post_id'] ) ) {
            PMS_Subscription_Plan::activate( (int)esc_attr( $_REQUEST['post_id'] ) );
        }

        // Deactivate subscription plan
        if( isset( $_REQUEST['pms-action'] ) && $_REQUEST['pms-action'] == 'deactivate_subscription_plan' && isset( $_REQUEST['post_id'] ) ) {
            PMS_Subscription_Plan::deactivate( (int)esc_attr( $_REQUEST['post_id'] ) );
        }

        // Delete subscription plan
        if( isset( $_REQUEST['pms-action'] ) && $_REQUEST['pms-action'] == 'delete_subscription_plan' && isset( $_REQUEST['post_id'] ) ) {
            PMS_Subscription_Plan::remove( (int)esc_attr( $_REQUEST['post_id'] ) );
        }

        // Move subscription plan up
        if( isset( $_REQUEST['pms-action'] ) && $_REQUEST['pms-action'] == 'move_up_subscription_plan' && isset( $_REQUEST['post_id'] ) ) {

            if( !isset( $_REQUEST['post_type'] ) || $_REQUEST['post_type'] != $this->post_type )
                return;

            $post_id      = (int)trim( $_REQUEST['post_id'] );
            $current_post = get_post( $post_id );

            // If this post doesn't have a parent do nothing
            if( $current_post->post_parent == 0 )
                return;

            $parent_post    = get_post( $current_post->post_parent );
            $children_posts = get_posts( array( 'post_type' => $this->post_type, 'post_status' => 'any', 'numberposts' => 1, 'post_parent' => $post_id ) );

            wp_update_post( array( 'ID' => $current_post->ID, 'post_parent' => $parent_post->post_parent ) );
            wp_update_post( array( 'ID' => $parent_post->ID, 'post_parent' => $current_post->ID ) );

            if( !empty( $children_posts ) ) {
                $child_post = $children_posts[0];
                wp_update_post( array( 'ID' => $child_post->ID, 'post_parent' => $parent_post->ID ) );
            }

            wp_redirect( add_query_arg( array( 'post_type' => $this->post_type ), pms_get_current_page_url(true) ) );

        }

        // Move subscription plan down
        if( isset( $_REQUEST['pms-action'] ) && $_REQUEST['pms-action'] == 'move_down_subscription_plan' && isset( $_REQUEST['post_id'] ) ) {

            if( !isset( $_REQUEST['post_type'] ) || $_REQUEST['post_type'] != $this->post_type )
                return;

            $post_id      = trim( $_REQUEST['post_id'] );
            $current_post = get_post( $post_id );

            $children_posts = get_posts( array( 'post_type' => $this->post_type, 'post_status' => 'any', 'numberposts' => 1, 'post_parent' => $post_id ) );

            // Exit if the post is the last in the group
            if( empty( $children_posts ) )
                return;

            $child_post = $children_posts[0];

            $children_posts = get_posts( array( 'post_type' => $this->post_type, 'post_status' => 'any', 'numberposts' => 1, 'post_parent' => $child_post->ID ) );

            wp_update_post( array( 'ID' => $child_post->ID, 'post_parent' => ( !empty( $current_post->post_parent ) ? $current_post->post_parent : 0 ) ) );
            wp_update_post( array( 'ID' => $current_post->ID, 'post_parent' => $child_post->ID ) );

            if( !empty( $children_posts ) ) {
                $child_post = $children_posts[0];
                wp_update_post( array( 'ID' => $child_post->ID, 'post_parent' => $current_post->ID ) );
            }

            wp_redirect( add_query_arg( array( 'post_type' => $this->post_type ), pms_get_current_page_url(true) ) );

        }

    }

    /**
     * Method for registering custom subscription plan statuses (active, inactive)
     *
     */
    public function register_custom_subscription_plan_statuses() {

        // Register custom Subscription Plan Statuses
        register_post_status( 'active', array(
            'label'                     => _x( 'Active', 'Active status for subscription plan', 'paid-member-subscriptions' ),
            'public'                    => true,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop( 'Active <span class="count">(%s)</span>', 'Active <span class="count">(%s)</span>', 'paid-member-subscriptions' )
        )  );
        register_post_status( 'inactive', array(
            'label'                     => _x( 'Inactive', 'Inactive status for subscription plan', 'paid-member-subscriptions' ),
            'public'                    => true,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop( 'Inactive <span class="count">(%s)</span>', 'Inactive <span class="count">(%s)</span>', 'paid-member-subscriptions' )
        )  );

    }


    /*
     * Method that removes all row actions besides the edit one
     *
     */
    public function remove_post_row_actions( $actions, $post ) {

        if( $post->post_type != $this->post_type )
            return $actions;

        if( empty( $actions ) )
            return $actions;

        foreach( $actions as $key => $action ) {
            if( $key != 'edit' ) {
                unset( $actions[ $key ] );
            }
        }

        return $actions;
    }


    /*
     * Method that adds new actions
     *
     */
    public function add_post_row_actions( $actions, $post ) {

        if( $post->post_type != $this->post_type )
            return $actions;

        if( empty( $actions ) )
            return $actions;


        /*
         * Add the option to activate and deactivate a subscription plan
         */
        $subscription_plan = new PMS_Subscription_Plan( $post );

        if( $subscription_plan->is_active() )
            $activate_deactivate = '<a href="' . esc_url( wp_nonce_url( add_query_arg( array( 'pms-action' => 'deactivate_subscription_plan', 'post_id' => $post->ID ) ), 'pms_subscription_plan_nonce' ) ) . '">' . __( 'Deactivate', 'paid-member-subscriptions' ) . '</a>';
        else
            $activate_deactivate = '<a href="' . esc_url( wp_nonce_url( add_query_arg( array( 'pms-action' => 'activate_subscription_plan', 'post_id' => $post->ID ) ), 'pms_subscription_plan_nonce' ) ) . '">' . __( 'Activate', 'paid-member-subscriptions' ) . '</a>';

        $actions['change_status'] = $activate_deactivate;


        /*
         * Add the option to add a parent to a subscription plan
         */
        $add_upgrade = '<a href="' . esc_url( add_query_arg( array( 'post_type' => $post->post_type, 'plan_id' => $post->ID, 'pms-action' => 'add_upgrade' ), admin_url( 'post-new.php' ) ) ) . '">' . __( 'Add Upgrade', 'paid-member-subscriptions' ) . '</a>';

        $actions['add_upgrade'] = $add_upgrade;


        /*
         * Add the options to add a child to a subscription plan
         */
        $add_downgrade = '<a href="' . esc_url( add_query_arg( array( 'post_type' => $post->post_type, 'plan_id' => $post->ID, 'pms-action' => 'add_downgrade' ), admin_url( 'post-new.php' ) ) ) . '">' . __( 'Add Downgrade', 'paid-member-subscriptions' ) . '</a>';

        $actions['add_downgrade'] = $add_downgrade;


        /*
         * Add the option to delete a subscription plan
         */
        $delete = '<span class="trash"><a onclick="return confirm( \'' . __( "Are you sure you want to delete this Subscription Plan?", "paid-member-subscriptions" ) . ' \' )" href="' . esc_url( wp_nonce_url( add_query_arg( array( 'pms-action' => 'delete_subscription_plan', 'post_id' => $post->ID, 'deleted' => 1 ) ), 'pms_subscription_plan_nonce' ) ) . '">' . __( 'Delete', 'paid-member-subscriptions' ) . '</a></span>';

        $actions['delete'] = $delete;


        // Return actions
        return $actions;

    }


    /*
     * Method that adds new columns on the subscription plan listing
     *
     */
    public static function manage_posts_columns( $columns ) {

        // Get currency set in settings
        $pms_settings = get_option('pms_settings');

        // New columns for the subscription plans
        $new_columns = array_merge( $columns, array(
            'price'     => __( 'Price', 'paid-member-subscriptions' ) . ' (' . $pms_settings['payments']['currency'] . ')',
            'status'    => __( 'Status', 'paid-member-subscriptions' ),
            'id' => __( 'ID', 'paid-member-subscriptions' ),
            'order'     => '',
        ));


        // Shift the order column after the checkbox column
        $beginning = array_slice( $new_columns, 0, 1 );
        $middle    = array_slice( $new_columns, 1, count( $new_columns ) - 3 );
        $end       = array_slice( $new_columns, count( $new_columns ) - 2, 2 );

        $new_columns = array_merge( $beginning, $end, $middle );


        // Shift the publishing date column to the end
        $date_column = $new_columns['date'];
        unset( $new_columns['date'] );
        $new_columns['date'] = $date_column;

        return $new_columns;

    }


    /*
     * Method to display values for each new column
     *
     */
	public static function manage_posts_custom_column( $column, $post_id ) {

        $subscription_plan = new PMS_Subscription_Plan( $post_id );

        // Information shown in the order column
        if( $column == 'order' ) {
            /* get parents */
            $parent_id = wp_get_post_parent_id( $post_id );
            /* get children */
            $post_children = get_children( array( 'post_parent' => $post_id, 'post_type' => 'pms-subscription' ) );

            if( $parent_id != false )
                echo '<a href="' . esc_url( wp_nonce_url( add_query_arg( array( 'pms-action' => 'move_up_subscription_plan', 'post_id' => $post_id ) ), 'pms_subscription_plan_nonce' ) ) . '" class="add-new-h2 pms-subscription-plan-order-move-up" title="' . __( 'Move Subscription Plan Up', 'pms-member-subscriptions' ) . '">&uarr;</a>';
            else
                echo '<span class="pms-subscription-plan-order-move-up pms-subscription-plan-order-placeholder ' . ( !empty( $post_children ) ? 'move-down' : '' ) . '"><span class="pms-inner">&uarr;</span></span>';

            if( !empty( $post_children ) )
                echo '<a href="' . esc_url( wp_nonce_url( add_query_arg( array( 'pms-action' => 'move_down_subscription_plan', 'post_id' => $post_id ) ), 'pms_subscription_plan_nonce' ) ) . '" class="add-new-h2 pms-subscription-plan-order-move-down" title="' . __( 'Move Subscription Plan Down', 'pms-member-subscriptions' ) . '">&darr;</a>';
            else
                echo '<span class="pms-subscription-plan-order-move-down pms-subscription-plan-order-placeholder ' . ( $parent_id != false ? 'move-up' : '' ) . '"><span class="pms-inner">&darr;</span></span>';

        }

        // Information shown in the status column
		if( $column == 'status' ) {

			$subscription_plan_status_dot = apply_filters( 'pms_list_table_subscription_plans_show_status_dot', '<span class="pms-status-dot ' . $subscription_plan->status . '"></span>' );

			if( $subscription_plan->is_active() )
				echo $subscription_plan_status_dot . '<span>' . __( 'Active', 'paid-member-subscriptions' ) . '</span>';
			else
				echo $subscription_plan_status_dot . '<span>' . __( 'Inactive', 'paid-member-subscriptions' ) . '</span>';
		}

        // Information shown in the price column
        if( $column == 'price' ) {
            $duration = '';
            if( $subscription_plan->duration > 0) {

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
                $duration = '<span class="pms-divider"> / </span>' . $duration;
            }

            if( $subscription_plan->price == 0 )
                echo __('Free', 'paid-member-subscriptions') . $duration;
            else {
                $pms_settings = get_option( 'pms_settings' );
                $currency_symbol='';

                if ( ( function_exists('pms_get_currency_symbol') ) && isset( $pms_settings['payments']['currency'] ) )
                    $currency_symbol = pms_get_currency_symbol( $pms_settings['payments']['currency'] );

                echo $subscription_plan->price . $currency_symbol . $duration;
                }
        }

        // Information shown in the id column
        if( $column == 'id' ) {
            echo $post_id;
        }

	}


    /*
     * Remove bulk actions
     *
     */
    public function remove_bulk_actions( $actions ) {

        // Remove unneeded actions
        unset( $actions['trash'] );

        return $actions;

    }


    /*
     * Returns an array with custom bulk actions for subscription plans
     *
     */
    public function get_custom_bulk_action() {

        return apply_filters( 'get_custom_bulk_actions_' . $this->post_type, array(
            'activate'   => __( 'Activate', 'paid-member-subscriptions' ),
            'deactivate' => __( 'Deactivate', 'paid-member-subscriptions' )
        ));

    }


    /*
     * Add bulk actions
     */
    public function add_bulk_actions() {

        global $post_type;

        if( $post_type == $this->post_type ) {

            echo '<script type="text/javascript">';
                echo 'jQuery(document).ready(function(){';

                    echo 'jQuery("#bulk-action-selector-top option[value=edit]").remove();';
                    echo 'jQuery("#bulk-action-selector-bottom option[value=edit]").remove();';

                    foreach( $this->get_custom_bulk_action() as $action_slug => $action_name ) {
                        echo 'jQuery("<option>").val("' . $action_slug . '").text("' . $action_name . '").appendTo("#bulk-action-selector-top");';
                        echo 'jQuery("<option>").val("' . $action_slug . '").text("' . $action_name . '").appendTo("#bulk-action-selector-bottom");';
                    }

                echo '});';
            echo '</script>';

        }

    }


    /*
     * Process what happens when a custom bulk action is applied.
     *
     */
    public function process_custom_bulk_actions() {

        if( !isset( $_REQUEST['post_type'] ) || trim($_REQUEST['post_type']) != $this->post_type )
            return;

        // Verify nonce before anything
        if( !isset( $_REQUEST['_wpnonce'] ) || !wp_verify_nonce( $_REQUEST['_wpnonce'], 'bulk-posts' ) )
            return;

        if( !isset( $_REQUEST['action'] ) && !isset( $_REQUEST['action2'] ) )
            return;


        $action = ( isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : $_REQUEST['action2'] );

        // Return if the action is not one of our custom actions
        if( !array_key_exists( $action, $this->get_custom_bulk_action() ) )
            return;


        // Subscription plan activation and deactivation
        if( $action == 'activate' || $action == 'deactivate' ) {

            if( isset( $_REQUEST['post'] ) && !empty( $_REQUEST['post'] ) ) {
                $subscription_plan_ids = $_REQUEST['post'];

                foreach( $subscription_plan_ids as $subscription_plan_id ) {

                    if( $action == 'activate' )
                        PMS_Subscription_Plan::activate( (int)$subscription_plan_id );

                    if( $action == 'deactivate' )
                        PMS_Subscription_Plan::deactivate( (int)$subscription_plan_id );

                }

                // Redirect arguments
                $redirect_args = array(
                    'post_type'       => $this->post_type,
                    'bulk_' . $action => count( $_REQUEST['post'] ),
                    'paged'           => ( isset($_REQUEST['paged']) ? (int)$_REQUEST['paged'] : 1 )
                );

                $redirect = add_query_arg( $redirect_args, pms_get_current_page_url( true ) );

                wp_redirect( $redirect );
                exit;

            }

        }


    }


    /*
     * Add a delete button where the move to trash was
     *
     */
    public function submitbox_add_delete_button() {
        global $post_type;
        global $post;

        if( $post_type != $this->post_type )
            return false;

        echo '<div id="pms-delete-action">';
            echo '<a class="submitdelete deletion" onclick="return confirm( \'' . __( "Are you sure you want to delete this Subscription Plan?", "paid-member-subscriptions" ) . ' \' )" href="' . esc_url( wp_nonce_url( add_query_arg( array( 'pms-action' => 'delete_subscription_plan', 'post_id' => $post->ID, 'deleted' => 1 ), admin_url( 'edit.php?post_type=' . $this->post_type ) ), 'pms_subscription_plan_nonce' ) ) . '">' . __( 'Delete Plan', 'paid-member-subscriptions' ) . '</a>';
        echo '</div>';

    }


    /*
     * Add "Add Upgrade" and "Add downgrade" on the edit page of a Subscription Plan
     * This buttons will be moved from the submit box next to the default "Add New" button from
     * the page title
     *
     */
    public function submitbox_add_upgrade_downgrade_buttons() {
        global $post_type;
        global $post;

        if( $post_type != $this->post_type )
            return false;

        if( !isset( $_GET['post'] ) )
            return false;

        echo '<div id="pms-upgrade-downgrade-buttons-wrapper">';
            echo '<a class="add-new-h2 page-title-action" href="' . esc_url( add_query_arg( array( 'post_type' => $this->post_type, 'plan_id' => $post->ID, 'pms-action' => 'add_upgrade' ), admin_url('post-new.php') ) ) . '">' . __( 'Add Upgrade', 'paid-member-subscriptions' ) . '</a>';
            echo '<a class="add-new-h2 page-title-action" href="' . esc_url( add_query_arg( array( 'post_type' => $this->post_type, 'plan_id' => $post->ID, 'pms-action' => 'add_downgrade' ), admin_url('post-new.php') ) ) . '">' . __( 'Add Downgrade', 'paid-member-subscriptions' ) . '</a>';
        echo '</div>';

    }


    /*
     * Method to change the default title text "Enter title here"
     *
     */
    public function change_title_prompt_text( $input ) {
        global $post_type;

        if( $post_type == $this->post_type ) {
            return __( 'Enter Subscription Plan name here', 'paid-member-subscriptions' );
        }

        return $input;
    }


    /*
     * Method that adds a new subscription plan as a downgrade
     *
     */
    public function add_new_subscription_plan( $post_id ) {

        $post_type = get_post_type( $post_id );
        if( $post_type != 'pms-subscription' )
            return;

        $pms_action = apply_filters( 'pms_action_add_new_subscription_plan', ( isset( $_GET['pms-action'] ) ? $_GET['pms-action'] : '' ) );

        // Exit if there is a custom action going on
        if( !empty( $pms_action ) )
            return;

        $current_plan = get_post( $post_id );

        // Exit if the plan already has a parent
        if( $current_plan->post_parent != 0 )
            return;

        // Remove action hook and add it again later for no infinite loop
        remove_action( 'save_post', array( $this, 'add_new_subscription_plan' ) );

        $top_level_plan = get_posts( array( 'post_type' => $this->post_type, 'numberposts' => 1, 'post_status' => 'any', 'post_parent' => 0, 'order' => 'ASC' ) );

        // Exit if we don't have any subscription plans yet
        if( empty($top_level_plan) )
            return;

        $top_level_plan = $top_level_plan[0];

        // Get all children and add the top level plan at the beginning of the array
        $children_plans = get_page_children( $top_level_plan->ID, get_posts( array( 'post_type' => $this->post_type, 'numberposts' => -1, 'post_status' => 'any' )) );
        array_unshift( $children_plans, $top_level_plan );

        $last_child_plan = $children_plans[ count($children_plans) - 1 ];

        $args = array(
            'ID'          => $post_id,
            'post_parent' => $last_child_plan->ID
        );

        wp_update_post( $args );

        // Add action hook again for no infinite loop
        add_action( 'save_post', array( $this, 'add_new_subscription_plan' ) );

    }


    /*
     * Method that makes subscription plan CPT hierarchical
     *
     */
    public function upgrade_downgrade( $post_id ) {

        // Check if plan is added by add upgrade or add downgrade row actions
        $pms_action = isset( $_GET['pms-action'] ) ? $_GET['pms-action'] : '';

        if( ! ( $pms_action == 'add_upgrade' || $pms_action == 'add_downgrade' ) ) {
            return;
        }

        // Remove action hook and add it again later for no infinite loop
        remove_action( 'save_post', array( $this, 'upgrade_downgrade' ) );

        if( $pms_action == 'add_upgrade' ) {

            // Get new plan id and child id
            $plan_id   = isset( $_GET['plan_id'] ) ? (int)$_GET['plan_id'] : '';
            $parent_id = $post_id;

            // Get current plan parent id
            $get_sibling = get_post( $plan_id );
            $sibling_parent_id = isset( $get_sibling->post_parent ) ? $get_sibling->post_parent : 0;
            $sibling_id = $post_id;

        } elseif( $pms_action == 'add_downgrade' ) {

            // Get new plan id and parent id
            $plan_id    = $post_id;
            $parent_id  = isset( $_GET['plan_id'] ) ? (int)$_GET['plan_id'] : '';

            $sibling = array(
                'post_type' => 'pms-subscription',
                'post_parent' => $parent_id,
                'post_status' => 'any'
            );

            // Get parent id from current plan child
            $get_sibling = get_posts( $sibling );
            $sibling_parent_id = $post_id;
            $sibling_id = isset( $get_sibling[0]->ID ) ? $get_sibling[0]->ID : 0;

        }

        // Check if current plan has siblings
        if( isset( $sibling_parent_id ) && $sibling_parent_id != 0 && isset( $sibling_id ) && $sibling_id !=0 ) {

            // Args for siblings update
            $sibling_args = array(
                'ID' => $sibling_id,
                'post_parent' => $sibling_parent_id
            );

            wp_update_post( $sibling_args );

        }

        // Args for new plan
        $plan_update = array(
            'ID' => isset( $plan_id ) ? $plan_id : '',
            'post_parent' => isset( $parent_id ) ? $parent_id : ''
        );

        wp_update_post( $plan_update );

        // Add action hook again for no infinite loop
        add_action( 'save_post', array( $this, 'upgrade_downgrade' ) );

    }


    /*
     * Method that sets the top parent id of each subscription plan as meta data
     *
     */
    public function update_subscription_plan_top_parent( $post_id ) {

        global $post_type;

        if( $post_type != $this->post_type )
            return;

        $subscription_plans = pms_get_subscription_plans( false );

        foreach( $subscription_plans as $subscription_plan ) {

            $top_parent_id = pms_get_subscription_plans_group_parent_id( $subscription_plan->id );

            update_post_meta( $subscription_plan->id, 'pms_subscription_plan_top_parent', $top_parent_id );

        }

    }


	/*
     * Method that set custom updated messages
     *
     */
	function set_custom_messages( $messages ) {

		global $post;

		$messages['pms-subscription'] = array(
			0  => 	'',
			1  => 	__( 'Subscription Plan updated.', 'paid-member-subscriptions' ),
			2  => 	__( 'Custom field updated.', 'paid-member-subscriptions' ),
			3  => 	__( 'Custom field deleted.', 'paid-member-subscriptions' ),
			4  => 	__( 'Subscription Plan updated.', 'paid-member-subscriptions' ),
			5  => 	isset( $_GET['revision'] ) ? sprintf( __( 'Subscription Plan' . ' restored to revision from %s', 'paid-member-subscriptions' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6  => 	__( 'Subscription Plan saved.', 'paid-member-subscriptions' ),
			7  => 	__( 'Subscription Plan saved.', 'paid-member-subscriptions' ),
			8  => 	__( 'Subscription Plan submitted.', 'paid-member-subscriptions' ),
			9  => 	sprintf( __( 'Subscription Plan' . ' scheduled for: <strong>%1$s</strong>.', 'paid-member-subscriptions' ), date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ) ),
			10 =>	__( 'Subscription Plan draft updated.', 'paid-member-subscriptions' ),
		);

		return $messages;

	}


	/*
     * Method that set custom bulk updated messages
     *
     */
	public function set_bulk_custom_messages( $bulk_messages, $bulk_counts ) {

		$bulk_messages['pms-subscription'] = array(
			'updated'   => _n( '%s Subscription Plan updated.', '%s Subscription Plans updated.', $bulk_counts['updated'], 'paid-member-subscriptions' ),
			'locked'    => _n( '%s Subscription Plan not updated, somebody is editing it.', '%s Subscription Plans not updated, somebody is editing them.', $bulk_counts['locked'], 'paid-member-subscriptions' ),
			'deleted'   => _n( '%s Subscription Plan permanently deleted.', '%s Subscription Plans permanently deleted.', $bulk_counts['deleted'], 'paid-member-subscriptions' ),
			'trashed'   => _n( '%s Subscription Plan moved to the Trash.', '%s Subscription Plans moved to the Trash.', $bulk_counts['trashed'], 'paid-member-subscriptions' ),
			'untrashed' => _n( '%s Subscription Plan restored from the Trash.', '%s Subscription Plans restored from the Trash.', $bulk_counts['untrashed'], 'paid-member-subscriptions' ),
		);

		return $bulk_messages;

	}


    /*
     * Add the query args we wish WP to remove from the URL
     *
     * @param array $query_args     - the arguments WP will remove by default
     *
     * @return array $query_args    - the argumnets WP will remove by default alongside the ones we wish to remove for this CPT
     *
     */
    public function removable_query_args( $query_args ) {

        global $post_type;

        if( $post_type != $this->post_type )
            return $query_args;

        $new_query_args = array();

        foreach( array_keys( $this->get_custom_bulk_action() ) as $bulk_action ) {
            $new_query_args[] = 'bulk_' . $bulk_action;
        }

        $query_args = array_merge( $query_args, $new_query_args );

        return $query_args;

    }

    /*
     * Display admin notices
     *
     */
    public function admin_notices() {

        global $post_type;

        if( $post_type != $this->post_type )
            return;

        $message = '';

        if( isset( $_REQUEST['bulk_activate'] ) && $_REQUEST['bulk_activate'] == true )
            $message = sprintf( _n( '%d subscription plan has been successfully activated', '%d subscription plans have been successfully activated', (int)$_REQUEST['bulk_activate'], 'paid-member-subscriptions' ), (int)$_REQUEST['bulk_activate'] );

        if( isset( $_REQUEST['bulk_deactivate'] ) && $_REQUEST['bulk_deactivate'] == true )
            $message = sprintf( _n( '%d subscription plan has been successfully deactivated', '%d subscription plans have been successfully deactivated', (int)$_REQUEST['bulk_deactivate'], 'paid-member-subscriptions' ), (int)$_REQUEST['bulk_deactivate'] );

        if( !empty( $message ) )
            echo '<div class="updated"><p>' . $message . '</p></div>';

    }


}


/*
 * Initialize the subscription custom post type
 *
 */

$args = array(
    'show_ui'            => true,
    'show_in_menu'       => 'paid-member-subscriptions',
    'query_var'          => true,
    'capability_type'    => 'post',
    'menu_position'      => null,
    'supports'           => array( 'title' ),
    'hierarchical'		 => true
);

$pms_cpt_subscribtion = new PMS_Custom_Post_Type_Subscription( 'pms-subscription', __( 'Subscription Plan', 'paid-member-subscriptions' ), __( 'Subscription Plans', 'paid-member-subscriptions' ), $args );
$pms_cpt_subscribtion->init();