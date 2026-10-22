# Aldebaran3DPrinting

Toko mainan dan karya cetak 3D untuk balita sampai anak SMA. Dibuat dengan PHP native, tanpa framework dan tanpa database.

## Struktur folder

```
toko3d/
├── api/index.php        Pintu masuk untuk Vercel (router ke halaman PHP)
├── includes/            data.php (produk awal), functions, auth, store Supabase/JSON
├── admin/               Panel admin: produk, pesan, sosmed, ganti password
├── data/                Fallback JSON (lokal) + .htaccess penjaga
├── supabase/schema.sql  Skema database Supabase (jalankan sekali)
├── public/
│   ├── images/          Gambar produk dan maskot (ganti dengan foto asli)
│   └── assets/          CSS dan JavaScript
├── index.php            Beranda, filter usia, kategori, pencarian
├── produk.php           Detail produk
├── keranjang.php        Keranjang belanja
├── vercel.json          Pengaturan Vercel (runtime PHP)
└── jalankan-lokal.bat   Menjalankan situs di komputer (Windows)
```

## Menjalankan di komputer

Butuh PHP 8.0 atau lebih baru (bisa dari XAMPP). Di folder proyek, jalankan:

```
php -S localhost:8000 -t public api/index.php
```

Atau klik dua kali `jalankan-lokal.bat`, lalu buka http://localhost:8000.
Jika `php` tidak dikenali, ubah isi file `.bat` menjadi `C:\xampp\php\php.exe -S localhost:8000 -t public api/index.php`.

## Mengubah produk

Cara termudah: login ke `/admin`. Produk awal ada di `includes/data.php` dan otomatis di-seed
ke Supabase saat pertama kali database dipakai. Tanpa Supabase, perubahan disimpan ke
`data/produk.json` (lokal). Foto bisa diunggah ke Cloudinary atau ditaruh di `public/images/`.

## Panel admin & Cloudinary

Situs punya panel admin di **`/admin`** untuk menambah, mengedit, menghapus produk, serta mengunggah gambar langsung ke **Cloudinary**.

### Menyiapkan environment variables

Salin `.env.example` jadi `.env`, lalu isi:

| Variabel | Isi |
| --- | --- |
| `ADMIN_PASSWORD` | Password login awal `/admin` (cadangan bila database belum punya password) |
| `SUPABASE_URL` | URL project Supabase (`https://xxxx.supabase.co`, tanpa `/rest/v1`) |
| `SUPABASE_SERVICE_KEY` | `service_role` key — **rahasia**, hanya untuk server |
| `WHATSAPP_TOKO` | Nomor WhatsApp tujuan checkout (opsional, default di `functions.php`) |
| `CLOUDINARY_CLOUD_NAME` | Cloud name dari dashboard Cloudinary |
| `CLOUDINARY_UPLOAD_PRESET` | Nama upload preset bertipe **Unsigned** |
| `CLOUDINARY_FOLDER` | Folder Media Library tujuan upload (opsional, default `ruang-3d`) |
| `APP_SECRET` | Teks acak panjang untuk menandatangani cookie |

Untuk lokal, file `.env` dibaca otomatis. Di **Vercel**, isi lewat **Project → Settings → Environment Variables**, lalu deploy ulang.

### Cara kerja produk

- Produk default ada di `includes/data.php`.
- **Dengan Supabase** (disarankan): produk, media sosial, pesan, dan statistik checkout
  disimpan di database sehingga panel admin **bisa menulis langsung dari Vercel** dan
  datanya persisten.
- **Tanpa Supabase** (fallback): perubahan disimpan ke file JSON di `data/`. Ini hanya
  persisten di lokal; filesystem Vercel serverless bersifat read-only, jadi perubahan
  dari admin online tidak tersimpan.

### Menyiapkan Supabase (agar admin bisa menulis online)

1. Buat project gratis di [supabase.com](https://supabase.com).
2. Buka **SQL Editor > New query**, tempel seluruh isi `supabase/schema.sql`, lalu **Run**.
3. Buka **Project Settings > API**, salin **Project URL** dan **service_role key**.
4. Isi `SUPABASE_URL` dan `SUPABASE_SERVICE_KEY`:
   - Lokal: di file `.env`.
   - Vercel: **Project > Settings > Environment Variables**, lalu redeploy.
5. Login ke `/admin`, buka **Password**, ganti password. Password disimpan sebagai hash
   di tabel `admin_akun` dan bisa diganti kapan saja dari web.
6. Bila penyimpanan gagal, buka **`/admin/status`** untuk melihat apakah env terbaca,
   role kunci benar (`service_role`), tabel sudah dibuat, dan uji tulis database.
7. Statistik pengunjung harian/mingguan disimpan di tabel `kunjungan`. Jalankan ulang
   `supabase/schema.sql` (aman diulang) agar tabel ini dibuat di project yang sudah ada.

Catatan keamanan: `SUPABASE_SERVICE_KEY` adalah kunci penuh database. Jangan pernah
menaruhnya di JavaScript, HTML, atau repo publik. File `.env` sudah di-.gitignore.
Jika password database lupa, hapus baris di tabel `admin_akun` lewat dashboard Supabase;
login akan kembali memakai `ADMIN_PASSWORD` dari environment.

### Upload gambar

Di form admin, seret/klik gambar untuk diunggah langsung ke Cloudinary (unsigned preset). URL `secure_url` yang dikembalikan otomatis terisi ke kolom gambar. Produk dengan URL `https://...` akan menampilkan gambar dari Cloudinary; nama file lokal tetap diambil dari `public/images/`.

## Kredit foto produk

Foto produk saat ini memakai foto berlisensi bebas (CC BY / CC BY-SA) yang ditemukan via
[Openverse](https://openverse.org). Daftar lengkap kreator dan tautan sumber ada di komentar
atas `includes/data.php`. Jika mengganti foto dengan foto sendiri, blok kredit tersebut boleh
dihapus. Atribusi ringkas juga tampil di badge footer situs.

## Upload ke GitHub dengan GitHub Desktop

1. Pasang GitHub Desktop dan masuk dengan akun GitHub.
2. Pilih **File > Add local repository**, lalu pilih folder `toko3d`.
3. Jika muncul peringatan bahwa folder ini belum repository, klik **create a repository**, lalu **Create Repository**.
4. Di kiri bawah, isi ringkasan commit, misalnya `Commit pertama Aldebaran3DPrinting`, lalu klik **Commit to main**.
5. Klik **Publish repository**. Beri nama `aldebaran3dprinting`. Repo private tetap bisa dipakai Vercel.

## Publish ke Vercel

1. Buka https://vercel.com dan masuk dengan **Continue with GitHub**.
2. Klik **Add New > Project**, lalu **Import** repo `aldebaran3dprinting`. Jika repo tidak muncul, klik **Adjust GitHub App Permissions** dan izinkan repo tersebut.
3. **Framework Preset**: pilih **Other**. Biarkan Build Command dan Output Directory kosong.
4. Buka **Environment Variables**, tambahkan `APP_SECRET` dengan isi teks acak yang panjang. Contoh pembuatan: `php -r "echo bin2hex(random_bytes(24));"`.
5. Klik **Deploy**. Setelah selesai, situs bisa dibuka di alamat `https://nama-proyek.vercel.app`.

## Memperbarui situs

Ubah file di komputer, buka GitHub Desktop, tulis ringkasan commit, klik **Commit to main**, lalu **Push origin**. Vercel akan otomatis memasang versi terbaru dalam beberapa menit.

## Domain sendiri

Di Vercel buka **Project > Settings > Domains**, tambahkan domain Anda, lalu atur DNS sesuai petunjuk di layar.

## Catatan

- Keranjang disimpan di cookie browser (tanpa database), jadi isinya hilang jika cookie dihapus. Checkout masih demo dan belum terhubung pembayaran.
- Vercel tidak menjalankan PHP secara bawaan. Proyek ini memakai runtime komunitas `vercel-php` yang diatur di `vercel.json`.
- Paket gratis (Hobby) Vercel ditujukan untuk pemakaian non-komersial. Jika situs dipakai untuk berjualan sungguhan, cek ketentuan paketnya.
