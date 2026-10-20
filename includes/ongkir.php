<?php
/**
 * Estimasi ongkos kirim Jabodetabek (JNE, SiCepat, J&T).
 *
 * Tanpa API eksternal / tanpa API key: tarif diperkirakan dari JARAK
 * (garis lurus dikali faktor jalan raya) dari lokasi toko di Tangerang
 * ke kecamatan tujuan, ditambah BOBOT paket. Hasilnya perkiraan, bukan
 * tarif resmi ekspedisi.
 *
 * Ubah asal pengiriman lewat konstanta di bawah bila toko pindah lokasi.
 */

const ONGKIR_ASAL_KOTA   = 'Tangerang';
const ONGKIR_ASAL_LAT    = -6.1783;
const ONGKIR_ASAL_LNG    = 106.6319;

/** Berat default per produk (gram) bila produk tidak punya kolom `berat`. */
const ONGKIR_BERAT_DEFAULT_GRAM = 200;

/** Faktor koreksi jarak garis lurus -> perkiraan jarak jalan raya. */
const ONGKIR_FAKTOR_JALAN = 1.3;

/**
 * Daftar kecamatan tujuan Jabodetabek, dikelompokkan per kota/kabupaten.
 * Format: kode => [nama, lat, lng]. Koordinat berupa nilai perkiraan
 * (cukup akurat untuk estimasi jarak beberapa kilometer).
 */
function ongkir_destinasi(): array {
    static $data = null;
    if ($data !== null) return $data;

    $data = [
        'Jakarta Pusat' => [
            'jakpus-gambir'      => ['Gambir', -6.1751, 106.8272],
            'jakpus-menteng'     => ['Menteng', -6.1959, 106.8319],
            'jakpus-tanahabang'  => ['Tanah Abang', -6.1863, 106.8117],
            'jakpus-kemayoran'   => ['Kemayoran', -6.1620, 106.8550],
            'jakpus-sawahbesar'  => ['Sawah Besar', -6.1580, 106.8280],
        ],
        'Jakarta Utara' => [
            'jakut-penjaringan'  => ['Penjaringan', -6.1318, 106.7893],
            'jakut-tanjungpriok' => ['Tanjung Priok', -6.1167, 106.8808],
            'jakut-koja'         => ['Koja', -6.1073, 106.8987],
            'jakut-kelapagading' => ['Kelapa Gading', -6.1586, 106.9054],
            'jakut-cilincing'    => ['Cilincing', -6.1085, 106.9364],
        ],
        'Jakarta Barat' => [
            'jakbar-cengkareng'  => ['Cengkareng', -6.1492, 106.7287],
            'jakbar-grogol'      => ['Grogol Petamburan', -6.1679, 106.7890],
            'jakbar-kebonjeruk'  => ['Kebon Jeruk', -6.1943, 106.7719],
            'jakbar-kalideres'   => ['Kalideres', -6.1558, 106.6966],
            'jakbar-palmerah'    => ['Palmerah', -6.1918, 106.7905],
        ],
        'Jakarta Selatan' => [
            'jaksel-kebayoranbaru' => ['Kebayoran Baru', -6.2443, 106.7995],
            'jaksel-tebet'         => ['Tebet', -6.2297, 106.8562],
            'jaksel-pancoran'      => ['Pancoran', -6.2439, 106.8477],
            'jaksel-pasarminggu'   => ['Pasar Minggu', -6.2841, 106.8452],
            'jaksel-cilandak'      => ['Cilandak', -6.2884, 106.7994],
            'jaksel-jagakarsa'     => ['Jagakarsa', -6.3320, 106.8206],
        ],
        'Jakarta Timur' => [
            'jaktim-matraman'    => ['Matraman', -6.2027, 106.8575],
            'jaktim-pulogadung'  => ['Pulogadung', -6.1865, 106.9017],
            'jaktim-cakung'      => ['Cakung', -6.1747, 106.9465],
            'jaktim-kramatjati'  => ['Kramat Jati', -6.2712, 106.8707],
            'jaktim-makasar'     => ['Makasar', -6.2689, 106.8907],
        ],
        'Kota Tangerang' => [
            'tang-ciledug'       => ['Ciledug', -6.2303, 106.7067],
            'tang-cipondoh'      => ['Cipondoh', -6.1890, 106.6790],
            'tang-tangerang'     => ['Tangerang (pusat kota)', -6.1783, 106.6319],
            'tang-karawaci'      => ['Karawaci', -6.1869, 106.6070],
            'tang-batuceper'     => ['Batuceper', -6.1745, 106.6800],
        ],
        'Kota Tangerang Selatan' => [
            'tangsel-ciputat'    => ['Ciputat', -6.2886, 106.7448],
            'tangsel-pamulang'   => ['Pamulang', -6.3412, 106.7422],
            'tangsel-serpong'    => ['Serpong', -6.3230, 106.6660],
            'tangsel-pondokaren' => ['Pondok Aren', -6.2705, 106.7099],
            'tangsel-setu'       => ['Setu', -6.3549, 106.6703],
        ],
        'Kabupaten Tangerang' => [
            'kabtang-kelapadua'  => ['Kelapa Dua', -6.2540, 106.6170],
            'kabtang-curug'      => ['Curug', -6.2340, 106.5660],
            'kabtang-cikupa'     => ['Cikupa', -6.2300, 106.5230],
            'kabtang-sepatan'    => ['Sepatan', -6.1150, 106.5700],
            'kabtang-balaraja'   => ['Balaraja', -6.1900, 106.4700],
        ],
        'Kota Bekasi' => [
            'bekasi-selatan'     => ['Bekasi Selatan', -6.2640, 106.9950],
            'bekasi-utara'       => ['Bekasi Utara', -6.2190, 107.0030],
            'bekasi-timur'       => ['Bekasi Timur', -6.2390, 107.0230],
            'bekasi-barat'       => ['Bekasi Barat', -6.2390, 106.9800],
            'bekasi-pondokgede'  => ['Pondok Gede', -6.2850, 106.9300],
        ],
        'Kabupaten Bekasi' => [
            'kabbek-cikarangutara' => ['Cikarang Utara', -6.2650, 107.1450],
            'kabbek-cibitung'      => ['Cibitung', -6.2550, 107.1050],
            'kabbek-tambun'        => ['Tambun', -6.2400, 107.0600],
            'kabbek-sukatani'      => ['Sukatani', -6.3500, 107.1700],
            'kabbek-babelan'       => ['Babelan', -6.1600, 107.0300],
        ],
        'Kota Depok' => [
            'depok-beji'         => ['Beji', -6.3760, 106.8200],
            'depok-pancoranmas'  => ['Pancoran Mas', -6.3900, 106.8220],
            'depok-cimanggis'    => ['Cimanggis', -6.3600, 106.8700],
            'depok-sukmajaya'    => ['Sukmajaya', -6.3900, 106.8500],
            'depok-cinere'       => ['Cinere', -6.3300, 106.7900],
        ],
        'Kota Bogor' => [
            'bogor-tengah'       => ['Bogor Tengah', -6.5950, 106.8000],
            'bogor-utara'        => ['Bogor Utara', -6.5600, 106.8000],
            'bogor-tanahsareal'  => ['Tanah Sareal', -6.5500, 106.7800],
            'bogor-selatan'      => ['Bogor Selatan', -6.6300, 106.7900],
            'bogor-barat'        => ['Bogor Barat', -6.5800, 106.7600],
        ],
        'Kabupaten Bogor' => [
            'kabbogor-cibinong'    => ['Cibinong', -6.4700, 106.8500],
            'kabbogor-gunungputri' => ['Gunung Putri', -6.4300, 106.9200],
            'kabbogor-citeureup'   => ['Citeureup', -6.4800, 106.8800],
            'kabbogor-parung'      => ['Parung', -6.4300, 106.7300],
            'kabbogor-cileungsi'   => ['Cileungsi', -6.3900, 106.9700],
        ],
    ];

    return $data;
}

/** Cari satu tujuan berdasarkan kode. Mengembalikan array atau null. */
function ongkir_cari_tujuan(string $kode): ?array {
    foreach (ongkir_destinasi() as $kota => $daftar) {
        if (isset($daftar[$kode])) {
            [$nama, $lat, $lng] = $daftar[$kode];
            return ['kode' => $kode, 'nama' => $nama, 'kota' => $kota, 'lat' => $lat, 'lng' => $lng];
        }
    }
    return null;
}

/** Jarak garis lurus dua koordinat (km) — rumus haversine. */
function ongkir_jarak_km(float $lat1, float $lng1, float $lat2, float $lng2): float {
    $r    = 6371.0;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a    = sin($dLat / 2) ** 2
          + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
    return 2 * $r * asin(min(1.0, sqrt($a)));
}

/** Daftar kurir beserta parameter tarif (dalam rupiah). */
function ongkir_kurir(): array {
    return [
        ['kurir' => 'JNE',     'layanan' => 'REG', 'base' => 8500, 'per_kg' => 4500, 'per_km' => 95, 'min' => 9000],
        ['kurir' => 'SiCepat', 'layanan' => 'REG', 'base' => 7000, 'per_kg' => 4000, 'per_km' => 88, 'min' => 8000],
        ['kurir' => 'J&T',     'layanan' => 'EZ',  'base' => 7800, 'per_kg' => 4300, 'per_km' => 92, 'min' => 8500],
    ];
}

/** Total berat paket dari isi keranjang (gram). */
function ongkir_berat_total(): int {
    $total = 0;
    foreach (keranjang() as $kunci => $qty) {
        [$id] = keranjang_urai((string)$kunci);
        $p = cari_produk($id);
        if (!$p) continue;
        $b = (int)($p['berat'] ?? 0);
        if ($b <= 0) $b = ONGKIR_BERAT_DEFAULT_GRAM;
        $total += $b * (int)$qty;
    }
    return max($total, ONGKIR_BERAT_DEFAULT_GRAM);
}

/** Perkiraan lama pengiriman berdasarkan jarak. */
function ongkir_eta(float $km): string {
    if ($km <= 20) return '1 hari';
    if ($km <= 45) return '1-2 hari';
    if ($km <= 70) return '2-3 hari';
    return '2-4 hari';
}

/**
 * Hitung estimasi ongkir ke satu tujuan.
 * Mengembalikan null jika kode tujuan tidak dikenal.
 */
function ongkir_estimasi(string $kode): ?array {
    $tujuan = ongkir_cari_tujuan($kode);
    if (!$tujuan) return null;

    $jarak = ongkir_jarak_km(ONGKIR_ASAL_LAT, ONGKIR_ASAL_LNG, $tujuan['lat'], $tujuan['lng']) * ONGKIR_FAKTOR_JALAN;
    $berat = ongkir_berat_total();
    $kg    = max(1, (int)ceil($berat / 1000));

    $opsi = [];
    foreach (ongkir_kurir() as $k) {
        $tarif = $k['base'] + ($kg - 1) * $k['per_kg'] + (int)round($jarak) * $k['per_km'];
        $tarif = max($k['min'], $tarif);
        $tarif = (int)(ceil($tarif / 500) * 500);
        $opsi[] = [
            'kurir'   => $k['kurir'],
            'layanan' => $k['layanan'],
            'tarif'   => $tarif,
            'eta'     => ongkir_eta($jarak),
        ];
    }
    usort($opsi, fn($a, $b) => $a['tarif'] <=> $b['tarif']);

    return [
        'kode'   => $kode,
        'nama'   => $tujuan['nama'],
        'kota'   => $tujuan['kota'],
        'jarak'  => $jarak,
        'berat'  => $berat,
        'opsi'   => $opsi,
    ];
}

/**
 * Opsi kurir yang dipilih pada satu tujuan (dipakai untuk total & pesan WA).
 * Mengembalikan null bila tujuan/kurir tidak cocok.
 */
function ongkir_pilihan(string $kodeTujuan, string $kurir): ?array {
    $estimasi = ongkir_estimasi($kodeTujuan);
    if (!$estimasi) return null;

    foreach ($estimasi['opsi'] as $o) {
        if (strcasecmp($o['kurir'], $kurir) === 0) {
            return [
                'kurir'     => $o['kurir'],
                'layanan'   => $o['layanan'],
                'tarif'     => $o['tarif'],
                'eta'       => $o['eta'],
                'destinasi' => $estimasi['nama'] . ', ' . $estimasi['kota'],
                'jarak'     => $estimasi['jarak'],
                'berat'     => $estimasi['berat'],
            ];
        }
    }
    return null;
}
