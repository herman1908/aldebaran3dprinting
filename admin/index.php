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
                if (produk_hapus($id)) {
                    $pesan = 'Produk "' . $p['nama'] . '" dihapus.';
                } else {
                    $galat = 'Gagal menghapus produk.';
                }
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
$terlaris_ids = produk_terlaris_ids();
$terlaris  = count($terlaris_ids);
$siap_cloud = cloudinary_siap();

// Statistik pengunjung (akumulatif di Supabase, fallback file lokal).
$stat              = statistik_ringkas();
$stat_harian       = $stat['harian'];
$stat_mingguan     = $stat['mingguan'];
$stat_hari_ini     = $stat['hari_ini'] ?: ['unik' => 0, 'views' => 0, 'random_unik' => 0, 'random_views' => 0, 'klik_unik' => 0, 'klik_views' => 0];
$stat_minggu_ini   = $stat['minggu_ini'] ?: ['unik' => 0, 'views' => 0, 'random_unik' => 0, 'random_views' => 0, 'klik_unik' => 0, 'klik_views' => 0];
$stat_halaman_klik = statistik_halaman_populer($stat['rows'], 8, 'klik');
$stat_halaman_rand = statistik_halaman_populer($stat['rows'], 8, 'random');

$judul_halaman = 'Admin — Kelola Produk';
require __DIR__ . '/../includes/admin-head.php';
?>

<div class="admin-wrap">
    <div class="admin-topbar">
        <div class="admin-judul">
            <h1>Kelola Produk</h1>
            <p><?= $total ?> produk &middot; <?= $unggulan ?> unggulan &middot; <?= $terlaris ?> terlaris &middot; <?= sb_aktif() ? 'DB aktif' : 'mode file' ?></p>
        </div>
        <div class="admin-aksi">
            <a class="tombol besar" href="/admin/edit.php">+ Tambah Produk</a>
            <a class="tombol abu kecil" href="/" target="_blank">Lihat Situs ↗</a>
            <a class="tombol bahaya kecil" href="/admin/logout.php">Keluar</a>
        </div>
    </div>

    <?php if (!sb_aktif()): ?>
        <div class="admin-peringatan">
            Supabase belum dikonfigurasi - data masih disimpan di file lokal, sehingga perubahan
            <strong>tidak persisten di Vercel</strong>. Isi <code>SUPABASE_URL</code> &amp;
            <code>SUPABASE_SERVICE_KEY</code>, lalu jalankan <code>supabase/schema.sql</code>.
        </div>
    <?php elseif (sb_key_role() !== 'service_role'): ?>
        <div class="admin-peringatan">
            Kunci Supabase tidak terdeteksi sebagai <strong>service_role</strong>
            (terbaca: <code><?= e(sb_key_role() ?: 'tidak dikenali') ?></code>).
            Jika simpan gagal, ganti kunci dan buka <a href="/admin/status.php">Status</a>.
        </div>
    <?php endif; ?>

    <?php if (!$siap_cloud): ?>
        <div class="admin-peringatan">
            Cloudinary belum dikonfigurasi. Set <code>CLOUDINARY_CLOUD_NAME</code> &amp; <code>CLOUDINARY_UPLOAD_PRESET</code>
            di environment variables agar upload gambar aktif. (Produk tetap bisa dikelola, gambar pakai URL manual.)
        </div>
    <?php endif; ?>

    <?php if ($pesan !== ''): ?><div class="admin-sukses"><?= e($pesan) ?></div><?php endif; ?>
    <?php if ($galat !== ''): ?><div class="admin-galat"><?= e($galat) ?></div><?php endif; ?>

    <section class="stat-blok" id="statistik">
        <div class="stat-kepala">
            <div>
                <h2>Statistik Pengunjung</h2>
                <p>
                    Tersimpan di <strong><?= sb_aktif() ? 'Supabase' : 'file lokal (data/kunjungan.json)' ?></strong> dan terus terakumulasi.
                    <span class="stat-legend"><i class="dot-random"></i>Random = datang langsung/eksternal</span>
                    <span class="stat-legend"><i class="dot-klik"></i>Klik = pindah halaman dari dalam situs</span>
                </p>
            </div>
            <span class="stat-periode"><?= (int)$stat['periode'] ?> hari terakhir &middot; <?= (int)$stat['total_unik'] ?> unik &middot; <?= (int)$stat['total_views'] ?> kunjungan</span>
        </div>

        <div class="stat-grid">
            <div class="stat-kartu stat-netral">
                <span class="stat-label">Pengunjung hari ini</span>
                <strong class="stat-nilai"><?= (int)$stat_hari_ini['unik'] ?></strong>
                <span class="stat-sub"><?= (int)$stat_hari_ini['views'] ?> kunjungan halaman</span>
            </div>
            <div class="stat-kartu stat-netral">
                <span class="stat-label">Pengunjung minggu ini</span>
                <strong class="stat-nilai"><?= (int)$stat_minggu_ini['unik'] ?></strong>
                <span class="stat-sub"><?= (int)$stat_minggu_ini['views'] ?> kunjungan halaman</span>
            </div>
            <div class="stat-kartu stat-random">
                <span class="stat-label">Random hari ini</span>
                <strong class="stat-nilai"><?= (int)$stat_hari_ini['random_unik'] ?></strong>
                <span class="stat-sub"><?= (int)$stat_hari_ini['random_views'] ?> kunjungan langsung/eksternal</span>
            </div>
            <div class="stat-kartu stat-klik">
                <span class="stat-label">Halaman diklik hari ini</span>
                <strong class="stat-nilai"><?= (int)$stat_hari_ini['klik_unik'] ?></strong>
                <span class="stat-sub"><?= (int)$stat_hari_ini['klik_views'] ?> kunjungan dari klik menu</span>
            </div>
        </div>

        <div class="stat-dua">
            <div class="stat-kartu stat-random">
                <span class="stat-label">Pengunjung Random</span>
                <strong class="stat-nilai"><?= (int)$stat_minggu_ini['random_unik'] ?></strong>
                <span class="stat-sub">unik minggu ini &middot; <?= (int)$stat_minggu_ini['random_views'] ?> kunjungan</span>
            </div>
            <div class="stat-kartu stat-klik">
                <span class="stat-label">Halaman yang Diklik</span>
                <strong class="stat-nilai"><?= (int)$stat_minggu_ini['klik_unik'] ?></strong>
                <span class="stat-sub">unik minggu ini &middot; <?= (int)$stat_minggu_ini['klik_views'] ?> kunjungan</span>
            </div>
        </div>

        <div class="stat-dua">
            <div class="stat-panel">
                <h3>Harian &mdash; 7 hari terakhir</h3>
                <div class="stat-tabel-bungkus">
                    <table class="admin-tabel stat-tabel">
                        <thead>
                            <tr><th>Tanggal</th><th>Random</th><th>Halaman diklik</th><th>Total</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_reverse($stat_harian) as $h): ?>
                            <tr>
                                <td>
                                    <strong><?= e(statistik_label_tanggal($h['tanggal'])) ?></strong>
                                    <?php if ($h['tanggal'] === date('Y-m-d')): ?><span class="stat-badge">hari ini</span><?php endif; ?>
                                </td>
                                <td><?= (int)$h['random_unik'] ?> unik<span class="admin-sub"><?= (int)$h['random_views'] ?> kunjungan</span></td>
                                <td><?= (int)$h['klik_unik'] ?> unik<span class="admin-sub"><?= (int)$h['klik_views'] ?> kunjungan</span></td>
                                <td><strong><?= (int)$h['unik'] ?></strong> unik<span class="admin-sub"><?= (int)$h['views'] ?> kunjungan</span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="stat-panel">
                <h3>Mingguan &mdash; 8 minggu terakhir</h3>
                <div class="stat-tabel-bungkus">
                    <table class="admin-tabel stat-tabel">
                        <thead>
                            <tr><th>Minggu</th><th>Random</th><th>Halaman diklik</th><th>Total</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_reverse($stat_mingguan) as $m): ?>
                            <tr>
                                <td>
                                    <strong><?= e(statistik_label_minggu($m['mulai'], $m['selesai'])) ?></strong>
                                    <?php if ($m['minggu'] === date('Y-m-d', strtotime('monday this week'))): ?><span class="stat-badge">minggu ini</span><?php endif; ?>
                                </td>
                                <td><?= (int)$m['random_unik'] ?> unik<span class="admin-sub"><?= (int)$m['random_views'] ?> kunjungan</span></td>
                                <td><?= (int)$m['klik_unik'] ?> unik<span class="admin-sub"><?= (int)$m['klik_views'] ?> kunjungan</span></td>
                                <td><strong><?= (int)$m['unik'] ?></strong> unik<span class="admin-sub"><?= (int)$m['views'] ?> kunjungan</span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="stat-dua">
            <div class="stat-panel">
                <h3>Halaman paling sering diklik</h3>
                <div class="stat-tabel-bungkus">
                    <table class="admin-tabel stat-tabel">
                        <thead><tr><th>Halaman</th><th class="admin-kanan">Kunjungan</th><th class="admin-kanan">Unik</th></tr></thead>
                        <tbody>
                            <?php if (!$stat_halaman_klik): ?>
                                <tr><td colspan="3" class="admin-kosong">Belum ada klik halaman.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($stat_halaman_klik as $hp): ?>
                            <tr>
                                <td><strong><?= e(statistik_nama_halaman($hp['halaman'])) ?></strong><span class="admin-sub"><?= e($hp['halaman']) ?></span></td>
                                <td class="admin-kanan"><?= (int)$hp['views'] ?></td>
                                <td class="admin-kanan"><?= (int)$hp['unik'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="stat-panel">
                <h3>Halaman masuk pengunjung random</h3>
                <div class="stat-tabel-bungkus">
                    <table class="admin-tabel stat-tabel">
                        <thead><tr><th>Halaman</th><th class="admin-kanan">Kunjungan</th><th class="admin-kanan">Unik</th></tr></thead>
                        <tbody>
                            <?php if (!$stat_halaman_rand): ?>
                                <tr><td colspan="3" class="admin-kosong">Belum ada kunjungan random.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($stat_halaman_rand as $hp): ?>
                            <tr>
                                <td><strong><?= e(statistik_nama_halaman($hp['halaman'])) ?></strong><span class="admin-sub"><?= e($hp['halaman']) ?></span></td>
                                <td class="admin-kanan"><?= (int)$hp['views'] ?></td>
                                <td class="admin-kanan"><?= (int)$hp['unik'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <div class="admin-tabel-bungkus">
        <table class="admin-tabel">
            <thead>
                <tr>
                    <th>Gambar</th>
                    <th>Produk</th>
                    <th>Kategori</th>
                    <th>Harga</th>
                    <th>Suka</th>
                    <th>Checkout</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$daftar): ?>
                    <tr><td colspan="8" class="admin-kosong">Belum ada produk.</td></tr>
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
                    <td class="admin-kanan"><?= (int)checkout_jumlah($p['id']) ?><span class="admin-sub"><?= in_array($p['id'], $terlaris_ids, true) ? ' · terlaris' : '' ?></span></td>
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
                            <button type="submit" class="admin-pill <?= $p['terlaris'] ? 'on' : '' ?>" title="Pin manual agar selalu masuk daftar Terlaris">Pin Terlaris</button>
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
