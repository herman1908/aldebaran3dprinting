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
        <?php foreach ($kategori_nav as $kunci => $ikon): ?>
            <?php item_menu($kunci, 'index.php?kategori=' . rawurlencode($kunci) . '#produk', $ikon, $kategori[$kunci] ?? $kunci, $menu_aktif); ?>
        <?php endforeach; ?>


        <h4>Kamu</h4>
        <?php
        item_menu('koleksi', 'keranjang.php', '◐', 'Keranjang', $menu_aktif);
        ?>
    </nav>
</aside>
