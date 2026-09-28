<?php
/**
 * Admin: daftar customer (PRD section 38)
 */
$pageTitle = 'Customer';
$activeMenu = 'users';
require_once __DIR__ . '/partials/header.php';

$page = max(1, (int) input('page', 1));
$userModel = new User();
$result = $userModel->paginateCustomers($page, 20);
$customers = $result['rows'];

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>

<?php if ($flash): ?>
    <div class="alert-custom alert-success mb-3"><?php echo e($flash); ?></div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <span class="text-muted small"><?php echo number_format($result['total'], 0, ',', '.'); ?> customer</span>
    <span class="small text-muted">Total order &amp; belanja customer dari data pesanan</span>
</div>

<div class="card-product p-3">
    <?php if (!$customers): ?>
        <p class="text-muted small mb-0">Belum ada customer terdaftar.</p>
    <?php else: ?>
        <div class="table-responsive-custom">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Telepon</th>
                        <th>Status</th>
                        <th class="text-end">Order</th>
                        <th class="text-end">Belanja</th>
                        <th>Bergabung</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $c):
                        $orderStats = Database::selectOne(
                            "SELECT COUNT(*) AS cnt, COALESCE(SUM(total), 0) AS total
                             FROM orders
                             WHERE user_id = :uid AND status IN ('paid','completed')",
                            [':uid' => (int) $c['id']]
                        );
                    ?>
                        <tr>
                            <td class="fw-medium small"><?php echo e($c['name']); ?></td>
                            <td class="small"><?php echo e($c['email']); ?></td>
                            <td class="small text-muted"><?php echo e($c['phone'] ?: '-'); ?></td>
                            <td>
                                <span class="badge-soft <?php echo $c['status'] === 'active' ? 'paid' : 'failed'; ?>">
                                    <?php echo $c['status'] === 'active' ? 'Aktif' : 'Nonaktif'; ?>
                                </span>
                            </td>
                            <td class="text-end small"><?php echo (int) ($orderStats['cnt'] ?? 0); ?></td>
                            <td class="text-end small"><?php echo rupiah($orderStats['total'] ?? 0); ?></td>
                            <td class="small text-muted"><?php echo tgl_id($c['created_at']); ?></td>
                            <td>
                                <form method="post" action="<?php echo app_url('admin/user-action.php'); ?>"
                                      class="d-inline">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="id" value="<?php echo (int) $c['id']; ?>">
                                    <?php if ($c['status'] === 'active'): ?>
                                        <button type="submit" name="action" value="suspend"
                                                class="btn btn-outline-ink btn-sm text-danger"
                                                onclick="return confirm('Nonaktifkan customer ini?')">
                                            Suspended
                                        </button>
                                    <?php else: ?>
                                        <button type="submit" name="action" value="activate"
                                                class="btn btn-outline-ink btn-sm">
                                            Aktifkan
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
    <?php echo pagination_nav($result, 'admin/users.php'); ?>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
