<?php
/**
 * PMS - WooCommerce integration
 *
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Add tab for WooCommerce integration under PMS Settings page
 *
 * @param array $pms_tabs The PMS Settings tabs
 * @return mixed
 */
function pms_woo_add_woocommerce_tab( $pms_tabs ){

    $pms_tabs['woocommerce'] = __('WooCommerce Integration', 'paid-member-subscriptions');
    return $pms_tabs;
}
add_filter('pms-settings-page_tabs', 'pms_woo_add_woocommerce_tab');


/**
 * Add content for WooCommerce integration tab
 *
 * @param array $options The PMS settings options
 */
function pms_woo_add_woocommerce_tab_content ( $options ){

    // Get active tab
    $active_tab = ( isset( $_REQUEST['nav_tab'] ) ? trim( $_REQUEST['nav_tab'] ) : 'general' );

    // Output Woocommerce tab content
    include_once('views/view-settings-tab-woocommerce-integration.php');

}
add_action('pms-settings-page_after_tabs', 'pms_woo_add_woocommerce_tab_content');

/**
 * Sanitize PMS WooCommerce integration settings
 *
 * @param array $options The PMS settings options
 * @return mixed
 */
function pms_woo_sanitize_settings( $options ){

    if ( isset( $options['woocommerce']['cumulative_discounts'] ) ){
        $options['woocommerce']['cumulative_discounts'] = (int) $options['woocommerce']['cumulative_discounts'];
    }

    if ( isset( $options['woocommerce']['exclude_on_sale'] ) ){
        $options['woocommerce']['exclude_on_sale'] = (int) $options['woocommerce']['exclude_on_sale'];
    }

    if ( isset( $options['woocommerce']['product_discounted_message'] ) ){
        $options['woocommerce']['product_discounted_message'] =  wp_kses_post( $options['woocommerce']['product_discounted_message'] );
    }

    return $options;
}
add_filter( 'pms_sanitize_settings', 'pms_woo_sanitize_settings' );


/**
 * Get WooCommerce version number
 *
 * @return null|string
 */
function pms_get_woo_version(){

    return defined( 'WC_VERSION' ) && WC_VERSION ? WC_VERSION : null;

}

/**
 * Returns true if the installed WooCommerce $version is greater or equal to $version
 *
 * @param string $version the version to compare
 *
 * @return boolean true if the installed version of WooCommerce is >= $version
 * */
function pms_is_woo_version_gte( $version ) {

    $woo_version = pms_get_woo_version();
    return $woo_version && version_compare( $woo_version, $version, '>=' );

}

/**
 *  Enqueue admin scripts required for product content restriction
 *
 */
function pms_woo_enqueue_admin_scripts_content_restriction(){
    global $post_type;

    // make sure to load script only on Product post type
    if ( $post_type == 'product' ) {

        if (file_exists(PMS_PLUGIN_DIR_PATH . 'extend/woocommerce/assets/js/admin/meta-box-product-content-restriction.js')) {
            wp_enqueue_script('pms-meta-box-product-content-restriction-js', PMS_PLUGIN_DIR_URL . 'extend/woocommerce/assets/js/admin/meta-box-product-content-restriction.js', array('jquery'), PMS_VERSION);
        }

    }
}

/**
 *  Enqueue admin scripts required for product membership discounts
 *
 */
function pms_woo_enqueue_admin_scripts_membership_discounts(){

    global $wp_scripts, $post_type;

    // make sure to load scripts only on Product and Subscription Plan post types
    if ( ( $post_type == 'product' ) || ( $post_type == 'pms-subscription' ) ) {

        // Try to detect if chosen has already been loaded; We use it for Product Discounts metabox - multiple select, under Subscription Plan
        $found_chosen = false;

        foreach ($wp_scripts as $wp_script) {
            if (!empty($wp_script['src']) && strpos($wp_script['src'], 'chosen') !== false)
                $found_chosen = true;
        }

        if (!$found_chosen) {
            wp_enqueue_script('pms-chosen', PMS_PLUGIN_DIR_URL . 'assets/libs/chosen/chosen.jquery.min.js', array('jquery'), PMS_VERSION);
            wp_enqueue_style('pms-chosen', PMS_PLUGIN_DIR_URL . 'assets/libs/chosen/chosen.css', array(), PMS_VERSION);
        }

        // If the file exists, enqueue it
        if (file_exists(PMS_PLUGIN_DIR_PATH . 'extend/woocommerce/assets/js/admin/meta-box-membership-discounts.js')) {

            wp_enqueue_script('pms-meta-box-product-membership-discounts-js', PMS_PLUGIN_DIR_URL . 'extend/woocommerce/assets/js/admin/meta-box-membership-discounts.js', array('jquery'), PMS_VERSION);
            wp_localize_script('pms-meta-box-product-membership-discounts-js', 'pms_woo_admin_vars', array(
                'currency_symbol' => get_woocommerce_currency_symbol(),
                'strings' => array(
                    'Active' => __('Active', 'paid-member-subscriptions'),
                    'Inactive' => __('Inactive', 'paid-member-subscriptions'),
                    'No discounts yet' => __('There are no discounts yet. Click below to add one.', 'paid-member-subscriptions'),
                    'Products' => __('Products', 'paid-member-subscriptions'),
                    'Product Categories' => __('Product Categories', 'paid-member-subscriptions'),
                    'Select...' => __('Select... or leave blank to apply to all', 'paid-member-subscriptions'),
                    'Percent' => __('Percent', 'paid-member-subscriptions'),
                    'Fixed' => __('Fixed', 'paid-member-subscriptions'),
                    'Choose' => __('Choose...', 'paid-member-subscriptions'),
                    'Remove this discount' => __('Remove this discount', 'paid-member-subscriptions'),
                    'Are you sure you want to remove this discount?' => __('Are you sure you want to remove this discount?', 'paid-member-subscriptions')
                )
            ));

        }

        // Back-end css
        if (file_exists(PMS_PLUGIN_DIR_PATH . 'extend/woocommerce/assets/css/back-end.css'))
            wp_enqueue_style('pms-meta-box-membership-discounts-style', PMS_PLUGIN_DIR_URL . 'extend/woocommerce/assets/css/back-end.css', array(), PMS_VERSION);

    }

}



/**
 * Include required files for Restricting Product Purchase/Viewing and Membership Discounts
 *
 */
function pms_woo_include_files(){

    // Load scripts for product content restriction
    add_action('admin_enqueue_scripts', 'pms_woo_enqueue_admin_scripts_content_restriction');

    // Restrict product purchase or viewing based on subscription plan or logged in status
    if (file_exists(PMS_PLUGIN_DIR_PATH . 'extend/woocommerce/functions-content-restriction.php'))
        include_once PMS_PLUGIN_DIR_PATH . 'extend/woocommerce/functions-content-restriction.php';

    // Check if WooCommerce version is greater than 3.0, as we don't support membership discounts for older versions
    if ( pms_is_woo_version_gte('3.0') ) {

        // Load scripts for product membership discounts
        add_action('admin_enqueue_scripts', 'pms_woo_enqueue_admin_scripts_membership_discounts');

        // Add meta-box for adding membership discounts per individual products
        if (file_exists(PMS_PLUGIN_DIR_PATH . 'extend/woocommerce/includes/admin/meta-boxes/class-meta-box-product-membership-discounts.php'))
            include_once PMS_PLUGIN_DIR_PATH . 'extend/woocommerce/includes/admin/meta-boxes/class-meta-box-product-membership-discounts.php';

        // Add meta-box for adding product discounts per subscription plan
        if (file_exists(PMS_PLUGIN_DIR_PATH . 'extend/woocommerce/includes/admin/meta-boxes/class-meta-box-subscription-plan-product-discounts.php'))
            include_once PMS_PLUGIN_DIR_PATH . 'extend/woocommerce/includes/admin/meta-boxes/class-meta-box-subscription-plan-product-discounts.php';

        // Modify prices viewed by an active member based on existing membership discounts (set per subscription plan and per product)
        if (file_exists(PMS_PLUGIN_DIR_PATH . 'extend/woocommerce/includes/class-pms-woo-subscription-discounts.php'))
            include_once PMS_PLUGIN_DIR_PATH . 'extend/woocommerce/includes/class-pms-woo-subscription-discounts.php';
    }


}
add_action('plugins_loaded', 'pms_woo_include_files');





