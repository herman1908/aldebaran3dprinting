<?php
/**
 * Diagnostik koneksi Supabase: env terbaca, kunci benar, tabel ada,
 * dan uji tulis. Membantu mencari penyebab "Gagal menyimpan".
 */
require_once __DIR__ . '/../includes/auth.php';
admin_wajib_login();

$pesan = '';
$galat = '';
$uji_tulis = null;

$aksi_uji = $_SERVER['REQUEST_METHOD'] === 'POST' ? (string)($_POST['aksi'] ?? '') : '';

if ($aksi_uji === 'uji_tulis' || $aksi_uji === 'uji_tulis_produk') {
    if (!csrf_cek()) {
        $galat = 'Token keamanan tidak valid.';
    } elseif (!sb_aktif()) {
        $galat = 'Supabase belum aktif, tidak bisa uji tulis.';
    } elseif ($aksi_uji === 'uji_tulis') {
        $nilai = date('c');
        $ok    = sb_pengaturan_set('tes_tulis', $nilai);
        if ($ok) {
            sb_delete('pengaturan', 'kunci=eq.tes_tulis');
            $pesan = 'Uji tulis berhasil — database bisa menerima perubahan.';
            $uji_tulis = true;
        } else {
            $galat = 'Uji tulis gagal: ' . (sb_error_terakhir() ?: 'tidak diketahui');
            $uji_tulis = false;
        }
    } else {
        $r = sb_post('produk', [
            'nama'      => '__tes_status__',
            'harga'     => 1,
            'kategori'  => [],
            'warna'     => 'koral',
            'galeri'    => [],
            'deskripsi' => 'baris uji status, dihapus otomatis',
            'terlaris'  => false,
            'unggulan'  => false,
        ]);
        if (!$r['ok']) {
            $galat = 'Uji tulis produk gagal: ' . (sb_error_terakhir() ?: 'tidak diketahui');
        } else {
            $hapus = sb_delete('produk', 'nama=eq.' . rawurlencode('__tes_status__'));
            if ($hapus['ok']) {
                $pesan = 'Uji tulis produk berhasil — kolom produk cocok dan baris uji sudah dihapus.';
            } else {
                $galat = 'Produk uji tersimpan tetapi gagal dihapus. Hapus baris bernama __tes_status__ di Supabase.';
            }
        }
    }
}

$aktif = sb_aktif();
$role  = sb_key_role();

$tabel_uji = [
    'produk'      => 'select=*&limit=1',
    'sosmed'      => 'select=*&limit=1',
    'pesan'       => 'select=*&limit=1',
    'checkout_stat' => 'select=*&limit=1',
    'pengaturan'  => 'select=kunci&limit=1',
    'admin_akun'  => 'select=id&limit=1',
];

$hasil_tabel = [];
if ($aktif) {
    foreach ($tabel_uji as $tabel => $query) {
        $r = sb_request('GET', $tabel, null, $query);
        $hasil_tabel[$tabel] = [
            'ok'     => $r['ok'],
            'status' => $r['status'],
            'error'  => $r['error'],
            'baris'  => is_array($r['data']) ? count($r['data']) : 0,
        ];
    }
}

$data_bisa_tulis = is_writable(dirname(__DIR__) . '/data');
$secret_terisi   = (string)(getenv('APP_SECRET') ?: '') !== '';
$url_terisi      = sb_url() !== '';
$key_terisi      = sb_key() !== '';

$petunjuk = [];
if (!$aktif) {
    $petunjuk[] = 'SUPABASE_URL dan/atau SUPABASE_SERVICE_KEY belum terbaca di server ini. Di Vercel: isi di Project > Settings > Environment Variables, lalu lakukan REDEPLOY (env baru belum berlaku di deployment lama).';
}
if ($aktif && $role === 'anon') {
    $petunjuk[] = 'Kunci yang dipakai adalah anon/publishable. Ganti dengan service_role key (Settings > API) agar bisa menulis.';
} elseif ($aktif && $role === '') {
    $petunjuk[] = 'Bentuk kunci tidak dikenali. Pastikan memakai service_role key, bukan password database.';
}
if ($aktif && !$url_terisi) {
    $petunjuk[] = 'SUPABASE_URL kosong. Contoh yang benar: https://xxxx.supabase.co';
}
foreach ($hasil_tabel as $tabel => $r) {
    if (!$r['ok']) {
        $petunjuk[] = 'Tabel "' . $tabel . '" belum bisa diakses: ' . $r['error'] . '. Jalankan seluruh isi supabase/schema.sql di SQL Editor Supabase.';
        break;
    }
}

$judul_halaman = 'Admin — Status Database';
require __DIR__ . '/../includes/admin-head.php';
?>
<div class="admin-wrap">
    <div class="admin-topbar">
        <div class="admin-judul">
            <h1>Status Database</h1>
            <p>Diagnostik penyimpanan Supabase &amp; folder lokal.</p>
        </div>
        <div class="admin-aksi">
            <a class="tombol abu kecil" href="/admin/status.php">Uji Ulang</a>
            <a class="tombol abu kecil" href="/admin/">← Kembali</a>
        </div>
    </div>

    <?php if ($pesan !== ''): ?><div class="admin-sukses"><?= e($pesan) ?></div><?php endif; ?>
    <?php if ($galat !== ''): ?><div class="admin-galat"><?= e($galat) ?></div><?php endif; ?>

    <?php if ($aktif && !$petunjuk): ?>
        <div class="admin-sukses">Supabase aktif dan tabel utama bisa diakses. Penyimpanan online siap dipakai.</div>
    <?php endif; ?>

    <?php foreach ($petunjuk as $p): ?>
        <div class="admin-peringatan"><?= e($p) ?></div>
    <?php endforeach; ?>

    <div class="admin-tabel-bungkus">
        <table class="admin-tabel">
            <thead>
                <tr><th>Pemeriksaan</th><th>Hasil</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td>Mode penyimpanan</td>
                    <td><strong><?= $aktif ? 'Supabase (database)' : 'File lokal (fallback)' ?></strong></td>
                </tr>
                <tr>
                    <td><code>SUPABASE_URL</code></td>
                    <td><?= $url_terisi ? e(sb_url()) : '<span class="admin-sub">kosong</span>' ?></td>
                </tr>
                <tr>
                    <td><code>SUPABASE_SERVICE_KEY</code></td>
                    <td>
                        <?php if (!$key_terisi): ?>
                            <span class="admin-sub">kosong</span>
                        <?php elseif ($role === 'service_role'): ?>
                            terisi &middot; role <strong>service_role</strong> (benar)
                        <?php elseif ($role === 'anon'): ?>
                            terisi &middot; role <strong>anon</strong> (salah, harus service_role)
                        <?php else: ?>
                            terisi &middot; role tidak dikenali
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><code>APP_SECRET</code></td>
                    <td><?= $secret_terisi ? 'terisi' : 'kosong (cookie memakai turunan kunci Supabase)' ?></td>
                </tr>
                <tr>
                    <td>Folder <code>data/</code> (fallback lokal)</td>
                    <td><?= $data_bisa_tulis ? 'bisa ditulis' : 'read-only (normal di Vercel)' ?></td>
                </tr>
                <tr>
                    <td>Sumber password admin</td>
                    <td><?= admin_password_sumber() === 'database' ? 'database Supabase' : (admin_password_sumber() === 'env' ? 'environment (ADMIN_PASSWORD)' : 'belum diatur') ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <?php if ($aktif): ?>
    <h2 style="margin:22px 0 10px;font-size:18px">Uji Tabel</h2>
    <div class="admin-tabel-bungkus">
        <table class="admin-tabel">
            <thead><tr><th>Tabel</th><th>Status</th><th>Keterangan</th></tr></thead>
            <tbody>
                <?php foreach ($tabel_uji as $tabel => $q): $r = $hasil_tabel[$tabel] ?? null; ?>
                <tr>
                    <td><code><?= e($tabel) ?></code></td>
                    <td><?= ($r && $r['ok']) ? '<strong>OK</strong>' : 'GAGAL' ?></td>
                    <td>
                        <?php if ($r && $r['ok']): ?>
                            <span class="admin-sub">HTTP <?= (int)$r['status'] ?><?= $r['baris'] > 0 ? ' &middot; ada data' : ' &middot; kosong' ?></span>
                        <?php else: ?>
                            <span class="admin-sub"><?= e($r['error'] ?? 'tidak diuji') ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:16px">
        <form method="post">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="aksi" value="uji_tulis">
            <button type="submit" class="tombol besar">Uji Tulis Database</button>
        </form>
        <form method="post">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="aksi" value="uji_tulis_produk">
            <button type="submit" class="tombol besar">Uji Tulis Produk</button>
        </form>
    </div>
    <p class="admin-sub" style="margin-top:8px">Uji database menulis lalu menghapus satu baris di tabel <code>pengaturan</code>. Uji produk menambah lalu menghapus satu produk uji; produk asli tidak diubah.</p>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/admin-foot.php'; ?>
