<?php
/**
 * Register customer (PRD section 30)
 */
require_once __DIR__ . '/includes/header.php';

if (is_logged_in()) {
    redirect(app_url('index.php'));
}

$errors = [];
$name = '';
$email = '';
$phone = '';

if (is_post()) {
    if (!csrf_verify(input('csrf_token'))) {
        $errors[] = 'Token tidak valid. Coba lagi.';
    } else {
        $name = clean(input('name', ''));
        $email = clean(input('email', ''));
        $phone = clean(input('phone', ''));
        $password = (string) input('password', '');
        $passwordConfirm = (string) input('password_confirm', '');

        if (mb_strlen($name) < 3) {
            $errors[] = 'Nama minimal 3 karakter';
        }
        if (!valid_email($email)) {
            $errors[] = 'Email tidak valid';
        } elseif ((new User())->findByEmail($email)) {
            $errors[] = 'Email sudah terdaftar';
        }
        if (mb_strlen($password) < 8) {
            $errors[] = 'Password minimal 8 karakter';
        } elseif ($password !== $passwordConfirm) {
            $errors[] = 'Konfirmasi password tidak sesuai';
        }
        if ($phone !== '' && !preg_match('/^[0-9+\-\s]{8,20}$/', $phone)) {
            $errors[] = 'Nomor telepon tidak valid';
        }

        if (!$errors) {
            $userModel = new User();
            $userModel->create([
                'name' => $name,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'phone' => $phone,
            ]);

            $user = $userModel->findByEmail($email);
            login_user($user);

            // Merge cart anonymous
            (new Cart())->mergeAnonymousToUser((int) $user['id']);

            redirect(app_url('index.php'));
        }
    }
}

$pageTitle = 'Daftar';
?>

<div class="container-narrow py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <div class="card-product p-4 p-md-5">
                <h1 class="fs-3 mb-1">Daftar</h1>
                <p class="text-muted mb-4">Buat akun untuk menyimpan riwayat pembelian dan akses produk.</p>

                <?php if ($errors): ?>
                    <div class="alert-custom alert-danger mb-3">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $err): ?>
                                <li><?php echo e($err); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" novalidate>
                    <div class="mb-3">
                        <label class="form-label" for="name">Nama lengkap</label>
                        <input type="text" class="form-control" id="name" name="name" required
                               value="<?php echo e($name); ?>" autocomplete="name">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required
                               value="<?php echo e($email); ?>" autocomplete="email">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="phone">Nomor WhatsApp <span class="text-muted fw-normal">(opsional)</span></label>
                        <input type="tel" class="form-control" id="phone" name="phone"
                               value="<?php echo e($phone); ?>" placeholder="08xxxxxxxxxx" autocomplete="tel">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="password">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required
                               minlength="8" autocomplete="new-password">
                        <div class="form-text">Minimal 8 karakter.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label" for="password_confirm">Ulangi password</label>
                        <input type="password" class="form-control" id="password_confirm" name="password_confirm" required
                               autocomplete="new-password">
                    </div>

                    <?php echo csrf_field(); ?>

                    <button type="submit" class="btn btn-primary btn-lg w-100">Daftar</button>
                </form>

                <p class="text-center text-muted small mt-4 mb-0">
                    Sudah punya akun? <a href="<?php echo app_url('login.php'); ?>">Masuk</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
