<?php
/**
 * Title: Cerita Foto
 * Slug: yka-portal/cerita-foto
 * Categories: yka
 * Description: Untuk kegiatan yang paling kuat diceritakan lewat foto: pengantar singkat, foto besar, dua foto berdampingan, lalu galeri penutup.
 * Keywords: foto, galeri, dokumentasi, cerita
 * Viewport Width: 1100
 *
 * @package YKA_Portal
 */

?>
<!-- wp:paragraph -->
<p><?php echo esc_html__( 'Pengantar singkat: kegiatan apa yang didokumentasikan, kapan, dan di unit mana. Dua sampai tiga kalimat sudah cukup — sisanya diceritakan oleh foto.', 'yka-portal' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:image {"align":"wide","sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image alignwide size-large"><img alt=""/><figcaption class="wp-element-caption"><?php echo esc_html__( 'Foto pembuka. Pilih foto paling representatif dengan resolusi tinggi.', 'yka-portal' ); ?></figcaption></figure>
<!-- /wp:image -->

<!-- wp:columns {"align":"wide"} -->
<div class="wp-block-columns alignwide"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large"><img alt=""/><figcaption class="wp-element-caption"><?php echo esc_html__( 'Keterangan foto kiri.', 'yka-portal' ); ?></figcaption></figure>
<!-- /wp:image --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large"><img alt=""/><figcaption class="wp-element-caption"><?php echo esc_html__( 'Keterangan foto kanan.', 'yka-portal' ); ?></figcaption></figure>
<!-- /wp:image --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:paragraph -->
<p><?php echo esc_html__( 'Paragraf penghubung. Jelaskan bagian kegiatan yang tidak terlihat di foto.', 'yka-portal' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:gallery {"columns":3,"linkTo":"none","align":"wide"} -->
<figure class="wp-block-gallery has-nested-images columns-3 is-cropped alignwide"><!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large"><img alt=""/></figure>
<!-- /wp:image -->

<!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large"><img alt=""/></figure>
<!-- /wp:image -->

<!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large"><img alt=""/></figure>
<!-- /wp:image --><figcaption class="blocks-gallery-caption wp-element-caption"><?php echo esc_html__( 'Galeri dokumentasi kegiatan.', 'yka-portal' ); ?></figcaption></figure>
<!-- /wp:gallery -->
