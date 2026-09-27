<?php
/**
 * API: Payment Status (PRD section 24, 56)
 *
 * Endpoint: GET /api/payment-status.php?order_number=DS-...
 *
 * Mengambil status pembayaran untuk order milik user yang login.
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/autoload.php';

$orderNumber = clean(input('order_number', ''));

if ($orderNumber === '') {
    json_error('Nomor order diperlukan', [], 400);
}

$orderModel = new Order();
$order = $orderModel->findByNumber($orderNumber);

if (!$order) {
    json_error('Order tidak ditemukan', [], 404);
}

// Guard: hanya pemilik order atau admin yang boleh lihat
$isLogged = !empty($_SESSION['user']['id']);
$isOwner = $isLogged && (int) $order['user_id'] === (int) $_SESSION['user']['id'];
$isAdmin = $isLogged && in_array($_SESSION['user']['role'], ['admin', 'super_admin'], true);

if (!$isLogged || (!$isOwner && !$isAdmin)) {
    json_error('Akses ditolak', [], 403);
}

$payment = (new Payment())->findByOrder((int) $order['id']);

// Tentukan label status untuk frontend
$statusLabel = match ($order['status']) {
    'pending' => 'Menunggu Pembayaran',
    'paid' => 'Pembayaran Berhasil',
    'processing' => 'Sedang Diproses',
    'completed' => 'Selesai',
    'cancelled' => 'Dibatalkan',
    'expired' => 'Kedaluwarsa',
    'refunded' => 'Dikembalikan',
    default => $order['status'],
};

$paymentStatus = $payment ? $payment['status'] : 'none';

json_success('Status pembayaran', [
    'order_number' => $order['order_number'],
    'status' => $order['status'],
    'status_label' => $statusLabel,
    'payment_status' => $paymentStatus,
    'total' => $order['total'],
    'expired_at' => $payment['expired_at'] ?? null,
    'qr_string' => $payment && $payment['status'] === 'pending' ? $payment['qr_string'] : null,
    'paid_at' => $order['paid_at'],
    'items' => array_map(static function ($item) {
        return [
            'name' => $item['product_name_snapshot'],
            'price' => $item['price'],
            'qty' => $item['quantity'],
        ];
    }, $orderModel->items((int) $order['id'])),
]);
