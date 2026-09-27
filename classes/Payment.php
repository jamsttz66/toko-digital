<?php
/**
 * Model Payment.
 */

class Payment extends Model
{
    protected string $table = 'payments';

    public function create(array $data): string
    {
        return Database::insert(
            "INSERT INTO payments (order_id, provider, provider_transaction_id, payment_method, amount,
                status, payment_url, qr_string, expired_at, raw_response, created_at, updated_at)
             VALUES (:oid, :provider, :ptx, :method, :amount, 'pending', :purl, :qr, :exp, :raw,
                datetime('now'), datetime('now'))",
            [
                ':oid' => $data['order_id'],
                ':provider' => $data['provider'],
                ':ptx' => $data['provider_transaction_id'] ?? null,
                ':method' => $data['payment_method'] ?? 'qris',
                ':amount' => $data['amount'],
                ':purl' => $data['payment_url'] ?? null,
                ':qr' => $data['qr_string'] ?? null,
                ':exp' => $data['expired_at'] ?? null,
                ':raw' => $data['raw_response'] ?? null,
            ]
        );
    }

    public function findByOrder(int $orderId): ?array
    {
        return Database::selectOne(
            "SELECT * FROM payments WHERE order_id = :oid ORDER BY id DESC LIMIT 1",
            [':oid' => $orderId]
        );
    }

    public function findByTransactionId(string $transactionId): ?array
    {
        return Database::selectOne(
            "SELECT * FROM payments WHERE provider_transaction_id = :txid LIMIT 1",
            [':txid' => $transactionId]
        );
    }

    public function markPaid(int $paymentId, ?string $rawResponse = null): void
    {
        Database::execute(
            "UPDATE payments SET status = 'paid', paid_at = datetime('now'),
                raw_response = :raw, updated_at = datetime('now')
             WHERE id = :id AND status NOT IN ('paid','refunded')",
            [':raw' => $rawResponse, ':id' => $paymentId]
        );
    }

    public function markFailed(int $paymentId, ?string $rawResponse = null): void
    {
        Database::execute(
            "UPDATE payments SET status = 'failed', raw_response = :raw, updated_at = datetime('now')
             WHERE id = :id",
            [':raw' => $rawResponse, ':id' => $paymentId]
        );
    }

    public function markExpired(int $paymentId): void
    {
        Database::execute(
            "UPDATE payments SET status = 'expired', updated_at = datetime('now')
             WHERE id = :id AND status = 'pending'",
            [':id' => $paymentId]
        );
    }

    /**
     * Catat event webhook (idempotency: event_id unik).
     * Kembalikan true jika event baru, false jika duplikat.
     */
    public function logEvent(array $data): bool
    {
        try {
            Database::insert(
                "INSERT INTO payment_events (provider, event_id, order_id, event_type, payload,
                    signature_valid, processed, created_at)
                 VALUES (:provider, :eid, :oid, :etype, :payload, :sig, 0, datetime('now'))",
                [
                    ':provider' => $data['provider'],
                    ':eid' => $data['event_id'],
                    ':oid' => $data['order_id'] ?? null,
                    ':etype' => $data['event_type'] ?? null,
                    ':payload' => $data['payload'] ?? null,
                    ':sig' => $data['signature_valid'] ?? 0,
                ]
            );
            return true;
        } catch (PDOException) {
            // UNIQUE constraint -> event duplikat
            return false;
        }
    }

    public function markEventProcessed(string $eventId): void
    {
        Database::execute(
            "UPDATE payment_events SET processed = 1 WHERE event_id = :eid",
            [':eid' => $eventId]
        );
    }

    /**
     * Ambil pembayaran terakhir milik user untuk order tertentu.
     */
    public function findUserPayment(int $orderId, int $userId): ?array
    {
        return Database::selectOne(
            "SELECT pay.* FROM payments pay
             INNER JOIN orders o ON o.id = pay.order_id
             WHERE pay.order_id = :oid AND o.user_id = :uid
             ORDER BY pay.id DESC LIMIT 1",
            [':oid' => $orderId, ':uid' => $userId]
        );
    }

    public function paginateAdmin(int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;

        $rows = Database::select(
            "SELECT pay.*, o.order_number, o.customer_name
             FROM payments pay
             INNER JOIN orders o ON o.id = pay.order_id
             ORDER BY pay.created_at DESC LIMIT :limit OFFSET :offset",
            [':limit' => $perPage, ':offset' => $offset]
        );

        return ['rows' => $rows, 'total' => $this->count(), 'page' => $page, 'per_page' => $perPage];
    }
}
