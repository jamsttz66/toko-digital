<?php
/**
 * Admin: detail pesanan (PRD section 35)
 */
$pageTitle = 'Detail Pesanan';
$activeMenu = 'orders';

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/autoload.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_admin();

$orderId = (int) input('id', 0);
$orderModel = new Order();
$order = Database::selectOne("SELECT * FROM orders WHERE id = :id LIMIT 1", [':id' => $orderId]);

if (!$order) {
    redirect(app_url('admin/orders.php'));
}

$items       = $orderModel->items($orderId);
$payment     = (new Payment())->findByOrder($orderId);
$deliveries  = (new Delivery())->findByOrder($orderId);

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$statusLabels = [
    'pending'   => ['Menunggu Pembayaran', 'pending'],
    'paid'      => ['Lunas', 'paid'],
    'completed' => ['Selesai', 'paid'],
    'cancelled' => ['Dibatalkan', 'failed'],
    'expired'   => ['Kedaluwarsa', 'failed'],
    'failed'    => ['Gagal', 'failed'],
];

require_once __DIR__ . '/partials/header.php';
?>

<?php if ($flash): ?>
    <div class="alert-custom alert-success mb-3"><?php echo e($flash); ?></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card-product p-4">
            <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                <div>
                    <h2 class="fs-5 mb-1">Order <?php echo e($order['order_number']); ?></h2>
                    <span class="badge-soft <?php echo e($statusLabels[$order['status']][1] ?? 'pending'); ?>">
                        <?php echo e($statusLabels[$order['status']][0] ?? $order['status']); ?>
                    </span>
                </div>
                <span class="small text-muted"><?php echo tgl_jam_id($order['created_at']); ?></span>
            </div>

            <div class="table-responsive-custom">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th class="text-end">Harga</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$items): ?>
                            <tr><td colspan="2" class="text-muted small">Tidak ada item.</td></tr>
                        <?php else: ?>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td class="small"><?php echo e($item['product_name_snapshot']); ?></td>
                                    <td class="text-end small"><?php echo rupiah($item['price']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <hr>
            <div class="d-flex justify-content-between small mb-1">
                <span class="text-muted">Subtotal</span>
                <span><?php echo rupiah($order['subtotal']); ?></span>
            </div>
            <?php if ((float) $order['discount'] > 0): ?>
                <div class="d-flex justify-content-between small mb-1">
                    <span class="text-muted">Diskon (<?php echo e($order['voucher_code']); ?>)</span>
                    <span class="text-danger">−<?php echo rupiah($order['discount']); ?></span>
                </div>
            <?php endif; ?>
            <div class="d-flex justify-content-between fw-bold">
                <span>Total</span>
                <span><?php echo rupiah($order['total']); ?></span>
            </div>
        </div>

        <div class="card-product p-4 mt-3">
            <h2 class="fs-5 mb-3">Delivery File Digital</h2>
            <?php if (!$deliveries): ?>
                <p class="text-muted small mb-0">
                    Belum ada delivery. File digital dibuat otomatis saat pembayaran terkonfirmasi via webhook.
                </p>
            <?php else: ?>
                <div class="table-responsive-custom">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th>Status</th>
                                <th class="text-end">Download</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($deliveries as $d): ?>
                                <tr>
                                    <td class="small">
                                        <a href="<?php echo app_url('admin/product-edit.php?id=' . (int) $d['product_id']); ?>"
                                           class="text-ink text-decoration-none">
                                            <?php echo e($d['product_name'] ?? 'Produk #' . (int) $d['product_id']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge-soft <?php echo $d['status'] === 'delivered' ? 'paid' : 'pending'; ?>">
                                            <?php echo e($d['status']); ?>
                                        </span>
                                    </td>
                                    <td class="text-end small"><?php echo (int) $d['download_count']; ?>×</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card-product p-4">
            <h2 class="fs-5 mb-3">Customer</h2>
            <p class="small mb-1 fw-medium"><?php echo e($order['customer_name']); ?></p>
            <p class="small text-muted mb-1"><?php echo e($order['customer_email']); ?></p>
            <p class="small text-muted mb-0"><?php echo e($order['customer_phone'] ?: '-'); ?></p>
        </div>

        <div class="card-product p-4 mt-3">
            <h2 class="fs-5 mb-3">Pembayaran</h2>
            <?php if (!$payment): ?>
                <p class="text-muted small mb-0">Belum ada record pembayaran.</p>
            <?php else: ?>
                <div class="small mb-2">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Provider</span>
                        <span><?php echo e(strtoupper($payment['provider'])); ?> · <?php echo e($payment['payment_method']); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Transaksi</span>
                        <span class="mono"><?php echo e($payment['provider_transaction_id']); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Jumlah</span>
                        <span><?php echo rupiah($payment['amount']); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Status</span>
                        <span class="badge-soft <?php echo $payment['status'] === 'paid' ? 'paid' : 'pending'; ?>">
                            <?php echo e($payment['status']); ?>
                        </span>
                    </div>
                    <?php if (!empty($payment['paid_at'])): ?>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Dibayar</span>
                            <span><?php echo tgl_jam_id($payment['paid_at']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                <a href="<?php echo app_url('admin/payments.php'); ?>" class="btn btn-outline-ink btn-sm mt-2">
                    Lihat Semua Pembayaran
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
