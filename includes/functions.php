<?php
/**
 * Fungsi umum Aldebaran3DPrinting.
 *
 * Keranjang dan token CSRF disimpan di cookie (bukan session PHP), karena
 * Vercel menjalankan PHP sebagai fungsi serverless tanpa penyimpanan session.
 * Cookie keranjang ditandatangani dengan APP_SECRET agar tidak bisa dipalsukan.
 */
require_once __DIR__ . '/data.php';

const NAMA_TOKO = 'Aldebaran3DPrinting';

function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function rupiah(int $angka): string {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

/** Format angka ringkas: 8000 => 8 rb, 1500000 => 1,5 jt */
function ringkas(int $n): string {
    if ($n >= 1000000) return rtrim(rtrim(number_format($n / 1000000, 1, ',', ''), '0'), ',') . ' jt';
    if ($n >= 1000)    return rtrim(rtrim(number_format($n / 1000, 1, ',', ''), '0'), ',') . ' rb';
    return (string)$n;
}

function gambar_url(string $file): string {
    return '/images/' . rawurlencode($file);
}

/* ---------- Cookie ---------- */

function rahasia(): string {
    return getenv('APP_SECRET') ?: 'ganti-rahasia-ini-lewat-env-APP_SECRET';
}

function https_aktif(): bool {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

/** Simpan cookie. $detik <= 0 menghapus cookie. */
function set_cookie_aman(string $nama, string $nilai, int $detik): void {
    if (!headers_sent()) {
        setcookie($nama, $nilai, [
            'expires'  => $detik > 0 ? time() + $detik : time() - 3600,
            'path'     => '/',
            'secure'   => https_aktif(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
    if ($detik > 0) $_COOKIE[$nama] = $nilai; else unset($_COOKIE[$nama]);
}

/* ---------- Keranjang (cookie bertanda tangan) ---------- */

function keranjang(): array {
    $c = $_COOKIE['keranjang'] ?? '';
    [$data, $tanda] = array_pad(explode('.', $c, 2), 2, '');
    if ($data === '' || !hash_equals(hash_hmac('sha256', $data, rahasia()), $tanda)) return [];

    $arr = json_decode((string)base64_decode(strtr($data, '-_', '+/')), true);
    if (!is_array($arr)) return [];

    $bersih = [];
    foreach ($arr as $id => $qty) {
        $id = (int)$id; $qty = (int)$qty;
        if ($qty >= 1 && cari_produk($id)) $bersih[$id] = min(99, $qty);
    }
    return $bersih;
}

function simpan_keranjang(array $k): void {
    if (!$k) { set_cookie_aman('keranjang', '', 0); return; }
    $data  = rtrim(strtr(base64_encode(json_encode($k)), '+/', '-_'), '=');
    $tanda = hash_hmac('sha256', $data, rahasia());
    set_cookie_aman('keranjang', $data . '.' . $tanda, 60 * 60 * 24 * 7);
}

function jumlah_keranjang(): int {
    return array_sum(keranjang());
}

function total_keranjang(): int {
    $total = 0;
    foreach (keranjang() as $id => $qty) {
        $p = cari_produk((int)$id);
        if ($p) $total += $p['harga'] * $qty;
    }
    return $total;
}

/* ---------- CSRF (double-submit cookie) ---------- */

// Dibuat di awal permintaan, sebelum ada output, agar cookie bisa dikirim.
if (empty($_COOKIE['csrf']) || !preg_match('/^[a-f0-9]{32}$/', $_COOKIE['csrf'])) {
    set_cookie_aman('csrf', bin2hex(random_bytes(16)), 60 * 60 * 24);
}

function csrf_token(): string {
    return $_COOKIE['csrf'] ?? '';
}

function csrf_cek(): bool {
    return isset($_POST['csrf'], $_COOKIE['csrf'])
        && $_COOKIE['csrf'] !== ''
        && hash_equals($_COOKIE['csrf'], (string)$_POST['csrf']);
}
