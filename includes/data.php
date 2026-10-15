<?php
/**
 * Data toko Aldebaran3DPrinting (statis, tanpa database).
 * Gambar produk diambil dari folder public/images (nama file saja).
 * Kategori berdasarkan BENTUK produk (bukan usia).
 *
 * KREDIT FOTO PRODUK (lisensi bebas, ditemukan via Openverse):
 * - balok-susun.jpg: "Ash Blocks" oleh A. Drauglis, CC BY-SA 2.0
 *   https://www.flickr.com/photos/85463968@N00/4192499051
 * - krincingan-bintang.jpg: "Plastic baby rattle toy 2" oleh Zeromonk, CC BY-SA 4.0
 *   https://commons.wikimedia.org/w/index.php?curid=112619335
 * - puzzle-huruf.jpg: "ABCDEFGHIJKLMNOPQRSTUVWXYZ (318)" oleh Doug Waldron, CC BY-SA 2.0
 *   https://www.flickr.com/photos/96713863@N00/2355862973
 * - pensil-robot.jpg: "Wooden desk organizer, pencil holder #1" oleh ZERGE_VIOLATOR, CC BY 2.0
 *   https://www.flickr.com/photos/47257185@N03/29633962454
 * - penyu-laut.jpg: "Turtle Figurine" oleh Michael Covel, CC BY-SA 2.0
 *   https://www.flickr.com/photos/80739272@N03/7402451744
 * - roda-gigi.jpg: "motion gears -team force" oleh ralphbijker, CC BY 2.0
 *   https://www.flickr.com/photos/17258892@N05/2588347668
 * - labu-halloween.jpg: foto lentera labu oleh sagesolar, CC BY 2.0
 *   https://www.flickr.com/photos/62356799@N06/10501009176
 * - gantungan-kunci.jpg: "Bethlehem Steel souvenir keychains" oleh TheSeafarer, CC BY 2.0
 *   https://www.flickr.com/photos/22028494@N03/7264788272
 * - dudukan-ponsel.jpg: "Wooden phone stand" oleh 99 Factory, CC BY 2.0
 *   https://www.flickr.com/photos/116118903@N04/14917943119
 * - rak-headset.jpg: "DIY headphone stand" oleh Technomancy, CC BY-SA 2.0
 *   https://www.flickr.com/photos/43319799@N00/40245358391
 * - magnet-pixel.jpg: "Pixel Heart" oleh bixentro, CC BY 2.0
 *   https://www.flickr.com/photos/82947612@N00/2395626510
 * - pot-kucing.jpg: "linda's cat planter" oleh penelope waits, CC BY 2.0
 *   https://www.flickr.com/photos/64148767@N00/7013410229
 * - filamen-pla.jpg: "Universal stand-alone filament spool holder (Fully 3D-printable)"
 *   oleh Creative Tools, CC BY 2.0
 *   https://www.flickr.com/photos/33907867@N02/15735401776
 */

// Kategori bentuk
$kategori = [
    'semua'     => 'Untuk Kamu',
    'terlaris'  => 'Terlaris',
    'clicker'   => 'Fidget Clicker',
    'gantungan' => 'Gantungan Kunci',
    'figure'    => 'Action Figure',
    'pajangan'  => 'Pajangan',
    'dekor'     => 'Dekor Kamar',
    'makanan'   => 'Makanan',
    'games'     => 'Games & Puzzle',
    'hobi'      => 'Hobi & DIY',
    'printer'   => 'Printer & Filamen',
];

// Showcase kategori bentuk untuk kartu zona di beranda: ikon, deskripsi, warna pastel
$kategori_meta = [
    'clicker'   => ['ikon' => '🖱️', 'ket' => 'Klik-klik satisfying, bisa custom nama & bentuk.', 'warna' => 'koral'],
    'gantungan' => ['ikon' => '🔑', 'ket' => 'Gantungan nama & karakter buat tas & kunci.',        'warna' => 'matahari'],
    'figure'    => ['ikon' => '🦸', 'ket' => 'Karakter action figure bersendi & kolektibel.',      'warna' => 'ungu'],
    'pajangan'  => ['ikon' => '🐢', 'ket' => 'Pajangan meja & rak yang gemoy.',                    'warna' => 'koral'],
    'dekor'     => ['ikon' => '🌷', 'ket' => 'Dekor kamar biar makin aesthetic.',                 'warna' => 'matahari'],
    'makanan'   => ['ikon' => '🧀', 'ket' => 'Miniatur makanan yang lucu & detail.',              'warna' => 'ungu'],
    'games'     => ['ikon' => '🎮', 'ket' => 'Puzzle, balok & games yang interaktif.',            'warna' => 'koral'],
    'hobi'      => ['ikon' => '🛠️', 'ket' => 'DIY, gadget & aksesori buat hobi.',                 'warna' => 'matahari'],
    'printer'   => ['ikon' => '🖨️', 'ket' => 'Printer, filamen & kebutuhan cetak.',               'warna' => 'ungu'],
];

// Setiap produk: id, nama, penjual, kategori (array), warna pastel, harga, terjual, suka, gambar, deskripsi, terlaris
$produk = [
    [
        'id' => 1, 'nama' => 'Balok Susun Hewan Jumbo', 'penjual' => 'Aldebaran Studio',
        'kategori' => ['games', 'pajangan', 'terlaris'], 'warna' => 'koral', 'harga' => 48000, 'unduhan' => 6200, 'suka' => 5400,
        'gambar' => 'balok-susun.jpg', 'terlaris' => true,
        'deskripsi' => 'Tiga balok besar bergambar wajah hewan yang bisa disusun jadi menara. Ukuran jumbo dan sudut membulat, tanpa bagian kecil yang bisa lepas.'
    ],
    [
        'id' => 2, 'nama' => 'Krincingan Bintang Besar', 'penjual' => 'Aldebaran Studio',
        'kategori' => ['pajangan', 'games'], 'warna' => 'koral', 'harga' => 35000, 'unduhan' => 3100, 'suka' => 2800,
        'gambar' => 'krincingan-bintang.jpg', 'terlaris' => false,
        'deskripsi' => 'Krincingan berbentuk bintang dengan gagang besar yang pas digenggam tangan kecil. Dicetak padat, tanpa komponen lepas.'
    ],
    [
        'id' => 3, 'nama' => 'Puzzle Huruf & Angka Jumbo', 'penjual' => 'BelajarLayer',
        'kategori' => ['games'], 'warna' => 'koral', 'harga' => 55000, 'unduhan' => 2700, 'suka' => 2300,
        'gambar' => 'puzzle-huruf.jpg', 'terlaris' => false,
        'deskripsi' => 'Puzzle huruf A–Z dan angka 0–9 dengan kepingan tebal dan mudah dipegang. Cocok untuk belajar mengenal huruf sambil bermain.'
    ],
    [
        'id' => 4, 'nama' => 'NAME CLICKER CREATOR', 'penjual' => 'Aldebaran Studio',
        'kategori' => ['clicker', 'terlaris', 'gantungan'], 'warna' => 'matahari', 'harga' => 12000, 'unduhan' => 9300, 'suka' => 8100,
        'gambar' => 'name-clicker-creator.jpeg', 'terlaris' => true, 'unggulan' => true,
        'galeri' => ['name-clicker-creator.jpeg', 'name-clicker-creator_b.jpeg'],
        'deskripsi' => 'Bikin namamu jadi fidget clicker yang bunyinya satisfying banget! Setiap huruf dicetak 3D tebal, warna pastel bisa pilih sendiri, dan tiap huruf bisa diklik-klik. Cocok untuk kado nama teman, dekor tas, atau koleksi di kamar. Harga mulai Rp12.000 untuk 1 huruf, tambah Rp4.000 per huruf berikutnya. Contoh: nama 5 huruf = Rp12.000 + (4 x Rp4.000) = Rp28.000. Tulis ejaan nama yang mau dibuat saat checkout ya!'
    ],
    [
        'id' => 15, 'nama' => 'Cheese Fidget Clicker', 'penjual' => 'Aldebaran Studio',
        'kategori' => ['clicker', 'terlaris', 'makanan'], 'warna' => 'matahari', 'harga' => 35000, 'unduhan' => 8900, 'suka' => 8050,
        'gambar' => 'cheese-fidget-clicker.jpeg', 'terlaris' => false, 'unggulan' => true,
        'galeri' => ['cheese-fidget-clicker.jpeg', 'cheese-fidget-clicker_b.jpeg'],
        'deskripsi' => 'Fidget clicker bentuk keju yang empuk diklik. Bikin tangan sibuk jadi tenang, cocok untuk main di rumah atau di sekolah.'
    ],
    [
        'id' => 16, 'nama' => 'Cloud Clicker', 'penjual' => 'Aldebaran Studio',
        'kategori' => ['clicker', 'terlaris'], 'warna' => 'matahari', 'harga' => 15999, 'unduhan' => 8700, 'suka' => 8000,
        'gambar' => 'cloud-clicker.jpeg', 'terlaris' => false, 'unggulan' => true,
        'galeri' => ['cloud-clicker.jpeg', 'cloud-clicker_b.jpeg', 'cloud-clicker_c.jpeg'],
        'deskripsi' => 'Fidget clicker bentuk awan yang gemoy dan bunyinya satisfying. Empuk diklik-klik, cocok untuk fokus belajar, koleksi, atau kado teman.'
    ],
    [
        'id' => 5, 'nama' => 'Tempat Pensil Robot', 'penjual' => 'Aldebaran Studio',
        'kategori' => ['dekor', 'figure'], 'warna' => 'matahari', 'harga' => 38000, 'unduhan' => 4800, 'suka' => 3900,
        'gambar' => 'pensil-robot.jpg', 'terlaris' => false,
        'deskripsi' => 'Robot lucu yang memegang pensil dan pulpen di kepalanya. Bikin meja belajar lebih rapi dan seru.'
    ],
    [
        'id' => 6, 'nama' => 'Set Penyu & Teman Laut', 'penjual' => 'Aldebaran Studio',
        'kategori' => ['figure', 'pajangan'], 'warna' => 'koral', 'harga' => 45000, 'unduhan' => 7000, 'suka' => 6600,
        'gambar' => 'penyu-laut.jpg', 'terlaris' => false,
        'deskripsi' => 'Penyu, ikan, dan kepiting dengan sendi yang bisa bergerak. Seru untuk main di bak mandi atau dikoleksi di rak.'
    ],
    [
        'id' => 7, 'nama' => 'Kit Roda Gigi Ajaib', 'penjual' => 'Aldebaran Studio',
        'kategori' => ['games', 'hobi'], 'warna' => 'ungu', 'harga' => 68000, 'unduhan' => 2200, 'suka' => 2900,
        'gambar' => 'roda-gigi.jpg', 'terlaris' => false,
        'deskripsi' => 'Satu set roda gigi warna-warni untuk belajar cara kerja putaran dan kecepatan. Susun sendiri, lalu putar dengan engkol.'
    ],
    [
        'id' => 8, 'nama' => 'Lampu Tidur Labu Halloween', 'penjual' => 'Aldebaran Studio',
        'kategori' => ['dekor', 'pajangan'], 'warna' => 'matahari', 'harga' => 55000, 'unduhan' => 2800, 'suka' => 2400,
        'gambar' => 'labu-halloween.jpg', 'terlaris' => false,
        'deskripsi' => 'Lampu tidur berbentuk labu. Dicetak tipis agar cahaya lampu LED menembus dengan hangat. Lampu LED dijual terpisah.'
    ],
    [
        'id' => 9, 'nama' => 'Gantungan Kunci Nama Kustom', 'penjual' => 'RoboPrint3D',
        'kategori' => ['gantungan', 'terlaris'], 'warna' => 'ungu', 'harga' => 25000, 'unduhan' => 8000, 'suka' => 6500,
        'gambar' => 'gantungan-kunci.jpg', 'terlaris' => true,
        'deskripsi' => 'Gantungan kunci dengan nama pilihanmu. Pilih warna dan font, cocok untuk tas sekolah atau kado teman.'
    ],
    [
        'id' => 10, 'nama' => 'Dudukan Ponsel Lipat', 'penjual' => 'Aldebaran Studio',
        'kategori' => ['hobi', 'dekor'], 'warna' => 'ungu', 'harga' => 18000, 'unduhan' => 7800, 'suka' => 5000,
        'gambar' => 'dudukan-ponsel.jpg', 'terlaris' => true,
        'deskripsi' => 'Dudukan ponsel yang bisa dilipat tipis dan muat di saku. Sudut layar bisa diatur untuk nonton atau video call.'
    ],
    [
        'id' => 11, 'nama' => 'Rak Gantung Headset Gaming', 'penjual' => 'Aldebaran Studio',
        'kategori' => ['dekor', 'hobi'], 'warna' => 'ungu', 'harga' => 52000, 'unduhan' => 3300, 'suka' => 3700,
        'gambar' => 'rak-headset.jpg', 'terlaris' => false,
        'deskripsi' => 'Rak untuk menggantung headset di pinggir meja. Kokoh, hemat tempat, dan bikin meja gaming lebih rapi.'
    ],
    [
        'id' => 12, 'nama' => 'Magnet Kulkas Pixel Hati', 'penjual' => 'Aldebaran Studio',
        'kategori' => ['dekor', 'pajangan'], 'warna' => 'koral', 'harga' => 12000, 'unduhan' => 5400, 'suka' => 4100,
        'gambar' => 'magnet-pixel.jpg', 'terlaris' => false,
        'deskripsi' => 'Magnet bergaya pixel art dalam berbagai bentuk. Cocok untuk dekor loker, kulkas, atau papan magnet kamar.'
    ],
    [
        'id' => 13, 'nama' => 'Pot Tanaman Telinga Kucing', 'penjual' => 'Aldebaran Studio',
        'kategori' => ['dekor'], 'warna' => 'koral', 'harga' => 30000, 'unduhan' => 3600, 'suka' => 4600,
        'gambar' => 'pot-kucing.jpg', 'terlaris' => false,
        'deskripsi' => 'Pot mini berbentuk kucing untuk kaktus atau sukulen. Pas di meja belajar atau rak kamar.'
    ],
    [
        'id' => 14, 'nama' => 'Filamen PLA+ 1 Kg', 'penjual' => 'Toko Filamen ID',
        'kategori' => ['printer', 'hobi'], 'warna' => 'ungu', 'harga' => 175000, 'unduhan' => 0, 'suka' => 3700,
        'gambar' => 'filamen-pla.jpg', 'terlaris' => false,
        'deskripsi' => 'Filamen PLA+ diameter 1,75 mm, 1 kg. Cocok untuk belajar 3D printing. Disarankan dengan pendampingan orang dewasa.'
    ],
];

function cari_produk(int $id): ?array {
    global $produk;
    foreach ($produk as $p) {
        if ($p['id'] === $id) return $p;
    }
    return null;
}
