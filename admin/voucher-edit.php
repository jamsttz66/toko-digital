<?php
/**
 * Admin: edit voucher (PRD section 39)
 */
$pageTitle = 'Edit Voucher';
$activeMenu = 'vouchers';

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/autoload.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_admin();

$voucherId = (int) input('id', 0);
$voucherModel = new Voucher();
$voucher = Database::selectOne("SELECT * FROM vouchers WHERE id = :id LIMIT 1", [':id' => $voucherId]);

if (!$voucher) {
    redirect(app_url('admin/vouchers.php'));
}

$errors = [];

if (is_post()) {
    if (!csrf_verify(input('csrf_token'))) {
        $errors[] = 'Token tidak valid';
    } else {
        $code = clean(input('code', ''));
        $type = clean(input('type', 'fixed'));
        $data = [
            'code' => $code,
            'type' => $type,
            'value' => (float) input('value', 0),
            'min_purchase' => (float) input('min_purchase', 0),
            'max_discount' => input('max_discount', ''),
            'usage_limit' => input('usage_limit', ''),
            'start_at' => input('start_at', '') . ' 00:00:00',
            'end_at' => input('end_at', '') . ' 23:59:59',
            'status' => clean(input('status', 'active')),
        ];

        if (mb_strlen($code) < 3) {
            $errors[] = 'Kode voucher minimal 3 karakter';
        }
        if ($data['value'] <= 0) {
            $errors[] = 'Nilai harus lebih dari 0';
        }
        if ($type === 'percentage' && (float) $data['value'] > 100) {
            $errors[] = 'Persentase diskon maksimal 100';
        }
        if (empty(input('start_at')) || empty(input('end_at'))) {
            $errors[] = 'Periode awal dan akhir wajib diisi';
        }
        $existing = (new Voucher())->findByCode($code);
        if (!$errors && $existing && (int) $existing['id'] !== $voucherId) {
            $errors[] = 'Kode voucher sudah digunakan';
        }

        if (!$errors) {
            $voucherModel->update($voucherId, $data);
            admin_audit('VOUCHER_UPDATED', 'vouchers', $voucherId, "Voucher diupdate: {$code}");
            $_SESSION['flash'] = 'Voucher berhasil diperbarui';
            redirect(app_url('admin/vouchers.php'));
        }
    }
}

require_once __DIR__ . '/partials/header.php';
?>

<?php if ($errors): ?>
    <div class="alert-custom alert-danger mb-3">
        <ul class="mb-0 ps-3">
            <?php foreach ($errors as $err): ?>
                <li><?php echo e($err); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card-product p-4">
    <form method="post" novalidate>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label" for="code">Kode Voucher</label>
                <input type="text" class="form-control mono" id="code" name="code" required
                       value="<?php echo e($voucher['code']); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="type">Tipe</label>
                <select class="form-select" id="type" name="type">
                    <option value="fixed" <?php echo $voucher['type'] === 'fixed' ? 'selected' : ''; ?>>Nominal (Rp)</option>
                    <option value="percentage" <?php echo $voucher['type'] === 'percentage' ? 'selected' : ''; ?>>Persen (%)</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="value">Nilai</label>
                <input type="number" class="form-control" id="value" name="value" required min="0" step="0.01"
                       value="<?php echo e($voucher['value']); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="min_purchase">Min. Belanja (Rp)</label>
                <input type="number" class="form-control" id="min_purchase" name="min_purchase" min="0" step="0.01"
                       value="<?php echo e($voucher['min_purchase']); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="max_discount">Maks. Diskon (Rp) <span class="text-muted fw-normal">(opsional, khusus persen)</span></label>
                <input type="number" class="form-control" id="max_discount" name="max_discount" min="0" step="0.01"
                       value="<?php echo e($voucher['max_discount']); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="usage_limit">Kuota Pakai <span class="text-muted fw-normal">(opsional)</span></label>
                <input type="number" class="form-control" id="usage_limit" name="usage_limit" min="1"
                       value="<?php echo e($voucher['usage_limit']); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="start_at">Berlaku Mulai</label>
                <input type="date" class="form-control" id="start_at" name="start_at" required
                       value="<?php echo e(substr((string) $voucher['start_at'], 0, 10)); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="end_at">Berakhir Pada</label>
                <input type="date" class="form-control" id="end_at" name="end_at" required
                       value="<?php echo e(substr((string) $voucher['end_at'], 0, 10)); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="status">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="active" <?php echo $voucher['status'] === 'active' ? 'selected' : ''; ?>>Aktif</option>
                    <option value="inactive" <?php echo $voucher['status'] === 'inactive' ? 'selected' : ''; ?>>Nonaktif</option>
                </select>
            </div>
        </div>

        <div class="mt-4 d-flex gap-2">
            <?php echo csrf_field(); ?>
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            <a href="<?php echo app_url('admin/vouchers.php'); ?>" class="btn btn-outline-ink">Batal</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
