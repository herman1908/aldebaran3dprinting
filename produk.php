<?php
require_once __DIR__ . '/includes/functions.php';

$p = cari_produk((int)($_GET['id'] ?? 0));
if (!$p) {
    http_response_code(404);
    $judul_halaman = 'Produk tidak ditemukan';
    require __DIR__ . '/includes/header.php';
    echo '<div class="kosong"><p>Produk tidak ditemukan.</p><a class="tombol" href="index.php">Kembali ke beranda</a></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

// Produk terkait: kategori sama, bukan produk ini
$terkait = array_filter($produk, function ($x) use ($p) {
    return $x['id'] !== $p['id'] && array_intersect($x['kategori'], $p['kategori']);
});
$terkait = array_slice(array_values($terkait), 0, 3);

$warna = $p['warna'] ?? 'matahari';
$kat_utama = $p['kategori'][0] ?? 'semua';
$kat_label = $kategori[$kat_utama] ?? $kat_utama;
$judul_halaman = $p['nama'];
$menu_aktif = $kat_utama;
// Galeri: pakai 'galeri' jika ada, fallback ke 'gambar' tunggal.
// Konvensi: gambar ke-2 dst boleh pakai akhiran _b, _c, _d, dst.
$galeri = array_values(array_filter((array)($p['galeri'] ?? [])));
if (!$galeri && ($p['gambar'] ?? '') !== '') $galeri = [$p['gambar']];
if (!$galeri) $galeri = [''];
$label_galeri = ['Utama', 'B', 'C', 'D', 'E', 'F'];
$terlaris_set = produk_terlaris_set();

// Pratinjau tautan memakai foto produk ini (WhatsApp/Facebook/Telegram).
$og_judul     = $p['nama'] . ' | ' . NAMA_TOKO;
$og_deskripsi = $p['nama'] . ' — ' . rupiah((int)$p['harga']) . '. ' . ($p['deskripsi'] ?? '');
$og_url       = url_absolut('/produk.php?id=' . $p['id']);
if (($galeri[0] ?? '') !== '') {
    $og_gambar = gambar_url($galeri[0]);
    if (!preg_match('#^https?://#i', $og_gambar)) {
        $berkas_og = __DIR__ . '/public' . $og_gambar;
        if (is_file($berkas_og) && ($ukuran_og = @getimagesize($berkas_og))) {
            $og_gambar_lebar  = $ukuran_og[0];
            $og_gambar_tinggi = $ukuran_og[1];
            $og_gambar_tipe   = $ukuran_og['mime'];
        }
    }
}
require __DIR__ . '/includes/header.php';
?>

<nav class="remah">
    <a href="index.php">Beranda</a> <span>/</span>
    <a href="index.php?kategori=<?= e($kat_utama) ?>"><?= e($kat_label) ?></a> <span>/</span>
    <?= e($p['nama']) ?>
</nav>

<section class="detail">
    <div>
        <div class="detail-gambar warna-<?= e($warna) ?>">
            <div class="detail-badge-float">
                <?php if (isset($terlaris_set[$p['id']])): ?><span>Terlaris</span><?php endif; ?>
                <span><?= e($kat_label) ?></span>
            </div>
            <img id="gambarUtama" src="<?= e(gambar_url($galeri[0])) ?>" alt="<?= e($p['nama']) ?>" title="Klik untuk memperbesar">
        </div>
        <?php if (count($galeri) > 1): ?>
        <div class="galeri-thumbs" role="tablist" aria-label="Galeri produk">
            <?php foreach ($galeri as $i => $g): ?>
            <button class="thumb-btn <?= $i === 0 ? 'aktif' : '' ?>" data-src="<?= e(gambar_url($g)) ?>" data-index="<?= $i ?>">
                <img src="<?= e(gambar_url($g)) ?>" alt="<?= e($p['nama']) ?> - foto <?= e($label_galeri[$i] ?? ($i + 1)) ?>" loading="lazy">
            </button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <span class="detail-zoom-hint">Klik gambar untuk memperbesar.</span>
        <div class="custom-notice">
            <strong>Butuh versi custom?</strong>
            <p>Produk ini dapat disesuaikan — hubungi WhatsApp untuk konfirmasi <b>desain, harga, dan estimasi pengerjaan</b>.</p>
            <a class="tombol kecil" target="_blank" rel="noopener" href="<?= e(wa_link('Halo! Saya mau tanya custom 3D print: ' . $p['nama'])) ?>">Konsultasi via WhatsApp</a>
        </div>
    </div>
    <div class="detail-info">
        <h1><?= e($p['nama']) ?></h1>
        <p class="penjual"><span class="avatar"><?= e(mb_strtoupper(mb_substr($p['penjual'], 0, 1))) ?></span> <?= e($p['penjual']) ?></p>
        <div class="detail-rating">
            <span class="stars">★★★★★</span>
            <span>4.9 · <?= ringkas($p['suka']) ?> suka<?php if ($p['unduhan'] > 0): ?> · <?= ringkas($p['unduhan']) ?> terjual<?php endif; ?></span>
        </div>
        <p class="tag-usia warna-<?= e($warna) ?>"><?= e($kat_label) ?></p>
        <p class="harga-besar"><?= rupiah($p['harga']) ?></p>
        <p><?= e($p['deskripsi']) ?></p>

        <?php $pilihan_warna = $p['pilihan_warna'] ?? []; ?>
        <?php if ($pilihan_warna): ?>
        <div class="warna-picker" aria-label="Pilih warna">
            <strong style="font-size:13.5px">Pilih warna:</strong>
            <?php foreach ($pilihan_warna as $i => $w):
                $idx_gambar = isset($galeri[$i]) ? $i : 0;
            ?>
                <button type="button"
                        class="warna-dot<?= $i === 0 ? ' aktif' : '' ?>"
                        style="background:<?= e($w['hex']) ?>"
                        data-nama="<?= e($w['nama']) ?>"
                        data-gambar="<?= e(gambar_url($galeri[$idx_gambar] ?? '')) ?>"
                        data-gambar-index="<?= $idx_gambar ?>"
                        title="<?= e($w['nama']) ?>"
                        aria-label="<?= e($w['nama']) ?>"
                        aria-pressed="<?= $i === 0 ? 'true' : 'false' ?>"></button>
            <?php endforeach; ?>
        </div>

        <p class="catatan">Warna terpilih: <strong id="warnaTerpilih"><?= e($pilihan_warna[0]['nama']) ?></strong> — otomatis masuk ke keranjang dan pesan WhatsApp.</p>
        <?php endif; ?>

        <div class="tag-baris">
            <?php foreach ($p['kategori'] as $k): ?>
                <a class="chip" href="index.php?kategori=<?= e($k) ?>"><?= e($kategori[$k] ?? $k) ?></a>
            <?php endforeach; ?>
        </div>

        <form method="post" action="keranjang.php" class="form-beli">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="aksi" value="tambah">
            <input type="hidden" name="id" value="<?= $p['id'] ?>">
            <input type="hidden" name="warna" id="warnaInput" value="<?= e($pilihan_warna[0]['nama'] ?? '') ?>">
            <label for="qty">Jumlah</label>
            <span class="qty-stepper">
                <button type="button" data-step="-1" aria-label="Kurangi">−</button>
                <input type="number" id="qty" name="qty" value="1" min="1" max="99">
                <button type="button" data-step="1" aria-label="Tambah">+</button>
            </span>
            <button class="tombol" type="submit">Tambah ke Keranjang</button>
        </form>
    </div>
</section>

<?php if ($terkait): ?>
    <h2 class="judul-bagian">Produk terkait</h2>
    <section class="grid-produk">
        <?php foreach ($terkait as $t):
            $w = $t['warna'] ?? 'matahari';
        ?>
            <article class="kartu reveal">
                <a class="kartu-gambar warna-<?= e($w) ?>" href="produk.php?id=<?= $t['id'] ?>">
                    <img src="<?= e(gambar_url($t['gambar'])) ?>" alt="<?= e($t['nama']) ?>" loading="lazy">
                </a>
                <div class="kartu-isi"><h3 class="kartu-judul"><a href="produk.php?id=<?= $t['id'] ?>"><?= e($t['nama']) ?></a></h3><strong class="harga"><?= rupiah($t['harga']) ?></strong></div>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
