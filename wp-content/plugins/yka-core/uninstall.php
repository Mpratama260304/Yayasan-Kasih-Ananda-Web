<?php
/**
 * Uninstall routine for YKA Core.
 *
 * Institutional data is deliberately preserved. Removing this plugin must
 * never delete articles, education unit terms, or the foundation's contact
 * details — an administrator who deactivates a plugin to troubleshoot
 * something should not lose the organisation's records.
 *
 * Only the plugin's own bookkeeping options and the optional custom role
 * are cleaned up here.
 *
 * @package YKA\Core
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'yka_core_default_terms_version' );
delete_option( 'yka_core_roles_version' );

if ( function_exists( 'remove_role' ) ) {
	remove_role( 'yka_dokumentator' );
}

// Left intentionally in place:
// - option `yka_settings`          (foundation identity and contacts)
// - taxonomy `yka_unit` and terms  (education units)
// - all post meta under `yka_*`    (activity dates, credits, captions)
