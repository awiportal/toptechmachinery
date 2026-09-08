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
			'telephone' => get_theme_mod( 'toptech_phone', '0797 720290' ),
			'address'   => array(
				'@type'           => 'PostalAddress',
				'streetAddress'   => 'This & That Exhibition, Opp. Ronald Ngala Post Office, Shop G15, Ronald Ngala Street',
				'addressLocality' => 'Nairobi',
				'addressRegion'   => 'Nairobi',
				'addressCountry'  => 'KE',
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
