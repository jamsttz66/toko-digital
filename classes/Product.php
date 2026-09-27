<?php
/**
 * Model Product.
 */

class Product extends Model
{
    protected string $table = 'products';

    public function findBySlug(string $slug): ?array
    {
        return Database::selectOne(
            "SELECT p.*, c.name AS category_name
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.slug = :slug AND p.status = 'active'
             LIMIT 1",
            [':slug' => $slug]
        );
    }

    public function findById(int $id): ?array
    {
        return Database::selectOne(
            "SELECT p.*, c.name AS category_name
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.id = :id LIMIT 1",
            [':id' => $id]
        );
    }

    public function create(array $data): string
    {
        return Database::insert(
            "INSERT INTO products (category_id, name, slug, short_description, description, price,
                compare_price, product_type, thumbnail, status, featured, created_at, updated_at)
             VALUES (:category_id, :name, :slug, :short_description, :description, :price,
                :compare_price, :product_type, :thumbnail, :status, :featured, datetime('now'), datetime('now'))",
            [
                ':category_id' => $data['category_id'] ?? null,
                ':name' => $data['name'],
                ':slug' => $data['slug'] ?? slugify($data['name']),
                ':short_description' => $data['short_description'] ?? null,
                ':description' => $data['description'] ?? null,
                ':price' => $data['price'],
                ':compare_price' => $data['compare_price'] ?? null,
                ':product_type' => $data['product_type'] ?? 'other',
                ':thumbnail' => $data['thumbnail'] ?? null,
                ':status' => $data['status'] ?? 'active',
                ':featured' => $data['featured'] ?? 0,
            ]
        );
    }

    public function update(int $id, array $data): int
    {
        return Database::execute(
            "UPDATE products SET category_id = :category_id, name = :name, slug = :slug,
                short_description = :short_description, description = :description, price = :price,
                compare_price = :compare_price, product_type = :product_type, thumbnail = :thumbnail,
                status = :status, featured = :featured, updated_at = datetime('now')
             WHERE id = :id",
            [
                ':category_id' => $data['category_id'] ?? null,
                ':name' => $data['name'],
                ':slug' => $data['slug'] ?? slugify($data['name']),
                ':short_description' => $data['short_description'] ?? null,
                ':description' => $data['description'] ?? null,
                ':price' => $data['price'],
                ':compare_price' => $data['compare_price'] ?? null,
                ':product_type' => $data['product_type'] ?? 'other',
                ':thumbnail' => $data['thumbnail'] ?? null,
                ':status' => $data['status'] ?? 'active',
                ':featured' => $data['featured'] ?? 0,
                ':id' => $id,
            ]
        );
    }

    public function featured(int $limit = 8): array
    {
        return Database::select(
            "SELECT p.*, c.name AS category_name
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.status = 'active' AND p.featured = 1
             ORDER BY p.created_at DESC LIMIT :limit",
            [':limit' => $limit]
        );
    }

    public function paginate(array $filter = [], int $page = 1, int $perPage = 12): array
    {
        $where = ["p.status = 'active'"];
        $params = [];

        if (!empty($filter['q'])) {
            $where[] = "(p.name LIKE :q OR p.short_description LIKE :q2)";
            $like = '%' . $filter['q'] . '%';
            $params[':q'] = $like;
            $params[':q2'] = $like;
        }

        if (!empty($filter['category_slug'])) {
            $where[] = "c.slug = :cslug";
            $params[':cslug'] = $filter['category_slug'];
        }

        if (isset($filter['min_price']) && $filter['min_price'] !== '' && $filter['min_price'] !== null) {
            $where[] = "p.price >= :min_price";
            $params[':min_price'] = (float) $filter['min_price'];
        }

        if (isset($filter['max_price']) && $filter['max_price'] !== '' && $filter['max_price'] !== null) {
            $where[] = "p.price <= :max_price";
            $params[':max_price'] = (float) $filter['max_price'];
        }

        $orderBy = match ($filter['sort'] ?? 'newest') {
            'price_asc' => 'p.price ASC',
            'price_desc' => 'p.price DESC',
            'popular' => 'p.featured DESC, p.created_at DESC',
            default => 'p.created_at DESC',
        };

        $offset = ($page - 1) * $perPage;
        $whereSql = implode(' AND ', $where);

        $rows = Database::select(
            "SELECT p.*, c.name AS category_name
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE {$whereSql}
             ORDER BY {$orderBy}
             LIMIT :limit OFFSET :offset",
            array_merge($params, [':limit' => $perPage, ':offset' => $offset])
        );

        $total = (int) (Database::selectOne(
            "SELECT COUNT(p.id) AS cnt FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE {$whereSql}",
            $params
        )['cnt'] ?? 0);

        return [
            'rows' => $rows,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $total > 0 ? (int) ceil($total / $perPage) : 0,
        ];
    }

    public function paginateAdmin(int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;

        $rows = Database::select(
            "SELECT p.*, c.name AS category_name
             FROM products p LEFT JOIN categories c ON c.id = p.category_id
             ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset",
            [':limit' => $perPage, ':offset' => $offset]
        );

        return ['rows' => $rows, 'total' => $this->count(), 'page' => $page, 'per_page' => $perPage];
    }

    public function isOwnedBy(int $productId, int $userId): bool
    {
        $row = Database::selectOne(
            "SELECT 1 FROM order_items oi
             INNER JOIN orders o ON o.id = oi.order_id
             WHERE oi.product_id = :pid AND o.user_id = :uid
               AND o.status IN ('paid','completed')
             LIMIT 1",
            [':pid' => $productId, ':uid' => $userId]
        );
        return $row !== null;
    }

    public function bestSellers(int $limit = 5): array
    {
        return Database::select(
            "SELECT p.name, p.price, COUNT(oi.id) AS sold
             FROM order_items oi
             INNER JOIN products p ON p.id = oi.product_id
             INNER JOIN orders o ON o.id = oi.order_id
             WHERE o.status IN ('paid','completed')
             GROUP BY p.id, p.name, p.price
             ORDER BY sold DESC LIMIT :limit",
            [':limit' => $limit]
        );
    }
}
