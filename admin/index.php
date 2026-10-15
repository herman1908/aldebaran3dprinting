<?php
/**
 * Dashboard admin: daftar produk, tambah, edit, hapus, toggle terlaris/unggulan.
 */
require_once __DIR__ . '/../includes/auth.php';
admin_wajib_login();

$pesan = '';
$galat = '';

// Pesan sukses dari redirect simpan.
if (($_GET['pesan'] ?? '') === 'disimpan') {
    $pesan = 'Produk berhasil disimpan.';
}

// Aksi cepat: hapus / toggle.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_cek()) {
        $galat = 'Token keamanan tidak valid.';
    } else {
        $aksi  = (string)($_POST['aksi'] ?? '');
        $id    = (int)($_POST['id'] ?? 0);
        $field = (string)($_POST['field'] ?? '');

        if ($aksi === 'hapus' && $id > 0) {
            $p = produk_cari($id);
            if ($p) {
                produk_hapus($id);
                $pesan = 'Produk "' . $p['nama'] . '" dihapus.';
            } else {
                $galat = 'Produk tidak ditemukan.';
            }
        } elseif ($aksi === 'toggle' && $id > 0) {
            if (produk_toggle($id, $field)) {
                $pesan = 'Berhasil diperbarui.';
            } else {
                $galat = 'Gagal memperbarui.';
            }
        }
    }
}

$daftar = array_map('produk_normalisasi', produk_semua());
usort($daftar, fn($a, $b) => ($b['id'] <=> $a['id']));

$total     = count($daftar);
$unggulan  = count(array_filter($daftar, fn($p) => !empty($p['unggulan'])));
$terlaris  = count(array_filter($daftar, fn($p) => !empty($p['terlaris'])));
$siap_cloud = cloudinary_siap();

$judul_halaman = 'Admin — Kelola Produk';
require __DIR__ . '/../includes/admin-head.php';
?>

<div class="admin-wrap">
    <div class="admin-topbar">
        <div class="admin-judul">
            <h1>Kelola Produk</h1>
            <p><?= $total ?> produk &middot; <?= $unggulan ?> unggulan &middot; <?= $terlaris ?> terlaris</p>
        </div>
        <div class="admin-aksi">
            <a class="tombol besar" href="/admin/edit.php">+ Tambah Produk</a>
            <a class="tombol abu kecil" href="/" target="_blank">Lihat Situs ↗</a>
            <a class="tombol bahaya kecil" href="/admin/logout.php">Keluar</a>
        </div>
    </div>

    <?php if (!$siap_cloud): ?>
        <div class="admin-peringatan">
            Cloudinary belum dikonfigurasi. Set <code>CLOUDINARY_CLOUD_NAME</code> &amp; <code>CLOUDINARY_UPLOAD_PRESET</code>
            di environment variables agar upload gambar aktif. (Produk tetap bisa dikelola, gambar pakai URL manual.)
        </div>
    <?php endif; ?>

    <?php if ($pesan !== ''): ?><div class="admin-sukses"><?= e($pesan) ?></div><?php endif; ?>
    <?php if ($galat !== ''): ?><div class="admin-galat"><?= e($galat) ?></div><?php endif; ?>

    <div class="admin-tabel-bungkus">
        <table class="admin-tabel">
            <thead>
                <tr>
                    <th>Gambar</th>
                    <th>Produk</th>
                    <th>Kategori</th>
                    <th>Harga</th>
                    <th>Suka</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$daftar): ?>
                    <tr><td colspan="7" class="admin-kosong">Belum ada produk.</td></tr>
                <?php endif; ?>
                <?php foreach ($daftar as $p): ?>
                <tr>
                    <td class="admin-gambar-sel">
                        <?php if ($p['gambar'] !== ''): ?>
                            <img src="<?= e(gambar_url($p['gambar'])) ?>" alt="<?= e($p['nama']) ?>" loading="lazy">
                        <?php else: ?>
                            <span class="admin-noimg">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?= e($p['nama']) ?></strong>
                        <span class="admin-sub">#<?= $p['id'] ?> &middot; <?= e($p['penjual']) ?></span>
                    </td>
                    <td class="admin-kanan">
                        <?php foreach ($p['kategori'] as $k): ?>
                            <span class="admin-chip"><?= e($kategori[$k] ?? $k) ?></span>
                        <?php endforeach; ?>
                    </td>
                    <td class="admin-kanan"><?= rupiah($p['harga']) ?></td>
                    <td class="admin-kanan"><?= ringkas($p['suka']) ?></td>
                    <td class="admin-kanan">
                        <form method="post" class="admin-toggle">
                            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="aksi" value="toggle">
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <input type="hidden" name="field" value="unggulan">
                            <button type="submit" class="admin-pill <?= $p['unggulan'] ? 'on' : '' ?>">Unggulan</button>
                        </form>
                        <form method="post" class="admin-toggle">
                            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="aksi" value="toggle">
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <input type="hidden" name="field" value="terlaris">
                            <button type="submit" class="admin-pill <?= $p['terlaris'] ? 'on' : '' ?>">Terlaris</button>
                        </form>
                    </td>
                    <td class="admin-kanan admin-baris-aksi">
                        <a class="tombol abu kecil" href="/admin/edit.php?id=<?= $p['id'] ?>">Edit</a>
                        <form method="post" onsubmit="return confirm('Hapus produk ini?')">
                            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="aksi" value="hapus">
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <button type="submit" class="tombol bahaya kecil">Hapus</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/admin-foot.php'; ?>
