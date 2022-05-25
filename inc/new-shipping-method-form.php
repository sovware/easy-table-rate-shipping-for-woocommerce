<?php
/**
 * Exit if accessed directly
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$features = array(
    'method_enabled' => array(
        'title'   => __( 'Enable/Disable', 'easy-table-rate-shipping-for-woocommerce' ),
        'type'    => 'checkbox',
        'label'   => __( 'Enable this shipping method', 'easy-table-rate-shipping-for-woocommerce' ),
        'default' => ! empty( $shipping_method_array['method_enabled'] ) ? $shipping_method_array['method_enabled'] : ''
    ),
    'method_title' => array(
        'title'       => __( 'Method Title', 'easy-table-rate-shipping-for-woocommerce' ),
        'description' => __( 'This controls the title which the user sees during checkout.', 'easy-table-rate-shipping-for-woocommerce' ),
        'type'        => 'text',
        'default'     => ! empty( $shipping_method_array['method_title'] ) ? $shipping_method_array['method_title'] : '',
        'desc_tip'    => true
    ),
    'method_tax_status' => array(
        'title'     => __( 'Tax Status', 'easy-table-rate-shipping-for-woocommerce' ),
        'type'      => 'select',
        'default'   => ! empty( $shipping_method_array['method_tax_status'] ) ? $shipping_method_array['method_tax_status'] : '',
        'options'   => array(
            'taxable'   => __( 'Taxable', 'easy-table-rate-shipping-for-woocommerce' ),
            'notax'     => __( 'Not Taxable', 'easy-table-rate-shipping-for-woocommerce' ),
        )
    ),
    'method_visibility' => array(
        'title'         => __( 'Visibility', 'easy-table-rate-shipping-for-woocommerce' ),
        'description'   => __( 'If select Yes, then Show only for logged in users','easy-table-rate-shipping-for-woocommerce' ),
        'type'          => 'select',
        'default'       => ! empty( $shipping_method_array['method_visibility'] ) ? $shipping_method_array['method_visibility'] : '',
        'options'       => array(
            'no'    => __( 'NO', 'easy-table-rate-shipping-for-woocommerce' ),
            'yes'   => __( 'Yes', 'easy-table-rate-shipping-for-woocommerce' ),
        )
    ),
    'table_rates_table' => array(
        'title' => __( 'Shipping Methods', 'easy-table-rate-shipping-for-woocommerce' ),
        'type' => 'table_rates_table',
        'default' => isset( $shipping_method_array['method_table_rates'] ) ? $shipping_method_array['method_table_rates'] : array(),
        'description' => '',
    )
);

return $features;
