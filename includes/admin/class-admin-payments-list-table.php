<?php

// WP_List_Table is not loaded automatically in the plugins section
if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


/*
 * Extent WP default list table for our custom payments section
 *
 */
Class PMS_Payments_List_Table extends WP_List_Table {

    /**
     * Payments per page
     *
     * @access public
     * @var int
     */
    public $items_per_page;

    /**
     * Payments table data
     *
     * @access public
     * @var array
     */
    public $data;

    /**
     * Payments table views count
     *
     * @access public
     * @var array
     */
    public $views_count = array();

    /**
     * The total number of items
     *
     * @access private
     * @var int
     *
     */
    private $total_items;

    /*
     * Constructor function
     *
     */
    public function __construct() {

        parent::__construct( array(
            'singular'  => 'payment',
            'plural'    => 'payments',
            'ajax'      => false
        ));

        // Set items per page
        $items_per_page = get_user_meta( get_current_user_id(), 'pms_payments_per_page', true );

        if( empty( $items_per_page ) ) {
            $screen     = get_current_screen();
            $per_page   = $screen->get_option('per_page');
            $items_per_page = $per_page['default'];
        }

        $this->items_per_page = $items_per_page;

        // Set table data
        $this->set_table_data();

    }

    /*
     * Overwrites the parent class.
     * Define the columns for the payments
     *
     * @return array
     *
     */
    public function get_columns() {

        $columns = array(
            'id'             => __( 'ID', 'paid-member-subscriptions' ),
            'username'       => __( 'User', 'paid-member-subscriptions' ),
            'subscriptions'  => __( 'Subscription', 'paid-member-subscriptions' ),
            'amount'         => __( 'Amount', 'paid-member-subscriptions' ),
            'date'           => __( 'Date / Time', 'paid-member-subscriptions' ),
            'type'           => __( 'Type', 'paid-member-subscriptions' ),
            'transaction_id' => __( 'Transaction ID', 'paid-member-subscriptions' ),
            'status'         => __( 'Status', 'paid-member-subscriptions' ),
        );

        return apply_filters( 'pms_payments_list_table_columns', $columns );

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
            'username'  => array( 'username', false ),
            'status'    => array( 'status', false )
        );

    }


    /*
     * Returns the possible views for the members list table
     *
     */
    protected function get_views() {

        return apply_filters( 'pms_payments_list_table_get_views', array(
            'all'       => '<a href="' . remove_query_arg( array( 'pms-view', 'paged' ) ) . '" ' . ( !isset( $_GET['pms-view'] ) ? 'class="current"' : '' ) . '>All <span class="count">(' . ( isset( $this->views_count['all'] ) ? $this->views_count['all'] : '' ) . ')</span></a>',
            'completed' => '<a href="' . add_query_arg( array( 'pms-view' => 'completed', 'paged' => 1 ) ) . '" ' . ( isset( $_GET['pms-view'] ) &&$_GET['pms-view'] == 'completed' ? 'class="current"' : '' ) . '>Completed <span class="count">(' . ( isset( $this->views_count['completed'] ) ? $this->views_count['completed'] : '' ) . ')</span></a>',
            'pending'   => '<a href="' . add_query_arg( array( 'pms-view' => 'pending', 'paged' => 1 ) ) . '" ' . ( isset( $_GET['pms-view'] ) &&$_GET['pms-view'] == 'pending' ? 'class="current"' : '' ) . '>Pending <span class="count">(' . ( isset( $this->views_count['pending'] ) ? $this->views_count['pending'] : '' ) . ')</span></a>',
            'refunded'  => '<a href="' . add_query_arg( array( 'pms-view' => 'refunded', 'paged' => 1 ) ) . '" ' . ( isset( $_GET['pms-view'] ) &&$_GET['pms-view'] == 'refunded' ? 'class="current"' : '' ) . '>Refunded <span class="count">(' . ( isset( $this->views_count['refunded'] ) ? $this->views_count['refunded'] : '' ) . ')</span></a>'
        ));

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

        $selected_view = ( isset( $_GET['pms-view'] ) ? sanitize_text_field( $_GET['pms-view'] ) : '' );
        $paged         = ( isset( $_GET['paged'] )    ? (int)$_GET['paged'] : 1 );

        /**
         * Set payments arguments
         *
         */
        $args['number'] = $this->items_per_page;
        $args['offset'] = ( $paged - 1 ) * $this->items_per_page;
        $args['status'] = $selected_view;

        if ( !empty($_REQUEST['s']) ) {
            $args['search'] = $_REQUEST['s'];
        }

        /**
         * Get payments
         *
         */
        $payments = pms_get_payments( $args );


        /**
         * Set views count for each view ( a.k.a payment status )
         *
         */
        $views = $this->get_views();

        $args = array();

        if ( !empty($_REQUEST['s']) ) {
            $args['search'] = $_REQUEST['s'];
        }

        foreach( $views as $view_slug => $view_link) {

            $args['status'] = ( $view_slug != 'all' ? $view_slug : '' );

            $this->views_count[$view_slug] = pms_get_payments_count( $args );

        }


        /**
         * Set data array
         *
         */
        foreach( $payments as $payment ) {

            if( !empty($selected_view) && $payment->status != $selected_view )
                continue;

            $user = get_user_by( 'id', $payment->user_id );

            if( $user )
                $username = $user->data->user_login;
            else
                $username = __( 'User no longer exists', 'paid-member-subscriptions' );

            $data[] = apply_filters( 'pms_payments_list_table_entry_data', array(
                'id'            => $payment->id,
                'username'      => $username,
                'subscription'  => $payment->subscription_id,
                'amount'        => $payment->amount,
                'date'          => date( 'F d, Y H:i:s', strtotime( $payment->date ) + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ),
                'type'          => pms_get_payment_type_name( $payment->type ),
                'transaction_id'=> $payment->transaction_id,
                'status'        => $payment->status,
                'discount_code' => $payment->discount_code
            ), $payment );
        }


        /**
         * Set all items
         *
         */
        $this->total_items = $this->views_count[ ( !empty( $selected_view ) ? $selected_view : 'all' ) ];


        /**
         * Set table data
         *
         */
        $this->data = $data;

    }


    /**
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

        $this->set_pagination_args( array(
            'total_items' => $this->total_items,
            'per_page'    => $this->items_per_page
        ));

        $this->items = $this->data;

    }


    /**
     * Return data that will be displayed in each column
     *
     * @param array $item           - data for the current row
     * @param string $column_name   - name of the current column
     *
     * @return string
     *
     */
    public function column_default( $item, $column_name ) {

        return !empty( $item[ $column_name ] ) ? $item[ $column_name ] : '-';

    }


    /**
     * Return data that will be displayed in the username column
     *
     * @param array $item   - data of the current row
     *
     * @return string
     *
     */
    public function column_username( $item ) {

        // Add row actions
        $actions = array();

        // Edit payment row action
        $actions['edit'] = '<a href="' . add_query_arg( array( 'pms-action' => 'edit_payment', 'payment_id' => $item['id'] ) ) . '">' . __( 'Edit Payment', 'paid-member-subscriptions' ) . '</a>';

        // Delete row action
        $actions['delete'] = '<a onclick="return confirm( \'' . __( "Are you sure you want to delete this Payment?", "paid-member-subscriptions" ) . ' \' )" href="' . wp_nonce_url( add_query_arg( array( 'pms-action' => 'delete_payment', 'payment_id' => $item['id'] ) ), 'pms_payment_nonce' ) . '">' . __( 'Delete', 'paid-member-subscriptions' ) . '</a>';

        $output = $item['username'];

        $output .= $this->row_actions( $actions );

        return $output;

    }


    /**
     * Return data that will be displayed in the subscriptions column
     *
     * @param array $item   - data of the current row
     *
     * @return string
     *
     */
    public function column_subscriptions( $item ) {

        $subscription_plan = pms_get_subscription_plan( $item['subscription'] );
        $output = '<span class="pms-payment-list-subscription">' . $subscription_plan->name . '</span>';

        return $output;

    }


    /**
     * Return data that will be displayed in the status column
     *
     * @param array $item   - data of the current row
     *
     * @return string
     *
     */
    public function column_status( $item ) {

        $payment_statuses = pms_get_payment_statuses();

        $output = apply_filters( 'pms_list_table_' . $this->_args['plural'] . '_show_status_dot', '<span class="pms-status-dot ' . $item['status'] . '"></span>' );

        $output .= ( isset( $payment_statuses[ $item['status'] ] ) ? $payment_statuses[ $item['status'] ] : $item['status'] );

        return $output;

    }


    /**
     * Return data that will be displayed in the amount column
     *
     * @param array $item   - data of the current row
     *
     * @return string
     *
     */
    public function column_amount( $item ) {

        // Get currency symbol to display next to amount
        $currency_symbol = '';
        if ( get_option('pms_settings') ) {

            $settings = get_option('pms_settings');
            if ( ( function_exists('pms_get_currency_symbol') ) && isset( $settings['payments']['currency'] ) )
                $currency_symbol = pms_get_currency_symbol( $settings['payments']['currency'] );
        }

        // Check if discount code was used for this payment
        if ( !empty($item['discount_code']) ) {
            $output = '<span class="pms-has-bubble">';

            $output .= $item['amount'] . $currency_symbol . '<span class="pms-discount-dot"> % </span>';

            $output .= '<div class="pms-bubble">';
            $output .= '<div><span class="alignleft">' . __('Discount code', 'paid-member-subscriptions') . '</span><span class="alignright">' . $item['discount_code'] . '</span></div>';
            $output .= '</div>';

            $output .= '</span>';

            return $output;
        }
        else
            return $item['amount'] . $currency_symbol;

    }


    /**
     * Display if no items are found
     *
     */
    public function no_items() {

        echo __( 'No payments found', 'paid-member-subscriptions' );

    }

}