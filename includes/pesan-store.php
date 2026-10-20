<?php
/**
 * Penyimpanan pesan/chat konsumen.
 *
 * - Supabase aktif: disimpan di tabel `pesan` (persisten di Vercel).
 * - Tanpa Supabase: fallback ke data/pesan.json (lokal).
 */

require_once __DIR__ . '/supabase.php';

/** Path file JSON pesan (fallback lokal). */
function pesan_json_path(): string {
    return dirname(__DIR__) . '/data/pesan.json';
}

/** Reset cache pesan dalam satu request. */
function pesan_cache_reset(): void {
    unset($GLOBALS['pesan_cache']);
}

/** Masa simpan pesan: 7 hari. Pesan yang lebih lama dihapus otomatis. */
const PESAN_MASA_SIMPAN = 7 * 24 * 60 * 60;

/** Batas waktu pesan tertua yang masih disimpan (ISO 8601 UTC). */
function pesan_batas_iso(): string {
    return gmdate('Y-m-d\TH:i:s\Z', time() - PESAN_MASA_SIMPAN);
}

/**
 * Hapus pesan yang umurnya sudah lewat 7 hari.
 * Dipanggil sekali per request (dijaga static) agar tidak membebani database.
 */
function pesan_bersihkan_lama(): void {
    static $sudah = false;
    if ($sudah) return;
    $sudah = true;

    $batas = time() - PESAN_MASA_SIMPAN;

    if (sb_aktif()) {
        $r = sb_delete('pesan', 'waktu=lt.' . rawurlencode(pesan_batas_iso()));
        if ($r['ok']) pesan_cache_reset();
        return;
    }

    $path = pesan_json_path();
    if (!is_file($path)) return;

    $isi = json_decode((string)file_get_contents($path), true);
    if (!is_array($isi)) return;

    $ada_lama = false;
    foreach ($isi as $m) {
        if ((int)($m['waktu'] ?? 0) < $batas) { $ada_lama = true; break; }
    }
    if (!$ada_lama) return;

    pesan_ubah(function (array &$daftar) use ($batas) {
        $daftar = array_values(array_filter(
            $daftar,
            fn($m) => (int)($m['waktu'] ?? 0) >= $batas
        ));
        return true;
    });
}

/** Baris Supabase -> format aplikasi. */
function pesan_db_ke_app(array $m): array {
    $waktu = $m['waktu'] ?? null;
    if (is_numeric($waktu)) {
        $ts = (int)$waktu;
    } elseif (is_string($waktu) && $waktu !== '') {
        $ts = (int)strtotime($waktu);
    } else {
        $ts = time();
    }
    return [
        'id'     => (int)($m['id'] ?? 0),
        'nama'   => (string)($m['nama'] ?? ''),
        'hp'     => (string)($m['hp'] ?? ''),
        'email'  => (string)($m['email'] ?? ''),
        'pesan'  => (string)($m['pesan'] ?? ''),
        'waktu'  => $ts,
        'dibaca' => !empty($m['dibaca']),
    ];
}

/** Ambil semua pesan (array of assoc). */
function pesan_semua(): array {
    if (array_key_exists('pesan_cache', $GLOBALS)) return $GLOBALS['pesan_cache'];

    pesan_bersihkan_lama();

    $batas = time() - PESAN_MASA_SIMPAN;

    if (sb_aktif()) {
        $r = sb_request('GET', 'pesan', null, 'select=*&order=waktu.desc,id.desc');
        if ($r['ok'] && is_array($r['data'])) {
            $daftar = array_map('pesan_db_ke_app', $r['data']);
            $daftar = array_values(array_filter($daftar, fn($m) => $m['waktu'] >= $batas));
            return $GLOBALS['pesan_cache'] = $daftar;
        }
        // Supabase bermasalah: pakai file lokal bila ada.
    }

    $path = pesan_json_path();
    if (!is_file($path)) return $GLOBALS['pesan_cache'] = [];
    $isi = json_decode((string)file_get_contents($path), true);
    if (!is_array($isi)) return $GLOBALS['pesan_cache'] = [];
    $isi = array_values(array_filter($isi, fn($m) => (int)($m['waktu'] ?? 0) >= $batas));
    return $GLOBALS['pesan_cache'] = $isi;
}

/**
 * Ubah isi file pesan dalam transaksi terkunci (mode lokal). $fn menerima &$daftar.
 */
function pesan_ubah(callable $fn): bool {
    $path = pesan_json_path();
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
    @fwrite($fp, json_encode(array_values($daftar), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
    @fflush($fp);
    @flock($fp, LOCK_UN);
    @fclose($fp);
    pesan_cache_reset();
    return true;
}

/** Simpan pesan baru; kembalikan id yang dibuat, atau 0 jika gagal. */
function pesan_tambah(array $data): int {
    $baru = [
        'nama'   => trim((string)($data['nama'] ?? '')),
        'hp'     => trim((string)($data['hp'] ?? '')),
        'email'  => trim((string)($data['email'] ?? '')),
        'pesan'  => trim((string)($data['pesan'] ?? '')),
        'dibaca' => false,
    ];
    if ($baru['pesan'] === '') return 0;

    pesan_bersihkan_lama();

    if (sb_aktif()) {
        $baru['waktu'] = date('c');
        $r = sb_post('pesan', $baru, 'return=representation');
        pesan_cache_reset();
        if (!$r['ok'] || !is_array($r['data']) || !$r['data']) return 0;
        return (int)($r['data'][0]['id'] ?? 0);
    }

    $baru['id']    = 0;
    $baru['waktu'] = time();
    $baru_id = 0;
    $ok = pesan_ubah(function (array &$daftar) use ($baru, &$baru_id) {
        $maks = 0;
        foreach ($daftar as $m) $maks = max($maks, (int)($m['id'] ?? 0));
        $baru['id'] = $maks + 1;
        $baru_id    = $baru['id'];
        $daftar[]   = $baru;
        return true;
    });
    return $ok ? $baru_id : 0;
}

/** Cari satu pesan berdasarkan id. */
function pesan_cari(int $id): ?array {
    foreach (pesan_semua() as $m) {
        if ((int)($m['id'] ?? 0) === $id) return $m;
    }
    return null;
}

/** Tandai pesan sudah / belum dibaca. */
function pesan_set_dibaca(int $id, bool $baca): bool {
    if (sb_aktif()) {
        $r = sb_patch('pesan', 'id=eq.' . $id, ['dibaca' => $baca]);
        pesan_cache_reset();
        return $r['ok'] && is_array($r['data']) && count($r['data']) > 0;
    }

    return pesan_ubah(function (array &$daftar) use ($id, $baca) {
        foreach ($daftar as $i => $m) {
            if ((int)($m['id'] ?? 0) === $id) { $daftar[$i]['dibaca'] = $baca; return true; }
        }
        return false;
    });
}

/** Tandai semua pesan sudah dibaca (satu operasi). */
function pesan_tandai_semua_dibaca(): bool {
    if (sb_aktif()) {
        $r = sb_patch('pesan', 'dibaca=eq.false', ['dibaca' => true]);
        pesan_cache_reset();
        return $r['ok'];
    }

    return pesan_ubah(function (array &$daftar) {
        foreach ($daftar as $i => $m) $daftar[$i]['dibaca'] = true;
        return true;
    });
}

/** Hapus pesan berdasarkan id. */
function pesan_hapus(int $id): bool {
    if (sb_aktif()) {
        $r = sb_delete('pesan', 'id=eq.' . $id);
        pesan_cache_reset();
        return $r['ok'] && is_array($r['data']) && count($r['data']) > 0;
    }

    return pesan_ubah(function (array &$daftar) use ($id) {
        $sebelum = count($daftar);
        $daftar = array_values(array_filter($daftar, fn($m) => (int)($m['id'] ?? 0) !== $id));
        return count($daftar) !== $sebelum;
    });
}

/** Jumlah pesan yang belum dibaca. */
function pesan_belum_dibaca(): int {
    pesan_bersihkan_lama();

    if (sb_aktif()) {
        $r = sb_request('GET', 'pesan', null, 'select=id&dibaca=eq.false&waktu=gte.' . rawurlencode(pesan_batas_iso()));
        if ($r['ok'] && is_array($r['data'])) return count($r['data']);
        // gagal -> cek lokal di bawah
    }

    $n = 0;
    foreach (pesan_semua() as $m) if (empty($m['dibaca'])) $n++;
    return $n;
}

/** Ubah nomor HP apa pun jadi tautan wa.me (normalkan ke format 62...). */
function kontak_wa_link(string $hp, string $teks = ''): string {
    $n = preg_replace('/\D+/', '', $hp);
    if ($n === '') return '';
    if ($n[0] === '0')     $n = '62' . substr($n, 1);
    elseif ($n[0] === '8') $n = '62' . $n;
    return 'https://wa.me/' . $n . ($teks !== '' ? '?text=' . rawurlencode($teks) : '');
}

/** Format waktu ringkas berbahasa Indonesia: 19 Okt 2026, 14:35 */
function waktu_ringkas(int $ts): string {
    $bulan = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    return date('j', $ts) . ' ' . $bulan[(int)date('n', $ts)] . ' ' . date('Y', $ts) . ', ' . date('H:i', $ts);
}
