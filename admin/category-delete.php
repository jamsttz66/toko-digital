<?php
/**
 * Admin: hapus kategori (PRD section 33)
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/autoload.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_admin();

if (!is_post()) {
    redirect(app_url('admin/categories.php'));
}

if (!csrf_verify(input('csrf_token'))) {
    $_SESSION['flash'] = 'Token tidak valid';
    redirect(app_url('admin/categories.php'));
}

$categoryId = (int) input('id', 0);
$categoryModel = new Category();
$category = Database::selectOne("SELECT * FROM categories WHERE id = :id LIMIT 1", [':id' => $categoryId]);

if (!$category) {
    $_SESSION['flash'] = 'Kategori tidak ditemukan';
    redirect(app_url('admin/categories.php'));
}

// Produk di kategori ini jadi tanpa kategori (category_id = NULL)
Database::execute(
    "UPDATE products SET category_id = NULL, updated_at = datetime('now') WHERE category_id = :cid",
    [':cid' => $categoryId]
);

// Hapus gambar kategori jika ada
if (!empty($category['image'])) {
    $imagePath = dirname(__DIR__) . '/uploads/' . $category['image'];
    if (is_file($imagePath)) {
        unlink($imagePath);
    }
}

$categoryModel->delete($categoryId);

admin_audit('CATEGORY_DELETED', 'categories', $categoryId, "Kategori dihapus: {$category['name']}");
$_SESSION['flash'] = 'Kategori berhasil dihapus';
redirect(app_url('admin/categories.php'));
