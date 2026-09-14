# Rencana Pengalihan Domain Lama

Status: **rencana**. Belum ada pengalihan yang dijalankan.

---

## Domain yang ada

| Domain | Unit | Status |
|---|---|---|
| `smpkasihananda1.sch.id` | SMP Kasih Ananda I | aktif |
| `smkkasihananda.sch.id` | SMK Kasih Ananda | aktif |
| — | SD Kasih Ananda I | belum ada situs |
| `yayasankasihananda.com` | Yayasan Kasih Ananda | portal baru |

---

## Kapan pengalihan boleh dijalankan

Semua syarat berikut harus terpenuhi lebih dulu:

1. Portal baru tayang di `yayasankasihananda.com` dan terverifikasi.
2. Isi bernilai dari situs lama sudah dipindahkan — lihat
   [LEGACY-CONTENT-MIGRATION.md](LEGACY-CONTENT-MIGRATION.md).
3. Setiap URL lama yang penting sudah dipetakan ke URL baru.
4. Pengurus yayasan dan pimpinan unit menyetujui.
5. Portal baru mulai terindeks Google.
6. Cadangan penuh situs lama sudah dibuat dan disimpan.

**Jangan mengalihkan apa pun sebelum keenam syarat terpenuhi.**

---

## Prinsip

**Jangan pernah mengalihkan seluruh URL lama ke beranda.** Google
memperlakukan pengalihan massal ke beranda sebagai *soft 404*, dan seluruh
nilai tautan yang dikumpulkan situs lama selama bertahun-tahun hilang.

Setiap URL lama yang bernilai harus mengarah ke halaman baru yang isinya
setara.

Contoh pemetaan yang benar:

```
smpkasihananda1.sch.id/profil/
  → yayasankasihananda.com/unit/smp-kasih-ananda-1/

smpkasihananda1.sch.id/berita/kegiatan-pramuka/
  → yayasankasihananda.com/berita/kegiatan-pramuka/

smpkasihananda1.sch.id/kontak/
  → yayasankasihananda.com/unit/smp-kasih-ananda-1/#kontak
```

URL lama yang tidak punya padanan bermakna sebaiknya mengembalikan **404**,
bukan dialihkan ke tempat sembarang.

---

## Menyusun inventaris URL lama

Sebelum memetakan, kumpulkan daftar URL yang ada:

1. **Google Search Console** situs lama → Performance → Pages.
   Ekspor URL yang benar-benar mendatangkan pengunjung.
2. **Peta situs situs lama**, biasanya di `/sitemap.xml` atau
   `/sitemap_index.xml`.
3. **Perayapan** memakai Screaming Frog SEO Spider (gratis sampai 500 URL)
   atau `wget --spider -r`.
4. **Google Analytics** situs lama bila tersedia.

Urutkan berdasarkan jumlah kunjungan. Biasanya 20 URL teratas mewakili
sebagian besar lalu lintas; petakan itu lebih dulu dengan teliti.

---

## Berkas peta pengalihan

Simpan sebagai berkas kerja di luar repositori, misalnya lembar kerja
dengan kolom:

| URL lama | URL baru | Kode | Catatan |
|---|---|---|---|
| `/profil/` | `/unit/smp-kasih-ananda-1/` | 301 | |
| `/berita/xyz/` | `/berita/xyz/` | 301 | judul dipertahankan |
| `/tes/` | — | 404 | halaman percobaan, tidak dipindahkan |

---

## Cara menjalankan pengalihan

### Pilihan A — di tingkat server situs lama (disarankan)

Paling cepat dan paling andal. Tidak membebani situs baru.

**Apache** — `.htaccess` pada situs lama:

```apache
RewriteEngine On

# URL spesifik lebih dulu
RewriteRule ^profil/?$ https://yayasankasihananda.com/unit/smp-kasih-ananda-1/ [R=301,L]
RewriteRule ^kontak/?$ https://yayasankasihananda.com/unit/smp-kasih-ananda-1/ [R=301,L]

# Artikel berita mempertahankan slug-nya
RewriteRule ^berita/([^/]+)/?$ https://yayasankasihananda.com/berita/$1/ [R=301,L]

# Sisanya diarahkan ke halaman unit, bukan ke beranda
RewriteRule ^(.*)$ https://yayasankasihananda.com/unit/smp-kasih-ananda-1/ [R=301,L]
```

**Nginx**:

```nginx
location = /profil/ {
    return 301 https://yayasankasihananda.com/unit/smp-kasih-ananda-1/;
}

location ~ ^/berita/([^/]+)/?$ {
    return 301 https://yayasankasihananda.com/berita/$1/;
}

location / {
    return 301 https://yayasankasihananda.com/unit/smp-kasih-ananda-1/;
}
```

### Pilihan B — modul Redirections Rank Math

Dipakai bila domain lama diarahkan ke server yang sama dengan portal baru.

**Rank Math → Redirections → Add New**

- Source URL: pola URL lama
- Destination URL: URL baru
- Redirection type: **301 Permanent**

Rank Math mendukung impor CSV untuk pemetaan dalam jumlah besar.

---

## Setelah pengalihan aktif

- [ ] Uji 20 URL teratas satu per satu:

  ```bash
  curl -sI https://smpkasihananda1.sch.id/profil/ | head -3
  ```

  Harus mengembalikan `HTTP/1.1 301` dan header `Location` yang benar.

- [ ] Pastikan tidak ada rantai pengalihan lebih dari satu lompatan
- [ ] Pastikan tidak ada gelung pengalihan
- [ ] Kirim peta situs situs lama ke Search Console agar Google merayapi
      ulang dan menemukan pengalihannya
- [ ] Pertahankan properti Search Console situs lama minimal enam bulan
- [ ] Gunakan alat **Change of Address** di Search Console bila seluruh
      domain dipindahkan
- [ ] Pantau laporan cakupan situs lama selama tiga bulan

---

## Berapa lama pengalihan harus dipertahankan

Minimal **satu tahun**. Idealnya selamanya, selama domainnya masih disewa.

Google memerlukan waktu berbulan-bulan untuk memindahkan seluruh sinyal.
Mematikan pengalihan terlalu cepat berarti membuang sebagian besar
manfaatnya.

Perpanjang masa sewa `smpkasihananda1.sch.id` dan `smkkasihananda.sch.id`
selama pengalihan masih berjalan.

---

## Yang tidak boleh dilakukan

- Jangan mengalihkan sebelum isi situs lama dipindahkan.
- Jangan mengalihkan seluruh URL ke beranda.
- Jangan memakai pengalihan 302 untuk perpindahan permanen.
- Jangan membuat rantai `lama → sementara → baru`.
- Jangan mematikan situs lama tanpa pengalihan sama sekali.
- Jangan mengalihkan halaman yang isinya tidak ada padanannya; biarkan 404.
