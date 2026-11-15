<?php
/**
 * En-tête, pied de page et mises en page (remplace le header/footer builder d'Astra par les hooks).
 */
defined( 'ABSPATH' ) || exit;

add_action( 'wp', function () {
	remove_all_actions( 'astra_header' );
	remove_all_actions( 'astra_footer' );
	remove_all_actions( 'astra_masthead' );
	add_action( 'astra_header', 'parfolys_render_header' );
	add_action( 'astra_footer', 'parfolys_render_footer' );
}, 99 );

/** Pages pleine largeur (sections gérées par le thème). */
function parfolys_is_full_width() {
	return is_front_page()
		|| is_page_template( array( 'page-templates/template-univers.php', 'page-templates/template-marques.php' ) );
}

add_filter( 'astra_get_content_layout', function ( $layout ) {
	if ( parfolys_is_full_width() ) {
		return 'page-builder';
	}
	return 'plain-container';
}, 99 );

add_filter( 'astra_page_layout', function () {
	return 'no-sidebar';
}, 99 );

/** Pas de titre Astra sur les pages qui ont leur propre en-tête. */
add_filter( 'astra_the_title_enabled', function ( $enabled ) {
	return parfolys_is_full_width() ? false : $enabled;
}, 99 );

add_filter( 'body_class', function ( $classes ) {
	$classes[] = 'parfolys';
	if ( parfolys_is_full_width() ) {
		$classes[] = 'pf-full';
	}
	return $classes;
} );

/** Le lien d'évitement pointe vers le contenu, en français. */
add_filter( 'astra_default_strings', function ( $strings ) {
	$strings['string-header-skip-link'] = __( 'Aller au contenu', 'parfolys' );
	return $strings;
} );

function parfolys_render_header() {
	$account_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : wp_login_url();
	$cart_url    = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/' );
	$count       = parfolys_cart_count();
	?>
	<header class="pf-header" id="masthead">
		<div class="pf-topbar">
			<div class="pf-container pf-topbar__in">
				<p class="pf-topbar__desc"><?php esc_html_e( 'Parfums, Beauté & Maison', 'parfolys' ); ?></p>
				<nav class="pf-topbar__nav" aria-label="<?php esc_attr_e( 'Navigation secondaire', 'parfolys' ); ?>">
					<?php parfolys_menu( 'parfolys-secondary', parfolys_secondary_links(), 'pf-menu pf-menu--secondary' ); ?>
				</nav>
			</div>
		</div>

		<div class="pf-container pf-header__main">
			<button class="pf-burger" type="button" aria-expanded="false" aria-controls="pf-drawer">
				<?php echo parfolys_icon( 'menu' ); // phpcs:ignore ?>
				<span class="screen-reader-text"><?php esc_html_e( 'Ouvrir le menu', 'parfolys' ); ?></span>
			</button>

			<div class="pf-header__brand"><?php parfolys_logo( 'header' ); ?></div>

			<form role="search" class="pf-search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
				<label class="screen-reader-text" for="pf-search-input"><?php esc_html_e( 'Rechercher un produit', 'parfolys' ); ?></label>
				<input id="pf-search-input" class="pf-search__input" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Parfum, soin, bougie…', 'parfolys' ); ?>" autocomplete="off">
				<input type="hidden" name="post_type" value="product">
				<button class="pf-search__btn" type="submit">
					<?php echo parfolys_icon( 'search' ); // phpcs:ignore ?>
					<span class="screen-reader-text"><?php esc_html_e( 'Rechercher', 'parfolys' ); ?></span>
				</button>
			</form>

			<div class="pf-header__actions">
				<a class="pf-action pf-action--account" href="<?php echo esc_url( $account_url ); ?>">
					<?php echo parfolys_icon( 'user' ); // phpcs:ignore ?>
					<span class="pf-action__label"><?php echo is_user_logged_in() ? esc_html__( 'Mon compte', 'parfolys' ) : esc_html__( 'Se connecter', 'parfolys' ); ?></span>
				</a>
				<a class="pf-action pf-action--cart" href="<?php echo esc_url( $cart_url ); ?>">
					<?php echo parfolys_icon( 'bag' ); // phpcs:ignore ?>
					<span class="pf-action__label"><?php esc_html_e( 'Panier', 'parfolys' ); ?></span>
					<?php parfolys_cart_badge( $count ); ?>
				</a>
			</div>
		</div>

		<nav class="pf-nav" aria-label="<?php esc_attr_e( 'Navigation principale', 'parfolys' ); ?>">
			<div class="pf-container">
				<?php parfolys_menu( 'parfolys-primary', parfolys_primary_links(), 'pf-menu pf-menu--primary' ); ?>
			</div>
		</nav>

		<div class="pf-drawer" id="pf-drawer" hidden>
			<div class="pf-drawer__panel" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Menu', 'parfolys' ); ?>">
				<button class="pf-drawer__close" type="button">
					<?php echo parfolys_icon( 'close' ); // phpcs:ignore ?>
					<span class="screen-reader-text"><?php esc_html_e( 'Fermer le menu', 'parfolys' ); ?></span>
				</button>
				<nav aria-label="<?php esc_attr_e( 'Navigation principale', 'parfolys' ); ?>">
					<?php parfolys_menu( 'parfolys-primary', parfolys_primary_links(), 'pf-menu pf-menu--drawer' ); ?>
				</nav>
				<nav aria-label="<?php esc_attr_e( 'Navigation secondaire', 'parfolys' ); ?>">
					<?php parfolys_menu( 'parfolys-secondary', parfolys_secondary_links(), 'pf-menu pf-menu--drawer-secondary' ); ?>
				</nav>
				<p class="pf-drawer__account"><a href="<?php echo esc_url( $account_url ); ?>"><?php echo parfolys_icon( 'user' ); // phpcs:ignore ?><span><?php echo is_user_logged_in() ? esc_html__( 'Mon compte', 'parfolys' ) : esc_html__( 'Se connecter', 'parfolys' ); ?></span></a></p>
				<p class="pf-drawer__desc"><?php esc_html_e( 'Parfums, Beauté & Maison', 'parfolys' ); ?></p>
			</div>
		</div>
	</header>
	<?php
}

function parfolys_cart_badge( $count ) {
	printf(
		'<span class="pf-cart-count%1$s" data-count="%2$d"><span aria-hidden="true">%2$d</span><span class="screen-reader-text">%3$s</span></span>',
		$count ? '' : ' is-empty',
		(int) $count,
		/* translators: %d: nombre d'articles */
		esc_html( sprintf( _n( '%d article', '%d articles', $count, 'parfolys' ), $count ) )
	);
}

/** Mise à jour AJAX du compteur panier. */
add_filter( 'woocommerce_add_to_cart_fragments', function ( $fragments ) {
	ob_start();
	parfolys_cart_badge( parfolys_cart_count() );
	$fragments['span.pf-cart-count'] = ob_get_clean();
	return $fragments;
} );

function parfolys_render_footer() {
	?>
	<footer class="pf-footer" id="colophon">
		<div class="pf-container pf-footer__grid">
			<div class="pf-footer__brand">
				<?php parfolys_logo( 'footer' ); ?>
				<p class="pf-footer__desc"><?php esc_html_e( 'Parfums, Beauté & Maison', 'parfolys' ); ?></p>
				<p class="pf-footer__sign"><?php esc_html_e( 'Le plaisir des senteurs, pour vous et votre maison.', 'parfolys' ); ?></p>
			</div>
			<nav class="pf-footer__col" aria-labelledby="pf-f1">
				<h2 class="pf-footer__title" id="pf-f1"><?php esc_html_e( 'Boutique', 'parfolys' ); ?></h2>
				<?php parfolys_menu( 'parfolys-footer-1', parfolys_primary_links() + array( __( 'Marques', 'parfolys' ) => parfolys_page_url( 'marques' ) ), 'pf-menu pf-menu--footer' ); ?>
			</nav>
			<nav class="pf-footer__col" aria-labelledby="pf-f2">
				<h2 class="pf-footer__title" id="pf-f2"><?php esc_html_e( 'Aide', 'parfolys' ); ?></h2>
				<?php
				parfolys_menu( 'parfolys-footer-2', array(
					__( 'Livraison & retours', 'parfolys' ) => parfolys_page_url( 'livraison-retours' ),
					__( 'Contact', 'parfolys' )             => parfolys_page_url( 'contact' ),
					__( 'Mon compte', 'parfolys' )          => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : wp_login_url(),
				), 'pf-menu pf-menu--footer' );
				?>
			</nav>
			<nav class="pf-footer__col" aria-labelledby="pf-f3">
				<h2 class="pf-footer__title" id="pf-f3"><?php esc_html_e( 'Informations', 'parfolys' ); ?></h2>
				<?php
				parfolys_menu( 'parfolys-footer-3', array(
					__( 'À propos', 'parfolys' )                   => parfolys_page_url( 'a-propos' ),
					__( 'Conseils', 'parfolys' )                   => parfolys_page_url( 'conseils' ),
					__( 'Conditions générales de vente', 'parfolys' ) => parfolys_page_url( 'conditions-generales-de-vente' ),
					__( 'Mentions légales', 'parfolys' )           => parfolys_page_url( 'mentions-legales' ),
					__( 'Confidentialité', 'parfolys' )            => get_privacy_policy_url() ? get_privacy_policy_url() : parfolys_page_url( 'politique-de-confidentialite' ),
				), 'pf-menu pf-menu--footer' );
				?>
			</nav>
		</div>
		<div class="pf-container pf-footer__bottom">
			<p>&copy; <?php echo esc_html( wp_date( 'Y' ) ); ?> PARFOLYS</p>
		</div>
	</footer>
	<?php
}
