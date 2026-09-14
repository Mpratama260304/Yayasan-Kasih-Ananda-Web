<?php
/**
 * Title: Berita Standar
 * Slug: yka-portal/berita-standar
 * Categories: yka
 * Description: Kerangka artikel berita harian: paragraf pembuka, isi bersubjudul, foto, dan penutup.
 * Keywords: berita, kegiatan, dokumentasi, artikel
 * Viewport Width: 900
 *
 * @package YKA_Portal
 */

?>
<!-- wp:paragraph -->
<p><?php echo esc_html__( 'Tulis paragraf pembuka di sini. Satu paragraf ini sebaiknya sudah menjawab apa yang terjadi, siapa yang terlibat, kapan, dan di mana. Salin paragraf ini juga ke kolom Ringkasan agar dipakai di beranda dan pratinjau berbagi.', 'yka-portal' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading"><?php echo esc_html__( 'Jalannya kegiatan', 'yka-portal' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php echo esc_html__( 'Uraikan urutan kegiatan secara faktual. Sebutkan jumlah peserta, kelas, atau program keahlian hanya bila angkanya pasti.', 'yka-portal' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large"><img alt=""/><figcaption class="wp-element-caption"><?php echo esc_html__( 'Tulis keterangan foto: siapa yang terlihat, sedang melakukan apa, di mana.', 'yka-portal' ); ?></figcaption></figure>
<!-- /wp:image -->

<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading"><?php echo esc_html__( 'Hasil dan tindak lanjut', 'yka-portal' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php echo esc_html__( 'Tuliskan hasil konkret bila ada. Jika kegiatan belum menghasilkan apa pun yang dapat disebutkan, hapus bagian ini — artikel pendek yang jujur lebih baik daripada paragraf pengisi.', 'yka-portal' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:gallery {"columns":3,"linkTo":"none"} -->
<figure class="wp-block-gallery has-nested-images columns-3 is-cropped"><!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large"><img alt=""/></figure>
<!-- /wp:image --></figure>
<!-- /wp:gallery -->
