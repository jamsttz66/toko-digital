<?php
/**
 * Model Cart.
 * Cart anonymous memakai session_id, cart customer memakai user_id.
 */

class Cart extends Model
{
    protected string $table = 'carts';

    public function getOrCreateForUser(int $userId): array
    {
        $cart = Database::selectOne(
            "SELECT * FROM carts WHERE user_id = :uid LIMIT 1",
            [':uid' => $userId]
        );

        if (!$cart) {
            $id = Database::insert(
                "INSERT INTO carts (user_id, created_at, updated_at) VALUES (:uid, datetime('now'), datetime('now'))",
                [':uid' => $userId]
            );
            $cart = ['id' => (int) $id, 'user_id' => $userId];
        }

        return $cart;
    }

    public function getOrCreateForSession(string $sessionId): array
    {
        $cart = Database::selectOne(
            "SELECT * FROM carts WHERE session_id = :sid LIMIT 1",
            [':sid' => $sessionId]
        );

        if (!$cart) {
            $id = Database::insert(
                "INSERT INTO carts (session_id, created_at, updated_at) VALUES (:sid, datetime('now'), datetime('now'))",
                [':sid' => $sessionId]
            );
            $cart = ['id' => (int) $id, 'session_id' => $sessionId];
        }

        return $cart;
    }

    public function current(): array
    {
        if (!empty($_SESSION['user']['id'])) {
            return $this->getOrCreateForUser((int) $_SESSION['user']['id']);
        }
        return $this->getOrCreateForSession(session_id());
    }

    public function mergeAnonymousToUser(int $userId): void
    {
        $anonCart = Database::selectOne(
            "SELECT id FROM carts WHERE session_id = :sid LIMIT 1",
            [':sid' => session_id()]
        );

        if (!$anonCart) {
            return;
        }

        $userCart = $this->getOrCreateForUser($userId);

        $items = Database::select(
            "SELECT * FROM cart_items WHERE cart_id = :cid",
            [':cid' => $anonCart['id']]
        );

        foreach ($items as $item) {
            Database::execute(
                "DELETE FROM cart_items WHERE cart_id = :usercart AND product_id = :pid",
                [':usercart' => $userCart['id'], ':pid' => $item['product_id']]
            );
            Database::execute(
                "INSERT INTO cart_items (cart_id, product_id, quantity, price_snapshot, created_at, updated_at)
                 VALUES (:cart, :pid, 1, :price, datetime('now'), datetime('now'))",
                [':cart' => $userCart['id'], ':pid' => $item['product_id'], ':price' => $item['price_snapshot']]
            );
        }

        Database::execute("DELETE FROM cart_items WHERE cart_id = :cid", [':cid' => $anonCart['id']]);
        Database::execute("DELETE FROM carts WHERE id = :cid", [':cid' => $anonCart['id']]);
    }

    public function addItem(int $cartId, int $productId): void
    {
        $product = Database::selectOne(
            "SELECT id, price, status FROM products WHERE id = :id AND status = 'active' LIMIT 1",
            [':id' => $productId]
        );

        if (!$product) {
            throw new RuntimeException('Produk tidak tersedia');
        }

        Database::insert(
            "INSERT OR IGNORE INTO cart_items (cart_id, product_id, quantity, price_snapshot, created_at, updated_at)
             VALUES (:cart, :pid, 1, :price, datetime('now'), datetime('now'))",
            [':cart' => $cartId, ':pid' => $productId, ':price' => $product['price']]
        );
    }

    public function removeItem(int $cartId, int $productId): void
    {
        Database::execute(
            "DELETE FROM cart_items WHERE cart_id = :cart AND product_id = :pid",
            [':cart' => $cartId, ':pid' => $productId]
        );
    }

    public function items(int $cartId): array
    {
        return Database::select(
            "SELECT ci.id, ci.product_id, ci.quantity, ci.price_snapshot,
                    p.name, p.slug, p.thumbnail, p.status, p.compare_price,
                    c.name AS category_name
             FROM cart_items ci
             INNER JOIN products p ON p.id = ci.product_id
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE ci.cart_id = :cart
             ORDER BY ci.created_at DESC",
            [':cart' => $cartId]
        );
    }

    public function countItems(int $cartId): int
    {
        $row = Database::selectOne(
            "SELECT COUNT(*) AS cnt FROM cart_items WHERE cart_id = :cart",
            [':cart' => $cartId]
        );
        return (int) ($row['cnt'] ?? 0);
    }

    public function total(int $cartId): float
    {
        $row = Database::selectOne(
            "SELECT SUM(price_snapshot) AS total FROM cart_items WHERE cart_id = :cart",
            [':cart' => $cartId]
        );
        return (float) ($row['total'] ?? 0);
    }

    public function clear(int $cartId): void
    {
        Database::execute("DELETE FROM cart_items WHERE cart_id = :cart", [':cart' => $cartId]);
    }
}
