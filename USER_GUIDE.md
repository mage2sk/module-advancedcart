# Panth Advanced Cart Page - User Guide

## Overview

Panth Advanced Cart Page enhances your Magento 2 shopping cart with multiple conversion-boosting features. All features can be individually enabled or disabled from the admin panel.

## Configuration

Navigate to **Stores > Configuration > Panth Extensions > Advanced Cart Page**.

### General Settings

- **Enable Module** — master toggle to enable/disable all features

### Free Shipping Progress Bar

- **Enable** — show/hide the progress bar
- **Threshold** — minimum order amount for free shipping (e.g., 50.00)
- **Progress Message** — message shown while below threshold. Use `{{remaining}}` placeholder for the remaining amount
- **Achieved Message** — message shown when free shipping is earned

### Quantity Buttons

- **Enable** — adds +/- buttons around quantity inputs on cart items

### Trust Badges

- **Enable** — show/hide trust badges section
- **Badges** — comma-separated list of badges to display. Available: `secure_checkout`, `money_back`, `free_returns`, `fast_shipping`, `support_24_7`, `quality_guarantee`

### Continue Shopping Button

- **Enable** — show/hide the continue shopping link
- **Label** — button text (default: "Continue Shopping")
- **URL** — relative URL path (default: `/`)

### Cart Savings Display

- **Enable** — show/hide total savings from discounts and special prices

### Estimated Delivery Date

- **Enable** — show/hide estimated delivery range
- **Min Days** — minimum business days for delivery (default: 3)
- **Max Days** — maximum business days for delivery (default: 7)
- **Label** — label text (default: "Estimated Delivery")

### Order Notes

- **Enable** — allow customers to add special instructions
- **Placeholder** — textarea placeholder text
- **Max Length** — character limit (default: 500)

Order notes appear on:
- Cart page (collapsible section)
- Checkout page (sidebar)
- Customer order view
- Admin order view
- Admin order grid (hidden column, can be enabled)

### Empty Cart

- **Enable** — show enhanced empty cart page
- **Heading** — custom heading text
- **Message** — custom message text
- **Button Label** — return-to-shop button text

## Theme Compatibility

The module automatically detects whether Hyva or Luma theme is active and renders the appropriate templates:
- **Hyva** — uses Alpine.js and Tailwind CSS
- **Luma** — uses vanilla JavaScript with inline styles

## Troubleshooting

1. **Features not showing** — ensure the module is enabled under General Settings and the individual feature is enabled
2. **Free shipping bar not accurate** — verify the threshold matches your actual free shipping rule configuration
3. **Order notes not saving** — check that the `panth_order_note` column exists in the `quote` and `sales_order` tables
4. **Cache** — flush Magento cache after configuration changes
