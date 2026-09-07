<?php
/**
 * Required + recommended plugins via TGM Plugin Activation.
 *
 * Deliberately LEAN: installing many plugins in one "install all" request
 * times out on shared hosting. We prompt only the essentials; users can add
 * SEO / caching / invoicing plugins later from Plugins > Add New.
 *
 * @package RickyTools
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

$ricky_tgmpa = RICKY_DIR . 'inc/tgmpa/class-tgm-plugin-activation.php';
if ( is_readable( $ricky_tgmpa ) ) {
	require $ricky_tgmpa;
}

add_action(
	'tgmpa_register',
	static function (): void {
		if ( ! function_exists( 'tgmpa' ) ) {
			return;
		}

		$plugins = array(
			array( 'name' => 'WooCommerce', 'slug' => 'woocommerce', 'required' => true ),
			array( 'name' => 'Perfect Brands for WooCommerce', 'slug' => 'perfect-woocommerce-brands', 'required' => true ),
			array( 'name' => 'One Click Demo Import', 'slug' => 'one-click-demo-import', 'required' => true ),
			array( 'name' => 'Contact Form 7', 'slug' => 'contact-form-7', 'required' => false ),
			array( 'name' => 'Elementor', 'slug' => 'elementor', 'required' => false ),
		);

		$config = array(
			'id'           => 'ricky-tools',
			'menu'         => 'ricky-install-plugins',
			'parent_slug'  => 'themes.php',
			'capability'   => 'edit_theme_options',
			'has_notices'  => true,
			'dismissable'  => true,
			'is_automatic' => false,
		);

		tgmpa( $plugins, $config );
	}
);
