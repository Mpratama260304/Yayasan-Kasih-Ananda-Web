# Daftar Periksa Produksi

Kerjakan berurutan. Jangan melewati langkah cadangan.

Halaman **Yayasan → Kesiapan Produksi** di dalam dasbor memeriksa sebagian
besar butir teknis secara otomatis. Dokumen ini mencakup sisanya.

---

## Sebelum migrasi

### Cadangan

- [ ] Cadangan penuh WPvivid di situs sumber (Database + Files)
- [ ] Seluruh bagian berkas cadangan sudah diunduh dan terverifikasi
- [ ] Cadangan disimpan di luar repositori
- [ ] Cadangan penuh situs tujuan sudah dibuat, meski situsnya kosong

### Konten

- [ ] Seluruh artikel `[DEMO]` dihapus (`./scripts/seed.sh --remove`)
- [ ] Gambar demo dihapus dari media library
- [ ] Halaman placeholder diisi teks resmi, atau sengaja dibiarkan kosong
      dengan penanda yang jelas
- [ ] Tidak ada nama, alamat, nomor telepon, atau statistik karangan
- [ ] Setiap prestasi yang tayang sudah diverifikasi unit terkait

### Identitas lembaga

- [ ] Logo resmi diunggah lewat **Tampilan → Sesuaikan → Identitas Situs**
- [ ] Ikon situs (favicon) diunggah di tempat yang sama
- [ ] **Yayasan → Pengaturan** terisi: nama resmi, deskripsi, alamat,
      telepon, WhatsApp, email
- [ ] Tautan media sosial hanya berisi akun resmi yang terverifikasi
- [ ] Metadata unit terisi untuk SD, SMP, SMK: nama resmi, pengantar,
      alamat, kontak, logo, foto hero
- [ ] Foto hero beranda memakai fotografi asli Yayasan Kasih Ananda

### Penulis

- [ ] Akun staf sungguhan dibuat; tidak ada artikel berpenulis "Admin"
- [ ] Nama tampilan setiap penulis wajar untuk dibaca publik
- [ ] Peran ditetapkan sesuai kebijakan (Editor, Author, atau Dokumentator)

---

## Server tujuan

- [ ] DNS `yayasankasihananda.com` menunjuk ke server produksi
- [ ] DNS `www` menunjuk ke tempat yang sama
- [ ] Sertifikat SSL aktif dan diperbarui otomatis
- [ ] `http://` mengalihkan ke `https://`
- [ ] `www` dan non-`www` sepakat pada satu domain kanonis
- [ ] PHP 8.1 atau lebih baru
- [ ] MySQL 8 atau MariaDB 10.6 ke atas
- [ ] Ekstensi PHP tersedia: `mysqli`, `gd` atau `imagick`, `mbstring`,
      `curl`, `zip`, `exif`, `intl`
- [ ] `memory_limit` minimal 256M
- [ ] `upload_max_filesize` minimal 64M
- [ ] `max_execution_time` minimal 300 untuk proses restore

### wp-config.php di server tujuan

```php
define( 'WP_ENVIRONMENT_TYPE', 'production' );
define( 'DISALLOW_FILE_EDIT', true );
define( 'WP_DEBUG', false );
define( 'WP_DEBUG_DISPLAY', false );
define( 'AUTOMATIC_UPDATER_DISABLED', false );
```

- [ ] Kunci keamanan (salts) dihasilkan ulang untuk situs produksi
- [ ] Kredensial basis data khusus produksi, tidak dipakai bersama
- [ ] `DISALLOW_FILE_EDIT` aktif

---

## Migrasi

- [ ] WPvivid terpasang di situs tujuan
- [ ] Cadangan diunggah lengkap, seluruh bagiannya
- [ ] Restore dijalankan **manual** dan tuntas
- [ ] Masuk kembali memakai kredensial administrator situs sumber

---

## Setelah migrasi

### URL dan permalink

- [ ] **Pengaturan → Permalink → Simpan Perubahan** (menulis ulang `.htaccess`)
- [ ] `siteurl` dan `home` bernilai `https://yayasankasihananda.com`
- [ ] Tautan menu mengarah ke domain produksi
- [ ] Gambar tampil di seluruh halaman
- [ ] Tidak ada peringatan mixed content di konsol peramban
- [ ] `/wp-json/` merespons

### Pengindeksan — butir paling penting

- [ ] **Pengaturan → Membaca** — "Cegah mesin pencari mengindeks situs ini"
      dalam keadaan **tidak tercentang**
- [ ] `curl -s https://yayasankasihananda.com/ | grep 'name="robots"'`
      tidak memuat `noindex`
- [ ] `https://yayasankasihananda.com/robots.txt` tidak memuat
      `Disallow: /`
- [ ] **Yayasan → Kesiapan Produksi** menampilkan lencana `PRODUCTION`
- [ ] Setiap butir wajib pada halaman itu berstatus terpenuhi

> Inilah kesalahan peluncuran paling mahal: situs produksi tetap `noindex`
> karena disalin dari staging. Dua pemeriksaan `curl` di atas menyelesaikan
> keraguan dalam sepuluh detik.

### Rank Math

- [ ] Rank Math aktif dan merupakan satu-satunya plugin SEO
- [ ] Nama dan logo organisasi benar
- [ ] Templat judul dan deskripsi benar
- [ ] `/sitemap_index.xml` dapat dibuka dan memuat URL produksi
- [ ] Breadcrumb tampil dalam bahasa Indonesia
- [ ] Modul Redirections aktif bila akan dipakai
- [ ] IndexNow aktif — **hanya setelah domain produksi tayang**

### Pemeriksaan sumber halaman

Untuk beranda, satu artikel, dan satu halaman unit, pastikan **tepat satu**
dari masing-masing:

- [ ] `<title>`
- [ ] `rel="canonical"`
- [ ] `meta name="description"`
- [ ] `og:title`, `og:image`, `og:url`
- [ ] `twitter:card`
- [ ] blok `application/ld+json`

Perintah cepat:

```bash
for u in / /berita/nama-artikel/ /unit/smp-kasih-ananda-1/; do
  echo "== $u"
  curl -s "https://yayasankasihananda.com$u" \
    | grep -cE '<title|rel="canonical"|name="description"|og:title|ld\+json'
done
```

### Data terstruktur

- [ ] Beranda lolos Rich Results Test
- [ ] Artikel menghasilkan `NewsArticle` yang sah
- [ ] Halaman unit menghasilkan `School` dengan `parentOrganization`
- [ ] Breadcrumb sah
- [ ] `sameAs` hanya memuat profil resmi
- [ ] Tidak ada skema `Event` pada artikel dokumentasi yang sudah lewat
- [ ] Tidak ada `AggregateRating` atau `Review`

### Berbagi ke media sosial

- [ ] Facebook Sharing Debugger menampilkan gambar dan judul yang benar
- [ ] Pratinjau WhatsApp benar (jalankan "Scrape Again" bila perlu)
- [ ] Tombol berbagi berfungsi di ponsel
- [ ] Salin tautan berfungsi

### Search Console dan Bing

- [ ] Properti domain diverifikasi di Google Search Console
- [ ] Peta situs dikirimkan
- [ ] Properti diverifikasi di Bing Webmaster Tools
- [ ] Peta situs dikirimkan ke Bing
- [ ] URL Inspection pada beranda menyatakan "URL is available to Google"

### Fungsional

- [ ] Beranda tampil di ponsel dan desktop
- [ ] Arsip berita dan paginasinya bekerja
- [ ] Keempat arsip unit bekerja
- [ ] Artikel dengan galeri tampil benar
- [ ] Artikel tanpa gambar utama tampil benar
- [ ] Pencarian mengembalikan hasil
- [ ] Pencarian tanpa hasil menampilkan keadaan kosong yang berguna
- [ ] URL yang tidak ada mengembalikan HTTP 404
- [ ] Feed RSS bekerja di `/feed/`
- [ ] Menu ponsel dapat dibuka dan ditutup
- [ ] Navigasi papan tik bekerja; indikator fokus terlihat

### Email

- [ ] Uji pemulihan kata sandi benar-benar terkirim
- [ ] Email berasal dari domain yayasan, bukan alamat hosting
- [ ] SPF dan DKIM dikonfigurasi bila memakai SMTP kustom

### Kinerja

- [ ] PageSpeed Insights dijalankan untuk beranda dan satu artikel
- [ ] Elemen LCP adalah foto hero, bukan blok teks
- [ ] Foto hero tidak lazy-load dan membawa `fetchpriority="high"`
- [ ] Tidak ada pergeseran tata letak yang terlihat saat memuat
- [ ] Plugin cache dikonfigurasi bila hosting menyediakannya —
      **hanya satu**

### Aksesibilitas

- [ ] Tab dari awal halaman memunculkan tautan lewati ke konten
- [ ] Menu ponsel dapat dibuka dan ditutup dengan papan tik
- [ ] Indikator fokus terlihat di seluruh tautan dan tombol
- [ ] Urutan judul benar pada artikel
- [ ] Seluruh gambar bermakna memiliki teks alternatif
- [ ] Kontras warna memadai

---

## Peluncuran

- [ ] Situs lama SMP dan SMK **belum** dialihkan — lihat
      [REDIRECT-PLAN.md](REDIRECT-PLAN.md)
- [ ] Peta pengalihan disiapkan dan disetujui
- [ ] Pemangku kepentingan sudah meninjau situs
- [ ] Cadangan penuh pasca-peluncuran dibuat
- [ ] Jadwal cadangan otomatis aktif — lihat
      [BACKUP-RESTORE.md](BACKUP-RESTORE.md)
- [ ] Kredensial administrator diserahkan dengan aman, tidak lewat obrolan
      atau email biasa

---

## Minggu pertama setelah tayang

- [ ] Search Console: periksa kesalahan perayapan
- [ ] Search Console: pastikan halaman mulai terindeks
- [ ] Periksa `wp-content/debug.log` untuk galat PHP
- [ ] Pastikan artikel baru muncul di beranda secara otomatis
- [ ] Uji menerbitkan satu artikel sungguhan dari awal sampai akhir
- [ ] Pastikan pratinjau berbagi bekerja untuk artikel baru itu
