<?php
defined( 'ABSPATH' ) || die( 'Cheating, huh? Direct access to this file is not allowed !!!!' );
?>
<table class="form-table">
    <?php
    $shipping_method_action = false;
    if (isset($_GET['action'])) {
        $shipping_method_action = esc_html($_GET['action']);
    }
    if ($shipping_method_action == 'new' || $shipping_method_action == 'edit') {
        $get_shipping_methods_options = get_option($wc_shipping_methods_option, array());

        $shipping_method_array = array(
            'method_title' => '',
            'method_enabled' => 'no',
            'method_handling_fee' => '',
            'method_visibility'=>'no',
            'method_tax_status' => 'taxable',
            'method_table_rates' => ''
        );
        $method_id = '';
        if ($shipping_method_action == 'edit') {
            $method_id = esc_html($_GET['method_id']);
            $shipping_method_array = $get_shipping_methods_options[$method_id];
            $method_id_for_shipping = $wc_id . '_' . $wc_instance_id . '_' . sanitize_title($shipping_method_array['method_title']);
            if (isset($shipping_method_array['method_id_for_shipping']) && $shipping_method_array['method_id_for_shipping'] != '') {
                $method_id_for_shipping = $shipping_method_array['method_id_for_shipping'];
            }
            $method_id_for_shipping = $method_id_for_shipping;
        } else {
            $method_id_for_shipping = '';
        }
        ?>
        <input type="hidden" name="shipping_method_action" value="<?php echo !empty($shipping_method_action) ? $shipping_method_action : ''; ?>" />
        <input type="hidden" name="shipping_method_id" value="<?php echo !empty($method_id) ? $method_id : ''; ?>" />
        <input type="hidden" name="method_id_for_shipping" value="<?php echo !empty($method_id_for_shipping) ? $method_id_for_shipping : ''; ?>" />
        <?php
        $shipping_method['woocommerce_method_instance_id'] = $wc_instance_id;
        $this->generate_settings_html($this->aazz_wc_new_shipping_method_form($shipping_method_array));
    } else if ($shipping_method_action == 'delete') {
        $selected_shipping_methods_id = '';
        // get selected methods id and explode it with ','
        if (isset($_GET['shipping_methods_id'])) {
            $selected_shipping_methods_id = explode(',', $_GET['shipping_methods_id']);
        }
        // get all shipping methods options for delete
        $get_shipping_methods_options_for_delete = get_option($wc_shipping_methods_option, array()); //
        // get all shipping methods order for delete
        $get_shipping_methods_order_for_delete = get_option( $wc_shipping_method_order_option, array() );
        foreach ($selected_shipping_methods_id as $removed_method_id) {
            if (isset($get_shipping_methods_options_for_delete[$removed_method_id])) {
                if (isset($get_shipping_methods_order_for_delete[$removed_method_id])) {
                    unset($get_shipping_methods_order_for_delete[$removed_method_id]);
                }
                $shipping_method = $get_shipping_methods_options_for_delete[$removed_method_id];
                unset($get_shipping_methods_options_for_delete[$removed_method_id]);
                // Update all shipping methods options after delete
                update_option($wc_shipping_methods_option, $get_shipping_methods_options_for_delete);
                // Update all shipping methods order after delete
                update_option( $wc_shipping_method_order_option, $get_shipping_methods_order_for_delete );
            }
        }
        $this->generate_settings_html();
    } else {
        $this->generate_settings_html();
    }
    ?>
</table>
