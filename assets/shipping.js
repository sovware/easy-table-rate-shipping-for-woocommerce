jQuery(document).ready(function () {
    jQuery("#checkall_checkbox").change(function () {
        var checked = jQuery(this).is(':checked'); // Checkbox state
        // Select all
        if (checked) {
            jQuery('.select_shipping').each(function () {
                jQuery(this).prop('checked', 'checked');
                jQuery('#aazz_wc_table_rate_remove_selected_method').prop('disabled', false);
            });
        } else {
            // Deselect All
            jQuery('.select_shipping').each(function () {
                jQuery(this).prop('checked', false);
                jQuery('#aazz_wc_table_rate_remove_selected_method').prop('disabled', true);
            });
        }

    });
});

