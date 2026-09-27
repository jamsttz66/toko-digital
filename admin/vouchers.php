<?php
/**
 * Admin: daftar voucher (PRD section 39)
 */
$pageTitle = 'Voucher';
$activeMenu = 'vouchers';
require_once __DIR__ . '/partials/header.php';

$voucherModel = new Voucher();
$vouchers = Database::select(
    "SELECT * FROM vouchers ORDER BY created_at DESC"
);

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$now = date('Y-m-d H:i:s');
?>

<?php if ($flash): ?>
    <div class="alert-custom alert-success mb-3"><?php echo e($flash); ?></div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <span class="text-muted small"><?php echo count($vouchers); ?> voucher</span>
    <a href="<?php echo app_url('admin/voucher-create.php'); ?>" class="btn btn-primary btn-sm">
        + Tambah Voucher
    </a>
</div>

<div class="card-product p-3">
    <?php if (!$vouchers): ?>
        <p class="text-muted small mb-0">Belum ada voucher.</p>
    <?php else: ?>
        <div class="table-responsive-custom">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Tipe</th>
                        <th>Nilai</th>
                        <th class="text-end">Min. Belanja</th>
                        <th class="text-end">Pakai</th>
                        <th>Periode</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vouchers as $v):
                        $kuotaHabis = $v['usage_limit'] !== null && (int) $v['used_count'] >= (int) $v['usage_limit'];
                        $kedaluwarsa = $v['end_at'] < $now;
                        if ($v['status'] !== 'active') {
                            $badgeClass = 'failed';
                            $badgeText = 'Nonaktif';
                        } elseif ($kedaluwarsa) {
                            $badgeClass = 'failed';
                            $badgeText = 'Kedaluwarsa';
                        } elseif ($kuotaHabis) {
                            $badgeClass = 'failed';
                            $badgeText = 'Kuota Habis';
                        } else {
                            $badgeClass = 'paid';
                            $badgeText = 'Berlaku';
                        }
                    ?>
                        <tr>
                            <td class="mono fw-medium"><?php echo e($v['code']); ?></td>
                            <td class="small"><?php echo $v['type'] === 'fixed' ? 'Nominal' : 'Persen'; ?></td>
                            <td class="small">
                                <?php echo $v['type'] === 'fixed'
                                    ? rupiah($v['value'])
                                    : number_format((float) $v['value'], 0, ',', '.') . '%'; ?>
                            </td>
                            <td class="text-end small"><?php echo rupiah($v['min_purchase']); ?></td>
                            <td class="text-end small">
                                <?php echo (int) $v['used_count']; ?>
                                <?php if ($v['usage_limit'] !== null): ?>
                                    <span class="text-muted">/ <?php echo (int) $v['usage_limit']; ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="small text-muted">
                                <?php echo tgl_id($v['start_at']); ?> → <?php echo tgl_id($v['end_at']); ?>
                            </td>
                            <td>
                                <span class="badge-soft <?php echo e($badgeClass); ?>"><?php echo e($badgeText); ?></span>
                            </td>
                            <td class="text-nowrap">
                                <a href="<?php echo app_url('admin/voucher-edit.php?id=' . (int) $v['id']); ?>"
                                   class="btn btn-outline-ink btn-sm">Edit</a>
                                <form method="post" action="<?php echo app_url('admin/voucher-delete.php'); ?>"
                                      class="d-inline" onsubmit="return confirm('Hapus voucher ini?')">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="id" value="<?php echo (int) $v['id']; ?>">
                                    <button type="submit" class="btn btn-outline-ink btn-sm text-danger">Hapus</button>
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
