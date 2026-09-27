<?php
/**
 * Profile customer
 */
require_once __DIR__ . '/includes/header.php';
require_login();

$userModel = new User();
$user = $userModel->find((int) $_SESSION['user']['id']);

$errors = [];
$success = false;

if (is_post()) {
    if (!csrf_verify(input('csrf_token'))) {
        $errors[] = 'Token tidak valid. Coba lagi.';
    } else {
        $name = clean(input('name', ''));
        $phone = clean(input('phone', ''));
        $currentPassword = (string) input('current_password', '');
        $newPassword = (string) input('new_password', '');
        $newPasswordConfirm = (string) input('new_password_confirm', '');

        if (mb_strlen($name) < 3) {
            $errors[] = 'Nama minimal 3 karakter';
        }
        if ($phone !== '' && !preg_match('/^[0-9+\-\s]{8,20}$/', $phone)) {
            $errors[] = 'Nomor telepon tidak valid';
        }

        // Ganti password hanya jika diisi
        if ($newPassword !== '') {
            if (!password_verify($currentPassword, $user['password'])) {
                $errors[] = 'Password saat ini salah';
            } elseif (mb_strlen($newPassword) < 8) {
                $errors[] = 'Password baru minimal 8 karakter';
            } elseif ($newPassword !== $newPasswordConfirm) {
                $errors[] = 'Konfirmasi password baru tidak sesuai';
            }
        }

        if (!$errors) {
            $userModel->updateProfile((int) $user['id'], ['name' => $name, 'phone' => $phone]);

            if ($newPassword !== '') {
                $userModel->updatePassword((int) $user['id'], password_hash($newPassword, PASSWORD_DEFAULT));
            }

            // Update session
            $_SESSION['user']['name'] = $name;

            $success = true;
            $user = $userModel->find((int) $user['id']);
        }
    }
}

$pageTitle = 'Profile';
?>

<div class="container-narrow py-4">
    <div class="page-header">
        <h1>Profile</h1>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <?php if ($success): ?>
                <div class="alert-custom alert-success mb-3">
                    Profile berhasil diperbarui.
                </div>
            <?php endif; ?>

            <?php if ($errors): ?>
                <div class="alert-custom alert-danger mb-3">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $err): ?>
                            <li><?php echo e($err); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="card-product p-4 p-md-5">
                <form method="post" novalidate>
                    <div class="mb-3">
                        <label class="form-label" for="name">Nama lengkap</label>
                        <input type="text" class="form-control" id="name" name="name" required
                               value="<?php echo e($user['name']); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" class="form-control" id="email" value="<?php echo e($user['email']); ?>" disabled>
                        <div class="form-text">Email tidak bisa diubah.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label" for="phone">Nomor WhatsApp</label>
                        <input type="tel" class="form-control" id="phone" name="phone"
                               value="<?php echo e($user['phone'] ?? ''); ?>" placeholder="08xxxxxxxxxx">
                    </div>

                    <div class="divider"></div>

                    <h2 class="fs-6 mb-3">Ganti password</h2>
                    <p class="small text-muted">Kosongkan jika tidak ingin mengubah password.</p>

                    <div class="mb-3">
                        <label class="form-label" for="current_password">Password saat ini</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" autocomplete="current-password">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="new_password">Password baru</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" autocomplete="new-password">
                    </div>

                    <div class="mb-4">
                        <label class="form-label" for="new_password_confirm">Ulangi password baru</label>
                        <input type="password" class="form-control" id="new_password_confirm" name="new_password_confirm" autocomplete="new-password">
                    </div>

                    <?php echo csrf_field(); ?>

                    <button type="submit" class="btn btn-primary btn-lg w-100">Simpan Perubahan</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
