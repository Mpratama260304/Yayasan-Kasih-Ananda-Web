<?php
/**
 * Static page.
 *
 * @package YKA_Portal
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<div class="yka-container">
		<?php yka_breadcrumbs(); ?>
	</div>

	<article class="yka-section yka-section--tight">
		<div class="yka-container">
			<header class="yka-article-header yka-prose">
				<h1 class="yka-article-header__title"><?php the_title(); ?></h1>
			</header>

			<?php if ( has_post_thumbnail() ) : ?>
				<figure class="yka-article-figure">
					<?php yka_lcp_image( (int) get_post_thumbnail_id(), 'yka-wide', array( 'sizes' => '(min-width: 1180px) 1180px, 100vw' ) ); ?>
				</figure>
			<?php endif; ?>

			<div class="yka-content">
				<?php the_content(); ?>
			</div>

			<?php
			wp_link_pages(
				array(
					'before' => '<nav class="yka-pagination" aria-label="' . esc_attr__( 'Halaman', 'yka-portal' ) . '"><div class="nav-links">',
					'after'  => '</div></nav>',
				)
			);
			?>

			<?php
			$yka_children = get_pages(
				array(
					'parent'      => (int) get_the_ID(),
					'sort_column' => 'menu_order',
					'sort_order'  => 'ASC',
				)
			);
			?>
			<?php if ( $yka_children ) : ?>
				<nav class="yka-related" aria-label="<?php esc_attr_e( 'Halaman terkait', 'yka-portal' ); ?>">
					<h2 class="yka-related__title"><?php esc_html_e( 'Halaman terkait', 'yka-portal' ); ?></h2>
					<ul class="yka-divided-list">
						<?php foreach ( $yka_children as $yka_child ) : ?>
							<li>
								<h3 class="yka-story__title" style="font-family:var(--yka-font-body);font-size:var(--yka-text-base)">
									<a href="<?php echo esc_url( (string) get_permalink( $yka_child ) ); ?>"><?php echo esc_html( get_the_title( $yka_child ) ); ?></a>
								</h3>
							</li>
						<?php endforeach; ?>
					</ul>
				</nav>
			<?php endif; ?>
		</div>
	</article>
	<?php
endwhile;

get_footer();
