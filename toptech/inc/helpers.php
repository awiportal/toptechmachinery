<?php
/**
 * Small template helpers.
 *
 * @package ToptechMachinery
 */

defined( 'ABSPATH' ) || exit;

/**
 * Return an inline SVG icon (accessible, currentColor).
 *
 * @param string $name Icon key.
 */
function rk_icon( string $name ): string {
	$icons = array(
		'user'  => '<path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5Zm0 2c-4 0-8 2-8 5v1h16v-1c0-3-4-5-8-5Z"/>',
		'heart' => '<path d="M12 21s-7-4.4-9.3-8.5C1 9.3 2.6 6 6 6c2 0 3.2 1.2 4 2.3C10.8 7.2 12 6 14 6c3.4 0 5 3.3 3.3 6.5C19 16.6 12 21 12 21Z"/>',
		'cart'  => '<path d="M7 4h-2l-1 2H2v2h1.6l2.5 9h11l2.4-8H7.3l-.5-2H21V4H7Zm1 15a2 2 0 1 0 2 2 2 2 0 0 0-2-2Zm9 0a2 2 0 1 0 2 2 2 2 0 0 0-2-2Z"/>',
	);
	$path = $icons[ $name ] ?? '';
	return '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false">' . $path . '</svg>';
}

/**
 * Cached product-category (or other taxonomy) term list to cut repeat DB queries.
 * Versioned cache: editing a product category bumps the version so lists refresh.
 *
 * @param string $taxonomy Taxonomy slug.
 * @param int    $number   Max terms.
 * @param string $orderby  Order field.
 * @return array
 */
function rk_cached_terms( string $taxonomy, int $number = 30, string $orderby = 'count' ): array {
	if ( ! taxonomy_exists( $taxonomy ) ) {
		return array();
	}
	$ver = get_option( 'rk_terms_ver', '1' );
	$key = 'rk_terms_' . md5( $taxonomy . '|' . $number . '|' . $orderby . '|' . $ver );
	$hit = get_transient( $key );
	if ( is_array( $hit ) ) {
		return $hit;
	}
	$terms = get_terms(
		array(
			'taxonomy'   => $taxonomy,
			'orderby'    => $orderby,
			'order'      => 'DESC',
			'hide_empty' => true,
			'number'     => $number,
		)
	);
	if ( is_wp_error( $terms ) ) {
		return array();
	}
	set_transient( $key, $terms, 6 * HOUR_IN_SECONDS );
	return $terms;
}

/**
 * Bump the cached-terms version when product categories change.
 */
function rk_bump_terms_version(): void {
	update_option( 'rk_terms_ver', (string) time(), false );
}
add_action( 'created_product_cat', 'rk_bump_terms_version' );
add_action( 'edited_product_cat', 'rk_bump_terms_version' );
add_action( 'delete_product_cat', 'rk_bump_terms_version' );

/**
 * Sanitised WhatsApp number (digits only), editable in Customizer > Contact.
 */
function rk_whatsapp_number(): string {
	$raw = (string) get_theme_mod( 'toptech_whatsapp', '254797720290' );
	$num = preg_replace( '/[^0-9]/', '', $raw );
	return is_string( $num ) ? $num : '';
}

/**
 * Build a wa.me order/inquiry link that prefills the product name + URL.
 *
 * @param mixed $product WC_Product.
 */
function rk_whatsapp_url( $product ): string {
	$num = rk_whatsapp_number();
	if ( '' === $num || is_object( $product ) === false ) {
		return '';
	}
	$name = method_exists( $product, 'get_name' ) ? $product->get_name() : '';
	$url  = method_exists( $product, 'get_permalink' ) ? $product->get_permalink() : '';
	$text = sprintf(
		/* translators: 1: product name, 2: product URL */
		__( 'Hello, I would like to inquire about or order this item: %1$s - %2$s', 'toptech-machinery' ),
		$name,
		$url
	);
	return 'https://wa.me/' . $num . '?text=' . rawurlencode( $text );
}

/**
 * Render an "Order on WhatsApp" button for a product (escaped).
 *
 * @param mixed  $product     WC_Product.
 * @param string $extra_class Optional modifier class.
 */
function rk_whatsapp_button( $product, string $extra_class = '' ): string {
	$link = rk_whatsapp_url( $product );
	if ( '' === $link ) {
		return '';
	}
	$svg = '<svg viewBox="0 0 32 32" width="18" height="18" fill="currentColor" aria-hidden="true" focusable="false"><path d="M16 3C9.4 3 4 8.4 4 15c0 2.1.6 4.2 1.6 6L4 29l8.2-1.6c1.7.9 3.7 1.4 5.8 1.4h.001C24.6 28.8 30 23.4 30 16.8 30 9.4 24.6 3 16 3zm0 23.6h-.001c-1.8 0-3.6-.5-5.1-1.4l-.4-.2-4.8 1 1-4.7-.3-.4C5.5 19 5 17 5 15c0-5.5 4.5-10 11-10s11 4.5 11 10-4.5 11.6-11 11.6zm6-8.3c-.3-.2-2-1-2.3-1.1-.3-.1-.5-.2-.8.2-.2.3-.9 1.1-1.1 1.3-.2.2-.4.2-.7.1-.3-.2-1.4-.5-2.6-1.6-1-.9-1.6-1.9-1.8-2.3-.2-.3 0-.5.1-.7.1-.1.3-.4.5-.6.1-.2.2-.3.3-.5.1-.2 0-.4 0-.6-.1-.2-.8-1.9-1.1-2.6-.3-.7-.6-.6-.8-.6h-.7c-.2 0-.6.1-.9.4-.3.3-1.2 1.2-1.2 2.9s1.2 3.4 1.4 3.6c.2.2 2.4 3.7 5.8 5.1.8.3 1.4.5 1.9.7.8.3 1.5.2 2.1.1.6-.1 2-.8 2.3-1.6.3-.8.3-1.4.2-1.6-.1-.1-.3-.2-.6-.4z"/></svg>';
	$cls = trim( 'rk-wa-btn ' . $extra_class );
	return sprintf(
		'<a class="%s" href="%s" target="_blank" rel="noopener nofollow">%s<span>%s</span></a>',
		esc_attr( $cls ),
		esc_url( $link ),
		$svg,
		esc_html__( 'Order on WhatsApp', 'toptech-machinery' )
	);
}
