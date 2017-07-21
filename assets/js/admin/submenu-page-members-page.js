/*
 * JavaScript for Members Submenu Page
 *
 */
jQuery( function($) {

    /*
     * Adds a spinner after the element
     */
    $.fn.pms_addSpinner = function( animation_speed ) {

        if( typeof animation_speed == 'undefined' )
            animation_speed = 100;

        $this = $(this);

        if( $this.siblings('.spinner').length == 0 )
            $this.after('<div class="spinner"></div>');

        $spinner = $this.siblings('.spinner');
        $spinner.css('visibility', 'visible').animate({opacity: 1}, animation_speed );

    };


    /*
     * Removes the spinners next to the element
     */
    $.fn.pms_removeSpinner = function( animation_speed ) {

        if( typeof animation_speed == 'undefined' )
            animation_speed = 100;

        if( $this.siblings('.spinner').length > 0 ) {

            $spinner = $this.siblings('.spinner');
            $spinner.animate({opacity: 0}, animation_speed );

            setTimeout( function() {
                $spinner.remove();
            }, animation_speed );

        }

    };


    if( $.fn.chosen != undefined ) {

        $('.pms-chosen').chosen();

    }


    /*
     * Function that checks to see if any field from a row is empty
     *
     */
    function checkEmptyRow( $row ) {

        is_field_empty = false;

        $row.find('.pms-subscription-field').each( function() {

            $field = $(this);

            if( $field.hasClass('pms-can-be-empty') )
                return true;

            if( $field.val().trim() == '' ) {
                $field.addClass('pms-field-error');
                is_field_empty = true;
            } else {
                $field.removeClass('pms-field-error');
            }

        });

        return is_field_empty;

    }

    var validation_errors = [];

    function displayErrors() {

        if( validation_errors.length == 0 )
            return false;

        errors_output = '';
        for( var i = 0; i < validation_errors.length; i++ ) {
            errors_output += '<p>' + validation_errors[i] + '</p>';
        }

        if( $('.wrap h2').first().siblings('.pms-admin-notice').length > 0 ) {

            $('.wrap h2').first().siblings('.pms-admin-notice').html( errors_output );

        } else {
            $('.wrap h2').first().after( '<div class="error pms-admin-notice">' + errors_output + '</div>' )
        }

    }


    /*
     * Initialize datepicker
     */
    $(document).on( 'focus', '.datepicker', function() {
        $(this).datepicker({ dateFormat: 'yy-mm-dd'});
    });



    /*
     * Populate the expiration date field when changing the subscription plan field
     * with the expiration date calculated from the duration of the subscription plan selected
     */
    $(document).on( 'change', '.wp-list-table .subscription_plan select', function() {

        $subscriptionPlanSelect = $(this);
        $expirationDateInput = $subscriptionPlanSelect.parents('td').siblings('.expiration_date').find('.datepicker');

        // Exit if no subscription plan was selected
        if( $subscriptionPlanSelect.val() == '' )
            return false;

        // De-focus the subscription plan select
        $subscriptionPlanSelect.blur();

        // Add the spinner
        $expirationDateInput.pms_addSpinner( 200 );

        $expirationDateSpinner = $expirationDateInput.siblings('.spinner');
        $expirationDateSpinner.animate({opacity: 1}, 200);

        // Disable the datepicker
        $expirationDateInput.attr( 'disabled', true );

        // Get the expiration date and set it the expiration date field
        $.post( ajaxurl, { action: 'populate_expiration_date', subscription_plan_id: $subscriptionPlanSelect.val() }, function( response ) {

            // Populate expiration date field
            $expirationDateInput.val( response );

            // Remove spinner and enable the expiration date field
            $expirationDateInput.pms_removeSpinner( 100 );
            $expirationDateInput.attr( 'disabled', false).trigger('change');

        });

    });


    /*
     * Selecting the username
     *
     */
    $(document).on( 'change', '#pms-member-username', function() {

        $select = $(this);

        if( $select.val().trim() == '' )
            return false;

        var user_id = $select.val().trim();

        $('#pms-member-user-id').val( user_id );

        $tableBody = $('.wp-list-table.member-subscriptions').find('tbody');

        if( $tableBody.find('tr.no-items').length > 0 ) {
            $('#pms-member-add-new-subscription').trigger('click');
        }

    });


    /*
     * Validate empty fields
     *
     */
    $(document).on( 'click', '.pms-edit-subscription-details', function(e) {
        e.preventDefault();

        $button = $(this);

        if( !$button.hasClass('button-primary') )
            return false;

        $row = $button.parents('tr');

        is_field_empty = checkEmptyRow( $row );

        if( is_field_empty )
            $row.addClass('pms-field-error');
        else
            $row.removeClass('pms-field-error');

    });


    /*
     * Add index on add new member form
     *
     */
    $(document).ready( function() {

        $('.wp-list-table.member-subscriptions').find('tr.pms-add-new').first().find('.pms-subscription-field').each( function() {
            name = $(this).attr('name').replace('pms-member-subscriptions[]', 'pms-member-subscriptions[0]');
            $(this).attr('name', name );
        });

    });


    /*
     * Show the inputs when user clicks on the edit subscription button
     *
     */
    $(document).on( 'click', '.pms-edit-subscription-details', function(e) {
        e.preventDefault();

        $button = $(this);
        $button.blur();

        if( $button.hasClass('button-primary') )
            return false;


        $row = $button.parents('tr');
        $tableBody = $button.parents('tbody');

        if( $tableBody.find('tr.edit-active').length > 0 ) {
            $activeRow = $tableBody.find('tr.edit-active');

            $activeRow.removeClass('edit-active');
            $activeRow.find('.button-primary').text('Edit').removeClass('button-primary');

        }

        $button.addClass('button-primary').text('Save');

        $tableBody.find('tr').removeClass('edit-active').addClass('edit-inactive');

        $row.removeClass('edit-inactive').addClass('edit-active');

    });


    /*
     * Save member subscription details when clicking the save button on
     *
     */
    $(document).on( 'click', '.pms-edit-subscription-details.button-primary', function(e) {
        e.preventDefault();

        $button = $(this);
        $button.blur();

        $row = $button.parents('tr');

        // Exit if the row has field errors
        if( $row.hasClass('pms-field-error') )
            return;

        // Add spinner
        $row.find('td').last().children().last().pms_addSpinner( 100 );

        // Disable the fields
        $row.find('.pms-subscription-field').attr( 'disabled', true );

        // Get needed data to update/add subscription
        var user_id = $('#pms-member-user-id').val().trim();
        var subscription_plan_id = $row.find('.subscription_plan select').val().trim();
        var start_date = $row.find('.start_date input').val().trim();
        var expiration_date = $row.find('.expiration_date input').val().trim();
        var status = $row.find('.status select').val().trim();

        // Set data
        var data = {
            action: 'member_add_update_subscription',
            user_id: user_id,
            subscription_plan_id: subscription_plan_id,
            start_date: start_date,
            expiration_date: expiration_date,
            status: status
        };

        // Add the id of the subscription plan in the delete button url
        $deleteButton = $row.find('.trash a');
        $deleteButton.attr( 'href', $deleteButton.attr( 'href' ).replace( 'subscription_plan_id', 'subscription_plan_id=' + subscription_plan_id ) );


        // Update/add subscription plan
        $.post( ajaxurl, data, function( response ) {

            // Remove empty value options
            $row.find('.subscription_plan select option').each( function() {
                if( $(this).val().trim() == '' )
                    $(this).remove();
            });

            // Remove spinner
            $row.find('td').last().children().last().pms_removeSpinner( 100 );

            // Enable the fields
            $row.find('.pms-subscription-field').attr( 'disabled', false );

            $row.removeClass('pms-add-new');
            $button.siblings('.pms-edit-subscription-details-cancel').trigger('click');
        });

    });


    /*
     * Cancel editing and return to the values that were before
     *
     */
    $(document).on( 'click', '.pms-edit-subscription-details-cancel', function(e) {
        e.preventDefault();

        $button = $(this);

        $button.blur();
        $button.siblings('.button-primary').text('Edit').removeClass('button-primary');

        $row = $button.parents('tr');
        $tableBody = $button.parents('tbody');


        if( $row.hasClass('pms-add-new') ) {
            $row.remove();

            // Show the "Add New Subscription" button if rows count are less than the number of subscription plan groups
            if( $tableBody.find('tr').length < $('#pms-subscription-groups-count').val() )
                $('#pms-member-add-new-subscription').show();
        }

        $row.removeClass('edit-active');

        if( $tableBody.find('tr.edit-active').length == 0 )
            $tableBody.find('tr').removeClass('edit-inactive');

    });


    /*
     * Validate form before submitting
     *
     */
    $('.pms-form input[type=submit]').click( function(e) {

        var errors = false;
        validation_errors = [];

        // Check to see if the user id exists
        if( $('#pms-member-user-id').val().trim() == 0 ) {
            errors = true;
            validation_errors.push( 'Please select a user.' );
        }

        // If no subscription plan is to be found return
        if( $('.wp-list-table.member-subscriptions tbody td.subscription_plan').length == 0 ) {
            errors = true;
            validation_errors.push( 'Please add at least one Subscription Plan.' );
        }


        // Check to see if any fields are left empty and return if so
        is_empty = false;
        $('.wp-list-table.member-subscriptions tbody tr').each( function() {
            if( checkEmptyRow( $(this) ) == true )
                is_empty = true;
        });

        if( is_empty ) {
            errors = true;
            validation_errors.push( 'Please fill all the required fields.' );
        }

        // Check to see if subscription plans repeat somewhere
        var subscription_group_repeated = false;

        $subscriptionPlanSelects = $('.wp-list-table.member-subscriptions .subscription_plan select');

        $subscriptionPlanSelects.each( function() {

            $select = $(this);
            found = 0;

            $subscriptionPlanSelects.each( function() {
                if( $select.find('option:selected').attr('data-group') == $(this).find('option:selected').attr('data-group') )
                    found += 1;
            });

            if( found > 1 )
                subscription_group_repeated = true;
        });

        if( subscription_group_repeated ) {
            errors = true;
            validation_errors.push( 'A member can be subscribed to only one subscription plan from a group.' );
        }


        if( errors ) {
            displayErrors();
            return false;
        }

    });


    /*
     * Member Subscriptions table - change text in the spans displaying information with
     * the values of the inputs/select from each table cell
     *
     */
    $('body').on( 'change', '.edit-active select, .edit-active input', function() {
        $input = $(this);

        if( $input.is('select') )
            $input.siblings('span').text( $input.find('option:selected').text() );

        if( $input.is('input') )
            $input.siblings('span').text( $input.val() );

        if( $input.is('select') && $input.siblings('input[type=hidden]').length > 0 )
            $input.siblings('input[type=hidden]').val( $input.find('option:selected').text() );

    });

});