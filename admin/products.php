<?php
/**
 * Admin: daftar produk (PRD section 33)
 */
$pageTitle = 'Produk';
$activeMenu = 'products';
require_once __DIR__ . '/partials/header.php';

$page = max(1, (int) input('page', 1));
$result = (new Product())->paginateAdmin($page, 20);
$products = $result['rows'];

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>

<?php if ($flash): ?>
    <div class="alert-custom alert-success mb-3"><?php echo e($flash); ?></div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <span class="text-muted small"><?php echo number_format($result['total'], 0, ',', '.'); ?> produk</span>
    <a href="<?php echo app_url('admin/product-create.php'); ?>" class="btn btn-primary btn-sm">
        + Tambah Produk
    </a>
</div>

<div class="card-product p-3">
    <div class="table-responsive-custom">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Produk</th>
                    <th>Kategori</th>
                    <th class="text-end">Harga</th>
                    <th>Status</th>
                    <th>File</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                    <tr>
                        <td>
                            <a href="<?php echo app_url('admin/product-edit.php?id=' . (int) $p['id']); ?>"
                               class="text-ink text-decoration-none fw-medium">
                                <?php echo e($p['name']); ?>
                            </a>
                        </td>
                        <td class="small text-muted"><?php echo e($p['category_name'] ?? '-'); ?></td>
                        <td class="text-end"><?php echo rupiah($p['price']); ?></td>
                        <td>
                            <span class="badge-soft <?php echo $p['status'] === 'active' ? 'paid' : 'failed'; ?>">
                                <?php echo $p['status'] === 'active' ? 'Aktif' : 'Nonaktif'; ?>
                            </span>
                            <?php if ((int) $p['featured'] === 1): ?>
                                <span class="badge-soft pending">Unggulan</span>
                            <?php endif; ?>
                        </td>
                        <td class="small text-muted">
                            <?php echo (new ProductFile())->findByProduct((int) $p['id']) ? 'Ada' : 'Belum'; ?>
                        </td>
                        <td class="text-nowrap">
                            <a href="<?php echo app_url('admin/product-edit.php?id=' . (int) $p['id']); ?>"
                               class="btn btn-outline-ink btn-sm">Edit</a>
                            <form method="post" action="<?php echo app_url('admin/product-delete.php'); ?>"
                                  class="d-inline" onsubmit="return confirm('Hapus produk ini?')">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="id" value="<?php echo (int) $p['id']; ?>">
                                <button type="submit" class="btn btn-outline-ink btn-sm text-danger">Hapus</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
