<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Gateway_Orange_Money extends WC_Payment_Gateway {

    const API_BASE_URL = 'https://api.orange.com';
    
    public $testmode;
    public $merchant_key;
    public $consumer_key;
    public $client_id;
    public $client_secret;
    
    public function __construct() {
        $this->id = 'orange_money';
        $this->icon = apply_filters('woocommerce_orange_money_icon', '');
        $this->has_fields = false;
        $this->method_title = __('Orange Money Madagascar', 'woocommerce-orange-money');
        $this->method_description = __('Accept payments via Orange Money using the WebPay API', 'woocommerce-orange-money');
        $this->supports = array(
            'products',
            'refunds'
        );

        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->testmode = 'yes' === $this->get_option('testmode');
        $this->merchant_key = $this->get_option('merchant_key');
        $this->consumer_key = $this->get_option('consumer_key');
        $this->client_id = $this->get_option('client_id');
        $this->client_secret = $this->get_option('client_secret');

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_api_wc_gateway_orange_money', array($this, 'handle_webhook'));
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'woocommerce-orange-money'),
                'type' => 'checkbox',
                'label' => __('Enable Orange Money payment', 'woocommerce-orange-money'),
                'default' => 'no'
            ),
            'title' => array(
                'title' => __('Title', 'woocommerce-orange-money'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'woocommerce-orange-money'),
                'default' => __('Orange Money', 'woocommerce-orange-money'),
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __('Description', 'woocommerce-orange-money'),
                'type' => 'textarea',
                'description' => __('This controls the description which the user sees during checkout.', 'woocommerce-orange-money'),
                'default' => __('Pay securely using Orange Money.', 'woocommerce-orange-money'),
            ),
            'testmode' => array(
                'title' => __('Test mode', 'woocommerce-orange-money'),
                'type' => 'checkbox',
                'label' => __('Enable Test Mode', 'woocommerce-orange-money'),
                'default' => 'yes',
                'description' => __('Use the sandbox environment for testing. No real money will be used.', 'woocommerce-orange-money'),
            ),
            'merchant_key' => array(
                'title' => __('Merchant Key', 'woocommerce-orange-money'),
                'type' => 'text',
                'description' => __('Get your merchant key from Orange Money developer portal.', 'woocommerce-orange-money'),
                'default' => '',
                'desc_tip' => true,
            ),
            'consumer_key' => array(
                'title' => __('Consumer Key', 'woocommerce-orange-money'),
                'type' => 'password',
                'description' => __('Get your consumer key from Orange Money developer portal.', 'woocommerce-orange-money'),
                'default' => '',
                'desc_tip' => true,
            ),
            'client_id' => array(
                'title' => __('Client ID', 'woocommerce-orange-money'),
                'type' => 'text',
                'description' => __('Get your client ID from Orange Money developer portal.', 'woocommerce-orange-money'),
                'default' => '',
                'desc_tip' => true,
            ),
            'client_secret' => array(
                'title' => __('Client Secret', 'woocommerce-orange-money'),
                'type' => 'password',
                'description' => __('Get your client secret from Orange Money developer portal.', 'woocommerce-orange-money'),
                'default' => '',
                'desc_tip' => true,
            ),
        );
    }

    public function is_available() {
        $is_available = parent::is_available();
        
        if (!$is_available) {
            return false;
        }

        if ($this->testmode) {
            return true;
        }

        if (empty($this->merchant_key) || empty($this->consumer_key)) {
            return false;
        }

        return true;
    }

    private function get_access_token() {
        $cached_token = get_transient('orange_money_access_token');
        if ($cached_token) {
            return $cached_token;
        }

        $response = wp_remote_post(self::API_BASE_URL . '/oauth/v3/token', array(
            'headers' => array(
                'Authorization' => 'Basic ' . $this->consumer_key,
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept' => 'application/json',
            ),
            'body' => array(
                'grant_type' => 'client_credentials',
            ),
        ));

        if (is_wp_error($response)) {
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['access_token'])) {
            $token = $body['access_token'];
            set_transient('orange_money_access_token', $token, 3600);
            return $token;
        }

        return false;
    }

    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        $access_token = $this->get_access_token();

        if (!$access_token) {
            wc_add_notice(__('Payment error: Could not authenticate with Orange Money.', 'woocommerce-orange-money'), 'error');
            return array('result' => 'fail');
        }

        $notif_token = wp_generate_password(40, false);
        $order_ref = 'order_' . wp_generate_password(25, false) . '_' . time();

        $url_prefix = $this->testmode ? '/orange-money-webpay/dev/v1/webpayment' : '/orange-money-webpay/gf/v1/webpayment';
        
        $payment_data = array(
            'merchant_key' => $this->merchant_key,
            'currency' => $this->testmode ? 'OUV' : get_woocommerce_currency(),
            'order_id' => $order_ref,
            'amount' => (float) $order->get_total(),
            'notif_url' => WC()->api_request_url('wc_gateway_orange_money'),
            'return_url' => $this->get_return_url($order),
            'cancel_url' => wc_get_checkout_url(),
            'lang' => substr(get_locale(), 0, 2),
        );

        $response = wp_remote_post(self::API_BASE_URL . $url_prefix, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ),
            'body' => json_encode($payment_data),
        ));

        if (is_wp_error($response)) {
            wc_add_notice(__('Payment error: ', 'woocommerce-orange-money') . $response->get_error_message(), 'error');
            return array('result' => 'fail');
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['message']) && $body['message'] === 'OK' && isset($body['payment_url'])) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'wc_orange_money_transactions';
            
            $wpdb->insert(
                $table_name,
                array(
                    'order_id' => $order_id,
                    'notif_token' => $body['notif_token'],
                    'payment_url' => $body['payment_url'],
                    'status' => 'pending',
                )
            );

            $order->update_status('pending', __('Awaiting Orange Money payment', 'woocommerce-orange-money'));

            return array(
                'result' => 'success',
                'redirect' => $body['payment_url'],
            );
        }

        wc_add_notice(__('Payment error: Could not initiate payment.', 'woocommerce-orange-money'), 'error');
        return array('result' => 'fail');
    }

    public function handle_webhook() {
        global $wpdb;

        $raw_body = file_get_contents('php://input');
        $payload = json_decode($raw_body, true);

        if (!$payload || !isset($payload['notif_token'])) {
            status_header(400);
            exit;
        }

        $table_name = $wpdb->prefix . 'wc_orange_money_transactions';
        $transaction = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE notif_token = %s",
            $payload['notif_token']
        ));

        if (!$transaction) {
            status_header(404);
            exit;
        }

        $order = wc_get_order($transaction->order_id);

        if (!$order) {
            status_header(404);
            exit;
        }

        if ($payload['status'] === 'SUCCESS') {
            $wpdb->update(
                $table_name,
                array(
                    'status' => 'success',
                    'txnid' => isset($payload['txnid']) ? $payload['txnid'] : '',
                ),
                array('notif_token' => $payload['notif_token'])
            );

            $order->payment_complete(isset($payload['txnid']) ? $payload['txnid'] : '');
            $order->add_order_note(
                sprintf(__('Orange Money payment completed. Transaction ID: %s', 'woocommerce-orange-money'), 
                isset($payload['txnid']) ? $payload['txnid'] : 'N/A')
            );

            status_header(200);
            echo 'Payment confirmed for order ' . $order->get_order_number();
        } else {
            $wpdb->update(
                $table_name,
                array('status' => 'failed'),
                array('notif_token' => $payload['notif_token'])
            );

            $order->update_status('failed', __('Orange Money payment failed', 'woocommerce-orange-money'));
            
            status_header(200);
            echo 'Payment failed for order ' . $order->get_order_number();
        }

        exit;
    }

    public function process_refund($order_id, $amount = null, $reason = '') {
        return false;
    }
}
