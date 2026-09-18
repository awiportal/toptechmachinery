<?php
/**
 * Structured data (JSON-LD) + SEO meta: Organization, WebSite, Breadcrumb, Product,
 * plus meta description and Open Graph / Twitter cards. Output is suppressed when a
 * dedicated SEO plugin (Yoast, Rank Math, SEOPress, AIOSEO) is active, to avoid duplicates.
 *
 * @package ToptechMachinery
 */

declare( strict_types = 1 );

namespace ToptechMachinery;

defined( 'ABSPATH' ) || exit;

/**
 * Emits schema.org JSON-LD and social meta for SEO.
 */
final class Schema {

	public function hooks(): void {
		add_action( 'wp_head', array( $this, 'meta_tags' ), 4 );
		add_action( 'wp_head', array( $this, 'organization' ), 5 );
		add_action( 'wp_head', array( $this, 'website' ), 6 );
		add_action( 'wp_head', array( $this, 'breadcrumb' ), 7 );
		add_action( 'wp_footer', array( $this, 'product' ), 20 );
		// Rank Math owns the product JSON-LD when active; extend it rather than
		// duplicating it. Registered unconditionally: the filter simply never
		// fires when Rank Math is absent.
		add_filter( 'rank_math/snippet/rich_snippet_product_entity', array( $this, 'rank_math_product' ) );
	}

	/**
	 * True when a dedicated SEO plugin is active (so we defer to it).
	 */
	private function has_seo_plugin(): bool {
		return (
			defined( 'WPSEO_VERSION' ) || defined( 'WPSEO_FILE' )
			|| defined( 'RANK_MATH_VERSION' ) || class_exists( 'RankMath' )
			|| defined( 'SEOPRESS_VERSION' )
			|| function_exists( 'aioseo' ) || defined( 'AIOSEO_VERSION' )
		);
	}

	/**
	 * Meta description + Open Graph + Twitter cards.
	 */
	public function meta_tags(): void {
		if ( $this->has_seo_plugin() ) {
			return;
		}
		$title = wp_get_document_title();
		$desc  = $this->meta_description();
		$url   = $this->current_url();
		$image = $this->og_image();
		$type  = is_singular( array( 'product', 'post' ) ) ? 'article' : 'website';

		if ( '' !== $desc ) {
			printf( '<meta name="description" content="%s">' . "\n", esc_attr( $desc ) );
		}
		printf( '<meta property="og:site_name" content="%s">' . "\n", esc_attr( get_bloginfo( 'name' ) ) );
		printf( '<meta property="og:type" content="%s">' . "\n", esc_attr( $type ) );
		printf( '<meta property="og:title" content="%s">' . "\n", esc_attr( $title ) );
		if ( '' !== $desc ) {
			printf( '<meta property="og:description" content="%s">' . "\n", esc_attr( $desc ) );
		}
		printf( '<meta property="og:url" content="%s">' . "\n", esc_url( $url ) );
		printf( '<meta property="og:locale" content="%s">' . "\n", esc_attr( get_locale() ) );
		if ( '' !== $image ) {
			printf( '<meta property="og:image" content="%s">' . "\n", esc_url( $image ) );
		}
		printf( '<meta name="twitter:card" content="%s">' . "\n", '' !== $image ? 'summary_large_image' : 'summary' );
		printf( '<meta name="twitter:title" content="%s">' . "\n", esc_attr( $title ) );
		if ( '' !== $desc ) {
			printf( '<meta name="twitter:description" content="%s">' . "\n", esc_attr( $desc ) );
		}
		if ( '' !== $image ) {
			printf( '<meta name="twitter:image" content="%s">' . "\n", esc_url( $image ) );
		}
	}

	private function meta_description(): string {
		$d = '';
		if ( is_front_page() || is_home() ) {
			$d = get_bloginfo( 'description' );
		} elseif ( function_exists( 'is_product' ) && is_product() ) {
			$p = wc_get_product( get_queried_object_id() );
			if ( $p instanceof \WC_Product ) {
				$d = $p->get_short_description() ? $p->get_short_description() : $p->get_description();
			}
		} elseif ( is_singular() ) {
			$post = get_queried_object();
			if ( $post instanceof \WP_Post ) {
				$d = has_excerpt( $post ) ? get_the_excerpt( $post ) : $post->post_content;
			}
		} elseif ( is_tax() || is_category() || is_tag() ) {
			$t = get_queried_object();
			if ( $t instanceof \WP_Term ) {
				$d = term_description( $t );
			}
		} elseif ( is_search() ) {
			/* translators: %s: search query. */
			$d = sprintf( __( 'Search results for "%s"', 'toptech-machinery' ), get_search_query() );
		}
		$d = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( (string) $d ) ) );
		if ( '' === $d ) {
			$d = get_bloginfo( 'description' );
		}
		if ( function_exists( 'mb_strlen' ) && mb_strlen( $d ) > 160 ) {
			$d = rtrim( mb_substr( $d, 0, 157 ) ) . '...';
		}
		return $d;
	}

	private function current_url(): string {
		if ( is_front_page() ) {
			return home_url( '/' );
		}
		if ( is_singular() ) {
			$link = get_permalink();
			if ( $link ) {
				return (string) $link;
			}
		}
		if ( is_tax() || is_category() || is_tag() ) {
			$link = get_term_link( get_queried_object() );
			if ( ! is_wp_error( $link ) ) {
				return (string) $link;
			}
		}
		if ( function_exists( 'is_shop' ) && is_shop() && function_exists( 'wc_get_page_permalink' ) ) {
			return (string) wc_get_page_permalink( 'shop' );
		}
		global $wp;
		return home_url( isset( $wp->request ) ? user_trailingslashit( $wp->request ) : '' );
	}

	private function og_image(): string {
		$id = 0;
		if ( function_exists( 'is_product' ) && is_product() ) {
			$p = wc_get_product( get_queried_object_id() );
			if ( $p instanceof \WC_Product && $p->get_image_id() ) {
				$id = (int) $p->get_image_id();
			}
		} elseif ( is_singular() ) {
			$id = (int) get_post_thumbnail_id( get_queried_object_id() );
		}
		if ( $id ) {
			$src = wp_get_attachment_image_url( $id, 'large' );
			if ( $src ) {
				return $src;
			}
		}
		$logo = get_theme_mod( 'custom_logo' );
		if ( $logo ) {
			$src = wp_get_attachment_image_url( (int) $logo, 'full' );
			if ( $src ) {
				return $src;
			}
		}
		return '';
	}

	/**
	 * Organization + Store on every page.
	 */
	public function organization(): void {
		if ( $this->has_seo_plugin() ) {
			return;
		}
		$data = array(
			'@context'  => 'https://schema.org',
			'@type'     => array( 'Organization', 'Store' ),
			'name'      => get_bloginfo( 'name' ),
			'url'       => home_url( '/' ),
			'email'     => get_theme_mod( 'toptech_email', 'info@toptechmachinery.co.ke' ),
			'telephone' => $this->tel_e164(),
			'address'   => array(
				'@type'           => 'PostalAddress',
				'streetAddress'   => $this->street_address(),
				'addressLocality' => 'Nairobi',
				'addressRegion'   => 'Nairobi',
				'postalCode'      => '00100',
				'addressCountry'  => 'KE',
			),
			'areaServed' => array(
				'@type' => 'Country',
				'name'  => 'Kenya',
			),
			'openingHoursSpecification' => array(
				array(
					'@type'     => 'OpeningHoursSpecification',
					'dayOfWeek' => array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' ),
					'opens'     => '08:00',
					'closes'    => '18:00',
				),
			),
		);
		$logo = get_theme_mod( 'custom_logo' );
		if ( $logo ) {
			$src = wp_get_attachment_image_src( (int) $logo, 'full' );
			if ( $src ) {
				$data['logo']  = esc_url( $src[0] );
				$data['image'] = esc_url( $src[0] );
			}
		}
		$this->print_ld( $data );
	}

	/**
	 * WebSite + SearchAction (sitelinks search box) on the homepage.
	 */
	public function website(): void {
		if ( $this->has_seo_plugin() || ! ( is_front_page() || is_home() ) ) {
			return;
		}
		$this->print_ld(
			array(
				'@context'        => 'https://schema.org',
				'@type'           => 'WebSite',
				'name'            => get_bloginfo( 'name' ),
				'url'             => home_url( '/' ),
				'potentialAction' => array(
					'@type'       => 'SearchAction',
					'target'      => array(
						'@type'       => 'EntryPoint',
						'urlTemplate' => home_url( '/?s={search_term_string}&post_type=product' ),
					),
					'query-input' => 'required name=search_term_string',
				),
			)
		);
	}

	/**
	 * BreadcrumbList on product + product taxonomy pages.
	 */
	public function breadcrumb(): void {
		if ( $this->has_seo_plugin() ) {
			return;
		}
		$items  = array();
		$has_wc = function_exists( 'wc_get_page_permalink' );
		if ( function_exists( 'is_product' ) && is_product() ) {
			$items[] = array( home_url( '/' ), __( 'Home', 'toptech-machinery' ) );
			if ( $has_wc ) {
				$items[] = array( wc_get_page_permalink( 'shop' ), __( 'Shop', 'toptech-machinery' ) );
			}
			$terms = get_the_terms( get_queried_object_id(), 'product_cat' );
			if ( $terms && ! is_wp_error( $terms ) ) {
				$term = array_shift( $terms );
				$link = get_term_link( $term );
				if ( ! is_wp_error( $link ) ) {
					$items[] = array( $link, $term->name );
				}
			}
			$items[] = array( get_permalink(), get_the_title() );
		} elseif ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) {
			$items[] = array( home_url( '/' ), __( 'Home', 'toptech-machinery' ) );
			if ( $has_wc ) {
				$items[] = array( wc_get_page_permalink( 'shop' ), __( 'Shop', 'toptech-machinery' ) );
			}
			$obj = get_queried_object();
			if ( $obj instanceof \WP_Term ) {
				$link = get_term_link( $obj );
				if ( ! is_wp_error( $link ) ) {
					$items[] = array( $link, $obj->name );
				}
			}
		} else {
			return;
		}

		$list = array();
		foreach ( $items as $i => $it ) {
			$list[] = array(
				'@type'    => 'ListItem',
				'position' => $i + 1,
				'name'     => wp_strip_all_tags( (string) $it[1] ),
				'item'     => esc_url_raw( (string) $it[0] ),
			);
		}
		$this->print_ld(
			array(
				'@context'        => 'https://schema.org',
				'@type'           => 'BreadcrumbList',
				'itemListElement' => $list,
			)
		);
	}

	/**
	 * Product schema on single product pages (identifiers + offer).
	 */
	public function product(): void {
		if ( $this->has_seo_plugin() || ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}
		global $product;
		if ( ! $product instanceof \WC_Product ) {
			$product = wc_get_product( get_the_ID() );
		}
		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		$data = array(
			'@context'    => 'https://schema.org',
			'@type'       => 'Product',
			'name'        => $product->get_name(),
			'description' => wp_strip_all_tags( $product->get_short_description() ? $product->get_short_description() : $product->get_description() ),
			'sku'         => $product->get_sku(),
			'mpn'         => get_post_meta( $product->get_id(), '_mpn', true ) ? get_post_meta( $product->get_id(), '_mpn', true ) : $product->get_sku(),
			'brand'       => array(
				'@type' => 'Brand',
				'name'  => $this->brand_name( $product ),
			),
			'offers'      => array(
				'@type'           => 'Offer',
				'url'             => $product->get_permalink(),
				'priceCurrency'   => get_woocommerce_currency(),
				'price'           => wc_get_price_to_display( $product ),
				'priceValidUntil' => gmdate( 'Y-m-d', time() + YEAR_IN_SECONDS ),
				'availability'    => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
				'itemCondition'   => 'https://schema.org/NewCondition',
				'seller'          => array(
					'@type' => 'Organization',
					'name'  => get_bloginfo( 'name' ),
				),
			),
		);
		// Return and shipping markup, mirroring the Merchant Center settings.
		$data['offers']['hasMerchantReturnPolicy'] = $this->return_policy();
		$data['offers']['shippingDetails']         = $this->shipping_details();

		$gtin = get_post_meta( $product->get_id(), '_gtin', true );
		if ( $gtin ) {
			$data['gtin'] = $gtin;
		}
		if ( $product->get_rating_count() > 0 ) {
			$data['aggregateRating'] = array(
				'@type'       => 'AggregateRating',
				'ratingValue' => $product->get_average_rating(),
				'reviewCount' => $product->get_review_count(),
			);
		}
		$img = wp_get_attachment_image_url( $product->get_image_id(), 'full' );
		if ( $img ) {
			$data['image'] = esc_url( $img );
		}
		$this->print_ld( $data );
	}

	/**
	 * Street address for schema, derived from the Customizer value so the markup
	 * cannot drift from the address shown in the header and footer. The stored
	 * value ends in ", Nairobi, Kenya", which PostalAddress carries separately in
	 * addressLocality / addressCountry, so that tail is trimmed here.
	 */
	private function street_address(): string {
		$addr = (string) get_theme_mod( 'toptech_address', 'This & That Exhibition, Opp. Ronald Ngala Post Office, Shop G15, Ronald Ngala Street, Nairobi, Kenya' );
		$addr = (string) preg_replace( '/,\s*Nairobi\s*,\s*Kenya\s*$/i', '', $addr );
		return trim( $addr, " ,\t\n\r" );
	}

	/**
	 * Phone in E.164, which is the format schema.org and Google expect. Falls
	 * back to the stored value when it cannot be normalised.
	 */
	private function tel_e164(): string {
		$raw    = (string) get_theme_mod( 'toptech_phone', '0797 720290' );
		$digits = (string) preg_replace( '/\D+/', '', $raw );
		if ( 10 === strlen( $digits ) && 0 === strpos( $digits, '0' ) ) {
			return '+254' . substr( $digits, 1 );
		}
		if ( 0 === strpos( $digits, '254' ) ) {
			return '+' . $digits;
		}
		return $raw;
	}

	/**
	 * MerchantReturnPolicy mirroring the Merchant Center return settings and the
	 * published Return & Refund Policy: a 14-day window for Kenya, returns in
	 * store or at a nominated drop-off point, no restocking fee, and the customer
	 * covering carriage on change-of-mind returns (we pay when the item is faulty).
	 *
	 * @return array<string,mixed>
	 */
	private function return_policy(): array {
		return (array) apply_filters(
			'toptech_schema_return_policy',
			array(
				'@type'                => 'MerchantReturnPolicy',
				'applicableCountry'    => 'KE',
				'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
				'merchantReturnDays'   => 14,
				'returnMethod'         => array(
					'https://schema.org/ReturnInStore',
					'https://schema.org/ReturnAtKiosk',
				),
				'returnFees'           => 'https://schema.org/ReturnFeesCustomerResponsibility',
				'restockingFee'        => array(
					'@type'    => 'MonetaryAmount',
					'value'    => 0,
					'currency' => 'KES',
				),
				'merchantReturnLink'   => home_url( '/return-refund-policy/' ),
			)
		);
	}

	/**
	 * OfferShippingDetails mirroring the Merchant Center shipping service: flat
	 * rate to Kenya, delivered in 1 to 5 working days. Handling is 0-1 day
	 * (orders confirmed before 3:00pm ship the same day) and transit 1-4 days, so
	 * the total Google derives stays inside the declared 1-5 day range. The flat
	 * rate is calculated at checkout, so no amount is asserted here.
	 *
	 * @return array<string,mixed>
	 */
	private function shipping_details(): array {
		return (array) apply_filters(
			'toptech_schema_shipping_details',
			array(
				'@type'               => 'OfferShippingDetails',
				'shippingDestination' => array(
					'@type'          => 'DefinedRegion',
					'addressCountry' => 'KE',
				),
				'deliveryTime'        => array(
					'@type'        => 'ShippingDeliveryTime',
					'handlingTime' => array(
						'@type'    => 'QuantitativeValue',
						'minValue' => 0,
						'maxValue' => 1,
						'unitCode' => 'DAY',
					),
					'transitTime'  => array(
						'@type'    => 'QuantitativeValue',
						'minValue' => 1,
						'maxValue' => 4,
						'unitCode' => 'DAY',
					),
				),
			)
		);
	}

	/**
	 * Add the return and shipping properties to Rank Math's product schema.
	 *
	 * On this site Rank Math emits the Product JSON-LD, so this class's own
	 * product() output is suppressed by has_seo_plugin() and anything added
	 * there would never reach the page. Rank Math's Offer carries price,
	 * availability, itemCondition, priceValidUntil, seller and url, but not
	 * hasMerchantReturnPolicy or shippingDetails, which are the two properties
	 * Google reads for Shopping and free listings. They are injected here into
	 * whichever Offer node Rank Math built, reusing the same definitions as the
	 * native output so the two can never disagree.
	 *
	 * @param mixed $entity Rank Math product entity (array when well-formed).
	 * @return mixed
	 */
	public function rank_math_product( $entity ) {
		if ( is_array( $entity ) === false ) {
			return $entity;
		}
		if ( isset( $entity['offers'] ) === false || is_array( $entity['offers'] ) === false ) {
			return $entity;
		}

		$return_policy = $this->return_policy();
		$shipping      = $this->shipping_details();

		// A single Offer is keyed directly; multiple Offers arrive as a list.
		if ( isset( $entity['offers']['@type'] ) ) {
			$entity['offers']['hasMerchantReturnPolicy'] = $return_policy;
			$entity['offers']['shippingDetails']         = $shipping;
			return $entity;
		}

		foreach ( $entity['offers'] as $key => $offer ) {
			if ( is_array( $offer ) ) {
				$entity['offers'][ $key ]['hasMerchantReturnPolicy'] = $return_policy;
				$entity['offers'][ $key ]['shippingDetails']         = $shipping;
			}
		}

		return $entity;
	}

	private function brand_name( \WC_Product $product ): string {
		foreach ( array( 'product_brand', 'pwb-brand', 'product-brand' ) as $tax ) {
			if ( taxonomy_exists( $tax ) ) {
				$terms = wp_get_post_terms( $product->get_id(), $tax, array( 'fields' => 'names' ) );
				if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
					return (string) $terms[0];
				}
			}
		}
		return (string) get_post_meta( $product->get_id(), '_powerplug_brand', true );
	}

	private function print_ld( array $data ): void {
		echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
	}
}
