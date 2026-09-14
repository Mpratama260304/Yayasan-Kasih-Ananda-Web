<?php
/**
 * Title: Pengumuman
 * Slug: yka-portal/pengumuman
 * Categories: yka
 * Description: Pengumuman resmi: kalimat pembuka, tanggal penting, rincian, dan narahubung.
 * Keywords: pengumuman, informasi, jadwal, spmb, ppdb
 * Viewport Width: 900
 *
 * @package YKA_Portal
 */

?>
<!-- wp:paragraph -->
<p><?php echo esc_html__( 'Kalimat pembuka yang langsung menyampaikan inti pengumuman. Hindari kalimat pengantar yang panjang.', 'yka-portal' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:group {"className":"is-style-yka-callout","layout":{"type":"constrained"}} -->
<div class="wp-block-group is-style-yka-callout"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading"><?php echo esc_html__( 'Tanggal penting', 'yka-portal' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:list {"className":"is-style-yka-checklist"} -->
<ul class="wp-block-list is-style-yka-checklist"><!-- wp:list-item -->
<li><?php echo esc_html__( 'Tanggal mulai: [isi tanggal]', 'yka-portal' ); ?></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><?php echo esc_html__( 'Batas akhir: [isi tanggal]', 'yka-portal' ); ?></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><?php echo esc_html__( 'Tempat: [isi lokasi]', 'yka-portal' ); ?></li>
<!-- /wp:list-item --></ul>
<!-- /wp:list --></div>
<!-- /wp:group -->

<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading"><?php echo esc_html__( 'Rincian', 'yka-portal' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php echo esc_html__( 'Uraikan ketentuan, persyaratan, atau prosedur yang perlu diketahui. Gunakan daftar bila langkahnya berurutan.', 'yka-portal' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading"><?php echo esc_html__( 'Narahubung', 'yka-portal' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php echo esc_html__( 'Nama bagian atau unit yang dapat dihubungi beserta nomor resminya. Jangan mencantumkan nomor pribadi tanpa izin.', 'yka-portal' ); ?></p>
<!-- /wp:paragraph -->
