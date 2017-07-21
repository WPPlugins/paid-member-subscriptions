<?php

// WP_List_Table is not loaded automatically in the plugins section
if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


/*
 * Extent WP default list table for our custom members section
 *
 */
Class PMS_Member_Subscription_List_Table extends WP_List_Table {

    /**
     * Member
     *
     * @access public
     * @var int
     */
    public $member;

    /**
     * Subscription plan ids
     *
     * @access public
     * @var array
     */
    public $existing_subscription_plan_ids;


    /*
     * Constructor function
     *
     */
    public function __construct( $user_id ) {

        global $pagenow, $wp_importers, $hook_suffix, $plugin_page, $typenow, $taxnow;
        $page_hook = get_plugin_page_hook($plugin_page, $plugin_page);

        parent::__construct( array(
            'singular'  => 'member-subscription',
            'plural'    => 'member-subscriptions',
            'ajax'      => false,

            // Screen is a must!
            'screen'    => $page_hook
        ));

        $this->member = pms_get_member($user_id);

    }


    /*
     * Overwrites the parent class.
     * Define the columns for the members
     *
     * @return array
     *
     */
    public function get_columns() {

        $columns = array(
            'subscription_plan' => __( 'Subscription Plan', 'paid-member-subscriptions' ),
            'start_date'        => __( 'Start Date', 'paid-member-subscriptions' ),
            'expiration_date'   => __( 'Expiration date', 'paid-member-subscriptions' ),
            'status'   			=> __( 'Status', 'paid-member-subscriptions' ),
            'actions'           => ''
        );

        return $columns;

    }


    /*
     * Overwrites the parent class.
     * Define which columns to hide
     *
     * @return array
     *
     */
    public function get_hidden_columns() {

        return array();

    }


    /*
     * Overwrites the parent class.
     * Define which columns are sortable
     *
     * @return array
     *
     */
    public function get_sortable_columns() {

        return array();

    }


    /*
     * Method to add an entire row to the table
     * Replaces parent method
     *
     * @param array $item - The current row information
     *
     */
    public function single_row( $item ) {

        $row_classes = '';

        if( isset( $item['errors'] ) )
            $row_classes .= ' pms-field-error';

        if( !$this->member->is_member() )
            $row_classes .= ' pms-add-new edit-active';

        echo '<tr class="' . $row_classes . '">';
        $this->single_row_columns( $item );
        echo '</tr>';
    }


    /*
     * Adds the rows if data exists, if not adds the empty row to be filled
     * Replaces parent method
     *
     */
    public function display_rows_or_placeholder() {
        if ( $this->has_items() ) {
            $this->display_rows();
        } else {
            $this->single_row_add_new( array() );
        }
    }


    /*
     * Method to insert a new empty row
     *
     * @param array $subscription_plan_ids  - an array with the subscription plans already added to the table
     *
     */
    public function single_row_add_new( $subscription_plan_ids ) {

        $this->existing_subscription_plan_ids = $subscription_plan_ids;

        $item = array(
            'subscription_plan_id'  => '',
            'subscription_plan'     => '',
            'start_date'            => date('Y-m-d'),
            'expiration_date'       => '',
            'status'			    => 'active'
        );

        echo '<tr class="pms-add-new edit-active">';
            $this->single_row_columns( $item );
        echo '</tr>';

    }


    /*
     * Method to add extra actions before and after the table
     * Replaces parent method
     *
     * @param string @which     - which side of the table ( top or bottom )
     *
     */
    public function extra_tablenav( $which ) {

        do_action( 'pms_member_subscription_list_table_extra_tablenav', $which, $this->member, $this->existing_subscription_plan_ids );

    }


    /*
     * Returns the table data
     *
     * @return array
     *
     */
    public function get_table_data() {

        $data = array();

        if( !$this->member->is_member() )
            return $data;

        $item_count = 0;

        foreach( $this->member->subscriptions as $subscription ) {

            $user_subscription_plan = pms_get_subscription_plan( $subscription['subscription_plan_id'] );

            $data[] = array(
                'subscription_plan_id'  => $subscription['subscription_plan_id'],
                'subscription_plan'     => $user_subscription_plan->name,
                'start_date'            => pms_sanitize_date( $subscription['start_date'] ),
                'expiration_date'       => pms_sanitize_date( $subscription['expiration_date'] ),
				'status'			    => $subscription['status'],
                'item_count'            => $item_count
            );

            $item_count++;
        }

        return $data;

    }


    /*
     * Populates the items for the table
     *
     */
    public function prepare_items() {

        $columns = $this->get_columns();
        $hidden_columns = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();

        $data = $this->get_table_data();

        $this->_column_headers = array( $columns, $hidden_columns, $sortable );
        $this->items = $data;

    }


    /*
     * Allows the addition of new items before the actual display of the table
     *
     * @param array $items  - an array with individual row items
     *
     */
    public function add_items( $items = array() ) {

        if( empty( $items ) )
            return;

        foreach( $items as $item ) {
            $this->items[] = $item;
        }

    }


    /*
     * Allows the replacing of the items before the display of the table
     *
     * @param array $items - an array with individual row items
     *
     */
    public function replace_items( $items = array() ) {

        if( empty( $items ) )
            return;

        $this->items = $items;

    }


    /*
     * Return data that will be displayed in each column
     *
     * @param array $item           - data for the current row
     * @param string $column_name   - name of the current column
     *
     * @return string
     *
     */
    public function column_default( $item, $column_name ) {

        if( !isset( $item[ $column_name ] ) )
            return false;

        return $item[ $column_name ];

    }


    /*
     * Compares the ids of two subscription plans
     *
     */
    public function compare_subscription_plans( $object_a, $object_b ) {
        return $object_a->id - $object_b->id;
    }


    /*
     * Return data that will be displayed in the subscription plan column
     *
     * @param array $item           - data for the current row
     *
     * @return string
     *
     */
    public function column_subscription_plan( $item ) {

        if( !empty( $item['subscription_plan_id'] ) )
            $subscription_plans = pms_get_subscription_plans_group( $item['subscription_plan_id'] );
        else {

            $subscription_plans = pms_get_subscription_plans();

            if( isset( $this->existing_subscription_plan_ids ) && !empty( $this->existing_subscription_plan_ids ) && !empty($subscription_plans) ) {

                foreach( $this->existing_subscription_plan_ids as $existing_subscription_plan_id ) {

                    $group_subscriptions = pms_get_subscription_plans_group( $existing_subscription_plan_id );

                    $subscription_plans_diff = array_udiff( $subscription_plans, $group_subscriptions, array( $this, 'compare_subscription_plans' ) );

                    $subscription_plans = array_values( $subscription_plans_diff );

                }

            }

        }


        $output = '<span>' . ( !empty( $item['subscription_plan'] ) ? $item['subscription_plan'] : sprintf( __( 'Not Found - ID: %s', 'paid-member-subscriptions' ), $item['subscription_plan_id'] ) ) . '</span>';

        $output .= '<input type="hidden" name="pms-member-subscriptions[' . array_search( $item, $this->items ) . '][subscription_plan]" value="' . ( isset( $item['subscription_plan'] ) ? $item['subscription_plan'] : '' ) . '" class="pms-subscription-field" />';

        $output .= '<select name="pms-member-subscriptions[' . array_search( $item, $this->items ) . '][subscription_plan_id]" class="pms-subscription-field ' . ( (isset( $item['errors'] ) && in_array( 'subscription_plan_id', $item['errors'] ) ) ? 'pms-field-error' : '' ) . '">';

            if( empty( $item['subscription_plan_id'] ) )
                $output .= '<option value="">' . __( 'Select...', 'paid-member-subscriptions' ) . '</option>';

            foreach( $subscription_plans as $subscription_plan ) {
                $output .= '<option data-group="' . pms_get_subscription_plans_group_parent_id( $subscription_plan->id ) . '" value="' . esc_attr( $subscription_plan->id ) . '"' . selected( $subscription_plan->id, $item['subscription_plan_id'], false ) . '>' . $subscription_plan->name . '</option>';
            }

        $output .= '</select>';

        return $output;

    }


    /*
     * Return data that will be displayed in the start date column
     *
     * @param array $item           - data for the current row
     *
     * @return string
     *
     */
    public function column_start_date( $item ) {

        $output = '<span>' . $item['start_date'] . '</span>';

        $output .= '<input type="text" name="pms-member-subscriptions[' . array_search( $item, $this->items ) . '][start_date]" class="datepicker pms-subscription-field ' . ( (isset( $item['errors'] ) && in_array( 'start_date', $item['errors'] ) ) ? 'pms-field-error' : '' ) . '" value="' . $item['start_date'] . '" />';

        return $output;

    }


    /*
     * Return data that will be displayed in the expiration date column
     *
     * @param array $item           - data for the current row
     *
     * @return string
     *
     */
    public function column_expiration_date( $item ) {

        $output = '<span>' . $item['expiration_date'] . '</span>';

        $output .= '<input type="text" name="pms-member-subscriptions[' . array_search( $item, $this->items ) . '][expiration_date]" class="datepicker pms-subscription-field ' . ( (isset( $item['errors'] ) && in_array( 'expiration_date', $item['errors'] ) ) ? 'pms-field-error' : '' ) . '" value="' . $item['expiration_date'] . '" />';

        return $output;

    }


    /*
     * Return data that will be displayed in the status column
     *
     * @param array $item           - data for the current row
     *
     * @return string
     *
     */
    public function column_status( $item ) {

        $statuses = pms_get_member_statuses();

        $output = '<span>' . ( isset($statuses[ $item['status'] ]) ? $statuses[ $item['status'] ] : '' ) . '</span>';

        $output .= '<select name="pms-member-subscriptions[' . array_search( $item, $this->items ) . '][status]" class="pms-subscription-field ' . ( (isset( $item['errors'] ) && in_array( 'status', $item['errors'] ) ) ? 'pms-field-error' : '' ) . '">';

        foreach( pms_get_member_statuses() as $member_status_slug => $member_status_name ) {
            $output .= '<option value="' . $member_status_slug . '"' . selected( $member_status_slug, $item['status'], false ) . '>' . $member_status_name . '</option>';
        }

        $output .= '</select>';

        return $output;

    }


    /*
     * Return data that will be displayed in the actions column
     *
     * @param array $item           - data for the current row
     *
     * @return string
     *
     */
    public function column_actions( $item ) {

        $output = '<div class="row-actions">';

            if( $this->member->is_member() )
                $output .= '<a href="#" class="pms-edit-subscription-details button button-secondary">' . __( 'Edit', 'paid-member-subscriptions' ) . '</a>';

            $output .= '<a href="#" class="pms-edit-subscription-details-cancel button">' . __( 'Cancel', 'paid-member-subscriptions' ) . '</a>';

            if( $this->member->is_member() )
                $output .= '<span class="trash"><a onclick="return confirm( \'' . __( "Are you sure you want to remove this Subscription Plan?", "paid-member-subscriptions" ) . ' \' )" href="' . esc_url( wp_nonce_url( add_query_arg( array( 'pms-action' => 'member_remove_subscription_plan', 'subscription_plan_id' => $item['subscription_plan_id'], 'member_id' => $this->member->user_id ) ), 'pms_member_nonce' ) ) . '" class="submitdelete">' . __( 'Remove', 'paid-member-subscriptions' ) . '</a></span>';

        $output .= '</div>';

        return $output;

    }

}