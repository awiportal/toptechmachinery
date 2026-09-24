<?php
/**
 * Auto-creates the legal / informational pages (fully editable) and builds
 * navigation menus when the theme is activated. All content uses the real
 * TopTech Machinery business details and is written to satisfy Google Merchant
 * Center and standard e-commerce trust requirements.
 *
 * @package ToptechMachinery
 */

declare( strict_types = 1 );

namespace ToptechMachinery;

defined( 'ABSPATH' ) || exit;

/**
 * Content bootstrapper.
 */
final class Content_Installer {

	private const FLAG = 'toptech_content_installed_v1';
	private const MENU_FLAG = 'toptech_menus_v2';
	private const CAT_IMG_FLAG = 'toptech_cat_images_v1';

	public function hooks(): void {
		add_action( 'admin_init', array( $this, 'install' ) );
		add_action( 'admin_init', array( $this, 'ensure_front_page' ) );
		add_action( 'admin_init', array( $this, 'sync_menus' ) );
		add_action( 'admin_init', array( $this, 'sync_category_images' ) );
		add_action( 'admin_init', array( $this, 'refresh_contact_details' ) );
		add_action( 'admin_init', array( $this, 'refresh_pages_content' ) );
		add_action( 'admin_init', array( $this, 'seed_contact_defaults' ) );
		add_action( 'admin_init', array( $this, 'fix_site_title' ) );
		add_action( 'admin_init', array( $this, 'cleanup_competitor_brand' ) );
		add_action( 'admin_init', array( $this, 'reclassify_dewalt_welders' ) );
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
			error_log( 'TopTech Machinery content install failed: ' . $e->getMessage() );
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
		if ( get_option( 'toptech_front_page_v1' ) ) {
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
			update_option( 'toptech_front_page_v1', time() );
		} catch ( \Throwable $e ) {
			error_log( 'TopTech Machinery front page setup failed: ' . $e->getMessage() );
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
	 * Re-sync theme navigation menus once per version so existing sites pick up
	 * menu changes idempotently, without duplicating items.
	 */
	public function sync_menus(): void {
		if ( get_option( self::MENU_FLAG ) ) {
			return;
		}
		if ( function_exists( 'current_user_can' ) === false || current_user_can( 'edit_theme_options' ) === false ) {
			return;
		}
		try {
			$slugs = array(
				'about-us', 'contact-us', 'payment-methods', 'return-refund-policy',
				'shipping-delivery-policy', 'track-order', 'faq', 'privacy-policy',
				'terms-conditions', 'warranty-policy', 'cookie-policy',
			);
			$ids = array();
			foreach ( $slugs as $slug ) {
				$page = get_page_by_path( $slug );
				if ( $page instanceof \WP_Post ) {
					$ids[ $slug ] = (int) $page->ID;
				}
			}
			$this->build_menus( $ids );
			update_option( self::MENU_FLAG, time() );
		} catch ( \Throwable $e ) {
			error_log( 'TopTech Machinery menu sync failed: ' . $e->getMessage() );
		}
	}

	/**
	 * Build primary + footer menus and assign locations. Idempotent: the menu
	 * assigned to each location is cleared and rebuilt from the definitions,
	 * so the method can be re-run safely.
	 *
	 * @param array<string,int> $ids Slug => page ID.
	 */
	private function build_menus( array $ids ): void {
		$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );

		$defs = array(
			'primary'         => array(
				array( 'custom', 'Home', home_url( '/' ) ),
				array( 'page', 'about-us' ),
				array( 'custom', 'Shop', $shop_url ),
				array( 'page', 'return-refund-policy' ),
				array( 'page', 'shipping-delivery-policy' ),
				array( 'page', 'payment-methods' ),
				array( 'page', 'contact-us' ),
			),
			'footer_service'  => array(
				array( 'page', 'contact-us' ),
				array( 'page', 'track-order' ),
				array( 'page', 'faq' ),
				array( 'page', 'payment-methods' ),
			),
			'footer_policies' => array(
				array( 'page', 'privacy-policy' ),
				array( 'page', 'terms-conditions' ),
				array( 'page', 'return-refund-policy' ),
				array( 'page', 'shipping-delivery-policy' ),
				array( 'page', 'warranty-policy' ),
				array( 'page', 'cookie-policy' ),
			),
		);

		$assigned = function_exists( 'get_nav_menu_locations' ) ? get_nav_menu_locations() : array();

		foreach ( $defs as $location => $items ) {
			$menu_id = 0;
			if ( isset( $assigned[ $location ] ) && $assigned[ $location ] ) {
				$obj = wp_get_nav_menu_object( (int) $assigned[ $location ] );
				if ( $obj ) {
					$menu_id = (int) $obj->term_id;
				}
			}
			if ( $menu_id < 1 ) {
				$menu_name = 'TopTech ' . $location;
				$menu      = wp_get_nav_menu_object( $menu_name );
				$created   = $menu ? (int) $menu->term_id : wp_create_nav_menu( $menu_name );
				if ( is_wp_error( $created ) ) {
					continue;
				}
				$menu_id = (int) $created;
			}
			if ( $menu_id < 1 ) {
				continue;
			}

			$existing = wp_get_nav_menu_items( $menu_id, array( 'post_status' => 'any' ) );
			if ( is_array( $existing ) ) {
				foreach ( $existing as $item ) {
					wp_delete_post( (int) $item->ID, true );
				}
			}

			foreach ( $items as $item ) {
				if ( 'custom' === $item[0] ) {
					wp_update_nav_menu_item(
						$menu_id,
						0,
						array(
							'menu-item-title'  => $item[1],
							'menu-item-url'    => $item[2],
							'menu-item-type'   => 'custom',
							'menu-item-status' => 'publish',
						)
					);
					continue;
				}
				$slug = $item[1];
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

			$locations              = get_theme_mod( 'nav_menu_locations', array() );
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
        $name  = 'TopTech Machinery';
        $phone = '0797 720290';
        $mail  = 'info@toptechmachinery.co.ke';
        $addr  = 'This & That Exhibition, Opp. Ronald Ngala Post Office, Shop G15, Ronald Ngala Street, Nairobi, Kenya';
        $upd   = '<p><em>Last updated: 18 September 2026</em></p>';

        return array(
            'about-us' => array(
                'title'   => 'About Us',
                'content' => "<p>{$name} is a Nairobi-based supplier of power tools, solar equipment, generators, water pumps, welding machines and general hardware. We serve contractors, fundis, farmers, small businesses and homeowners, and we deliver countrywide across Kenya.</p><h2>What we sell</h2><p>We stock well-known brands such as Total, Ingco, Makita, Bosch, Honda and Solarmax, alongside dependable value options. Everything we carry is sourced from authorised distributors, so the item you buy is genuine and covered by the manufacturer's warranty.</p><h2>How we work</h2><p><strong>Clear pricing.</strong> All prices are shown in Kenya Shillings (KSh) and include VAT where it applies. There are no hidden fees.</p><p><strong>Fast dispatch.</strong> Orders confirmed before 3:00pm on a working day are dispatched the same day. Delivery then takes 1 to 5 working days depending on your location.</p><p><strong>Expert support.</strong> If you are not sure which tool or machine suits the job, call or WhatsApp us and we will help you decide.</p><h2>Visit or contact us</h2><p><strong>Shop:</strong> {$addr}</p><p><strong>Phone and WhatsApp:</strong> {$phone}<br><strong>Email:</strong> {$mail}</p><p><strong>Opening hours:</strong> Monday to Saturday, 8:00am to 6:00pm. Closed on Sundays and public holidays.</p><h2>Business information</h2><p>{$name} is a trading name of Interglobe Enterprises, Nairobi, Kenya. You can reach us at {$addr}, on {$phone}, or by email at {$mail}.</p>",
            ),
            'contact-us' => array(
                'title'   => 'Contact Us',
                'content' => "<p>You can reach {$name} by phone, WhatsApp, email or in person at our Nairobi shop. We answer most calls and messages the same day during opening hours.</p><h2>Phone and WhatsApp</h2><p>Call or message us on {$phone}. WhatsApp is usually the quickest way to send a photo of what you need or to place an order.</p><h2>Email</h2><p>Write to us at {$mail}. Please include your order number if your message is about an order you have already placed.</p><h2>Our shop</h2><p>{$addr}</p><p>Open Monday to Saturday, 8:00am to 6:00pm. Closed on Sundays and public holidays.</p><h2>How to reach us</h2><p>The quickest way to reach us is a call or WhatsApp on {$phone}. You can also email us and we will reply within one working day.</p><p class=\"rk-contact-actions\"><a class=\"rk-btn rk-btn--primary\" href=\"tel:+254797720290\">Call {$phone}</a> <a class=\"rk-btn rk-btn--primary\" href=\"https://wa.me/254797720290\">WhatsApp us</a> <a class=\"rk-btn rk-btn--ghost\" href=\"mailto:{$mail}\">Email us</a></p><h2>Business information</h2><p>{$name} is a trading name of Interglobe Enterprises, Nairobi, Kenya.</p>",
            ),
            'privacy-policy' => array(
                'title'   => 'Privacy Policy',
                'content' => "{$upd}<p>This policy explains how {$name} collects and uses your personal information when you shop with us or use this website. We handle personal data in line with the Data Protection Act, 2019 and are guided by the Office of the Data Protection Commissioner.</p><h2>1. Information we collect</h2><p>When you place an order, create an account or get in touch, we collect your name, phone number, email address and delivery address, together with the details of what you bought. As you use the website we also collect basic technical information such as your IP address, device and browser type and the pages you visit, mostly through cookies.</p><h2>2. How we use your information</h2><p>We use it to process and deliver your orders, send order status and tracking updates, answer your questions and warranty requests, and meet our tax and record-keeping obligations in Kenya. We only send offers or marketing messages if you have asked to receive them, and you can opt out at any time.</p><h2>3. Payments</h2><p>Online orders are paid by M-PESA or by cash on delivery. You enter your M-PESA details directly with Safaricom over an encrypted connection, and we never see or store your M-PESA PIN. We do not process card payments online. Visa and Mastercard are accepted in person at our Nairobi shop, where your card is read by the bank's own terminal, and we do not see or store your full card number or PIN.</p><h2>4. Who we share it with</h2><p>We share your details only with the partners who help us complete your order, such as delivery and courier companies and our payment processors, and with the authorities where the law requires it. We do not sell or rent your personal information.</p><h2>5. How long we keep it</h2><p>We keep your information for as long as we need it to complete your order and satisfy legal, tax and accounting requirements, then we delete or anonymise it.</p><h2>6. Your rights</h2><p>Under the Data Protection Act, 2019 you can ask to see the information we hold about you, have it corrected, or have it deleted, and you can object to us using it in certain ways. Email {$mail} and we will respond.</p><h2>7. Cookies</h2><p>This website uses cookies to keep your cart and checkout working and to help us improve the site. Our Cookie Policy explains what we use and how to manage them.</p><h2>8. Contact</h2><p>{$name}, {$addr}. {$phone} / {$mail}.</p>",
            ),
            'terms-conditions' => array(
                'title'   => 'Terms &amp; Conditions',
                'content' => "{$upd}<h2>1. About these terms</h2><p>These terms apply when you buy from {$name}, whether on this website, by phone or on WhatsApp. Placing an order means you accept them.</p><h2>2. Products, prices and stock</h2><p>Prices are in Kenya Shillings and include VAT where it applies. We work hard to keep prices, descriptions, photographs and stock levels accurate, but mistakes do happen. If we find an error in the price or description of something you have ordered, we will contact you and, if you prefer, cancel the order and refund you in full.</p><h2>3. Placing an order</h2><p>Your order is confirmed once we have received payment or, for cash on delivery, once we have confirmed it with you by phone or WhatsApp before dispatch. We may decline or cancel an order if we cannot verify it or if we suspect fraud.</p><h2>4. Payment</h2><p>Online orders are paid by M-PESA, or by cash on delivery where it is available. Visa and Mastercard are accepted in person at our Nairobi shop only, not for online orders. There is more detail on our Payment Methods page.</p><h2>5. Delivery</h2><p>Orders confirmed before 3:00pm on a working day are dispatched the same day, and delivery takes 1 to 5 working days depending on your location. Full delivery times and charges are set out in our Shipping &amp; Delivery Policy. Goods become your responsibility once they are handed to you or to someone acting for you.</p><h2>6. Returns, exchanges and warranty</h2><p>You may request a return within 14 days of delivery, on both faulty and non-faulty items, and we accept exchanges. Our Return &amp; Refund Policy and Warranty Policy explain how to return an item or make a warranty claim, and they form part of these terms.</p><h2>7. Using the equipment safely</h2><p>Power tools and machinery must be used according to the manufacturer's instructions and with the right safety equipment. We are not responsible for injury or damage caused by incorrect or careless use.</p><h2>8. Our content</h2><p>The text, logos and images on this website belong to {$name} or our suppliers and may not be copied or reused without permission.</p><h2>9. Liability</h2><p>As far as the law allows, our responsibility for any claim is limited to the price you paid for the product concerned.</p><h2>10. Governing law</h2><p>These terms are governed by the laws of Kenya, and any dispute falls under the Kenyan courts.</p><p>{$name}, {$addr}. {$phone} / {$mail}.</p>",
            ),
            'shipping-delivery-policy' => array(
                'title'   => 'Shipping &amp; Delivery Policy',
                'content' => "{$upd}<p>We deliver across Kenya from our shop in Nairobi. This page explains how long delivery takes and what it costs.</p><h2>Where we deliver</h2><p>We deliver countrywide, to every county in Kenya. Within Nairobi we use our own riders and trusted courier partners; upcountry we send orders through established parcel and courier services.</p><h2>Dispatch and delivery times</h2><p>Orders confirmed before 3:00pm on a working day are dispatched the same day. Delivery then takes <strong>1 to 5 working days</strong>, depending on where you are:</p><p>Nairobi and its environs: 1 to 2 working days.<br>Major towns such as Mombasa, Kisumu, Nakuru and Eldoret: 1 to 3 working days.<br>All other areas: 2 to 5 working days, usually to the nearest courier office where there is no door delivery.</p><p>Working days are Monday to Saturday. We do not dispatch or deliver on Sundays or public holidays.</p><h2>Delivery charges</h2><p>Delivery is a flat <strong>KSh 500</strong> per order, anywhere in Kenya, on every product we sell. The charge is shown at checkout before you pay, and it is the same rate whether you order online, by phone or on WhatsApp. Some promotions include free delivery, and we say so clearly at the time.</p><h2>Large and heavy items</h2><p>Generators, welding machines, solar panels and similar heavy goods sometimes need special transport, which can affect how long delivery takes. The flat KSh 500 delivery rate still applies, and we will call you before dispatch if the timing changes.</p><h2>Collecting from the shop</h2><p>You are welcome to collect your order yourself from {$addr}, Monday to Saturday, 8:00am to 6:00pm. Please wait for our message confirming the order is ready before you travel.</p><h2>Delays</h2><p>Most orders arrive on time, but weather, poor roads or courier backlogs can occasionally cause a delay. If anything is going to be late, we will call you.</p><h2>Questions</h2><p>For anything to do with delivery, call or WhatsApp {$phone} or email {$mail} with your order number.</p>",
            ),
            'return-refund-policy' => array(
                'title'   => 'Return &amp; Refund Policy',
                'content' => "{$upd}<p>If there is a problem with something you bought from us, tell us and we will put it right. This policy sits alongside your rights under the Consumer Protection Act, 2012 and the Sale of Goods Act.</p><h2>Return window</h2><p>You may request a return within <strong>14 days</strong> of the delivery date. Requests made after 14 days fall outside this policy, although the item may still be covered by the manufacturer's warranty.</p><h2>What you can return</h2><p>We accept returns on both faulty and non-faulty items. You can return something because it arrived damaged, is defective, does not match its description or was the wrong product, and you can also return an item simply because you changed your mind.</p><h2>Condition of returned items</h2><p>Items must come back in new, unused condition, in their original packaging, with all accessories, manuals and any free gifts included, and with proof of purchase, meaning your receipt or order number.</p><h2>Exchanges</h2><p>We accept exchanges. If you would rather swap an item for a different model or size than take a refund, tell us when you start the return and we will arrange the exchange instead.</p><h2>How to return an item</h2><p>Start by contacting us on {$phone} by phone or WhatsApp, or by emailing {$mail}, with your order number and a photo of the item. We will confirm that the return qualifies and tell you where to send it. You can then return the item in either of two ways:</p><p><strong>In store.</strong> Bring it to our shop at {$addr}, Monday to Saturday, 8:00am to 6:00pm.</p><p><strong>At a drop-off location.</strong> Leave it at the courier drop-off point we nominate when we approve your return.</p><h2>Who pays for the return</h2><p>If the item is faulty, damaged, incorrect or not as described, we cover the full cost of returning it. If you are returning an item because you changed your mind, you cover the return delivery or courier cost.</p><h2>Restocking fees</h2><p>We do not charge a restocking fee on any return.</p><h2>Refunds</h2><p>Once we have received the item and checked it, we will tell you whether the refund is approved. Approved refunds go back to your original payment method and are processed <strong>within 7 days</strong>. M-PESA refunds usually arrive faster than card refunds, which can take a little longer to show up depending on your bank.</p><h2>What we cannot accept</h2><p>We cannot take back items that have been used, fitted or altered, unless they are faulty; opened consumables such as blades, drill bits, abrasives and lubricants; clearance items marked non-returnable; items damaged through misuse, an accident, the wrong power supply or by ignoring the manufacturer's instructions; and items returned after the 14-day window has closed.</p><h2>Faulty goods and warranty</h2><p>A product that develops a fault after the return window may still be covered by the manufacturer's warranty. See our Warranty Policy for how those claims work.</p><p>{$name}, {$addr}. {$phone} / {$mail}.</p>",
            ),
            'warranty-policy' => array(
                'title'   => 'Warranty Policy',
                'content' => "{$upd}<p>The tools and equipment we sell carry the manufacturer's warranty. This page explains what that covers and how to make a claim.</p><h2>Warranty period</h2><p>How long the cover lasts depends on the brand and the type of product. As a guide, most power tools carry six to twelve months, and many generators and solar products carry twelve months or more. The exact period is shown on the product page or in the papers that come with the item.</p><h2>Solar panel performance warranties</h2><p>Solar panels work differently from other products. Alongside the cover described above, which deals with faults in the panel itself, most manufacturers also publish a long-term performance warranty, often of 20 to 25 years, guaranteeing that the panel will still produce a stated share of its rated output after a given number of years. Where a product page, label or box mentions a period of that length, it refers to the manufacturer's performance warranty rather than to our own handling of faults, and it is claimed through the manufacturer. We will help you start that claim and point you to the right service contact. A panel that arrives faulty or fails in normal use is handled under the periods set out above.</p><h2>What is covered</h2><p>The warranty covers faults in materials or workmanship under normal use. If a covered item fails, it will be repaired or replaced; where neither is possible, a refund is arranged in line with the manufacturer's terms.</p><h2>What is not covered</h2><p>Normal wear and tear and consumable parts are not covered, and neither is damage caused by misuse, overloading, dropping, the wrong power supply, water, unauthorised repairs or not following the manufacturer's instructions.</p><h2>How to make a claim</h2><p>Contact us on {$phone} or {$mail} with your order number, your receipt and a short description of the fault. Keep the original box and accessories where you can. We will guide you through the claim and, where needed, book the item in with the manufacturer's service centre.</p><h2>Proof of purchase</h2><p>You will need your receipt or order confirmation for any warranty claim, so please keep it safe.</p><h2>Returns within 14 days</h2><p>If the fault appears within 14 days of delivery, you can use our Return &amp; Refund Policy instead, which is usually quicker.</p><p>{$name}, {$addr}. {$phone} / {$mail}.</p>",
            ),
            'payment-methods' => array(
                'title'   => 'Payment Methods',
                'content' => "<p>We keep paying simple, transparent and secure. Choose whichever option suits you when you check out or when you order by phone or WhatsApp.</p><h2>M-PESA</h2><p>Pay by M-PESA using the till or paybill details shown at checkout, or send payment directly to our official business number when you order by phone or WhatsApp. Keep the M-PESA confirmation message as your proof of payment.</p><h2>Visa and Mastercard (in our shop only)</h2><p>We accept Visa and Mastercard debit and credit cards <strong>in person at our Nairobi shop</strong>. We do not process card payments online. If you are ordering through this website, please pay by M-PESA, or choose cash on delivery where it is available.</p><h2>Cash on delivery</h2><p>Cash on delivery is available for eligible orders within Nairobi and its environs. You pay when you receive and inspect your package. Our team will confirm whether it applies to your order before we dispatch it.</p><h2>Prices and currency</h2><p>All prices are in Kenya Shillings (KSh) and include VAT where it applies. Prices are the same on the product page, in your cart and at checkout. Any delivery charge is shown before you pay, so there are no surprises at the end.</p><h2>Questions about payment</h2><p>Call or WhatsApp {$phone}, or email {$mail}. You can also visit us at {$addr}.</p>",
            ),
            'cookie-policy' => array(
                'title'   => 'Cookie Policy',
                'content' => "{$upd}<p>This website uses cookies. Cookies are small files saved on your phone or computer that help the site work and remember what you do.</p><h2>Your choice</h2><p>When you first open the site, a banner asks whether you accept analytics and advertising cookies. Nothing in either of those groups is set until you agree. Essential cookies stay active, because the shop cannot work without them.</p><h2>What we use</h2><p><strong>Essential.</strong> These hold your shopping cart, your session and the checkout together as you move around the site. Always on.</p><p><strong>Analytics and advertising.</strong> Google Analytics and Google Ads cookies, which show us how the shop is used and let us measure our adverts. These are set only after you have accepted them.</p><h2>Changing or withdrawing your choice</h2><p>Under the Data Protection Act, 2019 consent must be as easy to withdraw as it is to give. Use the <em>Cookie settings</em> link at the foot of any page to bring the banner back and choose again. You can also delete or block cookies in your browser settings, though turning off the essential ones will stop the cart and checkout working properly.</p><h2>More on your data</h2><p>Our Privacy Policy explains what personal information we collect and how we use it.</p><p>Questions about cookies: {$mail}.</p>",
            ),
            'faq' => array(
                'title'   => 'Frequently Asked Questions',
                'content' => "<h2>Ordering</h2><p><strong>How do I place an order?</strong><br>Add what you want to the cart and check out, or simply call or WhatsApp us on {$phone} and our team will place it for you.</p><p><strong>Are your products genuine?</strong><br>Yes. We buy only from authorised distributors, and our products come with the manufacturer's warranty.</p><h2>Payment</h2><p><strong>How can I pay?</strong><br>Online orders are paid by M-PESA, or by cash on delivery for eligible orders in Nairobi and its environs. Visa and Mastercard are accepted in person at our Nairobi shop, not online. See the Payment Methods page for more.</p><h2>Delivery</h2><p><strong>How long does delivery take?</strong><br>Orders confirmed before 3:00pm on a working day are dispatched the same day. Delivery takes 1 to 5 working days: 1 to 2 days in Nairobi and its environs, 1 to 3 days to major towns, and 2 to 5 days elsewhere. See the Shipping &amp; Delivery Policy.</p><p><strong>How much does delivery cost?</strong><br>A flat KSh 500 per order, anywhere in Kenya, on every product. The charge is shown at checkout before you pay.</p><p><strong>Do you deliver countrywide?</strong><br>Yes, to every county in Kenya.</p><p><strong>Can I collect my order myself?</strong><br>Yes, from our shop at {$addr}, once we confirm it is ready for collection.</p><h2>Returns and warranty</h2><p><strong>What if my item is faulty, damaged or wrong?</strong><br>Contact us within 14 days of delivery and we will arrange a return, an exchange or a full refund at no cost to you. See the Return &amp; Refund Policy.</p><p><strong>Can I return something I simply changed my mind about?</strong><br>Yes, within 14 days, provided it is unused and in its original packaging. You cover the return delivery cost. We never charge a restocking fee.</p><p><strong>How long do refunds take?</strong><br>Approved refunds are processed within 7 days of us receiving and checking the item.</p><h2>Talk to us</h2><p><strong>How do I reach you?</strong><br>Call or WhatsApp {$phone}, email {$mail}, or visit the shop Monday to Saturday, 8:00am to 6:00pm.</p>",
            ),
            'track-order' => array(
                'title'   => 'Track Order',
                'content' => "<p>We will keep you posted at each step, but you can check on your order any time.</p><h2>Check your order</h2><p>Have your order number ready and call or WhatsApp {$phone}, or email {$mail}. We will tell you whether your order has been dispatched and when to expect it.</p><h2>Track it online</h2><p>Enter your order number and the email address you used at checkout below.</p>[woocommerce_order_tracking]",
            ),
        );
    }

    /**
     * Refresh the auto-generated info / legal page content once per content
     * version so existing sites pick up rewritten copy without duplicating
     * pages or clobbering later manual edits. Idempotent (own flag).
     */
    public function refresh_pages_content(): void {
        if ( get_option( 'toptech_pages_content_v10' ) ) {
            return;
        }
        if ( function_exists( 'current_user_can' ) === false || current_user_can( 'edit_theme_options' ) === false ) {
            return;
        }
        try {
            foreach ( $this->pages() as $slug => $page ) {
                $existing = get_page_by_path( $slug );
                if ( $existing instanceof \WP_Post === false ) {
                    continue;
                }
                wp_update_post(
                    array(
                        'ID'           => (int) $existing->ID,
                        'post_content' => $page['content'],
                    )
                );
            }
            update_option( 'toptech_pages_content_v10', time() );
        } catch ( \Throwable $e ) {
            error_log( 'TopTech Machinery pages content refresh failed: ' . $e->getMessage() );
        }
    }

	/**
	 * Populate product category thumbnails from images bundled with the theme.
	 * Runs once (own flag). For each category with no saved thumbnail it looks
	 * for assets/img/categories/{slug}.jpg (matched on the term slug or the
	 * sanitized term name), sideloads it into the media library and stores the
	 * attachment id as that category thumbnail.
	 */
	public function sync_category_images(): void {
		if ( get_option( self::CAT_IMG_FLAG ) ) {
			return;
		}
		if ( function_exists( 'current_user_can' ) === false || current_user_can( 'edit_theme_options' ) === false ) {
			return;
		}
		if ( taxonomy_exists( 'product_cat' ) === false ) {
			return; // WooCommerce not ready yet; retry on a later admin load.
		}
		$dir = trailingslashit( get_template_directory() ) . 'assets/img/categories/';
		if ( is_dir( $dir ) === false ) {
			update_option( self::CAT_IMG_FLAG, time() );
			return;
		}
		try {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';

			$terms = get_terms(
				array(
					'taxonomy'   => 'product_cat',
					'hide_empty' => false,
				)
			);
			if ( is_wp_error( $terms ) || empty( $terms ) ) {
				return; // No terms yet; retry later without setting the flag.
			}
			foreach ( $terms as $term ) {
				if ( (int) get_term_meta( $term->term_id, 'thumbnail_id', true ) > 0 ) {
					continue;
				}
				$file = '';
				$keys = array_unique( array( $term->slug, sanitize_title( $term->name ) ) );
				foreach ( $keys as $key ) {
					$candidate = $dir . $key . '.jpg';
					if ( file_exists( $candidate ) ) {
						$file = $candidate;
						break;
					}
				}
				if ( '' === $file ) {
					continue;
				}
				$attach_id = $this->sideload_category_image( $file, $term->name );
				if ( $attach_id > 0 ) {
					update_term_meta( $term->term_id, 'thumbnail_id', $attach_id );
				}
			}
			update_option( self::CAT_IMG_FLAG, time() );
		} catch ( \Throwable $e ) {
			error_log( 'TopTech Machinery category image sync failed: ' . $e->getMessage() );
		}
	}

	/**
	 * Copy a bundled image into the media library and return its attachment id.
	 */
	private function sideload_category_image( string $path, string $title ): int {
		$tmp = wp_tempnam( basename( $path ) );
		if ( empty( $tmp ) ) {
			return 0;
		}
		if ( @copy( $path, $tmp ) === false ) {
			@unlink( $tmp );
			return 0;
		}
		$file_array = array(
			'name'     => sanitize_file_name( basename( $path ) ),
			'tmp_name' => $tmp,
		);
		$id = media_handle_sideload( $file_array, 0, $title );
		if ( is_wp_error( $id ) ) {
			@unlink( $tmp );
			return 0;
		}
		return (int) $id;
	}

	/**
	 * One-time migration: refresh contact details (phone, WhatsApp, address)
	 * on existing installs after a details change. Updates a saved Customizer
	 * value only when it still equals the previous default, and rewrites the
	 * matching strings inside the auto-generated info / legal pages. Idempotent.
	 */
	public function refresh_contact_details(): void {
		if ( get_option( 'toptech_contact_refresh_v1' ) ) {
			return;
		}
		if ( function_exists( 'current_user_can' ) === false || current_user_can( 'edit_theme_options' ) === false ) {
			return;
		}
		try {
			$mods = array(
				'toptech_phone'    => array( '0719 261277', '0797 720290' ),
				'toptech_whatsapp' => array( '254719261277', '254797720290' ),
				'toptech_address'  => array( 'Royal Palms Mall, Shop No. BG 55, Nairobi, Kenya', 'This & That Exhibition, Opp. Ronald Ngala Post Office, Shop G15, Ronald Ngala Street, Nairobi, Kenya' ),
			);
			foreach ( $mods as $key => $pair ) {
				if ( get_theme_mod( $key ) === $pair[0] ) {
					set_theme_mod( $key, $pair[1] );
				}
			}
			$repl = array(
				'Royal Palms Mall, Shop No. BG 55, Nairobi, Kenya' => 'This & That Exhibition, Opp. Ronald Ngala Post Office, Shop G15, Ronald Ngala Street, Nairobi, Kenya',
				'Royal Palms Mall, Shop No. BG 55'                 => 'This & That Exhibition, Opp. Ronald Ngala Post Office, Shop G15, Ronald Ngala Street',
				'0719 261277'                                      => '0797 720290',
				'254719261277'                                     => '254797720290',
			);
			$slugs = array(
				'about-us', 'contact-us', 'payment-methods', 'return-refund-policy',
				'shipping-delivery-policy', 'track-order', 'faq', 'privacy-policy',
				'terms-conditions', 'warranty-policy', 'cookie-policy', 'home',
			);
			foreach ( $slugs as $slug ) {
				$page = get_page_by_path( $slug );
				if ( $page instanceof \WP_Post === false ) {
					continue;
				}
				$content = (string) $page->post_content;
				$updated = strtr( $content, $repl );
				if ( $updated === $content ) {
					continue;
				}
				wp_update_post( array( 'ID' => (int) $page->ID, 'post_content' => $updated ) );
			}
			update_option( 'toptech_contact_refresh_v1', time() );
		} catch ( \Throwable $e ) {
			error_log( 'TopTech Machinery contact refresh failed: ' . $e->getMessage() );
		}
	}

	/**
	 * Seed the contact/support theme mods with the real business details on
	 * first run so the site (and the merchant inspector) always has a phone and
	 * email, even before anyone opens the Customizer. Only fills empty values.
	 */
	/**
	 * One-time fix: the WordPress Site Title was saved in lowercase
	 * ("toptech machinery"), which surfaced in the title tag of every page
	 * while all body copy uses the correct "TopTech Machinery" casing. Only
	 * rewrites the option when it still holds a case-insensitive match for the
	 * brand, so a deliberately different title is never clobbered. Idempotent.
	 */
	public function fix_site_title(): void {
		if ( get_option( 'toptech_site_title_v1' ) ) {
			return;
		}
		if ( function_exists( 'current_user_can' ) === false || current_user_can( 'edit_theme_options' ) === false ) {
			return;
		}
		try {
			$current = trim( (string) get_option( 'blogname' ) );
			if ( '' === $current || strcasecmp( $current, 'toptech machinery' ) === 0 ) {
				update_option( 'blogname', 'TopTech Machinery' );
			}
			update_option( 'toptech_site_title_v1', time() );
		} catch ( \Throwable $e ) {
			error_log( 'TopTech Machinery site title fix failed: ' . $e->getMessage() );
		}
	}

	public function seed_contact_defaults(): void {
		if ( get_option( 'toptech_contact_seed_v1' ) ) {
			return;
		}
		if ( function_exists( 'current_user_can' ) === false || current_user_can( 'edit_theme_options' ) === false ) {
			return;
		}
		try {
			$defaults = array(
				'toptech_phone'    => '0797 720290',
				'toptech_email'    => 'info@toptechmachinery.co.ke',
				'toptech_hours'    => 'Mon-Sat 8:00am - 6:00pm',
				'toptech_address'  => 'This & That Exhibition, Opp. Ronald Ngala Post Office, Shop G15, Ronald Ngala Street, Nairobi, Kenya',
				'toptech_whatsapp' => '254797720290',
			);
			foreach ( $defaults as $key => $val ) {
				if ( trim( (string) get_theme_mod( $key, '' ) ) === '' ) {
					set_theme_mod( $key, $val );
				}
			}
			update_option( 'toptech_contact_seed_v1', time() );
		} catch ( \Throwable $e ) {
			error_log( 'TopTech Machinery contact seed failed: ' . $e->getMessage() );
		}
	}

	/**
	 * One-time cleanup: strip leftover competitor brand references from product
	 * content. Earlier product copy contained "Ricky Power Tools" (and a
	 * truncated "from Ricky.") which was bulk-replaced in the database; this
	 * migration self-heals any residual mentions on deploy so no stray copy
	 * survives in titles, descriptions, short descriptions or stored SEO meta.
	 * Targets only posts that still reference the old name, is case-insensitive
	 * for the full phrase, and runs once (own flag). Idempotent.
	 */
	public function cleanup_competitor_brand(): void {
		if ( get_option( 'toptech_brand_cleanup_v1' ) ) {
			return;
		}
		if ( function_exists( 'current_user_can' ) === false || current_user_can( 'edit_theme_options' ) === false ) {
			return;
		}
		try {
			global $wpdb;
			if ( is_object( $wpdb ) === false ) {
				return;
			}
			$clean = static function ( string $value ): string {
				$value = str_ireplace( 'Ricky Power Tools', 'TopTech Machinery', $value );
				$value = strtr( $value, array( 'from Ricky.' => 'from TopTech Machinery.' ) );
				return $value;
			};
			$like = '%' . $wpdb->esc_like( 'Ricky' ) . '%';
			$ids  = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT DISTINCT p.ID FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID WHERE p.post_status <> 'trash' AND ( p.post_title LIKE %s OR p.post_content LIKE %s OR p.post_excerpt LIKE %s OR pm.meta_value LIKE %s )",
					$like,
					$like,
					$like,
					$like
				)
			);
			foreach ( (array) $ids as $id ) {
				$post = get_post( (int) $id );
				if ( $post instanceof \WP_Post === false ) {
					continue;
				}
				$update  = array();
				$title   = $clean( (string) $post->post_title );
				$content = $clean( (string) $post->post_content );
				$excerpt = $clean( (string) $post->post_excerpt );
				if ( $title !== $post->post_title ) {
					$update['post_title'] = $title;
				}
				if ( $content !== $post->post_content ) {
					$update['post_content'] = $content;
				}
				if ( $excerpt !== $post->post_excerpt ) {
					$update['post_excerpt'] = $excerpt;
				}
				if ( count( $update ) > 0 ) {
					$update['ID'] = (int) $id;
					wp_update_post( $update );
				}
				$metas = get_post_meta( (int) $id );
				if ( is_array( $metas ) ) {
					foreach ( $metas as $meta_key => $meta_values ) {
						foreach ( (array) $meta_values as $meta_value ) {
							if ( is_string( $meta_value ) === false || is_serialized( $meta_value ) || stripos( $meta_value, 'Ricky' ) === false ) {
								continue;
							}
							$new_value = $clean( $meta_value );
							if ( $new_value !== $meta_value ) {
								update_post_meta( (int) $id, $meta_key, $new_value, $meta_value );
							}
						}
					}
				}
			}
			update_option( 'toptech_brand_cleanup_v1', time() );
		} catch ( \Throwable $e ) {
			error_log( 'TopTech Machinery brand cleanup failed: ' . $e->getMessage() );
		}
	}

	/**
	 * One-time cleanup: reclassify mislabelled "DeWalt" welders. DeWalt does not
	 * manufacture arc / MMA stick inverter welders, so these listings read as
	 * counterfeit / brand misrepresentation to Google Merchant Center. This
	 * migration retitles the affected products, strips the DeWalt name from their
	 * copy and non-serialized stored meta, and reassigns their product_brand term
	 * to "Generic" (created if absent). Targets a fixed set of known welder IDs,
	 * only acts on ones whose title still claims DeWalt, and runs once (own flag).
	 * Idempotent.
	 */
	public function reclassify_dewalt_welders(): void {
		if ( get_option( 'toptech_brand_reclass_v1' ) ) {
			return;
		}
		if ( function_exists( 'current_user_can' ) === false || current_user_can( 'edit_theme_options' ) === false ) {
			return;
		}
		try {
			$targets = array( 30874, 13315, 12924 );
			$clean   = static function ( string $value ): string {
				return str_ireplace( array( 'DeWalt', 'De Walt', 'De-Walt' ), 'Generic', $value );
			};
			$brand_term_id = 0;
			if ( taxonomy_exists( 'product_brand' ) ) {
				$term = get_term_by( 'slug', 'generic', 'product_brand' );
				if ( $term instanceof \WP_Term ) {
					$brand_term_id = (int) $term->term_id;
				} else {
					$inserted = wp_insert_term( 'Generic', 'product_brand', array( 'slug' => 'generic' ) );
					if ( is_array( $inserted ) && isset( $inserted['term_id'] ) ) {
						$brand_term_id = (int) $inserted['term_id'];
					}
				}
			}
			foreach ( $targets as $target_id ) {
				$post = get_post( (int) $target_id );
				if ( $post instanceof \WP_Post === false ) {
					continue;
				}
				if ( stripos( (string) $post->post_title, 'Walt' ) === false ) {
					continue; // Already reclassified or not the expected product.
				}
				$update  = array();
				$title   = $clean( (string) $post->post_title );
				$content = $clean( (string) $post->post_content );
				$excerpt = $clean( (string) $post->post_excerpt );
				if ( $title !== $post->post_title ) {
					$update['post_title'] = $title;
				}
				if ( $content !== $post->post_content ) {
					$update['post_content'] = $content;
				}
				if ( $excerpt !== $post->post_excerpt ) {
					$update['post_excerpt'] = $excerpt;
				}
				if ( count( $update ) > 0 ) {
					$update['ID'] = (int) $target_id;
					wp_update_post( $update );
				}
				$metas = get_post_meta( (int) $target_id );
				if ( is_array( $metas ) ) {
					foreach ( $metas as $meta_key => $meta_values ) {
						foreach ( (array) $meta_values as $meta_value ) {
							if ( is_string( $meta_value ) === false || is_serialized( $meta_value ) || stripos( $meta_value, 'Walt' ) === false ) {
								continue;
							}
							$new_value = $clean( $meta_value );
							if ( $new_value !== $meta_value ) {
								update_post_meta( (int) $target_id, $meta_key, $new_value, $meta_value );
							}
						}
					}
				}
				if ( $brand_term_id > 0 ) {
					wp_set_object_terms( (int) $target_id, array( $brand_term_id ), 'product_brand', false );
				}
			}
			update_option( 'toptech_brand_reclass_v1', time() );
		} catch ( \Throwable $e ) {
			error_log( 'TopTech Machinery brand reclassification failed: ' . $e->getMessage() );
		}
	}
}
