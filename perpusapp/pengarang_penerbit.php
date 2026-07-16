<?php
require 'config.php';
$active = 'pengarang_penerbit';
$pageTitle = 'Kelola Pengarang/Penerbit/Klasifikasi';

$tab = $_GET['tab'] ?? 'penerbit';
if (!in_array($tab, ['penerbit', 'pengarang', 'klasifikasi'])) $tab = 'penerbit';

$msg = '';
$msgType = 'success';

// ==== HANDLE FORM SUBMIT ====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formTab = $_POST['form_tab'] ?? '';

    try {
        // ---- PENERBIT ----
        if ($formTab === 'penerbit') {
            if (isset($_POST['add'])) {
                $stmt = $pdo->prepare("INSERT INTO penerbit (nm_penerbit, alamat) VALUES (?, ?)");
                $stmt->execute([trim($_POST['nm_penerbit']), trim($_POST['alamat'])]);
                $msg = 'Penerbit berhasil ditambahkan.';
            } elseif (isset($_POST['edit'])) {
                $stmt = $pdo->prepare("UPDATE penerbit SET nm_penerbit=?, alamat=? WHERE kd_penerbit=?");
                $stmt->execute([trim($_POST['nm_penerbit']), trim($_POST['alamat']), $_POST['kd_penerbit']]);
                $msg = 'Penerbit berhasil diubah.';
            } elseif (isset($_POST['delete'])) {
                $stmt = $pdo->prepare("DELETE FROM penerbit WHERE kd_penerbit=?");
                $stmt->execute([$_POST['kd_penerbit']]);
                $msg = 'Penerbit berhasil dihapus.';
            }
            $tab = 'penerbit';
        }

        // ---- PENGARANG ----
        if ($formTab === 'pengarang') {
            if (isset($_POST['add'])) {
                $stmt = $pdo->prepare("INSERT INTO pengarang (nm_pengarang, jenis_kelamin) VALUES (?, ?)");
                $stmt->execute([trim($_POST['nm_pengarang']), $_POST['jenis_kelamin']]);
                $msg = 'Pengarang berhasil ditambahkan.';
            } elseif (isset($_POST['edit'])) {
                $stmt = $pdo->prepare("UPDATE pengarang SET nm_pengarang=?, jenis_kelamin=? WHERE kd_pengarang=?");
                $stmt->execute([trim($_POST['nm_pengarang']), $_POST['jenis_kelamin'], $_POST['kd_pengarang']]);
                $msg = 'Pengarang berhasil diubah.';
            } elseif (isset($_POST['delete'])) {
                $stmt = $pdo->prepare("DELETE FROM pengarang WHERE kd_pengarang=?");
                $stmt->execute([$_POST['kd_pengarang']]);
                $msg = 'Pengarang berhasil dihapus.';
            }
            $tab = 'pengarang';
        }

        // ---- KLASIFIKASI ----
        if ($formTab === 'klasifikasi') {
            if (isset($_POST['add'])) {
                $stmt = $pdo->prepare("INSERT INTO klasifikasi (nm_klasifikasi) VALUES (?)");
                $stmt->execute([trim($_POST['nm_klasifikasi'])]);
                $msg = 'Klasifikasi berhasil ditambahkan.';
            } elseif (isset($_POST['edit'])) {
                $stmt = $pdo->prepare("UPDATE klasifikasi SET nm_klasifikasi=? WHERE kd_klasifikasi=?");
                $stmt->execute([trim($_POST['nm_klasifikasi']), $_POST['kd_klasifikasi']]);
                $msg = 'Klasifikasi berhasil diubah.';
            } elseif (isset($_POST['delete'])) {
                $stmt = $pdo->prepare("DELETE FROM klasifikasi WHERE kd_klasifikasi=?");
                $stmt->execute([$_POST['kd_klasifikasi']]);
                $msg = 'Klasifikasi berhasil dihapus.';
            }
            $tab = 'klasifikasi';
        }
    } catch (PDOException $e) {
        $msg = 'Gagal: ' . $e->getMessage();
        $msgType = 'danger';
    }
}

// ==== FETCH DATA ====
$penerbitList = $pdo->query("SELECT * FROM penerbit ORDER BY nm_penerbit ASC")->fetchAll();
$pengarangList = $pdo->query("SELECT * FROM pengarang ORDER BY nm_pengarang ASC")->fetchAll();
$klasifikasiList = $pdo->query("SELECT * FROM klasifikasi ORDER BY nm_klasifikasi ASC")->fetchAll();

// Data untuk mode edit (jika ada ?edit_id=)
$editPenerbit = null;
$editPengarang = null;
$editKlasifikasi = null;
if ($tab === 'penerbit' && isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM penerbit WHERE kd_penerbit=?");
    $stmt->execute([$_GET['edit_id']]);
    $editPenerbit = $stmt->fetch();
}
if ($tab === 'pengarang' && isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM pengarang WHERE kd_pengarang=?");
    $stmt->execute([$_GET['edit_id']]);
    $editPengarang = $stmt->fetch();
}
if ($tab === 'klasifikasi' && isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM klasifikasi WHERE kd_klasifikasi=?");
    $stmt->execute([$_GET['edit_id']]);
    $editKlasifikasi = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Pengarang/Penerbit/Klasifikasi - PerpusApp</title>
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

            <div class="tabs">
                <a href="?tab=penerbit" class="<?= $tab === 'penerbit' ? 'active' : '' ?>">Penerbit</a>
                <a href="?tab=pengarang" class="<?= $tab === 'pengarang' ? 'active' : '' ?>">Pengarang</a>
                <a href="?tab=klasifikasi" class="<?= $tab === 'klasifikasi' ? 'active' : '' ?>">Klasifikasi</a>
            </div>

            <?php if ($tab === 'penerbit'): ?>
                <div class="card">
                    <h2><?= $editPenerbit ? 'Ubah Penerbit' : 'Tambah Penerbit' ?></h2>
                    <form method="post">
                        <input type="hidden" name="form_tab" value="penerbit">
                        <?php if ($editPenerbit): ?>
                            <input type="hidden" name="kd_penerbit" value="<?= $editPenerbit['kd_penerbit'] ?>">
                        <?php endif; ?>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Nama Penerbit</label>
                                <input type="text" name="nm_penerbit" required value="<?= htmlspecialchars($editPenerbit['nm_penerbit'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label>Alamat</label>
                                <input type="text" name="alamat" value="<?= htmlspecialchars($editPenerbit['alamat'] ?? '') ?>">
                            </div>
                        </div>
                        <button type="submit" name="<?= $editPenerbit ? 'edit' : 'add' ?>" class="btn btn-primary">
                            <?= $editPenerbit ? 'Simpan Perubahan' : 'Tambah' ?>
                        </button>
                        <?php if ($editPenerbit): ?>
                            <a href="pengarang_penerbit.php?tab=penerbit" class="btn btn-outline">Batal</a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="card">
                    <h2>Daftar Penerbit</h2>
                    <table>
                        <thead><tr><th>Nama Penerbit</th><th>Alamat</th><th>Aksi</th></tr></thead>
                        <tbody>
                        <?php foreach ($penerbitList as $row): ?>
                            <tr>
                                <td class="link-name"><?= htmlspecialchars($row['nm_penerbit']) ?></td>
                                <td><?= htmlspecialchars($row['alamat']) ?></td>
                                <td>
                                    <a class="btn btn-outline btn-sm" href="?tab=penerbit&edit_id=<?= $row['kd_penerbit'] ?>">Ubah</a>
                                    <form method="post" style="display:inline" onsubmit="return confirm('Hapus penerbit ini?');">
                                        <input type="hidden" name="form_tab" value="penerbit">
                                        <input type="hidden" name="kd_penerbit" value="<?= $row['kd_penerbit'] ?>">
                                        <button type="submit" name="delete" class="btn btn-danger btn-sm">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($penerbitList)): ?>
                            <tr><td colspan="3">Belum ada data.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($tab === 'pengarang'): ?>
                <div class="card">
                    <h2><?= $editPengarang ? 'Ubah Pengarang' : 'Tambah Pengarang' ?></h2>
                    <form method="post">
                        <input type="hidden" name="form_tab" value="pengarang">
                        <?php if ($editPengarang): ?>
                            <input type="hidden" name="kd_pengarang" value="<?= $editPengarang['kd_pengarang'] ?>">
                        <?php endif; ?>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Nama Pengarang</label>
                                <input type="text" name="nm_pengarang" required value="<?= htmlspecialchars($editPengarang['nm_pengarang'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label>Jenis Kelamin</label>
                                <select name="jenis_kelamin">
                                    <option value="L" <?= (($editPengarang['jenis_kelamin'] ?? 'L') === 'L') ? 'selected' : '' ?>>Laki-laki</option>
                                    <option value="P" <?= (($editPengarang['jenis_kelamin'] ?? '') === 'P') ? 'selected' : '' ?>>Perempuan</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" name="<?= $editPengarang ? 'edit' : 'add' ?>" class="btn btn-primary">
                            <?= $editPengarang ? 'Simpan Perubahan' : 'Tambah' ?>
                        </button>
                        <?php if ($editPengarang): ?>
                            <a href="pengarang_penerbit.php?tab=pengarang" class="btn btn-outline">Batal</a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="card">
                    <h2>Daftar Pengarang</h2>
                    <table>
                        <thead><tr><th>Nama Pengarang</th><th>Jenis Kelamin</th><th>Aksi</th></tr></thead>
                        <tbody>
                        <?php foreach ($pengarangList as $row): ?>
                            <tr>
                                <td class="link-name"><?= htmlspecialchars($row['nm_pengarang']) ?></td>
                                <td><?= $row['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></td>
                                <td>
                                    <a class="btn btn-outline btn-sm" href="?tab=pengarang&edit_id=<?= $row['kd_pengarang'] ?>">Ubah</a>
                                    <form method="post" style="display:inline" onsubmit="return confirm('Hapus pengarang ini?');">
                                        <input type="hidden" name="form_tab" value="pengarang">
                                        <input type="hidden" name="kd_pengarang" value="<?= $row['kd_pengarang'] ?>">
                                        <button type="submit" name="delete" class="btn btn-danger btn-sm">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($pengarangList)): ?>
                            <tr><td colspan="3">Belum ada data.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($tab === 'klasifikasi'): ?>
                <div class="card">
                    <h2><?= $editKlasifikasi ? 'Ubah Klasifikasi' : 'Tambah Klasifikasi' ?></h2>
                    <form method="post">
                        <input type="hidden" name="form_tab" value="klasifikasi">
                        <?php if ($editKlasifikasi): ?>
                            <input type="hidden" name="kd_klasifikasi" value="<?= $editKlasifikasi['kd_klasifikasi'] ?>">
                        <?php endif; ?>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Nama Klasifikasi</label>
                                <input type="text" name="nm_klasifikasi" required value="<?= htmlspecialchars($editKlasifikasi['nm_klasifikasi'] ?? '') ?>">
                            </div>
                        </div>
                        <button type="submit" name="<?= $editKlasifikasi ? 'edit' : 'add' ?>" class="btn btn-primary">
                            <?= $editKlasifikasi ? 'Simpan Perubahan' : 'Tambah' ?>
                        </button>
                        <?php if ($editKlasifikasi): ?>
                            <a href="pengarang_penerbit.php?tab=klasifikasi" class="btn btn-outline">Batal</a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="card">
                    <h2>Daftar Klasifikasi</h2>
                    <table>
                        <thead><tr><th>Nama Klasifikasi</th><th>Aksi</th></tr></thead>
                        <tbody>
                        <?php foreach ($klasifikasiList as $row): ?>
                            <tr>
                                <td class="link-name"><?= htmlspecialchars($row['nm_klasifikasi']) ?></td>
                                <td>
                                    <a class="btn btn-outline btn-sm" href="?tab=klasifikasi&edit_id=<?= $row['kd_klasifikasi'] ?>">Ubah</a>
                                    <form method="post" style="display:inline" onsubmit="return confirm('Hapus klasifikasi ini?');">
                                        <input type="hidden" name="form_tab" value="klasifikasi">
                                        <input type="hidden" name="kd_klasifikasi" value="<?= $row['kd_klasifikasi'] ?>">
                                        <button type="submit" name="delete" class="btn btn-danger btn-sm">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($klasifikasiList)): ?>
                            <tr><td colspan="2">Belum ada data.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>
</body>
</html>
