<?php
require_once __DIR__ . '/includes/functions.php';

$ajax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';

// Proses aksi keranjang (POST + token CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_cek()) {
        http_response_code(400);
        if ($ajax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'pesan' => 'Permintaan tidak valid.']);
            exit;
        }
        exit('Permintaan tidak valid.');
    }

    $aksi = $_POST['aksi'] ?? '';
    $id   = (int)($_POST['id'] ?? 0);
    $qty  = max(1, min(99, (int)($_POST['qty'] ?? 1)));

    $k    = keranjang();
    $kode = '';

    if ($aksi === 'tambah' && cari_produk($id)) {
        $k[$id] = min(99, ($k[$id] ?? 0) + $qty);
        $kode = 'tambah';
    } elseif ($aksi === 'ubah' && isset($k[$id])) {
        $k[$id] = $qty;
    } elseif ($aksi === 'hapus') {
        unset($k[$id]);
        $kode = 'hapus';
    } elseif ($aksi === 'kosongkan') {
        $k = [];
    } elseif ($aksi === 'checkout' && $k) {
        // Susun pesan WhatsApp (produk + ongkir + total), lalu kosongkan keranjang.
        $tujuan = trim((string)($_POST['tujuan'] ?? ''));
        $kurir  = trim((string)($_POST['kurir'] ?? ''));
        $ongkir = ($tujuan !== '' && $kurir !== '') ? ongkir_pilihan($tujuan, $kurir) : null;

        checkout_tambah($k);                      // hitung untuk "Terlaris" otomatis
        $teks = pesan_checkout_whatsapp($ongkir); // susun dulu (butuh isi keranjang)
        simpan_keranjang([]);                     // baru kosongkan
        header('Location: ' . wa_link($teks));
        exit;
    }

    simpan_keranjang($k);

    if ($ajax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok'     => $kode !== '',
            'jumlah' => jumlah_keranjang(),
            'pesan'  => $kode === 'tambah' ? 'Produk ditambahkan ke keranjang.' : '',
        ]);
        exit;
    }

    // Pertahankan pilihan ongkir setelah aksi ubah/hapus/tambah.
    $redirect = [];
    if ($kode !== '') $redirect['pesan'] = $kode;
    if (in_array($kode, ['', 'tambah', 'hapus'], true)) {
        $t = trim((string)($_POST['tujuan'] ?? ''));
        $c = trim((string)($_POST['kurir'] ?? ''));
        if ($t !== '') $redirect['tujuan'] = $t;
        if ($c !== '') $redirect['kurir']  = $c;
    }
    header('Location: keranjang.php' . ($redirect ? '?' . http_build_query($redirect) : ''));
    exit;
}

// Pesan singkat (kode tetap, bukan teks bebas dari URL)
$daftar_pesan = [
    'tambah' => 'Produk ditambahkan ke keranjang.',
    'hapus'  => 'Produk dihapus dari keranjang.',
];
$pesan = $daftar_pesan[$_GET['pesan'] ?? ''] ?? '';

// Pilihan tujuan & kurir (dipakai juga untuk total belanja).
$tujuan_kode   = trim((string)($_GET['tujuan'] ?? ''));
$kurir_dipilih = trim((string)($_GET['kurir'] ?? ''));

$judul_halaman = 'Keranjang Belanja';
$menu_aktif = 'koleksi';
require __DIR__ . '/includes/header.php';
?>

<h1 class="judul-bagian">Keranjang Belanja</h1>

<?php if ($pesan): ?><div class="notif"><?= e($pesan) ?></div><?php endif; ?>

<?php if (!keranjang()): ?>
    <div class="kosong">
        <img src="/images/aldi-maskot.svg" alt="" width="90" height="90">
        <p>Keranjang masih kosong. Jelajahi katalog untuk menemukan produk yang sesuai.</p>
        <a class="tombol" href="index.php#produk">Mulai belanja</a>
    </div>
<?php else: ?>
    <div class="tabel-bungkus">
    <table class="tabel-keranjang">
        <thead>
            <tr><th>Produk</th><th>Harga</th><th>Jumlah</th><th>Subtotal</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach (keranjang() as $id => $qty):
            $p = cari_produk((int)$id);
            if (!$p) continue; ?>
            <tr>
                <td class="sel-produk">
                    <img src="<?= e(gambar_url($p['gambar'])) ?>" alt="">
                    <a href="produk.php?id=<?= $p['id'] ?>"><?= e($p['nama']) ?></a>
                </td>
                <td><?= rupiah($p['harga']) ?></td>
                <td>
                    <form method="post" class="form-qty">
                        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                        <input type="hidden" name="aksi" value="ubah">
                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                        <input type="hidden" name="tujuan" value="<?= e($tujuan_kode) ?>">
                        <input type="hidden" name="kurir" value="<?= e($kurir_dipilih) ?>">
                        <input type="number" name="qty" value="<?= (int)$qty ?>" min="1" max="99">
                        <button class="tombol kecil abu" type="submit">Ubah</button>
                    </form>
                </td>
                <td><strong><?= rupiah($p['harga'] * $qty) ?></strong></td>
                <td>
                    <form method="post">
                        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                        <input type="hidden" name="aksi" value="hapus">
                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                        <input type="hidden" name="tujuan" value="<?= e($tujuan_kode) ?>">
                        <input type="hidden" name="kurir" value="<?= e($kurir_dipilih) ?>">
                        <button class="tombol kecil bahaya" type="submit">Hapus</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>

    <?php
    $estimasi    = $tujuan_kode !== '' ? ongkir_estimasi($tujuan_kode) : null;
    $berat_total = ongkir_berat_total();

    // Cocokkan kurir yang dipilih dengan salah satu opsi estimasi.
    $opsi_terpilih = null;
    if ($estimasi && $kurir_dipilih !== '') {
        foreach ($estimasi['opsi'] as $o) {
            if (strcasecmp($o['kurir'], $kurir_dipilih) === 0) { $opsi_terpilih = $o; break; }
        }
    }
    if (!$opsi_terpilih) $kurir_dipilih = '';

    $subtotal     = total_keranjang();
    $ongkir_pilih = $opsi_terpilih['tarif'] ?? 0;
    $total_akhir  = $subtotal + $ongkir_pilih;
    ?>
    <section class="ongkir" id="ongkir">
        <div class="ongkir-kepala">
            <h2 class="ongkir-judul">Cek Perkiraan Ongkir</h2>
            <p class="ongkir-desk">
                Perkiraan biaya kirim dari <strong><?= e(ONGKIR_ASAL_KOTA) ?></strong> ke alamatmu di Jabodetabek,
                dihitung dari <strong>jarak</strong> dan <strong>berat</strong> paket (±<?= number_format($berat_total / 1000, 2, ',', '.') ?> kg).
            </p>
        </div>
        <form class="ongkir-form" method="get" action="keranjang.php#ongkir">
            <label for="tujuan">Kecamatan tujuan</label>
            <select name="tujuan" id="tujuan">
                <option value="">— Pilih kecamatan tujuan —</option>
                <?php foreach (ongkir_destinasi() as $kota => $daftar): ?>
                    <optgroup label="<?= e($kota) ?>">
                        <?php foreach ($daftar as $kode => $d): ?>
                            <option value="<?= e($kode) ?>" <?= $tujuan_kode === $kode ? 'selected' : '' ?>><?= e($d[0]) ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endforeach; ?>
            </select>
            <button class="tombol kecil" type="submit">Cek ongkir</button>
        </form>

        <?php if ($estimasi): ?>
            <div class="ongkir-hasil">
                <div class="ongkir-meta">
                    <span>Tujuan: <strong><?= e($estimasi['nama']) ?></strong>, <?= e($estimasi['kota']) ?></span>
                    <span>Jarak ±<?= (int)round($estimasi['jarak']) ?> km</span>
                    <span>Berat ±<?= number_format($estimasi['berat'] / 1000, 2, ',', '.') ?> kg</span>
                </div>
                <p class="ongkir-pilih-hint">Pilih salah satu ekspedisi untuk menambahkan ongkir ke total belanja:</p>
                <ul class="ongkir-opsi">
                    <?php foreach ($estimasi['opsi'] as $o):
                        $terpilih = $opsi_terpilih && $o['kurir'] === $opsi_terpilih['kurir'];
                        $tautan = 'keranjang.php?tujuan=' . rawurlencode($estimasi['kode']) . '&kurir=' . rawurlencode($o['kurir']) . '#ongkir';
                    ?>
                    <li>
                        <a class="ongkir-item<?= $terpilih ? ' aktif' : '' ?>" href="<?= e($tautan) ?>">
                            <span class="ongkir-kurir"><?= e($o['kurir']) ?><small><?= e($o['layanan']) ?></small></span>
                            <span class="ongkir-eta">±<?= e($o['eta']) ?></span>
                            <span class="ongkir-tarif"><?= rupiah($o['tarif']) ?></span>
                            <span class="ongkir-pilih"><?= $terpilih ? '✓ Dipilih' : 'Pilih' ?></span>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <p class="ongkir-ket">*Perkiraan saja, bukan tarif resmi ekspedisi. Biaya final dikonfirmasi saat checkout.
                    <?php if ($opsi_terpilih): ?>
                        <a class="ongkir-batal" href="keranjang.php#ongkir">× Batalkan pilihan ongkir</a>
                    <?php endif; ?>
                </p>
            </div>
        <?php else: ?>
            <p class="ongkir-ket">Pilih kecamatan lalu klik <strong>Cek ongkir</strong> untuk melihat perkiraan biaya JNE, SiCepat, dan J&amp;T.</p>
        <?php endif; ?>
    </section>

    <div class="ringkasan">
        <div class="ringkasan-info">
            <p>Total belanja: <strong class="harga-besar"><?= rupiah($total_akhir) ?></strong></p>
            <ul class="rincian-belanja">
                <li><span>Subtotal produk</span><b><?= rupiah($subtotal) ?></b></li>
                <li<?= $opsi_terpilih ? '' : ' class="rincian-off"' ?>>
                    <span>Ongkir<?= $opsi_terpilih ? ' · ' . e($opsi_terpilih['kurir'] . ' ' . $opsi_terpilih['layanan']) : '' ?></span>
                    <b><?= $opsi_terpilih ? rupiah($ongkir_pilih) : 'Belum dipilih' ?></b>
                </li>
            </ul>
            <small class="ringkasan-ket">Sudah termasuk bungkus kado standar.</small>
        </div>
        <div class="aksi-baris">
            <form method="post">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="aksi" value="kosongkan">
                <button class="tombol abu" type="submit">Kosongkan</button>
            </form>
            <form method="post">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="aksi" value="checkout">
                <input type="hidden" name="tujuan" value="<?= e($tujuan_kode) ?>">
                <input type="hidden" name="kurir" value="<?= e($kurir_dipilih) ?>">
                <button class="tombol" type="submit">Checkout via WhatsApp</button>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
