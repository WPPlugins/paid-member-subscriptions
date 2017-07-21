<?php
/*
 * Extends core PMS_Submenu_Page base class to create and add custom functionality
 * for the payments section in the admin section
 *
 */
Class PMS_Submenu_Page_Payments extends PMS_Submenu_Page {


    /*
     * Method that initializes the class
     *
     */
    public function init() {

        // Enqueue admin scripts
        add_action( 'pms_submenu_page_enqueue_admin_scripts_' . $this->menu_slug, array( $this, 'admin_scripts' ) );

        // Hook the output method to the parent's class action for output instead of overwriting the
        // output method
        add_action( 'pms_output_content_submenu_page_' . $this->menu_slug, array( $this, 'output' ) );

        // Process different actions within the page
        add_action( 'init', array( $this, 'process_data' ) );

    }


    /*
     * Method to enqueue admin scripts
     *
     */
    public function admin_scripts() {

        wp_enqueue_script( 'jquery-ui-datepicker' );
        wp_enqueue_style('jquery-style', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');

    }


    /*
     * Method that processes data on payment admin pages
     *
     */
    public function process_data() {

        // Verify correct nonce
        if( !isset( $_REQUEST['_wpnonce'] ) || !wp_verify_nonce( $_REQUEST['_wpnonce'], 'pms_payment_nonce' ) )
            return;

        // Get current actions
        $action = !empty( $_REQUEST['pms-action'] ) ? $_REQUEST['pms-action'] : '';

        if( empty($action) )
            return;


        // Deleting a payment
        if( $action == 'delete_payment' ) {

            // Get payment id
            $payment_id = ( !empty( $_REQUEST['payment_id'] ) ? (int)$_REQUEST['payment_id'] : 0 );

            // Do nothing if there's no payment to work with
            if( $payment_id == 0 )
                return;

            $payment = pms_get_payment( $payment_id );

            if( $payment->remove() )
                $this->add_admin_notice( __( 'Payment successfully deleted.', 'paid-member-subscriptions' ), 'updated' );

        }


        // Saving / editing a payment
        if( $action == 'edit_payment' ) {

            // Get payment id
            $payment_id = ( !empty( $_REQUEST['payment_id'] ) ? (int)$_REQUEST['payment_id'] : 0 );

            // Do nothing if there's no payment to work with
            if( $payment_id == 0 )
                return;

            // Get payment and extract the object/payment vars with their value
            $payment      = pms_get_payment( $payment_id );
            $payment_vars = get_object_vars( $payment );

            // Pass through each payment var and see if the value provided by the admin is different
            foreach( $payment_vars as $payment_var => $payment_var_val ) {

                // Get the value from the form field
                $post_field_value = isset( $_POST['pms-payment-' . str_replace('_', '-', $payment_var) ] ) ? sanitize_text_field( $_POST['pms-payment-' . str_replace('_', '-', $payment_var) ] ) : '';

                // If we're handling the date value take into account the time zone difference
                // In the db we want to have universal time, not local time
                if( $payment_var == 'date' )
                    $post_field_value = date( 'Y-m-d H:i:s', strtotime( $post_field_value ) - ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) );

                // If the form value exists and differs from the one saved in the payment
                // replace it, if not simply unset the value from the object vars array
                if( $post_field_value !== '' && $post_field_value != $payment_var_val )
                    $payment_vars[$payment_var] = $post_field_value;
                else
                    unset( $payment_vars[$payment_var] );
            }

            // Subscription_id needs to be subscription_plan_id
            // This is not very consistent and should be modified
            if( !empty( $payment_vars['subscription_id'] ) ) {
                $payment_vars['subscription_plan_id'] = $payment_vars['subscription_id'];
                unset( $payment_vars['subscription_id'] );
            }

            // Update payment
            if( empty( $payment_vars ) )
                $updated = true;
            else
                $updated = $payment->update( $payment_vars );

            if( $updated )
                $this->add_admin_notice( __( 'Payment successfully updated.', 'paid-member-subscriptions' ), 'updated' );

        }

    }


    /*
     * Method to output content in the custom page
     *
     */
    public function output() {

        // Display the edit payment view
        if( isset( $_GET['pms-action'] ) && $_GET['pms-action'] == 'edit_payment' && !empty( $_GET['payment_id'] ) )
            include_once 'views/view-page-payments-edit.php';

        // Display all payments table
        else
            include_once 'views/view-page-payments-list-table.php';

    }


	/*
     * Method that adds Screen Options to Payments page
     *
     */
	public function add_screen_options() {

		$args = array(
			'label' => 'Payments per page',
			'default' => 10,
			'option' => 'pms_payments_per_page'
		);

		add_screen_option( 'per_page', $args );

	}

}

$pms_submenu_page_payments = new PMS_Submenu_Page_Payments( 'paid-member-subscriptions', __( 'Payments', 'paid-member-subscriptions' ), __( 'Payments', 'paid-member-subscriptions' ), 'manage_options', 'pms-payments-page', 20 );
$pms_submenu_page_payments->init();