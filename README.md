# TopTech Machinery

Source code for **[toptechmachinery.co.ke](https://toptechmachinery.co.ke/)** — a Nairobi-based
e-commerce store selling power tools, machinery, solar equipment, and hardware, built on WordPress
and WooCommerce.

This repository holds the two custom pieces of that site: the storefront theme and a WebP image
optimizer plugin. WordPress core, WooCommerce, and third-party plugins are installed normally and
are not tracked here.

| | |
|---|---|
| **Live site** | https://toptechmachinery.co.ke/ |
| **Stack** | WordPress 6.5+, WooCommerce 9+, PHP 8.1+ (tested to 8.3) |
| **Theme version** | 1.20.x |
| **Catalogue** | ~546 products across 37 categories and 81 brands |
| **Brand colours** | Blue `#005EB8` · Dark Navy `#0B1E3F` |
| **License** | GPL v2 or later |

---

## Repository layout

```
toptech/                The TopTech Machinery WooCommerce theme
  assets/               CSS, JS, fonts, images
  inc/                  Namespaced PHP classes (ToptechMachinery\)
  template-parts/       Reusable template partials
  woocommerce/          WooCommerce template overrides
  demo/                 One-click demo import content
  languages/            Translation files
  README.md             Theme documentation and install guide
  SETUP-FLOW.md         Five-step first-run setup walkthrough
  IMPORT-PRODUCTS.md    Product CSV import and column mapping
  ROADMAP.md            Remaining build phases
  PERFORMANCE-htaccess-rules.txt   Optional caching/compression rules

toptech-webp/           TopTech WebP Optimizer plugin
```

## The theme (`toptech/`)

A conversion-focused WooCommerce theme written specifically for machinery and hardware retail in
Kenya.

Highlights:

- **Object-oriented and namespaced** (`ToptechMachinery\`) with an autoloader — no global functions soup.
- **Header** with contact bar (phone, WhatsApp, email, hours), sticky-on-scroll behaviour, and
  debounced AJAX search across products, categories, brands, and SKUs.
- **Homepage** (`front-page.php`): hero slider with touch, keyboard, and autoplay support, a
  vertical category menu, "Shop by Category" cards with live product counts, and one product row
  per category with a "View more" link.
- **Uniform product cards**: 1:1 lazy-loaded images, clamped titles and descriptions,
  sale/stock/featured badges, star ratings, AJAX add-to-cart, and wishlist/quick-view affordances
  on hover (quick view currently links through to the product page — the AJAX modal is a roadmap
  item).
- **AJAX endpoints** for add-to-cart, mini-cart fragments, and live search — every one
  nonce-verified, input-sanitised, and output-escaped.
- **Footer** with company info, customer service and policy menus, accepted payment methods
  (M-PESA, cards), back-to-top, and a floating WhatsApp button.
- **Performance**: WebP-ready image sizes, self-hosted font preloading, inlined critical CSS,
  deferred JS, and WooCommerce asset trimming on non-shop pages.
- **Security**: hardening headers, version disclosure removal, `DISALLOW_FILE_EDIT`, xmlrpc
  disabled, and capability-safe AJAX.
- **SEO and Merchant Center**: JSON-LD Organization, Store, and LocalBusiness schema sitewide, plus
  Product schema (SKU, MPN, GTIN, brand, price, availability, condition) on product pages.
- **Auto-created content pages** on activation — About, Contact, Privacy Policy, Terms &
  Conditions, Shipping & Delivery, Return & Refund, Warranty, Payment Methods, Cookie Policy, FAQ,
  Track Order — filled with real, Kenya-specific, Merchant-Center-ready copy rather than
  placeholders. All remain fully editable in wp-admin.
- **Customizer panel** for brand colours and contact details.
- **TGMPA plugin installer** prompting for WooCommerce, Perfect Brands, One Click Demo Import,
  Elementor, Contact Form 7, and a recommended set (Rank Math, LiteSpeed Cache, WP Mail SMTP,
  Google Listings & Ads, PDF Invoices, wishlist/compare).
- **Accessibility**: skip link, visible focus states, ARIA labels, reduced-motion support.
- **Translation-ready**, **RTL-ready**, and **child-theme ready**.

Not yet implemented: the advanced AJAX filter sidebar, single-product enhancements (sticky
add-to-cart, delivery estimate, spec tabs), the quick-view modal, and the compare page. See
[`toptech/ROADMAP.md`](toptech/ROADMAP.md).

## The plugin (`toptech-webp/`)

**TopTech WebP Optimizer** (v1.0.0, requires WordPress 5.5+ / PHP 7.2+):

- Converts every JPEG and PNG in the Media Library to WebP, keeping originals intact.
- Auto-converts new uploads, including every generated thumbnail size.
- Serves WebP to supporting browsers via auto-managed `.htaccess` rules — URLs stay `.jpg`/`.png`
  while the delivered bytes are WebP.
- Deactivating removes the rules and stops conversion; images are never deleted.

Activate it, then run **Media → WebP Optimizer → Optimize all images now**. It also runs in the
background on a schedule.

## Installation

1. Zip the `toptech/` folder and upload it under **Appearance → Themes → Add New → Upload Theme**,
   then activate. Delete any older copy first so files are replaced cleanly.
2. Accept the TGMPA prompt and install the required plugins — at minimum WooCommerce, Perfect
   Brands for WooCommerce, and One Click Demo Import.
   Note: the TGMPA library itself must be placed at `toptech/inc/tgmpa/class-tgm-plugin-activation.php`
   (download from https://tgmpluginactivation.com/).
3. Import demo data: **Appearance → Import Demo Data → TopTech Machinery – Full Demo**. This
   creates the categories, brands, sample products, and navigation so the homepage renders fully.
4. Import the real catalogue: **Products → Import** with your product CSV — see
   [`toptech/IMPORT-PRODUCTS.md`](toptech/IMPORT-PRODUCTS.md). Matching SKUs are updated, not duplicated.
5. Zip and install `toptech-webp/` under **Plugins → Add New → Upload Plugin**, then activate.
6. Brand it: logo under **Appearance → Customize → Site Identity**, colours and contact details
   under **Customize → TopTech Machinery**.
7. Optional: apply the caching and compression rules in
   [`toptech/PERFORMANCE-htaccess-rules.txt`](toptech/PERFORMANCE-htaccess-rules.txt).

Full walkthrough: [`toptech/SETUP-FLOW.md`](toptech/SETUP-FLOW.md).

Requirements: WordPress 6.5+, WooCommerce 9+, PHP 8.1+, and HTTPS (required for secure checkout
and Google Merchant Center).

## Policy copy and trust claims

Delivery windows, Cash on Delivery availability, warranty wording, and returns terms appear in
theme templates, auto-created policy pages, and structured data. They must match the store's
actual policy everywhere they appear — Google Merchant Center checks site copy against feed data,
and unverifiable trust badges ("100% genuine", "verified business") should not be reintroduced.
Treat any change to these terms as a change that touches templates, page content, and schema
together.

## Contributing and conventions

- Work is committed **directly to `main`**; feature branches and pull requests are used only when
  explicitly requested.
- Live-site changes and repository commits are kept in sync — GitHub commits do not auto-deploy,
  so a change made in WordPress admin should also land here (and vice versa) to prevent a future
  theme upload reverting live edits.
- Put custom code in a child theme so theme updates do not overwrite it.

## Contact

- Phone / WhatsApp: 0797 720290
- Email: info@toptechmachinery.co.ke
- Address: This & That Exhibition, Opp. Ronald Ngala Post Office, Shop G15, Ronald Ngala Street,
  Nairobi, Kenya

## Related

A sister storefront built from the same theme architecture:
[awiportal/guruexpertpowertools](https://github.com/awiportal/guruexpertpowertools)
([guruexpertpowertools.co.ke](https://guruexpertpowertools.co.ke/)).

## License

GNU General Public License v2 or later — https://www.gnu.org/licenses/gpl-2.0.html
