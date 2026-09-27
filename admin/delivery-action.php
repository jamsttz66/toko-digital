<?php
/**
 * Admin: aksi delivery (PRD section 37)
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/autoload.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_admin();

if (!is_post()) {
    redirect(app_url('admin/deliveries.php'));
}

if (!csrf_verify(input('csrf_token'))) {
    $_SESSION['flash'] = 'Token tidak valid';
    redirect(app_url('admin/deliveries.php'));
}

$deliveryId = (int) input('id', 0);
$action = clean(input('action', ''));

$deliveryModel = new Delivery();
$delivery = Database::selectOne(
    "SELECT d.*, o.order_number FROM deliveries d
     INNER JOIN orders o ON o.id = d.order_id
     WHERE d.id = :id LIMIT 1",
    [':id' => $deliveryId]
);

if (!$delivery) {
    $_SESSION['flash'] = 'Delivery tidak ditemukan';
    redirect(app_url('admin/deliveries.php'));
}

switch ($action) {
    case 'ready':
        $deliveryModel->markReady($deliveryId);
        admin_audit('DELIVERY_READY', 'deliveries', $deliveryId, "Delivery siap: {$delivery['order_number']}");
        $_SESSION['flash'] = 'Delivery ditandai siap';
        break;

    case 'delivered':
        $deliveryModel->markDelivered($deliveryId);
        admin_audit('DELIVERY_DELIVERED', 'deliveries', $deliveryId, "Delivery diterima: {$delivery['order_number']}");
        $_SESSION['flash'] = 'Delivery ditandai diterima';
        break;

    case 'revoke':
        $deliveryModel->revoke($deliveryId);
        admin_audit('DELIVERY_REVOKED', 'deliveries', $deliveryId, "Delivery dicabut: {$delivery['order_number']}");
        $_SESSION['flash'] = 'Akses download dicabut';
        break;

    default:
        $_SESSION['flash'] = 'Aksi tidak dikenal';
        break;
}

redirect(app_url('admin/deliveries.php'));
