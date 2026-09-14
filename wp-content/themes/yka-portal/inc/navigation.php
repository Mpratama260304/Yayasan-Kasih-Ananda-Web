<?php
/**
 * Navigation rendering.
 *
 * @package YKA_Portal
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Renders a registered menu, or nothing at all if it has not been assigned.
 *
 * Navigation is never hard-coded into a template: an administrator must be
 * able to change it from Appearance → Menus without touching PHP.
 *
 * @param string               $location Menu location.
 * @param array<string, mixed> $args     wp_nav_menu overrides.
 * @return void
 */
function yka_nav_menu( string $location, array $args = array() ): void {
	if ( ! has_nav_menu( $location ) ) {
		return;
	}

	wp_nav_menu(
		wp_parse_args(
			$args,
			array(
				'theme_location' => $location,
				'container'      => false,
				'depth'          => 2,
				'fallback_cb'    => false,
			)
		)
	);
}

/**
 * Adds a disclosure button to submenu parents in the primary navigation.
 *
 * The submenu opens on hover for pointer users and on click or Enter for
 * everyone else, so no navigation is ever hover-only.
 *
 * @param string   $output Menu item output.
 * @param WP_Post  $item   Menu item.
 * @param int      $depth  Depth.
 * @param stdClass $args   Menu args.
 * @return string
 */
function yka_nav_submenu_toggle( $output, $item, $depth, $args ): string {
	$location = is_object( $args ) ? ( $args->theme_location ?? '' ) : '';

	if ( 'primary' !== $location || 0 !== (int) $depth ) {
		return (string) $output;
	}

	if ( ! in_array( 'menu-item-has-children', (array) $item->classes, true ) ) {
		return (string) $output;
	}

	$button = sprintf(
		'<button type="button" class="yka-submenu-toggle" aria-expanded="false"><span class="screen-reader-text">%s</span>%s</button>',
		esc_html(
			sprintf(
				/* translators: %s: parent menu item title. */
				__( 'Buka submenu %s', 'yka-portal' ),
				wp_strip_all_tags( (string) $item->title )
			)
		),
		yka_icon( 'chevron', array( 'size' => 16 ) )
	);

	return $output . $button;
}
add_filter( 'walker_nav_menu_start_el', 'yka_nav_submenu_toggle', 10, 4 );

/**
 * Gives submenus a predictable class.
 *
 * @param string[] $classes Submenu classes.
 * @return string[]
 */
function yka_submenu_classes( $classes ): array {
	$classes   = is_array( $classes ) ? $classes : array();
	$classes[] = 'yka-submenu';
	return $classes;
}
add_filter( 'nav_menu_submenu_css_class', 'yka_submenu_classes' );

/**
 * Marks the posts page as current when viewing any article or unit archive.
 *
 * WordPress only highlights the posts page on the archive itself, which
 * makes the navigation look wrong while reading an article.
 *
 * @param string[] $classes Menu item classes.
 * @param WP_Post  $item    Menu item.
 * @return string[]
 */
function yka_highlight_news_menu_item( $classes, $item ): array {
	$classes = is_array( $classes ) ? $classes : array();

	$posts_page = (int) get_option( 'page_for_posts' );
	if ( ! $posts_page || 'page' !== ( $item->object ?? '' ) || (int) $item->object_id !== $posts_page ) {
		return $classes;
	}

	$is_article_context = is_singular( 'post' )
		|| ( function_exists( 'yka_unit_taxonomy' ) && is_tax( yka_unit_taxonomy() ) )
		|| is_category()
		|| is_tag();

	if ( $is_article_context && ! in_array( 'current-menu-item', $classes, true ) ) {
		$classes[] = 'current-menu-ancestor';
	}

	return $classes;
}
add_filter( 'nav_menu_css_class', 'yka_highlight_news_menu_item', 10, 2 );
