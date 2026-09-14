# Berbagi ke Media Sosial

Bagaimana artikel tampil saat dibagikan, dan cara memeriksanya.

---

## Apa yang dikirim situs

Setiap artikel menerbitkan metadata berikut, seluruhnya dihasilkan oleh
Rank Math:

| Tag | Isi |
|---|---|
| `og:title` | judul SEO artikel |
| `og:description` | ringkasan artikel |
| `og:image` | gambar utama artikel |
| `og:url` | alamat kanonis |
| `og:type` | `article` |
| `og:site_name` | Yayasan Kasih Ananda |
| `article:published_time` | tanggal terbit |
| `article:section` | unit pendidikan |
| `twitter:card` | `summary_large_image` |

Halaman tanpa gambar utamanya sendiri — beranda misalnya — memakai foto
hero sebagai cadangan. YKA Core menjaga nilai ini tetap selaras setiap kali
pengaturan beranda disimpan.

Tidak ada SDK pihak ketiga yang dimuat. Tombol berbagi hanyalah tautan
biasa, sehingga tidak ada jaringan sosial yang dapat melacak pembaca yang
tidak pernah mengekliknya.

---

## Tombol berbagi

Di bawah setiap artikel:

| Tombol | Cara kerja |
|---|---|
| **WhatsApp** | `api.whatsapp.com/send?text=…` |
| **Facebook** | `facebook.com/sharer/sharer.php?u=…` |
| **X** | `twitter.com/intent/tweet?…` |
| **Salin tautan** | Clipboard API |
| **Bagikan** | Web Share API, hanya muncul bila peramban mendukungnya |

Tiga tombol pertama adalah tautan `<a href>` sungguhan dan tetap berfungsi
meski JavaScript gagal dimuat.

---

## Teks media sosial siap tempel

Di dalam editor artikel, panel **Teks Media Sosial** menyusun:

```
[Judul artikel]

[Ringkasan artikel]

Baca selengkapnya:
https://yayasankasihananda.com/berita/nama-artikel/

#YayasanKasihAnanda #SMPKasihAnandaI
```

Klik **Salin teks**, lalu tempel ke WhatsApp, caption Instagram, atau
Facebook.

### Menyesuaikan

Pada panel **Detail Dokumentasi**:

- **Teks singkat media sosial** — menggantikan ringkasan artikel bila diisi.
- **Tagar tambahan** — ditambahkan setelah tagar yayasan dan unit.

Situs ini tidak memposting otomatis ke jaringan sosial mana pun, dan tidak
memerlukan kredensial API apa pun. Menyalin dan menempel sudah cukup, dan
jauh lebih aman.

---

## Memeriksa pratinjau

Alat berikut hanya dapat membaca URL publik, jadi gunakan setelah situs
produksi tayang.

| Jaringan | Alat |
|---|---|
| Facebook & WhatsApp | <https://developers.facebook.com/tools/debug/> |
| X | <https://cards-dev.twitter.com/validator> |
| LinkedIn | <https://www.linkedin.com/post-inspector/> |

WhatsApp memakai cache Facebook. Bila pratinjau WhatsApp salah, jalankan
**Scrape Again** pada Facebook Sharing Debugger.

### Memeriksa dari terminal

```bash
curl -s https://yayasankasihananda.com/berita/nama-artikel/ \
  | grep -E 'og:|twitter:'
```

---

## Cache pratinjau

Jaringan sosial menyimpan pratinjau. Setelah mengubah judul atau gambar
utama:

1. Jalankan **Scrape Again** di Facebook Sharing Debugger.
2. LinkedIn Post Inspector menyegarkan sendiri saat URL diperiksa.
3. WhatsApp mengikuti cache Facebook.

Tidak ada cara memaksa penyegaran di sisi klien. Beri waktu beberapa jam.

---

## Ketika pratinjau tidak muncul

| Gejala | Penyebab yang paling sering |
|---|---|
| Tidak ada gambar | artikel belum punya gambar utama |
| Gambar buram | gambar utama lebih sempit dari 1200 piksel |
| Judul salah | jaringan sosial masih memakai cache lama |
| Tidak ada pratinjau sama sekali | situs masih `noindex` atau belum publik |
| Deskripsi kosong | ringkasan artikel belum diisi |

Panel **Kesiapan Publikasi** memperingatkan tiga penyebab pertama sebelum
artikel terbit.

---

## Ukuran gambar

Rasio terbaik untuk berbagi adalah 1,91 : 1 — kira-kira 1200 × 630 piksel.

Gambar utama artikel berukuran 1600 × 900 (16:9) sudah bekerja baik pada
seluruh jaringan. Situs juga menyiapkan potongan 1200 × 630 sebagai gambar
cadangan situs.

Hindari menaruh teks penting di tepi gambar: setiap jaringan memotong
sedikit berbeda.
