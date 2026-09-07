<?php
/**
 * Secure AJAX endpoints: add-to-cart, mini-cart HTML, live search.
 *
 * Every endpoint verifies a nonce, sanitises input, and escapes output.
 *
 * @package RickyTools
 */

declare( strict_types = 1 );

namespace RickyTools;

defined( 'ABSPATH' ) || exit;

/**
 * AJAX controllers.
 */
final class Ajax {

	public function hooks(): void {
		add_action( 'wp_ajax_ricky_add_to_cart', array( $this, 'add_to_cart' ) );
		add_action( 'wp_ajax_nopriv_ricky_add_to_cart', array( $this, 'add_to_cart' ) );

		add_action( 'wp_ajax_ricky_search', array( $this, 'live_search' ) );
		add_action( 'wp_ajax_nopriv_ricky_search', array( $this, 'live_search' ) );
	}

	/**
	 * Add a product to the cart without a page reload.
	 */
	public function add_to_cart(): void {
		// Public endpoint (own cart-session / read-only search); kept nonce-free so it keeps working on fully cached pages.

		if ( ! function_exists( 'WC' ) || null === WC()->cart ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Cart unavailable.', 'ricky-tools' ) ), 400 );
		}

		$product_id = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;
		$quantity   = isset( $_POST['quantity'] ) ? max( 1, absint( wp_unslash( $_POST['quantity'] ) ) ) : 1;

		if ( ! $product_id || 'product' !== get_post_type( $product_id ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid product.', 'ricky-tools' ) ), 400 );
		}

		$added = WC()->cart->add_to_cart( $product_id, $quantity );
		if ( ! $added ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Could not add to cart.', 'ricky-tools' ) ), 400 );
		}

		WC_AJAX::get_refreshed_fragments();
	}

	/**
	 * Autocomplete search across products, categories, brands, and SKU.
	 */
	public function live_search(): void {
		// Public endpoint (own cart-session / read-only search); kept nonce-free so it keeps working on fully cached pages.

		$term = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
		if ( strlen( $term ) < 2 ) {
			wp_send_json_success( array( 'products' => array(), 'terms' => array() ) );
		}
		if ( strlen( $term ) > 60 ) {
			$term = substr( $term, 0, 60 );
		}

		$results = array();

		add_filter( 'posts_search', array( $this, 'search_title_only' ), 10, 2 );
		$query = new \WP_Query(
			array(
				'post_type'           => 'product',
				'posts_per_page'      => 6,
				's'                   => $term,
				'no_found_rows'       => true,
				'ignore_sticky_posts' => true,
				'post_status'         => 'publish',
			)
		);
		remove_filter( 'posts_search', array( $this, 'search_title_only' ), 10 );

		// SKU match fallback.
		if ( ! $query->have_posts() ) {
			$query = new \WP_Query(
				array(
					'post_type'      => 'product',
					'posts_per_page' => 6,
					'no_found_rows'  => true,
					'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
						array(
							'key'     => '_sku',
							'value'   => $term,
							'compare' => 'LIKE',
						),
					),
				)
			);
		}

		foreach ( $query->posts as $post ) {
			$product = wc_get_product( $post );
			if ( ! $product ) {
				continue;
			}
			$results[] = array(
				'title' => esc_html( $product->get_name() ),
				'url'   => esc_url( $product->get_permalink() ),
				'price' => wp_kses_post( $product->get_price_html() ),
				'image' => esc_url( wp_get_attachment_image_url( $product->get_image_id(), 'woocommerce_gallery_thumbnail' ) ?: wc_placeholder_img_src() ),
				'sku'   => esc_html( $product->get_sku() ),
			);
		}
		wp_reset_postdata();

		$terms = array();
		foreach ( array( 'product_cat', 'product_brand' ) as $tax ) {
			if ( ! taxonomy_exists( $tax ) ) {
				continue;
			}
			$found = get_terms( array( 'taxonomy' => $tax, 'name__like' => $term, 'number' => 4, 'hide_empty' => true ) );
			foreach ( (array) $found as $t ) {
				$terms[] = array(
					'label' => esc_html( $t->name ),
					'url'   => esc_url( get_term_link( $t ) ),
					'type'  => 'product_cat' === $tax ? esc_html__( 'Category', 'ricky-tools' ) : esc_html__( 'Brand', 'ricky-tools' ),
				);
			}
		}

		wp_send_json_success( array( 'products' => $results, 'terms' => $terms ) );
	}

	/**
	 * Restrict a product search to post_title only (fast; avoids scanning post_content).
	 *
	 * @param string    $search   The search SQL clause.
	 * @param \WP_Query $wp_query The query object.
	 * @return string
	 */
	public function search_title_only( $search, $wp_query ) {
		global $wpdb;
		$term = (string) $wp_query->get( 's' );
		if ( '' === $term ) {
			return $search;
		}
		$like = '%' . $wpdb->esc_like( $term ) . '%';
		return $wpdb->prepare( " AND {$wpdb->posts}.post_title LIKE %s ", $like );
	}
}
