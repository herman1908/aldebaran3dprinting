<?php
/**
 * Header minimal untuk panel admin (tanpa sidebar toko).
 * Pakai: require __DIR__ . '/../includes/admin-head.php'; lalu set $judul_halaman.
 */
require_once __DIR__ . '/functions.php';
$judul_halaman = $judul_halaman ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title><?= e($judul_halaman) ?> | <?= e(NAMA_TOKO) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="/images/aldi-maskot.svg" type="image/svg+xml">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="halaman-admin">
<header class="topbar">
    <a class="logo" href="/admin/">
        <span class="logo-bubble"><img src="/images/aldi-maskot.svg" alt="" width="22" height="22"></span>
        <span class="toko-nama"><?= e(NAMA_TOKO) ?><small>Panel Admin</small></span>
    </a>
    <nav class="admin-nav">
        <a href="/admin/">Produk</a>
        <a href="/admin/edit.php">Tambah</a>
        <a href="/" target="_blank">Situs ↗</a>
        <a href="/admin/logout.php">Keluar</a>
    </nav>
</header>
<main class="admin-main">
