<?php
/**
 * Dipakai di setiap halaman setelah require auth.
 * Variabel yang harus disediakan sebelum include file ini:
 * $page_title (string), $nama_user (string)
 */
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($page_title ?? 'PerpusApp') ?> - PerpusApp</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<div class="app">
