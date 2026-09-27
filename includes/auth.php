<?php
/**
 * Authentication customer.
 * Dipanggil di setiap halaman customer yang butuh login.
 *
 * Pemakaian: require_once __DIR__ . '/includes/auth.php';
 */

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/autoload.php';

/**
 * Cek apakah user sudah login.
 */
function is_logged_in(): bool
{
    return !empty($_SESSION['user']['id']);
}

/**
 * Ambil data user yang login.
 */
function current_user(): ?array
{
    return is_logged_in() ? $_SESSION['user'] : null;
}

/**
 * Wajib login. Redirect ke login.php jika belum.
 */
function require_login(): void
{
    if (!is_logged_in()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/';
        redirect(app_url('login.php'));
    }
}

/**
 * Login user: set session + regenerate id (PRD 30).
 */
function login_user(array $user): void
{
    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
    ];

    $_SESSION['created_at'] = time();
}

/**
 * Logout user.
 */
function logout_user(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']);
    }

    session_destroy();
}

/**
 * Cek apakah user punya produk tertentu (untuk info duplicate purchase).
 */
function owns_product(int $productId): bool
{
    if (!is_logged_in()) {
        return false;
    }

    return (new Product())->isOwnedBy($productId, (int) $_SESSION['user']['id']);
}
