<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_role(['petugas', 'admin']);

$total_buku = $pdo->query("SELECT COUNT(*) c FROM buku")->fetch()['c'];
$anggota_aktif = $pdo->query("SELECT COUNT(*) c FROM anggota WHERE status_aktif=1")->fetch()['c'];
$peminjaman_aktif = $pdo->query("SELECT COUNT(*) c FROM detpinjam WHERE status_pinjam='Dipinjam'")->fetch()['c'];
$terlambat = $pdo->query("SELECT COUNT(*) c FROM detpinjam dp JOIN pinjam p ON p.no_pinjam=dp.no_pinjam WHERE dp.status_pinjam='Dipinjam' AND p.tgl_harus_kembali < CURDATE()")->fetch()['c'];
$denda_belum_lunas = $pdo->query("SELECT COALESCE(SUM(jmlh_denda),0) t FROM denda WHERE lunas=0")->fetch()['t'];

$stmt = $pdo->query("SELECT p.no_pinjam, a.nm_anggota, b.judul, p.tgl_harus_kembali, dp.status_pinjam
    FROM detpinjam dp
    JOIN pinjam p ON p.no_pinjam = dp.no_pinjam
    JOIN anggota a ON a.kd_anggota = p.kd_anggota
    JOIN inventaris i ON i.no_inventaris = dp.no_inventaris
    JOIN buku b ON b.kd_buku = i.kd_buku
    WHERE dp.status_pinjam = 'Dipinjam'
    ORDER BY p.tgl_harus_kembali ASC LIMIT 10");
$aktif_list = $stmt->fetchAll();

$page_title = 'Dashboard Petugas';
$nama_user = $_SESSION['nama'];
$active = 'dashboard';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/sidebar.php';
?>

<div class="cards">
    <div class="card c-blue"><div class="label">Total Buku</div><div class="value"><?= (int)$total_buku ?></div></div>
    <div class="card c-green"><div class="label">Anggota Aktif</div><div class="value"><?= (int)$anggota_aktif ?></div></div>
    <div class="card c-orange"><div class="label">Peminjaman Aktif</div><div class="value"><?= (int)$peminjaman_aktif ?></div></div>
    <div class="card c-red"><div class="label">Keterlambatan</div><div class="value"><?= (int)$terlambat ?></div></div>
</div>

<div class="panel">
    <div class="panel-header">
        <h2>Peminjaman Aktif Terdekat Jatuh Tempo</h2>
        <span class="text-muted">Denda belum lunas: <strong><?= rupiah($denda_belum_lunas) ?></strong></span>
    </div>
    <div class="panel-body" style="padding:0">
        <table>
            <thead><tr><th>No. Pinjam</th><th>Anggota</th><th>Judul Buku</th><th>Harus Kembali</th><th>Status</th></tr></thead>
            <tbody>
            <?php if (empty($aktif_list)): ?>
                <tr><td colspan="5" class="empty-state">Tidak ada peminjaman aktif.</td></tr>
            <?php else: foreach ($aktif_list as $r):
                $telat = strtotime($r['tgl_harus_kembali']) < strtotime(date('Y-m-d')); ?>
                <tr>
                    <td>#<?= (int)$r['no_pinjam'] ?></td>
                    <td><?= e($r['nm_anggota']) ?></td>
                    <td><?= e($r['judul']) ?></td>
                    <td><?= tgl_indo($r['tgl_harus_kembali']) ?></td>
                    <td><?= badge_status($telat ? 'Terlambat' : 'Dipinjam') ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
