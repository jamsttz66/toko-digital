<?php
/**
 * Layout admin (sidebar + topbar).
 * Dipakai oleh semua halaman admin.
 *
 * Variabel: $pageTitle, $activeMenu
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/autoload.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin-auth.php';

require_admin();

$pageTitle = $pageTitle ?? 'Admin';
$activeMenu = $activeMenu ?? '';

$menu = [
    'dashboard'  => ['Dashboard', 'index.php'],
    'products'   => ['Produk', 'products.php'],
    'categories' => ['Kategori', 'categories.php'],
    'orders'     => ['Pesanan', 'orders.php'],
    'payments'   => ['Pembayaran', 'payments.php'],
    'deliveries' => ['Pengiriman', 'deliveries.php'],
    'users'      => ['Customer', 'users.php'],
    'vouchers'   => ['Voucher', 'vouchers.php'],
    'reports'    => ['Laporan', 'reports.php'],
    'audit'      => ['Audit Log', 'audit-logs.php'],
    'settings'   => ['Pengaturan', 'settings.php'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($pageTitle); ?> — Admin — Toko Digital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo asset('css/app.css'); ?>" rel="stylesheet">
</head>
<body style="background: var(--color-bg)">
<nav class="navbar navbar-custom sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?php echo app_url('admin/dashboard.php'); ?>">
            Toko Digital <span class="text-muted" style="font-weight:400;font-size:.85rem">Admin</span>
        </a>
        <div class="d-flex align-items-center gap-3">
            <a href="<?php echo app_url('index.php'); ?>" class="btn btn-outline-ink btn-sm">Lihat Toko</a>
            <span class="small text-muted d-none d-md-inline"><?php echo e($_SESSION['user']['name']); ?></span>
        </div>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-2 px-0">
            <div class="admin-sidebar py-3">
                <ul class="nav flex-column">
                    <?php foreach ($menu as $key => [$label, $file]): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $activeMenu === $key ? 'active' : ''; ?>"
                               href="<?php echo app_url('admin/' . $file); ?>">
                                <?php echo e($label); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <div class="col-lg-10 py-4 px-md-4">
            <div class="page-header">
                <h1><?php echo e($pageTitle); ?></h1>
            </div>
