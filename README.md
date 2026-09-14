# Yayasan Kasih Ananda — Web Portal

Portal berita, dokumentasi, dan informasi resmi Yayasan Kasih Ananda beserta
unit pendidikannya: **SD Kasih Ananda I**, **SMP Kasih Ananda I**, dan
**SMK Kasih Ananda**.

Situs ini bukan sekadar profil lembaga. Ia adalah platform penerbitan: tim
dokumentasi sekolah menerbitkan artikel kegiatan secara rutin, dan seluruh
arsitekturnya dirancang untuk itu.

| | |
|---|---|
| Domain produksi | `https://yayasankasihananda.com` |
| Domain staging | `https://yayasankasihananda.us.ci` |
| Bahasa | Bahasa Indonesia (`id_ID`) |
| Zona waktu | Asia/Jakarta |
| Platform | WordPress 7.1 · PHP 8.3 · MariaDB 11.4 |

---

## Isi repositori

Repositori memuat kode kustom dan konfigurasi pengembangan saja. Inti
WordPress, unggahan media, basis data, dan arsip cadangan **tidak** pernah
dikomit.

```
.devcontainer/       konfigurasi GitHub Codespaces
.github/workflows/   pemeriksaan otomatis
config/php/          penyetelan PHP untuk kontainer pengembangan
docs/                dokumentasi operasional
scripts/             perintah pengembangan (start, bootstrap, seed, check)
tests/e2e/           uji asap Playwright
wp-content/
  themes/yka-portal/ tema editorial kustom
  plugins/yka-core/  model data dan alur kerja redaksional
docker-compose.yml   layanan pengembangan
wordpress-7.1.zip    inti WordPress, diekstrak ke ./wp saat bootstrap
```

---

## Menjalankan di GitHub Codespaces

```bash
./scripts/start.sh        # menyalakan layanan Docker
./scripts/bootstrap.sh    # memasang dan mengonfigurasi WordPress
./scripts/seed.sh         # konten demo (opsional, khusus pengembangan)
```

Setelah `start.sh` selesai, alamat situs dicetak di terminal. Di Codespaces
alamatnya berbentuk:

```
https://<nama-codespace>-8080.app.github.dev
```

Layanan lain:

| Layanan | Port | Keterangan |
|---|---|---|
| WordPress | 8080 | situs dan `/wp-admin/` |
| Mailpit | 8025 | seluruh email pengembangan berhenti di sini |
| phpMyAdmin | 8081 | opsional: `./scripts/start.sh --tools` |

### Masuk ke dasbor

Buka `/wp-admin/`.

Nama pengguna dan kata sandi administrator dibaca dari berkas `.env` lokal
(`WP_ADMIN_USER`, `WP_ADMIN_PASSWORD`). Berkas `.env` tidak pernah dikomit,
dan tidak ada kredensial di dalam repositori ini.

Bila `.env` belum ada, `start.sh` membuatnya dari `.env.example` dan
menghasilkan kata sandi basis data acak. Sunting nilai administrator sebelum
menjalankan `bootstrap.sh`.

---

## Perintah

| Perintah | Kegunaan |
|---|---|
| `./scripts/start.sh` | menyalakan kontainer; tambahkan `--tools` untuk phpMyAdmin |
| `./scripts/bootstrap.sh` | memasang WordPress, tema, plugin, halaman, dan menu. Aman diulang |
| `./scripts/seed.sh` | menambahkan artikel demo berawalan `[DEMO]` |
| `./scripts/seed.sh --remove` | menghapus seluruh konten demo |
| `./scripts/check.sh` | pemeriksaan kode dan situs |
| `./scripts/check.sh --lint` | pemeriksaan kode saja |
| `./scripts/wp.sh <perintah>` | menjalankan WP-CLI, misalnya `./scripts/wp.sh plugin list` |
| `./scripts/reset-dev.sh` | menghapus basis data dan media pengembangan lalu mulai ulang |
| `./scripts/fix-docker-network.sh` | memperbaiki jaringan antar-kontainer bila diperlukan |
| `npm test` | uji asap Playwright |
| `npm run shots` | tangkapan layar di lebar 375, 768, dan 1440 piksel |
| `vendor/bin/phpcs` | standar penulisan kode WordPress |

---

## Tema dan plugin

**`wp-content/themes/yka-portal`** — tema hibrida. Struktur situs diatur oleh
template PHP; isi artikel disunting dengan Gutenberg. Tidak memakai pembangun
halaman, tidak memakai kerangka kerja frontend.

**`wp-content/plugins/yka-core`** — taksonomi Unit Pendidikan, metadata
artikel, pengaturan lembaga, penjaga lingkungan (environment guard),
integrasi SEO, data terstruktur, dan bantuan berbagi.

Pemisahan ini disengaja: bila suatu hari tema diganti, model data institusi
tetap utuh.

---

## Membuat cadangan untuk migrasi

WPvivid sudah terpasang. Dari dasbor:

1. **WPvivid Backup → Backup Now**, pilih *Database + Files*.
2. Unduh seluruh bagian berkas cadangan.
3. Ikuti [docs/MIGRATION.md](docs/MIGRATION.md).

Jangan pernah menyimpan arsip cadangan di dalam repositori.

---

## Dokumentasi

| Berkas | Isi |
|---|---|
| [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) | keputusan teknis dan struktur kode |
| [docs/DEVELOPMENT.md](docs/DEVELOPMENT.md) | alur kerja pengembang dan pemecahan masalah |
| [docs/EDITORIAL-GUIDE.md](docs/EDITORIAL-GUIDE.md) | panduan menulis dan mengunggah foto |
| [docs/SEO-SETUP.md](docs/SEO-SETUP.md) | Search Console, Bing, peta situs, IndexNow |
| [docs/SOCIAL-SHARING.md](docs/SOCIAL-SHARING.md) | pratinjau berbagi dan teks media sosial |
| [docs/MIGRATION.md](docs/MIGRATION.md) | pemindahan ke staging dan produksi |
| [docs/PRODUCTION-CHECKLIST.md](docs/PRODUCTION-CHECKLIST.md) | daftar periksa sebelum tayang |
| [docs/BACKUP-RESTORE.md](docs/BACKUP-RESTORE.md) | strategi cadangan |
| [docs/REDIRECT-PLAN.md](docs/REDIRECT-PLAN.md) | rencana pengalihan domain lama |
| [docs/LEGACY-CONTENT-MIGRATION.md](docs/LEGACY-CONTENT-MIGRATION.md) | pemindahan isi situs lama |
| [BUILD_STATUS.md](BUILD_STATUS.md) | status setiap fase pembangunan |

---

## Lisensi

GPL-2.0-or-later, mengikuti WordPress. Berkas fonta di
`wp-content/themes/yka-portal/assets/fonts/` memakai SIL Open Font License 1.1;
rinciannya ada pada `LICENSE.md` di dalam folder tersebut.
