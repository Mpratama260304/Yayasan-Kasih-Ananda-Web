<?php
/**
 * Fallback template.
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
		'title'       => is_home() ? __( 'Berita &amp; Kegiatan', 'yka-portal' ) : wp_strip_all_tags( get_the_archive_title() ),
		'description' => '',
		'base_url'    => '',
		'show_units'  => true,
		'show_lead'   => is_home(),
		'empty_text'  => __( 'Belum ada artikel yang diterbitkan.', 'yka-portal' ),
	)
);

get_footer();
