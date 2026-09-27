<?php
/**
 * API: Checkout (PRD section 19, 56)
 *
 * Endpoint: POST /api/checkout.php
 *
 * Membuat order + payment transaction. Harga selalu dihitung server-side.
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/autoload.php';
require_once __DIR__ . '/../services/PaymentGatewayInterface.php';
require_once __DIR__ . '/../services/DummyQrisGateway.php';
require_once __DIR__ . '/../services/PaymentService.php';
require_once __DIR__ . '/../services/EmailService.php';

if (!is_post()) {
    json_error('Metode tidak diizinkan', [], 405);
}

if (!csrf_verify(input('csrf_token'))) {
    json_error('Token tidak valid', [], 419);
}

$name = clean(input('name', ''));
$email = clean(input('email', ''));
$phone = clean(input('phone', ''));
$voucherCode = clean(input('voucher_code', '')) ?: null;

// Validasi input customer
$errors = [];

if (mb_strlen($name) < 3) {
    $errors['name'] = 'Nama minimal 3 karakter';
}

if (!valid_email($email)) {
    $errors['email'] = 'Email tidak valid';
}

if ($phone !== '' && !preg_match('/^[0-9+\-\s]{8,20}$/', $phone)) {
    $errors['phone'] = 'Nomor WhatsApp tidak valid';
}

if ($errors) {
    json_error('Data tidak lengkap', $errors, 422);
}

$cartModel = new Cart();
$cart = $cartModel->current();

$items = $cartModel->items((int) $cart['id']);
if (!$items) {
    json_error('Keranjang masih kosong', [], 400);
}

try {
    $service = new PaymentService();
    $result = $service->createOrderFromCart($cart, [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
    ], $voucherCode);

    // Kirim email order dibuat
    $orderRow = (new Order())->find($result['order']['id']);
    if ($orderRow) {
        (new EmailService())->sendOrderCreated($orderRow);
    }

    json_success('Pembayaran berhasil dibuat', [
        'order_number' => $result['order']['order_number'],
        'total' => $result['order']['total'],
        'transaction_id' => $result['payment']['transaction_id'],
        'qr_string' => $result['payment']['qr_string'],
        'expired_at' => $result['payment']['expired_at'],
        'redirect' => app_url('payment.php?tx=' . urlencode($result['payment']['transaction_id'])),
    ]);
} catch (Throwable $e) {
    log_error('checkout', 'Gagal membuat order: ' . $e->getMessage());
    json_error($e->getMessage(), [], 400);
}
