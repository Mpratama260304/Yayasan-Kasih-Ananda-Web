<?php
/**
 * Archive filter controls.
 *
 * Plain links to real URLs. Every filtered view is a page a visitor can
 * bookmark, share, or reach without JavaScript.
 *
 * Expected args:
 *   base_url   string  URL the year filter applies to.
 *   show_units bool    Whether to render the unit row.
 *
 * @package YKA_Portal
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

$yka_args         = wp_parse_args(
	$args ?? array(),
	array(
		'base_url'   => '',
		'show_units' => true,
	)
);
$yka_base         = (string) $yka_args['base_url'];
$yka_units        = function_exists( 'yka_get_units' ) ? yka_get_units( true ) : array();
$yka_categories   = get_terms(
	array(
		'taxonomy'   => 'category',
		'hide_empty' => true,
		'orderby'    => 'count',
		'order'      => 'DESC',
		'number'     => 8,
	)
);
$yka_categories   = is_wp_error( $yka_categories ) ? array() : $yka_categories;
$yka_years        = function_exists( 'yka_article_years' ) ? yka_article_years() : array();
$yka_active_cat   = (string) get_query_var( 'kategori' );
$yka_active_year  = (int) get_query_var( 'tahun' );
$yka_current_unit = yka_current_unit();

if ( '' === $yka_base ) {
	$yka_posts_page = (int) get_option( 'page_for_posts' );
	$yka_base       = $yka_posts_page ? (string) get_permalink( $yka_posts_page ) : home_url( '/' );
}
?>
<div class="yka-filters">

	<?php if ( $yka_args['show_units'] && $yka_units ) : ?>
		<div class="yka-filters__group">
			<p class="yka-filters__label" id="yka-filter-unit"><?php esc_html_e( 'Unit pendidikan', 'yka-portal' ); ?></p>
			<ul class="yka-filters__list" aria-labelledby="yka-filter-unit">
				<li>
					<a href="<?php echo esc_url( $yka_base ); ?>"<?php echo $yka_current_unit instanceof WP_Term ? '' : ' aria-current="page"'; ?>>
						<?php esc_html_e( 'Semua unit', 'yka-portal' ); ?>
					</a>
				</li>
				<?php foreach ( $yka_units as $yka_unit ) : ?>
					<?php
					$yka_unit_link = get_term_link( $yka_unit );
					if ( is_wp_error( $yka_unit_link ) ) {
						continue;
					}
					$yka_is_current = $yka_current_unit instanceof WP_Term && $yka_current_unit->term_id === $yka_unit->term_id;
					?>
					<li>
						<a href="<?php echo esc_url( $yka_unit_link ); ?>"<?php echo $yka_is_current ? ' aria-current="page"' : ''; ?>>
							<?php echo esc_html( $yka_unit->name ); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<?php if ( $yka_categories ) : ?>
		<div class="yka-filters__group">
			<p class="yka-filters__label" id="yka-filter-category"><?php esc_html_e( 'Kategori', 'yka-portal' ); ?></p>
			<ul class="yka-filters__list" aria-labelledby="yka-filter-category">
				<li>
					<a href="<?php echo esc_url( remove_query_arg( 'kategori' ) ); ?>"<?php echo '' === $yka_active_cat ? ' aria-current="page"' : ''; ?>>
						<?php esc_html_e( 'Semua kategori', 'yka-portal' ); ?>
					</a>
				</li>
				<?php foreach ( $yka_categories as $yka_category ) : ?>
					<li>
						<a
							href="<?php echo esc_url( add_query_arg( 'kategori', $yka_category->slug, $yka_base ) ); ?>"
							<?php echo $yka_active_cat === $yka_category->slug ? ' aria-current="page"' : ''; ?>
						>
							<?php echo esc_html( $yka_category->name ); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<?php if ( count( $yka_years ) > 1 ) : ?>
		<div class="yka-filters__group">
			<p class="yka-filters__label" id="yka-filter-year"><?php esc_html_e( 'Tahun', 'yka-portal' ); ?></p>
			<ul class="yka-filters__list" aria-labelledby="yka-filter-year">
				<li>
					<a href="<?php echo esc_url( remove_query_arg( 'tahun' ) ); ?>"<?php echo 0 === $yka_active_year ? ' aria-current="page"' : ''; ?>>
						<?php esc_html_e( 'Semua tahun', 'yka-portal' ); ?>
					</a>
				</li>
				<?php foreach ( array_slice( $yka_years, 0, 6 ) as $yka_year ) : ?>
					<li>
						<a
							href="<?php echo esc_url( add_query_arg( 'tahun', (string) $yka_year ) ); ?>"
							<?php echo $yka_active_year === $yka_year ? ' aria-current="page"' : ''; ?>
						>
							<?php echo esc_html( (string) $yka_year ); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>
</div>
