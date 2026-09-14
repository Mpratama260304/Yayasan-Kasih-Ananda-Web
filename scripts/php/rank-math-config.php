<?php
/**
 * Configures Rank Math for this project.
 *
 * Rank Math suppresses its frontend output until its setup wizard has been
 * completed, which would otherwise leave the site with no meta description,
 * no Open Graph tags and no structured data. This script applies the same
 * settings the wizard would, so a fresh clone is correct immediately.
 *
 * Existing values are merged, never replaced wholesale: an administrator who
 * has tuned Rank Math by hand keeps their work.
 *
 *   wp eval-file /scripts/php/rank-math-config.php
 *
 * @package YKA
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}

if ( ! defined( 'RANK_MATH_VERSION' ) && ! class_exists( '\RankMath\Helper' ) ) {
	WP_CLI::warning( 'Rank Math tidak aktif — konfigurasi dilewati.' );
	return;
}

/**
 * Merges values into a Rank Math option array.
 *
 * @param string               $option Option name.
 * @param array<string, mixed> $values Values to apply.
 * @return void
 */
function yka_rm_merge( string $option, array $values ): void {
	$current = get_option( $option, array() );
	$current = is_array( $current ) ? $current : array();
	update_option( $option, array_merge( $current, $values ) );
}

/*
------------------------------------------------------------------
	Modules
	Only what this project actually uses. Analytics, Content AI and
	Instant Indexing stay off: the first needs Google credentials, and the
	last must never be able to submit a staging URL.
	------------------------------------------------------------------ */
update_option(
	'rank_math_modules',
	array(
		'sitemap',
		'rich-snippet',
		'link-counter',
		'redirections',
		'404-monitor',
	)
);
WP_CLI::log( '  * modul aktif: sitemap, schema, internal link, redirect, 404 monitor' );

/*
------------------------------------------------------------------
	Titles, meta and schema
	------------------------------------------------------------------ */
$site_name = get_bloginfo( 'name' );

yka_rm_merge(
	'rank-math-options-titles',
	array(
		'title_separator'              => '|',
		'capitalize_titles'            => 'off',
		'twitter_card_type'            => 'summary_large_image',

		// Organisation entity — one canonical identity for the foundation.
		'knowledgegraph_type'          => 'company',
		'knowledgegraph_name'          => $site_name,
		'website_name'                 => $site_name,
		'local_business_type'          => 'EducationalOrganization',

		// Homepage.
		'homepage_title'               => '%sitename% | Portal Resmi',
		'homepage_description'         => '%sitedesc%',

		// Articles. NewsArticle matches what this site publishes:
		// documentation of activities that have already happened.
		'pt_post_title'                => '%title% | %sitename%',
		'pt_post_description'          => '%excerpt%',
		'pt_post_default_rich_snippet' => 'article',
		'pt_post_default_article_type' => 'NewsArticle',
		'pt_post_default_snippet_name' => '%seo_title%',
		'pt_post_default_snippet_desc' => '%seo_description%',
		'pt_post_custom_robots'        => 'off',
		'pt_post_link_suggestions'     => 'on',

		// Pages.
		'pt_page_title'                => '%title% | %sitename%',
		'pt_page_description'          => '%excerpt%',
		'pt_page_default_rich_snippet' => 'off',
		'pt_page_custom_robots'        => 'off',

		// Media attachments are redirected to their parent, never indexed.
		'pt_attachment_custom_robots'  => 'on',
		'pt_attachment_robots'         => array( 'noindex' ),

		// Categories and education units are valuable landing pages.
		'tax_category_title'           => '%term% | %sitename%',
		'tax_category_description'     => '%term_description%',
		'tax_category_custom_robots'   => 'off',
		'tax_category_add_meta_box'    => 'on',

		'tax_yka_unit_title'           => '%term% | %sitename%',
		'tax_yka_unit_description'     => '%term_description%',
		'tax_yka_unit_custom_robots'   => 'off',
		'tax_yka_unit_add_meta_box'    => 'on',

		// Thin tag archives are not.
		'tax_post_tag_custom_robots'   => 'on',
		'tax_post_tag_robots'          => array( 'noindex' ),

		'noindex_empty_taxonomies'     => 'on',
		'noindex_archive_subpages'     => 'off',

		// One generic author on an institutional site produces duplicate
		// archives; date archives add nothing over the news archive.
		'disable_author_archives'      => 'on',
		'disable_date_archives'        => 'on',
	)
);
WP_CLI::log( '  * judul, deskripsi, dan schema dikonfigurasi' );

/*
------------------------------------------------------------------
	Sitemap
	------------------------------------------------------------------ */
yka_rm_merge(
	'rank-math-options-sitemap',
	array(
		'items_per_page'         => 200,
		'include_images'         => 'on',
		'include_featured_image' => 'on',

		'pt_post_sitemap'        => 'on',
		'pt_page_sitemap'        => 'on',
		'pt_attachment_sitemap'  => 'off',

		'tax_category_sitemap'   => 'on',
		'tax_post_tag_sitemap'   => 'off',
		'tax_yka_unit_sitemap'   => 'on',

		'authors_sitemap'        => 'off',
		'html_sitemap'           => 'off',

		// Never ping search engines from a non-production environment. The
		// YKA Core environment guard enforces this at runtime too.
		'ping_search_engines'    => 'off',
	)
);
WP_CLI::log( '  * peta situs: berita, halaman, kategori, unit pendidikan' );

/*
------------------------------------------------------------------
	General
	------------------------------------------------------------------ */
yka_rm_merge(
	'rank-math-options-general',
	array(
		'strip_category_base'                => 'off',
		'attachment_redirect_urls'           => 'on',
		'nofollow_external_links'            => 'off',
		'new_window_external_links'          => 'on',
		'redirections_debug'                 => 'off',
		'usage_tracking'                     => 'off',
		'console_caching_control'            => 90,
		'setup_mode'                         => 'advanced',
		'link_builder_links_per_page'        => 7,

		// Breadcrumbs live on the General tab. Rank Math owns both the
		// visible trail and the BreadcrumbList schema, so the theme renders
		// its markup rather than emitting a second, competing one.
		'breadcrumbs'                        => 'on',
		'breadcrumbs_separator'              => '›',
		'breadcrumbs_home'                   => 'on',
		'breadcrumbs_home_label'             => 'Beranda',
		'breadcrumbs_search_format'          => 'Hasil pencarian untuk %search_query%',
		'breadcrumbs_404_label'              => 'Halaman tidak ditemukan',
		'breadcrumbs_hide_post_title'        => 'off',
		'breadcrumbs_remove_postpage_prefix' => 'on',
	)
);
WP_CLI::log( '  * breadcrumb berbahasa Indonesia' );

/*
------------------------------------------------------------------
	Mark the wizard as done so the frontend output is enabled
	------------------------------------------------------------------ */
update_option( 'rank_math_is_configured', true );
update_option( 'rank_math_wizard_completed', true );
update_option( 'rank_math_registration_skip', true );
update_option( 'rank_math_view_modes', array() );
delete_option( 'rank_math_pro_notice' );

// Fallback share image, so pages without a featured image of their own still
// produce a usable WhatsApp or Facebook preview.
if ( class_exists( '\YKA\Core\Settings' ) ) {
	\YKA\Core\Settings::sync_share_image();
	WP_CLI::log( '  * gambar berbagi bawaan disinkronkan dari pengaturan YKA' );
}

// Rank Math caches its options in a singleton; force a rebuild on the next
// request rather than relying on this CLI process.
delete_transient( 'rank_math_sitemap_cache' );
wp_cache_flush();

WP_CLI::success( 'Rank Math dikonfigurasi untuk portal Yayasan Kasih Ananda.' );
