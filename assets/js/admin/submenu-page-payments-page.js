/*
 * JavaScript for Payments Submenu Page
 *
 */
jQuery( function($) {

    /*
     * Initialize datepicker
     */
    $(document).on( 'focus', '.datepicker', function() {
        $(this).datepicker({
            dateFormat : 'yy-mm-dd',

            // Maintain the Time when switching dates
            onSelect   : function( dateText, inst ) {

                date = inst.lastVal.split(" ");
                dateTime = ( date[1] ? date[1] : '' );

                $(this).val( dateText + " " + dateTime );

            }

        });
    });


});