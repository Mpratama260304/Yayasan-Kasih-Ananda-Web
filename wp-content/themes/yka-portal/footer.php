<?php
/**
 * Site footer.
 *
 * @package YKA_Portal
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

$yka_has_core    = function_exists( 'yka_setting' );
$yka_org_name    = $yka_has_core ? (string) yka_setting( 'org_name', get_bloginfo( 'name' ) ) : (string) get_bloginfo( 'name' );
$yka_description = $yka_has_core ? (string) yka_setting( 'org_description' ) : '';
$yka_address     = $yka_has_core ? (string) yka_setting( 'address' ) : '';
$yka_phone       = $yka_has_core ? (string) yka_setting( 'phone' ) : '';
$yka_email       = $yka_has_core ? (string) yka_setting( 'email' ) : '';
$yka_socials     = $yka_has_core ? yka_social_urls() : array();
$yka_units       = $yka_has_core ? yka_get_units() : array();
?>
</main><!-- .yka-main -->

<footer class="yka-footer" role="contentinfo">
	<div class="yka-container yka-container--wide">
		<div class="yka-footer__main">

			<div>
				<p class="yka-footer__brand-name"><?php echo esc_html( $yka_org_name ); ?></p>

				<?php if ( '' !== $yka_description ) : ?>
					<p class="yka-footer__description"><?php echo esc_html( $yka_description ); ?></p>
				<?php else : ?>
					<p class="yka-footer__description">
						<?php echo wp_kses_post( yka_placeholder( __( 'Deskripsi resmi yayasan belum diberikan', 'yka-portal' ) ) ); ?>
					</p>
				<?php endif; ?>

				<?php if ( $yka_socials ) : ?>
					<ul class="yka-footer__social">
						<?php foreach ( $yka_socials as $yka_network => $yka_url ) : ?>
							<li>
								<a href="<?php echo esc_url( $yka_url ); ?>" rel="noopener me" target="_blank">
									<?php
									$yka_icon_markup = yka_icon( $yka_network, array( 'size' => 18 ) );
									// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static inline SVG.
									echo '' !== $yka_icon_markup ? $yka_icon_markup : yka_icon( 'external', array( 'size' => 18 ) );
									?>
									<span class="screen-reader-text"><?php echo esc_html( ucfirst( $yka_network ) ); ?></span>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>

			<?php if ( $yka_units ) : ?>
				<div>
					<h2 class="yka-footer__title"><?php esc_html_e( 'Unit Pendidikan', 'yka-portal' ); ?></h2>
					<ul>
						<?php foreach ( $yka_units as $yka_unit ) : ?>
							<?php $yka_unit_link = get_term_link( $yka_unit ); ?>
							<?php if ( ! is_wp_error( $yka_unit_link ) ) : ?>
								<li><a href="<?php echo esc_url( $yka_unit_link ); ?>"><?php echo esc_html( $yka_unit->name ); ?></a></li>
							<?php endif; ?>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<?php if ( has_nav_menu( 'footer' ) ) : ?>
				<div>
					<h2 class="yka-footer__title"><?php esc_html_e( 'Informasi', 'yka-portal' ); ?></h2>
					<?php
					yka_nav_menu(
						'footer',
						array(
							'depth'      => 1,
							'items_wrap' => '<ul>%3$s</ul>',
						)
					);
					?>
				</div>
			<?php endif; ?>

			<div>
				<h2 class="yka-footer__title"><?php esc_html_e( 'Kontak', 'yka-portal' ); ?></h2>
				<ul>
					<li>
						<?php
						echo '' !== $yka_address
							? nl2br( esc_html( $yka_address ) )
							: wp_kses_post( yka_placeholder( __( 'Alamat resmi belum diberikan', 'yka-portal' ) ) );
						?>
					</li>
					<?php if ( '' !== $yka_phone ) : ?>
						<li><a href="tel:<?php echo esc_attr( (string) preg_replace( '/[^0-9+]/', '', $yka_phone ) ); ?>"><?php echo esc_html( $yka_phone ); ?></a></li>
					<?php endif; ?>
					<?php if ( '' !== $yka_email ) : ?>
						<li><a href="mailto:<?php echo esc_attr( $yka_email ); ?>"><?php echo esc_html( $yka_email ); ?></a></li>
					<?php endif; ?>
					<li>
						<a href="<?php echo esc_url( get_feed_link() ); ?>"><?php esc_html_e( 'Langganan RSS', 'yka-portal' ); ?></a>
					</li>
				</ul>
			</div>
		</div>

		<div class="yka-footer__bottom">
			<p class="yka-no-margin">
				<?php
				printf(
					/* translators: 1: current year, 2: organisation name. */
					esc_html__( '© %1$s %2$s. Seluruh hak cipta dilindungi.', 'yka-portal' ),
					esc_html( (string) wp_date( 'Y' ) ),
					esc_html( $yka_org_name )
				);
				?>
			</p>

			<ul>
				<?php $yka_privacy = get_privacy_policy_url(); ?>
				<?php if ( $yka_privacy ) : ?>
					<li><a href="<?php echo esc_url( $yka_privacy ); ?>"><?php esc_html_e( 'Kebijakan Privasi', 'yka-portal' ); ?></a></li>
				<?php endif; ?>
				<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Beranda', 'yka-portal' ); ?></a></li>
			</ul>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
