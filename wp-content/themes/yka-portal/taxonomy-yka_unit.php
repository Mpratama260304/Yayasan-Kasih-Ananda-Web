<?php
/**
 * Education unit portal — /unit/{slug}/
 *
 * Each unit reads as its own small portal while staying inside one
 * WordPress installation. Articles are never duplicated per unit: they live
 * once, at one canonical URL, and are surfaced here through the taxonomy.
 *
 * @package YKA_Portal
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

get_header();

$yka_unit = get_queried_object();

if ( ! $yka_unit instanceof WP_Term ) {
	get_template_part( 'template-parts/content/archive-body' );
	get_footer();
	return;
}

$yka_unit_id     = $yka_unit->term_id;
$yka_key         = yka_unit_key( $yka_unit->slug );
$yka_official    = yka_unit_meta( $yka_unit_id, 'official_name' );
$yka_intro       = yka_unit_meta( $yka_unit_id, 'intro' );
$yka_hero_id     = (int) yka_unit_meta( $yka_unit_id, 'hero_id' );
$yka_logo_id     = (int) yka_unit_meta( $yka_unit_id, 'logo_id' );
$yka_legacy      = yka_unit_meta( $yka_unit_id, 'legacy_url' );
$yka_address     = yka_unit_meta( $yka_unit_id, 'address' );
$yka_phone       = yka_unit_meta( $yka_unit_id, 'phone' );
$yka_whatsapp    = yka_unit_meta( $yka_unit_id, 'whatsapp' );
$yka_email       = yka_unit_meta( $yka_unit_id, 'email' );
$yka_maps        = yka_unit_meta( $yka_unit_id, 'maps_url' );
$yka_title       = '' !== $yka_official ? $yka_official : $yka_unit->name;
$yka_paged       = max( 1, (int) get_query_var( 'paged' ) );
$yka_is_page_one = 1 === $yka_paged && ! yka_has_active_filter();

// Supporting sections only appear on the first, unfiltered view.
$yka_achievements  = $yka_is_page_one ? yka_get_category_posts( 'prestasi', 3, array(), $yka_unit ) : array();
$yka_announcements = $yka_is_page_one ? yka_get_category_posts( 'pengumuman', 3, array(), $yka_unit ) : array();
?>

<section class="yka-hero <?php echo $yka_hero_id ? '' : 'yka-hero--plain'; ?>" style="--yka-stamp-color: var(--yka-unit-<?php echo esc_attr( $yka_key ); ?>)">
	<?php if ( $yka_hero_id ) : ?>
		<div class="yka-hero__media">
			<?php yka_lcp_image( $yka_hero_id, 'yka-wide', array( 'sizes' => '100vw' ) ); ?>
		</div>
	<?php endif; ?>

	<div class="yka-container yka-container--wide yka-hero__inner">
		<div class="yka-hero__content">
			<p class="yka-hero__eyebrow"><?php esc_html_e( 'Unit Pendidikan', 'yka-portal' ); ?></p>
			<h1 class="yka-hero__title"><?php echo esc_html( $yka_title ); ?></h1>

			<?php if ( '' !== $yka_intro ) : ?>
				<p class="yka-hero__text"><?php echo esc_html( $yka_intro ); ?></p>
			<?php else : ?>
				<p class="yka-hero__text">
					<?php
					printf(
						/* translators: %s: unit name. */
						esc_html__( 'Halaman dokumentasi kegiatan %s di bawah naungan Yayasan Kasih Ananda.', 'yka-portal' ),
						esc_html( $yka_unit->name )
					);
					?>
				</p>
			<?php endif; ?>

			<div class="yka-hero__actions">
				<a class="yka-btn yka-btn--on-dark" href="#berita-unit"><?php esc_html_e( 'Berita unit ini', 'yka-portal' ); ?></a>
				<?php if ( '' !== $yka_legacy ) : ?>
					<a class="yka-btn yka-btn--on-dark" href="<?php echo esc_url( $yka_legacy ); ?>" rel="noopener" target="_blank">
						<?php esc_html_e( 'Situs lama unit', 'yka-portal' ); ?>
						<?php echo yka_icon( 'external', array( 'size' => 16 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static inline SVG. ?>
					</a>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>

<div class="yka-container">
	<?php yka_breadcrumbs(); ?>
</div>

<?php if ( $yka_announcements ) : ?>
		<section class="yka-section yka-section--tight yka-section--cream">
			<div class="yka-container">
				<?php
				yka_section_head(
					__( 'Pengumuman', 'yka-portal' ),
					array( 'note' => __( 'Informasi resmi terbaru dari unit ini.', 'yka-portal' ) )
				);
				?>
				<ul class="yka-divided-list">
					<?php foreach ( $yka_announcements as $yka_item ) : ?>
						<li><?php get_template_part( 'template-parts/content/announcement', null, array( 'post' => $yka_item ) ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>
<?php endif; ?>

<section class="yka-section" id="berita-unit">
	<div class="yka-container">
		<?php
		yka_section_head(
			sprintf(
				/* translators: %s: unit name. */
				__( 'Berita & Dokumentasi %s', 'yka-portal' ),
				$yka_unit->name
			),
			array(
				'level' => 2,
				'note'  => __( 'Seluruh artikel di bawah ini terbit satu kali di portal yayasan dan ditampilkan di sini melalui unit pendidikannya.', 'yka-portal' ),
			)
		);
		?>

		<?php
		get_template_part(
			'template-parts/content/filters',
			null,
			array(
				'base_url'   => (string) get_term_link( $yka_unit ),
				'show_units' => true,
			)
		);
		?>

		<?php if ( have_posts() ) : ?>
			<div class="yka-archive-list yka-divided-list">
				<?php
				$yka_index = 0;
				while ( have_posts() ) :
					the_post();
					++$yka_index;
					?>
					<div class="yka-archive-list__item">
						<?php
						if ( 1 === $yka_index && $yka_is_page_one ) {
							yka_story( get_post(), 'lead' );
						} else {
							yka_story( get_post(), 'row' );
						}
						?>
					</div>
				<?php endwhile; ?>
			</div>

			<?php yka_pagination(); ?>

		<?php else : ?>
			<?php
			yka_empty_state(
				sprintf(
					/* translators: %s: unit name. */
					__( 'Belum ada artikel yang diterbitkan untuk %s.', 'yka-portal' ),
					$yka_unit->name
				),
				__( 'Artikel akan muncul di sini setelah tim dokumentasi menerbitkannya.', 'yka-portal' )
			);
			?>
		<?php endif; ?>
	</div>
</section>

<?php if ( $yka_is_page_one && ! empty( $yka_achievements ) ) : ?>
	<section class="yka-section yka-section--tight yka-section--cream">
		<div class="yka-container">
			<?php
			yka_section_head(
				__( 'Prestasi', 'yka-portal' ),
				array(
					'link'       => add_query_arg( 'kategori', 'prestasi', (string) get_term_link( $yka_unit ) ),
					'link_label' => __( 'Semua prestasi unit ini', 'yka-portal' ),
				)
			);
			?>
			<div class="yka-grid yka-grid--3">
				<?php foreach ( $yka_achievements as $yka_item ) : ?>
					<?php yka_story( $yka_item, 'secondary' ); ?>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
<?php endif; ?>

<?php if ( $yka_is_page_one ) : ?>
	<section class="yka-section yka-section--tight">
		<div class="yka-container">
			<?php yka_section_head( __( 'Kontak unit', 'yka-portal' ), array( 'level' => 2 ) ); ?>

			<div class="yka-grid yka-grid--2">
				<div>
					<dl class="yka-contact-list">
						<div>
							<dt><?php esc_html_e( 'Nama resmi', 'yka-portal' ); ?></dt>
							<dd><?php echo esc_html( $yka_title ); ?></dd>
						</div>
						<div>
							<dt><?php esc_html_e( 'Alamat', 'yka-portal' ); ?></dt>
							<dd>
								<?php
								echo '' !== $yka_address
									? nl2br( esc_html( $yka_address ) )
									: wp_kses_post( yka_placeholder( __( 'Alamat unit belum diberikan', 'yka-portal' ) ) );
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
						<?php if ( '' !== $yka_whatsapp ) : ?>
							<div>
								<dt><?php esc_html_e( 'WhatsApp', 'yka-portal' ); ?></dt>
								<dd><a href="<?php echo esc_url( yka_whatsapp_url( $yka_whatsapp ) ); ?>" rel="noopener" target="_blank"><?php echo esc_html( $yka_whatsapp ); ?></a></dd>
							</div>
						<?php endif; ?>
						<div>
							<dt><?php esc_html_e( 'Email', 'yka-portal' ); ?></dt>
							<dd>
								<?php if ( '' !== $yka_email ) : ?>
									<a href="mailto:<?php echo esc_attr( $yka_email ); ?>"><?php echo esc_html( $yka_email ); ?></a>
								<?php else : ?>
									<?php echo wp_kses_post( yka_placeholder( __( 'Email unit belum diberikan', 'yka-portal' ) ) ); ?>
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
					<?php if ( $yka_logo_id ) : ?>
						<p>
						<?php
						echo wp_kses_post(
							wp_get_attachment_image(
								$yka_logo_id,
								'medium',
								false,
								array(
									'loading' => 'lazy',
									'alt'     => $yka_title,
								)
							)
						);
						?>
							</p>
					<?php endif; ?>

					<p class="yka-muted">
						<?php esc_html_e( 'Informasi di atas dikelola melalui menu Unit Pendidikan di dasbor WordPress. Bidang yang belum diisi ditampilkan sebagai penanda, bukan diisi dengan data perkiraan.', 'yka-portal' ); ?>
					</p>

					<p>
						<a class="yka-textlink" href="<?php echo esc_url( home_url( '/unit-pendidikan/' ) ); ?>">
							<?php esc_html_e( 'Lihat seluruh unit pendidikan', 'yka-portal' ); ?>
						</a>
					</p>
				</div>
			</div>
		</div>
	</section>
<?php endif; ?>

<?php
get_footer();
