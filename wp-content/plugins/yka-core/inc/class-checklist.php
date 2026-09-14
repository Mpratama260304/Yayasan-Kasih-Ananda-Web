<?php
/**
 * Pre-publication quality checklist.
 *
 * @package YKA\Core
 */

declare( strict_types=1 );

namespace YKA\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Warns editors about missing information before an article goes live.
 *
 * Deliberately advisory. A three-sentence announcement about a change of
 * schedule is a legitimate article, so nothing here blocks publishing.
 */
final class Checklist {

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_box' ) );
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'enqueue_editor' ) );
	}

	/**
	 * Evaluates an article and returns the outstanding items.
	 *
	 * @param int $post_id Post id.
	 * @return array<int, array{id: string, level: string, message: string}>
	 */
	public static function evaluate( int $post_id ): array {
		$post = get_post( $post_id );
		if ( ! $post instanceof \WP_Post || 'post' !== $post->post_type ) {
			return array();
		}

		$issues = array();

		$add = static function ( string $id, string $level, string $message ) use ( &$issues ): void {
			$issues[] = array(
				'id'      => $id,
				'level'   => $level,
				'message' => $message,
			);
		};

		if ( '' === trim( $post->post_title ) || 'auto-draft' === $post->post_status ) {
			$add( 'title', 'error', __( 'Judul artikel masih kosong.', 'yka-core' ) );
		}

		$body = trim( wp_strip_all_tags( (string) $post->post_content ) );
		if ( '' === $body ) {
			$add( 'content', 'error', __( 'Isi artikel masih kosong.', 'yka-core' ) );
		} elseif ( str_word_count( $body ) < 25 ) {
			$add( 'content', 'warning', __( 'Isi artikel sangat pendek. Pastikan pertanyaan apa, siapa, kapan, dan di mana sudah terjawab.', 'yka-core' ) );
		}

		$units = get_the_terms( $post, Taxonomy::TAXONOMY );
		if ( ! is_array( $units ) || empty( $units ) ) {
			$add( 'unit', 'error', __( 'Unit pendidikan belum dipilih. Tanpa unit, artikel tidak muncul di halaman SD, SMP, SMK, atau Yayasan.', 'yka-core' ) );
		}

		$categories = get_the_terms( $post, 'category' );
		if ( ! is_array( $categories ) || empty( $categories ) ) {
			$add( 'category', 'warning', __( 'Kategori belum dipilih.', 'yka-core' ) );
		}

		if ( ! has_post_thumbnail( $post ) ) {
			$add( 'thumbnail', 'warning', __( 'Belum ada gambar utama. Artikel tanpa foto tampil lemah di hasil pencarian dan saat dibagikan.', 'yka-core' ) );
		} else {
			$thumb_id = (int) get_post_thumbnail_id( $post );
			$alt      = (string) get_post_meta( $thumb_id, '_wp_attachment_image_alt', true );
			if ( '' === trim( $alt ) ) {
				$add( 'thumbnail_alt', 'warning', __( 'Gambar utama belum memiliki teks alternatif (alt).', 'yka-core' ) );
			}
			$meta = wp_get_attachment_metadata( $thumb_id );
			if ( is_array( $meta ) && ( (int) ( $meta['width'] ?? 0 ) ) < 1200 ) {
				$add( 'thumbnail_size', 'warning', __( 'Gambar utama lebih sempit dari 1200 piksel. Pratinjau media sosial akan terlihat pecah.', 'yka-core' ) );
			}
		}

		if ( '' === trim( (string) $post->post_excerpt ) ) {
			$add( 'excerpt', 'warning', __( 'Ringkasan belum diisi. Ringkasan dipakai sebagai teks pembuka di beranda dan pratinjau berbagi.', 'yka-core' ) );
		}

		/**
		 * Filters the checklist result.
		 *
		 * @param array    $issues Outstanding items.
		 * @param \WP_Post $post   Post being checked.
		 */
		return (array) apply_filters( 'yka_publish_checklist', $issues, $post );
	}

	/**
	 * Registers the editor panel.
	 *
	 * @return void
	 */
	public static function add_meta_box(): void {
		add_meta_box(
			'yka-publish-checklist',
			__( 'Kesiapan Publikasi', 'yka-core' ),
			array( __CLASS__, 'render' ),
			'post',
			'side',
			'high',
			array( '__block_editor_compatible_meta_box' => true )
		);
	}

	/**
	 * Renders the panel.
	 *
	 * @param \WP_Post $post Current post.
	 * @return void
	 */
	public static function render( \WP_Post $post ): void {
		$issues = self::evaluate( $post->ID );

		echo '<div class="yka-checklist" id="yka-checklist" data-post="' . esc_attr( (string) $post->ID ) . '">';

		if ( empty( $issues ) ) {
			printf(
				'<p class="yka-checklist__ok">%s</p>',
				esc_html__( 'Semua bagian penting sudah terisi.', 'yka-core' )
			);
		} else {
			echo '<ul class="yka-checklist__list">';
			foreach ( $issues as $issue ) {
				printf(
					'<li class="yka-checklist__item yka-checklist__item--%s">%s</li>',
					esc_attr( $issue['level'] ),
					esc_html( $issue['message'] )
				);
			}
			echo '</ul>';
		}

		printf(
			'<p class="yka-checklist__note">%s</p>',
			esc_html__( 'Daftar ini bersifat pengingat. Artikel tetap dapat diterbitkan.', 'yka-core' )
		);
		echo '</div>';
	}

	/**
	 * Loads the live checklist script in the block editor.
	 *
	 * @return void
	 */
	public static function enqueue_editor(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'post' !== $screen->post_type ) {
			return;
		}

		wp_enqueue_style( 'yka-core-admin', YKA_CORE_URL . 'assets/css/admin.css', array(), VERSION );
		wp_enqueue_script(
			'yka-core-editor',
			YKA_CORE_URL . 'assets/js/editor.js',
			array( 'wp-data', 'wp-dom-ready', 'wp-i18n' ),
			VERSION,
			true
		);

		wp_localize_script(
			'yka-core-editor',
			'ykaEditor',
			array(
				'taxonomy' => Taxonomy::TAXONOMY,
				'messages' => array(
					'ok'        => __( 'Semua bagian penting sudah terisi.', 'yka-core' ),
					'title'     => __( 'Judul artikel masih kosong.', 'yka-core' ),
					'content'   => __( 'Isi artikel masih kosong.', 'yka-core' ),
					'short'     => __( 'Isi artikel sangat pendek. Pastikan pertanyaan apa, siapa, kapan, dan di mana sudah terjawab.', 'yka-core' ),
					'unit'      => __( 'Unit pendidikan belum dipilih. Tanpa unit, artikel tidak muncul di halaman SD, SMP, SMK, atau Yayasan.', 'yka-core' ),
					'category'  => __( 'Kategori belum dipilih.', 'yka-core' ),
					'thumbnail' => __( 'Belum ada gambar utama. Artikel tanpa foto tampil lemah di hasil pencarian dan saat dibagikan.', 'yka-core' ),
					'excerpt'   => __( 'Ringkasan belum diisi. Ringkasan dipakai sebagai teks pembuka di beranda dan pratinjau berbagi.', 'yka-core' ),
					'copied'    => __( 'Teks disalin.', 'yka-core' ),
					'copyfail'  => __( 'Tidak dapat menyalin otomatis. Silakan salin manual.', 'yka-core' ),
				),
			)
		);
	}
}
