<?php
/**
 * Featured categories - horizontal scroller (6 on desktop, 2 on mobile, scroll for the rest).
 *
 * @package ToptechMachinery
 */

defined( 'ABSPATH' ) || exit;

if ( ! taxonomy_exists( 'product_cat' ) ) {
	return;
}
$cats = function_exists( 'rk_cached_terms' ) ? rk_cached_terms( 'product_cat', 30 ) : array();
if ( empty( $cats ) ) {
	return;
}
?>
<section class="rk-section">
	<div class="container">
		<div class="rk-section__head"><h2><?php esc_html_e( 'Shop by Category', 'toptech-machinery' ); ?></h2></div>
		<div class="rk-catscroll at-start">
			<button type="button" class="rk-catscroll__arrow rk-catscroll__arrow--prev" aria-label="<?php esc_attr_e( 'Scroll left', 'toptech-machinery' ); ?>">&#8249;</button>
			<div class="rk-catgrid" role="list">
				<?php
				foreach ( $cats as $cat ) {
					$thumb_id = (int) get_term_meta( $cat->term_id, 'thumbnail_id', true );
					$img      = $thumb_id ? wp_get_attachment_image( $thumb_id, 'toptech-cat', false, array( 'loading' => 'lazy', 'alt' => $cat->name ) ) : wc_placeholder_img( 'toptech-cat' );
					printf(
						'<a class="rk-catcard" role="listitem" href="%s">%s<h3>%s</h3><small>%s</small></a>',
						esc_url( get_term_link( $cat ) ),
						$img, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						esc_html( $cat->name ),
						esc_html( sprintf( _n( '%d product', '%d products', $cat->count, 'toptech-machinery' ), $cat->count ) )
					);
				}
				?>
			</div>
			<button type="button" class="rk-catscroll__arrow rk-catscroll__arrow--next" aria-label="<?php esc_attr_e( 'Scroll right', 'toptech-machinery' ); ?>">&#8250;</button>
		</div>
	</div>
</section>
