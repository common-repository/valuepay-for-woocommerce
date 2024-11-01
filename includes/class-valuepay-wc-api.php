<?php
if ( !defined( 'ABSPATH' ) ) exit;

class Valuepay_WC_API extends Valuepay_WC_Client {

    // Initialize API
    public function __construct( $username = null, $app_key = null, $app_secret = null ) {

        $this->username   = $username ?: valuepay_wc_get_setting( 'username' );
        $this->app_key    = $app_key ?: valuepay_wc_get_setting( 'app_key' );
        $this->app_secret = $app_secret ?: valuepay_wc_get_setting( 'app_secret' );
        $this->debug      = valuepay_wc_get_setting( 'debug' ) === 'yes';

    }

    // Query bank list
    public function get_banks( array $params ) {
        return $this->post( 'querybanklist', $params );
    }

    // Create a bill
    public function create_bill( array $params ) {
        return $this->post( 'createbill', $params );
    }

    // Set enrolment data
    public function set_enrol_data( array $params ) {
        return $this->post( 'setenroldata', $params );
    }

}
