<?php
/**
 * Admin dashboard (PRD section 32)
 */
$pageTitle = 'Dashboard';
$activeMenu = 'dashboard';
require_once __DIR__ . '/partials/header.php';

$orderModel = new Order();
$stats = $orderModel->stats();

$userModel = new User();
$totalCustomers = $userModel->count("role = 'customer'");

$productModel = new Product();
$totalProducts = $productModel->count("status = 'active'");

$bestSellers = $productModel->bestSellers(5);
$recentOrders = (new Order())->paginateAdmin(1, 5)['rows'];

$deliveryModel = new Delivery();
$completedDeliveries = $deliveryModel->count("status IN ('ready','delivered')");
?>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-label">Total Revenue</div>
            <div class="stat-value"><?php echo rupiah($stats['revenue']); ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-label">Order Berbayar</div>
            <div class="stat-value"><?php echo $stats['paid']; ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-label">Pembayaran Pending</div>
            <div class="stat-value"><?php echo $stats['pending']; ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-label">Customer</div>
            <div class="stat-value"><?php echo $totalCustomers; ?></div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-label">Produk Aktif</div>
            <div class="stat-value"><?php echo $totalProducts; ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-label">Delivery Siap/Diterima</div>
            <div class="stat-value"><?php echo $completedDeliveries; ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-label">Order Hari Ini</div>
            <div class="stat-value"><?php echo $stats['today']; ?></div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card-product p-4">
            <h2 class="fs-5 mb-3">Pesanan Terbaru</h2>
            <?php if (!$recentOrders): ?>
                <p class="text-muted small mb-0">Belum ada pesanan.</p>
            <?php else: ?>
                <div class="table-responsive-custom">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Customer</th>
                                <th class="text-end">Total</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentOrders as $o): ?>
                                <tr>
                                    <td class="mono small"><?php echo e($o['order_number']); ?></td>
                                    <td class="small"><?php echo e($o['customer_name']); ?></td>
                                    <td class="text-end small"><?php echo rupiah($o['total']); ?></td>
                                    <td>
                                        <span class="badge-soft <?php echo e($o['status'] === 'paid' ? 'paid' : 'pending'); ?>">
                                            <?php echo e($o['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?php echo app_url('admin/order-detail.php?id=' . (int) $o['id']); ?>"
                                           class="btn btn-outline-ink btn-sm">Detail</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card-product p-4">
            <h2 class="fs-5 mb-3">Produk Terlaris</h2>
            <?php if (!$bestSellers): ?>
                <p class="text-muted small mb-0">Belum ada penjualan.</p>
            <?php else: ?>
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th class="text-end">Terjual</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bestSellers as $p): ?>
                            <tr>
                                <td class="small"><?php echo e($p['name']); ?></td>
                                <td class="text-end"><?php echo (int) $p['sold']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
