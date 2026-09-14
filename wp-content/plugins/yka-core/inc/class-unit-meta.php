<?php
/**
 * Institutional metadata for each education unit term.
 *
 * @package YKA\Core
 */

declare( strict_types=1 );

namespace YKA\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Adds institutional fields to `yka_unit` terms and renders the admin UI.
 *
 * Every value here is supplied by the organisation. The plugin ships no
 * addresses, phone numbers or descriptions of its own.
 */
final class Unit_Meta {

	private const NONCE = 'yka_unit_meta';

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( Taxonomy::TAXONOMY . '_add_form_fields', array( __CLASS__, 'render_add_fields' ) );
		add_action( Taxonomy::TAXONOMY . '_edit_form_fields', array( __CLASS__, 'render_edit_fields' ), 10, 1 );
		add_action( 'created_' . Taxonomy::TAXONOMY, array( __CLASS__, 'save' ) );
		add_action( 'edited_' . Taxonomy::TAXONOMY, array( __CLASS__, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_action( 'init', array( __CLASS__, 'register_meta' ), 6 );
	}

	/**
	 * Field definitions. `type` drives both the input and the sanitiser.
	 *
	 * @return array<string, array{label: string, type: string, help?: string, group: string}>
	 */
	public static function fields(): array {
		return array(
			'yka_unit_official_name' => array(
				'label' => __( 'Nama resmi', 'yka-core' ),
				'type'  => 'text',
				'help'  => __( 'Nama lengkap sesuai dokumen resmi, misalnya untuk kop surat dan data terstruktur.', 'yka-core' ),
				'group' => 'identity',
			),
			'yka_unit_short_name'    => array(
				'label' => __( 'Nama singkat', 'yka-core' ),
				'type'  => 'text',
				'help'  => __( 'Dipakai pada label ringkas seperti kartu berita. Contoh: SMP.', 'yka-core' ),
				'group' => 'identity',
			),
			'yka_unit_intro'         => array(
				'label' => __( 'Paragraf pengantar', 'yka-core' ),
				'type'  => 'textarea',
				'help'  => __( 'Satu sampai dua kalimat faktual tentang unit ini. Tampil di halaman unit dan beranda.', 'yka-core' ),
				'group' => 'identity',
			),
			'yka_unit_order'         => array(
				'label' => __( 'Urutan tampil', 'yka-core' ),
				'type'  => 'number',
				'help'  => __( 'Angka kecil tampil lebih dahulu.', 'yka-core' ),
				'group' => 'identity',
			),
			'yka_unit_logo_id'       => array(
				'label' => __( 'Logo unit', 'yka-core' ),
				'type'  => 'media',
				'help'  => __( 'Berkas logo resmi. Sebaiknya PNG atau SVG dengan latar transparan.', 'yka-core' ),
				'group' => 'media',
			),
			'yka_unit_hero_id'       => array(
				'label' => __( 'Foto utama (hero)', 'yka-core' ),
				'type'  => 'media',
				'help'  => __( 'Foto dokumentasi asli unit ini, lebar minimal 1600 piksel.', 'yka-core' ),
				'group' => 'media',
			),
			'yka_unit_address'       => array(
				'label' => __( 'Alamat', 'yka-core' ),
				'type'  => 'textarea',
				'group' => 'contact',
			),
			'yka_unit_phone'         => array(
				'label' => __( 'Telepon', 'yka-core' ),
				'type'  => 'tel',
				'group' => 'contact',
			),
			'yka_unit_whatsapp'      => array(
				'label' => __( 'WhatsApp', 'yka-core' ),
				'type'  => 'tel',
				'help'  => __( 'Format internasional tanpa spasi, misalnya 628xxxxxxxxxx.', 'yka-core' ),
				'group' => 'contact',
			),
			'yka_unit_email'         => array(
				'label' => __( 'Email', 'yka-core' ),
				'type'  => 'email',
				'group' => 'contact',
			),
			'yka_unit_maps_url'      => array(
				'label' => __( 'Tautan Google Maps', 'yka-core' ),
				'type'  => 'url',
				'group' => 'contact',
			),
			'yka_unit_legacy_url'    => array(
				'label' => __( 'Situs lama unit', 'yka-core' ),
				'type'  => 'url',
				'help'  => __( 'Ditampilkan sebagai tautan transisi selama situs lama masih aktif.', 'yka-core' ),
				'group' => 'contact',
			),
			'yka_unit_social'        => array(
				'label' => __( 'Profil media sosial resmi', 'yka-core' ),
				'type'  => 'urls',
				'help'  => __( 'Satu URL per baris. Hanya akun resmi yang terverifikasi — tautan ini masuk ke data terstruktur sameAs.', 'yka-core' ),
				'group' => 'contact',
			),
		);
	}

	/**
	 * Groups in render order.
	 *
	 * @return array<string, string>
	 */
	private static function groups(): array {
		return array(
			'identity' => __( 'Identitas unit', 'yka-core' ),
			'media'    => __( 'Gambar', 'yka-core' ),
			'contact'  => __( 'Kontak dan tautan', 'yka-core' ),
		);
	}

	/**
	 * Registers term meta so the REST API and block editor can read it.
	 *
	 * @return void
	 */
	public static function register_meta(): void {
		foreach ( self::fields() as $key => $field ) {
			register_term_meta(
				Taxonomy::TAXONOMY,
				$key,
				array(
					'type'              => in_array( $field['type'], array( 'media', 'number' ), true ) ? 'integer' : 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => static fn( $value ) => self::sanitize_value( $value, $field['type'] ),
					'auth_callback'     => static fn(): bool => current_user_can( 'manage_categories' ),
				)
			);
		}
	}

	/**
	 * Reads a unit meta value.
	 *
	 * @param int    $term_id Term id.
	 * @param string $key     Meta key.
	 * @return string
	 */
	public static function get( int $term_id, string $key ): string {
		$value = get_term_meta( $term_id, $key, true );
		return is_scalar( $value ) ? (string) $value : '';
	}

	/**
	 * Official social profile URLs for a unit.
	 *
	 * @param int $term_id Term id.
	 * @return string[]
	 */
	public static function get_social_urls( int $term_id ): array {
		$raw = self::get( $term_id, 'yka_unit_social' );
		if ( '' === $raw ) {
			return array();
		}
		return array_values( array_filter( array_map( 'trim', preg_split( '/\R+/', $raw ) ?: array() ) ) );
	}

	/**
	 * Loads the media picker only on the taxonomy screens that need it.
	 *
	 * @param string $hook Current admin page.
	 * @return void
	 */
	public static function enqueue( string $hook ): void {
		if ( ! in_array( $hook, array( 'edit-tags.php', 'term.php' ), true ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only screen check, no state change.
		$screen_taxonomy = isset( $_GET['taxonomy'] ) ? sanitize_key( wp_unslash( $_GET['taxonomy'] ) ) : '';
		if ( Taxonomy::TAXONOMY !== $screen_taxonomy ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_style( 'yka-core-admin', YKA_CORE_URL . 'assets/css/admin.css', array(), VERSION );
		wp_enqueue_script( 'yka-core-media', YKA_CORE_URL . 'assets/js/media-field.js', array(), VERSION, true );
		wp_localize_script(
			'yka-core-media',
			'ykaMediaField',
			array(
				'title'  => __( 'Pilih gambar', 'yka-core' ),
				'button' => __( 'Gunakan gambar ini', 'yka-core' ),
				'remove' => __( 'Hapus', 'yka-core' ),
			)
		);
	}

	/**
	 * "Add new term" form fields.
	 *
	 * @return void
	 */
	public static function render_add_fields(): void {
		wp_nonce_field( self::NONCE, self::NONCE . '_nonce' );
		echo '<div class="yka-term-fields yka-term-fields--add">';
		foreach ( self::fields() as $key => $field ) {
			echo '<div class="form-field">';
			self::render_label( $key, $field );
			self::render_input( $key, $field, '' );
			self::render_help( $field );
			echo '</div>';
		}
		echo '</div>';
	}

	/**
	 * "Edit term" form fields.
	 *
	 * @param \WP_Term $term Term being edited.
	 * @return void
	 */
	public static function render_edit_fields( $term ): void {
		if ( ! $term instanceof \WP_Term ) {
			return;
		}

		wp_nonce_field( self::NONCE, self::NONCE . '_nonce' );

		$fields = self::fields();
		foreach ( self::groups() as $group_key => $group_label ) {
			printf(
				'<tr class="form-field yka-term-group"><th colspan="2"><h2 class="yka-term-group__title">%s</h2></th></tr>',
				esc_html( $group_label )
			);

			foreach ( $fields as $key => $field ) {
				if ( $field['group'] !== $group_key ) {
					continue;
				}
				echo '<tr class="form-field"><th scope="row">';
				self::render_label( $key, $field );
				echo '</th><td>';
				self::render_input( $key, $field, self::get( $term->term_id, $key ) );
				self::render_help( $field );
				echo '</td></tr>';
			}
		}
	}

	/**
	 * Renders a field label.
	 *
	 * @param string               $key   Meta key.
	 * @param array<string, mixed> $field Field definition.
	 * @return void
	 */
	private static function render_label( string $key, array $field ): void {
		printf( '<label for="%s">%s</label>', esc_attr( $key ), esc_html( (string) $field['label'] ) );
	}

	/**
	 * Renders the help text.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @return void
	 */
	private static function render_help( array $field ): void {
		if ( empty( $field['help'] ) ) {
			return;
		}
		printf( '<p class="description">%s</p>', esc_html( (string) $field['help'] ) );
	}

	/**
	 * Renders the input control for a field.
	 *
	 * @param string               $key   Meta key.
	 * @param array<string, mixed> $field Field definition.
	 * @param string               $value Current value.
	 * @return void
	 */
	private static function render_input( string $key, array $field, string $value ): void {
		switch ( $field['type'] ) {
			case 'textarea':
			case 'urls':
				printf(
					'<textarea name="%1$s" id="%1$s" rows="4" class="large-text code">%2$s</textarea>',
					esc_attr( $key ),
					esc_textarea( $value )
				);
				break;

			case 'media':
				$attachment_id = (int) $value;
				$preview       = $attachment_id ? wp_get_attachment_image( $attachment_id, 'medium', false, array( 'alt' => '' ) ) : '';
				printf(
					'<div class="yka-media-field" data-yka-media><div class="yka-media-field__preview" data-yka-media-preview>%1$s</div>'
					. '<input type="hidden" name="%2$s" id="%2$s" value="%3$s" data-yka-media-input />'
					. '<button type="button" class="button" data-yka-media-select>%4$s</button> '
					. '<button type="button" class="button-link yka-media-field__remove" data-yka-media-remove%6$s>%5$s</button></div>',
					wp_kses_post( (string) $preview ),
					esc_attr( $key ),
					esc_attr( (string) $attachment_id ),
					esc_html__( 'Pilih gambar', 'yka-core' ),
					esc_html__( 'Hapus', 'yka-core' ),
					$attachment_id ? '' : ' hidden'
				);
				break;

			case 'number':
				printf(
					'<input type="number" step="10" min="0" name="%1$s" id="%1$s" value="%2$s" class="small-text" />',
					esc_attr( $key ),
					esc_attr( $value )
				);
				break;

			default:
				printf(
					'<input type="%3$s" name="%1$s" id="%1$s" value="%2$s" class="regular-text" />',
					esc_attr( $key ),
					esc_attr( $value ),
					esc_attr( (string) $field['type'] )
				);
		}
	}

	/**
	 * Persists submitted values.
	 *
	 * @param int $term_id Term id.
	 * @return void
	 */
	public static function save( int $term_id ): void {
		if ( ! current_user_can( 'manage_categories' ) ) {
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

			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitised by sanitize_value().
			$value = self::sanitize_value( wp_unslash( $_POST[ $key ] ), $field['type'] );

			if ( '' === $value || 0 === $value ) {
				delete_term_meta( $term_id, $key );
				continue;
			}
			update_term_meta( $term_id, $key, $value );
		}
	}

	/**
	 * Type-aware sanitiser.
	 *
	 * @param mixed  $value Raw value.
	 * @param string $type  Field type.
	 * @return string|int
	 */
	public static function sanitize_value( $value, string $type ) {
		switch ( $type ) {
			case 'media':
			case 'number':
				return max( 0, (int) $value );

			case 'email':
				return sanitize_email( (string) $value );

			case 'url':
				return esc_url_raw( (string) $value );

			case 'tel':
				return preg_replace( '/[^0-9+\-\s()]/', '', (string) $value ) ?? '';

			case 'textarea':
				return sanitize_textarea_field( (string) $value );

			case 'urls':
				$lines = preg_split( '/\R+/', (string) $value ) ?: array();
				$clean = array();
				foreach ( $lines as $line ) {
					$url = esc_url_raw( trim( $line ) );
					if ( '' !== $url ) {
						$clean[] = $url;
					}
				}
				return implode( "\n", $clean );

			default:
				return sanitize_text_field( (string) $value );
		}
	}
}
