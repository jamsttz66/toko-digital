<?php
/**
 * Admin: daftar kategori (PRD section 33)
 */
$pageTitle = 'Kategori';
$activeMenu = 'categories';
require_once __DIR__ . '/partials/header.php';

$categoryModel = new Category();
$categories = $categoryModel->activeWithCount();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>

<?php if ($flash): ?>
    <div class="alert-custom alert-success mb-3"><?php echo e($flash); ?></div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <span class="text-muted small"><?php echo count($categories); ?> kategori</span>
    <a href="<?php echo app_url('admin/category-create.php'); ?>" class="btn btn-primary btn-sm">
        + Tambah Kategori
    </a>
</div>

<div class="card-product p-3">
    <?php if (!$categories): ?>
        <p class="text-muted small mb-0">Belum ada kategori.</p>
    <?php else: ?>
        <div class="table-responsive-custom">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Kategori</th>
                        <th>Slug</th>
                        <th class="text-end">Produk</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $c): ?>
                        <tr>
                            <td>
                                <a href="<?php echo app_url('admin/category-edit.php?id=' . (int) $c['id']); ?>"
                                   class="text-ink text-decoration-none fw-medium">
                                    <?php echo e($c['name']); ?>
                                </a>
                            </td>
                            <td class="small text-muted mono"><?php echo e($c['slug']); ?></td>
                            <td class="text-end"><?php echo (int) ($c['product_count'] ?? 0); ?></td>
                            <td>
                                <span class="badge-soft <?php echo $c['status'] === 'active' ? 'paid' : 'failed'; ?>">
                                    <?php echo $c['status'] === 'active' ? 'Aktif' : 'Nonaktif'; ?>
                                </span>
                            </td>
                            <td class="text-nowrap">
                                <a href="<?php echo app_url('admin/category-edit.php?id=' . (int) $c['id']); ?>"
                                   class="btn btn-outline-ink btn-sm">Edit</a>
                                <form method="post" action="<?php echo app_url('admin/category-delete.php'); ?>"
                                      class="d-inline" onsubmit="return confirm('Hapus kategori ini? Produk di dalamnya tidak terhapus tapi jadi tanpa kategori.')">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="id" value="<?php echo (int) $c['id']; ?>">
                                    <button type="submit" class="btn btn-outline-ink btn-sm text-danger">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
