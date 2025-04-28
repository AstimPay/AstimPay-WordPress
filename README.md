# AstimPay WooCommerce Payment Gateway Installation & Settings Guide
![AstimPay Logo](https://astimpay.com/assets/images/logo.png)
---

## Overview

The AstimPay WooCommerce Payment Gateway allows users to securely make payments using AstimPay. This plugin also provides an option to convert the site currency to Bangladeshi Taka (BDT) at the admin-defined exchange rate.

---

### Table of Contents

1. [Requirements](#requirements)
2. [Installation](#installation)
3. [Configuration](#configuration)
4. [Setting Exchange Rate](#setting-exchange-rate)
5. [Testing Payment Flow](#testing-payment-flow)
6. [Cancel Payment Configuration](#cancel-payment-configuration)
7. [Additional Notes](#additional-notes)

---

## Requirements

- WordPress 5.0 or higher
- WooCommerce 4.0 or higher
- PHP 7.2 or higher
- AstimPay Payment Panel for API keys

---

## Installation

1. **Download the Plugin:**
   - Clone or download the AstimPay plugin repository to your local machine.[Download Now](https://github.com/AstimPay/Astimpay-Wordpress/releases/download/v1.0.0/AstimPay-v1.0.0.zip).

2. **Upload the Plugin:**
   - Log into your WordPress dashboard.
   - Go to **Plugins** > **Add New** > **Upload Plugin**.
   - Select the plugin `.zip` file and click **Install Now**.

3. **Activate the Plugin:**
   - After the installation completes, click **Activate** to enable the plugin.

---

## Configuration

1. **Navigate to Payment Settings:**
   - In your WordPress dashboard, go to **WooCommerce** > **Settings** > **Payments**.

2. **Enable AstimPay Gateway:**
   - Locate the `AstimPay` payment option and toggle the switch to **Enable**.

3. **Configure API Settings:**
   - Click on the **Manage** button next to AstimPay.
   - Fill in the required fields:
     - **Title**: The title displayed during checkout (e.g., `AstimPay`).
     - **Description**: A short description displayed to users during checkout.
     - **API Key**: Enter your live API key from the AstimPay dashboard.
     - **API Base URL**: Enter the base API URL (e.g., `https://pay.yourdomain.tld`).

4. **Save Changes:**
   - Click the **Save Changes** button to apply your settings.

---

## Setting Exchange Rate

1. **Set Currency to BDT Exchange Rate:**
   - In the AstimPay payment settings, you will find the field **Site Currency to BDT Exchange Rate**.
   - Enter the conversion rate between your site's currency and BDT (e.g., `84.50` if 1 USD = 84.50 BDT).

2. **Save Changes:**
   - Click **Save Changes** to apply the exchange rate.

---

## Testing Payment Flow

1. **Add a Product to Cart:**
   - Go to your website’s shop page, add any product to the cart, and proceed to checkout.

2. **Select AstimPay Payment:**
   - On the checkout page, select the `AstimPay` payment option.

3. **Complete the Payment:**
   - Once the payment is initiated, the plugin will redirect you to AstimPay's payment page where you can complete the transaction.
   - After successful payment, you will be redirected back to the order confirmation page.

---

## Cancel Payment Configuration

AstimPay provides a custom cancellation URL for users who wish to cancel their payment.

1. **Redirect to Checkout with Notice:**
   - If a user cancels the payment, they will be redirected back to the cart or checkout page with a notice: 
     **"Your payment has been canceled."**

---

## Additional Notes

- **REST API**: The plugin uses a custom REST API route for IPN (Instant Payment Notification) handling. This allows AstimPay to communicate the payment status back to your WooCommerce store.
  - The IPN URL is: `/wp-json/astimpay/v1/ipn`
  
- **Logs**: AstimPay request and response data are logged for debugging purposes. Check the log files for any errors or debugging information.

---

That’s it! You have successfully installed and configured the AstimPay payment gateway on your WooCommerce store.

For more information, visit the official [AstimPay Documentation](#).
