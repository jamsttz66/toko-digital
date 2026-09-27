<?php
/**
 * Order Detail (PRD section 29)
 * URL: /order-detail.php?id=...
 */
require_once __DIR__ . '/includes/header.php';
require_login();

$orderId = (int) input('id', 0);

if ($orderId <= 0) {
    redirect(app_url('orders.php'));
}

$orderModel = new Order();
$order = $orderModel->findUserOrder($orderId, (int) $_SESSION['user']['id']);

// Admin bisa lihat semua order
if (!$order && is_admin()) {
    $order = $orderModel->find($orderId);
}

if (!$order) {
    http_response_code(404);
    echo '<div class="container-narrow py-5 text-center"><h1>404</h1><p>Pesanan tidak ditemukan.</p><a href="' . app_url('orders.php') . '" class="btn btn-primary">Kembali ke Pesanan</a></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$items = $orderModel->items($orderId);
$payment = (new Payment())->findByOrder($orderId);
$deliveries = (new Delivery())->findByOrder($orderId);

$pageTitle = 'Pesanan ' . $order['order_number'];
?>

<div class="container-narrow py-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="<?php echo app_url('orders.php'); ?>">Pesanan</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo e($order['order_number']); ?></li>
        </ol>
    </nav>

    <div class="page-header">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h1 class="mono"><?php echo e($order['order_number']); ?></h1>
                <p class="text-muted mb-0"><?php echo e(tgl_jam_id($order['created_at'])); ?></p>
            </div>
            <?php
            $badgeClass = match ($order['status']) {
                'paid' => 'paid',
                'completed' => 'completed',
                'pending' => 'pending',
                'expired' => 'expired',
                'cancelled' => 'cancelled',
                'refunded' => 'revoked',
                default => 'pending',
            };
            $statusLabel = match ($order['status']) {
                'pending' => 'Menunggu Pembayaran',
                'paid' => 'Pembayaran Berhasil',
                'processing' => 'Diproses',
                'completed' => 'Selesai',
                'cancelled' => 'Dibatalkan',
                'expired' => 'Kedaluwarsa',
                'refunded' => 'Dikembalikan',
                default => $order['status'],
            };
            ?>
            <span class="badge-soft <?php echo e($badgeClass); ?>"><?php echo e($statusLabel); ?></span>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card-product p-4">
                <h2 class="fs-5 mb-3">Produk</h2>
                <div class="table-responsive-custom">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Produk</th>
                                <th scope="col" class="text-end">Harga</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo app_url('produk-detail.php?slug=' . urlencode($item['slug'])); ?>" class="text-ink text-decoration-none">
                                            <?php echo e($item['product_name_snapshot']); ?>
                                        </a>
                                    </td>
                                    <td class="text-end"><?php echo rupiah($item['price']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td class="text-muted">Subtotal</td>
                                <td class="text-end"><?php echo rupiah($order['subtotal']); ?></td>
                            </tr>
                            <?php if ((float) $order['discount'] > 0): ?>
                                <tr>
                                    <td class="text-muted">Diskon <?php echo e($order['voucher_code'] ? "({$order['voucher_code']})" : ''); ?></td>
                                    <td class="text-end text-danger">-<?php echo rupiah($order['discount']); ?></td>
                                </tr>
                            <?php endif; ?>
                            <tr>
                                <td class="fw-semibold">Total</td>
                                <td class="text-end fw-bold fs-5"><?php echo rupiah($order['total']); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <?php if ($order['status'] === 'pending' && $payment): ?>
                <div class="alert-custom mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <strong>Pembayaran belum selesai.</strong>
                        <span class="text-muted d-block small">Selesaikan pembayaran QRIS untuk mendapatkan produk.</span>
                    </div>
                    <a href="<?php echo app_url('payment.php?tx=' . urlencode($payment['provider_transaction_id'])); ?>"
                       class="btn btn-primary btn-sm">
                        Lihat QRIS
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <div class="card-product p-4 mb-3">
                <h2 class="fs-6 mb-3">Informasi Pesanan</h2>
                <ul class="list-unstyled small d-flex flex-column gap-2 mb-0">
                    <li><strong class="text-ink">Nama:</strong> <?php echo e($order['customer_name']); ?></li>
                    <li><strong class="text-ink">Email:</strong> <?php echo e($order['customer_email']); ?></li>
                    <?php if (!empty($order['customer_phone'])): ?>
                        <li><strong class="text-ink">WhatsApp:</strong> <?php echo e($order['customer_phone']); ?></li>
                    <?php endif; ?>
                    <?php if ($payment): ?>
                        <li><strong class="text-ink">Pembayaran:</strong> QRIS</li>
                        <li><strong class="text-ink">Status:</strong> <?php echo e($payment['status']); ?></li>
                    <?php endif; ?>
                </ul>
            </div>

            <?php if ($order['status'] === 'paid' || $order['status'] === 'completed'): ?>
                <div class="card-product p-4">
                    <h2 class="fs-6 mb-3">Akses Produk</h2>
                    <p class="small text-muted mb-3">Produk tersedia di halaman Produk Saya.</p>
                    <a href="<?php echo app_url('library.php'); ?>" class="btn btn-accent w-100">
                        Download Produk
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
