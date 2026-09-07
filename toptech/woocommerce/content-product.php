<?php
/**
 * Uniform product card (homepage rows + archives).
 * Overrides woocommerce/templates/content-product.php.
 *
 * @package ToptechMachinery
 */

defined( 'ABSPATH' ) || exit;

global $product;
if ( empty( $product ) || ! $product->is_visible() ) {
	return;
}
?>
<li <?php wc_product_class( 'rk-card', $product ); ?>>
	<div class="rk-card__badges"><?php do_action( 'woocommerce_before_shop_loop_item_title' ); ?></div>

	<div class="rk-card__media">
		<a href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr( $product->get_name() ); ?>">
			<?php echo $product->get_image( 'toptech-card', array( 'loading' => 'lazy', 'decoding' => 'async' ) ); // phpcs:ignore ?>
		</a>
		<div class="rk-card__actions">
			<button type="button" class="rk-icon-btn rk-wishlist" data-id="<?php echo esc_attr( $product->get_id() ); ?>" aria-label="<?php esc_attr_e( 'Add to wishlist', 'toptech-machinery' ); ?>">&#9825;</button>
			<a class="rk-icon-btn rk-quickview" href="<?php the_permalink(); ?>" aria-label="<?php esc_attr_e( 'Quick view', 'toptech-machinery' ); ?>">&#128065;</a>
		</div>
	</div>

	<a href="<?php the_permalink(); ?>" class="rk-card__title"><?php echo esc_html( $product->get_name() ); ?></a>

	<?php if ( $product->get_short_description() ) : ?>
		<p class="rk-card__excerpt"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $product->get_short_description() ), 22 ) ); ?></p>
	<?php endif; ?>

	<?php if ( wc_review_ratings_enabled() && $product->get_rating_count() ) : ?>
		<?php echo wc_get_rating_html( $product->get_average_rating(), $product->get_rating_count() ); // phpcs:ignore ?>
	<?php endif; ?>

	<span class="rk-stock <?php echo $product->is_in_stock() ? 'rk-stock--in' : 'rk-stock--out'; ?>">
		<?php echo $product->is_in_stock() ? esc_html__( 'In stock', 'toptech-machinery' ) : esc_html__( 'Out of stock', 'toptech-machinery' ); ?>
	</span>

	<div class="rk-card__price"><?php echo $product->get_price_html(); // phpcs:ignore ?></div>

	<?php if ( $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock() ) : ?>
		<button type="button" class="rk-btn rk-btn--primary rk-btn--block" data-toptech-add="<?php echo esc_attr( $product->get_id() ); ?>">
			<?php esc_html_e( 'Add to Cart', 'toptech-machinery' ); ?>
		</button>
	<?php else : ?>
		<a href="<?php the_permalink(); ?>" class="rk-btn rk-btn--navy rk-btn--block"><?php esc_html_e( 'View Product', 'toptech-machinery' ); ?></a>
	<?php endif; ?>

	<?php echo function_exists( 'rk_whatsapp_button' ) ? rk_whatsapp_button( $product ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper ?>
</li>
