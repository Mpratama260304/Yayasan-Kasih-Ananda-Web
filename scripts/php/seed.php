<?php
/**
 * Development-only demo content.
 *
 * Everything created here is prefixed [DEMO] and can be removed in one
 * command. No statement in this file may be mistaken for a fact about
 * Yayasan Kasih Ananda: there are no real names, no real achievements, no
 * real addresses, and no stock photographs of children.
 *
 *   wp eval-file /scripts/php/seed.php
 *   wp eval-file /scripts/php/seed.php -- --remove
 *
 * @package YKA
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}

const YKA_DEMO_FLAG   = '_yka_demo_content';
const YKA_DEMO_PREFIX = '[DEMO]';

/*
==================================================================
	Removal
	================================================================== */

$yka_remove = in_array( '--remove', (array) ( $args ?? array() ), true );

if ( $yka_remove ) {
	$ids = get_posts(
		array(
			'post_type'        => array( 'post', 'attachment' ),
			'post_status'      => 'any',
			'numberposts'      => -1,
			'fields'           => 'ids',
			'meta_key'         => YKA_DEMO_FLAG, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'suppress_filters' => false,
		)
	);

	foreach ( $ids as $id ) {
		wp_delete_post( (int) $id, true );
	}

	// Clear demo values out of the settings without touching real ones.
	$settings = get_option( 'yka_settings', array() );
	if ( is_array( $settings ) ) {
		foreach ( array( 'hero_image', 'featured_post' ) as $key ) {
			if ( isset( $settings[ $key ] ) && ! get_post( (int) $settings[ $key ] ) ) {
				$settings[ $key ] = '';
			}
		}
		update_option( 'yka_settings', $settings );
	}

	foreach ( get_terms(
		array(
			'taxonomy'   => 'yka_unit',
			'hide_empty' => false,
		)
	) ?: array() as $term ) {
		foreach ( array( 'yka_unit_hero_id', 'yka_unit_logo_id' ) as $meta_key ) {
			$value = (int) get_term_meta( $term->term_id, $meta_key, true );
			if ( $value && ! get_post( $value ) ) {
				delete_term_meta( $term->term_id, $meta_key );
			}
		}
	}

	WP_CLI::success( sprintf( '%d item konten demo dihapus.', count( $ids ) ) );
	return;
}

/*
==================================================================
	Placeholder imagery
	================================================================== */

/**
 * Draws an obvious placeholder image and adds it to the media library.
 *
 * Drawn locally with GD, never downloaded. A stock photograph presented as
 * school documentation would be a lie, so these are unmistakably synthetic:
 * flat brand colours, diagonal hatching and the word DEMO across them.
 *
 * @param string $label  Caption text.
 * @param int    $width  Width in pixels.
 * @param int    $height Height in pixels.
 * @param int    $tone   0-3, picks a palette variant.
 * @return int Attachment id, or 0 on failure.
 */
function yka_demo_image( string $label, int $width, int $height, int $tone = 0 ): int {
	if ( ! function_exists( 'imagecreatetruecolor' ) ) {
		WP_CLI::warning( 'Ekstensi GD tidak tersedia, gambar demo dilewati.' );
		return 0;
	}

	$palettes = array(
		array( array( 0x12, 0x46, 0x2e ), array( 0x2b, 0x7e, 0x54 ) ),
		array( array( 0x2f, 0x7d, 0x8c ), array( 0x18, 0x58, 0x3a ) ),
		array( array( 0x8a, 0x6a, 0x1f ), array( 0xd2, 0xb6, 0x3f ) ),
		array( array( 0x0d, 0x33, 0x22 ), array( 0x1f, 0x6b, 0x46 ) ),
	);

	$palette = $palettes[ $tone % count( $palettes ) ];

	$image = imagecreatetruecolor( $width, $height );
	$back  = imagecolorallocate( $image, $palette[0][0], $palette[0][1], $palette[0][2] );
	$fore  = imagecolorallocate( $image, $palette[1][0], $palette[1][1], $palette[1][2] );
	$ink   = imagecolorallocate( $image, 0xF7, 0xF5, 0xEE );

	imagefilledrectangle( $image, 0, 0, $width, $height, $back );

	// Diagonal hatching so the image can never be mistaken for a photograph.
	for ( $x = -$height; $x < $width; $x += 34 ) {
		imagefilledpolygon(
			$image,
			array( $x, $height, $x + 17, $height, $x + 17 + $height, 0, $x + $height, 0 ),
			$fore
		);
	}

	$title = 'DEMO';
	$font  = 5;
	imagestring(
		$image,
		$font,
		(int) ( ( $width - imagefontwidth( $font ) * strlen( $title ) ) / 2 ),
		(int) ( $height / 2 - 22 ),
		$title,
		$ink
	);

	$sub = substr( $label, 0, 48 );
	imagestring(
		$image,
		3,
		(int) ( ( $width - imagefontwidth( 3 ) * strlen( $sub ) ) / 2 ),
		(int) ( $height / 2 + 4 ),
		$sub,
		$ink
	);

	ob_start();
	imagejpeg( $image, null, 82 );
	$data = (string) ob_get_clean();
	imagedestroy( $image );

	$filename = 'demo-' . sanitize_title( $label ) . '-' . $width . 'x' . $height . '.jpg';
	$upload   = wp_upload_bits( $filename, null, $data );

	if ( ! empty( $upload['error'] ) ) {
		WP_CLI::warning( 'Gagal menulis gambar demo: ' . $upload['error'] );
		return 0;
	}

	$attachment_id = wp_insert_attachment(
		array(
			'post_mime_type' => 'image/jpeg',
			'post_title'     => YKA_DEMO_PREFIX . ' ' . $label,
			'post_excerpt'   => YKA_DEMO_PREFIX . ' Gambar contoh — ganti dengan foto dokumentasi asli.',
			'post_status'    => 'inherit',
		),
		$upload['file']
	);

	if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
		return 0;
	}

	require_once ABSPATH . 'wp-admin/includes/image.php';
	wp_update_attachment_metadata(
		$attachment_id,
		wp_generate_attachment_metadata( $attachment_id, $upload['file'] )
	);

	update_post_meta( $attachment_id, '_wp_attachment_image_alt', YKA_DEMO_PREFIX . ' ' . $label );
	update_post_meta( $attachment_id, YKA_DEMO_FLAG, 1 );

	return (int) $attachment_id;
}

/*
==================================================================
	Articles
	================================================================== */

/**
 * Builds block markup for a demo article.
 *
 * @param string   $lead      Lead paragraph.
 * @param string[] $sections  Alternating heading/paragraph pairs.
 * @param int[]    $image_ids Images to place inside the body.
 * @return string
 */
function yka_demo_body( string $lead, array $sections, array $image_ids = array() ): string {
	$blocks = array();

	$blocks[] = "<!-- wp:paragraph -->\n<p>" . esc_html( $lead ) . "</p>\n<!-- /wp:paragraph -->";

	$index = 0;
	foreach ( $sections as $heading => $text ) {
		$blocks[] = "<!-- wp:heading {\"level\":2} -->\n<h2 class=\"wp-block-heading\">"
			. esc_html( (string) $heading ) . "</h2>\n<!-- /wp:heading -->";
		$blocks[] = "<!-- wp:paragraph -->\n<p>" . esc_html( (string) $text ) . "</p>\n<!-- /wp:paragraph -->";

		if ( isset( $image_ids[ $index ] ) && $image_ids[ $index ] ) {
			$id  = (int) $image_ids[ $index ];
			$src = wp_get_attachment_image_url( $id, 'large' );
			if ( $src ) {
				$blocks[] = sprintf(
					"<!-- wp:image {\"id\":%1\$d,\"sizeSlug\":\"large\",\"linkDestination\":\"none\"} -->\n"
					. '<figure class="wp-block-image size-large"><img src="%2$s" alt="%3$s" class="wp-image-%1$d"/>'
					. "<figcaption class=\"wp-element-caption\">%4\$s</figcaption></figure>\n<!-- /wp:image -->",
					$id,
					esc_url( $src ),
					esc_attr( YKA_DEMO_PREFIX . ' gambar contoh' ),
					esc_html( YKA_DEMO_PREFIX . ' Keterangan foto contoh. Ganti dengan keterangan dokumentasi asli.' )
				);
			}
		}

		++$index;
	}

	return implode( "\n\n", $blocks );
}

$yka_author_id = (int) get_users(
	array(
		'role__in' => array( 'administrator' ),
		'number'   => 1,
		'fields'   => 'ID',
	)
)[0] ?? 1;

WP_CLI::log( 'Gambar demo' );

$yka_hero_image = yka_demo_image( 'Hero beranda', 1920, 1080, 3 );
WP_CLI::log( '  + hero beranda' );

$yka_unit_images = array();
foreach ( array(
	'yayasan-kasih-ananda' => array( 'Yayasan', 0 ),
	'sd-kasih-ananda-1'    => array( 'SD', 1 ),
	'smp-kasih-ananda-1'   => array( 'SMP', 2 ),
	'smk-kasih-ananda'     => array( 'SMK', 3 ),
) as $yka_slug => $yka_unit_meta ) {
	$yka_unit_images[ $yka_slug ] = yka_demo_image( 'Unit ' . $yka_unit_meta[0], 1600, 1067, (int) $yka_unit_meta[1] );

	$yka_term = get_term_by( 'slug', $yka_slug, 'yka_unit' );
	if ( $yka_term instanceof WP_Term && $yka_unit_images[ $yka_slug ] ) {
		update_term_meta( $yka_term->term_id, 'yka_unit_hero_id', $yka_unit_images[ $yka_slug ] );

		if ( '' === (string) get_term_meta( $yka_term->term_id, 'yka_unit_intro', true ) ) {
			update_term_meta(
				$yka_term->term_id,
				'yka_unit_intro',
				sprintf(
					'%s Keterangan unit belum diberikan oleh yayasan. Ganti teks ini dengan penjelasan resmi tentang %s.',
					YKA_DEMO_PREFIX,
					$yka_term->name
				)
			);
		}
	}
	WP_CLI::log( '  + unit ' . $yka_unit_meta[0] );
}

WP_CLI::log( 'Artikel demo' );

$yka_articles = array(
	array(
		'title'    => 'Upacara Bendera Rutin Setiap Senin di Lingkungan Yayasan',
		'unit'     => 'yayasan-kasih-ananda',
		'category' => 'kegiatan',
		'lead'     => 'Contoh artikel pengembangan. Teks ini menggambarkan bagaimana laporan kegiatan rutin ditulis: apa yang terjadi, siapa yang terlibat, kapan, dan di mana.',
		'location' => 'Lapangan utama',
		'days'     => 2,
		'sections' => array(
			'Jalannya kegiatan' => 'Bagian ini diisi dengan urutan kegiatan sebenarnya. Tulis secara faktual tanpa melebih-lebihkan.',
			'Tindak lanjut'     => 'Bagian ini diisi dengan hasil konkret bila ada. Jika tidak ada, bagian ini boleh dihapus.',
		),
	),
	array(
		'title'    => 'Kegiatan Literasi Pagi di SD Kasih Ananda I',
		'unit'     => 'sd-kasih-ananda-1',
		'category' => 'akademik',
		'lead'     => 'Contoh artikel pengembangan untuk jenjang SD. Paragraf pembuka sebaiknya menjawab pertanyaan pokok dalam dua sampai tiga kalimat.',
		'location' => 'Ruang kelas',
		'days'     => 5,
		'sections' => array(
			'Pelaksanaan' => 'Isi bagian ini dengan keterangan pelaksanaan kegiatan berdasarkan catatan tim dokumentasi.',
			'Dokumentasi' => 'Sisipkan foto asli beserta keterangan dan kredit fotografer pada bagian ini.',
		),
	),
	array(
		'title'    => 'Pendataan Ekstrakurikuler Semester Ganjil SMP Kasih Ananda I',
		'unit'     => 'smp-kasih-ananda-1',
		'category' => 'ekstrakurikuler',
		'lead'     => 'Contoh artikel pengembangan. Artikel pendek tetap bernilai selama informasinya jelas dan benar.',
		'location' => 'Aula sekolah',
		'days'     => 8,
		'sections' => array(
			'Daftar kegiatan' => 'Bagian ini diisi dengan daftar ekstrakurikuler yang benar-benar berjalan.',
		),
	),
	array(
		'title'    => 'Praktik Kerja Lapangan Siswa SMK Kasih Ananda',
		'unit'     => 'smk-kasih-ananda',
		'category' => 'kegiatan',
		'lead'     => 'Contoh artikel pengembangan untuk jenjang SMK. Sebutkan program keahlian, jumlah peserta, dan mitra hanya bila datanya sudah pasti.',
		'location' => 'Lokasi mitra industri',
		'days'     => 12,
		'sections' => array(
			'Persiapan'   => 'Isi dengan tahap persiapan yang benar-benar dilakukan.',
			'Pelaksanaan' => 'Isi dengan rangkaian pelaksanaan dan hasil yang terukur bila tersedia.',
		),
	),
	array(
		'title'    => 'Jadwal Penerimaan Murid Baru Tahun Ajaran Berikutnya',
		'unit'     => 'yayasan-kasih-ananda',
		'category' => 'spmb-ppdb',
		'lead'     => 'Contoh pengumuman pengembangan. Pengumuman resmi harus memuat tanggal, tempat, syarat, dan narahubung yang benar.',
		'location' => '',
		'days'     => 1,
		'sections' => array(
			'Ketentuan'  => 'Bagian ini diisi dengan ketentuan resmi dari panitia penerimaan murid baru.',
			'Narahubung' => 'Bagian ini diisi dengan nomor kontak resmi panitia.',
		),
	),
	array(
		'title'    => 'Pengumuman Perubahan Jadwal Kegiatan Belajar',
		'unit'     => 'smp-kasih-ananda-1',
		'category' => 'pengumuman',
		'lead'     => 'Contoh pengumuman pengembangan. Pengumuman singkat tiga kalimat pun sah diterbitkan selama informasinya lengkap.',
		'location' => '',
		'days'     => 3,
		'sections' => array(
			'Rincian perubahan' => 'Bagian ini diisi dengan rincian perubahan jadwal yang resmi.',
		),
	),
	array(
		'title'    => 'Contoh Format Berita Prestasi Siswa',
		'unit'     => 'smk-kasih-ananda',
		'category' => 'prestasi',
		'lead'     => 'Contoh artikel pengembangan. Berita prestasi hanya boleh diterbitkan setelah capaiannya diverifikasi oleh unit terkait.',
		'location' => 'Lokasi lomba',
		'days'     => 20,
		'sections' => array(
			'Yang perlu ditulis' => 'Sebutkan nama lomba, penyelenggara, tingkat, tanggal, peserta, dan hasil resmi. Jangan menuliskan capaian yang belum diverifikasi.',
		),
	),
	array(
		'title'    => 'Perawatan Sarana Belajar di SD Kasih Ananda I',
		'unit'     => 'sd-kasih-ananda-1',
		'category' => 'informasi-sekolah',
		'lead'     => 'Contoh artikel pengembangan tentang informasi operasional sekolah.',
		'location' => 'Area sekolah',
		'days'     => 26,
		'sections' => array(
			'Ruang lingkup' => 'Bagian ini diisi dengan keterangan pekerjaan yang benar-benar dilakukan.',
		),
	),
	array(
		'title'    => 'Rapat Koordinasi Pimpinan Unit Pendidikan',
		'unit'     => 'yayasan-kasih-ananda',
		'category' => 'yayasan',
		'lead'     => 'Contoh artikel pengembangan tingkat yayasan. Jangan mencantumkan nama pengurus sebelum data resminya diberikan.',
		'location' => 'Kantor yayasan',
		'days'     => 34,
		'sections' => array(
			'Pokok pembahasan' => 'Bagian ini diisi dengan pokok bahasan yang boleh dipublikasikan.',
		),
	),
	array(
		'title'    => 'Dokumentasi Kegiatan Bersama Tiga Unit Pendidikan',
		'unit'     => 'yayasan-kasih-ananda',
		'category' => 'kegiatan',
		'lead'     => 'Contoh artikel pengembangan untuk kegiatan lintas unit. Satu artikel dapat ditandai lebih dari satu unit pendidikan.',
		'location' => 'Lapangan utama',
		'days'     => 45,
		'sections' => array(
			'Rangkaian acara' => 'Bagian ini diisi dengan rangkaian acara sebenarnya.',
			'Galeri'          => 'Gunakan blok Galeri bawaan Gutenberg untuk menampilkan beberapa foto sekaligus.',
		),
	),
);

$yka_created = array();

foreach ( $yka_articles as $yka_index => $yka_article ) {
	$yka_existing = get_posts(
		array(
			'post_type'        => 'post',
			'post_status'      => 'any',
			'title'            => YKA_DEMO_PREFIX . ' ' . $yka_article['title'],
			'numberposts'      => 1,
			'suppress_filters' => false,
		)
	);

	if ( $yka_existing ) {
		WP_CLI::log( '  = sudah ada: ' . $yka_article['title'] );
		$yka_created[] = (int) $yka_existing[0]->ID;
		continue;
	}

	$yka_featured = yka_demo_image( $yka_article['title'], 1600, 900, $yka_index );
	$yka_inline   = array( yka_demo_image( 'Isi ' . $yka_article['title'], 1200, 800, $yka_index + 1 ) );

	$yka_date = gmdate( 'Y-m-d H:i:s', strtotime( '-' . (int) $yka_article['days'] . ' days' ) );

	$yka_post_id = wp_insert_post(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'post_author'    => $yka_author_id,
			'post_title'     => YKA_DEMO_PREFIX . ' ' . $yka_article['title'],
			'post_excerpt'   => $yka_article['lead'],
			'post_content'   => yka_demo_body( $yka_article['lead'], $yka_article['sections'], $yka_inline ),
			'post_date_gmt'  => $yka_date,
			'post_date'      => get_date_from_gmt( $yka_date ),
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
		),
		true
	);

	if ( is_wp_error( $yka_post_id ) ) {
		WP_CLI::warning( $yka_post_id->get_error_message() );
		continue;
	}

	$yka_post_id = (int) $yka_post_id;

	update_post_meta( $yka_post_id, YKA_DEMO_FLAG, 1 );

	if ( $yka_featured ) {
		set_post_thumbnail( $yka_post_id, $yka_featured );
	}

	wp_set_object_terms( $yka_post_id, $yka_article['unit'], 'yka_unit' );

	// One article is deliberately tagged with several units to exercise the
	// multi-unit path without duplicating the article itself.
	if ( str_contains( $yka_article['title'], 'Tiga Unit' ) ) {
		wp_set_object_terms(
			$yka_post_id,
			array( 'yayasan-kasih-ananda', 'sd-kasih-ananda-1', 'smp-kasih-ananda-1' ),
			'yka_unit'
		);
	}

	$yka_category = get_term_by( 'slug', $yka_article['category'], 'category' );
	if ( $yka_category instanceof WP_Term ) {
		wp_set_post_categories( $yka_post_id, array( $yka_category->term_id ) );
	}

	update_post_meta( $yka_post_id, 'yka_activity_date', gmdate( 'Y-m-d', strtotime( $yka_date . ' -1 day' ) ) );
	if ( '' !== $yka_article['location'] ) {
		update_post_meta( $yka_post_id, 'yka_activity_location', YKA_DEMO_PREFIX . ' ' . $yka_article['location'] );
	}
	update_post_meta( $yka_post_id, 'yka_photo_credit', YKA_DEMO_PREFIX . ' Tim Dokumentasi' );

	$yka_created[] = $yka_post_id;
	WP_CLI::log( '  + ' . $yka_article['title'] );
}

/*
==================================================================
	Homepage configuration
	================================================================== */

$yka_settings = get_option( 'yka_settings', array() );
$yka_settings = is_array( $yka_settings ) ? $yka_settings : array();

if ( $yka_hero_image ) {
	$yka_settings['hero_image'] = $yka_hero_image;
}

if ( empty( $yka_settings['hero_heading'] ) ) {
	$yka_settings['hero_heading'] = 'Yayasan Kasih Ananda';
}

if ( empty( $yka_settings['hero_text'] ) ) {
	$yka_settings['hero_text'] = 'Yayasan Kasih Ananda menaungi SD Kasih Ananda I, SMP Kasih Ananda I, dan SMK Kasih Ananda. Portal ini memuat dokumentasi kegiatan, pengumuman, dan informasi resmi dari setiap unit.';
}

if ( empty( $yka_settings['org_description'] ) ) {
	$yka_settings['org_description'] = YKA_DEMO_PREFIX . ' Deskripsi resmi yayasan belum diberikan. Ganti teks ini dengan profil singkat dari dokumen resmi yayasan.';
}

update_option( 'yka_settings', $yka_settings );

WP_CLI::success( sprintf( '%d artikel demo siap. Semua berawalan %s.', count( $yka_created ), YKA_DEMO_PREFIX ) );
