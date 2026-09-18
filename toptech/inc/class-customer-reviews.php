<?php
/**
 * Google Customer Reviews: opt-in survey and seller-rating badge.
 *
 * Two separate integrations from Merchant Center 5850531731:
 *
 * 1. Opt-in (required). Rendered on the WooCommerce order-received page. Google
 *    shows the buyer a survey invitation; if they accept, Google emails them a
 *    survey after the estimated delivery date and the response feeds seller
 *    ratings. This is the only mechanism that actually collects reviews, and the
 *    store currently has none across the whole catalogue.
 *
 * 2. Badge (optional). Displays programme participation and the seller rating.
 *    Until ratings exist it renders "no rating available", which is honest but
 *    not flattering on a new store.
 *
 * Notes on the choices here:
 * - position is BOTTOM_LEFT. The theme already floats a WhatsApp button and a
 *   back-to-top control bottom-right, so the badge would collide there.
 * - the optional "products" gtin array is omitted. The GTIN/UPC/EAN column is
 *   empty for every product in this catalogue, so there is nothing to send.
 * - config is emitted with wp_json_encode rather than string interpolation, so
 *   order numbers and email addresses cannot break out of the JSON.
 * - the opt-in is NOT gated behind the cookie banner. The survey invitation is
 *   itself an explicit opt-in, which is the consent step, and gating it would
 *   stop review collection entirely.
 *
 * @package ToptechMachinery
 */

declare( strict_types = 1 );

namespace ToptechMachinery;

defined( 'ABSPATH' ) || exit;

/**
 * Wires the Google Customer Reviews opt-in and badge.
 */
final class Customer_Reviews {

	private const MERCHANT_ID = 5850531731;

	/**
	 * Days after the order date to quote as estimated delivery. The Shipping &
	 * Delivery Policy tops out at "2 to 5 working days" for outlying areas, so
	 * 5 is the conservative end of what the site already promises.
	 */
	private const DELIVERY_DAYS = 5;

	public function hooks(): void {
		add_action( 'woocommerce_thankyou', array( $this, 'opt_in' ), 20 );
		add_action( 'wp_footer', array( $this, 'badge' ), 30 );
	}

	/**
	 * Render the survey opt-in on the order confirmation page.
	 *
	 * @param int $order_id WooCommerce order ID.
	 */
	public function opt_in( $order_id ): void {
		if ( function_exists( 'wc_get_order' ) === false ) {
			return;
		}
		$order = wc_get_order( $order_id );
		if ( $order instanceof \WC_Order === false ) {
			return;
		}

		$email = (string) $order->get_billing_email();
		if ( '' === $email ) {
			return;
		}

		$country = (string) $order->get_shipping_country();
		if ( '' === $country ) {
			$country = (string) $order->get_billing_country();
		}
		if ( '' === $country ) {
			$country = 'KE';
		}

		$created = $order->get_date_created();
		$base    = ( null === $created ) ? time() : (int) $created->getTimestamp();
		$eta     = gmdate( 'Y-m-d', $base + ( self::DELIVERY_DAYS * DAY_IN_SECONDS ) );

		$config = array(
			'merchant_id'             => self::MERCHANT_ID,
			'order_id'                => (string) $order->get_order_number(),
			'email'                   => $email,
			'delivery_country'        => $country,
			'estimated_delivery_date' => $eta,
		);

		echo '<script src="https://apis.google.com/js/platform.js?onload=renderOptIn" async defer></script>' . "\n";
		echo '<script id="toptech-gcr-optin">window.renderOptIn=function(){window.gapi.load("surveyoptin",function(){window.gapi.surveyoptin.render('
			. wp_json_encode( $config )
			. ');});};</script>' . "\n";
	}

	/**
	 * Render the seller-rating badge site-wide.
	 */
	public function badge(): void {
		if ( is_admin() ) {
			return;
		}
		$config = array(
			'merchant_id' => self::MERCHANT_ID,
			'position'    => 'BOTTOM_LEFT',
		);
		echo '<script id="merchantWidgetScript" src="https://www.gstatic.com/shopping/merchant/merchantwidget.js" defer></script>' . "\n";
		echo '<script id="toptech-gcr-badge">var s=document.getElementById("merchantWidgetScript");if(s){s.addEventListener("load",function(){merchantwidget.start('
			. wp_json_encode( $config )
			. ');});}</script>' . "\n";
	}
}
