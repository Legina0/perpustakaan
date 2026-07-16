<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_role(['petugas', 'admin']);

$kd = $_SESSION['user_id'];
$errors = [];

$stmt = $pdo->prepare("SELECT * FROM petugas WHERE kd_petugas=?");
$stmt->execute([$kd]);
$profil = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['upload_foto'])) {
        [$foto_baru, $err] = simpan_upload_foto_profil($_FILES['foto'] ?? [], $profil['foto'] ?? null);
        if ($err) {
            $errors[] = $err;
        } elseif ($foto_baru) {
            $pdo->prepare("UPDATE petugas SET foto=? WHERE kd_petugas=?")->execute([$foto_baru, $kd]);
            flash_set('success', 'Foto profil berhasil diperbarui.');
            header('Location: ' . BASE_URL . '/profil.php');
            exit;
        } else {
            $errors[] = 'Pilih file foto terlebih dahulu.';
        }
    } elseif (isset($_POST['hapus_foto'])) {
        hapus_foto_profil($profil['foto'] ?? null);
        $pdo->prepare("UPDATE petugas SET foto=NULL WHERE kd_petugas=?")->execute([$kd]);
        flash_set('success', 'Foto profil dihapus.');
        header('Location: ' . BASE_URL . '/profil.php');
        exit;
    } elseif (isset($_POST['update_profil'])) {
        $nama = trim($_POST['nm_petugas'] ?? '');
        $alamat = trim($_POST['alamat'] ?? '');
        $telp = trim($_POST['telp'] ?? '');
        if ($nama === '') $errors[] = 'Nama tidak boleh kosong.';
        if (!is_valid_phone($telp)) $errors[] = 'No. Telepon harus berupa angka (8-15 digit).';
        if (!$errors) {
            $pdo->prepare("UPDATE petugas SET nm_petugas=?, alamat=?, telp=? WHERE kd_petugas=?")
                ->execute([$nama, $alamat, $telp, $kd]);
            $_SESSION['nama'] = $nama;
            flash_set('success', 'Profil berhasil diperbarui.');
            header('Location: ' . BASE_URL . '/profil.php');
            exit;
        }
    } elseif (isset($_POST['ganti_password'])) {
        $lama = $_POST['password_lama'] ?? '';
        $baru = $_POST['password_baru'] ?? '';
        if (!password_verify($lama, $profil['password'])) {
            $errors[] = 'Password lama salah.';
        } elseif (strlen($baru) < 6) {
            $errors[] = 'Password baru minimal 6 karakter.';
        } else {
            $pdo->prepare("UPDATE petugas SET password=? WHERE kd_petugas=?")
                ->execute([password_hash($baru, PASSWORD_BCRYPT), $kd]);
            flash_set('success', 'Password berhasil diganti.');
            header('Location: ' . BASE_URL . '/profil.php');
            exit;
        }
    }
}

$foto_url = foto_profil_url($profil['foto'] ?? null);

$page_title = 'Profil';
$nama_user = $_SESSION['nama'];
$active = 'profil';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/sidebar.php';
?>

<?php render_errors_popup($errors); ?>

<div class="panel" style="margin-bottom:24px;">
    <div class="panel-header"><h2>Foto Profil</h2></div>
    <div class="panel-body" style="display:flex;align-items:center;gap:20px;flex-wrap:wrap;">
        <div class="avatar-preview-large">
            <?php if ($foto_url): ?>
                <img src="<?= e($foto_url) ?>" alt="Foto Profil">
            <?php else: ?>
                <?= svg_avatar_placeholder() ?>
            <?php endif; ?>
        </div>
        <form method="post" enctype="multipart/form-data" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
            <input type="file" name="foto" accept=".jpg,.jpeg,.png,.webp">
            <button class="btn btn-primary" type="submit" name="upload_foto" value="1">Ganti Foto</button>
            <?php if ($foto_url): ?>
                <button class="btn btn-outline" type="submit" name="hapus_foto" value="1" onclick="return confirm('Hapus foto profil?');">Hapus Foto</button>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="grid-2">
    <div class="panel">
        <div class="panel-header"><h2>Data Profil</h2></div>
        <div class="panel-body">
            <form method="post">
                <div class="form-grid">
                    <div class="form-group"><label>Nama</label><input type="text" name="nm_petugas" value="<?= e($profil['nm_petugas']) ?>" required></div>
                    <div class="form-group"><label>No. Telepon</label><input type="text" name="telp" inputmode="numeric" pattern="[0-9]{8,15}" title="Hanya angka, 8-15 digit" value="<?= e($profil['telp'] ?? '') ?>"></div>
                </div>
                <div class="form-group" style="margin-bottom:18px;"><label>Alamat</label><textarea name="alamat" rows="3"><?= e($profil['alamat'] ?? '') ?></textarea></div>
                <button class="btn btn-primary" type="submit" name="update_profil" value="1">Simpan Profil</button>
            </form>
        </div>
    </div>
    <div class="panel">
        <div class="panel-header"><h2>Ganti Password</h2></div>
        <div class="panel-body">
            <form method="post">
                <div class="form-group" style="margin-bottom:16px;"><label>Password Lama</label><div class="password-wrapper"><input type="password" name="password_lama" required><button type="button" class="toggle-password" aria-label="Tampilkan password"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button></div></div>
                <div class="form-group" style="margin-bottom:18px;"><label>Password Baru</label><div class="password-wrapper"><input type="password" name="password_baru" required><button type="button" class="toggle-password" aria-label="Tampilkan password"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button></div></div>
                <button class="btn btn-primary" type="submit" name="ganti_password" value="1">Ganti Password</button>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
