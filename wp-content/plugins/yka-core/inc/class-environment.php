<?php
/**
 * Environment guard: keeps anything that is not production out of search results.
 *
 * @package YKA\Core
 */

declare( strict_types=1 );

namespace YKA\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Determines whether the current installation is production, and enforces
 * `noindex` everywhere else.
 *
 * The single most damaging launch mistake on a project like this is a
 * staging clone that keeps `noindex` in production — or a staging site that
 * gets indexed. Both are handled here at runtime rather than by a stored
 * database flag that silently travels with a migration.
 */
final class Environment {

	/**
	 * Hostnames allowed to be indexed. Nothing else ever is.
	 */
	public const PRODUCTION_HOSTS = array(
		'yayasankasihananda.com',
		'www.yayasankasihananda.com',
	);

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		if ( ! self::is_production() ) {
			// Priority 11 keeps this after Rank Math so it always wins.
			add_filter( 'wp_robots', array( __CLASS__, 'force_noindex' ), 99 );
			add_filter( 'rank_math/frontend/robots', array( __CLASS__, 'force_rank_math_noindex' ), 99 );
			add_filter( 'rank_math/sitemap/enable_caching', '__return_false' );
			add_filter( 'rank_math/indexnow/enable', '__return_false', 99 );
			add_filter( 'rank_math/sitemap/ping_search_engines', '__return_false', 99 );
			add_action( 'admin_notices', array( __CLASS__, 'admin_notice' ) );
		}

		add_action( 'admin_bar_menu', array( __CLASS__, 'admin_bar_badge' ), 999 );
		add_action( 'admin_head', array( __CLASS__, 'badge_styles' ) );
		add_action( 'wp_head', array( __CLASS__, 'badge_styles' ) );
		add_filter( 'robots_txt', array( __CLASS__, 'filter_robots_txt' ), 99, 2 );
	}

	/**
	 * Current hostname, lowercased and without a port.
	 *
	 * @return string
	 */
	public static function host(): string {
		$host = wp_parse_url( home_url(), PHP_URL_HOST );
		if ( ! is_string( $host ) || '' === $host ) {
			return '';
		}
		return strtolower( $host );
	}

	/**
	 * True only when both the declared environment type and the hostname say
	 * production. Either one alone is not enough.
	 *
	 * @return bool
	 */
	public static function is_production(): bool {
		$declared = function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production';
		$host     = self::host();

		$host_is_production = in_array( $host, self::PRODUCTION_HOSTS, true );

		/**
		 * Allows an additional production hostname without editing the plugin.
		 *
		 * @param bool   $host_is_production Whether the host is a production host.
		 * @param string $host               Current hostname.
		 */
		$host_is_production = (bool) apply_filters( 'yka_host_is_production', $host_is_production, $host );

		return 'production' === $declared && $host_is_production;
	}

	/**
	 * Short label for the current environment.
	 *
	 * @return string
	 */
	public static function label(): string {
		if ( self::is_production() ) {
			return 'PRODUCTION';
		}

		$declared = function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production';
		$host     = self::host();

		if ( 'local' === $declared || str_contains( $host, 'localhost' ) || str_contains( $host, '.app.github.dev' ) ) {
			return 'LOCAL';
		}

		return 'STAGING';
	}

	/**
	 * Why the current environment is not treated as production.
	 *
	 * @return string
	 */
	public static function reason(): string {
		$declared = function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production';

		if ( 'production' !== $declared ) {
			return sprintf(
				/* translators: %s: environment type constant value. */
				__( 'WP_ENVIRONMENT_TYPE bernilai "%s", bukan "production".', 'yka-core' ),
				$declared
			);
		}

		return sprintf(
			/* translators: %s: current hostname. */
			__( 'Domain saat ini (%s) belum termasuk domain produksi resmi.', 'yka-core' ),
			self::host()
		);
	}

	/**
	 * Forces noindex on every non-production response.
	 *
	 * @param array<string, mixed> $robots Robots directives.
	 * @return array<string, mixed>
	 */
	public static function force_noindex( array $robots ): array {
		$robots['noindex']  = true;
		$robots['nofollow'] = true;
		unset( $robots['index'], $robots['follow'], $robots['max-image-preview'], $robots['max-snippet'] );
		return $robots;
	}

	/**
	 * Same, expressed the way Rank Math stores its directives.
	 *
	 * The array is replaced rather than merged: leaving `max-image-preview`
	 * or a second `nofollow` behind produces a malformed directive like
	 * "nofollow, noindex, nofollow, max-snippet:-1".
	 *
	 * @param array<string, string> $robots Rank Math robots array.
	 * @return array<string, string>
	 */
	public static function force_rank_math_noindex( $robots ): array {
		unset( $robots );

		return array(
			'index'  => 'noindex',
			'follow' => 'nofollow',
		);
	}

	/**
	 * Blocks all crawling in robots.txt outside production.
	 *
	 * @param string $output    Robots.txt body.
	 * @param bool   $is_public Whether the blog is public.
	 * @return string
	 */
	public static function filter_robots_txt( $output, $is_public ): string {
		unset( $is_public );

		if ( self::is_production() ) {
			return (string) $output;
		}

		return "# Lingkungan non-produksi Yayasan Kasih Ananda.\n"
			. "# Situs ini tidak boleh diindeks.\n"
			. "User-agent: *\nDisallow: /\n";
	}

	/**
	 * Adds the environment badge to the admin bar for logged-in staff only.
	 *
	 * @param \WP_Admin_Bar $bar Admin bar instance.
	 * @return void
	 */
	public static function admin_bar_badge( $bar ): void {
		if ( ! $bar instanceof \WP_Admin_Bar || ! is_user_logged_in() || ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		$label = self::label();
		$bar->add_node(
			array(
				'id'     => 'yka-environment',
				'title'  => '<span class="yka-env-badge yka-env-badge--' . esc_attr( strtolower( $label ) ) . '">' . esc_html( $label ) . '</span>',
				'href'   => current_user_can( 'manage_options' ) ? admin_url( 'admin.php?page=' . Readiness::MENU_SLUG ) : false,
				'parent' => 'top-secondary',
				'meta'   => array( 'title' => self::is_production() ? __( 'Situs produksi', 'yka-core' ) : self::reason() ),
			)
		);
	}

	/**
	 * Badge styles. Inline because it is a handful of rules on the admin bar
	 * only, and loading a stylesheet for it would cost a request.
	 *
	 * @return void
	 */
	public static function badge_styles(): void {
		if ( ! is_admin_bar_showing() || ! current_user_can( 'edit_posts' ) ) {
			return;
		}
		?>
<style id="yka-env-badge-css">
#wpadminbar .yka-env-badge{display:inline-block;padding:0 8px;border-radius:3px;font-weight:700;font-size:11px;letter-spacing:.06em;line-height:22px}
#wpadminbar .yka-env-badge--local{background:#2b7e54;color:#fff}
#wpadminbar .yka-env-badge--staging{background:#b8860b;color:#fff}
#wpadminbar .yka-env-badge--production{background:#8b1d1d;color:#fff}
</style>
		<?php
	}

	/**
	 * Reminds administrators why the site is not indexable.
	 *
	 * @return void
	 */
	public static function admin_notice(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || ! in_array( $screen->id, array( 'dashboard', 'options-reading', 'toplevel_page_' . Settings::MENU_SLUG ), true ) ) {
			return;
		}

		printf(
			'<div class="notice notice-info"><p><strong>%s</strong> %s <a href="%s">%s</a></p></div>',
			esc_html( sprintf( '[%s]', self::label() ) ),
			esc_html(
				sprintf(
				/* translators: %s: reason the site is not production. */
					__( 'Situs ini sengaja tidak dapat diindeks mesin pencari. %s', 'yka-core' ),
					self::reason()
				)
			),
			esc_url( admin_url( 'admin.php?page=' . Readiness::MENU_SLUG ) ),
			esc_html__( 'Lihat kesiapan produksi', 'yka-core' )
		);
	}
}
