<?php
/**
 * Authentication admin (PRD section 31).
 *
 * Authorization wajib server-side. Tidak cukup hanya menyembunyikan tombol.
 *
 * Pemakaian: require_once __DIR__ . '/../includes/admin-auth.php';
 */

require_once __DIR__ . '/auth.php';

/**
 * Cek apakah user adalah admin/super_admin.
 */
function is_admin(): bool
{
    return is_logged_in() && in_array($_SESSION['user']['role'], ['admin', 'super_admin'], true);
}

/**
 * Cek apakah user adalah super_admin.
 */
function is_super_admin(): bool
{
    return is_logged_in() && $_SESSION['user']['role'] === 'super_admin';
}

/**
 * Wajib admin. 403 jika bukan admin (PRD 31).
 */
function require_admin(): void
{
    if (!is_logged_in()) {
        redirect(app_url('admin/login.php'));
    }

    if (!is_admin()) {
        http_response_code(403);
        exit('Forbidden: akses admin diperlukan');
    }
}

/**
 * Wajib super_admin (untuk akses sensitif: config payment, manage admin).
 */
function require_super_admin(): void
{
    require_admin();

    if (!is_super_admin()) {
        http_response_code(403);
        exit('Forbidden: akses super admin diperlukan');
    }
}

/**
 * Catat action admin ke audit log (PRD 55).
 */
function admin_audit(string $action, string $entityType, ?int $entityId, string $description = ''): void
{
    audit_log($action, $entityType, $entityId, $description);
}
