<?php
/**
 * A single homepage product row: 6 products from one category + "View More".
 *
 * @package RickyTools
 */

defined( 'ABSPATH' ) || exit;

$slug = (string) get_query_var( 'rk_cat_slug' );
if ( '' === $slug || ! taxonomy_exists( 'product_cat' ) ) {
	return;
}
$term = get_term_by( 'slug', $slug, 'product_cat' );
if ( ! $term ) {
	return;
}

$q = new WP_Query(
	array(
		'post_type'      => 'product',
		'posts_per_page' => 6,
		'no_found_rows'  => true,
		'post_status'    => 'publish',
		'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery
			array( 'taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => $slug ),
		),
	)
);
if ( ! $q->have_posts() ) {
	wp_reset_postdata();
	return;
}
?>
<section class="rk-section rk-section--products">
	<div class="container">
		<div class="rk-section__head">
			<h2><?php echo esc_html( $term->name ); ?></h2>
			<a class="rk-viewmore" href="<?php echo esc_url( get_term_link( $term ) ); ?>"><?php esc_html_e( 'View more', 'ricky-tools' ); ?> &rarr;</a>
		</div>
		<div class="rk-products">
			<?php
			while ( $q->have_posts() ) {
				$q->the_post();
				wc_get_template_part( 'content', 'product' );
			}
			?>
		</div>
	</div>
</section>
<?php
wp_reset_postdata();
