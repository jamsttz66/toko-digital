<?php
/**
 * Cart (PRD section 17)
 * URL: /cart.php
 */
require_once __DIR__ . '/includes/header.php';

$cartModel = new Cart();
$cart = $cartModel->current();
$items = $cartModel->items((int) $cart['id']);
$subtotal = $cartModel->total((int) $cart['id']);

$pageTitle = 'Keranjang';
?>

<div class="container-narrow py-4">
    <div class="page-header">
        <h1>Keranjang</h1>
    </div>

    <?php if (!$items): ?>
        <div class="empty-state">
            <h3>Keranjang masih kosong</h3>
            <p>Pilih produk digital yang Anda butuhkan.</p>
            <a href="<?php echo app_url('produk.php'); ?>" class="btn btn-primary">Lihat Produk</a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card-product p-3">
                    <div class="table-responsive-custom">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">Produk</th>
                                    <th scope="col" class="text-end">Harga</th>
                                    <th scope="col" class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                    <tr data-cart-row data-price="<?php echo e((float) $item['price_snapshot']); ?>">
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <div style="width:56px;height:56px;flex-shrink:0;border-radius:8px;overflow:hidden;border:1px solid var(--color-line)">
                                                    <img src="<?php echo thumbnailUrl($item); ?>" alt="<?php echo e($item['name']); ?>" style="width:100%;height:100%;object-fit:cover">
                                                </div>
                                                <div>
                                                    <a href="<?php echo app_url('produk-detail.php?slug=' . urlencode($item['slug'])); ?>" class="text-ink text-decoration-none fw-medium">
                                                        <?php echo e($item['name']); ?>
                                                    </a>
                                                    <div class="small text-muted"><?php echo e($item['category_name'] ?? 'Produk'); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-end"><?php echo rupiah($item['price_snapshot']); ?></td>
                                        <td class="text-end">
                                            <button class="btn btn-outline-ink btn-sm"
                                                    data-remove-from-cart="<?php echo (int) $item['product_id']; ?>">
                                                Hapus
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-3">
                    <a href="<?php echo app_url('produk.php'); ?>" class="text-decoration-none">← Lanjut belanja</a>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card-product p-4">
                    <h2 class="fs-5 mb-3">Ringkasan</h2>

                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Subtotal (<?php echo count($items); ?> produk)</span>
                        <strong data-cart-subtotal><?php echo rupiah($subtotal); ?></strong>
                    </div>

                    <div class="d-flex justify-content-between mb-3 pb-3 border-bottom border-line">
                        <span class="text-muted">Diskon</span>
                        <strong data-cart-discount><?php echo rupiah(0); ?></strong>
                    </div>

                    <div class="d-flex justify-content-between mb-4">
                        <span class="fw-semibold">Total</span>
                        <strong class="fs-5" data-cart-total><?php echo rupiah($subtotal); ?></strong>
                    </div>

                    <a href="<?php echo app_url('checkout.php'); ?>" class="btn btn-primary btn-lg w-100" data-cart-checkout>
                        Checkout
                    </a>

                    <p class="small text-muted mt-3 mb-0 text-center">
                        Pembayaran dengan QRIS. Produk dikirim otomatis setelah pembayaran berhasil.
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    window.appConfig = { apiUrl: <?php echo json_encode(app_url('api/')); ?> };
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
