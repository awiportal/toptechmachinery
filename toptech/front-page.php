<?php
/**
 * Homepage: hero + vertical categories, featured categories, per-category product rows.
 *
 * @package RickyTools
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="primary" class="site-main">

	<?php get_template_part( 'template-parts/hero' ); ?>

	<?php get_template_part( 'template-parts/trust-band' ); ?>

	<?php get_template_part( 'template-parts/featured-categories' ); ?>

	<?php
	/**
	 * Product rows, one per featured category. Category slugs can be changed in the
	 * Customizer or by editing this array. Defaults reflect the imported catalogue.
	 */
	$rk_sections = apply_filters(
		'ricky_homepage_categories',
		array( 'water-pumps', 'power-tools', 'solar-panels', 'welding-machines', 'generators', 'batteries' )
	);
	foreach ( $rk_sections as $rk_slug ) {
		set_query_var( 'rk_cat_slug', $rk_slug );
		get_template_part( 'template-parts/product-section' );
	}
	?>

</main>
<?php
get_footer();
