<?php
/**
 * Penyimpanan pesan/chat konsumen berbasis file JSON (tanpa database).
 *
 * - Pesan dari form "kolom chat" di beranda disimpan ke data/pesan.json.
 * - Dibaca & dikelola dari panel admin (admin/pesan.php).
 *
 * Catatan Vercel: filesystem serverless read-only, jadi pesan tidak persisten
 * di Vercel. Berfungsi penuh secara lokal (XAMPP / php -S).
 */

/** Path file JSON pesan. */
function pesan_json_path(): string {
    return dirname(__DIR__) . '/data/pesan.json';
}

/** Ambil semua pesan (array of assoc). */
function pesan_semua(): array {
    $path = pesan_json_path();
    if (!is_file($path)) return [];
    $isi = json_decode((string)file_get_contents($path), true);
    return is_array($isi) ? $isi : [];
}

/**
 * Ubah isi file pesan dalam transaksi terkunci (flock) untuk hindari
 * race-condition saat tulis bersamaan. $fn menerima &$daftar.
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
    return true;
}

/** Simpan pesan baru; kembalikan id yang dibuat, atau 0 jika gagal. */
function pesan_tambah(array $data): int {
    $baru = [
        'id'     => 0,
        'nama'   => trim((string)($data['nama'] ?? '')),
        'hp'     => trim((string)($data['hp'] ?? '')),
        'email'  => trim((string)($data['email'] ?? '')),
        'pesan'  => trim((string)($data['pesan'] ?? '')),
        'waktu'  => time(),
        'dibaca' => false,
    ];
    if ($baru['pesan'] === '') return 0;

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
    return pesan_ubah(function (array &$daftar) use ($id, $baca) {
        foreach ($daftar as $i => $m) {
            if ((int)($m['id'] ?? 0) === $id) { $daftar[$i]['dibaca'] = $baca; return true; }
        }
        return false;
    });
}

/** Hapus pesan berdasarkan id. */
function pesan_hapus(int $id): bool {
    return pesan_ubah(function (array &$daftar) use ($id) {
        $sebelum = count($daftar);
        $daftar = array_values(array_filter($daftar, fn($m) => (int)($m['id'] ?? 0) !== $id));
        return count($daftar) !== $sebelum;
    });
}

/** Jumlah pesan yang belum dibaca. */
function pesan_belum_dibaca(): int {
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
