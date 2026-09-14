<?php
/**
 * Template Name: Halaman Pengumuman
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
		'category_name'       => 'pengumuman,spmb-ppdb',
		'posts_per_page'      => 20,
		'paged'               => $yka_paged,
		'ignore_sticky_posts' => true,
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
				<?php esc_html_e( 'Informasi resmi dari Yayasan Kasih Ananda dan unit pendidikannya, termasuk penerimaan murid baru.', 'yka-portal' ); ?>
			</p>
		</header>

		<?php if ( '' !== trim( (string) get_the_content() ) ) : ?>
			<div class="yka-content yka-mt-5"><?php the_content(); ?></div>
		<?php endif; ?>
		<?php
	endwhile;
	?>

	<?php if ( $yka_query->have_posts() ) : ?>

		<ul class="yka-divided-list yka-mt-6">
			<?php foreach ( $yka_query->posts as $yka_item ) : ?>
				<li><?php get_template_part( 'template-parts/content/announcement', null, array( 'post' => $yka_item ) ); ?></li>
			<?php endforeach; ?>
		</ul>

		<?php yka_pagination( $yka_query ); ?>

	<?php else : ?>

		<div class="yka-mt-6">
			<?php
			yka_empty_state(
				__( 'Belum ada pengumuman yang aktif.', 'yka-portal' ),
				__( 'Pengumuman baru akan muncul di sini secara otomatis setelah diterbitkan.', 'yka-portal' )
			);
			?>
		</div>

	<?php endif; ?>

	<?php wp_reset_postdata(); ?>
</div>

<?php
get_footer();
