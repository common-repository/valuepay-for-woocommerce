<?php
if ( !defined( 'ABSPATH' ) ) exit;

// Settings form fields
function valuepay_wc_settings_form_fields() {

    return array(
        'enabled' => array(
            'title'       => __( 'Enable/Disable', 'valuepay-wc' ),
            'type'        => 'checkbox',
            'label'       => __( 'Enable ValuePay', 'valuepay-wc' ),
            'default'     => 'no',
        ),
        'title' => array(
            'title'       => __( 'Title', 'valuepay-wc' ),
            'type'        => 'text',
            'description' => __( 'This controls the title which the user sees during checkout.', 'valuepay-wc' ),
            'placeholder' => __( 'ValuePay', 'valuepay-wc' ),
            'default'     => __( 'ValuePay', 'valuepay-wc' ),
            'desc_tip'    => true,
        ),
        'description' => array(
            'title'       => __( 'Description', 'valuepay-wc' ),
            'type'        => 'textarea',
            'description' => __( 'This controls the description which the user sees during checkout.', 'valuepay-wc' ),
            'desc_tip'    => true,
            'placeholder' => __( 'Pay with Online Banking', 'valuepay-wc' ),
            'default'     => __( 'Pay with Online Banking', 'valuepay-wc' ),
        ),
        'api_credentials' => array(
            'title'       => __( 'API Credentials', 'valuepay-wc' ),
            'type'        => 'title',
            'description' => __( 'API credentials can be obtained from ValuePay merchant dashboard in Business Profile page.', 'valuepay-wc' ),
        ),
        'username' => array(
            'title'       => __( 'Merchant Username', 'valuepay-wc' ),
            'type'        => 'text',
        ),
        'app_key' => array(
            'title'       => __( 'Application Key', 'valuepay-wc' ),
            'type'        => 'text',
        ),
        'app_secret' => array(
            'title'       => __( 'Application Secret', 'valuepay-wc' ),
            'type'        => 'text',
        ),
        'collection_mandate' => array(
            'title'       => __( 'Collection & Mandate', 'valuepay-wc' ),
            'type'        => 'title',
        ),
        'collection_id' => array(
            'title'       => __( 'Collection ID', 'valuepay-wc' ),
            'type'        => 'text',
            'description' => __( 'Collection ID can be obtained from ValuePay merchant dashboard under FPX Payment menu, in My Collection List page. Leave blank to disable one time payment.', 'valuepay-wc' ),
        ),
        'mandate_id' => array(
            'title'       => __( 'Mandate ID', 'valuepay-wc' ),
            'type'        => 'text',
            'description' => __( 'Mandate ID can be obtained from ValuePay merchant dashboard under E-Mandate Collection menu, in My Mandate List page. Leave blank to disable recurring payment.', 'valuepay-wc' ),
        ),
        'frequency_type' => array(
            'title'       => __( 'Frequency Type', 'valuepay-wc' ),
            'type'        => 'select',
            'description' => __( 'Select frequency type for the mandate above (if enabled).', 'valuepay-wc' ),
            'options'     => array(
                'weekly'  => __( 'Weekly', 'valuepay-wc' ),
                'monthly' => __( 'Monthly', 'valuepay-wc' ),
            ),
            'default'     => 'monthly',
        ),
        'debugging' => array(
            'title'       => __( 'Debugging', 'valuepay-wc' ),
            'type'        => 'title',
        ),
        'debug' => array(
            'title'       => __( 'Debug Log', 'valuepay-wc' ),
            'type'        => 'checkbox',
            'label'       => __( 'Enable debug log', 'valuepay-wc' ),
            'description' => __( 'Log ValuePay events, eg: IPN requests. Logs can be viewed on WooCommerce > Status > Logs.', 'valuepay-wc' ),
            'desc_tip'    => true,
            'default'     => 'no',
        ),
    );

}
