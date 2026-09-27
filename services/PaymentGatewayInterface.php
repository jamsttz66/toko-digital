<?php
/**
 * Interface PaymentGateway.
 * Abstraction agar provider payment bisa diganti tanpa mengubah kode lain.
 */

interface PaymentGatewayInterface
{
    /**
     * Buat transaksi pembayaran baru.
     * Kembalikan: transaction_id, payment_url, qr_string, expired_at, raw
     */
    public function createTransaction(array $order): array;

    /**
     * Cek status transaksi ke provider.
     */
    public function getTransactionStatus(string $transactionId): array;

    /**
     * Verifikasi signature webhook (HMAC/RSA tergantung provider).
     */
    public function verifyWebhook(string $payload, array $headers): bool;

    /**
     * Ambil data penting dari payload webhook.
     * Kembalikan: event_id, transaction_id, order_ref, amount, status
     */
    public function parseWebhook(array $payload): array;
}
