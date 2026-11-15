<?php
/**
 * Accueil PARFOLYS.
 */
defined( 'ABSPATH' ) || exit;

get_header();

$has_wc = function_exists( 'wc_get_product' );

// Produit réel présenté dans le hero.
$hero_product = null;
if ( $has_wc ) {
	$hero_id = (int) get_theme_mod( 'parfolys_hero_product', 0 );
	if ( $hero_id ) {
		$hero_product = wc_get_product( $hero_id );
	}
	if ( ! $hero_product ) {
		$featured = wc_get_products( array( 'featured' => true, 'limit' => 1, 'status' => 'publish' ) );
		$hero_product = $featured ? $featured[0] : null;
	}
	if ( $hero_product && 'publish' !== $hero_product->get_status() && ! current_user_can( 'edit_products' ) ) {
		$hero_product = null;
	}
}
$hero_img_id = (int) get_theme_mod( 'parfolys_hero_image', 0 );
if ( ! $hero_img_id && $hero_product ) {
	$hero_img_id = (int) $hero_product->get_image_id();
}
$parfums_url = parfolys_cat_url( 'parfums' );
?>
<main id="primary" class="pf-home">

	<section class="pf-hero" aria-labelledby="pf-hero-title">
		<div class="pf-container">
			<div class="pf-hero__panel">
				<div class="pf-hero__text">
					<h1 id="pf-hero-title" class="pf-hero__title"><?php esc_html_e( 'Découvrez le parfum qui vous ressemble.', 'parfolys' ); ?></h1>
					<p class="pf-hero__lead"><?php echo esc_html( get_theme_mod( 'parfolys_hero_text', __( 'Parfums, soins parfumés et senteurs pour la maison : une sélection soignée, avec des repères simples pour choisir.', 'parfolys' ) ) ); ?></p>
					<p class="pf-hero__cta">
						<a class="pf-btn pf-btn--lg" href="<?php echo esc_url( $parfums_url ); ?>"><?php esc_html_e( 'Découvrir les parfums', 'parfolys' ); ?></a>
					</p>
				</div>
				<figure class="pf-hero__media">
					<?php
					if ( $hero_img_id ) {
						echo wp_get_attachment_image( $hero_img_id, 'parfolys-hero', false, array(
							'class'         => 'pf-hero__img',
							'alt'           => $hero_product ? $hero_product->get_name() : '',
							'loading'       => 'eager',
							'fetchpriority' => 'high',
						) );
					} else {
						echo parfolys_placeholder( $hero_product ? __( 'Hero : ajouter la photo du produit présenté (fiche produit ou Personnaliser > PARFOLYS)', 'parfolys' ) : __( 'Hero : choisir un produit réel (Personnaliser > PARFOLYS > Accueil : hero)', 'parfolys' ), '1 / 1' ); // phpcs:ignore
					}
					if ( $hero_product ) {
						printf(
							'<figcaption class="pf-hero__caption"><a href="%s">%s<span class="pf-hero__price">%s</span></a></figcaption>',
							esc_url( $hero_product->get_permalink() ),
							esc_html( $hero_product->get_name() ),
							wp_kses_post( $hero_product->get_price_html() )
						);
					}
					?>
				</figure>
			</div>
		</div>
	</section>

	<section class="pf-entries" aria-label="<?php esc_attr_e( 'Nos deux univers', 'parfolys' ); ?>">
		<div class="pf-container pf-entries__grid">
			<article class="pf-entry">
				<a class="pf-entry__media" href="<?php echo esc_url( parfolys_page_url( 'parfums-beaute' ) ); ?>" tabindex="-1" aria-hidden="true">
					<?php echo parfolys_mod_image( 'parfolys_block_beaute_image', 'parfolys-block', '', __( 'Photo Parfums & Beauté (4:5)', 'parfolys' ) ); // phpcs:ignore ?>
				</a>
				<div class="pf-entry__body">
					<h2 class="pf-entry__title"><?php esc_html_e( 'Parfums & Beauté', 'parfolys' ); ?></h2>
					<p class="pf-entry__text"><?php esc_html_e( 'Parfums et soins parfumés, à choisir selon vos envies.', 'parfolys' ); ?></p>
					<a class="pf-btn" href="<?php echo esc_url( parfolys_page_url( 'parfums-beaute' ) ); ?>"><?php esc_html_e( 'Explorer cet univers', 'parfolys' ); ?></a>
				</div>
			</article>
			<article class="pf-entry">
				<a class="pf-entry__media" href="<?php echo esc_url( parfolys_cat_url( 'maison-parfumee' ) ); ?>" tabindex="-1" aria-hidden="true">
					<?php echo parfolys_mod_image( 'parfolys_block_maison_image', 'parfolys-block', '', __( 'Photo Maison parfumée (4:5)', 'parfolys' ) ); // phpcs:ignore ?>
				</a>
				<div class="pf-entry__body">
					<h2 class="pf-entry__title"><?php esc_html_e( 'Maison parfumée', 'parfolys' ); ?></h2>
					<p class="pf-entry__text"><?php esc_html_e( 'Des senteurs pour vos moments chez vous.', 'parfolys' ); ?></p>
					<a class="pf-btn" href="<?php echo esc_url( parfolys_cat_url( 'maison-parfumee' ) ); ?>"><?php esc_html_e( 'Explorer la maison parfumée', 'parfolys' ); ?></a>
				</div>
			</article>
		</div>
	</section>

	<?php if ( $has_wc ) : ?>
	<section class="pf-section" aria-labelledby="pf-sel-title">
		<div class="pf-container">
			<header class="pf-section__head">
				<h2 id="pf-sel-title" class="pf-section__title"><?php esc_html_e( 'Notre sélection', 'parfolys' ); ?></h2>
				<a class="pf-link-arrow" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'Toute la boutique', 'parfolys' ); ?><?php echo parfolys_icon( 'arrow' ); // phpcs:ignore ?></a>
			</header>
			<?php
			$featured_ids = wc_get_products( array( 'featured' => true, 'limit' => 4, 'return' => 'ids', 'status' => current_user_can( 'edit_products' ) ? array( 'publish', 'private' ) : 'publish' ) );
			parfolys_product_grid(
				$featured_ids ? array( 'include' => $featured_ids ) : array( 'orderby' => 'date', 'order' => 'DESC' ),
				__( 'La sélection PARFOLYS arrive bientôt.', 'parfolys' )
			);
			?>
		</div>
	</section>

	<section class="pf-section pf-section--alt" aria-labelledby="pf-care-title">
		<div class="pf-container pf-care">
			<div class="pf-care__intro">
				<h2 id="pf-care-title" class="pf-section__title"><?php esc_html_e( 'Beauté et soins', 'parfolys' ); ?></h2>
				<p><?php esc_html_e( 'Crèmes, huiles et gels douche parfumés : prolongez votre senteur préférée du bain au quotidien.', 'parfolys' ); ?></p>
				<a class="pf-btn pf-btn--outline" href="<?php echo esc_url( parfolys_cat_url( 'corps-bain' ) ); ?>"><?php esc_html_e( 'Voir Corps & Bain', 'parfolys' ); ?></a>
			</div>
			<div class="pf-care__products">
				<?php parfolys_product_grid( array( 'category' => array( 'corps-bain' ), 'limit' => 3 ), __( 'Les soins parfumés arrivent bientôt.', 'parfolys' ), 3 ); ?>
			</div>
		</div>
	</section>

	<section class="pf-section" aria-labelledby="pf-gift-title">
		<div class="pf-container">
			<header class="pf-section__head">
				<h2 id="pf-gift-title" class="pf-section__title"><?php esc_html_e( 'Idées cadeaux', 'parfolys' ); ?></h2>
				<a class="pf-link-arrow" href="<?php echo esc_url( parfolys_cat_url( 'cadeaux' ) ); ?>"><?php esc_html_e( 'Tous les cadeaux', 'parfolys' ); ?><?php echo parfolys_icon( 'arrow' ); // phpcs:ignore ?></a>
			</header>
			<?php parfolys_product_grid( array( 'category' => array( 'cadeaux' ), 'limit' => 4 ), __( 'Les idées cadeaux arrivent bientôt.', 'parfolys' ) ); ?>
		</div>
	</section>
	<?php endif; ?>

	<?php
	$posts = get_posts( array( 'numberposts' => 3, 'post_status' => 'publish' ) );
	if ( $posts ) :
		?>
	<section class="pf-section pf-section--alt" aria-labelledby="pf-tips-title">
		<div class="pf-container">
			<header class="pf-section__head">
				<h2 id="pf-tips-title" class="pf-section__title"><?php esc_html_e( 'Conseils', 'parfolys' ); ?></h2>
				<a class="pf-link-arrow" href="<?php echo esc_url( parfolys_page_url( 'conseils' ) ); ?>"><?php esc_html_e( 'Tous les conseils', 'parfolys' ); ?><?php echo parfolys_icon( 'arrow' ); // phpcs:ignore ?></a>
			</header>
			<div class="pf-tips">
				<?php foreach ( $posts as $i => $p ) : ?>
					<article class="pf-tip<?php echo 0 === $i ? ' pf-tip--lead' : ''; ?>">
						<a class="pf-tip__link" href="<?php echo esc_url( get_permalink( $p ) ); ?>">
							<div class="pf-tip__media">
								<?php echo has_post_thumbnail( $p ) ? get_the_post_thumbnail( $p, 'large', array( 'alt' => '' ) ) : parfolys_placeholder( '', '3 / 2' ); // phpcs:ignore ?>
							</div>
							<h3 class="pf-tip__title"><?php echo esc_html( get_the_title( $p ) ); ?></h3>
							<?php if ( 0 === $i ) : ?>
								<p class="pf-tip__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt( $p ), 24 ) ); ?></p>
							<?php endif; ?>
						</a>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
	<?php else : ?>
		<div class="pf-container"><?php parfolys_admin_note( __( 'Section Conseils masquée : aucun article publié.', 'parfolys' ) ); ?></div>
	<?php endif; ?>

	<?php
	$service = parfolys_lines( 'parfolys_service_items' );
	if ( $service ) :
		?>
	<section class="pf-service" aria-label="<?php esc_attr_e( 'Nos services', 'parfolys' ); ?>">
		<div class="pf-container">
			<ul class="pf-service__list">
				<?php foreach ( $service as $s ) : ?>
					<li><strong><?php echo esc_html( $s['title'] ); ?></strong><?php echo $s['text'] ? '<span>' . esc_html( $s['text'] ) . '</span>' : ''; ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	</section>
	<?php else : ?>
		<div class="pf-container"><?php parfolys_admin_note( __( 'Informations de service masquées : à renseigner une fois confirmées (Personnaliser > PARFOLYS > Informations de service).', 'parfolys' ) ); ?></div>
	<?php endif; ?>

</main>
<?php
get_footer();
