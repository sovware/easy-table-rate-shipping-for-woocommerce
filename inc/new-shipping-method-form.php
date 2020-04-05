<?php
defined( 'ABSPATH' ) || die( 'Cheating, huh? Direct access to this file is not allowed !!!!' );

$features = array(
    'method_enabled' => array(
        'title' => __('Enable/Disable', AAZZ_WC_TEXTDOMAIN),
        'type' => 'checkbox',
        'label' => __('Enable this shipping method', AAZZ_WC_TEXTDOMAIN),
        'default' => !empty($shipping_method_array['method_enabled']) ? $shipping_method_array['method_enabled'] : ''
    ),
    'method_title' => array(
        'title' => __('Method Title', AAZZ_WC_TEXTDOMAIN),
        'description' => __('This controls the title which the user sees during checkout.', AAZZ_WC_TEXTDOMAIN),
        'type' => 'text',
        'default' => !empty($shipping_method_array['method_title']) ? $shipping_method_array['method_title'] : '',
        'desc_tip' => true
    ),
    'method_tax_status' => array(
        'title' => __('Tax Status', AAZZ_WC_TEXTDOMAIN),
        'type' => 'select',
        'default' => !empty($shipping_method_array['method_tax_status']) ? $shipping_method_array['method_tax_status'] : '',
        'options' => array(
            'taxable' => __('Taxable', AAZZ_WC_TEXTDOMAIN),
            'notax' => __('Not Taxable', AAZZ_WC_TEXTDOMAIN),
        )
    ),
    'method_visibility' => array(
        'title' => __('Visibility', AAZZ_WC_TEXTDOMAIN),
        'description'=> __('If select Yes, then Show only for logged in users',AAZZ_WC_TEXTDOMAIN),
        'type' => 'select',
        'default' => !empty($shipping_method_array['method_visibility']) ? $shipping_method_array['method_visibility'] : '',
        'options'=> array(
            'no'=>__('NO', AAZZ_WC_TEXTDOMAIN),
            'yes'=>__('Yes', AAZZ_WC_TEXTDOMAIN),
        )
    ),
    'table_rates_table' => array(
        'title' => __('Shipping Methods', AAZZ_WC_TEXTDOMAIN),
        'type' => 'table_rates_table',
        'default' => isset($shipping_method_array['method_table_rates']) ? $shipping_method_array['method_table_rates'] : array(),
        'description' => '',
    )
);

return $features;
