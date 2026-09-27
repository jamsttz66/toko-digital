<?php
/**
 * Webhook Simulator (PRD section 67.3, 72)
 *
 * Tool untuk mengetes alur webhook di mode development/staging.
 * Mengirim webhook ke /api/payment-webhook.php dengan signature
 * HMAC-SHA256 yang valid, sama seperti payment gateway sungguhan.
 *
 * Pemakaian:
 *   php tools/simulate-webhook.php DS-20260927-000001 paid
 *
 * Status yang tersedia: paid, failed, expired
 *
 * JANGAN deploy tool ini ke production.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Tool ini hanya bisa dijalankan dari CLI');
}

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/autoload.php';
require_once __DIR__ . '/../services/PaymentGatewayInterface.php';
require_once __DIR__ . '/../services/DummyQrisGateway.php';

$orderNumber = $argv[1] ?? '';
$status = strtolower($argv[2] ?? 'paid');

if ($orderNumber === '') {
    echo "Pemakaian: php tools/simulate-webhook.php <order_number> [paid|failed|expired]\n";
    echo "Contoh:    php tools/simulate-webhook.php DS-20260927-000001 paid\n";
    exit(1);
}

$order = (new Order())->findByNumber($orderNumber);

if (!$order) {
    echo "Error: order {$orderNumber} tidak ditemukan\n";
    exit(1);
}

$payment = (new Payment())->findByOrder((int) $order['id']);

if (!$payment) {
    echo "Error: payment untuk order {$orderNumber} tidak ditemukan\n";
    exit(1);
}

if ($payment['status'] === 'paid') {
    echo "Info: payment untuk order {$orderNumber} sudah paid. Tidak ada yang dikirim.\n";
    exit(0);
}

$gateway = new DummyQrisGateway();

$payload = json_encode([
    'event_id' => 'EVT-' . strtoupper(bin2hex(random_bytes(8))),
    'transaction_id' => $payment['provider_transaction_id'],
    'order_number' => $order['order_number'],
    'amount' => (float) $order['total'],
    'status' => strtoupper($status),
    'created_at' => date('c'),
], JSON_UNESCAPED_UNICODE);

$signature = $gateway->sign($payload);

$webhookUrl = app_url('api/payment-webhook.php');

echo "Mengirim webhook ke: {$webhookUrl}\n";
echo "Order:               {$order['order_number']}\n";
echo "Status:              {$status}\n";
echo "Event ID:            " . json_decode($payload, true)['event_id'] . "\n";
echo "\n";

$ch = curl_init($webhookUrl);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'X-Signature: ' . $signature,
        'Content-Length: ' . strlen($payload),
    ],
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: {$httpCode}\n";

if ($error) {
    echo "cURL Error: {$error}\n";
    echo "\n";
    echo "Catatan: jika server belum berjalan, start dulu dengan:\n";
    echo "  php -S localhost:8080 -t " . dirname(__DIR__) . "\n";
    exit(1);
}

echo "Response: {$response}\n";

echo "\n";

if ($httpCode === 200) {
    echo "✓ Webhook diterima. Cek status order:\n";
    $updated = (new Order())->findByNumber($order['order_number']);
    echo "  Order status:  {$updated['status']}\n";
    echo "  Paid at:       " . ($updated['paid_at'] ?: '-') . "\n";

    $deliveries = (new Delivery())->findByOrder((int) $updated['id']);
    echo "  Deliveries:    " . count($deliveries) . "\n";
    foreach ($deliveries as $d) {
        echo "    - {$d['product_name']}: {$d['status']}\n";
    }
} else {
    echo "✗ Webhook ditolak (HTTP {$httpCode})\n";
    exit(1);
}
