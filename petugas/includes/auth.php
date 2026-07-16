<?php
/**
 * Fungsi-fungsi autentikasi berbasis session PHP.
 * Panggil require_once ini setelah database.php di setiap halaman.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in(): bool
{
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

function current_role(): ?string
{
    return $_SESSION['role'] ?? null;
}

/**
 * Wajib login. Jika belum login, redirect ke halaman login.
 */
function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
}

/**
 * Wajib login DAN salah satu dari role yang diizinkan.
 * Contoh: require_role(['admin']); atau require_role(['admin','petugas']);
 */
function require_role(array $allowed_roles): void
{
    require_login();
    if (!in_array(current_role(), $allowed_roles, true)) {
        http_response_code(403);
        die('<h2>403 - Akses ditolak</h2><p>Anda tidak memiliki hak akses ke halaman ini.</p><a href="<?= BASE_URL ?>/auth/login.php">Kembali ke Login</a>');
    }
}

/**
 * Redirect user ke dashboard sesuai role-nya.
 */
function redirect_to_dashboard(): void
{
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}
