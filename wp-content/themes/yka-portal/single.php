<?php
/**
 * Single article.
 *
 * This site exists to publish articles, so this template gets more care
 * than any homepage effect. One column, generous measure, photography and
 * facts close to the text that explains them.
 *
 * @package YKA_Portal
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$yka_id       = (int) get_the_ID();
	$yka_unit     = function_exists( 'yka_get_post_unit' ) ? yka_get_post_unit( $yka_id ) : null;
	$yka_unit_key = $yka_unit instanceof WP_Term ? yka_unit_key( $yka_unit->slug ) : 'yayasan';
	$yka_lead     = trim( (string) get_the_excerpt() );
	$yka_thumb_id = (int) get_post_thumbnail_id( $yka_id );

	$yka_activity = function_exists( 'yka_activity_date' ) ? yka_activity_date( $yka_id ) : '';
	$yka_location = function_exists( 'yka_post_meta' ) ? yka_post_meta( 'yka_activity_location', $yka_id ) : '';
	$yka_credit   = function_exists( 'yka_post_meta' ) ? yka_post_meta( 'yka_photo_credit', $yka_id ) : '';

	// Only surface an "updated" date when the change is meaningful, so the
	// page never implies a fresh publication because of a background task.
	$yka_published    = (int) get_post_time( 'U', true );
	$yka_modified     = (int) get_post_modified_time( 'U', true );
	$yka_show_updated = ( $yka_modified - $yka_published ) > DAY_IN_SECONDS;
	?>

	<div class="yka-container">
		<?php yka_breadcrumbs(); ?>
	</div>

	<article class="yka-article yka-section yka-section--tight" style="--yka-stamp-color: var(--yka-unit-<?php echo esc_attr( $yka_unit_key ); ?>)">
		<div class="yka-container">

			<header class="yka-article-header yka-prose">
				<div class="yka-article-header__meta">
					<?php yka_unit_stamp( $yka_unit ); ?>

					<?php $yka_categories = get_the_category(); ?>
					<?php if ( $yka_categories ) : ?>
						<a class="yka-meta__category" href="<?php echo esc_url( (string) get_category_link( $yka_categories[0]->term_id ) ); ?>">
							<?php echo esc_html( $yka_categories[0]->name ); ?>
						</a>
					<?php endif; ?>

					<time datetime="<?php echo esc_attr( (string) get_the_date( DATE_W3C ) ); ?>">
						<?php echo esc_html( (string) get_the_date() ); ?>
					</time>
				</div>

				<h1 class="yka-article-header__title"><?php the_title(); ?></h1>

				<?php if ( '' !== $yka_lead ) : ?>
					<p class="yka-article-lead"><?php echo esc_html( $yka_lead ); ?></p>
				<?php endif; ?>

				<div class="yka-byline">
					<span class="yka-byline__item">
						<span class="yka-byline__avatar"><?php echo get_avatar( (int) get_the_author_meta( 'ID' ), 28 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-escaped markup. ?></span>
						<span>
							<?php esc_html_e( 'Oleh', 'yka-portal' ); ?>
							<a class="yka-byline__name" href="<?php echo esc_url( (string) get_author_posts_url( (int) get_the_author_meta( 'ID' ) ) ); ?>" rel="author">
								<?php echo esc_html( (string) get_the_author() ); ?>
							</a>
						</span>
					</span>

					<span class="yka-byline__item">
						<?php esc_html_e( 'Terbit', 'yka-portal' ); ?>
						<time datetime="<?php echo esc_attr( (string) get_the_date( DATE_W3C ) ); ?>">
							<?php echo esc_html( (string) get_the_date() ); ?>
						</time>
					</span>

					<?php if ( $yka_show_updated ) : ?>
						<span class="yka-byline__item yka-byline__updated">
							<?php esc_html_e( 'Diperbarui', 'yka-portal' ); ?>
							<time datetime="<?php echo esc_attr( (string) get_the_modified_date( DATE_W3C ) ); ?>">
								<?php echo esc_html( (string) get_the_modified_date() ); ?>
							</time>
						</span>
					<?php endif; ?>
				</div>
			</header>

			<?php if ( $yka_thumb_id ) : ?>
				<figure class="yka-article-figure">
					<?php yka_lcp_image( $yka_thumb_id, 'yka-wide', array( 'sizes' => '(min-width: 1180px) 1180px, 100vw' ) ); ?>

					<?php
					$yka_caption = wp_get_attachment_caption( $yka_thumb_id );
					$yka_caption = is_string( $yka_caption ) ? trim( wp_strip_all_tags( $yka_caption ) ) : '';
					?>
					<?php if ( '' !== $yka_caption || '' !== $yka_credit ) : ?>
						<figcaption>
							<?php if ( '' !== $yka_caption ) : ?>
								<span><?php echo esc_html( $yka_caption ); ?></span>
							<?php endif; ?>
							<?php if ( '' !== $yka_credit ) : ?>
								<span class="yka-article-figure__credit">
									<?php
									printf(
										/* translators: %s: photographer name. */
										esc_html__( 'Foto: %s', 'yka-portal' ),
										esc_html( $yka_credit )
									);
									?>
								</span>
							<?php endif; ?>
						</figcaption>
					<?php endif; ?>
				</figure>
			<?php endif; ?>

			<?php if ( '' !== $yka_activity || '' !== $yka_location ) : ?>
				<div class="yka-facts yka-prose">
					<dl>
						<?php if ( '' !== $yka_activity ) : ?>
							<dt><?php esc_html_e( 'Tanggal kegiatan', 'yka-portal' ); ?></dt>
							<dd><?php echo esc_html( $yka_activity ); ?></dd>
						<?php endif; ?>
						<?php if ( '' !== $yka_location ) : ?>
							<dt><?php esc_html_e( 'Lokasi', 'yka-portal' ); ?></dt>
							<dd><?php echo esc_html( $yka_location ); ?></dd>
						<?php endif; ?>
						<?php if ( $yka_unit instanceof WP_Term ) : ?>
							<dt><?php esc_html_e( 'Unit', 'yka-portal' ); ?></dt>
							<dd><?php echo esc_html( $yka_unit->name ); ?></dd>
						<?php endif; ?>
					</dl>
				</div>
			<?php endif; ?>

			<div class="yka-content">
				<?php the_content(); ?>
			</div>

			<?php
			wp_link_pages(
				array(
					'before' => '<nav class="yka-pagination" aria-label="' . esc_attr__( 'Halaman artikel', 'yka-portal' ) . '"><div class="nav-links">',
					'after'  => '</div></nav>',
				)
			);
			?>

			<?php get_template_part( 'template-parts/content/share' ); ?>

			<?php
			$yka_author_bio = trim( (string) get_the_author_meta( 'description' ) );
			if ( '' !== $yka_author_bio ) :
				?>
				<aside class="yka-facts yka-prose yka-mt-6">
					<p class="yka-eyebrow"><?php esc_html_e( 'Tentang penulis', 'yka-portal' ); ?></p>
					<p><strong><?php echo esc_html( (string) get_the_author() ); ?></strong></p>
					<p><?php echo esc_html( $yka_author_bio ); ?></p>
				</aside>
			<?php endif; ?>

			<?php
			$yka_related = function_exists( 'yka_related_posts' ) ? yka_related_posts( $yka_id, 4 ) : array();
			if ( $yka_related ) :
				?>
				<section class="yka-related">
					<h2 class="yka-related__title">
						<?php
						if ( $yka_unit instanceof WP_Term ) {
							printf(
								/* translators: %s: unit name. */
								esc_html__( 'Berita lain dari %s', 'yka-portal' ),
								esc_html( $yka_unit->name )
							);
						} else {
							esc_html_e( 'Berita lainnya', 'yka-portal' );
						}
						?>
					</h2>

					<div class="yka-grid yka-grid--4">
						<?php foreach ( $yka_related as $yka_item ) : ?>
							<?php yka_story( $yka_item, 'secondary' ); ?>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endif; ?>

			<?php if ( $yka_unit instanceof WP_Term ) : ?>
				<?php $yka_unit_link = get_term_link( $yka_unit ); ?>
				<?php if ( ! is_wp_error( $yka_unit_link ) ) : ?>
					<aside class="yka-unit-cta">
						<div class="yka-unit-cta__text">
							<h2 class="yka-unit-cta__title"><?php echo esc_html( $yka_unit->name ); ?></h2>
							<p><?php esc_html_e( 'Lihat seluruh dokumentasi kegiatan, pengumuman, dan informasi kontak unit ini.', 'yka-portal' ); ?></p>
						</div>
						<a class="yka-btn" href="<?php echo esc_url( $yka_unit_link ); ?>">
							<?php esc_html_e( 'Buka halaman unit', 'yka-portal' ); ?>
						</a>
					</aside>
				<?php endif; ?>
			<?php endif; ?>

		</div>
	</article>

	<?php
endwhile;

get_footer();
