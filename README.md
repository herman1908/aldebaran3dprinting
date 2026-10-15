# Aldebaran3DPrinting

Toko mainan dan karya cetak 3D untuk balita sampai anak SMA. Dibuat dengan PHP native, tanpa framework dan tanpa database.

## Struktur folder

```
toko3d/
├── api/index.php        Pintu masuk untuk Vercel (router ke halaman PHP)
├── includes/            data.php (produk), functions.php, header, sidebar, footer
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

Semua produk ada di `includes/data.php`. Taruh foto produk di `public/images/`, lalu tulis nama file-nya di kolom `gambar`.

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
