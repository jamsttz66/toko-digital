<?php
/**
 * Homepage (PRD section 14, 48)
 */
require_once __DIR__ . '/includes/header.php';

$productModel = new Product();
$categoryModel = new Category();

$featured = $productModel->featured(8);
$categories = $categoryModel->activeWithCount();

$pageTitle = 'Produk digital pilihan, langsung setelah pembayaran';

/**
 * URL thumbnail produk. Jika tidak ada gamung, pakai placeholder SVG.
 */
function thumbnailUrl(array $product): string
{
    if (!empty($product['thumbnail'])) {
        return app_url('uploads/' . ltrim($product['thumbnail'], '/'));
    }
    return app_url('assets/images/placeholder.svg');
}
?>

<section class="hero">
    <div class="container-narrow">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <h1>Produk digital pilihan, langsung setelah pembayaran.</h1>
                <p class="lead">
                    Temukan produk digital yang praktis, aman, dan siap digunakan.
                    Bayar dengan QRIS dan dapatkan akses produk secara otomatis.
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="<?php echo app_url('produk.php'); ?>" class="btn btn-primary btn-lg">Lihat Produk</a>
                    <a href="#cara-kerja" class="btn btn-outline-ink btn-lg">Cara Kerja</a>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="row g-3">
                    <?php if (!empty($featured[0])): ?>
                        <div class="col-8">
                            <a class="card-product text-decoration-none" href="<?php echo app_url('produk-detail.php?slug=' . urlencode($featured[0]['slug'])); ?>">
                                <div class="product-thumb">
                                    <img src="<?php echo thumbnailUrl($featured[0]); ?>" alt="<?php echo e($featured[0]['name']); ?>" loading="lazy">
                                </div>
                                <div class="card-body">
                                    <span class="category-tag"><?php echo e($featured[0]['category_name'] ?? 'Produk'); ?></span>
                                    <h3 class="card-title fs-6"><?php echo e($featured[0]['name']); ?></h3>
                                    <div class="product-meta">
                                        <span class="price"><?php echo rupiah($featured[0]['price']); ?></span>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($featured[1])): ?>
                        <div class="col-4">
                            <a class="card-product text-decoration-none" href="<?php echo app_url('produk-detail.php?slug=' . urlencode($featured[1]['slug'])); ?>">
                                <div class="product-thumb">
                                    <img src="<?php echo thumbnailUrl($featured[1]); ?>" alt="<?php echo e($featured[1]['name']); ?>" loading="lazy">
                                </div>
                                <div class="card-body">
                                    <span class="category-tag"><?php echo e($featured[1]['category_name'] ?? 'Produk'); ?></span>
                                    <h3 class="card-title fs-6"><?php echo e($featured[1]['name']); ?></h3>
                                </div>
                            </a>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($featured[2])): ?>
                        <div class="col-5">
                            <a class="card-product text-decoration-none" href="<?php echo app_url('produk-detail.php?slug=' . urlencode($featured[2]['slug'])); ?>">
                                <div class="product-thumb">
                                    <img src="<?php echo thumbnailUrl($featured[2]); ?>" alt="<?php echo e($featured[2]['name']); ?>" loading="lazy">
                                </div>
                                <div class="card-body">
                                    <span class="category-tag"><?php echo e($featured[2]['category_name'] ?? 'Produk'); ?></span>
                                    <h3 class="card-title fs-6"><?php echo e($featured[2]['name']); ?></h3>
                                </div>
                            </a>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($featured[3])): ?>
                        <div class="col-7">
                            <a class="card-product text-decoration-none" href="<?php echo app_url('produk-detail.php?slug=' . urlencode($featured[3]['slug'])); ?>">
                                <div class="product-thumb">
                                    <img src="<?php echo thumbnailUrl($featured[3]); ?>" alt="<?php echo e($featured[3]['name']); ?>" loading="lazy">
                                </div>
                                <div class="card-body">
                                    <span class="category-tag"><?php echo e($featured[3]['category_name'] ?? 'Produk'); ?></span>
                                    <h3 class="card-title fs-6"><?php echo e($featured[3]['name']); ?></h3>
                                    <div class="product-meta">
                                        <span class="price"><?php echo rupiah($featured[3]['price']); ?></span>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="trust-strip">
    <div class="container-narrow">
        <div class="row row-cols-1 row-cols-md-4 g-3">
            <div class="col"><div class="trust-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <span>Bayar dengan QRIS</span>
            </div></div>
            <div class="col"><div class="trust-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <span>Pengiriman otomatis</span>
            </div></div>
            <div class="col"><div class="trust-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <span>Akses produk 24 jam</span>
            </div></div>
            <div class="col"><div class="trust-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <span>Tanpa menunggu admin</span>
            </div></div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container-narrow">
        <div class="section-header">
            <h2>Produk unggulan</h2>
            <p>Produk pilihan dengan harga yang jelas dan tidak ada biaya tersembunyi.</p>
        </div>

        <div class="row row-cols-2 row-cols-md-4 g-3 g-md-4">
            <?php foreach ($featured as $p): ?>
                <div class="col">
                    <a href="<?php echo app_url('produk-detail.php?slug=' . urlencode($p['slug'])); ?>"
                       class="card-product text-decoration-none">
                        <div class="product-thumb">
                            <img src="<?php echo thumbnailUrl($p); ?>" alt="<?php echo e($p['name']); ?>" loading="lazy">
                        </div>
                        <div class="card-body">
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
                    </a>
                    <?php if (!is_logged_in()): ?>
                        <div class="p-2 border-top border-line">
                            <button class="btn btn-outline-ink btn-sm w-100" data-add-to-cart="<?php echo (int) $p['id']; ?>">
                                Tambah ke Keranjang
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="p-2 border-top border-line d-flex gap-2">
                            <button class="btn btn-outline-ink btn-sm flex-grow-1" data-add-to-cart="<?php echo (int) $p['id']; ?>">
                                Tambah ke Keranjang
                            </button>
                            <a href="<?php echo app_url('produk-detail.php?slug=' . urlencode($p['slug'])); ?>" class="btn btn-primary btn-sm">
                                Detail
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-4">
            <a href="<?php echo app_url('produk.php'); ?>" class="btn btn-outline-ink">Lihat semua produk</a>
        </div>
    </div>
</section>

<section class="section bg-surface border-top border-bottom border-line">
    <div class="container-narrow">
        <div class="section-header">
            <h2>Kategori</h2>
            <p>Jelajahi produk berdasarkan kategori.</p>
        </div>
        <div class="row row-cols-2 row-cols-md-4 g-3">
            <?php foreach ($categories as $c): ?>
                <div class="col">
                    <a href="<?php echo app_url('kategori.php?slug=' . urlencode($c['slug'])); ?>"
                       class="card-product p-3 text-decoration-none d-block">
                        <h3 class="fs-6 mb-1"><?php echo e($c['name']); ?></h3>
                        <span class="text-muted" style="font-size:.85rem"><?php echo (int) $c['product_count']; ?> produk</span>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section" id="cara-kerja">
    <div class="container-narrow">
        <div class="row g-5">
            <div class="col-lg-5">
                <div class="section-header">
                    <h2>Cara kerja</h2>
                    <p>Dari pilih produk sampai download, semua otomatis setelah pembayaran berhasil.</p>
                </div>
            </div>
            <div class="col-lg-7">
                <ol class="step-list">
                    <li><div>
                        <strong>Pilih produk</strong><br>
                        <span class="text-muted">Buka katalog, pilih produk yang sesuai kebutuhan.</span>
                    </div></li>
                    <li><div>
                        <strong>Bayar dengan QRIS</strong><br>
                        <span class="text-muted">Checkout lalu pindai kode QRIS. Selesaikan pembayaran seperti biasa.</span>
                    </div></li>
                    <li><div>
                        <strong>Pembayaran diverifikasi</strong><br>
                        <span class="text-muted">Sistem memverifikasi pembayaran melalui payment gateway.</span>
                    </div></li>
                    <li><div>
                        <strong>Download produk</strong><br>
                        <span class="text-muted">Produk langsung tersedia di akun Anda. Tidak perlu menunggu admin.</span>
                    </div></li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="section bg-surface border-bottom border-line">
    <div class="container-narrow">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <h2>Pembayaran QRIS</h2>
                <p class="text-muted mb-3">QRIS adalah metode pembayaran nasional yang diterima oleh berbagai e-wallet dan aplikasi mobile banking di Indonesia.</p>
                <ul class="list-unstyled small text-muted">
                    <li class="mb-2">Bisa dipakai dengan GoPay, OVO, DANA, ShopeePay, dan mobile banking</li>
                    <li class="mb-2">Tidak ada biaya tambahan untuk pembeli</li>
                    <li class="mb-2">Status pembayaran terverifikasi otomatis</li>
                    <li class="mb-2">Kode QRIS berlaku 15 menit</li>
                </ul>
            </div>
            <div class="col-lg-6">
                <div class="qris-box">
                    <div class="qris-mock">
                        <svg width="180" height="180" viewBox="0 0 180 180" role="img" aria-label="Contoh tampilan kode QRIS">
                            <rect width="180" height="180" fill="#ffffff"/>
                            <?php
                            $cells = 21;
                            $cell = 180 / $cells;
                            srand(42);
                            for ($y = 0; $y < $cells; $y++) {
                                for ($x = 0; $x < $cells; $x++) {
                                    $isFinder = ($x < 7 && $y < 7)
                                        || ($x >= $cells - 7 && $y < 7)
                                        || ($x < 7 && $y >= $cells - 7);
                                    if ($isFinder) {
                                        continue;
                                    }
                                    if (rand(0, 1) === 1) {
                                        echo '<rect x="' . round($x * $cell, 2) . '" y="' . round($y * $cell, 2)
                                            . '" width="' . round($cell, 2) . '" height="' . round($cell, 2)
                                            . '" fill="#1a2332"/>';
                                    }
                                }
                            }
                            foreach ([[0, 0], [$cells - 7, 0], [0, $cells - 7]] as $finder) {
                                $fx = $finder[0];
                                $fy = $finder[1];
                                echo '<rect x="' . round($fx * $cell, 2) . '" y="' . round($fy * $cell, 2)
                                    . '" width="' . round(7 * $cell, 2) . '" height="' . round(7 * $cell, 2)
                                    . '" fill="#1a2332"/>';
                                echo '<rect x="' . round(($fx + 1) * $cell, 2) . '" y="' . round(($fy + 1) * $cell, 2)
                                    . '" width="' . round(5 * $cell,  2) . '" height="' . round(5 * $cell, 2)
                                    . '" fill="#ffffff"/>';
                                echo '<rect x="' . round(($fx + 2) * $cell, 2) . '" y="' . round(($fy + 2) * $cell, 2)
                                    . '" width="' . round(3 * $cell, 2) . '" height="' . round(3 * $cell, 2)
                                    . '" fill="#1a2332"/>';
                            }
                            ?>
                        </svg>
                    </div>
                    <p class="mb-0 small text-muted">Contoh tampilan QRIS. Kode asli diberikan saat checkout.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container-narrow">
        <div class="section-header">
            <h2>Pertanyaan umum</h2>
        </div>
        <div class="row">
            <div class="col-lg-8">
                <div class="accordion" id="faqHome">
                    <div class="accordion-item border-line">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                Berapa lama produk bisa di-download setelah bayar?
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqHome">
                            <div class="accordion-body text-muted">
                                Produk tersedia di akun Anda beberapa saat setelah pembayaran terverifikasi. Biasanya kurang dari satu menit.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item border-line">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                Metode pembayaran apa saja yang didukung?
                            </button>
                        </h2>
                        <div id="faq2"   class="accordion-collapse collapse" data-bs-parent="#faqHome">
                            <div class="accordion-body text-muted">
                                Saat ini QRIS, yang bisa dipakai dengan berbagai e-wallet dan aplikasi mobile banking.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item border-line">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                Apakah link download bisa dipakai berulang?
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqHome">
                            <div class="accordion-body text-muted">
                                Bisa. Produk yang sudah dibeli tetap bisa diakses kembali melalui halaman Produk Saya.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item border-line">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                Apakah pembayaran saya aman?
                            </button>
                        </h2>
                        <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqHome">
                            <div class="accordion-body text-muted">
                                Pembayaran diproses oleh payment gateway resmi. Sistem kami hanya menerima notifikasi pembayaran yang sudah diverifikasi.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section bg-surface border-top border-line">
    <div class="container-narrow text-center">
        <h2>Siap memulai?</h2>
        <p class="text-muted mb-4">Pilih produk, bayar dengan QRIS, dan gunakan langsung.</p>
        <a href="<?php echo app_url('produk.php'); ?>" class="btn btn-primary btn-lg">Lihat Produk</a>
    </div>
</section>

<script>
    window.appConfig = {
        apiUrl: <?php echo json_encode(app_url('api/')); ?>
    };
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
