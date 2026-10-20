<?php
/**
 * Ganti password admin. Password disimpan sebagai hash di Supabase
 * (tabel admin_akun), sehingga bisa diubah dari panel online dan persisten.
 */
require_once __DIR__ . '/../includes/auth.php';
admin_wajib_login();

$pesan = '';
$galat = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_cek()) {
        $galat = 'Token keamanan tidak valid. Muat ulang halaman lalu coba lagi.';
    } else {
        $lama  = (string)($_POST['lama'] ?? '');
        $baru  = (string)($_POST['baru'] ?? '');
        $ulang = (string)($_POST['ulang'] ?? '');

        if ($lama === '') {
            $galat = 'Password saat ini wajib diisi.';
        } elseif (!admin_login($lama)) {
            $galat = 'Password saat ini salah.';
        } elseif (strlen($baru) < 8) {
            $galat = 'Password baru minimal 8 karakter.';
        } elseif ($baru !== $ulang) {
            $galat = 'Konfirmasi password baru tidak sama.';
        } elseif (!sb_aktif()) {
            $galat = 'Supabase belum dikonfigurasi. Isi SUPABASE_URL & SUPABASE_SERVICE_KEY terlebih dahulu.';
        } elseif (admin_password_simpan($baru)) {
            admin_set_cookie();
            $pesan = 'Password berhasil disimpan ke database.';
        } else {
            $galat = 'Gagal menyimpan password. Pastikan tabel admin_akun sudah dibuat (jalankan supabase/schema.sql).';
        }
    }
}

$sumber = admin_password_sumber();

$judul_halaman = 'Admin — Ganti Password';
require __DIR__ . '/../includes/admin-head.php';
?>
<div class="admin-wrap">
    <div class="admin-topbar">
        <div class="admin-judul">
            <h1>Ganti Password</h1>
            <p>Password disimpan sebagai hash di database, tidak pernah dalam teks biasa.</p>
        </div>
        <div class="admin-aksi">
            <a class="tombol abu kecil" href="/admin/">← Kembali</a>
        </div>
    </div>

    <?php if (!$sumber): ?>
        <div class="admin-peringatan">Password admin belum diatur. Isi <code>ADMIN_PASSWORD</code> di environment variables untuk login pertama.</div>
    <?php elseif ($sumber === 'env'): ?>
        <div class="admin-peringatan">Password masih memakai <code>ADMIN_PASSWORD</code> dari environment. Setelah disimpan di halaman ini, password pindah ke database dan env tidak dipakai lagi.</div>
    <?php else: ?>
        <div class="admin-sukses">Sumber password: database Supabase.</div>
    <?php endif; ?>

    <?php if (!sb_aktif()): ?>
        <div class="admin-peringatan">
            Supabase belum aktif. Isi <code>SUPABASE_URL</code> dan <code>SUPABASE_SERVICE_KEY</code>
            di environment variables, lalu jalankan <code>supabase/schema.sql</code>.
        </div>
    <?php endif; ?>

    <?php if ($pesan !== ''): ?><div class="admin-sukses"><?= e($pesan) ?></div><?php endif; ?>
    <?php if ($galat !== ''): ?><div class="admin-galat"><?= e($galat) ?></div><?php endif; ?>

    <form method="post" class="admin-form admin-sosmed-form" autocomplete="off">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

        <label class="admin-label">Password saat ini</label>
        <input class="admin-input" type="password" name="lama" required autocomplete="current-password" placeholder="••••••••">

        <div class="admin-row2">
            <div>
                <label class="admin-label">Password baru</label>
                <input class="admin-input" type="password" name="baru" required minlength="8" autocomplete="new-password" placeholder="minimal 8 karakter">
            </div>
            <div>
                <label class="admin-label">Ulangi password baru</label>
                <input class="admin-input" type="password" name="ulang" required minlength="8" autocomplete="new-password" placeholder="ulangi password baru">
            </div>
        </div>

        <div class="admin-sosmed-aksi">
            <button type="submit" class="tombol besar" <?= sb_aktif() ? '' : 'disabled' ?>>Simpan Password</button>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../includes/admin-foot.php'; ?>
