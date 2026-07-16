<?php
session_start();
require '../config/koneksi.php';
require '../includes/functions.php';
require_login_anggota();

$kd_anggota = $_SESSION['kd_anggota'];

// --- Sedang Dipinjam ---
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM detpinjam dp
    JOIN pinjam p ON p.no_pinjam = dp.no_pinjam
    WHERE p.kd_anggota = ? AND dp.status_pinjam = 'Dipinjam'
");
$stmt->execute([$kd_anggota]);
$sedang_dipinjam = (int)$stmt->fetchColumn();

// --- Denda Aktif ---
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(d.jmlh_denda),0) FROM denda d
    JOIN detpinjam dp ON dp.id_detpinjam = d.id_detpinjam
    JOIN pinjam p ON p.no_pinjam = dp.no_pinjam
    WHERE p.kd_anggota = ? AND d.lunas = 0
");
$stmt->execute([$kd_anggota]);
$denda_aktif = (float)$stmt->fetchColumn();

// --- Total Riwayat Pinjam ---
$stmt = $pdo->prepare("SELECT COUNT(*) FROM pinjam WHERE kd_anggota = ?");
$stmt->execute([$kd_anggota]);
$total_riwayat = (int)$stmt->fetchColumn();

// --- Buku yang sedang dipinjam ---
$stmt = $pdo->prepare("
    SELECT b.judul, dp.tgl_pinjam, p.tgl_harus_kembali, dp.status_pinjam
    FROM detpinjam dp
    JOIN pinjam p ON p.no_pinjam = dp.no_pinjam
    JOIN inventaris inv ON inv.no_inventaris = dp.no_inventaris
    JOIN buku b ON b.kd_buku = inv.kd_buku
    WHERE p.kd_anggota = ? AND dp.status_pinjam = 'Dipinjam'
    ORDER BY dp.tgl_pinjam DESC
");
$stmt->execute([$kd_anggota]);
$list_dipinjam = $stmt->fetchAll();

$page_title  = 'Dashboard';
$active_menu = 'dashboard';
require '../includes/header_anggota.php';
?>

<div style="margin-bottom:22px;">
    <div style="font-size:18px;font-weight:700;color:#17233d;">
        Dashboard - Selamat Datang, <?= h($_SESSION['nm_anggota']) ?>
    </div>
</div>

<div class="stat-grid">
    <div class="stat-card blue">
        <div class="label">Sedang Dipinjam</div>
        <div class="value"><?= $sedang_dipinjam ?> Buku</div>
    </div>
    <div class="stat-card red">
        <div class="label">Denda Aktif</div>
        <div class="value"><?= rupiah($denda_aktif) ?></div>
    </div>
    <div class="stat-card green">
        <div class="label">Total Riwayat Pinjam</div>
        <div class="value"><?= $total_riwayat ?> kali</div>
    </div>
</div>

<div class="card">
    <div class="card-title">Buku yang Sedang Dipinjam</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Judul Buku</th>
                <th>Tgl Pinjam</th>
                <th>Harus Kembali</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($list_dipinjam)): ?>
                <tr><td colspan="4" class="empty-row">Tidak ada buku yang sedang dipinjam.</td></tr>
            <?php else: ?>
                <?php foreach ($list_dipinjam as $row): ?>
                    <tr>
                        <td><?= h($row['judul']) ?></td>
                        <td><?= tgl_indo($row['tgl_pinjam']) ?></td>
                        <td><?= tgl_indo($row['tgl_harus_kembali']) ?></td>
                        <td><span class="badge dipinjam">Dipinjam</span></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require '../includes/footer_anggota.php'; ?>
