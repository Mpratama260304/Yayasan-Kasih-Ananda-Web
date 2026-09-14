<?php
/**
 * Third-party plugin integrations.
 *
 * Everything here uses feature detection. The theme must render correctly
 * with every optional plugin deactivated.
 *
 * @package YKA_Portal
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Whether Rank Math is active.
 *
 * @return bool
 */
function yka_rank_math_active(): bool {
	return defined( 'RANK_MATH_VERSION' ) || class_exists( '\RankMath\Helper' );
}

/**
 * Whether YKA Core is active.
 *
 * The theme degrades to a plain but working news site without it, rather
 * than throwing a fatal error.
 *
 * @return bool
 */
function yka_core_active(): bool {
	return function_exists( 'yka_get_post_unit' );
}

/**
 * Warns an administrator when the theme is running without YKA Core.
 *
 * @return void
 */
function yka_core_missing_notice(): void {
	if ( yka_core_active() || ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	printf(
		'<div class="notice notice-warning"><p><strong>%s</strong> %s</p></div>',
		esc_html__( 'YKA Portal:', 'yka-portal' ),
		esc_html__( 'plugin YKA Core belum aktif. Taksonomi Unit Pendidikan, metadata artikel, dan data terstruktur tidak tersedia sampai plugin diaktifkan.', 'yka-portal' )
	);
}
add_action( 'admin_notices', 'yka_core_missing_notice' );

/**
 * Feeds every article's activity date and location to Rank Math's variable
 * system, so editors can use them inside title and description templates.
 *
 * @return void
 */
function yka_register_rank_math_variables(): void {
	if ( ! function_exists( 'rank_math_register_var_replacement' ) || ! yka_core_active() ) {
		return;
	}

	rank_math_register_var_replacement(
		'yka_unit',
		array(
			'name'        => __( 'Unit Pendidikan', 'yka-portal' ),
			'description' => __( 'Nama unit pendidikan yang terkait dengan artikel.', 'yka-portal' ),
			'variable'    => 'yka_unit',
			'example'     => 'SMP Kasih Ananda I',
		),
		static function (): string {
			$unit = yka_get_post_unit( get_the_ID() );
			return $unit instanceof WP_Term ? $unit->name : '';
		}
	);

	rank_math_register_var_replacement(
		'yka_activity_date',
		array(
			'name'        => __( 'Tanggal Kegiatan', 'yka-portal' ),
			'description' => __( 'Tanggal kegiatan berlangsung, bukan tanggal terbit.', 'yka-portal' ),
			'variable'    => 'yka_activity_date',
			'example'     => '17 Agustus 2026',
		),
		static function (): string {
			return function_exists( 'yka_activity_date' ) ? yka_activity_date( (int) get_the_ID() ) : '';
		}
	);
}
add_action( 'rank_math/vars/register_extra_replacements', 'yka_register_rank_math_variables' );

/**
 * Stops Rank Math printing its own breadcrumb wrapper twice when the theme
 * already provides the landmark.
 *
 * @param array<string, mixed> $args Breadcrumb args.
 * @return array<string, mixed>
 */
function yka_rank_math_breadcrumb_args( $args ): array {
	$args = is_array( $args ) ? $args : array();

	$args['wrap_before'] = '<ol>';
	$args['wrap_after']  = '</ol>';
	$args['separator']   = '';
	$args['item_before'] = '<li>';
	$args['item_after']  = '</li>';
	$args['home']        = __( 'Beranda', 'yka-portal' );

	return $args;
}
add_filter( 'rank_math/frontend/breadcrumb/args', 'yka_rank_math_breadcrumb_args' );

/**
 * Adds the education unit to the WordPress core sitemap when Rank Math is
 * not the sitemap owner.
 *
 * Exactly one sitemap implementation is ever active.
 *
 * @return void
 */
function yka_manage_core_sitemap(): void {
	if ( yka_rank_math_active() ) {
		// Rank Math owns sitemaps; core's must not also be served.
		add_filter( 'wp_sitemaps_enabled', '__return_false' );
		return;
	}

	add_filter(
		'wp_sitemaps_taxonomies',
		static function ( $taxonomies ) {
			unset( $taxonomies['post_tag'] );
			return $taxonomies;
		}
	);
}
add_action( 'init', 'yka_manage_core_sitemap', 20 );

/**
 * Keeps WPvivid's admin notices out of the editorial dashboard.
 *
 * Backup software is an administrator concern; documentation staff should
 * not be prompted to configure cloud storage.
 *
 * @return void
 */
function yka_quiet_backup_notices(): void {
	if ( current_user_can( 'manage_options' ) ) {
		return;
	}

	remove_all_actions( 'wpvivid_admin_notices' );
}
add_action( 'admin_init', 'yka_quiet_backup_notices', 99 );
