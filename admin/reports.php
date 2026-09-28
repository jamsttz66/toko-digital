<?php
/**
 * Admin: laporan penjualan (PRD section 40)
 */
$pageTitle = 'Laporan';
$activeMenu = 'reports';

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/autoload.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_admin();

$from = clean(input('from', date('Y-m-01')));
$to = clean(input('to', date('Y-m-d')));

$orderModel = new Order();

// Ringkasan pesanan
$summary = Database::selectOne(
    "SELECT COUNT(*) AS total_orders,
            SUM(CASE WHEN status IN ('paid','completed') THEN 1 ELSE 0 END) AS paid_orders,
            SUM(CASE WHEN status IN ('paid','completed') THEN total ELSE 0 END) AS revenue,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_orders
     FROM orders
     WHERE date(created_at) BETWEEN :from AND :to",
    [':from' => $from, ':to' => $to]
);

// Produk terlaris
$topProducts = Database::select(
    "SELECT oi.product_name_snapshot AS name,
            COUNT(*) AS sold,
            SUM(oi.subtotal) AS revenue
     FROM order_items oi
     INNER JOIN orders o ON o.id = oi.order_id
     WHERE o.status IN ('paid','completed')
       AND date(o.created_at) BETWEEN :from AND :to
     GROUP BY oi.product_id, oi.product_name_snapshot
     ORDER BY sold DESC
     LIMIT 10",
    [':from' => $from, ':to' => $to]
);

// Penjualan per kategori
$categorySales = Database::select(
    "SELECT c.name AS category_name,
            COUNT(oi.id) AS sold,
            SUM(oi.subtotal) AS revenue
     FROM order_items oi
     INNER JOIN orders o ON o.id = oi.order_id
     INNER JOIN products p ON p.id = oi.product_id
     LEFT JOIN categories c ON c.id = p.category_id
     WHERE o.status IN ('paid','completed')
       AND date(o.created_at) BETWEEN :from AND :to
     GROUP BY c.id, c.name
     ORDER BY revenue DESC",
    [':from' => $from, ':to' => $to]
);

// Penjualan harian
$daily = Database::select(
    "SELECT date(o.created_at) AS day,
            COUNT(*) AS orders,
            SUM(o.total) AS revenue
     FROM orders o
     WHERE o.status IN ('paid','completed')
       AND date(o.created_at) BETWEEN :from AND :to
     GROUP BY date(o.created_at)
     ORDER BY day ASC",
    [':from' => $from, ':to' => $to]
);

require_once __DIR__ . '/partials/header.php';
?>

<form method="get" class="card-product p-3 mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small" for="from">Dari Tanggal</label>
            <input type="date" class="form-control" id="from" name="from" value="<?php echo e($from); ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label small" for="to">Sampai Tanggal</label>
            <input type="date" class="form-control" id="to" name="to" value="<?php echo e($to); ?>">
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary">Filter</button>
            <a class="btn btn-outline-ink" href="<?php echo app_url('admin/reports-export.php?from=' . urlencode($from) . '&to=' . urlencode($to)); ?>">
                Export CSV
            </a>
        </div>
    </div>
</form>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-label">Total Pendapatan</div>
            <div class="stat-value"><?php echo rupiah($summary['revenue'] ?? 0); ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-label">Order Berbayar</div>
            <div class="stat-value"><?php echo (int) ($summary['paid_orders'] ?? 0); ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-label">Total Order</div>
            <div class="stat-value"><?php echo (int) ($summary['total_orders'] ?? 0); ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-label">Order Pending</div>
            <div class="stat-value"><?php echo (int) ($summary['pending_orders'] ?? 0); ?></div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card-product p-4">
            <h2 class="fs-5 mb-3">Produk Terlaris</h2>
            <?php if (!$topProducts): ?>
                <p class="text-muted small mb-0">Belum ada penjualan pada periode ini.</p>
            <?php else: ?>
                <div class="table-responsive-custom">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Produk</th>
                                <th class="text-end">Terjual</th>
                                <th class="text-end">Pendapatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topProducts as $i => $p): ?>
                                <tr>
                                    <td class="small text-muted"><?php echo $i + 1; ?></td>
                                    <td class="small"><?php echo e($p['name']); ?></td>
                                    <td class="text-end small"><?php echo (int) $p['sold']; ?></td>
                                    <td class="text-end small"><?php echo rupiah($p['revenue']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card-product p-4">
            <h2 class="fs-5 mb-3">Penjualan per Kategori</h2>
            <?php if (!$categorySales): ?>
                <p class="text-muted small mb-0">Belum ada penjualan pada periode ini.</p>
            <?php else: ?>
                <div class="table-responsive-custom">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Kategori</th>
                                <th class="text-end">Terjual</th>
                                <th class="text-end">Pendapatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categorySales as $c): ?>
                                <tr>
                                    <td class="small"><?php echo e($c['category_name'] ?: 'Tanpa Kategori'); ?></td>
                                    <td class="text-end small"><?php echo (int) $c['sold']; ?></td>
                                    <td class="text-end small"><?php echo rupiah($c['revenue']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row g-4 mt-0">
    <div class="col-12">
        <div class="card-product p-4">
            <h2 class="fs-5 mb-3">Penjualan Harian</h2>
            <?php if (!$daily): ?>
                <p class="text-muted small mb-0">Belum ada penjualan pada periode ini.</p>
            <?php else: ?>
                <div class="table-responsive-custom">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th class="text-end">Order</th>
                                <th class="text-end">Pendapatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($daily as $d): ?>
                                <tr>
                                    <td class="small"><?php echo tgl_id($d['day']); ?></td>
                                    <td class="text-end small"><?php echo (int) $d['orders']; ?></td>
                                    <td class="text-end small"><?php echo rupiah($d['revenue']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
