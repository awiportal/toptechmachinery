<?php
/**
 * Instantiate theme modules on load (each guarded).
 *
 * @package ToptechMachinery
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

require TOPTECH_DIR . 'inc/helpers.php';

$toptech_modules = array(
	'ToptechMachinery\\Setup',
	'ToptechMachinery\\Assets',
	'ToptechMachinery\\Security',
	'ToptechMachinery\\WooCommerce_Support',
	'ToptechMachinery\\Ajax',
	'ToptechMachinery\\Customizer',
	'ToptechMachinery\\Schema',
	'ToptechMachinery\\Content_Installer',
	'ToptechMachinery\\Demo_Import',
	'ToptechMachinery\\Single_Product',
		'ToptechMachinery\\Merchant_Inspector',
);

foreach ( $toptech_modules as $toptech_class ) {
	try {
		if ( class_exists( $toptech_class ) ) {
			( new $toptech_class() )->hooks();
		}
	} catch ( \Throwable $e ) {
		error_log( 'TopTech Machinery module ' . $toptech_class . ' failed: ' . $e->getMessage() );
	}
}

require TOPTECH_DIR . 'inc/required-plugins.php';
