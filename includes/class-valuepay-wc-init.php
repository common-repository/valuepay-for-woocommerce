<?php
if ( !defined( 'ABSPATH' ) ) exit;

class Valuepay_WC_Init {

    private $gateway_class = 'Valuepay_WC_Gateway';

    // Register hooks
    public function __construct() {

        add_action( 'woocommerce_payment_gateways', array( $this, 'register_gateway' ) );
        add_action( 'init', array( $this, 'load_dependencies' ) );

    }

    // Register ValuePay as WooCommerce payment method
    public function register_gateway( $methods ) {
        $methods[] = $this->gateway_class;
        return $methods;
    }

    // Load required files
    public function load_dependencies() {

        if ( !class_exists( 'WC_Payment_Gateway' ) ) {
            return;
        }

        require_once( VALUEPAY_WC_PATH . 'admin/settings.php' );
        require_once( VALUEPAY_WC_PATH . 'includes/class-valuepay-wc-gateway.php' );

    }

}
new Valuepay_WC_Init();
