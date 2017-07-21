<?php
/*
 * Extends core PMS_Submenu_Page base class to create and add custom functionality
 * for the settings page in the admin section
 *
 * The settings page will contain several tabs where the user will be able to customize e-mails,
 * user messages and also set up payment gateways
 *
 */
Class PMS_Submenu_Page_Settings extends PMS_Submenu_Page {


    /*
     * Method that initializes the class
     *
     */
    public function init() {

        // Hook the output method to the parent's class action for output instead of overwriting the
        // output method
        add_action( 'pms_output_content_submenu_page_' . $this->menu_slug, array( $this, 'output' ) );

        add_action( $this->menu_slug . '_tab_content_payments', array( $this, 'output_tab_payment' ) );

    }


    /*
     * Method to output content in the custom page
     *
     */
    public function output() {

        // Set options
        $this->options = get_option( $this->settings_slug, array() );

        include_once 'views/view-page-settings.php';

    }


    /*
     * Callback overwrite for sanitizing settings
     *
     */
    public function sanitize_settings( $options ) {

        // Sanitize all option values
        $options = pms_array_strip_script_tags( $options );

        // If no active payment gateways are checked, add paypal_standard
        // as a default
        if( !isset( $options['payments']['active_pay_gates'] ) )
            $options['payments']['active_pay_gates'] = array('paypal_standard');

        if( isset( $options['general']['register_success_page'] ) )
            $options['general']['register_success_page'] = (int)$options['general']['register_success_page'];

        if( isset( $options['general']['restricted_post_preview']['trim_content_length'] ) )
            $options['general']['restricted_post_preview']['trim_content_length'] = (int)$options['general']['restricted_post_preview']['trim_content_length'];

        $options = apply_filters( 'pms_sanitize_settings', $options );

        // Add settings success message
        add_settings_error( 'general', 'settings_updated', __( 'Settings saved.', 'paid-member-subscriptions' ), 'updated' );

        return $options;
    }


    /*
     * Returns the tabs we want for this page
     *
     */
    private function get_tabs() {

        $tabs = array(
            'general'              => __( 'General', 'paid-member-subscriptions' ),
            'payments'             => __( 'Payments', 'paid-member-subscriptions' ),
            'content-restriction'  => __( 'Content Restriction', 'paid-member-subscriptions' ),
            'emails'               => __( 'E-Mails', 'paid-member-subscriptions' )
        );

        return apply_filters( $this->menu_slug . '_tabs', $tabs );

    }

}

$pms_submenu_page_settings = new PMS_Submenu_Page_Settings( 'paid-member-subscriptions', __( 'Settings', 'paid-member-subscriptions' ), __( 'Settings', 'paid-member-subscriptions' ), 'manage_options', 'pms-settings-page', 30, 'pms_settings' );
$pms_submenu_page_settings->init();