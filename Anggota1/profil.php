<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['anggota']);

$kd_anggota = $_SESSION['user_id'];
$errors = [];
$buka_modal_password = false; // modal setting (ganti password) otomatis terbuka jika ada error

$stmt = $pdo->prepare("SELECT * FROM anggota WHERE kd_anggota = ?");
$stmt->execute([$kd_anggota]);
$profil = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['upload_foto'])) {
        [$foto_baru, $err] = simpan_upload_foto_profil($_FILES['foto'] ?? [], $profil['foto'] ?? null);
        if ($err) {
            $errors[] = $err;
        } elseif ($foto_baru) {
            $pdo->prepare("UPDATE anggota SET foto=? WHERE kd_anggota=?")->execute([$foto_baru, $kd_anggota]);
            flash_set('success', 'Foto profil berhasil diperbarui.');
            header('Location: ' . BASE_URL . '/anggota/profil.php');
            exit;
        } else {
            $errors[] = 'Pilih file foto terlebih dahulu.';
        }
    } elseif (isset($_POST['hapus_foto'])) {
        hapus_foto_profil($profil['foto'] ?? null);
        $pdo->prepare("UPDATE anggota SET foto=NULL WHERE kd_anggota=?")->execute([$kd_anggota]);
        flash_set('success', 'Foto profil dihapus.');
        header('Location: ' . BASE_URL . '/anggota/profil.php');
        exit;
    } elseif (isset($_POST['update_profil'])) {
        $nama = trim($_POST['nm_anggota'] ?? '');
        $alamat = trim($_POST['alamat'] ?? '');
        $telp = trim($_POST['no_telp'] ?? '');
        if ($nama === '') $errors[] = 'Nama tidak boleh kosong.';
        if (!is_valid_phone($telp)) $errors[] = 'No. Telepon harus berupa angka (8-15 digit).';

        if (!$errors) {
            $stmt = $pdo->prepare("UPDATE anggota SET nm_anggota=?, alamat=?, no_telp=? WHERE kd_anggota=?");
            $stmt->execute([$nama, $alamat, $telp, $kd_anggota]);
            $_SESSION['nama'] = $nama;
            flash_set('success', 'Profil berhasil diperbarui.');
            header('Location: ' . BASE_URL . '/anggota/profil.php');
            exit;
        }
    } elseif (isset($_POST['ganti_password'])) {
        $lama = $_POST['password_lama'] ?? '';
        $baru = $_POST['password_baru'] ?? '';
        if (!password_verify($lama, $profil['password'])) {
            $errors[] = 'Password lama salah.';
            $buka_modal_password = true;
        } elseif (strlen($baru) < 6) {
            $errors[] = 'Password baru minimal 6 karakter.';
            $buka_modal_password = true;
        } else {
            $hash = password_hash($baru, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE anggota SET password=? WHERE kd_anggota=?");
            $stmt->execute([$hash, $kd_anggota]);
            flash_set('success', 'Password berhasil diganti.');
            header('Location: ' . BASE_URL . '/anggota/profil.php');
            exit;
        }
    }
}

$foto_url = foto_profil_url($profil['foto'] ?? null);

$page_title = 'Profil';
$nama_user = $_SESSION['nama'];
$active = 'profil';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/sidebar.php';
?>

<?php render_errors_popup($errors); ?>

<style>
    :root {
        --hero-color: #1e293b;
    }
    .profile-hero {
        position: relative;
        border-radius: 20px;
        overflow: hidden;
        margin-bottom: 24px;
        background: var(--hero-color);
        color: #fff;
        padding: 40px 28px 32px;
        text-align: center;
        box-shadow: 0 12px 30px -10px rgba(0, 0, 0, 0.45);
        transition: background .25s ease;
    }
    .profile-hero::before {
        content: "";
        position: absolute;
        inset: 0;
        background-image: radial-gradient(circle at 15% 20%, rgba(255,255,255,0.18) 0, transparent 40%),
                           radial-gradient(circle at 85% 85%, rgba(255,255,255,0.15) 0, transparent 45%);
        pointer-events: none;
    }
    .settings-fab {
        position: absolute;
        top: 16px;
        right: 16px;
        width: 42px;
        height: 42px;
        border-radius: 50%;
        border: none;
        background: rgba(255,255,255,0.18);
        backdrop-filter: blur(4px);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: transform .25s ease, background .25s ease;
        z-index: 2;
    }
    .settings-fab:hover {
        background: rgba(255,255,255,0.32);
        transform: rotate(45deg);
    }
    .color-fab {
        position: absolute;
        top: 16px;
        right: 66px;
        width: 42px;
        height: 42px;
        border-radius: 50%;
        border: none;
        background: rgba(255,255,255,0.18);
        backdrop-filter: blur(4px);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: transform .25s ease, background .25s ease;
        z-index: 2;
    }
    .color-fab:hover {
        background: rgba(255,255,255,0.32);
        transform: scale(1.08);
    }
    .color-popover {
        position: absolute;
        top: 64px;
        right: 16px;
        width: 216px;
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 16px 34px -12px rgba(0,0,0,0.35);
        padding: 14px;
        z-index: 3;
        display: none;
        text-align: left;
    }
    .color-popover.open {
        display: block;
    }
    .color-popover-title {
        font-size: 13px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 10px;
    }
    .color-swatches {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 8px;
        margin-bottom: 12px;
    }
    .color-swatch {
        width: 26px;
        height: 26px;
        border-radius: 50%;
        border: 2px solid #fff;
        box-shadow: 0 0 0 1px #e5e7eb;
        cursor: pointer;
        padding: 0;
    }
    .color-swatch.active {
        box-shadow: 0 0 0 2px #1e293b;
    }
    .color-custom-row {
        display: flex;
        align-items: center;
        gap: 8px;
        border-top: 1px solid #f1f1f4;
        padding-top: 10px;
    }
    .color-custom-row input[type="color"] {
        width: 34px;
        height: 30px;
        border: none;
        background: none;
        padding: 0;
        cursor: pointer;
    }
    .color-custom-row span {
        font-size: 12.5px;
        color: #6b7280;
    }
    .color-reset-btn {
        margin-top: 10px;
        width: 100%;
        background: #f2f3f8;
        border: none;
        border-radius: 8px;
        padding: 8px;
        font-size: 12.5px;
        font-weight: 600;
        color: #374151;
        cursor: pointer;
        transition: background .2s ease;
    }
    .color-reset-btn:hover {
        background: #e5e7eb;
    }
    .profile-hero-content {
        position: relative;
        z-index: 1;
    }
    .avatar-ring {
        width: 128px;
        height: 128px;
        border-radius: 50%;
        margin: 0 auto 16px;
        padding: 4px;
        background: linear-gradient(135deg, #fff, rgba(255,255,255,0.4));
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 8px 20px -6px rgba(0,0,0,0.35);
    }
    .avatar-ring-inner {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        overflow: hidden;
        background: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .avatar-ring-inner img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .profile-hero-name {
        font-size: 22px;
        font-weight: 700;
        margin-bottom: 4px;
    }
    .profile-hero-code {
        font-size: 13px;
        opacity: .85;
        letter-spacing: .3px;
        margin-bottom: 22px;
    }
    .profile-photo-actions {
        display: flex;
        gap: 10px;
        align-items: center;
        justify-content: center;
        flex-wrap: wrap;
    }
    .profile-photo-actions .btn {
        border-color: rgba(255,255,255,0.55);
        color: #fff;
        background: rgba(255,255,255,0.08);
    }
    .profile-photo-actions .btn:hover {
        background: rgba(255,255,255,0.22);
    }
    .profile-photo-actions .btn-primary {
        background: #fff;
        color: #1e293b;
        border-color: #fff;
        font-weight: 600;
    }
    .profile-photo-actions .btn-primary:hover {
        background: #e2e8f0;
    }

    /* Modal pengaturan */
    .settings-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(20, 22, 35, 0.55);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        padding: 16px;
    }
    .settings-modal-overlay.open {
        display: flex;
    }
    .settings-modal-box {
        width: 100%;
        max-width: 620px;
        max-height: 88vh;
        overflow-y: auto;
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 20px 50px -15px rgba(0,0,0,0.35);
        animation: settingsModalIn .2s ease;
    }
    @keyframes settingsModalIn {
        from { opacity: 0; transform: translateY(12px) scale(.98); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }
    .settings-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 20px;
        border-bottom: 1px solid #eee;
        position: sticky;
        top: 0;
        background: #fff;
        border-radius: 16px 16px 0 0;
    }
    .settings-modal-title {
        font-size: 16px;
        font-weight: 700;
        color: #1e293b;
    }
    .settings-modal-close {
        border: none;
        background: #f2f3f8;
        width: 34px;
        height: 34px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        color: #555;
        font-size: 18px;
        line-height: 1;
        transition: background .2s ease;
    }
    .settings-modal-close:hover {
        background: #e5e7eb;
    }
    .settings-modal-body {
        padding: 22px 20px 24px;
    }

    /* Panel Data Profil di bawah kartu profil */
    .data-profil-panel {
        margin-bottom: 24px;
    }
</style>

<div class="profile-hero">
    <button type="button" class="color-fab" id="btnOpenColor" aria-label="Ubah Warna Profil" title="Ubah Warna Profil">
        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="13.5" cy="6.5" r=".5" fill="currentColor"></circle>
            <circle cx="17.5" cy="10.5" r=".5" fill="currentColor"></circle>
            <circle cx="8.5" cy="7.5" r=".5" fill="currentColor"></circle>
            <circle cx="6.5" cy="12.5" r=".5" fill="currentColor"></circle>
            <path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"></path>
        </svg>
    </button>

    <div class="color-popover" id="colorPopover">
        <div class="color-popover-title">Warna Tampilan Profil</div>
        <div class="color-swatches" id="colorSwatches"></div>
        <div class="color-custom-row">
            <input type="color" id="colorCustomInput" value="#1e293b">
            <span>Warna kustom</span>
        </div>
        <button type="button" class="color-reset-btn" id="btnResetColor">Reset ke Default</button>
    </div>

    <button type="button" class="settings-fab" id="btnOpenSettings" aria-label="Pengaturan Profil" title="Pengaturan Profil">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="3"></circle>
            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
        </svg>
    </button>

    <div class="profile-hero-content">
        <div class="avatar-ring">
            <div class="avatar-ring-inner" id="profileAvatar">
                <?php if ($foto_url): ?>
                    <img src="<?= e($foto_url) ?>" alt="Foto Profil">
                <?php else: ?>
                    <?= svg_avatar_placeholder() ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="profile-hero-name"><?= e($profil['nm_anggota']) ?></div>
        <div class="profile-hero-code">Kode Anggota: <?= e($profil['kd_anggota']) ?></div>

        <form method="post" enctype="multipart/form-data" class="profile-photo-actions">
            <label class="btn btn-outline btn-sm" style="display:inline-flex;align-items:center;gap:8px;cursor:pointer;">
                Pilih Foto
                <input id="fotoInput" type="file" name="foto" accept=".jpg,.jpeg,.png,.webp" style="display:none;">
            </label>
            <button class="btn btn-primary" type="submit" name="upload_foto" value="1">Unggah</button>
            <?php if ($foto_url): ?>
                <button class="btn btn-outline" type="submit" name="hapus_foto" value="1" data-confirm="Hapus foto profil?">Hapus Foto</button>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Panel Data Profil (tampil langsung di halaman, di bawah kartu profil) -->
<div class="panel data-profil-panel">
    <div class="panel-header"><h2>Data Profil</h2></div>
    <div class="panel-body">
        <form method="post">
            <div class="form-grid">
                <div class="form-group">
                    <label>Nama</label>
                    <input type="text" name="nm_anggota" value="<?= e($profil['nm_anggota']) ?>" required>
                </div>
                <div class="form-group">
                    <label>No. Telepon</label>
                    <input type="text" name="no_telp" inputmode="numeric" pattern="[0-9]{8,15}" title="Hanya angka, 8-15 digit" value="<?= e($profil['no_telp'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group" style="margin-bottom:18px;">
                <label>Alamat</label>
                <textarea name="alamat" rows="3"><?= e($profil['alamat'] ?? '') ?></textarea>
            </div>
            <button class="btn btn-primary" type="submit" name="update_profil" value="1">Simpan Profil</button>
        </form>
    </div>
</div>

<!-- Modal Pengaturan: hanya Ganti Password -->
<div class="settings-modal-overlay" id="settingsModalOverlay">
    <div class="settings-modal-box">
        <div class="settings-modal-header">
            <div class="settings-modal-title">Ganti Password</div>
            <button type="button" class="settings-modal-close" id="btnCloseSettings" aria-label="Tutup">&times;</button>
        </div>

        <div class="settings-modal-body">
            <form method="post">
                <div class="form-group" style="margin-bottom:16px;">
                    <label>Password Lama</label>
                    <div class="password-wrapper">
                        <input type="password" name="password_lama" required>
                        <button type="button" class="toggle-password" aria-label="Tampilkan password"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
                    </div>
                </div>
                <div class="form-group" style="margin-bottom:18px;">
                    <label>Password Baru</label>
                    <div class="password-wrapper">
                        <input type="password" name="password_baru" required>
                        <button type="button" class="toggle-password" aria-label="Tampilkan password"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
                    </div>
                </div>
                <button class="btn btn-primary" type="submit" name="ganti_password" value="1">Ganti Password</button>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Preview foto sebelum diunggah
    var input = document.getElementById('fotoInput');
    var avatar = document.getElementById('profileAvatar');
    if (input && avatar) {
        input.addEventListener('change', function (e) {
            var f = this.files && this.files[0];
            if (!f) return;
            var reader = new FileReader();
            reader.onload = function (ev) {
                avatar.innerHTML = '<img src="' + ev.target.result + '" alt="Preview">';
            };
            reader.readAsDataURL(f);
        });
    }

    // Modal pengaturan (Ganti Password)
    var overlay = document.getElementById('settingsModalOverlay');
    var btnOpen = document.getElementById('btnOpenSettings');
    var btnClose = document.getElementById('btnCloseSettings');

    function openModal() {
        closeColorPopover();
        overlay.classList.add('open');
    }
    function closeModal() {
        overlay.classList.remove('open');
    }

    if (btnOpen) btnOpen.addEventListener('click', openModal);
    if (btnClose) btnClose.addEventListener('click', closeModal);
    if (overlay) {
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) closeModal();
        });
    }
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeModal();
            closeColorPopover();
        }
    });

    // Ubah warna tampilan profil
    var DEFAULT_HERO_COLOR = '#1e293b';
    var STORAGE_KEY = 'profil_hero_color';
    var presetColors = ['#1e293b', '#0f172a', '#4f6ef7', '#7b5cf0', '#a855f7', '#dc2626', '#ea580c', '#d97706', '#16a34a', '#0d9488', '#0891b2', '#db2777'];

    var btnOpenColor = document.getElementById('btnOpenColor');
    var colorPopover = document.getElementById('colorPopover');
    var colorSwatchesWrap = document.getElementById('colorSwatches');
    var colorCustomInput = document.getElementById('colorCustomInput');
    var btnResetColor = document.getElementById('btnResetColor');

    function applyHeroColor(hex) {
        document.documentElement.style.setProperty('--hero-color', hex);
        if (colorCustomInput) colorCustomInput.value = hex;
        var swatchBtns = colorSwatchesWrap ? colorSwatchesWrap.querySelectorAll('.color-swatch') : [];
        swatchBtns.forEach(function (sw) {
            sw.classList.toggle('active', sw.dataset.color.toLowerCase() === hex.toLowerCase());
        });
    }

    function saveHeroColor(hex) {
        try { localStorage.setItem(STORAGE_KEY, hex); } catch (e) {}
    }

    function openColorPopover() {
        closeModal();
        colorPopover.classList.add('open');
    }
    function closeColorPopover() {
        if (colorPopover) colorPopover.classList.remove('open');
    }

    if (colorSwatchesWrap) {
        presetColors.forEach(function (hex) {
            var sw = document.createElement('button');
            sw.type = 'button';
            sw.className = 'color-swatch';
            sw.style.background = hex;
            sw.dataset.color = hex;
            sw.setAttribute('aria-label', 'Pilih warna ' + hex);
            sw.addEventListener('click', function () {
                applyHeroColor(hex);
                saveHeroColor(hex);
            });
            colorSwatchesWrap.appendChild(sw);
        });
    }

    if (btnOpenColor) {
        btnOpenColor.addEventListener('click', function () {
            if (colorPopover.classList.contains('open')) {
                closeColorPopover();
            } else {
                openColorPopover();
            }
        });
    }

    document.addEventListener('click', function (e) {
        if (!colorPopover) return;
        if (colorPopover.classList.contains('open') &&
            !colorPopover.contains(e.target) &&
            e.target !== btnOpenColor &&
            !btnOpenColor.contains(e.target)) {
            closeColorPopover();
        }
    });

    if (colorCustomInput) {
        colorCustomInput.addEventListener('input', function () {
            applyHeroColor(this.value);
        });
        colorCustomInput.addEventListener('change', function () {
            saveHeroColor(this.value);
        });
    }

    if (btnResetColor) {
        btnResetColor.addEventListener('click', function () {
            applyHeroColor(DEFAULT_HERO_COLOR);
            try { localStorage.removeItem(STORAGE_KEY); } catch (e) {}
        });
    }

    // Terapkan warna tersimpan (jika ada) saat halaman dimuat
    (function initHeroColor() {
        var saved = null;
        try { saved = localStorage.getItem(STORAGE_KEY); } catch (e) {}
        applyHeroColor(saved || DEFAULT_HERO_COLOR);
    })();

    <?php if ($buka_modal_password): ?>
    // Buka modal otomatis jika ada error saat ganti password
    openModal();
    <?php endif; ?>
});
</script>