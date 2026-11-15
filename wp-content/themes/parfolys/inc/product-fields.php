<?php
/**
 * Champs de fiche produit PARFOLYS (méta produit WooCommerce, onglet « Fiche PARFOLYS » de l'admin).
 * Chaque champ n'est affiché en boutique que s'il est renseigné : rien n'est inventé.
 */
defined( 'ABSPATH' ) || exit;

function parfolys_product_fields() {
	return array(
		'_pf_type'        => array( 'type' => 'select', 'label' => __( 'Type de fiche', 'parfolys' ), 'options' => array(
			''          => __( 'Choisir…', 'parfolys' ),
			'parfum'    => __( 'Parfum', 'parfolys' ),
			'soin'      => __( 'Soin corps & bain', 'parfolys' ),
			'bougie'    => __( 'Bougie parfumée', 'parfolys' ),
			'diffuseur' => __( 'Diffuseur', 'parfolys' ),
			'ambiance'  => __( 'Parfum d\'ambiance', 'parfolys' ),
			'coffret'   => __( 'Coffret', 'parfolys' ),
		) ),
		'_pf_highlights'  => array( 'type' => 'textarea', 'label' => __( 'Points clés (un par ligne)', 'parfolys' ), 'desc' => __( 'Trois lignes maximum, issues de la documentation fournisseur.', 'parfolys' ) ),
		'_pf_notes_top'   => array( 'type' => 'text', 'label' => __( 'Notes de tête', 'parfolys' ), 'desc' => __( 'Séparées par des virgules. Parfums, soins parfumés, bougies.', 'parfolys' ) ),
		'_pf_notes_heart' => array( 'type' => 'text', 'label' => __( 'Notes de cœur', 'parfolys' ) ),
		'_pf_notes_base'  => array( 'type' => 'text', 'label' => __( 'Notes de fond', 'parfolys' ) ),
		'_pf_ingredients' => array( 'type' => 'textarea', 'label' => __( 'Ingrédients (liste INCI)', 'parfolys' ), 'desc' => __( 'Copier la liste exacte de l\'emballage ou de la fiche fournisseur.', 'parfolys' ) ),
		'_pf_usage'       => array( 'type' => 'textarea', 'label' => __( 'Conseils d\'utilisation', 'parfolys' ) ),
		'_pf_precautions' => array( 'type' => 'textarea', 'label' => __( 'Précautions d\'emploi', 'parfolys' ), 'desc' => __( 'Mentions obligatoires (inflammable, usage externe, pictogrammes CLP des bougies et diffuseurs…).', 'parfolys' ) ),
		'_pf_burn_time'   => array( 'type' => 'text', 'label' => __( 'Durée de combustion indicative', 'parfolys' ), 'desc' => __( 'Bougies uniquement, selon la donnée fournisseur. Ex. : 40 h', 'parfolys' ) ),
		'_pf_dispatch'    => array( 'type' => 'text', 'label' => __( 'Délai de préparation confirmé', 'parfolys' ), 'desc' => __( 'Laisser vide tant que le délai n\'est pas confirmé par le fournisseur.', 'parfolys' ) ),
	);
}

add_filter( 'woocommerce_product_data_tabs', function ( $tabs ) {
	$tabs['parfolys'] = array(
		'label'    => __( 'Fiche PARFOLYS', 'parfolys' ),
		'target'   => 'parfolys_product_data',
		'priority' => 15,
	);
	return $tabs;
} );

add_action( 'woocommerce_product_data_panels', function () {
	global $post;
	echo '<div id="parfolys_product_data" class="panel woocommerce_options_panel hidden"><div class="options_group">';
	foreach ( parfolys_product_fields() as $key => $f ) {
		$args = array(
			'id'          => $key,
			'label'       => $f['label'],
			'description' => $f['desc'] ?? '',
			'desc_tip'    => ! empty( $f['desc'] ),
			'value'       => get_post_meta( $post->ID, $key, true ),
		);
		if ( 'select' === $f['type'] ) {
			woocommerce_wp_select( $args + array( 'options' => $f['options'] ) );
		} elseif ( 'textarea' === $f['type'] ) {
			woocommerce_wp_textarea_input( $args + array( 'rows' => 4 ) );
		} else {
			woocommerce_wp_text_input( $args );
		}
	}
	echo '</div></div>';
} );

add_action( 'woocommerce_admin_process_product_object', function ( WC_Product $product ) {
	foreach ( parfolys_product_fields() as $key => $f ) {
		if ( ! isset( $_POST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			continue;
		}
		$raw   = wp_unslash( $_POST[ $key ] ); // phpcs:ignore
		$value = 'textarea' === $f['type'] ? sanitize_textarea_field( $raw ) : sanitize_text_field( $raw );
		if ( 'select' === $f['type'] && ! array_key_exists( $value, $f['options'] ) ) {
			$value = '';
		}
		$product->update_meta_data( $key, $value );
	}
} );

/** Lecture d'un champ. */
function parfolys_pf( $product, $key ) {
	$product = wc_get_product( $product );
	return $product ? trim( (string) $product->get_meta( $key ) ) : '';
}
