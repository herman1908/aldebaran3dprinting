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
    <meta name="description" content="Aldebaran3DPrinting — studio cetak 3D: fidget clicker, gantungan kunci, action figure, pajangan dan dekor kamar.">
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
        <?php if (jumlah_keranjang() > 0): ?>
            <span class="lencana"><?= jumlah_keranjang() ?></span>
        <?php endif; ?>
    </a>
</header>

<div class="tata-letak">
<?php require __DIR__ . '/sidebar.php'; ?>
<div class="lapisan" id="lapisan"></div>
<main class="konten">
