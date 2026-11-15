<?php
/**
 * Template Name: Marques
 * Liste des marques WooCommerce (taxonomie product_brand) qui ont des produits.
 */
defined( 'ABSPATH' ) || exit;

get_header();
$brands = taxonomy_exists( 'product_brand' ) ? get_terms( array( 'taxonomy' => 'product_brand', 'hide_empty' => ! current_user_can( 'edit_products' ) ) ) : array();
?>
<main id="primary" class="pf-brands">
	<div class="pf-container">
		<header class="pf-page-head">
			<?php if ( function_exists( 'woocommerce_breadcrumb' ) ) { woocommerce_breadcrumb(); } ?>
			<h1 class="pf-page-head__title"><?php the_title(); ?></h1>
			<?php
			while ( have_posts() ) :
				the_post();
				if ( trim( get_the_content() ) ) {
					echo '<div class="pf-page-head__lead">';
					the_content();
					echo '</div>';
				}
			endwhile;
			?>
		</header>

		<?php if ( $brands && ! is_wp_error( $brands ) ) : ?>
			<ul class="pf-brand-list">
				<?php foreach ( $brands as $b ) :
					$thumb = (int) get_term_meta( $b->term_id, 'thumbnail_id', true ); ?>
					<li class="pf-brand">
						<a href="<?php echo esc_url( get_term_link( $b ) ); ?>">
							<span class="pf-brand__logo"><?php echo $thumb ? wp_get_attachment_image( $thumb, 'medium', false, array( 'alt' => '' ) ) : ''; // phpcs:ignore ?></span>
							<span class="pf-brand__name"><?php echo esc_html( $b->name ); ?></span>
							<span class="pf-brand__count"><?php echo esc_html( sprintf( _n( '%d produit', '%d produits', $b->count, 'parfolys' ), $b->count ) ); ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<div class="pf-empty"><p><?php esc_html_e( 'Les marques de la sélection seront présentées ici dès la mise en ligne du catalogue.', 'parfolys' ); ?></p></div>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();
