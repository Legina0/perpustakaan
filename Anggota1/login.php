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

<style>
    /* ==== Restyle halaman login saja (tidak menyentuh style.css global) ==== */
    * { box-sizing: border-box; }

    .login-wrapper {
        position: relative;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        background: #0f172a;
        padding: 24px;
    }

    /* ==== Hiasan buku di pinggir kiri & kanan (SVG, bukan emoji) ==== */
    .book-decor {
        position: absolute;
        z-index: 1;
        pointer-events: none;
        opacity: 0.55;
        filter: drop-shadow(0 10px 16px rgba(0,0,0,0.25));
    }
    .book-decor svg { display: block; }

    .book-decor--tl { top: 6%;  left: 4%;  width: 92px;  animation: bookFloat 7s ease-in-out infinite; }
    .book-decor--bl { bottom: 8%; left: 8%; width: 118px; animation: bookFloat 9s ease-in-out infinite .5s; }
    .book-decor--tr { top: 10%; right: 5%; width: 100px; animation: bookFloat 8s ease-in-out infinite .3s; }
    .book-decor--br { bottom: 6%; right: 7%; width: 96px;  animation: bookFloat 6.5s ease-in-out infinite .8s; }

    @keyframes bookFloat {
        0%, 100% { transform: translateY(0) rotate(var(--rot, -6deg)); }
        50% { transform: translateY(-14px) rotate(var(--rot, -6deg)); }
    }
    .book-decor--tl { --rot: -8deg; }
    .book-decor--bl { --rot: 6deg; }
    .book-decor--tr { --rot: 7deg; }
    .book-decor--br { --rot: -5deg; }

    @media (max-width: 900px) {
        .book-decor { display: none; }
    }

    .login-box {
        position: relative;
        z-index: 2;
        width: 100%;
        max-width: 380px;
        background: #1e293b;
        border-radius: 22px;
        padding: 40px 34px 32px;
        box-shadow: 0 25px 60px -15px rgba(0,0,0,0.55);
        animation: loginIn .5s cubic-bezier(.22,1,.36,1);
    }
    @keyframes loginIn {
        from { opacity: 0; transform: translateY(18px) scale(.97); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }

    .login-box h2 {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        font-size: 22px;
        font-weight: 800;
        color: #f1f5f9;
        margin: 0 0 6px;
        text-align: center;
    }
    .login-box h2 .emoji-badge {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        background: #33415a;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }
    .login-subtitle {
        text-align: center;
        font-size: 12.5px;
        color: #94a3b8;
        margin-bottom: 26px;
    }

    .login-box .login-error {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #b91c1c;
        font-size: 13px;
        padding: 10px 14px;
        border-radius: 10px;
        margin-bottom: 18px;
        animation: shake .35s ease;
    }
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-4px); }
        75% { transform: translateX(4px); }
    }

    .login-box form {
        display: flex;
        flex-direction: column;
    }
    .login-box label {
        font-size: 12.5px;
        font-weight: 600;
        color: #cbd5e1;
        margin-bottom: 6px;
        margin-top: 14px;
    }
    .login-box label:first-of-type {
        margin-top: 0;
    }
    .login-box input[type="text"],
    .login-box input[type="password"] {
        width: 100%;
        border: 1.5px solid #334155;
        border-radius: 12px;
        padding: 12px 14px;
        font-size: 14px;
        color: #e2e8f0;
        background: #0f172a;
        transition: border-color .2s ease, box-shadow .2s ease;
    }
    .login-box input[type="text"]:focus,
    .login-box input[type="password"]:focus {
        outline: none;
        border-color: #6d8cff;
        box-shadow: 0 0 0 4px rgba(109,140,255,0.25);
    }

    .password-wrapper {
        position: relative;
        display: flex;
    }
    .password-wrapper input {
        padding-right: 42px !important;
    }
    .toggle-password {
        position: absolute;
        right: 6px;
        top: 50%;
        transform: translateY(-50%);
        border: none;
        background: transparent;
        color: #94a3b8;
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: color .2s ease, background .2s ease;
    }
    .toggle-password:hover {
        color: #6d8cff;
        background: rgba(255,255,255,0.08);
    }

    .login-box button[type="submit"] {
        margin-top: 24px;
        border: none;
        border-radius: 12px;
        padding: 13px 16px;
        font-size: 14.5px;
        font-weight: 700;
        letter-spacing: .4px;
        color: #fff;
        background: #4f6ef7;
        cursor: pointer;
        box-shadow: 0 10px 22px -8px rgba(79,110,247,0.6);
        transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
    }
    .login-box button[type="submit"]:hover {
        transform: translateY(-2px);
        box-shadow: 0 14px 26px -8px rgba(79,110,247,0.7);
        background: #3f5be0;
    }
    .login-box button[type="submit"]:active {
        transform: translateY(0);
    }

    .login-box .login-hint {
        text-align: center;
        font-size: 12px;
        color: #94a3b8;
        margin: 14px 0 0;
    }
    .login-box hr {
        border: none;
        border-top: 1px solid #334155;
        margin: 18px 0 !important;
    }
</style>

</head>
<body>
<div class="login-wrapper">

    <!-- Hiasan tumpukan buku (SVG, bukan emoji) -->
    <div class="book-decor book-decor--tl" aria-hidden="true">
        <svg viewBox="0 0 100 70" xmlns="http://www.w3.org/2000/svg">
            <rect x="4"  y="40" width="70" height="14" rx="2" fill="#4f6ef7"/>
            <rect x="10" y="26" width="70" height="14" rx="2" fill="#fbbf24"/>
            <rect x="2"  y="12" width="70" height="14" rx="2" fill="#f87171"/>
            <rect x="4"  y="40" width="70" height="4" fill="rgba(0,0,0,0.15)"/>
            <rect x="10" y="26" width="70" height="4" fill="rgba(0,0,0,0.15)"/>
            <rect x="2"  y="12" width="70" height="4" fill="rgba(0,0,0,0.15)"/>
        </svg>
    </div>

    <div class="book-decor book-decor--bl" aria-hidden="true">
        <svg viewBox="0 0 120 90" xmlns="http://www.w3.org/2000/svg">
            <rect x="6"  y="20" width="26" height="60" rx="2" fill="#34d399"/>
            <rect x="34" y="10" width="26" height="70" rx="2" fill="#4f6ef7"/>
            <rect x="62" y="26" width="26" height="54" rx="2" fill="#f87171"/>
            <rect x="6"  y="20" width="6" height="60" fill="rgba(0,0,0,0.18)"/>
            <rect x="34" y="10" width="6" height="70" fill="rgba(0,0,0,0.18)"/>
            <rect x="62" y="26" width="6" height="54" fill="rgba(0,0,0,0.18)"/>
        </svg>
    </div>

    <div class="book-decor book-decor--tr" aria-hidden="true">
        <svg viewBox="0 0 110 90" xmlns="http://www.w3.org/2000/svg">
            <rect x="10" y="24" width="24" height="58" rx="2" fill="#fbbf24"/>
            <rect x="38" y="14" width="24" height="68" rx="2" fill="#34d399"/>
            <rect x="66" y="28" width="24" height="54" rx="2" fill="#4f6ef7"/>
            <rect x="10" y="24" width="6" height="58" fill="rgba(0,0,0,0.18)"/>
            <rect x="38" y="14" width="6" height="68" fill="rgba(0,0,0,0.18)"/>
            <rect x="66" y="28" width="6" height="54" fill="rgba(0,0,0,0.18)"/>
        </svg>
    </div>

    <div class="book-decor book-decor--br" aria-hidden="true">
        <svg viewBox="0 0 100 70" xmlns="http://www.w3.org/2000/svg">
            <rect x="4"  y="12" width="70" height="14" rx="2" fill="#f87171"/>
            <rect x="10" y="26" width="70" height="14" rx="2" fill="#4f6ef7"/>
            <rect x="2"  y="40" width="70" height="14" rx="2" fill="#fbbf24"/>
            <rect x="4"  y="12" width="70" height="4" fill="rgba(0,0,0,0.15)"/>
            <rect x="10" y="26" width="70" height="4" fill="rgba(0,0,0,0.15)"/>
            <rect x="2"  y="40" width="70" height="4" fill="rgba(0,0,0,0.15)"/>
        </svg>
    </div>

    <div class="login-box">
        <h2><span class="emoji-badge">&#128218;</span>Login PerpusApp</h2>
        <div class="login-subtitle">Masuk untuk mengakses akun perpustakaan Anda</div>

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

        <hr>
        <p class="login-hint">Sistem otomatis mendeteksi peran: Anggota / Petugas / Admin</p>
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