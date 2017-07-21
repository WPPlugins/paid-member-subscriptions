<?php
/*
 * HTML output for the payments admin page
 */
?>

<div class="wrap">

    <h2><?php echo $this->page_title; ?></h2>

    <form method="get">
        <input type="hidden" name="page" value="pms-payments-page" />
    <?php

        $payments_list_table = new PMS_Payments_List_Table();
        $payments_list_table->prepare_items();
        $payments_list_table->views();
        $payments_list_table->search_box(__('Search Payments'),'pms_search_payments');
        $payments_list_table->display();

    ?>
    </form>

</div>