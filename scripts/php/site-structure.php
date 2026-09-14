<?php
/**
 * Creates the institutional page structure, categories and navigation menus.
 *
 * Run through WP-CLI: `wp eval-file /scripts/php/site-structure.php`
 * Idempotent — existing content is matched by slug and never overwritten.
 *
 * This is real site structure, not demo content. Demo articles live in
 * scripts/php/seed.php and are removed before production.
 *
 * @package YKA
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}

/**
 * Finds a page by slug or creates it.
 *
 * Matching is done on slug *and* parent: `get_page_by_path()` only resolves
 * full hierarchical paths, so a child page would otherwise be recreated on
 * every run.
 *
 * @param array $args Page definition.
 * @return int Page ID.
 */
function yka_structure_page( array $args ) {
	$parent_id = (int) ( $args['parent'] ?? 0 );

	$existing = get_posts(
		array(
			'post_type'        => 'page',
			'name'             => $args['slug'],
			'post_parent'      => $parent_id,
			'post_status'      => 'any',
			'numberposts'      => 1,
			'suppress_filters' => false,
		)
	);

	if ( $existing ) {
		return (int) $existing[0]->ID;
	}

	$id = wp_insert_post(
		array(
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'post_title'     => $args['title'],
			'post_name'      => $args['slug'],
			'post_content'   => $args['content'] ?? '',
			'post_parent'    => $parent_id,
			'menu_order'     => $args['order'] ?? 0,
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
		),
		true
	);

	if ( is_wp_error( $id ) ) {
		WP_CLI::warning( sprintf( 'Page "%s": %s', $args['title'], $id->get_error_message() ) );
		return 0;
	}

	if ( ! empty( $args['template'] ) ) {
		update_post_meta( $id, '_wp_page_template', $args['template'] );
	}

	WP_CLI::log( sprintf( '  + page: %s (/%s/)', $args['title'], $args['slug'] ) );
	return (int) $id;
}

/**
 * Builds a placeholder block body that is obviously unfinished.
 *
 * Real institutional copy must be supplied by Yayasan Kasih Ananda.
 *
 * @param string $heading Section heading.
 * @param string $note    What the organisation still needs to provide.
 * @return string Block markup.
 */
function yka_placeholder_body( $heading, $note ) {
	return sprintf(
		"<!-- wp:heading {\"level\":2} -->\n<h2 class=\"wp-block-heading\">%s</h2>\n<!-- /wp:heading -->\n\n"
		. "<!-- wp:paragraph -->\n<p>%s</p>\n<!-- /wp:paragraph -->",
		esc_html( $heading ),
		esc_html( $note )
	);
}

WP_CLI::log( 'Categories' );

$categories = array(
	'kegiatan'          => array( 'Kegiatan', 'Dokumentasi kegiatan harian, program, dan acara di lingkungan Yayasan Kasih Ananda.' ),
	'prestasi'          => array( 'Prestasi', 'Capaian siswa, guru, dan unit pendidikan yang telah diverifikasi.' ),
	'pengumuman'        => array( 'Pengumuman', 'Informasi resmi untuk siswa, orang tua, dan masyarakat.' ),
	'akademik'          => array( 'Akademik', 'Kurikulum, pembelajaran, ujian, dan kegiatan akademik.' ),
	'ekstrakurikuler'   => array( 'Ekstrakurikuler', 'Kegiatan pengembangan minat dan bakat di luar jam pelajaran.' ),
	'spmb-ppdb'         => array( 'SPMB / PPDB', 'Informasi penerimaan murid baru pada unit pendidikan Kasih Ananda.' ),
	'yayasan'           => array( 'Yayasan', 'Kegiatan dan informasi tingkat Yayasan Kasih Ananda.' ),
	'informasi-sekolah' => array( 'Informasi Sekolah', 'Informasi operasional sekolah seperti jadwal, layanan, dan fasilitas.' ),
);

foreach ( $categories as $slug => $data ) {
	if ( term_exists( $slug, 'category' ) ) {
		continue;
	}
	$term = wp_insert_term(
		$data[0],
		'category',
		array(
			'slug'        => $slug,
			'description' => $data[1],
		)
	);
	if ( ! is_wp_error( $term ) ) {
		WP_CLI::log( '  + category: ' . $data[0] );
	}
}

// Rename the default "Uncategorized" category so no article is ever filed
// under an English placeholder.
$default_id = (int) get_option( 'default_category' );
$default    = get_term( $default_id, 'category' );
if ( $default instanceof WP_Term && in_array( $default->slug, array( 'uncategorized', 'uncategorised' ), true ) ) {
	$kegiatan = get_term_by( 'slug', 'kegiatan', 'category' );
	if ( $kegiatan instanceof WP_Term ) {
		update_option( 'default_category', $kegiatan->term_id );
		WP_CLI::log( '  * default category set to Kegiatan' );
	}
}

WP_CLI::log( 'Pages' );

$beranda = yka_structure_page(
	array(
		'title'   => 'Beranda',
		'slug'    => 'beranda',
		'order'   => 1,
		'content' => '<!-- wp:paragraph --><p>Halaman depan portal Yayasan Kasih Ananda. Tampilan beranda diatur oleh template tema, bukan oleh isi halaman ini.</p><!-- /wp:paragraph -->',
	)
);

$berita = yka_structure_page(
	array(
		'title' => 'Berita & Kegiatan',
		'slug'  => 'berita',
		'order' => 4,
	)
);

$tentang = yka_structure_page(
	array(
		'title'   => 'Tentang Yayasan',
		'slug'    => 'tentang-yayasan',
		'order'   => 2,
		'content' => yka_placeholder_body(
			'Tentang Yayasan Kasih Ananda',
			'[Profil resmi Yayasan Kasih Ananda belum diberikan. Bagian ini akan diisi dengan keterangan resmi dari yayasan.]'
		),
	)
);

yka_structure_page(
	array(
		'title'   => 'Sejarah',
		'slug'    => 'sejarah',
		'parent'  => $tentang,
		'order'   => 1,
		'content' => yka_placeholder_body(
			'Sejarah Yayasan Kasih Ananda',
			'[Riwayat pendirian yayasan belum diberikan. Isi bagian ini dengan tahun pendirian, pendiri, dan perkembangan unit pendidikan berdasarkan dokumen resmi.]'
		),
	)
);

yka_structure_page(
	array(
		'title'   => 'Visi & Misi',
		'slug'    => 'visi-misi',
		'parent'  => $tentang,
		'order'   => 2,
		'content' => yka_placeholder_body(
			'Visi dan Misi',
			'[Rumusan visi dan misi resmi belum diberikan. Salin persis dari dokumen yayasan tanpa mengubah kalimat.]'
		),
	)
);

yka_structure_page(
	array(
		'title'   => 'Struktur Organisasi',
		'slug'    => 'struktur-organisasi',
		'parent'  => $tentang,
		'order'   => 3,
		'content' => yka_placeholder_body(
			'Struktur Organisasi',
			'[Susunan pengurus yayasan dan pimpinan unit pendidikan belum diberikan. Nama dan jabatan tidak boleh diisi tanpa data resmi.]'
		),
	)
);

yka_structure_page(
	array(
		'title'    => 'Unit Pendidikan',
		'slug'     => 'unit-pendidikan',
		'order'    => 3,
		'template' => 'templates/unit-index.php',
	)
);

yka_structure_page(
	array(
		'title'    => 'Prestasi',
		'slug'     => 'prestasi',
		'order'    => 5,
		'template' => 'templates/prestasi.php',
	)
);

yka_structure_page(
	array(
		'title'    => 'Galeri',
		'slug'     => 'galeri',
		'order'    => 6,
		'template' => 'templates/galeri.php',
		'content'  => yka_placeholder_body(
			'Galeri Dokumentasi',
			'[Belum ada arsip foto yang diunggah. Tambahkan blok Galeri berisi dokumentasi kegiatan yang sudah diverifikasi.]'
		),
	)
);

yka_structure_page(
	array(
		'title'    => 'Pengumuman',
		'slug'     => 'pengumuman',
		'order'    => 7,
		'template' => 'templates/pengumuman.php',
	)
);

yka_structure_page(
	array(
		'title'    => 'Kontak',
		'slug'     => 'kontak',
		'order'    => 8,
		'template' => 'templates/kontak.php',
	)
);

yka_structure_page(
	array(
		'title'   => 'Kebijakan Privasi',
		'slug'    => 'kebijakan-privasi',
		'order'   => 9,
		'content' => yka_placeholder_body(
			'Kebijakan Privasi',
			'[Kebijakan privasi resmi belum diberikan. Jelaskan data apa yang dikumpulkan situs ini, bagaimana data disimpan, dan kepada siapa pengunjung dapat mengajukan pertanyaan.]'
		),
	)
);

// Front page + posts page.
if ( $beranda ) {
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $beranda );
}
if ( $berita ) {
	update_option( 'page_for_posts', $berita );
}
WP_CLI::log( '  * front page: Beranda, posts page: Berita & Kegiatan' );

$privacy = get_page_by_path( 'kebijakan-privasi' );
if ( $privacy instanceof WP_Post ) {
	update_option( 'wp_page_for_privacy_policy', $privacy->ID );
}

// Remove WordPress's own sample content, but only while it is still
// untouched: an editor who has actually written into these must keep them.
foreach ( array(
	array(
		'type' => 'post',
		'slug' => 'hello-world',
	),
	array(
		'type' => 'page',
		'slug' => 'sample-page',
	),
	array(
		'type' => 'page',
		'slug' => 'privacy-policy',
	),
) as $sample ) {
	$found = get_posts(
		array(
			'post_type'        => $sample['type'],
			'name'             => $sample['slug'],
			'post_status'      => 'any',
			'numberposts'      => 1,
			'suppress_filters' => false,
		)
	);

	if ( ! $found ) {
		continue;
	}

	$candidate = $found[0];
	if ( (int) $candidate->post_modified_gmt !== 0 && $candidate->post_modified_gmt !== $candidate->post_date_gmt ) {
		continue;
	}

	wp_delete_post( $candidate->ID, true );
	WP_CLI::log( '  - removed WordPress sample content: ' . $candidate->post_title );
}

WP_CLI::log( 'Navigation menus' );
/**
 * Creates a menu if it does not exist and returns its term id.
 *
 * @param string $name Menu name.
 * @return int Menu id, or 0 on failure.
 */
function yka_structure_menu( $name ) {
	$menu = wp_get_nav_menu_object( $name );
	if ( $menu ) {
		return (int) $menu->term_id;
	}
	$id = wp_create_nav_menu( $name );
	if ( is_wp_error( $id ) ) {
		WP_CLI::warning( sprintf( 'Menu "%s": %s', $name, $id->get_error_message() ) );
		return 0;
	}
	WP_CLI::log( '  + menu: ' . $name );
	return (int) $id;
}

/**
 * Adds a menu item unless the same target already appears under the same parent.
 *
 * Matching is done on object id rather than title: WordPress stores menu
 * titles with entities applied, so "Berita & Kegiatan" and
 * "Berita &amp; Kegiatan" would otherwise look like two different items and
 * a duplicate would be appended on every run.
 *
 * @param int   $menu_id Menu id.
 * @param array $args    wp_update_nav_menu_item args.
 * @return int Menu item id.
 */
function yka_structure_menu_item( $menu_id, array $args ) {
	if ( ! $menu_id ) {
		return 0;
	}

	$target_object = (string) ( $args['menu-item-object'] ?? '' );
	$target_id     = (int) ( $args['menu-item-object-id'] ?? 0 );
	$target_parent = (int) ( $args['menu-item-parent-id'] ?? 0 );

	foreach ( wp_get_nav_menu_items( $menu_id ) ?: array() as $item ) {
		$same_target = $item->object === $target_object && (int) $item->object_id === $target_id;
		$same_parent = (int) $item->menu_item_parent === $target_parent;

		if ( $same_target && $same_parent ) {
			return (int) $item->ID;
		}
	}

	$id = wp_update_nav_menu_item( $menu_id, 0, wp_parse_args( $args, array( 'menu-item-status' => 'publish' ) ) );
	return is_wp_error( $id ) ? 0 : (int) $id;
}

$primary_id = yka_structure_menu( 'Navigasi Utama' );
$utility_id = yka_structure_menu( 'Navigasi Atas' );
$footer_id  = yka_structure_menu( 'Navigasi Footer' );

$page_item = static function ( $menu_id, $slug, $title, $parent_item = 0 ) {
	$page = get_page_by_path( $slug );
	if ( ! $page instanceof WP_Post ) {
		return 0;
	}
	return yka_structure_menu_item(
		$menu_id,
		array(
			'menu-item-title'     => $title,
			'menu-item-object'    => 'page',
			'menu-item-object-id' => $page->ID,
			'menu-item-type'      => 'post_type',
			'menu-item-parent-id' => $parent_item,
		)
	);
};

$page_item( $primary_id, 'beranda', 'Beranda' );
$page_item( $primary_id, 'tentang-yayasan', 'Tentang Yayasan' );
$unit_item = $page_item( $primary_id, 'unit-pendidikan', 'Unit Pendidikan' );

// Unit children point at the yka_unit taxonomy archives.
foreach ( array(
	'sd-kasih-ananda-1'  => 'SD Kasih Ananda I',
	'smp-kasih-ananda-1' => 'SMP Kasih Ananda I',
	'smk-kasih-ananda'   => 'SMK Kasih Ananda',
) as $term_slug => $term_label ) {
	$term = get_term_by( 'slug', $term_slug, 'yka_unit' );
	if ( $term instanceof WP_Term ) {
		yka_structure_menu_item(
			$primary_id,
			array(
				'menu-item-title'     => $term_label,
				'menu-item-object'    => 'yka_unit',
				'menu-item-object-id' => $term->term_id,
				'menu-item-type'      => 'taxonomy',
				'menu-item-parent-id' => $unit_item,
			)
		);
	}
}

$page_item( $primary_id, 'berita', 'Berita & Kegiatan' );
$page_item( $primary_id, 'prestasi', 'Prestasi' );
$page_item( $primary_id, 'galeri', 'Galeri' );
$page_item( $primary_id, 'pengumuman', 'Pengumuman' );
$page_item( $primary_id, 'kontak', 'Kontak' );

$page_item( $utility_id, 'tentang-yayasan/sejarah', 'Sejarah' );
$page_item( $utility_id, 'tentang-yayasan/visi-misi', 'Visi & Misi' );
$page_item( $utility_id, 'tentang-yayasan/struktur-organisasi', 'Struktur Organisasi' );

$page_item( $footer_id, 'tentang-yayasan', 'Tentang Yayasan' );
$page_item( $footer_id, 'unit-pendidikan', 'Unit Pendidikan' );
$page_item( $footer_id, 'berita', 'Berita & Kegiatan' );
$page_item( $footer_id, 'pengumuman', 'Pengumuman' );
$page_item( $footer_id, 'kontak', 'Kontak' );
$page_item( $footer_id, 'kebijakan-privasi', 'Kebijakan Privasi' );

$locations            = get_theme_mod( 'nav_menu_locations', array() );
$locations            = is_array( $locations ) ? $locations : array();
$locations['primary'] = $primary_id;
$locations['utility'] = $utility_id;
$locations['footer']  = $footer_id;
set_theme_mod( 'nav_menu_locations', $locations );
WP_CLI::log( '  * menu locations assigned' );

WP_CLI::success( 'Site structure is in place.' );
