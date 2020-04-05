<?php
defined( 'ABSPATH' ) || die( 'Cheating, huh? Direct access to this file is not allowed !!!!' );
if (isset($_POST['shipping_method_action'])) {
    $shipping_method_action = $_POST['shipping_method_action'];
}
if ($shipping_method_action == 'new' || $shipping_method_action == 'edit') {
    //Arrays to hold the clean POST vars
    $keys = array();
    $zone_name = array();
    $condition = array();
    $countries = array();
    $min = array();
    $max = array();
    $shipping = array();

    if ( !empty($_POST['key']) )
        $keys = array_map('wc_clean', $_POST['key']);
    if ( !empty($_POST['zone-name']) )
        $zone_name = array_map('wc_clean', $_POST['zone-name']);
    if ( !empty($_POST['countries']) )
        $countries = array_map('wc_clean',$_POST['countries']);
    if ( !empty($_POST['conditions']) )
        $conditions = array_map('wc_clean',$_POST['conditions']);
    if ( !empty($_POST['min']) && intval($_POST['min']) )
        $min = array_map('wc_clean',$_POST['min']);
    if ( !empty($_POST['max']) && intval($_POST['max']) )
        $max = array_map('wc_clean',$_POST['max']);
    if ( !empty($_POST['shipping']) )
        $shipping = array_map('wc_clean',$_POST['shipping']);

    //todo - need to add soem validation here and some error messages???
    //Master var of options - we keep it in one big bad boy
    $options = array();

    //OK we need to loop thru all of them - the keys will help us here - process by key
    foreach ($keys as $key => $value) {

        //Get the zone name - this is our main key
        $name = $zone_name[$key];

        //Going to add the rates now.
        //before we do that check if we have any empty rows and delete them
        $obj = array();
        if( !empty($min) ){
            foreach ($min[$key] as $k => $val) {
                if (
                    empty($conditions[$key][$k]) &&
                    empty($min[$key][$k]) &&
                    empty($max[$key][$k]) &&
                    empty($shipping[$key][$k])
                ) {
                    unset($conditions[$key][$k]);
                    unset($min[$key][$k]);
                    unset($max[$key][$k]);
                    unset($shipping[$key][$k]);
                } else {
                    //add it to the object array
                    $obj[] = array("condition" => $conditions[$key][$k] , "min" => $min[$key][$k], "max" => $max[$key][$k], "shipping" => $shipping[$key][$k]);
                }
            }
        }
        //lets sort or array of objects!!
        usort($obj, 'self::aazz_comparision');

        //create the array to hold the data
        $options[$name] = array();
        $options[$name]['min'] = $min[$key];
        $options[$name]['max'] = $max[$key];
        $options[$name]['shipping'] = $shipping[$key];
        $options[$name]['rates'] = $obj;   //This is the sorted rates object!
    }
    $wc_methods_option = !empty($wc_methods_option) ? $wc_methods_option : '';
    $wc_method_order_option = !empty($wc_method_order_option) ? $wc_method_order_option : '';
    $get_shipping_methods_options = get_option($wc_methods_option, array());
    $get_shipping_method_order = get_option( $wc_method_order_option, array() );
    $shipping_method_array = array();
    if ($shipping_method_action == 'new') {
        $get_shipping_methods_options = get_option($wc_methods_option, array());
        $method_id = get_option('aazz_wc_table_rate_sub_shipping_method_id', 0);
        foreach ($get_shipping_methods_options as $shipping_method_array) {
            if (intval($shipping_method_array['method_id']) > $method_id)
                $method_id = intval($shipping_method_array['method_id']);
        }
        $method_id++;
        update_option('aazz_wc_table_rate_sub_shipping_method_id', $method_id);
        $method_id_for_shipping = $this->id . '_' . $this->instance_id . '_' . $method_id;
    }
    else {
        $method_id = $_POST['shipping_method_id'];
        $method_id_for_shipping = $_POST['method_id_for_shipping'];
    }

    $shipping_method_array['method_id'] = $method_id;
    $shipping_method['method_id_for_shipping'] = $method_id_for_shipping;
    if (isset($_POST['woocommerce_' . $this->id . '_method_enabled']) && $_POST['woocommerce_' . $this->id . '_method_enabled'] == 1) {
        $shipping_method_array['method_enabled'] = 'yes';
    } else {
        $shipping_method_array['method_enabled'] = 'no';
    }
    $shipping_method_array['method_title'] = esc_html($_POST['woocommerce_' . $this->id . '_method_title']);
    $shipping_method_array['method_tax_status'] = esc_html($_POST['woocommerce_' . $this->id . '_method_tax_status']);
    $shipping_method_array['method_visibility'] = esc_html($_POST['woocommerce_' . $this->id . '_method_visibility']);

    //SAVE IT
    $shipping_method_array['method_table_rates'] = $options;
    $get_shipping_methods_options[$method_id] = $shipping_method_array;
    update_option($wc_methods_option, $get_shipping_methods_options);
    if (isset($_GET['action'])) {
        $shipping_method_action = esc_html($_GET['action']);
    }

    if ($shipping_method_action == 'new') {
        $get_shipping_method_order[$method_id] = $method_id;
        update_option($wc_method_order_option, $get_shipping_method_order);
        $redirect = add_query_arg(array('action' => 'edit', 'method_id' => $method_id));
        if (1 == 1 && headers_sent()) {
            ?>
            <script>
                parent.location.replace('<?php echo !empty($redirect) ? $redirect : ""; ?>');
            </script>
            <?php
        } else {
            wp_safe_redirect($redirect);
        }
        exit;
    }
}
else{
    if (isset($_POST['method_order'])) {
        $wc_method_order_option = !empty($wc_method_order_option) ? $wc_method_order_option : '';
        update_option($wc_method_order_option, sanitize_text_field($_POST['method_order']));
    }
}
