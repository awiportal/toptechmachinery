<?php
/**
 * Fires Google Ads / Analytics events when a visitor starts a phone or
 * WhatsApp contact.
 *
 * The shop sells through a real mix of channels: some customers complete
 * checkout on the site, many browse and then call or message to order. Only
 * the website purchase was measurable, so Performance Max had no way to see
 * the phone and WhatsApp half of the business and was optimising toward a
 * signal that represents part of it.
 *
 * This emits two named events so they appear in Google Ads under detected
 * events, where each can be promoted to a conversion action:
 *
 *   contact_phone_click     - any tel: link (header, footer, contact page, PDP)
 *   contact_whatsapp_click  - any wa.me / WhatsApp link, including the float
 *                             button and the per-product enquiry button
 *
 * Implementation notes:
 * - one delegated listener on the document, so every present and future link
 *   is covered without editing each template
 * - no gtag of its own. It rides the tag Google for WooCommerce already loads
 *   (AW-18452419534), and does nothing if that tag is absent
 * - consent aware by inheritance: the Cookie_Consent module denies ad_storage
 *   until the visitor accepts, so these events are modelled rather than
 *   cookie-attributed for anyone who declines. That is the intended behaviour
 *   under the Data Protection Act, 2019, not a fault to debug later
 * - page-cache safe: the markup is identical for every visitor and carries no
 *   per-user or per-order data
 *
 * A click is intent, not a sale. These should start life as Secondary
 * conversions in Google Ads so they inform reporting without distorting a
 * Maximize conversion value strategy, and only be promoted once there is
 * enough volume to judge how often a contact becomes an order.
 *
 * @package ToptechMachinery
 */

declare( strict_types = 1 );

namespace ToptechMachinery;

defined( 'ABSPATH' ) || exit;

/**
 * Emits contact-intent events for phone and WhatsApp clicks.
 */
final class Conversion_Events {

	public function hooks(): void {
		add_action( 'wp_footer', array( $this, 'tracker' ), 40 );
	}

	/**
	 * Delegated click tracking for tel: and WhatsApp links.
	 */
	public function tracker(): void {
		if ( is_admin() ) {
			return;
		}
		echo '<script id="toptech-conv-events">';
		echo <<<'JS'
( function () {
function send( name, href ) {
if ( typeof window.gtag === 'function' ) {
window.gtag( 'event', name, { link_url: href, page_path: location.pathname } );
}
}
document.addEventListener( 'click', function ( e ) {
var t = e.target;
if ( t === null || typeof t.closest === 'undefined' ) { return; }
var a = t.closest( 'a[href]' );
if ( a === null ) { return; }
var href = a.getAttribute( 'href' ) || '';
var low = href.toLowerCase();
if ( low.indexOf( 'tel:' ) === 0 ) {
send( 'contact_phone_click', href );
return;
}
if ( low.indexOf( 'wa.me' ) > -1 || low.indexOf( 'whatsapp.com' ) > -1 || low.indexOf( 'api.whatsapp' ) > -1 ) {
send( 'contact_whatsapp_click', href );
}
}, true );
}() );
JS;
		echo '</script>' . "\n";
	}
}
