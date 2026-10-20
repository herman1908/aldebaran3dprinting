<?php
/**
 * Klien Supabase (PostgREST) minimal tanpa library tambahan.
 *
 * Aktif bila SUPABASE_URL dan SUPABASE_SERVICE_KEY diisi. Kunci service_role
 * hanya dipakai di server (PHP) dan tidak pernah dikirim ke browser.
 *
 * Semua fungsi mengembalikan hasil terstruktur dan tidak melempar exception:
 *   ['ok' => bool, 'status' => int, 'data' => array|null, 'error' => string]
 */

function sb_url(): string {
    return rtrim((string)(getenv('SUPABASE_URL') ?: ''), '/');
}

function sb_key(): string {
    foreach (['SUPABASE_SERVICE_KEY', 'SUPABASE_SERVICE_ROLE_KEY'] as $nama) {
        $nilai = (string)(getenv($nama) ?: '');
        if ($nilai !== '') return $nilai;
    }
    return '';
}

function sb_aktif(): bool {
    return sb_url() !== '' && sb_key() !== '';
}

/**
 * Panggil PostgREST. $path relatif terhadap /rest/v1 (mis. "produk" atau "rpc/nama").
 * $extra berisi header tambahan, mis. ['Prefer: return=representation'].
 */
function sb_request(string $method, string $path, ?array $body = null, string $query = '', array $extra = []): array {
    $hasil = ['ok' => false, 'status' => 0, 'data' => null, 'error' => ''];

    if (!sb_aktif()) {
        $hasil['error'] = 'Supabase belum dikonfigurasi.';
        return $hasil;
    }

    $url = sb_url() . '/rest/v1/' . ltrim($path, '/');
    if ($query !== '') $url .= '?' . $query;

    $json = null;
    if ($body !== null) {
        $json = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            $hasil['error'] = 'Gagal menyiapkan data JSON.';
            return $hasil;
        }
    }

    $headers = array_merge([
        'apikey: ' . sb_key(),
        'Authorization: Bearer ' . sb_key(),
        'Content-Type: application/json',
        'Accept: application/json',
    ], $extra);

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_FOLLOWLOCATION => false,
        ]);
        if ($json !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        $mentah = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $galat  = curl_error($ch);
        curl_close($ch);
        if ($mentah === false) {
            $hasil['error'] = $galat !== '' ? $galat : 'Koneksi ke Supabase gagal.';
            return $hasil;
        }
    } else {
        $konteks = stream_context_create(['http' => [
            'method'        => $method,
            'header'        => implode("\r\n", $headers),
            'content'       => $json ?? '',
            'timeout'       => 10,
            'ignore_errors' => true,
        ]]);
        $mentah = @file_get_contents($url, false, $konteks);
        $status = 0;
        if (!empty($http_response_header[0]) && preg_match('#\s(\d{3})\s#', $http_response_header[0], $m)) {
            $status = (int)$m[1];
        }
        if ($mentah === false) {
            $hasil['error'] = 'Koneksi ke Supabase gagal.';
            return $hasil;
        }
    }

    $data = $mentah === '' ? null : json_decode($mentah, true);

    $hasil['status'] = $status;
    $hasil['data']   = $data;
    $hasil['ok']     = $status >= 200 && $status < 300;

    if (!$hasil['ok']) {
        if (is_array($data) && isset($data['message'])) {
            $hasil['error'] = (string)$data['message'];
        } elseif (is_string($data) && $data !== '') {
            $hasil['error'] = $data;
        } else {
            $hasil['error'] = 'HTTP ' . $status;
        }
    }

    return $hasil;
}

/** Ambil baris (array asosiatif). Kosong bila gagal. */
function sb_get(string $tabel, string $query = ''): array {
    $r = sb_request('GET', $tabel, null, $query);
    return $r['ok'] && is_array($r['data']) ? $r['data'] : [];
}

/** Insert satu/beberapa baris. $prefer mis. "return=representation". */
function sb_post(string $tabel, array $baris, string $prefer = 'return=representation', string $query = ''): array {
    return sb_request('POST', $tabel, $baris, $query, ['Prefer: ' . $prefer]);
}

/** Update baris sesuai filter $query. */
function sb_patch(string $tabel, string $query, array $baris): array {
    return sb_request('PATCH', $tabel, $baris, $query, ['Prefer: return=representation']);
}

/** Hapus baris sesuai filter $query. */
function sb_delete(string $tabel, string $query): array {
    return sb_request('DELETE', $tabel, null, $query, ['Prefer: return=representation']);
}

/** Panggil fungsi SQL (RPC). */
function sb_rpc(string $fungsi, array $argumen): array {
    return sb_request('POST', 'rpc/' . $fungsi, $argumen, '', ['Prefer: return=minimal']);
}

/** Normalisasi nilai jsonb/teks menjadi array PHP. */
function sb_json_arr($nilai): array {
    if (is_array($nilai)) return $nilai;
    if (is_string($nilai) && $nilai !== '') {
        $data = json_decode($nilai, true);
        if (is_array($data)) return $data;
    }
    return [];
}

/* ---------- Tabel pengaturan (flag seed, dll.) ---------- */

function sb_pengaturan(string $kunci): ?string {
    $baris = sb_get('pengaturan', 'select=nilai&kunci=eq.' . rawurlencode($kunci) . '&limit=1');
    if (!$baris) return null;
    return (string)($baris[0]['nilai'] ?? '');
}

function sb_pengaturan_set(string $kunci, string $nilai): bool {
    $r = sb_post(
        'pengaturan?on_conflict=kunci',
        ['kunci' => $kunci, 'nilai' => $nilai],
        'resolution=merge-duplicates,return=minimal'
    );
    return $r['ok'];
}
