<?php
/**
 * Plugin Name:       YKA Core
 * Plugin URI:        https://yayasankasihananda.com/
 * Description:       Model data dan alur kerja redaksional Yayasan Kasih Ananda: taksonomi Unit Pendidikan, metadata artikel, pengaturan lembaga, penjaga lingkungan (environment guard), integrasi SEO, dan bantuan berbagi ke media sosial. Dirancang agar data institusi tetap utuh meskipun tema situs diganti.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      8.0
 * Author:            Yayasan Kasih Ananda
 * Author URI:        https://yayasankasihananda.com/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       yka-core
 * Domain Path:       /languages
 *
 * @package YKA\Core
 */

declare( strict_types=1 );

namespace YKA\Core;

defined( 'ABSPATH' ) || exit;

const VERSION     = '1.0.0';
const PLUGIN_FILE = __FILE__;

define( 'YKA_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'YKA_CORE_URL', plugin_dir_url( __FILE__ ) );

/**
 * Maps `YKA\Core\Some_Thing` to `inc/class-some-thing.php`.
 *
 * @param string $class_name Fully qualified class name.
 * @return void
 */
function autoload( string $class_name ): void {
	if ( ! str_starts_with( $class_name, __NAMESPACE__ . '\\' ) ) {
		return;
	}

	$relative = substr( $class_name, strlen( __NAMESPACE__ ) + 1 );
	$file     = YKA_CORE_DIR . 'inc/class-' . strtolower( str_replace( '_', '-', $relative ) ) . '.php';

	if ( is_readable( $file ) ) {
		require_once $file;
	}
}
spl_autoload_register( __NAMESPACE__ . '\\autoload' );

require_once YKA_CORE_DIR . 'inc/helpers.php';

/**
 * Boots every module once WordPress has loaded its plugin API.
 *
 * Modules are intentionally small and independent: none of them may
 * assume the YKA Portal theme is active, and none of them may fatal
 * when an optional third-party plugin is missing.
 *
 * @return void
 */
function bootstrap(): void {
	$modules = array(
		Taxonomy::class,
		Unit_Meta::class,
		Post_Meta::class,
		Settings::class,
		Images::class,
		Environment::class,
		Editorial::class,
		Admin_Columns::class,
		Dashboard::class,
		Checklist::class,
		Social_Caption::class,
		Roles::class,
		Seo::class,
		Schema::class,
		Blueprint::class,
		Dev_Mail::class,
	);

	foreach ( $modules as $module ) {
		if ( class_exists( $module ) && method_exists( $module, 'init' ) ) {
			$module::init();
		}
	}
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\bootstrap', 5 );

/**
 * Loads translations.
 *
 * @return void
 */
function load_textdomain(): void {
	load_plugin_textdomain( 'yka-core', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', __NAMESPACE__ . '\\load_textdomain', 1 );

/**
 * Activation.
 *
 * Deliberately additive only. Activating this plugin must never delete,
 * overwrite or restore site content — see docs/MIGRATION.md.
 *
 * @return void
 */
function activate(): void {
	require_once YKA_CORE_DIR . 'inc/class-taxonomy.php';
	require_once YKA_CORE_DIR . 'inc/class-roles.php';
	require_once YKA_CORE_DIR . 'inc/class-images.php';

	Taxonomy::register();
	Taxonomy::ensure_default_terms();
	Roles::add_role();
	Images::register_sizes();

	flush_rewrite_rules();
	set_transient( 'yka_core_activated', 1, 60 );
}
register_activation_hook( __FILE__, __NAMESPACE__ . '\\activate' );

/**
 * Deactivation. Only clears rewrite rules; content and settings are kept.
 *
 * @return void
 */
function deactivate(): void {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, __NAMESPACE__ . '\\deactivate' );
