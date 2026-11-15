<?php
/**
 * Fonctions d'affichage partagées.
 */
defined( 'ABSPATH' ) || exit;

/** Icônes SVG en ligne (décoratives, aria-hidden). */
function parfolys_icon( $name ) {
	$paths = array(
		'search'  => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
		'bag'     => '<path d="M5 8h14l-1 12H6L5 8Z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/>',
		'user'    => '<circle cx="12" cy="8" r="4"/><path d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6"/>',
		'menu'    => '<path d="M4 7h16M4 12h16M4 17h16"/>',
		'close'   => '<path d="M6 6l12 12M18 6 6 18"/>',
		'arrow'   => '<path d="M5 12h14M13 6l6 6-6 6"/>',
		'box'     => '<path d="M3 7l9-4 9 4v10l-9 4-9-4V7Z"/><path d="M3 7l9 4 9-4M12 11v10"/>',
		'info'    => '<circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7.5v.5"/>',
	);
	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}
	return '<svg class="pf-icon pf-icon--' . esc_attr( $name ) . '" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths[ $name ] . '</svg>';
}

/**
 * Logo : uniquement le fichier original déposé dans Apparence > Personnaliser > Identité du site.
 * Aucun logo n'est redessiné ni remplacé par du texte visible.
 */
function parfolys_logo( $context = 'header' ) {
	$id = (int) get_theme_mod( 'custom_logo' );
	if ( 'footer' === $context && get_theme_mod( 'parfolys_logo_footer' ) ) {
		$id = (int) get_theme_mod( 'parfolys_logo_footer' );
	}
	if ( $id && wp_attachment_is_image( $id ) ) {
		// Fichier original uniquement (pas de srcset vers des miniatures recompressées).
		$meta = wp_get_attachment_metadata( $id );
		$img  = sprintf(
			'<img class="pf-logo__img" src="%1$s" width="%2$d" height="%3$d" alt="%4$s" loading="%5$s" decoding="async"%6$s>',
			esc_url( wp_get_attachment_url( $id ) ),
			(int) ( $meta['width'] ?? 0 ),
			(int) ( $meta['height'] ?? 0 ),
			esc_attr__( 'PARFOLYS, Parfums & Beauté', 'parfolys' ),
			'header' === $context ? 'eager' : 'lazy',
			'header' === $context ? ' fetchpriority="high"' : ''
		);
		printf( '<a class="pf-logo pf-logo--%1$s" href="%2$s" rel="home" aria-label="%3$s">%4$s</a>',
			esc_attr( $context ), esc_url( home_url( '/' ) ), esc_attr__( 'PARFOLYS, accueil', 'parfolys' ), $img ); // phpcs:ignore
		return;
	}
	// Pas encore de fichier : emplacement réservé, signalé seulement aux administrateurs.
	printf( '<a class="pf-logo pf-logo--%1$s pf-logo--missing" href="%2$s" rel="home"><span class="screen-reader-text">%3$s</span>%4$s</a>',
		esc_attr( $context ),
		esc_url( home_url( '/' ) ),
		esc_html__( 'PARFOLYS, accueil', 'parfolys' ),
		current_user_can( 'edit_theme_options' ) ? '<span class="pf-logo__slot" aria-hidden="true">' . esc_html__( 'Logo original à déposer', 'parfolys' ) . '</span>' : ''
	);
}

/** Menu WordPress avec repli sur une liste de liens. */
function parfolys_menu( $location, $fallback_links = array(), $class = 'pf-menu' ) {
	if ( has_nav_menu( $location ) ) {
		wp_nav_menu( array(
			'theme_location' => $location,
			'container'      => false,
			'menu_class'     => $class,
			'depth'          => 1,
			'fallback_cb'    => false,
		) );
		return;
	}
	if ( ! $fallback_links ) {
		return;
	}
	echo '<ul class="' . esc_attr( $class ) . '">';
	foreach ( $fallback_links as $label => $url ) {
		printf( '<li class="menu-item"><a href="%s">%s</a></li>', esc_url( $url ), esc_html( $label ) );
	}
	echo '</ul>';
}

/** URL d'une catégorie de produits (ou de la boutique si elle n'existe pas). */
function parfolys_cat_url( $slug ) {
	$term = get_term_by( 'slug', $slug, 'product_cat' );
	if ( $term && ! is_wp_error( $term ) ) {
		return get_term_link( $term );
	}
	return function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
}

/** URL d'une page par son slug. */
function parfolys_page_url( $slug ) {
	$page = get_page_by_path( $slug );
	return $page ? get_permalink( $page ) : home_url( '/' . $slug . '/' );
}

function parfolys_primary_links() {
	return array(
		__( 'Parfums', 'parfolys' )          => parfolys_cat_url( 'parfums' ),
		__( 'Corps & Bain', 'parfolys' )     => parfolys_cat_url( 'corps-bain' ),
		__( 'Maison parfumée', 'parfolys' )  => parfolys_cat_url( 'maison-parfumee' ),
		__( 'Cadeaux', 'parfolys' )          => parfolys_cat_url( 'cadeaux' ),
	);
}

function parfolys_secondary_links() {
	return array(
		__( 'Marques', 'parfolys' )  => parfolys_page_url( 'marques' ),
		__( 'Conseils', 'parfolys' ) => parfolys_page_url( 'conseils' ),
		__( 'À propos', 'parfolys' ) => parfolys_page_url( 'a-propos' ),
	);
}

/**
 * Visuel de remplacement déclaré (dégradé de la charte), utilisé tant qu'aucune photo réelle n'est fournie.
 * Il ne se fait jamais passer pour un produit.
 */
function parfolys_placeholder( $label = '', $ratio = '4 / 5' ) {
	$note = current_user_can( 'edit_theme_options' ) && $label ? '<span class="pf-ph__note">' . esc_html( $label ) . '</span>' : '';
	return '<div class="pf-ph" style="aspect-ratio:' . esc_attr( $ratio ) . '" role="presentation">' . $note . '</div>';
}

/** Image d'un réglage du Customizer, ou visuel de remplacement. */
function parfolys_mod_image( $mod, $size, $alt, $placeholder_label, $ratio = '4 / 5' ) {
	$id = (int) get_theme_mod( $mod );
	if ( $id && wp_attachment_is_image( $id ) ) {
		return wp_get_attachment_image( $id, $size, false, array( 'alt' => $alt, 'loading' => 'lazy', 'decoding' => 'async' ) );
	}
	return parfolys_placeholder( $placeholder_label, $ratio );
}

/** Nombre d'articles du panier. */
function parfolys_cart_count() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return 0;
	}
	return (int) WC()->cart->get_cart_contents_count();
}

/**
 * Grille de produits WooCommerce avec état vide composé.
 *
 * @param array  $query Arguments wc_get_products().
 * @param string $empty Message si aucun produit.
 */
function parfolys_product_grid( $query, $empty, $columns = 4 ) {
	if ( ! function_exists( 'wc_get_products' ) ) {
		return;
	}
	$query = wp_parse_args( $query, array(
		'status'     => current_user_can( 'edit_products' ) ? array( 'publish', 'private' ) : 'publish',
		'limit'      => $columns,
		'visibility' => 'catalog',
		'return'     => 'ids',
	) );
	$ids = wc_get_products( $query );

	if ( ! $ids ) {
		echo '<div class="pf-empty"><p>' . esc_html( $empty ) . '</p></div>';
		return;
	}

	global $post;
	$saved = $post;
	wc_set_loop_prop( 'columns', $columns );
	wc_set_loop_prop( 'name', 'parfolys' );
	woocommerce_product_loop_start();
	foreach ( $ids as $id ) {
		$post = get_post( $id ); // phpcs:ignore
		setup_postdata( $post );
		wc_get_template_part( 'content', 'product' );
	}
	woocommerce_product_loop_end();
	$post = $saved; // phpcs:ignore
	wp_reset_postdata();
	wc_reset_loop();
}

/** Lignes « Titre | texte » d'un réglage texte. */
function parfolys_lines( $mod ) {
	$raw   = (string) get_theme_mod( $mod, '' );
	$items = array();
	foreach ( preg_split( '/\r\n|\n/', $raw ) as $line ) {
		$line = trim( $line );
		if ( '' === $line ) {
			continue;
		}
		$parts   = array_map( 'trim', explode( '|', $line, 2 ) );
		$items[] = array( 'title' => $parts[0], 'text' => $parts[1] ?? '' );
	}
	return $items;
}

/** Note visible uniquement par les administrateurs (préparation). */
function parfolys_admin_note( $text ) {
	if ( current_user_can( 'edit_theme_options' ) ) {
		echo '<p class="pf-admin-note">' . parfolys_icon( 'info' ) . '<span>' . esc_html( $text ) . '</span></p>'; // phpcs:ignore
	}
}
