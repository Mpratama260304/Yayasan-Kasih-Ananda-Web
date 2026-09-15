# Arsitektur

Dokumen ini menjelaskan mengapa proyek ini dibangun seperti sekarang.
Keputusan dicatat beserta alasannya agar pengembang berikutnya tidak perlu
menebak.

---

## 1. Pilihan platform

**WordPress dengan tema hibrida kustom.**

Situs ini akan dikelola bertahun-tahun oleh staf sekolah, bukan oleh
pengembang. Yang dibutuhkan adalah alur penerbitan yang sudah dikenal:
tulis, unggah foto, pilih kategori, terbitkan.

Yang **tidak** dipakai, beserta alasannya:

| Ditolak | Alasan |
|---|---|
| Elementor, Divi, WPBakery | mengunci isi ke dalam markup milik plugin; sulit dipindahkan |
| WordPress headless + Next.js | memerlukan Node di produksi; hosting sekolah tidak menyediakannya |
| Tailwind sebagai dependensi produksi | memerlukan langkah build; menambah rantai alat yang tidak perlu |
| jQuery | tidak ada yang membutuhkannya di frontend |
| Custom post type untuk berita | memutus kompatibilitas dengan RSS, ekspor, plugin SEO, dan tema masa depan |

Produksi hanya memerlukan PHP dan MySQL. Node, Docker, dan Composer adalah
alat pengembangan.

---

## 2. Pemisahan tema dan plugin

```
wp-content/themes/yka-portal    tampilan
wp-content/plugins/yka-core     data dan perilaku institusi
```

Aturannya sederhana: **jika mematikan tema akan menghilangkan data, maka
data itu salah tempat.**

Karena itu taksonomi Unit Pendidikan, metadata artikel, pengaturan lembaga,
dan data terstruktur berada di plugin. Tema boleh diganti tanpa kehilangan
satu pun catatan institusi.

YKA Core juga tidak boleh menjadi pengganti WordPress. Ia hanya memodelkan
apa yang khas Yayasan Kasih Ananda.

---

## 3. Model konten

### Artikel

Artikel memakai post type bawaan `post`. Menu admin diberi label
**Berita & Dokumentasi**, tetapi tipenya tetap `post`. Ini menjaga
kompatibilitas dengan Gutenberg, RSS, ekspor WordPress, Rank Math, WPvivid,
dan tema apa pun di masa depan.

Permalink: `/berita/%postname%/`

Tanpa tanggal di URL, sehingga artikel tetap terasa relevan dan alamatnya
tidak perlu berubah.

### Unit Pendidikan — taksonomi `yka_unit`

Hierarkis, publik, dengan URL rata:

```
/unit/yayasan-kasih-ananda/
/unit/sd-kasih-ananda-1/
/unit/smp-kasih-ananda-1/
/unit/smk-kasih-ananda/
```

Inilah tulang punggung portal. Satu artikel terbit **satu kali** di satu
alamat kanonis, lalu muncul di halaman unitnya melalui taksonomi. Tidak ada
duplikasi konten antar unit — ini alasan utama arsitektur portal terpusat.

Satu artikel boleh ditandai beberapa unit (kegiatan bersama). Unit dengan
urutan tampil terkecil menjadi unit utama untuk breadcrumb dan skema.

Metadata tiap unit disimpan sebagai term meta: nama resmi, nama singkat,
pengantar, logo, foto hero, alamat, telepon, WhatsApp, email, tautan Maps,
situs lama, profil media sosial, dan urutan tampil.

### Metadata artikel

Enam bidang opsional, disimpan sebagai post meta:

| Kunci | Kegunaan |
|---|---|
| `yka_activity_date` | tanggal kegiatan berlangsung |
| `yka_activity_location` | lokasi kegiatan |
| `yka_photo_credit` | kredit fotografer |
| `yka_source_note` | catatan internal, tidak pernah tampil publik |
| `yka_social_caption` | teks media sosial khusus |
| `yka_social_hashtags` | tagar tambahan |

**Tanggal kegiatan bukan tanggal terbit.** Kegiatan sering didokumentasikan
beberapa hari sebelum artikelnya tayang. Skema memakai `datePublished` dari
WordPress; tanggal kegiatan ditampilkan terpisah pada panel fakta.

### Kategori

Delapan kategori yang benar-benar dipakai: Kegiatan, Prestasi, Pengumuman,
Akademik, Ekstrakurikuler, SPMB / PPDB, Yayasan, Informasi Sekolah.
Tag bersifat opsional dan dikurasi manual.

---

## 4. Struktur tema

```
yka-portal/
├── style.css              header tema saja
├── theme.json             token desain untuk Gutenberg
├── functions.php          hanya memuat inc/*
├── inc/
│   ├── setup.php          theme support, menu, ukuran gambar
│   ├── enqueue.php        aset dan preload fonta
│   ├── template-tags.php  fungsi render (yka_story, yka_unit_stamp, …)
│   ├── query.php          saringan arsip dan kueri terbatas
│   ├── navigation.php     penanganan menu
│   ├── integrations.php   Rank Math, WPvivid, deteksi YKA Core
│   └── blocks.php         gaya blok, pola, pembatasan peran
├── template-parts/
├── templates/             template halaman kustom
├── patterns/              pola blok Gutenberg
└── assets/{css,js,fonts,images}
```

### CSS

Enam berkas, dimuat berurutan dengan dependensi:

```
tokens.css      hanya token: warna, tipografi, jarak
base.css        elemen dasar, fokus, prefers-reduced-motion
layout.css      kontainer, grid, irama bagian
components.css  header, navigasi, kartu, tombol, footer
editorial.css   badan artikel, figur, galeri, berbagi
utilities.css   sedikit pembantu
```

Berkas tetap terpisah, tidak digabung. Satu berkas CSS raksasa tidak dapat
dirawat, sementara biaya beberapa berkas kecil yang sangat cacheable pada
HTTP/2 dapat diabaikan. Tidak ada langkah build, sehingga tidak ada risiko
berkas hasil build tertinggal usang.

### JavaScript

Dua berkas frontend, keduanya `defer`:

- `navigation.js` — menu ponsel, panel pencarian, submenu, peta ber-consent
- `share.js` — Web Share API dan salin tautan, hanya di halaman artikel

Semua tautan berbagi tetap berfungsi tanpa JavaScript. Isi artikel tidak
pernah bergantung pada JavaScript.

---

## 5. Desain

Sistem desain dirancang agar situs ini **tidak** dapat ditukar lognya lalu
menjadi situs perusahaan mana pun.

**Perangkat identitas yang berulang:**

1. *Unit stamp* — label persegi datar berisi nama unit, dengan garis tepi
   berwarna khas unitnya. Muncul di atas foto, di depan metadata, dan pada
   header artikel. Bukan pil, tidak pernah membulat.
2. *Garis editorial* — garis rambut 1px dan garis atas 2px hijau tua
   memisahkan bagian, seperti buletin cetak. Bukan kartu bayangan.
3. *Aksen emas pendek* — garis emas 3px sepanjang 96px, bukan selebar layar.
4. *Grid berita asimetris* — satu berita utama besar, dua berita sekunder,
   lalu kolom judul bergaris. Bukan tiga kartu identik.

**Warna:** mayoritas krem hangat dan putih; hijau sebagai identitas
struktural; emas sebagai aksen tipis. Nilai warna bersifat sementara sampai
logo resmi diberikan — seluruhnya terpusat di `tokens.css`.

**Tipografi:** Source Serif 4 untuk judul, Source Sans 3 untuk teks dan
antarmuka. Keduanya fonta variabel, dihosting sendiri, berlisensi SIL OFL.
Dua berkas untuk semua bobot.

**Radius:** 2–8px. **Bayangan:** hanya untuk menu melayang.

---

## 6. SEO dan data terstruktur

**Rank Math adalah pemilik tunggal** metadata SEO: judul, deskripsi,
canonical, robots, Open Graph, peta situs, breadcrumb, dan skema.

YKA Core tidak pernah menerbitkan versi kedua. Ia hanya:

- menambahkan node `School` pada halaman unit;
- menambahkan `articleSection` dan `contentLocation` pada artikel;
- memasok beberapa rasio gambar artikel (16:9, 4:3, 1:1);
- menyelaraskan gambar berbagi bawaan dengan foto hero YKA.

Bila Rank Math dinonaktifkan, `class-seo.php` dan `class-schema.php`
otomatis mengambil alih dan menerbitkan metadata lengkap sendiri. Situs tidak
pernah kehilangan canonical atau skema, dan tidak pernah menerbitkan dua.

Deteksi memakai `defined( 'RANK_MATH_VERSION' )` — deteksi fitur, bukan
pemeriksaan berkas plugin — agar tidak fatal bila plugin berubah nama.

---

## 7. Penjaga lingkungan

Kesalahan peluncuran paling mahal pada proyek seperti ini ada dua:

1. situs staging terindeks Google;
2. situs produksi tetap `noindex` setelah disalin dari staging.

`YKA\Core\Environment` mencegah keduanya. Sebuah instalasi dianggap produksi
hanya bila **dua** syarat terpenuhi:

```php
wp_get_environment_type() === 'production'
&& host ∈ { yayasankasihananda.com, www.yayasankasihananda.com }
```

Di luar itu, setiap halaman memuat `noindex, nofollow`, `robots.txt` menolak
seluruh perayapan, dan IndexNow dimatikan — semuanya saat runtime, bukan
sebagai nilai basis data yang bisa ikut terbawa migrasi tanpa disadari.

Lencana lingkungan (`LOCAL` / `STAGING` / `PRODUCTION`) tampil di bilah admin
untuk staf yang masuk, tidak pernah untuk pengunjung.

Tidak ada halaman dasbor yang menilai kesiapan peluncuran. Daftar periksanya
ada di `docs/PRODUCTION-CHECKLIST.md`, dikerjakan manusia, dan tidak ada satu
pun tombol yang mengubah pengaturan hanya karena nama host berubah.

---

## 8. Kinerja

- Tanpa kerangka kerja frontend, tanpa pustaka ikon, tanpa slider.
- Ikon berupa SVG sebaris, ditulis tangan, hanya di tempat yang bermakna.
- Fonta dihosting sendiri; hanya subset `latin` yang di-preload.
- Gambar hero memakai `yka_lcp_image()` yang menjamin `loading="eager"` dan
  `fetchpriority="high"` bertahan melewati KSES dan heuristik inti.
- Seluruh gambar lain `loading="lazy"` dengan `width`/`height` eksplisit.
- Kueri beranda dibatasi dan dikumpulkan: ID yang sudah dipakai dikirim ke
  `post__not_in` agar tidak ada artikel muncul dua kali dan tidak ada kueri
  berulang di dalam loop.
- Daftar tahun arsip di-cache satu hari dalam transient.

Tema tidak bergantung pada plugin cache mana pun.

---

## 9. Aksesibilitas

Target WCAG 2.2 AA.

- Tautan lewati ke konten, landmark semantik, urutan judul yang benar.
- Menu ponsel dan submenu dapat dioperasikan dengan papan tik; tidak ada
  informasi yang hanya muncul saat hover.
- Indikator fokus tidak pernah dihapus tanpa pengganti.
- Sasaran sentuh minimal 44px.
- `prefers-reduced-motion` dihormati.
- Saringan arsip berupa tautan sungguhan, bukan tombol ber-JavaScript.

---

## 10. Keamanan

- Seluruh keluaran di-escape; seluruh masukan disanitasi.
- Aksi admin dilindungi nonce dan pemeriksaan kapabilitas.
- ID media divalidasi sebagai lampiran sungguhan sebelum disimpan.
- Unggahan SVG hanya untuk administrator, tidak pernah untuk Dokumentator.
- `DISALLOW_FILE_EDIT` aktif; XML-RPC dimatikan.
- Tidak ada `eval`, `shell_exec`, penulisan berkas sembarangan, atau
  pengambilan URL jarak jauh saat merender halaman.
- Tidak ada kredensial di dalam repositori.

---

## 11. Batas yang sengaja tidak dilewati

- Tidak ada blok Gutenberg kustom. Blok inti sudah cukup dan lebih tahan
  terhadap pembaruan.
- Tidak ada tabel basis data kustom. Post, term, dan meta sudah memadai.
- Tidak ada mesin cadangan sendiri. WPvivid menanganinya.
- Tidak ada `llms.txt`, tidak ada "AI schema", tidak ada trik GEO. Keunggulan
  situs ini adalah dokumentasi orisinal, bukan rekayasa markup.
- Tidak ada penghitung tampilan, waktu baca wajib, atau testimoni.
