<?php
/**
 * Shared article archive body: lead item, list, pagination.
 *
 * Expected args:
 *   title       string
 *   description string
 *   base_url    string  URL filters apply to.
 *   show_units  bool
 *   show_lead   bool    Give the first result on page 1 extra prominence.
 *   empty_text  string
 *
 * @package YKA_Portal
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

$yka_args = wp_parse_args(
	$args ?? array(),
	array(
		'title'       => '',
		'description' => '',
		'base_url'    => '',
		'show_units'  => true,
		'show_lead'   => true,
		'empty_text'  => __( 'Belum ada artikel pada bagian ini.', 'yka-portal' ),
	)
);

$yka_paged     = max( 1, (int) get_query_var( 'paged' ) );
$yka_show_lead = (bool) $yka_args['show_lead'] && 1 === $yka_paged && ! yka_has_active_filter();
$yka_index     = 0;
?>
<div class="yka-container">
	<?php yka_breadcrumbs(); ?>
</div>

<div class="yka-container yka-section yka-section--tight">

	<header class="yka-archive-header">
		<h1 class="yka-archive-header__title"><?php echo esc_html( (string) $yka_args['title'] ); ?></h1>
		<?php if ( '' !== $yka_args['description'] ) : ?>
			<p class="yka-lede"><?php echo esc_html( (string) $yka_args['description'] ); ?></p>
		<?php endif; ?>
	</header>

	<?php
	get_template_part(
		'template-parts/content/filters',
		null,
		array(
			'base_url'   => (string) $yka_args['base_url'],
			'show_units' => (bool) $yka_args['show_units'],
		)
	);
	?>

	<?php if ( have_posts() ) : ?>

		<?php if ( yka_has_active_filter() ) : ?>
			<p class="yka-eyebrow">
				<?php
				global $wp_query;
				printf(
					/* translators: %d: number of matching articles. */
					esc_html( _n( '%d artikel sesuai saringan', '%d artikel sesuai saringan', (int) $wp_query->found_posts, 'yka-portal' ) ),
					(int) $wp_query->found_posts
				);
				?>
			</p>
		<?php endif; ?>

		<div class="yka-archive-list yka-divided-list">
			<?php
			while ( have_posts() ) :
				the_post();
				++$yka_index;
				?>
				<div class="yka-archive-list__item">
					<?php
					if ( 1 === $yka_index && $yka_show_lead ) {
						yka_story( get_post(), 'lead', array( 'eager' => true ) );
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
			(string) $yka_args['empty_text'],
			__( 'Coba hapus saringan, atau telusuri unit pendidikan lain.', 'yka-portal' )
		);
		?>

	<?php endif; ?>
</div>
