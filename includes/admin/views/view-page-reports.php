<?php
/*
 * HTML output for the reports admin page
 */
?>

<div class="wrap">

    <h2><?php echo $this->page_title; ?></h2>

    <form id="pms-form-reports" class="pms-form" action="<?php echo admin_url( 'admin.php' ); ?>" method="get">
        <input type="hidden" name="page" value="pms-reports-page" />

        <!-- Filter box -->
        <div class="postbox">
            <div class="inside">
                <h4><?php echo __( 'Filters', 'paid-member-subscriptions' ); ?></h4>

                <?php do_action( 'pms_reports_filters' ); ?>

                <button name="pms-action" type="submit" class="button-secondary" value="filter_results"><?php echo __( 'Filter', 'paid-member-subscriptions' ); ?></button>
            </div>
        </div>

        <!-- Chart and details -->
        <div class="postbox">
            <div class="inside" style="padding: 20px 45px;">
                <canvas id="payment-report-chart" width="1000" height="250"></canvas>
            </div>
        </div>

        <?php do_action( 'pms_reports_form_bottom' ); ?>

        <?php wp_nonce_field( 'pms_reports_nonce', '_wpnonce', false ); ?>

    </form>

    <?php do_action( 'pms_reports_page_bottom' ); ?>

</div>