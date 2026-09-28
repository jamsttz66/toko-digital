<?php
/**
 * Midtrans Snap (sandbox / production).
 *
 * Token transaksi: POST https://app.sandbox.midtrans.com/snap/v1/transactions
 * Notifikasi: SHA512(order_id + status_code + gross_amount + ServerKey)
 * https://docs.midtrans.com/docs/https-notification-webhooks
 *
 * order_id Midtrans = order_number toko, biar notifikasi bisa dicocokkan
 * tanpa menyimpan mapping terpisah.
 */

class MidtransGateway implements PaymentGatewayInterface
{
    private string $serverKey;
    private string $clientKey;
    private bool $production;

    public function __construct()
    {
        $this->serverKey = (string) env('MIDTRANS_SERVER_KEY', '');
        $this->clientKey = (string) env('MIDTRANS_CLIENT_KEY', '');
        $env = strtolower((string) env('PAYMENT_ENVIRONMENT', 'sandbox'));
        $this->production = $env === 'production';
    }

    public function clientKey(): string
    {
        return $this->clientKey;
    }

    public function snapJsUrl(): string
    {
        return $this->production
            ? 'https://app.midtrans.com/snap/snap.js'
            : 'https://app.sandbox.midtrans.com/snap/snap.js';
    }

    public function createTransaction(array $order): array
    {
        if ($this->serverKey === '') {
            throw new RuntimeException('MIDTRANS_SERVER_KEY kosong');
        }

        $orderId = (string) ($order['order_number'] ?? '');
        $amount = (int) round((float) ($order['total'] ?? 0));

        $body = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $amount,
            ],
            'customer_details' => [
                'first_name' => (string) ($order['customer_name'] ?? 'Customer'),
                'email' => (string) ($order['customer_email'] ?? ''),
                'phone' => (string) ($order['customer_phone'] ?? ''),
            ],
            'item_details' => $order['items'] ?? [[
                'id' => $orderId,
                'price' => $amount,
                'quantity' => 1,
                'name' => 'Pesanan ' . $orderId,
            ]],
            'callbacks' => [
                'finish' => app_url('payment.php?order=' . urlencode($orderId)),
            ],
        ];

        $host = $this->production ? 'https://app.midtrans.com' : 'https://app.sandbox.midtrans.com';
        $ch = curl_init($host . '/snap/v1/transactions');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode($this->serverKey . ':'),
            ],
        ]);

        $raw = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($raw === false || $code < 200 || $code >= 300) {
            log_error('midtrans', 'Snap gagal', ['http' => $code, 'err' => $err, 'body' => $raw]);
            throw new RuntimeException('Midtrans menolak transaksi (HTTP ' . $code . ')');
        }

        $data = json_decode((string) $raw, true);
        if (!is_array($data) || empty($data['token']) || empty($data['redirect_url'])) {
            throw new RuntimeException('Respons Midtrans tidak lengkap');
        }

        return [
            'transaction_id' => $orderId,
            'payment_url' => $data['redirect_url'],
            'qr_string' => (string) $data['token'],
            'expired_at' => date('Y-m-d H:i:s', time() + 86400),
            'raw' => (string) $raw,
        ];
    }

    public function getTransactionStatus(string $transactionId): array
    {
        $host = $this->production ? 'https://api.midtrans.com' : 'https://api.sandbox.midtrans.com';
        $ch = curl_init($host . '/v2/' . rawurlencode($transactionId) . '/status');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Authorization: Basic ' . base64_encode($this->serverKey . ':'),
            ],
        ]);
        $raw = curl_exec($ch);
        curl_close($ch);
        $data = json_decode((string) $raw, true);

        return [
            'transaction_id' => $transactionId,
            'status' => strtoupper((string) ($data['transaction_status'] ?? 'PENDING')),
            'amount' => (float) ($data['gross_amount'] ?? 0),
        ];
    }

    public function verifyWebhook(string $payload, array $headers): bool
    {
        $data = json_decode($payload, true);
        if (!is_array($data)) {
            return false;
        }

        $given = (string) ($data['signature_key'] ?? '');
        if ($given === '' || $this->serverKey === '') {
            return false;
        }

        $expected = hash(
            'sha512',
            (string) ($data['order_id'] ?? '')
            . (string) ($data['status_code'] ?? '')
            . (string) ($data['gross_amount'] ?? '')
            . $this->serverKey
        );

        return hash_equals($expected, $given);
    }

    public function parseWebhook(array $payload): array
    {
        $status = strtolower((string) ($payload['transaction_status'] ?? ''));
        $fraud = strtolower((string) ($payload['fraud_status'] ?? 'accept'));

        $mapped = 'PENDING';
        if (in_array($status, ['settlement', 'capture'], true) && $fraud === 'accept') {
            $mapped = 'PAID';
        } elseif (in_array($status, ['deny', 'cancel', 'failure'], true)) {
            $mapped = 'FAILED';
        } elseif ($status === 'expire') {
            $mapped = 'EXPIRED';
        }

        return [
            'event_id' => (string) ($payload['transaction_id'] ?? '') . ':' . $status,
            'transaction_id' => (string) ($payload['order_id'] ?? ''),
            'order_ref' => (string) ($payload['order_id'] ?? ''),
            'amount' => (float) ($payload['gross_amount'] ?? 0),
            'status' => $mapped,
        ];
    }
}
