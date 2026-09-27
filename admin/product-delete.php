<?php
/**
 * Admin: hapus produk (PRD section 33)
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/autoload.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_admin();

if (!is_post()) {
    redirect(app_url('admin/products.php'));
}

if (!csrf_verify(input('csrf_token'))) {
    $_SESSION['flash'] = 'Token tidak valid';
    redirect(app_url('admin/products.php'));
}

$productId = (int) input('id', 0);
$productModel = new Product();
$product = $productId > 0 ? $productModel->findById($productId) : null;

if (!$product) {
    $_SESSION['flash'] = 'Produk tidak ditemukan';
    redirect(app_url('admin/products.php'));
}

// Hapus file digital & thumbnail yang terkait
$productFileModel = new ProductFile();
foreach ($productFileModel->findByProduct($productId) as $f) {
    $path = $productFileModel->storagePath() . '/' . $f['stored_name'];
    if (is_file($path)) {
        unlink($path);
    }
}

$thumbnail = $product['thumbnail'] ?? '';
if (!empty($thumbnail)) {
    $thumbPath = dirname(__DIR__) . '/uploads/' . $thumbnail;
    if (is_file($thumbPath)) {
        unlink($thumbPath);
    }
}

$productModel->delete($productId);

admin_audit('PRODUCT_DELETED', 'products', $productId, "Produk dihapus: {$product['name']}");
$_SESSION['flash'] = 'Produk berhasil dihapus';
redirect(app_url('admin/products.php'));
