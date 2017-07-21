<?php
/*
 * HTML output for the payments admin edit payment page
 */

    $settings   = get_option('pms_settings');

    $payment_id = !empty( $_GET['payment_id'] ) ? (int)$_GET['payment_id'] : 0;
    $payment    = pms_get_payment( $payment_id );

    // Display nothing if this is not a valid payment
    if( !$payment->is_valid() )
        return;

    $member = pms_get_member( $payment->user_id );

?>

<div class="wrap">

    <h2><?php printf( __( 'Payment #%s', 'paid-member-subscriptions' ), $payment_id ); ?></h2>

    <form id="pms-form-edit-payment" class="pms-form" method="POST" action="<?php echo admin_url( 'admin.php?page=pms-payments-page' ); ?>">

        <!-- Hidden fields -->
        <input type="hidden" name="pms-action" value="<?php echo $_GET['pms-action']; ?>" />
        <input type="hidden" name="payment_id" value="<?php echo $payment_id; ?>" />

        <!-- User's Username -->
        <div class="pms-form-field-wrapper pms-form-field-user-name">

            <label class="pms-form-field-label"><?php echo __( 'Username', 'paid-member-subscriptions' ); ?></label>
            <span class="readonly medium"><strong><?php echo esc_html( $member->username ); ?></strong></span>

        </div>


        <!-- Payment Subscription -->
        <div class="pms-form-field-wrapper">

            <label for="pms-payment-subscription-id" class="pms-form-field-label"><?php echo __( 'Subscription', 'paid-member-subscriptions' ); ?></label>

            <select id="pms-payment-subscription-id" name="pms-payment-subscription-id" class="medium">
                <?php
                $subscription_plans = pms_get_subscription_plans();

                foreach( $subscription_plans as $subscription_plan ) {
                    echo '<option ' . selected( $payment->subscription_id, $subscription_plan->id, false ) . ' value="' . esc_attr( $subscription_plan->id ) . '">' . esc_html( $subscription_plan->name ) . '</option>';
                }
                ?>
            </select>

        </div>


        <!-- Payment Amount -->
        <div class="pms-form-field-wrapper">

            <?php $currency_symbol = pms_get_currency_symbol( $settings['payments']['currency'] ); ?>

            <label for="pms-payment-amount" class="pms-form-field-label"><?php printf( __( 'Amount (%s)', 'paid-member-subscriptions' ), $currency_symbol ); ?></label>
            <input type="text" id="pms-payment-amount" name="pms-payment-amount" class="medium" value="<?php echo esc_attr( $payment->amount ); ?>" />

        </div>


        <!-- Payment Discount Code -->
        <?php if( !empty( $payment->discount_code ) ): ?>
        <div class="pms-form-field-wrapper">

            <label for="pms-payment-discount" class="pms-form-field-label"><?php echo __( 'Discount Code', 'paid-member-subscriptions' ); ?></label>
            <span class="readonly medium"><strong><?php echo esc_html( $payment->discount_code ); ?></strong></span>

        </div>
        <?php endif; ?>


        <!-- Payment Date -->
        <div class="pms-form-field-wrapper">

            <label for="pms-payment-date" class="pms-form-field-label"><?php echo __( 'Date', 'paid-member-subscriptions' ); ?></label>
            <input type="text" id="pms-payment-date" name="pms-payment-date" class="datepicker medium" value="<?php echo esc_attr( date( 'Y-m-d H:i:s', strtotime( $payment->date ) + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ) ); ?>" />

        </div>


        <!-- Payment Type -->
        <div class="pms-form-field-wrapper">

            <label for="pms-payment-type" class="pms-form-field-label"><?php echo __( 'Type', 'paid-member-subscriptions' ); ?></label>

            <select id="pms-payment-type" name="pms-payment-type" class="widefat">
                <?php

                $types = pms_get_payment_types();

                foreach( $types as $type_slug => $type_name ) {
                    echo '<option ' . selected( $payment->type, $type_slug, false ) . ' value="' . esc_attr( $type_slug ) . '">' . esc_html( $type_name ) . '</option>';
                }
                ?>
            </select>

        </div>


        <!-- Payment Transaction ID -->
        <div class="pms-form-field-wrapper">

            <label for="pms-payment-transaction-id" class="pms-form-field-label"><?php echo __( 'Transaction ID', 'paid-member-subscriptions' ); ?></label>

            <?php if( !empty( $payment->transaction_id ) ): ?>
                <input type="text" id="pms-payment-transaction-id" name="pms-payment-transaction-id" class="widefat" value="<?php echo esc_attr( $payment->transaction_id ); ?>" />
            <?php else: ?>
                <p class="description"><?php _e( 'The Transaction ID will be provided by the payment gateway when the payment is registered within their system.', 'paid-member-subscriptions' ); ?></p>
            <?php endif; ?>

        </div>


        <!-- Payment Status -->
        <div class="pms-form-field-wrapper">

            <label for="pms-payment-status" class="pms-form-field-label"><?php echo __( 'Status', 'paid-member-subscriptions' ); ?></label>

            <select id="pms-payment-status" name="pms-payment-status" class="medium">
                <?php
                $statuses = pms_get_payment_statuses();

                foreach( $statuses as $status_slug => $status_name ) {
                    echo '<option ' . selected( $payment->status, $status_slug, false ) . ' value="' . esc_attr( $status_slug ) . '">' . esc_html( $status_name ) . '</option>';
                }
                ?>
            </select>

        </div>


        <!-- Payment IP Address -->
        <div class="pms-form-field-wrapper pms-form-field-ip-address">

            <label class="pms-form-field-label"><?php echo __( 'IP Address', 'paid-member-subscriptions' ); ?></label>
            <span class="readonly medium"><strong><?php echo ( !empty( $payment->ip_address ) ? esc_html( $payment->ip_address ) : '-' ); ?></strong></span>

        </div>


        <?php do_action( 'pms_payment_edit_form_field' ); ?>

        <?php wp_nonce_field( 'pms_payment_nonce' ); ?>

        <!-- Submit button and Cancel button -->
        <p class="submit">
            <?php submit_button( __( 'Save Payment', 'paid-member-subscriptions' ), 'primary', 'submit_edit_payment', false ); ?>
            <a href="<?php echo admin_url( 'admin.php?page=pms-payments-page' ); ?>" class="button button-secondary"><?php _e( 'Cancel', 'paid-member-subscriptions' ); ?></a>
        </p>

    </form>

</div>