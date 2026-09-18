<?php
/**
 * Front-end + editor asset loading with performance defaults.
 *
 * @package ToptechMachinery
 */

declare( strict_types = 1 );

namespace ToptechMachinery;

defined( 'ABSPATH' ) || exit;

/**
 * Enqueues styles/scripts, preloads fonts, defers non-critical JS.
 */
final class Assets {

	public function hooks(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'wp_head', array( $this, 'preload_and_critical' ), 1 );
		add_filter( 'script_loader_tag', array( $this, 'defer_scripts' ), 10, 3 );
		// Trim WooCommerce bloat on non-woo pages (perf).
		add_action( 'wp_enqueue_scripts', array( $this, 'dequeue_woo_bloat' ), 99 );
			add_action( 'init', array( $this, 'trim_head' ) );
		add_action( 'admin_head', array( $this, 'admin_list_css' ) );
	}

	public function enqueue(): void {
		$css_rel = file_exists( TOPTECH_DIR . 'assets/css/theme.min.css' ) ? 'assets/css/theme.min.css' : 'assets/css/theme.css';
		wp_enqueue_style( 'toptech-theme', TOPTECH_URI . $css_rel, array(), TOPTECH_VERSION );
		wp_style_add_data( 'toptech-theme', 'rtl', 'replace' );
		wp_enqueue_style( 'toptech-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Oswald:wght@500;600;700&display=swap', array(), null );
		wp_enqueue_style( 'toptech-industrial', TOPTECH_URI . 'assets/css/theme-industrial.css', array( 'toptech-theme' ), TOPTECH_VERSION );

		wp_enqueue_script( 'toptech-theme', TOPTECH_URI . 'assets/js/theme.js', array(), TOPTECH_VERSION, true );

		if ( class_exists( 'WooCommerce' ) ) {
			wp_enqueue_script( 'toptech-ajax', TOPTECH_URI . 'assets/js/ajax-cart.js', array( 'toptech-theme' ), TOPTECH_VERSION, true );
			wp_localize_script(
				'toptech-ajax',
				'ToptechAjax',
				array(
					'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
					'nonce'     => wp_create_nonce( 'toptech_ajax' ),
					'cartUrl'   => wc_get_cart_url(),
					'i18n'      => array(
						'added'   => esc_html__( 'Added to cart', 'toptech-machinery' ),
						'adding'  => esc_html__( 'Adding...', 'toptech-machinery' ),
						'error'   => esc_html__( 'Something went wrong. Please try again.', 'toptech-machinery' ),
						'viewCart'=> esc_html__( 'View cart', 'toptech-machinery' ),
					),
				)
			);
		}

		if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
			wp_enqueue_script( 'comment-reply' );
		}
	}

	/**
	 * Inline minimal critical CSS for fast FCP. Uses system fonts (no webfont download).
	 */
	public function preload_and_critical(): void {
		echo '<style id="toptech-critical">:root{--rk-primary:#005EB8;--rk-navy:#0B1E3F}body{margin:0;font-family:Inter,system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif;color:#1a1f2e;background:#fff}.rk-header{background:var(--rk-navy)}img{max-width:100%;height:auto}</style>' . "\n";
	}

	/**
	 * Defer all theme JS to remove render-blocking.
	 */
	public function defer_scripts( $tag, $handle = '', $src = '' ) {
		$defer = array( 'toptech-theme', 'toptech-ajax' );
		if ( is_string( $tag ) && in_array( $handle, $defer, true ) && false === strpos( $tag, 'defer' ) ) {
			$tag = str_replace( ' src', ' defer src', $tag );
		}
		return $tag;
	}

	/**
	 * Only load WooCommerce cart/checkout assets where needed.
	 */
	public function dequeue_woo_bloat(): void {
		if ( ! function_exists( 'is_woocommerce' ) ) {
			return;
		}
		if ( ! is_woocommerce() && ! is_cart() && ! is_checkout() && ! is_account_page() ) {
			wp_dequeue_style( 'wc-blocks-style' );
		}
	}

	/**
	 * Strip front-end bloat: emoji detection, embed script, generator/rsd meta.
	 */
	public function trim_head(): void {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		remove_action( 'wp_head', 'wp_generator' );
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		add_filter( 'emoji_svg_url', '__return_false' );
		add_filter(
			'tiny_mce_plugins',
			static function ( $plugins ) {
				return is_array( $plugins ) ? array_diff( $plugins, array( 'wpemoji' ) ) : $plugins;
			}
		);
		add_action(
			'wp_footer',
			static function () {
				wp_dequeue_script( 'wp-embed' );
			},
			1
		);
	}


	/**
	 * Keep the Products list table readable in wp-admin.
	 *
	 * Rank Math adds an "SEO Details" column to the products list. Between that,
	 * the WooCommerce columns, the GTIN/ISBN plugin column and two separate brand
	 * taxonomies, the table runs out of horizontal room and the SEO column
	 * collapses to roughly one character wide. Its text then wraps a letter per
	 * line ("Keyword: Not Set", "Schema: WooCommerce Product"), which stretches
	 * every product row to several hundred pixels tall.
	 *
	 * Rather than strip anyone's columns, give the cramped ones a floor and let
	 * the table scroll sideways instead of crushing itself. Admin-only: this has
	 * no effect on the storefront, the product feed or Merchant Center.
	 */
	public function admin_list_css(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( null === $screen ) {
			return;
		}
		if ( 'edit-product' === $screen->id ) {
			echo '<style id="toptech-admin-list">'
				// Hide the GTIN/UPC/EAN/ISBN column. It is empty for every product
				// here and machinery is not sold by barcode, so it only costs width.
				// Several candidate class names are listed because the column comes
				// from WooCommerce core on some versions and a plugin on others; a
				// selector that matches nothing is harmless.
				. '.post-type-product th.column-global_unique_id,.post-type-product td.column-global_unique_id,'
				. '.post-type-product th.column-isbn,.post-type-product td.column-isbn,'
				. '.post-type-product th.column-gtin,.post-type-product td.column-gtin{display:none}'
				// Let the table size itself to the viewport. No min-width and no
				// overflow wrapper: a horizontal scrollbar on a 40-page list is worse
				// than slightly narrow cells.
				. '.post-type-product table.wp-list-table{table-layout:auto}'
				// A floor just wide enough that "Keyword: Not Set" wraps as words
				// rather than one letter per line, which was the original fault.
				. '.post-type-product th.column-rank_math_seo_details,'
				. '.post-type-product td.column-rank_math_seo_details{min-width:150px;white-space:normal;word-break:normal;overflow-wrap:break-word}'
				. '.post-type-product th.column-name,.post-type-product td.column-name{min-width:190px}'
				. '.post-type-product th.column-thumb,.post-type-product td.column-thumb{width:52px}'
				. '</style>' . "\n";
		}
	}
}
