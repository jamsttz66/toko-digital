<?php
/**
 * Model Delivery.
 * Delivery dibuat otomatis setelah pembayaran terverifikasi.
 */

class Delivery extends Model
{
    protected string $table = 'deliveries';

    public function create(int $orderId, int $orderItemId, int $productId): string
    {
        return Database::insert(
            "INSERT INTO deliveries (order_id, order_item_id, product_id, status, download_count,
                created_at, updated_at)
             VALUES (:oid, :oiid, :pid, 'pending', 0, datetime('now'), datetime('now'))",
            [':oid' => $orderId, ':oiid' => $orderItemId, ':pid' => $productId]
        );
    }

    /**
     * Ambil delivery untuk satu order.
     */
    public function findByOrder(int $orderId): array
    {
        return Database::select(
            "SELECT d.*, p.name AS product_name, p.slug, p.product_type,
                    pf.id AS file_id, pf.original_name, pf.file_path, pf.file_size, pf.mime_type
             FROM deliveries d
             INNER JOIN products p ON p.id = d.product_id
             LEFT JOIN product_files pf ON pf.product_id = d.product_id AND pf.status = 'active'
             WHERE d.order_id = :oid
             ORDER BY d.id ASC",
            [':oid' => $orderId]
        );
    }

    /**
     * Ambil delivery milik user tertentu (guard kepemilikan).
     */
    public function findUserDelivery(int $deliveryId, int $userId): ?array
    {
        return Database::selectOne(
            "SELECT d.*, p.name AS product_name, pf.file_path, pf.original_name, pf.file_size, pf.mime_type
             FROM deliveries d
             INNER JOIN orders o ON o.id = d.order_id
             INNER JOIN products p ON p.id = d.product_id
             LEFT JOIN product_files pf ON pf.product_id = d.product_id AND pf.status = 'active'
             WHERE d.id = :did AND o.user_id = :uid
             LIMIT 1",
            [':did' => $deliveryId, ':uid' => $userId]
        );
    }

    public function markReady(int $deliveryId): void
    {
        Database::execute(
            "UPDATE deliveries SET status = 'ready', generated_at = datetime('now'), updated_at = datetime('now')
             WHERE id = :id",
            [':id' => $deliveryId]
        );
    }

    public function markDelivered(int $deliveryId): void
    {
        Database::execute(
            "UPDATE deliveries SET status = 'delivery', last_download_at = datetime('now'),
                download_count = download_count + 1, updated_at = datetime('now')
             WHERE id = :id",
            [':id' => $deliveryId]
        );
    }

    public function markFailed(int $deliveryId, string $reason = ''): void
    {
        Database::execute(
            "UPDATE deliveries SET status = 'failed', updated_at = datetime('now')
             WHERE id = :id",
            [':id' => $deliveryId]
        );
        log_error('delivery', "Delivery #{$deliveryId} gagal: {$reason}");
    }

    public function revoke(int $deliveryId): void
    {
        Database::execute(
            "UPDATE deliveries SET status = 'revoked', updated_at = datetime('now')
             WHERE id = :id",
            [':id' => $deliveryId]
        );
    }

    /**
     * Product library customer: semua produk yang sudah dibeli.
     */
    public function userLibrary(int $userId): array
    {
        return Database::select(
            "SELECT d.id AS delivery_id, d.status, d.download_count, d.last_download_at,
                    p.name AS product_name, p.slug, p.thumbnail, p.product_type,
                    o.order_number, o.paid_at,
                    pf.original_name, pf.file_size
             FROM deliveries d
             INNER JOIN orders o ON o.id = d.order_id
             INNER JOIN products p ON p.id = d.product_id
             LEFT JOIN product_files pf ON pf.product_id = d.product_id AND pf.status = 'active'
             WHERE o.user_id = :uid AND o.status IN ('paid','completed')
             ORDER BY d.generated_at DESC, d.id DESC",
            [':uid' => $userId]
        );
    }

    public function paginateAdmin(int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;

        $rows = Database::select(
            "SELECT d.*, p.name AS product_name, o.order_number, o.customer_name
             FROM deliveries d
             INNER JOIN orders o ON o.id = d.order_id
             INNER JOIN products p ON p.id = d.product_id
             ORDER BY d.created_at DESC LIMIT :limit OFFSET :offset",
            [':limit' => $perPage, ':offset' => $offset]
        );

        return ['rows' => $rows, 'total' => $this->count(), 'page' => $page, 'per_page' => $perPage];
    }
}
