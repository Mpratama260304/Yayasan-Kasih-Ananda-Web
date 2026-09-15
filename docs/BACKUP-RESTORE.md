# Cadangan dan Pemulihan

Alat: **WPvivid Backup & Migration**.

Proyek ini sengaja tidak menulis mesin cadangan sendiri. Cadangan adalah
masalah yang sudah diselesaikan dengan baik, dan salah menuliskannya
berarti kehilangan data.

---

## Apa yang perlu dicadangkan

| Bagian | Isi | Kritis? |
|---|---|---|
| Basis data | artikel, halaman, unit, pengaturan, pengguna | ya |
| `wp-content/uploads/` | seluruh foto dokumentasi | ya |
| `wp-content/themes/yka-portal/` | tema kustom | ada di Git |
| `wp-content/plugins/yka-core/` | plugin kustom | ada di Git |
| Plugin pihak ketiga | Rank Math, WPvivid | dapat dipasang ulang |
| Inti WordPress | | dapat diunduh ulang |

Dua baris pertama tidak dapat dibuat ulang dari mana pun. Foto kegiatan
sekolah yang hilang hilang selamanya.

---

## Jadwal yang disarankan

| Kapan | Jenis | Simpan |
|---|---|---|
| Harian | basis data saja | 7 hari terakhir |
| Mingguan | penuh (basis data + berkas) | 4 minggu terakhir |
| Bulanan | penuh | 6 bulan terakhir |
| Sebelum pembaruan apa pun | penuh, manual | sampai dipastikan aman |
| Sebelum migrasi | penuh, manual | permanen |

Artikel sekolah terbit beberapa kali seminggu, sehingga cadangan basis data
harian sudah memadai. Unggahan media jarang berubah drastis, sehingga
cadangan penuh mingguan cukup.

---

## Menyiapkan jadwal

**WPvivid Backup → Schedule**

1. Aktifkan **Enable backup schedule**.
2. Pilih frekuensi.
3. Pilih **Database + Files** untuk cadangan penuh.
4. Setel **Backup retention** sesuai tabel di atas.
5. Setel tujuan penyimpanan jarak jauh.

---

## Penyimpanan di luar server

Cadangan yang hanya tersimpan di server yang sama tidak melindungi dari
kegagalan server, peretasan, atau berakhirnya masa sewa hosting.

WPvivid mendukung Google Drive, Dropbox, Amazon S3, Microsoft OneDrive, dan
SFTP.

**Kredensial penyimpanan tidak boleh masuk ke repositori.** Konfigurasikan
langsung lewat dasbor WordPress, di mana WPvivid menyimpannya di basis data
situs.

Saran untuk yayasan: gunakan Google Drive pada akun institusi, bukan akun
pribadi seorang staf. Akun pribadi akan menjadi masalah ketika orangnya
berhenti.

---

## Cadangan manual

Sebelum tindakan berisiko apa pun:

```
WPvivid Backup → Backup & Restore
→ Database + Files
→ Backup Now
```

Selalu lakukan sebelum:

- memperbarui WordPress, tema, atau plugin;
- memasang plugin baru;
- mengubah struktur permalink;
- migrasi apa pun;
- penyuntingan massal konten.

---

## Memulihkan

1. **WPvivid Backup → Backups**
2. Pilih cadangan
3. **Restore**
4. Konfirmasikan

Pemulihan mengganti isi situs. Lakukan hanya bila memang itu yang
diinginkan.

Setelah pemulihan:

- masuk lagi memakai kredensial **dari saat cadangan dibuat**;
- **Pengaturan → Permalink → Simpan Perubahan**;
- **Pengaturan → Membaca** — pastikan visibilitas mesin pencari sesuai
  lingkungan;
- kosongkan cache bila hosting memakainya.

---

## Menguji pemulihan

Cadangan yang belum pernah diuji bukanlah cadangan.

Sekali setiap enam bulan:

1. Buat instalasi WordPress sementara pada subdomain.
2. Pasang WPvivid di sana.
3. Pulihkan cadangan terakhir.
4. Pastikan artikel, foto, dan pengaturan lengkap.
5. Hapus instalasi sementara itu.

Catat tanggal ujinya.

---

## Cadangan lewat WP-CLI

Di lingkungan pengembangan:

```bash
# Ekspor basis data
./scripts/wp.sh db export /var/www/html/wp-content/uploads/yka-dev.sql

# Impor basis data
./scripts/wp.sh db import /var/www/html/wp-content/uploads/yka-dev.sql
```

Berkas `.sql` sudah masuk `.gitignore`. Jangan pernah mengomitnya: ia
memuat hash kata sandi pengguna.

---

## Retensi dan privasi

Cadangan memuat data pribadi: nama pengguna, alamat email, dan hash kata
sandi.

- Simpan cadangan di tempat yang aksesnya terbatas.
- Jangan membagikannya lewat obrolan atau email biasa.
- Hapus cadangan lama sesuai jadwal retensi.
- Jangan menaruh cadangan di folder yang dapat diakses publik lewat web.

Bila cadangan disimpan lewat SFTP di server yang sama, pastikan direktorinya
berada di luar akar dokumen web.
