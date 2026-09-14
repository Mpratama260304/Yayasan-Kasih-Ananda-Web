<?php
/**
 * Development configuration for the YKA WordPress container.
 *
 * Loaded from wp-config.php through a single `require_once`. The official
 * WordPress image passes extra configuration as a string that it runs
 * through eval(); keeping the real logic in this file means it is version
 * controlled, readable, and linted like the rest of the project.
 *
 * Never loaded in production. Production hosting supplies its own
 * wp-config.php, and none of this applies there.
 *
 * @package YKA
 */

defined( 'ABSPATH' ) || defined( 'WP_INSTALLING' ) || true;

/* -----------------------------------------------------------------
   Environment
   ----------------------------------------------------------------- */

if ( ! defined( 'WP_ENVIRONMENT_TYPE' ) ) {
	define( 'WP_ENVIRONMENT_TYPE', getenv( 'WP_ENVIRONMENT_TYPE' ) ?: 'local' );
}

/* -----------------------------------------------------------------
   Site address
   -----------------------------------------------------------------
   The container is always reached through a proxy, and the two proxies
   that matter behave differently:

     docker publish   Host: localhost:8080   no forwarded headers
     Codespaces       Host: localhost:8080   X-Forwarded-Proto: https
                                             X-Forwarded-Host: <name>.app.github.dev

   Reading HTTP_HOST alone therefore yields localhost:8080 for every
   request, including one from a browser on the forwarded URL — which makes
   every stylesheet, script and image point at the developer's own machine
   and fail with ERR_CONNECTION_REFUSED.

   Resolution order:

     1. X-Forwarded-Host, when it passes the allow-list.
     2. YKA_PUBLIC_URL, when the request is plainly tunnelled (forwarded
        as HTTPS but claiming a loopback host) yet no forwarded host was
        supplied. scripts/lib.sh sets this to the Codespaces URL.
     3. Host, for direct access from curl, Playwright, or a browser on the
        same machine.

   Header values are validated against an allow-list so a spoofed header
   cannot point the site somewhere else, even in development.
   ----------------------------------------------------------------- */

if ( 'production' !== WP_ENVIRONMENT_TYPE ) {

	/**
	 * Whether a hostname is one this development site may answer on.
	 *
	 * @param string $host Hostname, optionally with a port.
	 * @return bool
	 */
	$yka_host_allowed = static function ( string $host ): bool {
		if ( '' === $host || strlen( $host ) > 255 ) {
			return false;
		}
		if ( ! preg_match( '/^[A-Za-z0-9.\-]+(:\d{1,5})?$/', $host ) ) {
			return false;
		}

		$name = strtolower( (string) strtok( $host, ':' ) );

		if ( in_array( $name, array( 'localhost', '127.0.0.1', '0.0.0.0' ), true ) ) {
			return true;
		}

		foreach ( array( '.app.github.dev', '.githubpreview.dev', '.us.ci', 'yayasankasihananda.com' ) as $suffix ) {
			if ( str_ends_with( $name, $suffix ) ) {
				return true;
			}
		}

		return false;
	};

	/**
	 * Whether a hostname refers to this machine.
	 *
	 * @param string $host Hostname, optionally with a port.
	 * @return bool
	 */
	$yka_is_loopback = static function ( string $host ): bool {
		return in_array(
			strtolower( (string) strtok( $host, ':' ) ),
			array( 'localhost', '127.0.0.1', '0.0.0.0' ),
			true
		);
	};

	// A forwarded header may carry a chain; the first entry faces the client.
	$yka_first = static function ( string $header ): string {
		return empty( $_SERVER[ $header ] )
			? ''
			: trim( explode( ',', (string) $_SERVER[ $header ] )[0] );
	};

	$yka_request_host   = $yka_first( 'HTTP_HOST' );
	$yka_forwarded_host = $yka_first( 'HTTP_X_FORWARDED_HOST' );
	$yka_forwarded_prot = strtolower( $yka_first( 'HTTP_X_FORWARDED_PROTO' ) );

	$yka_url = '';

	if ( '' !== $yka_forwarded_host && $yka_host_allowed( $yka_forwarded_host ) ) {
		$yka_scheme = ( 'https' === $yka_forwarded_prot || str_contains( $yka_forwarded_host, '.app.github.dev' ) ) ? 'https' : 'http';
		$yka_url    = $yka_scheme . '://' . $yka_forwarded_host;
	} elseif ( 'https' === $yka_forwarded_prot && $yka_is_loopback( $yka_request_host ) ) {
		// wp-config runs before WordPress loads, so only plain PHP is available.
		$yka_public = (string) ( getenv( 'YKA_PUBLIC_URL' ) ?: '' );
		$yka_public_host = '' === $yka_public ? '' : (string) parse_url( $yka_public, PHP_URL_HOST );

		if ( '' !== $yka_public_host && $yka_host_allowed( $yka_public_host ) ) {
			$yka_url = rtrim( $yka_public, '/' );
		}
	} elseif ( '' !== $yka_request_host && $yka_host_allowed( $yka_request_host ) ) {
		$yka_secure = ( ! empty( $_SERVER['HTTPS'] ) && 'off' !== $_SERVER['HTTPS'] ) || 'https' === $yka_forwarded_prot;
		$yka_url    = ( $yka_secure ? 'https://' : 'http://' ) . $yka_request_host;
	}

	if ( '' !== $yka_url ) {
		if ( str_starts_with( $yka_url, 'https://' ) ) {
			$_SERVER['HTTPS'] = 'on';
		}

		define( 'WP_HOME', $yka_url );
		define( 'WP_SITEURL', $yka_url );
	}

	unset(
		$yka_host_allowed,
		$yka_is_loopback,
		$yka_first,
		$yka_request_host,
		$yka_forwarded_host,
		$yka_forwarded_prot,
		$yka_scheme,
		$yka_secure,
		$yka_public,
		$yka_public_host,
		$yka_url
	);
}

/* -----------------------------------------------------------------
   Debugging
   ----------------------------------------------------------------- */

define( 'WP_DEBUG_LOG', filter_var( getenv( 'WP_DEBUG_LOG' ) ?: 'true', FILTER_VALIDATE_BOOLEAN ) );
define( 'WP_DEBUG_DISPLAY', filter_var( getenv( 'WP_DEBUG_DISPLAY' ) ?: 'false', FILTER_VALIDATE_BOOLEAN ) );
@ini_set( 'display_errors', '0' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.PHP.IniSet.display_errors_Disallowed -- errors go to the log, never to the page.

/* -----------------------------------------------------------------
   Hardening and limits
   ----------------------------------------------------------------- */

define( 'DISALLOW_FILE_EDIT', true );
define( 'AUTOMATIC_UPDATER_DISABLED', true );
define( 'WP_MEMORY_LIMIT', '512M' );
define( 'WP_MAX_MEMORY_LIMIT', '512M' );
define( 'WP_POST_REVISIONS', 12 );
define( 'EMPTY_TRASH_DAYS', 30 );

/* -----------------------------------------------------------------
   Mail
   -----------------------------------------------------------------
   Routed to Mailpit by YKA Core. Nothing in development can reach a real
   inbox: PHP's sendmail transport is disabled in the container too.
   ----------------------------------------------------------------- */

define( 'YKA_MAIL_HOST', getenv( 'YKA_MAIL_HOST' ) ?: 'mailpit' );
define( 'YKA_MAIL_PORT', (int) ( getenv( 'YKA_MAIL_PORT' ) ?: 1025 ) );
