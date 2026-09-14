<?php
/**
 * Template Name: Halaman Galeri
 *
 * Documentation index. The page body is edited with the native Gutenberg
 * gallery block; below it, the most recent article photographs are surfaced
 * automatically so the archive stays current without manual curation.
 *
 * @package YKA_Portal
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

get_header();

$yka_recent = yka_get_recent_posts( 12 );
$yka_recent = array_values(
	array_filter(
		$yka_recent,
		static fn( WP_Post $post ): bool => has_post_thumbnail( $post )
	)
);
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
				<?php esc_html_e( 'Dokumentasi foto kegiatan di lingkungan Yayasan Kasih Ananda. Setiap foto berasal dari artikel dokumentasi, sehingga konteksnya selalu dapat ditelusuri.', 'yka-portal' ); ?>
			</p>
		</header>

		<?php if ( '' !== trim( (string) get_the_content() ) ) : ?>
			<div class="yka-content yka-mt-5"><?php the_content(); ?></div>
		<?php endif; ?>
		<?php
	endwhile;
	?>

	<?php if ( $yka_recent ) : ?>
		<section class="yka-mt-6">
			<?php
			yka_section_head(
				__( 'Dokumentasi terbaru', 'yka-portal' ),
				array(
					'note'  => __( 'Foto utama dari artikel yang paling baru diterbitkan.', 'yka-portal' ),
					'level' => 2,
				)
			);
			?>

			<div class="yka-grid yka-grid--3">
				<?php foreach ( $yka_recent as $yka_item ) : ?>
					<figure class="yka-story">
						<a href="<?php echo esc_url( (string) get_permalink( $yka_item ) ); ?>">
							<?php
							yka_story_image(
								$yka_item,
								'yka-4x3',
								array(
									'unit_overlay' => true,
									'sizes'        => '(min-width: 900px) 360px, (min-width: 600px) 50vw, 100vw',
								)
							);
							?>
						</a>
						<figcaption>
							<?php yka_meta_line( $yka_item, array( 'show_unit' => false ) ); ?>
							<a href="<?php echo esc_url( (string) get_permalink( $yka_item ) ); ?>">
								<?php echo esc_html( get_the_title( $yka_item ) ); ?>
							</a>
						</figcaption>
					</figure>
				<?php endforeach; ?>
			</div>
		</section>
	<?php else : ?>
		<div class="yka-mt-6">
			<?php
			yka_empty_state(
				__( 'Belum ada foto dokumentasi yang tersedia.', 'yka-portal' ),
				__( 'Galeri akan terisi setelah artikel dengan gambar utama diterbitkan.', 'yka-portal' )
			);
			?>
		</div>
	<?php endif; ?>
</div>

<?php
get_footer();
