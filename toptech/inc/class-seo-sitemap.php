<?php
/**
 * SEO: keep transactional pages out of the XML sitemap, and redirect legacy
 * policy slugs that 404.
 *
 * Search Console reports a "reason" for every submitted URL it declines to
 * index, and the sitemap was submitting three URLs that can never be indexed:
 *
 *   /cart/        noindex, follow  -> "Submitted URL marked 'noindex'"
 *   /my-account/  noindex, follow  -> "Submitted URL marked 'noindex'"
 *   /checkout/    302 -> /cart/    -> "Page with redirect"
 *
 * None of the three is a fault in itself. Cart and My Account are correctly
 * noindex, and an empty-cart checkout correctly redirects. The fault is
 * submitting them for indexing while simultaneously telling Google not to
 * index them, which is a contradiction Google reports back as an error.
 *
 * Resolved by omission rather than by changing the pages: a transactional page
 * should not be in a sitemap at all.
 *
 * @package ToptechMachinery
 */

declare( strict_types = 1 );

namespace ToptechMachinery;

defined( 'ABSPATH' ) || exit;

/**
 * Sitemap hygiene and legacy slug redirects.
 */
class Seo_Sitemap {

	/**
	 * Slugs referenced off-site that do not resolve, mapped to the live page.
	 *
	 * /terms-and-conditions/ returns a genuine 404: the live page is
	 * /terms-conditions/. WordPress slug guessing does not bridge the gap
	 * because the strings differ by more than a trailing token. Anywhere the
	 * longer form was published externally -- Merchant Center's terms URL, an
	 * ad extension, an invoice -- it currently lands on a 404, which reads as
	 * a missing policy to a reviewer checking the site's stated terms.
	 *
	 * @var array<string,string>
	 */
	private const LEGACY_REDIRECTS = array(
		'terms-and-conditions' => 'terms-conditions',
		'terms-of-service'     => 'terms-conditions',
		'terms-of-use'         => 'terms-conditions',
	);

	/**
	 * Register hooks.
	 */
	public function hooks(): void {
		// Rank Math is the active SEO plugin and owns /sitemap_index.xml.
		add_filter( 'rank_math/sitemap/entry', array( $this, 'drop_transactional_entry' ), 10, 3 );

		// Fallback for WordPress core sitemaps, in case Rank Math is ever
		// deactivated and core resumes serving /wp-sitemap.xml.
		add_filter( 'wp_sitemaps_posts_query_args', array( $this, 'exclude_from_core_sitemap' ), 10, 2 );

		add_action( 'template_redirect', array( $this, 'redirect_legacy_slugs' ) );
	}

	/**
	 * Page IDs that must never be submitted for indexing.
	 *
	 * Read from WooCommerce settings rather than hardcoded, so the exclusion
	 * follows the pages even if they are swapped or their slugs change.
	 *
	 * @return int[]
	 */
	private function excluded_ids(): array {
		if ( ! function_exists( 'wc_get_page_id' ) ) {
			return array();
		}

		$ids = array();

		foreach ( array( 'cart', 'checkout', 'myaccount' ) as $page ) {
			$id = (int) wc_get_page_id( $page );

			if ( $id > 0 ) {
				$ids[] = $id;
			}
		}

		return $ids;
	}

	/**
	 * Permalinks of the excluded pages, without trailing slashes.
	 *
	 * @return string[]
	 */
	private function excluded_urls(): array {
		$urls = array();

		foreach ( $this->excluded_ids() as $id ) {
			$link = get_permalink( $id );

			if ( is_string( $link ) && '' !== $link ) {
				$urls[] = untrailingslashit( $link );
			}
		}

		return $urls;
	}

	/**
	 * Remove a transactional page from the Rank Math sitemap.
	 *
	 * Compares on the entry URL rather than inspecting the third argument,
	 * whose shape varies between Rank Math versions and between entry types.
	 * Returning an empty value drops the entry.
	 *
	 * @param array<string,mixed> $url    Sitemap entry.
	 * @param string              $type   Entry type.
	 * @param mixed               $object Source object.
	 * @return array<string,mixed>|false
	 */
	public function drop_transactional_entry( $url, $type = '', $object = null ) {
		if ( ! is_array( $url ) || empty( $url['loc'] ) || ! is_string( $url['loc'] ) ) {
			return $url;
		}

		if ( in_array( untrailingslashit( $url['loc'] ), $this->excluded_urls(), true ) ) {
			return false;
		}

		return $url;
	}

	/**
	 * Exclude the same pages from core WordPress sitemaps.
	 *
	 * @param array<string,mixed> $args      Query args.
	 * @param string              $post_type Post type.
	 * @return array<string,mixed>
	 */
	public function exclude_from_core_sitemap( $args, $post_type = '' ) {
		if ( 'page' !== $post_type || ! is_array( $args ) ) {
			return $args;
		}

		$ids = $this->excluded_ids();

		if ( empty( $ids ) ) {
			return $args;
		}

		$existing            = isset( $args['post__not_in'] ) && is_array( $args['post__not_in'] )
			? $args['post__not_in']
			: array();
		$args['post__not_in'] = array_values( array_unique( array_merge( $existing, $ids ) ) );

		return $args;
	}

	/**
	 * Send legacy policy slugs to their live equivalents with a 301.
	 *
	 * Only acts on a 404, so it can never shadow a real page created at one of
	 * these slugs later.
	 */
	public function redirect_legacy_slugs(): void {
		if ( ! is_404() ) {
			return;
		}

		$path = isset( $_SERVER['REQUEST_URI'] )
			? (string) wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH )
			: '';

		$slug = trim( $path, '/' );

		if ( '' === $slug || ! isset( self::LEGACY_REDIRECTS[ $slug ] ) ) {
			return;
		}

		$target = get_page_by_path( self::LEGACY_REDIRECTS[ $slug ] );

		if ( ! $target ) {
			return;
		}

		$link = get_permalink( $target );

		if ( ! is_string( $link ) || '' === $link ) {
			return;
		}

		wp_safe_redirect( $link, 301 );
		exit;
	}
}
