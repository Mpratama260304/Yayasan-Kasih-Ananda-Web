<?php
/**
 * Article metadata for news and documentation posts.
 *
 * @package YKA\Core
 */

declare( strict_types=1 );

namespace YKA\Core;

defined( 'ABSPATH' ) || exit;

/**
 * A deliberately small set of optional article fields.
 *
 * The activity date is separate from the WordPress publication date: an
 * event usually happens days before the article about it goes live, and
 * schema must not confuse the two.
 */
final class Post_Meta {

	public const ACTIVITY_DATE = 'yka_activity_date';
	public const LOCATION      = 'yka_activity_location';
	public const PHOTO_CREDIT  = 'yka_photo_credit';
	public const SOURCE_NOTE   = 'yka_source_note';
	public const SOCIAL_TEXT   = 'yka_social_caption';
	public const SOCIAL_TAGS   = 'yka_social_hashtags';

	private const NONCE = 'yka_post_meta';

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'register_meta' ), 6 );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_box' ) );
		add_action( 'save_post', array( __CLASS__, 'save' ), 10, 2 );
	}

	/**
	 * Field definitions.
	 *
	 * @return array<string, array{label: string, type: string, help: string, public: bool}>
	 */
	public static function fields(): array {
		return array(
			self::ACTIVITY_DATE => array(
				'label'  => __( 'Tanggal kegiatan', 'yka-core' ),
				'type'   => 'date',
				'help'   => __( 'Tanggal kegiatan berlangsung. Berbeda dari tanggal terbit artikel.', 'yka-core' ),
				'public' => true,
			),
			self::LOCATION      => array(
				'label'  => __( 'Lokasi kegiatan', 'yka-core' ),
				'type'   => 'text',
				'help'   => __( 'Contoh: Aula SMK Kasih Ananda. Kosongkan bila tidak relevan.', 'yka-core' ),
				'public' => true,
			),
			self::PHOTO_CREDIT  => array(
				'label'  => __( 'Kredit fotografer', 'yka-core' ),
				'type'   => 'text',
				'help'   => __( 'Nama pengambil foto. Tampil di bawah foto utama.', 'yka-core' ),
				'public' => true,
			),
			self::SOURCE_NOTE   => array(
				'label'  => __( 'Catatan dokumentasi (internal)', 'yka-core' ),
				'type'   => 'textarea',
				'help'   => __( 'Catatan untuk tim redaksi. Tidak pernah ditampilkan di halaman publik.', 'yka-core' ),
				'public' => false,
			),
			self::SOCIAL_TEXT   => array(
				'label'  => __( 'Teks singkat media sosial', 'yka-core' ),
				'type'   => 'textarea',
				'help'   => __( 'Kosongkan untuk memakai ringkasan artikel secara otomatis.', 'yka-core' ),
				'public' => false,
			),
			self::SOCIAL_TAGS   => array(
				'label'  => __( 'Tagar tambahan', 'yka-core' ),
				'type'   => 'text',
				'help'   => __( 'Dipisah spasi, misalnya #Pramuka #HUTRI. Tagar yayasan dan unit ditambahkan otomatis.', 'yka-core' ),
				'public' => false,
			),
		);
	}

	/**
	 * Registers post meta with REST support.
	 *
	 * @return void
	 */
	public static function register_meta(): void {
		foreach ( self::fields() as $key => $field ) {
			register_post_meta(
				'post',
				$key,
				array(
					'type'              => 'string',
					'single'            => true,
					'default'           => '',
					'show_in_rest'      => true,
					'sanitize_callback' => static fn( $value ) => self::sanitize_value( $value, $field['type'] ),
					'auth_callback'     => static function (): bool {
						return current_user_can( 'edit_posts' );
					},
				)
			);
		}
	}

	/**
	 * Registers the editor panel.
	 *
	 * @return void
	 */
	public static function add_meta_box(): void {
		add_meta_box(
			'yka-article-meta',
			__( 'Detail Dokumentasi', 'yka-core' ),
			array( __CLASS__, 'render' ),
			'post',
			'side',
			'default',
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
		wp_nonce_field( self::NONCE, self::NONCE . '_nonce' );

		echo '<div class="yka-meta-panel">';
		foreach ( self::fields() as $key => $field ) {
			$value = self::get( $post->ID, $key );

			printf( '<p class="yka-meta-panel__field"><label for="%s"><strong>%s</strong></label><br />', esc_attr( $key ), esc_html( $field['label'] ) );

			if ( 'textarea' === $field['type'] ) {
				printf(
					'<textarea name="%1$s" id="%1$s" rows="3" style="width:100%%">%2$s</textarea>',
					esc_attr( $key ),
					esc_textarea( $value )
				);
			} else {
				printf(
					'<input type="%3$s" name="%1$s" id="%1$s" value="%2$s" style="width:100%%" />',
					esc_attr( $key ),
					esc_attr( $value ),
					esc_attr( $field['type'] )
				);
			}

			printf( '<span class="description">%s</span></p>', esc_html( $field['help'] ) );
		}
		echo '</div>';
	}

	/**
	 * Saves submitted values.
	 *
	 * @param int      $post_id Post id.
	 * @param \WP_Post $post    Post object.
	 * @return void
	 */
	public static function save( int $post_id, $post ): void {
		if ( ! $post instanceof \WP_Post || 'post' !== $post->post_type ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$nonce = isset( $_POST[ self::NONCE . '_nonce' ] )
			? sanitize_text_field( wp_unslash( $_POST[ self::NONCE . '_nonce' ] ) )
			: '';

		if ( ! wp_verify_nonce( $nonce, self::NONCE ) ) {
			return;
		}

		foreach ( self::fields() as $key => $field ) {
			if ( ! isset( $_POST[ $key ] ) ) {
				continue;
			}

			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitised below.
			$value = self::sanitize_value( wp_unslash( $_POST[ $key ] ), $field['type'] );

			if ( '' === $value ) {
				delete_post_meta( $post_id, $key );
				continue;
			}
			update_post_meta( $post_id, $key, $value );
		}
	}

	/**
	 * Sanitises a value by field type.
	 *
	 * @param mixed  $value Raw value.
	 * @param string $type  Field type.
	 * @return string
	 */
	public static function sanitize_value( $value, string $type ): string {
		$value = (string) $value;

		if ( 'date' === $type ) {
			$value = trim( $value );
			return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ? $value : '';
		}
		if ( 'textarea' === $type ) {
			return sanitize_textarea_field( $value );
		}
		return sanitize_text_field( $value );
	}

	/**
	 * Reads a meta value.
	 *
	 * @param int    $post_id Post id.
	 * @param string $key     Meta key.
	 * @return string
	 */
	public static function get( int $post_id, string $key ): string {
		$value = get_post_meta( $post_id, $key, true );
		return is_scalar( $value ) ? (string) $value : '';
	}

	/**
	 * The activity date formatted for display, or an empty string.
	 *
	 * @param int $post_id Post id.
	 * @return string
	 */
	public static function activity_date_display( int $post_id ): string {
		$raw = self::get( $post_id, self::ACTIVITY_DATE );
		if ( '' === $raw ) {
			return '';
		}

		$date = date_create_immutable( $raw, wp_timezone() );
		if ( ! $date ) {
			return '';
		}

		return wp_date( (string) get_option( 'date_format', 'j F Y' ), $date->getTimestamp() );
	}
}
