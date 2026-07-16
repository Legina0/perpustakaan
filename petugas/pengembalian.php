<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_role(['petugas', 'admin']);

$errors = [];
$info = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proses_kembali'])) {
    $id_detpinjam = $_POST['id_detpinjam'] ?? '';

    if ($id_detpinjam === '') {
        $errors[] = 'Pilih buku yang akan dikembalikan.';
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT dp.*, p.tgl_harus_kembali, p.no_pinjam
                FROM detpinjam dp JOIN pinjam p ON p.no_pinjam = dp.no_pinjam
                WHERE dp.id_detpinjam = ? AND dp.status_pinjam='Dipinjam' FOR UPDATE");
            $stmt->execute([$id_detpinjam]);
            $item = $stmt->fetch();

            if (!$item) {
                throw new Exception('Data peminjaman tidak ditemukan atau sudah dikembalikan.');
            }

            $tgl_kembali_aktual = date('Y-m-d');
            $hari_terlambat = hitung_hari_terlambat($item['tgl_harus_kembali'], $tgl_kembali_aktual);

            $pdo->prepare("UPDATE detpinjam SET status_pinjam='Kembali', tgl_kembali=? WHERE id_detpinjam=?")
                ->execute([$tgl_kembali_aktual, $id_detpinjam]);

            $pdo->prepare("UPDATE inventaris SET status='Tersedia' WHERE no_inventaris=?")
                ->execute([$item['no_inventaris']]);

            $denda_dibuat = null;
            if ($hari_terlambat > 0) {
                $tarif = (int)get_konfigurasi($pdo, 'tarif_denda_per_hari', 1000);
                $jmlh_denda = $hari_terlambat * $tarif;
                $pdo->prepare("INSERT INTO denda (id_detpinjam, tgl_denda, jmlh_denda, lunas) VALUES (?,?,?,0)")
                    ->execute([$id_detpinjam, $tgl_kembali_aktual, $jmlh_denda]);
                $denda_dibuat = $jmlh_denda;
            }

            $pdo->commit();

            $info = [
                'terlambat' => $hari_terlambat > 0,
                'hari' => $hari_terlambat,
                'denda' => $denda_dibuat,
            ];
            flash_set('success', $hari_terlambat > 0
                ? "Pengembalian berhasil. Terlambat $hari_terlambat hari, denda " . rupiah($denda_dibuat) . " tercatat."
                : "Pengembalian berhasil diproses tanpa denda.");
            header('Location: ' . BASE_URL . '/pengembalian.php');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = $e->getMessage();
        }
    }
}

$q = trim($_GET['q'] ?? '');
$sql = "SELECT dp.id_detpinjam, p.no_pinjam, a.nm_anggota, b.judul, p.tgl_harus_kembali, dp.tgl_pinjam
        FROM detpinjam dp
        JOIN pinjam p ON p.no_pinjam = dp.no_pinjam
        JOIN anggota a ON a.kd_anggota = p.kd_anggota
        JOIN inventaris i ON i.no_inventaris = dp.no_inventaris
        JOIN buku b ON b.kd_buku = i.kd_buku
        WHERE dp.status_pinjam = 'Dipinjam'
          AND (a.nm_anggota LIKE ? OR p.no_pinjam LIKE ? OR b.judul LIKE ?)
        ORDER BY p.tgl_harus_kembali ASC";
$stmt = $pdo->prepare($sql);
$like = "%$q%";
$stmt->execute([$like, $like, $like]);
$daftar_aktif = $stmt->fetchAll();

ob_start();
if (empty($daftar_aktif)): ?>
    <tr><td colspan="6" class="empty-state">Tidak ada peminjaman aktif yang cocok.</td></tr>
<?php else: foreach ($daftar_aktif as $r):
    $telat = strtotime($r['tgl_harus_kembali']) < strtotime(date('Y-m-d')); ?>
    <tr>
        <td><input type="radio" name="id_detpinjam" value="<?= (int)$r['id_detpinjam'] ?>" required></td>
        <td>#<?= (int)$r['no_pinjam'] ?></td>
        <td><?= e($r['nm_anggota']) ?></td>
        <td><?= e($r['judul']) ?></td>
        <td><?= tgl_indo($r['tgl_harus_kembali']) ?></td>
        <td><?= badge_status($telat ? 'Terlambat' : 'Dipinjam') ?></td>
    </tr>
<?php endforeach; endif;
$baris_pengembalian_html = ob_get_clean();

if (isset($_GET['ajax'])) {
    echo $baris_pengembalian_html;
    exit;
}

$page_title = 'Pengembalian';
$nama_user = $_SESSION['nama'];
$active = 'pengembalian';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/sidebar.php';
?>

<?php render_errors_popup($errors); ?>

<div class="panel">
    <div class="panel-header">
        <h2>Cari Peminjaman Aktif</h2>
        <form class="search-box" method="get" data-live-search data-target="#tbody-pengembalian">
            <input type="text" name="q" placeholder="No. pinjam / nama anggota / judul buku..." value="<?= e($q) ?>">
            <button class="btn btn-primary" type="submit">Cari</button>
        </form>
    </div>
    <div class="panel-body" style="padding:0">
        <form method="post">
            <table>
                <thead><tr><th></th><th>No. Pinjam</th><th>Anggota</th><th>Judul Buku</th><th>Harus Kembali</th><th>Status</th></tr></thead>
                <tbody id="tbody-pengembalian"><?= $baris_pengembalian_html ?></tbody>
            </table>
            <div style="padding:16px 20px;">
                <button class="btn btn-primary" type="submit" name="proses_kembali" value="1">Proses Pengembalian</button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
