<?php
/**
 * Generic fallback template (blog/archive).
 *
 * @package RickyTools
 */

defined( 'ABSPATH' ) || exit;
get_header();
?>
<main id="primary" class="site-main container" style="padding:32px 20px">
	<?php if ( have_posts() ) : ?>
		<?php if ( is_home() && ! is_front_page() ) : ?>
			<h1 class="page-title"><?php single_post_title(); ?></h1>
		<?php endif; ?>
		<div class="rk-postlist">
			<?php
			while ( have_posts() ) :
				the_post();
				?>
				<article <?php post_class( 'rk-post' ); ?>>
					<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
					<div class="rk-post__meta"><?php echo esc_html( get_the_date() ); ?></div>
					<div class="rk-post__excerpt"><?php the_excerpt(); ?></div>
				</article>
				<?php
			endwhile;
			the_posts_pagination();
		else :
			?>
			<p><?php esc_html_e( 'Nothing found.', 'ricky-tools' ); ?></p>
		<?php endif; ?>
</main>
<?php
get_footer();
