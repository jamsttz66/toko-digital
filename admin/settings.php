<?php
/**
 * Admin: pengaturan toko (PRD section 41)
 */
$pageTitle = 'Pengaturan';
$activeMenu = 'settings';

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/autoload.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_super_admin();

$errors = [];
$saved = false;

if (is_post()) {
    if (!csrf_verify(input('csrf_token'))) {
        $errors[] = 'Token tidak valid';
    } else {
        $envPath = dirname(__DIR__) . '/.env';
        $envContents = is_file($envPath) ? file_get_contents($envPath) : '';

        $updates = [
            'PAYMENT_PROVIDER'    => clean(input('PAYMENT_PROVIDER', 'dummy')),
            'PAYMENT_ENVIRONMENT' => clean(input('PAYMENT_ENVIRONMENT', 'sandbox')),
            'PAYMENT_API_KEY'     => clean(input('PAYMENT_API_KEY', '')),
            'PAYMENT_MERCHANT_ID' => clean(input('PAYMENT_MERCHANT_ID', '')),
            'MAIL_HOST'           => clean(input('MAIL_HOST', '')),
            'MAIL_USERNAME'       => clean(input('MAIL_USERNAME', '')),
            'MAIL_PASSWORD'       => (string) input('MAIL_PASSWORD', ''),
            'MAIL_FROM_NAME'      => clean(input('MAIL_FROM_NAME', 'Toko Digital')),
            'APP_URL'             => clean(input('APP_URL', '')),
        ];

        // Tulis ulang .env baris demi baris: update key yang ada, append yang baru
        $lines = array_filter(
            array_map('trim', explode("\n", (string) $envContents)),
            fn (string $line) => $line !== '' && !str_starts_with($line, '#')
        );

        $written = [];
        $output = [];
        foreach ($lines as $line) {
            [$key] = explode('=', $line, 2);
            $key = trim($key);
            if (array_key_exists($key, $updates)) {
                $output[] = "{$key}={$updates[$key]}";
                $written[$key] = true;
            } else {
                $output[] = $line;
            }
        }
        foreach ($updates as $key => $value) {
            if (!isset($written[$key])) {
                $output[] = "{$key}={$value}";
            }
        }

        if (file_put_contents($envPath, implode("\n", $output) . "\n") !== false) {
            $saved = true;
            admin_audit('SETTINGS_UPDATED', 'settings', null, 'Pengaturan toko diperbarui');
        } else {
            $errors[] = 'Gagal menyimpan file .env';
        }
    }
}

// Ambil setting saat ini dari .env
$envPath = dirname(__DIR__) . '/.env';
$settings = [
    'APP_URL' => '',
    'PAYMENT_PROVIDER' => 'dummy',
    'PAYMENT_ENVIRONMENT' => 'sandbox',
    'PAYMENT_API_KEY' => '',
    'PAYMENT_MERCHANT_ID' => '',
    'MAIL_HOST' => '',
    'MAIL_USERNAME' => '',
    'MAIL_PASSWORD' => '',
    'MAIL_FROM_NAME' => 'Toko Digital',
];

if (is_file($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        if (array_key_exists($key, $settings)) {
            $settings[$key] = trim($value, "\"'");
        }
    }
}

require_once __DIR__ . '/partials/header.php';
?>

<?php if ($saved): ?>
    <div class="alert-custom alert-success mb-3">Pengaturan berhasil disimpan.</div>
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

<div class="card-product p-4">
    <form method="post" novalidate>
        <h2 class="fs-5 mb-3">Pembayaran (QRIS)</h2>
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <label class="form-label" for="PAYMENT_PROVIDER">Provider</label>
                <select class="form-select" id="PAYMENT_PROVIDER" name="PAYMENT_PROVIDER">
                    <option value="dummy" <?php echo $settings['PAYMENT_PROVIDER'] === 'dummy' ? 'selected' : ''; ?>>Dummy (Sandbox Lokal)</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="PAYMENT_ENVIRONMENT">Environment</label>
                <select class="form-select" id="PAYMENT_ENVIRONMENT" name="PAYMENT_ENVIRONMENT">
                    <option value="sandbox" <?php echo $settings['PAYMENT_ENVIRONMENT'] === 'sandbox' ? 'selected' : ''; ?>>Sandbox</option>
                    <option value="production" <?php echo $settings['PAYMENT_ENVIRONMENT'] === 'production' ? 'selected' : ''; ?>>Production</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="APP_URL">URL Toko</label>
                <input type="text" class="form-control" id="APP_URL" name="APP_URL"
                       value="<?php echo e($settings['APP_URL']); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="PAYMENT_API_KEY">API Key</label>
                <input type="password" class="form-control" id="PAYMENT_API_KEY" name="PAYMENT_API_KEY"
                       value="<?php echo e($settings['PAYMENT_API_KEY']); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="PAYMENT_MERCHANT_ID">Merchant ID</label>
                <input type="text" class="form-control" id="PAYMENT_MERCHANT_ID" name="PAYMENT_MERCHANT_ID"
                       value="<?php echo e($settings['PAYMENT_MERCHANT_ID']); ?>">
            </div>
        </div>

        <h2 class="fs-5 mb-3">Email (SMTP)</h2>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="MAIL_HOST">SMTP Host</label>
                <input type="text" class="form-control" id="MAIL_HOST" name="MAIL_HOST"
                       value="<?php echo e($settings['MAIL_HOST']); ?>" placeholder="smtp.gmail.com">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="MAIL_USERNAME">SMTP Username</label>
                <input type="text" class="form-control" id="MAIL_USERNAME" name="MAIL_USERNAME"
                       value="<?php echo e($settings['MAIL_USERNAME']); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="MAIL_PASSWORD">SMTP Password</label>
                <input type="password" class="form-control" id="MAIL_PASSWORD" name="MAIL_PASSWORD"
                       value="<?php echo e($settings['MAIL_PASSWORD']); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="MAIL_FROM_NAME">Nama Pengirim</label>
                <input type="text" class="form-control" id="MAIL_FROM_NAME" name="MAIL_FROM_NAME"
                       value="<?php echo e($settings['MAIL_FROM_NAME']); ?>">
            </div>
        </div>

        <div class="mt-4">
            <?php echo csrf_field(); ?>
            <button type="submit" class="btn btn-primary">Simpan Pengaturan</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
