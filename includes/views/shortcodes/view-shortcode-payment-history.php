<?php
/*
 * User's payment history table
 */
?>

<?php

    $number_per_page = $args['number_per_page'];
    $page            = get_query_var( 'paged' );

    $payments = pms_get_payments( array(
        'order'   => 'DESC',
        'user_id' => $user_id,
        'number'  => $number_per_page,
        'offset'  => ( $page !== 0 ? ( $page - 1 ) * $number_per_page : '' )
    ));

?>

<?php if( !empty( $payments ) ): // Handle no payments situation ?>
<table id="pms-payment-history" class="pms-table">

    <thead>
        <tr>
            <th class="pms-payment-id"><?php _e( 'ID', 'paid-member-subscriptions' ); ?></th>
            <th class="pms-payment-amount"><?php _e( 'Amount', 'paid-member-subscriptions' ); ?></th>
            <th class="pms-payment-date"><?php _e( 'Date / Time', 'paid-member-subscriptions' ); ?></th>
            <th class="pms-payment-status"><?php _e( 'Status', 'paid-member-subscriptions' ); ?></th>

            <?php do_action( 'pms_payment_history_table_header', $user_id, $payments ); ?>
        </tr>
    </thead>

    <tbody>
        <?php foreach( $payments as $payment ): ?>
            <tr>
                <td class="pms-payment-id"><?php echo '#' . esc_html( $payment->id ); ?></td>
                <td class="pms-payment-amount"><?php echo esc_html( pms_format_price( $payment->amount, pms_get_active_currency() ) ); ?></td>
                <td class="pms-payment-date"><?php echo date( apply_filters( 'pms_payment_history_date_format', 'j F, Y H:i' ), strtotime( $payment->date ) ); ?></td>
                <td class="pms-payment-status"><?php echo ucfirst( esc_html( $payment->status ) ); ?></td>
            </tr>
        <?php endforeach; ?>

        <?php do_action( 'pms_payment_history_table_body', $user_id, $payments ); ?>
    </tbody>

</table>

<?php echo pms_paginate_links( apply_filters( 'pms_payment_history_table_paginate_links', array( 'id' => 'pms-payment-history', 'current' => max( 1, $page ), 'total'   => ceil( pms_get_member_payments_count( $user_id ) / $number_per_page )  ) ) ); ?>

<?php else: // Add payments ?>
    <p class="pms-no-payments"><?php _e( 'No payments found', 'paid-member-subscriptions' ); ?></p>
<?php endif; // End of payments ?>