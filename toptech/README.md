# TopTech Machinery - Premium WooCommerce Theme (v1.0.0)

A fast, secure, conversion-focused WooCommerce theme for power tools, solar, and hardware
retail in Kenya. Brand colours: **Blue `#005EB8`** + **Dark Navy `#0B1E3F`**.

Built for **WordPress 6.5+**, **WooCommerce 9+**, **PHP 8.1+** (8.3 ready).

---

## What's inside (v1.0.0 foundation)

- **Object-oriented, namespaced** codebase (`ToptechMachinery\`) with an autoloader — no global soup.
- **Header**: top contact bar (phone/WhatsApp/email/hours), logo, intelligent AJAX search
  (products + categories + brands + SKU), account/wishlist/cart actions, **sticky on scroll**.
- **Homepage** (`front-page.php`): hero slider (touch + keyboard + autoplay) with vertical
  category menu, "Shop by Category" cards with live product counts, and **one product row per
  category** (6 products each) with a *View more* link.
- **Uniform product cards**: 1:1 lazy-loaded images, 2-line title clamp, 3-line description
  clamp, sale/stock/featured badges, star ratings, **AJAX Add to Cart** (no reload), quick view
  + wishlist on hover. No Compare button on the homepage (as specified).
- **AJAX**: secure add-to-cart, mini-cart count fragments, and debounced live search — every
  endpoint nonce-verified, input-sanitised, output-escaped.
- **Footer**: company info, customer service + policy menus, accepted payments (M-PESA, cards,
  etc.), security badges, back-to-top, floating WhatsApp button.
- **Performance**: self-hosted font preload, inlined critical CSS, deferred JS, WooCommerce
  asset trimming on non-shop pages, WebP-ready image sizes.
- **Security**: hardening headers, version/disclosure removal, `DISALLOW_FILE_EDIT`, xmlrpc off,
  nonces + capability-safe AJAX.
- **SEO / Merchant Center**: JSON-LD Organization + Store + LocalBusiness on every page, and
  Product schema (SKU, MPN, GTIN, Brand, price, availability, condition) on product pages.
- **Auto-created, fully editable pages**: About Us, Contact Us, Privacy Policy, Terms &
  Conditions, Shipping & Delivery, Return & Refund, Warranty, Payment Methods, Cookie Policy,
  FAQ, Track Order — with complete, Kenya-specific, Merchant-Center-ready content.
- **Customizer**: brand colours + contact details (phone, WhatsApp, email, hours, address).
- **TGMPA plugin installer**: prompts for WooCommerce, Elementor, Perfect Brands, SEO, caching,
  wishlist/compare, Google Listings & Ads, PDF invoices, order tracking, demo import, etc.
- **Accessibility**: skip link, visible focus states, ARIA labels, reduced-motion support.
- **Translation-ready** (`languages/`), **RTL-ready**, **child-theme ready**.

---

## Installation

1. In WordPress: **Appearance → Themes → Add New → Upload Theme** → upload `toptech-machinery.zip` → **Activate**.
2. On activation the theme prompts you to install the required plugins (TGMPA). Install at least
   **WooCommerce** and **Perfect Brands for WooCommerce**, then the recommended ones.
   > Before shipping/using: place the TGMPA library at
   > `inc/tgmpa/class-tgm-plugin-activation.php` (download from https://tgmpluginactivation.com/).
3. On activation, all legal/info **Pages and menus are created automatically**. Edit any of them
   under **Pages** — the content is real, not placeholder.
4. Upload your logo at **Appearance → Customize → Site Identity** (use the supplied `TOPTECH-LOGO.webp`).
5. Set brand colours + contact details under **Customize → TopTech Machinery**.
6. Import your products (see `IMPORT-PRODUCTS.md`).
7. Set **Settings → Reading → Homepage displays → A static page** and pick a page, or leave the
   default — `front-page.php` renders the homepage automatically.

## Child theme

Use `toptech-machinery-child/` for any custom code so updates never overwrite your changes. Zip that
folder separately and install it the same way, then activate the child.

## Requirements

- WordPress 6.5+, WooCommerce 9+, PHP 8.1+ (tested to 8.3), HTTPS enabled (required for
  Merchant Center and secure checkout).

## Support / brand details baked in

- Phone / WhatsApp: **0797 720290**  ·  Email: **info@toptechmachinery.co.ke**
- Address: **This & That Exhibition, Opp. Ronald Ngala Post Office, Shop G15, Ronald Ngala Street, Nairobi, Kenya**

## Roadmap (phases still to build)

See `ROADMAP.md` for the remaining phases (advanced product filters widget, single-product
trust-badge/delivery-estimate block, one-click demo importer package, compare page, PageSpeed
critical-CSS per template, QA matrix, and ThemeForest packaging checklist).
