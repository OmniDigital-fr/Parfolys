<?php
/**
 * Template Name: Univers Parfums & Beauté
 * Entrée du bloc « Parfums & Beauté » : deux parcours distincts, Parfums et Corps & Bain.
 */
defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="primary" class="pf-univers">
	<div class="pf-container">
		<header class="pf-page-head">
			<?php if ( function_exists( 'woocommerce_breadcrumb' ) ) { woocommerce_breadcrumb(); } ?>
			<h1 class="pf-page-head__title"><?php the_title(); ?></h1>
			<p class="pf-page-head__lead"><?php esc_html_e( 'Parfums et soins parfumés, à choisir selon vos envies.', 'parfolys' ); ?></p>
		</header>

		<div class="pf-entries__grid pf-entries__grid--paths">
			<article class="pf-entry">
				<a class="pf-entry__media" href="<?php echo esc_url( parfolys_cat_url( 'parfums' ) ); ?>" tabindex="-1" aria-hidden="true">
					<?php echo parfolys_mod_image( 'parfolys_parcours_parfums_image', 'parfolys-block', '', __( 'Photo parcours Parfums (4:5)', 'parfolys' ) ); // phpcs:ignore ?>
				</a>
				<div class="pf-entry__body">
					<h2 class="pf-entry__title"><?php esc_html_e( 'Parfums', 'parfolys' ); ?></h2>
					<p class="pf-entry__text"><?php esc_html_e( 'Choisissez par famille olfactive, par format ou par marque.', 'parfolys' ); ?></p>
					<a class="pf-btn" href="<?php echo esc_url( parfolys_cat_url( 'parfums' ) ); ?>"><?php esc_html_e( 'Voir les parfums', 'parfolys' ); ?></a>
				</div>
			</article>
			<article class="pf-entry">
				<a class="pf-entry__media" href="<?php echo esc_url( parfolys_cat_url( 'corps-bain' ) ); ?>" tabindex="-1" aria-hidden="true">
					<?php echo parfolys_mod_image( 'parfolys_parcours_corps_image', 'parfolys-block', '', __( 'Photo parcours Corps & Bain (4:5)', 'parfolys' ) ); // phpcs:ignore ?>
				</a>
				<div class="pf-entry__body">
					<h2 class="pf-entry__title"><?php esc_html_e( 'Corps & Bain', 'parfolys' ); ?></h2>
					<p class="pf-entry__text"><?php esc_html_e( 'Soins parfumés pour le corps, la douche et le bain.', 'parfolys' ); ?></p>
					<a class="pf-btn" href="<?php echo esc_url( parfolys_cat_url( 'corps-bain' ) ); ?>"><?php esc_html_e( 'Voir Corps & Bain', 'parfolys' ); ?></a>
				</div>
			</article>
		</div>

		<?php
		while ( have_posts() ) :
			the_post();
			if ( trim( get_the_content() ) ) {
				echo '<div class="pf-prose">';
				the_content();
				echo '</div>';
			}
		endwhile;
		?>
	</div>
</main>
<?php
get_footer();
