<?php
/**
 * Title: Prestasi
 * Slug: yka-portal/prestasi
 * Categories: yka
 * Description: Laporan capaian: nama lomba, peserta, hasil resmi, dan dokumentasi. Hanya untuk prestasi yang sudah diverifikasi.
 * Keywords: prestasi, lomba, juara, penghargaan
 * Viewport Width: 900
 *
 * @package YKA_Portal
 */

?>
<!-- wp:paragraph -->
<p><?php echo esc_html__( 'Paragraf pembuka: siapa meraih apa, pada lomba apa, kapan, dan di mana. Cantumkan penyelenggara dan tingkat lomba agar capaian dapat ditelusuri.', 'yka-portal' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:group {"className":"is-style-yka-callout","layout":{"type":"constrained"}} -->
<div class="wp-block-group is-style-yka-callout"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading"><?php echo esc_html__( 'Ringkasan capaian', 'yka-portal' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:table -->
<figure class="wp-block-table"><table><tbody><tr><td><?php echo esc_html__( 'Nama kegiatan', 'yka-portal' ); ?></td><td><?php echo esc_html__( '[isi nama lomba]', 'yka-portal' ); ?></td></tr><tr><td><?php echo esc_html__( 'Penyelenggara', 'yka-portal' ); ?></td><td><?php echo esc_html__( '[isi penyelenggara]', 'yka-portal' ); ?></td></tr><tr><td><?php echo esc_html__( 'Tingkat', 'yka-portal' ); ?></td><td><?php echo esc_html__( '[sekolah / kecamatan / kota / provinsi / nasional]', 'yka-portal' ); ?></td></tr><tr><td><?php echo esc_html__( 'Peserta', 'yka-portal' ); ?></td><td><?php echo esc_html__( '[nama siswa atau tim, tulis hanya bila sudah ada izin]', 'yka-portal' ); ?></td></tr><tr><td><?php echo esc_html__( 'Hasil', 'yka-portal' ); ?></td><td><?php echo esc_html__( '[hasil resmi sesuai sertifikat]', 'yka-portal' ); ?></td></tr></tbody></table></figure>
<!-- /wp:table --></div>
<!-- /wp:group -->

<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading"><?php echo esc_html__( 'Jalannya lomba', 'yka-portal' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php echo esc_html__( 'Uraikan persiapan dan jalannya lomba. Tuliskan hanya yang benar-benar diketahui.', 'yka-portal' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large"><img alt=""/><figcaption class="wp-element-caption"><?php echo esc_html__( 'Foto penyerahan penghargaan atau dokumentasi lomba.', 'yka-portal' ); ?></figcaption></figure>
<!-- /wp:image -->
