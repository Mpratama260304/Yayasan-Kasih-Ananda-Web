<?php
/**
 * Category archive.
 *
 * @package YKA_Portal
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

get_header();

$yka_term = get_queried_object();
$yka_desc = $yka_term instanceof WP_Term ? wp_strip_all_tags( (string) $yka_term->description ) : '';

get_template_part(
	'template-parts/content/archive-body',
	null,
	array(
		'title'       => $yka_term instanceof WP_Term ? $yka_term->name : __( 'Kategori', 'yka-portal' ),
		'description' => $yka_desc,
		'base_url'    => $yka_term instanceof WP_Term ? (string) get_category_link( $yka_term->term_id ) : '',
		'show_units'  => true,
		'show_lead'   => false,
		'empty_text'  => __( 'Belum ada artikel dalam kategori ini.', 'yka-portal' ),
	)
);

get_footer();
