# WooCommerce Payment Method Discount Plugin

A professional WordPress plugin that provides percentage or fixed discounts based on the selected payment method in WooCommerce stores.

## Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Screenshots](#screenshots)
- [Technical Documentation](#technical-documentation)
- [Hooks and Filters](#hooks-and-filters)
- [Troubleshooting](#troubleshooting)
- [Changelog](#changelog)
- [Support](#support)

## Overview

The WooCommerce Payment Method Discount plugin allows store owners to incentivize customers to use specific payment methods by offering automatic discounts. The plugin supports both percentage-based and fixed-amount discounts, with real-time discount calculation during checkout.

### Key Benefits
- **Increase conversion rates** by encouraging preferred payment methods
- **Reduce transaction fees** by incentivizing lower-cost payment options
- **Improve cash flow** with discounts for direct payment methods
- **Enhance customer experience** with transparent, automatic discounts

## Features

### Core Functionality
- ✅ **Flexible Discount Types**: Support for both percentage and fixed-amount discounts
- ✅ **Real-time Updates**: Instant discount calculation when payment method changes
- ✅ **Multiple Payment Methods**: Configure discounts for any available payment gateway
- ✅ **Custom Labels**: Personalized discount descriptions for better customer understanding
- ✅ **Session Management**: Robust handling of payment method selection across checkout process
- ✅ **AJAX Integration**: Smooth, no-reload discount updates
- ✅ **Admin Interface**: User-friendly settings panel in WooCommerce admin

### Technical Features
- ✅ **WooCommerce Integration**: Native integration with WooCommerce hooks and filters
- ✅ **Security**: Proper nonce verification and data sanitization
- ✅ **Performance Optimized**: Efficient AJAX calls and session handling
- ✅ **Compatibility**: Supports WooCommerce HPOS (High-Performance Order Storage)
- ✅ **Clean Code**: Well-structured, documented PHP code following WordPress standards

## Requirements

- **WordPress**: 5.0 or higher
- **WooCommerce**: 6.0 or higher
- **PHP**: 7.4 or higher (8.0+ recommended)
- **MySQL**: 5.6 or higher

### Tested Compatibility
- WordPress: Up to 6.4
- WooCommerce: Up to 8.5
- PHP: 7.4 - 8.2

## Installation

### Method 1: Manual Installation

1. Download the plugin files
2. Upload the `wc_payment_discount_plugin` folder to `/wp-content/plugins/`
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Navigate to **WooCommerce > Payment Discounts** to configure

### Method 2: WordPress Admin Upload

1. Go to **Plugins > Add New** in WordPress admin
2. Click **Upload Plugin**
3. Choose the plugin ZIP file and click **Install Now**
4. Activate the plugin
5. Configure via **WooCommerce > Payment Discounts**

## Configuration

### Initial Setup

1. **Enable the Plugin**
   - Navigate to **WooCommerce > Payment Discounts**
   - Check "Enable discounts based on payment method"
   - Click **Save Changes**

*[IMAGE NEEDED: Admin settings page with enable checkbox highlighted]*

2. **Configure Payment Method Discounts**
   - For each payment method, set:
     - **Discount Type**: Percentage (%) or Fixed Amount
     - **Discount Value**: The discount amount
     - **Display Label**: Custom text shown to customers

*[IMAGE NEEDED: Discount rules table showing different payment methods with configured discounts]*

### Discount Configuration Examples

#### Example 1: Cash Payment Incentive
- **Payment Method**: Direct Bank Transfer
- **Discount Type**: Percentage
- **Discount Value**: 5
- **Display Label**: "Bank Transfer Discount (5% off)"

#### Example 2: Check Payment Discount
- **Payment Method**: Check Payments
- **Discount Type**: Fixed Amount
- **Discount Value**: 10.00
- **Display Label**: "Check Payment Discount"

*[IMAGE NEEDED: Example configuration showing both percentage and fixed discounts]*

## Usage

### Customer Experience

1. **Product Selection**: Customer adds products to cart
2. **Checkout Process**: Customer proceeds to checkout
3. **Payment Method Selection**: Available payment methods are displayed
4. **Automatic Discount**: Discount is applied instantly when payment method is selected
5. **Order Review**: Final order total reflects the discount

*[IMAGE NEEDED: Checkout page showing payment methods with discount applied]*

### Admin Monitoring

Administrators can monitor discount usage through:
- WooCommerce order details
- Payment method reports
- Sales analytics

*[IMAGE NEEDED: Order admin page showing applied discount]*

## Screenshots

### Required Images for Documentation

1. **Plugin Settings Page**
   - Location: `WooCommerce > Payment Discounts`
   - Shows: Enable/disable toggle and discount rules table
   - Highlight: Main settings interface

2. **Discount Configuration Table**
   - Location: Admin settings page
   - Shows: Payment methods with discount type, value, and label fields
   - Highlight: Example configurations for different payment methods

3. **Checkout Page - Before Discount**
   - Location: Frontend checkout page
   - Shows: Order total before payment method selection
   - Highlight: Initial order summary

4. **Checkout Page - After Discount**
   - Location: Frontend checkout page
   - Shows: Updated order total with discount applied
   - Highlight: Discount line item and new total

5. **Order Confirmation Page**
   - Location: Thank you page
   - Shows: Final order details with discount
   - Highlight: Discount line in order summary

6. **Admin Order View**
   - Location: WooCommerce admin order details
   - Shows: Order with applied payment method discount
   - Highlight: Discount as fee line item

## Technical Documentation

### Plugin Architecture

```
WC_Payment_Method_Discount
├── __construct()           # Initialize plugin
├── init()                 # Hook into WordPress/WooCommerce
├── admin_menu()           # Add admin menu
├── admin_page()           # Render admin interface
├── enqueue_scripts()      # Load frontend JavaScript
├── ajax_update_payment_discount()  # Handle AJAX requests
├── add_payment_method_discount()   # Apply discount to cart
└── remove_payment_method_fees()    # Clean up existing fees
```

### Session Management

The plugin uses WooCommerce sessions to track payment method selection:
- `selected_payment_method`: Current payment method
- `chosen_payment_method`: Fallback payment method

### AJAX Implementation

Real-time discount updates are handled via AJAX:
- **Trigger**: Payment method radio button change
- **Action**: `update_payment_discount`
- **Response**: Triggers checkout update if discount applicable

### Database Storage

Settings are stored in WordPress options:
- `wc_payment_discount_enabled`: Plugin enable/disable status
- `wc_payment_discount_rules`: Array of discount rules per payment method

## Hooks and Filters

### Action Hooks Used

```php
// Admin hooks
add_action('admin_menu', array($this, 'admin_menu'));
add_action('admin_init', array($this, 'admin_init'));

// Frontend hooks
add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
add_action('woocommerce_cart_calculate_fees', array($this, 'add_payment_method_discount'));

// AJAX hooks
add_action('wp_ajax_update_payment_discount', array($this, 'ajax_update_payment_discount'));
add_action('wp_ajax_nopriv_update_payment_discount', array($this, 'ajax_update_payment_discount'));

// Checkout hooks
add_action('woocommerce_checkout_update_order_review', array($this, 'handle_checkout_update'));
add_action('woocommerce_checkout_process', array($this, 'save_payment_method_to_session'));
```

### Available Filters (for developers)

Currently, the plugin doesn't expose custom filters, but developers can extend functionality by hooking into standard WooCommerce filters.

## Troubleshooting

### Common Issues

#### Discount Not Applying
**Symptoms**: Discount doesn't appear when payment method is selected
**Solutions**:
1. Verify plugin is enabled in settings
2. Check discount rules are properly configured
3. Ensure JavaScript is not blocked by other plugins
4. Clear browser cache and cookies

#### AJAX Errors
**Symptoms**: Console errors during payment method selection
**Solutions**:
1. Check WordPress AJAX functionality
2. Verify nonce generation
3. Disable conflicting plugins temporarily
4. Check server error logs

#### Discount Amount Issues
**Symptoms**: Incorrect discount calculation
**Solutions**:
1. Verify discount type (percentage vs. fixed)
2. Check for cart total edge cases
3. Review tax calculation conflicts
4. Ensure proper number formatting

### Debug Mode

To enable debug mode, add this to `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Check `/wp-content/debug.log` for plugin-related errors.

## Changelog

### Version 1.0.0
- Initial release
- Basic discount functionality for payment methods
- Admin interface for configuration
- AJAX-powered real-time updates
- Session management for checkout process
- WooCommerce HPOS compatibility

## Support

### Documentation
- Plugin documentation: Available in this README
- WooCommerce documentation: [WooCommerce Docs](https://docs.woocommerce.com/)

### Development
- **Author**: Expert Web Developer
- **Version**: 1.0.0
- **License**: GPL v2 or later

### Reporting Issues

When reporting issues, please include:
1. WordPress version
2. WooCommerce version
3. PHP version
4. Active theme and plugins
5. Steps to reproduce the issue
6. Expected vs. actual behavior

---

## License

This plugin is licensed under the GPL v2 or later.

```
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```