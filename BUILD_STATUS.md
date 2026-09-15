# Status Pembangunan

Terakhir diperbarui: 14 September 2026

Sebuah fase hanya ditandai **Selesai** bila fungsinya benar-benar berjalan
dan sudah diuji, bukan sekadar kodenya ada.

---

## Ringkasan

| Fase | Status |
|---|---|
| 1. Lingkungan pengembangan | Selesai |
| 2. Bootstrap WordPress | Selesai |
| 3. Fondasi tema dan token desain | Selesai |
| 4. Model konten YKA Core | Selesai |
| 5. Header, footer, navigasi | Selesai |
| 6. Beranda | Selesai |
| 7. Portal unit pendidikan | Selesai |
| 8. Arsip berita dan pencarian | Selesai |
| 9. Halaman artikel | Selesai |
| 10. Alur kerja redaksi | Selesai |
| 11. SEO, skema, Rank Math | Selesai |
| 12. Berbagi sosial | Selesai |
| 13. Aksesibilitas | Selesai |
| 14. Kinerja | Selesai |
| 15. Kesiapan migrasi WPvivid | Selesai |
| 16. Pengujian | Selesai |
| 17. Dokumentasi | Selesai |

---

## Rincian

### 1. Lingkungan pengembangan

- Devcontainer berbasis PHP 8.3 dengan Docker-in-Docker dan Node 22.
- Docker Compose: WordPress (PHP 8.3 + Apache), MariaDB 11.4, WP-CLI,
  Mailpit, phpMyAdmin (profil `tools`).
- Port 8080 / 8081 / 8025, diperiksa kosong sebelum dipakai.
- Skrip: `start.sh`, `bootstrap.sh`, `seed.sh`, `check.sh`, `reset-dev.sh`,
  `wp.sh`, `extract-core.sh`, `fix-docker-network.sh`.
- `.env.example` tanpa satu pun kredensial nyata; `.env` dihasilkan lokal
  dengan kata sandi basis data acak.

**Catatan:** Docker-in-Docker di Codespaces memiliki aturan firewall di dua
backend sekaligus, yang membuang lalu lintas antar-kontainer.
`fix-docker-network.sh` mendeteksi dan memperbaikinya; `start.sh`
memanggilnya otomatis bila diperlukan. Rinciannya di `docs/DEVELOPMENT.md`.

### 2. Bootstrap WordPress

- WordPress 7.1 diekstrak dari `wordpress-7.1.zip` ke `./wp` (di luar Git).
- Locale `id_ID`, zona waktu Asia/Jakarta, format tanggal `j F Y`.
- Permalink `/berita/%postname%/`, basis kategori `topik`.
- `.htaccess` ditulis oleh skrip karena WP-CLI menolak menulisnya dari luar
  Apache.
- Komentar publik dimatikan, feed RSS dipertahankan.
- Delapan kategori, dua belas halaman, tiga menu navigasi.
- Konten contoh bawaan WordPress dihapus bila belum disunting.
- Rank Math dan WPvivid dipasang dan dikonfigurasi.
- Seluruh skrip idempoten — aman dijalankan berulang.

### 3. Fondasi tema dan token desain

- `theme.json` v3: palet, skala tipografi, skala jarak, lebar tata letak.
  Warna kustom, gradien, dan duotone dimatikan.
- Enam berkas CSS modular dengan rantai dependensi.
- Fonta variabel Source Serif 4 dan Source Sans 3 dihosting sendiri
  (SIL OFL), hanya subset latin yang di-preload.
- Gaya editor Gutenberg menyerupai tampilan frontend.

### 4. Model konten YKA Core

- Taksonomi `yka_unit`, hierarkis, empat term dibuat saat aktivasi.
- Metadata unit: 13 bidang dengan pemilih media.
- Metadata artikel: 6 bidang, termasuk tanggal kegiatan terpisah.
- Pengaturan lembaga lewat Settings API, satu opsi array.
- Ukuran gambar: 1920×900, 1280×720, 1200×900, 1080×1080, 1200×630.
- Peran opsional **Dokumentator**.
- Aktivasi plugin bersifat menambah saja; tidak pernah menghapus apa pun.

### 5. Header, footer, navigasi

- Bilah utilitas hijau tua, header putih lengket, navigasi desktop dengan
  dropdown, panel pencarian, menu ponsel.
- Submenu terbuka lewat hover, fokus, **dan** tombol disclosure — tidak ada
  navigasi yang hanya dapat diakses lewat hover.
- Escape menutup menu dan pencarian serta mengembalikan fokus.
- Menu sepenuhnya dikelola dari **Tampilan → Menu**.

### 6. Beranda

- Hero dari pengaturan, dengan cadangan bila belum ada foto.
- Grid berita asimetris: satu berita utama, dua sekunder, kolom judul.
- Tiga kartu unit pendidikan dengan aksen warna berbeda.
- Bagian pengumuman dan prestasi, keduanya menampilkan keadaan kosong yang
  jujur bila belum ada isinya.
- Bagian profil yayasan dan kontak.
- Seluruhnya dari kueri; tidak ada ID artikel yang ditulis di dalam kode.

### 7. Portal unit pendidikan

- `/unit/{slug}/` dengan hero, pengantar, pengumuman, arsip berita unit,
  prestasi, dan kontak.
- Tautan situs lama ditampilkan sebagai tautan transisi bila diisi.
- Halaman indeks `/unit-pendidikan/` menampilkan seluruh unit.

### 8. Arsip berita dan pencarian

- Arsip `/berita/` dengan berita utama menonjol di halaman pertama.
- Saringan unit, kategori, dan tahun berupa tautan sungguhan.
- Tampilan tersaring otomatis `noindex, follow` agar tidak membentuk
  halaman tipis berlipat.
- Paginasi bernomor; tidak ada infinite scroll.
- Pencarian menampilkan jenis konten, unit, tanggal, dan kutipan, serta
  keadaan kosong yang berguna.
- Daftar tahun di-cache dalam transient.

### 9. Halaman artikel

- Breadcrumb, metadata unit dan kategori, H1, paragraf pembuka, baris
  penulis, foto utama beserta keterangan dan kredit.
- Panel fakta kegiatan bila tanggal atau lokasi diisi.
- Tanggal pembaruan hanya tampil bila perubahannya berarti (>24 jam).
- Alat berbagi, biografi penulis, artikel terkait seunit, ajakan ke halaman
  unit.
- HTML semantik: `article`, `header`, `time`, `figure`, `figcaption`.

### 10. Alur kerja redaksi

- Menu Posts diberi label **Berita & Dokumentasi**.
- Widget dasbor **Ruang Redaksi**: tombol tambah berita, hitungan per
  status, hitungan per unit, draf, terjadwal, terbit terakhir.
- Kolom daftar artikel: foto, judul, unit, kategori, penulis, tanggal
  kegiatan, status, tanggal terbit. Saringan unit tersedia.
- Panel **Kesiapan Publikasi** memperbarui diri secara langsung sambil
  mengetik, memakai `wp.data` tanpa langkah build.
- Empat pola blok Gutenberg.

### 11. SEO, skema, Rank Math

- Rank Math dikonfigurasi otomatis; tanpa itu ia membisu sepenuhnya.
- Satu pemilik metadata. Bila Rank Math nonaktif, YKA Core mengambil alih
  penuh — tidak pernah keduanya sekaligus.
- Skema: `EducationalOrganization`, `WebSite`, `School` per unit,
  `NewsArticle`, `BreadcrumbList`, `ImageObject`.
- Penjaga lingkungan memerlukan konstanta **dan** nama host yang cocok
  sebelum memperlakukan situs sebagai produksi.
- Peta situs bawaan WordPress dimatikan saat Rank Math aktif.

### 12. Berbagi sosial

- WhatsApp, Facebook, X sebagai tautan biasa; Web Share API dan salin
  tautan sebagai peningkatan progresif.
- Tidak ada SDK pihak ketiga.
- Penyusun teks media sosial di dalam editor dengan tombol salin.
- Gambar berbagi cadangan disinkronkan dengan foto hero YKA.

### 13. Aksesibilitas

- Tautan lewati ke konten, landmark, urutan judul benar.
- Menu dapat dioperasikan dengan papan tik; Escape menutup dan
  mengembalikan fokus.
- Indikator fokus tidak pernah dihapus.
- Sasaran sentuh 44px; `prefers-reduced-motion` dihormati.
- Diuji: tab, Escape, urutan judul, atribut alt.

### 14. Kinerja

- Dua berkas JavaScript frontend, keduanya `defer`, total di bawah 6 KB.
- Tanpa kerangka kerja, tanpa pustaka ikon, tanpa slider.
- `yka_lcp_image()` menjamin `fetchpriority="high"` bertahan melewati KSES.
- Seluruh gambar membawa `width` dan `height`.
- Kueri beranda dibatasi dan tidak saling mengulang.

### 15. Kesiapan migrasi WPvivid

- WPvivid terpasang dan aktif.
- **Yayasan → Ekspor & Impor**: susunan situs sebagai satu berkas JSON,
  diterapkan ke WordPress kosong dengan satu klik. Hanya menambah dan
  memperbarui; tidak pernah menghapus. Diuji dari ekspor sampai impor pada
  instalasi WordPress kosong, termasuk uji idempoten.
- Tidak ada satu pun jalur kode yang melakukan restore otomatis.
- Tidak ada URL lingkungan yang ditulis di dalam kode — diperiksa oleh
  `check.sh`.
- Dokumentasi metode manual dan otomatis.

### 16. Pengujian

- **PHPCS** dengan WordPress Coding Standards: 0 kesalahan.
- **`check.sh`**: 38 pemeriksaan lolos.
- **Playwright**: 32 uji lolos di desktop dan ponsel.
- Tangkapan layar pada 375, 768, dan 1440 piksel untuk tinjauan manual.

### 17. Dokumentasi

Sebelas berkas di `docs/` beserta `README.md`, `BUILD_STATUS.md`, dan
`CHANGELOG.md`.

---

## Keputusan yang dicatat

| Keputusan | Alasan |
|---|---|
| Post type `post`, bukan tipe kustom | menjaga kompatibilitas RSS, ekspor, SEO, tema masa depan |
| Model data di plugin, bukan tema | data institusi bertahan meski tema diganti |
| Enam berkas CSS, tanpa penggabungan | dapat dirawat; tanpa langkah build; tanpa risiko berkas usang |
| Fonta dihosting sendiri | tanpa permintaan pihak ketiga; dua berkas untuk semua bobot |
| Rank Math dikonfigurasi lewat skrip | tanpa itu ia tidak menerbitkan metadata apa pun |
| Produksi memerlukan konstanta **dan** host | salinan staging tidak dapat bocor ke indeks |
| Meta box klasik, bukan panel React | tanpa langkah build; tetap berfungsi di kedua editor |
| Tanpa formulir kontak di MVP | detail kontak melayani orang tua lebih baik daripada formulir yang pengirimannya belum terjamin |
| Peta Google hanya dimuat setelah diklik | tidak ada permintaan pihak ketiga saat halaman dibuka |

---

## Yang masih dibutuhkan dari Yayasan

Hal-hal berikut tidak dapat dibuat oleh pengembang. Sampai diberikan,
situs menampilkan penanda `[…]` yang jelas, bukan data karangan.

| Kebutuhan | Dipakai di |
|---|---|
| Berkas logo resmi (SVG atau PNG transparan) | header, footer, skema, favicon |
| Alamat resmi kantor yayasan | footer, halaman kontak, skema |
| Telepon, WhatsApp, email resmi | header, footer, halaman kontak |
| Profil singkat yayasan (2–3 kalimat) | beranda, footer, deskripsi meta |
| Tahun pendirian | skema `foundingDate` |
| Sejarah yayasan | halaman Sejarah |
| Visi dan misi resmi | halaman Visi & Misi |
| Struktur organisasi | halaman Struktur Organisasi |
| Data tiap unit: nama resmi, alamat, kontak, logo | halaman unit, skema `School` |
| URL akun media sosial resmi | footer, `sameAs` |
| Tautan Google Maps tiap lokasi | halaman kontak |
| Foto dokumentasi asli | hero, kartu unit, galeri |
| Nama staf untuk akun penulis | baris penulis artikel |
| Kebijakan privasi | halaman Kebijakan Privasi |
| Informasi SPMB/PPDB | tombol ajakan di header |

---

## Keterbatasan yang diketahui

1. **Seluruh gambar saat ini adalah placeholder buatan.** Digambar dengan
   GD dan diberi tanda `[DEMO]`, bukan foto stok. Harus diganti dengan
   dokumentasi asli sebelum tayang.
2. **Nilai warna bersifat sementara.** Diturunkan dari deskripsi merek,
   bukan dari logo resmi. Seluruhnya terpusat di `tokens.css`.
3. **Validasi Rich Results memerlukan URL publik.** Tidak dapat dijalankan
   dari Codespaces; harus dikerjakan setelah produksi tayang.
4. **Core Web Vitals lapangan belum dapat diukur.** Memerlukan pengunjung
   nyata di hosting produksi.
5. **Belum ada formulir kontak.** Disengaja untuk MVP.
6. **Migrasi otomatis WPvivid tidak berfungsi dari Codespaces.** Alamat
   yang diteruskan berada di balik proksi berautentikasi. Gunakan metode
   unggah manual.
7. **Baris penulis memakai satu akun redaksi.** Akun staf sungguhan perlu
   dibuat sebelum tayang.
