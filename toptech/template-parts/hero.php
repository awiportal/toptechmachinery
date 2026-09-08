<?php
/**
 * Hero: vertical category menu + slider.
 *
 * @package ToptechMachinery
 */

defined( 'ABSPATH' ) || exit;
?>
<section class="rk-hero">
	<div class="container">
		<aside class="rk-vertcat" aria-label="<?php esc_attr_e( 'Shop by category', 'toptech-machinery' ); ?>">
			<h2><?php esc_html_e( 'All Categories', 'toptech-machinery' ); ?></h2>
			<?php
			if ( has_nav_menu( 'vertical_cats' ) ) {
				wp_nav_menu( array( 'theme_location' => 'vertical_cats', 'container' => false, 'fallback_cb' => false, 'depth' => 1 ) );
			} else {
				$terms = function_exists( 'rk_cached_terms' ) ? rk_cached_terms( 'product_cat', 0 ) : array();
				if ( $terms ) {
					echo '<ul>';
					foreach ( $terms as $t ) {
						printf( '<li><a href="%s">%s <span>%d</span></a></li>', esc_url( get_term_link( $t ) ), esc_html( $t->name ), (int) $t->count );
					}
					echo '</ul>';
				}
			}
			?>
		</aside>

		<div class="rk-slider" tabindex="0" aria-roledescription="carousel" aria-label="<?php esc_attr_e( 'Promotions', 'toptech-machinery' ); ?>">
			<?php
			$slides  = get_theme_mod( 'toptech_slides', array() );
			$rk_shop = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
			if ( empty( $slides ) || ! is_array( $slides ) ) {
				$slides = array(
					array( 'img' => TOPTECH_URI . 'assets/img/banner-tools.jpg', 'title' => __( 'Power Tools & Hardware', 'toptech-machinery' ), 'text' => __( 'Genuine brands. Fair prices. Fast countrywide delivery.', 'toptech-machinery' ), 'url' => $rk_shop ),
					array( 'img' => TOPTECH_URI . 'assets/img/banner-solar.jpg', 'title' => __( 'Solar Solutions', 'toptech-machinery' ), 'text' => __( 'Panels, inverters, batteries & street lights in stock.', 'toptech-machinery' ), 'url' => $rk_shop ),
				);
			}
			foreach ( $slides as $i => $s ) {
				$img_attr = 0 === $i
					? 'width="1200" height="500" fetchpriority="high" decoding="async" loading="eager"'
					: 'width="1200" height="500" loading="lazy" decoding="async"';
				printf(
					'<div class="rk-slide%1$s"><img src="%2$s" alt="%3$s" %4$s><div class="rk-slide__promo"><h2>%3$s</h2><p>%5$s</p><a class="rk-btn rk-btn--primary" href="%6$s">%7$s</a></div></div>',
					0 === $i ? ' is-active' : '',
					esc_url( $s['img'] ),
					esc_attr( $s['title'] ),
					$img_attr, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static attribute string.
					esc_html( $s['text'] ),
					esc_url( $s['url'] ),
					esc_html__( 'Shop Now', 'toptech-machinery' )
				);
			}
			?>
			<button class="rk-slider__arrow rk-slider__arrow--prev" aria-label="<?php esc_attr_e( 'Previous slide', 'toptech-machinery' ); ?>">&#8249;</button>
			<button class="rk-slider__arrow rk-slider__arrow--next" aria-label="<?php esc_attr_e( 'Next slide', 'toptech-machinery' ); ?>">&#8250;</button>
			<div class="rk-slider__dots"></div>
		</div>
	</div>
</section>
