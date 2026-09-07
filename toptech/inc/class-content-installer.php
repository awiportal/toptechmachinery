<?php
/**
 * Auto-creates the legal / informational pages (fully editable) and builds
 * navigation menus when the theme is activated. All content uses the real
 * Ricky Tools business details and is written to satisfy Google Merchant
 * Center and standard e-commerce trust requirements.
 *
 * @package RickyTools
 */

declare( strict_types = 1 );

namespace RickyTools;

defined( 'ABSPATH' ) || exit;

/**
 * Content bootstrapper.
 */
final class Content_Installer {

	private const FLAG = 'ricky_content_installed_v1';

	public function hooks(): void {
		add_action( 'admin_init', array( $this, 'install' ) );
		add_action( 'admin_init', array( $this, 'ensure_front_page' ) );
	}

	/**
	 * Create pages + menus once.
	 */
	public function install(): void {
		if ( get_option( self::FLAG ) ) {
			return;
		}
		// Only run for a user who could have just activated the theme.
		if ( ! function_exists( 'current_user_can' ) || ! current_user_can( 'edit_theme_options' ) ) {
			return;
		}
		try {
			$ids = array();
			foreach ( $this->pages() as $slug => $page ) {
				$ids[ $slug ] = $this->upsert_page( $slug, $page['title'], $page['content'] );
			}
			$this->build_menus( $ids );
			update_option( self::FLAG, time() );
		} catch ( \Throwable $e ) {
			error_log( 'Ricky Tools content install failed: ' . $e->getMessage() );
		}
	}

	/**
	 * Create the page if a page with that slug does not already exist.
	 */
	/**
	 * Make the storefront a static homepage instead of the blog index.
	 * Runs once (own flag) so it applies even if pages were already installed.
	 */
	public function ensure_front_page(): void {
		if ( get_option( 'ricky_front_page_v1' ) ) {
			return;
		}
		if ( ! function_exists( 'current_user_can' ) || ! current_user_can( 'edit_theme_options' ) ) {
			return;
		}
		try {
			$home = get_page_by_path( 'home' );
			if ( $home instanceof \WP_Post ) {
				$home_id = (int) $home->ID;
			} else {
				$home_id = wp_insert_post(
					array(
						'post_title'   => 'Home',
						'post_name'    => 'home',
						'post_status'  => 'publish',
						'post_type'    => 'page',
						'post_content' => '',
					)
				);
			}
			if ( $home_id && ! is_wp_error( $home_id ) ) {
				update_option( 'show_on_front', 'page' );
				update_option( 'page_on_front', (int) $home_id );
			}
			update_option( 'ricky_front_page_v1', time() );
		} catch ( \Throwable $e ) {
			error_log( 'Ricky Tools front page setup failed: ' . $e->getMessage() );
		}
	}

	private function upsert_page( string $slug, string $title, string $content ): int {
		$existing = get_page_by_path( $slug );
		if ( $existing instanceof \WP_Post ) {
			return (int) $existing->ID;
		}
		$id = wp_insert_post(
			array(
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_content' => $content,
				'post_status'  => 'publish',
				'post_type'    => 'page',
			)
		);
		return is_wp_error( $id ) ? 0 : (int) $id;
	}

	/**
	 * Build primary + footer menus and assign locations.
	 *
	 * @param array<string,int> $ids Slug => page ID.
	 */
	private function build_menus( array $ids ): void {
		$defs = array(
			'primary' => array( 'about-us', 'contact-us', 'faq' ),
			'footer_service' => array( 'contact-us', 'track-order', 'faq', 'payment-methods' ),
			'footer_policies' => array( 'privacy-policy', 'terms-conditions', 'return-refund-policy', 'shipping-delivery-policy', 'warranty-policy', 'cookie-policy' ),
		);
		foreach ( $defs as $location => $slugs ) {
			$menu_name = 'Ricky ' . $location;
			$menu = wp_get_nav_menu_object( $menu_name );
			$created = $menu ? (int) $menu->term_id : wp_create_nav_menu( $menu_name );
			if ( is_wp_error( $created ) ) {
				continue;
			}
			$menu_id = (int) $created;
			if ( ! $menu_id || is_wp_error( $menu_id ) ) {
				continue;
			}
			foreach ( $slugs as $slug ) {
				if ( empty( $ids[ $slug ] ) ) {
					continue;
				}
				wp_update_nav_menu_item(
					$menu_id,
					0,
					array(
						'menu-item-object'    => 'page',
						'menu-item-object-id' => $ids[ $slug ],
						'menu-item-type'      => 'post_type',
						'menu-item-status'    => 'publish',
					)
				);
			}
			$locations = get_theme_mod( 'nav_menu_locations', array() );
			$locations[ $location ] = $menu_id;
			set_theme_mod( 'nav_menu_locations', $locations );
		}
	}

	/**
	 * The page definitions. Content is intentionally complete (not placeholder).
	 *
	 * @return array<string,array{title:string,content:string}>
	 */
	private function pages(): array {
		$name  = 'Ricky Tools';
		$phone = '0793 965654';
		$mail  = 'info@rickytools.com';
		$addr  = 'Tusky Magic Business Centre, Junction of Mfangano Lane and Ronald Ngara Street, Nairobi CBD, Kenya';

		return array(
			'about-us' => array(
				'title'   => 'About Us',
				'content' => "<h2>Who We Are</h2><p>{$name} is a Kenyan retailer of power tools, solar equipment, generators, water pumps, welding machines and general hardware. Operating from Nairobi's Central Business District, we supply genuine, warranty-backed equipment to contractors, artisans, farmers, businesses and homeowners across Kenya.</p><h2>What We Do</h2><p>We stock trusted brands including Total, Ingco, Makita, DeWalt, Honda, Solarmax and many more. Every product we sell is sourced from authorised distributors, so you can buy with confidence knowing you are getting authentic equipment at fair, clearly displayed prices.</p><h2>Why Shop With Us</h2><ul><li><strong>Genuine products:</strong> Authentic, brand-new equipment from authorised suppliers.</li><li><strong>Fair pricing:</strong> Transparent prices in Kenyan Shillings with no hidden charges.</li><li><strong>Fast delivery:</strong> Countrywide delivery, with same-day dispatch for orders placed before our daily cut-off in Nairobi.</li><li><strong>Expert support:</strong> Our team helps you choose the right tool for the job.</li><li><strong>After-sales care:</strong> Manufacturer warranties and responsive support.</li></ul><h2>Visit Us</h2><p>You are welcome at our shop: {$addr}. Call or WhatsApp us on {$phone} or email {$mail}.</p>",
			),
			'contact-us' => array(
				'title'   => 'Contact Us',
				'content' => "<h2>Get In Touch</h2><p>We are here to help with product advice, orders, delivery and after-sales support.</p><ul><li><strong>Phone / WhatsApp:</strong> {$phone}</li><li><strong>Email:</strong> {$mail}</li><li><strong>Shop Address:</strong> {$addr}</li><li><strong>Opening Hours:</strong> Monday to Saturday, 8:00am - 6:00pm. Closed on Sundays and public holidays.</li></ul><h2>Send Us a Message</h2><p>Use the form below and we will respond within one business day. For the fastest response, call or WhatsApp us during opening hours.</p>[contact-form-7 title=\"Contact form\"]<h2>Customer Support</h2><p>For questions about an existing order, please have your order number ready. For warranty or return requests, please see our Return &amp; Refund Policy and Warranty Policy pages.</p>",
			),
			'privacy-policy' => array(
				'title'   => 'Privacy Policy',
				'content' => "<p><em>Last updated: this policy is maintained by {$name}.</em></p><h2>Introduction</h2><p>{$name} (\"we\", \"us\", \"our\") respects your privacy and is committed to protecting your personal data in line with the Data Protection Act, 2019 of Kenya. This policy explains what information we collect, how we use it, and your rights.</p><h2>Information We Collect</h2><ul><li>Contact details you provide: name, email address, phone number and delivery address.</li><li>Order information: products purchased, order value and payment confirmation (we do not store full card details).</li><li>Technical data: IP address, browser type and cookies used to operate and improve the website.</li></ul><h2>How We Use Your Information</h2><ul><li>To process and deliver your orders and provide customer support.</li><li>To communicate order updates, respond to enquiries and, where you consent, send offers.</li><li>To prevent fraud and keep our website and customers secure.</li><li>To comply with our legal and tax obligations in Kenya.</li></ul><h2>Payment Security</h2><p>Payments are processed by trusted providers (including M-PESA and licensed card processors). Card and mobile-money credentials are handled directly by these providers over secure, encrypted connections; we never store your full payment credentials.</p><h2>Sharing Your Information</h2><p>We share data only with delivery partners, payment processors and service providers who help us operate, and with authorities where required by law. We never sell your personal data.</p><h2>Cookies</h2><p>We use cookies to keep your cart working, remember preferences and understand site usage. See our Cookie Policy for details and how to control them.</p><h2>Data Retention</h2><p>We keep personal data only as long as necessary to fulfil orders and meet legal obligations, after which it is securely deleted or anonymised.</p><h2>Your Rights</h2><p>You may request access to, correction of, or deletion of your personal data, and object to certain processing. Contact us at {$mail} to exercise these rights.</p><h2>Contact</h2><p>Questions about this policy: {$mail} or {$phone}, {$addr}.</p>",
			),
			'terms-conditions' => array(
				'title'   => 'Terms &amp; Conditions',
				'content' => "<h2>1. Introduction</h2><p>These Terms &amp; Conditions govern your use of the {$name} website and the purchase of products from us. By placing an order you agree to these terms.</p><h2>2. Products and Pricing</h2><p>All prices are shown in Kenyan Shillings (KSh) and include applicable taxes unless stated otherwise. We make every effort to display accurate prices, descriptions, specifications and stock availability. In the rare event of a pricing or description error, we reserve the right to cancel the affected order and issue a full refund.</p><h2>3. Orders</h2><p>An order is confirmed once payment is received or, for cash-on-delivery orders, once we confirm the order by phone. We reserve the right to refuse or cancel any order suspected of fraud.</p><h2>4. Payment</h2><p>We accept M-PESA, Visa, Mastercard and cash on delivery where available. See our Payment Methods page.</p><h2>5. Delivery</h2><p>Delivery times and charges are set out in our Shipping &amp; Delivery Policy. Risk in the goods passes to you on delivery.</p><h2>6. Returns and Warranty</h2><p>Your rights to return goods and claim warranty are described in our Return &amp; Refund Policy and Warranty Policy, which form part of these terms.</p><h2>7. Intellectual Property</h2><p>All content on this website, including logos, text and images, is owned by or licensed to {$name} and may not be copied without permission.</p><h2>8. Limitation of Liability</h2><p>To the extent permitted by Kenyan law, our liability for any claim is limited to the value of the product purchased. Tools must be used strictly in accordance with the manufacturer's instructions and appropriate safety precautions.</p><h2>9. Governing Law</h2><p>These terms are governed by the laws of Kenya and disputes are subject to the jurisdiction of the Kenyan courts.</p><h2>10. Contact</h2><p>{$name}, {$addr}. {$phone} / {$mail}.</p>",
			),
			'shipping-delivery-policy' => array(
				'title'   => 'Shipping &amp; Delivery Policy',
				'content' => "<h2>Delivery Coverage</h2><p>{$name} delivers countrywide across Kenya. We dispatch from our Nairobi CBD shop.</p><h2>Delivery Times</h2><ul><li><strong>Nairobi:</strong> Same-day or next-day delivery for orders confirmed before 3:00pm on business days.</li><li><strong>Major towns:</strong> 1-3 business days via courier or parcel service.</li><li><strong>Remote areas:</strong> 2-5 business days, delivered to the nearest courier pick-up point where door delivery is unavailable.</li></ul><h2>Delivery Charges</h2><p>Delivery fees are calculated at checkout based on your location, order size and weight. Charges are shown clearly before you pay. Selected promotions may include free delivery.</p><h2>Order Processing</h2><p>Orders are processed on business days (Monday to Saturday, excluding public holidays). You will receive confirmation by phone, WhatsApp or email once your order is dispatched.</p><h2>Bulky and Heavy Items</h2><p>Large items such as generators, welding machines and solar panels may require specialised transport; our team will confirm timing and any additional handling charge before dispatch.</p><h2>Collection</h2><p>You may also collect your order in person from {$addr} during opening hours. Please wait for confirmation that your order is ready before travelling.</p><h2>Tracking and Support</h2><p>For delivery updates, contact us on {$phone} or {$mail} with your order number.</p>",
			),
			'return-refund-policy' => array(
				'title'   => 'Return &amp; Refund Policy',
				'content' => "<h2>Our Commitment</h2><p>Your satisfaction matters to us. If something is wrong with your order, we will make it right in line with this policy and your rights under Kenyan consumer law.</p><h2>Return Window</h2><p>You may request a return within <strong>7 days</strong> of delivery for most products, provided the item is unused, in its original packaging and complete with all accessories and documentation.</p><h2>Items Eligible for Return</h2><ul><li>Products that arrive damaged, defective or not as described.</li><li>Incorrect items sent in error.</li></ul><h2>Items Not Eligible</h2><ul><li>Items that have been used, installed or altered (except where faulty).</li><li>Consumables and clearance items marked non-returnable.</li><li>Products damaged by misuse, accident or failure to follow instructions.</li></ul><h2>How to Request a Return</h2><p>Contact us on {$phone} or {$mail} with your order number and photos of the item. Our team will confirm eligibility and arrange collection or return.</p><h2>Refunds</h2><p>Approved refunds are processed within <strong>3-7 business days</strong> to your original payment method (M-PESA, card or mobile money). Where a replacement is preferred, we will ship it once the returned item is received and inspected.</p><h2>Return Shipping</h2><p>If the return is due to our error or a defective product, we cover return shipping. For other returns, return shipping is the customer's responsibility.</p><h2>Faulty Products</h2><p>Products with a manufacturer defect may also be covered under our Warranty Policy.</p><h2>Contact</h2><p>{$name}, {$addr}. {$phone} / {$mail}.</p>",
			),
			'warranty-policy' => array(
				'title'   => 'Warranty Policy',
				'content' => "<h2>Manufacturer Warranty</h2><p>Products sold by {$name} are covered by the manufacturer's warranty where applicable. Warranty periods vary by brand and product type and are stated on the product page or accompanying documentation.</p><h2>What the Warranty Covers</h2><p>Warranty covers defects in materials and workmanship under normal use. If a covered product fails, we will facilitate repair, replacement or, where those are not possible, a refund in line with the manufacturer's terms.</p><h2>What the Warranty Does Not Cover</h2><ul><li>Normal wear and tear and consumable parts.</li><li>Damage from misuse, overloading, accidents, unauthorised repair or failure to follow the manufacturer's instructions.</li><li>Damage from incorrect power supply or environmental conditions.</li></ul><h2>How to Make a Warranty Claim</h2><p>Contact us on {$phone} or {$mail} with your order number, proof of purchase and a description of the fault. Keep the original packaging and accessories where possible. Our team will guide you through the claim and, where needed, coordinate with the manufacturer's service centre.</p><h2>Proof of Purchase</h2><p>A valid receipt or order confirmation is required for all warranty claims. Please retain yours.</p><h2>Contact</h2><p>{$name}, {$addr}. {$phone} / {$mail}.</p>",
			),
			'payment-methods' => array(
				'title'   => 'Payment Methods',
				'content' => "<h2>How You Can Pay</h2><p>{$name} offers secure, convenient payment options for customers in Kenya:</p><ul><li><strong>M-PESA:</strong> Fast, secure mobile payment.</li><li><strong>Visa &amp; Mastercard:</strong> Debit and credit cards processed over encrypted, secure connections.</li><li><strong>Cash on Delivery:</strong> Available for eligible locations and order values.</li></ul><h2>Secure Payments</h2><p>All online payments are processed over secure HTTPS connections by licensed payment providers. {$name} does not store your full card or mobile-money credentials.</p><h2>Pricing and Currency</h2><p>All prices are displayed in Kenyan Shillings (KSh) and are consistent between product pages, cart and checkout. Any delivery charges are shown clearly before you confirm payment.</p><h2>Need Help?</h2><p>For payment questions, contact us on {$phone} or {$mail}.</p>",
			),
			'cookie-policy' => array(
				'title'   => 'Cookie Policy',
				'content' => "<h2>What Are Cookies</h2><p>Cookies are small text files stored on your device that help websites function and remember your preferences.</p><h2>How We Use Cookies</h2><ul><li><strong>Essential cookies:</strong> Keep your shopping cart and checkout working.</li><li><strong>Preference cookies:</strong> Remember choices such as recently viewed products.</li><li><strong>Analytics cookies:</strong> Help us understand how visitors use the site so we can improve it.</li></ul><h2>Managing Cookies</h2><p>You can control or delete cookies through your browser settings. Disabling essential cookies may prevent parts of the website, such as the cart and checkout, from working correctly.</p><h2>Contact</h2><p>Questions about cookies: {$mail}.</p>",
			),
			'faq' => array(
				'title'   => 'Frequently Asked Questions',
				'content' => "<h2>Ordering</h2><p><strong>How do I place an order?</strong> Browse or search for a product, add it to your cart and complete checkout. You can also order by calling or WhatsApp on {$phone}.</p><p><strong>Are your products genuine?</strong> Yes. We source only authentic products from authorised distributors, backed by manufacturer warranties.</p><h2>Payment</h2><p><strong>What payment methods do you accept?</strong> M-PESA, Visa, Mastercard and cash on delivery where available. See our Payment Methods page.</p><h2>Delivery</h2><p><strong>Do you deliver countrywide?</strong> Yes, we deliver across Kenya. Nairobi orders can be delivered same or next day. See our Shipping &amp; Delivery Policy.</p><p><strong>Can I collect my order?</strong> Yes, from {$addr} during opening hours once your order is confirmed ready.</p><h2>Returns &amp; Warranty</h2><p><strong>What if my item is faulty?</strong> Contact us within 7 days for a return, or make a warranty claim. See our Return &amp; Refund Policy and Warranty Policy.</p><h2>Support</h2><p><strong>How do I reach you?</strong> Call or WhatsApp {$phone}, email {$mail}, or visit our shop in Nairobi CBD, Monday to Saturday, 8:00am - 6:00pm.</p>",
			),
			'track-order' => array(
				'title'   => 'Track Order',
				'content' => "<h2>Track Your Order</h2><p>To check the status of your order, please have your order number ready and contact our support team on {$phone} (call or WhatsApp) or email {$mail}. We will confirm dispatch and expected delivery time.</p><p>If your WooCommerce order-tracking plugin is active, the tracking form will appear below.</p>[woocommerce_order_tracking]",
			),
		);
	}
}
