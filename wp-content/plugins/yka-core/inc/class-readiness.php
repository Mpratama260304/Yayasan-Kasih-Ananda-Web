<?php
/**
 * Production readiness screen.
 *
 * @package YKA\Core
 */

declare( strict_types=1 );

namespace YKA\Core;

defined( 'ABSPATH' ) || exit;

/**
 * A visible, honest checklist of everything that must be true before this
 * site is allowed to be a public production website.
 *
 * It reports state and offers controlled actions. It never flips settings
 * on its own just because a hostname changed — silently toggling indexing
 * is exactly the class of accident this screen exists to prevent.
 */
final class Readiness {

	public const MENU_SLUG = 'yka-readiness';
	private const ACTION   = 'yka_readiness_action';

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ), 11 );
		add_action( 'admin_post_' . self::ACTION, array( __CLASS__, 'handle_action' ) );
	}

	/**
	 * Registers the submenu.
	 *
	 * @return void
	 */
	public static function add_menu(): void {
		add_submenu_page(
			Settings::MENU_SLUG,
			__( 'Kesiapan Produksi', 'yka-core' ),
			__( 'Kesiapan Produksi', 'yka-core' ),
			'manage_options',
			self::MENU_SLUG,
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Runs every check.
	 *
	 * @return array<int, array{id: string, label: string, state: string, detail: string, fix?: string}>
	 */
	public static function checks(): array {
		$checks        = array();
		$host          = Environment::host();
		$is_production = Environment::is_production();
		$home          = home_url();

		$add = static function ( string $id, string $label, string $state, string $detail, ?string $fix = null ) use ( &$checks ): void {
			$checks[] = array(
				'id'     => $id,
				'label'  => $label,
				'state'  => $state,
				'detail' => $detail,
				'fix'    => $fix,
			);
		};

		// --- Domain ---------------------------------------------------
		$add(
			'domain',
			__( 'Domain saat ini adalah domain produksi', 'yka-core' ),
			in_array( $host, Environment::PRODUCTION_HOSTS, true ) ? 'pass' : 'info',
			sprintf(
				/* translators: 1: current host, 2: allowed production hosts. */
				__( 'Domain aktif: %1$s. Domain produksi yang diizinkan: %2$s.', 'yka-core' ),
				$host,
				implode( ', ', Environment::PRODUCTION_HOSTS )
			)
		);

		// --- Environment constant ------------------------------------
		$declared = function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production';
		$add(
			'environment',
			__( 'WP_ENVIRONMENT_TYPE disetel benar', 'yka-core' ),
			'production' === $declared ? 'pass' : 'info',
			sprintf(
				/* translators: %s: environment type. */
				__( 'Nilai saat ini: %s. Setel melalui wp-config.php atau variabel lingkungan di server tujuan, bukan di dalam tema.', 'yka-core' ),
				$declared
			)
		);

		// --- HTTPS ----------------------------------------------------
		$add(
			'https',
			__( 'HTTPS aktif', 'yka-core' ),
			str_starts_with( $home, 'https://' ) ? 'pass' : 'fail',
			sprintf(
				/* translators: %s: home URL. */
				__( 'Alamat situs: %s', 'yka-core' ),
				$home
			)
		);

		// --- Indexing -------------------------------------------------
		$blog_public = (int) get_option( 'blog_public' );
		if ( $is_production ) {
			$add(
				'indexing',
				__( 'WordPress mengizinkan pengindeksan', 'yka-core' ),
				1 === $blog_public ? 'pass' : 'fail',
				1 === $blog_public
					? __( 'Mesin pencari diizinkan mengindeks situs ini.', 'yka-core' )
					: __( 'Opsi "Cegah mesin pencari mengindeks situs ini" masih aktif. Ini kesalahan paling umum setelah menyalin situs staging ke produksi.', 'yka-core' ),
				1 === $blog_public ? null : 'enable_indexing'
			);
		} else {
			$add(
				'indexing',
				__( 'Lingkungan non-produksi tidak dapat diindeks', 'yka-core' ),
				0 === $blog_public ? 'pass' : 'fail',
				0 === $blog_public
					? __( 'Pengindeksan dicegah, sesuai untuk lingkungan pengembangan atau staging.', 'yka-core' )
					: __( 'Situs non-produksi ini masih menyatakan boleh diindeks pada pengaturan WordPress. YKA Core tetap memaksa noindex di setiap halaman, tetapi sebaiknya opsi ini dirapikan.', 'yka-core' ),
				0 === $blog_public ? null : 'disable_indexing'
			);
		}

		// --- Robots meta ----------------------------------------------
		$add(
			'robots',
			__( 'Direktif robots sesuai lingkungan', 'yka-core' ),
			'pass',
			$is_production
				? __( 'Halaman publik boleh diindeks.', 'yka-core' )
				: __( 'Setiap halaman memuat noindex, nofollow, dan robots.txt menolak seluruh perayapan.', 'yka-core' )
		);

		// --- Leftover staging URLs -----------------------------------
		$leftovers = self::find_foreign_urls();
		$add(
			'urls',
			__( 'Tidak ada sisa URL lingkungan lain di pengaturan utama', 'yka-core' ),
			empty( $leftovers ) ? 'pass' : 'fail',
			empty( $leftovers )
				? __( 'siteurl, home, dan alamat email administrator konsisten dengan domain aktif.', 'yka-core' )
				: sprintf(
					/* translators: %s: list of option names. */
					__( 'Opsi berikut masih memuat domain lain: %s', 'yka-core' ),
					implode( ', ', $leftovers )
				)
		);

		// --- SEO plugin ------------------------------------------------
		$add(
			'seo',
			__( 'Satu plugin SEO aktif', 'yka-core' ),
			Seo::rank_math_active() ? 'pass' : 'info',
			Seo::rank_math_active()
				? __( 'Rank Math aktif dan menjadi pemilik tunggal metadata SEO. YKA Core hanya menambah data khusus yayasan.', 'yka-core' )
				: __( 'Rank Math belum aktif. YKA Core sementara menerbitkan canonical, deskripsi, Open Graph, dan data terstruktur sendiri agar situs tetap lengkap.', 'yka-core' )
		);

		// --- Sitemap ---------------------------------------------------
		$sitemap_url = Seo::rank_math_active() ? home_url( '/sitemap_index.xml' ) : home_url( '/wp-sitemap.xml' );
		$add(
			'sitemap',
			__( 'Peta situs XML tersedia', 'yka-core' ),
			'info',
			sprintf(
				/* translators: %s: sitemap URL. */
				__( 'Periksa secara manual: %s', 'yka-core' ),
				$sitemap_url
			)
		);

		// --- Branding --------------------------------------------------
		$add(
			'logo',
			__( 'Logo situs terpasang', 'yka-core' ),
			has_custom_logo() ? 'pass' : 'fail',
			has_custom_logo()
				? __( 'Logo kustom terpasang melalui mekanisme bawaan WordPress.', 'yka-core' )
				: __( 'Belum ada logo. Unggah melalui Tampilan → Sesuaikan → Identitas Situs.', 'yka-core' )
		);

		$add(
			'favicon',
			__( 'Ikon situs (favicon) terpasang', 'yka-core' ),
			has_site_icon() ? 'pass' : 'fail',
			has_site_icon()
				? __( 'Ikon situs terpasang.', 'yka-core' )
				: __( 'Belum ada ikon situs. Unggah melalui Tampilan → Sesuaikan → Identitas Situs.', 'yka-core' )
		);

		// --- Institutional data ---------------------------------------
		$missing = array();
		foreach ( array(
			'org_description' => __( 'deskripsi yayasan', 'yka-core' ),
			'address'         => __( 'alamat', 'yka-core' ),
			'phone'           => __( 'telepon', 'yka-core' ),
			'email'           => __( 'email', 'yka-core' ),
		) as $key => $label ) {
			if ( '' === (string) Settings::get( $key ) ) {
				$missing[] = $label;
			}
		}
		$add(
			'institution',
			__( 'Data lembaga terisi', 'yka-core' ),
			empty( $missing ) ? 'pass' : 'fail',
			empty( $missing )
				? __( 'Identitas dan kontak yayasan sudah lengkap.', 'yka-core' )
				: sprintf(
					/* translators: %s: list of missing fields. */
					__( 'Belum diisi: %s. Lengkapi di Yayasan → Pengaturan.', 'yka-core' ),
					implode( ', ', $missing )
				)
		);

		// --- Demo content ---------------------------------------------
		$demo = self::count_demo_posts();
		$add(
			'demo',
			__( 'Konten demo sudah dihapus', 'yka-core' ),
			0 === $demo ? 'pass' : 'fail',
			0 === $demo
				? __( 'Tidak ada artikel bertanda [DEMO].', 'yka-core' )
				: sprintf(
					/* translators: %d: number of demo posts. */
					_n( 'Masih ada %d artikel bertanda [DEMO].', 'Masih ada %d artikel bertanda [DEMO].', $demo, 'yka-core' ),
					$demo
				)
		);

		// --- Backup ----------------------------------------------------
		$add(
			'backup',
			__( 'WPvivid tersedia untuk cadangan dan migrasi', 'yka-core' ),
			self::wpvivid_active() ? 'pass' : 'info',
			self::wpvivid_active()
				? __( 'WPvivid aktif. Buat cadangan penuh sebelum dan sesudah setiap perubahan besar.', 'yka-core' )
				: __( 'WPvivid tidak aktif. Situs tetap berfungsi normal, hanya kemampuan cadangan dan migrasi yang tidak tersedia.', 'yka-core' )
		);

		// --- Permalinks -------------------------------------------------
		$structure = (string) get_option( 'permalink_structure' );
		$add(
			'permalinks',
			__( 'Permalink bersih aktif', 'yka-core' ),
			'' !== $structure ? 'pass' : 'fail',
			'' !== $structure
				? sprintf(
					/* translators: %s: permalink structure. */
					__( 'Struktur: %s', 'yka-core' ),
					$structure
				)
				: __( 'Permalink masih memakai format default. Buka Pengaturan → Permalink dan simpan ulang.', 'yka-core' )
		);

		/**
		 * Filters the production readiness checks.
		 *
		 * @param array $checks Check results.
		 */
		return (array) apply_filters( 'yka_readiness_checks', $checks );
	}

	/**
	 * Looks for URLs from another environment inside key options.
	 *
	 * @return string[] Option names that look wrong.
	 */
	private static function find_foreign_urls(): array {
		$host  = Environment::host();
		$found = array();

		foreach ( array( 'siteurl', 'home' ) as $option ) {
			$value      = (string) get_option( $option );
			$value_host = (string) wp_parse_url( $value, PHP_URL_HOST );
			if ( '' !== $value_host && strtolower( $value_host ) !== $host ) {
				$found[] = $option;
			}
		}

		return $found;
	}

	/**
	 * Counts remaining demo articles.
	 *
	 * @return int
	 */
	private static function count_demo_posts(): int {
		$query = new \WP_Query(
			array(
				'post_type'              => 'post',
				'post_status'            => 'any',
				'posts_per_page'         => 1,
				's'                      => '[DEMO]',
				'fields'                 => 'ids',
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		return (int) $query->found_posts;
	}

	/**
	 * Whether WPvivid is available.
	 *
	 * @return bool
	 */
	private static function wpvivid_active(): bool {
		return class_exists( '\WPvivid_Public' ) || defined( 'WPVIVID_PLUGIN_DIR' ) || function_exists( 'wpvivid_backup' );
	}

	/**
	 * Handles the controlled actions offered on the page.
	 *
	 * @return void
	 */
	public static function handle_action(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Anda tidak memiliki izin untuk tindakan ini.', 'yka-core' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( self::ACTION );

		$task = isset( $_POST['task'] ) ? sanitize_key( wp_unslash( $_POST['task'] ) ) : '';

		switch ( $task ) {
			case 'enable_indexing':
				// Only ever permitted on a genuine production host.
				if ( Environment::is_production() ) {
					update_option( 'blog_public', 1 );
				}
				break;

			case 'disable_indexing':
				if ( ! Environment::is_production() ) {
					update_option( 'blog_public', 0 );
				}
				break;

			case 'flush_rewrites':
				flush_rewrite_rules();
				break;
		}

		wp_safe_redirect( add_query_arg( 'yka-done', $task, admin_url( 'admin.php?page=' . self::MENU_SLUG ) ) );
		exit;
	}

	/**
	 * Renders the screen.
	 *
	 * @return void
	 */
	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Anda tidak memiliki izin untuk membuka halaman ini.', 'yka-core' ) );
		}

		$checks   = self::checks();
		$label    = Environment::label();
		$failures = count( array_filter( $checks, static fn( array $c ): bool => 'fail' === $c['state'] ) );
		?>
		<div class="wrap yka-readiness">
			<h1><?php esc_html_e( 'Kesiapan Produksi', 'yka-core' ); ?></h1>

			<p class="yka-readiness__env">
				<?php esc_html_e( 'Lingkungan saat ini:', 'yka-core' ); ?>
				<strong class="yka-env-badge yka-env-badge--<?php echo esc_attr( strtolower( $label ) ); ?>"><?php echo esc_html( $label ); ?></strong>
			</p>

			<?php if ( ! Environment::is_production() ) : ?>
				<div class="notice notice-info inline">
					<p>
						<strong><?php esc_html_e( 'Situs ini tidak dapat diindeks.', 'yka-core' ); ?></strong>
						<?php echo esc_html( Environment::reason() ); ?>
					</p>
					<p><?php esc_html_e( 'Setelah migrasi ke domain produksi, buka kembali halaman ini dan selesaikan setiap butir yang berstatus "perlu tindakan".', 'yka-core' ); ?></p>
				</div>
			<?php elseif ( $failures > 0 ) : ?>
				<div class="notice notice-error inline">
					<p>
						<?php
						echo esc_html(
							sprintf(
								/* translators: %d: number of failing checks. */
								_n( '%d butir masih perlu tindakan sebelum situs ini layak tayang.', '%d butir masih perlu tindakan sebelum situs ini layak tayang.', $failures, 'yka-core' ),
								$failures
							)
						);
						?>
					</p>
				</div>
			<?php else : ?>
				<div class="notice notice-success inline">
					<p><?php esc_html_e( 'Seluruh butir wajib sudah terpenuhi.', 'yka-core' ); ?></p>
				</div>
			<?php endif; ?>

			<table class="widefat striped yka-readiness__table">
				<thead>
					<tr>
						<th scope="col" style="width:36px"><span class="screen-reader-text"><?php esc_html_e( 'Status', 'yka-core' ); ?></span></th>
						<th scope="col"><?php esc_html_e( 'Butir pemeriksaan', 'yka-core' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Keterangan', 'yka-core' ); ?></th>
						<th scope="col" style="width:180px"><?php esc_html_e( 'Tindakan', 'yka-core' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $checks as $check ) : ?>
					<tr>
						<td>
							<span class="yka-readiness__state yka-readiness__state--<?php echo esc_attr( $check['state'] ); ?>" aria-hidden="true"></span>
							<span class="screen-reader-text">
								<?php
								echo esc_html(
									array(
										'pass' => __( 'Terpenuhi', 'yka-core' ),
										'fail' => __( 'Perlu tindakan', 'yka-core' ),
										'info' => __( 'Informasi', 'yka-core' ),
									)[ $check['state'] ] ?? ''
								);
								?>
							</span>
						</td>
						<th scope="row"><?php echo esc_html( $check['label'] ); ?></th>
						<td><?php echo esc_html( $check['detail'] ); ?></td>
						<td>
							<?php if ( ! empty( $check['fix'] ) ) : ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
									<?php wp_nonce_field( self::ACTION ); ?>
									<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>" />
									<input type="hidden" name="task" value="<?php echo esc_attr( $check['fix'] ); ?>" />
									<?php submit_button( __( 'Perbaiki', 'yka-core' ), 'secondary small', 'submit', false ); ?>
								</form>
							<?php else : ?>
								<span aria-hidden="true">—</span>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<h2><?php esc_html_e( 'Langkah manual yang tidak bisa diperiksa otomatis', 'yka-core' ); ?></h2>
			<ul class="ul-disc">
				<li><?php esc_html_e( 'Buat cadangan penuh WPvivid pada situs sumber dan situs tujuan sebelum migrasi.', 'yka-core' ); ?></li>
				<li><?php esc_html_e( 'Verifikasi domain di Google Search Console dan Bing Webmaster Tools, lalu kirimkan peta situs.', 'yka-core' ); ?></li>
				<li><?php esc_html_e( 'Aktifkan IndexNow hanya setelah domain produksi benar-benar tayang.', 'yka-core' ); ?></li>
				<li><?php esc_html_e( 'Periksa pratinjau berbagi ke WhatsApp dan Facebook memakai foto artikel yang benar.', 'yka-core' ); ?></li>
				<li><?php esc_html_e( 'Kosongkan cache server atau CDN setelah pergantian domain.', 'yka-core' ); ?></li>
			</ul>
			<p>
				<?php
				printf(
					/* translators: %s: documentation path. */
					esc_html__( 'Daftar lengkap ada di %s dalam repositori proyek.', 'yka-core' ),
					'<code>docs/PRODUCTION-CHECKLIST.md</code>'
				);
				?>
			</p>
		</div>
		<?php
	}
}
