<?php
/**
 * Theme Customizer: brand colours + contact/support details.
 *
 * @package ToptechMachinery
 */

declare( strict_types = 1 );

namespace ToptechMachinery;

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
		$wp_customize->add_panel( 'toptech_panel', array( 'title' => __( 'TopTech Machinery', 'toptech-machinery' ), 'priority' => 20 ) );

		// Colours.
		$wp_customize->add_section( 'toptech_colors', array( 'title' => __( 'Brand Colours', 'toptech-machinery' ), 'panel' => 'toptech_panel' ) );
		$this->color( $wp_customize, 'toptech_primary', '#005EB8', __( 'Primary (Blue)', 'toptech-machinery' ) );
		$this->color( $wp_customize, 'toptech_navy', '#0B1E3F', __( 'Secondary (Dark Blue)', 'toptech-machinery' ) );

		// Contact + support.
		$wp_customize->add_section( 'toptech_contact', array( 'title' => __( 'Contact & Support', 'toptech-machinery' ), 'panel' => 'toptech_panel' ) );
		$this->text( $wp_customize, 'toptech_phone', '0797 720290', __( 'Phone / WhatsApp', 'toptech-machinery' ) );
		$this->text( $wp_customize, 'toptech_email', 'info@toptechmachinery.co.ke', __( 'Email', 'toptech-machinery' ) );
		$this->text( $wp_customize, 'toptech_hours', 'Mon-Sat 8:00am - 6:00pm', __( 'Support Hours', 'toptech-machinery' ) );
		$this->text( $wp_customize, 'toptech_address', 'This & That Exhibition, Opp. Ronald Ngala Post Office, Shop G15, Ronald Ngala Street, Nairobi, Kenya', __( 'Business Address', 'toptech-machinery' ) );
		$this->text( $wp_customize, 'toptech_whatsapp', '254797720290', __( 'WhatsApp number (intl, no +)', 'toptech-machinery' ) );
	}

	private function color( $wp, string $id, string $default, string $label ): void {
		$wp->add_setting( $id, array( 'default' => $default, 'sanitize_callback' => 'sanitize_hex_color', 'transport' => 'postMessage' ) );
		$wp->add_control( new \WP_Customize_Color_Control( $wp, $id, array( 'label' => $label, 'section' => 'toptech_colors' ) ) );
	}

	private function text( $wp, string $id, string $default, string $label ): void {
		$wp->add_setting( $id, array( 'default' => $default, 'sanitize_callback' => 'sanitize_text_field' ) );
		$wp->add_control( $id, array( 'label' => $label, 'section' => 'toptech_contact', 'type' => 'text' ) );
	}

	/**
	 * Print brand colours as CSS custom properties.
	 */
	public function output_css_vars(): void {
		$yellow = sanitize_hex_color( (string) get_theme_mod( 'toptech_primary', '#005EB8' ) );
		$navy   = sanitize_hex_color( (string) get_theme_mod( 'toptech_navy', '#0B1E3F' ) );
		printf(
			'<style id="toptech-brand">:root{--rk-primary:%s;--rk-navy:%s}</style>' . "\n",
			esc_html( $yellow ),
			esc_html( $navy )
		);
	}
}
