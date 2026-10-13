<?php
require_once __DIR__ . '/includes/functions.php';

$p = cari_produk((int)($_GET['id'] ?? 0));
if (!$p) {
    http_response_code(404);
    $judul_halaman = 'Produk tidak ditemukan';
    require __DIR__ . '/includes/header.php';
    echo '<div class="kosong"><p>Ups, produknya nggak ketemu 🥺</p><a class="tombol" href="index.php">🏠 Kembali ke beranda</a></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

// Produk terkait: kategori sama, bukan produk ini
$terkait = array_filter($produk, function ($x) use ($p) {
    return $x['id'] !== $p['id'] && array_intersect($x['kategori'], $p['kategori']);
});
$terkait = array_slice(array_values($terkait), 0, 3);

$z = $usia[$p['usia']];
$judul_halaman = $p['nama'];
$menu_aktif = $p['usia'];
require __DIR__ . '/includes/header.php';
?>

<nav class="remah">
    <a href="index.php">🏠 Beranda</a> ›
    <a href="index.php?usia=<?= e($p['usia']) ?>#produk"><?= e($z['label']) ?></a> ›
    <a href="index.php?kategori=<?= e($p['kategori'][0]) ?>"><?= e($kategori[$p['kategori'][0]]) ?></a> ›
    <?= e($p['nama']) ?>
</nav>

<section class="detail">
    <div class="detail-gambar warna-<?= e($z['warna']) ?>">
        <div class="detail-badge-float">
            <?php if ($p['terlaris']): ?><span>🌟 Terlaris!</span><?php endif; ?>
            <span>✨ <?= e($z['label']) ?></span>
        </div>
        <img src="<?= e(gambar_url($p['gambar'])) ?>" alt="<?= e($p['nama']) ?>">
    </div>
    <div class="detail-info">
        <h1><?= e($p['nama']) ?></h1>
        <p class="penjual"><span class="avatar"><?= e(mb_strtoupper(mb_substr($p['penjual'], 0, 1))) ?></span> oleh <?= e($p['penjual']) ?> ✔</p>
        <div class="detail-rating">
            <span class="stars">★★★★★</span>
            <span>4.9 · ❤ <?= ringkas($p['suka']) ?> suka<?php if ($p['unduhan'] > 0): ?> · 📦 <?= ringkas($p['unduhan']) ?> terjual<?php endif; ?></span>
        </div>
        <p class="tag-usia warna-<?= e($z['warna']) ?>">Cocok untuk <?= e($z['label']) ?> &middot; <?= e($z['rentang']) ?></p>
        <p class="harga-besar"><?= rupiah($p['harga']) ?></p>
        <p><?= e($p['deskripsi']) ?></p>

        <div class="warna-picker" aria-label="Pilih warna">
            <strong style="font-size:13.5px">🎨 Pilih warna:</strong>
            <span class="warna-dot pink" title="Pink"></span>
            <span class="warna-dot mint" title="Mint"></span>
            <span class="warna-dot sky" title="Sky"></span>
            <span class="warna-dot lilac" title="Lilac"></span>
            <span class="warna-dot lemon" title="Lemon"></span>
        </div>

        <?php if ($p['usia'] === 'balita'): ?>
            <p class="catatan">🧸 Selalu dampingi balita saat bermain dan periksa mainan secara berkala ya!</p>
        <?php else: ?>
            <p class="catatan" style="background:#EDE4FF;border-color:#8B5CF6;color:#4C2EB5">💡 Tips: semua warna pastel bisa di-request gratis, tinggal tulis di catatan checkout~</p>
        <?php endif; ?>

        <div class="tag-baris">
            <?php foreach ($p['kategori'] as $k): ?>
                <a class="chip" href="index.php?kategori=<?= e($k) ?>"><?= e($kategori[$k]) ?></a>
            <?php endforeach; ?>
        </div>

        <form method="post" action="keranjang.php" class="form-beli">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="aksi" value="tambah">
            <input type="hidden" name="id" value="<?= $p['id'] ?>">
            <label for="qty">Jumlah</label>
            <span class="qty-stepper">
                <button type="button" data-step="-1" aria-label="Kurangi">−</button>
                <input type="number" id="qty" name="qty" value="1" min="1" max="99">
                <button type="button" data-step="1" aria-label="Tambah">+</button>
            </span>
            <button class="tombol" type="submit">🛒 Tambah ke Keranjang</button>
        </form>
    </div>
</section>

<?php if ($terkait): ?>
    <h2 class="judul-bagian">💖 Kamu mungkin juga suka</h2>
    <section class="grid-produk">
        <?php foreach ($terkait as $t): ?>
            <article class="kartu reveal">
                <a class="kartu-gambar warna-<?= e($usia[$t['usia']]['warna']) ?>" href="produk.php?id=<?= $t['id'] ?>">
                    <span class="kartu-hati" aria-hidden="true">💗</span>
                    <img src="<?= e(gambar_url($t['gambar'])) ?>" alt="<?= e($t['nama']) ?>" loading="lazy">
                </a>
                <div class="kartu-isi"><h3 class="kartu-judul"><a href="produk.php?id=<?= $t['id'] ?>"><?= e($t['nama']) ?></a></h3><strong class="harga"><?= rupiah($t['harga']) ?></strong></div>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
