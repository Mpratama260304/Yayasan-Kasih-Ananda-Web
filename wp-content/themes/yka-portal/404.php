<?php
/**
 * 404.
 *
 * Returns a real HTTP 404 and then does something useful with the visit.
 *
 * @package YKA_Portal
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

get_header();

$yka_units      = function_exists( 'yka_get_units' ) ? yka_get_units() : array();
$yka_recent     = yka_get_recent_posts( 4 );
$yka_posts_page = (int) get_option( 'page_for_posts' );
?>

<div class="yka-container yka-section yka-section--tight">

	<header class="yka-archive-header yka-prose">
		<p class="yka-404__code">404</p>
		<h1 class="yka-archive-header__title"><?php esc_html_e( 'Halaman tidak ditemukan', 'yka-portal' ); ?></h1>
		<p class="yka-lede">
			<?php esc_html_e( 'Alamat yang Anda buka tidak tersedia. Halaman mungkin telah dipindahkan, atau tautannya salah ketik.', 'yka-portal' ); ?>
		</p>
	</header>

	<div class="yka-prose yka-mt-5">
		<?php get_search_form(); ?>
	</div>

	<div class="yka-mt-6">
		<p>
			<a class="yka-btn" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Kembali ke beranda', 'yka-portal' ); ?></a>
			<?php if ( $yka_posts_page ) : ?>
				<a class="yka-btn yka-btn--secondary" href="<?php echo esc_url( (string) get_permalink( $yka_posts_page ) ); ?>">
					<?php esc_html_e( 'Berita & Kegiatan', 'yka-portal' ); ?>
				</a>
			<?php endif; ?>
		</p>
	</div>

	<?php if ( $yka_units ) : ?>
		<div class="yka-filters yka-mt-6">
			<div class="yka-filters__group">
				<p class="yka-filters__label" id="yka-404-units"><?php esc_html_e( 'Unit pendidikan', 'yka-portal' ); ?></p>
				<ul class="yka-filters__list" aria-labelledby="yka-404-units">
					<?php foreach ( $yka_units as $yka_unit ) : ?>
						<?php $yka_link = get_term_link( $yka_unit ); ?>
						<?php if ( ! is_wp_error( $yka_link ) ) : ?>
							<li><a href="<?php echo esc_url( $yka_link ); ?>"><?php echo esc_html( $yka_unit->name ); ?></a></li>
						<?php endif; ?>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
	<?php endif; ?>

	<?php if ( $yka_recent ) : ?>
		<section class="yka-related">
			<h2 class="yka-related__title"><?php esc_html_e( 'Artikel terbaru', 'yka-portal' ); ?></h2>
			<div class="yka-grid yka-grid--4">
				<?php foreach ( $yka_recent as $yka_item ) : ?>
					<?php yka_story( $yka_item, 'secondary' ); ?>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>
</div>

<?php
get_footer();
