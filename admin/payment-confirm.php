<?php
/**
 * Admin: konfirmasi pembayaran manual.
 *
 * Untuk kasus transfer/QRIS yang tidak lewat webhook (bukti bayar dicek admin).
 * Alurnya sama dengan webhook yang tervalidasi: tandai payment + order paid,
 * lalu buat delivery file digital. Tidak boleh dipakai untuk order yang sudah
 * lunas, refund, atau dibatalkan.
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/autoload.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../services/DeliveryService.php';
require_admin();

$orderId = (int) input('id', 0);
$back = app_url('admin/order-detail.php?id=' . $orderId);

if (!is_post() || !csrf_verify(input('csrf_token'))) {
    $_SESSION['flash'] = 'Token tidak valid';
    redirect($orderId > 0 ? $back : app_url('admin/orders.php'));
}

$order = (new Order())->find($orderId);
if (!$order) {
    $_SESSION['flash'] = 'Order tidak ditemukan';
    redirect(app_url('admin/orders.php'));
}

if (in_array($order['status'], ['paid', 'completed', 'refunded'], true)) {
    $_SESSION['flash'] = 'Order sudah lunas, tidak perlu dikonfirmasi ulang';
    redirect($back);
}

if ($order['status'] === 'cancelled') {
    $_SESSION['flash'] = 'Order dibatalkan, tidak bisa dikonfirmasi';
    redirect($back);
}

$payment = (new Payment())->findByOrder($orderId);
if (!$payment) {
    $_SESSION['flash'] = 'Belum ada record pembayaran untuk order ini';
    redirect($back);
}

$note = json_encode([
    'source' => 'manual',
    'admin_id' => (int) ($_SESSION['user']['id'] ?? 0),
    'confirmed_at' => date('c'),
]);

(new Payment())->markPaid((int) $payment['id'], $note);
(new Order())->markPaid($orderId);
(new DeliveryService())->createForOrder($orderId);

admin_audit(
    'PAYMENT_CONFIRMED_MANUAL',
    'orders',
    $orderId,
    "Konfirmasi manual: {$order['order_number']} sebesar " . rupiah($payment['amount'])
);

$_SESSION['flash'] = 'Pembayaran dikonfirmasi. File digital sudah dibuat untuk customer.';
redirect($back);
