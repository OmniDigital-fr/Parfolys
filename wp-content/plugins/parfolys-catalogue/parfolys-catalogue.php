<?php
/**
 * Plugin Name: PARFOLYS Catalogue
 * Description: Contrôle des produits importés par l'API WooCommerce (AW Connect / AW Dropship EU) : brouillon « À valider », classe d'expédition Maison parfumée, repérage dans la liste des produits.
 * Version:     0.1.0
 * Author:      PARFOLYS
 * Requires Plugins: woocommerce
 * Text Domain: parfolys-catalogue
 */

defined( 'ABSPATH' ) || exit;

/**
 * Réglages (Options API) :
 * - parfolys_api_import_draft    : 'yes' = un produit créé par l'API reste en brouillon jusqu'à validation.
 * - parfolys_api_shipping_class  : slug de la classe d'expédition appliquée aux produits importés sans classe.
 */
function parfolys_cat_option( $key, $default ) {
	$value = get_option( $key, $default );
	return '' === $value ? $default : $value;
}

/**
 * Produit créé via l'API REST WooCommerce (AW Connect crée les produits ainsi).
 * Seule la création est concernée : les mises à jour de stock d'AW ne changent ni le statut ni la fiche.
 */
add_action( 'woocommerce_rest_insert_product_object', 'parfolys_cat_on_api_create', 10, 3 );
add_action( 'woocommerce_rest_insert_product_variation_object', 'parfolys_cat_on_api_create', 10, 3 );

function parfolys_cat_on_api_create( $product, $request, $creating ) {
	if ( ! $creating || ! $product instanceof WC_Product ) {
		return;
	}
	$changed = false;

	$product->update_meta_data( '_pf_source', 'api' );
	$product->update_meta_data( '_pf_imported_at', gmdate( 'c' ) );

	if ( ! $product->is_type( 'variation' ) ) {
		$product->update_meta_data( '_pf_needs_review', '1' );

		// Le Caderno WooCommerce demande une validation avant publication.
		if ( 'yes' === parfolys_cat_option( 'parfolys_api_import_draft', 'yes' ) && 'publish' === $product->get_status() ) {
			$product->set_status( 'draft' );
		}
	}

	if ( ! $product->get_shipping_class_id() ) {
		$slug = parfolys_cat_option( 'parfolys_api_shipping_class', 'colis-maison' );
		$term = get_term_by( 'slug', $slug, 'product_shipping_class' );
		if ( $term ) {
			$product->set_shipping_class_id( $term->term_id );
		}
	}

	$product->save();
	$changed = true;

	if ( $changed && function_exists( 'wc_get_logger' ) ) {
		wc_get_logger()->info(
			sprintf( 'Produit importé par l’API : #%d %s (statut %s)', $product->get_id(), $product->get_name(), $product->get_status() ),
			array( 'source' => 'parfolys-catalogue' )
		);
	}
}

/** Validation : publier depuis l'admin retire le marqueur « À valider ». */
add_action( 'transition_post_status', function ( $new, $old, $post ) {
	if ( 'product' !== $post->post_type || 'publish' !== $new || 'publish' === $old ) {
		return;
	}
	if ( ! is_admin() || ! current_user_can( 'edit_post', $post->ID ) ) {
		return;
	}
	delete_post_meta( $post->ID, '_pf_needs_review' );
}, 10, 3 );

/* ---------------------------------------------------------------------------
 * Liste des produits : colonne Origine et filtre « À valider »
 * ------------------------------------------------------------------------- */

add_filter( 'manage_edit-product_columns', function ( $cols ) {
	$out = array();
	foreach ( $cols as $k => $v ) {
		$out[ $k ] = $v;
		if ( 'name' === $k ) {
			$out['pf_source'] = __( 'Origine', 'parfolys-catalogue' );
		}
	}
	return $out;
}, 20 );

add_action( 'manage_product_posts_custom_column', function ( $col, $post_id ) {
	if ( 'pf_source' !== $col ) {
		return;
	}
	$source = get_post_meta( $post_id, '_pf_source', true );
	echo 'api' === $source ? esc_html__( 'Import API (AW)', 'parfolys-catalogue' ) : esc_html__( 'Manuel', 'parfolys-catalogue' );
	if ( get_post_meta( $post_id, '_pf_needs_review', true ) ) {
		echo '<br><mark style="background:#F7F1E8;color:#76182A;padding:1px 6px;border-radius:4px;font-weight:600">' . esc_html__( 'À valider', 'parfolys-catalogue' ) . '</mark>';
	}
}, 10, 2 );

add_filter( 'views_edit-product', function ( $views ) {
	$count = (int) ( new WP_Query( array(
		'post_type'      => 'product',
		'post_status'    => 'any',
		'meta_key'       => '_pf_needs_review', // phpcs:ignore
		'fields'         => 'ids',
		'posts_per_page' => 1,
	) ) )->found_posts;
	$url   = add_query_arg( array( 'post_type' => 'product', 'pf_review' => 1 ), admin_url( 'edit.php' ) );
	$class = isset( $_GET['pf_review'] ) ? ' class="current"' : ''; // phpcs:ignore
	$views['pf_review'] = sprintf( '<a href="%s"%s>%s <span class="count">(%d)</span></a>', esc_url( $url ), $class, esc_html__( 'À valider', 'parfolys-catalogue' ), $count );
	return $views;
} );

add_action( 'pre_get_posts', function ( WP_Query $q ) {
	if ( is_admin() && $q->is_main_query() && 'product' === $q->get( 'post_type' ) && ! empty( $_GET['pf_review'] ) ) { // phpcs:ignore
		$q->set( 'meta_key', '_pf_needs_review' ); // phpcs:ignore
		$q->set( 'post_status', 'any' );
	}
} );

/** Rappel sur la fiche produit en cours de validation. */
add_action( 'admin_notices', function () {
	$screen = get_current_screen();
	if ( ! $screen || 'product' !== $screen->id ) {
		return;
	}
	global $post;
	if ( $post && get_post_meta( $post->ID, '_pf_needs_review', true ) ) {
		echo '<div class="notice notice-warning"><p><strong>' . esc_html__( 'Produit importé par l’API AW, à valider avant publication :', 'parfolys-catalogue' ) . '</strong> '
			. esc_html__( 'marque effective, catégorie, nom factuel (marque + nom + type + format), notes ou famille olfactive documentées, précautions, photos et droits, puis l’onglet « Fiche PARFOLYS ».', 'parfolys-catalogue' ) . '</p></div>';
	}
} );
