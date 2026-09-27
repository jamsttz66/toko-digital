<?php
/**
 * Admin: hapus voucher (PRD section 39)
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/autoload.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_admin();

if (!is_post()) {
    redirect(app_url('admin/vouchers.php'));
}

if (!csrf_verify(input('csrf_token'))) {
    $_SESSION['flash'] = 'Token tidak valid';
    redirect(app_url('admin/vouchers.php'));
}

$voucherId = (int) input('id', 0);
$voucherModel = new Voucher();
$voucher = Database::selectOne("SELECT * FROM vouchers WHERE id = :id LIMIT 1", [':id' => $voucherId]);

if (!$voucher) {
    $_SESSION['flash'] = 'Voucher tidak ditemukan';
    redirect(app_url('admin/vouchers.php'));
}

$voucherModel->delete($voucherId);

admin_audit('VOUCHER_DELETED', 'vouchers', $voucherId, "Voucher dihapus: {$voucher['code']}");
$_SESSION['flash'] = 'Voucher berhasil dihapus';
redirect(app_url('admin/vouchers.php'));
