<?php

declare(strict_types=1);

namespace AstimPay\AstimPayGateway;

use AstimPay\AstimPayGateway\APIHandler;
use AstimPay\AstimPayGateway\Enums\OrderStatus;

// If this file is called directly, abort!!!
defined('ABSPATH') || die('Direct access is not allowed.');

class ShippingOnlyGateway extends \WC_Payment_Gateway
{
    /**
     * API Handler instance
     *
     * @var APIHandler|null
     */
    protected $api = null;

    /**
     * Debug mode
     *
     * @var bool
     */
    protected $debug = false;

    /**
     * API URL
     *
     * @var string
     */
    protected $api_url = '';

    /**
     * API Key
     *
     * @var string
     */
    protected $api_key = '';

    /**
     * Webhook URL
     *
     * @var string
     */
    protected $webhook_url;

    /**
     * Exchange rate
     *
     * @var float
     */
    protected $exchange_rate = 120.0;

    /**
     * Constructor for the gateway.
     */
    public function __construct()
    {
        // Setup general properties
        $this->setup_properties();

        // Load the settings
        $this->init_form_fields();
        $this->init_settings();

        // Get settings with type coercion
        $this->title = (string) $this->get_option('title', __('AstimPay (Shipping Only)', 'astimpay-gateway'));
        $this->description = (string) $this->get_option('description', __('Pay only the shipping cost via Bangladeshi & International payment methods.', 'astimpay-gateway'));
        $this->api_key = (string) $this->get_option('api_key', '');
        $this->api_url = (string) $this->get_option('api_url', '');
        $this->exchange_rate = (float) $this->get_option('exchange_rate', '120');
        $this->debug = $this->get_option('debug') === 'yes';

        // Actions
        add_action(
            'woocommerce_update_options_payment_gateways_' . $this->id,
            [$this, 'process_admin_options']
        );

        add_action(
            'woocommerce_api_' . $this->id,
            [$this, 'handle_webhook']
        );

        add_action('woocommerce_admin_order_data_after_billing_address',
            [$this, 'display_transaction_data']
        );

        // Validation
        if (!$this->is_valid_for_use()) {
            $this->enabled = 'no';
        }
    }

    /**
     * Setup general properties for the gateway
     *
     * @return void
     */
    protected function setup_properties()
    {
        $this->id = 'astimpay_shipping_only';
        $this->icon = (string) apply_filters('woocommerce_astimpay_icon', '');
        $this->has_fields = false;
        $this->method_title = __('AstimPay Shipping Only', 'astimpay-gateway');
        $this->method_description = sprintf(
            '%s<br/><a href="%s" target="_blank">%s</a>',
            __('Pay only the shipping cost via Bangladeshi payment gateways like bKash, Nagad, Rocket, and more.', 'astimpay-gateway'),
            esc_url('https://astimpay.com'),
            __('Sign up for AstimPay account', 'astimpay-gateway')
        );
        $this->webhook_url = (string) add_query_arg('wc-api', $this->id, home_url('/'));
        $this->supports = [
            'products',
        ];
    }

    /**
     * Check if gateway is valid for use
     *
     * @return bool
     */
    protected function is_valid_for_use()
    {
        if (empty($this->api_key) || empty($this->api_url)) {
            $this->add_error(__('AstimPay Shipping Only requires API Key and API URL to be configured.', 'astimpay-gateway'));
            return false;
        }
        return true;
    }

    /**
     * Initialize Gateway Settings Form Fields
     *
     * @return void
     */
    public function init_form_fields()
    {
        $currency = get_woocommerce_currency();

        $base_fields = [
            'enabled' => [
                'title' => __('Enable/Disable', 'astimpay-gateway'),
                'type' => 'checkbox',
                'label' => __('Enable AstimPay Shipping Only', 'astimpay-gateway'),
                'default' => 'no',
            ],
            'title' => [
                'title' => __('Title', 'astimpay-gateway'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'astimpay-gateway'),
                'default' => __('Bangladeshi Payment (Shipping Only)', 'astimpay-gateway'),
                'desc_tip' => true,
            ],
            'description' => [
                'title' => __('Description', 'astimpay-gateway'),
                'type' => 'textarea',
                'description' => __('This controls the description which the user sees during checkout.', 'astimpay-gateway'),
                'default' => __('Pay only the shipping cost via Bangladeshi payment methods.', 'astimpay-gateway'),
                'desc_tip' => true,
            ],
            'api_key' => [
                'title' => __('API Key', 'astimpay-gateway'),
                'type' => 'password',
                'description' => __('Get your API key from AstimPay Panel → Brand Settings.', 'astimpay-gateway'),
            ],
            'api_url' => [
                'title' => __('API URL', 'astimpay-gateway'),
                'type' => 'url',
                'description' => __('Get your API URL from AstimPay Panel → Brand Settings.', 'astimpay-gateway'),
            ],
            'physical_product_status' => [
                'title' => __('Physical Product Status', 'astimpay-gateway'),
                'type' => 'select',
                'description' => __('Select status for physical product orders after successful payment.', 'astimpay-gateway'),
                'default' => OrderStatus::PROCESSING,
                'options' => [
                    OrderStatus::ON_HOLD => __('On Hold', 'astimpay-gateway'),
                    OrderStatus::PROCESSING => __('Processing', 'astimpay-gateway'),
                    OrderStatus::COMPLETED => __('Completed', 'astimpay-gateway'),
                ],
            ],
            'digital_product_status' => [
                'title' => __('Digital Product Status', 'astimpay-gateway'),
                'type' => 'select',
                'description' => __('Select status for digital/downloadable product orders after successful payment.', 'astimpay-gateway'),
                'default' => OrderStatus::COMPLETED,
                'options' => [
                    OrderStatus::ON_HOLD => __('On Hold', 'astimpay-gateway'),
                    OrderStatus::PROCESSING => __('Processing', 'astimpay-gateway'),
                    OrderStatus::COMPLETED => __('Completed', 'astimpay-gateway'),
                ],
            ],
        ];

        if ($currency !== 'BDT') {
            $base_fields['exchange_rate'] = [
                'title' => sprintf(__('%s to BDT Exchange Rate', 'astimpay-gateway'), $currency),
                'type' => 'text',
                'desc_tip' => true,
                'description' => __('This rate will be applied to convert the shipping amount to BDT', 'astimpay-gateway'),
                'default' => '0',
                'custom_attributes' => [
                    'step' => '0.01',
                    'min' => '0',
                ],
            ];
        }

        $base_fields['debug'] = [
            'title' => __('Debug Log', 'astimpay-gateway'),
            'type' => 'checkbox',
            'label' => __('Enable logging', 'astimpay-gateway'),
            'default' => 'no',
            'description' => sprintf(
                __('Log gateway events inside %s', 'astimpay-gateway'),
                '<code>' . \WC_Log_Handler_File::get_log_file_path('astimpay_shipping_only') . '</code>'
            ),
        ];

        $this->form_fields = $base_fields;
    }

    /**
     * Get the API Handler instance
     *
     * @return APIHandler
     */
    protected function get_api()
    {
        if ($this->api === null) {
            APIHandler::$debug = $this->debug;
            APIHandler::$api_url = $this->api_url;
            APIHandler::$api_key = $this->api_key;

            $this->api = new APIHandler();
        }
        return $this->api;
    }

    /**
     * Process Payment (Shipping Cost Only)
     *
     * @param int $order_id Order ID
     * @return array|null
     */
    public function process_payment($order_id)
    {
        try {
            $order = wc_get_order($order_id);
            if (!$order) {
                throw new \Exception(__('Invalid order', 'astimpay-gateway'));
            }

            // Get only the shipping total
            $shipping_total = $order->get_shipping_total();
            if ($shipping_total <= 0) {
                throw new \Exception(__('No shipping cost to process.', 'astimpay-gateway'));
            }

            $metadata = [
                'order_id' => $order->get_id(),
                'redirect_url' => $this->get_return_url($order),
                'payment_type' => 'shipping_only', // Add identifier for this payment type
            ];

            $result = $this->get_api()->create_payment(
                $shipping_total, // Use only shipping total instead of full order total
                $order->get_currency(),
                $order->get_billing_first_name(),
                $order->get_billing_email(),
                $metadata,
                $this->webhook_url,
                $order->get_cancel_order_url_raw(),
                $this->webhook_url,
                $this->exchange_rate
            );

            if (empty($result->payment_url)) {
                throw new \Exception($result->message ?? __('Payment URL not received', 'astimpay-gateway'));
            }

            // Mark as pending payment
            $order->update_status(
                OrderStatus::PENDING,
                __('Awaiting AstimPay Shipping Only payment', 'astimpay-gateway')
            );

            // Add a note indicating this payment is for shipping only
            $order->add_order_note(__('AstimPay Shipping Only payment initiated for amount: ' . $shipping_total, 'astimpay-gateway'));

            // Empty cart
            WC()->cart->empty_cart();

            return [
                'result' => 'success',
                'redirect' => $result->payment_url,
            ];

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Handle Webhook
     *
     * @return void
     */
    public function handle_webhook()
    {
        try {
            $invoice_id = isset($_POST['invoice_id']) ? sanitize_text_field($_POST['invoice_id']) : '';

            if (!empty($invoice_id)) {
                $this->handle_redirect_verification($invoice_id);
            } else {
                $this->handle_webhook_notification();
            }
        } catch (\Exception $e) {
            wp_die($e->getMessage(), 'AstimPay Shipping Only Webhook Error', ['response' => 500]);
        }
    }

    /**
     * Handle redirect verification
     *
     * @param string $invoice_id
     * @return void
     */
    protected function handle_redirect_verification($invoice_id)
    {
        $result = $this->get_api()->verify_payment($invoice_id);

        if (!isset($result->metadata->order_id)) {
            throw new \Exception(__('Invalid order data received', 'astimpay-gateway'));
        }

        $order = wc_get_order($result->metadata->order_id);
        if (!$order) {
            throw new \Exception(__('Order not found', 'astimpay-gateway'));
        }

        $this->process_order_status($order, $result);

        wp_redirect($result->metadata->redirect_url);
        exit;
    }

    /**
     * Handle webhook notification
     *
     * @return void
     */
    protected function handle_webhook_notification()
    {
        $payload = file_get_contents('php://input');

        if (empty($payload)) {
            throw new \Exception(__('Empty webhook payload', 'astimpay-gateway'));
        }

        if (!$this->validate_webhook_signature()) {
            throw new \Exception(__('Invalid webhook signature', 'astimpay-gateway'));
        }

        $data = json_decode($payload);

        if (!isset($data->metadata->order_id)) {
            throw new \Exception(__('Order ID not found in webhook data', 'astimpay-gateway'));
        }

        $order = wc_get_order($data->metadata->order_id);
        if (!$order) {
            throw new \Exception(__('Order not found', 'astimpay-gateway'));
        }

        $this->process_order_status($order, $data);
    }

    /**
     * Validate webhook signature
     *
     * @return bool
     */
    protected function validate_webhook_signature()
    {
        $provided_key = isset($_SERVER['API-KEY']) ? $_SERVER['API-KEY'] : '';
        return hash_equals($this->api_key, $provided_key);
    }

    /**
     * Process order status
     *
     * @param WC_Order $order
     * @param object $data
     * @return void
     */
    protected function process_order_status($order, $data)
    {
        if ($order->get_status() === OrderStatus::COMPLETED) {
            return;
        }

        $order->update_meta_data('astimpay_shipping_only_payment_data', $data);

        if ($data->status === 'COMPLETED') {
            $this->handle_completed_payment($order, $data);
        } else {
            $order->update_status(
                OrderStatus::ON_HOLD,
                __('Shipping payment is on hold. Please check manually.', 'astimpay-gateway')
            );
        }

        $order->save();
    }

    /**
     * Handle completed payment
     *
     * @param WC_Order $order
     * @param object $data
     * @return void
     */
    protected function handle_completed_payment($order, $data)
    {
        $status = $this->is_order_virtual($order) ? $this->get_option('digital_product_status', OrderStatus::COMPLETED) : $this->get_option('physical_product_status', OrderStatus::PROCESSING);
        $note = sprintf(
            __('Shipping payment via %s. Amount: %s, Transaction ID: %s', 'astimpay-gateway'),
            $data->payment_method,
            $data->amount,
            $data->transaction_id
        );

        // Since this is shipping only, we don't call payment_complete() as it marks the full order as paid
        $order->update_status($status, $note);
        $order->add_order_note(__('AstimPay Shipping Only payment completed.', 'astimpay-gateway'));
    }

    /**
     * Check if order is virtual
     *
     * @param WC_Order $order
     * @return bool
     */
    protected function is_order_virtual($order)
    {
        $virtual = false;

        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if ($product && ($product->is_virtual() || $product->is_downloadable())) {
                $virtual = true;
                break;
            }
        }

        return $virtual;
    }

    /**
     * Display transaction data in admin order page
     *
     * @param WC_Order $order
     */
    public function display_transaction_data($order)
    {
        // Check if we've already displayed this data
        if (defined('ASTIMPAY_SHIPPING_ONLY_ADMIN_DATA_DISPLAYED')) {
            return;
        }

        if ($order->get_payment_method() !== $this->id) {
            return;
        }

        $payment_data = $order->get_meta('astimpay_shipping_only_payment_data');
        if (empty($payment_data)) {
            return;
        }

        $this->display_payment_info_html($payment_data);
        // Mark as displayed
        define('ASTIMPAY_SHIPPING_ONLY_ADMIN_DATA_DISPLAYED', true);
    }

    /**
     * Display payment information HTML
     *
     * @param object $data Payment data
     */
    protected function display_payment_info_html($data)
    {
        $payment_method = esc_html(ucfirst($data->payment_method ?? ''));
        $sender_number = esc_html($data->sender_number ?? '');
        $transaction_id = esc_html($data->transaction_id ?? $data->invoice_id);
        $amount = esc_html($data->amount ?? '');

        echo "<div class='form-field form-field-wide astimpay-shipping-only-admin-data'>

            <table class='wp-list-table widefat striped posts'>
                <tbody>
                    <tr>
                        <th>
                            <strong>Payment Method ( {$this->title} - Shipping Only)</strong>
                        </th>
                        <td>
                                {$payment_method}
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <strong>Sender</strong>
                        </th>
                        <td>
                                {$sender_number}
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <strong>Transaction ID</strong>
                        </th>
                        <td>
                                {$transaction_id}
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <strong>Shipping Amount (PAID)</strong>
                        </th>
                        <td>
                                {$amount}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>";
    }
}