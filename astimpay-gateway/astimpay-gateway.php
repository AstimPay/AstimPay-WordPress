<?php
/**
 * Plugin Name:    AstimPay
 * Plugin URI:     https://github.com/AstimPay/Astimpay-WordPress
 * Description:    Accept payments via bKash, Rocket, Nagad, Upay and International methods through AstimPay
 * Version:        1.0.0
 * Author:         AstimPay
 * Author URI:     https://astimpay.com
 * License:        GPL v2 or later
 * License URI:    https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:    astimpay-gateway
 */

// If this file is called directly, abort!!!
defined('ABSPATH') || die('Direct access is not allowed.');

// Constant
define('ASTIMPAY_VERSION', '1.0.0');
define('ASTIMPAY_FILE', __FILE__);
define('ASTIMPAY_PATH', plugin_dir_path(ASTIMPAY_FILE));
define('ASTIMPAY_URL', plugin_dir_url(ASTIMPAY_FILE));

// Require composer autoload
require_once ASTIMPAY_PATH . '/vendor/autoload.php';

final class AstimPay_Plugin
{
    private static $instance = null;

    public $gateways = [];

    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        $this->init_hooks();
    }

    private function init_hooks()
    {
        add_action('plugins_loaded', [$this, 'init_plugin']);
        add_action('woocommerce_blocks_loaded', [$this, 'init_blocks_support']);
        add_action('before_woocommerce_init', [$this, 'declare_blocks_compatibility']);
    }

    public function init_plugin()
    {
        if (!class_exists('WC_Payment_Gateway')) {
            add_action('admin_notices', [$this, 'woocommerce_missing_notice']);
            return;
        }

        $this->init_gateways();
        $this->init_admin();
    }

    public function woocommerce_missing_notice()
    {
        printf(
            '<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
            sprintf(
                esc_html__('%1$s requires %2$s to be installed and activated.', 'astimpay-gateway'),
                '<strong>AstimPay Gateway</strong>',
                '<strong>WooCommerce</strong>'
            )
        );
    }

    private function init_gateways()
    {
        $this->gateways = [
            new AstimPay\AstimPayGateway\LocalGateway(),
            new AstimPay\AstimPayGateway\ShippingOnlyGateway(),
        ];

        add_filter('woocommerce_payment_gateways', [$this, 'add_gateways']);
        add_action('woocommerce_after_checkout_form', [$this, 'refresh_checkout']);
    }

    public function add_gateways($gateways)
    {
        foreach ($this->gateways as $gateway) {
            $gateways[] = $gateway;
        }
        return $gateways;
    }

    public function refresh_checkout()
    {
        wc_enqueue_js("
            jQuery('form.checkout').on('change', 'input[name^=\"payment_method\"]', function() {
                jQuery('body').trigger('update_checkout');
            });
        ");
    }

    private function init_admin()
    {
        if (!is_admin()) {
            return;
        }

        add_filter('plugin_action_links_' . plugin_basename(ASTIMPAY_FILE), [$this, 'plugin_action_links']);
    }

    public function plugin_action_links($links)
    {
        $plugin_links = [
            sprintf(
                '<a href="%s">%s</a>',
                admin_url('admin.php?page=wc-settings&tab=checkout&section=astimpay'),
                __('AstimPay Methods Settings', 'astimpay-gateway')
            ),
            sprintf(
                '<a href="%s">%s</a>',
                'https://astimpay.com',
                __('<b style="color: green">Purchase License</b>', 'astimpay-gateway')
            ),
        ];

        return array_merge($links, $plugin_links);
    }

    public function init_blocks_support()
    {
        if (!class_exists('\Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
            return;
        }

        add_action(
            'woocommerce_blocks_payment_method_type_registration',
            function ($registry) {
                $registry->register(new AstimPay\AstimPayGateway\Blocks\LocalBlocks());
                $registry->register(new AstimPay\AstimPayGateway\Blocks\ShippingBlocks());
            }
        );
    }

    public function declare_blocks_compatibility()
    {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', ASTIMPAY_FILE, true);
        }
    }
}

AstimPay_Plugin::instance();
