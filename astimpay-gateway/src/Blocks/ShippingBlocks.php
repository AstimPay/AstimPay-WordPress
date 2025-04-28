<?php

declare(strict_types=1);

namespace AstimPay\AstimPayGateway\Blocks;

// If this file is called directly, abort!!!
defined('ABSPATH') || die('Direct access is not allowed.');

class ShippingBlocks extends \Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType
{
    /**
     * Payment method name/id/slug
     *
     * @var string
     */
    protected $name = 'astimpay_shipping_only';

    /**
     * Settings Array
     *
     * @var array
     */
    private $gateway_settings = [];

    /**
     * Initialize payment method
     *
     * @return void
     */
    public function initialize()
    {
        $this->settings = get_option('woocommerce_astimpay_shipping_only_settings', []);
        $this->gateway_settings = [
            'title' => $this->settings['title'] ?? __('Mobile Banking (Shipping Only)', 'astimpay-gateway'),
            'description' => $this->settings['description'] ?? __('Pay only the shipping cost with bKash, Rocket, Nagad, Upay', 'astimpay-gateway'),
            'supports' => [
                'products',
            ],
        ];
    }

    /**
     * Check if payment method is active
     *
     * @return boolean
     */
    public function is_active()
    {
        return !empty($this->settings['enabled']) && 'yes' === $this->settings['enabled'];
    }

    /**
     * Get payment method script handles
     *
     * @return array
     */
    public function get_payment_method_script_handles()
    {
        wp_register_script(
            'astimpay-shipping-only-blocks-integration',
            ASTIMPAY_URL . 'assets/js/blocks-integration-shipping.js',
            ['wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-html-entities', 'wp-i18n'],
            ASTIMPAY_VERSION,
            true
        );

        return ['astimpay-shipping-only-blocks-integration'];
    }

    /**
     * Get payment method data
     *
     * @return array
     */
    public function get_payment_method_data()
    {
        return $this->gateway_settings;
    }
}