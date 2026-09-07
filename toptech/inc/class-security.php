<?php
/**
 * Security hardening: headers, disclosure reduction, safe defaults.
 *
 * @package ToptechMachinery
 */

declare( strict_types = 1 );

namespace ToptechMachinery;

defined( 'ABSPATH' ) || exit;

/**
 * Applies WordPress security best practices at the theme layer.
 */
final class Security {

	public function hooks(): void {
		remove_action( 'wp_head', 'wp_generator' );
		add_filter( 'the_generator', '__return_empty_string' );
		add_filter( 'xmlrpc_enabled', '__return_false' );
		add_action( 'send_headers', array( $this, 'security_headers' ) );
		// Disable file editing from the dashboard if not already disabled.
		if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
			define( 'DISALLOW_FILE_EDIT', true );
		}
		// Remove version query strings that leak version info.
		add_filter( 'style_loader_src', array( $this, 'strip_version' ), 15 );
		add_filter( 'script_loader_src', array( $this, 'strip_version' ), 15 );
		// Anti-spam / anti-enumeration hardening.
		add_filter( 'wp_headers', array( $this, 'remove_pingback_header' ) );
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		add_filter( 'xmlrpc_methods', array( $this, 'block_pingback_methods' ) );
		add_action( 'template_redirect', array( $this, 'block_user_enumeration' ) );
		add_filter( 'rest_endpoints', array( $this, 'restrict_rest_users' ) );
		add_filter( 'login_errors', array( $this, 'generic_login_error' ) );
	}

	/**
	 * Send hardening headers. HSTS should be added at the server/HTTPS layer.
	 */
	public function security_headers(): void {
		if ( headers_sent() ) {
			return;
		}
		header( 'X-Content-Type-Options: nosniff' );
		header( 'X-Frame-Options: SAMEORIGIN' );
		header( 'Referrer-Policy: strict-origin-when-cross-origin' );
		header( 'Permissions-Policy: geolocation=(), microphone=(), camera=()' );
	}

	/**
	 * Remove core version from asset URLs (keep theme version cache-busting).
	 *
	 * @param string $src Asset URL.
	 */
	public function strip_version( $src ) {
		if ( is_string( $src ) && $src && get_bloginfo( 'version' ) && strpos( $src, 'ver=' . get_bloginfo( 'version' ) ) ) {
			$src = remove_query_arg( 'ver', $src );
		}
		return $src;
	}

	/**
	 * Drop the X-Pingback header (pingback spam vector).
	 *
	 * @param array $headers Response headers.
	 */
	public function remove_pingback_header( $headers ) {
		if ( is_array( $headers ) && isset( $headers['X-Pingback'] ) ) {
			unset( $headers['X-Pingback'] );
		}
		return $headers;
	}

	/**
	 * Remove pingback/multicall XML-RPC methods.
	 *
	 * @param array $methods Registered XML-RPC methods.
	 */
	public function block_pingback_methods( $methods ) {
		if ( is_array( $methods ) ) {
			unset( $methods['pingback.ping'], $methods['pingback.extensions.getPingbacks'], $methods['system.multicall'] );
		}
		return $methods;
	}

	/**
	 * Block ?author=N username enumeration for logged-out visitors.
	 */
	public function block_user_enumeration(): void {
		if ( is_admin() || is_user_logged_in() ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only public recon guard.
		if ( isset( $_GET['author'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$author = trim( (string) wp_unslash( $_GET['author'] ) );
			if ( '' !== $author && ctype_digit( $author ) ) {
				wp_safe_redirect( home_url( '/' ), 301 );
				exit;
			}
		}
	}

	/**
	 * Hide the REST users endpoints from logged-out requests (enumeration).
	 *
	 * @param array $endpoints REST endpoints.
	 */
	public function restrict_rest_users( $endpoints ) {
		if ( is_user_logged_in() ) {
			return $endpoints;
		}
		foreach ( array_keys( (array) $endpoints ) as $route ) {
			if ( is_string( $route ) && 0 === strpos( $route, '/wp/v2/users' ) ) {
				unset( $endpoints[ $route ] );
			}
		}
		return $endpoints;
	}

	/**
	 * Generic login error to prevent username/password disclosure.
	 */
	public function generic_login_error() {
		return esc_html__( 'Invalid login details.', 'toptech-machinery' );
	}
}
