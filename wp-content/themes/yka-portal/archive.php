<?php
/**
 * Generic archive fallback.
 *
 * @package YKA_Portal
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

get_header();

get_template_part(
	'template-parts/content/archive-body',
	null,
	array(
		'title'       => wp_strip_all_tags( get_the_archive_title() ),
		'description' => wp_strip_all_tags( get_the_archive_description() ),
		'base_url'    => '',
		'show_units'  => true,
		'show_lead'   => false,
		'empty_text'  => __( 'Belum ada artikel pada arsip ini.', 'yka-portal' ),
	)
);

get_footer();
