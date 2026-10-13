<?php
function item_menu(string $kunci, string $href, string $ikon, string $label, string $aktif): void {
    $kelas = $kunci === $aktif ? 'aktif' : '';
    echo '<a class="' . $kelas . '" href="' . e($href) . '"><span class="ikon">' . $ikon . '</span>' . e($label) . '</a>';
}
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-card-sapa">
        <img src="/images/aldi-maskot.svg" alt="" width="52" height="52">
        <div>
            <strong>Halo, aku Aldi! ⭐</strong>
            <span>Yuk pilih mainan sesuai usiamu~</span>
        </div>
    </div>
    <nav>
        <?php
        item_menu('beranda',  'index.php',                     '🏠', 'Beranda',        $menu_aktif);
        item_menu('semua',    'index.php?urut=terbaru',        '🧩', 'Semua Produk',   $menu_aktif);
        item_menu('terlaris', 'index.php?kategori=terlaris',   '🌟', 'Terlaris',       $menu_aktif);
        ?>

        <h4>Pilih sesuai usia</h4>
        <?php
        item_menu('balita', 'index.php?usia=balita#produk', '🧸', 'Balita (2–5 th)',      $menu_aktif);
        item_menu('anak',   'index.php?usia=anak#produk',   '🚀', 'Anak SD (6–12 th)',    $menu_aktif);
        item_menu('remaja', 'index.php?usia=remaja#produk', '🎧', 'SMP & SMA (13–18 th)', $menu_aktif);
        ?>

        <h4>Jelajahi</h4>
        <?php
        item_menu('halloween', 'index.php?kategori=halloween#produk', '🎃', 'Halloween',         $menu_aktif);
        item_menu('dekor',     'index.php?kategori=dekor#produk',     '🌷', 'Dekor Kamar',       $menu_aktif);
        item_menu('printer',   'index.php?kategori=printer#produk',   '🖨️', 'Printer & Filamen', $menu_aktif);
        ?>

        <h4>Kamu</h4>
        <?php
        item_menu('koleksi', 'keranjang.php', '🛒', 'Keranjang', $menu_aktif);
        ?>
    </nav>
</aside>
