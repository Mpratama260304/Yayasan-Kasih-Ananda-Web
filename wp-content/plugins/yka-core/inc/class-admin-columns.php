<?php
/**
 * Post list table columns and filters.
 *
 * @package YKA\Core
 */

declare( strict_types=1 );

namespace YKA\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Rebuilds the article list so a documentation team can see at a glance
 * which unit a story belongs to, whether it has a photograph, and when the
 * activity actually happened.
 */
final class Admin_Columns {

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_filter( 'manage_post_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_post_posts_custom_column', array( __CLASS__, 'render_column' ), 10, 2 );
		add_filter( 'manage_edit-post_sortable_columns', array( __CLASS__, 'sortable_columns' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'handle_sorting' ) );
		add_action( 'restrict_manage_posts', array( __CLASS__, 'render_filters' ) );
		add_action( 'admin_head', array( __CLASS__, 'column_widths' ) );
	}

	/**
	 * Defines the column order.
	 *
	 * @param array<string, string> $columns Default columns.
	 * @return array<string, string>
	 */
	public static function columns( $columns ): array {
		$columns = is_array( $columns ) ? $columns : array();

		return array(
			'cb'                => $columns['cb'] ?? '',
			'yka_thumb'         => __( 'Foto', 'yka-core' ),
			'title'             => __( 'Judul', 'yka-core' ),
			'yka_unit'          => __( 'Unit', 'yka-core' ),
			'categories'        => __( 'Kategori', 'yka-core' ),
			'author'            => __( 'Penulis', 'yka-core' ),
			'yka_activity_date' => __( 'Tgl. kegiatan', 'yka-core' ),
			'yka_state'         => __( 'Status', 'yka-core' ),
			'date'              => __( 'Terbit', 'yka-core' ),
		);
	}

	/**
	 * Renders a custom column.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post id.
	 * @return void
	 */
	public static function render_column( $column, $post_id ): void {
		$post_id = (int) $post_id;

		switch ( $column ) {
			case 'yka_thumb':
				if ( has_post_thumbnail( $post_id ) ) {
					echo '<a href="' . esc_url( (string) get_edit_post_link( $post_id ) ) . '" class="yka-col-thumb">';
					echo wp_kses_post( get_the_post_thumbnail( $post_id, array( 80, 54 ), array( 'loading' => 'lazy' ) ) );
					echo '</a>';
				} else {
					printf(
						'<span class="yka-col-thumb yka-col-thumb--empty" title="%s" aria-hidden="true"></span><span class="screen-reader-text">%s</span>',
						esc_attr__( 'Belum ada gambar utama', 'yka-core' ),
						esc_html__( 'Belum ada gambar utama', 'yka-core' )
					);
				}
				break;

			case 'yka_unit':
				$terms = get_the_terms( $post_id, Taxonomy::TAXONOMY );
				if ( ! is_array( $terms ) || empty( $terms ) ) {
					printf( '<span class="yka-col-missing">%s</span>', esc_html__( 'Belum dipilih', 'yka-core' ) );
					break;
				}
				$links = array();
				foreach ( $terms as $term ) {
					$links[] = sprintf(
						'<a href="%s">%s</a>',
						esc_url(
							add_query_arg(
								array(
									'post_type'        => 'post',
									Taxonomy::TAXONOMY => $term->slug,
								),
								admin_url( 'edit.php' )
							)
						),
						esc_html( $term->name )
					);
				}
				echo wp_kses_post( implode( ', ', $links ) );
				break;

			case 'yka_activity_date':
				$display = Post_Meta::activity_date_display( $post_id );
				echo '' !== $display
					? esc_html( $display )
					: '<span class="yka-col-muted">—</span>';
				break;

			case 'yka_state':
				echo wp_kses_post( self::render_state( $post_id ) );
				break;
		}
	}

	/**
	 * Publication readiness summary for one article.
	 *
	 * @param int $post_id Post id.
	 * @return string
	 */
	private static function render_state( int $post_id ): string {
		$status        = get_post_status( $post_id );
		$issues        = Checklist::evaluate( $post_id );
		$blocker_count = count( array_filter( $issues, static fn( array $i ): bool => 'error' === $i['level'] ) );

		$labels = array(
			'publish' => __( 'Terbit', 'yka-core' ),
			'future'  => __( 'Terjadwal', 'yka-core' ),
			'draft'   => __( 'Draf', 'yka-core' ),
			'pending' => __( 'Menunggu tinjauan', 'yka-core' ),
			'private' => __( 'Pribadi', 'yka-core' ),
		);

		$out = sprintf(
			'<span class="yka-state yka-state--%s">%s</span>',
			esc_attr( (string) $status ),
			esc_html( $labels[ $status ] ?? (string) $status )
		);

		if ( $issues ) {
			$out .= sprintf(
				'<br /><span class="yka-state-issues" title="%s">%s</span>',
				esc_attr( implode( ' · ', wp_list_pluck( $issues, 'message' ) ) ),
				esc_html(
					sprintf(
						/* translators: %d: number of checklist warnings. */
						_n( '%d catatan', '%d catatan', count( $issues ), 'yka-core' ),
						count( $issues )
					)
				)
			);
		}

		unset( $blocker_count );
		return $out;
	}

	/**
	 * Marks the activity date column sortable.
	 *
	 * @param array<string, string> $columns Sortable columns.
	 * @return array<string, string>
	 */
	public static function sortable_columns( $columns ): array {
		$columns                      = is_array( $columns ) ? $columns : array();
		$columns['yka_activity_date'] = 'yka_activity_date';
		return $columns;
	}

	/**
	 * Applies activity-date sorting.
	 *
	 * @param \WP_Query $query Current query.
	 * @return void
	 */
	public static function handle_sorting( $query ): void {
		if ( ! is_admin() || ! $query instanceof \WP_Query || ! $query->is_main_query() ) {
			return;
		}
		if ( 'yka_activity_date' !== $query->get( 'orderby' ) ) {
			return;
		}

		$query->set( 'meta_key', Post_Meta::ACTIVITY_DATE );
		$query->set( 'orderby', 'meta_value' );
	}

	/**
	 * Renders the unit and activity-year filter dropdowns.
	 *
	 * @param string $post_type Current post type.
	 * @return void
	 */
	public static function render_filters( $post_type ): void {
		if ( 'post' !== $post_type ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- list table filter, read-only.
		$selected = isset( $_GET[ Taxonomy::TAXONOMY ] ) ? sanitize_title( wp_unslash( (string) $_GET[ Taxonomy::TAXONOMY ] ) ) : '';

		$units = Taxonomy::get_units();
		if ( ! $units ) {
			return;
		}

		printf(
			'<label class="screen-reader-text" for="%1$s">%2$s</label><select name="%1$s" id="%1$s"><option value="">%3$s</option>',
			esc_attr( Taxonomy::TAXONOMY ),
			esc_html__( 'Saring menurut unit', 'yka-core' ),
			esc_html__( 'Semua unit', 'yka-core' )
		);

		foreach ( $units as $unit ) {
			printf(
				'<option value="%s"%s>%s (%d)</option>',
				esc_attr( $unit->slug ),
				selected( $selected, $unit->slug, false ),
				esc_html( $unit->name ),
				(int) $unit->count
			);
		}
		echo '</select>';
	}

	/**
	 * Keeps the thumbnail column from stretching the table.
	 *
	 * @return void
	 */
	public static function column_widths(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'edit-post' !== $screen->id ) {
			return;
		}
		?>
<style id="yka-admin-columns-css">
.column-yka_thumb{width:96px}
.column-yka_unit,.column-yka_activity_date,.column-yka_state{width:11%}
</style>
		<?php
	}
}
