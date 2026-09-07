<?php
/**
 * One-Click Demo Import integration.
 *
 * Registers the bundled demo content with the "One Click Demo Import" (OCDI)
 * plugin so that, after the required plugins are installed, the user gets a
 * single "Import Demo Data" button that loads pages, menus, product
 * categories, brands and sample products.
 *
 * @package RickyTools
 */

declare( strict_types = 1 );

namespace RickyTools;

defined( 'ABSPATH' ) || exit;

/**
 * Hooks the theme demo package into OCDI.
 */
final class Demo_Import {

	public function hooks(): void {
		add_filter( 'ocdi/import_files', array( $this, 'import_files' ) );
		add_filter( 'pt-ocdi/import_files', array( $this, 'import_files' ) );
		add_action( 'ocdi/after_import', array( $this, 'after_import' ) );
		add_action( 'pt-ocdi/after_import', array( $this, 'after_import' ) );
		// Skip OCDI branding / intro screens are left default.
		add_filter( 'ocdi/plugin_page_setup', array( $this, 'page_setup' ) );
		add_filter( 'pt-ocdi/plugin_page_setup', array( $this, 'page_setup' ) );
	}

	/**
	 * Register the demo package(s).
	 *
	 * @return array<int,array<string,string>>
	 */
	public function import_files(): array {
		return array(
			array(
				'import_file_name'       => __( 'Ricky Tools - Full Demo', 'ricky-tools' ),
				'local_import_file'      => RICKY_DIR . 'demo/content.xml',
				'import_preview_image_url' => RICKY_URI . 'assets/img/logo.webp',
				'import_notice'          => __( 'Installs demo pages, menus, product categories, brands and sample products. After importing, use Products > Import to load your full product CSV.', 'ricky-tools' ),
				'preview_url'            => home_url( '/' ),
			),
		);
	}

	/**
	 * Present the importer under Appearance so it is easy to find.
	 *
	 * @param array<string,mixed> $default OCDI page config.
	 * @return array<string,mixed>
	 */
	public function page_setup( array $default ): array {
		$default['parent_slug'] = 'themes.php';
		$default['page_title']  = __( 'Ricky Tools Demo Import', 'ricky-tools' );
		$default['menu_title']  = __( 'Import Demo Data', 'ricky-tools' );
		$default['capability']  = 'import';
		$default['menu_slug']   = 'ricky-demo-import';
		return $default;
	}

	/**
	 * After a demo import: assign menus to locations and set the front page.
	 */
	public function after_import(): void {
		// Assign primary menu if one was imported.
		$primary = get_term_by( 'name', 'Primary', 'nav_menu' );
		if ( ! $primary ) {
			$primary = get_term_by( 'name', 'Ricky primary', 'nav_menu' );
		}
		$locations = get_theme_mod( 'nav_menu_locations', array() );
		if ( $primary ) {
			$locations['primary'] = (int) $primary->term_id;
		}
		$cats = get_term_by( 'name', 'Categories', 'nav_menu' );
		if ( $cats ) {
			$locations['vertical_cats'] = (int) $cats->term_id;
		}
		set_theme_mod( 'nav_menu_locations', $locations );

		// Ensure the site shows the theme homepage.
		$front = get_page_by_path( 'home' );
		if ( $front instanceof \WP_Post ) {
			update_option( 'show_on_front', 'page' );
			update_option( 'page_on_front', (int) $front->ID );
		}

		// Build a vertical categories menu from the imported product categories.
		if ( taxonomy_exists( 'product_cat' ) ) {
			$menu_name = 'Categories';
			$menu      = wp_get_nav_menu_object( $menu_name );
			$menu_id   = $menu ? (int) $menu->term_id : wp_create_nav_menu( $menu_name );
			if ( ! is_wp_error( $menu_id ) && $menu_id ) {
				$menu_id = (int) $menu_id;
				// Clear any existing items so re-importing does not duplicate the menu.
				$existing_items = wp_get_nav_menu_items( $menu_id );
				if ( is_array( $existing_items ) ) {
					foreach ( $existing_items as $existing_item ) {
						wp_delete_post( (int) $existing_item->ID, true );
					}
				}
				$terms   = get_terms( array( 'taxonomy' => 'product_cat', 'orderby' => 'count', 'order' => 'DESC', 'number' => 12, 'hide_empty' => false ) );
				if ( ! is_wp_error( $terms ) ) {
					foreach ( $terms as $t ) {
						wp_update_nav_menu_item(
							$menu_id,
							0,
							array(
								'menu-item-title'     => $t->name,
								'menu-item-url'       => get_term_link( $t ),
								'menu-item-status'    => 'publish',
								'menu-item-type'      => 'taxonomy',
								'menu-item-object'    => 'product_cat',
								'menu-item-object-id' => (int) $t->term_id,
							)
						);
					}
				}
				$locations                  = get_theme_mod( 'nav_menu_locations', array() );
				$locations['vertical_cats'] = $menu_id;
				set_theme_mod( 'nav_menu_locations', $locations );
			}
		}

		if ( function_exists( 'flush_rewrite_rules' ) ) {
			flush_rewrite_rules();
		}
	}
}
