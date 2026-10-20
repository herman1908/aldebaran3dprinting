<?php
/**
 * Pintu masuk tunggal untuk Vercel (runtime vercel-php).
 * Semua permintaan non-statis diarahkan ke sini oleh vercel.json,
 * lalu diteruskan ke halaman PHP yang sesuai.
 *
 * Untuk uji lokal: php -S localhost:8000 -t public api/index.php
 */
$akar  = dirname(__DIR__);
$path  = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');

// Server bawaan PHP (lokal): sajikan file statis dari folder public/ langsung.
if (PHP_SAPI === 'cli-server') {
    $publik = realpath($akar . '/public');
    $berkas = realpath($akar . '/public' . $path);
    if ($berkas && is_file($berkas) && str_starts_with($berkas, $publik)) {
        return false;
    }
}

$halaman = [
    '/'               => 'index.php',
    '/index.php'      => 'index.php',
    '/produk.php'     => 'produk.php',
    '/keranjang.php'  => 'keranjang.php',

    // Panel admin.
    '/admin'          => 'admin/index.php',
    '/admin/'         => 'admin/index.php',
    '/admin/index.php'=> 'admin/index.php',
    '/admin/login.php'=> 'admin/login.php',
    '/admin/logout.php'=> 'admin/logout.php',
    '/admin/edit.php' => 'admin/edit.php',
    '/admin/pesan.php'=> 'admin/pesan.php',
    '/admin/sosmed.php'=> 'admin/sosmed.php',
    '/admin/password.php'=> 'admin/password.php',
    '/admin/status.php'=> 'admin/status.php',
];

// Izinkan URL admin tanpa ekstensi .php (mis. /admin/status).
if (!isset($halaman[$path]) && preg_match('#^/admin/([a-z0-9_-]+)$#', $path, $m)) {
    $kandidat = 'admin/' . $m[1] . '.php';
    if (is_file($akar . '/' . $kandidat)) {
        $halaman[$path] = $kandidat;
    }
}

chdir($akar);

if (isset($halaman[$path])) {
    require $akar . '/' . $halaman[$path];
    return;
}

// Halaman tidak ditemukan
require_once $akar . '/includes/functions.php';
http_response_code(404);
$judul_halaman = 'Halaman tidak ditemukan';
$menu_aktif = '';
require $akar . '/includes/header.php';
?>
<div class="kosong">
    <img src="/images/aldi-maskot.svg" alt="" width="90" height="90">
    <p>Halaman yang kamu cari tidak ada. Mungkin Aldi salah jalan di luar angkasa.</p>
    <a class="tombol" href="/">Kembali ke beranda</a>
</div>
<?php require $akar . '/includes/footer.php';
