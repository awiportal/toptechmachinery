# TopTech Machinery - Setup Flow (ThemeForest-style)

This theme now installs like a premium ThemeForest theme:
**Activate -> install required plugins -> import demo data -> import your full products.**

## Step 1 - Activate the theme
Appearance -> Themes -> Add New -> Upload Theme -> upload `toptech-machinery.zip` -> Activate.
(If an old copy exists, delete that theme first so the new files replace it.)

## Step 2 - Install required plugins (automatic prompt)
Immediately after activation a yellow notice appears:
**"This theme requires the following plugins..."** -> click **Begin installing plugins**.
Select all and choose **Install**, then **Activate**. Required set:
- WooCommerce (the shop engine)
- Perfect Brands for WooCommerce (your 81 brands)
- One Click Demo Import (the demo importer)
- Elementor, Contact Form 7 (page building + contact form)
Recommended: Rank Math SEO, LiteSpeed Cache, WP Mail SMTP, Variation Swatches,
Google Listings & Ads, PDF Invoices, Wishlist, Compare.

## Step 3 - Import demo data (one click)
Go to **Appearance -> Import Demo Data** -> click **Import** on "TopTech Machinery - Full Demo".
This creates:
- All product **categories** (37) and **brands** (81)
- **42 sample products** with images, prices and SKUs (6 per top category)
- The **vertical categories menu** + navigation
- Your homepage layout comes alive (hero, category cards, product rows)

Legal/policy pages (About, Privacy, Terms, Returns, Shipping, Warranty, Contact, FAQ,
Cookies, Track Order, Payment Methods) are created automatically on activation - already
filled with complete, Merchant-Center-ready content.

## Step 4 - Import your full catalogue (546 products)
The demo loads a sample. To load your entire catalogue:
**Products -> Import -> upload `product_export_COMPLETE.csv`** and run it (mapping guide in
`IMPORT-PRODUCTS.md`). This brings in all 546 products with images. Existing sample products
with the same SKU are updated, not duplicated.

## Step 5 - Brand it
- Upload your logo: Appearance -> Customize -> Site Identity (use `assets/img/logo.webp`).
- Colours + contact details: Customize -> TopTech Machinery (yellow #005EB8 / navy #0B1E3F,
  phone 0797 720290, WhatsApp, email info@toptechmachinery.co.ke, Nairobi address).
- Set the homepage: Settings -> Reading -> "Your homepage displays" is handled automatically,
  but you can point it at the "Home" page if you prefer.

## Notes
- HTTPS must be on (required for checkout + Google Merchant Center).
- If image import is slow, that is WordPress sideloading images from your current host; let it
  finish or import products in batches.
- Everything (pages, colours, menus, products) stays fully editable in wp-admin.
