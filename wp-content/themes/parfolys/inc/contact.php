<?php
/**
 * Formulaire de contact natif (sans extension) : nonce, pot de miel, wp_mail.
 */
defined( 'ABSPATH' ) || exit;

function parfolys_contact_subjects() {
	return array(
		'commande' => __( 'Une commande en cours', 'parfolys' ),
		'produit'  => __( 'Un produit', 'parfolys' ),
		'retour'   => __( 'Un retour', 'parfolys' ),
		'autre'    => __( 'Autre demande', 'parfolys' ),
	);
}

add_action( 'admin_post_nopriv_parfolys_contact', 'parfolys_contact_handle' );
add_action( 'admin_post_parfolys_contact', 'parfolys_contact_handle' );

function parfolys_contact_handle() {
	$back = wp_get_referer() ? wp_get_referer() : home_url( '/contact/' );
	$back = remove_query_arg( array( 'envoi', 'erreurs' ), $back );

	if ( ! isset( $_POST['pf_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['pf_nonce'] ), 'parfolys_contact' ) ) {
		wp_safe_redirect( add_query_arg( 'envoi', 'expire', $back ) . '#pf-contact' );
		exit;
	}
	// Pot de miel : un robot remplit ce champ caché.
	if ( ! empty( $_POST['pf_site'] ) ) {
		wp_safe_redirect( add_query_arg( 'envoi', 'ok', $back ) . '#pf-contact' );
		exit;
	}

	$name    = sanitize_text_field( wp_unslash( $_POST['pf_name'] ?? '' ) );
	$email   = sanitize_email( wp_unslash( $_POST['pf_email'] ?? '' ) );
	$subject = sanitize_key( $_POST['pf_subject'] ?? '' );
	$order   = sanitize_text_field( wp_unslash( $_POST['pf_order'] ?? '' ) );
	$message = sanitize_textarea_field( wp_unslash( $_POST['pf_message'] ?? '' ) );
	$consent = ! empty( $_POST['pf_consent'] );

	$errors = array();
	if ( '' === $name ) {
		$errors[] = 'name';
	}
	if ( ! is_email( $email ) ) {
		$errors[] = 'email';
	}
	if ( ! array_key_exists( $subject, parfolys_contact_subjects() ) ) {
		$errors[] = 'subject';
	}
	if ( mb_strlen( $message ) < 10 ) {
		$errors[] = 'message';
	}
	if ( ! $consent ) {
		$errors[] = 'consent';
	}

	if ( $errors ) {
		set_transient( 'pf_contact_' . md5( $email . wp_get_session_token() . ( $_SERVER['REMOTE_ADDR'] ?? '' ) ), compact( 'name', 'email', 'subject', 'order', 'message' ), 10 * MINUTE_IN_SECONDS );
		wp_safe_redirect( add_query_arg( array( 'envoi' => 'erreur', 'erreurs' => implode( ',', $errors ), 'k' => md5( $email . wp_get_session_token() . ( $_SERVER['REMOTE_ADDR'] ?? '' ) ) ), $back ) . '#pf-contact' );
		exit;
	}

	$to   = get_theme_mod( 'parfolys_contact_email' ) ? get_theme_mod( 'parfolys_contact_email' ) : get_option( 'admin_email' );
	$subs = parfolys_contact_subjects();
	$body = sprintf(
		"Nom : %s\nE-mail : %s\nSujet : %s\nCommande : %s\n\n%s\n",
		$name, $email, $subs[ $subject ], $order ? $order : '-', $message
	);
	$sent = wp_mail( $to, '[PARFOLYS] ' . $subs[ $subject ], $body, array( 'Reply-To: ' . $name . ' <' . $email . '>' ) );

	wp_safe_redirect( add_query_arg( 'envoi', $sent ? 'ok' : 'echec', $back ) . '#pf-contact' );
	exit;
}

/** Formulaire, utilisable via le shortcode [parfolys_contact]. */
add_shortcode( 'parfolys_contact', function () {
	$state  = isset( $_GET['envoi'] ) ? sanitize_key( $_GET['envoi'] ) : ''; // phpcs:ignore
	$errors = isset( $_GET['erreurs'] ) ? array_map( 'sanitize_key', explode( ',', wp_unslash( $_GET['erreurs'] ) ) ) : array(); // phpcs:ignore
	$old    = array();
	if ( isset( $_GET['k'] ) ) { // phpcs:ignore
		$old = (array) get_transient( 'pf_contact_' . sanitize_key( $_GET['k'] ) ); // phpcs:ignore
	}
	$v = function ( $k ) use ( $old ) {
		return isset( $old[ $k ] ) ? $old[ $k ] : '';
	};
	$err = function ( $k, $msg ) use ( $errors ) {
		return in_array( $k, $errors, true ) ? '<p class="pf-field__error" id="pf-err-' . esc_attr( $k ) . '">' . esc_html( $msg ) . '</p>' : '';
	};
	$inv = function ( $k ) use ( $errors ) {
		return in_array( $k, $errors, true ) ? ' aria-invalid="true" aria-describedby="pf-err-' . esc_attr( $k ) . '"' : '';
	};

	ob_start();
	echo '<div id="pf-contact" class="pf-contact">';
	if ( 'ok' === $state ) {
		echo '<div class="pf-alert pf-alert--success" role="status">' . esc_html__( 'Merci, votre message a bien été envoyé.', 'parfolys' ) . '</div>';
	} elseif ( 'erreur' === $state ) {
		echo '<div class="pf-alert pf-alert--error" role="alert">' . esc_html__( 'Certains champs sont à corriger.', 'parfolys' ) . '</div>';
	} elseif ( 'echec' === $state ) {
		echo '<div class="pf-alert pf-alert--error" role="alert">' . esc_html__( 'Le message n\'a pas pu être envoyé. Merci de réessayer un peu plus tard.', 'parfolys' ) . '</div>';
	} elseif ( 'expire' === $state ) {
		echo '<div class="pf-alert pf-alert--error" role="alert">' . esc_html__( 'La page a expiré. Merci de renvoyer le formulaire.', 'parfolys' ) . '</div>';
	}
	?>
	<form class="pf-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" novalidate>
		<input type="hidden" name="action" value="parfolys_contact">
		<?php wp_nonce_field( 'parfolys_contact', 'pf_nonce' ); ?>
		<p class="pf-hp" aria-hidden="true"><label>Site <input type="text" name="pf_site" tabindex="-1" autocomplete="off"></label></p>

		<div class="pf-form__row">
			<p class="pf-field">
				<label for="pf_name"><?php esc_html_e( 'Nom', 'parfolys' ); ?> <span aria-hidden="true">*</span></label>
				<input id="pf_name" name="pf_name" type="text" required autocomplete="name" value="<?php echo esc_attr( $v( 'name' ) ); ?>"<?php echo $inv( 'name' ); // phpcs:ignore ?>>
				<?php echo $err( 'name', __( 'Indiquez votre nom.', 'parfolys' ) ); // phpcs:ignore ?>
			</p>
			<p class="pf-field">
				<label for="pf_email"><?php esc_html_e( 'E-mail', 'parfolys' ); ?> <span aria-hidden="true">*</span></label>
				<input id="pf_email" name="pf_email" type="email" required autocomplete="email" value="<?php echo esc_attr( $v( 'email' ) ); ?>"<?php echo $inv( 'email' ); // phpcs:ignore ?>>
				<?php echo $err( 'email', __( 'Indiquez une adresse e-mail valide.', 'parfolys' ) ); // phpcs:ignore ?>
			</p>
		</div>
		<div class="pf-form__row">
			<p class="pf-field">
				<label for="pf_subject"><?php esc_html_e( 'Votre demande concerne', 'parfolys' ); ?> <span aria-hidden="true">*</span></label>
				<select id="pf_subject" name="pf_subject" required<?php echo $inv( 'subject' ); // phpcs:ignore ?>>
					<option value=""><?php esc_html_e( 'Choisir…', 'parfolys' ); ?></option>
					<?php foreach ( parfolys_contact_subjects() as $key => $label ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $v( 'subject' ), $key ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
				<?php echo $err( 'subject', __( 'Choisissez un sujet.', 'parfolys' ) ); // phpcs:ignore ?>
			</p>
			<p class="pf-field">
				<label for="pf_order"><?php esc_html_e( 'Numéro de commande (facultatif)', 'parfolys' ); ?></label>
				<input id="pf_order" name="pf_order" type="text" value="<?php echo esc_attr( $v( 'order' ) ); ?>">
			</p>
		</div>
		<p class="pf-field">
			<label for="pf_message"><?php esc_html_e( 'Message', 'parfolys' ); ?> <span aria-hidden="true">*</span></label>
			<textarea id="pf_message" name="pf_message" rows="6" required<?php echo $inv( 'message' ); // phpcs:ignore ?>><?php echo esc_textarea( $v( 'message' ) ); ?></textarea>
			<?php echo $err( 'message', __( 'Votre message doit contenir au moins 10 caractères.', 'parfolys' ) ); // phpcs:ignore ?>
		</p>
		<p class="pf-field pf-field--check">
			<input id="pf_consent" name="pf_consent" type="checkbox" value="1" required<?php echo $inv( 'consent' ); // phpcs:ignore ?>>
			<label for="pf_consent"><?php
				printf(
					/* translators: %s: lien politique de confidentialité */
					esc_html__( 'J\'accepte que mes données soient utilisées pour répondre à ma demande (%s).', 'parfolys' ),
					'<a href="' . esc_url( get_privacy_policy_url() ? get_privacy_policy_url() : home_url( '/politique-de-confidentialite/' ) ) . '">' . esc_html__( 'confidentialité', 'parfolys' ) . '</a>'
				);
			?></label>
			<?php echo $err( 'consent', __( 'Votre accord est nécessaire pour envoyer le message.', 'parfolys' ) ); // phpcs:ignore ?>
		</p>
		<p><button class="pf-btn" type="submit"><?php esc_html_e( 'Envoyer le message', 'parfolys' ); ?></button></p>
		<p class="pf-form__legend"><?php esc_html_e( '* Champs obligatoires', 'parfolys' ); ?></p>
	</form>
	</div>
	<?php
	return ob_get_clean();
} );
