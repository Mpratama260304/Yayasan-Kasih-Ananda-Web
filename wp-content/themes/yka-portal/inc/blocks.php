<?php
/**
 * Block editor configuration: block styles and pattern registration.
 *
 * No custom block is built here. Core blocks already solve everything this
 * site needs, and they keep working across theme and WordPress upgrades.
 *
 * @package YKA_Portal
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Registers block style variations.
 *
 * @return void
 */
function yka_register_block_styles(): void {
	register_block_style(
		'core/group',
		array(
			'name'  => 'yka-callout',
			'label' => __( 'Kotak informasi', 'yka-portal' ),
		)
	);

	register_block_style(
		'core/image',
		array(
			'name'  => 'yka-bordered',
			'label' => __( 'Bergaris tepi', 'yka-portal' ),
		)
	);

	register_block_style(
		'core/list',
		array(
			'name'  => 'yka-checklist',
			'label' => __( 'Daftar periksa', 'yka-portal' ),
		)
	);

	register_block_style(
		'core/separator',
		array(
			'name'  => 'yka-short',
			'label' => __( 'Garis pendek', 'yka-portal' ),
		)
	);
}
add_action( 'init', 'yka_register_block_styles' );

/**
 * Registers the pattern category when YKA Core is not available.
 *
 * The plugin normally owns this, so the category survives a theme change.
 *
 * @return void
 */
function yka_register_pattern_category_fallback(): void {
	if ( ! function_exists( 'register_block_pattern_category' ) ) {
		return;
	}

	if ( class_exists( '\WP_Block_Pattern_Categories_Registry' ) ) {
		$registry = WP_Block_Pattern_Categories_Registry::get_instance();
		if ( $registry->is_registered( 'yka' ) ) {
			return;
		}
	}

	register_block_pattern_category(
		'yka',
		array( 'label' => __( 'Yayasan Kasih Ananda', 'yka-portal' ) )
	);
}
add_action( 'init', 'yka_register_pattern_category_fallback', 9 );

/**
 * Restricts the block palette for the Dokumentator role only.
 *
 * Editors and administrators keep every block. Documentation staff get a
 * focused set that matches what a news article actually needs — this is a
 * usability decision, not a lockdown.
 *
 * @param bool|string[]           $allowed Allowed block types.
 * @param WP_Block_Editor_Context $context Editor context.
 * @return bool|string[]
 */
function yka_allowed_block_types( $allowed, $context ) {
	unset( $context );

	if ( ! function_exists( 'wp_get_current_user' ) ) {
		return $allowed;
	}

	$user = wp_get_current_user();
	if ( ! in_array( 'yka_dokumentator', (array) $user->roles, true ) ) {
		return $allowed;
	}

	return array(
		'core/paragraph',
		'core/heading',
		'core/list',
		'core/list-item',
		'core/image',
		'core/gallery',
		'core/quote',
		'core/separator',
		'core/spacer',
		'core/table',
		'core/embed',
		'core/video',
		'core/file',
		'core/group',
		'core/columns',
		'core/column',
		'core/buttons',
		'core/button',
		'core/pullquote',
	);
}
add_filter( 'allowed_block_types_all', 'yka_allowed_block_types', 10, 2 );

/**
 * Removes core pattern directory downloads.
 *
 * Remote patterns are generic marketing layouts that would undo the point of
 * shipping institution-specific patterns.
 *
 * @return void
 */
function yka_disable_remote_patterns(): void {
	remove_theme_support( 'core-block-patterns' );
	add_filter( 'should_load_remote_block_patterns', '__return_false' );
}
add_action( 'after_setup_theme', 'yka_disable_remote_patterns', 20 );
