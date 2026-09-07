<?php
/**
 * Site footer.
 *
 * @package ToptechMachinery
 */

defined( 'ABSPATH' ) || exit;

$rk_phone    = get_theme_mod( 'toptech_phone', '0719 261277' );
$rk_email    = get_theme_mod( 'toptech_email', 'info@toptechmachinery.co.ke' );
$rk_address  = get_theme_mod( 'toptech_address', 'Royal Palms Mall, Shop No. BG 55, Nairobi, Kenya' );
$rk_whatsapp = get_theme_mod( 'toptech_whatsapp', '254719261277' );
?>
</div><!-- #content -->
<footer class="rk-footer">
	<div class="container">
		<div class="rk-footer__cols">
			<div>
				<h3><?php bloginfo( 'name' ); ?></h3>
				<p><?php esc_html_e( 'Your trusted supplier of power tools, solar, and hardware in Kenya. Genuine brands, fair prices, fast delivery countrywide.', 'toptech-machinery' ); ?></p>
				<p><strong><?php esc_html_e( 'Address:', 'toptech-machinery' ); ?></strong><br><?php echo esc_html( $rk_address ); ?></p>
				<p><a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $rk_phone ) ); ?>"><?php echo esc_html( $rk_phone ); ?></a> &middot; <a href="mailto:<?php echo esc_attr( $rk_email ); ?>"><?php echo esc_html( $rk_email ); ?></a></p>
			</div>
			<div>
				<h3><?php esc_html_e( 'Customer Service', 'toptech-machinery' ); ?></h3>
				<?php wp_nav_menu( array( 'theme_location' => 'footer_service', 'container' => false, 'fallback_cb' => false, 'depth' => 1 ) ); ?>
			</div>
			<div>
				<h3><?php esc_html_e( 'Policies', 'toptech-machinery' ); ?></h3>
				<?php wp_nav_menu( array( 'theme_location' => 'footer_policies', 'container' => false, 'fallback_cb' => false, 'depth' => 1 ) ); ?>
			</div>
			<div>
				<h3><?php esc_html_e( 'We Accept', 'toptech-machinery' ); ?></h3>
				<div class="rk-payments">
					<span>M-PESA</span><span>Visa</span><span>Mastercard</span><span>Cash on Delivery</span>
				</div>
				<h3 style="margin-top:18px"><?php esc_html_e( 'Secure Shopping', 'toptech-machinery' ); ?></h3>
				<div class="rk-payments"><span>SSL Secured</span><span>Verified Business</span></div>
			</div>
		</div>
	</div>
	<div class="rk-footer__bar">
		<div class="container">
			&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>. <?php esc_html_e( 'All rights reserved.', 'toptech-machinery' ); ?>
		</div>
	</div>
</footer>

<a class="rk-whatsapp" href="https://wa.me/<?php echo esc_attr( $rk_whatsapp ); ?>" target="_blank" rel="noopener" aria-label="<?php esc_attr_e( 'Chat on WhatsApp', 'toptech-machinery' ); ?>">
	<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15l-1.4 5 5.1-1.3A10 10 0 1 0 12 2Zm5.3 14.1c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .2-3.3-.7-2.8-1.1-4.5-3.9-4.7-4.1-.1-.2-1-1.4-1-2.6s.6-1.8.9-2.1c.2-.2.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 1.9c.1.2.1.4 0 .5l-.4.5c-.2.2-.3.4-.1.6.2.4.9 1.4 1.9 2.3 1.3 1.1 2.3 1.4 2.5 1.5.2.1.4.1.6-.1l.7-.9c.2-.3.4-.2.6-.1l1.8.9c.2.1.4.2.5.3.1.3.1.7-.1 1.3Z"/></svg>
</a>
<button class="rk-backtop" aria-label="<?php esc_attr_e( 'Back to top', 'toptech-machinery' ); ?>">&uarr;</button>

<?php if ( function_exists( 'woocommerce_mini_cart' ) ) : ?>
<div class="rk-drawer" aria-hidden="true">
	<div class="rk-drawer__overlay" data-rk-drawer-close></div>
	<aside class="rk-drawer__panel" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Shopping cart', 'toptech-machinery' ); ?>">
		<div class="rk-drawer__head">
			<h3><?php esc_html_e( 'Your Cart', 'toptech-machinery' ); ?></h3>
			<button type="button" class="rk-drawer__close" data-rk-drawer-close aria-label="<?php esc_attr_e( 'Close cart', 'toptech-machinery' ); ?>">&times;</button>
		</div>
		<div class="rk-drawer__body widget_shopping_cart_content"><?php woocommerce_mini_cart(); ?></div>
		<div class="rk-drawer__spin" aria-hidden="true"><span class="rk-spinner"></span></div>
	</aside>
</div>
<?php endif; ?>

<?php wp_footer(); ?>
</body>
</html>
