<?php
/**
 * Boutique : filtres de catégories, fiches produits, panier (colis séparés), états vides.
 */
defined( 'ABSPATH' ) || exit;

/* ---------------------------------------------------------------------------
 * Réglages de grille
 * ------------------------------------------------------------------------- */
add_filter( 'loop_shop_columns', function () {
	return 3;
}, 99 );
add_filter( 'loop_shop_per_page', function () {
	return 24;
}, 99 );

/** Fil d'Ariane WooCommerce, en français, sur les pages boutique. */
add_filter( 'woocommerce_breadcrumb_defaults', function ( $d ) {
	$d['delimiter']   = '<span class="pf-crumb-sep" aria-hidden="true">/</span>';
	$d['wrap_before'] = '<nav class="woocommerce-breadcrumb pf-breadcrumb" aria-label="' . esc_attr__( 'Fil d\'Ariane', 'parfolys' ) . '">';
	$d['home']        = __( 'Accueil', 'parfolys' );
	return $d;
} );
/** Structure Astra : pas de catégorie au-dessus du titre, pas de bloc méta (déjà dans le fil d'Ariane). */
add_filter( 'astra_woo_single_product_structure', function () {
	return array( 'title', 'ratings', 'price', 'short_desc', 'add_cart' );
}, 99 );
add_filter( 'astra_woo_shop_product_structure', function () {
	return array( 'title', 'ratings', 'price', 'add_cart' );
}, 99 );

/* ---------------------------------------------------------------------------
 * Filtres d'archive
 * ------------------------------------------------------------------------- */

/** Produits visibles dans le contexte courant (catégorie, marque ou boutique), sans les filtres actifs. */
function parfolys_context_product_ids() {
	static $ids = null;
	if ( null !== $ids ) {
		return $ids;
	}
	$args = array(
		'status'     => current_user_can( 'edit_products' ) ? array( 'publish', 'private' ) : 'publish',
		'limit'      => -1,
		'return'     => 'ids',
		'visibility' => 'catalog',
	);
	if ( is_product_category() ) {
		$args['category'] = array( get_queried_object()->slug );
	}
	if ( is_tax( 'product_brand' ) ) {
		$args['tax_query'] = array( array( 'taxonomy' => 'product_brand', 'field' => 'term_id', 'terms' => get_queried_object_id() ) ); // phpcs:ignore
	}
	if ( is_search() ) {
		$args['s'] = get_search_query();
	}
	$ids = wc_get_products( $args );
	return $ids;
}

/** Termes d'une taxonomie réellement présents dans le contexte, avec effectifs. */
function parfolys_context_terms( $taxonomy ) {
	$ids = parfolys_context_product_ids();
	if ( ! $ids || ! taxonomy_exists( $taxonomy ) ) {
		return array();
	}
	$counts = array();
	foreach ( $ids as $id ) {
		$terms = wp_get_post_terms( $id, $taxonomy ); // Attributs des variations portés par le parent.
		foreach ( (array) $terms as $t ) {
			if ( ! isset( $counts[ $t->term_id ] ) ) {
				$counts[ $t->term_id ] = array( 'term' => $t, 'count' => 0 );
			}
			$counts[ $t->term_id ]['count']++;
		}
	}
	uasort( $counts, function ( $a, $b ) {
		return $a['term']->term_order <=> $b['term']->term_order ?: strcmp( $a['term']->name, $b['term']->name );
	} );
	return $counts;
}

/** Valeur de filtre active pour une clé GET. */
function parfolys_filter_value( $key ) {
	return isset( $_GET[ $key ] ) ? sanitize_title( wp_unslash( $_GET[ $key ] ) ) : ''; // phpcs:ignore
}

/** Groupe de filtres sous forme de liens (un choix par groupe, accessible au clavier). */
function parfolys_filter_group( $label, $key, $items ) {
	if ( count( $items ) < 2 && ! parfolys_filter_value( $key ) ) {
		return; // Un seul choix possible : le filtre n'apporte rien.
	}
	$active = parfolys_filter_value( $key );
	echo '<div class="pf-filter"><p class="pf-filter__label" id="pf-fl-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</p><ul class="pf-chips" aria-labelledby="pf-fl-' . esc_attr( $key ) . '">';
	foreach ( $items as $item ) {
		$is   = $active === $item['slug'];
		$url  = $is ? remove_query_arg( array( $key, 'paged' ) ) : add_query_arg( $key, $item['slug'], remove_query_arg( 'paged' ) );
		$url  = preg_replace( '#/page/\d+/?#', '/', $url );
		printf(
			'<li><a class="pf-chip%1$s" href="%2$s"%3$s>%4$s <span class="pf-chip__count">%5$d</span></a></li>',
			$is ? ' is-active' : '',
			esc_url( $url ),
			$is ? ' aria-current="true"' : '',
			esc_html( $item['name'] ),
			(int) $item['count']
		);
	}
	echo '</ul></div>';
}

add_action( 'woocommerce_before_shop_loop', 'parfolys_render_filters', 5 );
add_action( 'woocommerce_no_products_found', 'parfolys_render_filters', 5 );

function parfolys_render_filters() {
	static $done = false;
	if ( $done || ! ( is_shop() || is_product_taxonomy() || is_search() ) ) {
		return;
	}
	$done = true;
	ob_start();

	// Sous-catégories (ex. Maison parfumée : bougies, diffuseurs, parfums d'ambiance).
	if ( is_product_category() ) {
		$current  = get_queried_object();
		$children = get_terms( array( 'taxonomy' => 'product_cat', 'parent' => $current->term_id, 'hide_empty' => false ) );
		$ids      = parfolys_context_product_ids();
		$items    = array();
		foreach ( $children as $child ) {
			$in = wc_get_products( array(
				'status'   => current_user_can( 'edit_products' ) ? array( 'publish', 'private' ) : 'publish',
				'category' => array( $child->slug ),
				'limit'    => -1,
				'return'   => 'ids',
			) );
			if ( $in ) {
				$items[] = array( 'name' => $child->name, 'slug' => $child->slug, 'count' => count( $in ), 'url' => get_term_link( $child ) );
			}
		}
		if ( $items ) {
			echo '<nav class="pf-subcats" aria-label="' . esc_attr__( 'Sous-catégories', 'parfolys' ) . '"><ul class="pf-chips">';
			foreach ( $items as $item ) {
				printf( '<li><a class="pf-chip" href="%s">%s <span class="pf-chip__count">%d</span></a></li>', esc_url( $item['url'] ), esc_html( $item['name'] ), (int) $item['count'] );
			}
			echo '</ul></nav>';
		}
		if ( $current->parent ) {
			$parent = get_term( $current->parent, 'product_cat' );
			printf( '<p class="pf-subcats__back"><a href="%s">%s</a></p>', esc_url( get_term_link( $parent ) ), esc_html( sprintf( __( 'Tout l\'univers %s', 'parfolys' ), $parent->name ) ) );
		}
	}

	// Attributs WooCommerce (filtrage natif filter_xxx).
	$groups = array(
		'public'            => __( 'Pour', 'parfolys' ),
		'famille-olfactive' => __( 'Famille olfactive', 'parfolys' ),
		'concentration'     => __( 'Concentration', 'parfolys' ),
		'contenance'        => __( 'Format', 'parfolys' ),
	);
	foreach ( $groups as $attr => $label ) {
		$items = array();
		foreach ( parfolys_context_terms( 'pa_' . $attr ) as $row ) {
			$items[] = array( 'name' => $row['term']->name, 'slug' => $row['term']->slug, 'count' => $row['count'] );
		}
		parfolys_filter_group( $label, 'filter_' . $attr, $items );
	}

	// Marques.
	if ( ! is_tax( 'product_brand' ) ) {
		$items = array();
		foreach ( parfolys_context_terms( 'product_brand' ) as $row ) {
			$items[] = array( 'name' => $row['term']->name, 'slug' => $row['term']->slug, 'count' => $row['count'] );
		}
		parfolys_filter_group( __( 'Marque', 'parfolys' ), 'marque', $items );
	}

	// Disponibilité (seulement s'il existe des produits indisponibles dans le contexte).
	$ids = parfolys_context_product_ids();
	$out = 0;
	foreach ( $ids as $id ) {
		$p = wc_get_product( $id );
		if ( $p && ! $p->is_in_stock() ) {
			$out++;
		}
	}
	if ( $out && $out < count( $ids ) ) {
		parfolys_filter_group( __( 'Disponibilité', 'parfolys' ), 'dispo', array(
			array( 'name' => __( 'Disponible', 'parfolys' ), 'slug' => 'en-stock', 'count' => count( $ids ) - $out ),
		) );
	}

	$html = trim( ob_get_clean() );
	if ( ! $html ) {
		return;
	}
	$active = parfolys_active_filters();
	$base   = is_product_taxonomy() ? get_term_link( get_queried_object() ) : wc_get_page_permalink( 'shop' );
	if ( is_search() ) {
		$base = add_query_arg( array( 's' => get_search_query(), 'post_type' => 'product' ), home_url( '/' ) );
	}
	echo '<details class="pf-filters-panel" data-pf-filters>';
	printf(
		'<summary class="pf-filters-panel__toggle">%s%s</summary>',
		esc_html__( 'Filtrer', 'parfolys' ),
		$active ? ' <span class="pf-filters-panel__count">' . count( $active ) . '</span>' : ''
	);
	echo '<div class="pf-filters">' . $html . '<div class="pf-filters__actions">'; // phpcs:ignore
	echo '<button type="button" class="pf-btn pf-filters__close">' . esc_html__( 'Afficher les résultats', 'parfolys' ) . '</button>';
	if ( $active ) {
		printf( '<a class="pf-filters__reset" href="%s">%s</a>', esc_url( $base ), esc_html__( 'Réinitialiser', 'parfolys' ) );
	}
	echo '</div></div></details>';

	if ( $active ) {
		echo '<ul class="pf-active-filters" aria-label="' . esc_attr__( 'Filtres actifs', 'parfolys' ) . '">';
		foreach ( $active as $f ) {
			printf(
				'<li><a class="pf-chip is-active" href="%1$s">%2$s <span aria-hidden="true">×</span><span class="screen-reader-text">%3$s</span></a></li>',
				esc_url( preg_replace( '#/page/\d+/?#', '/', remove_query_arg( array( $f['key'], 'paged' ) ) ) ),
				esc_html( $f['label'] ),
				esc_html__( '(retirer ce filtre)', 'parfolys' )
			);
		}
		printf( '<li><a class="pf-filters__reset" href="%s">%s</a></li>', esc_url( $base ), esc_html__( 'Réinitialiser les filtres', 'parfolys' ) );
		echo '</ul>';
	}
}

/** Filtres actifs (clé GET et libellé lisible). */
function parfolys_active_filters() {
	$out = array();
	foreach ( $_GET as $key => $val ) { // phpcs:ignore
		$key = sanitize_key( $key );
		$val = sanitize_title( wp_unslash( $val ) );
		if ( 0 === strpos( $key, 'filter_' ) ) {
			$term  = get_term_by( 'slug', $val, 'pa_' . substr( $key, 7 ) );
			$out[] = array( 'key' => $key, 'label' => $term ? $term->name : $val );
		} elseif ( 'marque' === $key ) {
			$term  = get_term_by( 'slug', $val, 'product_brand' );
			$out[] = array( 'key' => $key, 'label' => $term ? $term->name : $val );
		} elseif ( 'dispo' === $key ) {
			$out[] = array( 'key' => $key, 'label' => __( 'Disponible', 'parfolys' ) );
		}
	}
	return $out;
}

/** Marque et disponibilité appliquées à la requête principale. */
add_action( 'woocommerce_product_query', function ( WP_Query $q ) {
	$tax = (array) $q->get( 'tax_query' );
	$brand = parfolys_filter_value( 'marque' );
	if ( $brand && taxonomy_exists( 'product_brand' ) ) {
		$tax[] = array( 'taxonomy' => 'product_brand', 'field' => 'slug', 'terms' => $brand );
	}
	$q->set( 'tax_query', $tax );
	if ( 'en-stock' === parfolys_filter_value( 'dispo' ) ) {
		$meta   = (array) $q->get( 'meta_query' );
		$meta[] = array( 'key' => '_stock_status', 'value' => 'instock' );
		$q->set( 'meta_query', $meta );
	}
	// Les administrateurs voient les produits privés (préparation du catalogue).
	if ( current_user_can( 'edit_products' ) ) {
		$q->set( 'post_status', array( 'publish', 'private' ) );
	}
} );

/** État vide composé. */
remove_action( 'woocommerce_no_products_found', 'wc_no_products_found' );
add_action( 'woocommerce_no_products_found', function () {
	$filtered = false;
	foreach ( array_keys( $_GET ) as $k ) { // phpcs:ignore
		if ( 0 === strpos( $k, 'filter_' ) || in_array( $k, array( 'marque', 'dispo' ), true ) ) {
			$filtered = true;
		}
	}
	echo '<div class="pf-empty pf-empty--archive">';
	if ( $filtered ) {
		echo '<h2>' . esc_html__( 'Aucun produit ne correspond à ces filtres.', 'parfolys' ) . '</h2>';
		echo '<p>' . esc_html__( 'Essayez d’élargir votre sélection.', 'parfolys' ) . '</p>';
		$reset = is_product_taxonomy() ? get_term_link( get_queried_object() ) : wc_get_page_permalink( 'shop' );
		printf( '<p><a class="pf-btn" href="%s">%s</a></p>', esc_url( $reset ), esc_html__( 'Réinitialiser les filtres', 'parfolys' ) );
		echo '</div>';
		return;
	} elseif ( is_search() ) {
		echo '<h2>' . esc_html__( 'Aucun résultat pour cette recherche.', 'parfolys' ) . '</h2>';
		echo '<p>' . esc_html__( 'Essayez un nom de produit ou une famille olfactive.', 'parfolys' ) . '</p>';
	} else {
		echo '<h2>' . esc_html__( 'Cette sélection est en préparation.', 'parfolys' ) . '</h2>';
		echo '<p>' . esc_html__( 'Les produits de cet univers seront présentés ici dès la mise en ligne du catalogue.', 'parfolys' ) . '</p>';
	}
	printf( '<p><a class="pf-btn pf-btn--outline" href="%s">%s</a></p>', esc_url( parfolys_cat_url( 'parfums' ) ), esc_html__( 'Explorer les parfums', 'parfolys' ) );
	echo '</div>';
}, 10 );

/* ---------------------------------------------------------------------------
 * Cartes produit
 * ------------------------------------------------------------------------- */

/** Marque au-dessus du nom du produit. */
function parfolys_brand_name( $product_id ) {
	if ( ! taxonomy_exists( 'product_brand' ) ) {
		return null;
	}
	$terms = get_the_terms( $product_id, 'product_brand' );
	return ( $terms && ! is_wp_error( $terms ) ) ? $terms[0] : null;
}

add_action( 'astra_woo_shop_title_before', function () {
	$brand = parfolys_brand_name( get_the_ID() );
	if ( $brand ) {
		echo '<p class="pf-card__brand">' . esc_html( $brand->name ) . '</p>';
	}
}, 5 );

/** Formats disponibles sous le nom (ex. 30 ml · 100 ml). */
add_action( 'astra_woo_shop_title_after', function () {
	global $product;
	if ( ! $product ) {
		return;
	}
	$parts   = array();
	$conc    = wc_get_product_terms( $product->get_id(), 'pa_concentration', array( 'fields' => 'names' ) );
	if ( $conc && ! is_wp_error( $conc ) ) {
		$parts[] = implode( ', ', $conc );
	}
	$formats = wc_get_product_terms( $product->get_id(), 'pa_contenance', array( 'fields' => 'names' ) );
	if ( $formats && ! is_wp_error( $formats ) ) {
		natsort( $formats );
		$parts[] = implode( ' / ', $formats );
	}
	if ( $parts ) {
		echo '<p class="pf-card__formats">' . esc_html( implode( ' · ', $parts ) ) . '</p>';
	}
} );

/** Libellé « Indisponible » lisible sur la carte. */
add_action( 'woocommerce_before_shop_loop_item_title', function () {
	global $product;
	if ( $product && ! $product->is_in_stock() ) {
		echo '<span class="pf-badge pf-badge--out">' . esc_html__( 'Indisponible', 'parfolys' ) . '</span>';
	}
}, 9 );

/* ---------------------------------------------------------------------------
 * Fiche produit
 * ------------------------------------------------------------------------- */

add_action( 'astra_woo_single_title_before', function () {
	global $product;
	$brand = parfolys_brand_name( $product->get_id() );
	if ( $brand ) {
		printf( '<p class="pf-single__brand"><a href="%s">%s</a></p>', esc_url( get_term_link( $brand ) ), esc_html( $brand->name ) );
	}
} );

/** « Ce qu'il faut savoir » : sous le bloc d'achat. */
add_action( 'astra_woo_single_add_to_cart_after', function () {
	global $product;
	$notes = parfolys_notes( $product );
	$lines = array_filter( array_map( 'trim', preg_split( '/\r\n|\n/', parfolys_pf( $product, '_pf_highlights' ) ) ) );
	if ( ! $lines && ! $notes ) {
		return;
	}
	echo '<section class="pf-know" aria-labelledby="pf-know-title"><h2 class="pf-know__title" id="pf-know-title">' . esc_html__( 'Ce qu’il faut savoir', 'parfolys' ) . '</h2>';
	if ( $lines ) {
		echo '<ul class="pf-highlights">';
		foreach ( array_slice( $lines, 0, 3 ) as $l ) {
			echo '<li>' . esc_html( $l ) . '</li>';
		}
		echo '</ul>';
	}
	if ( $notes ) {
		echo '<dl class="pf-notes pf-notes--compact">';
		foreach ( $notes as $label => $value ) {
			echo '<div><dt>' . esc_html( $label ) . '</dt><dd>' . esc_html( $value ) . '</dd></div>';
		}
		echo '</dl>';
	}
	echo '</section>';
}, 10 );

function parfolys_notes( $product ) {
	return array_filter( array(
		__( 'Tête', 'parfolys' )  => parfolys_pf( $product, '_pf_notes_top' ),
		__( 'Cœur', 'parfolys' )  => parfolys_pf( $product, '_pf_notes_heart' ),
		__( 'Fond', 'parfolys' )  => parfolys_pf( $product, '_pf_notes_base' ),
	) );
}

/** Encadré livraison sous le bouton d'achat. */
add_action( 'astra_woo_single_add_to_cart_after', function () {
	global $product;
	$rows     = array();
	$dispatch = parfolys_pf( $product, '_pf_dispatch' );
	if ( $dispatch ) {
		$rows[] = sprintf( __( 'Préparation : %s', 'parfolys' ), $dispatch );
	}
	$class = $product->get_shipping_class();
	if ( 'colis-maison' === $class ) {
		$rows[] = __( 'Expédition : colis Maison parfumée. Si votre commande contient aussi des parfums ou des soins, le détail des envois est indiqué dans le panier.', 'parfolys' );
	} elseif ( 'colis-beaute' === $class ) {
		$rows[] = __( 'Expédition : colis Parfums & Beauté. Si votre commande contient aussi des articles Maison parfumée, le détail des envois est indiqué dans le panier.', 'parfolys' );
	}
	$page   = get_page_by_path( 'livraison-retours' );
	if ( ! $rows && ! $page ) {
		return;
	}
	echo '<div class="pf-delivery">' . parfolys_icon( 'box' ) . '<div>'; // phpcs:ignore
	foreach ( $rows as $r ) {
		echo '<p>' . esc_html( $r ) . '</p>';
	}
	if ( $page ) {
		printf( '<p><a href="%s">%s</a></p>', esc_url( get_permalink( $page ) ), esc_html__( 'Livraison et retours', 'parfolys' ) );
	}
	echo '</div></div>';
}, 20 );

/** Onglets adaptés au type de produit. */
add_filter( 'woocommerce_product_tabs', function ( $tabs ) {
	global $product;
	if ( ! $product ) {
		return $tabs;
	}
	if ( isset( $tabs['additional_information'] ) ) {
		$tabs['additional_information']['title'] = __( 'Caractéristiques', 'parfolys' );
	}
	if ( parfolys_notes( $product ) ) {
		$tabs['pf_notes'] = array(
			'title'    => __( 'Notes olfactives', 'parfolys' ),
			'priority' => 15,
			'callback' => function () use ( $product ) {
				echo '<dl class="pf-notes">';
				foreach ( parfolys_notes( $product ) as $label => $value ) {
					echo '<div><dt>' . esc_html( sprintf( __( 'Notes de %s', 'parfolys' ), mb_strtolower( $label ) ) ) . '</dt><dd>' . esc_html( $value ) . '</dd></div>';
				}
				echo '</dl>';
			},
		);
	}
	$ingredients = parfolys_pf( $product, '_pf_ingredients' );
	$precautions = parfolys_pf( $product, '_pf_precautions' );
	if ( $ingredients || $precautions ) {
		$tabs['pf_composition'] = array(
			'title'    => $ingredients ? __( 'Ingrédients & précautions', 'parfolys' ) : __( 'Précautions', 'parfolys' ),
			'priority' => 20,
			'callback' => function () use ( $ingredients, $precautions ) {
				if ( $ingredients ) {
					echo '<h3>' . esc_html__( 'Ingrédients', 'parfolys' ) . '</h3><p class="pf-inci">' . nl2br( esc_html( $ingredients ) ) . '</p>';
				}
				if ( $precautions ) {
					echo '<h3>' . esc_html__( 'Précautions d\'emploi', 'parfolys' ) . '</h3><p>' . nl2br( esc_html( $precautions ) ) . '</p>';
				}
			},
		);
	}
	$usage = parfolys_pf( $product, '_pf_usage' );
	$burn  = parfolys_pf( $product, '_pf_burn_time' );
	if ( $usage || $burn ) {
		$tabs['pf_usage'] = array(
			'title'    => __( 'Utilisation', 'parfolys' ),
			'priority' => 25,
			'callback' => function () use ( $usage, $burn ) {
				if ( $burn ) {
					echo '<p><strong>' . esc_html__( 'Durée de combustion indicative :', 'parfolys' ) . '</strong> ' . esc_html( $burn ) . '</p>';
				}
				if ( $usage ) {
					echo '<p>' . nl2br( esc_html( $usage ) ) . '</p>';
				}
			},
		);
	}
	return $tabs;
}, 20 );

/* ---------------------------------------------------------------------------
 * Panier et commande : transparence sur les colis séparés
 * ------------------------------------------------------------------------- */

function parfolys_cart_parcels() {
	$classes = array();
	if ( ! WC()->cart ) {
		return $classes;
	}
	foreach ( WC()->cart->get_cart() as $item ) {
		$c = $item['data']->get_shipping_class();
		if ( $c ) {
			$classes[ $c ] = true;
		}
	}
	return array_keys( $classes );
}

function parfolys_parcel_labels() {
	return array(
		'colis-beaute' => __( 'Colis Parfums & Beauté', 'parfolys' ),
		'colis-maison' => __( 'Colis Maison parfumée', 'parfolys' ),
	);
}

function parfolys_parcel_notice() {
	$classes = parfolys_cart_parcels();
	if ( ! ( in_array( 'colis-beaute', $classes, true ) && in_array( 'colis-maison', $classes, true ) ) ) {
		return;
	}
	$msg    = get_theme_mod( 'parfolys_parcel_notice', __( 'Votre commande sera expédiée en plusieurs colis. Retrouvez le détail des envois ci-dessous.', 'parfolys' ) );
	$labels = parfolys_parcel_labels();
	$groups = array();
	foreach ( WC()->cart->get_cart() as $item ) {
		$c = $item['data']->get_shipping_class();
		if ( isset( $labels[ $c ] ) ) {
			$groups[ $c ][] = $item['data']->get_name() . ( $item['quantity'] > 1 ? ' × ' . $item['quantity'] : '' );
		}
	}
	echo '<div class="pf-parcels" role="note"><p class="pf-parcels__head">' . parfolys_icon( 'box' ) . '<span>' . esc_html( $msg ) . '</span></p><ul class="pf-parcels__list">'; // phpcs:ignore
	foreach ( $groups as $c => $names ) {
		echo '<li><strong>' . esc_html( $labels[ $c ] ) . '</strong> : ' . esc_html( implode( ', ', $names ) ) . '</li>';
	}
	echo '</ul></div>';
}
add_action( 'woocommerce_before_cart_table', 'parfolys_parcel_notice' );
add_action( 'woocommerce_checkout_before_order_review', 'parfolys_parcel_notice' );

/** Colis indiqué sur chaque ligne du panier, de la commande et des e-mails. */
add_filter( 'woocommerce_get_item_data', function ( $data, $item ) {
	$class = $item['data']->get_shipping_class();
	$map   = parfolys_parcel_labels();
	if ( isset( $map[ $class ] ) && count( parfolys_cart_parcels() ) > 1 ) {
		$data[] = array( 'key' => __( 'Expédition', 'parfolys' ), 'value' => $map[ $class ] );
	}
	return $data;
}, 10, 2 );

/** Panier vide composé. */
remove_action( 'woocommerce_cart_is_empty', 'wc_empty_cart_message', 10 );
add_action( 'woocommerce_cart_is_empty', function () {
	echo '<div class="pf-empty pf-empty--cart"><h2>' . esc_html__( 'Votre panier est vide.', 'parfolys' ) . '</h2><p>' . esc_html__( 'Parcourez nos univers pour trouver une senteur qui vous plaît.', 'parfolys' ) . '</p></div>';
}, 10 );
add_filter( 'woocommerce_return_to_shop_text', function () {
	return __( 'Découvrir la boutique', 'parfolys' );
} );

/* ---------------------------------------------------------------------------
 * Divers
 * ------------------------------------------------------------------------- */

/** Pas de produits « non classés » mis en avant dans la navigation. */
add_filter( 'woocommerce_product_subcategories_args', function ( $args ) {
	$args['exclude'] = get_option( 'default_product_cat' );
	return $args;
} );

/** Libellés de bouton cohérents. */
add_filter( 'woocommerce_product_single_add_to_cart_text', function () {
	return __( 'Ajouter au panier', 'parfolys' );
} );
add_filter( 'woocommerce_product_add_to_cart_text', function ( $text, $product ) {
	if ( ! $product->is_in_stock() ) {
		return __( 'Voir les détails', 'parfolys' );
	}
	if ( $product->is_type( 'variable' ) && $product->is_purchasable() ) {
		return __( 'Choisir mon format', 'parfolys' );
	}
	return $text;
}, 10, 2 );

/** Indisponibilité : texte de la carta editorial. */
add_filter( 'woocommerce_get_availability_text', function ( $text, $product ) {
	if ( ! $product->is_in_stock() ) {
		return __( 'Ce produit est actuellement indisponible.', 'parfolys' );
	}
	return $text;
}, 10, 2 );

/**
 * Recherche produits : inclut les noms de marque, avec ou sans accent (Boticario / Boticário).
 */
add_filter( 'posts_search', function ( $search, WP_Query $q ) {
	global $wpdb;
	if ( ! $q->is_main_query() || ! $q->is_search() || is_admin() || 'product' !== $q->get( 'post_type' ) || '' === $search ) {
		return $search;
	}
	$term = trim( (string) $q->get( 's' ) );
	if ( '' === $term || ! taxonomy_exists( 'product_brand' ) ) {
		return $search;
	}
	$like = '%' . $wpdb->esc_like( remove_accents( $term ) ) . '%';
	$ids  = $wpdb->get_col( $wpdb->prepare(
		"SELECT tr.object_id FROM {$wpdb->term_relationships} tr
		 INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy IN ('product_brand','pa_famille-olfactive')
		 INNER JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
		 WHERE t.name LIKE %s OR t.slug LIKE %s",
		$like,
		'%' . $wpdb->esc_like( sanitize_title( $term ) ) . '%'
	) );
	if ( ! $ids ) {
		return $search;
	}
	$ids = implode( ',', array_map( 'absint', $ids ) );
	return preg_replace( '/^\s*AND\s*\(/', " AND ( {$wpdb->posts}.ID IN ({$ids}) OR (", $search, 1 ) . ')';
}, 10, 2 );
