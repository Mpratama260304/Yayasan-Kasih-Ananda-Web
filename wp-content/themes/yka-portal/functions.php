<?php
/**
 * YKA Portal — theme bootstrap.
 *
 * Responsibilities are split across inc/ so this file stays readable.
 * Institution-specific data and behaviour live in the YKA Core plugin, not
 * here, so the site keeps its content model if the theme is ever replaced.
 *
 * @package YKA_Portal
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

define( 'YKA_THEME_VERSION', '1.0.0' );
define( 'YKA_THEME_DIR', get_template_directory() );
define( 'YKA_THEME_URI', get_template_directory_uri() );

require_once YKA_THEME_DIR . '/inc/setup.php';
require_once YKA_THEME_DIR . '/inc/enqueue.php';
require_once YKA_THEME_DIR . '/inc/template-tags.php';
require_once YKA_THEME_DIR . '/inc/query.php';
require_once YKA_THEME_DIR . '/inc/navigation.php';
require_once YKA_THEME_DIR . '/inc/integrations.php';
require_once YKA_THEME_DIR . '/inc/blocks.php';
