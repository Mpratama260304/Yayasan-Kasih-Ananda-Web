import { test } from '@playwright/test';

/**
 * Visual capture helper.
 *
 * Not an assertion suite — it writes full-page screenshots to
 * tests/artifacts/ so a human can review layout at real breakpoints.
 *
 *   npm run shots
 */

const PAGES: Array< { name: string; path: string } > = [
	{ name: 'beranda', path: '/' },
	{ name: 'berita', path: '/berita/' },
	{ name: 'unit-smp', path: '/unit/smp-kasih-ananda-1/' },
	{ name: 'kontak', path: '/kontak/' },
	{ name: 'pencarian', path: '/?s=kegiatan' },
	{ name: '404', path: '/halaman-yang-pasti-tidak-ada/' },
];

const WIDTHS = [ 375, 768, 1440 ];

for ( const width of WIDTHS ) {
	for ( const item of PAGES ) {
		test( `${ item.name } @ ${ width }px`, async ( { page } ) => {
			await page.setViewportSize( { width, height: 900 } );
			await page.goto( item.path, { waitUntil: 'networkidle' } );
			await page.screenshot( {
				path: `tests/artifacts/${ item.name }-${ width }.png`,
				fullPage: true,
			} );
		} );
	}
}

test( 'artikel @ 1440px', async ( { page } ) => {
	await page.setViewportSize( { width: 1440, height: 900 } );
	await page.goto( '/berita/', { waitUntil: 'networkidle' } );
	await page.locator( '.yka-story__title a' ).first().click();
	await page.waitForLoadState( 'networkidle' );
	await page.screenshot( { path: 'tests/artifacts/artikel-1440.png', fullPage: true } );
} );

test( 'artikel @ 375px', async ( { page } ) => {
	await page.setViewportSize( { width: 375, height: 900 } );
	await page.goto( '/berita/', { waitUntil: 'networkidle' } );
	await page.locator( '.yka-story__title a' ).first().click();
	await page.waitForLoadState( 'networkidle' );
	await page.screenshot( { path: 'tests/artifacts/artikel-375.png', fullPage: true } );
} );
