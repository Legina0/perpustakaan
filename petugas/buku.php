<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_role(['petugas', 'admin']);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_buku'])) {
    $id = $_POST['kd_buku'] ?? null;
    $judul = trim($_POST['judul'] ?? '');
    $kd_penerbit = $_POST['kd_penerbit'] ?: null;
    $kd_klasifikasi = $_POST['kd_klasifikasi'] ?: null;
    $kd_pengarang = $_POST['kd_pengarang'] ?: null;
    $thn_terbit = $_POST['thn_terbit'] ?: null;
    $bahasa = trim($_POST['bahasa'] ?? '');
    $edisi = trim($_POST['edisi'] ?? '');
    $isbn = trim($_POST['isbn'] ?? '');
    $jumlah = (int)($_POST['jumlah'] ?? 0);

    if ($judul === '') $errors[] = 'Judul wajib diisi.';
    if ($thn_terbit !== null && (!ctype_digit((string)$thn_terbit) || (int)$thn_terbit < 1900 || (int)$thn_terbit > (int)date('Y'))) {
        $errors[] = 'Tahun terbit tidak valid (harus angka antara 1900 - ' . date('Y') . ').';
    }

    if (!$errors) {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE buku SET judul=?, kd_penerbit=?, kd_klasifikasi=?, kd_pengarang=?, thn_terbit=?, bahasa=?, edisi=?, isbn=?, jumlah=? WHERE kd_buku=?");
            $stmt->execute([$judul, $kd_penerbit, $kd_klasifikasi, $kd_pengarang, $thn_terbit, $bahasa, $edisi, $isbn, $jumlah, $id]);
            flash_set('success', 'Data buku berhasil diperbarui.');
        } else {
            $stmt = $pdo->prepare("INSERT INTO buku (judul, kd_penerbit, kd_klasifikasi, kd_pengarang, thn_terbit, bahasa, edisi, isbn, jumlah) VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$judul, $kd_penerbit, $kd_klasifikasi, $kd_pengarang, $thn_terbit, $bahasa, $edisi, $isbn, $jumlah]);
            $kd_buku_baru = $pdo->lastInsertId();
            $stmt2 = $pdo->prepare("INSERT INTO inventaris (kd_buku, no_buku, tgl_masuk, status) VALUES (?,?,CURDATE(),'Tersedia')");
            for ($i = 1; $i <= $jumlah; $i++) {
                $stmt2->execute([$kd_buku_baru, 'B' . str_pad($kd_buku_baru, 3, '0', STR_PAD_LEFT) . '-' . $i]);
            }
            flash_set('success', 'Buku baru berhasil ditambahkan beserta eksemplar inventaris.');
        }
        header('Location: ' . BASE_URL . '/buku.php');
        exit;
    }
}

if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $cek = $pdo->prepare("SELECT COUNT(*) c FROM inventaris WHERE kd_buku=? AND status='Dipinjam'");
    $cek->execute([$id]);
    if ($cek->fetch()['c'] > 0) {
        flash_set('error', 'Buku tidak bisa dihapus karena masih ada eksemplar yang sedang dipinjam.');
    } else {
        $pdo->prepare("DELETE FROM inventaris WHERE kd_buku=?")->execute([$id]);
        $pdo->prepare("DELETE FROM buku WHERE kd_buku=?")->execute([$id]);
        flash_set('success', 'Buku berhasil dihapus.');
    }
    header('Location: ' . BASE_URL . '/buku.php');
    exit;
}

$edit_data = null;
if (($_GET['action'] ?? '') === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM buku WHERE kd_buku=?");
    $stmt->execute([$_GET['id']]);
    $edit_data = $stmt->fetch();
}

$q = trim($_GET['q'] ?? '');
$stmt = $pdo->prepare("SELECT b.*, pn.nm_penerbit, k.nm_klasifikasi, pe.nm_pengarang,
        (SELECT COUNT(*) FROM inventaris i WHERE i.kd_buku=b.kd_buku AND i.status='Tersedia') tersedia,
        (SELECT COUNT(*) FROM inventaris i WHERE i.kd_buku=b.kd_buku) total_eks
    FROM buku b
    LEFT JOIN penerbit pn ON pn.kd_penerbit=b.kd_penerbit
    LEFT JOIN klasifikasi k ON k.kd_klasifikasi=b.kd_klasifikasi
    LEFT JOIN pengarang pe ON pe.kd_pengarang=b.kd_pengarang
    WHERE b.judul LIKE ?
    ORDER BY b.judul");
$stmt->execute(["%$q%"]);
$buku_list = $stmt->fetchAll();

// ---- Render baris tabel (dipakai untuk halaman penuh maupun respons AJAX live search) ----
ob_start();
if (empty($buku_list)): ?>
    <tr><td colspan="6" class="empty-state">Belum ada data buku.</td></tr>
<?php else: foreach ($buku_list as $b): ?>
    <tr>
        <td><?= e($b['judul']) ?></td>
        <td><?= e($b['nm_pengarang'] ?? '-') ?></td>
        <td><?= e($b['nm_penerbit'] ?? '-') ?></td>
        <td><?= e($b['nm_klasifikasi'] ?? '-') ?></td>
        <td><?= (int)$b['tersedia'] ?>/<?= (int)$b['total_eks'] ?></td>
        <td>
            <a class="btn btn-outline btn-sm" href="?action=edit&id=<?= (int)$b['kd_buku'] ?>">Ubah</a>
            <a class="btn btn-danger btn-sm" href="?hapus=<?= (int)$b['kd_buku'] ?>" data-confirm="Hapus buku ini beserta eksemplarnya?">Hapus</a>
        </td>
    </tr>
<?php endforeach; endif;
$baris_buku_html = ob_get_clean();

if (isset($_GET['ajax'])) {
    echo $baris_buku_html;
    exit;
}

$penerbit_list = $pdo->query("SELECT * FROM penerbit ORDER BY nm_penerbit")->fetchAll();
$klasifikasi_list = $pdo->query("SELECT * FROM klasifikasi ORDER BY nm_klasifikasi")->fetchAll();
$pengarang_list = $pdo->query("SELECT * FROM pengarang ORDER BY nm_pengarang")->fetchAll();

$page_title = 'Data Buku';
$nama_user = $_SESSION['nama'];
$active = 'buku';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/sidebar.php';
?>

<?php render_errors_popup($errors); ?>

<div class="panel">
    <div class="panel-header"><h2><?= $edit_data ? 'Ubah Buku' : 'Tambah Buku Baru' ?></h2></div>
    <div class="panel-body">
        <form method="post">
            <?php if ($edit_data): ?><input type="hidden" name="kd_buku" value="<?= (int)$edit_data['kd_buku'] ?>"><?php endif; ?>
            <div class="form-grid">
                <div class="form-group">
                    <label>Judul</label>
                    <input type="text" name="judul" value="<?= e($edit_data['judul'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Penerbit</label>
                    <select name="kd_penerbit">
                        <option value="">-- Pilih --</option>
                        <?php foreach ($penerbit_list as $p): ?>
                            <option value="<?= (int)$p['kd_penerbit'] ?>" <?= ($edit_data['kd_penerbit'] ?? null) == $p['kd_penerbit'] ? 'selected' : '' ?>><?= e($p['nm_penerbit']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Pengarang</label>
                    <select name="kd_pengarang">
                        <option value="">-- Pilih --</option>
                        <?php foreach ($pengarang_list as $p): ?>
                            <option value="<?= (int)$p['kd_pengarang'] ?>" <?= ($edit_data['kd_pengarang'] ?? null) == $p['kd_pengarang'] ? 'selected' : '' ?>><?= e($p['nm_pengarang']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Klasifikasi</label>
                    <select name="kd_klasifikasi">
                        <option value="">-- Pilih --</option>
                        <?php foreach ($klasifikasi_list as $p): ?>
                            <option value="<?= (int)$p['kd_klasifikasi'] ?>" <?= ($edit_data['kd_klasifikasi'] ?? null) == $p['kd_klasifikasi'] ? 'selected' : '' ?>><?= e($p['nm_klasifikasi']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Tahun Terbit</label>
                    <input type="number" name="thn_terbit" min="1900" max="<?= (int)date('Y') ?>" step="1" value="<?= e($edit_data['thn_terbit'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Bahasa</label>
                    <input type="text" name="bahasa" value="<?= e($edit_data['bahasa'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Edisi</label>
                    <input type="text" name="edisi" value="<?= e($edit_data['edisi'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>ISBN</label>
                    <input type="text" name="isbn" value="<?= e($edit_data['isbn'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Jumlah Eksemplar <?= $edit_data ? '(tidak diubah otomatis di sini)' : '' ?></label>
                    <input type="number" name="jumlah" min="0" value="<?= e($edit_data['jumlah'] ?? 1) ?>" <?= $edit_data ? 'readonly' : '' ?>>
                </div>
            </div>
            <button class="btn btn-primary" type="submit" name="simpan_buku" value="1"><?= $edit_data ? 'Simpan Perubahan' : 'Tambah Buku' ?></button>
            <?php if ($edit_data): ?><a href="<?= BASE_URL ?>/petugas/buku.php" class="btn btn-outline">Batal</a><?php endif; ?>
        </form>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <h2>Daftar Buku</h2>
        <form class="search-box" method="get" data-live-search data-target="#tbody-buku">
            <input type="text" name="q" placeholder="Cari judul..." value="<?= e($q) ?>">
            <button class="btn btn-primary" type="submit">Cari</button>
        </form>
    </div>
    <div class="panel-body" style="padding:0">
        <table>
            <thead><tr><th>Judul</th><th>Pengarang</th><th>Penerbit</th><th>Klasifikasi</th><th>Ketersediaan</th><th>Aksi</th></tr></thead>
            <tbody id="tbody-buku"><?= $baris_buku_html ?></tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
