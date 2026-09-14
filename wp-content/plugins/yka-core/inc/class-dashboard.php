<?php
/**
 * Editorial dashboard widget.
 *
 * @package YKA\Core
 */

declare( strict_types=1 );

namespace YKA\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Replaces the generic WordPress dashboard clutter with the two things a
 * documentation team actually needs: a way to start writing, and a view of
 * what is in the pipeline per education unit.
 */
final class Dashboard {

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'wp_dashboard_setup', array( __CLASS__, 'register_widgets' ), 20 );
		// Runs after every other plugin has registered its widget, otherwise
		// the newsroom panel ends up below them.
		add_action( 'wp_dashboard_setup', array( __CLASS__, 'promote_widget' ), 999 );
		add_action( 'load-index.php', array( __CLASS__, 'hide_welcome_panel' ) );
	}

	/**
	 * Removes the WordPress welcome panel.
	 *
	 * It advertises block themes and the Customizer to people whose job is to
	 * publish school news. The newsroom widget replaces it.
	 *
	 * @return void
	 */
	public static function hide_welcome_panel(): void {
		remove_action( 'welcome_panel', 'wp_welcome_panel' );
	}

	/**
	 * Registers the widget and removes the noisy defaults.
	 *
	 * @return void
	 */
	public static function register_widgets(): void {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		wp_add_dashboard_widget(
			'yka_newsroom',
			__( 'Ruang Redaksi Kasih Ananda', 'yka-core' ),
			array( __CLASS__, 'render' )
		);

		foreach ( array( 'dashboard_primary', 'dashboard_quick_press', 'dashboard_incoming_links', 'dashboard_plugins' ) as $widget_id ) {
			remove_meta_box( $widget_id, 'dashboard', 'normal' );
			remove_meta_box( $widget_id, 'dashboard', 'side' );
		}
	}

	/**
	 * Moves the newsroom widget to the top of the main column.
	 *
	 * WordPress exposes no API for ordering dashboard widgets, so the meta
	 * box global is rewritten directly.
	 *
	 * @return void
	 */
	public static function promote_widget(): void {
		global $wp_meta_boxes;

		if ( ! isset( $wp_meta_boxes['dashboard']['normal']['core']['yka_newsroom'] ) ) {
			return;
		}

		$widget = $wp_meta_boxes['dashboard']['normal']['core']['yka_newsroom'];
		unset( $wp_meta_boxes['dashboard']['normal']['core']['yka_newsroom'] );

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- documented above.
		$wp_meta_boxes['dashboard']['normal']['core'] = array_merge(
			array( 'yka_newsroom' => $widget ),
			$wp_meta_boxes['dashboard']['normal']['core']
		);
	}

	/**
	 * Renders the widget.
	 *
	 * @return void
	 */
	public static function render(): void {
		$counts  = wp_count_posts( 'post' );
		$new_url = admin_url( 'post-new.php' );

		echo '<div class="yka-newsroom">';

		printf(
			'<p class="yka-newsroom__cta"><a href="%s" class="button button-primary button-hero">%s</a></p>',
			esc_url( $new_url ),
			esc_html__( 'Tambah Berita / Dokumentasi', 'yka-core' )
		);

		// --- Pipeline counts -------------------------------------------
		echo '<ul class="yka-newsroom__stats">';
		foreach ( array(
			'publish' => __( 'Terbit', 'yka-core' ),
			'draft'   => __( 'Draf', 'yka-core' ),
			'pending' => __( 'Menunggu tinjauan', 'yka-core' ),
			'future'  => __( 'Terjadwal', 'yka-core' ),
		) as $status => $label ) {
			$count = isset( $counts->$status ) ? (int) $counts->$status : 0;
			printf(
				'<li><a href="%s"><strong>%s</strong><span>%s</span></a></li>',
				esc_url(
					add_query_arg(
						array(
							'post_type'   => 'post',
							'post_status' => $status,
						),
						admin_url( 'edit.php' )
					)
				),
				esc_html( number_format_i18n( $count ) ),
				esc_html( $label )
			);
		}
		echo '</ul>';

		// --- Per-unit quick filters ------------------------------------
		$units = Taxonomy::get_units();
		if ( $units ) {
			printf( '<h3 class="yka-newsroom__heading">%s</h3>', esc_html__( 'Menurut unit pendidikan', 'yka-core' ) );
			echo '<ul class="yka-newsroom__units">';
			foreach ( $units as $unit ) {
				printf(
					'<li><a href="%s"><span class="yka-newsroom__unit-name">%s</span><span class="yka-newsroom__unit-count">%d</span></a></li>',
					esc_url(
						add_query_arg(
							array(
								'post_type'        => 'post',
								Taxonomy::TAXONOMY => $unit->slug,
							),
							admin_url( 'edit.php' )
						)
					),
					esc_html( $unit->name ),
					(int) $unit->count
				);
			}
			echo '</ul>';
		}

		// --- Needs attention -------------------------------------------
		self::render_list(
			__( 'Perlu ditindaklanjuti', 'yka-core' ),
			array(
				'post_status'    => array( 'draft', 'pending' ),
				'posts_per_page' => 5,
			),
			__( 'Tidak ada draf yang menunggu.', 'yka-core' )
		);

		self::render_list(
			__( 'Dijadwalkan terbit', 'yka-core' ),
			array(
				'post_status'    => 'future',
				'posts_per_page' => 5,
				'order'          => 'ASC',
			),
			__( 'Belum ada artikel terjadwal.', 'yka-core' )
		);

		self::render_list(
			__( 'Terbit terakhir', 'yka-core' ),
			array(
				'post_status'    => 'publish',
				'posts_per_page' => 5,
			),
			__( 'Belum ada artikel yang terbit.', 'yka-core' )
		);

		echo '</div>';
	}

	/**
	 * Renders one post list block.
	 *
	 * @param string               $heading       Section heading.
	 * @param array<string, mixed> $args          Query args.
	 * @param string               $empty_message Empty-state message.
	 * @return void
	 */
	private static function render_list( string $heading, array $args, string $empty_message ): void {
		$query = new \WP_Query(
			array_merge(
				array(
					'post_type'              => 'post',
					'ignore_sticky_posts'    => true,
					'no_found_rows'          => true,
					'update_post_term_cache' => true,
					'update_post_meta_cache' => false,
				),
				$args
			)
		);

		printf( '<h3 class="yka-newsroom__heading">%s</h3>', esc_html( $heading ) );

		if ( ! $query->have_posts() ) {
			printf( '<p class="yka-newsroom__empty">%s</p>', esc_html( $empty_message ) );
			return;
		}

		echo '<ul class="yka-newsroom__posts">';
		foreach ( $query->posts as $post ) {
			$unit = Taxonomy::get_post_unit( $post );
			printf(
				'<li><a href="%s">%s</a><span class="yka-newsroom__meta">%s%s</span></li>',
				esc_url( (string) get_edit_post_link( $post->ID ) ),
				esc_html( get_the_title( $post ) ?: __( '(tanpa judul)', 'yka-core' ) ),
				$unit instanceof \WP_Term ? esc_html( $unit->name . ' · ' ) : '',
				esc_html( get_the_time( (string) get_option( 'date_format' ), $post ) )
			);
		}
		echo '</ul>';

		wp_reset_postdata();
	}
}
