<?php
defined( 'ABSPATH' ) || die( 'Cheating, huh? Direct access to this file is not allowed !!!!' );

$get_shipping_methods_options = get_option($wc_methods_option, array());
$get_shipping_method_order = get_option( $wc_method_order_option, array() );
$method_rate_id = $id.':'.$instance_id;
$zone_id = $this->get_shipping_zone_from_method_rate_id( $method_rate_id );
$delivery_zones = WC_Shipping_Zones::get_zones();
$zone_countries = array();


foreach ((array) $delivery_zones[$zone_id]['zone_locations'] as $zlocation ) {
    $zone_countries[] = $zlocation->code;
}

$shipping_methods_options_array = array();

//TODO - need to work out what this array is holding??
if ( is_array( $get_shipping_method_order ) ) {
    foreach ( $get_shipping_method_order as $method_id ) {
        if ( isset( $get_shipping_methods_options[$method_id] ) ) $shipping_methods_options_array[$method_id] = $get_shipping_methods_options[$method_id];
    }
}

//And what is this
foreach ($get_shipping_methods_options as $shipping_method) {
    if (!isset($shipping_methods_options_array[$shipping_method['method_id']]))
        $shipping_methods_options_array[$shipping_method['method_id']] = $shipping_method;
}

//TODO = can we check for this earlier rather than do a seperate loop???
// Remove table rates if shipping method is disable
foreach ($shipping_methods_options_array as $key => $shipping_method) {
    if (isset($shipping_method['method_enabled']) && 'yes' != $shipping_method['method_enabled'])
        unset($shipping_methods_options_array[$key]);
}

$shipping_methods_options = $shipping_methods_options_array;

$loop_count = 0;
foreach ($shipping_methods_options as $shipping_method_option) {

    if ( isset( $shipping_method_option['method_visibility'] ) && $shipping_method_option['method_visibility'] == 'yes' && !is_user_logged_in() ) {
        /* only for logged in */
        continue;
    }

    foreach ($shipping_method_option['method_table_rates'] as $method_rule) {

        //SE - Added in to stop the error
        $cost = 0;

        //what is the tax status
        if ($shipping_method_option['method_tax_status'] == 'notax') {
            $taxes = false;
        } else {
            $taxes = '';
        }

        //ok first lets get the country that this order is for
        // check destination country is available in rule
        $dest_country = $package['destination']['country'];
        if (!in_array($dest_country, $zone_countries)) {
            $cost = null;
        }

        // NISL custom code based on rates and conditions set for each row set.
        foreach( $method_rule['rates'] as $rates ){
            if( $rates['condition'] == 'total' ){
                $costs = $this->find_matching_rate_custom(WC()->cart->cart_contents_total, $rates);
                $cost = $cost + $costs;
            }
            else if($rates['condition'] == 'weight' ){
                $costs = $this->find_matching_rate_custom(WC()->cart->cart_contents_weight, $rates);
                $cost = $cost + $costs;
            }
        }
        // END NISL custom code



        $method_id = $id . '_' . $instance_id . '_' . sanitize_title($shipping_method_option['method_title']);
        if (isset($shipping_method_option['method_id_for_shipping']) && $shipping_method_option['method_id_for_shipping'] != '') {
            $method_id = $shipping_method_option['method_id_for_shipping'];
        }


        //If it's free shipping append the Woo value)
        if($cost === 0){
            $shipping_method_option['method_title'] =$shipping_method_option['method_title'] . " (" . __("Free Shipping", 'woocommerce') . ")";
        }

        if ( !is_null($cost) ) {
            $rate = array(
                'id' => $method_id,
                'label' => $shipping_method_option['method_title'],
                'cost' => $cost,
                'taxes' => $taxes,
                'calc_tax' => 'per_order'
            );
        }

        // Register the rate
        $this->add_rate($rate);
        $loop_count = $loop_count + 1;
    }
    //$cost = 0;
}
