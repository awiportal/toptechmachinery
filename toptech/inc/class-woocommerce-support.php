<?php
/**
 * WooCommerce integration: supports, layout wrappers, uniform cards.
 *
 * @package ToptechMachinery
 */

declare( strict_types = 1 );

namespace ToptechMachinery;

defined( 'ABSPATH' ) || exit;

/**
 * Declares WooCommerce support and shapes shop/product markup.
 */
final class WooCommerce_Support {

	public function hooks(): void {
		add_action( 'after_setup_theme', array( $this, 'declare_support' ) );
		add_filter( 'http_request_timeout', array( $this, 'import_timeout' ), 20 );

		// Replace default wrappers with theme wrappers.
		remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
		remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
		add_action( 'woocommerce_before_main_content', array( $this, 'wrapper_start' ), 10 );
		add_action( 'woocommerce_after_main_content', array( $this, 'wrapper_end' ), 10 );

		// Products per row / per page tuned for uniform 4-up grids.
		add_filter( 'loop_shop_columns', static fn() => 6 );
		add_filter( 'loop_shop_per_page', static fn() => 24 );

		// Card structure: badge, wishlist, quick view hooks.
		add_action( 'woocommerce_before_shop_loop_item_title', array( $this, 'card_badges' ), 9 );

		// Our content-product.php builds the whole card manually, so strip WooCommerce's
		// default loop output (it was dumping a duplicate thumbnail into the badges div).
		add_action( 'init', array( $this, 'unhook_loop_defaults' ), 20 );

		// Mini-cart fragment refresh (count bubble).
		add_filter( 'woocommerce_add_to_cart_fragments', array( $this, 'cart_count_fragment' ) );

		// Breadcrumb tuning.
		add_filter( 'woocommerce_breadcrumb_defaults', array( $this, 'breadcrumbs' ) );

		// Remove the leftover default sidebar (Search / Recent Posts) from shop + product pages.
		remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );

		// Tidy related products into a clean 4-up row.
		add_filter( 'woocommerce_output_related_products_args', array( $this, 'related_args' ) );

		// Shop and category filters: sidebar UI plus query modifications.
		add_filter( 'woocommerce_product_query_tax_query', array( $this, 'filter_tax_query' ), 10, 2 );
		add_filter( 'woocommerce_product_query_meta_query', array( $this, 'filter_meta_query' ), 10, 2 );
		add_action( 'woocommerce_before_shop_loop', array( $this, 'filter_toggle_button' ), 5 );
		add_action( 'woocommerce_before_shop_loop', array( $this, 'filter_chips' ), 6 );
		add_action( 'woocommerce_product_query', array( $this, 'filter_product_query' ), 10, 2 );

		// Prominent sort pills replace the default ordering dropdown; unified toolbar replaces default result count.
		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
		add_action( 'woocommerce_before_shop_loop', array( $this, 'catalog_ordering' ), 8 );

		// Cart & checkout trust: reassurance note + payment chips.
		add_action( 'woocommerce_review_order_after_submit', array( $this, 'checkout_trust' ) );
		add_action( 'woocommerce_proceed_to_checkout', array( $this, 'cart_trust' ), 25 );
		add_action( 'woocommerce_thankyou', array( $this, 'thankyou_steps' ), 15 );
	}

	public function declare_support(): void {
		add_theme_support(
			'woocommerce',
			array(
				'thumbnail_image_width' => 600,
				'single_image_width'    => 900,
				'product_grid'          => array(
					'default_columns' => 6,
					'min_columns'     => 2,
					'max_columns'     => 6,
				),
			)
		);
		add_theme_support( 'wc-product-gallery-zoom' );
		add_theme_support( 'wc-product-gallery-lightbox' );
		add_theme_support( 'wc-product-gallery-slider' );
	}

	/**
	 * Give slow remote image downloads room during product import (admin only).
	 *
	 * @param mixed $timeout Current timeout (seconds).
	 * @return mixed
	 */
	public function import_timeout( $timeout ) {
		if ( is_admin() ) {
			return max( 60, (int) $timeout );
		}
		return $timeout;
	}

	public function wrapper_start(): void {
		$filtered = $this->is_filterable_archive();
		printf( '<div class="rk-shop container%s">', $filtered ? ' rk-shop--filtered' : '' );
		if ( $filtered ) {
			$this->render_filters();
		}
		echo '<main id="primary" class="rk-shop__main">';
	}

	public function wrapper_end(): void {
		echo '</main></div>';
	}

	/**
	 * Detach WooCommerce's default product-loop hooks; the theme template renders each card.
	 */
	public function unhook_loop_defaults(): void {
		remove_action( 'woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10 );
		remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );
		remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10 );
		remove_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10 );
		remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );
		remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );
		remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 );
		remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
	}

	/**
	 * Show a clean 4-up related products row.
	 *
	 * @param array $args Related product args.
	 */
	public function related_args( array $args ): array {
		$args['posts_per_page'] = 4;
		$args['columns']        = 4;
		return $args;
	}

	/**
	 * Output sale / stock badges on the product card.
	 */
	public function card_badges(): void {
		global $product;
		if ( ! $product instanceof \WC_Product ) {
			return;
		}
		if ( $product->is_on_sale() ) {
			echo '<span class="rk-badge rk-badge--sale">' . esc_html__( 'Sale', 'toptech-machinery' ) . '</span>';
		}
		if ( ! $product->is_in_stock() ) {
			echo '<span class="rk-badge rk-badge--oos">' . esc_html__( 'Out of stock', 'toptech-machinery' ) . '</span>';
		} elseif ( $product->is_featured() ) {
			echo '<span class="rk-badge rk-badge--feat">' . esc_html__( 'Featured', 'toptech-machinery' ) . '</span>';
		}
	}

	/**
	 * Keep the header cart count in sync via AJAX fragments.
	 *
	 * @param array $fragments Fragment map.
	 */
	public function cart_count_fragment( array $fragments ): array {
		$count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
		ob_start();
		printf( '<span class="rk-cart-count" data-count="%1$d">%1$d</span>', (int) $count );
		$fragments['.rk-cart-count'] = ob_get_clean();
		return $fragments;
	}


	/**
	 * @param array $defaults Breadcrumb args.
	 */
	public function breadcrumbs( array $defaults ): array {
		$defaults['delimiter'] = '<span class="rk-crumb-sep" aria-hidden="true">/</span>';
		$defaults['wrap_before'] = '<nav class="rk-breadcrumb container" aria-label="' . esc_attr__( 'Breadcrumb', 'toptech-machinery' ) . '">';
		$defaults['wrap_after']  = '</nav>';
		return $defaults;
	}

	/**
	 * True on shop and product taxonomy archives (category / brand / tag), where filters belong.
	 */
	private function is_filterable_archive(): bool {
		return function_exists( 'is_shop' ) && ( is_shop() || is_product_taxonomy() );
	}

	/**
	 * Resolve whichever brand taxonomy is active (native product_brand, Perfect Brands, YITH, etc.).
	 */
	private function brand_taxonomy(): string {
		static $cached = null;
		if ( null !== $cached ) {
			return $cached;
		}
		$candidates = array( 'product_brand', 'pwb-brand', 'product-brand', 'yith_product_brand', 'berocket_brand', 'pa_brand' );
		foreach ( $candidates as $tax ) {
			if ( taxonomy_exists( $tax ) ) {
				$cached = $tax;
				return $cached;
			}
		}
		foreach ( get_object_taxonomies( 'product', 'names' ) as $tax ) {
			if ( false !== stripos( $tax, 'brand' ) ) {
				$cached = $tax;
				return $cached;
			}
		}
		$cached = '';
		return $cached;
	}

	/**
	 * Base URL of the current archive (no query string) for the filter form and clear link.
	 */
	private function base_archive_url(): string {
		if ( is_product_taxonomy() ) {
			$term = get_queried_object();
			if ( $term instanceof \WP_Term ) {
				$link = get_term_link( $term );
				if ( ! is_wp_error( $link ) ) {
					return $link;
				}
			}
		}
		$shop = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : '';
		return $shop ? $shop : home_url( '/' );
	}

	/**
	 * Mobile-only "Filters" toggle button, shown above the product grid.
	 */
	public function filter_toggle_button(): void {
		if ( ! $this->is_filterable_archive() ) {
			return;
		}
		echo '<button type="button" class="rk-filters-toggle" data-rk-filters-open aria-controls="rk-filters" aria-expanded="false">';
		echo '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M3 5h18M6 12h12M10 19h4"/></svg>';
		echo '<span>' . esc_html__( 'Filters', 'toptech-machinery' ) . '</span>';
		echo '</button>';
	}

	/**
	 * Append the selected brands to the shop product query.
	 *
	 * @param array $tax_query Existing tax query.
	 * @param mixed $query     WC product query object.
	 */
	public function filter_tax_query( $tax_query, $query ) {
		$brand_tax = $this->brand_taxonomy();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only catalog filtering.
		if ( $brand_tax && ! empty( $_GET['rk_brand'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$brands = array_filter( array_map( 'sanitize_title', (array) wp_unslash( $_GET['rk_brand'] ) ) );
			if ( ! empty( $brands ) ) {
				$tax_query[] = array(
					'taxonomy' => $brand_tax,
					'field'    => 'slug',
					'terms'    => $brands,
					'operator' => 'IN',
				);
			}
		}
		return $tax_query;
	}

	/**
	 * Append the selected price band to the shop product query.
	 *
	 * @param array $meta_query Existing meta query.
	 * @param mixed $query      WC product query object.
	 */
	public function filter_meta_query( $meta_query, $query ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$min = isset( $_GET['rk_min'] ) ? (float) $_GET['rk_min'] : 0.0;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$max = isset( $_GET['rk_max'] ) ? (float) $_GET['rk_max'] : 0.0;
		if ( $min > 0 || $max > 0 ) {
			$lo = $min > 0 ? $min : 0;
			$hi = $max > 0 ? $max : 99999999;
			if ( $lo > $hi ) {
				$swap = $lo;
				$lo   = $hi;
				$hi   = $swap;
			}
			$meta_query[] = array(
				'key'     => '_price',
				'value'   => array( $lo, $hi ),
				'compare' => 'BETWEEN',
				'type'    => 'NUMERIC',
			);
		}
		return $meta_query;
	}

	/**
	 * Render the filters sidebar: categories (links), brands (checkboxes), price band.
	 */
	public function render_filters(): void {
		$brand_tax = $this->brand_taxonomy();
		$base      = $this->base_archive_url();
		$shop_url  = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );

		$cats = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => true,
				'orderby'    => 'count',
				'order'      => 'DESC',
				'number'     => 40,
			)
		);
		$brands = $brand_tax ? get_terms(
			array(
				'taxonomy'   => $brand_tax,
				'hide_empty' => true,
				'orderby'    => 'count',
				'order'      => 'DESC',
				'number'     => 60,
			)
		) : array();

		$current_cat = is_product_category() ? (int) get_queried_object_id() : 0;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$sel_brands = isset( $_GET['rk_brand'] ) ? array_map( 'sanitize_title', (array) wp_unslash( $_GET['rk_brand'] ) ) : array();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$min_val = ( isset( $_GET['rk_min'] ) && '' !== $_GET['rk_min'] ) ? (int) $_GET['rk_min'] : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$max_val = ( isset( $_GET['rk_max'] ) && '' !== $_GET['rk_max'] ) ? (int) $_GET['rk_max'] : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : '';

		$has_active = ( ! empty( $sel_brands ) || '' !== $min_val || '' !== $max_val );
		?>
		<aside class="rk-filters" id="rk-filters" aria-label="<?php esc_attr_e( 'Product filters', 'toptech-machinery' ); ?>">
			<div class="rk-filters__head">
				<h2 class="rk-filters__title"><?php esc_html_e( 'Filters', 'toptech-machinery' ); ?></h2>
				<button type="button" class="rk-filters__close" data-rk-filters-close aria-label="<?php esc_attr_e( 'Close filters', 'toptech-machinery' ); ?>">&times;</button>
			</div>

			<?php if ( ! is_wp_error( $cats ) && ! empty( $cats ) ) : ?>
			<div class="rk-filter">
				<h3 class="rk-filter__title"><?php esc_html_e( 'Categories', 'toptech-machinery' ); ?></h3>
				<ul class="rk-filter__cats rk-filter__scroll">
					<li><a class="rk-filter__cat<?php echo $current_cat ? '' : ' is-active'; ?>" href="<?php echo esc_url( $shop_url ); ?>"><span class="rk-filter__cat-name"><?php esc_html_e( 'All products', 'toptech-machinery' ); ?></span></a></li>
					<?php foreach ( $cats as $cat ) : ?>
						<li>
							<a class="rk-filter__cat<?php echo ( (int) $cat->term_id === $current_cat ) ? ' is-active' : ''; ?>" href="<?php echo esc_url( get_term_link( $cat ) ); ?>">
								<span class="rk-filter__cat-name"><?php echo esc_html( $cat->name ); ?></span>
								<span class="rk-filter__count"><?php echo esc_html( (string) $cat->count ); ?></span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
			<?php endif; ?>

			<form class="rk-filters__form" method="get" action="<?php echo esc_url( $base ); ?>">
				<?php if ( '' !== $orderby ) : ?>
					<input type="hidden" name="orderby" value="<?php echo esc_attr( $orderby ); ?>">
				<?php endif; ?>

				<?php if ( ! is_wp_error( $brands ) && ! empty( $brands ) ) : ?>
				<div class="rk-filter">
					<h3 class="rk-filter__title"><?php esc_html_e( 'Brands', 'toptech-machinery' ); ?></h3>
					<div class="rk-filter__scroll rk-filter__brands">
						<?php foreach ( $brands as $brand ) : ?>
							<label class="rk-check">
								<input type="checkbox" name="rk_brand[]" value="<?php echo esc_attr( $brand->slug ); ?>" <?php checked( in_array( $brand->slug, $sel_brands, true ) ); ?>>
								<span class="rk-check__box" aria-hidden="true"></span>
								<span class="rk-check__label"><?php echo esc_html( $brand->name ); ?></span>
								<span class="rk-filter__count"><?php echo esc_html( (string) $brand->count ); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
				</div>
				<?php endif; ?>

				<div class="rk-filter">
					<h3 class="rk-filter__title"><?php esc_html_e( 'Price (KSh)', 'toptech-machinery' ); ?></h3>
					<div class="rk-price">
						<input type="number" class="rk-price__in" name="rk_min" value="<?php echo esc_attr( '' === $min_val ? '' : (string) $min_val ); ?>" placeholder="<?php esc_attr_e( 'Min', 'toptech-machinery' ); ?>" min="0" inputmode="numeric">
						<span class="rk-price__sep">&ndash;</span>
						<input type="number" class="rk-price__in" name="rk_max" value="<?php echo esc_attr( '' === $max_val ? '' : (string) $max_val ); ?>" placeholder="<?php esc_attr_e( 'Max', 'toptech-machinery' ); ?>" min="0" inputmode="numeric">
					</div>
				</div>

				<div class="rk-filter">
					<h3 class="rk-filter__title"><?php esc_html_e( 'Availability', 'toptech-machinery' ); ?></h3>
					<div class="rk-filter__avail">
						<label class="rk-check">
							<input type="checkbox" name="rk_instock" value="1" <?php checked( ! empty( $_GET['rk_instock'] ) ); ?>>
							<span class="rk-check__box" aria-hidden="true"></span>
							<span class="rk-check__label"><?php esc_html_e( 'In stock only', 'toptech-machinery' ); ?></span>
						</label>
						<label class="rk-check">
							<input type="checkbox" name="rk_sale" value="1" <?php checked( ! empty( $_GET['rk_sale'] ) ); ?>>
							<span class="rk-check__box" aria-hidden="true"></span>
							<span class="rk-check__label"><?php esc_html_e( 'On sale', 'toptech-machinery' ); ?></span>
						</label>
					</div>
				</div>
				<div class="rk-filters__actions">
					<button type="submit" class="rk-btn rk-btn--primary rk-btn--block"><?php esc_html_e( 'Apply filters', 'toptech-machinery' ); ?></button>
					<?php if ( $has_active ) : ?>
						<a class="rk-filters__clear" href="<?php echo esc_url( $base ); ?>"><?php esc_html_e( 'Clear all', 'toptech-machinery' ); ?></a>
					<?php endif; ?>
				</div>
			</form>
		</aside>
		<div class="rk-filters__overlay" data-rk-filters-close></div>
		<?php
	}


	/**
	 * Apply the on-sale and in-stock toggles to the shop query.
	 *
	 * @param \WP_Query $q        The product query.
	 * @param mixed     $wc_query WC_Query instance.
	 */
	public function filter_product_query( $q, $wc_query ): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only catalog filtering.
		if ( ! empty( $_GET['rk_sale'] ) ) {
			$on_sale = function_exists( 'wc_get_product_ids_on_sale' ) ? wc_get_product_ids_on_sale() : array();
			if ( empty( $on_sale ) ) {
				$on_sale = array( 0 );
			}
			$existing = array_filter( (array) $q->get( 'post__in' ) );
			if ( ! empty( $existing ) ) {
				$on_sale = array_intersect( $existing, $on_sale );
				if ( empty( $on_sale ) ) {
					$on_sale = array( 0 );
				}
			}
			$q->set( 'post__in', array_values( $on_sale ) );
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $_GET['rk_instock'] ) ) {
			$mq   = (array) $q->get( 'meta_query' );
			$mq[] = array(
				'key'   => '_stock_status',
				'value' => 'instock',
			);
			$q->set( 'meta_query', $mq );
		}
	}

	/**
	 * Collect the currently active filters from the request.
	 */
	private function active_filters(): array {
		$f = array();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $_GET['rk_brand'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$brands = array_filter( array_map( 'sanitize_title', (array) wp_unslash( $_GET['rk_brand'] ) ) );
			if ( ! empty( $brands ) ) {
				$f['rk_brand'] = array_values( $brands );
			}
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['rk_min'] ) && '' !== $_GET['rk_min'] ) {
			$f['rk_min'] = (int) $_GET['rk_min'];
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['rk_max'] ) && '' !== $_GET['rk_max'] ) {
			$f['rk_max'] = (int) $_GET['rk_max'];
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $_GET['rk_instock'] ) ) {
			$f['rk_instock'] = 1;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $_GET['rk_sale'] ) ) {
			$f['rk_sale'] = 1;
		}
		return $f;
	}

	/**
	 * Build a filter URL from a set of params, defaulting to the current archive base.
	 *
	 * @param array  $params Query params to encode.
	 * @param string $base   Optional base URL.
	 */
	private function filter_url( array $params, string $base = '' ): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : '';
		if ( '' !== $orderby ) {
			$params['orderby'] = $orderby;
		}
		if ( '' === $base ) {
			$base = $this->base_archive_url();
		}
		if ( empty( $params ) ) {
			return $base;
		}
		return $base . '?' . http_build_query( $params );
	}

	/**
	 * Render the active-filter chips row above the product grid.
	 */
	public function filter_chips(): void {
		if ( ! $this->is_filterable_archive() ) {
			return;
		}
		$f      = $this->active_filters();
		$on_cat = is_product_category();

		global $wp_query;
		$total = isset( $wp_query->found_posts ) ? (int) $wp_query->found_posts : 0;

		echo '<div class="rk-shop-toolbar">';
		echo '<div class="rk-chips">';

		if ( ! empty( $f ) || $on_cat ) {
			echo '<span class="rk-chips__label">' . esc_html__( 'Active:', 'toptech-machinery' ) . '</span>';

			if ( $on_cat ) {
				$term = get_queried_object();
				$shop = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
				if ( $term instanceof \WP_Term ) {
					printf(
						'<a class="rk-chip rk-chip--cat" href="%s">%s <span class="rk-chip__x" aria-hidden="true">&times;</span></a>',
						esc_url( $this->filter_url( $f, $shop ) ),
						esc_html( $term->name )
					);
				}
			}

			if ( ! empty( $f['rk_brand'] ) ) {
				$brand_tax = $this->brand_taxonomy();
				foreach ( $f['rk_brand'] as $slug ) {
					$term             = $brand_tax ? get_term_by( 'slug', $slug, $brand_tax ) : false;
					$name             = $term ? $term->name : $slug;
					$rest             = $f;
					$rest['rk_brand'] = array_values( array_diff( $f['rk_brand'], array( $slug ) ) );
					if ( empty( $rest['rk_brand'] ) ) {
						unset( $rest['rk_brand'] );
					}
					printf(
						'<a class="rk-chip" href="%s">%s <span class="rk-chip__x" aria-hidden="true">&times;</span></a>',
						esc_url( $this->filter_url( $rest ) ),
						esc_html( $name )
					);
				}
			}

			if ( isset( $f['rk_min'] ) || isset( $f['rk_max'] ) ) {
				$lo = isset( $f['rk_min'] ) ? $f['rk_min'] : null;
				$hi = isset( $f['rk_max'] ) ? $f['rk_max'] : null;
				if ( null !== $lo && null !== $hi ) {
					$label = sprintf( 'KSh %s - %s', number_format( $lo ), number_format( $hi ) );
				} elseif ( null !== $lo ) {
					$label = sprintf( 'KSh %s+', number_format( $lo ) );
				} else {
					$label = sprintf( 'Up to KSh %s', number_format( $hi ) );
				}
				$rest = $f;
				unset( $rest['rk_min'], $rest['rk_max'] );
				printf(
					'<a class="rk-chip" href="%s">%s <span class="rk-chip__x" aria-hidden="true">&times;</span></a>',
					esc_url( $this->filter_url( $rest ) ),
					esc_html( $label )
				);
			}

			if ( ! empty( $f['rk_instock'] ) ) {
				$rest = $f;
				unset( $rest['rk_instock'] );
				printf(
					'<a class="rk-chip" href="%s">%s <span class="rk-chip__x" aria-hidden="true">&times;</span></a>',
					esc_url( $this->filter_url( $rest ) ),
					esc_html__( 'In stock', 'toptech-machinery' )
				);
			}

			if ( ! empty( $f['rk_sale'] ) ) {
				$rest = $f;
				unset( $rest['rk_sale'] );
				printf(
					'<a class="rk-chip" href="%s">%s <span class="rk-chip__x" aria-hidden="true">&times;</span></a>',
					esc_url( $this->filter_url( $rest ) ),
					esc_html__( 'On sale', 'toptech-machinery' )
				);
			}

			if ( ! empty( $f ) ) {
				printf(
					'<a class="rk-chip rk-chip--clear" href="%s">%s</a>',
					esc_url( $this->base_archive_url() ),
					esc_html__( 'Clear all', 'toptech-machinery' )
				);
			}
		}

		echo '</div>';

		printf(
			'<div class="rk-result-count">%s</div>',
			esc_html( sprintf( _n( '%s product', '%s products', $total, 'toptech-machinery' ), number_format( $total ) ) )
		);

		echo '</div>';
	}


	/**
	 * Prominent sort pills that replace WooCommerce's default ordering dropdown.
	 */
	public function catalog_ordering(): void {
		if ( ! $this->is_filterable_archive() ) {
			return;
		}
		$options = array(
			'menu_order' => __( 'Featured', 'toptech-machinery' ),
			'popularity' => __( 'Popular', 'toptech-machinery' ),
			'date'       => __( 'Newest', 'toptech-machinery' ),
			'price'      => __( 'Price: Low to High', 'toptech-machinery' ),
			'price-desc' => __( 'Price: High to Low', 'toptech-machinery' ),
			'rating'     => __( 'Top rated', 'toptech-machinery' ),
		);
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only catalog ordering.
		$current = isset( $_GET['orderby'] ) ? wc_clean( wp_unslash( $_GET['orderby'] ) ) : 'menu_order';
		if ( ! array_key_exists( $current, $options ) ) {
			$current = 'menu_order';
		}
		$params = $this->active_filters();
		echo '<div class="rk-sort">';
		echo '<span class="rk-sort__label">' . esc_html__( 'Sort by', 'toptech-machinery' ) . '</span>';
		echo '<div class="rk-sort__pills">';
		foreach ( $options as $val => $label ) {
			$p = $params;
			if ( 'menu_order' !== $val ) {
				$p['orderby'] = $val;
			}
			$url    = $this->sort_url( $p );
			$active = ( $current === $val ) ? ' is-active' : '';
			printf(
				'<a class="rk-sort__pill%s" href="%s">%s</a>',
				esc_attr( $active ),
				esc_url( $url ),
				esc_html( $label )
			);
		}
		echo '</div></div>';
	}

	/**
	 * Build a sort/filter URL with explicit params (no implicit orderby).
	 *
	 * @param array $params Query params.
	 */
	private function sort_url( array $params ): string {
		$base = $this->base_archive_url();
		if ( empty( $params ) ) {
			return $base;
		}
		return $base . '?' . http_build_query( $params );
	}


	/**
	 * Payment method chips (reused on cart totals and checkout).
	 */
	private function payment_chips(): string {
		$methods = array(
			__( 'M-PESA', 'toptech-machinery' ),
			__( 'Visa', 'toptech-machinery' ),
			__( 'Mastercard', 'toptech-machinery' ),
			__( 'Cash on Delivery', 'toptech-machinery' ),
		);
		$out = '<div class="rk-payments rk-payments--checkout">';
		foreach ( $methods as $m ) {
			$out .= '<span>' . esc_html( $m ) . '</span>';
		}
		$out .= '</div>';
		return $out;
	}

	/**
	 * Small padlock icon for trust notes.
	 */
	private function lock_icon(): string {
		return '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="10.5" width="16" height="9.5" rx="2"/><path d="M8 10.5V7a4 4 0 0 1 8 0v3.5"/></svg>';
	}

	/**
	 * Trust note + payment chips shown under the checkout place-order button.
	 */
	public function checkout_trust(): void {
		echo '<div class="rk-checkout-trust">';
		echo '<p class="rk-secure-note">' . $this->lock_icon() . '<span>' . esc_html__( 'Secure checkout. Your details are encrypted and never shared.', 'toptech-machinery' ) . '</span></p>';
		echo $this->payment_chips(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts.
		echo '</div>';
	}

	/**
	 * Reassurance note under the cart "Proceed to checkout" button.
	 */
	public function cart_trust(): void {
		echo '<div class="rk-cart-trust">';
		echo '<p class="rk-secure-note">' . $this->lock_icon() . '<span>' . esc_html__( 'Secure checkout. M-PESA, cards and cash on delivery accepted.', 'toptech-machinery' ) . '</span></p>';
		echo '</div>';
	}


	/**
	 * "What happens next" steps on the order-received page.
	 *
	 * @param int $order_id Received order ID.
	 */
	public function thankyou_steps( $order_id ): void {
		$order = $order_id ? wc_get_order( $order_id ) : false;
		if ( $order && $order->has_status( array( 'failed', 'cancelled' ) ) ) {
			return;
		}
		$shop  = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
		$steps = array(
			array( __( 'Order confirmed', 'toptech-machinery' ), __( 'We have received your order and emailed you a confirmation.', 'toptech-machinery' ) ),
			array( __( 'We prepare your items', 'toptech-machinery' ), __( 'Our team packs and checks your order for dispatch.', 'toptech-machinery' ) ),
			array( __( 'Delivery or pickup', 'toptech-machinery' ), __( 'We deliver countrywide or you collect in-store. We will call to confirm.', 'toptech-machinery' ) ),
		);
		echo '<section class="rk-next">';
		echo '<h2 class="rk-next__title">' . esc_html__( 'What happens next', 'toptech-machinery' ) . '</h2>';
		echo '<ol class="rk-next__steps">';
		$i = 1;
		foreach ( $steps as $step ) {
			printf(
				'<li class="rk-next__step"><span class="rk-next__num">%d</span><span class="rk-next__body"><strong>%s</strong><span>%s</span></span></li>',
				(int) $i,
				esc_html( $step[0] ),
				esc_html( $step[1] )
			);
			$i++;
		}
		echo '</ol>';
		echo '<div class="rk-next__cta">';
		printf( '<a class="rk-btn rk-btn--primary" href="%s">%s</a>', esc_url( $shop ), esc_html__( 'Continue shopping', 'toptech-machinery' ) );
		echo '<p class="rk-next__help">' . esc_html__( 'Questions about your order? Call us on 0797 720290.', 'toptech-machinery' ) . '</p>';
		echo '</div>';
		echo '</section>';
	}

}
