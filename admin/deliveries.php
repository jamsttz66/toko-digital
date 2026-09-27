<?php
/**
 * Admin: daftar delivery file digital (PRD section 37)
 */
$pageTitle = 'Pengiriman';
$activeMenu = 'deliveries';

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/autoload.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_admin();

$page = max(1, (int) input('page', 1));
$deliveryModel = new Delivery();
$result = $deliveryModel->paginateAdmin($page, 20);
$deliveries = $result['rows'];

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$deliveryStatusClass = [
    'generated' => 'pending',
    'ready'     => 'pending',
    'delivered' => 'paid',
    'failed'    => 'failed',
    'revoked'   => 'failed',
];

require_once __DIR__ . '/partials/header.php';
?>

<?php if ($flash): ?>
    <div class="alert-custom alert-success mb-3"><?php echo e($flash); ?></div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <span class="text-muted small"><?php echo number_format($result['total'], 0, ',', '.'); ?> delivery</span>
</div>

<div class="card-product p-3">
    <?php if (!$deliveries): ?>
        <p class="text-muted small mb-0">
            Belum ada delivery. File digital dibuat otomatis saat pembayaran terkonfirmasi via webhook.
        </p>
    <?php else: ?>
        <div class="table-responsive-custom">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Status</th>
                        <th class="text-end">Download</th>
                        <th>Terakhir Diunduh</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($deliveries as $d): ?>
                        <tr>
                            <td class="small">
                                <a href="<?php echo app_url('admin/product-edit.php?id=' . (int) $d['product_id']); ?>"
                                   class="text-ink text-decoration-none">
                                    <?php echo e($d['product_name']); ?>
                                </a>
                            </td>
                            <td class="mono small">
                                <a href="<?php echo app_url('admin/order-detail.php?id=' . (int) $d['order_id']); ?>"
                                   class="text-ink text-decoration-none">
                                    <?php echo e($d['order_number']); ?>
                                </a>
                            </td>
                            <td class="small"><?php echo e($d['customer_name']); ?></td>
                            <td>
                                <span class="badge-soft <?php echo e($deliveryStatusClass[$d['status']] ?? 'pending'); ?>">
                                    <?php echo e($d['status']); ?>
                                </span>
                            </td>
                            <td class="text-end"><?php echo (int) $d['download_count']; ?>×</td>
                            <td class="small text-muted">
                                <?php echo !empty($d['last_download_at']) ? tgl_jam_id($d['last_download_at']) : '-'; ?>
                            </td>
                            <td class="text-nowrap">
                                <form method="post" action="<?php echo app_url('admin/delivery-action.php'); ?>"
                                      class="d-inline">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="id" value="<?php echo (int) $d['id']; ?>">
                                    <?php if ($d['status'] === 'failed'): ?>
                                        <button type="submit" name="action" value="ready"
                                                class="btn btn-outline-ink btn-sm">Tandai Siap</button>
                                    <?php endif; ?>
                                    <?php if (in_array($d['status'], ['ready', 'generated'], true)): ?>
                                        <button type="submit" name="action" value="delivered"
                                                class="btn btn-outline-ink btn-sm">Tandai Diterima</button>
                                    <?php endif; ?>
                                    <?php if ($d['status'] !== 'revoked'): ?>
                                        <button type="submit" name="action" value="revoke"
                                                class="btn btn-outline-ink btn-sm text-danger"
                                                onclick="return confirm('Cabut akses download ini?')">
                                            Cabut
                                        </button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
