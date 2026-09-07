<?php
/**
 * Theme Customizer: brand colours + contact/support details.
 *
 * @package RickyTools
 */

declare( strict_types = 1 );

namespace RickyTools;

defined( 'ABSPATH' ) || exit;

/**
 * Registers Customizer settings and outputs brand CSS variables.
 */
final class Customizer {

	public function hooks(): void {
		add_action( 'customize_register', array( $this, 'register' ) );
		add_action( 'wp_head', array( $this, 'output_css_vars' ), 20 );
	}

	/**
	 * @param \WP_Customize_Manager $wp_customize Customizer manager.
	 */
	public function register( $wp_customize ): void {
		$wp_customize->add_panel( 'ricky_panel', array( 'title' => __( 'Ricky Tools', 'ricky-tools' ), 'priority' => 20 ) );

		// Colours.
		$wp_customize->add_section( 'ricky_colors', array( 'title' => __( 'Brand Colours', 'ricky-tools' ), 'panel' => 'ricky_panel' ) );
		$this->color( $wp_customize, 'ricky_yellow', '#FDB913', __( 'Primary (Yellow)', 'ricky-tools' ) );
		$this->color( $wp_customize, 'ricky_navy', '#0B1E3F', __( 'Secondary (Dark Blue)', 'ricky-tools' ) );

		// Contact + support.
		$wp_customize->add_section( 'ricky_contact', array( 'title' => __( 'Contact & Support', 'ricky-tools' ), 'panel' => 'ricky_panel' ) );
		$this->text( $wp_customize, 'ricky_phone', '0793 965654', __( 'Phone / WhatsApp', 'ricky-tools' ) );
		$this->text( $wp_customize, 'ricky_email', 'info@rickytools.com', __( 'Email', 'ricky-tools' ) );
		$this->text( $wp_customize, 'ricky_hours', 'Mon-Sat 8:00am - 6:00pm', __( 'Support Hours', 'ricky-tools' ) );
		$this->text( $wp_customize, 'ricky_address', 'Tusky Magic Business Centre, Junction of Mfangano Lane and Ronald Ngara Street, Nairobi CBD', __( 'Business Address', 'ricky-tools' ) );
		$this->text( $wp_customize, 'ricky_whatsapp', '254793965654', __( 'WhatsApp number (intl, no +)', 'ricky-tools' ) );
	}

	private function color( $wp, string $id, string $default, string $label ): void {
		$wp->add_setting( $id, array( 'default' => $default, 'sanitize_callback' => 'sanitize_hex_color', 'transport' => 'postMessage' ) );
		$wp->add_control( new \WP_Customize_Color_Control( $wp, $id, array( 'label' => $label, 'section' => 'ricky_colors' ) ) );
	}

	private function text( $wp, string $id, string $default, string $label ): void {
		$wp->add_setting( $id, array( 'default' => $default, 'sanitize_callback' => 'sanitize_text_field' ) );
		$wp->add_control( $id, array( 'label' => $label, 'section' => 'ricky_contact', 'type' => 'text' ) );
	}

	/**
	 * Print brand colours as CSS custom properties.
	 */
	public function output_css_vars(): void {
		$yellow = sanitize_hex_color( (string) get_theme_mod( 'ricky_yellow', '#FDB913' ) );
		$navy   = sanitize_hex_color( (string) get_theme_mod( 'ricky_navy', '#0B1E3F' ) );
		printf(
			'<style id="ricky-brand">:root{--rk-yellow:%s;--rk-navy:%s}</style>' . "\n",
			esc_html( $yellow ),
			esc_html( $navy )
		);
	}
}
