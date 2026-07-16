<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_role(['petugas', 'admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ubah_status'])) {
    $no_inv = $_POST['no_inventaris'];
    $status = $_POST['status'];
    if (in_array($status, ['Tersedia', 'Rusak', 'Hilang'], true)) {
        $pdo->prepare("UPDATE inventaris SET status=? WHERE no_inventaris=? AND status != 'Dipinjam'")->execute([$status, $no_inv]);
        flash_set('success', 'Status inventaris berhasil diperbarui.');
    }
    header('Location: ' . BASE_URL . '/inventaris.php');
    exit;
}

$q = trim($_GET['q'] ?? '');
$filter_status = $_GET['status'] ?? '';

$sql = "SELECT i.*, b.judul FROM inventaris i JOIN buku b ON b.kd_buku = i.kd_buku WHERE b.judul LIKE ?";
$params = ["%$q%"];
if ($filter_status !== '') {
    $sql .= " AND i.status = ?";
    $params[] = $filter_status;
}
$sql .= " ORDER BY b.judul, i.no_inventaris";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$inventaris_list = $stmt->fetchAll();

ob_start();
if (empty($inventaris_list)): ?>
    <tr><td colspan="6" class="empty-state">Tidak ada data.</td></tr>
<?php else: foreach ($inventaris_list as $i): ?>
    <tr>
        <td>#<?= (int)$i['no_inventaris'] ?></td>
        <td><?= e($i['no_buku']) ?></td>
        <td><?= e($i['judul']) ?></td>
        <td><?= tgl_indo($i['tgl_masuk']) ?></td>
        <td><?= badge_status($i['status']) ?></td>
        <td>
            <?php if ($i['status'] !== 'Dipinjam'): ?>
            <form method="post" style="display:flex;gap:6px;">
                <input type="hidden" name="no_inventaris" value="<?= (int)$i['no_inventaris'] ?>">
                <select name="status" style="padding:4px 8px;font-size:12px;">
                    <?php foreach (['Tersedia','Rusak','Hilang'] as $s): ?>
                        <option value="<?= $s ?>" <?= $i['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-outline btn-sm" type="submit" name="ubah_status" value="1">Ubah</button>
            </form>
            <?php else: ?>
                <span class="text-muted">Sedang dipinjam</span>
            <?php endif; ?>
        </td>
    </tr>
<?php endforeach; endif;
$baris_inventaris_html = ob_get_clean();

if (isset($_GET['ajax'])) {
    echo $baris_inventaris_html;
    exit;
}

$page_title = 'Inventaris';
$nama_user = $_SESSION['nama'];
$active = 'inventaris';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/sidebar.php';
?>

<div class="panel">
    <div class="panel-header">
        <h2>Daftar Inventaris (Per Eksemplar)</h2>
        <form class="search-box" method="get" data-live-search data-target="#tbody-inventaris">
            <input type="text" name="q" placeholder="Cari judul buku..." value="<?= e($q) ?>">
            <select name="status">
                <option value="">Semua Status</option>
                <?php foreach (['Tersedia','Dipinjam','Rusak','Hilang'] as $s): ?>
                    <option value="<?= $s ?>" <?= $filter_status === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-primary" type="submit">Filter</button>
        </form>
    </div>
    <div class="panel-body" style="padding:0">
        <table>
            <thead><tr><th>No. Inventaris</th><th>No. Buku</th><th>Judul</th><th>Tgl Masuk</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody id="tbody-inventaris"><?= $baris_inventaris_html ?></tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
