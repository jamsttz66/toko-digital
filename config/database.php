<?php
/**
 * Koneksi database via PDO.
 * Mendukung MySQL (production) dan SQLite (development/demo).
 */

require_once __DIR__ . '/env.php';

class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            self::$pdo = self::connect();
        }

        return self::$pdo;
    }

    private static function connect(): PDO
    {
        $driver = strtolower((string) env('DB_DRIVER', 'mysql'));

        if ($driver === 'sqlite') {
            return self::connectSqlite();
        }

        return self::connectMysql();
    }

    private static function connectMysql(): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            env('DB_HOST', 'localhost'),
            env('DB_PORT', '3306'),
            env('DB_DATABASE', 'digital_store')
        );

        $pdo = new PDO($dsn, env('DB_USERNAME'), env('DB_PASSWORD'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return $pdo;
    }

    private static function connectSqlite(): PDO
    {
        $path = env('DB_PATH', dirname(__DIR__) . '/storage/database.sqlite');

        if (!str_starts_with($path, '/')) {
            $path = dirname(__DIR__) . '/' . $path;
        }

        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $pdo = new PDO('sqlite:' . $path, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        // Foreign keys wajib aktif di SQLite
        $pdo->exec('PRAGMA foreign_keys = ON');

        // Bootstrap schema SQLite jika database masih kosong
        self::bootstrapSqlite($pdo, $path);

        return $pdo;
    }

    private static function bootstrapSqlite(PDO $pdo, string $path): void
    {
        $schemaFile = dirname(__DIR__) . '/database/schema_sqlite.sql';

        if (filesize($path) > 0 || !is_file($schemaFile)) {
            return;
        }

        $sql = file_get_contents($schemaFile);
        if ($sql === false || $sql === '') {
            return;
        }

        $pdo->exec($sql);
    }

    /**
     * Jalankan query SELECT dengan prepared statement.
     */
    public static function select(string $sql, array $params = []): array
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Ambi satu baris.
     */
    public static function selectOne(string $sql, array $params = []): ?array
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);

        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * Jalankan INSERT/UPDATE/DELETE. Kembalikan jumlah baris terpengaruh.
     */
    public static function execute(string $sql, array $params = []): int
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }

    /**
     * INSERT dan kembalikan last insert id.
     */
    public static function insert(string $sql, array $params = []): string
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);

        return self::pdo()->lastInsertId();
    }

    /**
     * Cek apakah tabel ada (untuk migrasi/pengecekan).
     */
    public static function tableExists(string $table): bool
    {
        $driver = strtolower((string) env('DB_DRIVER', 'mysql'));

        if ($driver === 'sqlite') {
            $row = self::selectOne(
                "SELECT name FROM sqlite_master WHERE type='table' AND name = :t",
                [':t' => $table]
            );
        } else {
            $row = self::selectOne(
                "SELECT 1 FROM information_schema.tables WHERE table_name = :t LIMIT 1",
                [':t' => $table]
            );
        }

        return $row !== null;
    }
}
