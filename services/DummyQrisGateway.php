<?php
/**
 * DummyQrisGateway.
 *
 * Provider simulasi untuk development & staging. Mengikuti kontrak
 * PaymentGatewayInterface sehingga saat provider QRIS asli (Midtrans, Xendit,
 * Tripay, dll) diintegrasikan, cukup ganti implementasi di config/payment.php.
 *
 * Signature webhook memakai HMAC-SHA256 dengan secret dari .env,
 * sehingga alur verifikasi tetap dilatih di mode dummy.
 */

class DummyQrisGateway implements PaymentGatewayInterface
{
    private string $secret;
    private string $merchantId;
    private string $environment;

    public function __construct()
    {
        $this->secret = (string) env('PAYMENT_SECRET', 'dev-secret-change-me');
        $this->merchantId = (string) env('PAYMENT_MERCHANT_ID', 'DUMMY-MERCHANT');
        $this->environment = (string) env('PAYMENT_ENVIRONMENT', 'sandbox');
    }

    public function createTransaction(array $order): array
    {
        $txId = 'DUMMY-' . strtoupper(bin2hex(random_bytes(8)));
        $expiredAt = date('Y-m-d H:i:s', time() + 900); // 15 menit

        // Simulasi QR string (pada provider asli berisi payload QRIS)
        $qrString = '00020101021226' . strlen($this->merchantId) . 'QRIS.' . $txId . '5204599953033605802ID59'
            . substr($order['order_number'] ?? 'ORDER', 0, 12) . '62200707' . $txId . '6304ABCD';

        $paymentUrl = app_url('payment.php?tx=' . $txId);

        return [
            'transaction_id' => $txId,
            'payment_url' => $paymentUrl,
            'qr_string' => $qrString,
            'expired_at' => $expiredAt,
            'raw' => json_encode([
                'transaction_id' => $txId,
                'order_number' => $order['order_number'] ?? null,
                'amount' => $order['total'] ?? 0,
                'status' => 'PENDING',
                'expired_at' => $expiredAt,
            ]),
        ];
    }

    public function getTransactionStatus(string $transactionId): array
    {
        return [
            'transaction_id' => $transactionId,
            'status' => 'PENDING',
            'amount' => 0,
        ];
    }

    public function verifyWebhook(string $payload, array $headers): bool
    {
        $signature = $headers['X-Signature']
            ?? $headers['x-signature']
            ?? $headers['HTTP_X_SIGNATURE']
            ?? '';

        if ($signature === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $payload, $this->secret);

        return hash_equals($expected, $signature);
    }

    public function parseWebhook(array $payload): array
    {
        return [
            'event_id' => $payload['event_id'] ?? null,
            'transaction_id' => $payload['transaction_id'] ?? null,
            'order_ref' => $payload['order_number'] ?? ($payload['order_ref'] ?? null),
            'amount' => (float) ($payload['amount'] ?? 0),
            'status' => strtoupper((string) ($payload['status'] ?? '')),
        ];
    }

    /**
     * Generate signature untuk webhook keluaran (dipakai tool simulasi).
     */
    public function sign(string $payload): string
    {
        return hash_hmac('sha256', $payload, $this->secret);
    }

    public function getSecret(): string
    {
        return $this->secret;
    }
}
