# Importing your 546 products

Your `product_export_COMPLETE.csv` is a standard **WooCommerce product CSV**. Import it with the
built-in importer — no extra plugin needed.

## Steps

1. Install & activate **WooCommerce** first (and **Perfect Brands for WooCommerce** so the
   `product_brand` taxonomy exists for your 81 brands).
2. Go to **Products → All Products → Import** (top of page).
3. Upload `product_export_COMPLETE.csv`.
4. On the column-mapping screen, confirm these mappings (WooCommerce auto-detects most):

| CSV column            | Map to                          |
|-----------------------|---------------------------------|
| `post_title`          | Name                            |
| `post_content`        | Description                     |
| `post_excerpt`        | Short description               |
| `sku`                 | SKU                             |
| `regular_price`       | Regular price                   |
| `sale_price`          | Sale price                      |
| `stock_status`        | Stock status                    |
| `stock` / `manage_stock` | Stock / Manage stock         |
| `weight`,`length`,`width`,`height` | Dimensions          |
| `images`              | Images                          |
| `tax:product_cat`     | Categories                      |
| `tax:product_brand`   | Brands (Perfect Brands taxonomy)|
| `tax:product_tag`     | Tags                            |
| `meta:_powerplug_brand` | Meta: _powerplug_brand (or map to Brand) |

5. Run the import.

## Notes about your catalogue (detected)

- **546 products**, all *simple* type. Prices **KSh 699 – 150,000** (median ~KSh 13,000).
- **39 categories** — top: Water Pumps (50), Hardware Tools (49), Saws (42), Drills (40),
  Weighing Scales (39), Batteries (35), Home Appliances (33), Agricultural Equipment (31),
  Grinders (28), Welding Machines (28), Solar Panels (23)...
- **81 brands** — Total, Ingco, Makita, DeWalt, Honda, Solarmax, Aico, DCA, Premier, etc.
- All items are `instock`; none currently on sale (sale badges will appear automatically when you
  set a sale price).
- Images are hosted on `powertoolsplug.co.ke`. The importer will **sideload** them into your media
  library. If your host blocks remote fetch, re-host the images or import in batches.

## After import — Google Merchant Center identifiers

For best Merchant Center results, add these per product (Product data → custom fields or the
Google Listings & Ads plugin):
- **GTIN** → meta key `_gtin`  (theme reads this for Product schema)
- **MPN** → meta key `_mpn`
- **Brand** → already handled via the `product_brand` taxonomy.

The homepage product rows read from these category slugs (edit in `front-page.php` or via the
`toptech_homepage_categories` filter):
`water-pumps, power-tools, solar-panels, welding-machines, generators, batteries`
Adjust the slugs to match the exact category slugs WooCommerce creates on import.
