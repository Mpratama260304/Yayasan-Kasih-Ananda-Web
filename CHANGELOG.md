# Changelog

Format mengikuti [Keep a Changelog](https://keepachangelog.com/id/1.1.0/).
Proyek ini memakai [Semantic Versioning](https://semver.org/lang/id/).

---

## [1.0.1] — 2026-09-15

### Diperbaiki

- **Dev container gagal dibangun dan Codespace jatuh ke recovery mode.**
  Image `devcontainers/php:1-8.3-bookworm` membawa sumber apt milik Yarn yang
  kunci penandatangannya sudah tidak cocok lagi, sehingga `apt-get update`
  berakhir dengan galat "is not signed" dan keluar dengan kode 100. Fitur
  Docker-in-Docker menjalankan `apt-get update` sebelum memasang apa pun,
  jadi pembangunan kontainer berhenti di sana. `.devcontainer/Dockerfile`
  baru membuang sumber apt tersebut sebelum fitur mana pun dipasang.
- **Setiap halaman mengalihkan ke `https://localhost/` di balik terowongan
  Codespaces.** `redirect_canonical()` menyusun ulang alamat yang diminta
  dari `HTTP_HOST` dan `SERVER_PORT`, yang masih menunjuk `localhost:8080`
  meski `WP_HOME` sudah benar. `config/wp/yka-config.php` kini menuliskan
  ulang keduanya menjadi alamat publik.

---

## [1.0.0] — 2026-09-14

Rilis pertama: portal berita dan dokumentasi Yayasan Kasih Ananda, siap
dipindahkan ke staging.

### Ditambahkan

**Lingkungan pengembangan**

- Devcontainer GitHub Codespaces dengan Docker-in-Docker, PHP 8.3, Node 22.
- Docker Compose: WordPress 7.1, MariaDB 11.4, WP-CLI, Mailpit, phpMyAdmin.
- Konfigurasi wp-config proyek pada `config/wp/yka-config.php`: alamat situs
  diselesaikan dari `X-Forwarded-Host`, lalu `YKA_PUBLIC_URL`, lalu `Host`,
  dengan daftar izin nama host agar header palsu tidak dapat mengalihkan
  situs.
- Skrip `start`, `bootstrap`, `seed`, `check`, `reset-dev`, `wp`,
  `extract-core`, `fix-docker-network`.
- Bootstrap idempoten: locale `id_ID`, zona waktu Asia/Jakarta, permalink
  `/berita/%postname%/`, kategori, halaman, dan menu.
- Konten demo berawalan `[DEMO]` dengan gambar placeholder buatan GD,
  dapat dihapus dalam satu perintah.

**Tema YKA Portal**

- Tema editorial hibrida: template PHP untuk struktur, Gutenberg untuk isi.
- `theme.json` v3 dengan palet, skala tipografi, dan skala jarak terkunci.
- CSS modular enam berkas; tidak ada langkah build.
- Fonta variabel Source Serif 4 dan Source Sans 3 dihosting sendiri
  (SIL OFL 1.1).
- Beranda dengan grid berita asimetris.
- Portal per unit pendidikan di `/unit/{slug}/`.
- Arsip berita dengan saringan unit, kategori, dan tahun berupa tautan.
- Halaman artikel dengan panel fakta kegiatan, kredit foto, artikel
  terkait, dan alat berbagi.
- Halaman pencarian, 404, serta template kustom untuk Unit Pendidikan,
  Prestasi, Pengumuman, Galeri, dan Kontak.
- Empat pola blok Gutenberg.
- Dua berkas JavaScript frontend, keduanya `defer`.

**Plugin YKA Core**

- Taksonomi `yka_unit` dengan empat term dan 13 bidang metadata.
- Enam bidang metadata artikel, termasuk tanggal kegiatan yang terpisah
  dari tanggal terbit.
- Pengaturan lembaga lewat Settings API.
- Lima ukuran gambar editorial dan sosial.
- Widget dasbor Ruang Redaksi.
- Kolom dan saringan daftar artikel.
- Panel Kesiapan Publikasi yang memperbarui diri secara langsung.
- Penyusun teks media sosial dengan tombol salin.
- Peran opsional Dokumentator.
- Integrasi Rank Math dengan mode cadangan mandiri.
- Data terstruktur: `EducationalOrganization`, `WebSite`, `School`,
  `NewsArticle`, `BreadcrumbList`, `ImageObject`.
- Penjaga lingkungan dengan lencana bilah admin.
- Halaman Kesiapan Produksi dengan 13 pemeriksaan.
- Perutean email pengembangan ke Mailpit.

**Pengujian dan dokumentasi**

- PHPCS dengan WordPress Coding Standards.
- 32 uji asap Playwright di desktop dan ponsel.
- `check.sh` dengan 38 pemeriksaan kode dan situs.
- Alur kerja GitHub Actions.
- Sebelas dokumen operasional di `docs/`.

### Keamanan

- Seluruh keluaran di-escape; seluruh masukan disanitasi.
- Aksi admin dilindungi nonce dan pemeriksaan kapabilitas.
- ID media divalidasi sebagai lampiran sungguhan sebelum disimpan.
- Unggahan SVG dibatasi untuk administrator.
- `DISALLOW_FILE_EDIT` aktif; XML-RPC dimatikan.
- Tidak ada kredensial di dalam repositori.
- Aktivasi plugin tidak pernah menimpa atau memulihkan situs tujuan.

### Catatan

- Seluruh gambar adalah placeholder `[DEMO]` buatan sendiri, bukan foto
  stok. Harus diganti dengan dokumentasi asli.
- Nilai warna bersifat sementara sampai logo resmi diberikan; seluruhnya
  terpusat di `tokens.css`.
- Daftar aset dan informasi yang masih dibutuhkan dari yayasan ada di
  `BUILD_STATUS.md`.
