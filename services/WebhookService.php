<?php
/**
 * WebhookService.
 *
 * Pemrosesan callback payment gateway sesuai PRD section 22-23:
 *   verify signature -> validate event id (idempotency) -> find order
 *   -> validate amount -> update payment -> update order
 *   -> create delivery -> generate token -> kirim email
 *
 * Sumber kebenaran pembayaran HANYA webhook yang tervalidasi.
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/autoload.php';
require_once __DIR__ . '/PaymentGatewayInterface.php';
require_once __DIR__ . '/DummyQrisGateway.php';
require_once __DIR__ . '/DeliveryService.php';

class WebhookService
{
    private PaymentGatewayInterface $gateway;

    public function __construct()
    {
        $provider = strtolower((string) env('PAYMENT_PROVIDER', 'dummy'));
        $this->gateway = match ($provider) {
            default => new DummyQrisGateway(),
        };
    }

    /**
     * Proses webhook masuk. Kembalikan array hasil untuk endpoint.
     */
    public function handle(string $rawPayload, array $headers): array
    {
        $payload = json_decode($rawPayload, true);
        if (!is_array($payload)) {
            log_error('webhook', 'Payload JSON tidak valid');
            return ['ok' => false, 'code' => 400, 'message' => 'Payload tidak valid'];
        }

        // 1. Verify signature
        $signatureValid = $this->gateway->verifyWebhook($rawPayload, $headers);

        if (!$signatureValid) {
            log_error('webhook', 'Signature webhook tidak valid');
            return ['ok' => false, 'code' => 401, 'message' => 'Signature tidak valid'];
        }

        // 2. Parse & validasi event
        $event = $this->gateway->parseWebhook($payload);

        if (empty($event['event_id']) || empty($event['transaction_id'])) {
            log_error('webhook', 'Event tidak lengkap', $event);
            return ['ok' => false, 'code' => 400, 'message' => 'Event tidak lengkap'];
        }

        $paymentModel = new Payment();

        // 3. Idempotency: cek event_id dulu
        $isNew = $paymentModel->logEvent([
            'provider' => env('PAYMENT_PROVIDER', 'dummy'),
            'event_id' => $event['event_id'],
            'order_id' => null,
            'event_type' => $event['status'] ?? null,
            'payload' => $rawPayload,
            'signature_valid' => 1,
        ]);

        if (!$isNew) {
            // Event sudah pernah diproses -> 200 tanpa side effect
            return ['ok' => true, 'code' => 200, 'message' => 'Event sudah diproses'];
        }

        // 4. Cari payment by transaction id
        $payment = $paymentModel->findByTransactionId($event['transaction_id']);

        if (!$payment) {
            log_error('webhook', 'Transaksi tidak ditemukan', ['tx' => $event['transaction_id']]);
            return ['ok' => false, 'code' => 404, 'message' => 'Transaksi tidak ditemukan'];
        }

        $orderId = (int) $payment['order_id'];
        $order = (new Order())->find($orderId);

        if (!$order) {
            log_error('webhook', 'Order tidak ditemukan', ['order_id' => $orderId]);
            return ['ok' => false, 'code' => 404, 'message' => 'Order tidak ditemukan'];
        }

        // 5. Validasi amount
        if (abs((float) $payment['amount'] - $event['amount']) > 0.01) {
            log_error('webhook', 'Amount tidak sesuai', [
                'expected' => $payment['amount'],
                'received' => $event['amount'],
            ]);
            return ['ok' => false, 'code' => 400, 'message' => 'Amount tidak sesuai'];
        }

        // 6. Order belum paid (jangan proses ulang)
        if (in_array($order['status'], ['paid', 'completed', 'refunded'], true)) {
            $paymentModel->markEventProcessed($event['event_id']);
            return ['ok' => true, 'code' => 200, 'message' => 'Order sudah dibayar'];
        }

        // 7. Status provider menunjukkan pembayaran berhasil?
        if ($event['status'] !== 'PAID') {
            $paymentModel->markFailed((int) $payment['id'], json_encode($payload));
            (new Order())->updateStatus($orderId, 'expired');
            $paymentModel->markEventProcessed($event['event_id']);
            return ['ok' => true, 'code' => 200, 'message' => 'Bukan event paid'];
        }

        // 8. Update payment + order
        $paymentModel->markPaid((int) $payment['id'], json_encode($payload));
        (new Order())->markPaid($orderId);

        // 9. Delivery otomatis (idempotent)
        (new DeliveryService())->createForOrder($orderId);

        $paymentModel->markEventProcessed($event['event_id']);

        return ['ok' => true, 'code' => 200, 'message' => 'Pembayaran berhasil diproses'];
    }
}
