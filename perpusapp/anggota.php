<?php
require 'config.php';
$active = 'anggota';
$pageTitle = 'Data Anggota';

$msg = '';
$msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['add'])) {
            $hash = password_hash($_POST['password'] ?: 'anggota123', PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO anggota (nm_anggota, jenis_kelamin, alamat, no_telp, tgl_daftar, user, password, status_aktif)
                                    VALUES (?, ?, ?, ?, CURDATE(), ?, ?, 1)");
            $stmt->execute([
                trim($_POST['nm_anggota']),
                $_POST['jenis_kelamin'],
                trim($_POST['alamat']),
                trim($_POST['no_telp']),
                trim($_POST['user']),
                $hash,
            ]);
            $msg = 'Anggota baru berhasil ditambahkan.';
        } elseif (isset($_POST['edit'])) {
            $stmt = $pdo->prepare("UPDATE anggota SET nm_anggota=?, jenis_kelamin=?, alamat=?, no_telp=?, user=? WHERE kd_anggota=?");
            $stmt->execute([
                trim($_POST['nm_anggota']),
                $_POST['jenis_kelamin'],
                trim($_POST['alamat']),
                trim($_POST['no_telp']),
                trim($_POST['user']),
                $_POST['kd_anggota'],
            ]);
            $msg = 'Data anggota berhasil diubah.';
        } elseif (isset($_POST['toggle_status'])) {
            $stmt = $pdo->prepare("UPDATE anggota SET status_aktif = 1 - status_aktif WHERE kd_anggota=?");
            $stmt->execute([$_POST['kd_anggota']]);
            $msg = 'Status anggota berhasil diperbarui.';
        }
    } catch (PDOException $e) {
        $msg = 'Gagal: ' . $e->getMessage();
        $msgType = 'danger';
    }
}

$search = trim($_GET['q'] ?? '');
if ($search !== '') {
    $stmt = $pdo->prepare("SELECT * FROM anggota WHERE nm_anggota LIKE ? OR user LIKE ? ORDER BY nm_anggota ASC");
    $like = "%$search%";
    $stmt->execute([$like, $like]);
    $anggotaList = $stmt->fetchAll();
} else {
    $anggotaList = $pdo->query("SELECT * FROM anggota ORDER BY nm_anggota ASC")->fetchAll();
}

$editAnggota = null;
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM anggota WHERE kd_anggota=?");
    $stmt->execute([$_GET['edit_id']]);
    $editAnggota = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Anggota - PerpusApp</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="layout">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main">
        <?php include 'includes/header.php'; ?>
        <div class="content">

            <?php if ($msg): ?>
                <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>

            <div class="card">
                <h2><?= $editAnggota ? 'Ubah Anggota' : 'Tambah Anggota Baru' ?></h2>
                <form method="post">
                    <?php if ($editAnggota): ?>
                        <input type="hidden" name="kd_anggota" value="<?= $editAnggota['kd_anggota'] ?>">
                    <?php endif; ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Nama Lengkap</label>
                            <input type="text" name="nm_anggota" required value="<?= htmlspecialchars($editAnggota['nm_anggota'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Jenis Kelamin</label>
                            <select name="jenis_kelamin">
                                <option value="L" <?= (($editAnggota['jenis_kelamin'] ?? 'L') === 'L') ? 'selected' : '' ?>>Laki-laki</option>
                                <option value="P" <?= (($editAnggota['jenis_kelamin'] ?? '') === 'P') ? 'selected' : '' ?>>Perempuan</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>No. Telepon</label>
                            <input type="text" name="no_telp" value="<?= htmlspecialchars($editAnggota['no_telp'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Username Login</label>
                            <input type="text" name="user" required value="<?= htmlspecialchars($editAnggota['user'] ?? '') ?>">
                        </div>
                        <?php if (!$editAnggota): ?>
                        <div class="form-group">
                            <label>Password (kosongkan utk default "anggota123")</label>
                            <input type="password" name="password">
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Alamat</label>
                            <textarea name="alamat" rows="2"><?= htmlspecialchars($editAnggota['alamat'] ?? '') ?></textarea>
                        </div>
                    </div>
                    <button type="submit" name="<?= $editAnggota ? 'edit' : 'add' ?>" class="btn btn-primary">
                        <?= $editAnggota ? 'Simpan Perubahan' : 'Tambah Anggota' ?>
                    </button>
                    <?php if ($editAnggota): ?>
                        <a href="anggota.php" class="btn btn-outline">Batal</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="card">
                <div class="card-header-row">
                    <h2>Daftar Anggota</h2>
                    <form class="search-bar" method="get">
                        <input type="text" name="q" placeholder="Cari nama/username..." value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="btn btn-primary">Cari</button>
                    </form>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Nama</th><th>Username</th><th>No. Telp</th><th>Tgl Daftar</th><th>Status</th><th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($anggotaList as $row): ?>
                        <tr>
                            <td class="link-name"><?= htmlspecialchars($row['nm_anggota']) ?></td>
                            <td><?= htmlspecialchars($row['user']) ?></td>
                            <td><?= htmlspecialchars($row['no_telp']) ?></td>
                            <td><?= $row['tgl_daftar'] ? date('d M Y', strtotime($row['tgl_daftar'])) : '-' ?></td>
                            <td>
                                <?php if ($row['status_aktif']): ?>
                                    <span class="badge badge-aktif">Aktif</span>
                                <?php else: ?>
                                    <span class="badge badge-nonaktif">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a class="btn btn-outline btn-sm" href="?edit_id=<?= $row['kd_anggota'] ?>">Ubah</a>
                                <form method="post" style="display:inline">
                                    <input type="hidden" name="kd_anggota" value="<?= $row['kd_anggota'] ?>">
                                    <button type="submit" name="toggle_status" class="btn <?= $row['status_aktif'] ? 'btn-danger' : 'btn-success' ?> btn-sm">
                                        <?= $row['status_aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($anggotaList)): ?>
                        <tr><td colspan="6">Tidak ada data ditemukan.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>
</body>
</html>
