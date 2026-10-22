<?php
/**
 * Statistik pengunjung situs.
 *
 * Setiap kunjungan halaman publik dicatat ke tabel Supabase `kunjungan`
 * (fallback: data/kunjungan.json untuk mode lokal). Data tidak dihapus,
 * jadi terus terakumulasi.
 *
 * Klasifikasi kunjungan:
 * - "klik"   : pengunjung datang dari tautan di dalam situs (referrer internal).
 * - "random" : pengunjung langsung / dari luar situs (referrer kosong/eksternal).
 *
 * Pengunjung unik dihitung dari hash IP + user agent + tanggal (tanpa
 * menyimpan IP asli), sehingga privasi tetap terjaga.
 */

require_once __DIR__ . '/supabase.php';

/** Path file JSON statistik (fallback lokal). */
function statistik_json_path(): string {
    return dirname(__DIR__) . '/data/kunjungan.json';
}

/** Deteksi bot/crawler yang tidak perlu dihitung sebagai pengunjung. */
function statistik_bot(): bool {
    $ua = strtolower(trim((string)($_SERVER['HTTP_USER_AGENT'] ?? '')));
    if ($ua === '') return true;

    return (bool)preg_match(
        '/bot|crawl|spider|slurp|bingpreview|facebookexternalhit|headless|lighthouse|pagespeed|semrush|ahrefs|uptime|monitor|curl|wget|python-requests|go-http-client/',
        $ua
    );
}

/** Path halaman yang sedang dibuka, dinormalisasi (tanpa query). */
function statistik_halaman_aktif(): string {
    $path = rawurldecode((string)(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'));
    if ($path === '') $path = '/';
    if ($path[0] !== '/') $path = '/' . $path;

    // Buang subfolder proyek bila diakses lewat htdocs (mis. /aldebaran3dprinting/).
    $base = rtrim(str_replace('\\', '/', (string)dirname((string)($_SERVER['SCRIPT_NAME'] ?? ''))), '/');
    if ($base !== ''
        && !in_array($base, ['/api', '/admin'], true)
        && !str_ends_with($base, '/admin')
        && !str_ends_with($base, '/api')
    ) {
        if ($path === $base) {
            $path = '/';
        } elseif (str_starts_with($path, $base . '/')) {
            $path = substr($path, strlen($base));
        }
    }

    if ($path === '/index.php') return '/';
    return $path;
}

/** Abaikan halaman admin, API, dan aset statis. */
function statistik_diabaikan(string $path): bool {
    if (preg_match('#/admin(/|$)#i', $path)) return true;
    if (preg_match('#/api(/|$)#i', $path)) return true;
    if (preg_match('#/(assets|images)/#i', $path)) return true;

    // Akses langsung file admin (termasuk lewat subfolder htdocs).
    $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    return (bool)preg_match('#/admin(/|$)#i', $script);
}

/** Klasifikasi sumber kunjungan berdasarkan referrer. */
function statistik_sumber(string $rujukan): string {
    if (trim($rujukan) === '') return 'random';

    $host_ref = strtolower((string)parse_url($rujukan, PHP_URL_HOST));
    $host_ini = strtolower((string)($_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? '')));

    $host_ref = (string)preg_replace('/:\d+$/', '', $host_ref);
    $host_ini = (string)preg_replace('/:\d+$/', '', $host_ini);
    if (str_starts_with($host_ref, 'www.')) $host_ref = substr($host_ref, 4);
    if (str_starts_with($host_ini, 'www.')) $host_ini = substr($host_ini, 4);

    return ($host_ref !== '' && $host_ref === $host_ini) ? 'klik' : 'random';
}

/** Tulis baris statistik ke file JSON lokal dengan kunci file. */
function statistik_ubah(callable $fn): bool {
    $path = statistik_json_path();
    $dir  = dirname($path);
    if (!is_dir($dir)) @mkdir($dir, 0775, true);

    $fp = @fopen($path, 'c+');
    if (!$fp) return false;
    if (!@flock($fp, LOCK_EX)) { @fclose($fp); return false; }

    $daftar = json_decode((string)stream_get_contents($fp), true);
    if (!is_array($daftar)) $daftar = [];

    $hasil = $fn($daftar);
    if ($hasil === false) { @flock($fp, LOCK_UN); @fclose($fp); return false; }

    @ftruncate($fp, 0);
    @rewind($fp);
    @fwrite($fp, json_encode(array_values($daftar), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
    @fflush($fp);
    @flock($fp, LOCK_UN);
    @fclose($fp);
    return true;
}

/**
 * Catat satu kunjungan halaman. Dipanggil otomatis sekali per request
 * dari functions.php. Hanya request GET publik yang dihitung.
 */
function statistik_catat(): void {
    static $sudah = false;
    if ($sudah) return;
    $sudah = true;

    if (PHP_SAPI === 'cli') return;
    if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'GET') return;

    $path = statistik_halaman_aktif();
    if (statistik_diabaikan($path)) return;
    if (statistik_bot()) return;

    $ua = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
    $ip = trim((string)explode(',', (string)($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ($_SERVER['REMOTE_ADDR'] ?? '')))[0]);
    $rujukan = substr(trim((string)($_SERVER['HTTP_REFERER'] ?? '')), 0, 500);
    $tanggal  = date('Y-m-d');

    $baris = [
        'tanggal'    => $tanggal,
        'halaman'    => substr($path, 0, 190),
        'rujukan'    => $rujukan,
        'sumber'     => statistik_sumber($rujukan),
        'pengunjung' => substr(hash_hmac('sha256', $ip . '|' . $ua . '|' . $tanggal, rahasia()), 0, 40),
    ];

    if (sb_aktif()) {
        $baris['waktu'] = date('c');
        sb_post('kunjungan', $baris, 'return=minimal');
        return;
    }

    $baris['waktu'] = time();
    statistik_ubah(function (array &$daftar) use ($baris) {
        $maks = 0;
        foreach ($daftar as $d) $maks = max($maks, (int)($d['id'] ?? 0));
        $baris['id'] = $maks + 1;
        $daftar[] = $baris;
        return true;
    });
}

/** Samakan bentuk baris Supabase / JSON lokal. */
function statistik_normalisasi(array $r): array {
    $tanggal = (string)($r['tanggal'] ?? '');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
        $ts = is_numeric($r['waktu'] ?? null) ? (int)$r['waktu'] : strtotime((string)($r['waktu'] ?? 'now'));
        $tanggal = date('Y-m-d', $ts ?: time());
    }

    return [
        'tanggal'    => $tanggal,
        'halaman'    => (string)($r['halaman'] ?? '/'),
        'rujukan'    => (string)($r['rujukan'] ?? ''),
        'sumber'     => ($r['sumber'] ?? '') === 'klik' ? 'klik' : 'random',
        'pengunjung' => (string)($r['pengunjung'] ?? ''),
    ];
}

/** Ambil data kunjungan N hari terakhir (default 60 hari). */
function statistik_data(int $hari = 60): array {
    $hari  = max(1, $hari);
    $mulai = date('Y-m-d', strtotime('-' . ($hari - 1) . ' days'));

    if (sb_aktif()) {
        $query = 'select=tanggal,halaman,rujukan,sumber,pengunjung'
               . '&tanggal=gte.' . rawurlencode($mulai)
               . '&order=tanggal.asc&limit=50000';
        $r = sb_request('GET', 'kunjungan', null, $query);
        if ($r['ok'] && is_array($r['data'])) {
            return array_map('statistik_normalisasi', $r['data']);
        }
        // Supabase gagal: coba fallback file lokal di bawah.
    }

    $path = statistik_json_path();
    if (!is_file($path)) return [];
    $isi = json_decode((string)file_get_contents($path), true);
    if (!is_array($isi)) return [];

    $hasil = [];
    foreach ($isi as $r) {
        $n = statistik_normalisasi((array)$r);
        if ($n['tanggal'] >= $mulai) $hasil[] = $n;
    }
    return $hasil;
}

/** Rekap harian N hari terakhir (termasuk hari ini). */
function statistik_harian(array $rows, int $jumlah = 7): array {
    $hasil = [];
    for ($i = $jumlah - 1; $i >= 0; $i--) {
        $t = date('Y-m-d', strtotime("-$i day"));
        $hasil[$t] = [
            'tanggal'      => $t,
            'views'        => 0,
            'random_views' => 0,
            'klik_views'   => 0,
            '_u'  => [],
            '_ru' => [],
            '_ku' => [],
        ];
    }

    foreach ($rows as $r) {
        $t = $r['tanggal'];
        if (!isset($hasil[$t])) continue;
        $p = $r['pengunjung'];

        $hasil[$t]['views']++;
        $hasil[$t]['_u'][$p] = 1;

        if ($r['sumber'] === 'klik') {
            $hasil[$t]['klik_views']++;
            $hasil[$t]['_ku'][$p] = 1;
        } else {
            $hasil[$t]['random_views']++;
            $hasil[$t]['_ru'][$p] = 1;
        }
    }

    foreach ($hasil as &$h) {
        $h['unik']         = count($h['_u']);
        $h['random_unik']  = count($h['_ru']);
        $h['klik_unik']    = count($h['_ku']);
        unset($h['_u'], $h['_ru'], $h['_ku']);
    }
    unset($h);

    return array_values($hasil);
}

/** Rekap mingguan N minggu terakhir (Senin–Minggu, termasuk minggu ini). */
function statistik_mingguan(array $rows, int $jumlah = 8): array {
    $hasil = [];
    $senin = strtotime('monday this week');

    for ($i = $jumlah - 1; $i >= 0; $i--) {
        $mulai = strtotime("-$i week", $senin);
        $kunci = date('Y-m-d', $mulai);
        $hasil[$kunci] = [
            'minggu'       => $kunci,
            'mulai'        => $kunci,
            'selesai'      => date('Y-m-d', strtotime('+6 days', $mulai)),
            'views'        => 0,
            'random_views' => 0,
            'klik_views'   => 0,
            '_u'  => [],
            '_ru' => [],
            '_ku' => [],
        ];
    }

    foreach ($rows as $r) {
        $ts = strtotime($r['tanggal'] . ' 12:00:00');
        if ($ts === false) continue;
        $kunci = date('Y-m-d', strtotime('monday this week', $ts));
        if (!isset($hasil[$kunci])) continue;
        $p = $r['pengunjung'];

        $hasil[$kunci]['views']++;
        $hasil[$kunci]['_u'][$p] = 1;

        if ($r['sumber'] === 'klik') {
            $hasil[$kunci]['klik_views']++;
            $hasil[$kunci]['_ku'][$p] = 1;
        } else {
            $hasil[$kunci]['random_views']++;
            $hasil[$kunci]['_ru'][$p] = 1;
        }
    }

    foreach ($hasil as &$h) {
        $h['unik']        = count($h['_u']);
        $h['random_unik'] = count($h['_ru']);
        $h['klik_unik']   = count($h['_ku']);
        unset($h['_u'], $h['_ru'], $h['_ku']);
    }
    unset($h);

    return array_values($hasil);
}

/** Halaman terpopuler. $sumber '' = semua, 'klik'/'random' = per klasifikasi. */
function statistik_halaman_populer(array $rows, int $limit = 8, string $sumber = ''): array {
    $agg = [];

    foreach ($rows as $r) {
        if ($sumber !== '' && $r['sumber'] !== $sumber) continue;
        $h = $r['halaman'] !== '' ? $r['halaman'] : '/';

        if (!isset($agg[$h])) $agg[$h] = ['halaman' => $h, 'views' => 0, '_u' => []];
        $agg[$h]['views']++;
        $agg[$h]['_u'][$r['pengunjung']] = 1;
    }

    foreach ($agg as &$a) {
        $a['unik'] = count($a['_u']);
        unset($a['_u']);
    }
    unset($a);

    usort($agg, fn($a, $b) => $b['views'] <=> $a['views']);
    return array_slice($agg, 0, $limit);
}

/** Ringkasan siap pakai untuk dashboard admin. */
function statistik_ringkas(int $hari = 60): array {
    $rows     = statistik_data($hari);
    $harian   = statistik_harian($rows, 7);
    $mingguan = statistik_mingguan($rows, 8);

    $total_unik = [];
    foreach ($rows as $r) $total_unik[$r['pengunjung']] = 1;

    return [
        'rows'       => $rows,
        'harian'     => $harian,
        'mingguan'   => $mingguan,
        'hari_ini'   => $harian[count($harian) - 1] ?? null,
        'minggu_ini' => $mingguan[count($mingguan) - 1] ?? null,
        'total_views'=> count($rows),
        'total_unik' => count($total_unik),
        'periode'    => $hari,
    ];
}

/** Nama halaman yang ramah dibaca. */
function statistik_nama_halaman(string $path): string {
    $peta = [
        '/' => 'Beranda',
        '/index.php' => 'Beranda',
        '/produk.php' => 'Detail Produk',
        '/keranjang.php' => 'Keranjang',
    ];
    if (isset($peta[$path])) return $peta[$path];
    return $path !== '' ? $path : 'Beranda';
}

/** Label tanggal ringkas: 22 Okt 2026. */
function statistik_label_tanggal(string $tanggal): string {
    $bulan = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $ts = strtotime($tanggal . ' 12:00:00');
    if ($ts === false) return $tanggal;
    return date('j', $ts) . ' ' . $bulan[(int)date('n', $ts)] . ' ' . date('Y', $ts);
}

/** Label minggu ringkas: 20–26 Okt 2026. */
function statistik_label_minggu(string $mulai, string $selesai): string {
    $bulan = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $a = strtotime($mulai . ' 12:00:00');
    $b = strtotime($selesai . ' 12:00:00');
    if ($a === false || $b === false) return $mulai;

    $teks = date('j', $a);
    if (date('n', $a) !== date('n', $b)) $teks .= ' ' . $bulan[(int)date('n', $a)];
    return $teks . '–' . date('j', $b) . ' ' . $bulan[(int)date('n', $b)] . ' ' . date('Y', $b);
}
