<?php
if ( !defined( 'ABSPATH' ) ) exit;

// Get plugin setting by key
function valuepay_wc_get_setting( $key, $default = null ) {

    $settings = get_option( 'woocommerce_valuepay_settings' );

    if ( isset( $settings[ $key ] ) && !empty( $settings[ $key ] ) ) {
        return $settings[ $key ];
    }

    return $default;

}

// Display notice
function valuepay_wc_notice( $message, $type = 'success' ) {

    $plugin = esc_html__( 'ValuePay for WooCommerce', 'valuepay-wc' );

    printf( '<div class="notice notice-%1$s"><p><strong>%2$s:</strong> %3$s</p></div>', esc_attr( $type ), $plugin, $message );

}

// Log a message in WooCommerce logs
function valuepay_wc_logger( $message ) {

    if ( function_exists( 'wc_get_logger' ) ) {
        return wc_get_logger()->add( 'valuepay-wc', $message );
    }

}

// List of identity types accepted by ValuePay
function valuepay_wc_get_identity_types() {

    return array(
        1 => __( 'New IC No.', 'valuepay-wc' ),
        2 => __( 'Old IC No.', 'valuepay-wc' ),
        3 => __( 'Passport No.', 'valuepay-wc' ),
        4 => __( 'Business Reg. No.', 'valuepay-wc' ),
        5 => __( 'Others', 'valuepay-wc' ),
    );

}

// Get readable identity type
function valuepay_wc_get_identity_type( $key ) {
    $types = valuepay_wc_get_identity_types();
    return isset( $types[ $key ] ) ? $types[ $key ] : false;
}

// Format telephone number
function valuepay_wc_format_telephone( $telephone ) {

    // Get numbers only
    $telephone = preg_replace( '/[^0-9]/', '', $telephone );

    // Add country code in the front of phone number if the phone number starts with zero (0)
    if ( strpos( $telephone, '0' ) === 0 ) {
        $telephone = '+6' . $telephone;
    }

    // Add + symbol in the front of phone number if the phone number has no + symbol
    if ( strpos( $telephone, '+' ) !== 0 ) {
        $telephone = '+' . $telephone;
    }

    return $telephone;

}
