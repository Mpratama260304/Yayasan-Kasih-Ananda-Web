# Pengembangan

Panduan untuk pengembang yang bekerja pada repositori ini.

---

## Menyiapkan lingkungan

### GitHub Codespaces

```bash
./scripts/start.sh
./scripts/bootstrap.sh
./scripts/seed.sh      # opsional
```

`start.sh` akan:

1. mengekstrak `wordpress-7.1.zip` ke `./wp` bila belum ada;
2. menyalakan kontainer;
3. menunggu basis data siap;
4. memeriksa dan memperbaiki jaringan antar-kontainer bila perlu.

`bootstrap.sh` aman dijalankan berulang kali. Ia tidak pernah menghapus
konten.

### Lokal dengan Docker

Sama persis. Bedanya hanya alamat: `http://localhost:8080`.

---

## Susunan berkas

| Lokasi | Dalam Git? | Keterangan |
|---|---|---|
| `wp-content/themes/yka-portal` | ya | tema kustom |
| `wp-content/plugins/yka-core` | ya | plugin kustom |
| `scripts/` | ya | perintah pengembangan |
| `wp/` | **tidak** | inti WordPress hasil ekstraksi |
| `wp/wp-content/uploads/` | **tidak** | media |
| `wp/wp-content/plugins/` | **tidak** | plugin pihak ketiga |
| `.env` | **tidak** | kredensial lokal |
| `vendor/`, `node_modules/` | **tidak** | dependensi |

Tema dan plugin kustom di-*bind mount* ke dalam kontainer, sehingga setiap
perubahan langsung tampak tanpa menyalin berkas.

---

## Alur kerja sehari-hari

```bash
# menyunting tema atau plugin -> muat ulang peramban, itu saja

./scripts/check.sh --lint      # cepat: sintaks + standar kode
./scripts/check.sh             # lengkap: termasuk pemeriksaan situs
npm test                       # uji asap Playwright
npm run shots                  # tangkapan layar untuk tinjauan visual
```

### WP-CLI

```bash
./scripts/wp.sh plugin list
./scripts/wp.sh post list --post_type=post
./scripts/wp.sh term list yka_unit
./scripts/wp.sh rewrite flush
./scripts/wp.sh option get yka_settings --format=json
```

### Melihat log

```bash
docker compose logs -f wordpress
docker compose exec wordpress tail -f /var/www/html/wp-content/debug.log
```

### Mengulang dari nol

```bash
./scripts/reset-dev.sh        # meminta konfirmasi 'reset'
./scripts/start.sh && ./scripts/bootstrap.sh && ./scripts/seed.sh
```

---

## Standar penulisan kode

```bash
vendor/bin/phpcs              # memeriksa
vendor/bin/phpcbf             # memperbaiki yang bisa diperbaiki otomatis
```

Hanya kode YKA yang diperiksa. WordPress, Rank Math, dan WPvivid sengaja
dikecualikan.

Konvensi:

- PHP kustom memakai `declare( strict_types=1 )`.
- Kelas plugin berada di namespace `YKA\Core`; berkas dinamai
  `inc/class-nama-kelas.php`.
- Fungsi global memakai awalan `yka_`.
- Variabel lokal di dalam template memakai awalan `$yka_` agar tidak
  bertabrakan dengan variabel yang disediakan WordPress.
- Seluruh keluaran di-escape pada titik keluarnya, bukan lebih awal.

---

## Uji Playwright

```bash
npm test                              # desktop + ponsel
npx playwright test --project=mobile
npx playwright test -g "404"
npm run test:report                   # membuka laporan HTML
```

Uji berjalan terhadap `http://localhost:8080`. Untuk menguji alamat lain:

```bash
YKA_BASE_URL=https://yayasankasihananda.us.ci npm test
```

Playwright hanya alat pengembangan. Produksi tidak memerlukannya.

---

## Pemecahan masalah

### Aset gagal dimuat dengan `ERR_CONNECTION_REFUSED`

Gejalanya: situs terbuka lewat alamat Codespaces, tetapi seluruh CSS, JS,
fonta, dan gambar gagal dimuat. Konsol peramban menunjukkan alamat
`localhost:8080` atau `https://localhost:8080`.

**Penyebabnya:** terowongan Codespaces meneruskan permintaan ke kontainer
dengan header `Host: localhost:8080`, dan menaruh nama host aslinya pada
`X-Forwarded-Host`. WordPress yang membaca `HTTP_HOST` saja akan menyimpulkan
alamat situsnya adalah `localhost:8080`, lalu menerbitkan seluruh URL aset
ke alamat itu — yang tentu saja tidak ada di komputer pengunjung.

Penanganannya ada di `config/wp/yka-config.php`, dengan urutan:

1. `X-Forwarded-Host`, bila lolos daftar izin;
2. variabel `YKA_PUBLIC_URL`, bila permintaan jelas ditunelkan (diteruskan
   sebagai HTTPS tetapi mengaku berasal dari localhost) namun host aslinya
   tidak disertakan;
3. `Host`, untuk akses langsung dari curl, Playwright, atau peramban di
   mesin yang sama.

Nilai header divalidasi terhadap daftar izin, sehingga header palsu tidak
dapat mengalihkan situs ke tempat lain.

Bila gejalanya muncul kembali:

```bash
./scripts/start.sh      # menyetel ulang YKA_PUBLIC_URL lalu menyalakan ulang
```

Lalu muat ulang peramban dengan mengabaikan cache (Ctrl/Cmd + Shift + R).

### "Error establishing a database connection"

Jaringan antar-kontainer terputus. Ini lazim pada Docker-in-Docker,
termasuk GitHub Codespaces.

```bash
./scripts/fix-docker-network.sh
```

**Penyebabnya:** host memiliki aturan firewall di *dua* backend sekaligus.
Docker 29 menulis aturannya melalui `iptables-nft`, tetapi tabel
`iptables-legacy` masih memberlakukan `-P FORWARD DROP` dan hanya
mengizinkan `docker0`. Lalu lintas antar kontainer pada bridge compose
(`br-*`) ikut terbuang tanpa pesan.

Perbaikannya menambahkan dua aturan pada tabel legacy:

```bash
sudo iptables-legacy -I FORWARD 1 -i br+ -j ACCEPT
sudo iptables-legacy -I FORWARD 1 -o br+ -j ACCEPT
```

Aturan ini hanya berlaku pada mesin pengembangan dan hilang saat host
dimulai ulang. Tidak ada hubungannya dengan produksi.

### Permalink menghasilkan 404

`.htaccess` hilang. WP-CLI tidak dapat mendeteksi mod_rewrite dari luar
Apache sehingga menolak menulisnya.

```bash
./scripts/bootstrap.sh     # langkah 3 menulis .htaccess bila belum ada
```

### Halaman ganda setelah menjalankan bootstrap berulang kali

Sudah diperbaiki: pencocokan halaman kini memakai slug **dan** induk, dan
item menu dicocokkan berdasarkan ID objek, bukan judul. Bila masih ada sisa
duplikat dari versi lama:

```bash
./scripts/wp.sh post list --post_type=page --fields=ID,post_name,post_parent
./scripts/wp.sh post delete <ID> --force
```

### Rank Math tidak menerbitkan metadata apa pun

Rank Math membisu sampai wisayanya dijalankan.

```bash
./scripts/wp.sh eval-file /scripts/php/rank-math-config.php
```

Skrip ini juga dipanggil otomatis oleh `bootstrap.sh`.

### `wp option update` menggagalkan skrip

WP-CLI mengembalikan kode kesalahan bila nilainya tidak berubah. Gunakan
pembantu `wp_option` dari `scripts/lib.sh`, bukan `wp option update`
langsung.

### Email pengembangan

Seluruh email berhenti di Mailpit pada port 8025. `sendmail` sengaja
dinonaktifkan di dalam kontainer, sehingga tidak ada cara bagi lingkungan
pengembangan untuk menjangkau kotak masuk sungguhan.

---

## Menambah kemampuan

### Menambah bidang metadata artikel

Tambahkan satu entri pada `Post_Meta::fields()`. Panel editor, penyimpanan,
sanitasi, dan dukungan REST mengikuti otomatis.

### Menambah bidang metadata unit

Tambahkan satu entri pada `Unit_Meta::fields()` beserta `group`-nya.

### Menambah pengaturan lembaga

Tambahkan satu entri pada `Settings::schema()`. Settings API, sanitasi, dan
render bidang mengikuti otomatis.

### Menambah pola blok

Buat berkas baru di `wp-content/themes/yka-portal/patterns/`. WordPress
mendaftarkannya sendiri dari header komentar berkas.

### Mengubah warna merek

Sunting `assets/css/tokens.css` dan palet pada `theme.json`. Tidak ada nilai
heksadesimal lain di dalam kode.
