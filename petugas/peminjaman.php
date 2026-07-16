<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_role(['petugas', 'admin']);

$errors = [];
$sukses = null;

// ---- PROSES SIMPAN TRANSAKSI ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proses_pinjam'])) {
    $kd_anggota = $_POST['kd_anggota'] ?? '';
    $no_inventaris_list = $_POST['no_inventaris'] ?? [];

    if ($kd_anggota === '' || empty($no_inventaris_list)) {
        $errors[] = 'Pilih anggota dan minimal satu buku untuk dipinjam.';
    } else {
        // Cek denda belum lunas
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(d.jmlh_denda),0) t FROM denda d
            JOIN detpinjam dp ON dp.id_detpinjam = d.id_detpinjam
            JOIN pinjam p ON p.no_pinjam = dp.no_pinjam
            WHERE p.kd_anggota = ? AND d.lunas = 0");
        $stmt->execute([$kd_anggota]);
        $denda_belum_lunas = $stmt->fetch()['t'];

        if ($denda_belum_lunas > 0) {
            $errors[] = 'Anggota ini masih memiliki denda belum lunas sebesar ' . rupiah($denda_belum_lunas) . '. Pelunasan denda diperlukan sebelum meminjam buku baru.';
        } else {
            try {
                $pdo->beginTransaction();

                $lama_pinjam = (int)get_konfigurasi($pdo, 'lama_pinjam_hari', 7);
                $tgl_pinjam = date('Y-m-d');
                $tgl_harus_kembali = date('Y-m-d', strtotime("+$lama_pinjam days"));

                $stmt = $pdo->prepare("INSERT INTO pinjam (kd_anggota, kd_petugas, tgl_pinjam, tgl_harus_kembali) VALUES (?,?,?,?)");
                $stmt->execute([$kd_anggota, $_SESSION['user_id'], $tgl_pinjam, $tgl_harus_kembali]);
                $no_pinjam = $pdo->lastInsertId();

                foreach ($no_inventaris_list as $no_inv) {
                    // Pastikan masih tersedia (mencegah double booking)
                    $cek = $pdo->prepare("SELECT status FROM inventaris WHERE no_inventaris=? FOR UPDATE");
                    $cek->execute([$no_inv]);
                    $status_now = $cek->fetch();
                    if (!$status_now || $status_now['status'] !== 'Tersedia') {
                        throw new Exception('Salah satu buku yang dipilih sudah tidak tersedia.');
                    }

                    $stmt = $pdo->prepare("INSERT INTO detpinjam (no_pinjam, no_inventaris, tgl_pinjam, status_pinjam) VALUES (?,?,?, 'Dipinjam')");
                    $stmt->execute([$no_pinjam, $no_inv, $tgl_pinjam]);

                    $pdo->prepare("UPDATE inventaris SET status='Dipinjam' WHERE no_inventaris=?")->execute([$no_inv]);
                }

                $pdo->commit();
                flash_set('success', "Transaksi peminjaman #$no_pinjam berhasil disimpan. Harus kembali: " . tgl_indo($tgl_harus_kembali));
                header('Location: ' . BASE_URL . '/peminjaman.php');
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = 'Gagal menyimpan transaksi: ' . $e->getMessage();
            }
        }
    }
}

// ---- DATA PENDUKUNG ----
$anggota_list = $pdo->query("SELECT kd_anggota, nm_anggota, user FROM anggota WHERE status_aktif=1 ORDER BY nm_anggota")->fetchAll();

$q_buku = trim($_GET['q_buku'] ?? '');
$stmt = $pdo->prepare("SELECT i.no_inventaris, i.no_buku, b.judul
    FROM inventaris i JOIN buku b ON b.kd_buku = i.kd_buku
    WHERE i.status = 'Tersedia' AND b.judul LIKE ?
    ORDER BY b.judul LIMIT 50");
$stmt->execute(["%$q_buku%"]);
$buku_tersedia = $stmt->fetchAll();

ob_start();
if (empty($buku_tersedia)): ?>
    <p class="empty-state">Tidak ada buku tersedia.</p>
<?php else: foreach ($buku_tersedia as $b): ?>
    <label style="display:flex;align-items:center;gap:10px;padding:6px 0;font-size:14px;">
        <input type="checkbox" name="no_inventaris[]" value="<?= (int)$b['no_inventaris'] ?>">
        <?= e($b['judul']) ?> <span class="text-muted">(<?= e($b['no_buku']) ?>)</span>
    </label>
<?php endforeach; endif;
$daftar_buku_tersedia_html = ob_get_clean();

if (isset($_GET['ajax'])) {
    echo $daftar_buku_tersedia_html;
    exit;
}

// Transaksi aktif terbaru (untuk referensi di bawah form)
$stmt = $pdo->query("SELECT p.no_pinjam, a.nm_anggota, p.tgl_pinjam, p.tgl_harus_kembali, COUNT(dp.id_detpinjam) jml_buku
    FROM pinjam p
    JOIN anggota a ON a.kd_anggota = p.kd_anggota
    JOIN detpinjam dp ON dp.no_pinjam = p.no_pinjam
    GROUP BY p.no_pinjam
    ORDER BY p.no_pinjam DESC LIMIT 10");
$transaksi_terbaru = $stmt->fetchAll();

$page_title = 'Peminjaman Baru';
$nama_user = $_SESSION['nama'];
$active = 'peminjaman';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/sidebar.php';
?>

<?php render_errors_popup($errors); ?>

<div class="panel">
    <div class="panel-header"><h2>Buat Peminjaman Baru</h2></div>
    <div class="panel-body">
        <form method="post">
            <div class="form-grid">
                <div class="form-group">
                    <label>Anggota</label>
                    <select name="kd_anggota" required>
                        <option value="">-- Pilih Anggota --</option>
                        <?php foreach ($anggota_list as $a): ?>
                            <option value="<?= (int)$a['kd_anggota'] ?>"><?= e($a['nm_anggota']) ?> (<?= e($a['user']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <p class="text-muted" style="margin-bottom:8px;">Pilih buku yang tersedia untuk dipinjam (bisa lebih dari satu):</p>
            <div class="panel" style="box-shadow:none;border:1px solid var(--border);margin-bottom:18px;">
                <div class="panel-body" id="daftar-buku-tersedia" style="max-height:280px;overflow:auto;padding:10px 20px;">
                    <?= $daftar_buku_tersedia_html ?>
                </div>
            </div>

            <button class="btn btn-primary" type="submit" name="proses_pinjam" value="1">Simpan Transaksi Peminjaman</button>
        </form>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <h2>Cari Buku Tersedia</h2>
        <form class="search-box" method="get" data-live-search data-target="#daftar-buku-tersedia">
            <input type="text" name="q_buku" placeholder="Cari judul buku..." value="<?= e($q_buku) ?>">
            <button class="btn btn-primary" type="submit">Cari</button>
        </form>
    </div>
</div>

<div class="panel">
    <div class="panel-header"><h2>Transaksi Peminjaman Terbaru</h2></div>
    <div class="panel-body" style="padding:0">
        <table>
            <thead><tr><th>No. Pinjam</th><th>Anggota</th><th>Tgl Pinjam</th><th>Harus Kembali</th><th>Jml Buku</th></tr></thead>
            <tbody>
            <?php if (empty($transaksi_terbaru)): ?>
                <tr><td colspan="5" class="empty-state">Belum ada transaksi.</td></tr>
            <?php else: foreach ($transaksi_terbaru as $t): ?>
                <tr>
                    <td>#<?= (int)$t['no_pinjam'] ?></td>
                    <td><?= e($t['nm_anggota']) ?></td>
                    <td><?= tgl_indo($t['tgl_pinjam']) ?></td>
                    <td><?= tgl_indo($t['tgl_harus_kembali']) ?></td>
                    <td><?= (int)$t['jml_buku'] ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
