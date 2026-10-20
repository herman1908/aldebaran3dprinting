<?php
/**
 * Form tambah / edit produk. Upload gambar ke Cloudinary (unsigned preset).
 */
require_once __DIR__ . '/../includes/auth.php';
admin_wajib_login();

$id   = (int)($_GET['id'] ?? 0);
$edit = $id > 0 ? produk_cari($id) : null;
if ($id > 0 && !$edit) {
    header('Location: /admin/');
    exit;
}
$edit = $edit ? produk_normalisasi($edit) : null;

$galat = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_cek()) {
        $galat = 'Token keamanan tidak valid. Muat ulang halaman lalu coba lagi.';
    } else {
        $galeri_urls = array_values(array_unique(array_filter(array_map('trim', explode("\n", (string)($_POST['galeri_urls'] ?? ''))))));

        $pilihan_warna = [];
        $nama_warna    = (array)($_POST['warna_nama'] ?? []);
        $hex_warna     = (array)($_POST['warna_hex'] ?? []);
        foreach ($nama_warna as $i => $nm) {
            $pilihan_warna[] = [
                'nama' => (string)$nm,
                'hex'  => (string)($hex_warna[$i] ?? ''),
            ];
        }

        $data = [
            'id'        => (int)($_POST['id'] ?? 0),
            'nama'      => (string)($_POST['nama'] ?? ''),
            'penjual'   => (string)($_POST['penjual'] ?? 'Aldebaran Studio'),
            'kategori'  => (array)($_POST['kategori'] ?? []),
            'warna'     => (string)($_POST['warna'] ?? 'koral'),
            'harga'     => (int)($_POST['harga'] ?? 0),
            'unduhan'   => (int)($_POST['unduhan'] ?? 0),
            'suka'      => (int)($_POST['suka'] ?? 0),
            'gambar'    => $galeri_urls[0] ?? '',
            'galeri'    => $galeri_urls,
            'deskripsi' => (string)($_POST['deskripsi'] ?? ''),
            'pilihan_warna' => $pilihan_warna,
            'terlaris'  => isset($_POST['terlaris']),
            'unggulan'  => isset($_POST['unggulan']),
        ];

        if ($data['nama'] === '') {
            $galat = 'Nama produk wajib diisi.';
        } elseif (count($galeri_urls) > 5) {
            $galat = 'Maksimal 5 foto per produk. Hapus sebagian foto lalu simpan lagi.';
        } else {
            $baru_id = produk_simpan($data);
            if ($baru_id > 0) {
                header('Location: /admin/?pesan=disimpan&id=' . $baru_id);
                exit;
            }
            $galat = 'Gagal menyimpan produk.';
            if (!sb_aktif()) {
                $galat .= ' Supabase belum terbaca di server ini (SUPABASE_URL/SUPABASE_SERVICE_KEY). Di Vercel, isi env lalu redeploy.';
            } elseif (sb_error_terakhir() !== '') {
                $galat .= ' Detail: ' . sb_error_terakhir();
                if (stripos(sb_error_terakhir(), 'pilihan_warna') !== false) {
                    $galat .= ' Jalankan migrasi kolom pilihan_warna di Supabase (lihat supabase/schema.sql).';
                }
            }
            $galat .= ' Buka menu Status untuk diagnostik.';
        }
    }
}

// Pilihan saat ini untuk form (baik POST gagal maupun GET awal).
$f = $edit ?: [
    'id' => 0, 'nama' => '', 'penjual' => 'Aldebaran Studio', 'kategori' => [],
    'warna' => 'koral', 'harga' => 0, 'unduhan' => 0, 'suka' => 0,
    'gambar' => '', 'galeri' => [], 'deskripsi' => '', 'terlaris' => false, 'unggulan' => false,
    'pilihan_warna' => produk_warna_default(),
];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $galeri_urls    = array_values(array_unique(array_filter(array_map('trim', explode("\n", (string)($_POST['galeri_urls'] ?? ''))))));
    $f['nama']      = (string)($_POST['nama'] ?? '');
    $f['penjual']   = (string)($_POST['penjual'] ?? '');
    $f['kategori']  = (array)($_POST['kategori'] ?? []);
    $f['warna']     = (string)($_POST['warna'] ?? 'koral');
    $f['harga']     = (int)($_POST['harga'] ?? 0);
    $f['unduhan']   = (int)($_POST['unduhan'] ?? 0);
    $f['suka']      = (int)($_POST['suka'] ?? 0);
    $f['gambar']    = $galeri_urls[0] ?? '';
    $f['galeri']    = $galeri_urls;
    $f['deskripsi'] = (string)($_POST['deskripsi'] ?? '');
    $f['pilihan_warna'] = $pilihan_warna ?? produk_warna_default();
    $f['terlaris']  = isset($_POST['terlaris']);
    $f['unggulan']  = isset($_POST['unggulan']);
}

$foto = $f['galeri'] ?: ($f['gambar'] !== '' ? [$f['gambar']] : []);

$judul_halaman = $edit ? 'Edit Produk' : 'Tambah Produk';
require __DIR__ . '/../includes/admin-head.php';
?>
<script src="/assets/js/admin.js" defer></script>

<div class="admin-wrap admin-form-wrap">
    <div class="admin-topbar">
        <div class="admin-judul">
            <h1><?= $edit ? 'Edit Produk' : 'Tambah Produk' ?></h1>
            <p><?= $edit ? 'ID #' . $f['id'] : 'Produk baru' ?></p>
        </div>
        <a class="tombol abu kecil" href="/admin/">← Kembali</a>
    </div>

    <?php if ($galat !== ''): ?><div class="admin-galat"><?= e($galat) ?></div><?php endif; ?>

    <form method="post" class="admin-form" id="formProduk" autocomplete="off">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">

        <div class="admin-grid">
            <div class="admin-kolom">
                <label class="admin-label">Nama Produk *</label>
                <input class="admin-input" type="text" name="nama" required value="<?= e($f['nama']) ?>" placeholder="cth. Fidget Clicker Awan">

                <label class="admin-label">Penjual</label>
                <input class="admin-input" type="text" name="penjual" value="<?= e($f['penjual']) ?>" placeholder="Aldebaran Studio">

                <label class="admin-label">Deskripsi</label>
                <textarea class="admin-textarea" name="deskripsi" rows="5" placeholder="Jelaskan produk..."><?= e($f['deskripsi']) ?></textarea>

                <div class="admin-row2">
                    <div>
                        <label class="admin-label">Harga (Rp)</label>
                        <input class="admin-input" type="number" name="harga" min="0" value="<?= (int)$f['harga'] ?>">
                    </div>
                    <div>
                        <label class="admin-label">Warna Pastel</label>
                        <select class="admin-input" name="warna">
                            <?php foreach (['koral' => 'Koral (pink)', 'matahari' => 'Matahari (kuning)', 'ungu' => 'Ungu (lilac)'] as $k => $lbl): ?>
                                <option value="<?= $k ?>" <?= $f['warna'] === $k ? 'selected' : '' ?>><?= $lbl ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <label class="admin-label">Pilihan Warna di Detail Produk <span class="admin-label-note">maks. 12</span></label>
                <div class="warna-editor" id="warnaEditor">
                    <?php foreach ($f['pilihan_warna'] as $w): ?>
                    <div class="warna-baris">
                        <input type="color" class="warna-hex" name="warna_hex[]" value="<?= e($w['hex']) ?>" aria-label="Kode warna">
                        <input type="text" class="admin-input warna-nama" name="warna_nama[]" maxlength="30" value="<?= e($w['nama']) ?>" placeholder="Nama warna">
                        <button type="button" class="warna-hapus" aria-label="Hapus warna">✕</button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="tombol abu kecil" id="tambahWarna">+ Tambah warna</button>
                <span class="admin-sub">Warna ini muncul sebagai pilihan di halaman detail produk. Kosongkan semua untuk memakai palet default.</span>

                <div class="admin-row2">
                    <div>
                        <label class="admin-label">Suka</label>
                        <input class="admin-input" type="number" name="suka" min="0" value="<?= (int)$f['suka'] ?>">
                    </div>
                    <div>
                        <label class="admin-label">Terjual / Unduhan</label>
                        <input class="admin-input" type="number" name="unduhan" min="0" value="<?= (int)$f['unduhan'] ?>">
                    </div>
                </div>

                <label class="admin-label">Kategori Bentuk</label>
                <div class="admin-cek-grid">
                    <?php foreach ($kategori as $kunci => $label):
                        if (in_array($kunci, ['semua', 'terlaris'], true)) continue; ?>
                        <label class="admin-cek">
                            <input type="checkbox" name="kategori[]" value="<?= e($kunci) ?>" <?= in_array($kunci, $f['kategori'], true) ? 'checked' : '' ?>>
                            <?= e($label) ?>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="admin-cek-inline">
                    <label class="admin-cek"><input type="checkbox" name="terlaris" <?= !empty($f['terlaris']) ? 'checked' : '' ?>> Pin Terlaris (manual)</label>
                    <label class="admin-cek"><input type="checkbox" name="unggulan" <?= !empty($f['unggulan']) ? 'checked' : '' ?>> Unggulan (hero carousel)</label>
                </div>
                <span class="admin-sub">Tanpa pin manual, produk otomatis masuk "Terlaris" dari jumlah klik checkout terbanyak (top 8).</span>
            </div>

            <div class="admin-kolom">
                <label class="admin-label">Foto Produk <span class="admin-label-note">maks. 5 &middot; foto pertama jadi foto utama</span></label>
                <div class="admin-upload" id="uploadArea">
                    <input type="file" id="fileGambar" accept="image/*" class="admin-file" multiple>
                    <div class="admin-upload-inner" id="uploadInner">
                        <span class="admin-upload-ikon">⬆</span>
                        <strong>Klik atau seret foto ke sini</strong>
                        <span>Bisa pilih beberapa foto sekaligus (maks. 5)</span>
                    </div>
                    <div class="admin-progress" id="uploadProgress" hidden><div></div></div>
                </div>
                <?php if (!cloudinary_siap()): ?>
                    <div class="admin-peringatan kecil">Cloudinary belum dikonfigurasi di <code>.env</code> — isi <code>CLOUDINARY_CLOUD_NAME</code> &amp; <code>CLOUDINARY_UPLOAD_PRESET</code>, atau isi URL foto secara manual di bawah.</div>
                <?php endif; ?>

                <label class="admin-label">URL Foto (opsional, satu per baris)</label>
                <textarea class="admin-textarea" name="galeri_urls" id="galeriUrls" rows="3" placeholder="https://res.cloudinary.com/... atau nama-file.jpg"><?= e(implode("\n", $foto)) ?></textarea>
                <p class="admin-foto-hint" id="fotoHint" aria-live="polite"></p>
                <div class="admin-foto-grid" id="fotoGrid" aria-live="polite"></div>

                <button type="submit" class="tombol besar admin-simpan"><?= $edit ? 'Simpan Perubahan' : 'Simpan Produk' ?></button>
            </div>
        </div>
    </form>
</div>

<?php
// Konfigurasi Cloudinary untuk JS.
$cloud_cfg = [
    'uploadUrl' => cloudinary_upload_url(),
    'cloudName' => cloudinary_cloud_name(),
    'preset'    => cloudinary_upload_preset(),
    'folder'    => cloudinary_folder(),
    'siap'      => cloudinary_siap(),
];
?>
<script>window.CLOUDINARY = <?= json_encode($cloud_cfg, JSON_UNESCAPED_SLASHES) ?>;</script>
<?php require __DIR__ . '/../includes/admin-foot.php'; ?>
