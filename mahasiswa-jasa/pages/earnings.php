<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/order_functions.php';

requireLogin();
$stats = getProviderStats2(currentUserId());
$done = getProviderOrders(currentUserId(), 'selesai');

$activeNav = 'earnings';
$pageTitle = 'Pendapatan';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="layout">
    <?php require __DIR__ . '/../includes/dash_sidebar.php'; ?>
    <main class="main-content">
        <h1 class="page-title">Pendapatan</h1>
        <p class="page-sub">Ringkasan penghasilan dari pesanan yang sudah selesai.</p>

        <div class="stats-grid">
            <div class="card stat-card"><div class="label">Total Pendapatan</div><div class="value"><?= formatRupiah($stats['revenue_total']) ?></div><div class="hint"><?= (int) $stats['done_orders'] ?> pesanan selesai</div></div>
            <div class="card stat-card"><div class="label">Bulan Ini</div><div class="value"><?= formatRupiah($stats['revenue_month']) ?></div><div class="hint">Bulan lalu: <?= formatRupiah($stats['revenue_prev']) ?></div></div>
            <div class="card stat-card"><div class="label">Dalam Proses</div><div class="value"><?= formatRupiah($stats['pending_total']) ?></div><div class="hint">Cair setelah pesanan selesai</div></div>
        </div>

        <h2 class="section-title">Riwayat Pesanan Selesai</h2>
        <div class="card table-wrap" style="padding:0;">
            <?php if (!$done): ?>
                <div class="empty-box">Belum ada pesanan yang selesai.</div>
            <?php else: ?>
                <table class="simple-table">
                    <thead><tr><th>Tanggal</th><th>Kode</th><th>Jasa</th><th>Pembeli</th><th style="text-align:right;">Nominal</th></tr></thead>
                    <tbody>
                    <?php foreach ($done as $o): ?>
                        <tr>
                            <td><?= formatTanggalIndo($o['updated_at']) ?></td>
                            <td><?= clean($o['kode_pesanan']) ?></td>
                            <td><?= clean($o['judul']) ?></td>
                            <td><?= clean($o['buyer_nama']) ?></td>
                            <td style="text-align:right;font-weight:700;"><?= formatRupiah($o['total_harga']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
