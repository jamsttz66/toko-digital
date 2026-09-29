<?php
/**
 * Header global customer.
 * Pemakaian: require_once __DIR__ . '/includes/header.php';
 *
 * Variabel opsional yang bisa diset sebelum include:
 *   $pageTitle       - judul halaman
 *   $pageDescription - meta description (SEO PRD 52)
 *   $currentPage     - key nav aktif
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/admin-auth.php';

$pageTitle = $pageTitle ?? 'Toko Digital';
$pageDescription = $pageDescription ?? 'Produk digital pilihan, langsung setelah pembayaran. Bayar dengan QRIS, dapatkan akses otomatis.';
$currentPage = $currentPage ?? '';

// Hitung cart count untuk navbar
$cartCount = 0;
try {
    $cartCount = (new Cart())->countItems((int) (new Cart())->current()['id']);
} catch (Throwable) {
    // database belum siap
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($pageTitle); ?> — Toko Digital</title>
    <meta name="description" content="<?php echo e($pageDescription); ?>">

    <link rel="canonical" href="<?php echo e('https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '/')); ?>">

    <!-- Open Graph (PRD 52) -->
    <meta property="og:title" content="<?php echo e($pageTitle); ?>">
    <meta property="og:description" content="<?php echo e($pageDescription); ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Toko Digital">

    <link rel="icon" href="data:,">

    <!-- CSRF token untuk fetch API (PRD 44.3) -->
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="<?php echo asset('css/app-dark.css'); ?>" rel="stylesheet">
</head>
<body>
<?php require_once __DIR__ . '/navbar.php'; ?>

<main id="main-content">
