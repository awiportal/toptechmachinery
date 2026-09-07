<?php
/**
 * Site header: top bar, logo + search + actions, primary nav, sticky.
 *
 * @package ToptechMachinery
 */

defined( 'ABSPATH' ) || exit;

$rk_phone    = get_theme_mod( 'toptech_phone', '0719 261277' );
$rk_email    = get_theme_mod( 'toptech_email', 'info@toptechmachinery.co.ke' );
$rk_hours    = get_theme_mod( 'toptech_hours', 'Mon-Sat 8:00am - 6:00pm' );
$rk_whatsapp = get_theme_mod( 'toptech_whatsapp', '254719261277' );
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="sr-only" href="#primary"><?php esc_html_e( 'Skip to content', 'toptech-machinery' ); ?></a>

<header class="rk-header">
	<div class="rk-header__top">
		<div class="container">
			<div class="rk-header__hours"><?php echo esc_html( $rk_hours ); ?></div>
			<div class="rk-header__contact">
				<a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $rk_phone ) ); ?>"><?php echo esc_html( __( 'Call: ', 'toptech-machinery' ) . $rk_phone ); ?></a>
				<a href="https://wa.me/<?php echo esc_attr( $rk_whatsapp ); ?>" rel="noopener" target="_blank">WhatsApp</a>
				<a href="mailto:<?php echo esc_attr( $rk_email ); ?>"><?php echo esc_html( $rk_email ); ?></a>
			</div>
		</div>
	</div>

	<div class="rk-header__mid">
		<div class="container">
			<button class="rk-nav-toggle" aria-expanded="false" aria-controls="rk-primary-menu" aria-label="<?php esc_attr_e( 'Menu', 'toptech-machinery' ); ?>"><span class="rk-burger"></span></button>
			<div class="rk-logo">
				<?php
				if ( function_exists( 'the_custom_logo' ) && has_custom_logo() ) {
					the_custom_logo();
				} else {
					printf( '<a href="%s" style="color:#fff;font-weight:800;font-size:1.4rem">%s</a>', esc_url( home_url( '/' ) ), esc_html( get_bloginfo( 'name' ) ) );
				}
				?>
			</div>

			<div class="rk-search">
				<?php get_search_form(); ?>
				<div class="rk-search__panel" role="listbox" aria-label="<?php esc_attr_e( 'Search suggestions', 'toptech-machinery' ); ?>"></div>
			</div>

			<div class="rk-actions">
				<?php
				$rk_has_wc    = function_exists( 'wc_get_page_permalink' );
				$rk_account   = $rk_has_wc ? wc_get_page_permalink( 'myaccount' ) : home_url( '/' );
				$rk_cart_url  = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/' );
				$rk_cart_ct   = ( function_exists( 'WC' ) && WC() && WC()->cart ) ? WC()->cart->get_cart_contents_count() : 0;
				?>
				<a class="rk-actions__item" href="<?php echo esc_url( $rk_account ); ?>">
					<?php echo rk_icon( 'user' ); // phpcs:ignore ?>
					<span><?php esc_html_e( 'Account', 'toptech-machinery' ); ?></span>
				</a>
				<a class="rk-actions__item" href="<?php echo esc_url( home_url( '/wishlist/' ) ); ?>">
					<?php echo rk_icon( 'heart' ); // phpcs:ignore ?>
					<span><?php esc_html_e( 'Wishlist', 'toptech-machinery' ); ?></span>
				</a>
				<?php if ( $rk_has_wc ) : ?>
				<a class="rk-actions__item" href="<?php echo esc_url( $rk_cart_url ); ?>" data-rk-drawer-open>
					<?php echo rk_icon( 'cart' ); // phpcs:ignore ?>
					<span class="rk-cart-count" data-count="<?php echo esc_attr( $rk_cart_ct ); ?>"><?php echo esc_html( $rk_cart_ct ); ?></span>
					<span><?php esc_html_e( 'Cart', 'toptech-machinery' ); ?></span>
				</a>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<nav class="rk-nav" aria-label="<?php esc_attr_e( 'Primary', 'toptech-machinery' ); ?>">
		<div class="container">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => false,
					'menu_id'        => 'rk-primary-menu',
					'fallback_cb'    => false,
					'depth'          => 2,
				)
			);
			?>
		</div>
	</nav>
	<nav class="rk-mobile" id="rk-mobile" aria-label="Shop by category" aria-hidden="true">
		<div class="rk-mobile__head">
			<span><?php esc_html_e( 'Shop by Category', 'toptech-machinery' ); ?></span>
			<button type="button" class="rk-mobile__close" data-rk-mob-close aria-label="<?php esc_attr_e( 'Close menu', 'toptech-machinery' ); ?>">&times;</button>
		</div>
		<ul class="rk-mobile__cats">
			<?php
			if ( taxonomy_exists( 'product_cat' ) ) {
				$rk_cats = function_exists( 'rk_cached_terms' ) ? rk_cached_terms( 'product_cat', 40 ) : array();
				if ( ! is_wp_error( $rk_cats ) ) {
					foreach ( $rk_cats as $rk_c ) {
						printf(
							'<li><a href="%1$s"><span>%2$s</span><span class="rk-mobile__count">%3$d</span></a></li>',
							esc_url( get_term_link( $rk_c ) ),
							esc_html( $rk_c->name ),
							(int) $rk_c->count
						);
					}
				}
			}
			?>
		</ul>
		<?php
		if ( has_nav_menu( 'primary' ) ) {
			echo '<div class="rk-mobile__links">';
			wp_nav_menu( array( 'theme_location' => 'primary', 'container' => false, 'menu_class' => 'rk-mobile__toplinks', 'fallback_cb' => false, 'depth' => 1 ) );
			echo '</div>';
		}
		?>
	</nav>
	<div class="rk-mobile__overlay" data-rk-mob-close></div>
</header>
<div id="content" class="site-content">
