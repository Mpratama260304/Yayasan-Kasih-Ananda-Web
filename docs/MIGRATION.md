# Migrasi

Memindahkan situs dari pengembangan ke staging, lalu ke produksi.

```
Codespaces / lokal
        ↓
yayasankasihananda.us.ci     (staging)
        ↓
yayasankasihananda.com       (produksi)
```

Alat migrasi: **WPvivid Backup & Migration** (`wpvivid-backuprestore`).

---

## Aturan yang tidak boleh dilanggar

1. **Selalu buat cadangan situs tujuan lebih dulu**, bahkan bila situs itu
   baru dan kosong.
2. **Restore harus dijalankan manual.** Mengaktifkan YKA Core tidak pernah
   menimpa situs tujuan. Tidak ada mekanisme apa pun di dalam proyek ini
   yang melakukan restore otomatis.
3. **Jangan memindahkan `wp-config.php`.** Berkas itu milik server tujuan.
4. **Jangan menyimpan arsip cadangan di dalam repositori.** Sudah masuk
   `.gitignore`, biarkan begitu.
5. **Periksa status pengindeksan setelah setiap migrasi.** Lihat langkah 8.

---

## Metode A — unggah manual (disarankan)

Dipakai bila situs sumber tidak dapat dihubungi langsung oleh situs tujuan.
Codespaces termasuk kasus ini: alamatnya berada di balik proksi
berautentikasi.

### 1. Membuat cadangan di situs sumber

1. Buka **WPvivid Backup → Backup & Restore**.
2. Pilih **Database + Files (WordPress Files)**.
3. Pilih **Save backups to localhost**.
4. Klik **Backup Now** dan tunggu sampai selesai.

### 2. Mengunduh cadangan

Pada tab **Backups**, unduh **seluruh** bagian berkas. WPvivid memecah
cadangan besar menjadi beberapa berkas; satu bagian yang hilang membuat
restore gagal.

Simpan di luar repositori.

### 3. Menyiapkan situs tujuan

Di `yayasankasihananda.us.ci`:

1. Pasang WordPress bersih melalui panel hosting.
2. Catat kredensial basis datanya.
3. Masuk ke `/wp-admin/`.
4. Pasang dan aktifkan **WPvivid Backup & Migration**.
5. Naikkan batas unggah PHP bila perlu:

   ```ini
   upload_max_filesize = 256M
   post_max_size = 256M
   max_execution_time = 600
   memory_limit = 512M
   ```

### 4. Mencadangkan situs tujuan

Buat cadangan penuh situs tujuan sebelum menimpanya. Selalu. Bahkan bila
isinya hanya instalasi WordPress kosong.

### 5. Mengunggah cadangan

**WPvivid Backup → Backup & Restore → Upload**, lalu unggah seluruh bagian
berkas.

Bila unggahan lewat peramban gagal karena batas server, unggah lewat SFTP
ke:

```
wp-content/wpvividbackups/
```

lalu klik **Scan uploaded backup** agar WPvivid mengenalinya.

### 6. Menjalankan restore

1. Pada tab **Backups**, cari cadangan yang diunggah.
2. Klik **Restore**.
3. Konfirmasikan. WPvivid akan menggantikan isi situs tujuan.
4. Tunggu hingga selesai tanpa menutup tab.

WPvivid mengganti URL lama menjadi URL baru di dalam basis data secara
otomatis, termasuk nilai yang terserialisasi.

### 7. Masuk kembali

Sesi lama terputus setelah restore. Masuk memakai kredensial administrator
**situs sumber**, bukan situs tujuan.

### 8. Memeriksa hasil

Segera setelah restore:

```
Pengaturan → Permalink → Simpan Perubahan     (menulis ulang .htaccess)
Yayasan → Kesiapan Produksi                    (memeriksa seluruh butir)
```

Lalu buka:

- beranda;
- `/berita/`;
- satu artikel;
- `/unit/smp-kasih-ananda-1/`;
- `/kontak/`;
- satu URL yang tidak ada, pastikan HTTP 404;
- `/sitemap_index.xml`.

Lihat [PRODUCTION-CHECKLIST.md](PRODUCTION-CHECKLIST.md) untuk daftar
lengkapnya.

---

## Metode B — migrasi otomatis

Dipakai bila situs sumber dan tujuan dapat saling menghubungi.

1. Pasang WPvivid di **kedua** situs.
2. Di situs tujuan: **WPvivid → Key**, salin kunci migrasi.
3. Di situs sumber: **WPvivid → Auto-Migration**, tempel kunci tersebut.
4. Jalankan **Transfer**.
5. Di situs tujuan, jalankan **Restore** secara manual.

Metode ini **tidak dapat dipakai dari Codespaces**: alamat yang diteruskan
berada di balik proksi berautentikasi, sehingga situs tujuan tidak dapat
menjangkaunya. Gunakan Metode A untuk langkah pertama, dan Metode B hanya
untuk staging → produksi bila keduanya publik.

---

## Yang ikut berpindah dan yang tidak

### Ikut berpindah

- seluruh artikel, halaman, kategori, dan term Unit Pendidikan;
- seluruh media di `wp-content/uploads/`;
- tema YKA Portal dan plugin YKA Core;
- plugin pihak ketiga beserta pengaturannya;
- pengaturan Rank Math;
- pengaturan Yayasan (`yka_settings`);
- menu navigasi;
- pengguna dan kata sandi.

### Tidak ikut berpindah — dan memang tidak boleh

| Berkas / pengaturan | Alasan |
|---|---|
| `wp-config.php` | kredensial basis data milik server tujuan |
| `.htaccess` | dibuat ulang saat menyimpan permalink |
| konfigurasi Nginx / Apache | milik server |
| sertifikat SSL | milik server |
| DNS | milik pengelola domain |
| versi dan batas PHP | milik hosting |
| `WP_ENVIRONMENT_TYPE` | **harus** disetel manual di server tujuan |

### Menyetel jenis lingkungan di server tujuan

Tambahkan pada `wp-config.php` situs tujuan, di atas baris
`/* That's all, stop editing! */`:

```php
// Staging:
define( 'WP_ENVIRONMENT_TYPE', 'staging' );

// Produksi:
define( 'WP_ENVIRONMENT_TYPE', 'production' );
```

Nilai ini menentukan apakah situs boleh diindeks. YKA Core memerlukan
**dua** hal sekaligus untuk memperlakukan situs sebagai produksi: konstanta
di atas bernilai `production` **dan** nama host termasuk domain produksi
resmi. Salah satu saja tidak cukup.

Artinya salinan staging dari situs produksi tetap otomatis `noindex`,
bahkan bila basis datanya identik.

---

## Dari staging ke produksi

Prosedurnya sama, dengan tambahan:

1. Pastikan DNS `yayasankasihananda.com` sudah menunjuk ke server produksi.
2. Pastikan sertifikat SSL aktif dan `https://` bekerja.
3. Setel `WP_ENVIRONMENT_TYPE` ke `production`.
4. Hapus konten demo:

   ```bash
   # dari mesin pengembangan, sebelum membuat cadangan
   ./scripts/seed.sh --remove
   ```

   Atau dari dasbor produksi: **Berita & Dokumentasi**, cari `[DEMO]`,
   pilih semua, pindahkan ke tempat sampah, lalu kosongkan tempat sampah.

5. Buka **Yayasan → Kesiapan Produksi** dan selesaikan setiap butir yang
   berstatus "perlu tindakan".
6. Aktifkan pengindeksan lewat tombol **Perbaiki** pada halaman tersebut.
   Tombol itu hanya muncul bila domainnya memang domain produksi.
7. Kirim peta situs ke Search Console dan Bing.
8. Aktifkan IndexNow.
9. Buat cadangan penuh pasca-peluncuran.

---

## Bila restore gagal

| Gejala | Tindakan |
|---|---|
| Kehabisan memori | naikkan `memory_limit` ke 512M, ulangi |
| Waktu habis | naikkan `max_execution_time` ke 600, ulangi |
| Berkas terlalu besar | unggah lewat SFTP ke `wp-content/wpvividbackups/` |
| Bagian cadangan hilang | unduh ulang **seluruh** bagian, jangan sebagian |
| Restore separuh jalan | pulihkan cadangan pengaman situs tujuan, lalu ulangi |
| Layar putih setelah restore | aktifkan `WP_DEBUG_LOG`, baca `wp-content/debug.log` |

Karena Anda selalu membuat cadangan pengaman situs tujuan lebih dulu,
kegagalan restore tidak pernah berakhir dengan kehilangan data.

---

## Setelah migrasi domain

Pemeriksaan yang harus dilakukan:

- [ ] `siteurl` dan `home` memakai domain baru dengan `https://`
- [ ] tautan menu mengarah ke domain baru
- [ ] gambar tampil (URL media sudah diganti)
- [ ] canonical menunjuk ke domain baru
- [ ] `og:url` menunjuk ke domain baru
- [ ] peta situs memuat URL domain baru
- [ ] `@id` pada data terstruktur memakai domain baru
- [ ] permalink bekerja (simpan ulang pengaturan permalink)
- [ ] tidak ada peringatan mixed content di konsol peramban
- [ ] REST API merespons di `/wp-json/`
- [ ] logo dan favicon tampil
- [ ] email keluar bekerja

Sebagian besar dapat diperiksa sekaligus:

```bash
YKA_BASE_URL=https://yayasankasihananda.com npm test
```
