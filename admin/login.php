<?php
/**
 * Login panel admin.
 */
require_once __DIR__ . '/../includes/auth.php';

// Sudah login? langsung ke dashboard.
if (admin_cek()) {
    header('Location: /admin/');
    exit;
}

$galat  = '';
$password_terisi = admin_password() !== '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_cek()) {
        $galat = 'Token keamanan tidak valid. Muat ulang halaman lalu coba lagi.';
    } elseif (!$password_terisi) {
        $galat = 'ADMIN_PASSWORD belum diatur di environment variables.';
    } else {
        $pw = (string)($_POST['password'] ?? '');
        if (admin_login($pw)) {
            admin_set_cookie();
            header('Location: /admin/');
            exit;
        }
        $galat = 'Password salah.';
    }
}

$judul_halaman = 'Login Admin';
require __DIR__ . '/../includes/admin-head.php';
?>
<style>
    .admin-main{min-height:70vh;display:grid;place-items:center;padding:24px}
    .kartu-login{width:min(400px,100%);background:rgba(255,255,255,.7);-webkit-backdrop-filter:blur(18px);backdrop-filter:blur(18px);border:1.5px solid rgba(255,255,255,.7);border-radius:26px;padding:32px 28px;box-shadow:0 12px 40px rgba(75,75,110,.14), inset 0 1px 0 rgba(255,255,255,.9)}
    .kartu-login h1{font-size:24px;font-weight:800;margin-bottom:6px}
    .kartu-login p.sub{margin:0 0 22px;color:var(--muted);font-size:14px}
    .kartu-login label{display:block;font-weight:800;font-size:13px;margin-bottom:6px}
    .kartu-login input[type=password]{width:100%;padding:12px 16px;border-radius:14px;border:var(--bd);background:var(--bg);box-shadow:var(--sh-brutal-in);font:inherit;font-size:15px;margin-bottom:16px}
    .kartu-login .tombol{width:100%}
    .galat-login{background:#FFE9E7;color:#B3261E;border:2px solid #fff;border-radius:14px;padding:11px 14px;font-size:13.5px;font-weight:700;margin-bottom:16px;box-shadow:var(--sh-brutal-sm)}
    .info-env{background:var(--sand);color:#7A6030;border:2px dashed #EFD48A;border-radius:14px;padding:11px 14px;font-size:12.5px;font-weight:700;margin-top:16px}
</style>

<form class="kartu-login" method="post" autocomplete="off">
    <h1>Login Admin</h1>
    <p class="sub">Panel kelola produk Aldebaran3DPrinting.</p>

    <?php if ($galat !== ''): ?>
        <div class="galat-login"><?= e($galat) ?></div>
    <?php endif; ?>

    <label for="password">Password</label>
    <input type="password" id="password" name="password" required autofocus placeholder="••••••••">

    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <button type="submit" class="tombol besar">Masuk</button>

    <?php if (!$password_terisi): ?>
        <div class="info-env">Set <code>ADMIN_PASSWORD</code> di environment variables Vercel (atau .env lokal) untuk mengaktifkan login.</div>
    <?php endif; ?>
</form>
<?php require __DIR__ . '/../includes/admin-foot.php'; ?>
