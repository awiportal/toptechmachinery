<?php
/**
 * Google Analytics 4 and Google Tag Manager.
 *
 * The shop carried no analytics at all: only the Google Ads conversion tag
 * (AW-18431692376) appeared on any page, while the cookie banner and Cookie
 * Policy both told visitors that Google Analytics cookies were in use. This
 * module makes that statement true and gives the shop measurement to sit
 * behind its ad spend.
 *
 * Ordering is deliberate. Cookie_Consent registers an unscoped consent default
 * at wp_head priority 1 and defines window.dataLayer and gtag() there. A
 * consent default is only honoured before the first measurement call, so
 * everything here runs after it, at priorities 2 and 3, and never redefines
 * gtag(). GA4 therefore inherits analytics_storage: denied until the visitor
 * accepts, and is modelled rather than cookie-attributed for anyone who
 * declines, per the Data Protection Act, 2019.
 *
 * Double-counting warning: GA4 is configured here directly via gtag. The GTM
 * container is loaded for tag management but must NOT also contain a GA4
 * Configuration tag for this same measurement ID, or every hit counts twice.
 * Add other tags to GTM freely; leave GA4 to this module.
 *
 * The inline JavaScript deliberately avoids the logical-negation operator and
 * uses strict equality with inverted branches instead. The deployment
 * toolchain escapes that character, which silently corrupts emitted JS.
 *
 * Page-cache safe: identical markup for every visitor, no per-user or
 * per-order data, so LiteSpeed can serve it from cache.
 *
 * @package ToptechMachinery
 */

declare( strict_types = 1 );

namespace ToptechMachinery;

defined( 'ABSPATH' ) || exit;

/**
 * Loads GA4 and the Google Tag Manager container.
 */
final class Analytics {

	/** GA4 measurement ID. Stream ID 15809086207. */
	private const GA4_ID = 'G-80DN7N0BXT';

	/** Google Tag Manager container ID. */
	private const GTM_ID = 'GTM-TJKKRVCV';

	public function hooks(): void {
		// Priorities 2 and 3 must follow Cookie_Consent's consent default at 1.
		add_action( 'wp_head', array( $this, 'gtm_head' ), 2 );
		add_action( 'wp_head', array( $this, 'ga4_head' ), 3 );
		add_action( 'wp_body_open', array( $this, 'gtm_noscript' ), 1 );
	}

	/** Google Tag Manager container loader. */
	public function gtm_head(): void {
		if ( is_admin() ) {
			return;
		}
		$gtm = esc_js( self::GTM_ID );
		echo '<script id="toptech-gtm">';
		echo "(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});";
		echo "var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=(l==='dataLayer')?'':'&l='+l;";
		echo "j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;";
		echo "f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','" . $gtm . "');";
		echo '</script>' . "\n";
	}

	/**
	 * GA4 tag. Does not redefine gtag(); Cookie_Consent already did, ahead of
	 * this, together with the denied-by-default consent state. The ||
	 * fallback only applies if that module is ever removed.
	 */
	public function ga4_head(): void {
		if ( is_admin() ) {
			return;
		}
		$ga4 = esc_js( self::GA4_ID );
		echo '<script async src="https://www.googletagmanager.com/gtag/js?id=' . esc_attr( self::GA4_ID ) . '"></script>' . "\n";
		echo '<script id="toptech-ga4">';
		echo 'window.dataLayer = window.dataLayer || [];';
		echo 'window.gtag = window.gtag || function(){ window.dataLayer.push( arguments ); };';
		echo "gtag( 'js', new Date() );";
		echo "gtag( 'config', '" . $ga4 . "' );";
		echo '</script>' . "\n";
	}

	/** Google Tag Manager noscript fallback, immediately after the body tag. */
	public function gtm_noscript(): void {
		if ( is_admin() ) {
			return;
		}
		echo '<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=' . esc_attr( self::GTM_ID ) . '" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>' . "\n";
	}
}
