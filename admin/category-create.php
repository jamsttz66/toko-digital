<?php
/**
 * Admin: buat kategori (PRD section 33)
 */
$pageTitle = 'Tambah Kategori';
$activeMenu = 'categories';

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/autoload.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_admin();

$errors = [];

if (is_post()) {
    if (!csrf_verify(input('csrf_token'))) {
        $errors[] = 'Token tidak valid';
    } else {
        $name = clean(input('name', ''));
        $data = [
            'name' => $name,
            'slug' => clean(input('slug', '')) ?: slugify($name),
            'description' => clean(input('description', '')),
            'image' => '',
            'status' => clean(input('status', 'active')),
        ];

        if (mb_strlen($name) < 2) {
            $errors[] = 'Nama kategori minimal 2 karakter';
        }

        // Upload gambar kategori (opsional)
        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'svg'], true)) {
                $errors[] = 'Gambar harus JPG/PNG/WEBP/SVG';
            } else {
                $storedName = 'cat-' . bin2hex(random_bytes(10)) . '.' . $ext;
                $dir = dirname(__DIR__) . '/uploads';
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                if (move_uploaded_file($_FILES['image']['tmp_name'], $dir . '/' . $storedName)) {
                    $data['image'] = $storedName;
                }
            }
        }

        if (!$errors) {
            (new Category())->create($data);
            admin_audit('CATEGORY_CREATED', 'categories', 0, "Kategori dibuat: {$name}");
            $_SESSION['flash'] = 'Kategori berhasil dibuat';
            redirect(app_url('admin/categories.php'));
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
                <label class="form-label" for="name">Nama Kategori</label>
                <input type="text" class="form-control" id="name" name="name" required
                       value="<?php echo e(clean(input('name', ''))); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="slug">Slug <span class="text-muted fw-normal">(opsional)</span></label>
                <input type="text" class="form-control" id="slug" name="slug"
                       value="<?php echo e(clean(input('slug', ''))); ?>" placeholder="otomatis">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="status">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="active">Aktif</option>
                    <option value="inactive">Nonaktif</option>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label" for="description">Deskripsi</label>
                <textarea class="form-control" id="description" name="description" rows="3"><?php echo e(clean(input('description', ''))); ?></textarea>
            </div>
            <div class="col-12">
                <label class="form-label" for="image">Gambar Kategori <span class="text-muted fw-normal">(opsional)</span></label>
                <input type="file" class="form-control" id="image" name="image" accept="image/*">
            </div>
        </div>

        <div class="mt-4 d-flex gap-2">
            <?php echo csrf_field(); ?>
            <button type="submit" class="btn btn-primary">Simpan Kategori</button>
            <a href="<?php echo app_url('admin/categories.php'); ?>" class="btn btn-outline-ink">Batal</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
