<?php
/**
 * Search results.
 *
 * @package YKA_Portal
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

get_header();

global $wp_query;
$yka_term  = get_search_query();
$yka_found = (int) $wp_query->found_posts;
?>

<div class="yka-container">
	<?php yka_breadcrumbs(); ?>
</div>

<div class="yka-container yka-section yka-section--tight">

	<header class="yka-archive-header">
		<p class="yka-eyebrow"><?php esc_html_e( 'Pencarian', 'yka-portal' ); ?></p>
		<h1 class="yka-archive-header__title">
			<?php
			printf(
				/* translators: %s: search term. */
				esc_html__( 'Hasil untuk “%s”', 'yka-portal' ),
				esc_html( $yka_term )
			);
			?>
		</h1>
		<p class="yka-lede">
			<?php
			printf(
				/* translators: %s: number of results. */
				esc_html( _n( '%s hasil ditemukan.', '%s hasil ditemukan.', $yka_found, 'yka-portal' ) ),
				esc_html( number_format_i18n( $yka_found ) )
			);
			?>
		</p>
	</header>

	<div class="yka-prose yka-mt-5">
		<?php get_search_form(); ?>
	</div>

	<?php if ( have_posts() ) : ?>

		<div class="yka-archive-list yka-divided-list yka-mt-6">
			<?php
			while ( have_posts() ) :
				the_post();
				$yka_is_post = 'post' === get_post_type();
				?>
				<div class="yka-archive-list__item">
					<article class="yka-story yka-story--row">
						<?php if ( $yka_is_post && has_post_thumbnail() ) : ?>
							<?php yka_story_image( get_post(), 'yka-4x3', array( 'sizes' => '(min-width: 680px) 280px, 100vw' ) ); ?>
						<?php endif; ?>

						<div class="yka-story__body">
							<span class="yka-searchresult__type">
								<?php
								echo esc_html(
									$yka_is_post
										? __( 'Berita', 'yka-portal' )
										: __( 'Halaman', 'yka-portal' )
								);
								?>
							</span>

							<?php if ( $yka_is_post ) : ?>
								<?php yka_meta_line( get_post() ); ?>
							<?php endif; ?>

							<h2 class="yka-story__title">
								<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
							</h2>

							<?php $yka_excerpt = yka_excerpt( get_post(), 30 ); ?>
							<?php if ( '' !== $yka_excerpt ) : ?>
								<p class="yka-story__excerpt"><?php echo esc_html( $yka_excerpt ); ?></p>
							<?php endif; ?>

							<p class="yka-searchresult__url"><?php echo esc_html( (string) get_permalink() ); ?></p>
						</div>
					</article>
				</div>
			<?php endwhile; ?>
		</div>

		<?php yka_pagination(); ?>

	<?php else : ?>

		<div class="yka-mt-6">
			<?php
			yka_empty_state(
				__( 'Tidak ada hasil yang cocok dengan kata kunci tersebut.', 'yka-portal' ),
				__( 'Coba kata kunci yang lebih umum, atau telusuri berita per unit pendidikan di bawah ini.', 'yka-portal' )
			);
			?>

			<?php $yka_units = function_exists( 'yka_get_units' ) ? yka_get_units() : array(); ?>
			<?php if ( $yka_units ) : ?>
				<div class="yka-filters yka-mt-6">
					<div class="yka-filters__group">
						<p class="yka-filters__label" id="yka-search-units"><?php esc_html_e( 'Telusuri per unit', 'yka-portal' ); ?></p>
						<ul class="yka-filters__list" aria-labelledby="yka-search-units">
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

			<?php $yka_recent = yka_get_recent_posts( 4 ); ?>
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

	<?php endif; ?>
</div>

<?php
get_footer();
