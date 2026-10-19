<?php
/**
 * Kotak masuk pesan/chat konsumen dari kolom chat beranda.
 * Aksi: tandai dibaca/belum, tandai semua dibaca, hapus, balas via WA/email.
 */
require_once __DIR__ . '/../includes/auth.php';
admin_wajib_login();

$pesan = '';
$galat = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_cek()) {
        $galat = 'Token keamanan tidak valid.';
    } else {
        $aksi = (string)($_POST['aksi'] ?? '');
        $id   = (int)($_POST['id'] ?? 0);

        if ($aksi === 'hapus' && $id > 0) {
            pesan_hapus($id) ? $pesan = 'Pesan dihapus.' : $galat = 'Gagal menghapus pesan.';
        } elseif ($aksi === 'baca' && $id > 0) {
            pesan_set_dibaca($id, true) ? $pesan = 'Ditandai sudah dibaca.' : $galat = 'Gagal memperbarui.';
        } elseif ($aksi === 'belum' && $id > 0) {
            pesan_set_dibaca($id, false) ? $pesan = 'Ditandai belum dibaca.' : $galat = 'Gagal memperbarui.';
        } elseif ($aksi === 'baca_semua') {
            foreach (pesan_semua() as $m) pesan_set_dibaca((int)($m['id'] ?? 0), true);
            $pesan = 'Semua pesan ditandai sudah dibaca.';
        }
    }
}

$lihat        = ($_GET['lihat'] ?? 'semua') === 'belum' ? 'belum' : 'semua';
$jumlah_belum = pesan_belum_dibaca();

$daftar = pesan_semua();
usort($daftar, fn($a, $b) => ((int)($b['waktu'] ?? 0)) <=> ((int)($a['waktu'] ?? 0)));
if ($lihat === 'belum') {
    $daftar = array_values(array_filter($daftar, fn($m) => empty($m['dibaca'])));
}

$judul_halaman = 'Admin — Pesan Masuk';
require __DIR__ . '/../includes/admin-head.php';
?>
<div class="admin-wrap">
    <div class="admin-topbar">
        <div class="admin-judul">
            <h1>Pesan Masuk</h1>
            <p><?= count($daftar) ?> pesan ditampilkan &middot; <?= $jumlah_belum ?> belum dibaca</p>
        </div>
        <div class="admin-aksi">
            <?php if ($jumlah_belum > 0): ?>
            <form method="post">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="aksi" value="baca_semua">
                <button type="submit" class="tombol abu kecil">Tandai semua dibaca</button>
            </form>
            <?php endif; ?>
            <a class="tombol abu kecil" href="/" target="_blank">Lihat Situs ↗</a>
        </div>
    </div>

    <?php if ($pesan !== ''): ?><div class="admin-sukses"><?= e($pesan) ?></div><?php endif; ?>
    <?php if ($galat !== ''): ?><div class="admin-galat"><?= e($galat) ?></div><?php endif; ?>

    <div class="pesan-filter">
        <a class="admin-pill <?= $lihat === 'semua' ? 'on' : '' ?>" href="/admin/pesan.php">Semua</a>
        <a class="admin-pill <?= $lihat === 'belum' ? 'on' : '' ?>" href="/admin/pesan.php?lihat=belum">Belum dibaca<?= $jumlah_belum > 0 ? ' (' . $jumlah_belum . ')' : '' ?></a>
    </div>

    <?php if (!$daftar): ?>
        <div class="admin-tabel-bungkus"><p class="admin-kosong">Belum ada pesan.</p></div>
    <?php else: ?>
        <div class="pesan-daftar">
            <?php foreach ($daftar as $m):
                $mid   = (int)($m['id'] ?? 0);
                $nama  = (string)($m['nama'] ?? '');
                $hp    = (string)($m['hp'] ?? '');
                $email = (string)($m['email'] ?? '');
                $isi   = (string)($m['pesan'] ?? '');
                $waktu = (int)($m['waktu'] ?? 0);
                $baru  = empty($m['dibaca']);

                $wa = $hp !== '' ? kontak_wa_link($hp, 'Halo ' . $nama . ', terima kasih sudah menghubungi ' . NAMA_TOKO . '. ') : '';
                $subjek = 'Balasan ' . NAMA_TOKO;
                $body   = "Halo " . $nama . ",\n\nTerima kasih sudah menghubungi " . NAMA_TOKO . ".\n\n---\nPesanmu:\n" . $isi;
                $mail   = $email !== '' ? 'mailto:' . rawurlencode($email) . '?subject=' . rawurlencode($subjek) . '&body=' . rawurlencode($body) : '';
            ?>
            <article class="pesan-kartu<?= $baru ? ' baru' : '' ?>">
                <div class="pesan-kepala">
                    <div>
                        <span class="pesan-nama"><?= e($nama !== '' ? $nama : 'Tanpa nama') ?></span>
                        <?php if ($baru): ?><span class="pesan-baru-tag">Baru</span><?php endif; ?>
                        <div class="pesan-kontak">
                            <?php if ($hp !== ''): ?><a href="tel:<?= e($hp) ?>">📱 <?= e($hp) ?></a><?php endif; ?>
                            <?php if ($email !== ''): ?><a href="mailto:<?= e($email) ?>">✉️ <?= e($email) ?></a><?php endif; ?>
                            <?php if ($hp === '' && $email === ''): ?><span class="admin-sub">Tanpa kontak</span><?php endif; ?>
                        </div>
                    </div>
                    <span class="pesan-waktu"><?= e(waktu_ringkas($waktu)) ?> &middot; #<?= $mid ?></span>
                </div>

                <p class="pesan-isi"><?= e($isi) ?></p>

                <div class="pesan-aksi">
                    <?php if ($wa !== ''): ?>
                        <a class="tombol kecil" href="<?= e($wa) ?>" target="_blank" rel="noopener">Balas via WhatsApp</a>
                    <?php endif; ?>
                    <?php if ($mail !== ''): ?>
                        <a class="tombol abu kecil" href="<?= e($mail) ?>">Balas via Email</a>
                    <?php endif; ?>

                    <form method="post">
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="id" value="<?= $mid ?>">
                        <input type="hidden" name="aksi" value="<?= $baru ? 'baca' : 'belum' ?>">
                        <button type="submit" class="tombol abu kecil"><?= $baru ? 'Tandai dibaca' : 'Tandai belum dibaca' ?></button>
                    </form>

                    <form method="post" onsubmit="return confirm('Hapus pesan ini?')">
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="id" value="<?= $mid ?>">
                        <input type="hidden" name="aksi" value="hapus">
                        <button type="submit" class="tombol bahaya kecil">Hapus</button>
                    </form>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/admin-foot.php'; ?>
