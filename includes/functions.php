<?php
/**
 * Fungsi umum Aldebaran3DPrinting.
 *
 * Keranjang dan token CSRF disimpan di cookie (bukan session PHP), karena
 * Vercel menjalankan PHP sebagai fungsi serverless tanpa penyimpanan session.
 * Cookie keranjang ditandatangani dengan APP_SECRET agar tidak bisa dipalsukan.
 */

/** Muat file .env di root proyek (untuk lokal). Env asli (Vercel) tidak ditimpa. */
function muat_env_lokal(): void {
    $path = dirname(__DIR__) . '/.env';
    if (!is_file($path)) return;
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $baris) {
        $baris = trim($baris);
        if ($baris === '' || $baris[0] === '#') continue;
        $eq = strpos($baris, '=');
        if ($eq === false) continue;
        $kunci = trim(substr($baris, 0, $eq));
        $nilai = trim(substr($baris, $eq + 1));
        // Buang tanda kutip ganda/satuan di sekeliling nilai.
        if (strlen($nilai) >= 2 && ($nilai[0] === '"' || $nilai[0] === "'") && $nilai[0] === $nilai[strlen($nilai) - 1]) {
            $nilai = substr($nilai, 1, -1);
        }
        if ($kunci !== '' && getenv($kunci) === false) {
            putenv("$kunci=$nilai");
            $_ENV[$kunci] = $nilai;
        }
    }
}
muat_env_lokal();

date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/supabase.php';
require_once __DIR__ . '/data.php';
require_once __DIR__ . '/cloudinary.php';
require_once __DIR__ . '/produk-store.php';
require_once __DIR__ . '/pesan-store.php';
require_once __DIR__ . '/sosmed-store.php';
require_once __DIR__ . '/statistik-store.php';
require_once __DIR__ . '/ongkir.php';

// Muat produk dari JSON admin (jika ada), else default dari data.php.
produk_muat_ke_global();

const NAMA_TOKO = 'Aldebaran3DPrinting';

/** Nomor WhatsApp toko (format internasional). Bisa diganti lewat env WHATSAPP_TOKO. */
const WHATSAPP_TOKO_DEFAULT = '6285694803867';

function whatsapp_nomor(): string {
    $n = preg_replace('/\D+/', '', (string)(getenv('WHATSAPP_TOKO') ?: WHATSAPP_TOKO_DEFAULT));
    if ($n === '') $n = WHATSAPP_TOKO_DEFAULT;
    if ($n[0] === '0')      $n = '62' . substr($n, 1);
    elseif ($n[0] === '8')  $n = '62' . $n;
    return $n;
}

/** Tautan wa.me; otomatis membuka WhatsApp Web / aplikasi. */
function wa_link(string $teks = ''): string {
    return 'https://wa.me/' . whatsapp_nomor() . ($teks !== '' ? '?text=' . rawurlencode($teks) : '');
}

function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

/** URL dasar situs tanpa garis miring di akhir (bisa ditimpa lewat env SITE_URL). */
function url_situs(): string {
    $env = trim((string)(getenv('SITE_URL') ?: ''));
    if ($env !== '') return rtrim($env, '/');
    $host  = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $skema = https_aktif() ? 'https' : 'http';
    return $skema . '://' . $host;
}

/** Ubah path lokal atau URL relatif menjadi URL absolut (wajib untuk og:image). */
function url_absolut(string $path): string {
    if (preg_match('#^https?://#i', $path)) return $path;
    return url_situs() . '/' . ltrim($path, '/');
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

/**
 * Bangun URL gambar produk.
 * - URL absolut (Cloudinary / http) dipakai apa adanya.
 * - Nama file lokal diarahkan ke /images/.
 */
function gambar_url(string $file): string {
    if (gambar_adalah_url($file)) return $file;
    return '/images/' . rawurlencode($file);
}

/* ---------- Cookie ---------- */

function rahasia(): string {
    $secret = (string)(getenv('APP_SECRET') ?: '');
    if ($secret !== '') return $secret;

    // Cadangan agar cookie login/keranjang tetap valid antar-deploy meski
    // APP_SECRET belum diisi: turunkan dari kunci Supabase yang stabil.
    $kunci = sb_key();
    if ($kunci !== '') return hash('sha256', 'aldebaran-cookie|' . $kunci);

    return 'ganti-rahasia-ini-lewat-env-APP_SECRET';
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

/** Kunci baris keranjang: id produk + warna bila produk punya pilihan warna. */
function keranjang_kunci(int $id, string $warna = ''): string {
    return $warna !== '' ? $id . ':' . $warna : (string)$id;
}

/** Pisahkan kunci keranjang menjadi [id produk, nama warna]. */
function keranjang_urai(string $kunci): array {
    $bagian = explode(':', $kunci, 2);
    return [(int)($bagian[0] ?? 0), trim((string)($bagian[1] ?? ''))];
}

/** Nama warna valid untuk produk; fallback ke warna pertama. */
function produk_warna_pilih(array $p, string $warna = ''): string {
    $daftar = $p['pilihan_warna'] ?? [];
    if (!is_array($daftar) || !$daftar) return '';

    foreach ($daftar as $w) {
        if (is_array($w) && strcasecmp((string)($w['nama'] ?? ''), $warna) === 0) {
            return (string)$w['nama'];
        }
    }

    // Kunci lama dari editor ("1", "Warna 1", "Warna 2", ...) cocokkan lewat urutan.
    if (preg_match('/^(?:Warna\s*)?(\d+)$/i', trim($warna), $cocok)) {
        $indeks = (int)$cocok[1] - 1;
        if (isset($daftar[$indeks]) && is_array($daftar[$indeks])) {
            return (string)($daftar[$indeks]['nama'] ?? '');
        }
    }

    $pertama = $daftar[0] ?? null;
    return is_array($pertama) ? (string)($pertama['nama'] ?? '') : '';
}

function keranjang(): array {
    $c = $_COOKIE['keranjang'] ?? '';
    [$data, $tanda] = array_pad(explode('.', $c, 2), 2, '');
    if ($data === '' || !hash_equals(hash_hmac('sha256', $data, rahasia()), $tanda)) return [];

    $arr = json_decode((string)base64_decode(strtr($data, '-_', '+/')), true);
    if (!is_array($arr)) return [];

    $bersih = [];
    foreach ($arr as $kunci => $qty) {
        [$id, $warna] = keranjang_urai((string)$kunci);
        $qty = (int)$qty;
        if ($qty < 1) continue;

        $p = cari_produk($id);
        if (!$p) continue;

        $warna = produk_warna_pilih($p, $warna);
        $kunci = keranjang_kunci($id, $warna);
        $bersih[$kunci] = min(99, ($bersih[$kunci] ?? 0) + $qty);
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
    foreach (keranjang() as $kunci => $qty) {
        [$id] = keranjang_urai((string)$kunci);
        $p = cari_produk($id);
        if ($p) $total += $p['harga'] * $qty;
    }
    return $total;
}

/**
 * Susun teks pesanan untuk WhatsApp dari isi keranjang.
 * $ongkir (opsional): ['kurir','layanan','tarif','destinasi','cod'] dari ongkir_pilihan().
 */
function pesan_checkout_whatsapp(?array $ongkir = null): string {
    $baris    = [];
    $subtotal = 0;
    $no       = 0;
    foreach (keranjang() as $kunci => $qty) {
        [$id, $warna] = keranjang_urai((string)$kunci);
        $p = cari_produk($id);
        if (!$p) continue;
        $no++;
        $sub       = $p['harga'] * $qty;
        $subtotal += $sub;
        $baris[]   = $no . '. ' . $p['nama'] . ' x' . $qty . ' = ' . rupiah($sub);
        if ($warna !== '') $baris[] = '   Warna yang dipilih : ' . $warna;
    }

    $lines   = [];
    $lines[] = 'Halo ' . NAMA_TOKO . '! Saya mau pesan:';
    $lines[] = '';
    $lines   = array_merge($lines, $baris ?: ['(keranjang kosong)']);
    $lines[] = '';
    $lines[] = 'Subtotal: ' . rupiah($subtotal);
    if ($ongkir) {
        $tujuan = !empty($ongkir['destinasi']) ? ' ke ' . $ongkir['destinasi'] : '';
        $tarif  = (int)$ongkir['tarif'];
        if (!empty($ongkir['cod'])) {
            $lines[] = 'Pengiriman: COD - ' . $ongkir['layanan'] . $tujuan;
            $lines[] = 'Ongkir COD: ' . ($tarif > 0 ? rupiah($tarif) : 'Gratis');
            $lines[] = 'Pembayaran: Cash on Delivery (COD) - bayar saat produk diterima';
            $lines[] = 'Total dibayar di tempat: ' . rupiah($subtotal + $tarif);
        } else {
            $lines[] = 'Ongkir (' . $ongkir['kurir'] . ' ' . $ongkir['layanan'] . $tujuan . '): ' . rupiah($tarif);
            $lines[] = 'Total: ' . rupiah($subtotal + $tarif);
        }
    } else {
        $lines[] = 'Ongkir: belum dipilih';
        $lines[] = 'Total: ' . rupiah($subtotal);
    }
    $lines[] = '';
    $lines[] = 'Nama: ';
    $lines[] = 'Alamat lengkap: ';
    return implode("\n", $lines);
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

/* Catat kunjungan halaman publik (sekali per request, GET saja). */
statistik_catat();
