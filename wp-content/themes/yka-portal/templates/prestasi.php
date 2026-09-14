<?php
/**
 * Template Name: Halaman Prestasi
 *
 * Lists verified achievements, drawn from the Prestasi category. When there
 * are none, it says so plainly — an institutional site must never display
 * invented awards.
 *
 * @package YKA_Portal
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

get_header();

$yka_paged = max( 1, (int) get_query_var( 'paged' ) );

$yka_query = new WP_Query(
	array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'category_name'       => 'prestasi',
		'posts_per_page'      => 12,
		'paged'               => $yka_paged,
		'ignore_sticky_posts' => true,
	)
);

$yka_units = function_exists( 'yka_get_units' ) ? yka_get_units( true ) : array();
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
				<?php esc_html_e( 'Capaian siswa, guru, dan unit pendidikan yang telah diverifikasi oleh unit terkait. Prestasi hanya ditayangkan setelah bukti resminya diterima.', 'yka-portal' ); ?>
			</p>
		</header>

		<?php if ( '' !== trim( (string) get_the_content() ) ) : ?>
			<div class="yka-content yka-mt-5"><?php the_content(); ?></div>
		<?php endif; ?>
		<?php
	endwhile;
	?>

	<?php if ( $yka_units ) : ?>
		<div class="yka-filters yka-mt-6">
			<div class="yka-filters__group">
				<p class="yka-filters__label" id="yka-prestasi-units"><?php esc_html_e( 'Menurut unit', 'yka-portal' ); ?></p>
				<ul class="yka-filters__list" aria-labelledby="yka-prestasi-units">
					<?php foreach ( $yka_units as $yka_unit ) : ?>
						<?php $yka_link = get_term_link( $yka_unit ); ?>
						<?php if ( ! is_wp_error( $yka_link ) ) : ?>
							<li>
								<a href="<?php echo esc_url( add_query_arg( 'kategori', 'prestasi', $yka_link ) ); ?>">
									<?php echo esc_html( $yka_unit->name ); ?>
								</a>
							</li>
						<?php endif; ?>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
	<?php endif; ?>

	<?php if ( $yka_query->have_posts() ) : ?>

		<div class="yka-grid yka-grid--3 yka-mt-6">
			<?php
			foreach ( $yka_query->posts as $yka_item ) {
				yka_story( $yka_item, 'secondary' );
			}
			?>
		</div>

		<?php yka_pagination( $yka_query ); ?>

	<?php else : ?>

		<div class="yka-mt-6">
			<?php
			yka_empty_state(
				__( 'Belum ada prestasi yang dipublikasikan.', 'yka-portal' ),
				__( 'Halaman ini akan terisi setelah unit pendidikan mengirimkan capaian beserta bukti resminya. Tidak ada prestasi yang ditampilkan tanpa verifikasi.', 'yka-portal' )
			);
			?>
		</div>

	<?php endif; ?>

	<?php wp_reset_postdata(); ?>
</div>

<?php
get_footer();
