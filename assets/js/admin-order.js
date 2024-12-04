jQuery(document).ready(function($) {

    if( "" === checkorder.status ){
        // Method 1: Check on load.
        var isChecked = $('#order_checkbox').is(':checked');
        if (!isChecked) {
            var confirmation = confirm("This order is currently marked as a test order. Do you want to keep it as a test order?");
            console.log(confirmation);
            if (confirmation) {
                $('#order_checkbox').prop('checked', true); // Uncheck if not confirmed.
            }
        }
    }

    // Method 2: Manual checkbox change event
    $('#order_checkbox').on('change', function() {
        var isChecked = $(this).is(':checked');
        var confirmation = confirm("Are you sure you want to mark this order as a test order?");
        
        if (!confirmation) {
            $(this).prop('checked', !isChecked); // Revert the checkbox state if not confirmed
        }
    });
});
