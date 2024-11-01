<?php
if ( !defined( 'ABSPATH' ) ) exit;

class Valuepay_WC_Gateway extends WC_Payment_Gateway {

    private $valuepay;

    private $username;
    private $app_key;
    private $app_secret;
    private $collection_id;
    private $mandate_id;
    private $frequency_type;
    private $debug;

    public function __construct() {

        $this->id                 = 'valuepay';
        $this->has_fields         = true;
        $this->method_title       = __( 'ValuePay', 'valuepay-wc' );
        $this->method_description = __( 'Enable ValuePay payment gateway for your site.', 'valuepay-wc' );
        $this->order_button_text  = __( 'Pay with ValuePay', 'valuepay-wc' );
        $this->supports           = array( 'products' );

        $this->init_form_fields();
        $this->init_settings();

        $this->title              = $this->get_option( 'title' );
        $this->description        = $this->get_option( 'description' );
        $this->icon               = VALUEPAY_WC_URL . 'assets/images/pay-with-valuepay.png';

        $this->username           = $this->get_option( 'username' );
        $this->app_key            = $this->get_option( 'app_key' );
        $this->app_secret         = $this->get_option( 'app_secret' );
        $this->collection_id      = $this->get_option( 'collection_id' );
        $this->mandate_id         = $this->get_option( 'mandate_id' );
        $this->frequency_type     = $this->get_option( 'frequency_type' );
        $this->debug              = $this->get_option( 'debug' ) === 'yes';

        $this->register_hooks();

        // Check if the payment gateway is ready to use
        if ( !$this->validate_required_settings() ) {
            $this->enabled = 'no';
        }

        $this->init_api();

    }

    // Register WooCommerce payment gateway hooks
    private function register_hooks() {

        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        add_action( 'woocommerce_api_' . $this->id . '_wc_gateway', array( $this, 'handle_ipn' ) );

        add_action( 'woocommerce_checkout_create_order', array( $this, 'save_payment_fields' ), 10, 2 );

    }

    // Check if all required settings is filled
    private function validate_required_settings() {

        return $this->username
            && $this->app_key
            && $this->app_secret
            && ( $this->collection_id || $this->mandate_id );

    }

    // Override the normal options so we can print the webhook and callback URL to the admin
    public function admin_options() {
        parent::admin_options();
        include( VALUEPAY_WC_PATH . 'admin/views/settings/callback-url.php' );
    }

    // Form fields
    public function init_form_fields() {
        $this->form_fields = valuepay_wc_settings_form_fields();
    }

    // Initialize API
    private function init_api() {

        $this->valuepay = new Valuepay_WC_API(
            $this->username,
            $this->app_key,
            $this->app_secret,
            $this->debug
        );

    }

    // Register extra payment fields on checkout page
    public function payment_fields() {

        parent::payment_fields();

        $available_payment_types = $this->get_available_payment_types();

        // Show necessary fields if recurring payment option is enabled
        if ( isset( $available_payment_types['recurring'] ) ) {

            $banks = $this->get_banks();
            array_unshift( $banks, __( 'Select any bank', 'valuepay-wc' ) );

            ob_start();
            echo '<div class="' . esc_attr( $this->id ) . '-fields">';

            woocommerce_form_field( 'valuepay_identity_type', array(
                'type'          => 'select',
                'label'         => __( 'Identity Type', 'valuepay-wc' ),
                'class'         => array( 'form-row-wide' ),
                'options'       => valuepay_wc_get_identity_types(),
            ), '' );

            woocommerce_form_field( 'valuepay_identity_value', array(
                'type'          => 'text',
                'label'         => __( 'Identity Value', 'valuepay-wc' ),
                'class'         => array( 'form-row-wide' ),
            ), '' );

            woocommerce_form_field( 'valuepay_bank', array(
                'type'          => 'select',
                'label'         => __( 'Bank', 'valuepay-wc' ),
                'class'         => array( 'form-row-wide' ),
                'options'       => $banks,
            ), '' );

            woocommerce_form_field( 'valuepay_payment_type', array(
                'type'          => 'select',
                'label'         => __( 'Payment Type', 'valuepay-wc' ),
                'class'         => array( 'form-row-wide' ),
                'required'      => true,
                'options'       => $available_payment_types,
            ), '' );

            echo '<div>';

            echo ob_get_clean();
        }

    }

    // Get available payment types
    private function get_available_payment_types() {

        if ( $this->collection_id ) {
            $payment_types['single'] = __( 'One Time Payment', 'valuepay-wc' );
        }

        if ( $this->mandate_id ) {
            $frequency_type_label = $this->frequency_type === 'weekly' ? __( 'Weekly', 'valuepay-wc' ) : __( 'Monthly', 'valuepay-wc' );

            $payment_types['recurring'] = sprintf( __( 'Recurring %s Payment', 'valuepay-wc' ), $frequency_type_label );
        }

        return $payment_types;

    }

    // Get list of banks from ValuePay
    private function get_banks() {

        $banks = get_transient( 'valuepay_wc_banks' );

        if ( !$banks || !is_array( $banks ) ) {
            $banks = array();

            try {
                $banks_query = $this->valuepay->get_banks( array(
                    'username' => $this->username,
                    'reqhash'  => md5( $this->app_key . $this->username ),
                ) );

                if ( isset( $banks_query[1]['bank_list'] ) && !empty( $banks_query[1]['bank_list'] ) ) {
                    $banks = $banks_query[1]['bank_list'];

                    // Set transient, so that we can retrieve using transient
                    // instead of retrieve through API request to ValuePay.
                    set_transient( 'valuepay_wc_banks', $banks, DAY_IN_SECONDS );
                }
            } catch ( Exception $e ) {}
        }

        return $banks;

    }

    // Validate extra payment fields value from checkout page
    public function validate_fields() {

        $payment_type   = isset( $_POST[ 'valuepay_payment_type' ] ) ? wc_clean( $_POST[ 'valuepay_payment_type' ] ) : null;
        $identity_type  = isset( $_POST[ 'valuepay_identity_type' ] ) ? wc_clean( $_POST[ 'valuepay_identity_type' ] ) : null;
        $identity_value = isset( $_POST[ 'valuepay_identity_value' ] ) ? wc_clean( $_POST[ 'valuepay_identity_value' ] ) : null;
        $bank           = isset( $_POST[ 'valuepay_bank' ] ) ? wc_clean( $_POST[ 'valuepay_bank' ] ) : null;

        if ( $payment_type === 'recurring' ) {
            if ( !$identity_type || !$identity_value ) {
                wc_add_notice( __( 'Identity information is required for recurring payment.', 'valuepay-wc' ), 'error' );
            }

            if ( !$bank ) {
                wc_add_notice( __( 'Bank is required for recurring payment.', 'valuepay-wc' ), 'error' );
            }
        }

    }

    // Save extra payment fields value from checkout page
    public function save_payment_fields( $order, $data ) {

        $payment_type   = isset( $_POST[ 'valuepay_payment_type' ] ) ? wc_clean( $_POST[ 'valuepay_payment_type' ] ) : null;
        $identity_type  = isset( $_POST[ 'valuepay_identity_type' ] ) ? wc_clean( $_POST[ 'valuepay_identity_type' ] ) : null;
        $identity_value = isset( $_POST[ 'valuepay_identity_value' ] ) ? wc_clean( $_POST[ 'valuepay_identity_value' ] ) : null;
        $bank           = isset( $_POST[ 'valuepay_bank' ] ) ? wc_clean( $_POST[ 'valuepay_bank' ] ) : null;

        // Set default payment type
        if ( !$payment_type ) {
            $payment_type = 'single';
        }

        $order->update_meta_data(  '_valuepay_payment_type', $payment_type );

        if ( $payment_type == 'recurring' ) {
            $order->update_meta_data(  '_valuepay_identity_type', $identity_type );
            $order->update_meta_data(  '_valuepay_identity_value', $identity_value );
            $order->update_meta_data(  '_valuepay_bank', $bank );
        }

    }

    // Process the payment
    public function process_payment( $order_id ) {

        if ( !$this->validate_required_settings() ) {
            return false;
        }

        if ( !$order = wc_get_order( $order_id ) ) {
            return false;
        }

        // Redirect to the payment page if the bill ID has been saved
        if ( $bill_id = get_post_meta( $order_id, '_transaction_id', true ) ) {
            return array(
                'result'   => 'success',
                'redirect' => 'https://valuepay.my/b/' . $bill_id, // ValuePay payment URL
            );
        }

        try {
            valuepay_wc_logger( 'Creating payment for order #' . $order_id );

            $payment_type = $order->get_meta( '_valuepay_payment_type' );

            if ( $payment_type === 'recurring' ) {
                $payment_url = $this->get_enrolment_url( $order );
            } else {
                $payment_url = $this->get_bill_url( $order );
            }

            if ( !$payment_url ) {
                return;
            }

            valuepay_wc_logger( 'Payment created for order #' . $order_id );

            // Redirect to the payment page
            return array(
                'result'   => 'success',
                'redirect' => $payment_url,
            );

        } catch ( Exception $e ) {
            wc_add_notice( __( 'Payment error: ', 'valuepay-wc' ) . $e->getMessage(), 'error' );
        }

        return;

    }

    // Create an enrolment in ValuePay (for recurring payment)
    private function get_enrolment_url( $order ) {

        $full_name      = $order->get_formatted_billing_full_name() ?: $order->get_formatted_shipping_full_name();
        $email          = $order->get_billing_email();
        $telephone      = valuepay_wc_format_telephone( $order->get_billing_phone() );
        $identity_type  = $order->get_meta( '_valuepay_identity_type' );
        $identity_value = $order->get_meta( '_valuepay_identity_value' );
        $bank           = $order->get_meta( '_valuepay_bank' );

        if ( !$full_name ) {
            throw new Exception( __( 'Name is required', 'valuepay-wc' ) );
        }

        if ( !$email ) {
            throw new Exception( __( 'Email is required', 'valuepay-wc' ) );
        }

        if ( !$telephone ) {
            throw new Exception( __( 'Telephone is required', 'valuepay-wc' ) );
        }

        if ( !$identity_type || !$identity_value ) {
            throw new Exception( __( 'Identity information is required for recurring payment', 'valuepay-wc' ) );
        }

        if ( !$bank ) {
            throw new Exception( __( 'Bank is required for recurring payment', 'valuepay-wc' ) );
        }

        $params = array(
            'username'        => $this->username,
            'sub_fullname'    => $full_name,
            'sub_ident_type'  => $identity_type,
            'sub_ident_value' => $identity_value,
            'sub_telephone'   => $telephone,
            'sub_email'       => $email,
            'sub_mandate_id'  => $this->mandate_id,
            'sub_bank_id'     => $bank,
            'sub_amount'      => (float) $order->get_total(),
        );

        $hash_data = array(
            $this->app_key,
            $this->username,
            $params['sub_fullname'],
            $params['sub_ident_type'],
            $params['sub_telephone'],
            $params['sub_email'],
            $params['sub_mandate_id'],
            $params['sub_bank_id'],
            $params['sub_amount'],
        );

        $params['reqhash'] = md5( implode( '', array_values( $hash_data ) ) );

        list( $code, $response ) = $this->valuepay->set_enrol_data( $params );

        // Delete meta data
        $order->delete_meta_data( '_valuepay_identity_type' );
        $order->delete_meta_data( '_valuepay_identity_value' );
        $order->delete_meta_data( '_valuepay_bank' );
        $order->delete_meta_data( '_valuepay_payment_type' );

        if ( isset( $response['method'] ) && isset( $response['method'] ) == 'GET' && isset( $response['action'] ) ) {

            $order->set_status( 'on-hold' );

            return $response['action'];
        }

        return false;

    }

    // Create a bill in ValuePay (for one time payment)
    private function get_bill_url( $order ) {

        $full_name = $order->get_formatted_billing_full_name() ?: $order->get_formatted_shipping_full_name();
        $email     = $order->get_billing_email();
        $telephone = valuepay_wc_format_telephone( $order->get_billing_phone() );

        if ( !$full_name ) {
            throw new Exception( __( 'Name is required', 'valuepay-wc' ) );
        }

        if ( !$email ) {
            throw new Exception( __( 'Email is required', 'valuepay-wc' ) );
        }

        if ( !$telephone ) {
            throw new Exception( __( 'Telephone is required', 'valuepay-wc' ) );
        }

        $params = array(
            'username'          => $this->username,
            'orderno'           => $order->get_id(),
            'bill_amount'       => (float) $order->get_total(),
            'collection_id'     => $this->collection_id,
            'buyer_data'        => array(
                'buyer_name'    => $full_name,
                'mobile_number' => $telephone,
                'email'         => $email,
            ),
            'bill_frontend_url' => $this->get_return_url( $order ),
            'bill_backend_url'  => WC()->api_request_url( get_class( $this ) ),
        );

        $hash_data = array(
            $this->app_key,
            $this->username,
            $params['bill_amount'],
            $params['collection_id'],
            $params['orderno'],
        );

        $params['reqhash'] = md5( implode( '', array_values( $hash_data ) ) );

        list( $code, $response ) = $this->valuepay->create_bill( $params );

        if ( isset( $response['bill_id'] ) ) {
            update_post_meta( $order->get_id(), '_transaction_id', wc_clean( $response['bill_id'] ) );
        }

        if ( isset( $response['bill_url'] ) ) {
            return $response['bill_url'];
        }

        return false;

    }

    // Handle IPN
    public function handle_ipn() {

        $response = $this->valuepay->get_ipn_response();

        if ( $_SERVER['REQUEST_METHOD'] === 'GET' ) {
            return $this->handle_ipn_redirect( $response );
        } else {
            return $this->handle_ipn_callback( $response );
        }

    }

    // Handle IPN (redirect)
    private function handle_ipn_redirect( $response ) {

        if ( !$response ) {
            valuepay_wc_logger( 'IPN (redirect) failed' );
            wp_die( 'ValuePay IPN (redirect) failed', 'ValuePay IPN', array( 'response' => 500 ) );
        }

        valuepay_wc_logger( 'IPN (redirect) response: ' . wp_json_encode( $response ) );

        $order = wc_get_order( $response['order_id'] );

        if ( !$order ) {
            valuepay_wc_logger( 'Order #' . $response['order_id'] . ' not found' );
            wp_die( 'An error occured. Please refresh this page or contact admin for further assistance.', 'ValuePay IPN', array( 'response' => 200 ) );
        }

        wp_redirect( $order->get_checkout_order_received_url() );
        exit;

    }

    // Handle IPN (callback)
    private function handle_ipn_callback( $response ) {

        if ( !$response ) {
            valuepay_wc_logger( 'IPN (callback) failed' );
            wp_die( 'ValuePay IPN (callback) failed', 'ValuePay IPN', array( 'response' => 200 ) );
        }

        valuepay_wc_logger( 'IPN (callback) response: ' . wp_json_encode( $response ) );

        $order_id = absint( $response['orderno'] );
        $order = wc_get_order( $order_id );

        if ( !$order ) {
            valuepay_wc_logger( 'Order #' . $order_id . ' not found' );
            return false;
        }

        // Check if the payment already marked as paid
        if ( get_post_meta( $order_id, $response['bill_id'], true ) === 'paid' ) {
            return false;
        }

        try {
            valuepay_wc_logger( 'Verifying hash for order #' . $order_id );
            $this->valuepay->validate_ipn_response( $response );
        } catch ( Exception $e ) {
            valuepay_wc_logger( $e->getMessage() );
            wp_die( $e->getMessage(), 'ValuePay IPN', array( 'response' => 200 ) );
        } finally {
            valuepay_wc_logger( 'Verified hash for order #' . $order_id );
        }

        if ( $response['bill_status'] === 'paid' ) {
            $this->handle_success_payment( $order, $response );
        }

        valuepay_wc_logger( 'IPN (callback) success' );
        wp_die( 'ValuePay IPN (callback) success', 'ValuePay IPN', array( 'response' => 200 ) );

    }

    // Handle success payment
    private function handle_success_payment( WC_Order $order, $response ) {

        update_post_meta( $order->get_id(), '_transaction_id', wc_clean( $response['bill_id'] ) );
        update_post_meta( $order->get_id(), $response['bill_id'], 'paid' );

        $order->payment_complete();

        $order->add_order_note( sprintf( esc_html__( 'Payment success! Payment ID: %s', 'valuepay-wc' ), $response['bill_id'] ) );

        valuepay_wc_logger( 'Order #' . $order->get_id() . ' has been marked as Paid' );

    }

}
