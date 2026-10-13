<?php
require_once __DIR__ . '/includes/functions.php';

// Proses aksi keranjang (POST + token CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_cek()) {
        http_response_code(400);
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
        $k = [];
        $kode = 'checkout';
    }

    simpan_keranjang($k);
    header('Location: keranjang.php' . ($kode !== '' ? '?pesan=' . $kode : ''));
    exit;
}

// Pesan singkat (kode tetap, bukan teks bebas dari URL)
$daftar_pesan = [
    'tambah'   => 'Produk ditambahkan ke keranjang.',
    'hapus'    => 'Produk dihapus dari keranjang.',
    'checkout' => 'Terima kasih! Pesananmu sudah kami terima (demo, belum terhubung pembayaran).',
];
$pesan = $daftar_pesan[$_GET['pesan'] ?? ''] ?? '';

$judul_halaman = 'Keranjang Belanja';
$menu_aktif = 'koleksi';
require __DIR__ . '/includes/header.php';
?>

<h1 class="judul-bagian">🛒 Keranjang Belanja</h1>

<?php if ($pesan): ?><div class="notif">✨ <?= e($pesan) ?></div><?php endif; ?>

<?php if (!keranjang()): ?>
    <div class="kosong">
        <img src="/images/aldi-maskot.svg" alt="" width="90" height="90">
        <p>Keranjangmu masih kosong 🥺 Yuk cari mainan pastel yang gemas dulu!</p>
        <a class="tombol" href="index.php#produk">🎈 Mulai belanja</a>
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
                        <button class="tombol kecil bahaya" type="submit">Hapus</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>

    <div class="ringkasan">
        <p>🎀 Total belanja: <strong class="harga-besar"><?= rupiah(total_keranjang()) ?></strong><br><small style="color:var(--teks-redup)">Sudah termasuk bungkus kado pastel gratis!</small></p>
        <div class="aksi-baris">
            <form method="post">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="aksi" value="kosongkan">
                <button class="tombol abu" type="submit">🗑️ Kosongkan</button>
            </form>
            <form method="post">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="aksi" value="checkout">
                <button class="tombol" type="submit">💖 Checkout sekarang</button>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
