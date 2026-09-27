<?php
/**
 * Login customer (PRD section 30)
 */
require_once __DIR__ . '/includes/header.php';

if (is_logged_in()) {
    redirect(app_url('index.php'));
}

$errors = [];

if (is_post()) {
    if (!csrf_verify(input('csrf_token'))) {
        $errors[] = 'Token tidak valid. Coba lagi.';
    } else {
        $email = clean(input('email', ''));
        $password = (string) input('password', '');

        if (!valid_email($email)) {
            $errors[] = 'Email tidak valid';
        } elseif ($password === '') {
            $errors[] = 'Password diperlukan';
        } else {
            $user = (new User())->findByEmail($email);

            if (!$user || !password_verify($password, $user['password'])) {
                $errors[] = 'Email atau password salah';
                log_error('auth', 'Login gagal', ['email' => $email]);
            } elseif ($user['status'] !== 'active') {
                $errors[] = 'Akun Anda diblokir. Hubungi support.';
            } else {
                login_user($user);

                // Merge cart anonymous ke akun user
                (new Cart())->mergeAnonymousToUser((int) $user['id']);

                $redirect = $_SESSION['redirect_after_login'] ?? app_url('index.php');
                unset($_SESSION['redirect_after_login']);
                redirect($redirect);
            }
        }
    }
}

$pageTitle = 'Masuk';
?>

<div class="container-narrow py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <div class="card-product p-4 p-md-5">
                <h1 class="fs-3 mb-1">Masuk</h1>
                <p class="text-muted mb-4">Masuk untuk mengakses produk yang sudah dibeli.</p>

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
                               value="<?php echo e(clean(input('email', ''))); ?>" autocomplete="email">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="password">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required
                               autocomplete="current-password">
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label small" for="remember">Ingat saya</label>
                        </div>
                        <a href="<?php echo app_url('forgot-password.php'); ?>" class="small">Lupa password?</a>
                    </div>

                    <?php echo csrf_field(); ?>

                    <button type="submit" class="btn btn-primary btn-lg w-100">Masuk</button>
                </form>

                <p class="text-center text-muted small mt-4 mb-0">
                    Belum punya akun? <a href="<?php echo app_url('register.php'); ?>">Daftar</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
