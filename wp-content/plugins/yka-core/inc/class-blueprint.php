<?php
/**
 * Portable blueprint of the institutional configuration.
 *
 * @package YKA\Core
 */

declare( strict_types=1 );

namespace YKA\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Exports and imports everything that makes this a Yayasan Kasih Ananda site
 * rather than a blank WordPress: institution settings, education units and
 * their metadata, categories, the page skeleton, and the navigation menus.
 *
 * The file is JSON, small enough to read, diff, and keep in version control.
 *
 * Two deliberate boundaries:
 *
 *   1. It carries no media and no plugin or theme files. Those are the job of
 *      a real backup tool, and pretending otherwise would produce a file that
 *      restores a site only halfway.
 *   2. Importing only ever creates or updates, matched by slug. Nothing is
 *      deleted, so applying a blueprint to a live site cannot lose work.
 */
final class Blueprint {

	public const MENU_SLUG = 'yka-blueprint';
	public const FORMAT    = 'yka-blueprint';
	public const VERSION   = 1;

	private const NONCE     = 'yka_blueprint';
	private const MAX_BYTES = 2097152; // 2 MB — a blueprint is text, never large.
	private const MAX_DEPTH = 16;
	private const TRANSIENT = 'yka_blueprint_report';

	/**
	 * Core options worth carrying. Anything site-specific — URLs, keys, user
	 * data — is deliberately absent.
	 *
	 * @var string[]
	 */
	private const OPTIONS = array(
		'blogname',
		'blogdescription',
		'permalink_structure',
		'category_base',
		'tag_base',
		'posts_per_page',
		'posts_per_rss',
		'rss_use_excerpt',
		'date_format',
		'time_format',
		'start_of_week',
		'timezone_string',
		'default_comment_status',
		'default_ping_status',
		'image_default_size',
		'image_default_link_type',
	);

	/**
	 * Settings keys holding an attachment or post ID. These identify rows in
	 * one particular database and mean nothing anywhere else, so they are
	 * dropped on the way out rather than imported as dangling references.
	 *
	 * @var string[]
	 */
	private const LOCAL_IDS = array( 'hero_image', 'featured_post' );

	/**
	 * Unit meta keys holding an attachment ID. Dropped for the same reason.
	 *
	 * @var string[]
	 */
	private const LOCAL_UNIT_IDS = array( 'yka_unit_logo_id', 'yka_unit_hero_id' );

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ), 11 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_action( 'admin_post_yka_blueprint_export', array( __CLASS__, 'handle_export' ) );
		add_action( 'admin_post_yka_blueprint_import', array( __CLASS__, 'handle_import' ) );
	}

	/**
	 * Loads the shared admin stylesheet on this screen.
	 *
	 * @param string $hook Current admin page.
	 * @return void
	 */
	public static function enqueue( string $hook ): void {
		if ( ! str_contains( $hook, self::MENU_SLUG ) ) {
			return;
		}

		wp_enqueue_style( 'yka-core-admin', YKA_CORE_URL . 'assets/css/admin.css', array(), VERSION );
	}

	/**
	 * Registers the screen under the Yayasan menu.
	 *
	 * @return void
	 */
	public static function add_menu(): void {
		add_submenu_page(
			Settings::MENU_SLUG,
			__( 'Ekspor & Impor', 'yka-core' ),
			__( 'Ekspor & Impor', 'yka-core' ),
			'manage_options',
			self::MENU_SLUG,
			array( __CLASS__, 'render_page' )
		);
	}

	/*
	==============================================================
		Building the blueprint
		============================================================== */

	/**
	 * Collects the whole configuration into one array.
	 *
	 * @return array<string, mixed>
	 */
	public static function build(): array {
		return array(
			'format'     => self::FORMAT,
			'version'    => self::VERSION,
			'generated'  => gmdate( 'c' ),
			'source'     => home_url(),
			'settings'   => self::collect_settings(),
			'options'    => self::collect_options(),
			'units'      => self::collect_units(),
			'categories' => self::collect_categories(),
			'pages'      => self::collect_pages(),
			'menus'      => self::collect_menus(),
		);
	}

	/**
	 * Institution settings, minus anything that points at a local row.
	 *
	 * @return array<string, string>
	 */
	private static function collect_settings(): array {
		$stored = (array) get_option( Settings::OPTION, array() );
		$out    = array();

		foreach ( $stored as $key => $value ) {
			$key = (string) $key;
			if ( in_array( $key, self::LOCAL_IDS, true ) || ! is_scalar( $value ) ) {
				continue;
			}
			$out[ $key ] = (string) $value;
		}

		ksort( $out );
		return $out;
	}

	/**
	 * Whitelisted core options.
	 *
	 * @return array<string, string>
	 */
	private static function collect_options(): array {
		$out = array();

		foreach ( self::OPTIONS as $name ) {
			$value = get_option( $name, null );
			if ( is_scalar( $value ) ) {
				$out[ $name ] = (string) $value;
			}
		}

		$front = (int) get_option( 'page_on_front' );
		$posts = (int) get_option( 'page_for_posts' );

		// Stored as slugs: IDs differ on every install.
		$out['show_on_front']       = (string) get_option( 'show_on_front', 'posts' );
		$out['page_on_front_slug']  = $front > 0 ? (string) get_post_field( 'post_name', $front ) : '';
		$out['page_for_posts_slug'] = $posts > 0 ? (string) get_post_field( 'post_name', $posts ) : '';

		return $out;
	}

	/**
	 * Education units with their institutional metadata.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private static function collect_units(): array {
		$terms = get_terms(
			array(
				'taxonomy'   => Taxonomy::TAXONOMY,
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $terms ) ) {
			return array();
		}

		$out = array();

		foreach ( $terms as $term ) {
			$meta = array();

			foreach ( array_keys( Unit_Meta::fields() ) as $key ) {
				if ( in_array( $key, self::LOCAL_UNIT_IDS, true ) ) {
					continue;
				}
				$value = get_term_meta( $term->term_id, $key, true );
				if ( '' !== $value && null !== $value ) {
					$meta[ $key ] = is_scalar( $value ) ? (string) $value : '';
				}
			}

			$out[] = array(
				'name'        => $term->name,
				'slug'        => $term->slug,
				'description' => $term->description,
				'parent'      => self::term_slug( (int) $term->parent ),
				'meta'        => $meta,
			);
		}

		return $out;
	}

	/**
	 * Post categories.
	 *
	 * @return array<int, array<string, string>>
	 */
	private static function collect_categories(): array {
		$terms = get_terms(
			array(
				'taxonomy'   => 'category',
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $terms ) ) {
			return array();
		}

		$out = array();

		foreach ( $terms as $term ) {
			$out[] = array(
				'name'        => $term->name,
				'slug'        => $term->slug,
				'description' => $term->description,
				'parent'      => self::term_slug( (int) $term->parent, 'category' ),
			);
		}

		return $out;
	}

	/**
	 * Published pages, content included, with the parent expressed as a slug.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private static function collect_pages(): array {
		$pages = get_posts(
			array(
				'post_type'        => 'page',
				'post_status'      => array( 'publish', 'draft', 'private' ),
				'numberposts'      => -1,
				'orderby'          => 'menu_order',
				'order'            => 'ASC',
				'suppress_filters' => false,
			)
		);

		$out = array();

		foreach ( $pages as $page ) {
			$parent = (int) $page->post_parent;

			$out[] = array(
				'title'    => $page->post_title,
				'slug'     => $page->post_name,
				'status'   => $page->post_status,
				'order'    => (int) $page->menu_order,
				'parent'   => $parent > 0 ? (string) get_post_field( 'post_name', $parent ) : '',
				'template' => (string) get_page_template_slug( $page->ID ),
				'content'  => $page->post_content,
				'excerpt'  => $page->post_excerpt,
			);
		}

		return $out;
	}

	/**
	 * Navigation menus, their theme locations, and their items.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private static function collect_menus(): array {
		$menus     = wp_get_nav_menus();
		$locations = array_filter( (array) get_nav_menu_locations() );
		$out       = array();

		foreach ( $menus as $menu ) {
			$items = wp_get_nav_menu_items( $menu->term_id );
			$items = is_array( $items ) ? $items : array();

			$by_id = array();
			foreach ( $items as $item ) {
				$by_id[ (int) $item->ID ] = $item;
			}

			$entries = array();

			foreach ( $items as $item ) {
				$parent = (int) $item->menu_item_parent;

				$entries[] = array(
					'title'       => $item->title,
					'type'        => (string) $item->type,
					'object'      => (string) $item->object,
					'object_slug' => self::menu_object_slug( $item ),
					'url'         => 'custom' === $item->type ? (string) $item->url : '',
					'order'       => (int) $item->menu_order,
					'parent_key'  => isset( $by_id[ $parent ] ) ? self::menu_item_key( $by_id[ $parent ] ) : '',
					'key'         => self::menu_item_key( $item ),
				);
			}

			$out[] = array(
				'name'      => $menu->name,
				'slug'      => $menu->slug,
				'locations' => array_keys( $locations, (int) $menu->term_id, true ),
				'items'     => $entries,
			);
		}

		return $out;
	}

	/*
	==============================================================
		Export
		============================================================== */

	/**
	 * Streams the blueprint as a JSON download.
	 *
	 * @return void
	 */
	public static function handle_export(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Anda tidak berwenang mengekspor konfigurasi.', 'yka-core' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( self::NONCE );

		$json = wp_json_encode( self::build(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

		if ( false === $json ) {
			wp_die( esc_html__( 'Konfigurasi gagal diubah menjadi JSON.', 'yka-core' ) );
		}

		$host = (string) wp_parse_url( home_url(), PHP_URL_HOST );
		$name = sprintf( 'yka-blueprint-%s-%s.json', sanitize_file_name( $host ), gmdate( 'Ymd-Hi' ) );

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $name . '"' );
		header( 'Content-Length: ' . strlen( $json ) );

		echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON download, not markup.
		exit;
	}

	/*
	==============================================================
		Import
		============================================================== */

	/**
	 * Validates the uploaded file and applies it.
	 *
	 * @return void
	 */
	public static function handle_import(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Anda tidak berwenang mengimpor konfigurasi.', 'yka-core' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( self::NONCE );

		$report = self::read_upload();

		if ( is_wp_error( $report ) ) {
			self::report( array( 'error' => $report->get_error_message() ) );
			self::redirect_back();
		}

		self::report( self::apply( (array) $report ) );
		self::redirect_back();
	}

	/**
	 * Turns `$_FILES` into a validated blueprint array.
	 *
	 * @return array<string, mixed>|\WP_Error
	 */
	private static function read_upload() {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- the nonce is verified by the caller.
		if ( ! isset( $_FILES['yka_blueprint']['tmp_name'], $_FILES['yka_blueprint']['error'], $_FILES['yka_blueprint']['size'] ) ) {
			return new \WP_Error( 'yka_no_file', __( 'Tidak ada berkas yang diunggah.', 'yka-core' ) );
		}

		$error = (int) $_FILES['yka_blueprint']['error'];
		$size  = (int) $_FILES['yka_blueprint']['size'];
		$tmp   = sanitize_text_field( wp_unslash( (string) $_FILES['yka_blueprint']['tmp_name'] ) );
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( UPLOAD_ERR_OK !== $error ) {
			return new \WP_Error( 'yka_upload', __( 'Berkas gagal diunggah. Periksa batas ukuran unggahan di server.', 'yka-core' ) );
		}

		// The only check that matters: PHP itself must vouch that this path
		// came from this request's upload, not from anywhere else on disk.
		if ( ! is_uploaded_file( $tmp ) ) {
			return new \WP_Error( 'yka_not_upload', __( 'Berkas tidak dikenali sebagai unggahan yang sah.', 'yka-core' ) );
		}

		if ( $size > self::MAX_BYTES ) {
			return new \WP_Error( 'yka_too_big', __( 'Berkas terlalu besar untuk sebuah blueprint. Ini bukan berkas cadangan.', 'yka-core' ) );
		}

		$raw = file_get_contents( $tmp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- reading an upload from disk, not a URL.

		if ( false === $raw || '' === $raw ) {
			return new \WP_Error( 'yka_empty', __( 'Berkas kosong.', 'yka-core' ) );
		}

		$data = json_decode( $raw, true, self::MAX_DEPTH );

		if ( ! is_array( $data ) || JSON_ERROR_NONE !== json_last_error() ) {
			return new \WP_Error( 'yka_json', __( 'Isi berkas bukan JSON yang sah.', 'yka-core' ) );
		}

		if ( ( $data['format'] ?? '' ) !== self::FORMAT ) {
			return new \WP_Error( 'yka_format', __( 'Berkas ini bukan blueprint YKA.', 'yka-core' ) );
		}

		if ( (int) ( $data['version'] ?? 0 ) > self::VERSION ) {
			return new \WP_Error( 'yka_version', __( 'Blueprint dibuat oleh versi plugin yang lebih baru. Perbarui YKA Core lebih dulu.', 'yka-core' ) );
		}

		return $data;
	}

	/**
	 * Applies a validated blueprint. Creates or updates; never deletes.
	 *
	 * @param array<string, mixed> $data Blueprint.
	 * @return array<string, mixed> Report.
	 */
	public static function apply( array $data ): array {
		$source = isset( $data['source'] ) ? esc_url_raw( (string) $data['source'] ) : '';
		$log    = array();

		$log['settings']   = self::apply_settings( (array) ( $data['settings'] ?? array() ) );
		$log['units']      = self::apply_units( (array) ( $data['units'] ?? array() ) );
		$log['categories'] = self::apply_categories( (array) ( $data['categories'] ?? array() ) );
		$log['pages']      = self::apply_pages( (array) ( $data['pages'] ?? array() ), $source );
		$log['options']    = self::apply_options( (array) ( $data['options'] ?? array() ) );
		$log['menus']      = self::apply_menus( (array) ( $data['menus'] ?? array() ) );

		flush_rewrite_rules( false );

		return $log;
	}

	/**
	 * Merges institution settings over the stored ones.
	 *
	 * @param array<string, mixed> $incoming Settings.
	 * @return array<string, int>
	 */
	private static function apply_settings( array $incoming ): array {
		if ( array() === $incoming ) {
			return array( 'diperbarui' => 0 );
		}

		$stored  = (array) get_option( Settings::OPTION, array() );
		$allowed = array();

		foreach ( Settings::schema() as $section ) {
			foreach ( array_keys( (array) ( $section['fields'] ?? array() ) ) as $key ) {
				$allowed[] = (string) $key;
			}
		}

		$changed = 0;

		foreach ( $incoming as $key => $value ) {
			$key = (string) $key;

			if ( ! in_array( $key, $allowed, true ) || in_array( $key, self::LOCAL_IDS, true ) || ! is_scalar( $value ) ) {
				continue;
			}

			$stored[ $key ] = sanitize_textarea_field( (string) $value );
			++$changed;
		}

		update_option( Settings::OPTION, $stored );

		return array( 'diperbarui' => $changed );
	}

	/**
	 * Creates or updates education units and their metadata.
	 *
	 * @param array<int, mixed> $units Units.
	 * @return array<string, int>
	 */
	private static function apply_units( array $units ): array {
		$created = 0;
		$updated = 0;

		// Two passes so a child never looks for a parent that is not there yet.
		foreach ( array( 'roots', 'children' ) as $pass ) {
			foreach ( $units as $unit ) {
				if ( ! is_array( $unit ) ) {
					continue;
				}

				$parent_slug = sanitize_title( (string) ( $unit['parent'] ?? '' ) );

				if ( ( 'roots' === $pass ) === ( '' !== $parent_slug ) ) {
					continue;
				}

				$slug = sanitize_title( (string) ( $unit['slug'] ?? '' ) );
				$name = sanitize_text_field( (string) ( $unit['name'] ?? '' ) );

				if ( '' === $slug || '' === $name ) {
					continue;
				}

				$parent_id = 0;
				if ( '' !== $parent_slug ) {
					$parent    = get_term_by( 'slug', $parent_slug, Taxonomy::TAXONOMY );
					$parent_id = $parent instanceof \WP_Term ? (int) $parent->term_id : 0;
				}

				$args = array(
					'description' => sanitize_textarea_field( (string) ( $unit['description'] ?? '' ) ),
					'parent'      => $parent_id,
				);

				$term = get_term_by( 'slug', $slug, Taxonomy::TAXONOMY );

				if ( $term instanceof \WP_Term ) {
					wp_update_term( (int) $term->term_id, Taxonomy::TAXONOMY, $args + array( 'name' => $name ) );
					$term_id = (int) $term->term_id;
					++$updated;
				} else {
					$result = wp_insert_term( $name, Taxonomy::TAXONOMY, $args + array( 'slug' => $slug ) );
					if ( is_wp_error( $result ) ) {
						continue;
					}
					$term_id = (int) $result['term_id'];
					++$created;
				}

				self::apply_unit_meta( $term_id, (array) ( $unit['meta'] ?? array() ) );
			}
		}

		return array(
			'dibuat'     => $created,
			'diperbarui' => $updated,
		);
	}

	/**
	 * Writes unit metadata through the same sanitisation the editor uses.
	 *
	 * @param int                  $term_id Term.
	 * @param array<string, mixed> $meta    Incoming metadata.
	 * @return void
	 */
	private static function apply_unit_meta( int $term_id, array $meta ): void {
		$fields = Unit_Meta::fields();

		foreach ( $meta as $key => $value ) {
			$key = (string) $key;

			if ( ! isset( $fields[ $key ] ) || in_array( $key, self::LOCAL_UNIT_IDS, true ) || ! is_scalar( $value ) ) {
				continue;
			}

			update_term_meta( $term_id, $key, sanitize_textarea_field( (string) $value ) );
		}
	}

	/**
	 * Creates or updates post categories.
	 *
	 * @param array<int, mixed> $categories Categories.
	 * @return array<string, int>
	 */
	private static function apply_categories( array $categories ): array {
		$created = 0;
		$updated = 0;

		foreach ( array( 'roots', 'children' ) as $pass ) {
			foreach ( $categories as $category ) {
				if ( ! is_array( $category ) ) {
					continue;
				}

				$parent_slug = sanitize_title( (string) ( $category['parent'] ?? '' ) );

				if ( ( 'roots' === $pass ) === ( '' !== $parent_slug ) ) {
					continue;
				}

				$slug = sanitize_title( (string) ( $category['slug'] ?? '' ) );
				$name = sanitize_text_field( (string) ( $category['name'] ?? '' ) );

				if ( '' === $slug || '' === $name ) {
					continue;
				}

				$parent_id = 0;
				if ( '' !== $parent_slug ) {
					$parent    = get_term_by( 'slug', $parent_slug, 'category' );
					$parent_id = $parent instanceof \WP_Term ? (int) $parent->term_id : 0;
				}

				$args = array(
					'description' => sanitize_textarea_field( (string) ( $category['description'] ?? '' ) ),
					'parent'      => $parent_id,
				);

				$term = get_term_by( 'slug', $slug, 'category' );

				if ( $term instanceof \WP_Term ) {
					wp_update_term( (int) $term->term_id, 'category', $args + array( 'name' => $name ) );
					++$updated;
					continue;
				}

				$result = wp_insert_term( $name, 'category', $args + array( 'slug' => $slug ) );
				if ( ! is_wp_error( $result ) ) {
					++$created;
				}
			}
		}

		return array(
			'dibuat'     => $created,
			'diperbarui' => $updated,
		);
	}

	/**
	 * Creates or updates pages, rewriting any address left over from the
	 * source site.
	 *
	 * @param array<int, mixed> $pages  Pages.
	 * @param string            $source Source site address.
	 * @return array<string, int>
	 */
	private static function apply_pages( array $pages, string $source ): array {
		$created = 0;
		$updated = 0;

		foreach ( array( 'roots', 'children' ) as $pass ) {
			foreach ( $pages as $page ) {
				if ( ! is_array( $page ) ) {
					continue;
				}

				$parent_slug = sanitize_title( (string) ( $page['parent'] ?? '' ) );

				if ( ( 'roots' === $pass ) === ( '' !== $parent_slug ) ) {
					continue;
				}

				$slug  = sanitize_title( (string) ( $page['slug'] ?? '' ) );
				$title = sanitize_text_field( (string) ( $page['title'] ?? '' ) );

				if ( '' === $slug || '' === $title ) {
					continue;
				}

				$content = (string) ( $page['content'] ?? '' );
				if ( '' !== $source ) {
					$content = str_replace( untrailingslashit( $source ), untrailingslashit( home_url() ), $content );
				}

				$existing = self::post_by_slug( $slug, 'page' );
				$parent   = '' !== $parent_slug ? self::post_by_slug( $parent_slug, 'page' ) : null;

				$args = array(
					'post_title'   => $title,
					'post_name'    => $slug,
					'post_type'    => 'page',
					'post_status'  => in_array( (string) ( $page['status'] ?? '' ), array( 'publish', 'draft', 'private' ), true ) ? (string) $page['status'] : 'publish',
					'post_content' => wp_kses_post( $content ),
					'post_excerpt' => sanitize_textarea_field( (string) ( $page['excerpt'] ?? '' ) ),
					'menu_order'   => (int) ( $page['order'] ?? 0 ),
					'post_parent'  => $parent instanceof \WP_Post ? (int) $parent->ID : 0,
				);

				if ( $existing instanceof \WP_Post ) {
					$args['ID'] = (int) $existing->ID;
					$page_id    = wp_update_post( $args, true );
					++$updated;
				} else {
					$page_id = wp_insert_post( $args, true );
					++$created;
				}

				if ( is_wp_error( $page_id ) || 0 === (int) $page_id ) {
					continue;
				}

				$template = (string) ( $page['template'] ?? '' );
				if ( '' !== $template && preg_match( '#^[A-Za-z0-9_\-/]+\.php$#', $template ) ) {
					update_post_meta( (int) $page_id, '_wp_page_template', $template );
				}
			}
		}

		return array(
			'dibuat'     => $created,
			'diperbarui' => $updated,
		);
	}

	/**
	 * Applies whitelisted core options, resolving page slugs back to IDs.
	 *
	 * @param array<string, mixed> $options Options.
	 * @return array<string, int>
	 */
	private static function apply_options( array $options ): array {
		$changed = 0;

		foreach ( self::OPTIONS as $name ) {
			if ( ! isset( $options[ $name ] ) || ! is_scalar( $options[ $name ] ) ) {
				continue;
			}

			update_option( $name, sanitize_text_field( (string) $options[ $name ] ) );
			++$changed;
		}

		foreach ( array(
			'page_on_front'  => 'page_on_front_slug',
			'page_for_posts' => 'page_for_posts_slug',
		) as $option => $key ) {
			$slug = sanitize_title( (string) ( $options[ $key ] ?? '' ) );
			if ( '' === $slug ) {
				continue;
			}

			$page = self::post_by_slug( $slug, 'page' );
			if ( $page instanceof \WP_Post ) {
				update_option( $option, (int) $page->ID );
				++$changed;
			}
		}

		$front = (string) ( $options['show_on_front'] ?? '' );
		if ( in_array( $front, array( 'posts', 'page' ), true ) ) {
			update_option( 'show_on_front', $front );
			++$changed;
		}

		return array( 'diperbarui' => $changed );
	}

	/**
	 * Rebuilds navigation menus and reassigns their theme locations.
	 *
	 * @param array<int, mixed> $menus Menus.
	 * @return array<string, int>
	 */
	private static function apply_menus( array $menus ): array {
		$created   = 0;
		$updated   = 0;
		$locations = (array) get_nav_menu_locations();

		foreach ( $menus as $menu ) {
			if ( ! is_array( $menu ) ) {
				continue;
			}

			$name = sanitize_text_field( (string) ( $menu['name'] ?? '' ) );
			if ( '' === $name ) {
				continue;
			}

			$object = wp_get_nav_menu_object( $name );

			if ( $object instanceof \WP_Term ) {
				$menu_id = (int) $object->term_id;
				++$updated;
			} else {
				$menu_id = wp_create_nav_menu( $name );
				if ( is_wp_error( $menu_id ) ) {
					continue;
				}
				$menu_id = (int) $menu_id;
				++$created;
			}

			self::rebuild_menu_items( $menu_id, (array) ( $menu['items'] ?? array() ) );

			foreach ( (array) ( $menu['locations'] ?? array() ) as $location ) {
				$location = sanitize_key( (string) $location );
				if ( '' !== $location ) {
					$locations[ $location ] = $menu_id;
				}
			}
		}

		set_theme_mod( 'nav_menu_locations', $locations );

		return array(
			'dibuat'     => $created,
			'diperbarui' => $updated,
		);
	}

	/**
	 * Replaces a menu's items with the ones described by the blueprint.
	 *
	 * Menu items are positional by nature, so this is the one place that
	 * clears before writing. Only the items of a menu named in the file are
	 * touched; menus absent from the file are left alone.
	 *
	 * @param int               $menu_id Menu.
	 * @param array<int, mixed> $items   Items.
	 * @return void
	 */
	private static function rebuild_menu_items( int $menu_id, array $items ): void {
		foreach ( (array) wp_get_nav_menu_items( $menu_id ) as $existing ) {
			wp_delete_post( (int) $existing->ID, true );
		}

		$made = array();

		foreach ( array( 'roots', 'children' ) as $pass ) {
			foreach ( $items as $item ) {
				if ( ! is_array( $item ) ) {
					continue;
				}

				$parent_key = (string) ( $item['parent_key'] ?? '' );

				if ( ( 'roots' === $pass ) === ( '' !== $parent_key ) ) {
					continue;
				}

				$type   = (string) ( $item['type'] ?? '' );
				$object = sanitize_key( (string) ( $item['object'] ?? '' ) );
				$slug   = sanitize_title( (string) ( $item['object_slug'] ?? '' ) );

				$args = array(
					'menu-item-title'     => sanitize_text_field( (string) ( $item['title'] ?? '' ) ),
					'menu-item-position'  => (int) ( $item['order'] ?? 0 ),
					'menu-item-status'    => 'publish',
					'menu-item-parent-id' => $made[ $parent_key ] ?? 0,
				);

				if ( 'taxonomy' === $type ) {
					$term = get_term_by( 'slug', $slug, $object );
					if ( ! $term instanceof \WP_Term ) {
						continue;
					}
					$args['menu-item-type']      = 'taxonomy';
					$args['menu-item-object']    = $object;
					$args['menu-item-object-id'] = (int) $term->term_id;
				} elseif ( 'post_type' === $type ) {
					$post = self::post_by_slug( $slug, $object );
					if ( ! $post instanceof \WP_Post ) {
						continue;
					}
					$args['menu-item-type']      = 'post_type';
					$args['menu-item-object']    = $object;
					$args['menu-item-object-id'] = (int) $post->ID;
				} else {
					$url = esc_url_raw( (string) ( $item['url'] ?? '' ) );
					if ( '' === $url ) {
						continue;
					}
					$args['menu-item-type'] = 'custom';
					$args['menu-item-url']  = $url;
				}

				$new_id = wp_update_nav_menu_item( $menu_id, 0, $args );

				if ( ! is_wp_error( $new_id ) ) {
					$made[ (string) ( $item['key'] ?? '' ) ] = (int) $new_id;
				}
			}
		}
	}

	/*
	==============================================================
		Screen
		============================================================== */

	/**
	 * Renders the export and import screen.
	 *
	 * @return void
	 */
	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$report = get_transient( self::TRANSIENT );
		delete_transient( self::TRANSIENT );
		?>
<div class="wrap yka-blueprint">
	<h1><?php esc_html_e( 'Ekspor & Impor', 'yka-core' ); ?></h1>

		<?php if ( is_array( $report ) ) : ?>
			<?php self::render_report( $report ); ?>
		<?php endif; ?>

	<p class="yka-blueprint__intro">
		<?php esc_html_e( 'Blueprint adalah satu berkas JSON berisi seluruh susunan situs: pengaturan lembaga, unit pendidikan beserta metadatanya, kategori, kerangka halaman, dan menu navigasi. Terapkan pada situs WordPress kosong dan situs itu langsung berbentuk portal Yayasan Kasih Ananda.', 'yka-core' ); ?>
	</p>

	<div class="yka-blueprint__grid">
		<div class="card yka-blueprint__card">
			<h2><?php esc_html_e( 'Ekspor', 'yka-core' ); ?></h2>
			<p><?php esc_html_e( 'Mengunduh susunan situs ini sebagai satu berkas JSON. Berkasnya kecil, dapat dibaca manusia, dan aman disimpan di dalam repositori.', 'yka-core' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="yka_blueprint_export">
				<?php wp_nonce_field( self::NONCE ); ?>
				<?php submit_button( __( 'Unduh blueprint', 'yka-core' ), 'primary', 'submit', false ); ?>
			</form>
		</div>

		<div class="card yka-blueprint__card">
			<h2><?php esc_html_e( 'Impor', 'yka-core' ); ?></h2>
			<p><?php esc_html_e( 'Menerapkan blueprint ke situs ini. Hanya menambah dan memperbarui, dicocokkan berdasarkan slug — tidak ada yang dihapus, kecuali isi menu yang namanya disebut di dalam berkas.', 'yka-core' ); ?></p>
			<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="yka_blueprint_import">
				<?php wp_nonce_field( self::NONCE ); ?>
				<p><input type="file" name="yka_blueprint" accept="application/json,.json" required></p>
				<?php submit_button( __( 'Terapkan blueprint', 'yka-core' ), 'secondary', 'submit', false ); ?>
			</form>
		</div>
	</div>

	<h2><?php esc_html_e( 'Yang tidak dibawa berkas ini', 'yka-core' ); ?></h2>
	<p class="yka-blueprint__limits">
		<?php esc_html_e( 'Blueprint tidak memuat gambar, artikel, berkas tema, maupun plugin. Nomor lampiran media hanya berlaku pada satu basis data, jadi membawanya justru menghasilkan tautan yang putus. Untuk memindahkan situs secara utuh — media, tema, plugin, dan seluruh isinya — gunakan WPvivid; langkahnya ada di docs/MIGRATION.md.', 'yka-core' ); ?>
	</p>
</div>
		<?php
	}

	/**
	 * Prints the outcome of the last import.
	 *
	 * @param array<string, mixed> $report Report.
	 * @return void
	 */
	private static function render_report( array $report ): void {
		if ( isset( $report['error'] ) ) {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html( (string) $report['error'] )
			);
			return;
		}

		$labels = array(
			'settings'   => __( 'Pengaturan lembaga', 'yka-core' ),
			'units'      => __( 'Unit pendidikan', 'yka-core' ),
			'categories' => __( 'Kategori', 'yka-core' ),
			'pages'      => __( 'Halaman', 'yka-core' ),
			'options'    => __( 'Pengaturan WordPress', 'yka-core' ),
			'menus'      => __( 'Menu navigasi', 'yka-core' ),
		);

		echo '<div class="notice notice-success"><p><strong>' . esc_html__( 'Blueprint diterapkan.', 'yka-core' ) . '</strong></p><ul>';

		foreach ( $labels as $key => $label ) {
			if ( ! isset( $report[ $key ] ) || ! is_array( $report[ $key ] ) ) {
				continue;
			}

			$parts = array();
			foreach ( $report[ $key ] as $what => $count ) {
				$parts[] = sprintf( '%s %d', (string) $what, (int) $count );
			}

			printf( '<li>%s: %s</li>', esc_html( $label ), esc_html( implode( ', ', $parts ) ) );
		}

		echo '</ul></div>';
	}

	/*
	==============================================================
		Small helpers
		============================================================== */

	/**
	 * Stores the import outcome for one page load.
	 *
	 * @param array<string, mixed> $report Report.
	 * @return void
	 */
	private static function report( array $report ): void {
		set_transient( self::TRANSIENT, $report, 60 );
	}

	/**
	 * Returns to the screen after handling a form.
	 *
	 * @return void
	 */
	private static function redirect_back(): void {
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::MENU_SLUG ) );
		exit;
	}

	/**
	 * Finds a post by its slug alone.
	 *
	 * `get_page_by_path()` cannot do this: it resolves a whole path, so a
	 * child page never matches on its own slug and every import would create
	 * a second copy of it.
	 *
	 * @param string $slug Post slug.
	 * @param string $type Post type.
	 * @return \WP_Post|null
	 */
	private static function post_by_slug( string $slug, string $type ): ?\WP_Post {
		if ( '' === $slug || '' === $type ) {
			return null;
		}

		$found = get_posts(
			array(
				'post_type'        => $type,
				'post_status'      => array( 'publish', 'draft', 'private', 'pending', 'future' ),
				'post_name__in'    => array( $slug ),
				'numberposts'      => 1,
				'orderby'          => 'ID',
				'order'            => 'ASC',
				'suppress_filters' => false,
			)
		);

		return $found ? $found[0] : null;
	}

	/**
	 * Slug of a term, or an empty string when there is no parent.
	 *
	 * @param int    $term_id  Term.
	 * @param string $taxonomy Taxonomy.
	 * @return string
	 */
	private static function term_slug( int $term_id, string $taxonomy = Taxonomy::TAXONOMY ): string {
		if ( $term_id <= 0 ) {
			return '';
		}

		$term = get_term( $term_id, $taxonomy );

		return $term instanceof \WP_Term ? $term->slug : '';
	}

	/**
	 * Slug of whatever a menu item points at.
	 *
	 * @param \WP_Post $item Menu item.
	 * @return string
	 */
	private static function menu_object_slug( $item ): string {
		if ( 'taxonomy' === $item->type ) {
			$term = get_term( (int) $item->object_id, (string) $item->object );
			return $term instanceof \WP_Term ? $term->slug : '';
		}

		if ( 'post_type' === $item->type ) {
			return (string) get_post_field( 'post_name', (int) $item->object_id );
		}

		return '';
	}

	/**
	 * A stable identifier for a menu item, used to rebuild the tree without
	 * relying on database IDs.
	 *
	 * @param \WP_Post $item Menu item.
	 * @return string
	 */
	private static function menu_item_key( $item ): string {
		return $item->type . ':' . $item->object . ':' . ( self::menu_object_slug( $item ) ?: (string) $item->ID );
	}
}
