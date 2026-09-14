<?php
/**
 * Editorial adjustments to the WordPress admin.
 *
 * @package YKA\Core
 */

declare( strict_types=1 );

namespace YKA\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Small changes that make the admin read like a school documentation desk
 * rather than a generic blog: relabelled menus, a block pattern category,
 * and defaults tuned for an institutional newsroom.
 *
 * The underlying post type stays `post` so exports, feeds, SEO plugins and
 * future themes keep working.
 */
final class Editorial {

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'relabel_posts_menu' ), 20 );
		add_action( 'init', array( __CLASS__, 'relabel_post_object' ), 20 );
		add_action( 'init', array( __CLASS__, 'register_pattern_category' ), 20 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin' ) );

		// Institutional site: no public comment moderation burden.
		add_filter( 'comments_open', '__return_false', 20 );
		add_filter( 'pings_open', '__return_false', 20 );
		add_filter( 'xmlrpc_enabled', '__return_false' );
		add_action( 'init', array( __CLASS__, 'disable_comment_support' ), 20 );
		add_action( 'admin_menu', array( __CLASS__, 'hide_comments_menu' ), 999 );

		// Excerpts are the lead paragraph of every article; make them visible.
		add_filter( 'excerpt_length', array( __CLASS__, 'excerpt_length' ), 20 );
		add_filter( 'excerpt_more', array( __CLASS__, 'excerpt_more' ), 20 );

		add_filter( 'upload_mimes', array( __CLASS__, 'allow_svg_for_admins' ) );
	}

	/**
	 * Renames the Posts menu without changing the post type.
	 *
	 * WordPress exposes no API for relabelling an existing admin menu entry,
	 * so the menu globals are edited in place. The post type itself stays
	 * `post`, which is what keeps exports, feeds and SEO plugins working.
	 *
	 * @return void
	 */
	public static function relabel_posts_menu(): void {
		global $menu, $submenu;

		if ( ! is_array( $menu ) ) {
			return;
		}

		foreach ( $menu as $index => $item ) {
			if ( isset( $item[2] ) && 'edit.php' === $item[2] ) {
				// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- documented above.
				$menu[ $index ][0] = __( 'Berita & Dokumentasi', 'yka-core' );
				break;
			}
		}

		if ( isset( $submenu['edit.php'][10][0] ) ) {
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- documented above.
			$submenu['edit.php'][10][0] = __( 'Tambah Berita / Dokumentasi', 'yka-core' );
		}
		if ( isset( $submenu['edit.php'][5][0] ) ) {
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- documented above.
			$submenu['edit.php'][5][0] = __( 'Semua Berita', 'yka-core' );
		}
	}

	/**
	 * Relabels the post type object so editor screens match the menu.
	 *
	 * @return void
	 */
	public static function relabel_post_object(): void {
		$object = get_post_type_object( 'post' );
		if ( ! $object ) {
			return;
		}

		$object->labels->name               = __( 'Berita & Dokumentasi', 'yka-core' );
		$object->labels->singular_name      = __( 'Berita', 'yka-core' );
		$object->labels->add_new_item       = __( 'Tambah Berita / Dokumentasi', 'yka-core' );
		$object->labels->edit_item          = __( 'Ubah Berita', 'yka-core' );
		$object->labels->new_item           = __( 'Berita Baru', 'yka-core' );
		$object->labels->view_item          = __( 'Lihat Berita', 'yka-core' );
		$object->labels->search_items       = __( 'Cari Berita', 'yka-core' );
		$object->labels->not_found          = __( 'Belum ada berita.', 'yka-core' );
		$object->labels->not_found_in_trash = __( 'Tidak ada berita di tempat sampah.', 'yka-core' );
		$object->labels->all_items          = __( 'Semua Berita', 'yka-core' );
		$object->labels->menu_name          = __( 'Berita & Dokumentasi', 'yka-core' );
	}

	/**
	 * Registers the YKA block pattern category.
	 *
	 * @return void
	 */
	public static function register_pattern_category(): void {
		if ( ! function_exists( 'register_block_pattern_category' ) ) {
			return;
		}

		register_block_pattern_category(
			'yka',
			array(
				'label'       => __( 'Yayasan Kasih Ananda', 'yka-core' ),
				'description' => __( 'Susunan blok siap pakai untuk berita dan dokumentasi kegiatan.', 'yka-core' ),
			)
		);
	}

	/**
	 * Removes comment support from posts and pages.
	 *
	 * @return void
	 */
	public static function disable_comment_support(): void {
		foreach ( array( 'post', 'page' ) as $type ) {
			remove_post_type_support( $type, 'comments' );
			remove_post_type_support( $type, 'trackbacks' );
		}
	}

	/**
	 * Hides the Comments admin menu.
	 *
	 * @return void
	 */
	public static function hide_comments_menu(): void {
		remove_menu_page( 'edit-comments.php' );
	}

	/**
	 * Excerpt length in words.
	 *
	 * @param int $length Default length.
	 * @return int
	 */
	public static function excerpt_length( $length ): int {
		unset( $length );
		return 32;
	}

	/**
	 * Excerpt ellipsis without a "read more" link, because cards already link.
	 *
	 * @param string $more Default more string.
	 * @return string
	 */
	public static function excerpt_more( $more ): string {
		unset( $more );
		return '…';
	}

	/**
	 * Allows SVG uploads for administrators only.
	 *
	 * Unit and foundation logos are usually supplied as SVG. Lower-privilege
	 * documentation staff never gain this capability, because an SVG can
	 * carry script.
	 *
	 * @param array<string, string> $mimes Allowed mime types.
	 * @return array<string, string>
	 */
	public static function allow_svg_for_admins( $mimes ): array {
		$mimes = is_array( $mimes ) ? $mimes : array();

		if ( current_user_can( 'manage_options' ) ) {
			$mimes['svg'] = 'image/svg+xml';
		}

		return $mimes;
	}

	/**
	 * Loads the admin stylesheet on editorial screens.
	 *
	 * @param string $hook Current admin page.
	 * @return void
	 */
	public static function enqueue_admin( string $hook ): void {
		$screens = array( 'index.php', 'edit.php', 'post.php', 'post-new.php' );
		if ( ! in_array( $hook, $screens, true ) ) {
			return;
		}

		wp_enqueue_style( 'yka-core-admin', YKA_CORE_URL . 'assets/css/admin.css', array(), VERSION );
	}
}
