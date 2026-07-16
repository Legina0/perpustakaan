<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_role(['petugas', 'admin']);

$action = $_GET['action'] ?? 'list';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_anggota'])) {
    $id = $_POST['kd_anggota'] ?? null;
    $nama = trim($_POST['nm_anggota'] ?? '');
    $jk = $_POST['jenis_kelamin'] ?? 'L';
    $alamat = trim($_POST['alamat'] ?? '');
    $telp = trim($_POST['no_telp'] ?? '');
    $user = trim($_POST['user'] ?? '');

    if ($nama === '' || $user === '') $errors[] = 'Nama dan username wajib diisi.';
    if (!is_valid_phone($telp)) $errors[] = 'No. Telepon harus berupa angka (8-15 digit).';

    if (!$errors) {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE anggota SET nm_anggota=?, jenis_kelamin=?, alamat=?, no_telp=?, user=? WHERE kd_anggota=?");
            $stmt->execute([$nama, $jk, $alamat, $telp, $user, $id]);
            flash_set('success', 'Data anggota berhasil diperbarui.');
        } else {
            $password_default = password_hash('password123', PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO anggota (nm_anggota, jenis_kelamin, alamat, no_telp, tgl_daftar, user, password, status_aktif)
                                    VALUES (?, ?, ?, ?, CURDATE(), ?, ?, 1)");
            $stmt->execute([$nama, $jk, $alamat, $telp, $user, $password_default]);
            flash_set('success', 'Anggota baru berhasil ditambahkan (password default: password123).');
        }
        header('Location: ' . BASE_URL . '/anggota.php');
        exit;
    }
}

// ---- NONAKTIFKAN / AKTIFKAN ----
if (isset($_GET['toggle'])) {
    $stmt = $pdo->prepare("UPDATE anggota SET status_aktif = 1 - status_aktif WHERE kd_anggota = ?");
    $stmt->execute([$_GET['toggle']]);
    flash_set('success', 'Status anggota diperbarui.');
    header('Location: ' . BASE_URL . '/anggota.php');
    exit;
}

// ---- DATA UNTUK FORM EDIT ----
$edit_data = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM anggota WHERE kd_anggota=?");
    $stmt->execute([$_GET['id']]);
    $edit_data = $stmt->fetch();
}

// ---- LIST + SEARCH ----
$q = trim($_GET['q'] ?? '');
$stmt = $pdo->prepare("SELECT * FROM anggota WHERE nm_anggota LIKE ? OR user LIKE ? ORDER BY nm_anggota");
$like = "%$q%";
$stmt->execute([$like, $like]);
$anggota_list = $stmt->fetchAll();

ob_start();
if (empty($anggota_list)): ?>
    <tr><td colspan="6" class="empty-state">Belum ada data anggota.</td></tr>
<?php else: foreach ($anggota_list as $a): ?>
    <tr>
        <td><?= e($a['nm_anggota']) ?></td>
        <td><?= e($a['user']) ?></td>
        <td><?= e($a['no_telp'] ?? '-') ?></td>
        <td><?= tgl_indo($a['tgl_daftar']) ?></td>
        <td><?= badge_status($a['status_aktif'] ? 'Aktif' : 'Nonaktif') ?></td>
        <td>
            <a class="btn btn-outline btn-sm" href="?action=edit&id=<?= (int)$a['kd_anggota'] ?>">Ubah</a>
            <a class="btn btn-danger btn-sm" href="?toggle=<?= (int)$a['kd_anggota'] ?>" data-confirm="Ubah status aktif anggota ini?">
                <?= $a['status_aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?>
            </a>
        </td>
    </tr>
<?php endforeach; endif;
$baris_anggota_html = ob_get_clean();

if (isset($_GET['ajax'])) {
    echo $baris_anggota_html;
    exit;
}

$page_title = 'Data Anggota';
$nama_user = $_SESSION['nama'];
$active = 'anggota';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/sidebar.php';
?>

<?php render_errors_popup($errors); ?>

<div class="panel">
    <div class="panel-header">
        <h2><?= $action === 'edit' ? 'Ubah Data Anggota' : 'Tambah Anggota Baru' ?></h2>
    </div>
    <div class="panel-body">
        <form method="post">
            <?php if ($edit_data): ?><input type="hidden" name="kd_anggota" value="<?= (int)$edit_data['kd_anggota'] ?>"><?php endif; ?>
            <div class="form-grid">
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nm_anggota" value="<?= e($edit_data['nm_anggota'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Jenis Kelamin</label>
                    <select name="jenis_kelamin">
                        <option value="L" <?= ($edit_data['jenis_kelamin'] ?? '') === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                        <option value="P" <?= ($edit_data['jenis_kelamin'] ?? '') === 'P' ? 'selected' : '' ?>>Perempuan</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>No. Telepon</label>
                    <input type="text" name="no_telp" inputmode="numeric" pattern="[0-9]{8,15}" title="Hanya angka, 8-15 digit" value="<?= e($edit_data['no_telp'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Username Login</label>
                    <input type="text" name="user" value="<?= e($edit_data['user'] ?? '') ?>" required>
                </div>
            </div>
            <div class="form-group" style="margin-bottom:18px;">
                <label>Alamat</label>
                <textarea name="alamat" rows="2"><?= e($edit_data['alamat'] ?? '') ?></textarea>
            </div>
            <button class="btn btn-primary" type="submit" name="simpan_anggota" value="1">
                <?= $edit_data ? 'Simpan Perubahan' : 'Tambah Anggota' ?>
            </button>
            <?php if ($edit_data): ?><a href="<?= BASE_URL ?>/petugas/anggota.php" class="btn btn-outline">Batal</a><?php endif; ?>
        </form>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <h2>Daftar Anggota</h2>
        <form class="search-box" method="get" data-live-search data-target="#tbody-anggota">
            <input type="text" name="q" placeholder="Cari nama/username..." value="<?= e($q) ?>">
            <button class="btn btn-primary" type="submit">Cari</button>
        </form>
    </div>
    <div class="panel-body" style="padding:0">
        <table>
            <thead><tr><th>Nama</th><th>Username</th><th>No. Telp</th><th>Tgl Daftar</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody id="tbody-anggota"><?= $baris_anggota_html ?></tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
