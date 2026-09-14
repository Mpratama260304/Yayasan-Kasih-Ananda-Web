<?php
/**
 * Query behaviour: archive filtering, search tuning, related content.
 *
 * All filtering happens server-side through real URLs, so every filtered
 * view is a working, shareable, crawlable page.
 *
 * @package YKA_Portal
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Registers the public filter query variables.
 *
 * @param string[] $vars Existing query vars.
 * @return string[]
 */
function yka_query_vars( array $vars ): array {
	$vars[] = 'kategori';
	$vars[] = 'tahun';
	return $vars;
}
add_filter( 'query_vars', 'yka_query_vars' );

/**
 * Whether the current request has an archive filter applied.
 *
 * @return bool
 */
function yka_has_active_filter(): bool {
	return '' !== (string) get_query_var( 'kategori' ) || '' !== (string) get_query_var( 'tahun' );
}

/**
 * Applies archive filters and tunes the main query.
 *
 * @param WP_Query $query Current query.
 * @return void
 */
function yka_pre_get_posts( $query ): void {
	if ( ! $query instanceof WP_Query || is_admin() || ! $query->is_main_query() ) {
		return;
	}

	// Search: published articles and pages only, never media attachments.
	if ( $query->is_search() ) {
		$query->set( 'post_type', array( 'post', 'page' ) );
		$query->set( 'posts_per_page', 10 );
		return;
	}

	$is_article_archive = $query->is_home()
		|| $query->is_category()
		|| $query->is_tag()
		|| ( function_exists( 'yka_unit_taxonomy' ) && $query->is_tax( yka_unit_taxonomy() ) );

	if ( ! $is_article_archive ) {
		return;
	}

	$query->set( 'posts_per_page', 12 );
	$query->set( 'ignore_sticky_posts', true );

	$category = sanitize_title( (string) $query->get( 'kategori' ) );
	if ( '' !== $category && term_exists( $category, 'category' ) ) {
		$query->set( 'category_name', $category );
	}

	$year = (int) $query->get( 'tahun' );
	if ( $year >= 1990 && $year <= ( (int) gmdate( 'Y' ) + 1 ) ) {
		$query->set( 'year', $year );
	}
}
add_action( 'pre_get_posts', 'yka_pre_get_posts' );

/**
 * Keeps filtered archive permutations out of the index.
 *
 * The unfiltered unit and category archives remain fully indexable — only
 * the combinatorial filtered views are excluded, so the site never builds a
 * long tail of near-duplicate thin pages.
 *
 * @param array<string, mixed> $robots Robots directives.
 * @return array<string, mixed>
 */
function yka_robots_for_filters( array $robots ): array {
	if ( yka_has_active_filter() ) {
		$robots['noindex'] = true;
		$robots['follow']  = true;
		unset( $robots['index'] );
	}

	return $robots;
}
add_filter( 'wp_robots', 'yka_robots_for_filters', 15 );

/**
 * The years in which articles were published, newest first.
 *
 * Cached for a day: it changes at most once a year in practice, and the
 * archive page would otherwise run this on every request.
 *
 * @return int[]
 */
function yka_article_years(): array {
	$cached = get_transient( 'yka_article_years' );
	if ( is_array( $cached ) ) {
		return $cached;
	}

	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- aggregate not expressible through WP_Query; result cached below.
	$years = $wpdb->get_col(
		"SELECT DISTINCT YEAR(post_date) AS y
		 FROM {$wpdb->posts}
		 WHERE post_type = 'post' AND post_status = 'publish'
		 ORDER BY y DESC"
	);

	$years = array_map( 'intval', (array) $years );
	set_transient( 'yka_article_years', $years, DAY_IN_SECONDS );

	return $years;
}

/**
 * Clears the cached year list when an article is published or removed.
 *
 * @return void
 */
function yka_flush_article_years(): void {
	delete_transient( 'yka_article_years' );
}
add_action( 'save_post_post', 'yka_flush_article_years' );
add_action( 'deleted_post', 'yka_flush_article_years' );

/**
 * Latest articles for a given unit.
 *
 * @param WP_Term|null $unit     Unit term, or null for all units.
 * @param int          $count    How many.
 * @param int[]        $exclude  Post ids to skip.
 * @return WP_Post[]
 */
function yka_get_unit_posts( ?WP_Term $unit, int $count = 4, array $exclude = array() ): array {
	$args = array(
		'post_type'              => 'post',
		'post_status'            => 'publish',
		'posts_per_page'         => $count,
		'ignore_sticky_posts'    => true,
		'no_found_rows'          => true,
		'update_post_meta_cache' => false,
		'post__not_in'           => array_map( 'intval', $exclude ),
	);

	if ( $unit instanceof WP_Term ) {
		$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			array(
				'taxonomy' => yka_unit_taxonomy(),
				'field'    => 'term_id',
				'terms'    => $unit->term_id,
			),
		);
	}

	$query = new WP_Query( $args );
	return $query->posts;
}

/**
 * Latest articles in a category slug, optionally limited to one unit.
 *
 * @param string       $slug    Category slug.
 * @param int          $count   How many.
 * @param int[]        $exclude Post ids to skip.
 * @param WP_Term|null $unit    Restrict to this education unit.
 * @return WP_Post[]
 */
function yka_get_category_posts( string $slug, int $count = 4, array $exclude = array(), ?WP_Term $unit = null ): array {
	if ( ! term_exists( $slug, 'category' ) ) {
		return array();
	}

	$args = array(
		'post_type'              => 'post',
		'post_status'            => 'publish',
		'category_name'          => $slug,
		'posts_per_page'         => $count,
		'ignore_sticky_posts'    => true,
		'no_found_rows'          => true,
		'update_post_meta_cache' => false,
		'post__not_in'           => array_map( 'intval', $exclude ),
	);

	if ( $unit instanceof WP_Term ) {
		$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			array(
				'taxonomy' => yka_unit_taxonomy(),
				'field'    => 'term_id',
				'terms'    => $unit->term_id,
			),
		);
	}

	$query = new WP_Query( $args );

	return $query->posts;
}

/**
 * Recent articles for the homepage, in one query.
 *
 * @param int   $count   How many.
 * @param int[] $exclude Post ids to skip.
 * @return WP_Post[]
 */
function yka_get_recent_posts( int $count = 8, array $exclude = array() ): array {
	$query = new WP_Query(
		array(
			'post_type'              => 'post',
			'post_status'            => 'publish',
			'posts_per_page'         => $count,
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'post__not_in'           => array_map( 'intval', $exclude ),
		)
	);

	return $query->posts;
}
