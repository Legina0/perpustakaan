<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_role(['petugas', 'admin']);

if (isset($_GET['lunas'])) {
    $pdo->prepare("UPDATE denda SET lunas=1 WHERE id_denda=?")->execute([$_GET['lunas']]);
    flash_set('success', 'Denda ditandai lunas.');
    header('Location: ' . BASE_URL . '/denda.php');
    exit;
}

$filter = $_GET['filter'] ?? 'belum_lunas';
$q = trim($_GET['q'] ?? '');

$sql = "SELECT d.id_denda, a.nm_anggota, b.judul, d.tgl_denda, d.jmlh_denda, d.lunas
        FROM denda d
        JOIN detpinjam dp ON dp.id_detpinjam = d.id_detpinjam
        JOIN pinjam p ON p.no_pinjam = dp.no_pinjam
        JOIN anggota a ON a.kd_anggota = p.kd_anggota
        JOIN inventaris i ON i.no_inventaris = dp.no_inventaris
        JOIN buku b ON b.kd_buku = i.kd_buku
        WHERE a.nm_anggota LIKE ?";
$params = ["%$q%"];
if ($filter === 'belum_lunas') {
    $sql .= " AND d.lunas = 0";
} elseif ($filter === 'lunas') {
    $sql .= " AND d.lunas = 1";
}
$sql .= " ORDER BY d.tgl_denda DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$denda_list = $stmt->fetchAll();

ob_start();
if (empty($denda_list)): ?>
    <tr><td colspan="6" class="empty-state">Tidak ada data denda.</td></tr>
<?php else: foreach ($denda_list as $d): ?>
    <tr>
        <td><?= e($d['nm_anggota']) ?></td>
        <td><?= e($d['judul']) ?></td>
        <td><?= tgl_indo($d['tgl_denda']) ?></td>
        <td><?= rupiah($d['jmlh_denda']) ?></td>
        <td><?= badge_status($d['lunas'] ? 'Lunas' : 'Belum Lunas') ?></td>
        <td>
            <?php if (!$d['lunas']): ?>
                <a class="btn btn-primary btn-sm" href="?lunas=<?= (int)$d['id_denda'] ?>" data-confirm="Tandai denda ini lunas?">Tandai Lunas</a>
            <?php else: ?>
                <span class="text-muted">-</span>
            <?php endif; ?>
        </td>
    </tr>
<?php endforeach; endif;
$baris_denda_html = ob_get_clean();

if (isset($_GET['ajax'])) {
    echo $baris_denda_html;
    exit;
}

$total_belum_lunas = $pdo->query("SELECT COALESCE(SUM(jmlh_denda),0) t FROM denda WHERE lunas=0")->fetch()['t'];

$page_title = 'Denda';
$nama_user = $_SESSION['nama'];
$active = 'denda';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/sidebar.php';
?>

<div class="cards">
    <div class="card c-red"><div class="label">Total Denda Belum Lunas</div><div class="value"><?= rupiah($total_belum_lunas) ?></div></div>
</div>

<div class="panel">
    <div class="panel-header">
        <h2>Daftar Denda</h2>
        <form class="search-box" method="get" data-live-search data-target="#tbody-denda">
            <input type="text" name="q" placeholder="Cari nama anggota..." value="<?= e($q) ?>">
            <select name="filter">
                <option value="belum_lunas" <?= $filter === 'belum_lunas' ? 'selected' : '' ?>>Belum Lunas</option>
                <option value="lunas" <?= $filter === 'lunas' ? 'selected' : '' ?>>Lunas</option>
                <option value="semua" <?= $filter === 'semua' ? 'selected' : '' ?>>Semua</option>
            </select>
            <button class="btn btn-primary" type="submit">Filter</button>
        </form>
    </div>
    <div class="panel-body" style="padding:0">
        <table>
            <thead><tr><th>Anggota</th><th>Judul Buku</th><th>Tgl Denda</th><th>Jumlah</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody id="tbody-denda"><?= $baris_denda_html ?></tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
