<?php
/**
 * News archive — the page assigned as "Posts page" (Berita & Kegiatan).
 *
 * @package YKA_Portal
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

get_header();

$yka_posts_page = (int) get_option( 'page_for_posts' );
$yka_title      = $yka_posts_page ? get_the_title( $yka_posts_page ) : __( 'Berita &amp; Kegiatan', 'yka-portal' );
$yka_intro      = $yka_posts_page ? yka_excerpt( $yka_posts_page, 40 ) : '';

if ( '' === $yka_intro ) {
	$yka_intro = __( 'Dokumentasi kegiatan, pengumuman, dan capaian dari Yayasan Kasih Ananda beserta unit pendidikan SD, SMP, dan SMK.', 'yka-portal' );
}

get_template_part(
	'template-parts/content/archive-body',
	null,
	array(
		'title'       => $yka_title,
		'description' => $yka_intro,
		'base_url'    => $yka_posts_page ? (string) get_permalink( $yka_posts_page ) : home_url( '/' ),
		'show_units'  => true,
		'show_lead'   => true,
		'empty_text'  => __( 'Belum ada artikel yang diterbitkan.', 'yka-portal' ),
	)
);

get_footer();
