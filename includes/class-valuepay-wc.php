<?php
if ( !defined( 'ABSPATH' ) ) exit;

class Valuepay_WC {

    // Load dependencies
    public function __construct() {

        // Functions
        require_once( VALUEPAY_WC_PATH . 'includes/functions.php' );

        // API
        require_once( VALUEPAY_WC_PATH . 'includes/abstracts/abstract-valuepay-wc-client.php' );
        require_once( VALUEPAY_WC_PATH . 'includes/class-valuepay-wc-api.php' );

        // Admin
        require_once( VALUEPAY_WC_PATH . 'admin/class-valuepay-wc-admin.php' );

        // Initialize payment gateway
        require_once( VALUEPAY_WC_PATH . 'includes/class-valuepay-wc-init.php' );

    }

}
new Valuepay_WC();
