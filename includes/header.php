<?php
// Variabel opsional: $judul_halaman, $menu_aktif, $og_judul, $og_deskripsi,
// $og_gambar, $og_url, $og_tipe, $og_gambar_lebar, $og_gambar_tinggi, $og_gambar_tipe
$judul_halaman = $judul_halaman ?? 'Beranda';
$menu_aktif    = $menu_aktif ?? 'beranda';

$og_judul      = $og_judul      ?? ($judul_halaman . ' | ' . NAMA_TOKO);
$og_deskripsi  = $og_deskripsi  ?? 'Aldebaran3DPrinting — studio cetak 3D: fidget clicker, gantungan kunci, action figure, pajangan dan dekor kamar.';
$og_tipe       = $og_tipe       ?? 'website';
$og_gambar     = $og_gambar     ?? '/images/og-banner-2026.jpg';
$og_url        = $og_url        ?? url_absolut($_SERVER['REQUEST_URI'] ?? '/');
$og_gambar_abs = og_gambar_url($og_gambar);

// Banner bawaan berukuran 1200x630; halaman produk boleh mengisi sendiri.
if ($og_gambar === '/images/og-banner-2026.jpg') {
    $og_gambar_lebar  = $og_gambar_lebar  ?? 1200;
    $og_gambar_tinggi = $og_gambar_tinggi ?? 630;
    $og_gambar_tipe   = $og_gambar_tipe   ?? 'image/jpeg';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= e($og_deskripsi) ?>">
    <title><?= e($judul_halaman) ?> | <?= e(NAMA_TOKO) ?></title>

    <!-- Open Graph: dipakai WhatsApp/Facebook/Telegram untuk thumbnail tautan. -->
    <meta property="og:type" content="<?= e($og_tipe) ?>">
    <meta property="og:site_name" content="<?= e(NAMA_TOKO) ?>">
    <meta property="og:locale" content="id_ID">
    <meta property="og:title" content="<?= e($og_judul) ?>">
    <meta property="og:description" content="<?= e($og_deskripsi) ?>">
    <meta property="og:url" content="<?= e($og_url) ?>">
    <meta property="og:image" content="<?= e($og_gambar_abs) ?>">
    <meta property="og:image:secure_url" content="<?= e($og_gambar_abs) ?>">
    <meta property="og:image:alt" content="<?= e($og_judul) ?>">
<?php if (!empty($og_gambar_lebar)): ?>
    <meta property="og:image:width" content="<?= (int)$og_gambar_lebar ?>">
<?php endif; ?>
<?php if (!empty($og_gambar_tinggi)): ?>
    <meta property="og:image:height" content="<?= (int)$og_gambar_tinggi ?>">
<?php endif; ?>
<?php if (!empty($og_gambar_tipe)): ?>
    <meta property="og:image:type" content="<?= e($og_gambar_tipe) ?>">
<?php endif; ?>

    <!-- Twitter/X card (dipakai juga oleh beberapa aplikasi chat). -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($og_judul) ?>">
    <meta name="twitter:description" content="<?= e($og_deskripsi) ?>">
    <meta name="twitter:image" content="<?= e($og_gambar_abs) ?>">
    <link rel="image_src" href="<?= e($og_gambar_abs) ?>">
    <meta name="theme-color" content="#FF7AA2">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="/images/aldi-maskot.svg" type="image/svg+xml">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<header class="topbar">
    <button class="tombol-menu" id="tombolMenu" aria-label="Buka menu">☰</button>
    <a class="logo" href="index.php" aria-label="Ke beranda">
        <span class="logo-bubble"><img src="/images/aldi-maskot.svg" alt="" width="22" height="22"></span>
        <span class="toko-nama"><?= e(NAMA_TOKO) ?><small>3D Print Studio</small></span>
    </a>

    <form class="form-cari" action="index.php" method="get">
        <input type="search" name="q" placeholder="Cari clicker, gantungan, figure, dekor..."
               value="<?= e($_GET['q'] ?? '') ?>" aria-label="Cari produk">
        <button type="submit">Cari</button>
    </form>

    <a class="tombol-keranjang" href="keranjang.php" aria-label="Keranjang belanja">
        <span class="ikon-keranjang" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg></span><span class="teks-keranjang">Keranjang</span>
        <span class="lencana" id="lencanaKeranjang"<?= jumlah_keranjang() > 0 ? '' : ' hidden' ?>><?= jumlah_keranjang() ?></span>
    </a>
</header>

<div class="tata-letak">
<?php require __DIR__ . '/sidebar.php'; ?>
<div class="lapisan" id="lapisan"></div>
<main class="konten">
