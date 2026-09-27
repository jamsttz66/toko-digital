<?php
/**
 * Kontak
 */
require_once __DIR__ . '/includes/header.php';

$errors = [];
$success = false;

if (is_post()) {
    if (!csrf_verify(input('csrf_token'))) {
        $errors[] = 'Token tidak valid. Coba lagi.';
    } else {
        $name = clean(input('name', ''));
        $email = clean(input('email', ''));
        $message = clean(input('message', ''));

        if (mb_strlen($name) < 3) {
            $errors[] = 'Nama minimal 3 karakter';
        }
        if (!valid_email($email)) {
            $errors[] = 'Email tidak valid';
        }
        if (mb_strlen($message) < 10) {
            $errors[] = 'Pesan minimal 10 karakter';
        }

        if (!$errors) {
            log_error('contact', 'Pesan masuk', [
                'name' => $name,
                'email' => $email,
                'message' => mb_substr($message, 0, 500),
            ]);
            $success = true;
        }
    }
}

$pageTitle = 'Kontak';
?>

<div class="container-narrow py-4">
    <div class="page-header">
        <h1>Kontak</h1>
        <p class="text-muted mb-0">Ada kendala atau pertanyaan? Kirim pesan kepada kami.</p>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card-product p-4 p-md-5">
                <?php if ($success): ?>
                    <div class="alert-custom alert-success mb-4">
                        Pesan Anda sudah terkirim. Kami akan membalas via email secepatnya.
                    </div>
                <?php endif; ?>

                <?php if ($errors): ?>
                    <div class="alert-custom alert-danger mb-4">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $err): ?>
                                <li><?php echo e($err); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" novalidate>
                    <div class="mb-3">
                        <label class="form-label" for="name">Nama</label>
                        <input type="text" class="form-control" id="name" name="name" required
                               value="<?php echo e(clean(input('name', ''))); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required
                               value="<?php echo e(clean(input('email', ''))); ?>">
                    </div>

                    <div class="mb-4">
                        <label class="form-label" for="message">Pesan</label>
                        <textarea class="form-control" id="message" name="message" rows="5" required
                                  placeholder="Tuliskan kendala atau pertanyaan Anda..."><?php echo e(clean(input('message', ''))); ?></textarea>
                    </div>

                    <?php echo csrf_field(); ?>

                    <button type="submit" class="btn btn-primary btn-lg w-100">Kirim Pesan</button>
                </form>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card-product p-4 mb-3">
                <h2 class="fs-6 mb-3">Informasi</h2>
                <ul class="list-unstyled small d-flex flex-column gap-3 mb-0">
                    <li class="d-flex gap-3">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" class="flex-shrink-0 mt-1" aria-hidden="true"><path d="M4 4h16v16H4z" stroke="#2f5d50" stroke-width="1.6"/><path d="M4 4l8 6 8-6" stroke="#2f5d50" stroke-width="1.6"/></svg>
                        <span><strong class="text-ink d-block">Email</strong> support@tokodigital.test</span>
                    </li>
                    <li class="d-flex gap-3">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" class="flex-shrink-0 mt-1" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="#2f5d50" stroke-width="1.6"/><path d="M12 7v5l3 3" stroke="#2f5d50" stroke-width="1.6" stroke-linecap="round"/></svg>
                        <span><strong class="text-ink d-block">Jam operasional</strong> Otomatis 24 jam. Support membalas pada jam kerja.</span>
                    </li>
                </ul>
            </div>

            <div class="card-product p-4">
                <h2 class="fs-6 mb-2">Sebelum menghubungi</h2>
                <p class="small text-muted mb-0">
                    Untuk masalah pembayaran atau download, sertakan nomor pesanan Anda (format DS-YYYYMMDD-XXXXXX)
                    agar prosesnya lebih cepat.
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
