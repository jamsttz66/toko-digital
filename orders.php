<?php
/**
 * Order History (PRD section 29)
 * URL: /orders.php
 */
require_once __DIR__ . '/includes/header.php';
require_login();

$page = max(1, (int) input('page', 1));
$result = (new Order())->userHistory((int) $_SESSION['user']['id'], $page, 10);
$orders = $result['rows'];

$pageTitle = 'Pesanan';
?>

<div class="container-narrow py-4">
    <div class="page-header">
        <h1>Pesanan</h1>
        <p class="text-muted mb-0">Riwayat semua pesanan Anda.</p>
    </div>

    <?php if (!$orders): ?>
        <div class="empty-state">
            <h3>Belum ada pesanan</h3>
            <p>Pesanan Anda akan muncul di sini setelah checkout.</p>
            <a href="<?php echo app_url('produk.php'); ?>" class="btn btn-primary">Lihat Produk</a>
        </div>
    <?php else: ?>
        <div class="card-product p-3">
            <div class="table-responsive-custom">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Order</th>
                            <th scope="col">Tanggal</th>
                            <th scope="col" class="text-end">Total</th>
                            <th scope="col">Pembayaran</th>
                            <th scope="col">Status</th>
                            <th scope="col">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $o): ?>
                            <tr>
                                <td class="mono small"><?php echo e($o['order_number']); ?></td>
                                <td class="small"><?php echo e(tgl_id($o['created_at'])); ?></td>
                                <td class="text-end"><?php echo rupiah($o['total']); ?></td>
                                <td class="small">QRIS</td>
                                <td>
                                    <?php
                                    $badgeClass = match ($o['status']) {
                                        'paid' => 'paid',
                                        'completed' => 'completed',
                                        'pending' => 'pending',
                                        'expired' => 'expired',
                                        'cancelled' => 'cancelled',
                                        'refunded' => 'revoked',
                                        default => 'pending',
                                    };
                                    $statusLabel = match ($o['status']) {
                                        'pending' => 'Menunggu Pembayaran',
                                        'paid' => 'Pembayaran Berhasil',
                                        'processing' => 'Diproses',
                                        'completed' => 'Selesai',
                                        'cancelled' => 'Dibatalkan',
                                        'expired' => 'Kedaluwarsa',
                                        'refunded' => 'Dikembalikan',
                                        default => $o['status'],
                                    };
                                    ?>
                                    <span class="badge-soft <?php echo e($badgeClass); ?>"><?php echo e($statusLabel); ?></span>
                                </td>
                                <td>
                                    <a href="<?php echo app_url('order-detail.php?id=' . (int) $o['id']); ?>" class="btn btn-outline-ink btn-sm">
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php
        $totalPages = (int) ceil($result['total'] / $result['per_page']);
        if ($totalPages > 1):
        ?>
            <nav class="mt-4" aria-label="Pagination pesanan">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item"><a class="page-link" href="?page=<?php echo $page - 1; ?>">Sebelumnya</a></li>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?>
                        <li class="page-item"><a class="page-link" href="?page=<?php echo $page + 1; ?>">Berikutnya</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
