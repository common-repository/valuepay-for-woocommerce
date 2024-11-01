<?php
/**
 * Plugin Name:       ValuePay for WooCommerce
 * Description:       Accept payment on WooCommerce using ValuePay.
 * Version:           1.0.3
 * Requires at least: 4.6
 * Requires PHP:      7.0
 * Author:            Valuefy Solutions Sdn Bhd
 * Author URI:        https://valuepay.my/
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( !defined( 'ABSPATH' ) ) exit;

if ( class_exists( 'Valuepay_WC' ) ) return;

define( 'VALUEPAY_WC_FILE', __FILE__ );
define( 'VALUEPAY_WC_URL', plugin_dir_url( VALUEPAY_WC_FILE ) );
define( 'VALUEPAY_WC_PATH', plugin_dir_path( VALUEPAY_WC_FILE ) );
define( 'VALUEPAY_WC_BASENAME', plugin_basename( VALUEPAY_WC_FILE ) );
define( 'VALUEPAY_WC_VERSION', '1.0.3' );

// Plugin core class
require( VALUEPAY_WC_PATH . 'includes/class-valuepay-wc.php' );
