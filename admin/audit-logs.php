<?php
/**
 * Admin: audit log (PRD section 55)
 */
$pageTitle = 'Audit Log';
$activeMenu = 'audit';

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/autoload.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_admin();

$page = max(1, (int) input('page', 1));
$perPage = 30;
$offset = ($page - 1) * $perPage;

$logs = Database::select(
    "SELECT a.*, u.name AS admin_name
     FROM audit_logs a
     LEFT JOIN users u ON u.id = a.user_id
     ORDER BY a.created_at DESC, a.id DESC
     LIMIT :limit OFFSET :offset",
    [':limit' => $perPage, ':offset' => $offset]
);

$total = (int) (Database::selectOne("SELECT COUNT(*) AS cnt FROM audit_logs")['cnt'] ?? 0);

require_once __DIR__ . '/partials/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <span class="text-muted small"><?php echo number_format($total, 0, ',', '.'); ?> log</span>
    <span class="small text-muted">Audit log tidak bisa dihapus (PRD 55)</span>
</div>

<div class="card-product p-3">
    <?php if (!$logs): ?>
        <p class="text-muted small mb-0">Belum ada aktivitas yang tercatat.</p>
    <?php else: ?>
        <div class="table-responsive-custom">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Admin</th>
                        <th>Aksi</th>
                        <th>Entitas</th>
                        <th>Deskripsi</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td class="small text-muted"><?php echo tgl_jam_id($log['created_at']); ?></td>
                            <td class="small"><?php echo e($log['admin_name'] ?? 'Sistem'); ?></td>
                            <td>
                                <span class="badge-soft pending mono"><?php echo e($log['action']); ?></span>
                            </td>
                            <td class="small text-muted"><?php echo e($log['entity_type']); ?><?php echo $log['entity_id'] ? ' #' . (int) $log['entity_id'] : ''; ?></td>
                            <td class="small"><?php echo e($log['description']); ?></td>
                            <td class="small text-muted mono"><?php echo e($log['ip_address']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
