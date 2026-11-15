<?php
/**
 * Réglages PARFOLYS (Apparence > Personnaliser > PARFOLYS).
 * Tout le contenu variable de l'accueil est stocké en theme_mods, rien n'est figé dans le code.
 */
defined( 'ABSPATH' ) || exit;

add_action( 'customize_register', function ( WP_Customize_Manager $wp ) {

	$wp->add_panel( 'parfolys', array(
		'title'    => __( 'PARFOLYS', 'parfolys' ),
		'priority' => 25,
	) );

	// Accueil : hero.
	$wp->add_section( 'parfolys_hero', array( 'title' => __( 'Accueil : hero', 'parfolys' ), 'panel' => 'parfolys' ) );

	$wp->add_setting( 'parfolys_hero_product', array( 'default' => 0, 'sanitize_callback' => 'absint' ) );
	$wp->add_control( 'parfolys_hero_product', array(
		'label'       => __( 'Produit présenté (ID WooCommerce)', 'parfolys' ),
		'description' => __( 'Le hero affiche la photo, le nom et le lien de ce produit réel. Laisser 0 pour utiliser le premier produit mis en avant.', 'parfolys' ),
		'section'     => 'parfolys_hero',
		'type'        => 'number',
	) );

	$wp->add_setting( 'parfolys_hero_image', array( 'default' => 0, 'sanitize_callback' => 'absint' ) );
	$wp->add_control( new WP_Customize_Media_Control( $wp, 'parfolys_hero_image', array(
		'label'       => __( 'Photo du hero (facultatif)', 'parfolys' ),
		'description' => __( 'Remplace la photo principale du produit présenté, par exemple par une photo de mise en scène du même produit.', 'parfolys' ),
		'section'     => 'parfolys_hero',
		'mime_type'   => 'image',
	) ) );

	$wp->add_setting( 'parfolys_hero_text', array(
		'default'           => __( 'Explorez les parfums, la beauté et les senteurs pour la maison, avec des repères pour choisir selon vos envies.', 'parfolys' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp->add_control( 'parfolys_hero_text', array(
		'label'   => __( 'Texte sous le titre', 'parfolys' ),
		'section' => 'parfolys_hero',
		'type'    => 'textarea',
	) );

	// Accueil : les deux blocs d'entrée.
	$wp->add_section( 'parfolys_blocks', array(
		'title'       => __( 'Accueil : deux univers', 'parfolys' ),
		'description' => __( 'Utilisez deux photos de même format (4:5 recommandé). Elles sont recadrées à l\'identique.', 'parfolys' ),
		'panel'       => 'parfolys',
	) );
	foreach ( array(
		'parfolys_block_beaute_image' => __( 'Photo « Parfums & Beauté »', 'parfolys' ),
		'parfolys_block_maison_image' => __( 'Photo « Maison parfumée »', 'parfolys' ),
	) as $id => $label ) {
		$wp->add_setting( $id, array( 'default' => 0, 'sanitize_callback' => 'absint' ) );
		$wp->add_control( new WP_Customize_Media_Control( $wp, $id, array(
			'label'     => $label,
			'section'   => 'parfolys_blocks',
			'mime_type' => 'image',
		) ) );
	}

	// Page Parfums & Beauté : deux parcours.
	$wp->add_section( 'parfolys_univers', array( 'title' => __( 'Page Parfums & Beauté', 'parfolys' ), 'panel' => 'parfolys' ) );
	foreach ( array(
		'parfolys_parcours_parfums_image' => __( 'Photo du parcours Parfums', 'parfolys' ),
		'parfolys_parcours_corps_image'   => __( 'Photo du parcours Corps & Bain', 'parfolys' ),
	) as $id => $label ) {
		$wp->add_setting( $id, array( 'default' => 0, 'sanitize_callback' => 'absint' ) );
		$wp->add_control( new WP_Customize_Media_Control( $wp, $id, array(
			'label'     => $label,
			'section'   => 'parfolys_univers',
			'mime_type' => 'image',
		) ) );
	}

	// Informations de service confirmées.
	$wp->add_section( 'parfolys_service', array(
		'title'       => __( 'Informations de service', 'parfolys' ),
		'description' => __( 'Une ligne par engagement, au format « Titre | texte ». N\'indiquez que des informations confirmées (livraison, retours, paiement, contact). Laisser vide pour masquer la section.', 'parfolys' ),
		'panel'       => 'parfolys',
	) );
	$wp->add_setting( 'parfolys_service_items', array( 'default' => '', 'sanitize_callback' => 'sanitize_textarea_field' ) );
	$wp->add_control( 'parfolys_service_items', array(
		'label'   => __( 'Engagements affichés', 'parfolys' ),
		'section' => 'parfolys_service',
		'type'    => 'textarea',
	) );

	$wp->add_setting( 'parfolys_parcel_notice', array(
		'default'           => __( 'Votre commande sera expédiée en plusieurs colis. Retrouvez le détail des envois ci-dessous.', 'parfolys' ),
		'sanitize_callback' => 'sanitize_textarea_field',
	) );
	$wp->add_control( 'parfolys_parcel_notice', array(
		'label'       => __( 'Message « colis séparés »', 'parfolys' ),
		'description' => __( 'Affiché dans le panier et la commande lorsque le panier contient les deux classes d\'expédition.', 'parfolys' ),
		'section'     => 'parfolys_service',
		'type'        => 'textarea',
	) );

	$wp->add_setting( 'parfolys_contact_email', array( 'default' => '', 'sanitize_callback' => 'sanitize_email' ) );
	$wp->add_control( 'parfolys_contact_email', array(
		'label'       => __( 'E-mail de réception du formulaire de contact', 'parfolys' ),
		'description' => __( 'Par défaut : l\'e-mail d\'administration du site.', 'parfolys' ),
		'section'     => 'parfolys_service',
		'type'        => 'email',
	) );
} );
