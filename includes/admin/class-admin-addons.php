<?php
/*
 * Extends core PMS_Submenu_Page base class to create and add custom functionality
 * for the add-ons page in the admin section
 *
 * The Add-ons page will contain a listing of all the available add-ons for PMS,
 * allowing the user to purchase, install or activate a certain add-on.
 *
 */
Class PMS_Submenu_Page_Addons extends PMS_Submenu_Page {

    /*
     * Method that initializes the class
     *
     * */
    public function init() {

        // Hook the output method to the parent's class action for output instead of overwriting the
        // output method
        add_action( 'pms_output_content_submenu_page_' . $this->menu_slug, array( $this, 'output' ) );

        add_action( 'wp_ajax_pms_add_on_activate', array( $this, 'add_on_activate' ) );
        add_action( 'wp_ajax_pms_add_on_deactivate', array( $this, 'add_on_deactivate' ) );
        
        add_action( 'wp_ajax_pms_add_on_save_serial', array( $this, 'add_on_save_serial' ) );

    }

    /*
     * Method to output the content in the Add-ons page
     *
     * */
    public function output(){

        include_once 'views/view-page-addons.php';

    }

    /*
    * Function that returns the array of add-ons from cozmoslabs.com if it finds the file
    * If something goes wrong it returns false
    *
    * @since v.2.1.0
    */
    static function  add_ons_get_remote_content() {

        $response = wp_remote_get( 'http://www.cozmoslabs.com/wp-content/plugins/cozmoslabs-products-add-ons/paid-member-subscriptions-add-ons.json' );

        if( is_wp_error($response) ) {
            return false;
        } else {
            $json_file_contents = $response['body'];
            $pms_add_ons = json_decode( $json_file_contents, true );
        }

        if( !is_object( $pms_add_ons ) && !is_array( $pms_add_ons ) ) {
            return false;
        }

        return $pms_add_ons;
    }


    /**
     * Function that is triggered through Ajax to activate an add-on
     *
     */
    function add_on_activate(){
        
        check_ajax_referer( 'pms-activate-addon', 'nonce' );

        if( current_user_can( 'manage_options' ) ){

            // Setup variables from POST
            $pms_add_on_to_activate = sanitize_text_field( $_POST['pms_add_on_to_activate'] );
            $response               = (int)$_POST['pms_add_on_index'];

            if( !empty( $pms_add_on_to_activate ) && !is_plugin_active( $pms_add_on_to_activate )) {
                activate_plugin( $pms_add_on_to_activate );
            }

            if( !empty( $response ) || $response == 0 )
                echo $response;
        }

        wp_die();
    }

    /**
     * Function that is triggered through Ajax to deactivate an add-on
     *
     */
    function add_on_deactivate() {

        check_ajax_referer( 'pms-activate-addon', 'nonce' );

        if( current_user_can( 'manage_options' ) ) {

            // Setup variables from POST
            $pms_add_on_to_deactivate = sanitize_text_field( $_POST['pms_add_on_to_deactivate'] );
            $response                 = (int)$_POST['pms_add_on_index'];

            if( !empty( $pms_add_on_to_deactivate ))
                deactivate_plugins( $pms_add_on_to_deactivate );

            if( !empty( $response ) || $response == 0 )
                echo $response;
        }

        wp_die();

    }

    /**
     * Ajax function to save the addon serial
     *
     */
    function add_on_save_serial() {

        $pms_add_on_slug        = sanitize_text_field( $_POST['pms_add_on_slug'] );
        $pms_add_on_unique_name = sanitize_text_field( $_POST['pms_add_on_unique_name'] );
        $pms_serial_value       = sanitize_text_field( $_POST['pms_serial_value'] );

        $response = '';

        if( !empty( $pms_add_on_slug ) ){
            if( !empty( $pms_serial_value ) ) {
                update_option($pms_add_on_slug . '_serial_number', $pms_serial_value);
                $response = PMS_Submenu_Page_Addons::add_on_check_serial_number( $pms_serial_value, $pms_add_on_slug, $pms_add_on_unique_name );
            }
            else
                delete_option( $pms_add_on_slug.'_serial_number' );
        }
        
        die( $response );
    }


    //the function to check the validity of the serial number and save a variable in the DB; purely visual
    static function add_on_check_serial_number( $serial, $add_on_slug, $pms_add_on_unique_name ){
        $remote_url = 'http://updatemetadata.cozmoslabs.com/checkserial/?serialNumberSent='.$serial;
        if( !empty( $pms_add_on_unique_name ) )
            $remote_url = $remote_url.'&uniqueproduct='.$pms_add_on_unique_name;
        $remote_response = wp_remote_get( $remote_url );
        $response = PMS_Submenu_Page_Addons::add_on_update_serial_status( $remote_response, $add_on_slug );
        wp_clear_scheduled_hook( "check_plugin_updates-". $add_on_slug );
        return $response;
    }

    /* function to update the serial number status */
    static function add_on_update_serial_status( $response, $add_on_slug ){
        if (is_wp_error($response)) {
            update_option( 'pms_add_on_'.$add_on_slug.'_serial_status', 'serverDown'); //server down
            return 'serverDown';
        } elseif ((trim($response['body']) != 'notFound') && (trim($response['body']) != 'found') && (trim($response['body']) != 'expired') && (strpos( $response['body'], 'aboutToExpire' ) === false)) {
            update_option( 'pms_add_on_'. $add_on_slug .'_serial_status', 'serverDown'); //unknown response parameter
            update_option( 'pms_add_on_'. $add_on_slug .'_serial_number', ''); //reset the entered password, since the user will need to try again later
            return 'serverDown';

        } else {
            update_option( 'pms_add_on_'.$add_on_slug.'_serial_status', trim($response['body'])); //either found, notFound or expired
            return trim( $response['body'] );
        }
    }
}

$pms_submenu_page_addons = new PMS_Submenu_Page_Addons( 'paid-member-subscriptions', __( 'Add-ons', 'paid-member-subscriptions' ), __( 'Add-ons', 'paid-member-subscriptions' ), 'manage_options', 'pms-addons-page', 30 );
$pms_submenu_page_addons->init();