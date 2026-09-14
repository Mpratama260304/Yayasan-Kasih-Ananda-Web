<?php
/**
 * Development mail routing.
 *
 * @package YKA\Core
 */

declare( strict_types=1 );

namespace YKA\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Sends all WordPress mail to the local Mailpit sink outside production.
 *
 * Development must never be able to email a real parent, student or member
 * of staff. PHP's mail() transport is disabled in the container, so without
 * this module mail simply fails — which is safe, but unhelpful when testing.
 */
final class Dev_Mail {

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		if ( Environment::is_production() ) {
			return;
		}

		add_action( 'phpmailer_init', array( __CLASS__, 'configure' ) );
		add_filter( 'wp_mail_from', array( __CLASS__, 'from_address' ), 99 );
		add_filter( 'wp_mail_from_name', array( __CLASS__, 'from_name' ), 99 );
	}

	/**
	 * Points PHPMailer at Mailpit when it is reachable.
	 *
	 * @param \PHPMailer\PHPMailer\PHPMailer $mailer Mailer instance.
	 * @return void
	 */
	public static function configure( $mailer ): void {
		if ( ! is_object( $mailer ) || ! method_exists( $mailer, 'isSMTP' ) ) {
			return;
		}

		$host = defined( 'YKA_MAIL_HOST' ) ? (string) YKA_MAIL_HOST : 'mailpit';
		$port = defined( 'YKA_MAIL_PORT' ) ? (int) YKA_MAIL_PORT : 1025;

		$mailer->isSMTP();
		$mailer->Host        = $host;   // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$mailer->Port        = $port;   // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$mailer->SMTPAuth    = false;   // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$mailer->SMTPSecure  = '';      // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$mailer->SMTPAutoTLS = false;  // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$mailer->Timeout     = 5;       // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	}

	/**
	 * A sender address that clearly belongs to a development environment.
	 *
	 * @param string $from Original sender.
	 * @return string
	 */
	public static function from_address( $from ): string {
		unset( $from );
		return 'no-reply@' . ( Environment::host() ?: 'localhost' );
	}

	/**
	 * Sender name including the environment label.
	 *
	 * @param string $name Original name.
	 * @return string
	 */
	public static function from_name( $name ): string {
		unset( $name );
		return sprintf( '[%s] %s', Environment::label(), (string) Settings::get( 'org_name', get_bloginfo( 'name' ) ) );
	}
}
