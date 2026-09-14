<?php
/**
 * Homepage.
 *
 * An institutional newsroom, not a brochure. Everything below is driven by
 * queries, so publishing an article updates the homepage without anyone
 * editing a template.
 *
 * @package YKA_Portal
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

get_header();

$yka_has_core = function_exists( 'yka_setting' );

/* -------------------------------------------------- hero configuration */
$yka_hero_id      = $yka_has_core ? (int) yka_setting( 'hero_image', 0 ) : 0;
$yka_hero_heading = $yka_has_core ? (string) yka_setting( 'hero_heading' ) : '';
$yka_hero_text    = $yka_has_core ? (string) yka_setting( 'hero_text' ) : '';
$yka_org_name     = $yka_has_core ? (string) yka_setting( 'org_name', get_bloginfo( 'name' ) ) : (string) get_bloginfo( 'name' );

if ( '' === $yka_hero_heading ) {
	$yka_hero_heading = $yka_org_name;
}

if ( '' === $yka_hero_text ) {
	$yka_hero_text = __( 'Yayasan Kasih Ananda menaungi unit pendidikan SD, SMP, dan SMK. Portal ini memuat dokumentasi kegiatan, pengumuman, dan informasi resmi dari setiap unit.', 'yka-portal' );
}

/*
------------------------------------------------- content selection
 * Post ids are collected as they are used so no article appears twice on
 * the page, and each section runs one bounded query.
 */
$yka_used = array();

$yka_featured = $yka_has_core ? yka_featured_post() : null;
if ( $yka_featured instanceof WP_Post ) {
	$yka_used[] = $yka_featured->ID;
}

$yka_recent = yka_get_recent_posts( 7, $yka_used );
foreach ( $yka_recent as $yka_item ) {
	$yka_used[] = $yka_item->ID;
}

$yka_secondary = array_slice( $yka_recent, 0, 2 );
$yka_headlines = array_slice( $yka_recent, 2, 5 );

$yka_announcements = yka_get_category_posts( 'pengumuman', 4 );
$yka_achievements  = yka_get_category_posts( 'prestasi', 3, wp_list_pluck( $yka_announcements, 'ID' ) );

$yka_units      = $yka_has_core ? yka_get_school_units() : array();
$yka_posts_page = (int) get_option( 'page_for_posts' );
$yka_news_url   = $yka_posts_page ? (string) get_permalink( $yka_posts_page ) : home_url( '/' );
$yka_about      = get_page_by_path( 'tentang-yayasan' );
?>

<section class="yka-hero <?php echo $yka_hero_id ? '' : 'yka-hero--plain'; ?>">
	<?php if ( $yka_hero_id ) : ?>
		<div class="yka-hero__media">
			<?php
			// The hero is the LCP element: eager, high priority, never lazy.
			yka_lcp_image(
				$yka_hero_id,
				'yka-wide',
				array(
					'sizes' => '100vw',
					'alt'   => (string) get_post_meta( $yka_hero_id, '_wp_attachment_image_alt', true ),
				)
			);
			?>
		</div>
	<?php endif; ?>

	<div class="yka-container yka-container--wide yka-hero__inner">
		<div class="yka-hero__content">
			<p class="yka-hero__eyebrow"><?php esc_html_e( 'Portal Resmi', 'yka-portal' ); ?></p>
			<h1 class="yka-hero__title"><?php echo esc_html( $yka_hero_heading ); ?></h1>
			<p class="yka-hero__text"><?php echo esc_html( $yka_hero_text ); ?></p>

			<div class="yka-hero__actions">
				<?php if ( $yka_about instanceof WP_Post ) : ?>
					<a class="yka-btn yka-btn--on-dark" href="<?php echo esc_url( (string) get_permalink( $yka_about ) ); ?>">
						<?php esc_html_e( 'Profil Yayasan', 'yka-portal' ); ?>
					</a>
				<?php endif; ?>
				<a class="yka-btn yka-btn--on-dark" href="<?php echo esc_url( $yka_news_url ); ?>">
					<?php esc_html_e( 'Berita Terbaru', 'yka-portal' ); ?>
				</a>
			</div>
		</div>
	</div>

	<?php if ( $yka_hero_id ) : ?>
		<?php $yka_hero_caption = wp_get_attachment_caption( $yka_hero_id ); ?>
		<?php if ( is_string( $yka_hero_caption ) && '' !== $yka_hero_caption ) : ?>
			<p class="yka-hero__credit"><?php echo esc_html( wp_strip_all_tags( $yka_hero_caption ) ); ?></p>
		<?php endif; ?>
	<?php endif; ?>
</section>

<?php /* ------------------------------------------------ latest news */ ?>
<section class="yka-section">
	<div class="yka-container yka-container--wide">
		<?php
		yka_section_head(
			__( 'Berita &amp; Dokumentasi Terbaru', 'yka-portal' ),
			array(
				'note'       => __( 'Kegiatan, pengumuman, dan capaian dari seluruh unit pendidikan.', 'yka-portal' ),
				'link'       => $yka_news_url,
				'link_label' => __( 'Semua berita', 'yka-portal' ),
				'id'         => 'berita-terbaru',
			)
		);
		?>

		<?php if ( $yka_featured instanceof WP_Post ) : ?>

			<div class="yka-newsgrid">
				<div class="yka-newsgrid__lead">
					<?php yka_story( $yka_featured, 'lead' ); ?>
				</div>

				<?php if ( $yka_secondary ) : ?>
					<div class="yka-newsgrid__secondary">
						<?php foreach ( $yka_secondary as $yka_item ) : ?>
							<?php yka_story( $yka_item, 'secondary' ); ?>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<?php if ( $yka_headlines ) : ?>
					<div class="yka-newsgrid__headlines">
						<h3 class="yka-aside-block__title"><?php esc_html_e( 'Terbaru lainnya', 'yka-portal' ); ?></h3>
						<ul class="yka-divided-list">
							<?php foreach ( $yka_headlines as $yka_item ) : ?>
								<li><?php yka_story( $yka_item, 'headline' ); ?></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>
			</div>

		<?php else : ?>
			<?php
			yka_empty_state(
				__( 'Belum ada artikel yang diterbitkan.', 'yka-portal' ),
				__( 'Setelah tim dokumentasi menerbitkan berita pertama, bagian ini akan terisi otomatis.', 'yka-portal' )
			);
			?>
		<?php endif; ?>
	</div>
</section>

<?php /* --------------------------------------------- unit pendidikan */ ?>
<?php if ( $yka_units ) : ?>
	<section class="yka-section yka-section--cream">
		<div class="yka-container yka-container--wide">
			<?php
			yka_section_head(
				__( 'Unit Pendidikan', 'yka-portal' ),
				array(
					'note'       => __( 'Tiga jenjang pendidikan di bawah naungan yayasan, masing-masing dengan halaman dokumentasinya sendiri.', 'yka-portal' ),
					'link'       => home_url( '/unit-pendidikan/' ),
					'link_label' => __( 'Tentang unit', 'yka-portal' ),
				)
			);
			?>

			<div class="yka-grid yka-grid--3">
				<?php foreach ( $yka_units as $yka_unit ) : ?>
					<?php
					$yka_unit_link = get_term_link( $yka_unit );
					if ( is_wp_error( $yka_unit_link ) ) {
						continue;
					}
					$yka_unit_key  = yka_unit_key( $yka_unit->slug );
					$yka_unit_hero = (int) yka_unit_meta( $yka_unit->term_id, 'hero_id' );
					$yka_unit_text = yka_unit_meta( $yka_unit->term_id, 'intro' );
					$yka_unit_last = yka_get_unit_posts( $yka_unit, 2 );
					?>
					<article class="yka-unit-card" style="--yka-stamp-color: var(--yka-unit-<?php echo esc_attr( $yka_unit_key ); ?>)">
						<?php if ( $yka_unit_hero ) : ?>
							<figure class="yka-unit-card__figure">
								<?php
								echo wp_kses_post(
									wp_get_attachment_image(
										$yka_unit_hero,
										'yka-card',
										false,
										array(
											'loading'  => 'lazy',
											'decoding' => 'async',
											'sizes'    => '(min-width: 900px) 360px, (min-width: 600px) 50vw, 100vw',
										)
									)
								);
								?>
							</figure>
						<?php endif; ?>

						<div class="yka-unit-card__body">
							<h3 class="yka-unit-card__title">
								<a href="<?php echo esc_url( $yka_unit_link ); ?>"><?php echo esc_html( $yka_unit->name ); ?></a>
							</h3>

							<?php if ( '' !== $yka_unit_text ) : ?>
								<p class="yka-unit-card__intro"><?php echo esc_html( $yka_unit_text ); ?></p>
							<?php else : ?>
								<p class="yka-unit-card__intro">
									<?php echo wp_kses_post( yka_placeholder( __( 'Keterangan unit belum diberikan', 'yka-portal' ) ) ); ?>
								</p>
							<?php endif; ?>

							<?php if ( $yka_unit_last ) : ?>
								<ul class="yka-divided-list">
									<?php foreach ( $yka_unit_last as $yka_item ) : ?>
										<li><?php yka_story( $yka_item, 'headline' ); ?></li>
									<?php endforeach; ?>
								</ul>
							<?php else : ?>
								<p class="yka-muted"><?php esc_html_e( 'Belum ada artikel untuk unit ini.', 'yka-portal' ); ?></p>
							<?php endif; ?>

							<p class="yka-unit-card__links">
								<a class="yka-textlink" href="<?php echo esc_url( $yka_unit_link ); ?>">
									<?php
									printf(
										/* translators: %s: unit name. */
										esc_html__( 'Halaman %s', 'yka-portal' ),
										esc_html( $yka_unit->name )
									);
									?>
								</a>
							</p>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
<?php endif; ?>

<?php /* ----------------------------------- announcements + achievements */ ?>
<?php if ( $yka_announcements || $yka_achievements ) : ?>
	<section class="yka-section">
		<div class="yka-container yka-container--wide">
			<div class="yka-grid yka-grid--2">

				<?php if ( $yka_announcements ) : ?>
					<div>
						<?php
						yka_section_head(
							__( 'Pengumuman', 'yka-portal' ),
							array(
								'level'      => 2,
								'link'       => add_query_arg( 'kategori', 'pengumuman', $yka_news_url ),
								'link_label' => __( 'Semua', 'yka-portal' ),
							)
						);
						?>
						<ul class="yka-divided-list">
							<?php foreach ( $yka_announcements as $yka_item ) : ?>
								<li><?php get_template_part( 'template-parts/content/announcement', null, array( 'post' => $yka_item ) ); ?></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>

				<?php if ( $yka_achievements ) : ?>
					<div>
						<?php
						yka_section_head(
							__( 'Prestasi', 'yka-portal' ),
							array(
								'level'      => 2,
								'note'       => __( 'Hanya capaian yang sudah diverifikasi oleh unit terkait.', 'yka-portal' ),
								'link'       => home_url( '/prestasi/' ),
								'link_label' => __( 'Semua', 'yka-portal' ),
							)
						);
						?>
						<ul class="yka-divided-list">
							<?php foreach ( $yka_achievements as $yka_item ) : ?>
								<li><?php yka_story( $yka_item, 'compact' ); ?></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>

			</div>
		</div>
	</section>
<?php endif; ?>

<?php /* ------------------------------------------- about the foundation */ ?>
<section class="yka-section yka-section--ink">
	<div class="yka-container yka-container--wide">
		<?php
		yka_section_head(
			__( 'Tentang Yayasan Kasih Ananda', 'yka-portal' ),
			array(
				'level'      => 2,
				'link'       => $yka_about instanceof WP_Post ? (string) get_permalink( $yka_about ) : '',
				'link_label' => __( 'Profil lengkap', 'yka-portal' ),
			)
		);
		?>

		<div class="yka-grid yka-grid--2">
			<div>
				<?php $yka_org_description = $yka_has_core ? (string) yka_setting( 'org_description' ) : ''; ?>
				<?php if ( '' !== $yka_org_description ) : ?>
					<p class="yka-lede" style="color:#dfe9e3"><?php echo esc_html( $yka_org_description ); ?></p>
				<?php else : ?>
					<p class="yka-lede" style="color:#dfe9e3">
						<?php esc_html_e( 'Yayasan Kasih Ananda menaungi SD Kasih Ananda I, SMP Kasih Ananda I, dan SMK Kasih Ananda.', 'yka-portal' ); ?>
						<?php echo wp_kses_post( yka_placeholder( __( 'Profil resmi yayasan belum diberikan', 'yka-portal' ) ) ); ?>
					</p>
				<?php endif; ?>
			</div>

			<div>
				<dl class="yka-contact-list">
					<div>
						<dt><?php esc_html_e( 'Alamat', 'yka-portal' ); ?></dt>
						<dd>
							<?php
							$yka_address = $yka_has_core ? (string) yka_setting( 'address' ) : '';
							echo '' !== $yka_address
								? nl2br( esc_html( $yka_address ) )
								: wp_kses_post( yka_placeholder( __( 'Alamat resmi belum diberikan', 'yka-portal' ) ) );
							?>
						</dd>
					</div>
					<div>
						<dt><?php esc_html_e( 'Kontak', 'yka-portal' ); ?></dt>
						<dd>
							<?php
							$yka_phone = $yka_has_core ? (string) yka_setting( 'phone' ) : '';
							$yka_email = $yka_has_core ? (string) yka_setting( 'email' ) : '';
							if ( '' !== $yka_phone || '' !== $yka_email ) {
								$yka_bits = array();
								if ( '' !== $yka_phone ) {
									$yka_bits[] = '<a href="tel:' . esc_attr( (string) preg_replace( '/[^0-9+]/', '', $yka_phone ) ) . '">' . esc_html( $yka_phone ) . '</a>';
								}
								if ( '' !== $yka_email ) {
									$yka_bits[] = '<a href="mailto:' . esc_attr( $yka_email ) . '">' . esc_html( $yka_email ) . '</a>';
								}
								echo wp_kses_post( implode( ' · ', $yka_bits ) );
							} else {
								echo wp_kses_post( yka_placeholder( __( 'Kontak resmi belum diberikan', 'yka-portal' ) ) );
							}
							?>
						</dd>
					</div>
				</dl>

				<p class="yka-mt-5">
					<a class="yka-btn yka-btn--on-dark" href="<?php echo esc_url( home_url( '/kontak/' ) ); ?>">
						<?php esc_html_e( 'Halaman Kontak', 'yka-portal' ); ?>
					</a>
				</p>
			</div>
		</div>
	</div>
</section>

<?php
get_footer();
