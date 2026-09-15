<?php
/**
 * Router for PHP's built-in web server (development only).
 *
 * Serves existing files/assets as-is and routes every other request to
 * WordPress's front controller, emulating the mod_rewrite rules.
 */
$root = __DIR__;
$uri  = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );

if ( '/' !== $uri && file_exists( $root . $uri ) ) {
	return false; // Let the built-in server serve the real file, dir index, or PHP script.
}

require $root . '/index.php';
