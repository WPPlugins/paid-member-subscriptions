<?php
/*
 * HTML output for the members admin add new member page
 */
?>

<div class="wrap">

    <h2>
        <?php echo __( 'Add Member Information to a User', 'paid-member-subscriptions' ); ?>
    </h2>

    <form id="pms-form-add-new-member" class="pms-form" method="POST">

        <div class="pms-form-field-wrapper">

            <label for="pms-member-username" class="pms-form-field-label"><?php echo __( 'Username', 'paid-member-subscriptions' ); ?></label>

            <select id="pms-member-username" name="pms-member-username" class="widefat pms-chosen">
                <option value=""><?php echo __( 'Select...', 'paid-member-subscriptions' ); ?></option>
                <?php
                    $users = pms_get_users_non_members();

                    foreach( $users as $user ) {
                        echo '<option ' . ( isset( $this->request_data['pms-member-user-id'] ) ? selected( $this->request_data['pms-member-user-id'], $user->ID, false ) : '' ) . ' value="' . esc_attr( $user->ID ) . '">' . esc_html( $user->data->user_login ) . '</option>';
                    }
                ?>
            </select>
            <input type="hidden" id="pms-member-user-id" name="pms-member-user-id" class="widefat" value="<?php echo ( isset( $this->request_data['pms-member-user-id'] ) ? esc_attr( $this->request_data['pms-member-user-id'] ) : 0 ); ?>" />

            <p class="description"><?php printf( __( 'Select the username you wish to associate a subscription plan with. You can create a new user <a href="%s">here</a>.', 'paid-member-subscriptions' ), admin_url('user-new.php') ); ?></p>

        </div>


        <?php
            $members_list_table = new PMS_Member_Subscription_List_Table( 0 );
            $members_list_table->prepare_items();

            if( isset($this->request_data['pms-member-subscriptions']) )
                $members_list_table->replace_items( $this->request_data['pms-member-subscriptions'] );

            $members_list_table->display();
        ?>


        <?php do_action( 'pms_member_add_new_form_field' ); ?>

        <?php wp_nonce_field( 'pms_member_nonce' ); ?>

        <?php submit_button( __( 'Add Member', 'paid-member-subscriptions' ), 'primary', 'submit_add_new_member' ); ?>

    </form>

</div>