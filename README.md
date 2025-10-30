# WooCommerce Orange Money Madagascar

Orange Money Madagascar payment gateway plugin for WooCommerce using the WebPay API.

## Description

This plugin integrates Orange Money Madagascar's WebPay API into your WooCommerce store, allowing customers to pay for their orders using Orange Money mobile money service.

## Features

- Easy integration with Orange Money WebPay API
- Support for both sandbox (testing) and live environments
- Automatic payment confirmation via webhooks
- Secure payment processing
- Compatible with WooCommerce 5.0+
- WordPress 5.8+

## Requirements

- WordPress 5.8 or higher
- WooCommerce 5.0 or higher
- PHP 7.4 or higher
- SSL certificate (required for production)
- Orange Money merchant account

## Installation

1. Download or clone this repository to your WordPress plugins directory:
   ```bash
   cd wp-content/plugins/
   git clone https://github.com/mikaoelitiana/aiza-woocommerce-orange-money.git woocommerce-orange-money
   ```

2. Activate the plugin through the 'Plugins' menu in WordPress

3. Go to WooCommerce > Settings > Payments

4. Enable "Orange Money Madagascar" and click "Manage"

5. Configure your Orange Money API credentials:
   - **Merchant Key**: Your Orange Money merchant key
   - **Consumer Key**: Your OAuth consumer key (Base64 encoded)
   - **Client ID**: Your OAuth client ID
   - **Client Secret**: Your OAuth client secret

## Configuration

### Getting API Credentials

1. Register for an Orange Money merchant account at [Orange Developer Portal](https://developer.orange.com/)
2. Create a new application and subscribe to the "Orange Money WebPay" API
3. Obtain your credentials:
   - Merchant Key
   - Consumer Key (for OAuth authentication)
   - Client ID
   - Client Secret

### Plugin Settings

Navigate to **WooCommerce > Settings > Payments > Orange Money Madagascar**

#### Basic Settings

- **Enable/Disable**: Enable or disable the payment method
- **Title**: Payment method title displayed to customers (default: "Orange Money")
- **Description**: Payment method description shown on checkout page

#### API Settings

- **Test Mode**: Enable to use the sandbox environment for testing
- **Merchant Key**: Enter your Orange Money merchant key
- **Consumer Key**: Enter your OAuth consumer key (Base64 encoded credentials)
- **Client ID**: Enter your OAuth client ID
- **Client Secret**: Enter your OAuth client secret

## Testing

### Sandbox Mode

When Test Mode is enabled:
- The plugin uses the sandbox API endpoint: `/orange-money-webpay/dev/v1/webpayment`
- Currency is set to `OUV` (test currency)
- No real money is charged

For more information about testing, visit: [Orange Money WebPay Developer Guide](https://developer.orange.com/apis/om-webpay-dev/getting-started)

### Test Cards

Refer to the Orange Money developer documentation for test payment credentials.

## How It Works

1. Customer selects Orange Money as payment method at checkout
2. Customer is redirected to Orange Money payment page
3. Customer completes payment on Orange Money platform
4. Orange Money sends a webhook notification to your site
5. Plugin confirms the payment and updates the order status
6. Customer is redirected back to your store

## Webhook Configuration

The plugin automatically handles webhook notifications at:
```
https://yoursite.com/?wc-api=wc_gateway_orange_money
```

Make sure this URL is accessible and not blocked by security plugins or server configurations.

## Currency Support

- **Live Mode**: Uses your WooCommerce store currency
- **Test Mode**: Uses `OUV` (Orange test currency)

Supported currencies for live mode:
- MGA (Malagasy Ariary)
- XOF (West African CFA Franc)
- XAF (Central African CFA Franc)
- Other currencies supported by Orange Money

## Troubleshooting

### Payment not completing

1. Check that webhooks are not blocked by security plugins
2. Verify your API credentials are correct
3. Ensure SSL is enabled (required for production)
4. Check WooCommerce > Status > Logs for error messages

### Access Token Issues

The plugin caches access tokens for 1 hour. If you experience authentication issues:
1. Check your Consumer Key is correctly formatted (Base64 encoded)
2. Verify your Client ID and Client Secret are correct
3. Clear WordPress transients

### Order Status Not Updating

1. Verify webhook URL is accessible: `https://yoursite.com/?wc-api=wc_gateway_orange_money`
2. Check database table `wp_wc_orange_money_transactions` exists
3. Review WooCommerce logs for webhook errors

## Database

The plugin creates a custom table `wp_wc_orange_money_transactions` to track payments:

- `id`: Transaction record ID
- `order_id`: WooCommerce order ID
- `notif_token`: Unique notification token
- `payment_url`: Orange Money payment URL
- `txnid`: Orange Money transaction ID
- `status`: Payment status (pending, success, failed)
- `created_at`: Record creation timestamp
- `updated_at`: Record update timestamp

## Security

- All API credentials are stored securely in WordPress options
- OAuth tokens are cached and automatically refreshed
- Payment notifications are validated using unique tokens
- No sensitive data is logged

## Support

For issues, questions, or contributions:
- GitHub Issues: https://github.com/mikaoelitiana/aiza-woocommerce-orange-money/issues
- Orange Money API Documentation: https://developer.orange.com/apis/om-webpay-dev

## Credits

Based on the Pretix Orange Money Madagascar plugin: https://github.com/mikaoelitiana/pretix-orange-money-mdg

## License

Copyright 2024 Mika Andrianarijaona

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.

## Changelog

### 1.0.0
- Initial release
- Orange Money WebPay API integration
- Support for sandbox and live environments
- Webhook handling for payment notifications
- WooCommerce 5.0+ compatibility
