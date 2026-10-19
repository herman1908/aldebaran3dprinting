<?php
require_once __DIR__ . '/includes/functions.php';

$kat  = $_GET['kategori'] ?? 'semua';
$q    = trim($_GET['q'] ?? '');
$urut = $_GET['urut'] ?? 'populer';

if (!isset($kategori[$kat])) $kat = 'semua';

// "Terlaris" dihitung: pin manual + top-N checkout otomatis.
$terlaris_ids = produk_terlaris_ids();
$terlaris_set = array_fill_keys($terlaris_ids, true);

/** Bangun URL daftar produk dengan mengganti sebagian parameter. */
function url_daftar(array $ubah): string {
    global $kat, $q, $urut;
    $p = array_merge(['kategori' => $kat, 'q' => $q, 'urut' => $urut], $ubah);
    foreach (['kategori' => 'semua', 'q' => '', 'urut' => 'populer'] as $k => $default) {
        if ($p[$k] === $default) unset($p[$k]);
    }
    return 'index.php' . ($p ? '?' . http_build_query($p) : '') . '#produk';
}

// Filter
$hasil = array_filter($produk, function ($p) use ($kat, $q, $terlaris_set) {
    if ($kat === 'terlaris') {
        if (!isset($terlaris_set[$p['id']])) return false;
    } elseif ($kat !== 'semua' && !in_array($kat, $p['kategori'], true)) {
        return false;
    }
    if ($q !== '' && stripos($p['nama'], $q) === false && stripos($p['penjual'], $q) === false) return false;
    return true;
});

// Urutkan: produk 'unggulan' selalu dikunci di paling depan,
// baru sisanya ikut mode urut (populer/terbaru/termurah/termahal).
$hasil = array_values($hasil);

if ($kat === 'terlaris') {
    // Halaman Terlaris: ikut urutan pin manual lalu checkout terbanyak.
    $posisi = array_flip($terlaris_ids);
    usort($hasil, fn($a, $b) => ($posisi[$a['id']] ?? PHP_INT_MAX) <=> ($posisi[$b['id']] ?? PHP_INT_MAX));
} else {
    usort($hasil, function ($a, $b) use ($urut) {
        $ua = !empty($a['unggulan']) ? 1 : 0;
        $ub = !empty($b['unggulan']) ? 1 : 0;
        if ($ua !== $ub) return $ub <=> $ua;
        switch ($urut) {
            case 'termurah': return $a['harga'] <=> $b['harga'];
            case 'termahal': return $b['harga'] <=> $a['harga'];
            case 'terbaru':  return $b['id'] <=> $a['id'];
            default:         return $b['suka'] <=> $a['suka'];
        }
    });
}

$ada_filter = $kat !== 'semua' || $q !== '';
if ($kat !== 'semua') $menu_aktif = $kat;
else                   $menu_aktif = $q === '' ? 'beranda' : '';

$judul_halaman = $kat === 'semua' ? 'Beranda' : $kategori[$kat];

// Hero carousel: 4 produk terbaik (unggulan dulu, lalu paling disukai).
$hero_produk = $produk;
usort($hero_produk, function ($a, $b) {
    $ua = !empty($a['unggulan']) ? 1 : 0;
    $ub = !empty($b['unggulan']) ? 1 : 0;
    if ($ua !== $ub) return $ub <=> $ua;
    return $b['suka'] <=> $a['suka'];
});
$hero_produk = array_slice(array_values($hero_produk), 0, 4);

require __DIR__ . '/includes/header.php';
?>

<?php if (!$ada_filter): ?>
<section class="hero">
    <span class="hero-blob b1" aria-hidden="true"></span>
    <span class="hero-blob b2" aria-hidden="true"></span>
    <span class="hero-blob b3" aria-hidden="true"></span>
    <span class="hero-doodle d1" aria-hidden="true">✦</span>
    <span class="hero-doodle d2" aria-hidden="true">★</span>
    <span class="hero-doodle d3" aria-hidden="true">♥</span>
    <span class="hero-doodle d4" aria-hidden="true">✧</span>
    <span class="hero-doodle d5" aria-hidden="true">✩</span>

    <div class="hero-teks">
        <span class="hero-badge"><span class="dot">Fun</span> Warna &amp; bentuk bebas kamu atur sendiri</span>
        <h1>Mainan 3D yang bikin kamar &amp; mood <span class="hl-grad">auto ceria!</span></h1>
        <p>Clicker anti-bosan, figure gemas, sampai dekor kamar aesthetic. Dicetak presisi, warnanya bisa custom, dan siap bikin teman-teman kamu iri (dalam artian baik ya).</p>
        <div class="hero-cta-row">
            <a class="tombol besar" href="#produk">Gas jelajah <span aria-hidden="true">→</span></a>
            <a class="tombol besar ghost" href="#produk">Ide custom</a>
        </div>
        <div class="hero-stats">
            <div class="hero-stat"><strong><?= count($produk) ?>+</strong><span>Produk lucu</span></div>
            <div class="hero-stat"><strong><?= count($kategori_meta) ?></strong><span>Kategori bentuk</span></div>
            <div class="hero-stat"><strong>4.9/5</strong><span>2.000+ ulasan</span></div>
        </div>
    </div>

    <div class="hero-gambar">
        <span class="hero-tape t1" aria-hidden="true"></span>
        <span class="hero-tape t2" aria-hidden="true"></span>
        <div class="hero-carousel" id="heroCarousel" role="region" aria-roledescription="carousel" aria-label="Produk unggulan">
            <div class="hero-carousel-bar">
                <span class="hero-live"><i aria-hidden="true"></i> Lagi ngetren</span>
                <span class="hero-counter" id="heroCounter"><?= sprintf('%02d / %02d', 1, max(1, count($hero_produk))) ?></span>
            </div>
            <div class="hero-viewport">
                <div class="hero-track" id="heroTrack">
                    <?php foreach ($hero_produk as $i => $hp):
                        $hp_kat = $hp['kategori'][0] ?? 'semua';
                        $hp_kat_label = $kategori[$hp_kat] ?? $hp_kat;
                    ?>
                    <article class="hero-slide<?= $i === 0 ? ' aktif' : '' ?>" data-index="<?= $i ?>" aria-hidden="<?= $i === 0 ? 'false' : 'true' ?>">
                        <a class="hero-slide-img" href="produk.php?id=<?= $hp['id'] ?>" tabindex="<?= $i === 0 ? '0' : '-1' ?>">
                            <img src="<?= e(gambar_url($hp['gambar'])) ?>" alt="<?= e($hp['nama']) ?>" <?= $i === 0 ? 'fetchpriority="high"' : 'loading="lazy"' ?>>
                            <?php if (isset($terlaris_set[$hp['id']])): ?><span class="stiker">Terlaris</span><?php endif; ?>
                            <span class="hero-slide-glare" aria-hidden="true"></span>
                            <div class="hero-slide-overlay">
                                <span class="hero-slide-kat"><?= e($hp_kat_label) ?></span>
                                <strong class="hero-slide-nama"><?= e($hp['nama']) ?></strong>
                            </div>
                        </a>
                        <div class="hero-slide-info">
                            <div>
                                <span class="hero-slide-label">Mulai dari</span>
                                <span class="hero-slide-harga"><?= rupiah($hp['harga']) ?></span>
                            </div>
                            <a class="tombol kecil hero-slide-cta" href="produk.php?id=<?= $hp['id'] ?>">Lihat <span aria-hidden="true">→</span></a>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="hero-nav">
                <button class="hero-arrow" id="heroPrev" type="button" aria-label="Produk sebelumnya">←</button>
                <div class="hero-dots" role="tablist" aria-label="Pilih slide">
                    <?php foreach ($hero_produk as $i => $hp): ?>
                    <button class="hero-dot<?= $i === 0 ? ' aktif' : '' ?>" type="button" data-go="<?= $i ?>" role="tab" aria-label="Slide <?= $i + 1 ?>"></button>
                    <?php endforeach; ?>
                </div>
                <div class="hero-nav-kanan">
                    <button class="hero-pause" id="heroPause" type="button" aria-label="Jeda slide otomatis" aria-pressed="false">❚❚</button>
                    <button class="hero-arrow" id="heroNext" type="button" aria-label="Produk berikutnya">→</button>
                </div>
            </div>
            <div class="hero-progress" aria-hidden="true"><i id="heroProgress"></i></div>
        </div>
        <span class="hero-sticker s1">100%<br>custom</span>
        <span class="hero-sticker s2">★ gemas maksimal ★</span>
    </div>
</section>
<div class="pita-info" aria-hidden="true">
    <span class="pita-item"><i>—</i> Bungkus kado gratis</span>
    <span class="pita-item"><i>—</i> Custom warna & bentuk</span>
    <span class="pita-item"><i>—</i> Finishing rapi & presisi</span>
    <span class="pita-item"><i>—</i> Rating 4.9 dari 2.000+ ulasan</span>
</div>
<?php endif; ?>

<div class="bar-urut" id="produk">
    <span class="info-hasil">
        <?= count($hasil) ?> produk<?= $kat !== 'semua' ? ' · ' . e($kategori[$kat]) : '' ?><?= $q !== '' ? ' dengan kata &ldquo;' . e($q) . '&rdquo;' : '' ?>
    </span>
    <form method="get" action="index.php">
        <input type="hidden" name="kategori" value="<?= e($kat) ?>">
        <?php if ($q !== ''): ?><input type="hidden" name="q" value="<?= e($q) ?>"><?php endif; ?>
        <label for="urut">Urutkan:</label>
        <select name="urut" id="urut" onchange="this.form.submit()">
            <option value="populer"  <?= $urut === 'populer'  ? 'selected' : '' ?>>Paling disukai</option>
            <option value="terbaru"  <?= $urut === 'terbaru'  ? 'selected' : '' ?>>Terbaru</option>
            <option value="termurah" <?= $urut === 'termurah' ? 'selected' : '' ?>>Harga termurah</option>
            <option value="termahal" <?= $urut === 'termahal' ? 'selected' : '' ?>>Harga termahal</option>
        </select>
    </form>
</div>

<?php if (!$hasil): ?>
    <div class="kosong">
        <img src="/images/aldi-maskot.svg" alt="" width="90" height="90">
        <p>Tidak ada produk yang cocok. Coba kata kunci lain atau tampilkan semua katalog.</p>
        <a class="tombol" href="index.php#produk">Lihat semua produk</a>
    </div>
<?php else: ?>
    <section class="grid-produk">
        <?php foreach ($hasil as $p):
            $warna = $p['warna'] ?? 'matahari';
            $kat_utama = $p['kategori'][0] ?? 'semua';
            $kat_label = $kategori[$kat_utama] ?? $kat_utama;
        ?>
            <article class="kartu reveal">
                <a class="kartu-gambar warna-<?= e($warna) ?>" href="produk.php?id=<?= $p['id'] ?>">
                    <?php if (isset($terlaris_set[$p['id']])): ?><span class="stiker">Terlaris</span><?php endif; ?>
                    <img src="<?= e(gambar_url($p['gambar'])) ?>" alt="<?= e($p['nama']) ?>" loading="lazy">
                </a>
                <div class="kartu-isi">
                    <span class="tag-usia warna-<?= e($warna) ?>"><?= e($kat_label) ?></span>
                    <h3 class="kartu-judul"><a href="produk.php?id=<?= $p['id'] ?>"><?= e($p['nama']) ?></a></h3>
                    <div class="kartu-meta">
                        <span class="penjual"><span class="avatar"><?= e(mb_strtoupper(mb_substr($p['penjual'], 0, 1))) ?></span><?= e($p['penjual']) ?></span>
                        <span class="statistik"><?= ringkas($p['suka']) ?> suka</span>
                    </div>
                    <div class="kartu-bawah">
                        <strong class="harga"><?= rupiah($p['harga']) ?></strong>
                        <form method="post" action="keranjang.php">
                            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                            <input type="hidden" name="aksi" value="tambah">
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <button class="tombol kecil" type="submit">+ Keranjang</button>
                        </form>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
