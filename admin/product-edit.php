<?php
/**
 * Admin: edit produk (PRD section 33, 34)
 */
$pageTitle = 'Edit Produk';
$activeMenu = 'products';

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/autoload.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_admin();

$productId = (int) input('id', 0);
$productModel = new Product();
$product = $productId > 0 ? $productModel->findById($productId) : null;

if (!$product) {
    redirect(app_url('admin/products.php'));
}

$productFileModel = new ProductFile();
$files = $productFileModel->findByProduct($productId);
$categories = (new Category())->active();

$errors = [];

if (is_post()) {
    if (!csrf_verify(input('csrf_token'))) {
        $errors[] = 'Token tidak valid';
    } else {
        $name = clean(input('name', ''));
        $data = [
            'category_id' => (int) input('category_id', 0) ?: null,
            'name' => $name,
            'slug' => clean(input('slug', '')) ?: slugify($name),
            'short_description' => clean(input('short_description', '')),
            'description' => input('description', ''),
            'price' => (float) input('price', 0),
            'compare_price' => input('compare_price', '') !== '' ? (float) input('compare_price') : null,
            'product_type' => clean(input('product_type', 'other')),
            'status' => clean(input('status', 'active')),
            'featured' => (int) input('featured', 0),
        ];

        if (mb_strlen($name) < 3) {
            $errors[] = 'Nama produk minimal 3 karakter';
        }
        if ($data['price'] <= 0) {
            $errors[] = 'Harga harus lebih dari 0';
        }

        // Thumbnail baru?
        $data['thumbnail'] = $product['thumbnail'];
        if (!empty($_FILES['thumbnail']['name']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'svg'], true)) {
                $storedName = bin2hex(random_bytes(10)) . '.' . $ext;
                $dir = dirname(__DIR__) . '/uploads';
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $dir . '/' . $storedName)) {
                    if (!empty($product['thumbnail']) && is_file($dir . '/' . $product['thumbnail'])) {
                        unlink($dir . '/' . $product['thumbnail']);
                    }
                    $data['thumbnail'] = $storedName;
                }
            }
        }

        if (!$errors) {
            $productModel->update($productId, $data);

            // File digital baru?
            if (!empty($_FILES['digital_file']['name']) && $_FILES['digital_file']['error'] === UPLOAD_ERR_OK) {
                try {
                    $fileData = $productFileModel->handleUpload($productId, $_FILES['digital_file']);
                    $productFileModel->create([
                        'product_id' => $productId,
                        'original_name' => $fileData['original_name'],
                        'stored_name' => $fileData['stored_name'],
                        'file_path' => $fileData['file_path'],
                        'file_size' => $fileData['file_size'],
                        'mime_type' => $fileData['mime_type'],
                    ]);
                } catch (Throwable $e) {
                    $errors[] = 'File gagal diunggah: ' . $e->getMessage();
                }
            }

            if (!$errors) {
                admin_audit('PRODUCT_UPDATED', 'products', $productId, "Produk diupdate: {$name}");
                $_SESSION['flash'] = 'Produk berhasil diperbarui';
                redirect(app_url('admin/products.php'));
            }
        }
    }
}

require_once __DIR__ . '/partials/header.php';
?>

<?php if ($errors): ?>
    <div class="alert-custom alert-danger mb-3">
        <ul class="mb-0 ps-3">
            <?php foreach ($errors as $err): ?>
                <li><?php echo e($err); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card-product p-4">
    <form method="post" enctype="multipart/form-data" novalidate>
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label" for="name">Nama Produk</label>
                <input type="text" class="form-control" id="name" name="name" required
                       value="<?php echo e($product['name']); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="slug">Slug</label>
                <input type="text" class="form-control" id="slug" name="slug" value="<?php echo e($product['slug']); ?>">
            </div>

            <div class="col-md-4">
                <label class="form-label" for="category_id">Kategori</label>
                <select class="form-select" id="category_id" name="category_id">
                    <option value="">— pilih —</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?php echo (int) $c['id']; ?>"
                            <?php echo (int) $product['category_id'] === (int) $c['id'] ? 'selected' : ''; ?>>
                            <?php echo e($c['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label" for="product_type">Tipe Produk</label>
                <select class="form-select" id="product_type" name="product_type">
                    <?php foreach (['ebook', 'template', 'asset', 'software', 'document', 'other'] as $t): ?>
                        <option value="<?php echo e($t); ?>"
                            <?php echo $product['product_type'] === $t ? 'selected' : ''; ?>>
                            <?php echo e(ucfirst($t)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label" for="status">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="active" <?php echo $product['status'] === 'active' ? 'selected' : ''; ?>>Aktif</option>
                    <option value="inactive" <?php echo $product['status'] === 'inactive' ? 'selected' : ''; ?>>Nonaktif</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label" for="price">Harga (Rp)</label>
                <input type="number" class="form-control" id="price" name="price" required min="0"
                       value="<?php echo e($product['price']); ?>">
            </div>

            <div class="col-md-4">
                <label class="form-label" for="compare_price">Harga Coret</label>
                <input type="number" class="form-control" id="compare_price" name="compare_price" min="0"
                       value="<?php echo e($product['compare_price']); ?>">
            </div>

            <div class="col-md-4 d-flex align-items-end">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="featured" name="featured" value="1"
                        <?php echo (int) $product['featured'] === 1 ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="featured">Produk Unggulan</label>
                </div>
            </div>

            <div class="col-12">
                <label class="form-label" for="short_description">Deskripsi Singkat</label>
                <input type="text" class="form-control" id="short_description" name="short_description"
                       value="<?php echo e($product['short_description']); ?>">
            </div>

            <div class="col-12">
                <label class="form-label" for="description">Deskripsi Lengkap</label>
                <textarea class="form-control" id="description" name="description" rows="6"><?php echo e($product['description']); ?></textarea>
            </div>

            <div class="col-md-6">
                <label class="form-label" for="thumbnail">Thumbnail Baru <span class="text-muted fw-normal">(opsional)</span></label>
                <input type="file" class="form-control" id="thumbnail" name="thumbnail" accept="image/*">
                <?php if (!empty($product['thumbnail'])): ?>
                    <div class="form-text">Thumbnail saat ini: <?php echo e($product['thumbnail']); ?></div>
                <?php endif; ?>
            </div>

            <div class="col-md-6">
                <label class="form-label" for="digital_file">File Digital Baru <span class="text-muted fw-normal">(opsional)</span></label>
                <input type="file" class="form-control" id="digital_file" name="digital_file">
                <?php if ($files): ?>
                    <div class="form-text">File: <?php echo e($files[0]['original_name']); ?> (<?php echo e($files[0]['stored_name']); ?>)</div>
                <?php else: ?>
                    <div class="form-text text-danger">Belum ada file digital.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="mt-4 d-flex gap-2">
            <?php echo csrf_field(); ?>
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            <a href="<?php echo app_url('admin/products.php'); ?>" class="btn btn-outline-ink">Batal</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
