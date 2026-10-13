<?php
require_once __DIR__ . '/includes/functions.php';

$kat  = $_GET['kategori'] ?? 'semua';
$u    = $_GET['usia'] ?? '';
$q    = trim($_GET['q'] ?? '');
$urut = $_GET['urut'] ?? 'populer';

if (!isset($kategori[$kat])) $kat = 'semua';
if (!isset($usia[$u])) $u = '';

/** Bangun URL daftar produk dengan mengganti sebagian parameter. */
function url_daftar(array $ubah): string {
    global $kat, $u, $q, $urut;
    $p = array_merge(['kategori' => $kat, 'usia' => $u, 'q' => $q, 'urut' => $urut], $ubah);
    foreach (['kategori' => 'semua', 'usia' => '', 'q' => '', 'urut' => 'populer'] as $k => $default) {
        if ($p[$k] === $default) unset($p[$k]);
    }
    return 'index.php' . ($p ? '?' . http_build_query($p) : '') . '#produk';
}

// Filter
$hasil = array_filter($produk, function ($p) use ($kat, $u, $q) {
    if ($kat !== 'semua' && !in_array($kat, $p['kategori'], true)) return false;
    if ($u !== '' && $p['usia'] !== $u) return false;
    if ($q !== '' && stripos($p['nama'], $q) === false && stripos($p['penjual'], $q) === false) return false;
    return true;
});

// Urutkan
$hasil = array_values($hasil);
usort($hasil, function ($a, $b) use ($urut) {
    switch ($urut) {
        case 'termurah': return $a['harga'] <=> $b['harga'];
        case 'termahal': return $b['harga'] <=> $a['harga'];
        case 'terbaru':  return $b['id'] <=> $a['id'];
        default:         return $b['suka'] <=> $a['suka'];
    }
});

$ada_filter = $kat !== 'semua' || $u !== '' || $q !== '';
if ($u !== '')            $menu_aktif = $u;
elseif ($kat !== 'semua') $menu_aktif = $kat;
else                      $menu_aktif = $q === '' ? 'beranda' : '';

$judul_halaman = $u !== '' ? $usia[$u]['label'] : ($kat === 'semua' ? 'Beranda' : $kategori[$kat]);
require __DIR__ . '/includes/header.php';
?>

<?php if (!$ada_filter): ?>
<section class="hero">
    <div class="hero-teks">
        <span class="hero-badge"><span class="dot">✨</span> Bisa pilih warna sendiri, lho!</span>
        <h1>Mainan 3D <span class="hl-pink">super gemas</span> buat <span class="hl-mint">balita</span> sampai <span class="hl-sky">anak SMA</span></h1>
        <p>Dicetak dengan printer 3D, warnanya pastel semua~ Tinggal pilih usiamu, lalu temukan mainan, alat sekolah, dan hiasan kamar yang paling kamu suka. 💖</p>
        <div class="hero-cta-row">
            <a class="tombol besar" href="#zona">🎈 Pilih sesuai usia</a>
            <a class="tombol besar ghost" href="#produk">Lihat semua 👀</a>
        </div>
        <div class="hero-stats">
            <div class="hero-stat"><strong><?= count($produk) ?>+</strong><span>produk lucu</span></div>
            <div class="hero-stat"><strong>3 zona</strong><span>usia main</span></div>
            <div class="hero-stat"><strong>100%</strong><span>handmade 3D</span></div>
        </div>
    </div>
    <div class="hero-gambar" aria-hidden="true">
        <span class="hero-blob"></span>
        <span class="orbit"></span>
        <img class="maskot" src="/images/aldi-maskot.svg" alt="" width="220" height="220">
        <span class="kubus k1"></span><span class="kubus k2"></span><span class="kubus k3"></span>
        <span class="hero-sticker s1">🌟 Terlaris!</span>
        <span class="hero-sticker s2">🎨 pastel edition</span>
    </div>
</section>
<div class="pita-info" aria-hidden="true">
    <span class="pita-item"><i>🚚</i> Gratis bungkus kado</span>
    <span class="pita-item"><i>🎨</i> Warna bisa custom</span>
    <span class="pita-item"><i>🛡️</i> Aman untuk anak</span>
    <span class="pita-item"><i>⭐</i> 4.9 dari 2.000+ ulasan</span>
</div>
<?php endif; ?>

<section class="zona" id="zona" aria-label="Pilih sesuai usia">
    <?php
    $ikon_zona = ['balita' => '🧸', 'anak' => '🚀', 'remaja' => '🎧'];
    foreach ($usia as $kunci => $z): ?>
        <a class="zona-kartu warna-<?= e($z['warna']) ?> <?= $u === $kunci ? 'aktif' : '' ?>"
           href="<?= e($u === $kunci ? url_daftar(['usia' => '']) : url_daftar(['usia' => $kunci])) ?>">
            <span class="zona-top">
                <span class="zona-ikon"><?= $ikon_zona[$kunci] ?? '✨' ?></span>
                <span class="zona-rentang"><?= e($z['rentang']) ?></span>
            </span>
            <strong class="zona-judul"><?= e($z['label']) ?></strong>
            <span class="zona-ket"><?= e($z['ket']) ?></span>
            <span class="zona-panah"><i>→</i> <?= $u === $kunci ? 'Klik lagi buat reset' : 'Yuk intip!' ?></span>
        </a>
    <?php endforeach; ?>
</section>

<div class="chip-baris" id="produk" role="tablist">
    <?php foreach ($kategori as $kunci => $label): ?>
        <a class="chip <?= $kunci === $kat ? 'aktif' : '' ?>" href="<?= e(url_daftar(['kategori' => $kunci])) ?>">
            <?= e($label) ?>
        </a>
    <?php endforeach; ?>
</div>

<div class="bar-urut">
    <span class="info-hasil">
        <?= count($hasil) ?> produk<?= $u !== '' ? ' untuk ' . e($usia[$u]['label']) : '' ?><?= $q !== '' ? ' dengan kata &ldquo;' . e($q) . '&rdquo;' : '' ?>
    </span>
    <form method="get" action="index.php">
        <input type="hidden" name="kategori" value="<?= e($kat) ?>">
        <?php if ($u !== ''): ?><input type="hidden" name="usia" value="<?= e($u) ?>"><?php endif; ?>
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
        <p>Yahh, belum ada yang cocok 🥺 Coba kata lain atau lihat semua produk yang gemas-gemas!</p>
        <a class="tombol" href="index.php#produk">✨ Lihat semua produk</a>
    </div>
<?php else: ?>
    <section class="grid-produk">
        <?php foreach ($hasil as $p): $z = $usia[$p['usia']]; ?>
            <article class="kartu reveal">
                <a class="kartu-gambar warna-<?= e($z['warna']) ?>" href="produk.php?id=<?= $p['id'] ?>">
                    <?php if ($p['terlaris']): ?><span class="stiker">🌟 Terlaris!</span><?php endif; ?>
                    <span class="kartu-hati" aria-hidden="true">💗</span>
                    <img src="<?= e(gambar_url($p['gambar'])) ?>" alt="<?= e($p['nama']) ?>" loading="lazy">
                </a>
                <div class="kartu-isi">
                    <span class="tag-usia warna-<?= e($z['warna']) ?>">✨ <?= e($z['label']) ?> &middot; <?= e($z['rentang']) ?></span>
                    <h3 class="kartu-judul"><a href="produk.php?id=<?= $p['id'] ?>"><?= e($p['nama']) ?></a></h3>
                    <div class="kartu-meta">
                        <span class="penjual"><span class="avatar"><?= e(mb_strtoupper(mb_substr($p['penjual'], 0, 1))) ?></span><?= e($p['penjual']) ?></span>
                        <span class="statistik">❤ <?= ringkas($p['suka']) ?></span>
                    </div>
                    <div class="kartu-bawah">
                        <strong class="harga"><?= rupiah($p['harga']) ?></strong>
                        <form method="post" action="keranjang.php">
                            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                            <input type="hidden" name="aksi" value="tambah">
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <button class="tombol kecil" type="submit">+ 🛒</button>
                        </form>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
