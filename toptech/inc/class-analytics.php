<?php
/**
 * Google Analytics 4.
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
 * everything here runs after it, at priority 3, and never redefines gtag().
 * GA4 therefore inherits analytics_storage: denied until the visitor accepts,
 * and is modelled rather than cookie-attributed for anyone who declines, per
 * the Data Protection Act, 2019.
 *
 * Google Tag Manager was removed on 24 Sep 2026. Container GTM-TJKKRVCV was
 * loaded on every page of the site, in the head and again as a noscript iframe
 * after the body tag, and the container was empty: its only published version
 * was literally named "Empty Container", with zero tags, zero triggers and
 * zero variables. Every visitor paid for a third-party request, plus the
 * container script, to run nothing.
 *
 * Removing it also retires the double-counting hazard this file used to warn
 * about. GA4 is configured here directly via gtag, so a GA4 Configuration tag
 * added to that container for the same measurement ID would have counted every
 * hit twice -- a mistake that is easy to make and hard to notice, and one that
 * corrupts the conversion data a Maximize conversion value bid strategy feeds
 * on. With no container on the page, the hazard cannot recur by accident.
 *
 * If tag management is wanted later, re-add the container loader and keep GA4
 * here: put other vendors' tags in GTM and leave this measurement ID alone.
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
 * Loads GA4.
 */
final class Analytics {

	/** GA4 measurement ID. Stream ID 15809086207, property 555053091. */
	private const GA4_ID = 'G-80DN7N0BXT';

	public function hooks(): void {
		// Priority 3 must follow Cookie_Consent's consent default at 1.
		add_action( 'wp_head', array( $this, 'ga4_head' ), 3 );
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
}
