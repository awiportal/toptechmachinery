<?php
/**
 * Default page template (used by About, Policies, Contact, etc.).
 *
 * @package ToptechMachinery
 */

defined( 'ABSPATH' ) || exit;
get_header();
?>
<main id="primary" class="site-main container" style="padding:32px 20px;max-width:900px">
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<article <?php post_class( 'rk-page' ); ?>>
			<h1 class="page-title"><?php the_title(); ?></h1>
			<div class="rk-page__content"><?php the_content(); ?></div>
		</article>
		<?php
	endwhile;
	?>
</main>
<?php
get_footer();
