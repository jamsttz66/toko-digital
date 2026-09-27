<?php
/**
 * Kategori (PRD section 15)
 * URL: /kategori.php?slug=...
 */
require_once __DIR__ . '/includes/header.php';

$categoryModel = new Category();
$productModel = new Product();

$slug = clean(input('slug', ''));
$category = null;

if ($slug !== '') {
    $category = $categoryModel->findBySlug($slug);
}

$categories = $categoryModel->activeWithCount();

$pageTitle = $category ? $category['name'] : 'Kategori';
?>

<div class="page-header">
    <div class="container-narrow">
        <h1><?php echo $category ? e($category['name']) : 'Kategori'; ?></h1>
        <p class="text-muted mb-0">
            <?php if ($category): ?>
                <?php echo e($category['description'] ?? 'Jelajahi produk di kategori ini.'); ?>
            <?php else: ?>
                Jelajahi produk berdasarkan kategori.
            <?php endif; ?>
        </p>
    </div>
</div>

<div class="container-narrow">
    <div class="row row-cols-2 row-cols-md-4 g-3 g-md-4 mb-5">
        <?php foreach ($categories as $c): ?>
            <div class="col">
                <a href="<?php echo app_url('kategori.php?slug=' . urlencode($c['slug'])); ?>"
                   class="card-product p-3 text-decoration-none d-block <?php echo ($category && $category['id'] === $c['id']) ? 'border-primary' : ''; ?>">
                    <h3 class="fs-6 mb-1"><?php echo e($c['name']); ?></h3>
                    <span class="text-muted" style="font-size:.85rem"><?php echo (int) $c['product_count']; ?> produk</span>
                </a>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($category): ?>
        <?php
        $result = $productModel->paginate(['category_slug' => $slug], 1, 24);
        $products = $result['rows'];
        ?>
        <div class="section-header">
            <h2>Produk di kategori ini</h2>
        </div>

        <?php if (!$products): ?>
            <div class="empty-state">
                <h3>Belum ada produk</h3>
                <p>Produk di kategori ini belum tersedia.</p>
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
                                    <span class="price"><?php echo rupiah($p['price']); ?></span>
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
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
    window.appConfig = { apiUrl: <?php echo json_encode(app_url('api/')); ?> };
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
