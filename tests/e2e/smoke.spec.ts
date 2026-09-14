import { test, expect, Page } from '@playwright/test';

/**
 * Smoke tests for the YKA Portal.
 *
 * These check that the site works, not that it looks a particular way:
 * pages render, navigation is usable by keyboard, articles are readable,
 * nothing overflows horizontally on a phone, and a non-production build
 * can never be indexed.
 */

/** Fails the test if the page scrolls sideways. */
async function expectNoHorizontalOverflow( page: Page ) {
	const overflow = await page.evaluate( () => {
		const doc = document.documentElement;
		return doc.scrollWidth - doc.clientWidth;
	} );
	expect( overflow, 'page should not scroll horizontally' ).toBeLessThanOrEqual( 1 );
}

/** Collects browser console errors for the duration of a test. */
function collectConsoleErrors( page: Page ): string[] {
	const errors: string[] = [];
	page.on( 'console', ( message ) => {
		if ( message.type() === 'error' ) {
			errors.push( message.text() );
		}
	} );
	page.on( 'pageerror', ( error ) => errors.push( error.message ) );
	return errors;
}

test.describe( 'Homepage', () => {
	test( 'renders the newsroom', async ( { page } ) => {
		const errors = collectConsoleErrors( page );
		const response = await page.goto( '/' );

		expect( response?.status() ).toBe( 200 );
		await expect( page.locator( 'h1' ) ).toHaveCount( 1 );
		await expect( page.locator( 'header.yka-header' ) ).toBeVisible();
		await expect( page.locator( 'footer.yka-footer' ) ).toBeVisible();
		await expect( page.locator( '#yka-main' ) ).toBeVisible();

		// At least one story must be linked from the homepage.
		expect( await page.locator( '.yka-story__title a' ).count() ).toBeGreaterThan( 0 );

		await expectNoHorizontalOverflow( page );
		expect( errors ).toEqual( [] );
	} );

	test( 'declares Indonesian and offers a skip link', async ( { page } ) => {
		await page.goto( '/' );

		// The Indonesian translation of html_lang_attribute is "id"; accept
		// either that or the full id-ID tag.
		await expect( page.locator( 'html' ) ).toHaveAttribute( 'lang', /^id(-ID)?$/ );

		const skip = page.locator( '.yka-skip-link' );
		await expect( skip ).toHaveAttribute( 'href', '#yka-main' );

		await page.keyboard.press( 'Tab' );
		await expect( skip ).toBeFocused();
	} );

	test( 'emits exactly one of each critical head tag', async ( { page } ) => {
		await page.goto( '/' );

		await expect( page.locator( 'title' ) ).toHaveCount( 1 );
		expect( await page.locator( 'link[rel="canonical"]' ).count() ).toBeLessThanOrEqual( 1 );
		expect( await page.locator( 'meta[name="description"]' ).count() ).toBeLessThanOrEqual( 1 );
		expect( await page.locator( 'meta[property="og:title"]' ).count() ).toBeLessThanOrEqual( 1 );
		expect( await page.locator( 'meta[name="robots"]' ).count() ).toBeLessThanOrEqual( 1 );
	} );

	test( 'the hero image is not lazy loaded', async ( { page } ) => {
		await page.goto( '/' );

		const hero = page.locator( '.yka-hero__media img' );
		if ( await hero.count() ) {
			await expect( hero.first() ).not.toHaveAttribute( 'loading', 'lazy' );
			await expect( hero.first() ).toHaveAttribute( 'fetchpriority', 'high' );
		}
	} );
} );

test.describe( 'Navigation', () => {
	test( 'search panel opens and closes with the keyboard', async ( { page } ) => {
		await page.goto( '/' );

		const toggle = page.locator( '.yka-search-toggle' );
		const panel = page.locator( '#yka-search-panel' );

		await expect( toggle ).toHaveAttribute( 'aria-expanded', 'false' );
		await toggle.click();
		await expect( toggle ).toHaveAttribute( 'aria-expanded', 'true' );
		await expect( panel ).toBeVisible();

		await page.keyboard.press( 'Escape' );
		await expect( toggle ).toHaveAttribute( 'aria-expanded', 'false' );
	} );

	test( 'every primary navigation item is a real link', async ( { page } ) => {
		await page.goto( '/' );

		const links = page.locator( '.yka-nav__list a, .yka-mobile-nav__list a' );
		const total = await links.count();
		expect( total ).toBeGreaterThan( 0 );

		for ( let index = 0; index < total; index++ ) {
			const href = await links.nth( index ).getAttribute( 'href' );
			expect( href, 'navigation must use href, not JavaScript handlers' ).toBeTruthy();
			expect( href ).not.toBe( '#' );
		}
	} );
} );

test.describe( 'Mobile', () => {
	test.skip( ( { isMobile } ) => ! isMobile, 'mobile-only' );

	test( 'the menu button opens and closes the panel', async ( { page } ) => {
		await page.goto( '/' );

		const toggle = page.locator( '.yka-nav-toggle' );
		const panel = page.locator( '#yka-mobile-nav' );

		await expect( panel ).toBeHidden();
		await toggle.click();
		await expect( panel ).toBeVisible();
		await expect( toggle ).toHaveAttribute( 'aria-expanded', 'true' );

		await page.keyboard.press( 'Escape' );
		await expect( panel ).toBeHidden();
	} );

	test( 'key pages do not overflow sideways', async ( { page } ) => {
		for ( const path of [ '/', '/berita/', '/unit/smp-kasih-ananda-1/', '/kontak/' ] ) {
			await page.goto( path );
			await expectNoHorizontalOverflow( page );
		}
	} );
} );

test.describe( 'News archive', () => {
	test( 'lists articles and offers unit filters', async ( { page } ) => {
		const response = await page.goto( '/berita/' );
		expect( response?.status() ).toBe( 200 );

		await expect( page.locator( 'h1' ) ).toHaveCount( 1 );
		expect( await page.locator( '.yka-story__title a' ).count() ).toBeGreaterThan( 0 );
		await expect( page.locator( '.yka-filters' ) ).toBeVisible();
	} );

	test( 'unit filter links lead to a working archive', async ( { page } ) => {
		await page.goto( '/berita/' );

		const unitLink = page.locator( '.yka-filters__list a[href*="/unit/"]' ).first();
		await unitLink.click();

		await expect( page.locator( 'h1' ) ).toHaveCount( 1 );
		expect( page.url() ).toContain( '/unit/' );
	} );
} );

test.describe( 'Unit portal', () => {
	test( 'renders unit identity and its own news', async ( { page } ) => {
		const response = await page.goto( '/unit/smp-kasih-ananda-1/' );
		expect( response?.status() ).toBe( 200 );

		await expect( page.locator( 'h1' ) ).toContainText( 'SMP Kasih Ananda' );
		await expect( page.locator( '#berita-unit' ) ).toBeVisible();
	} );
} );

test.describe( 'Article', () => {
	test( 'reads correctly and exposes share tools', async ( { page } ) => {
		await page.goto( '/berita/' );
		await page.locator( '.yka-story__title a' ).first().click();

		await expect( page.locator( 'article.yka-article' ) ).toBeVisible();
		await expect( page.locator( 'h1.yka-article-header__title' ) ).toHaveCount( 1 );
		await expect( page.locator( '.yka-byline' ) ).toBeVisible();
		await expect( page.locator( 'time' ).first() ).toBeVisible();

		// Share targets are ordinary links plus a copy button.
		await expect( page.locator( '.yka-share' ) ).toBeVisible();
		expect( await page.locator( '.yka-share__list a' ).count() ).toBeGreaterThanOrEqual( 3 );
		await expect( page.locator( '[data-yka-copy-link]' ) ).toBeVisible();

		// Article images must be crawlable elements, not CSS backgrounds.
		const images = page.locator( 'article img' );
		expect( await images.count() ).toBeGreaterThan( 0 );
	} );

	test( 'links back to its education unit', async ( { page } ) => {
		await page.goto( '/berita/' );
		await page.locator( '.yka-story__title a' ).first().click();

		expect( await page.locator( 'a[href*="/unit/"]' ).count() ).toBeGreaterThan( 0 );
	} );

	test( 'headings follow a sensible order', async ( { page } ) => {
		await page.goto( '/berita/' );
		await page.locator( '.yka-story__title a' ).first().click();

		const levels = await page.evaluate( () =>
			Array.from( document.querySelectorAll( 'main h1, main h2, main h3, main h4' ) ).map(
				( node ) => Number( node.tagName.substring( 1 ) )
			)
		);

		expect( levels[ 0 ] ).toBe( 1 );
		for ( let index = 1; index < levels.length; index++ ) {
			expect(
				levels[ index ] - levels[ index - 1 ],
				`heading level jumped from h${ levels[ index - 1 ] } to h${ levels[ index ] }`
			).toBeLessThanOrEqual( 1 );
		}
	} );
} );

test.describe( 'Search', () => {
	test( 'returns results and handles no results gracefully', async ( { page } ) => {
		await page.goto( '/?s=kegiatan' );
		await expect( page.locator( 'h1' ) ).toContainText( 'kegiatan' );

		await page.goto( '/?s=zzzzqqqxyz' );
		await expect( page.locator( '.yka-empty' ) ).toBeVisible();
		// A dead end is not acceptable: offer something to do next.
		expect( await page.locator( '.yka-story__title a, .yka-filters__list a' ).count() ).toBeGreaterThan( 0 );
	} );
} );

test.describe( '404', () => {
	test( 'returns a real 404 status with useful content', async ( { page } ) => {
		const response = await page.goto( '/halaman-yang-pasti-tidak-ada/' );

		expect( response?.status() ).toBe( 404 );
		await expect( page.locator( '.yka-404__code' ) ).toContainText( '404' );
		// The header search form is collapsed by default, so scope to the page body.
		await expect( page.locator( '#yka-main form[role="search"]' ).first() ).toBeVisible();
		expect( await page.locator( 'a[href*="/unit/"]' ).count() ).toBeGreaterThan( 0 );
	} );
} );

test.describe( 'Environment safety', () => {
	test( 'a non-production build cannot be indexed', async ( { page, baseURL } ) => {
		const isProduction = ( baseURL || '' ).includes( 'yayasankasihananda.com' );
		test.skip( isProduction, 'production is expected to be indexable' );

		await page.goto( '/' );
		const robots = await page.locator( 'meta[name="robots"]' ).getAttribute( 'content' );
		expect( robots ).toContain( 'noindex' );

		const response = await page.request.get( '/robots.txt' );
		expect( await response.text() ).toContain( 'Disallow: /' );
	} );
} );
