<?php
/**
 * Theme setup: supports, menus, image sizes, i18n.
 *
 * @package ToptechMachinery
 */

declare( strict_types = 1 );

namespace ToptechMachinery;

defined( 'ABSPATH' ) || exit;

/**
 * Registers core theme supports and navigation.
 */
final class Setup {

	/**
	 * Register hooks.
	 */
	public function hooks(): void {
		add_action( 'after_setup_theme', array( $this, 'theme_supports' ) );
		add_action( 'after_setup_theme', array( $this, 'register_menus' ) );
		add_action( 'after_setup_theme', array( $this, 'image_sizes' ) );
		add_action( 'widgets_init', array( $this, 'register_sidebars' ) );
	}

	/**
	 * Declare theme feature supports.
	 */
	public function theme_supports(): void {
		load_theme_textdomain( 'toptech-machinery', TOPTECH_DIR . 'languages' );

		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'customize-selective-refresh-widgets' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'align-wide' );
		add_theme_support(
			'html5',
			array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' )
		);
		add_theme_support(
			'custom-logo',
			array(
				'height'      => 60,
				'width'       => 220,
				'flex-height' => true,
				'flex-width'  => true,
			)
		);
	}

	/**
	 * Register navigation menus used across the header/footer.
	 */
	public function register_menus(): void {
		register_nav_menus(
			array(
				'primary'        => __( 'Primary Navigation', 'toptech-machinery' ),
				'vertical_cats'  => __( 'Hero Vertical Categories', 'toptech-machinery' ),
				'footer_company' => __( 'Footer: Company', 'toptech-machinery' ),
				'footer_service' => __( 'Footer: Customer Service', 'toptech-machinery' ),
				'footer_policies'=> __( 'Footer: Policies', 'toptech-machinery' ),
			)
		);
	}

	/**
	 * Register 1:1 product image size for uniform cards + hero.
	 */
	public function image_sizes(): void {
		add_image_size( 'toptech-card', 600, 600, true );
		add_image_size( 'toptech-hero', 1200, 500, true );
		add_image_size( 'toptech-cat', 480, 360, true );
	}

	/**
	 * Register footer + shop sidebar widget areas.
	 */
	public function register_sidebars(): void {
		$defaults = array(
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h3 class="widget__title">',
			'after_title'   => '</h3>',
		);
		register_sidebar( array_merge( $defaults, array( 'name' => __( 'Shop Sidebar', 'toptech-machinery' ), 'id' => 'shop-sidebar' ) ) );
		foreach ( array( 1, 2, 3, 4 ) as $i ) {
			register_sidebar( array_merge( $defaults, array(
				'name' => sprintf( __( 'Footer Column %d', 'toptech-machinery' ), $i ),
				'id'   => 'footer-' . $i,
			) ) );
		}
	}
}
