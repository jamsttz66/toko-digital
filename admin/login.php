<?php
/**
 * Admin login
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/autoload.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin-auth.php';

if (is_admin()) {
    redirect(app_url('admin/dashboard.php'));
}

$errors = [];

if (is_post()) {
    if (!csrf_verify(input('csrf_token'))) {
        $errors[] = 'Token tidak valid. Coba lagi.';
    } else {
        $email = clean(input('email', ''));
        $password = (string) input('password', '');

        if (!valid_email($email) || $password === '') {
            $errors[] = 'Email atau password salah';
        } else {
            $user = (new User())->findByEmail($email);

            if (!$user || !password_verify($password, $user['password'])) {
                $errors[] = 'Email atau password salah';
                log_error('admin_auth', 'Login admin gagal', ['email' => $email]);
            } elseif (!in_array($user['role'], ['admin', 'super_admin'], true)) {
                $errors[] = 'Anda tidak memiliki akses admin';
            } elseif ($user['status'] !== 'active') {
                $errors[] = 'Akun diblokir';
            } else {
                login_user($user);
                audit_log('ADMIN_LOGIN', 'users', (int) $user['id'], 'Login admin berhasil');
                redirect(app_url('admin/dashboard.php'));
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — Toko Digital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo asset('css/app-dark.css'); ?>" rel="stylesheet">
</head>
<body style="background: var(--color-bg)">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card-product p-4 p-md-5">
                <h1 class="fs-3 mb-1">Admin Panel</h1>
                <p class="text-muted mb-4">Masuk untuk mengelola toko.</p>

                <?php if ($errors): ?>
                    <div class="alert-custom alert-danger mb-3">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $err): ?>
                                <li><?php echo e($err); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <div class="mb-3">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required
                               value="<?php echo e(clean(input('email', ''))); ?>">
                    </div>
                    <div class="mb-4">
                        <label class="form-label" for="password">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="btn btn-primary btn-lg w-100">Masuk</button>
                </form>

                <p class="text-center small text-muted mt-4 mb-0">
                    <a href="<?php echo app_url('index.php'); ?>">← Kembali ke toko</a>
                </p>
            </div>
        </div>
    </div>
</div>
</body>
</html>
