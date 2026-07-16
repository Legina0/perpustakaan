<?php
/**
 * Kumpulan fungsi bantu yang dipakai di banyak halaman.
 */

function rupiah($angka): string
{
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

function tgl_indo($tanggal): string
{
    if (!$tanggal) return '-';
    $bulan = ['', 'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $t = strtotime($tanggal);
    return date('d', $t) . ' ' . $bulan[(int)date('n', $t)] . ' ' . date('Y', $t);
}

/**
 * Ambil nilai konfigurasi sistem (mis. lama_pinjam_hari, tarif_denda_per_hari)
 */
function get_konfigurasi(PDO $pdo, string $key, $default = null)
{
    $stmt = $pdo->prepare("SELECT `value` FROM konfigurasi WHERE `key` = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? $row['value'] : $default;
}

function set_konfigurasi(PDO $pdo, string $key, string $value): void
{
    $stmt = $pdo->prepare("INSERT INTO konfigurasi (`key`, `value`) VALUES (?, ?)
                            ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
    $stmt->execute([$key, $value]);
}

/**
 * Hitung jumlah hari terlambat antara tanggal_kembali_aktual dan tgl_harus_kembali.
 * Return 0 jika tidak terlambat.
 */
function hitung_hari_terlambat(string $tgl_harus_kembali, string $tgl_kembali_aktual): int
{
    $harus = new DateTime($tgl_harus_kembali);
    $aktual = new DateTime($tgl_kembali_aktual);
    if ($aktual <= $harus) {
        return 0;
    }
    return (int)$harus->diff($aktual)->days;
}

function badge_status(string $status): string
{
    $map = [
        'Tersedia' => 'badge-hijau',
        'Dipinjam' => 'badge-kuning',
        'Rusak'    => 'badge-merah',
        'Hilang'   => 'badge-merah',
        'Kembali'  => 'badge-hijau',
        'Terlambat'=> 'badge-merah',
        'Aktif'    => 'badge-hijau',
        'Nonaktif' => 'badge-abu',
        'Lunas'    => 'badge-hijau',
        'Belum Lunas' => 'badge-merah',
    ];
    $class = $map[$status] ?? 'badge-abu';
    return '<span class="badge ' . $class . '">' . htmlspecialchars($status) . '</span>';
}

function e(string $str): string
{
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * ================== FOTO PROFIL ==================
 * Semua foto profil (anggota/petugas/admin) disimpan sebagai file fisik
 * di folder assets/uploads/profil/, dan nama filenya disimpan di kolom
 * `foto` (VARCHAR) pada tabel anggota / petugas.
 *
 * Kalau nama kolom foto di database kamu BEDA dari "foto", tinggal ganti
 * semua `foto` di query SELECT/UPDATE pada anggota/profil.php dan
 * petugas/profil.php dengan nama kolom kamu.
 */

/** Path folder fisik tempat menyimpan foto profil di server. */
function foto_profil_dir(): string
{
    return __DIR__ . '/../assets/uploads/profil/';
}

/**
 * Ubah nama file foto (yang tersimpan di DB) jadi URL yang bisa diakses browser.
 * Return null kalau tidak ada foto / file-nya tidak ditemukan di server.
 */
function foto_profil_url(?string $foto): ?string
{
    if (!$foto) return null;
    $path = foto_profil_dir() . $foto;
    if (!is_file($path)) return null;
    return BASE_URL . '/assets/uploads/profil/' . rawurlencode($foto) . '?v=' . filemtime($path);
}

/**
 * Proses file yang diupload lewat <input type="file" name="foto">.
 * Panggil ini SETELAH validasi lain lolos, biasanya begini pemakaiannya:
 *
 *   [$foto_baru, $err] = simpan_upload_foto_profil($_FILES['foto'] ?? [], $profil['foto'] ?? null);
 *   if ($err) { $errors[] = $err; }
 *   elseif ($foto_baru) { // simpan $foto_baru ke kolom foto di DB }
 *
 * Return [nama_file_baru|null, pesan_error|null].
 * Kalau user tidak memilih file sama sekali -> [null, null] (bukan error, dianggap "tidak diubah").
 */
function simpan_upload_foto_profil(array $file, ?string $foto_lama): array
{
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return [null, null];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return [null, 'Upload foto gagal, silakan coba lagi.'];
    }

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = function_exists('mime_content_type') ? mime_content_type($file['tmp_name']) : null;
    if (!$mime || !isset($allowed[$mime])) {
        return [null, 'Format foto harus JPG, PNG, atau WEBP.'];
    }

    $maks = 2 * 1024 * 1024; // 2MB
    if ($file['size'] > $maks) {
        return [null, 'Ukuran foto maksimal 2MB.'];
    }

    $dir = foto_profil_dir();
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $nama_baru = uniqid('foto_', true) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], $dir . $nama_baru)) {
        return [null, 'Gagal menyimpan foto ke server.'];
    }

    // Hapus foto lama supaya tidak menumpuk file sampah
    if ($foto_lama && is_file($dir . $foto_lama)) {
        @unlink($dir . $foto_lama);
    }

    return [$nama_baru, null];
}

/** Hapus file foto profil dari server (dipanggil saat user klik "Hapus Foto"). */
function hapus_foto_profil(?string $foto): void
{
    if (!$foto) return;
    $path = foto_profil_dir() . $foto;
    if (is_file($path)) {
        @unlink($path);
    }
}

/** SVG placeholder ala WhatsApp untuk user yang belum punya foto profil. */
function svg_avatar_placeholder(): string
{
    return '<svg viewBox="0 0 24 24" fill="currentColor" width="60%" height="60%">'
        . '<path d="M12 12c2.7 0 4.9-2.2 4.9-4.9S14.7 2.2 12 2.2 7.1 4.4 7.1 7.1 9.3 12 12 12zm0 2.3c-3.4 0-10.1 1.7-10.1 5.1V22h20.2v-2.6c0-3.4-6.7-5.1-10.1-5.1z"/>'
        . '</svg>';
}

/**
 * Validasi nomor telepon: hanya boleh angka, panjang 8-15 digit.
 * Nilai kosong dianggap valid (field opsional) - cek wajib isi dilakukan terpisah jika perlu.
 */
function is_valid_phone(string $telp): bool
{
    if ($telp === '') return true;
    return (bool) preg_match('/^[0-9]{8,15}$/', $telp);
}

/**
 * Tampilkan daftar error validasi (yang biasanya dirender inline di form)
 * sebagai popup SweetAlert2, bukan div <alert> statis.
 * Dipanggil langsung di halaman, setelah header.php & sidebar.php di-include.
 */
function render_errors_popup(array $errors): void
{
    if (empty($errors)) {
        return;
    }
    $html = '<ul style="text-align:left;margin:0;padding-left:20px;">';
    foreach ($errors as $err) {
        $html .= '<li>' . e($err) . '</li>';
    }
    $html .= '</ul>';
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        Swal.fire({
            icon: 'error',
            title: 'Periksa kembali form',
            html: <?= json_encode($html) ?>,
            confirmButtonColor: '#4f46e5',
        });
    });
    </script>
    <?php
}

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_get(): ?array
{
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}
