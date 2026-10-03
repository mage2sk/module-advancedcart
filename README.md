# Magento 2 Advanced Cart Page

Panth Advanced Cart Page adds a set of blocks to the Magento 2 shopping cart page: a free shipping progress bar, trust badges, a continue shopping link, a cart savings line, an estimated delivery range, an order notes textarea and a replacement empty cart page. Order notes are stored on the quote, copied to the order when it is placed, and shown on the customer order view, the admin order view and as a hidden column in the admin order grid.

The module ships two sets of cart templates. The block class selects the Hyva templates (Alpine.js and Tailwind classes) or the Luma templates (inline CSS and vanilla JavaScript) at render time using the theme detection provided by `Panth_Core`. Every feature has its own enable switch and its settings can be changed per store view.

Product page: [kishansavaliya.com/magento-2-advancedcart.html](https://kishansavaliya.com/magento-2-advancedcart.html)

## Features

- Free shipping progress bar above the cart items. It compares the quote base subtotal with the configured threshold (a base currency amount), fills a bar to the matching percentage and prints a progress or achieved message. The threshold and the remaining amount are converted to the store view currency before they are printed. The progress message supports a `{{remaining}}` placeholder for the formatted remaining amount.
- Continue shopping link above the cart items with a configurable label and a URL resolved against the store base URL.
- Order notes textarea below the cart items. Input is saved to the quote with a debounced AJAX request (800 ms after the last keystroke). HTML tags are stripped and the text is cut to the configured maximum length on the server.
- Order note field injected into the Luma checkout summary sidebar, pre-filled from the quote and saved with the same AJAX endpoint.
- Order note copied from the quote to the order by an observer on `sales_model_service_quote_submit_before` while the order is placed (storefront, REST and GraphQL checkout).
- Order note displayed on the storefront order view page and in a "Customer Order Note" section on the admin order view.
- "Order Note" column added to the admin sales order grid (hidden by default, text filter).
- Cart savings line in the cart summary. The amount is the larger of the applied base discount amount and the difference between product regular prices and the quote item base prices multiplied by quantity, converted to the store view currency. The line is hidden when the amount is zero.
- Estimated delivery range in the cart summary, computed as a minimum and maximum number of business days from the current store date. Saturdays and Sundays are skipped; public holidays are not.
- Trust badges under the cart summary. Six badge keys are available: `secure_checkout`, `money_back`, `free_returns`, `fast_shipping`, `support_24_7`, `quality_guarantee`. Icons are inline SVG in the template.
- Replacement template for the empty cart block with a configurable heading, message and button label.
- Quantity +/- buttons around each cart item quantity input (`input[data-role="cart-item-qty"]`) on Hyva and Luma. When the theme already renders buttons next to the input, those are kept and no second pair is added. When the setting is off, buttons placed directly next to the input are hidden.

## Compatibility

| Platform | Versions |
|---|---|
| Magento Open Source | 2.4.4 to 2.4.8 (as published on the product page) |
| Adobe Commerce | 2.4.4 to 2.4.8 (as published on the product page) |
| PHP | >= 8.1 (from `composer.json`) |
| Themes | Hyva and Luma (separate template sets, selected at runtime) |

Composer constraints on Magento packages: `magento/framework` >= 103.0, `magento/module-checkout` >= 100.4, `magento/module-quote` >= 101.2, `magento/module-sales` >= 103.0, `magento/module-store` >= 101.1, `magento/module-backend` >= 102.0, `magento/module-config` >= 101.2, `magento/module-ui` >= 101.2, `magento/module-catalog` >= 104.0.

## Requirements

- Magento Open Source or Adobe Commerce 2.4.4 to 2.4.8
- PHP 8.1 or newer
- `mage2kishan/module-core` ^1.0 (module `Panth_Core`); the module uses `Panth\Core\Helper\Theme` for theme detection and declares `Panth_Core` in its load sequence
- `magento/framework`, `magento/module-checkout`, `magento/module-quote`, `magento/module-sales`, `magento/module-store`, `magento/module-backend`, `magento/module-config`, `magento/module-ui` and `magento/module-catalog` in the versions listed above

No other packages are required or suggested by `composer.json`.

## Installation

```bash
composer require mage2kishan/module-advancedcart
bin/magento module:enable Panth_Core Panth_AdvancedCart
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

`setup:di:compile` is only needed when the store runs in production mode. The module ships no files under `view/*/web`, so `setup:static-content:deploy` is not required for this module.

`setup:upgrade` applies the declarative schema, which adds the `panth_order_note` column to the `quote`, `sales_order` and `sales_order_grid` tables.

Check the result with:

```bash
bin/magento module:status Panth_AdvancedCart
```

## Configuration

Admin path: Stores > Configuration > Panth Extensions > Advanced Cart Page. The section is available at default, website and store view scope. All settings live under the config path prefix `panth_advancedcart/`.

### General Settings

| Setting | Default | What it does |
|---|---|---|
| Enable Module | Yes | Master switch. When set to No, every feature below is disabled regardless of its own switch. |

Config path: `panth_advancedcart/general/enabled`

### Free Shipping Progress Bar

| Setting | Default | What it does |
|---|---|---|
| Enable Free Shipping Bar | Yes | Shows the progress bar above the cart items. |
| Free Shipping Threshold | 50 | Subtotal, in the base currency, at which the bar reports free shipping. Store views that display another currency show it converted with the configured currency rate. Validated as a number of zero or greater. A value of 0 is used as is; 50 is used only when the field is empty. |
| Progress Message | You're only {{remaining}} away from free shipping! | Message shown while the subtotal is below the threshold. `{{remaining}}` is replaced with the formatted remaining amount. |
| Achieved Message | Congratulations! You've earned FREE shipping! | Message shown once the subtotal reaches the threshold. |

Config paths: `panth_advancedcart/free_shipping_bar/enabled`, `.../threshold`, `.../message_progress`, `.../message_achieved`. The threshold and message fields are only shown when the bar is enabled. The bar does not read the shipping method or cart price rule configuration; set the threshold to match your actual free shipping rule.

### Quantity +/- Buttons

| Setting | Default | What it does |
|---|---|---|
| Enable Qty Increment/Decrement Buttons | Yes | Yes adds a - and a + button around each cart item quantity input, unless the theme already shows buttons there. No hides buttons placed directly next to the quantity input, including buttons rendered by the theme. The buttons change the value within the input's min, max and step; the cart is saved with the normal "Update Shopping Cart" action. |

Config path: `panth_advancedcart/qty_buttons/enabled`

### Trust Badges

| Setting | Default | What it does |
|---|---|---|
| Enable Trust Badges | Yes | Shows the badge grid under the cart summary. |
| Badges to Display | secure_checkout,money_back,free_returns | Comma-separated badge keys. Unknown keys are ignored. Available keys: `secure_checkout`, `money_back`, `free_returns`, `fast_shipping`, `support_24_7`, `quality_guarantee`. |

Config paths: `panth_advancedcart/trust_badges/enabled`, `.../badges`

### Continue Shopping Button

| Setting | Default | What it does |
|---|---|---|
| Enable Continue Shopping Button | Yes | Shows the link above the cart items. |
| Button Label | Continue Shopping | Link text. |
| Button URL | / | Path relative to the store base URL, for example `/` or `sale.html`, or a full `https://` URL. Paths are resolved against the store base URL, so they work with or without a leading slash. |

Config paths: `panth_advancedcart/continue_shopping/enabled`, `.../label`, `.../url`

### Cart Savings Display

| Setting | Default | What it does |
|---|---|---|
| Show Savings Summary | Yes | Shows the "You save ..." line in the cart summary when the calculated savings are above zero. |

Config path: `panth_advancedcart/cart_savings/enabled`

### Estimated Delivery Date

| Setting | Default | What it does |
|---|---|---|
| Show Estimated Delivery | Yes | Shows the delivery range in the cart summary. |
| Minimum Days | 3 | Business days added to today for the start of the range. A value of 0 is used as is. |
| Maximum Days | 7 | Business days added to today for the end of the range. A value of 0 is used as is. |
| Label Text | Estimated Delivery | Label printed before the date range. |

Config paths: `panth_advancedcart/estimated_delivery/enabled`, `.../min_days`, `.../max_days`, `.../label`. Dates are printed in the `M j` format (for example "Oct 2 - Oct 8").

### Order Notes

| Setting | Default | What it does |
|---|---|---|
| Enable Order Notes | Yes | Shows the textarea on the cart page and the checkout sidebar field, and enables the save endpoint. |
| Placeholder Text | Add special instructions for your order... | Placeholder of the textarea. |
| Maximum Characters | 500 | `maxlength` of the textarea and the server-side cut-off. |

Config paths: `panth_advancedcart/order_notes/enabled`, `.../placeholder`, `.../max_length`

### Enhanced Empty Cart

| Setting | Default | What it does |
|---|---|---|
| Enable Enhanced Empty Cart | Yes | Replaces the stock empty cart text with the heading, message and button below. When disabled, the module template prints the standard "You have no items in your shopping cart." text. |
| Heading Text | Your cart is empty | Heading of the empty cart page. |
| Message Text | Looks like you haven't added anything to your cart yet. Browse our collection and find something you love! | Paragraph under the heading. |
| Button Label | Start Shopping | Label of the button, which links to Magento's continue shopping URL. |

Config paths: `panth_advancedcart/empty_cart/enabled`, `.../heading`, `.../message`, `.../button_label`

With the default configuration every feature is on as soon as the module is enabled.

## Usage

### Cart page

The layout update for `checkout_cart_index` places the blocks as follows:

- `checkout.cart.form.before`: free shipping bar, then the continue shopping link
- `checkout.cart.widget`: order notes textarea (collapsible `details` element, open by default, with a character counter)
- `cart.summary.before`: cart savings line, then estimated delivery range (Hyva)
- `cart.summary`: cart savings line, then estimated delivery range, after the totals (Luma and other themes without `cart.summary.before`; removed on Hyva by `hyva_checkout_cart_index.xml`)
- `cart.summary`: trust badges (last child)
- `before.body.end`: quantity +/- buttons script
- `checkout.cart.empty`: template replaced with the module's empty cart template

The same layout update sets the page title classes `text-3xl font-bold mb-6 text-center` on `page.main.title`.

Each block renders nothing when its feature is disabled. The order notes textarea posts to advancedcart/cart/savenote with the form key; the response is JSON with a `success` flag. The endpoint rejects requests without a valid form key and only updates an existing active quote of the current session; it does not create a new quote.

### Checkout page (Luma)

On `checkout_index_index` a script is added at `before.body.end` (only when the module is enabled). It uses RequireJS and jQuery to insert a labelled textarea into the order summary sidebar (`.opc-block-summary`), trying in order: before `.checkout-methods-items`, before the place order action, after the last `.payment-option` block, or after the totals table. It retries every 600 ms for up to 60 attempts. Changes are saved with the same AJAX endpoint as the cart page. On a body with the class `panth-checkout-extended` the inline styles are skipped so the theme can style the field.

### Order placement

When a quote is converted to an order, the observer `Observer\CopyOrderNoteToOrder` on `sales_model_service_quote_submit_before` copies a non-empty `panth_order_note` from the quote onto the order before the order is saved. No second order save is made.

### Order views

- Storefront order view (`sales/order/view`): an "Order Note" box is printed after the order details when a note exists and the order notes feature is enabled. The block is marked non-cacheable.
- Admin order view: a "Customer Order Note" section is added to the `order_additional_info` container when a note exists.
- Admin Sales > Orders grid: an "Order Note" column can be enabled from the Columns control. The value is copied into `sales_order_grid` when the order grid row is written, so orders placed before this version show an empty value until their grid row is refreshed.

### Templates

Templates can be overridden in a theme under `Panth_AdvancedCart/templates/`:

- `cart/free-shipping-bar.phtml`, `cart/continue-shopping.phtml`, `cart/order-notes.phtml`, `cart/cart-savings.phtml`, `cart/estimated-delivery.phtml`, `cart/trust-badges.phtml`, `cart/qty-buttons.phtml`, `cart/empty-cart.phtml` (Hyva)
- `cart/luma/*.phtml` with the same file names (Luma; selected by `Block\Cart\CartBlock` when `Panth\Core\Helper\Theme::isHyva()` returns false)
- `checkout/order_note.phtml` (Luma checkout sidebar script)
- `order/note.phtml` (storefront order view)
- Admin: `order/note.phtml` (admin order view)

The empty cart block is Magento's own `checkout.cart.empty` block, so the layout always sets `cart/empty-cart.phtml`; on non-Hyva themes that template renders `cart/luma/empty-cart.phtml` instead.

There are no cron jobs, console commands, web API routes or widgets in this module. The only observer is the order note copy described under Order placement.

## Developer Notes

- Module name: `Panth_AdvancedCart`
- Composer package: `mage2kishan/module-advancedcart` (version 1.0.11)
- PHP namespace: `Panth\AdvancedCart`
- Load sequence: `Panth_Core`, `Magento_Checkout`, `Magento_Quote`, `Magento_Sales`, `Magento_Catalog`
- Frontend route: `advancedcart` (controller `Controller\Cart\SaveNote`, POST only, returns JSON)
- Key classes:
  - `Helper\Data`: typed getters for every setting, `isEnabled()` and `isFeatureEnabled(string $group)`
  - `ViewModel\CartEnhancements`: subtotal, free shipping percentage and message, cart savings, trust badge data, estimated delivery range, order note values, `isHyva()`
  - `Block\Cart\CartBlock`: rewrites the template path from `cart/` to `cart/luma/` on non-Hyva themes
  - `Block\Checkout\OrderNote`, `Block\Order\OrderNote`, `Block\Adminhtml\Order\OrderNote`: order note blocks for checkout, storefront order view and admin order view
- `etc/events.xml` registers `Observer\CopyOrderNoteToOrder` on `sales_model_service_quote_submit_before` (all areas).
- `etc/theme-config.json` declares colour tokens (primary colour, free shipping bar, savings, estimated delivery, trust badges) for use by a Panth theme build.
- Database changes (`etc/db_schema.xml`): nullable text column `panth_order_note` on `quote`, `sales_order` and `sales_order_grid`. No new tables are created.
- ACL: no custom resources. The configuration section uses `Magento_Config::config`.

## Uninstallation

```bash
bin/magento module:disable Panth_AdvancedCart
composer remove mage2kishan/module-advancedcart
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

Disabling the module leaves the `panth_order_note` columns on `quote`, `sales_order` and `sales_order_grid`, and the `panth_advancedcart/*` values in `core_config_data`, in place. Remove them manually if they are no longer wanted. Keep `Panth_Core` if other Panth modules use it.

## Support

- Product page: [kishansavaliya.com/magento-2-advancedcart.html](https://kishansavaliya.com/magento-2-advancedcart.html)
- Contact form: [kishansavaliya.com/contact](https://kishansavaliya.com/contact)
- Email: kishansavaliyakb@gmail.com
- Issues: [github.com/mage2sk/module-advancedcart/issues](https://github.com/mage2sk/module-advancedcart/issues)

## Documentation

[USER_GUIDE.md](USER_GUIDE.md) describes each configuration group, where order notes appear, theme handling and a short troubleshooting list.

## License

Commercial software license. See [LICENSE.txt](LICENSE.txt) in this repository.

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## Links

- Website: [kishansavaliya.com](https://kishansavaliya.com)
- All extensions: [kishansavaliya.com/magento-extensions.html](https://kishansavaliya.com/magento-extensions.html)
- GitHub: [github.com/mage2sk/module-advancedcart](https://github.com/mage2sk/module-advancedcart)
- Packagist: [packagist.org/packages/mage2kishan/module-advancedcart](https://packagist.org/packages/mage2kishan/module-advancedcart)
