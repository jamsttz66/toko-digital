<?php
/**
 * Base Model - operasi generik.
 */

abstract class Model
{
    protected string $table;
    protected string $primaryKey = 'id';

    public function find(int $id): ?array
    {
        return Database::selectOne(
            "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1",
            [':id' => $id]
        );
    }

    public function all(): array
    {
        return Database::select("SELECT * FROM {$this->table} ORDER BY {$this->primaryKey} DESC");
    }

    public function delete(int $id): int
    {
        return Database::execute(
            "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id",
            [':id' => $id]
        );
    }

    public function count(string $where = '', array $params = []): int
    {
        $sql = "SELECT COUNT(*) AS cnt FROM {$this->table}";
        if ($where !== '') {
            $sql .= " WHERE {$where}";
        }
        $row = Database::selectOne($sql, $params);
        return (int) ($row['cnt'] ?? 0);
    }
}
