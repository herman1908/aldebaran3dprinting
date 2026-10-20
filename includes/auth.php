<?php
/**
 * Autentikasi admin Aldebaran3DPrinting.
 *
 * Vercel serverless tidak menyimpan session PHP, jadi login memakai cookie
 * bertanda tangan (HMAC).
 *
 * Sumber password (berurutan):
 *   1. Hash di tabel Supabase `admin_akun` (bisa diganti dari /admin/password.php).
 *   2. Environment variable ADMIN_PASSWORD (cadangan / saat pertama kali).
 *
 * Token login bergantung pada identitas password + APP_SECRET. Selama
 * APP_SECRET (atau kunci Supabase) tetap, login bertahan antar-deploy.
 */

require_once __DIR__ . '/functions.php';

const ADMIN_COOKIE = 'admin_auth';
const ADMIN_LAMA   = 60 * 60 * 24 * 7; // 7 hari

/** Password dari environment (cadangan). */
function admin_env_password(): string {
    return (string)(getenv('ADMIN_PASSWORD') ?: '');
}

/** Hash password dari Supabase, atau null bila belum ada / DB nonaktif. */
function admin_password_db_hash(): ?string {
    if (array_key_exists('admin_hash_cache', $GLOBALS)) return $GLOBALS['admin_hash_cache'];

    $hash = null;
    if (sb_aktif()) {
        $baris = sb_get('admin_akun', 'select=password_hash&id=eq.1&limit=1');
        if ($baris && !empty($baris[0]['password_hash'])) {
            $hash = (string)$baris[0]['password_hash'];
        }
    }
    return $GLOBALS['admin_hash_cache'] = $hash;
}

/** Reset cache hash dalam satu request. */
function admin_password_reset_cache(): void {
    unset($GLOBALS['admin_hash_cache']);
}

/** True bila ada password aktif (database atau env). */
function admin_password_ada(): bool {
    return admin_password_db_hash() !== null || admin_env_password() !== '';
}

/** Sumber password aktif: 'database', 'env', atau '' (belum diatur). */
function admin_password_sumber(): string {
    if (admin_password_db_hash() !== null) return 'database';
    return admin_env_password() !== '' ? 'env' : '';
}

/** Identitas password untuk menandatangani cookie login. */
function admin_identitas(): string {
    $hash = admin_password_db_hash();
    if ($hash !== null) return 'db:' . $hash;
    $env = admin_env_password();
    return $env !== '' ? 'env:' . $env : '';
}

/** Token unik yang benar untuk admin saat ini. */
function admin_token(): string {
    return hash_hmac('sha256', 'admin:' . admin_identitas(), rahasia());
}

/** Verifikasi input password (database didahulukan, lalu env). */
function admin_login(string $pw): bool {
    $hash = admin_password_db_hash();
    if ($hash !== null) return password_verify($pw, $hash);

    $benar = admin_env_password();
    return $benar !== '' && hash_equals($benar, $pw);
}

/** true jika cookie admin valid. */
function admin_cek(): bool {
    return admin_password_ada()
        && isset($_COOKIE[ADMIN_COOKIE])
        && hash_equals(admin_token(), (string)$_COOKIE[ADMIN_COOKIE]);
}

/** Set cookie admin (login sukses). */
function admin_set_cookie(): void {
    set_cookie_aman(ADMIN_COOKIE, admin_token(), ADMIN_LAMA);
}

/** Hapus cookie admin (logout). */
function admin_hapus_cookie(): void {
    set_cookie_aman(ADMIN_COOKIE, '', 0);
}

/** Paksa login: hentikan eksekusi & redirect ke halaman login. */
function admin_wajib_login(): void {
    if (admin_cek()) return;
    header('Location: /admin/login.php');
    exit;
}

/**
 * Simpan password baru (hash) ke Supabase.
 * Mengembalikan false bila Supabase belum aktif atau penyimpanan gagal.
 */
function admin_password_simpan(string $pw): bool {
    if (!sb_aktif()) return false;

    $hash = password_hash($pw, PASSWORD_DEFAULT);
    if ($hash === false) return false;

    $r = sb_post(
        'admin_akun?on_conflict=id',
        ['id' => 1, 'password_hash' => $hash, 'diperbarui' => date('c')],
        'resolution=merge-duplicates,return=minimal'
    );

    admin_password_reset_cache();
    return $r['ok'];
}
