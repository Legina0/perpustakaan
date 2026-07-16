<?php
session_start();
require '../config/koneksi.php';
require '../includes/functions.php';
require_login_anggota();

$kd_anggota = $_SESSION['kd_anggota'];

$stmt = $pdo->prepare("
    SELECT b.judul, dp.tgl_pinjam, p.tgl_harus_kembali, dp.tgl_kembali, dp.status_pinjam
    FROM detpinjam dp
    JOIN pinjam p ON p.no_pinjam = dp.no_pinjam
    JOIN inventaris inv ON inv.no_inventaris = dp.no_inventaris
    JOIN buku b ON b.kd_buku = inv.kd_buku
    WHERE p.kd_anggota = ?
    ORDER BY dp.tgl_pinjam DESC
");
$stmt->execute([$kd_anggota]);
$riwayat = $stmt->fetchAll();

$page_title  = 'Riwayat Peminjaman';
$active_menu = 'riwayat';
require '../includes/header_anggota.php';
?>

<div class="card">
    <div class="card-title">Riwayat Peminjaman Saya</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Judul Buku</th>
                <th>Tgl Pinjam</th>
                <th>Harus Kembali</th>
                <th>Tgl Kembali</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($riwayat)): ?>
                <tr><td colspan="5" class="empty-row">Belum ada riwayat peminjaman.</td></tr>
            <?php else: ?>
                <?php foreach ($riwayat as $r): ?>
                    <tr>
                        <td><?= h($r['judul']) ?></td>
                        <td><?= tgl_indo($r['tgl_pinjam']) ?></td>
                        <td><?= tgl_indo($r['tgl_harus_kembali']) ?></td>
                        <td><?= $r['tgl_kembali'] ? tgl_indo($r['tgl_kembali']) : '-' ?></td>
                        <td>
                            <?php if ($r['status_pinjam'] === 'Dipinjam'): ?>
                                <span class="badge dipinjam">Dipinjam</span>
                            <?php else: ?>
                                <span class="badge kembali">Kembali</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require '../includes/footer_anggota.php'; ?>
