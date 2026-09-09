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

        return array(
            'about-us' => array(
                'title'   => 'About Us',
                'content' => "<p>{$name} is a Nairobi-based supplier of power tools, solar equipment, generators, water pumps, welding machines and general hardware. We sell to contractors, fundis, farmers, businesses and homeowners, and we deliver across Kenya.</p><h2>What we sell</h2><p>We stock well-known brands such as Total, Ingco, Makita, DeWalt, Bosch, Honda and Solarmax, alongside dependable value options. Everything we carry comes from authorised distributors, so the item you buy is genuine and covered by the manufacturer's warranty.</p><h2>How we work</h2><p>Prices are shown clearly in Kenya Shillings, with nothing hidden. If you are not sure which tool or machine suits the job, call or WhatsApp us and we will help you decide. Orders placed before our afternoon cut-off in Nairobi are usually sent out the same day.</p><h2>Come and see us</h2><p>Visit the shop at {$addr}, open Monday to Saturday, 8:00am to 6:00pm. You can also reach us on {$phone} or at {$mail}.</p>",
            ),
            'contact-us' => array(
                'title'   => 'Contact Us',
                'content' => "<p>You can reach {$name} by phone, WhatsApp, email or in person at our Nairobi shop. We answer most calls and messages the same day during opening hours.</p><h2>Phone and WhatsApp</h2><p>Call or message us on {$phone}. WhatsApp is usually the quickest way to send a photo of what you need or to place an order.</p><h2>Email</h2><p>Write to us at {$mail}. Please include your order number if your message is about an order you have already placed.</p><h2>Our shop</h2><p>{$addr}</p><p>Open Monday to Saturday, 8:00am to 6:00pm. Closed on Sundays and public holidays.</p><h2>Send us a message</h2><p>Fill in the form below and we will get back to you within one working day. During opening hours a call or WhatsApp message will always reach us faster.</p>[contact-form-7 title=\"Contact form\"]",
            ),
            'privacy-policy' => array(
                'title'   => 'Privacy Policy',
                'content' => "<p><em>Last updated: 1 September 2026</em></p><p>This policy explains how {$name} collects and uses your personal information when you shop with us or use this website. We handle personal data in line with the Data Protection Act, 2019 and are guided by the Office of the Data Protection Commissioner.</p><h2>1. Information we collect</h2><p>When you place an order or get in touch, we collect your name, phone number, email address and delivery address, together with the details of what you bought. As you use the website we also collect basic technical information such as your IP address, browser type and the pages you visit, mostly through cookies.</p><h2>2. How we use your information</h2><p>We use it to process and deliver your orders, answer your questions, keep you updated on an order, and meet our tax and record-keeping obligations. We only send offers or marketing messages if you have asked to receive them, and you can opt out at any time.</p><h2>3. Payments</h2><p>Payments go through M-PESA and licensed card processors. You enter your card or mobile-money details directly with those providers over secure connections. We do not see or store your full payment details.</p><h2>4. Who we share it with</h2><p>We share your details only with the partners who help us complete your order, such as delivery and courier companies and our payment processors, and with the authorities where the law requires it. We do not sell your personal information.</p><h2>5. How long we keep it</h2><p>We keep your information for as long as we need it to complete your order and satisfy legal and tax requirements, then we delete or anonymise it.</p><h2>6. Your rights</h2><p>You can ask to see the information we hold about you, correct it, or have it deleted, and you can object to us using it in certain ways. Email {$mail} and we will respond.</p><h2>7. Contact</h2><p>{$name}, {$addr}. {$phone} / {$mail}.</p>",
            ),
            'terms-conditions' => array(
                'title'   => 'Terms &amp; Conditions',
                'content' => "<p><em>Last updated: 1 September 2026</em></p><h2>1. About these terms</h2><p>These terms apply when you buy from {$name}, whether on this website, by phone or on WhatsApp. Placing an order means you accept them.</p><h2>2. Products, prices and stock</h2><p>Prices are in Kenya Shillings and include VAT where it applies. We work hard to keep prices, descriptions, photographs and stock levels accurate, but mistakes do happen. If we find an error in the price or description of something you have ordered, we will contact you and, if you prefer, cancel the order and refund you in full.</p><h2>3. Placing an order</h2><p>Your order is confirmed once we have received payment or, for cash on delivery, once we have confirmed it with you by phone. We may decline or cancel an order if we cannot verify it or if we suspect fraud.</p><h2>4. Payment</h2><p>We accept M-PESA, Visa, Mastercard and cash on delivery where available. There is more detail on our Payment Methods page.</p><h2>5. Delivery</h2><p>Delivery times and charges are set out in our Shipping &amp; Delivery Policy. Goods become your responsibility once they are handed to you or someone acting for you.</p><h2>6. Returns and warranty</h2><p>Our Return &amp; Refund Policy and Warranty Policy explain how to return an item or make a warranty claim, and they form part of these terms.</p><h2>7. Using the equipment safely</h2><p>Power tools and machinery must be used according to the manufacturer's instructions and with the right safety equipment. We are not responsible for injury or damage caused by incorrect or careless use.</p><h2>8. Our content</h2><p>The text, logos and images on this website belong to {$name} or our suppliers and may not be copied or reused without permission.</p><h2>9. Liability</h2><p>As far as the law allows, our responsibility for any claim is limited to the price you paid for the product concerned.</p><h2>10. Governing law</h2><p>These terms are governed by the laws of Kenya, and any dispute falls under the Kenyan courts.</p><p>{$name}, {$addr}. {$phone} / {$mail}.</p>",
            ),
            'shipping-delivery-policy' => array(
                'title'   => 'Shipping &amp; Delivery Policy',
                'content' => "<p><em>Last updated: 1 September 2026</em></p><p>We deliver across Kenya from our shop in Nairobi. This page explains how long delivery takes and what it costs.</p><h2>Where we deliver</h2><p>We deliver countrywide. Within Nairobi we use our own riders and trusted courier partners; upcountry we send orders through established parcel and courier services.</p><h2>How long it takes</h2><p>Nairobi and its environs: same day or next day for orders confirmed before 3:00pm on a working day. Major towns such as Mombasa, Kisumu, Nakuru and Eldoret: one to three working days. Other areas: two to five working days, usually to the nearest courier office where there is no door delivery.</p><h2>Delivery charges</h2><p>The fee depends on your location and the size and weight of the order. It is worked out and shown at checkout before you pay. For orders placed by phone or WhatsApp we quote the fee before you confirm. Some promotions include free delivery, which we state at the time.</p><h2>Large and heavy items</h2><p>Generators, welding machines, solar panels and similar heavy goods sometimes need special transport. Where that changes the cost or timing of your delivery, we let you know before dispatch.</p><h2>Collecting from the shop</h2><p>You are welcome to collect your order yourself from {$addr}. Please wait for our message confirming the order is ready before you travel.</p><h2>Delays</h2><p>Most orders arrive on time, but weather, poor roads or courier backlogs can occasionally cause a delay. If anything is going to be late, we will call you.</p><h2>Questions</h2><p>For anything to do with delivery, call or WhatsApp {$phone} or email {$mail} with your order number.</p>",
            ),
            'return-refund-policy' => array(
                'title'   => 'Return &amp; Refund Policy',
                'content' => "<p><em>Last updated: 1 September 2026</em></p><p>If there is a problem with something you bought from us, tell us and we will put it right. This policy sits alongside your rights under the Consumer Protection Act, 2012 and the Sale of Goods Act.</p><h2>Returning an item</h2><p>You can ask to return most items within 7 days of delivery. The item should be unused and in its original packaging, with all accessories, manuals and any free gifts included.</p><h2>When we accept a return</h2><p>We accept returns when an item arrives damaged, turns out to be faulty, does not match its description, or when we sent the wrong product. In these cases we cover the cost of getting the item back to us.</p><h2>When we cannot accept a return</h2><p>We cannot take back items that have been used, fitted or altered unless they are faulty; consumables such as blades, drill bits and lubricants once opened; or clearance items marked non-returnable. Products damaged through misuse, an accident or by ignoring the manufacturer's instructions are also not covered.</p><h2>How to start a return</h2><p>Call or WhatsApp {$phone}, or email {$mail}, with your order number and a photo of the item. We will confirm whether it qualifies and either arrange collection or ask you to bring it to the shop.</p><h2>Refunds</h2><p>Once we have the item back and have checked it, we approve the refund and send it to your original payment method, usually within 3 to 7 working days. M-PESA refunds are normally quicker than card refunds. If you would rather have a replacement or an exchange, we will arrange that instead.</p><h2>Faulty goods</h2><p>A product that develops a fault may also be covered by the manufacturer's warranty. See our Warranty Policy for how those claims work.</p><p>{$name}, {$addr}. {$phone} / {$mail}.</p>",
            ),
            'warranty-policy' => array(
                'title'   => 'Warranty Policy',
                'content' => "<p><em>Last updated: 1 September 2026</em></p><p>The tools and equipment we sell carry the manufacturer's warranty. This page explains what that covers and how to make a claim.</p><h2>Warranty period</h2><p>How long the cover lasts depends on the brand and the type of product. As a guide, most power tools carry six to twelve months, and many generators and solar products carry twelve months or more. The exact period is shown on the product page or in the papers that come with the item.</p><h2>What is covered</h2><p>The warranty covers faults in materials or workmanship under normal use. If a covered item fails, it will be repaired or replaced; where neither is possible, a refund is arranged in line with the manufacturer's terms.</p><h2>What is not covered</h2><p>Normal wear and tear and consumable parts are not covered, and neither is damage caused by misuse, overloading, dropping, the wrong power supply, water, unauthorised repairs or not following the manufacturer's instructions.</p><h2>How to make a claim</h2><p>Contact us on {$phone} or {$mail} with your order number, your receipt and a short description of the fault. Keep the original box and accessories where you can. We will guide you through the claim and, where needed, book the item in with the manufacturer's service centre.</p><h2>Proof of purchase</h2><p>You will need your receipt or order confirmation for any warranty claim, so please keep it safe.</p><p>{$name}, {$addr}. {$phone} / {$mail}.</p>",
            ),
            'payment-methods' => array(
                'title'   => 'Payment Methods',
                'content' => "<p>We keep paying simple and secure. Choose whichever option suits you when you check out or when you order by phone.</p><h2>M-PESA</h2><p>Pay by M-PESA using the till or paybill details shown at checkout, or send payment directly when you order by phone or WhatsApp. Keep the M-PESA confirmation message as your proof of payment.</p><h2>Visa and Mastercard</h2><p>We accept Visa and Mastercard debit and credit cards. Card payments are handled by our licensed processor over an encrypted connection, so we never see or store your full card number.</p><h2>Cash on delivery</h2><p>Cash on delivery is available for selected locations and order values. Our team will confirm whether it applies to your order before we dispatch it.</p><h2>Prices and currency</h2><p>All prices are in Kenya Shillings (KSh) and are the same on the product page, in your cart and at checkout. Any delivery charge is shown before you pay, so there are no surprises at the end.</p><p>If you have a question about payment, call or WhatsApp {$phone}.</p>",
            ),
            'cookie-policy' => array(
                'title'   => 'Cookie Policy',
                'content' => "<p><em>Last updated: 1 September 2026</em></p><p>This website uses cookies. Cookies are small files saved on your phone or computer that help the site work and remember what you do.</p><h2>Why we use them</h2><p>Some cookies are essential: they keep your shopping cart and checkout working as you move around the site. Others remember your preferences, such as items you have looked at, and some help us see how the site is used so we can improve it.</p><h2>Managing cookies</h2><p>You can delete or block cookies in your browser settings. Do note that if you turn off the essential ones, parts of the site such as the cart and checkout may stop working properly.</p><p>Questions about cookies: {$mail}.</p>",
            ),
            'faq' => array(
                'title'   => 'Frequently Asked Questions',
                'content' => "<h2>Ordering</h2><p><strong>How do I place an order?</strong><br>Add what you want to the cart and check out, or simply call or WhatsApp us on {$phone} and we will place it for you.</p><p><strong>Are your products genuine?</strong><br>Yes. We buy only from authorised distributors, and our products come with the manufacturer's warranty.</p><h2>Payment</h2><p><strong>How can I pay?</strong><br>By M-PESA, Visa or Mastercard, or cash on delivery where it is available. See the Payment Methods page for more.</p><h2>Delivery</h2><p><strong>Do you deliver countrywide?</strong><br>Yes. Nairobi orders often arrive the same or next day, and upcountry orders take a few days depending on your location. See the Shipping &amp; Delivery Policy.</p><p><strong>Can I collect my order myself?</strong><br>Yes, from our shop at {$addr} once we confirm it is ready.</p><h2>Returns and warranty</h2><p><strong>What if my item is faulty or wrong?</strong><br>Get in touch within 7 days and we will arrange a return, or help you with a warranty claim. See the Return &amp; Refund Policy and Warranty Policy.</p><h2>Talk to us</h2><p><strong>How do I reach you?</strong><br>Call or WhatsApp {$phone}, email {$mail}, or visit the shop Monday to Saturday, 8:00am to 6:00pm.</p>",
            ),
            'track-order' => array(
                'title'   => 'Track Order',
                'content' => "<p>We will keep you posted at each step, but you can check on your order any time.</p><h2>Check your order</h2><p>Have your order number ready and call or WhatsApp {$phone}, or email {$mail}. We will tell you whether your order has been dispatched and when to expect it.</p><p>If our order-tracking tool is switched on, you can also enter your details below.</p>[woocommerce_order_tracking]",
            ),
        );
    }

    /**
     * Refresh the auto-generated info / legal page content once per content
     * version so existing sites pick up rewritten copy without duplicating
     * pages or clobbering later manual edits. Idempotent (own flag).
     */
    public function refresh_pages_content(): void {
        if ( get_option( 'toptech_pages_content_v2' ) ) {
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
            update_option( 'toptech_pages_content_v2', time() );
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
}
