<?php
/**
 * 404 template.
 *
 * @package RickyTools
 */

defined( 'ABSPATH' ) || exit;
get_header();

$rk_cats = array();
if ( taxonomy_exists( 'product_cat' ) ) {
	$rk_cats = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'orderby'    => 'count',
			'order'      => 'DESC',
			'number'     => 8,
		)
	);
}
?>
<main id="primary" class="site-main">
	<div class="rk-404">
		<p class="rk-404__code">404</p>
		<h1 class="rk-404__title"><?php esc_html_e( 'Page not found', 'ricky-tools' ); ?></h1>
		<p class="rk-404__text"><?php esc_html_e( 'The page you are looking for may have moved or no longer exists. Try a search, or jump straight to a product category below.', 'ricky-tools' ); ?></p>
		<div class="rk-404__search"><?php get_search_form(); ?></div>
		<div class="rk-404__actions">
			<a class="rk-btn rk-btn--primary" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Back to home', 'ricky-tools' ); ?></a>
			<?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
				<a class="rk-btn rk-btn--navy" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'Browse shop', 'ricky-tools' ); ?></a>
			<?php endif; ?>
		</div>
		<?php if ( ! is_wp_error( $rk_cats ) && ! empty( $rk_cats ) ) : ?>
			<div class="rk-404__cats">
				<h3><?php esc_html_e( 'Popular categories', 'ricky-tools' ); ?></h3>
				<ul class="rk-404__catlist">
					<?php foreach ( $rk_cats as $rk_cat ) : ?>
						<li><a href="<?php echo esc_url( get_term_link( $rk_cat ) ); ?>"><?php echo esc_html( $rk_cat->name ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();
