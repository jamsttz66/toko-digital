<?php
/**
 * Model User.
 */

class User extends Model
{
    protected string $table = 'users';

    public function findByEmail(string $email): ?array
    {
        return Database::selectOne(
            "SELECT * FROM users WHERE email = :email LIMIT 1",
            [':email' => $email]
        );
    }

    public function create(array $data): string
    {
        return Database::insert(
            "INSERT INTO users (name, email, password, role, phone, status, created_at, updated_at)
             VALUES (:name, :email, :password, :role, :phone, 'active', datetime('now'), datetime('now'))",
            [
                ':name' => $data['name'],
                ':email' => $data['email'],
                ':password' => $data['password'],
                ':role' => $data['role'] ?? 'customer',
                ':phone' => $data['phone'] ?? null,
            ]
        );
    }

    public function updateProfile(int $id, array $data): int
    {
        return Database::execute(
            "UPDATE users SET name = :name, phone = :phone, updated_at = datetime('now') WHERE id = :id",
            [':name' => $data['name'], ':phone' => $data['phone'] ?? null, ':id' => $id]
        );
    }

    public function updatePassword(int $id, string $hashedPassword): int
    {
        return Database::execute(
            "UPDATE users SET password = :p, updated_at = datetime('now') WHERE id = :id",
            [':p' => $hashedPassword, ':id' => $id]
        );
    }

    public function updateRole(int $id, string $role): int
    {
        return Database::execute(
            "UPDATE users SET role = :role, updated_at = datetime('now') WHERE id = :id",
            [':role' => $role, ':id' => $id]
        );
    }

    public function updateStatus(int $id, string $status): int
    {
        return Database::execute(
            "UPDATE users SET status = :status, updated_at = datetime('now') WHERE id = :id",
            [':status' => $status, ':id' => $id]
        );
    }

    public function paginateCustomers(int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;

        $rows = Database::select(
            "SELECT id, name, email, phone, role, status, created_at
             FROM users WHERE role = 'customer'
             ORDER BY created_at DESC LIMIT :limit OFFSET :offset",
            [':limit' => $perPage, ':offset' => $offset]
        );

        return ['rows' => $rows, 'total' => $this->count("role = 'customer'"),
                'page' => $page, 'per_page' => $perPage];
    }

    public function admins(): array
    {
        return Database::select(
            "SELECT id, name, email, role, status, created_at
             FROM users WHERE role IN ('admin','super_admin') ORDER BY name ASC"
        );
    }
}
