<?php
/**
 * Shop sidebar (filters widget area).
 *
 * @package RickyTools
 */

defined( 'ABSPATH' ) || exit;
if ( ! is_active_sidebar( 'shop-sidebar' ) ) {
	return;
}
?>
<aside class="rk-sidebar" aria-label="<?php esc_attr_e( 'Shop filters', 'ricky-tools' ); ?>">
	<?php dynamic_sidebar( 'shop-sidebar' ); ?>
</aside>
