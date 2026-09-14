<?php
/**
 * The Dokumentator role.
 *
 * @package YKA\Core
 */

declare( strict_types=1 );

namespace YKA\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Adds an optional role for staff who document activities but do not
 * administer the site.
 *
 * Native WordPress roles are untouched. Using this role is a policy choice
 * for the organisation, not a requirement — see docs/EDITORIAL-GUIDE.md.
 */
final class Roles {

	public const ROLE = 'yka_dokumentator';

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'admin_init', array( __CLASS__, 'maybe_add_role' ) );
	}

	/**
	 * Capabilities granted to a documentation contributor.
	 *
	 * Close to Author, minus the ability to publish or delete published
	 * work, so an editor reviews before anything goes live.
	 *
	 * @return array<string, bool>
	 */
	private static function capabilities(): array {
		return array(
			'read'                   => true,
			'upload_files'           => true,
			'edit_posts'             => true,
			'edit_published_posts'   => false,
			'delete_posts'           => true,
			'publish_posts'          => false,
			'delete_published_posts' => false,
			'read_private_posts'     => false,
			'edit_others_posts'      => false,
			'assign_terms'           => true,
		);
	}

	/**
	 * Creates the role.
	 *
	 * @return void
	 */
	public static function add_role(): void {
		remove_role( self::ROLE );
		add_role( self::ROLE, __( 'Dokumentator', 'yka-core' ), self::capabilities() );
		update_option( 'yka_core_roles_version', VERSION, false );
	}

	/**
	 * Creates the role once per plugin version.
	 *
	 * @return void
	 */
	public static function maybe_add_role(): void {
		if ( get_option( 'yka_core_roles_version' ) === VERSION && get_role( self::ROLE ) ) {
			return;
		}
		self::add_role();
	}
}
