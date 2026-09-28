<?php
/**
 * Admin: daftar pembayaran (PRD section 36)
 */
$pageTitle = 'Pembayaran';
$activeMenu = 'payments';
require_once __DIR__ . '/partials/header.php';

$page = max(1, (int) input('page', 1));
$result = (new Payment())->paginateAdmin($page, 20);
$payments = $result['rows'];

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$payStatusClass = [
    'paid'      => 'paid',
    'pending'   => 'pending',
    'failed'    => 'failed',
    'expired'   => 'failed',
    'refunded'  => 'failed',
];
?>

<?php if ($flash): ?>
    <div class="alert-custom alert-success mb-3"><?php echo e($flash); ?></div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <span class="text-muted small"><?php echo number_format($result['total'], 0, ',', '.'); ?> transaksi</span>
    <a href="<?php echo app_url('api/payment-status.php'); ?>" class="btn btn-outline-ink btn-sm" target="_blank">
        API Cek Status
    </a>
</div>

<div class="card-product p-3">
    <?php if (!$payments): ?>
        <p class="text-muted small mb-0">Belum ada transaksi pembayaran.</p>
    <?php else: ?>
        <div class="table-responsive-custom">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Provider</th>
                        <th class="text-end">Jumlah</th>
                        <th>Status</th>
                        <th>Waktu</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $pay): ?>
                        <tr>
                            <td class="mono small">
                                <a href="<?php echo app_url('admin/order-detail.php?id=' . (int) $pay['order_id']); ?>"
                                   class="text-ink text-decoration-none">
                                    <?php echo e($pay['order_number']); ?>
                                </a>
                            </td>
                            <td class="small"><?php echo e($pay['customer_name']); ?></td>
                            <td class="small">
                                <?php echo e(strtoupper($pay['provider'])); ?>
                                <span class="text-muted d-block"><?php echo e($pay['payment_method']); ?></span>
                            </td>
                            <td class="text-end"><?php echo rupiah($pay['amount']); ?></td>
                            <td>
                                <span class="badge-soft <?php echo e($payStatusClass[$pay['status']] ?? 'pending'); ?>">
                                    <?php echo e($pay['status']); ?>
                                </span>
                            </td>
                            <td class="small text-muted"><?php echo tgl_jam_id($pay['created_at']); ?></td>
                            <td class="text-nowrap">
                                <a href="<?php echo app_url('admin/order-detail.php?id=' . (int) $pay['order_id']); ?>"
                                   class="btn btn-outline-ink btn-sm">Order</a>
                                <?php if (!empty($pay['payment_url'])): ?>
                                    <a href="<?php echo e($pay['payment_url']); ?>" target="_blank"
                                       class="btn btn-outline-ink btn-sm">URL</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    <?php echo pagination_nav($result, 'admin/payments.php'); ?>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
