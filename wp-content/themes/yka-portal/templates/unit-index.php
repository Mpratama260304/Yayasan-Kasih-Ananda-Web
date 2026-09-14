<?php
/**
 * Template Name: Daftar Unit Pendidikan
 *
 * Overview of every education unit, with its contact details and latest
 * articles. Nothing here duplicates an article: each unit links through to
 * the single canonical story.
 *
 * @package YKA_Portal
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

get_header();

$yka_units = function_exists( 'yka_get_units' ) ? yka_get_units() : array();
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
				<?php esc_html_e( 'Yayasan Kasih Ananda menaungi tiga jenjang pendidikan. Setiap unit memiliki halaman dokumentasi sendiri yang memuat berita, pengumuman, dan informasi kontaknya.', 'yka-portal' ); ?>
			</p>
		</header>

		<?php if ( '' !== trim( (string) get_the_content() ) ) : ?>
			<div class="yka-content yka-mt-5"><?php the_content(); ?></div>
		<?php endif; ?>
		<?php
	endwhile;
	?>
</div>

<?php if ( $yka_units ) : ?>
	<?php foreach ( $yka_units as $yka_index => $yka_unit ) : ?>
		<?php
		$yka_link = get_term_link( $yka_unit );
		if ( is_wp_error( $yka_link ) ) {
			continue;
		}

		$yka_key      = yka_unit_key( $yka_unit->slug );
		$yka_official = yka_unit_meta( $yka_unit->term_id, 'official_name' );
		$yka_intro    = yka_unit_meta( $yka_unit->term_id, 'intro' );
		$yka_hero     = (int) yka_unit_meta( $yka_unit->term_id, 'hero_id' );
		$yka_address  = yka_unit_meta( $yka_unit->term_id, 'address' );
		$yka_legacy   = yka_unit_meta( $yka_unit->term_id, 'legacy_url' );
		$yka_posts    = yka_get_unit_posts( $yka_unit, 3 );
		?>
		<section
			class="yka-section yka-section--tight <?php echo 0 === $yka_index % 2 ? '' : 'yka-section--cream'; ?>"
			style="--yka-stamp-color: var(--yka-unit-<?php echo esc_attr( $yka_key ); ?>)"
		>
			<div class="yka-container">
				<div class="yka-grid yka-grid--2">

					<div>
						<?php if ( $yka_hero ) : ?>
							<figure class="yka-unit-card__figure">
								<?php
								echo wp_kses_post(
									wp_get_attachment_image(
										$yka_hero,
										'yka-card',
										false,
										array(
											'loading'  => 'lazy',
											'decoding' => 'async',
											'sizes'    => '(min-width: 600px) 560px, 100vw',
										)
									)
								);
								?>
							</figure>
						<?php endif; ?>
					</div>

					<div>
						<?php yka_unit_stamp( $yka_unit, array( 'link' => false ) ); ?>

						<h2 class="yka-unit-card__title yka-mt-5">
							<a href="<?php echo esc_url( $yka_link ); ?>">
								<?php echo esc_html( '' !== $yka_official ? $yka_official : $yka_unit->name ); ?>
							</a>
						</h2>

						<?php if ( '' !== $yka_intro ) : ?>
							<p class="yka-lede"><?php echo esc_html( $yka_intro ); ?></p>
						<?php else : ?>
							<p class="yka-lede"><?php echo wp_kses_post( yka_placeholder( __( 'Keterangan unit belum diberikan', 'yka-portal' ) ) ); ?></p>
						<?php endif; ?>

						<dl class="yka-contact-list yka-mt-5">
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
								<dt><?php esc_html_e( 'Jumlah artikel', 'yka-portal' ); ?></dt>
								<dd><?php echo esc_html( number_format_i18n( (int) $yka_unit->count ) ); ?></dd>
							</div>
						</dl>

						<p class="yka-mt-5">
							<a class="yka-btn" href="<?php echo esc_url( $yka_link ); ?>">
								<?php esc_html_e( 'Buka halaman unit', 'yka-portal' ); ?>
							</a>
							<?php if ( '' !== $yka_legacy ) : ?>
								<a class="yka-btn yka-btn--secondary" href="<?php echo esc_url( $yka_legacy ); ?>" rel="noopener" target="_blank">
									<?php esc_html_e( 'Situs lama', 'yka-portal' ); ?>
								</a>
							<?php endif; ?>
						</p>

						<?php if ( $yka_posts ) : ?>
							<h3 class="yka-aside-block__title yka-mt-6"><?php esc_html_e( 'Artikel terbaru', 'yka-portal' ); ?></h3>
							<ul class="yka-divided-list">
								<?php foreach ( $yka_posts as $yka_item ) : ?>
									<li><?php yka_story( $yka_item, 'headline' ); ?></li>
								<?php endforeach; ?>
							</ul>
						<?php else : ?>
							<p class="yka-muted yka-mt-6"><?php esc_html_e( 'Belum ada artikel untuk unit ini.', 'yka-portal' ); ?></p>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</section>
	<?php endforeach; ?>
<?php else : ?>
	<div class="yka-container yka-section yka-section--tight">
		<?php
		yka_empty_state(
			__( 'Belum ada unit pendidikan yang terdaftar.', 'yka-portal' ),
			__( 'Unit pendidikan dikelola melalui menu Unit Pendidikan di dasbor WordPress.', 'yka-portal' )
		);
		?>
	</div>
<?php endif; ?>

<?php
get_footer();
