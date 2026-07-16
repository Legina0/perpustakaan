<?php
/**
 * Variabel yang harus disediakan sebelum include:
 * $active   -> string kode menu aktif, contoh 'dashboard', 'buku', dll
 * $page_title, $nama_user
 * current_role() dipakai untuk menentukan menu
 */
$role = current_role();

// Ambil foto profil user yang sedang login (dari tabel anggota / petugas)
$foto_user_url = null;
if (isset($_SESSION['user_id'])) {
    if ($role === 'anggota') {
        $stmt = $pdo->prepare("SELECT foto FROM anggota WHERE kd_anggota = ?");
    } else {
        $stmt = $pdo->prepare("SELECT foto FROM petugas WHERE kd_petugas = ?");
    }
    $stmt->execute([$_SESSION['user_id']]);
    $foto_user_url = foto_profil_url($stmt->fetchColumn() ?: null);
}

$menu_petugas = [
    'dashboard'    => ['Dashboard', BASE_URL . '/dashboard.php'],
    'anggota'      => ['Data Anggota', BASE_URL . '/anggota.php'],
    'buku'         => ['Data Buku', BASE_URL . '/buku.php'],
    'peminjaman'   => ['Peminjaman', BASE_URL . '/peminjaman.php'],
    'pengembalian' => ['Pengembalian', BASE_URL . '/pengembalian.php'],
    'inventaris'   => ['Inventaris', BASE_URL . '/inventaris.php'],
    'denda'        => ['Denda', BASE_URL . '/denda.php'],
    'profil'       => ['Profil', BASE_URL . '/profil.php'],
];
$menu = $menu_petugas;
?>
<aside class="sidebar">
    <div class="brand">&#128218; PerpusApp</div>
    <span class="role-badge <?= e($role) ?>"><?= strtoupper(e($role)) ?></span>
    <nav>
        <?php foreach ($menu as $key => [$label, $url]): ?>
            <a href="<?= $url ?>" class="<?= ($active ?? '') === $key ? 'active' : '' ?> <?= $role === 'admin' ? 'admin-menu' : '' ?>">
                <?= e($label) ?>
            </a>
        <?php endforeach; ?>
    </nav>
</aside>
<div class="main">
    <div class="topbar">
        <h1><?= e($page_title ?? '') ?></h1>
        <div class="user-menu" id="userMenu">
            <div class="user-chip" onclick="document.getElementById('userMenu').classList.toggle('open')">
                <div class="avatar-circle">
                    <?php if ($foto_user_url): ?>
                        <img src="<?= e($foto_user_url) ?>" alt="Foto Profil">
                    <?php else: ?>
                        <?= svg_avatar_placeholder() ?>
                    <?php endif; ?>
                </div>
                <span class="user-chip-name"><?= e($nama_user ?? 'User') ?></span>
            </div>
            <div class="user-dropdown">
                <a href="<?= BASE_URL ?>/profil.php">Profil Saya</a>
                <a href="<?= BASE_URL ?>/auth/logout.php">Logout</a>
            </div>
        </div>
    </div>
    <div class="content">
        <?php if ($f = flash_get()): ?>
            <script>
            document.addEventListener('DOMContentLoaded', function () {
                Swal.fire({
                    icon: <?= json_encode($f['type'] === 'error' ? 'error' : 'success') ?>,
                    title: <?= json_encode($f['type'] === 'error' ? 'Gagal' : 'Berhasil') ?>,
                    text: <?= json_encode($f['message']) ?>,
                    confirmButtonColor: '#4f46e5',
                    <?php if ($f['type'] !== 'error'): ?>
                    timer: 2200,
                    timerProgressBar: true,
                    <?php endif; ?>
                });
            });
            </script>
        <?php endif; ?>
