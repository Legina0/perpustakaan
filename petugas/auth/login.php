<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) {
    redirect_to_dashboard();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        // 1. Cek tabel petugas (admin & petugas)
        $stmt = $pdo->prepare("SELECT * FROM petugas WHERE user = ? LIMIT 1");
        $stmt->execute([$username]);
        $petugas = $stmt->fetch();

        if ($petugas) {
            if (!$petugas['status_aktif']) {
                $error = 'Akun dinonaktifkan. Hubungi Admin.';
            } elseif (password_verify($password, $petugas['password'])) {
                $_SESSION['user_id'] = $petugas['kd_petugas'];
                $_SESSION['nama']    = $petugas['nm_petugas'];
                $_SESSION['role']    = $petugas['role']; // admin | petugas
                redirect_to_dashboard();
            } else {
                $error = 'Username atau password salah.';
            }
        } else {
            // 2. Cek tabel anggota
            $stmt = $pdo->prepare("SELECT * FROM anggota WHERE user = ? LIMIT 1");
            $stmt->execute([$username]);
            $anggota = $stmt->fetch();

            if ($anggota && $anggota['status_aktif'] && password_verify($password, $anggota['password'])) {
                $_SESSION['user_id'] = $anggota['kd_anggota'];
                $_SESSION['nama']    = $anggota['nm_anggota'];
                $_SESSION['role']    = 'anggota';
                redirect_to_dashboard();
            } elseif ($anggota && !$anggota['status_aktif']) {
                $error = 'Akun dinonaktifkan. Hubungi petugas perpustakaan.';
            } else {
                $error = 'Username atau password salah.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - PerpusApp</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
</head>
<body>
<div class="login-wrapper">
    <div class="login-box">
        <h2>&#128218; Login PerpusApp</h2>
        <?php if ($error): ?>
            <div class="login-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post" action="">
            <label>Username / NIS / NIP</label>
            <input type="text" name="username" required autofocus value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">

            <label>Password</label>
            <div class="password-wrapper">
                <input type="password" name="password" id="loginPassword" required>
                <button type="button" class="toggle-password" aria-label="Tampilkan password"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
            </div>

            <button type="submit">LOGIN</button>
        </form>
        <p class="login-hint">Sistem otomatis mendeteksi peran: Anggota / Petugas / Admin</p>
        <hr style="border:none;border-top:1px solid #eee;margin:16px 0;">
        <p class="login-hint">Belum punya akun? Hubungi petugas perpustakaan</p>
    </div>
</div>
<script>
var ICON_EYE = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
var ICON_EYE_OFF = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a20.3 20.3 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a20.3 20.3 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';
document.addEventListener('click', function (e) {
    var btn = e.target.closest('.toggle-password');
    if (!btn) return;
    var input = btn.parentElement.querySelector('input');
    if (!input) return;
    if (input.type === 'password') {
        input.type = 'text';
        btn.innerHTML = ICON_EYE_OFF;
        btn.setAttribute('aria-label', 'Sembunyikan password');
    } else {
        input.type = 'password';
        btn.innerHTML = ICON_EYE;
        btn.setAttribute('aria-label', 'Tampilkan password');
    }
});
</script>
</body>
</html>
