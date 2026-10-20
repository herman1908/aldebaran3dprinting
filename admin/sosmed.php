<?php
/**
 * Kelola ikon media sosial yang tampil di beranda.
 * Aksi: tambah, edit, hapus.
 */
require_once __DIR__ . '/../includes/auth.php';
admin_wajib_login();

$pesan = '';
$galat = '';
$form  = ['id' => 0, 'platform' => 'instagram', 'label' => '', 'url' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_cek()) {
        $galat = 'Token keamanan tidak valid.';
    } else {
        $aksi = (string)($_POST['aksi'] ?? '');
        $id   = (int)($_POST['id'] ?? 0);

        if ($aksi === 'hapus' && $id > 0) {
            sosmed_hapus($id) ? $pesan = 'Media sosial dihapus.' : $galat = 'Gagal menghapus.';
        } elseif ($aksi === 'simpan') {
            $platform = (string)($_POST['platform'] ?? 'website');
            $label    = trim((string)($_POST['label'] ?? ''));
            $url      = sosmed_normal_url($platform, (string)($_POST['url'] ?? ''));
            $form     = ['id' => $id, 'platform' => $platform, 'label' => $label, 'url' => $url];

            if (!isset(sosmed_platform()[$platform])) {
                $galat = 'Platform tidak dikenali.';
            } elseif ($url === '') {
                $galat = 'Alamat / URL wajib diisi.';
            } elseif (!sosmed_url_valid($url)) {
                $galat = 'Alamat / URL tidak valid. Contoh: https://instagram.com/namamu';
            } elseif (sosmed_simpan($form)) {
                header('Location: /admin/sosmed.php?pesan=simpan');
                exit;
            } else {
                $galat = 'Gagal menyimpan media sosial.';
            }
        }
    }
}

if (($_GET['pesan'] ?? '') === 'simpan') $pesan = 'Media sosial berhasil disimpan.';

$edit_id = (int)($_GET['edit'] ?? 0);
$edit    = $edit_id > 0 ? sosmed_cari($edit_id) : null;
if ($edit && $_SERVER['REQUEST_METHOD'] !== 'POST') $form = $edit;

$daftar      = sosmed_semua();
$platforms   = sosmed_platform();
$judul_halaman = 'Admin — Media Sosial';
require __DIR__ . '/../includes/admin-head.php';
?>
<div class="admin-wrap">
    <div class="admin-topbar">
        <div class="admin-judul">
            <h1>Media Sosial</h1>
            <p><?= count($daftar) ?> ikon tampil di beranda</p>
        </div>
        <div class="admin-aksi">
            <a class="tombol abu kecil" href="/" target="_blank">Lihat Situs ↗</a>
        </div>
    </div>

    <?php if ($pesan !== ''): ?><div class="admin-sukses"><?= e($pesan) ?></div><?php endif; ?>
    <?php if ($galat !== ''): ?><div class="admin-galat"><?= e($galat) ?></div><?php endif; ?>

    <form method="post" class="admin-form admin-sosmed-form" autocomplete="off">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="aksi" value="simpan">
        <input type="hidden" name="id" value="<?= (int)$form['id'] ?>">

        <div class="admin-row2">
            <div>
                <label class="admin-label">Platform</label>
                <select class="admin-input" name="platform">
                    <?php foreach ($platforms as $key => $p): ?>
                        <option value="<?= e($key) ?>" <?= $form['platform'] === $key ? 'selected' : '' ?>><?= e($p['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="admin-label">Label (opsional)</label>
                <input class="admin-input" type="text" name="label" maxlength="60" value="<?= e($form['label']) ?>" placeholder="cth. Instagram Toko">
            </div>
        </div>

        <label class="admin-label">Alamat / URL</label>
        <input class="admin-input" type="text" name="url" maxlength="300" value="<?= e($form['url']) ?>" placeholder="https://instagram.com/namamu">
        <span class="admin-sub">Boleh tanpa https:// (otomatis ditambah). Untuk email, cukup tulis alamat emailnya.</span>

        <div class="admin-sosmed-aksi">
            <button type="submit" class="tombol besar"><?= $form['id'] ? 'Simpan Perubahan' : '+ Tambah Media Sosial' ?></button>
            <?php if ($form['id']): ?>
                <a class="tombol abu kecil" href="/admin/sosmed.php">Batal edit</a>
            <?php endif; ?>
        </div>
    </form>

    <div class="admin-tabel-bungkus">
        <table class="admin-tabel">
            <thead>
                <tr>
                    <th>Ikon</th>
                    <th>Platform</th>
                    <th>Label</th>
                    <th>Alamat</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$daftar): ?>
                    <tr><td colspan="5" class="admin-kosong">Belum ada media sosial. Tambahkan di form atas.</td></tr>
                <?php endif; ?>
                <?php foreach ($daftar as $s): ?>
                <tr>
                    <td>
                        <span class="sosmed-link sosmed-<?= e($s['platform']) ?>" style="pointer-events:none"><?= sosmed_ikon($s['platform']) ?></span>
                    </td>
                    <td><?= e($platforms[$s['platform']]['label'] ?? $s['platform']) ?></td>
                    <td><?= e($s['label']) ?></td>
                    <td><a class="admin-sub" href="<?= e($s['url']) ?>" target="_blank" rel="noopener" style="word-break:break-all"><?= e($s['url']) ?></a></td>
                    <td class="admin-kanan admin-baris-aksi">
                        <a class="tombol abu kecil" href="/admin/sosmed.php?edit=<?= (int)$s['id'] ?>">Edit</a>
                        <form method="post" onsubmit="return confirm('Hapus media sosial ini?')">
                            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="aksi" value="hapus">
                            <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
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
