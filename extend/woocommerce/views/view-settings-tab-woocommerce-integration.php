<?php
/**
 * HTML Output for the settings page, WooCommerce Integration tab
 */
?>

<div id="pms-settings-woocommerce" class="pms-tab <?php echo ( $active_tab == 'woocommerce' ? 'tab-active' : '' ); ?>">

    <?php do_action( 'pms-settings-page_tab_woocommerce_before_content', $options ); ?>

    <div id="woocommerce-products">

        <h3><?php echo __( 'Products', 'paid-member-subscriptions' ); ?></h3>

        <div class="pms-form-field-wrapper">
            <label class="pms-form-field-label" for="woocommerce-cumulative-discounts"><?php echo __( 'Allow cumulative discounts', 'paid-member-subscriptions' ) ?></label>

            <p class="description"><input type="checkbox" id="woocommerce-cumulative-discounts" name="pms_settings[woocommerce][cumulative_discounts]" value="1" <?php echo ( isset( $options['woocommerce']['cumulative_discounts'] ) ? checked($options['woocommerce']['cumulative_discounts'], '1', false) : '' ); ?> /><?php echo __( 'By checking this option we will cumulate all discounts that apply to a specific product. <strong> By default we\'re applying only the highest discount. </strong>', 'paid-member-subscriptions' ); ?></p>
        </div>

        <div class="pms-form-field-wrapper">
            <label class="pms-form-field-label" for="woocommerce-exclude-on-sale"><?php echo __( 'Exclude products on sale ', 'paid-member-subscriptions' ) ?></label>

            <p class="description"><input type="checkbox" id="woocommerce-exclude-on-sale" name="pms_settings[woocommerce][exclude_on_sale]" value="1" <?php echo ( isset( $options['woocommerce']['exclude_on_sale'] ) ? checked($options['woocommerce']['exclude_on_sale'], '1', false) : '' ); ?> /><?php echo __( 'Do not apply any member discounts to products that are currently on sale.', 'paid-member-subscriptions' ); ?></p>
        </div>

        <?php do_action( 'pms-settings-page_woocommerce_products_after_content', $options ); ?>

    </div>

    <div id="woocommerce-products">

        <h3><?php echo __( 'Product Messages', 'paid-member-subscriptions' ); ?></h3>

        <div class="pms-form-field-wrapper">
            <label class="pms-form-field-label" for="woocommerce-product-discounted-message"><?php echo __( 'Product Discounted - Membership Required', 'paid-member-subscriptions' ) ?></label>
            <?php wp_editor( ( isset($options['woocommerce']['product_discounted_message']) ? wp_kses_post($options['woocommerce']['product_discounted_message']) : __( 'Want a discount? Become a member, sign up for a subscription plan.' ,'paid-member-subscriptions') ), 'woocommerce-product-discounted-message', array( 'textarea_name' => 'pms_settings[woocommerce][product_discounted_message]', 'editor_height' => 150 ) ); ?>
            <p class="description"> <?php echo __('Message displayed to non-members if the product has a membership discount. Displays below add to cart buttons. Leave blank to disable.','paid-member-subscriptions') ?></p>
        </div>

        <?php do_action( 'pms-settings-page_woocommerce_product_messages_after_content', $options ); ?>

    </div>

</div>