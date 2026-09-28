<?php
/**
 * Midtrans Core API — QRIS (sandbox / production).
 *
 * Charge QRIS: POST https://api.sandbox.midtrans.com/v2/charge
 * QR code:     GET  https://api.sandbox.midtrans.com/v2/qris/{tx}/qr-code
 * Notifikasi:  SHA512(order_id + status_code + gross_amount + ServerKey)
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

        $email = trim((string) ($order['customer_email'] ?? ''));
        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new RuntimeException('Email customer tidak valid untuk Midtrans');
        }
        $phone = preg_replace('/[^0-9]+/', '', (string) ($order['customer_phone'] ?? ''));
        if (strpos($phone, '0') === 0) {
            $phone = '62' . substr($phone, 1);
        }

        $body = [
            'payment_type' => 'qris',
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $amount,
            ],
            'customer_details' => [
                'first_name' => substr((string) ($order['customer_name'] ?? 'Customer'), 0, 20) ?: 'Customer',
                'email' => $email,
                'phone' => $phone !== '' ? $phone : '6281234567890',
            ],
            'item_details' => $order['items'] ?? [[
                'id' => substr($orderId, 0, 50),
                'price' => $amount,
                'quantity' => 1,
                'name' => substr('Pesanan ' . $orderId, 0, 20),
            ]],
        ];

        $host = $this->production ? 'https://api.midtrans.com' : 'https://api.sandbox.midtrans.com';
        $ch = curl_init($host . '/v2/charge');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_USERPWD => $this->serverKey . ':',
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
            ],
        ]);

        $raw = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($raw === false || $code < 200 || $code >= 300) {
            log_error('midtrans', 'QRIS charge gagal', ['http' => $code, 'err' => $err, 'body' => $raw, 'request_body' => json_encode($body), 'auth_header_len' => strlen('Basic ' . base64_encode($this->serverKey . ':'))]);
            throw new RuntimeException('Midtrans menolak transaksi (HTTP ' . $code . ')');
        }

        $data = json_decode((string) $raw, true);
        if (!is_array($data) || empty($data['transaction_id']) || empty($data['qr_string'])) {
            throw new RuntimeException('Respons Midtrans tidak lengkap');
        }

        $expired = strtotime((string) ($data['expiry_time'] ?? ''));
        if ($expired === false) {
            $expired = time() + 86400;
        }

        return [
            'transaction_id' => $data['transaction_id'],
            'payment_url' => $host . '/v2/qris/' . rawurlencode($data['transaction_id']) . '/qr-code',
            'qr_string' => (string) $data['qr_string'],
            'expired_at' => date('Y-m-d H:i:s', $expired),
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
