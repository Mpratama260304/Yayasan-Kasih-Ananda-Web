<?php
/**
 * Theme supports, menus and image sizes.
 *
 * @package YKA_Portal
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Registers theme support.
 *
 * @return void
 */
function yka_theme_setup(): void {
	load_theme_textdomain( 'yka-portal', YKA_THEME_DIR . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'custom-line-height' );
	add_theme_support( 'custom-spacing' );

	add_theme_support(
		'html5',
		array( 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' )
	);

	add_theme_support(
		'custom-logo',
		array(
			'height'               => 96,
			'width'                => 400,
			'flex-height'          => true,
			'flex-width'           => true,
			'unlink-homepage-logo' => false,
		)
	);

	// Crops used by the theme itself. Structured-data crops are registered
	// by YKA Core so they survive a change of theme.
	add_image_size( 'yka-card', 800, 600, true );
	add_image_size( 'yka-thumb', 300, 300, true );

	register_nav_menus(
		array(
			'primary' => __( 'Navigasi Utama', 'yka-portal' ),
			'utility' => __( 'Navigasi Atas', 'yka-portal' ),
			'footer'  => __( 'Navigasi Footer', 'yka-portal' ),
		)
	);

	// Editors should not be able to dismantle the site's global layout by
	// accident; templates are managed in code, content in Gutenberg.
	remove_theme_support( 'block-templates' );
}
add_action( 'after_setup_theme', 'yka_theme_setup' );

/**
 * Sets the content width used by oEmbeds.
 *
 * @return void
 */
function yka_content_width(): void {
	$GLOBALS['content_width'] = 760;
}
add_action( 'after_setup_theme', 'yka_content_width', 0 );

/**
 * Exposes the theme crops in the block editor image size picker.
 *
 * @param array<string, string> $sizes Registered choices.
 * @return array<string, string>
 */
function yka_editor_image_sizes( array $sizes ): array {
	$sizes['yka-card'] = __( 'Kartu berita', 'yka-portal' );
	return $sizes;
}
add_filter( 'image_size_names_choose', 'yka_editor_image_sizes' );

/**
 * Adds a body class naming the current education unit.
 *
 * Lets the per-unit accent colour cascade without inline styles.
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function yka_body_classes( array $classes ): array {
	$unit = yka_current_unit();

	if ( $unit instanceof WP_Term ) {
		$classes[] = 'yka-has-unit';
		$classes[] = 'yka-unit-' . sanitize_html_class( $unit->slug );
	}

	if ( is_singular( 'post' ) ) {
		$classes[] = 'yka-single-article';
	}

	return $classes;
}
add_filter( 'body_class', 'yka_body_classes' );

/**
 * Lets performance hints survive output escaping.
 *
 * KSES strips any attribute it does not know, and `fetchpriority` is not on
 * its allow-list. Without this, every `wp_kses_post()` call silently removes
 * the one hint that tells the browser which image to fetch first.
 *
 * @param array<string, array<string, bool>> $tags    Allowed tags.
 * @param string                             $context Escaping context.
 * @return array<string, array<string, bool>>
 */
function yka_allow_image_priority_attributes( $tags, $context ): array {
	$tags = is_array( $tags ) ? $tags : array();

	if ( 'post' !== $context ) {
		return $tags;
	}

	foreach ( array( 'img', 'iframe' ) as $tag ) {
		if ( isset( $tags[ $tag ] ) && is_array( $tags[ $tag ] ) ) {
			$tags[ $tag ]['fetchpriority'] = true;
			$tags[ $tag ]['decoding']      = true;
			$tags[ $tag ]['loading']       = true;
		}
	}

	return $tags;
}
add_filter( 'wp_kses_allowed_html', 'yka_allow_image_priority_attributes', 10, 2 );

/**
 * Removes markup WordPress emits that this site has no use for.
 *
 * RSS feeds are kept: they matter for syndication and future integrations.
 *
 * @return void
 */
function yka_clean_head(): void {
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head' );
	remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10 );
}
add_action( 'init', 'yka_clean_head' );

/**
 * Disables the emoji detection script and its DNS prefetch.
 *
 * @return void
 */
function yka_disable_emojis(): void {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	add_filter( 'emoji_svg_url', '__return_false' );
}
add_action( 'init', 'yka_disable_emojis' );

/**
 * Returns a real 404 status for feeds that have no content.
 *
 * @return void
 */
function yka_feed_404(): void {
	if ( is_feed() && ! have_posts() ) {
		status_header( 404 );
	}
}
add_action( 'template_redirect', 'yka_feed_404' );
