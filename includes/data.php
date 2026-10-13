<?php
/**
 * Data toko Aldebaran3DPrinting (statis, tanpa database).
 * Gambar produk diambil dari folder public/images (nama file saja).
 * Ganti file di folder public/images/ dengan foto produk asli Anda,
 * lalu sesuaikan nama file pada kolom 'gambar'.
 */

// Zona usia: kunci => [label, rentang, deskripsi singkat, warna CSS]
$usia = [
    'balita'  => ['label' => 'Balita',      'rentang' => '2–5 tahun',   'ket' => 'Besar, bulat, dan tanpa bagian kecil.',          'warna' => 'koral'],
    'anak'    => ['label' => 'Anak SD',     'rentang' => '6–12 tahun',  'ket' => 'Mainan gerak, alat sekolah, dan eksperimen.',    'warna' => 'matahari'],
    'remaja'  => ['label' => 'SMP & SMA',   'rentang' => '13–18 tahun', 'ket' => 'Aksesori kamar, gadget, dan karya kreatif.',     'warna' => 'ungu'],
];

$kategori = [
    'semua'     => 'Untuk Kamu',
    'terlaris'  => 'Terlaris',
    'mainan'    => 'Mainan & Game',
    'edukasi'   => 'Edukasi & Sekolah',
    'dekor'     => 'Dekor Kamar',
    'halloween' => 'Halloween',
    'hobi'      => 'Hobi & DIY',
    'printer'   => 'Printer & Filamen',
];

// Setiap produk: id, nama, penjual, kategori (array), usia, harga (Rupiah), terjual, suka, gambar, deskripsi, terlaris
$produk = [
    [
        'id' => 1, 'nama' => 'Balok Susun Hewan Jumbo', 'penjual' => 'Aldebaran Studio',
        'kategori' => ['mainan', 'edukasi', 'terlaris'], 'usia' => 'balita', 'harga' => 48000, 'unduhan' => 6200, 'suka' => 5400,
        'gambar' => 'balok-susun.svg', 'terlaris' => true,
        'deskripsi' => 'Tiga balok besar bergambar wajah hewan yang bisa disusun jadi menara. Ukuran jumbo dan sudut membulat, tanpa bagian kecil yang bisa lepas.'
    ],
    [
        'id' => 2, 'nama' => 'Krincingan Bintang Besar', 'penjual' => 'Aldebaran Studio',
        'kategori' => ['mainan'], 'usia' => 'balita', 'harga' => 35000, 'unduhan' => 3100, 'suka' => 2800,
        'gambar' => 'krincingan-bintang.svg', 'terlaris' => false,
        'deskripsi' => 'Krincingan berbentuk bintang dengan gagang besar yang pas digenggam tangan kecil. Dicetak padat, tanpa komponen lepas.'
    ],
    [
        'id' => 3, 'nama' => 'Puzzle Huruf & Angka Jumbo', 'penjual' => 'BelajarLayer',
        'kategori' => ['edukasi', 'mainan'], 'usia' => 'balita', 'harga' => 55000, 'unduhan' => 2700, 'suka' => 2300,
        'gambar' => 'puzzle-huruf.svg', 'terlaris' => false,
        'deskripsi' => 'Puzzle huruf A–Z dan angka 0–9 dengan kepingan tebal dan mudah dipegang. Cocok untuk belajar mengenal huruf sambil bermain.'
    ],
    [
        'id' => 4, 'nama' => 'Dinosaurus Bersendi', 'penjual' => 'KreasiLayer',
        'kategori' => ['mainan', 'terlaris'], 'usia' => 'anak', 'harga' => 42000, 'unduhan' => 9300, 'suka' => 8100,
        'gambar' => 'dinosaurus.svg', 'terlaris' => true,
        'deskripsi' => 'Dinosaurus dengan leher, ekor, dan kaki yang bisa digerakkan. Langsung jadi dari printer, tanpa perakitan.'
    ],
    [
        'id' => 5, 'nama' => 'Tempat Pensil Robot', 'penjual' => 'Meja Rapi',
        'kategori' => ['edukasi', 'dekor'], 'usia' => 'anak', 'harga' => 38000, 'unduhan' => 4800, 'suka' => 3900,
        'gambar' => 'pensil-robot.svg', 'terlaris' => false,
        'deskripsi' => 'Robot lucu yang memegang pensil dan pulpen di kepalanya. Bikin meja belajar lebih rapi dan seru.'
    ],
    [
        'id' => 6, 'nama' => 'Set Penyu & Teman Laut', 'penjual' => 'KreasiLayer',
        'kategori' => ['mainan'], 'usia' => 'anak', 'harga' => 45000, 'unduhan' => 7000, 'suka' => 6600,
        'gambar' => 'penyu-laut.svg', 'terlaris' => false,
        'deskripsi' => 'Penyu, ikan, dan kepiting dengan sendi yang bisa bergerak. Seru untuk main di bak mandi atau dikoleksi di rak.'
    ],
    [
        'id' => 7, 'nama' => 'Kit Roda Gigi Ajaib', 'penjual' => 'Lab Mini',
        'kategori' => ['edukasi', 'hobi'], 'usia' => 'anak', 'harga' => 68000, 'unduhan' => 2200, 'suka' => 2900,
        'gambar' => 'roda-gigi.svg', 'terlaris' => false,
        'deskripsi' => 'Satu set roda gigi warna-warni untuk belajar cara kerja putaran dan kecepatan. Susun sendiri, lalu putar dengan engkol.'
    ],
    [
        'id' => 8, 'nama' => 'Lampu Tidur Labu Halloween', 'penjual' => 'SeramKreatif',
        'kategori' => ['halloween', 'dekor'], 'usia' => 'anak', 'harga' => 55000, 'unduhan' => 2800, 'suka' => 2400,
        'gambar' => 'labu-halloween.svg', 'terlaris' => false,
        'deskripsi' => 'Lampu tidur berbentuk labu. Dicetak tipis agar cahaya lampu LED menembus dengan hangat. Lampu LED dijual terpisah.'
    ],
    [
        'id' => 9, 'nama' => 'Gantungan Kunci Nama Kustom', 'penjual' => 'RoboPrint3D',
        'kategori' => ['hobi', 'terlaris'], 'usia' => 'remaja', 'harga' => 25000, 'unduhan' => 8000, 'suka' => 6500,
        'gambar' => 'gantungan-kunci.svg', 'terlaris' => true,
        'deskripsi' => 'Gantungan kunci dengan nama pilihanmu. Pilih warna dan font, cocok untuk tas sekolah atau kado teman.'
    ],
    [
        'id' => 10, 'nama' => 'Dudukan Ponsel Lipat', 'penjual' => 'TeknoMaker',
        'kategori' => ['hobi', 'terlaris'], 'usia' => 'remaja', 'harga' => 18000, 'unduhan' => 7800, 'suka' => 5000,
        'gambar' => 'dudukan-ponsel.svg', 'terlaris' => true,
        'deskripsi' => 'Dudukan ponsel yang bisa dilipat tipis dan muat di saku. Sudut layar bisa diatur untuk nonton atau video call.'
    ],
    [
        'id' => 11, 'nama' => 'Rak Gantung Headset Gaming', 'penjual' => 'TeknoMaker',
        'kategori' => ['dekor', 'hobi'], 'usia' => 'remaja', 'harga' => 52000, 'unduhan' => 3300, 'suka' => 3700,
        'gambar' => 'rak-headset.svg', 'terlaris' => false,
        'deskripsi' => 'Rak untuk menggantung headset di pinggir meja. Kokoh, hemat tempat, dan bikin meja gaming lebih rapi.'
    ],
    [
        'id' => 12, 'nama' => 'Magnet Kulkas Pixel Hati', 'penjual' => 'MagnetKita',
        'kategori' => ['hobi', 'dekor'], 'usia' => 'remaja', 'harga' => 12000, 'unduhan' => 5400, 'suka' => 4100,
        'gambar' => 'magnet-pixel.svg', 'terlaris' => false,
        'deskripsi' => 'Magnet bergaya pixel art dalam berbagai bentuk. Cocok untuk dekor loker, kulkas, atau papan magnet kamar.'
    ],
    [
        'id' => 13, 'nama' => 'Pot Tanaman Telinga Kucing', 'penjual' => 'Kamar Estetik',
        'kategori' => ['dekor'], 'usia' => 'remaja', 'harga' => 30000, 'unduhan' => 3600, 'suka' => 4600,
        'gambar' => 'pot-kucing.svg', 'terlaris' => false,
        'deskripsi' => 'Pot mini berbentuk kucing untuk kaktus atau sukulen. Pas di meja belajar atau rak kamar.'
    ],
    [
        'id' => 14, 'nama' => 'Filamen PLA+ 1 Kg', 'penjual' => 'Toko Filamen ID',
        'kategori' => ['printer', 'hobi'], 'usia' => 'remaja', 'harga' => 175000, 'unduhan' => 0, 'suka' => 3700,
        'gambar' => 'filamen-pla.svg', 'terlaris' => false,
        'deskripsi' => 'Filamen PLA+ diameter 1,75 mm, 1 kg. Cocok untuk belajar 3D printing di klub sekolah. Disarankan dengan pendampingan orang dewasa.'
    ],
];

function cari_produk(int $id): ?array {
    global $produk;
    foreach ($produk as $p) {
        if ($p['id'] === $id) return $p;
    }
    return null;
}
