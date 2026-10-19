<?php
/**
 * Penyimpanan produk berbasis file JSON (tanpa database).
 *
 * - Data default ada di includes/data.php ($produk).
 * - Perubahan dari panel admin disimpan ke data/produk.json.
 * - Saat runtime: jika data/produk.json ada, isinya dipakai; jika tidak,
 *   pakai $produk default dari data.php.
 *
 * Catatan Vercel: filesystem serverless bersifat read-only, sehingga
 * data/produk.json tidak bisa ditulis dari dashboard di Vercel. Panel admin
 * berfungsi penuh secara lokal (XAMPP / php -S). Di Vercel, tampilan toko
 * tetap memakai data default.
 */

require_once __DIR__ . '/cloudinary.php';

/** Path file JSON produk. */
function produk_json_path(): string {
    return dirname(__DIR__) . '/data/produk.json';
}

/** Ambil daftar produk: JSON jika ada, else default dari data.php. */
function produk_semua(): array {
    global $produk;
    $path = produk_json_path();
    if (is_file($path)) {
        $isi = json_decode((string)file_get_contents($path), true);
        if (is_array($isi)) return $isi;
    }
    return is_array($produk) ? $produk : [];
}

/** Tulis daftar produk ke JSON (pretty-print). */
function produk_tulis(array $daftar): bool {
    $path = produk_json_path();
    $dir  = dirname($path);
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    $json = json_encode(array_values($daftar), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return $json !== false && @file_put_contents($path, $json . "\n", LOCK_EX) !== false;
}

/** Cari satu produk berdasarkan id. */
function produk_cari(int $id): ?array {
    foreach (produk_semua() as $p) {
        if ((int)($p['id'] ?? 0) === $id) return $p;
    }
    return null;
}

/** Id berikutnya (maks + 1). */
function produk_id_baru(): int {
    $maks = 0;
    foreach (produk_semua() as $p) {
        $maks = max($maks, (int)($p['id'] ?? 0));
    }
    return $maks + 1;
}

/**
 * Simpan (tambah / update) satu produk.
 * $data adalah array field dari form admin; id kosong = tambah baru.
 */
function produk_simpan(array $data): int {
    $daftar = produk_semua();
    $id     = (int)($data['id'] ?? 0);

    $baru = [
        'id'         => $id > 0 ? $id : produk_id_baru(),
        'nama'       => trim((string)($data['nama'] ?? '')),
        'penjual'    => trim((string)($data['penjual'] ?? 'Aldebaran Studio')),
        'kategori'   => array_values(array_filter((array)($data['kategori'] ?? []), 'is_string')),
        'warna'      => in_array($data['warna'] ?? '', ['koral', 'matahari', 'ungu'], true) ? $data['warna'] : 'koral',
        'harga'      => max(0, (int)($data['harga'] ?? 0)),
        'unduhan'    => max(0, (int)($data['unduhan'] ?? 0)),
        'suka'       => max(0, (int)($data['suka'] ?? 0)),
        'gambar'     => trim((string)($data['gambar'] ?? '')),
        'terlaris'   => !empty($data['terlaris']),
        'unggulan'   => !empty($data['unggulan']),
        'deskripsi'  => trim((string)($data['deskripsi'] ?? '')),
    ];

    $galeri = array_values(array_filter(array_map('trim', (array)($data['galeri'] ?? []))));
    if ($galeri) $baru['galeri'] = $galeri;

    $ditemukan = false;
    foreach ($daftar as $i => $p) {
        if ((int)($p['id'] ?? 0) === $baru['id']) {
            $daftar[$i] = $baru;
            $ditemukan  = true;
            break;
        }
    }
    if (!$ditemukan) $daftar[] = $baru;

    produk_tulis($daftar);
    return $baru['id'];
}

/** Hapus produk berdasarkan id. */
function produk_hapus(int $id): bool {
    $daftar = produk_semua();
    $baru   = array_values(array_filter($daftar, fn($p) => (int)($p['id'] ?? 0) !== $id));
    if (count($baru) === count($daftar)) return false;
    return produk_tulis($baru);
}

/** Toggle boolean field produk (terlaris / unggulan). */
function produk_toggle(int $id, string $field): bool {
    if (!in_array($field, ['terlaris', 'unggulan'], true)) return false;
    $daftar = produk_semua();
    foreach ($daftar as $i => $p) {
        if ((int)($p['id'] ?? 0) === $id) {
            $daftar[$i][$field] = empty($p[$field]);
            return produk_tulis($daftar);
        }
    }
    return false;
}

/* ---------- Penghitung checkout (dasar "Terlaris" otomatis) ---------- */

/** Path file JSON penghitung checkout. */
function checkout_json_path(): string {
    return dirname(__DIR__) . '/data/checkout.json';
}

/** Ambil peta id => jumlah checkout (dibaca sekali per request). */
function checkout_data(): array {
    static $cache = null;
    if ($cache !== null) return $cache;
    $path = checkout_json_path();
    $data = [];
    if (is_file($path)) {
        $isi = json_decode((string)file_get_contents($path), true);
        if (is_array($isi)) {
            foreach ($isi as $id => $n) $data[(int)$id] = max(0, (int)$n);
        }
    }
    return $cache = $data;
}

/**
 * Tambah hitungan checkout tiap produk di keranjang.
 * +1 per peristiwa checkout (bukan per qty), jadi mencerminkan
 * "berapa customer yang checkout item ini". Tulis dengan lock supaya
 * aman saat beberapa checkout bersamaan.
 */
function checkout_tambah(array $keranjang): void {
    if (!$keranjang) return;
    $path = checkout_json_path();
    $dir  = dirname($path);
    if (!is_dir($dir)) @mkdir($dir, 0775, true);

    $fp = @fopen($path, 'c+');
    if (!$fp) return;
    @flock($fp, LOCK_EX);
    $data = json_decode((string)stream_get_contents($fp), true);
    if (!is_array($data)) $data = [];

    $ubah = false;
    foreach ($keranjang as $id => $qty) {
        $id = (int)$id;
        if ($id <= 0) continue;
        $data[$id] = (int)($data[$id] ?? 0) + 1;
        $ubah = true;
    }
    if ($ubah) {
        @ftruncate($fp, 0);
        @rewind($fp);
        @fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
        @fflush($fp);
    }
    @flock($fp, LOCK_UN);
    @fclose($fp);
}

/** Jumlah checkout untuk satu produk. */
function checkout_jumlah(int $id): int {
    return checkout_data()[(int)$id] ?? 0;
}

/**
 * Urutan id produk "Terlaris".
 * - Produk yang dipin manual (field `terlaris`) selalu paling depan.
 * - Lalu top-$limit produk dengan jumlah checkout terbanyak.
 */
function produk_terlaris_ids(int $limit = 8): array {
    $ada = [];
    foreach (produk_semua() as $p) $ada[(int)($p['id'] ?? 0)] = $p;

    $ids = [];
    foreach ($ada as $id => $p) {
        if (!empty($p['terlaris'])) $ids[] = $id;
    }

    $data = checkout_data();
    arsort($data);
    $n = 0;
    foreach ($data as $id => $jml) {
        if ($n >= $limit) break;
        $id = (int)$id;
        if (!isset($ada[$id]) || in_array($id, $ids, true)) continue;
        $ids[] = $id;
        $n++;
    }
    return $ids;
}

/** Set id produk terlaris untuk pengecekan cepat (badge). */
function produk_terlaris_set(): array {
    return array_fill_keys(produk_terlaris_ids(), true);
}

/**
 * Normalisasi field produk: pastikan kategori/galeri bertipe array,
 * warna valid, dll. Dipakai saat memuat untuk ditampilkan.
 */
function produk_normalisasi(array $p): array {
    $p['id']        = (int)($p['id'] ?? 0);
    $p['nama']      = (string)($p['nama'] ?? '');
    $p['penjual']   = (string)($p['penjual'] ?? 'Aldebaran Studio');
    $p['kategori']  = array_values((array)($p['kategori'] ?? []));
    $p['warna']     = in_array($p['warna'] ?? '', ['koral', 'matahari', 'ungu'], true) ? $p['warna'] : 'koral';
    $p['harga']     = (int)($p['harga'] ?? 0);
    $p['unduhan']   = (int)($p['unduhan'] ?? 0);
    $p['suka']      = (int)($p['suka'] ?? 0);
    $p['gambar']    = (string)($p['gambar'] ?? '');
    $p['terlaris']  = !empty($p['terlaris']);
    $p['unggulan']  = !empty($p['unggulan']);
    $p['deskripsi'] = (string)($p['deskripsi'] ?? '');
    $p['galeri']    = array_values((array)($p['galeri'] ?? []));
    return $p;
}

/**
 * Timpa global $produk dengan hasil produk_semua() (JSON atau default).
 * Dipanggil dari functions.php setelah data.php dimuat, sehingga semua
 * halaman (index, produk, keranjang) otomatis memakai data terbaru.
 */
function produk_muat_ke_global(): void {
    global $produk;
    $produk = array_map('produk_normalisasi', produk_semua());
}
