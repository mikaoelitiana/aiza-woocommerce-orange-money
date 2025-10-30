<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class WC_Orange_Money_Blocks_Support extends AbstractPaymentMethodType {
    
    protected $name = 'orange_money';

    public function initialize() {
        $this->settings = get_option('woocommerce_orange_money_settings', []);
    }

    public function is_active() {
        return !empty($this->settings['enabled']) && 'yes' === $this->settings['enabled'];
    }

    public function get_payment_method_script_handles() {
        wp_register_script(
            'wc-orange-money-blocks',
            WC_ORANGE_MONEY_PLUGIN_URL . 'assets/js/blocks-checkout.js',
            ['wc-blocks-registry', 'wp-element', 'wp-i18n'],
            WC_ORANGE_MONEY_VERSION,
            true
        );

        return ['wc-orange-money-blocks'];
    }

    public function get_payment_method_data() {
        return [
            'title' => $this->get_setting('title'),
            'description' => $this->get_setting('description'),
            'supports' => ['products'],
        ];
    }
}
