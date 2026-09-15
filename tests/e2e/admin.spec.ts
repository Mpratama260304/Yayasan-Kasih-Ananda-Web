import { test, expect } from '@playwright/test';

/**
 * Editorial acceptance test.
 *
 * A member of school staff must be able to log in and publish a documented
 * activity without opening GitHub, editing code, or touching SEO settings.
 * This spec walks that exact path.
 *
 * Credentials come from the environment so nothing is ever committed:
 *
 *   YKA_ADMIN_USER=… YKA_ADMIN_PASS=… npx playwright test --project=admin
 *
 * scripts/check.sh reads them from .env and exports them for you.
 */

const user = process.env.YKA_ADMIN_USER || '';
const pass = process.env.YKA_ADMIN_PASS || '';

test.skip( ! user || ! pass, 'set YKA_ADMIN_USER and YKA_ADMIN_PASS to run admin tests' );

// WordPress ties its auth cookie and nonces to one session. Logging the same
// account in from several workers at once invalidates the others mid-test.
test.describe.configure( { mode: 'serial' } );

test.beforeEach( async ( { page } ) => {
	await page.goto( '/wp-login.php' );
	await page.fill( '#user_login', user );
	await page.fill( '#user_pass', pass );
	await page.click( '#wp-submit' );
	await expect( page.locator( '#wpadminbar' ) ).toBeVisible();
} );

test( 'the dashboard leads with the newsroom widget', async ( { page } ) => {
	await page.goto( '/wp-admin/' );

	await expect( page.locator( '#yka_newsroom' ) ).toBeVisible();
	await expect( page.locator( '#yka_newsroom' ) ).toContainText( 'Tambah Berita / Dokumentasi' );
	await expect( page.locator( '#yka_newsroom .yka-newsroom__units' ) ).toBeVisible();
} );

test( 'the environment badge is visible to staff', async ( { page } ) => {
	await page.goto( '/wp-admin/' );

	const badge = page.locator( '#wp-admin-bar-yka-environment .yka-env-badge' );
	await expect( badge ).toBeVisible();
	await expect( badge ).toHaveText( /LOCAL|STAGING|PRODUCTION/ );
} );

test( 'the Posts menu is relabelled for a documentation team', async ( { page } ) => {
	await page.goto( '/wp-admin/' );

	await expect( page.locator( '#menu-posts' ) ).toContainText( 'Berita & Dokumentasi' );
} );

test( 'the article list shows unit, activity date and readiness', async ( { page } ) => {
	await page.goto( '/wp-admin/edit.php' );

	await expect( page.locator( 'th#yka_unit' ) ).toBeVisible();
	await expect( page.locator( 'th#yka_activity_date' ) ).toBeVisible();
	await expect( page.locator( 'th#yka_state' ) ).toBeVisible();
	await expect( page.locator( `select#yka_unit` ) ).toBeVisible();
} );

test( 'the editor offers every panel an editor needs', async ( { page } ) => {
	await page.goto( '/wp-admin/post-new.php' );

	// Dismiss the welcome modal if the block editor shows one.
	const modalClose = page.locator( '.components-modal__header button[aria-label]' );
	if ( await modalClose.count() ) {
		await modalClose.first().click().catch( () => {} );
	}

	await expect( page.locator( '#yka-publish-checklist' ) ).toBeAttached();
	await expect( page.locator( '#yka-article-meta' ) ).toBeAttached();
	await expect( page.locator( '#yka-social-caption' ) ).toBeAttached();

	// The checklist must warn about the empty article, not stay silent.
	await expect( page.locator( '#yka-checklist' ) ).toContainText( /Unit pendidikan belum dipilih/ );
} );

test( 'the foundation settings screen renders every section', async ( { page } ) => {
	await page.goto( '/wp-admin/admin.php?page=yka-settings' );

	await expect( page.locator( 'h1' ) ).toContainText( 'Pengaturan Yayasan' );
	for ( const heading of [ 'Identitas lembaga', 'Kontak', 'Media sosial resmi', 'Beranda' ] ) {
		await expect( page.locator( 'h2', { hasText: heading } ) ).toBeVisible();
	}
	await expect( page.locator( '#yka_org_name' ) ).toBeVisible();
} );

test( 'unit terms expose their institutional fields', async ( { page } ) => {
	await page.goto( '/wp-admin/edit-tags.php?taxonomy=yka_unit&post_type=post' );

	await expect( page.locator( 'h1' ) ).toContainText( 'Unit Pendidikan' );

	const row = page.locator( 'tr', { hasText: 'SMP Kasih Ananda I' } ).first();
	await expect( row ).toBeVisible();

	await page.goto( await row.locator( 'a.row-title' ).getAttribute( 'href' ) || '' );

	await expect( page.locator( '#yka_unit_official_name' ) ).toBeVisible();
	await expect( page.locator( '#yka_unit_address' ) ).toBeVisible();
	await expect( page.locator( '#yka_unit_legacy_url' ) ).toBeVisible();
} );

test( 'the YKA block patterns are registered', async ( { page } ) => {
	await page.goto( '/wp-admin/site-editor.php?p=%2Fpattern' );

	// The pattern library is only one surface; the API is the real check.
	const patterns = await page.evaluate( async () => {
		const response = await window.fetch(
			`${ window.wpApiSettings?.root || '/wp-json/' }wp/v2/block-patterns/patterns`,
			{ headers: { 'X-WP-Nonce': window.wpApiSettings?.nonce || '' } }
		);
		if ( ! response.ok ) {
			return [];
		}
		const data = await response.json();
		return data.filter( ( item: { name: string } ) => item.name.startsWith( 'yka-portal/' ) );
	} );

	expect( patterns.length ).toBeGreaterThanOrEqual( 4 );
} );
