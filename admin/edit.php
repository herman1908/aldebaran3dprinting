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

        $kunci_warna = array_values(array_unique(array_filter((array)($_POST['warna_pilih'] ?? []), 'is_string')));
        $pilihan_warna = [];
        foreach (produk_palet_warna() as $kunci => $w) {
            if (in_array($kunci, $kunci_warna, true)) $pilihan_warna[] = $w;
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
        } elseif (!$pilihan_warna) {
            $galat = 'Pilih minimal satu warna untuk produk ini.';
        } else {
            $baru_id = produk_simpan($data);
            if ($baru_id > 0) {
                // Simpan harga per huruf bila produk ini memakai opsi huruf.
                $huruf_ok = true;
                if (in_array($baru_id, PRODUK_HURUF_IDS, true) && isset($_POST['huruf_awal'], $_POST['huruf_tambah'])) {
                    $huruf_ok = produk_huruf_simpan($baru_id, (int)$_POST['huruf_awal'], (int)$_POST['huruf_tambah']);
                }
                if ($huruf_ok) {
                    header('Location: /admin/?pesan=disimpan&id=' . $baru_id);
                    exit;
                }
                $galat = 'Produk tersimpan, tetapi harga per huruf gagal disimpan. Coba simpan ulang.';
            } else {
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

// Harga per huruf hanya untuk produk tertentu (mis. #5 & #7).
$huruf_admin_aktif = (int)$f['id'] > 0 && in_array((int)$f['id'], PRODUK_HURUF_IDS, true);
$huruf_cfg_admin   = $huruf_admin_aktif
    ? produk_huruf_config($f)
    : ['aktif' => false, 'awal' => PRODUK_HURUF_AWAL_DEFAULT, 'tambah' => PRODUK_HURUF_TAMBAH_DEFAULT, 'maks' => PRODUK_HURUF_MAKS];
if ($huruf_admin_aktif && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['huruf_awal']))   $huruf_cfg_admin['awal']   = max(0, (int)$_POST['huruf_awal']);
    if (isset($_POST['huruf_tambah'])) $huruf_cfg_admin['tambah'] = max(0, (int)$_POST['huruf_tambah']);
}

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
                        <label class="admin-label">Harga (Rp)<?php if ($huruf_admin_aktif): ?><span class="admin-label-note">mengikuti harga per huruf</span><?php endif; ?></label>
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

                <?php if ($huruf_admin_aktif): ?>
                <div class="admin-huruf" id="hurufAdmin">
                    <label class="admin-label">Harga per Huruf <span class="admin-label-note">khusus produk ini</span></label>
                    <div class="admin-row2">
                        <div>
                            <label class="admin-label">Harga 1 Huruf (Rp)</label>
                            <input class="admin-input" type="number" min="0" step="500" name="huruf_awal" id="hurufAwal" value="<?= (int)$huruf_cfg_admin['awal'] ?>">
                        </div>
                        <div>
                            <label class="admin-label">Tambahan per Huruf Berikutnya (Rp)</label>
                            <input class="admin-input" type="number" min="0" step="500" name="huruf_tambah" id="hurufTambah" value="<?= (int)$huruf_cfg_admin['tambah'] ?>">
                        </div>
                    </div>
                    <span class="admin-sub">Harga produk di halaman publik dihitung otomatis: harga 1 huruf + tambahan × (jumlah huruf − 1).</span>
                    <p class="admin-label">Pratinjau harga 1–<?= (int)$huruf_cfg_admin['maks'] ?> huruf</p>
                    <div class="admin-huruf-pratinjau" id="hurufPratinjau">
                        <?php for ($i = 1; $i <= (int)$huruf_cfg_admin['maks']; $i++):
                            $harga_h = (int)$huruf_cfg_admin['awal'] + (($i - 1) * (int)$huruf_cfg_admin['tambah']);
                        ?>
                        <span class="admin-huruf-chip" data-huruf="<?= $i ?>">
                            <b><?= $i ?> Huruf</b>
                            <span><?= e(rupiah($harga_h)) ?></span>
                        </span>
                        <?php endfor; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php $palet_admin = produk_palet_warna(); ?>
                <label class="admin-label">Pilihan Warna di Detail Produk <span class="admin-label-note"><?= count($palet_admin) ?> warna tetap</span></label>
                <div class="warna-palet">
                    <?php
                    $hex_dipilih = array_map(
                        fn($w) => strtoupper(ltrim((string)($w['hex'] ?? ''), '#')),
                        $f['pilihan_warna']
                    );
                    foreach ($palet_admin as $kunci => $w):
                        $aktif = in_array(strtoupper(ltrim($w['hex'], '#')), $hex_dipilih, true);
                    ?>
                    <label class="warna-palet-item<?= $aktif ? ' aktif' : '' ?>">
                        <input type="checkbox" name="warna_pilih[]" value="<?= e($kunci) ?>" <?= $aktif ? 'checked' : '' ?>>
                        <span class="warna-palet-dot" style="background:<?= e($w['hex']) ?>"></span>
                        <span class="warna-palet-teks">
                            <span class="warna-palet-nama"><?= e($w['nama']) ?></span>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
                <span class="admin-sub">Pilih warna yang tampil di halaman detail produk. Warna aktif pertama (urutan palet) jadi default. Minimal satu warna.</span>

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
