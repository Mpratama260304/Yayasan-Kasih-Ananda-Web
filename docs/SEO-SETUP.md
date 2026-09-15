# Penyiapan SEO

Untuk administrator situs. Seluruh langkah di sini dijalankan **setelah**
domain produksi tayang.

---

## Prinsip

Rank Math adalah pemilik tunggal metadata SEO di situs ini. Jangan memasang
Yoast atau AIOSEO berdampingan — dua plugin SEO akan menerbitkan dua
canonical, dua Open Graph, dan dua skema.

YKA Core hanya melengkapi apa yang tidak diketahui Rank Math: unit
pendidikan, lokasi kegiatan, dan beberapa rasio gambar artikel.

---

## 1. Memeriksa konfigurasi Rank Math

Konfigurasi sudah diterapkan otomatis oleh `scripts/php/rank-math-config.php`
dan ikut berpindah bersama basis data saat migrasi. Setelah migrasi,
pastikan kembali di **Rank Math → Dashboard**:

| Pengaturan | Nilai |
|---|---|
| Modul aktif | Sitemap, Schema, Link Counter, Redirections, 404 Monitor |
| Modul nonaktif | Analytics, Content AI, Instant Indexing (sampai produksi siap) |
| Judul beranda | `%sitename% \| Portal Resmi` |
| Judul artikel | `%title% \| %sitename%` |
| Judul unit dan kategori | `%term% \| %sitename%` |
| Tipe organisasi | Company / EducationalOrganization |
| Skema artikel | Article → NewsArticle |
| Arsip penulis | dinonaktifkan |
| Arsip tanggal | dinonaktifkan |
| Arsip tag | `noindex` |
| Kartu Twitter | `summary_large_image` |

### Yang diindeks

Boleh diindeks: beranda, profil yayasan, halaman unit pendidikan, halaman
statis yang berguna, seluruh artikel, arsip kategori, arsip unit.

Tidak diindeks: hasil pencarian internal, arsip tag tipis, arsip tanggal,
arsip penulis, dan setiap tampilan arsip yang sedang disaring
(`?kategori=` atau `?tahun=`).

Arsip kategori dan unit **tidak** boleh di-`noindex`. Keduanya adalah
halaman masuk yang bernilai.

---

## 2. Google Search Console

1. Buka <https://search.google.com/search-console>.
2. **Tambah properti → Domain**, masukkan `yayasankasihananda.com`.
3. Verifikasi lewat data DNS TXT pada penyedia domain.
   Verifikasi domain mencakup `www` dan `https` sekaligus.
4. Bila DNS tidak memungkinkan, gunakan **Awalan URL** lalu verifikasi
   melalui **Rank Math → General Settings → Webmaster Tools → Google Search
   Console**.

Nilai verifikasi tidak pernah ditulis di dalam kode. Ia disimpan sebagai
pengaturan Rank Math sehingga ikut berpindah bersama basis data.

### Mengirim peta situs

Kirimkan:

```
https://yayasankasihananda.com/sitemap_index.xml
```

Itu satu-satunya peta situs. Peta situs bawaan WordPress dimatikan otomatis
saat Rank Math aktif, agar tidak ada dua peta situs yang bersaing.

### Yang dipantau

| Laporan | Cari |
|---|---|
| Pages | halaman yang tidak terindeks dan alasannya |
| Sitemaps | jumlah URL ditemukan vs terindeks |
| Core Web Vitals | URL berstatus "Poor" atau "Needs improvement" |
| Enhancements | kesalahan data terstruktur |
| Performance | kueri yang membawa pengunjung |

Periksa mingguan pada bulan pertama, lalu bulanan.

---

## 3. Bing Webmaster Tools

1. Buka <https://www.bing.com/webmasters>.
2. **Import from Google Search Console** — cara tercepat.
3. Bila impor tidak dipakai, verifikasi lewat DNS atau meta tag melalui
   Rank Math.
4. Kirimkan peta situs yang sama.

Bing menyuplai Copilot dan beberapa sistem jawaban lain, jadi jangan
dilewatkan.

---

## 4. IndexNow

**Jangan diaktifkan sebelum domain produksi benar-benar tayang.**

IndexNow mengumumkan URL baru kepada Bing dan mesin lain. Bila diaktifkan
saat masih di staging, alamat staging ikut dikirimkan.

YKA Core memblokir IndexNow di lingkungan non-produksi
(`rank_math/indexnow/enable` dipaksa `false`), tetapi lapisan kedua tetap
diperlukan: biarkan modulnya mati sampai peluncuran.

Setelah produksi tayang dan terverifikasi:

1. **Rank Math → Dashboard → Instant Indexing**, aktifkan.
2. Rank Math membuat kunci API dan berkas kuncinya sendiri.
3. Uji dengan menerbitkan satu artikel, lalu periksa log Instant Indexing.

---

## 5. Rich Results dan validasi skema

Alat berikut memerlukan URL publik, sehingga hanya dapat dipakai setelah
produksi tayang:

| Alat | Alamat |
|---|---|
| Rich Results Test | <https://search.google.com/test/rich-results> |
| Schema Markup Validator | <https://validator.schema.org/> |
| URL Inspection | di dalam Search Console |
| PageSpeed Insights | <https://pagespeed.web.dev/> |

Yang harus lolos:

- **Beranda** — `EducationalOrganization` dan `WebSite`, tanpa duplikat.
- **Artikel** — `NewsArticle` dengan `headline`, `image`, `datePublished`,
  `dateModified`, `author`, `publisher`, `mainEntityOfPage`.
- **Halaman unit** — `School` dengan `parentOrganization` menunjuk ke
  yayasan.
- **Semua halaman** — satu `BreadcrumbList`.

Yang tidak boleh ada: `Event` pada artikel dokumentasi kegiatan yang sudah
lewat, `AggregateRating`, `Review`, atau skema apa pun yang tidak
menggambarkan isi halaman yang terlihat.

### Memvalidasi secara lokal

```bash
curl -s http://localhost:8080/ \
  | grep -oP '(?<=<script type="application/ld\+json">).*?(?=</script>)' \
  | head -1 | python3 -m json.tool
```

---

## 6. Core Web Vitals

Sasaran:

```
LCP ≤ 2,5 detik
INP ≤ 200 milidetik
CLS ≤ 0,1
```

Ini sasaran, bukan jaminan. Angka nyata bergantung pada hosting, ukuran
foto, dan jaringan pengunjung.

Yang sudah dikerjakan di dalam kode:

- gambar hero tidak pernah lazy-load dan selalu membawa `fetchpriority="high"`;
- seluruh gambar memiliki `width` dan `height` sehingga tata letak tidak
  bergeser;
- hanya dua berkas JavaScript kecil, keduanya `defer`;
- fonta dihosting sendiri dengan `font-display: swap`, hanya subset latin
  yang di-preload;
- tidak ada slider, tidak ada media autoplay, tidak ada pustaka ikon.

Yang menjadi tanggung jawab pengelola:

- **Kompres foto sebelum mengunggah.** Ini penyebab LCP buruk nomor satu.
- Gunakan hosting yang wajar; hindari shared hosting paling murah.
- Bila hosting memakai LiteSpeed, pertimbangkan LiteSpeed Cache. Jangan
  memasang dua plugin cache halaman sekaligus.

---

## 7. Struktur URL

```
Beranda          /
Artikel          /berita/nama-artikel/
Arsip berita     /berita/
Unit pendidikan  /unit/smp-kasih-ananda-1/
Kategori         /topik/kegiatan/
Halaman          /tentang-yayasan/
```

Jangan mengubah struktur ini setelah tayang. Bila terpaksa, buat pengalihan
301 lebih dulu — lihat [REDIRECT-PLAN.md](REDIRECT-PLAN.md).

---

## 8. Analitik

Tidak ada pelacak yang dipasang secara bawaan, dan tidak ada yang boleh
dipasang tanpa keputusan pemilik situs.

Bila yayasan memutuskan memakai analitik:

1. Pilih layanan yang sadar privasi, atau Google Analytics 4 bila memang
   diperlukan.
2. Pasang lewat **Rank Math → Analytics** atau plugin khusus — jangan
   menanam skrip di dalam tema.
3. Perbarui [Kebijakan Privasi](/kebijakan-privasi/) sesuai kenyataan.
4. Jangan pernah mengaktifkan pelacakan di lingkungan staging.

---

## 9. Kesiapan pencarian generatif

Yang membuat situs ini terlihat oleh sistem jawaban berbasis AI bukanlah
trik markup, melainkan hal-hal berikut:

- HTML yang dapat dirayapi tanpa menjalankan JavaScript;
- entitas yang jelas: nama lembaga dan unit yang konsisten di seluruh situs;
- tanggal terbit dan tanggal kegiatan yang eksplisit;
- foto orisinal beserta keterangan;
- informasi penulis;
- tautan internal antara artikel, unit, dan kategori;
- dokumentasi pihak pertama yang tidak ada di tempat lain.

Yang **tidak** dikerjakan proyek ini, dan tidak boleh ditambahkan tanpa
alasan kuat: `llms.txt`, "AI schema", ringkasan tersembunyi, halaman variasi
kata kunci, atau artikel tipis yang digenerasi massal. Tidak ada bukti
bahwa hal-hal itu bekerja, dan sebagian melanggar pedoman mesin pencari.

---

## 10. Setelah migrasi

Pengaturan Rank Math ikut berpindah bersama basis data, tetapi tidak
otomatis benar. Periksa ulang:

- [ ] Nama dan logo organisasi
- [ ] Templat judul dan deskripsi
- [ ] Peta situs dapat dibuka dan memuat URL produksi
- [ ] Breadcrumb tampil dalam bahasa Indonesia
- [ ] Metadata sosial memakai domain produksi
- [ ] Modul Redirections tetap aktif bila dipakai
- [ ] IndexNow — aktifkan hanya di produksi
- [ ] **Pengaturan → Membaca → visibilitas mesin pencari** mengizinkan
      pengindeksan

Butir terakhir adalah kesalahan peluncuran yang paling sering terjadi.
Buktikan sendiri dengan `curl`, jangan percaya pada tampilan dasbor:

```bash
curl -s https://yayasankasihananda.com/ | grep 'name="robots"'
```
