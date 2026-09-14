<?php
/**
 * Education unit taxonomy.
 *
 * @package YKA\Core
 */

declare( strict_types=1 );

namespace YKA\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Registers and maintains the `yka_unit` taxonomy.
 *
 * One article belongs to one or more education units. This is the spine of
 * the whole portal: unit landing pages, homepage sections, related posts
 * and schema all read from it.
 */
final class Taxonomy {

	public const TAXONOMY = 'yka_unit';

	/**
	 * Canonical slugs for the four units the foundation operates.
	 *
	 * Kept as constants so templates never hard-code strings.
	 */
	public const UNIT_YAYASAN = 'yayasan-kasih-ananda';
	public const UNIT_SD      = 'sd-kasih-ananda-1';
	public const UNIT_SMP     = 'smp-kasih-ananda-1';
	public const UNIT_SMK     = 'smk-kasih-ananda';

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'register' ), 5 );
		add_action( 'admin_init', array( __CLASS__, 'maybe_ensure_default_terms' ) );
		add_filter( 'term_link', array( __CLASS__, 'filter_term_link' ), 10, 3 );
	}

	/**
	 * Registers the taxonomy.
	 *
	 * @return void
	 */
	public static function register(): void {
		$labels = array(
			'name'              => _x( 'Unit Pendidikan', 'taxonomy general name', 'yka-core' ),
			'singular_name'     => _x( 'Unit Pendidikan', 'taxonomy singular name', 'yka-core' ),
			'search_items'      => __( 'Cari Unit Pendidikan', 'yka-core' ),
			'all_items'         => __( 'Semua Unit', 'yka-core' ),
			'parent_item'       => __( 'Unit Induk', 'yka-core' ),
			'parent_item_colon' => __( 'Unit Induk:', 'yka-core' ),
			'edit_item'         => __( 'Ubah Unit', 'yka-core' ),
			'update_item'       => __( 'Perbarui Unit', 'yka-core' ),
			'add_new_item'      => __( 'Tambah Unit Baru', 'yka-core' ),
			'new_item_name'     => __( 'Nama Unit Baru', 'yka-core' ),
			'menu_name'         => __( 'Unit Pendidikan', 'yka-core' ),
			'not_found'         => __( 'Belum ada unit pendidikan.', 'yka-core' ),
			'back_to_items'     => __( '← Kembali ke Unit Pendidikan', 'yka-core' ),
		);

		register_taxonomy(
			self::TAXONOMY,
			array( 'post', 'page', 'attachment' ),
			array(
				'labels'             => $labels,
				'description'        => __( 'Unit pendidikan di bawah Yayasan Kasih Ananda.', 'yka-core' ),
				'hierarchical'       => true,
				'public'             => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'show_in_nav_menus'  => true,
				'show_in_rest'       => true,
				'show_tagcloud'      => false,
				'show_admin_column'  => false,
				'rest_base'          => 'unit',
				'query_var'          => 'unit',
				'rewrite'            => array(
					'slug'         => 'unit',
					'with_front'   => false,
					'hierarchical' => false,
				),
				'capabilities'       => array(
					'manage_terms' => 'manage_categories',
					'edit_terms'   => 'manage_categories',
					'delete_terms' => 'manage_categories',
					'assign_terms' => 'edit_posts',
				),
			)
		);
	}

	/**
	 * The four units, in display order, with their default labels.
	 *
	 * Only names the organisation actually uses. No invented data.
	 *
	 * @return array<string, array{name: string, short: string, order: int, legacy: string}>
	 */
	public static function default_terms(): array {
		return array(
			self::UNIT_YAYASAN => array(
				'name'   => 'Yayasan Kasih Ananda',
				'short'  => 'Yayasan',
				'order'  => 10,
				'legacy' => '',
			),
			self::UNIT_SD      => array(
				'name'   => 'SD Kasih Ananda I',
				'short'  => 'SD',
				'order'  => 20,
				'legacy' => '',
			),
			self::UNIT_SMP     => array(
				'name'   => 'SMP Kasih Ananda I',
				'short'  => 'SMP',
				'order'  => 30,
				'legacy' => 'https://smpkasihananda1.sch.id/',
			),
			self::UNIT_SMK     => array(
				'name'   => 'SMK Kasih Ananda',
				'short'  => 'SMK',
				'order'  => 40,
				'legacy' => 'https://smkkasihananda.sch.id/',
			),
		);
	}

	/**
	 * Creates any missing default term. Never modifies existing terms, so an
	 * administrator can freely rename or re-describe a unit.
	 *
	 * @return void
	 */
	public static function ensure_default_terms(): void {
		foreach ( self::default_terms() as $slug => $data ) {
			$term = get_term_by( 'slug', $slug, self::TAXONOMY );
			if ( $term instanceof \WP_Term ) {
				continue;
			}

			$created = wp_insert_term( $data['name'], self::TAXONOMY, array( 'slug' => $slug ) );
			if ( is_wp_error( $created ) ) {
				continue;
			}

			$term_id = (int) $created['term_id'];
			update_term_meta( $term_id, 'yka_unit_official_name', $data['name'] );
			update_term_meta( $term_id, 'yka_unit_short_name', $data['short'] );
			update_term_meta( $term_id, 'yka_unit_order', $data['order'] );
			if ( '' !== $data['legacy'] ) {
				update_term_meta( $term_id, 'yka_unit_legacy_url', $data['legacy'] );
			}
		}

		update_option( 'yka_core_default_terms_version', VERSION, false );
	}

	/**
	 * Runs the term check once per plugin version instead of on every request.
	 *
	 * @return void
	 */
	public static function maybe_ensure_default_terms(): void {
		if ( get_option( 'yka_core_default_terms_version' ) === VERSION ) {
			return;
		}
		self::ensure_default_terms();
	}

	/**
	 * Keeps unit URLs flat (`/unit/smp-kasih-ananda-1/`) even though the
	 * taxonomy is hierarchical.
	 *
	 * @param string   $link     Term link.
	 * @param \WP_Term $term     Term object.
	 * @param string   $taxonomy Taxonomy name.
	 * @return string
	 */
	public static function filter_term_link( string $link, $term, string $taxonomy ): string {
		if ( self::TAXONOMY !== $taxonomy || ! $term instanceof \WP_Term ) {
			return $link;
		}
		return $link;
	}

	/**
	 * All unit terms ordered by their configured display order.
	 *
	 * @param bool $hide_empty Whether to skip units with no posts.
	 * @return \WP_Term[]
	 */
	public static function get_units( bool $hide_empty = false ): array {
		$terms = get_terms(
			array(
				'taxonomy'   => self::TAXONOMY,
				'hide_empty' => $hide_empty,
				'meta_key'   => 'yka_unit_order', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'orderby'    => 'meta_value_num',
				'order'      => 'ASC',
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			$terms = get_terms(
				array(
					'taxonomy'   => self::TAXONOMY,
					'hide_empty' => $hide_empty,
					'orderby'    => 'name',
				)
			);
		}

		return is_wp_error( $terms ) ? array() : $terms;
	}

	/**
	 * The school units only — the foundation itself is excluded.
	 *
	 * @return \WP_Term[]
	 */
	public static function get_school_units(): array {
		return array_values(
			array_filter(
				self::get_units(),
				static fn( \WP_Term $term ): bool => self::UNIT_YAYASAN !== $term->slug
			)
		);
	}

	/**
	 * The primary unit assigned to a post.
	 *
	 * @param int|\WP_Post|null $post Post.
	 * @return \WP_Term|null
	 */
	public static function get_post_unit( $post = null ): ?\WP_Term {
		$post = get_post( $post );
		if ( ! $post instanceof \WP_Post ) {
			return null;
		}

		$terms = get_the_terms( $post, self::TAXONOMY );
		if ( ! is_array( $terms ) || empty( $terms ) ) {
			return null;
		}

		// A post may legitimately be tagged with several units (a joint
		// activity). The one with the lowest display order leads.
		usort(
			$terms,
			static function ( \WP_Term $a, \WP_Term $b ): int {
				$oa = (int) get_term_meta( $a->term_id, 'yka_unit_order', true );
				$ob = (int) get_term_meta( $b->term_id, 'yka_unit_order', true );
				return $oa <=> $ob;
			}
		);

		return $terms[0];
	}
}
