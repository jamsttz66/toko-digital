<?php
/**
 * Product detail (PRD section 16)
 * URL: /produk-detail.php?slug=...
 */
require_once __DIR__ . '/includes/header.php';

$slug = clean(input('slug', ''));

if ($slug === '') {
    http_response_code(404);
    echo '<div class="container-narrow py-5 text-center"><h1>404</h1><p>Produk tidak ditemukan.</p><a href="' . app_url('produk.php') . '" class="btn btn-primary">Kembali ke katalog</a></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$productModel = new Product();
$product = $productModel->findBySlug($slug);

if (!$product) {
    http_response_code(404);
    echo '<div class="container-narrow py-5 text-center"><h1>404</h1><p>Produk tidak ditemukan.</p><a href="' . app_url('produk.php') . '" class="btn btn-primary">Kembali ke katalog</a></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$productFileModel = new ProductFile();
$files = $productFileModel->findByProduct((int) $product['id']);

$isOwned = is_logged_in() && $productModel->isOwnedBy((int) $product['id'], (int) $_SESSION['user']['id']);

$pageTitle = $product['name'];
$pageDescription = $product['short_description'] ?? excerpt($product['description'], 160);
?>

<div class="container-narrow py-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="<?php echo app_url('index.php'); ?>">Beranda</a></li>
            <li class="breadcrumb-item"><a href="<?php echo app_url('produk.php'); ?>">Produk</a></li>
            <?php if (!empty($product['category_name'])): ?>
                <li class="breadcrumb-item"><a href="<?php echo app_url('produk.php?category=' . urlencode(slugify($product['category_name']))); ?>"><?php echo e($product['category_name']); ?></a></li>
            <?php endif; ?>
            <li class="breadcrumb-item active" aria-current="page"><?php echo e($product['name']); ?></li>
        </ol>
    </nav>

    <div class="row g-4 g-lg-5">
        <div class="col-lg-6">
            <div class="card-product overflow-hidden">
                <div class="product-thumb" style="aspect-ratio: 4/3;">
                    <img src="<?php echo thumbnailUrl($product); ?>" alt="<?php echo e($product['name']); ?>">
                </div>
            </div>

            <?php if (!empty($product['description'])): ?>
                <div class="card-product p-4 mt-3">
                    <h2 class="fs-5 mb-3">Tentang produk</h2>
                    <div class="text-muted" style="white-space: pre-line; line-height: 1.8;"><?php echo e($product['description']); ?></div>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-6">
            <div class="card-product p-4">
                <span class="category-tag text-uppercase">
                    <?php echo e($product['category_name'] ?? 'Produk'); ?>
                    &bull; <?php echo e(ucfirst($product['product_type'])); ?>
                </span>

                <h1 class="fs-3 mb-2 mt-2"><?php echo e($product['name']); ?></h1>

                <p class="text-muted mb-3"><?php echo e($product['short_description'] ?? ''); ?></p>

                <div class="d-flex align-items-baseline gap-2 mb-4 pb-3 border-bottom border-line">
                    <span class="price" style="font-size: 1.8rem; font-weight: 700;"><?php echo rupiah($product['price']); ?></span>
                    <?php if (!empty($product['compare_price']) && $product['compare_price'] > $product['price']): ?>
                        <span class="price-compare"><?php echo rupiah($product['compare_price']); ?></span>
                    <?php endif; ?>
                </div>

                <?php if ($isOwned): ?>
                    <div class="alert-custom alert-success mb-3 d-flex align-items-center gap-2">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <span>Anda sudah memiliki produk ini.</span>
                    </div>
                    <a href="<?php echo app_url('library.php'); ?>" class="btn btn-accent btn-lg w-100">Lihat Produk Saya</a>
                <?php else: ?>
                    <div class="d-flex flex-column gap-2">
                        <button class="btn btn-primary btn-lg" data-add-to-cart="<?php echo (int) $product['id']; ?>">
                            Tambah ke Keranjang
                        </button>
                        <a href="<?php echo app_url('cart.php'); ?>" class="btn btn-outline-ink btn-lg">Beli Sekarang</a>
                    </div>
                <?php endif; ?>

                <div class="divider"></div>

                <h2 class="fs-6 mb-2">Detail produk</h2>
                <ul class="list-unstyled small text-muted d-flex flex-column gap-2">
                    <li><strong class="text-ink">Tipe:</strong> <?php echo e(ucfirst($product['product_type'])); ?></li>
                    <li><strong class="text-ink">Versi:</strong> <?php echo e($files[0]['version'] ?? '1.0'); ?></li>
                    <?php if (!empty($files)): ?>
                        <li><strong class="text-ink">Format:</strong> <?php echo e(strtoupper(pathinfo($files[0]['original_name'], PATHINFO_EXTENSION))); ?></li>
                        <li><strong class="text-ink">Ukuran:</strong> <?php echo number_format((float) ($files[0]['file_size'] / 1048576), 1, ',', '.'); ?> MB</li>
                    <?php endif; ?>
                </ul>

                <div class="divider"></div>

                <h2 class="fs-6 mb-2">Kebijakan refund</h2>
                <p class="small text-muted mb-0">
                    Karena produk digital bisa langsung diunduh setelah pembayaran, refund hanya diberikan jika file rusak atau tidak bisa diakses dan tidak ada pengganti yang tersedia.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
    window.appConfig = { apiUrl: <?php echo json_encode(app_url('api/')); ?> };
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
