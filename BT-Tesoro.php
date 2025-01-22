<?php
/*
Plugin Name: Botón de Pago (C2P) Banco del Tesoro Payment Gateway for WooCommerce
Description: Botón de Pago (C2P) Banco del Tesoro payment gateway integration for WooCommerce
license: GPLv2
icon: assets/logo.png
Version: 1.1.0
Author: Abdullah Fares
*/

if (!defined('ABSPATH')) {
    exit;
}

add_filter('woocommerce_payment_gateways', 'add_bt_tesoro_gateway');

function add_bt_tesoro_gateway($gateways) {
    $gateways[] = 'WC_BT_Tesoro';
    return $gateways;
}

add_action('before_woocommerce_init', 'declare_cart_checkout_blocks_compatibility');
function declare_cart_checkout_blocks_compatibility() {
    // Check if the required class exists
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        // Declare compatibility for 'cart_checkout_blocks'
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
}

// Initialize the gateway after WooCommerce has loaded
add_action('plugins_loaded', 'init_bt_tesoro_gateway');

function init_bt_tesoro_gateway() {
    // Check if WooCommerce is active
    if (!class_exists('WC_Payment_Gateway')) return;
    
    // Include the gateway class
    require_once plugin_dir_path(__FILE__) . 'includes/class-wc-bt-tesoro.php';
}

add_action('woocommerce_blocks_loaded', 'bt_tesoro_register_checkout_blocks_payment_method_type');

/**
 * Custom function to register a payment method type
 */
function bt_tesoro_register_checkout_blocks_payment_method_type() {
    // Check if the required class exists
    if (!class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
        return;
    }

    // Include the custom Blocks Checkout class
    require_once plugin_dir_path(__FILE__) . 'includes/class-wc-bt-tesoro-blocks.php';

    // Hook the registration function to the 'woocommerce_blocks_payment_method_type_registration' action
    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function(Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
            // Register an instance of WC_BT_Tesoro_Blocks
            $payment_method_registry->register(new WC_BT_Tesoro_Blocks());
        }
    );
}
