<?php
/*
 * HTML output for the members admin add new members bulk page
 */
?>

<div class="wrap">

    <h2>
        <?php echo __( 'Bulk Add Subscription Plans to Users', 'paid-member-subscriptions' ); ?>
    </h2>

    <form id="pms-form-add-new-member-bulk" method="POST" action="">
    <?php
        $members_list_table = new PMS_Members_Add_New_Bulk_List_Table();
        $members_list_table->prepare_items();
        $members_list_table->views();
        $members_list_table->display();

        wp_nonce_field( 'pms_member_nonce' );
    ?>

    </form>

</div>