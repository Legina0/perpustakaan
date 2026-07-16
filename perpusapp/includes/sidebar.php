<?php
// $active harus diset di file pemanggil, contoh: $active = 'anggota';
$menu = [
    'dashboard'          => ['label' => 'Dashboard',           'href' => 'dashboard.php'],
    'dashboard_grafik'   => ['label' => 'Dashboard Grafik',    'href' => 'dashboard_grafik.php'],
    'data_buku'          => ['label' => 'Data Buku',           'href' => 'data_buku.php'],
    'pengarang_penerbit' => ['label' => 'Pengarang/Penerbit',  'href' => 'pengarang_penerbit.php'],
    'anggota'            => ['label' => 'Anggota',             'href' => 'anggota.php'],
    'petugas'            => ['label' => 'Petugas',             'href' => 'petugas.php'],
    'inventaris'         => ['label' => 'Inventaris',          'href' => 'inventaris.php'],
    'peminjaman'         => ['label' => 'Peminjaman',          'href' => 'peminjaman.php'],
    'pengembalian'       => ['label' => 'Pengembalian',        'href' => 'pengembalian.php'],
    'denda'              => ['label' => 'Denda',               'href' => 'denda.php'],
    'laporan'            => ['label' => 'Laporan',             'href' => 'laporan.php'],
    'pengaturan'         => ['label' => 'Pengaturan',          'href' => 'pengaturan.php'],
    'profil'             => ['label' => 'Profil',              'href' => 'profil.php'],
];
?>
<aside class="sidebar">
    <div class="brand">📚 PerpusApp</div>
    <span class="role-badge">ADMIN</span>
    <nav>
        <?php foreach ($menu as $key => $item): ?>
            <a href="<?= $item['href'] ?>" class="<?= ($active ?? '') === $key ? 'active' : '' ?>">
                <?= htmlspecialchars($item['label']) ?>
            </a>
        <?php endforeach; ?>
    </nav>
</aside>
