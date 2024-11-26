<?php
// BT Tesoro Plugin/includes/class-wc-bt-tesoro-blocks.php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class WC_BT_Tesoro_Blocks extends AbstractPaymentMethodType {

    private $gateway;
    protected $name = 'bt-tesoro'; // Your payment gateway name

    public function initialize() {
        $this->settings = get_option('woocommerce_bt_tesoro_settings', []);
        $this->gateway = new WC_BT_Tesoro();
    }

    public function is_active() {
        return $this->gateway->is_available();
    }

    public function get_payment_method_script_handles() {

        wp_register_script(
            'wc-bt-tesoro-blocks-integration',
            plugin_dir_url(__FILE__) . 'block/checkout.js',
            [
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n',
            ],
            null,
            true
        );

        return ['wc-bt-tesoro-blocks-integration'];
    }

    public function get_payment_method_data() {
        return [
            'title'       => $this->gateway->get_title(),
            'description' => $this->gateway->get_description(),
        ];
    }

}