<?php
/**
 * Supports, menus, assets.
 */
defined( 'ABSPATH' ) || exit;

add_action( 'after_setup_theme', function () {
	load_child_theme_textdomain( 'parfolys', PARFOLYS_DIR . '/languages' );

	add_theme_support( 'custom-logo', array(
		'flex-width'  => true,
		'flex-height' => true,
		// Le logo original est affiché tel quel : aucun recadrage imposé.
		'header-text' => array(),
	) );
	add_theme_support( 'woocommerce' );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );

	register_nav_menus( array(
		'parfolys-primary'   => __( 'PARFOLYS : navigation principale', 'parfolys' ),
		'parfolys-secondary' => __( 'PARFOLYS : navigation secondaire', 'parfolys' ),
		'parfolys-footer-1'  => __( 'PARFOLYS : pied de page, Boutique', 'parfolys' ),
		'parfolys-footer-2'  => __( 'PARFOLYS : pied de page, Aide', 'parfolys' ),
		'parfolys-footer-3'  => __( 'PARFOLYS : pied de page, Informations', 'parfolys' ),
	) );

	add_image_size( 'parfolys-block', 960, 1200, true );   // 4:5, blocs d'entrée.
	add_image_size( 'parfolys-hero', 1200, 1200, false );
}, 20 );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'parfolys', PARFOLYS_URI . '/assets/css/parfolys.css', array( 'astra-theme-css' ), PARFOLYS_VERSION . '.' . filemtime( PARFOLYS_DIR . '/assets/css/parfolys.css' ) );
	wp_enqueue_script( 'parfolys', PARFOLYS_URI . '/assets/js/parfolys.js', array( 'jquery' ), PARFOLYS_VERSION . '.' . filemtime( PARFOLYS_DIR . '/assets/js/parfolys.js' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
	wp_localize_script( 'parfolys', 'PARFOLYS', array(
		'i18n' => array(
			'menuOpen'  => __( 'Ouvrir le menu', 'parfolys' ),
			'menuClose' => __( 'Fermer le menu', 'parfolys' ),
			'choose'     => __( 'Choisir', 'parfolys' ),
			'outOfStock' => __( 'en rupture de stock', 'parfolys' ),
		),
	) );
}, 20 );

/** Précharge des polices principales. */
add_action( 'wp_head', function () {
	foreach ( array( 'montserrat.woff2', 'libre-baskerville.woff2' ) as $f ) {
		printf( '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n", esc_url( PARFOLYS_URI . '/assets/fonts/' . $f ) );
	}
}, 1 );

/** Couleur de la barre d'adresse mobile. */
add_action( 'wp_head', function () {
	echo '<meta name="theme-color" content="#FFFFFF">' . "\n";
}, 2 );
