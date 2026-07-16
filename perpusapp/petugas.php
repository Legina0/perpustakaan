<?php
require 'config.php';
$active = 'petugas';
$pageTitle = 'Kelola Akun Petugas';

$msg = '';
$msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['add'])) {
            $hash = password_hash($_POST['password'] ?: 'petugas123', PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO petugas (nm_petugas, jenis_kelamin, alamat, telp, user, password, role, status_aktif)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
            $stmt->execute([
                trim($_POST['nm_petugas']),
                $_POST['jenis_kelamin'],
                trim($_POST['alamat']),
                trim($_POST['telp']),
                trim($_POST['user']),
                $hash,
                $_POST['role'],
            ]);
            $msg = 'Akun petugas baru berhasil ditambahkan.';
        } elseif (isset($_POST['edit'])) {
            $stmt = $pdo->prepare("UPDATE petugas SET nm_petugas=?, jenis_kelamin=?, alamat=?, telp=?, user=?, role=? WHERE kd_petugas=?");
            $stmt->execute([
                trim($_POST['nm_petugas']),
                $_POST['jenis_kelamin'],
                trim($_POST['alamat']),
                trim($_POST['telp']),
                trim($_POST['user']),
                $_POST['role'],
                $_POST['kd_petugas'],
            ]);
            $msg = 'Data petugas berhasil diubah.';
        } elseif (isset($_POST['reset_pass'])) {
            $hash = password_hash('petugas123', PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE petugas SET password=? WHERE kd_petugas=?");
            $stmt->execute([$hash, $_POST['kd_petugas']]);
            $msg = 'Password berhasil direset ke default (petugas123).';
        } elseif (isset($_POST['toggle_status'])) {
            $stmt = $pdo->prepare("UPDATE petugas SET status_aktif = 1 - status_aktif WHERE kd_petugas=?");
            $stmt->execute([$_POST['kd_petugas']]);
            $msg = 'Status akun berhasil diperbarui.';
        }
    } catch (PDOException $e) {
        $msg = 'Gagal: ' . $e->getMessage();
        $msgType = 'danger';
    }
}

$search = trim($_GET['q'] ?? '');
if ($search !== '') {
    $stmt = $pdo->prepare("SELECT * FROM petugas WHERE nm_petugas LIKE ? OR user LIKE ? ORDER BY nm_petugas ASC");
    $like = "%$search%";
    $stmt->execute([$like, $like]);
    $petugasList = $stmt->fetchAll();
} else {
    $petugasList = $pdo->query("SELECT * FROM petugas ORDER BY nm_petugas ASC")->fetchAll();
}

$editPetugas = null;
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM petugas WHERE kd_petugas=?");
    $stmt->execute([$_GET['edit_id']]);
    $editPetugas = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Petugas - PerpusApp</title>
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
                <h2><?= $editPetugas ? 'Ubah Akun' : 'Tambah Akun Baru' ?></h2>
                <form method="post">
                    <?php if ($editPetugas): ?>
                        <input type="hidden" name="kd_petugas" value="<?= $editPetugas['kd_petugas'] ?>">
                    <?php endif; ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Nama</label>
                            <input type="text" name="nm_petugas" required value="<?= htmlspecialchars($editPetugas['nm_petugas'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="user" required value="<?= htmlspecialchars($editPetugas['user'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Role</label>
                            <select name="role">
                                <option value="petugas" <?= (($editPetugas['role'] ?? 'petugas') === 'petugas') ? 'selected' : '' ?>>Petugas</option>
                                <option value="admin" <?= (($editPetugas['role'] ?? '') === 'admin') ? 'selected' : '' ?>>Admin</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>No. Telepon</label>
                            <input type="text" name="telp" value="<?= htmlspecialchars($editPetugas['telp'] ?? '') ?>">
                        </div>
                        <?php if (!$editPetugas): ?>
                        <div class="form-group">
                            <label>Password (kosongkan utk default "petugas123")</label>
                            <input type="password" name="password">
                        </div>
                        <?php endif; ?>
                        <div class="form-group">
                            <label>Jenis Kelamin</label>
                            <select name="jenis_kelamin">
                                <option value="L" <?= (($editPetugas['jenis_kelamin'] ?? 'L') === 'L') ? 'selected' : '' ?>>Laki-laki</option>
                                <option value="P" <?= (($editPetugas['jenis_kelamin'] ?? '') === 'P') ? 'selected' : '' ?>>Perempuan</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Alamat</label>
                            <textarea name="alamat" rows="2"><?= htmlspecialchars($editPetugas['alamat'] ?? '') ?></textarea>
                        </div>
                    </div>
                    <button type="submit" name="<?= $editPetugas ? 'edit' : 'add' ?>" class="btn btn-primary">
                        <?= $editPetugas ? 'Simpan Perubahan' : 'Tambah Akun' ?>
                    </button>
                    <?php if ($editPetugas): ?>
                        <a href="petugas.php" class="btn btn-outline">Batal</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="card">
                <div class="card-header-row">
                    <h2>Daftar Akun Petugas/Admin</h2>
                    <form class="search-bar" method="get">
                        <input type="text" name="q" placeholder="Cari nama/username..." value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="btn btn-primary">Cari</button>
                    </form>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Nama</th><th>Username</th><th>Role</th><th>Status</th><th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($petugasList as $row): ?>
                        <tr>
                            <td class="link-name"><?= htmlspecialchars($row['nm_petugas']) ?></td>
                            <td><?= htmlspecialchars($row['user']) ?></td>
                            <td>
                                <span class="badge <?= $row['role'] === 'admin' ? 'badge-admin' : 'badge-petugas' ?>">
                                    <?= strtoupper($row['role']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($row['status_aktif']): ?>
                                    <span class="badge badge-aktif">Aktif</span>
                                <?php else: ?>
                                    <span class="badge badge-nonaktif">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a class="btn btn-outline btn-sm" href="?edit_id=<?= $row['kd_petugas'] ?>">Ubah</a>
                                <form method="post" style="display:inline" onsubmit="return confirm('Reset password ke default?');">
                                    <input type="hidden" name="kd_petugas" value="<?= $row['kd_petugas'] ?>">
                                    <button type="submit" name="reset_pass" class="btn btn-outline btn-sm">Reset Pass</button>
                                </form>
                                <form method="post" style="display:inline">
                                    <input type="hidden" name="kd_petugas" value="<?= $row['kd_petugas'] ?>">
                                    <button type="submit" name="toggle_status" class="btn <?= $row['status_aktif'] ? 'btn-danger' : 'btn-success' ?> btn-sm">
                                        <?= $row['status_aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($petugasList)): ?>
                        <tr><td colspan="5">Tidak ada data ditemukan.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>
</body>
</html>
