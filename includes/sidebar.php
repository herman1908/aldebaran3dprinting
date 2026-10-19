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
            <strong>Studio Cetak 3D</strong>
            <span>Fidget, figure & dekor kamar.</span>
        </div>
    </div>
    <nav>
        <?php
        item_menu('beranda',  'index.php',                   '⌂', 'Beranda',      $menu_aktif);
        item_menu('terlaris', 'index.php?kategori=terlaris', '★', 'Terlaris',     $menu_aktif);
        ?>

        <h4>Kategori</h4>
        <?php
        item_menu('clicker',   'index.php?kategori=clicker#produk',   '◍', 'Fidget Clicker',   $menu_aktif);
        item_menu('gantungan', 'index.php?kategori=gantungan#produk', '⌘', 'Gantungan Kunci',  $menu_aktif);
        item_menu('figure',    'index.php?kategori=figure#produk',    '⬢', 'Action Figure',    $menu_aktif);
        item_menu('pajangan',  'index.php?kategori=pajangan#produk',  '❏', 'Pajangan',         $menu_aktif);
        item_menu('dekor',     'index.php?kategori=dekor#produk',     '◧', 'Dekor Kamar',      $menu_aktif);
        item_menu('games',     'index.php?kategori=games#produk',     '▣', 'Games & Puzzle',   $menu_aktif);
        item_menu('hobi',    'index.php?kategori=hobi#produk',    '✚', 'Hobi & DIY',        $menu_aktif);
        ?>


        <h4>Kamu</h4>
        <?php
        item_menu('koleksi', 'keranjang.php', '◐', 'Keranjang', $menu_aktif);
        ?>
    </nav>
</aside>
