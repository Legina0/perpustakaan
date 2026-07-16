<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['anggota']);

$kd_anggota = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT b.judul, d.tgl_denda, d.jmlh_denda, d.lunas
    FROM denda d
    JOIN detpinjam dp ON dp.id_detpinjam = d.id_detpinjam
    JOIN pinjam p ON p.no_pinjam = dp.no_pinjam
    JOIN inventaris i ON i.no_inventaris = dp.no_inventaris
    JOIN buku b ON b.kd_buku = i.kd_buku
    WHERE p.kd_anggota = ?
    ORDER BY d.tgl_denda DESC");
$stmt->execute([$kd_anggota]);
$denda_list = $stmt->fetchAll();

$total_belum_lunas = array_sum(array_map(fn($d) => $d['lunas'] ? 0 : $d['jmlh_denda'], $denda_list));

$page_title = 'Denda Saya';
$nama_user = $_SESSION['nama'];
$active = 'denda';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/sidebar.php';
?>

<style>
    .denda-summary-card {
        position: relative;
        overflow: visible;
        border-radius: 18px;
        background: #1e293b;
        color: #fff;
        padding: 26px 28px;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 20px;
        box-shadow: 0 12px 28px -12px rgba(0,0,0,0.45);
    }
    .denda-summary-icon {
        flex-shrink: 0;
        width: 56px;
        height: 56px;
        border-radius: 14px;
        background: rgba(220,38,38,0.18);
        border: 1px solid rgba(248,113,113,0.35);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #f87171;
    }
    .denda-summary-text {
        flex: 1;
    }
    .denda-summary-label {
        font-size: 13px;
        opacity: .8;
        letter-spacing: .3px;
        margin-bottom: 4px;
    }
    .denda-summary-value {
        font-size: 28px;
        font-weight: 700;
        letter-spacing: .3px;
    }

    /* Bell reminder button */
    .bell-fab-wrap {
        position: relative;
        flex-shrink: 0;
    }
    .history-fab {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        border: none;
        background: rgba(255,255,255,0.14);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: transform .25s ease, background .25s ease;
    }
    .history-fab:hover,
    .history-fab.active {
        background: rgba(255,255,255,0.26);
        transform: scale(1.08);
    }
    .history-fab.ringing svg {
        animation: bellRing .6s ease;
    }
    @keyframes bellRing {
        0%, 100% { transform: rotate(0deg); }
        20% { transform: rotate(-15deg); }
        40% { transform: rotate(12deg); }
        60% { transform: rotate(-8deg); }
        80% { transform: rotate(5deg); }
    }

    .bell-tooltip {
        position: absolute;
        top: calc(100% + 12px);
        right: 0;
        width: 240px;
        background: #fff;
        color: #78350f;
        font-size: 12.5px;
        line-height: 1.5;
        font-style: italic;
        padding: 12px 14px;
        border-radius: 12px;
        box-shadow: 0 12px 28px -10px rgba(0,0,0,0.35);
        border: 1px solid #fde68a;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-6px);
        transition: opacity .18s ease, transform .18s ease, visibility .18s ease;
        z-index: 20;
    }
    .bell-tooltip::before {
        content: "";
        position: absolute;
        top: -6px;
        right: 14px;
        width: 12px;
        height: 12px;
        background: #fff;
        border-left: 1px solid #fde68a;
        border-top: 1px solid #fde68a;
        transform: rotate(45deg);
    }
    .bell-tooltip.open {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }
    .bell-tooltip-quote {
        display: block;
        font-weight: 600;
        color: #92400e;
    }

    .denda-panel .panel-header {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .denda-panel .panel-header h2 {
        margin: 0;
    }

    .denda-table-wrap {
        overflow-x: auto;
    }
    .denda-table {
        width: 100%;
        border-collapse: collapse;
    }
    .denda-table thead th {
        text-align: left;
        font-size: 12.5px;
        text-transform: uppercase;
        letter-spacing: .4px;
        color: #6b7280;
        background: #f8fafc;
        padding: 12px 20px;
        border-bottom: 1px solid #eef0f4;
        white-space: nowrap;
    }
    .denda-table tbody td {
        padding: 14px 20px;
        border-bottom: 1px solid #f1f3f7;
        font-size: 14px;
        color: #1f2937;
        vertical-align: middle;
    }
    .denda-table tbody tr:last-child td {
        border-bottom: none;
    }
    .denda-table tbody tr {
        transition: background .15s ease;
    }
    .denda-table tbody tr:hover {
        background: #f9fafb;
    }
    .denda-book-title {
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 600;
    }
    .denda-book-icon {
        flex-shrink: 0;
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: #eef2ff;
        color: #4f6ef7;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .denda-amount {
        font-weight: 700;
    }
    .denda-empty {
        text-align: center;
        padding: 48px 20px !important;
        color: #9ca3af;
    }
    .denda-empty-icon {
        margin: 0 auto 10px;
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: #f3f4f6;
        color: #9ca3af;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .denda-empty-title {
        font-weight: 600;
        color: #6b7280;
        margin-bottom: 4px;
    }
    .denda-empty-sub {
        font-size: 12.5px;
        color: #9ca3af;
    }
</style>

<div class="denda-summary-card">
    <div class="denda-summary-icon">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
    </div>
    <div class="denda-summary-text">
        <div class="denda-summary-label">Total Denda Belum Lunas</div>
        <div class="denda-summary-value"><?= rupiah($total_belum_lunas) ?></div>
    </div>

    <div class="bell-fab-wrap">
        <button type="button" class="history-fab" id="btnBellReminder" aria-label="Pengingat Denda" title="Pengingat Denda">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
            </svg>
        </button>
        <div class="bell-tooltip" id="bellTooltip">
            <span class="bell-tooltip-quote">"Jangan tunda, segera lunasi dendamu."</span>
            Pengingat supaya segera membayar denda agar tidak mengganggu peminjaman berikutnya.
        </div>
    </div>
</div>

<?php if ($total_belum_lunas > 0): ?>
<div class="denda-reminder">
    <div class="denda-reminder-icon">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
        </svg>
    </div>
    <div>
        <div class="denda-reminder-title">Pengingat</div>
        Anda masih memiliki denda yang belum lunas sebesar <strong><?= rupiah($total_belum_lunas) ?></strong>. Segera selesaikan pembayaran denda agar tidak mengganggu proses peminjaman buku berikutnya.
    </div>
</div>
<?php endif; ?>

<!-- Riwayat Denda langsung di halaman -->
<div class="denda-panel panel">
    <div class="panel-header">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 3v18h18"></path>
            <path d="M18.7 8.3l-6.2 6.2-4-4L3 15"></path>
        </svg>
        <h2>Riwayat Denda</h2>
    </div>

    <div class="denda-table-wrap">
        <table class="denda-table">
            <thead>
                <tr><th>Judul Buku</th><th>Tanggal Denda</th><th>Jumlah</th><th>Status</th></tr>
            </thead>
            <tbody>
                <?php if (empty($denda_list)): ?>
                    <tr>
                        <td colspan="4" class="empty-state denda-empty">
                            <div class="denda-empty-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 6L9 17l-5-5"></path>
                                </svg>
                            </div>
                            <div class="denda-empty-title">Tidak ada denda.</div>
                            <div class="denda-empty-sub">Terima kasih sudah selalu mengembalikan buku tepat waktu.</div>
                        </td>
                    </tr>
                <?php else: foreach ($denda_list as $d): ?>
                    <tr>
                        <td>
                            <div class="denda-book-title">
                                <span class="denda-book-icon">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                                    </svg>
                                </span>
                                <?= e($d['judul']) ?>
                            </div>
                        </td>
                        <td><?= tgl_indo($d['tgl_denda']) ?></td>
                        <td class="denda-amount"><?= rupiah($d['jmlh_denda']) ?></td>
                        <td><?= badge_status($d['lunas'] ? 'Lunas' : 'Belum Lunas') ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var btnBell = document.getElementById('btnBellReminder');
    var tooltip = document.getElementById('bellTooltip');

    function toggleTooltip() {
        tooltip.classList.toggle('open');
        btnBell.classList.toggle('active');
        btnBell.classList.remove('ringing');
        void btnBell.offsetWidth; // restart animation
        btnBell.classList.add('ringing');
    }
    function closeTooltip() {
        tooltip.classList.remove('open');
        btnBell.classList.remove('active');
    }

    if (btnBell) {
        btnBell.addEventListener('click', function (e) {
            e.stopPropagation();
            toggleTooltip();
        });
    }
    document.addEventListener('click', function (e) {
        if (tooltip && !tooltip.contains(e.target) && e.target !== btnBell) {
            closeTooltip();
        }
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeTooltip();
    });
});
</script>