# Migrasi Isi Situs Lama

Rencana pemindahan isi dari `smpkasihananda1.sch.id` dan
`smkkasihananda.sch.id` ke portal baru.

Status: **rencana**. Belum ada isi yang dipindahkan, dan tidak boleh
dipindahkan tanpa persetujuan.

---

## Prinsip

1. **Jangan menyalin otomatis.** Tidak ada scraper, tidak ada impor massal
   tanpa tinjauan. Situs lama memuat halaman percobaan, isi kedaluwarsa,
   dan kemungkinan data pribadi yang tidak boleh diterbitkan ulang.
2. **Kurasi, jangan pindahkan semuanya.** Sebagian besar isi situs sekolah
   lama tidak layak dipertahankan.
3. **Pertahankan tanggal terbit asli.** Artikel dari 2019 harus tetap
   bertanggal 2019. Menerbitkan ulang dengan tanggal hari ini adalah
   kebohongan, dan Google memperlakukannya demikian.
4. **Tanyakan izin untuk foto.** Foto siswa dari situs lama memerlukan
   kejelasan izin sebelum diterbitkan ulang.

---

## Tahap 1 — inventaris

Buat daftar seluruh isi situs lama:

```bash
# Merayapi peta situs lama
curl -s https://smpkasihananda1.sch.id/sitemap.xml \
  | grep -oP '(?<=<loc>)[^<]+' > smp-urls.txt

wc -l smp-urls.txt
```

Untuk setiap URL, catat:

| Kolom | Isi |
|---|---|
| URL | alamat lama |
| Judul | judul halaman |
| Jenis | artikel / halaman / lampiran |
| Tanggal | tanggal terbit asli |
| Kunjungan | dari Search Console atau Analytics |
| Keputusan | pindahkan / ringkas / abaikan |

---

## Tahap 2 — memilih

### Pindahkan

- artikel kegiatan dengan foto asli;
- prestasi yang terverifikasi beserta buktinya;
- sejarah dan profil unit;
- informasi yang masih berlaku;
- halaman yang mendatangkan kunjungan menurut Search Console.

### Ringkas lalu pindahkan

- beberapa artikel pendek tentang topik yang sama dapat digabung;
- informasi yang tersebar dapat disatukan ke satu halaman unit.

### Abaikan

- halaman percobaan dan "Hello world";
- pengumuman yang sudah lewat dan tidak lagi relevan;
- kalender kegiatan tahun-tahun lampau;
- artikel tanpa isi nyata;
- konten hasil salin-tempel dari situs lain;
- halaman yang hanya memuat tautan rusak.

Bila ragu, tinggalkan. Arsip kecil yang berkualitas lebih baik daripada
arsip besar berisi halaman kosong.

---

## Tahap 3 — membersihkan HTML

Situs sekolah lama biasanya memuat HTML yang berantakan: gaya sebaris,
tabel tata letak, tag `<font>`, dan lebar tetap.

Sebelum menempelkan ke Gutenberg:

1. Salin sebagai teks polos.
2. Bangun ulang memakai blok: Heading, Paragraph, List, Image, Gallery.
3. Hapus seluruh atribut `style`, `width`, `align` peninggalan lama.
4. Ganti tabel tata letak dengan blok Columns.
5. Perbaiki ejaan dan tanda baca sekalian.

Blok **Custom HTML** boleh dipakai, tetapi hampir selalu menandakan isi
tersebut sebaiknya dibangun ulang.

---

## Tahap 4 — media

1. Unduh gambar asli dari situs lama, bukan versi thumbnail.
2. Ubah nama berkas menjadi deskriptif sebelum mengunggah.
3. Unggah ke media library portal baru.
4. Isi teks alternatif dan keterangan.
5. Jangan menautkan langsung ke berkas di domain lama — tautan itu akan
   mati ketika domain lama berakhir.

**Izin:** foto yang menampilkan siswa memerlukan kejelasan izin. Bila tidak
ada catatan izinnya, tanyakan kepada unit terkait sebelum menerbitkan
ulang. Bila tetap tidak jelas, jangan diterbitkan.

---

## Tahap 5 — pemetaan

Untuk setiap artikel yang dipindahkan:

| Bidang lama | Bidang baru |
|---|---|
| Judul | judul artikel |
| Tanggal terbit | **tanggal terbit** (jangan diubah) |
| Tanggal kegiatan | bidang `Tanggal kegiatan` bila diketahui |
| Penulis | akun staf; jangan mengarang nama |
| Kategori lama | salah satu dari delapan kategori baru |
| — | **Unit Pendidikan** wajib diisi |
| Gambar unggulan | gambar utama |
| URL lama | catat untuk peta pengalihan |

Menyetel tanggal terbit asli melalui WP-CLI:

```bash
./scripts/wp.sh post update <ID> --post_date='2019-08-17 09:00:00'
```

Atau lewat editor: **Terbit → Segera → ubah tanggal**.

---

## Tahap 6 — pengalihan

Setiap URL lama yang dipindahkan masuk ke peta pengalihan. Lihat
[REDIRECT-PLAN.md](REDIRECT-PLAN.md).

Bila slug artikel dipertahankan, pengalihannya menjadi sederhana:

```
smpkasihananda1.sch.id/berita/nama-artikel/
  → yayasankasihananda.com/berita/nama-artikel/
```

Karena itu, pertahankan slug asli bila masih masuk akal.

---

## Perkiraan beban kerja

Pemindahan manual memakan waktu sekitar 10–20 menit per artikel, termasuk
membersihkan HTML, mengunggah ulang gambar, dan mengisi metadata.

Untuk 50 artikel pilihan, siapkan sekitar dua hari kerja.

Itu wajar. Arsip yang bersih adalah aset jangka panjang; impor massal yang
berantakan adalah utang teknis yang akan dibayar berkali-kali.

---

## Bila impor massal benar-benar diperlukan

Bila jumlah artikel terlalu besar untuk ditangani manual:

1. Ekspor dari WordPress lama: **Tools → Export → Posts**, hasilnya berkas
   WXR.
2. Impor di portal baru: **Tools → Import → WordPress**.
3. Impor sebagai **draf**, bukan langsung terbit.
4. Tinjau setiap draf, tetapkan Unit Pendidikan, bersihkan HTML.
5. Terbitkan satu per satu.

WordPress Importer mempertahankan tanggal terbit asli, yang penting.

Ia **tidak** menetapkan Unit Pendidikan — itu harus dikerjakan manual,
dan tanpa unit, artikel tidak akan muncul di halaman SD, SMP, SMK, atau
Yayasan.

Untuk penetapan massal setelah impor:

```bash
# Contoh: menandai seluruh artikel hasil impor dengan unit SMP
./scripts/wp.sh post list --post_type=post --post_status=draft --format=ids \
  | xargs -d ' ' -I{} ./scripts/wp.sh post term add {} yka_unit smp-kasih-ananda-1
```

Periksa hasilnya sebelum menerbitkan.

---

## Yang tidak boleh dilakukan

- Jangan menjalankan scraper terhadap situs lama.
- Jangan menerbitkan ulang isi tanpa meninjaunya.
- Jangan mengubah tanggal terbit menjadi hari ini.
- Jangan menerbitkan foto siswa tanpa kejelasan izin.
- Jangan menyalin isi dari situs sekolah lain.
- Jangan mengimpor komentar; komentar publik dimatikan pada portal ini.
