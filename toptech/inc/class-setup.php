<?php
/**
 * Theme setup: supports, menus, image sizes, i18n.
 *
 * @package RickyTools
 */

declare( strict_types = 1 );

namespace RickyTools;

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
		load_theme_textdomain( 'ricky-tools', RICKY_DIR . 'languages' );

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
				'primary'        => __( 'Primary Navigation', 'ricky-tools' ),
				'vertical_cats'  => __( 'Hero Vertical Categories', 'ricky-tools' ),
				'footer_company' => __( 'Footer: Company', 'ricky-tools' ),
				'footer_service' => __( 'Footer: Customer Service', 'ricky-tools' ),
				'footer_policies'=> __( 'Footer: Policies', 'ricky-tools' ),
			)
		);
	}

	/**
	 * Register 1:1 product image size for uniform cards + hero.
	 */
	public function image_sizes(): void {
		add_image_size( 'ricky-card', 600, 600, true );
		add_image_size( 'ricky-hero', 1200, 500, true );
		add_image_size( 'ricky-cat', 480, 360, true );
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
		register_sidebar( array_merge( $defaults, array( 'name' => __( 'Shop Sidebar', 'ricky-tools' ), 'id' => 'shop-sidebar' ) ) );
		foreach ( array( 1, 2, 3, 4 ) as $i ) {
			register_sidebar( array_merge( $defaults, array(
				'name' => sprintf( __( 'Footer Column %d', 'ricky-tools' ), $i ),
				'id'   => 'footer-' . $i,
			) ) );
		}
	}
}
