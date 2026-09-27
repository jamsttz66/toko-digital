<?php
/**
 * Katalog produk (PRD section 15)
 * URL: /produk.php
 */
require_once __DIR__ . '/includes/header.php';

$productModel = new Product();
$categoryModel = new Category();

$q = clean(input('q', ''));
$catSlug = clean(input('category', ''));
$sort = clean(input('sort', 'newest'));
$page = max(1, (int) input('page', 1));

$filter = [
    'q' => $q !== '' ? $q : null,
    'category_slug' => $catSlug !== '' ? $catSlug : null,
    'sort' => $sort,
];

$result = $productModel->paginate($filter, $page, 12);
$products = $result['rows'];
$categories = $categoryModel->active();

$pageTitle = 'Katalog Produk';
if ($q !== '') {
    $pageTitle = 'Cari: ' . $q;
}
?>

<div class="page-header">
    <div class="container-narrow">
        <h1><?php echo e($pageTitle); ?></h1>
        <p class="text-muted mb-0"><?php echo number_format($result['total'], 0, ',', '.'); ?> produk tersedia</p>
    </div>
</div>

<div class="container-narrow">
    <div class="row g-4">
        <!-- Sidebar filter -->
        <div class="col-lg-3">
            <div class="card-product p-3 mb-3">
                <h2 class="fs-6 mb-3">Filter</h2>

                <form method="get" action="<?php echo app_url('produk.php'); ?>" id="filterForm">
                    <div class="mb-3">
                        <label class="form-label" for="searchInput">Cari</label>
                        <input type="search" class="form-control" id="searchInput" name="q"
                               value="<?php echo e($q); ?>" placeholder="Nama produk...">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="categorySelect">Kategori</label>
                        <select class="form-select" id="categorySelect" name="category">
                            <option value="">Semua kategori</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?php echo e($c['slug']); ?>"
                                    <?php echo $catSlug === $c['slug'] ? 'selected' : ''; ?>>
                                    <?php echo e($c['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Terapkan</button>
                    <?php if ($q !== '' || $catSlug !== ''): ?>
                        <a href="<?php echo app_url('produk.php'); ?>" class="btn btn-outline-ink w-100 mt-2">Reset</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Product grid -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div class="text-muted small">
                    Halaman <?php echo $result['page']; ?> dari <?php echo max(1, $result['total_pages']); ?>
                </div>
                <div>
                    <label class="form-label d-inline me-2 small" for="sortSelect">Urutkan</label>
                    <select class="form-select d-inline w-auto" id="sortSelect" name="sort" onchange="applySort(this.value)">
                        <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Terbaru</option>
                        <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Harga Termurah</option>
                        <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Harga Tertinggi</option>
                        <option value="popular" <?php echo $sort === 'popular' ? 'selected' : ''; ?>>Populer</option>
                    </select>
                </div>
            </div>

            <?php if (!$products): ?>
                <div class="empty-state">
                    <h3>Produk tidak ditemukan</h3>
                    <p>Coba kata kunci lain atau reset filter.</p>
                    <a href="<?php echo app_url('produk.php'); ?>" class="btn btn-outline-ink">Reset Filter</a>
                </div>
            <?php else: ?>
                <div class="row row-cols-2 row-cols-md-3 g-3 g-md-4">
                    <?php foreach ($products as $p): ?>
                        <div class="col">
                            <a href="<?php echo app_url('produk-detail.php?slug=' . urlencode($p['slug'])); ?>"
                               class="card-product text-decoration-none h-100 d-flex flex-column">
                                <div class="product-thumb">
                                    <img src="<?php echo thumbnailUrl($p); ?>" alt="<?php echo e($p['name']); ?>" loading="lazy">
                                </div>
                                <div class="card-body d-flex flex-column flex-grow-1">
                                    <span class="category-tag"><?php echo e($p['category_name'] ?? 'Produk'); ?></span>
                                    <h3 class="card-title"><?php echo e($p['name']); ?></h3>
                                    <p class="card-text"><?php echo excerpt($p['short_description'], 80); ?></p>
                                    <div class="product-meta">
                                        <div>
                                            <span class="price"><?php echo rupiah($p['price']); ?></span>
                                            <?php if (!empty($p['compare_price']) && $p['compare_price'] > $p['price']): ?>
                                                <span class="price-compare ms-1"><?php echo rupiah($p['compare_price']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-2 border-top border-line mt-auto">
                                    <button class="btn btn-outline-ink btn-sm w-100" data-add-to-cart="<?php echo (int) $p['id']; ?>">
                                        Tambah ke Keranjang
                                    </button>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($result['total_pages'] > 1): ?>
                    <nav class="mt-4" aria-label="Pagination produk">
                        <ul class="pagination justify-content-center">
                            <?php if ($result['page'] > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $result['page'] - 1])); ?>">Sebelumnya</a>
                                </li>
                            <?php endif; ?>

                            <?php
                            $start = max(1, $result['page'] - 2);
                            $end = min($result['total_pages'], $result['page'] + 2);
                            for ($i = $start; $i <= $end; $i++):
                            ?>
                                <li class="page-item <?php echo $i === $result['page'] ? 'active' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($result['page'] < $result['total_pages']): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $result['page'] + 1])); ?>">Berikutnya</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    window.appConfig = { apiUrl: <?php echo json_encode(app_url('api/')); ?> };

    function applySort(value) {
        var params = new URLSearchParams(window.location.search);
        params.set('sort', value);
        window.location.search = params.toString();
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
