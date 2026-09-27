<?php
/**
 * Model Category.
 */

class Category extends Model
{
    protected string $table = 'categories';

    public function findBySlug(string $slug): ?array
    {
        return Database::selectOne(
            "SELECT * FROM categories WHERE slug = :slug LIMIT 1",
            [':slug' => $slug]
        );
    }

    public function create(array $data): string
    {
        return Database::insert(
            "INSERT INTO categories (name, slug, description, image, status, created_at, updated_at)
             VALUES (:name, :slug, :description, :image, :status, datetime('now'), datetime('now'))",
            [
                ':name' => $data['name'],
                ':slug' => $data['slug'] ?? slugify($data['name']),
                ':description' => $data['description'] ?? null,
                ':image' => $data['image'] ?? null,
                ':status' => $data['status'] ?? 'active',
            ]
        );
    }

    public function update(int $id, array $data): int
    {
        return Database::execute(
            "UPDATE categories SET name = :name, slug = :slug, description = :description,
                image = :image, status = :status, updated_at = datetime('now')
             WHERE id = :id",
            [
                ':name' => $data['name'],
                ':slug' => $data['slug'] ?? slugify($data['name']),
                ':description' => $data['description'] ?? null,
                ':image' => $data['image'] ?? null,
                ':status' => $data['status'] ?? 'active',
                ':id' => $id,
            ]
        );
    }

    public function active(): array
    {
        return Database::select("SELECT * FROM categories WHERE status = 'active' ORDER BY name ASC");
    }

    public function activeWithCount(): array
    {
        return Database::select(
            "SELECT c.*, COUNT(p.id) AS product_count
             FROM categories c
             LEFT JOIN products p ON p.category_id = c.id AND p.status = 'active'
             WHERE c.status = 'active'
             GROUP BY c.id
             ORDER BY c.name ASC"
        );
    }
}
