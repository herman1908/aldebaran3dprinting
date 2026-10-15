<?php
/**
 * Autentikasi admin Aldebaran3DPrinting.
 *
 * Karena Vercel serverless tidak menyimpan session PHP, login admin memakai
 * cookie bertanda tangan (HMAC) — pola sama dengan cookie keranjang & CSRF.
 *
 * Password admin diambil dari environment variable ADMIN_PASSWORD.
 * Jika kosong, panel admin dinonaktifkan (selalu redirect ke login).
 */

require_once __DIR__ . '/functions.php';

const ADMIN_COOKIE = 'admin_auth';
const ADMIN_LAMA   = 60 * 60 * 24 * 7; // 7 hari

function admin_password(): string {
    return (string)(getenv('ADMIN_PASSWORD') ?: '');
}

/** Token unik yang benar untuk admin saat ini (bergantung password + secret). */
function admin_token(): string {
    return hash_hmac('sha256', 'admin:' . admin_password(), rahasia());
}

/** Verifikasi input password terhadap ADMIN_PASSWORD. */
function admin_login(string $pw): bool {
    $benar = admin_password();
    return $benar !== '' && hash_equals($benar, $pw);
}

/** true jika cookie admin valid. */
function admin_cek(): bool {
    return admin_password() !== ''
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
