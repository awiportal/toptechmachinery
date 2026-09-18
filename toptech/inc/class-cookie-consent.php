<?php
/**
 * Cookie consent banner and Google Consent Mode wiring.
 *
 * Kenya's Data Protection Act, 2019 treats analytics and advertising cookies as
 * non-essential, so consent is required before they are set. The Google for
 * WooCommerce plugin does emit a gtag consent default, but it is scoped with
 * region: [ 32 EEA/UK/CH codes ]. Kenya is absent from that list, which leaves
 * Kenyan visitors - the shop's actual market - with no declared default at all,
 * so storage is treated as granted and the tags fire on first page load. This
 * module declares an unscoped denied default ahead of that snippet and grants
 * only once the visitor has agreed.
 *
 * Page-cache safety: every byte emitted here is identical for every visitor.
 * The stored choice is never rendered into the HTML; it is read from the cookie
 * in JavaScript, which then issues gtag( 'consent', 'update', ... ) and reveals
 * the banner. That keeps the output safe to serve from the LiteSpeed page cache,
 * which would otherwise hand one visitor's consent state to the next.
 *
 * @package ToptechMachinery
 */

declare( strict_types = 1 );

namespace ToptechMachinery;

defined( 'ABSPATH' ) || exit;

/**
 * Collects cookie consent and relays it to Google Consent Mode.
 */
final class Cookie_Consent {

	public function hooks(): void {
		// Priority 1: must precede the Google for WooCommerce gtag snippet, as a
		// consent default is only honoured before the first measurement call.
		add_action( 'wp_head', array( $this, 'consent_defaults' ), 1 );
		add_action( 'wp_footer', array( $this, 'banner' ), 20 );
	}

	/**
	 * Permalink of the Cookie Policy page, with a sensible fallback.
	 */
	private function policy_url(): string {
		$page = get_page_by_path( 'cookie-policy' );
		if ( $page instanceof \WP_Post ) {
			$url = get_permalink( $page );
			if ( is_string( $url ) && strlen( $url ) > 0 ) {
				return $url;
			}
		}
		return home_url( '/cookie-policy/' );
	}

	/**
	 * Deny analytics and advertising storage by default, then grant it again in
	 * JavaScript when a prior acceptance is found in the cookie.
	 */
	public function consent_defaults(): void {
		if ( is_admin() ) {
			return;
		}
		echo '<script id="toptech-consent-defaults">';
		echo <<<'JS'
window.dataLayer = window.dataLayer || [];
function gtag(){ dataLayer.push( arguments ); }
gtag( 'consent', 'default', {
analytics_storage: 'denied',
ad_storage: 'denied',
ad_user_data: 'denied',
ad_personalization: 'denied'
} );
( function () {
var m = document.cookie.match( /(?:^|;\s*)toptech_consent=([^;]*)/ );
if ( m && decodeURIComponent( m[1] ) === 'all' ) {
gtag( 'consent', 'update', {
analytics_storage: 'granted',
ad_storage: 'granted',
ad_user_data: 'granted',
ad_personalization: 'granted'
} );
}
}() );
JS;
		echo '</script>' . "\n";
	}

	/**
	 * Banner markup, styles and behaviour. Rendered on every page and hidden by
	 * default in CSS, so a cached page never flashes the banner at a visitor who
	 * has already chosen.
	 */
	public function banner(): void {
		if ( is_admin() ) {
			return;
		}

		$policy = $this->policy_url();
		?>
<style id="toptech-cc-css">
#toptech-cc{display:none;position:fixed;left:0;right:0;bottom:0;z-index:99999;background:#10161d;color:#f3f5f7;padding:18px 20px;box-shadow:0 -2px 18px rgba(0,0,0,.28);font-size:14px;line-height:1.55}
#toptech-cc.is-open{display:block}
#toptech-cc .cc-in{max-width:1180px;margin:0 auto;display:flex;gap:18px;align-items:center;flex-wrap:wrap}
#toptech-cc p{margin:0;flex:1 1 440px}
#toptech-cc a{color:#ffc451;text-decoration:underline}
#toptech-cc .cc-btns{display:flex;gap:10px;flex-wrap:wrap}
#toptech-cc button{cursor:pointer;border:0;border-radius:4px;padding:11px 20px;font-size:14px;font-weight:600;font-family:inherit}
#toptech-cc .cc-yes{background:#ffc451;color:#10161d}
#toptech-cc .cc-no{background:transparent;color:#f3f5f7;border:1px solid rgba(243,245,247,.45)}
#toptech-cc-open{background:none;border:0;padding:0;color:inherit;font:inherit;cursor:pointer;text-decoration:underline;opacity:.75}
@media(max-width:640px){#toptech-cc .cc-btns{width:100%}#toptech-cc button{flex:1 1 auto}}
</style>
<div id="toptech-cc" role="dialog" aria-live="polite" aria-label="<?php esc_attr_e( 'Cookie consent', 'toptech-machinery' ); ?>">
	<div class="cc-in">
		<p>
			<?php esc_html_e( 'We use cookies to keep your cart and checkout working. With your agreement we also use Google Analytics and Google Ads cookies to see how the shop is used and to measure our adverts.', 'toptech-machinery' ); ?>
			<a href="<?php echo esc_url( $policy ); ?>"><?php esc_html_e( 'Read our Cookie Policy', 'toptech-machinery' ); ?></a>
		</p>
		<div class="cc-btns">
			<button type="button" class="cc-yes" data-cc="all"><?php esc_html_e( 'Accept all', 'toptech-machinery' ); ?></button>
			<button type="button" class="cc-no" data-cc="essential"><?php esc_html_e( 'Essential only', 'toptech-machinery' ); ?></button>
		</div>
	</div>
</div>
<script id="toptech-cc-js">
<?php
		echo <<<'JS'
( function () {
var box = document.getElementById( 'toptech-cc' );
if ( box === null ) { return; }

function read() {
var m = document.cookie.match( /(?:^|;\s*)toptech_consent=([^;]*)/ );
return m ? decodeURIComponent( m[1] ) : '';
}

function store( value ) {
var when = new Date();
when.setTime( when.getTime() + 15552000000 );
var secure = ( location.protocol === 'https:' ) ? '; Secure' : '';
document.cookie = 'toptech_consent=' + value + '; expires=' + when.toUTCString() + '; path=/; SameSite=Lax' + secure;
}

function apply( value ) {
if ( value === 'all' && typeof window.gtag === 'function' ) {
window.gtag( 'consent', 'update', {
analytics_storage: 'granted',
ad_storage: 'granted',
ad_user_data: 'granted',
ad_personalization: 'granted'
} );
}
}

if ( read() === '' ) { box.classList.add( 'is-open' ); }

box.addEventListener( 'click', function ( e ) {
var btn = e.target.closest( '[data-cc]' );
if ( btn === null ) { return; }
var choice = btn.getAttribute( 'data-cc' );
store( choice );
apply( choice );
box.classList.remove( 'is-open' );
} );

// Lets the visitor withdraw or change consent later, as the Act requires.
document.addEventListener( 'click', function ( e ) {
var link = e.target.closest( '#toptech-cc-open' );
if ( link === null ) { return; }
e.preventDefault();
box.classList.add( 'is-open' );
} );
}() );
JS;
?>
</script>
		<?php
	}
}
