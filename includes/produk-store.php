<?php
/**
 * Penyimpanan produk.
 *
 * - Jika Supabase aktif (SUPABASE_URL + SUPABASE_SERVICE_KEY), data disimpan
 *   di tabel `produk` dan `checkout_stat` sehingga persisten di Vercel.
 * - Jika tidak, fallback ke data/produk.json (lokal) / default data.php.
 */

require_once __DIR__ . '/cloudinary.php';
require_once __DIR__ . '/supabase.php';

/** Path file JSON produk (fallback lokal). */
function produk_json_path(): string {
    return dirname(__DIR__) . '/data/produk.json';
}

/** Reset cache produk dalam satu request. */
function produk_cache_reset(): void {
    unset($GLOBALS['produk_cache']);
}

/** Reset cache statistik checkout dalam satu request. */
function checkout_cache_reset(): void {
    unset($GLOBALS['checkout_cache']);
}

/** Palet warna tetap yang bisa dipilih admin (urutan = urutan tampil). */
function produk_palet_warna(): array {
    return [
        'black'         => ['nama' => 'PLA Basic Black',         'hex' => '#000000', 'ket' => 'Hitam'],
        'chestnut-black'=> ['nama' => 'PLA Basic Chestnut Black','hex' => '#2B1B17', 'ket' => 'Hitam Kecokelatan'],
        'cyan'          => ['nama' => 'PLA Basic Cyan',          'hex' => '#00FFFF', 'ket' => 'Sian / Biru Muda Terang'],
        'dark-grey'     => ['nama' => 'PLA Basic Dark Grey',     'hex' => '#414A4C', 'ket' => 'Abu-Abu Tua'],
        'dark-purple'   => ['nama' => 'PLA Basic Dark Purple',   'hex' => '#301934', 'ket' => 'Ungu Tua'],
        'green'         => ['nama' => 'PLA Basic Green',         'hex' => '#008000', 'ket' => 'Hijau'],
        'light-green'   => ['nama' => 'PLA Basic Light Green',   'hex' => '#90EE90', 'ket' => 'Hijau Muda'],
        'light-grey'    => ['nama' => 'PLA Basic Light Grey',    'hex' => '#D3D3D3', 'ket' => 'Abu-Abu Muda'],
        'magenta'       => ['nama' => 'PLA Basic Magenta',       'hex' => '#FF00FF', 'ket' => 'Magenta / Merah Muda Tua'],
        'olive-green'   => ['nama' => 'PLA Basic Olive Green',   'hex' => '#556B2F', 'ket' => 'Hijau Zaitun'],
        'orange'        => ['nama' => 'PLA Basic Orange',        'hex' => '#FFA500', 'ket' => 'Jingga / Oranye'],
        'purple'        => ['nama' => 'PLA Basic Purple',        'hex' => '#800080', 'ket' => 'Ungu'],
        'transparent'   => ['nama' => 'PLA Basic Transparent',   'hex' => '#E0E0E0', 'ket' => 'Bening / Transparan'],
        'white'         => ['nama' => 'PLA Basic White',         'hex' => '#FFFFFF', 'ket' => 'Putih'],
        'yellow'        => ['nama' => 'PLA Basic Yellow',        'hex' => '#FFFF00', 'ket' => 'Kuning'],
        'vivid-yellow'  => ['nama' => 'PLA Basic Vivid Yellow',  'hex' => '#FFE300', 'ket' => 'Kuning Cerah/Mencolok'],
        'oak'           => ['nama' => 'PLA Basic Oak',           'hex' => '#C3A368', 'ket' => 'Cokelat Kayu Oak'],
        'light-blue'    => ['nama' => 'PLA Basic Light Blue',    'hex' => '#ADD8E6', 'ket' => 'Biru Muda'],
        'klein-blue'    => ['nama' => 'PLA Basic Klein Blue',    'hex' => '#002FA7', 'ket' => 'Biru Klein / Biru Khas'],
        'dark-green'    => ['nama' => 'PLA Basic Dark Green',    'hex' => '#006400', 'ket' => 'Hijau Tua'],
        'dark-blue'     => ['nama' => 'PLA Basic Dark Blue',     'hex' => '#00008B', 'ket' => 'Biru Tua'],
        'bright-yellow' => ['nama' => 'PLA Basic Bright Yellow', 'hex' => '#FFEA00', 'ket' => 'Kuning Terang'],
        'bright-red'    => ['nama' => 'PLA Basic Bright Red',    'hex' => '#FF0000', 'ket' => 'Merah Terang'],
        'coffee-brown'  => ['nama' => 'PLA Basic Coffee Brown',  'hex' => '#4A2C2A', 'ket' => 'Cokelat Kopi'],
        'dark-red'      => ['nama' => 'PLA Basic Dark Red',      'hex' => '#8B0000', 'ket' => 'Merah Tua'],
        'flesh'         => ['nama' => 'PLA Basic Flesh Color/Skin', 'hex' => '#FFCBA4', 'ket' => 'Warna Kulit'],
        'lavender-blue' => ['nama' => 'PLA Basic Lavender Blue', 'hex' => '#967BB6', 'ket' => 'Biru Lavender / Ungu Muda Kebiruan'],
        'pink'          => ['nama' => 'PLA Basic Pink',          'hex' => '#FFC0CB', 'ket' => 'Merah Muda / Pink'],
    ];
}

/** Palet default: semua warna tetap. */
function produk_warna_default(): array {
    return array_values(produk_palet_warna());
}

/** Kunci palet terdekat dari kode hex. */
function warna_palet_terdekat(string $hex): string {
    $hex = strtoupper(ltrim(trim($hex), '#'));
    if (!preg_match('/^[0-9A-F]{6}$/', $hex)) return '';

    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));

    $terdekat = '';
    $jarak    = PHP_INT_MAX;
    foreach (produk_palet_warna() as $kunci => $w) {
        $kode = ltrim($w['hex'], '#');
        $dr = $r - hexdec(substr($kode, 0, 2));
        $dg = $g - hexdec(substr($kode, 2, 2));
        $db = $b - hexdec(substr($kode, 4, 2));
        $d  = ($dr * $dr) + ($dg * $dg) + ($db * $db);
        if ($d < $jarak) {
            $jarak    = $d;
            $terdekat = $kunci;
        }
    }
    return $terdekat;
}

/**
 * Petakan daftar pilihan warna lama ke palet tetap.
 * Nama/hex yang cocok dipakai; selain itu dicari warna palet terdekat.
 * Bila kosong, kembalikan semua warna palet.
 */
function produk_warna_bersih($daftar): array {
    $palet = produk_palet_warna();
    $hasil = [];

    foreach ((array)$daftar as $w) {
        if (!is_array($w)) continue;

        $nama = trim((string)($w['nama'] ?? ''));
        $hex  = strtoupper(ltrim(trim((string)($w['hex'] ?? '')), '#'));
        $kunci = '';

        // Nama palet lebih diutamakan supaya warna lama tetap konsisten.
        foreach ($palet as $k => $p) {
            if ($nama !== '' && strcasecmp($nama, $p['nama']) === 0) {
                $kunci = $k;
                break;
            }
        }

        // Hex persis.
        if ($kunci === '' && preg_match('/^[0-9A-F]{6}$/', $hex)) {
            foreach ($palet as $k => $p) {
                if (strcasecmp($hex, ltrim($p['hex'], '#')) === 0) {
                    $kunci = $k;
                    break;
                }
            }
        }

        // Warna di luar palet: ambil yang paling dekat.
        if ($kunci === '' && preg_match('/^[0-9A-F]{6}$/', $hex)) {
            $kunci = warna_palet_terdekat($hex);
        }

        if ($kunci === '' || !isset($palet[$kunci]) || isset($hasil[$kunci])) continue;

        $hasil[$kunci] = $palet[$kunci];
        if (count($hasil) >= count($palet)) break;
    }

    return $hasil ? array_values($hasil) : produk_warna_default();
}

/** Sumber data awal untuk seed/fallback: JSON lokal, else default data.php. */
function produk_sumber_awal(): array {
    $path = produk_json_path();
    if (is_file($path)) {
        $isi = json_decode((string)file_get_contents($path), true);
        if (is_array($isi)) return $isi;
    }
    global $produk;
    return is_array($produk) ? $produk : [];
}

/** Baris Supabase -> format aplikasi. */
function produk_db_ke_app(array $r): array {
    return produk_normalisasi([
        'id'        => (int)($r['id'] ?? 0),
        'nama'      => (string)($r['nama'] ?? ''),
        'penjual'   => (string)($r['penjual'] ?? ''),
        'kategori'  => sb_json_arr($r['kategori'] ?? []),
        'warna'     => (string)($r['warna'] ?? 'koral'),
        'harga'     => (int)($r['harga'] ?? 0),
        'unduhan'   => (int)($r['unduhan'] ?? 0),
        'suka'      => (int)($r['suka'] ?? 0),
        'gambar'    => (string)($r['gambar'] ?? ''),
        'galeri'    => sb_json_arr($r['galeri'] ?? []),
        'deskripsi' => (string)($r['deskripsi'] ?? ''),
        'pilihan_warna' => sb_json_arr($r['pilihan_warna'] ?? []),
        'terlaris'  => !empty($r['terlaris']),
        'unggulan'  => !empty($r['unggulan']),
    ]);
}

/** Format aplikasi -> baris Supabase. */
function produk_app_ke_db(array $p): array {
    $galeri = array_slice(array_values((array)($p['galeri'] ?? [])), 0, 5);
    return [
        'id'        => (int)($p['id'] ?? 0),
        'nama'      => (string)($p['nama'] ?? ''),
        'penjual'   => (string)($p['penjual'] ?? 'Aldebaran Studio'),
        'kategori'  => array_values((array)($p['kategori'] ?? [])),
        'warna'     => in_array($p['warna'] ?? '', ['koral', 'matahari', 'ungu'], true) ? $p['warna'] : 'koral',
        'harga'     => max(0, (int)($p['harga'] ?? 0)),
        'unduhan'   => max(0, (int)($p['unduhan'] ?? 0)),
        'suka'      => max(0, (int)($p['suka'] ?? 0)),
        'gambar'    => (string)($p['gambar'] ?? ''),
        'galeri'    => $galeri,
        'deskripsi' => (string)($p['deskripsi'] ?? ''),
        'pilihan_warna' => produk_warna_bersih($p['pilihan_warna'] ?? []),
        'terlaris'  => !empty($p['terlaris']),
        'unggulan'  => !empty($p['unggulan']),
    ];
}

/**
 * Ambil daftar produk.
 * Urutan sumber: cache -> Supabase -> data/produk.json -> default data.php.
 */
function produk_semua(): array {
    if (array_key_exists('produk_cache', $GLOBALS)) return $GLOBALS['produk_cache'];

    if (sb_aktif()) {
        $r = sb_request('GET', 'produk', null, 'select=*&order=id.asc');

        if (!$r['ok']) {
            // Supabase bermasalah: tampilkan data lokal agar toko tetap jalan.
            return $GLOBALS['produk_cache'] = produk_sumber_awal();
        }

        $baris = is_array($r['data']) ? $r['data'] : [];
        if ($baris) {
            return $GLOBALS['produk_cache'] = array_map('produk_db_ke_app', $baris);
        }

        // Tabel kosong: isi sekali dari data lokal.
        if (sb_pengaturan('seed_produk') !== '1') {
            $awal = produk_sumber_awal();
            if ($awal) {
                $kirim = array_map('produk_app_ke_db', array_map('produk_normalisasi', $awal));
                $seed  = sb_post('produk?on_conflict=id', $kirim, 'resolution=ignore-duplicates,return=minimal');
                if ($seed['ok']) {
                    sb_pengaturan_set('seed_produk', '1');
                    produk_cache_reset();
                    return produk_semua();
                }
                return $GLOBALS['produk_cache'] = $awal;
            }
            sb_pengaturan_set('seed_produk', '1');
        }

        return $GLOBALS['produk_cache'] = [];
    }

    $path = produk_json_path();
    if (is_file($path)) {
        $isi = json_decode((string)file_get_contents($path), true);
        if (is_array($isi)) return $GLOBALS['produk_cache'] = $isi;
    }
    global $produk;
    return $GLOBALS['produk_cache'] = is_array($produk) ? $produk : [];
}

/** Tulis daftar produk ke JSON (mode lokal / fallback). */
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
 * Kembalikan id produk, atau 0 bila penyimpanan gagal.
 */
function produk_simpan(array $data): int {
    $id = (int)($data['id'] ?? 0);

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
        'pilihan_warna' => produk_warna_bersih($data['pilihan_warna'] ?? []),
    ];

    $galeri = array_values(array_unique(array_filter(array_map('trim', (array)($data['galeri'] ?? [])))));
    $baru['galeri'] = array_slice($galeri, 0, 5);

    if (sb_aktif()) {
        $baris = produk_app_ke_db($baru);

        if ($id > 0 && produk_cari($id)) {
            $r = sb_patch('produk', 'id=eq.' . $id, $baris);
            produk_cache_reset();
            return ($r['ok'] && is_array($r['data']) && count($r['data']) > 0) ? (int)$baru['id'] : 0;
        }

        $r = sb_post('produk', $baris, 'return=representation');
        if (!$r['ok'] && $r['status'] === 409) {
            // Id bentrok (mis. dua admin menyimpan bersamaan) -> coba id baru.
            produk_cache_reset();
            $baru['id'] = produk_id_baru();
            $baris['id'] = $baru['id'];
            $r = sb_post('produk', $baris, 'return=representation');
        }
        produk_cache_reset();
        return $r['ok'] ? (int)$baru['id'] : 0;
    }

    $daftar    = produk_semua();
    $ditemukan = false;
    foreach ($daftar as $i => $p) {
        if ((int)($p['id'] ?? 0) === $baru['id']) {
            $daftar[$i] = $baru;
            $ditemukan  = true;
            break;
        }
    }
    if (!$ditemukan) $daftar[] = $baru;

    $ok = produk_tulis($daftar);
    produk_cache_reset();
    return $ok ? (int)$baru['id'] : 0;
}

/** Hapus produk berdasarkan id. */
function produk_hapus(int $id): bool {
    if (sb_aktif()) {
        $r = sb_delete('produk', 'id=eq.' . $id);
        produk_cache_reset();
        checkout_cache_reset();
        return $r['ok'] && is_array($r['data']) && count($r['data']) > 0;
    }

    $daftar = produk_semua();
    $baru   = array_values(array_filter($daftar, fn($p) => (int)($p['id'] ?? 0) !== $id));
    if (count($baru) === count($daftar)) return false;
    $ok = produk_tulis($baru);
    produk_cache_reset();
    return $ok;
}

/** Toggle boolean field produk (terlaris / unggulan). */
function produk_toggle(int $id, string $field): bool {
    if (!in_array($field, ['terlaris', 'unggulan'], true)) return false;

    if (sb_aktif()) {
        $baris = sb_get('produk', 'select=' . $field . '&id=eq.' . $id . '&limit=1');
        if (!$baris) return false;
        $nilai = empty($baris[0][$field]);
        $r = sb_patch('produk', 'id=eq.' . $id, [$field => $nilai]);
        produk_cache_reset();
        return $r['ok'];
    }

    $daftar = produk_semua();
    foreach ($daftar as $i => $p) {
        if ((int)($p['id'] ?? 0) === $id) {
            $daftar[$i][$field] = empty($p[$field]);
            $ok = produk_tulis($daftar);
            produk_cache_reset();
            return $ok;
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
    if (array_key_exists('checkout_cache', $GLOBALS)) return $GLOBALS['checkout_cache'];

    $data = [];

    if (sb_aktif()) {
        $r = sb_request('GET', 'checkout_stat', null, 'select=produk_id,jumlah');
        if ($r['ok'] && is_array($r['data'])) {
            foreach ($r['data'] as $baris) {
                $data[(int)($baris['produk_id'] ?? 0)] = max(0, (int)($baris['jumlah'] ?? 0));
            }
            return $GLOBALS['checkout_cache'] = $data;
        }
    }

    $path = checkout_json_path();
    if (is_file($path)) {
        $isi = json_decode((string)file_get_contents($path), true);
        if (is_array($isi)) {
            foreach ($isi as $id => $n) $data[(int)$id] = max(0, (int)$n);
        }
    }
    return $GLOBALS['checkout_cache'] = $data;
}

/**
 * Tambah hitungan checkout tiap produk di keranjang.
 * +1 per peristiwa checkout (bukan per qty).
 */
function checkout_tambah(array $keranjang): void {
    if (!$keranjang) return;

    $diproses = [];
    if (sb_aktif()) {
        foreach ($keranjang as $kunci => $qty) {
            $id = (int)explode(':', (string)$kunci, 2)[0];
            if ($id <= 0 || isset($diproses[$id])) continue;
            $diproses[$id] = true;

            $r = sb_rpc('checkout_tambah_stat', ['p_id' => $id]);
            if ($r['ok']) continue;

            // Fallback bila RPC belum dibuat: baca lalu tulis.
            $baris = sb_get('checkout_stat', 'select=jumlah&produk_id=eq.' . $id . '&limit=1');
            if ($baris) {
                sb_patch('checkout_stat', 'produk_id=eq.' . $id, ['jumlah' => (int)($baris[0]['jumlah'] ?? 0) + 1]);
            } else {
                sb_post('checkout_stat', ['produk_id' => $id, 'jumlah' => 1], 'return=minimal');
            }
        }
        checkout_cache_reset();
        return;
    }

    $path = checkout_json_path();
    $dir  = dirname($path);
    if (!is_dir($dir)) @mkdir($dir, 0775, true);

    $fp = @fopen($path, 'c+');
    if (!$fp) return;
    @flock($fp, LOCK_EX);
    $data = json_decode((string)stream_get_contents($fp), true);
    if (!is_array($data)) $data = [];

    $ubah = false;
    foreach ($keranjang as $kunci => $qty) {
        $id = (int)explode(':', (string)$kunci, 2)[0];
        if ($id <= 0 || isset($diproses[$id])) continue;
        $diproses[$id] = true;
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
    checkout_cache_reset();
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
 * Normalisasi field produk.
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
    $p['pilihan_warna'] = produk_warna_bersih($p['pilihan_warna'] ?? []);
    return $p;
}

/**
 * Timpa global $produk dengan hasil produk_semua().
 */
function produk_muat_ke_global(): void {
    global $produk;
    $produk = array_map('produk_normalisasi', produk_semua());
}
