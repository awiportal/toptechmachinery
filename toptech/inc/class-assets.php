<?php
/**
 * Front-end + editor asset loading with performance defaults.
 *
 * @package RickyTools
 */

declare( strict_types = 1 );

namespace RickyTools;

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
	}

	public function enqueue(): void {
		$css_rel = file_exists( RICKY_DIR . 'assets/css/theme.min.css' ) ? 'assets/css/theme.min.css' : 'assets/css/theme.css';
		wp_enqueue_style( 'ricky-theme', RICKY_URI . $css_rel, array(), RICKY_VERSION );
		wp_style_add_data( 'ricky-theme', 'rtl', 'replace' );

		wp_enqueue_script( 'ricky-theme', RICKY_URI . 'assets/js/theme.js', array(), RICKY_VERSION, true );

		if ( class_exists( 'WooCommerce' ) ) {
			wp_enqueue_script( 'ricky-ajax', RICKY_URI . 'assets/js/ajax-cart.js', array( 'ricky-theme' ), RICKY_VERSION, true );
			wp_localize_script(
				'ricky-ajax',
				'RickyAjax',
				array(
					'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
					'nonce'     => wp_create_nonce( 'ricky_ajax' ),
					'cartUrl'   => wc_get_cart_url(),
					'i18n'      => array(
						'added'   => esc_html__( 'Added to cart', 'ricky-tools' ),
						'adding'  => esc_html__( 'Adding...', 'ricky-tools' ),
						'error'   => esc_html__( 'Something went wrong. Please try again.', 'ricky-tools' ),
						'viewCart'=> esc_html__( 'View cart', 'ricky-tools' ),
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
		echo '<style id="ricky-critical">:root{--rk-yellow:#FDB913;--rk-navy:#0B1E3F}body{margin:0;font-family:Inter,system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif;color:#1a1f2e;background:#fff}.rk-header{background:var(--rk-navy)}img{max-width:100%;height:auto}</style>' . "\n";
	}

	/**
	 * Defer all theme JS to remove render-blocking.
	 */
	public function defer_scripts( $tag, $handle = '', $src = '' ) {
		$defer = array( 'ricky-theme', 'ricky-ajax' );
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

}
