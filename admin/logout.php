<?php
/**
 * Logout admin.
 */
require_once __DIR__ . '/../includes/auth.php';

admin_hapus_cookie();
header('Location: /admin/login.php');
exit;
