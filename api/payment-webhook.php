<?php
/**
 * API: Payment Webhook (PRD section 22, 71)
 *
 * Endpoint: POST /api/payment-webhook.php
 *
 * Alur:
 *   read raw body -> verify signature -> validate event id (idempotency)
 *   -> find order -> validate amount -> update payment -> update order
 *   -> create delivery -> generate token -> send email
 *
 * Endpoint ini TIDAK memerlukan login session customer.
 * Authentication memakai signature HMAC dari provider.
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/autoload.php';
require_once __DIR__ . '/../services/PaymentGatewayInterface.php';
require_once __DIR__ . '/../services/DummyQrisGateway.php';
require_once __DIR__ . '/../services/DeliveryService.php';
require_once __DIR__ . '/../services/EmailService.php';
require_once __DIR__ . '/../services/WebhookService.php';

// Webhook selalu memakai raw body
$rawPayload = file_get_contents('php://input');

if (empty($rawPayload)) {
    json_error('Payload kosong', [], 400);
}

$headers = [];
foreach ($_SERVER as $k => $v) {
    if (str_starts_with($k, 'HTTP_')) {
        $name = str_replace('_', '-', substr($k, 5));
        $headers[$name] = $v;
    }
}

$service = new WebhookService();
$result = $service->handle($rawPayload, $headers);

if ($result['ok'] === true) {
    json_response($result['code'], [
        'success' => true,
        'message' => $result['message'],
        'data' => new stdClass(),
    ]);
}

json_response($result['code'], [
    'success' => false,
    'message' => $result['message'],
    'errors' => new stdClass(),
]);
