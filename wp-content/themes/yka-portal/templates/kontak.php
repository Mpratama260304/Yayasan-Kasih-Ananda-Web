<?php
/**
 * Template Name: Halaman Kontak
 *
 * Contact details for the foundation and every education unit. No form is
 * shipped in the MVP: published contact details serve parents better than a
 * form whose delivery cannot yet be guaranteed.
 *
 * @package YKA_Portal
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

get_header();

$yka_has_core   = function_exists( 'yka_setting' );
$yka_address    = $yka_has_core ? (string) yka_setting( 'address' ) : '';
$yka_phone      = $yka_has_core ? (string) yka_setting( 'phone' ) : '';
$yka_whatsapp   = $yka_has_core ? (string) yka_setting( 'whatsapp' ) : '';
$yka_email      = $yka_has_core ? (string) yka_setting( 'email' ) : '';
$yka_maps       = $yka_has_core ? (string) yka_setting( 'maps_url' ) : '';
$yka_maps_embed = $yka_has_core ? (string) yka_setting( 'maps_embed' ) : '';
$yka_units      = $yka_has_core ? yka_get_school_units() : array();
?>

<div class="yka-container">
	<?php yka_breadcrumbs(); ?>
</div>

<div class="yka-container yka-section yka-section--tight">

	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<header class="yka-archive-header yka-prose">
			<h1 class="yka-archive-header__title"><?php the_title(); ?></h1>
			<p class="yka-lede">
				<?php esc_html_e( 'Hubungi kantor yayasan atau langsung ke unit pendidikan yang dituju.', 'yka-portal' ); ?>
			</p>
		</header>

		<?php if ( '' !== trim( (string) get_the_content() ) ) : ?>
			<div class="yka-content yka-mt-5"><?php the_content(); ?></div>
		<?php endif; ?>
		<?php
	endwhile;
	?>

	<div class="yka-grid yka-grid--2 yka-mt-6">
		<div>
			<?php yka_section_head( __( 'Kantor Yayasan', 'yka-portal' ), array( 'level' => 2 ) ); ?>

			<dl class="yka-contact-list">
				<div>
					<dt><?php esc_html_e( 'Alamat', 'yka-portal' ); ?></dt>
					<dd>
						<?php
						echo '' !== $yka_address
							? nl2br( esc_html( $yka_address ) )
							: wp_kses_post( yka_placeholder( __( 'Alamat resmi yayasan belum diberikan', 'yka-portal' ) ) );
						?>
					</dd>
				</div>
				<div>
					<dt><?php esc_html_e( 'Telepon', 'yka-portal' ); ?></dt>
					<dd>
						<?php if ( '' !== $yka_phone ) : ?>
							<a href="tel:<?php echo esc_attr( (string) preg_replace( '/[^0-9+]/', '', $yka_phone ) ); ?>"><?php echo esc_html( $yka_phone ); ?></a>
						<?php else : ?>
							<?php echo wp_kses_post( yka_placeholder( __( 'Nomor telepon belum diberikan', 'yka-portal' ) ) ); ?>
						<?php endif; ?>
					</dd>
				</div>
				<div>
					<dt><?php esc_html_e( 'WhatsApp', 'yka-portal' ); ?></dt>
					<dd>
						<?php if ( '' !== $yka_whatsapp ) : ?>
							<a href="<?php echo esc_url( yka_whatsapp_url( $yka_whatsapp ) ); ?>" rel="noopener" target="_blank"><?php echo esc_html( $yka_whatsapp ); ?></a>
						<?php else : ?>
							<?php echo wp_kses_post( yka_placeholder( __( 'Nomor WhatsApp belum diberikan', 'yka-portal' ) ) ); ?>
						<?php endif; ?>
					</dd>
				</div>
				<div>
					<dt><?php esc_html_e( 'Email', 'yka-portal' ); ?></dt>
					<dd>
						<?php if ( '' !== $yka_email ) : ?>
							<a href="mailto:<?php echo esc_attr( $yka_email ); ?>"><?php echo esc_html( $yka_email ); ?></a>
						<?php else : ?>
							<?php echo wp_kses_post( yka_placeholder( __( 'Email resmi belum diberikan', 'yka-portal' ) ) ); ?>
						<?php endif; ?>
					</dd>
				</div>
				<?php if ( '' !== $yka_maps ) : ?>
					<div>
						<dt><?php esc_html_e( 'Lokasi', 'yka-portal' ); ?></dt>
						<dd><a href="<?php echo esc_url( $yka_maps ); ?>" rel="noopener" target="_blank"><?php esc_html_e( 'Lihat di Google Maps', 'yka-portal' ); ?></a></dd>
					</div>
				<?php endif; ?>
			</dl>
		</div>

		<div>
			<?php if ( '' !== $yka_maps_embed ) : ?>
				<?php yka_section_head( __( 'Peta', 'yka-portal' ), array( 'level' => 2 ) ); ?>
				<div class="yka-map" data-yka-map data-src="<?php echo esc_url( $yka_maps_embed ); ?>">
					<button type="button" class="yka-btn yka-btn--secondary" data-yka-map-load>
						<?php esc_html_e( 'Tampilkan peta', 'yka-portal' ); ?>
					</button>
				</div>
				<p class="yka-muted yka-mt-5">
					<?php esc_html_e( 'Peta dimuat dari Google hanya setelah Anda menekan tombol di atas, sehingga tidak ada permintaan ke pihak ketiga saat halaman dibuka.', 'yka-portal' ); ?>
				</p>
			<?php endif; ?>
		</div>
	</div>

	<?php if ( $yka_units ) : ?>
		<section class="yka-mt-6">
			<?php yka_section_head( __( 'Kontak Unit Pendidikan', 'yka-portal' ), array( 'level' => 2 ) ); ?>

			<div class="yka-grid yka-grid--3">
				<?php foreach ( $yka_units as $yka_unit ) : ?>
					<?php
					$yka_link      = get_term_link( $yka_unit );
					$yka_key       = yka_unit_key( $yka_unit->slug );
					$yka_u_address = yka_unit_meta( $yka_unit->term_id, 'address' );
					$yka_u_phone   = yka_unit_meta( $yka_unit->term_id, 'phone' );
					$yka_u_email   = yka_unit_meta( $yka_unit->term_id, 'email' );
					?>
					<article class="yka-unit-card" style="--yka-stamp-color: var(--yka-unit-<?php echo esc_attr( $yka_key ); ?>)">
						<div class="yka-unit-card__body">
							<h3 class="yka-unit-card__title">
								<?php if ( ! is_wp_error( $yka_link ) ) : ?>
									<a href="<?php echo esc_url( $yka_link ); ?>"><?php echo esc_html( $yka_unit->name ); ?></a>
								<?php else : ?>
									<?php echo esc_html( $yka_unit->name ); ?>
								<?php endif; ?>
							</h3>

							<dl class="yka-contact-list">
								<div>
									<dt><?php esc_html_e( 'Alamat', 'yka-portal' ); ?></dt>
									<dd>
										<?php
										echo '' !== $yka_u_address
											? nl2br( esc_html( $yka_u_address ) )
											: wp_kses_post( yka_placeholder( __( 'Belum diberikan', 'yka-portal' ) ) );
										?>
									</dd>
								</div>
								<div>
									<dt><?php esc_html_e( 'Telepon', 'yka-portal' ); ?></dt>
									<dd>
										<?php
										echo '' !== $yka_u_phone
											? esc_html( $yka_u_phone )
											: wp_kses_post( yka_placeholder( __( 'Belum diberikan', 'yka-portal' ) ) );
										?>
									</dd>
								</div>
								<div>
									<dt><?php esc_html_e( 'Email', 'yka-portal' ); ?></dt>
									<dd>
										<?php
										echo '' !== $yka_u_email
											? esc_html( $yka_u_email )
											: wp_kses_post( yka_placeholder( __( 'Belum diberikan', 'yka-portal' ) ) );
										?>
									</dd>
								</div>
							</dl>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>
</div>

<?php
get_footer();
