<?php
session_start();
require '../config/koneksi.php';
require '../includes/functions.php';
require_login_anggota();

$keyword = trim($_GET['cari'] ?? '');

$sql = "
    SELECT b.kd_buku, b.judul,
           pg.nm_pengarang, pn.nm_penerbit, kl.nm_klasifikasi,
           b.jumlah,
           (SELECT COUNT(*) FROM inventaris i WHERE i.kd_buku = b.kd_buku AND i.status = 'Tersedia') AS tersedia,
           (SELECT COUNT(*) FROM inventaris i WHERE i.kd_buku = b.kd_buku) AS total_inventaris
    FROM buku b
    LEFT JOIN pengarang pg ON pg.kd_pengarang = b.kd_pengarang
    LEFT JOIN penerbit pn ON pn.kd_penerbit = b.kd_penerbit
    LEFT JOIN klasifikasi kl ON kl.kd_klasifikasi = b.kd_klasifikasi
";

$params = [];
if ($keyword !== '') {
    $sql .= " WHERE b.judul LIKE ? OR pg.nm_pengarang LIKE ? OR pn.nm_penerbit LIKE ? ";
    $like = "%{$keyword}%";
    $params = [$like, $like, $like];
}
$sql .= " ORDER BY b.judul ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$buku_list = $stmt->fetchAll();

$page_title  = 'Katalog Buku';
$active_menu = 'katalog';
require '../includes/header_anggota.php';
?>

<div class="card">
    <div class="toolbar">
        <div class="card-title" style="margin-bottom:0;">Katalog Buku</div>
        <form method="get" action="katalog.php" class="search-box">
            <input type="text" name="cari" placeholder="Cari judul, pengarang, penerbit" value="<?= h($keyword) ?>">
            <button type="submit" class="btn">Cari</button>
        </form>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>Judul</th>
                <th>Pengarang</th>
                <th>Penerbit</th>
                <th>Klasifikasi</th>
                <th>Ketersediaan</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($buku_list)): ?>
                <tr><td colspan="5" class="empty-row">Tidak ada buku ditemukan.</td></tr>
            <?php else: ?>
                <?php foreach ($buku_list as $b): ?>
                    <tr>
                        <td><a class="link" href="detail_buku.php?id=<?= (int)$b['kd_buku'] ?>"><?= h($b['judul']) ?></a></td>
                        <td><?= h($b['nm_pengarang'] ?? '-') ?></td>
                        <td><?= h($b['nm_penerbit'] ?? '-') ?></td>
                        <td><?= h($b['nm_klasifikasi'] ?? '-') ?></td>
                        <td>
                            <?php if ($b['tersedia'] > 0): ?>
                                <span class="badge tersedia">Tersedia</span>
                            <?php else: ?>
                                <span class="badge habis">Habis</span>
                            <?php endif; ?>
                            (<?= (int)$b['tersedia'] ?>/<?= (int)$b['total_inventaris'] ?>)
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require '../includes/footer_anggota.php'; ?>
