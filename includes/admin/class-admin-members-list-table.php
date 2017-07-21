<?php

// WP_List_Table is not loaded automatically in the plugins section
if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


/*
 * Extent WP default list table for our custom members section
 *
 */
Class PMS_Members_List_Table extends WP_List_Table {

    /**
     * Members per page
     *
     * @access public
     * @var int
     */
    public $items_per_page;

    /**
     * Members table data
     *
     * @access public
     * @var array
     */
    public $data;

    /**
     * Members table views count
     *
     * @access public
     * @var array
     */
    public $views_count = array();

    /*
     * Constructor function
     *
     */
    public function __construct() {

        parent::__construct( array(
            'singular'  => 'member',
            'plural'    => 'members',
            'ajax'      => false
        ));

        // Set data
        $this->set_table_data();

        // Set items per page
        $items_per_page = get_user_meta( get_current_user_id(), 'pms_members_per_page', true );

        if( empty( $items_per_page ) ) {
            $screen     = get_current_screen();
            $per_page   = $screen->get_option('per_page');
            $items_per_page = $per_page['default'];
        }

        $this->items_per_page = $items_per_page;

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
            'user_id'           => __( 'User ID', 'paid-member-subscriptions' ),
            'username'          => __( 'Username', 'paid-member-subscriptions' ),
            'email'             => __( 'E-mail', 'paid-member-subscriptions' ),
            'subscriptions'     => __( 'Subscribed to', 'paid-member-subscriptions' )
        );

        return apply_filters( 'pms_members_list_table_columns', $columns );

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

        return array(
            'user_id'           => array( 'user_id', false ),
            'username'          => array( 'username', false )
        );

    }


    /*
     * Returns the possible views for the members list table
     *
     */
    protected function get_views() {

        return apply_filters( 'pms_members_list_table_get_views', array(
            'all'       => '<a href="' . remove_query_arg( array( 'pms-view', 'paged' ) ) . '" ' . ( !isset( $_GET['pms-view'] ) ? 'class="current"' : '' ) . '>All <span class="count">(' . ( isset( $this->views_count['all'] ) ? $this->views_count['all'] : '' ) . ')</span></a>',
            'active'    => '<a href="' . add_query_arg( array( 'pms-view' => 'active', 'paged' => 1 ) ) . '" ' . ( isset( $_GET['pms-view'] ) &&$_GET['pms-view'] == 'active' ? 'class="current"' : '' ) . '>Active <span class="count">(' . ( isset( $this->views_count['active'] ) ? $this->views_count['active'] : '' ) . ')</span></a>',
            'expired'   => '<a href="' . add_query_arg( array( 'pms-view' => 'expired', 'paged' => 1 ) ) . '" ' . ( isset( $_GET['pms-view'] ) &&$_GET['pms-view'] == 'expired' ? 'class="current"' : '' ) . '>Expired <span class="count">(' . ( isset( $this->views_count['expired'] ) ? $this->views_count['expired'] : '' ) . ')</span></a>',
            'pending'   => '<a href="' . add_query_arg( array( 'pms-view' => 'pending', 'paged' => 1 ) ) . '" ' . ( isset( $_GET['pms-view'] ) &&$_GET['pms-view'] == 'pending' ? 'class="current"' : '' ) . '>Pending <span class="count">(' . ( isset( $this->views_count['pending'] ) ? $this->views_count['pending'] : '' ) . ')</span></a>'
        ));

    }


    /*
     * Overwrite parent display tablenav to avoid WP's default nonce for bulk actions
     *
     * @param string @which     - which side of the table ( top or bottom )
     *
     */
    protected function display_tablenav( $which ) {

        echo '<div class="tablenav ' . esc_attr( $which ) . '">';

            $this->extra_tablenav( $which );
            $this->pagination( $which );

            echo '<br class="clear" />';
        echo '</div>';

    }


    /*
     * Method to add extra actions before and after the table
     * Replaces parent method
     *
     * @param string @which     - which side of the table ( top or bottom )
     *
     */
    public function extra_tablenav( $which ) {

        if( $which == 'bottom' )
            return;

        echo '<div style="display: inline-block;">';

            /*
             * Add a custom select box to
             *
             */
            $subscription_plans = pms_get_subscription_plans( false );
            echo '<select name="pms-filter-subscription-plan">';
                echo '<option value="">' . __( 'Filter by Subscription Plan...', 'paid-member-subscriptions' ) . '</option>';

                foreach( $subscription_plans as $subscription_plan )
                    echo '<option value="' . $subscription_plan->id . '" ' . ( !empty( $_GET['pms-filter-subscription-plan'] ) ? selected( $subscription_plan->id, $_GET['pms-filter-subscription-plan'], false ) : '' ) . '>' . $subscription_plan->name . '</option>';
            echo '</select>';

            /*
             * Filter button
             *
             */
            echo '<input class="button button-secondary" type="submit" value="' . __( 'Filter', 'paid-member-subscriptions' ) . '" />';

        echo '</div>';

    }


    /*
     * Sets the table data
     *
     * @return array
     *
     */
    public function set_table_data() {

        $data = array();

        $args = array();

        // If it's a search query send search parameter through $args
        if ( !empty($_REQUEST['s']) ) {
            $args = array(
                'order'                => 'ASC',
                'orderby'              => 'ID',
                'offset'               => '',
                'number'               => '',
                'subscription_plan_id' => '',
                'search'               => $_REQUEST['s']
            );
        }

        // Set subscription plan if it exists
        if( !empty( $_GET['pms-filter-subscription-plan'] ) ) {
            $args['subscription_plan_id'] = (int)$_GET['pms-filter-subscription-plan'];
        }

        // Get the members
        $members = pms_get_members($args);

        // Set views count array to 0, we use this to display the count
        // next to the views links (all, active, expired, etc)
        $views = $this->get_views();
        foreach( $views as $view_slug => $view_link) {
            $this->views_count[$view_slug] = 0;
        }

        // Get the current view to filter results
        $selected_view = ( isset( $_GET['pms-view'] ) ? sanitize_text_field( $_GET['pms-view'] ) : '' );

        foreach( $members as $member ) {

            // Get member subscription statuses into an array
            $subscriptions_statuses = array();
            foreach( $member->subscriptions as $subscription ) {
                $subscriptions_statuses[] = $subscription['status'];
            }

            // Increment view count for each of the views if we find the view in the
            // subscription statuses array
            foreach( $views as $view_slug => $view_link ) {
                if( in_array( $view_slug, $subscriptions_statuses ) )
                    $this->views_count[$view_slug]++;
            }
            $this->views_count['all']++;

            // If this is one of the status views we do not wish to add data to the table
            if( !empty( $selected_view ) ) {
                if( !in_array( $selected_view, $subscriptions_statuses ) )
                    continue;
            }


            $data[] = apply_filters( 'pms_members_list_table_entry_data', array(
                'user_id'           => $member->user_id,
                'username'          => '<strong><a href="' . add_query_arg( array( 'pms-action' => 'edit_member', 'member_id' => $member->user_id ) ) . '">' . esc_attr( $member->username ) . '</a></strong>',
                'email'             => $member->email,
                'subscriptions'     => $member->subscriptions
            ), $member );

        }

        $this->data = $data;

    }



    /*
     * Populates the items for the table
     *
     * @param array $item           - data for the current row
     *
     * @return string
     *
     */
    public function prepare_items() {

        $columns        = $this->get_columns();
        $hidden_columns = $this->get_hidden_columns();
        $sortable       = $this->get_sortable_columns();

        $this->_column_headers = array( $columns, $hidden_columns, $sortable );


        $data = $this->data;
        usort( $data, array( $this, 'sort_data' ) );

        $paged = ( isset( $_GET['paged'] ) ? (int)$_GET['paged'] : 1 );

        $this->set_pagination_args( array(
            'total_items' => count( $data ),
            'per_page'    => $this->items_per_page
        ) );

        $data = array_slice( $data, $this->items_per_page * ( $paged-1 ), $this->items_per_page );

        $this->items = $data;

    }


    /*
     * Sorts the data by the variables in GET
     *
     */
    public function sort_data( $a, $b ) {

        // Set defaults
        $orderby = 'username';
        $order = 'asc';

        // If orderby is set, use this as the sort column
        if(!empty($_GET['orderby']))
        {
            $orderby = sanitize_text_field( $_GET['orderby'] );
        }

        // If order is set use this as the order
        if(!empty($_GET['order']))
        {
            $order = sanitize_text_field( $_GET['order'] );
        }

        $result = strnatcmp( $a[$orderby], $b[$orderby] );

        if($order === 'asc')
        {
            return $result;
        }

        return -$result;

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

        return !empty( $item[ $column_name ] ) ? $item[ $column_name ] : '';

    }


    /*
     * Return data that will be displayed in the username column
     *
     * @param array $item   - row data
     *
     * @return string
     *
     */
    public function column_username( $item ) {

        $actions = array();

        // Add an edit user action for each member
        $actions['edit'] = '<a href="' . add_query_arg( array( 'pms-action' => 'edit_member', 'member_id' => $item['user_id'] ) ) . '">' . __( 'Edit Member', 'paid-member-subscriptions' ) . '</a>';

        // Return value saved for username and also the row actions
        return $item['username'] . $this->row_actions( $actions );

    }


    /*
     * Return data that will be displayed in the subscriptions column
     *
     * @param array $item   - row data
     *
     * @return string
     *
     */
    public function column_subscriptions( $item ) {

        $output = '';

        foreach( $item['subscriptions'] as $member_subscription ) {

            $subscription_plan = pms_get_subscription_plan( $member_subscription['subscription_plan_id'] );

            $output .= '<span class="pms-member-list-subscription pms-has-bubble">';

                $output .= apply_filters( 'pms_list_table_' . $this->_args['plural'] . '_show_status_dot', '<span class="pms-status-dot ' . $member_subscription['status'] . '"></span>' );

                $output .= ( !empty( $subscription_plan->id ) ? $subscription_plan->name : sprintf( __( 'Subscription Plan Not Found - ID: %s', 'paid-member-subscriptions' ), $member_subscription['subscription_plan_id'] ) );

                $output .= '<div class="pms-bubble">';

                    $statuses = pms_get_member_statuses();

                    $output .= '<div><span class="alignleft">' . __( 'Start date', 'paid-member-subscriptions' ) . '</span><span class="alignright">' . pms_sanitize_date( $member_subscription['start_date'] ) . '</span></div>';
                    $output .= '<div><span class="alignleft">' . __( 'Expiration date', 'paid-member-subscriptions' ) . '</span><span class="alignright">' . pms_sanitize_date( $member_subscription['expiration_date'] ) . '</span></div>';
                    $output .= '<div><span class="alignleft">' . __( 'Status', 'paid-member-subscriptions' ) . '</span><span class="alignright">' . ( isset( $statuses[ $member_subscription['status'] ] ) ? $statuses[ $member_subscription['status'] ] : '' ) . '</span></div>';

                $output .= '</div>';

            $output .= '</span>';

        }

        return $output;

    }


    /*
     * Display if no items are found
     *
     */
    public function no_items() {

        echo __( 'No members found', 'paid-member-subscriptions' );

    }

}