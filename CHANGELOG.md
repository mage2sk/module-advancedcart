# Changelog

All notable changes to this extension are documented here. The format
is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [1.0.16] - 2026-10-04

### Fixed
- Luma cart at 768-1023px: the cart table no longer runs under the Summary sidebar. The quantity stepper wraps (input above the -/+ buttons) and the product thumbnail is limited to 80px at this width.
- Luma cart: the quantity input matches the -/+ buttons (36px on desktop, 44px below 1024px), is vertically centred and uses 16px text on phones.
- Luma cart: the order notes block stays in the cart column next to the Summary instead of spreading under it, keeps its heading visible and has side gutters on phones.
- Luma cart on phones: the savings and estimated delivery boxes in the Summary have the same side gutter as the rest of the summary.
- Estimated delivery dates no longer break in the middle of the date range.
- The free shipping progress bar has an accessible name.
- Order notes on the cart page have an accessible label, announce Saving/Saved/errors to screen readers, use readable text colours and 16px text on phones, and show a message when a note cannot be saved.
- The checkout order note field is added whenever the checkout summary is rendered, including after switching between the shipping and payment steps, and its saving hint is easier to read.
- A Continue Shopping URL entered without a leading slash now opens the store page instead of a page under /checkout/cart/. Full URLs are kept as entered.
- Decorative icons on the empty cart page are hidden from screen readers, and multi-line order notes keep their line breaks on the storefront and admin order views.
