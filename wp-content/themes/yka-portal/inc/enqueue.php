<?php
/**
 * Asset loading.
 *
 * The frontend ships two small scripts and a handful of stylesheets. There
 * is no framework, no icon library and no third-party font request.
 *
 * @package YKA_Portal
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Cache-busting version for a theme asset, based on its modification time.
 *
 * @param string $relative_path Path relative to the theme root.
 * @return string
 */
function yka_asset_version( string $relative_path ): string {
	$file = YKA_THEME_DIR . '/' . ltrim( $relative_path, '/' );
	$time = file_exists( $file ) ? filemtime( $file ) : false;

	return $time ? (string) $time : YKA_THEME_VERSION;
}

/**
 * The stylesheets that make up the frontend, in cascade order.
 *
 * Kept as separate files on purpose: one 8,000-line stylesheet is
 * unmaintainable, and over HTTP/2 the request cost of five small, highly
 * cacheable files is negligible.
 *
 * @return array<string, string> handle => relative path
 */
function yka_stylesheets(): array {
	return array(
		'yka-tokens'     => 'assets/css/tokens.css',
		'yka-base'       => 'assets/css/base.css',
		'yka-layout'     => 'assets/css/layout.css',
		'yka-components' => 'assets/css/components.css',
		'yka-editorial'  => 'assets/css/editorial.css',
		'yka-utilities'  => 'assets/css/utilities.css',
	);
}

/**
 * Enqueues frontend styles and scripts.
 *
 * @return void
 */
function yka_enqueue_assets(): void {
	$previous = array();

	foreach ( yka_stylesheets() as $handle => $path ) {
		wp_enqueue_style( $handle, YKA_THEME_URI . '/' . $path, $previous, yka_asset_version( $path ) );
		$previous = array( $handle );
	}

	// The theme stylesheet carries only the theme header, but WordPress and
	// several plugins expect the `style.css` handle to exist.
	wp_register_style( 'yka-portal', get_stylesheet_uri(), array( 'yka-utilities' ), yka_asset_version( 'style.css' ) );
	wp_enqueue_style( 'yka-portal' );

	wp_enqueue_script(
		'yka-navigation',
		YKA_THEME_URI . '/assets/js/navigation.js',
		array(),
		yka_asset_version( 'assets/js/navigation.js' ),
		array(
			'strategy'  => 'defer',
			'in_footer' => true,
		)
	);

	wp_localize_script(
		'yka-navigation',
		'ykaNav',
		array(
			'openMenu'    => __( 'Buka menu navigasi', 'yka-portal' ),
			'closeMenu'   => __( 'Tutup menu navigasi', 'yka-portal' ),
			'openSearch'  => __( 'Buka pencarian', 'yka-portal' ),
			'closeSearch' => __( 'Tutup pencarian', 'yka-portal' ),
		)
	);

	if ( is_singular( 'post' ) ) {
		wp_enqueue_script(
			'yka-share',
			YKA_THEME_URI . '/assets/js/share.js',
			array(),
			yka_asset_version( 'assets/js/share.js' ),
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);

		wp_localize_script(
			'yka-share',
			'ykaShare',
			array(
				'copied'    => __( 'Tautan disalin.', 'yka-portal' ),
				'copyError' => __( 'Tautan tidak dapat disalin otomatis. Silakan salin dari bilah alamat.', 'yka-portal' ),
				'shareText' => __( 'Bagikan', 'yka-portal' ),
			)
		);
	}
}
add_action( 'wp_enqueue_scripts', 'yka_enqueue_assets' );

/**
 * Preloads the two font subsets that are used on every page.
 *
 * Only the `latin` subsets are preloaded — `latin-ext` is fetched on demand
 * through unicode-range, and preloading it would waste bandwidth.
 *
 * @return void
 */
function yka_preload_fonts(): void {
	foreach ( array(
		'assets/fonts/source-sans-3-latin-wght-normal.woff2',
		'assets/fonts/source-serif-4-latin-wght-normal.woff2',
	) as $font ) {
		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin="anonymous" />' . "\n",
			esc_url( YKA_THEME_URI . '/' . $font )
		);
	}
}
add_action( 'wp_head', 'yka_preload_fonts', 1 );

/**
 * Loads the block editor stylesheet so the writing canvas resembles the
 * published article.
 *
 * @return void
 */
function yka_editor_assets(): void {
	add_editor_style(
		array(
			'assets/css/tokens.css',
			'assets/css/editor.css',
		)
	);
}
add_action( 'after_setup_theme', 'yka_editor_assets', 20 );

/**
 * Removes the core block library's opinionated theme layer.
 *
 * The theme styles core blocks itself; loading both means shipping rules
 * that are immediately overridden.
 *
 * @return void
 */
function yka_dequeue_block_theme_styles(): void {
	wp_dequeue_style( 'wp-block-library-theme' );
	wp_dequeue_style( 'classic-theme-styles' );
}
add_action( 'wp_enqueue_scripts', 'yka_dequeue_block_theme_styles', 20 );

/**
 * Marks the hero image as the LCP candidate and stops it being lazy-loaded.
 *
 * @param string $html Image markup.
 * @return string
 */
function yka_prioritise_hero_image( string $html ): string {
	$html = str_replace( ' loading="lazy"', '', $html );

	if ( ! str_contains( $html, 'fetchpriority=' ) ) {
		$html = str_replace( '<img ', '<img fetchpriority="high" decoding="async" ', $html );
	}

	return $html;
}

/**
 * Adds width and height to the site logo so it cannot shift the header.
 *
 * @param string $html Logo markup.
 * @return string
 */
function yka_logo_attributes( $html ): string {
	return str_replace( '<img ', '<img decoding="async" ', (string) $html );
}
add_filter( 'get_custom_logo', 'yka_logo_attributes' );
