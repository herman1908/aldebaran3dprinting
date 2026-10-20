<?php
/**
 * Media sosial toko.
 *
 * - Supabase aktif: disimpan di tabel `sosmed` (persisten, bisa diedit online).
 * - Tanpa Supabase: fallback ke data/sosmed.json (lokal).
 */

require_once __DIR__ . '/supabase.php';

/** Path file JSON media sosial (fallback lokal). */
function sosmed_json_path(): string {
    return dirname(__DIR__) . '/data/sosmed.json';
}

/** Reset cache sosmed dalam satu request. */
function sosmed_cache_reset(): void {
    unset($GLOBALS['sosmed_cache']);
}

/** Daftar platform yang didukung beserta ikon SVG-nya. */
function sosmed_platform(): array {
    static $p = null;
    if ($p !== null) return $p;

    return $p = [
        'instagram' => ['label' => 'Instagram', 'ikon' =>
            '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg>'],
        'tiktok' => ['label' => 'TikTok', 'ikon' =>
            '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true"><path d="M16 3c.3 1.9 1.5 3.3 3.4 3.6v2.4c-1.3 0-2.5-.4-3.5-1v5.8a5.6 5.6 0 1 1-5.6-5.6c.3 0 .6 0 .9.1v2.5a3.1 3.1 0 1 0 2.2 3V3H16z"/></svg>'],
        'facebook' => ['label' => 'Facebook', 'ikon' =>
            '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true"><path d="M14 9h3V6h-3c-2.2 0-4 1.8-4 4v2H7v3h3v6h3v-6h3l1-3h-4v-2c0-.6.4-1 1-1z"/></svg>'],
        'whatsapp' => ['label' => 'WhatsApp', 'ikon' =>
            '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm0 18a8 8 0 0 1-4.1-1.1l-.3-.2-3 .8.8-2.9-.2-.3A8 8 0 1 1 12 20zm4.5-5.9c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1-.2.2-.6.8-.8 1-.1.2-.3.2-.5.1-.2-.1-1-.4-2-1.2-.7-.7-1.2-1.5-1.4-1.8-.1-.2 0-.3.1-.5l.4-.5c.1-.2.2-.3.3-.5.1-.2 0-.4 0-.5 0-.1-.6-1.5-.8-2-.2-.5-.4-.4-.6-.4h-.5c-.2 0-.5.1-.7.3-.2.2-.9.9-.9 2.2 0 1.3 1 2.6 1.1 2.7.1.2 1.9 3 4.6 4.1.6.3 1.1.4 1.5.6.6.2 1.2.2 1.6.1.5-.1 1.5-.6 1.7-1.2.2-.6.2-1.1.1-1.2-.1-.1-.2-.2-.4-.3z"/></svg>'],
        'youtube' => ['label' => 'YouTube', 'ikon' =>
            '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true"><path d="M21.6 7.2a2.5 2.5 0 0 0-1.8-1.8C18.2 5 12 5 12 5s-6.2 0-7.8.4A2.5 2.5 0 0 0 2.4 7.2 26 26 0 0 0 2 12a26 26 0 0 0 .4 4.8 2.5 2.5 0 0 0 1.8 1.8C5.8 19 12 19 12 19s6.2 0 7.8-.4a2.5 2.5 0 0 0 1.8-1.8A26 26 0 0 0 22 12a26 26 0 0 0-.4-4.8zM10 15V9l5 3-5 3z"/></svg>'],
        'twitter' => ['label' => 'X / Twitter', 'ikon' =>
            '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true"><path d="M17.5 3h3l-6.6 7.5L21.7 21h-6.1l-4.8-6.2L5.3 21H2.3l7-8L2 3h6.2l4.3 5.7L17.5 3zm-1.1 16h1.7L7.6 4.7H5.8L16.4 19z"/></svg>'],
        'telegram' => ['label' => 'Telegram', 'ikon' =>
            '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true"><path d="M21.9 4.3 18.7 19c-.2 1-.9 1.3-1.8.8l-4.9-3.6-2.4 2.3c-.3.3-.5.5-1 .5l.4-5 9.1-8.2c.4-.4-.1-.6-.6-.2L6.2 12.9 1.4 11.4c-1-.3-1-1 .2-1.5l19.1-7.4c.9-.3 1.6.2 1.2 1.7z"/></svg>'],
        'shopee' => ['label' => 'Shopee', 'ikon' =>
            '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 7h12l-1 13H7L6 7z"/><path d="M9 7a3 3 0 0 1 6 0"/></svg>'],
        'tokopedia' => ['label' => 'Tokopedia', 'ikon' =>
            '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 7h12l-1 13H7L6 7z"/><path d="M9 7a3 3 0 0 1 6 0"/></svg>'],
        'website' => ['label' => 'Website', 'ikon' =>
            '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18"/></svg>'],
        'email' => ['label' => 'Email', 'ikon' =>
            '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>'],
    ];
}

/** Sumber data awal: JSON lokal, else WhatsApp toko. */
function sosmed_sumber_awal(): array {
    $path = sosmed_json_path();
    if (is_file($path)) {
        $isi = json_decode((string)file_get_contents($path), true);
        if (is_array($isi)) return $isi;
    }
    return [[
        'id'       => 1,
        'platform' => 'whatsapp',
        'label'    => 'WhatsApp',
        'url'      => 'https://wa.me/' . whatsapp_nomor(),
    ]];
}

/** Baris Supabase -> format aplikasi. */
function sosmed_db_ke_app(array $s): array {
    $platform = sosmed_platform();
    $p = (string)($s['platform'] ?? 'website');
    if (!isset($platform[$p])) $p = 'website';
    return [
        'id'       => (int)($s['id'] ?? 0),
        'platform' => $p,
        'label'    => (string)($s['label'] ?? ''),
        'url'      => (string)($s['url'] ?? ''),
    ];
}

/** Format aplikasi -> baris Supabase. */
function sosmed_app_ke_db(array $s): array {
    return [
        'id'       => (int)($s['id'] ?? 0),
        'platform' => (string)($s['platform'] ?? 'website'),
        'label'    => (string)($s['label'] ?? ''),
        'url'      => (string)($s['url'] ?? ''),
    ];
}

/** Ambil daftar media sosial (dinormalisasi). */
function sosmed_semua(): array {
    if (array_key_exists('sosmed_cache', $GLOBALS)) return $GLOBALS['sosmed_cache'];

    if (sb_aktif()) {
        $r = sb_request('GET', 'sosmed', null, 'select=*&order=id.asc');

        if (!$r['ok']) {
            return $GLOBALS['sosmed_cache'] = array_map('sosmed_db_ke_app', sosmed_sumber_awal());
        }

        $baris = is_array($r['data']) ? $r['data'] : [];
        if ($baris) {
            return $GLOBALS['sosmed_cache'] = array_map('sosmed_db_ke_app', $baris);
        }

        if (sb_pengaturan('seed_sosmed') !== '1') {
            $awal = sosmed_sumber_awal();
            if ($awal) {
                $kirim = array_map('sosmed_app_ke_db', $awal);
                $seed  = sb_post('sosmed?on_conflict=id', $kirim, 'resolution=ignore-duplicates,return=minimal');
                if ($seed['ok']) {
                    sb_pengaturan_set('seed_sosmed', '1');
                    sosmed_cache_reset();
                    return sosmed_semua();
                }
                return $GLOBALS['sosmed_cache'] = array_map('sosmed_db_ke_app', $awal);
            }
            sb_pengaturan_set('seed_sosmed', '1');
        }

        return $GLOBALS['sosmed_cache'] = [];
    }

    $path = sosmed_json_path();
    if (!is_file($path)) return $GLOBALS['sosmed_cache'] = [];
    $isi = json_decode((string)file_get_contents($path), true);
    if (!is_array($isi)) return $GLOBALS['sosmed_cache'] = [];

    $out = [];
    foreach ($isi as $s) {
        if (is_array($s)) $out[] = sosmed_db_ke_app($s);
    }
    return $GLOBALS['sosmed_cache'] = $out;
}

/** Ubah file sosmed dalam transaksi terkunci (mode lokal). */
function sosmed_ubah(callable $fn): bool {
    $path = sosmed_json_path();
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
    sosmed_cache_reset();
    return true;
}

/** Id berikutnya (maks + 1). */
function sosmed_id_baru(): int {
    $maks = 0;
    foreach (sosmed_semua() as $s) $maks = max($maks, (int)($s['id'] ?? 0));
    return $maks + 1;
}

/** Simpan (tambah/ubah) satu media sosial. */
function sosmed_simpan(array $data): bool {
    $id       = (int)($data['id'] ?? 0);
    $platform = (string)($data['platform'] ?? 'website');
    if (!isset(sosmed_platform()[$platform])) return false;

    $label = trim((string)($data['label'] ?? ''));
    if ($label === '') $label = sosmed_platform()[$platform]['label'];
    $url = trim((string)($data['url'] ?? ''));
    if ($url === '') return false;

    $baris = ['id' => $id, 'platform' => $platform, 'label' => $label, 'url' => $url];

    if (sb_aktif()) {
        if ($id > 0) {
            $r = sb_patch('sosmed', 'id=eq.' . $id, $baris);
            sosmed_cache_reset();
            return $r['ok'] && is_array($r['data']) && count($r['data']) > 0;
        }

        $baris['id'] = sosmed_id_baru();
        $r = sb_post('sosmed', $baris, 'return=representation');
        if (!$r['ok'] && $r['status'] === 409) {
            sosmed_cache_reset();
            $baris['id'] = sosmed_id_baru();
            $r = sb_post('sosmed', $baris, 'return=representation');
        }
        sosmed_cache_reset();
        return $r['ok'];
    }

    return sosmed_ubah(function (array &$daftar) use ($id, $baris) {
        if ($id > 0) {
            foreach ($daftar as $i => $s) {
                if ((int)($s['id'] ?? 0) === $id) {
                    $daftar[$i] = $baris;
                    return true;
                }
            }
        }
        $maks = 0;
        foreach ($daftar as $s) $maks = max($maks, (int)($s['id'] ?? 0));
        $baris['id'] = $maks + 1;
        $daftar[] = $baris;
        return true;
    });
}

/** Cari satu media sosial berdasarkan id. */
function sosmed_cari(int $id): ?array {
    foreach (sosmed_semua() as $s) {
        if ((int)$s['id'] === $id) return $s;
    }
    return null;
}

/** Hapus media sosial. */
function sosmed_hapus(int $id): bool {
    if (sb_aktif()) {
        $r = sb_delete('sosmed', 'id=eq.' . $id);
        sosmed_cache_reset();
        return $r['ok'] && is_array($r['data']) && count($r['data']) > 0;
    }

    return sosmed_ubah(function (array &$daftar) use ($id) {
        $sebelum = count($daftar);
        $daftar = array_values(array_filter($daftar, fn($s) => (int)($s['id'] ?? 0) !== $id));
        return count($daftar) !== $sebelum;
    });
}

/** Ikon SVG untuk platform (fallback ikon tautan). */
function sosmed_ikon(string $platform): string {
    $p = sosmed_platform();
    if (isset($p[$platform])) return $p[$platform]['ikon'];
    return '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7 0l2-2a5 5 0 0 0-7-7l-1 1"/><path d="M14 11a5 5 0 0 0-7 0l-2 2a5 5 0 0 0 7 7l1-1"/></svg>';
}

/** Normalisasi URL: tambah skema bila belum ada. */
function sosmed_normal_url(string $platform, string $url): string {
    $url = trim($url);
    if ($url === '') return '';
    if (preg_match('#^(https?://|mailto:|tel:)#i', $url)) return $url;
    if ($platform === 'email') return 'mailto:' . $url;
    return 'https://' . ltrim($url, '/');
}

/** Validasi URL media sosial (http/https, mailto, atau tel). */
function sosmed_url_valid(string $url): bool {
    if (preg_match('#^(mailto:|tel:)#i', $url)) return true;
    return filter_var($url, FILTER_VALIDATE_URL) !== false
        && preg_match('#^https?://#i', $url) === 1;
}
