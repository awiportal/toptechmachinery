<?php
/**
 * Instantiate theme modules on load (each guarded).
 *
 * @package RickyTools
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

require RICKY_DIR . 'inc/helpers.php';

$ricky_modules = array(
	'RickyTools\\Setup',
	'RickyTools\\Assets',
	'RickyTools\\Security',
	'RickyTools\\WooCommerce_Support',
	'RickyTools\\Ajax',
	'RickyTools\\Customizer',
	'RickyTools\\Schema',
	'RickyTools\\Content_Installer',
	'RickyTools\\Demo_Import',
	'RickyTools\\Single_Product',
		'RickyTools\\Merchant_Inspector',
);

foreach ( $ricky_modules as $ricky_class ) {
	try {
		if ( class_exists( $ricky_class ) ) {
			( new $ricky_class() )->hooks();
		}
	} catch ( \Throwable $e ) {
		error_log( 'Ricky Tools module ' . $ricky_class . ' failed: ' . $e->getMessage() );
	}
}

require RICKY_DIR . 'inc/required-plugins.php';
