<?php
/**
 * Institutional settings for Yayasan Kasih Ananda.
 *
 * @package YKA\Core
 */

declare( strict_types=1 );

namespace YKA\Core;

defined( 'ABSPATH' ) || exit;

/**
 * A single options record holding the information that appears across
 * templates: contact details, social profiles and homepage controls.
 *
 * Stored as one array option so a site migration carries it in one piece.
 */
final class Settings {

	public const OPTION    = 'yka_settings';
	public const MENU_SLUG = 'yka-settings';
	public const GROUP     = 'yka_settings_group';

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ), 9 );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_action( 'update_option_' . self::OPTION, array( __CLASS__, 'sync_share_image' ) );
	}

	/**
	 * Keeps the SEO plugin's fallback share image pointing at the YKA hero.
	 *
	 * Without a fallback, pages that have no featured image of their own —
	 * the homepage above all — are shared with no preview picture at all.
	 * This writes Rank Math configuration, never Rank Math code.
	 *
	 * @return void
	 */
	public static function sync_share_image(): void {
		if ( ! Seo::rank_math_active() ) {
			return;
		}

		$image_id = (int) self::get( 'hero_image', 0 );
		if ( ! $image_id ) {
			$image_id = (int) get_theme_mod( 'custom_logo', 0 );
		}
		if ( ! $image_id ) {
			return;
		}

		$titles = get_option( 'rank-math-options-titles', array() );
		$titles = is_array( $titles ) ? $titles : array();

		$titles['open_graph_image']    = Images::url( $image_id, Images::SIZE_SOCIAL );
		$titles['open_graph_image_id'] = $image_id;

		update_option( 'rank-math-options-titles', $titles );
	}

	/**
	 * Section and field definitions.
	 *
	 * @return array<string, array{title: string, description: string, fields: array<string, array<string, mixed>>}>
	 */
	public static function schema(): array {
		return array(
			'identity' => array(
				'title'       => __( 'Identitas lembaga', 'yka-core' ),
				'description' => __( 'Dipakai pada footer, halaman kontak, dan data terstruktur. Isi hanya dengan data resmi.', 'yka-core' ),
				'fields'      => array(
					'org_name'        => array(
						'label'   => __( 'Nama resmi yayasan', 'yka-core' ),
						'type'    => 'text',
						'default' => 'Yayasan Kasih Ananda',
					),
					'org_short_name'  => array(
						'label'   => __( 'Nama singkat', 'yka-core' ),
						'type'    => 'text',
						'default' => 'Kasih Ananda',
					),
					'org_description' => array(
						'label' => __( 'Deskripsi footer', 'yka-core' ),
						'type'  => 'textarea',
						'help'  => __( 'Dua sampai tiga kalimat faktual. Hindari slogan pemasaran.', 'yka-core' ),
					),
					'org_founded'     => array(
						'label' => __( 'Tahun berdiri', 'yka-core' ),
						'type'  => 'text',
						'help'  => __( 'Kosongkan jika belum ada dokumen resmi yang memastikan tahunnya.', 'yka-core' ),
					),
				),
			),
			'contact'  => array(
				'title'       => __( 'Kontak', 'yka-core' ),
				'description' => __( 'Biarkan kosong bila datanya belum diberikan. Situs akan menampilkan penanda "belum tersedia", bukan informasi karangan.', 'yka-core' ),
				'fields'      => array(
					'address'    => array(
						'label' => __( 'Alamat kantor yayasan', 'yka-core' ),
						'type'  => 'textarea',
					),
					'phone'      => array(
						'label' => __( 'Telepon', 'yka-core' ),
						'type'  => 'tel',
					),
					'whatsapp'   => array(
						'label' => __( 'WhatsApp', 'yka-core' ),
						'type'  => 'tel',
						'help'  => __( 'Format internasional tanpa spasi, contoh 628xxxxxxxxxx.', 'yka-core' ),
					),
					'email'      => array(
						'label' => __( 'Email', 'yka-core' ),
						'type'  => 'email',
					),
					'maps_url'   => array(
						'label' => __( 'Tautan Google Maps', 'yka-core' ),
						'type'  => 'url',
					),
					'maps_embed' => array(
						'label' => __( 'Kode sematan peta', 'yka-core' ),
						'type'  => 'url',
						'help'  => __( 'URL iframe Google Maps (bagian src saja). Peta hanya dimuat setelah pengunjung menekan tombol.', 'yka-core' ),
					),
				),
			),
			'social'   => array(
				'title'       => __( 'Media sosial resmi', 'yka-core' ),
				'description' => __( 'Hanya akun resmi yang sudah diverifikasi. Tautan ini masuk ke properti sameAs pada data terstruktur, sehingga akun palsu akan merusak identitas entitas.', 'yka-core' ),
				'fields'      => array(
					'social_facebook'  => array(
						'label' => __( 'Facebook', 'yka-core' ),
						'type'  => 'url',
					),
					'social_instagram' => array(
						'label' => __( 'Instagram', 'yka-core' ),
						'type'  => 'url',
					),
					'social_youtube'   => array(
						'label' => __( 'YouTube', 'yka-core' ),
						'type'  => 'url',
					),
					'social_tiktok'    => array(
						'label' => __( 'TikTok', 'yka-core' ),
						'type'  => 'url',
					),
					'social_x'         => array(
						'label' => __( 'X (Twitter)', 'yka-core' ),
						'type'  => 'url',
					),
				),
			),
			'homepage' => array(
				'title'       => __( 'Beranda', 'yka-core' ),
				'description' => __( 'Mengatur bagian atas beranda tanpa menyentuh kode. Jika artikel unggulan dikosongkan, beranda otomatis memakai artikel terbaru.', 'yka-core' ),
				'fields'      => array(
					'hero_image'    => array(
						'label' => __( 'Foto hero', 'yka-core' ),
						'type'  => 'media',
						'help'  => __( 'Foto dokumentasi asli, lebar minimal 1920 piksel. Gambar ini adalah elemen LCP beranda, jadi pilih berkas yang sudah dikompresi.', 'yka-core' ),
					),
					'hero_heading'  => array(
						'label' => __( 'Judul hero', 'yka-core' ),
						'type'  => 'text',
						'help'  => __( 'Kalimat faktual, bukan slogan.', 'yka-core' ),
					),
					'hero_text'     => array(
						'label' => __( 'Paragraf hero', 'yka-core' ),
						'type'  => 'textarea',
					),
					'featured_post' => array(
						'label' => __( 'Artikel unggulan', 'yka-core' ),
						'type'  => 'post',
						'help'  => __( 'Pilih satu artikel untuk disorot. Kosongkan agar mengikuti artikel terbaru.', 'yka-core' ),
					),
				),
			),
		);
	}

	/**
	 * Flattened field map.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function fields(): array {
		$fields = array();
		foreach ( self::schema() as $section ) {
			$fields += $section['fields'];
		}
		return $fields;
	}

	/**
	 * All settings with defaults applied.
	 *
	 * @return array<string, mixed>
	 */
	public static function all(): array {
		$stored   = get_option( self::OPTION, array() );
		$stored   = is_array( $stored ) ? $stored : array();
		$defaults = array();

		foreach ( self::fields() as $key => $field ) {
			$defaults[ $key ] = $field['default'] ?? '';
		}

		return array_merge( $defaults, $stored );
	}

	/**
	 * Reads a single setting.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default_value Fallback.
	 * @return mixed
	 */
	public static function get( string $key, $default_value = '' ) {
		$all = self::all();
		$val = $all[ $key ] ?? $default_value;
		return ( '' === $val || null === $val ) ? $default_value : $val;
	}

	/**
	 * Official social profile URLs that are actually filled in.
	 *
	 * @return array<string, string>
	 */
	public static function social_urls(): array {
		$out = array();
		foreach ( self::fields() as $key => $field ) {
			if ( ! str_starts_with( $key, 'social_' ) ) {
				continue;
			}
			$value = (string) self::get( $key );
			if ( '' !== $value ) {
				$out[ substr( $key, 7 ) ] = $value;
			}
		}
		return $out;
	}

	/**
	 * Registers the admin menu.
	 *
	 * @return void
	 */
	public static function add_menu(): void {
		add_menu_page(
			__( 'Yayasan Kasih Ananda', 'yka-core' ),
			__( 'Yayasan', 'yka-core' ),
			'manage_options',
			self::MENU_SLUG,
			array( __CLASS__, 'render_page' ),
			'dashicons-bank',
			3
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Pengaturan Yayasan', 'yka-core' ),
			__( 'Pengaturan', 'yka-core' ),
			'manage_options',
			self::MENU_SLUG,
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Registers settings, sections and fields.
	 *
	 * @return void
	 */
	public static function register(): void {
		register_setting(
			self::GROUP,
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => array(),
			)
		);

		foreach ( self::schema() as $section_id => $section ) {
			add_settings_section(
				'yka_section_' . $section_id,
				$section['title'],
				static function () use ( $section ): void {
					printf( '<p class="description">%s</p>', esc_html( $section['description'] ) );
				},
				self::MENU_SLUG
			);

			foreach ( $section['fields'] as $key => $field ) {
				add_settings_field(
					$key,
					$field['label'],
					array( __CLASS__, 'render_field' ),
					self::MENU_SLUG,
					'yka_section_' . $section_id,
					array(
						'key'       => $key,
						'field'     => $field,
						'label_for' => 'yka_' . $key,
					)
				);
			}
		}
	}

	/**
	 * Validates and sanitises the whole option array.
	 *
	 * @param mixed $input Raw submitted values.
	 * @return array<string, mixed>
	 */
	public static function sanitize( $input ): array {
		$input  = is_array( $input ) ? $input : array();
		$clean  = array();
		$fields = self::fields();

		foreach ( $fields as $key => $field ) {
			$raw  = $input[ $key ] ?? '';
			$type = (string) $field['type'];

			switch ( $type ) {
				case 'media':
					$id = max( 0, (int) $raw );
					// Only accept ids that really are attachments on this site.
					$clean[ $key ] = ( $id && 'attachment' === get_post_type( $id ) ) ? $id : '';
					break;

				case 'post':
					$id            = max( 0, (int) $raw );
					$clean[ $key ] = ( $id && 'post' === get_post_type( $id ) ) ? $id : '';
					break;

				case 'email':
					$clean[ $key ] = sanitize_email( (string) $raw );
					break;

				case 'url':
					$clean[ $key ] = esc_url_raw( (string) $raw );
					break;

				case 'tel':
					$clean[ $key ] = preg_replace( '/[^0-9+\-\s()]/', '', (string) $raw ) ?? '';
					break;

				case 'textarea':
					$clean[ $key ] = sanitize_textarea_field( (string) $raw );
					break;

				default:
					$clean[ $key ] = sanitize_text_field( (string) $raw );
			}
		}

		return $clean;
	}

	/**
	 * Renders one field.
	 *
	 * @param array<string, mixed> $args Field args.
	 * @return void
	 */
	public static function render_field( array $args ): void {
		$key   = (string) $args['key'];
		$field = (array) $args['field'];
		$value = self::get( $key );
		$name  = sprintf( '%s[%s]', self::OPTION, $key );
		$id    = 'yka_' . $key;

		switch ( $field['type'] ) {
			case 'textarea':
				printf(
					'<textarea name="%s" id="%s" rows="4" class="large-text">%s</textarea>',
					esc_attr( $name ),
					esc_attr( $id ),
					esc_textarea( (string) $value )
				);
				break;

			case 'media':
				$attachment_id = (int) $value;
				printf(
					'<div class="yka-media-field" data-yka-media><div class="yka-media-field__preview" data-yka-media-preview>%1$s</div>'
					. '<input type="hidden" name="%2$s" id="%3$s" value="%4$s" data-yka-media-input />'
					. '<button type="button" class="button" data-yka-media-select>%5$s</button> '
					. '<button type="button" class="button-link yka-media-field__remove" data-yka-media-remove%7$s>%6$s</button></div>',
					$attachment_id ? wp_kses_post( (string) wp_get_attachment_image( $attachment_id, 'medium', false, array( 'alt' => '' ) ) ) : '',
					esc_attr( $name ),
					esc_attr( $id ),
					esc_attr( (string) $attachment_id ),
					esc_html__( 'Pilih gambar', 'yka-core' ),
					esc_html__( 'Hapus', 'yka-core' ),
					$attachment_id ? '' : ' hidden'
				);
				break;

			case 'post':
				$recent = get_posts(
					array(
						'post_type'        => 'post',
						'post_status'      => 'publish',
						'numberposts'      => 40,
						'suppress_filters' => false,
					)
				);
				printf( '<select name="%s" id="%s"><option value="">%s</option>', esc_attr( $name ), esc_attr( $id ), esc_html__( '— Gunakan artikel terbaru —', 'yka-core' ) );
				foreach ( $recent as $post ) {
					printf(
						'<option value="%d"%s>%s</option>',
						(int) $post->ID,
						selected( (int) $value, (int) $post->ID, false ),
						esc_html( get_the_title( $post ) )
					);
				}
				echo '</select>';
				break;

			default:
				printf(
					'<input type="%s" name="%s" id="%s" value="%s" class="regular-text" />',
					esc_attr( (string) $field['type'] ),
					esc_attr( $name ),
					esc_attr( $id ),
					esc_attr( (string) $value )
				);
		}

		if ( ! empty( $field['help'] ) ) {
			printf( '<p class="description">%s</p>', esc_html( (string) $field['help'] ) );
		}
	}

	/**
	 * Renders the settings screen.
	 *
	 * @return void
	 */
	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Anda tidak memiliki izin untuk membuka halaman ini.', 'yka-core' ) );
		}
		?>
		<div class="wrap yka-settings">
			<h1><?php esc_html_e( 'Pengaturan Yayasan Kasih Ananda', 'yka-core' ); ?></h1>
			<p class="yka-settings__intro">
				<?php esc_html_e( 'Informasi di halaman ini dipakai ulang di seluruh situs: footer, halaman kontak, metadata berbagi, dan data terstruktur. Kosongkan bidang yang datanya belum resmi.', 'yka-core' ); ?>
			</p>
			<form action="options.php" method="post">
				<?php
				settings_fields( self::GROUP );
				do_settings_sections( self::MENU_SLUG );
				submit_button( __( 'Simpan pengaturan', 'yka-core' ) );
				?>
			</form>

			<hr />
			<h2><?php esc_html_e( 'Logo dan ikon situs', 'yka-core' ); ?></h2>
			<p>
				<?php
				printf(
					/* translators: %s: link to the customizer. */
					esc_html__( 'Logo utama dan favicon memakai mekanisme bawaan WordPress agar tetap ikut saat migrasi. Aturlah melalui %s.', 'yka-core' ),
					'<a href="' . esc_url( admin_url( 'customize.php' ) ) . '">' . esc_html__( 'Tampilan → Sesuaikan', 'yka-core' ) . '</a>'
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Loads the media picker on the settings screen.
	 *
	 * @param string $hook Current admin page.
	 * @return void
	 */
	public static function enqueue( string $hook ): void {
		if ( ! str_contains( $hook, self::MENU_SLUG ) ) {
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
}
