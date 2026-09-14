import { defineConfig, devices } from '@playwright/test';

/**
 * Playwright is a development-time tool only. The production site has no
 * Node dependency; these tests run against the Docker development stack
 * (or any URL passed through YKA_BASE_URL).
 */
const baseURL = process.env.YKA_BASE_URL || 'http://localhost:8080';

export default defineConfig( {
	testDir: './tests/e2e',
	outputDir: './tests/test-results',
	fullyParallel: true,
	forbidOnly: !! process.env.CI,
	retries: process.env.CI ? 1 : 0,
	workers: process.env.CI ? 2 : undefined,
	reporter: [
		[ 'list' ],
		[ 'html', { outputFolder: './tests/playwright-report', open: 'never' } ],
	],
	timeout: 30_000,
	expect: { timeout: 10_000 },
	use: {
		baseURL,
		ignoreHTTPSErrors: true,
		trace: 'retain-on-failure',
		screenshot: 'only-on-failure',
		locale: 'id-ID',
		timezoneId: 'Asia/Jakarta',
	},
	projects: [
		{
			name: 'desktop',
			use: { ...devices[ 'Desktop Chrome' ], viewport: { width: 1440, height: 900 } },
			testIgnore: [ /screenshots\.spec\.ts/, /admin\.spec\.ts/ ],
		},
		{
			name: 'mobile',
			use: { ...devices[ 'Pixel 7' ] },
			testIgnore: [ /screenshots\.spec\.ts/, /admin\.spec\.ts/ ],
		},
		{
			name: 'admin',
			use: { ...devices[ 'Desktop Chrome' ], viewport: { width: 1440, height: 1000 } },
			testMatch: /admin\.spec\.ts/,
		},
		{
			name: 'screenshots',
			use: { ...devices[ 'Desktop Chrome' ], viewport: { width: 1440, height: 900 } },
			testMatch: /screenshots\.spec\.ts/,
		},
	],
} );
