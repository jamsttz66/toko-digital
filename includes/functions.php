<?php
/**
 * Helper functions global.
 * Security: escape output, CSRF, format Rupiah, dll.
 */

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/session.php';

/**
 * Escape output untuk mencegah XSS.
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Format harga ke Rupiah: 79000 -> "Rp79.000"
 */
function rupiah(float|int|string|null $amount): string
{
    $amount = (float) ($amount ?? 0);
    return 'Rp' . number_format($amount, 0, ',', '.');
}

/**
 * Format harga ke Rupiah dengan 2 desimal.
 */
function rupiah2(float|int|string|null $amount): string
{
    $amount = (float) ($amount ?? 0);
    return 'Rp' . number_format($amount, 2, ',', '.');
}

/**
 * Format tanggal Indonesia: "27 September 2026"
 */
function tgl_id(?string $datetime): string
{
    if (empty($datetime)) {
        return '-';
    }
    $ts = strtotime($datetime);
    if ($ts === false) {
        return '-';
    }
    $bulan = [1 => 'Januari','Februari','Maret','April','Mei','Juni',
        'Juli','Agustus','September','Oktober','November','Desember'];
    return (int) date('j', $ts) . ' ' . $bulan[(int) date('n', $ts)] . ' ' . date('Y', $ts);
}

/**
 * Format tanggal + jam Indonesia.
 */
function tgl_jam_id(?string $datetime): string
{
    if (empty($datetime)) {
        return '-';
    }
    $ts = strtotime($datetime);
    if ($ts === false) {
        return '-';
    }
    return tgl_id($datetime) . ' ' . date('H:i', $ts) . ' WIB';
}

/**
 * Generate CSRF token untuk session.
 */
function csrf_token(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validasi CSRF token dari POST.
 */
function csrf_verify(?string $token): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return !empty($_SESSION['csrf_token'])
        && !empty($token)
        && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Field input hidden berisi CSRF token.
 */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

/**
 * Tombol prev/next untuk hasil paginate model (rows, total, page, per_page).
 * $query adalah query string yang dipertahankan (filter status, tanggal, dll).
 */
function pagination_nav(array $result, string $basePath, array $query = []): string
{
    $page = max(1, (int) ($result['page'] ?? 1));
    $perPage = max(1, (int) ($result['per_page'] ?? 20));
    $total = (int) ($result['total'] ?? 0);
    $pages = max(1, (int) ceil($total / $perPage));

    if ($pages <= 1) {
        return '';
    }

    $link = static function (int $p) use ($basePath, $query): string {
        $query['page'] = $p;
        return app_url($basePath . '?' . http_build_query($query));
    };

    $html = '<nav class="d-flex justify-content-between align-items-center mt-3">';
    if ($page > 1) {
        $html .= '<a class="btn btn-outline-ink btn-sm" href="' . e($link($page - 1)) . '">← Sebelumnya</a>';
    } else {
        $html .= '<span class="btn btn-outline-ink btn-sm disabled">← Sebelumnya</span>';
    }
    $html .= '<span class="small text-muted">Halaman ' . $page . ' dari ' . $pages . '</span>';
    if ($page < $pages) {
        $html .= '<a class="btn btn-outline-ink btn-sm" href="' . e($link($page + 1)) . '">Berikutnya →</a>';
    } else {
        $html .= '<span class="btn btn-outline-ink btn-sm disabled">Berikutnya →</span>';
    }
    $html .= '</nav>';

    return $html;
}

/**
 * Redirect aman.
 */
function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

/**
 * Cek apakah request method POST.
 */
function is_post(): bool
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/**
 * Ambil nilai input POST/GET dengan default.
 */
function input(string $key, mixed $default = null): mixed
{
    return $_POST[$key] ?? $_GET[$key] ?? $default;
}

/**
 * Sanitasi string input.
 */
function clean(?string $value): string
{
    return trim(strip_tags((string) $value));
}

/**
 * Validasi email.
 */
function valid_email(?string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * URL aplikasi.
 */
function app_url(string $path = ''): string
{
    $base = rtrim((string) env('APP_URL', '/'), '/');
    return $base . '/' . ltrim($path, '/');
}

/**
 * URL asset publik.
 */
function asset(string $path): string
{
    return app_url('assets/' . ltrim($path, '/'));
}

/**
 * Buat slug aman dari teks.
 */
function slugify(string $text): string
{
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    $text = trim($text, '-');
    return $text !== '' ? $text : 'produk';
}

/**
 * Excerpt teks.
 */
function excerpt(?string $text, int $limit = 120): string
{
    $text = trim(strip_tags((string) $text));
    if (mb_strlen($text) <= $limit) {
        return e($text);
    }
    return e(mb_substr($text, 0, $limit)) . '…';
}

/**
 * Response JSON untuk API.
 */
function json_response(int $status, array $data): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function json_success(string $message = 'OK', array $data = [], int $status = 200): void
{
    json_response($status, [
        'success' => true,
        'message' => $message,
        'data' => $data,
    ]);
}

function json_error(string $message, array $errors = [], int $status = 400): void
{
    json_response($status, [
        'success' => false,
        'message' => $message,
        'errors' => $errors,
    ]);
}

/**
 * Log ke storage/logs.
 */
function log_error(string $channel, string $message, array $context = []): void
{
    $dir = dirname(__DIR__) . '/storage/logs';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $line = sprintf(
        "[%s] %s: %s %s\n",
        date('Y-m-d H:i:s'),
        strtoupper($channel),
        $message,
        $context ? json_encode($context, JSON_UNESCAPED_UNICODE) : ''
    );
    file_put_contents($dir . '/app.log', $line, FILE_APPEND | LOCK_EX);
}

/**
 * IP client untuk audit log.
 */
function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * User agent untuk audit log.
 */
function user_agent(): string
{
    return substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500);
}

/**
 * Catat audit log.
 */
function audit_log(string $action, string $entityType, ?int $entityId, string $description = ''): void
{
    $userId = $_SESSION['user']['id'] ?? null;

    Database::execute(
        "INSERT INTO audit_logs (user_id, action, entity_type, entity_id, description, ip_address, user_agent, created_at)
         VALUES (:uid, :action, :etype, :eid, :desc, :ip, :ua, datetime('now'))",
        [
            ':uid' => $userId,
            ':action' => $action,
            ':etype' => $entityType,
            ':eid' => $entityId,
            ':desc' => $description,
            ':ip' => client_ip(),
            ':ua' => user_agent(),
        ]
    );
}

/**
 * URL thumbnail produk. Pakai placeholder SVG jika tidak ada gambar.
 */
function thumbnailUrl(array $product): string
{
    if (!empty($product['thumbnail'])) {
        return app_url('uploads/' . ltrim($product['thumbnail'], '/'));
    }
    return app_url('assets/images/placeholder.svg');
}
