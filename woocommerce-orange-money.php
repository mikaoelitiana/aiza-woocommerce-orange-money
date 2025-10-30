<?php
/**
 * Plugin Name: WooCommerce Orange Money Madagascar
 * Plugin URI: https://github.com/mikaoelitiana/aiza-woocommerce-orange-money
 * Description: Orange Money Madagascar payment gateway for WooCommerce using WebPay API
 * Version: 1.0.0
 * Author: Mika Andrianarijaona
 * Author URI: https://github.com/mikaoelitiana
 * License: Apache License 2.0
 * License URI: http://www.apache.org/licenses/LICENSE-2.0
 * Text Domain: woocommerce-orange-money
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WC_ORANGE_MONEY_VERSION', '1.0.0');
define('WC_ORANGE_MONEY_PLUGIN_FILE', __FILE__);
define('WC_ORANGE_MONEY_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('WC_ORANGE_MONEY_PLUGIN_URL', plugin_dir_url(__FILE__));

function wc_orange_money_add_gateway($gateways) {
    $gateways[] = 'WC_Gateway_Orange_Money';
    return $gateways;
}

function wc_orange_money_init() {
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

    require_once WC_ORANGE_MONEY_PLUGIN_PATH . 'includes/class-wc-gateway-orange-money.php';

    add_filter('woocommerce_payment_gateways', 'wc_orange_money_add_gateway');
}
add_action('plugins_loaded', 'wc_orange_money_init', 11);

add_action('before_woocommerce_init', function() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
});

add_action('woocommerce_blocks_loaded', function() {
    if (!class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
        return;
    }

    require_once WC_ORANGE_MONEY_PLUGIN_PATH . 'includes/class-wc-orange-money-blocks-support.php';

    add_action('woocommerce_blocks_payment_method_type_registration', function($payment_method_registry) {
        $payment_method_registry->register(new WC_Orange_Money_Blocks_Support());
    });
});

function wc_orange_money_plugin_links($links) {
    $settings_url = admin_url('admin.php?page=wc-settings&tab=checkout&section=orange_money');
    $plugin_links = array(
        '<a href="' . esc_url($settings_url) . '">' . __('Settings', 'woocommerce-orange-money') . '</a>',
    );
    return array_merge($plugin_links, $links);
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'wc_orange_money_plugin_links');

function wc_orange_money_activate() {
    if (!class_exists('WooCommerce')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(__('This plugin requires WooCommerce to be installed and active.', 'woocommerce-orange-money'));
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'wc_orange_money_transactions';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        order_id bigint(20) NOT NULL,
        notif_token varchar(190) NOT NULL,
        payment_url text,
        txnid varchar(100),
        status varchar(50),
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY notif_token (notif_token),
        KEY order_id (order_id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'wc_orange_money_activate');
