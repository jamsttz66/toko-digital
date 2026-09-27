<?php
/**
 * Product Library (PRD section 28)
 * URL: /library.php
 *
 * Customer melihat semua produk yang sudah dibeli + download.
 */
require_once __DIR__ . '/includes/header.php';
require_login();

$deliveryService = new DeliveryService();
$library = (new Delivery())->userLibrary((int) $_SESSION['user']['id']);

$pageTitle = 'Produk Saya';
?>

<div class="container-narrow py-4">
    <div class="page-header">
        <h1>Produk Saya</h1>
        <p class="text-muted mb-0">Semua produk digital yang sudah Anda beli.</p>
    </div>

    <?php if (!$library): ?>
        <div class="empty-state">
            <h3>Belum ada produk</h3>
            <p>Produk yang Anda beli akan muncul di sini.</p>
            <a href="<?php echo app_url('produk.php'); ?>" class="btn btn-primary">Lihat Produk</a>
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-md-2 g-3 g-md-4">
            <?php foreach ($library as $item): ?>
                <div class="col">
                    <div class="card-product h-100 d-flex flex-column">
                        <div class="card-body">
                            <div class="d-flex align-items-start gap-3">
                                <div style="width:64px;height:64px;flex-shrink:0;border-radius:8px;overflow:hidden;border:1px solid var(--color-line)">
                                    <img src="<?php echo thumbnailUrl($item); ?>" alt="<?php echo e($item['product_name']); ?>" style="width:100%;height:100%;object-fit:cover">
                                </div>
                                <div class="flex-grow-1">
                                    <h3 class="fs-6 mb-1"><?php echo e($item['product_name']); ?></h3>
                                    <div class="small text-muted">
                                        Dibeli: <?php echo e(tgl_id($item['paid_at'])); ?>
                                    </div>
                                    <div class="small text-muted mono">
                                        <?php echo e($item['order_number']); ?>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3 d-flex align-items-center gap-2">
                                <?php
                                $badgeClass = match ($item['status']) {
                                    'ready' => 'ready',
                                    'delivered' => 'delivered',
                                    'pending' => 'pending',
                                    default => 'failed',
                                };
                                $statusLabel = match ($item['status']) {
                                    'ready' => 'Siap diunduh',
                                    'delivered' => 'Sudah diunduh',
                                    'pending' => 'Diproses',
                                    'revoked' => 'Akses dicabut',
                                    default => $item['status'],
                                };
                                ?>
                                <span class="badge-soft <?php echo e($badgeClass); ?>"><?php echo e($statusLabel); ?></span>
                                <?php if ((int) $item['download_count'] > 0): ?>
                                    <span class="small text-muted">Diunduh <?php echo (int) $item['download_count']; ?>×</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($item['status'] === 'ready' || $item['status'] === 'delivered'): ?>
                            <div class="p-3 border-top border-line">
                                <?php $downloadUrl = $deliveryService->getDownloadUrl((int) $item['delivery_id'], (int) $_SESSION['user']['id']); ?>
                                <?php if ($downloadUrl): ?>
                                    <a href="<?php echo e($downloadUrl); ?>" class="btn btn-primary w-100">
                                        Download Produk
                                    </a>
                                <?php else: ?>
                                    <button class="btn btn-outline-ink w-100" disabled>
                                        File belum tersedia
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
