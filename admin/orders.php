<?php
/**
 * Admin: daftar pesanan (PRD section 35)
 */
$pageTitle = 'Pesanan';
$activeMenu = 'orders';
require_once __DIR__ . '/partials/header.php';

$status = clean(input('status', ''));
$page = max(1, (int) input('page', 1));

$orderModel = new Order();
$result = $orderModel->paginateAdmin($page, 20, $status);
$orders = $result['rows'];

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$statusLabels = [
    'pending' => ['Menunggu Pembayaran', 'pending'],
    'paid' => ['Lunas', 'paid'],
    'completed' => ['Selesai', 'paid'],
    'cancelled' => ['Dibatalkan', 'failed'],
    'failed' => ['Gagal', 'failed'],
];
?>

<?php if ($flash): ?>
    <div class="alert-custom alert-success mb-3"><?php echo e($flash); ?></div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <span class="text-muted small"><?php echo number_format($result['total'], 0, ',', '.'); ?> pesanan</span>
    <div class="d-flex gap-2 flex-wrap">
        <?php foreach (['', 'pending', 'paid', 'completed', 'cancelled'] as $s): ?>
            <a href="<?php echo app_url('admin/orders.php' . ($s !== '' ? '?status=' . $s : '')); ?>"
               class="btn btn-sm <?php echo $status === $s ? 'btn-primary' : 'btn-outline-ink'; ?>">
                <?php echo $s === '' ? 'Semua' : ($statusLabels[$s][0] ?? $s); ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<div class="card-product p-3">
    <?php if (!$orders): ?>
        <p class="text-muted small mb-0">Belum ada pesanan.</p>
    <?php else: ?>
        <div class="table-responsive-custom">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>No. Order</th>
                        <th>Customer</th>

                        <th class="text-end">Total</th>
                        <th>Status</th>
                        <th>Tanggal</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td class="mono small"><?php echo e($o['order_number']); ?></td>
                            <td class="small"><?php echo e($o['customer_name']); ?><br>
                                <span class="text-muted"><?php echo e($o['customer_email']); ?></span></td>
                            <td class="text-end"><?php echo rupiah($o['total']); ?></td>
                            <td>
                                <span class="badge-soft <?php echo e($statusLabels[$o['status']][1] ?? 'pending'); ?>">
                                    <?php echo e($statusLabels[$o['status']][0] ?? $o['status']); ?>
                                </span>
                            </td>
                            <td class="small text-muted"><?php echo tgl_jam_id($o['created_at']); ?></td>
                            <td class="text-nowrap">
                                <a href="<?php echo app_url('admin/order-detail.php?id=' . (int) $o['id']); ?>"
                                   class="btn btn-outline-ink btn-sm">Detail</a>
                            </td>
                        </tr>
.php<?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
