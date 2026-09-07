<?php
/**
 * Product-aware search form.
 *
 * @package RickyTools
 */

defined( 'ABSPATH' ) || exit;
$rk_id = 'rk-search-' . wp_unique_id();
?>
<form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="sr-only" for="<?php echo esc_attr( $rk_id ); ?>"><?php esc_html_e( 'Search products', 'ricky-tools' ); ?></label>
	<input type="search" id="<?php echo esc_attr( $rk_id ); ?>" name="s" value="<?php echo esc_attr( get_search_query() ); ?>"
		placeholder="<?php esc_attr_e( 'Search products, categories, brands, SKU...', 'ricky-tools' ); ?>"
		autocomplete="off" aria-controls="rk-search-panel">
	<?php if ( class_exists( 'WooCommerce' ) ) : ?>
		<input type="hidden" name="post_type" value="product">
	<?php endif; ?>
	<button type="submit"><?php esc_html_e( 'Search', 'ricky-tools' ); ?></button>
</form>
