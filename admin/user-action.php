<?php
/**
 * Admin: aksi customer (suspend / activate) — PRD section 38
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/autoload.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_admin();

if (!is_post()) {
    redirect(app_url('admin/users.php'));
}

if (!csrf_verify(input('csrf_token'))) {
    $_SESSION['flash'] = 'Token tidak valid';
    redirect(app_url('admin/users.php'));
}

$userId = (int) input('id', 0);
$action = clean(input('action', ''));

$userModel = new User();

// Ambil user by id
$user = Database::selectOne("SELECT * FROM users WHERE id = :id LIMIT 1", [':id' => $userId]);

if (!$user) {
    $_SESSION['flash'] = 'User tidak ditemukan';
    redirect(app_url('admin/users.php'));
}

// Jangan biarkan super_admin mem_suspend dirinya sendiri
$me = (int) ($_SESSION['user']['id'] ?? 0);
if ($userId === $me) {
    $_SESSION['flash'] = 'Anda tidak bisa mengubah status akun sendiri';
    redirect(app_url('admin/users.php'));
}

if ($action === 'suspend') {
    if ($user['role'] !== 'customer') {
        $_SESSION['flash'] = 'Hanya customer yang bisa di-suspend';
        redirect(app_url('admin/users.php'));
    }
    $userModel->updateStatus($userId, 'suspended');
    admin_audit('USER_SUSPENDED', 'users', $userId, "Customer di-suspend: {$user['email']}");
    $_SESSION['flash'] = 'Customer di-nonaktifkan';
} elseif ($action === 'activate') {
    $userModel->updateStatus($userId, 'active');
    admin_audit('USER_ACTIVATED', 'users', $userId, "Customer diaktifkan: {$user['email']}");
    $_SESSION['flash'] = 'Customer diaktifkan kembali';
} else {
    $_SESSION['flash'] = 'Aksi tidak dikenal';
}

redirect(app_url('admin/users.php'));
