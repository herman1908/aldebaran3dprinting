<?php
// Variabel opsional: $judul_halaman, $menu_aktif
$judul_halaman = $judul_halaman ?? 'Beranda';
$menu_aktif    = $menu_aktif ?? 'beranda';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Aldebaran3DPrinting — mainan & karya cetak 3D pastel yang lucu untuk balita sampai SMA.">
    <title><?= e($judul_halaman) ?> | <?= e(NAMA_TOKO) ?></title>
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
        <span class="logo-bubble"><img src="/images/aldi-maskot.svg" alt="" width="30" height="30"></span>
        <span class="toko-nama"><?= e(NAMA_TOKO) ?><small>✨ pastel 3D shop ✨</small></span>
    </a>

    <form class="form-cari" action="index.php" method="get">
        <input type="search" name="q" placeholder="Cari dino, robot, gantungan nama... 🔍"
               value="<?= e($_GET['q'] ?? '') ?>" aria-label="Cari produk">
        <button type="submit">Cari ✨</button>
    </form>

    <a class="tombol-keranjang" href="keranjang.php" aria-label="Keranjang belanja">
        <span class="ikon-keranjang" aria-hidden="true">🛒</span><span class="teks-keranjang"> Keranjang</span>
        <?php if (jumlah_keranjang() > 0): ?>
            <span class="lencana"><?= jumlah_keranjang() ?></span>
        <?php endif; ?>
    </a>
</header>

<div class="tata-letak">
<?php require __DIR__ . '/sidebar.php'; ?>
<div class="lapisan" id="lapisan"></div>
<main class="konten">
