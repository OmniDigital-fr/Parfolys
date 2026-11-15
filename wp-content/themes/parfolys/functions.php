<?php
/**
 * PARFOLYS, thème enfant Astra.
 */
defined( 'ABSPATH' ) || exit;

define( 'PARFOLYS_VERSION', '0.1.0' );
define( 'PARFOLYS_DIR', get_stylesheet_directory() );
define( 'PARFOLYS_URI', get_stylesheet_directory_uri() );

require PARFOLYS_DIR . '/inc/setup.php';
require PARFOLYS_DIR . '/inc/helpers.php';
require PARFOLYS_DIR . '/inc/customizer.php';
require PARFOLYS_DIR . '/inc/layout.php';
require PARFOLYS_DIR . '/inc/contact.php';

if ( class_exists( 'WooCommerce' ) ) {
	require PARFOLYS_DIR . '/inc/product-fields.php';
	require PARFOLYS_DIR . '/inc/shop.php';
}
