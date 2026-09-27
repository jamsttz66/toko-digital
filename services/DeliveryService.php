<?php
/**
 * DeliveryService.
 *
 * Pengiriman digital otomatis setelah pembayaran terverifikasi (PRD 25-27).
 * - delivery bersifat idempotent (webhook dua kali = satu delivery)
 * - token random 64 hex chars, disimpan sebagai HASH
 * - file tidak bisa diakses langsung
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/autoload.php';

class DeliveryService
{
    private int $tokenLifetimeHours = 87600; // 10 tahun default

    /**
     * Buat delivery untuk semua item order (idempotent).
     */
    public function createForOrder(int $orderId): void
    {
        $order = (new Order())->find($orderId);
        if (!$order) {
            return;
        }

        if (!in_array($order['status'], ['paid', 'completed'], true)) {
            return;
        }

        $items = (new Order())->items($orderId);

        foreach ($items as $item) {
            $this->createForOrderItem($orderId, $item);
        }
    }

    /**
     * Buat delivery untuk satu item. Lewati jika sudah ada (idempotent).
     */
    private function createForOrderItem(int $orderId, array $item): void
    {
        $deliveryModel = new Delivery();

        // Cek duplikat
        $existing = Database::selectOne(
            "SELECT id FROM deliveries WHERE order_id = :oid AND order_item_id = :oiid LIMIT 1",
            [':oid' => $orderId, ':oiid' => $item['id']]
        );

        if ($existing) {
            return;
        }

        $deliveryId = $deliveryModel->create($orderId, (int) $item['id'], (int) $item['product_id']);

        // Generate secure token
        $token = bin2hex(random_bytes(32)); // 64 hex chars
        $tokenHash = hash('sha256', $token);

        $expiresAt = date('Y-m-d H:i:s', time() + ($this->tokenLifetimeHours * 3600));

        Database::insert(
            "INSERT INTO download_tokens (delivery_id, user_id, token_hash, expires_at,
                max_downloads, download_count, created_at)
             VALUES (:did, :uid, :thash, :exp, 5, 0, datetime('now'))",
            [
                ':did' => $deliveryId,
                ':uid' => $_SESSION['user']['id'] ?? null,
                ':thash' => $tokenHash,
                ':exp' => $expiresAt,
            ]
        );

        $deliveryModel->markReady((int) $deliveryId);
    }

    /**
     * Ambil token aktif milik delivery (untuk link download di library).
     */
    public function getActiveToken(int $deliveryId, int $userId): ?array
    {
        return Database::selectOne(
            "SELECT dt.id, dt.token_hash, dt.expires_at, dt.max_downloads, dt.download_count,
                    dt.revoked_at, dt.created_at
             FROM download_tokens dt
             INNER JOIN deliveries d ON d.id = dt.delivery_id
             INNER JOIN orders o ON o.id = d.order_id
             WHERE dt.delivery_id = :did AND o.user_id = :uid
               AND dt.revoked_at IS NULL
             ORDER BY dt.created_at DESC LIMIT 1",
            [':did' => $deliveryId, ':uid' => $userId]
        );
    }

    /**
     * Generate token baru (regenerate oleh admin atau sistem).
     */
    public function regenerateToken(int $deliveryId, ?int $userId = null): string
    {
        // Revoke token lama
        Database::execute(
            "UPDATE download_tokens SET revoked_at = datetime('now')
             WHERE delivery_id = :did AND revoked_at IS NULL",
            [':did' => $deliveryId]
        );

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', time() + ($this->tokenLifetimeHours * 3600));

        Database::insert(
            "INSERT INTO download_tokens (delivery_id, user_id, token_hash, expires_at,
                max_downloads, download_count, created_at)
             VALUES (:did, :uid, :thash, :exp, 5, 0, datetime('now'))",
            [
                ':did' => $deliveryId,
                ':uid' => $userId,
                ':thash' => $tokenHash,
                ':exp' => $expiresAt,
            ]
        );

        return $token;
    }

    /**
     * Validasi token download (PRD 26).
     * Kembalikan delivery + data file jika valid.
     */
    public function validateToken(string $token): ?array
    {
        $tokenHash = hash('sha256', $token);

        $row = Database::selectOne(
            "SELECT dt.id AS token_id, dt.delivery_id, dt.user_id, dt.expires_at,
                    dt.max_downloads, dt.download_count, dt.revoked_at,
                    d.status AS delivery_status, d.product_id,
                    o.order_number, o.status AS order_status,
                    p.name AS product_name, p.slug,
                    pf.id AS file_id, pf.file_path, pf.original_name, pf.file_size, pf.mime_type
             FROM download_tokens dt
             INNER JOIN deliveries d ON d.id = dt.delivery_id
             INNER JOIN orders o ON o.id = d.order_id
             INNER JOIN products p ON p.id = d.product_id
             LEFT JOIN product_files pf ON pf.product_id = d.product_id AND pf.status = 'active'
             WHERE dt.token_hash = :thash
             LIMIT 1",
            [':thash' => $tokenHash]
        );

        if (!$row) {
            return null;
        }

        // Token revoked?
        if ($row['revoked_at'] !== null) {
            return ['invalid' => 'revoked'];
        }

        // Token expired?
        if (strtotime($row['expires_at']) < time()) {
            return ['invalid' => 'expired'];
        }

        // Delivery revoked?
        if ($row['delivery_status'] === 'revoked') {
            return ['invalid' => 'revoked'];
        }

        // Limit download tercapai?
        if ($row['max_downloads'] !== null && $row['download_count'] >= (int) $row['max_downloads']) {
            return ['invalid' => 'limit'];
        }

        // File ada?
        if (empty($row['file_path']) || !is_file($row['file_path'])) {
            log_error('download', 'File tidak ditemukan', ['product' => $row['product_name']]);
            return ['invalid' => 'file_missing'];
        }

        return $row;
    }

    /**
     * Catat download dan kirim file (dipanggil download.php).
     */
    public function recordDownload(int $tokenId, int $deliveryId): void
    {
        Database::execute(
            "UPDATE download_tokens SET download_count = download_count + 1,
                last_used_at = datetime('now')
             WHERE id = :tid",
            [':tid' => $tokenId]
        );

        Database::execute(
            "UPDATE deliveries SET last_download_at = datetime('now'),
                download_count = download_count + 1
             WHERE id = :did",
            [':did' => $deliveryId]
        );
    }

    /**
     * Revoke token (admin).
     */
    public function revokeToken(int $tokenId): void
    {
        Database::execute(
            "UPDATE download_tokens SET revoked_at = datetime('now') WHERE id = :tid",
            [':tid' => $tokenId]
        );
    }
}
