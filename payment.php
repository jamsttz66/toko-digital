<?php
/**
 * Payment page (PRD section 20, 24)
 * URL: /payment.php?tx=TRANSACTION_ID
 */
require_once __DIR__ . '/includes/header.php';

$tx = clean(input('tx', ''));

$paymentModel = new Payment();
$payment = null;
$order = null;

if ($tx !== '') {
    $payment = $paymentModel->findByTransactionId($tx);
    if ($payment) {
        $order = (new Order())->find((int) $payment['order_id']);
    }
}

if (!$payment || !$order) {
    http_response_code(404);
    echo '<div class="container-narrow py-5 text-center">
        <h1>Pembayaran tidak ditemukan</h1>
        <p class="text-muted">Link pembayaran tidak valid atau sudah kedaluwarsa.</p>
        <a href="' . app_url('produk.php') . '" class="btn btn-primary">Kembali ke Produk</a>
    </div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Guard: hanya pemilik order atau admin
$canView = false;
if (is_logged_in()) {
    if ((int) ($order['user_id'] ?? 0) === (int) $_SESSION['user']['id']) {
        $canView = true;
    } elseif (in_array($_SESSION['user']['role'], ['admin', 'super_admin'], true)) {
        $canView = true;
    }
} elseif ($order['user_id'] === null) {
    $canView = true; // guest checkout
}

if (!$canView) {
    http_response_code(403);
    echo '<div class="container-narrow py-5 text-center">
        <h1>Akses ditolak</h1>
        <p class="text-muted">Anda tidak bisa melihat pembayaran ini.</p>
    </div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$orderItems = (new Order())->items((int) $order['id']);
$isPending = $payment['status'] === 'pending';
$isPaid = in_array($payment['status'], ['paid'], true) || in_array($order['status'], ['paid', 'completed'], true);

// Midtrans QRIS (Core API). QR string Midtrans dirender jadi QR code di payment.php.

$pageTitle = 'Pembayaran ' . $order['order_number'];
?>

<div class="container-narrow py-4">
    <div class="row g-4 justify-content-center">
        <div class="col-lg-7">
            <?php if ($isPaid): ?>
                <!-- STATUS: PAID -->
                <div class="card-product p-5 text-center">
                    <div style="width:64px;height:64px;margin:0 auto 16px;border-radius:50%;background:var(--color-accent-soft);display:flex;align-items:center;justify-content:center">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 6L9 17l-5-5" stroke="#2f5d50" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>
                    <h1 class="fs-3 mb-2">Pembayaran Berhasil</h1>
                    <p class="text-muted">Pembayaran Anda telah diterima. Produk sudah tersedia di akun Anda.</p>

                    <div class="divider"></div>

                    <div class="text-start small mb-4">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Nomor pesanan</span>
                            <strong class="mono"><?php echo e($order['order_number']); ?></strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Total</span>
                            <strong><?php echo rupiah($order['total']); ?></strong>
                        </div>
                    </div>

                    <div class="d-flex flex-column gap-2">
                        <a href="<?php echo app_url('library.php'); ?>" class="btn btn-primary btn-lg">Download Produk</a>
                        <a href="<?php echo app_url('orders.php'); ?>" class="btn btn-outline-ink">Lihat Pesanan</a>
                    </div>
                </div>

            <?php elseif ($payment['status'] === 'failed'): ?>
                <!-- STATUS: FAILED -->
                <div class="card-product p-5 text-center">
                    <div style="width:64px;height:64px;margin:0 auto 16px;border-radius:50%;background:var(--color-danger-soft);display:flex;align-items:center;justify-content:center">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12" stroke="#9b2c2c" stroke-width="2.5" stroke-linecap="round"/></svg>
                    </div>
                    <h1 class="fs-3 mb-2">Pembayaran Tidak Berhasil</h1>
                    <p class="text-muted">Pembayaran gagal atau dibatalkan. Anda bisa mencoba transaksi baru.</p>

                    <div class="divider"></div>

                    <a href="<?php echo app_url('produk.php'); ?>" class="btn btn-primary btn-lg">Beli Lagi</a>
                </div>

            <?php elseif ($payment['status'] === 'expired'): ?>
                <!-- STATUS: EXPIRED -->
                <div class="card-product p-5 text-center">
                    <div style="width:64px;height:64px;margin:0 auto 16px;border-radius:50%;background:var(--color-danger-soft);display:flex;align-items:center;justify-content:center">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="#9b2c2c" stroke-width="2"/><path d="M12 7v5l3 3" stroke="#9b2c2c" stroke-width="2" stroke-linecap="round"/></svg>
                    </div>
                    <h1 class="fs-3 mb-2">Pembayaran Kedaluwarsa</h1>
                    <p class="text-muted">Sesi pembayaran sudah kedaluwarsa. Silakan buat pembayaran baru.</p>

                    <div class="divider"></div>

                    <a href="<?php echo app_url('produk.php'); ?>" class="btn btn-primary btn-lg">Beli Lagi</a>
                </div>

            <?php else: ?>
                <!-- STATUS: PENDING -->
                <?php
                $isMidtrans = strtolower((string) $payment['provider']) === 'midtrans';
                $snapToken = $isMidtrans ? (string) $payment['qr_string'] : '';
                ?>
                <?php if ($isMidtrans && $snapToken !== ''): ?>
                    <div class="card-product p-5 text-center">
                        <h1 class="fs-3 mb-2">Scan QRIS untuk Bayar</h1>
                        <p class="text-muted small mb-4">Pindai kode QRIS di bawah dengan e-wallet atau aplikasi mobile banking Anda (GoPay, OVO, DANA, ShopeePay, Bank apa pun).</p>
                        <div id="qrisBox" class="d-flex justify-content-center mb-3 p-4 rounded-3" style="background:#fff;border:1px solid var(--color-line);box-shadow:var(--shadow-sm)"></div>
                        <p class="text-muted small mb-4" id="qrisTimer"></p>
                        <div class="divider"></div>
                        <a href="<?php echo app_url('payment.php?tx=' . urlencode($payment['transaction_id'])); ?>" class="btn btn-outline-ink btn-sm">
                            Cek status pembayaran
                        </a>
                        <div class="divider"></div>
                        <p class="small text-muted mb-0">
                            Pembayaran dikonfirmasi otomatis setelah Anda scan &amp; bayar. Halaman ini akan memuat ulang sendiri.
                        </p>
                    </div>
                    <script src="<?php echo app_url('assets/js/qrcode.min.js'); ?>"></script>
                    <script>
                        (function () {
                            var box = document.getElementById('qrisBox');
                            var timerEl = document.getElementById('qrisTimer');
                            var qrData = <?php echo json_encode($snapToken); ?>;
                            new QRCode(box, { text: qrData, width: 260, height: 260, correctLevel: QRCode.CorrectLevel.M });
                            var expiredAt = new Date(<?php echo json_encode(date('c', strtotime($payment['expired_at'] ?? 'now'))); ?>).getTime();
                            function tick() {
                                var left = expiredAt - Date.now();
                                if (left <= 0) { timerEl.textContent = 'Waktu pembayaran habis. Silakan checkout ulang.'; clearInterval(iv); return; }
                                var m = Math.floor(left / 60000), s = Math.floor((left % 60000) / 1000);
                                timerEl.textContent = 'Berlaku hingga ' + new Date(expiredAt).toLocaleString('id-ID') + ' (sisa ' + m + ' menit ' + s + ' detik)';
                            }
                            tick();
                            var iv = setInterval(tick, 1000);
                            setTimeout(function () { window.location.reload(); }, 20000);
                        })();
                    </script>
                        <div class="text-start small mb-4">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Nomor pesanan</span>
                                <strong class="mono"><?php echo e($order['order_number']); ?></strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Total</span>
                                <strong class="fs-5"><?php echo rupiah($order['total']); ?></strong>
                            </div>
                        </div>
                        <div class="d-flex flex-column gap-2">
                            <button class="btn btn-outline-ink" onclick="window.location.reload()">
                                Periksa Status Pembayaran
                            </button>
                            <a href="<?php echo app_url('orders.php'); ?>" class="btn btn-outline-ink">Kembali ke Pesanan</a>
                        </div>
                    </div>

                    <script>
                    (function () {
                        var box = document.getElementById('qrisBox');
                        var timerEl = document.getElementById('qrisTimer');
                        var qrData = <?php echo json_encode($snapToken); ?>;
                        if (window.QRCode && box) {
                            new QRCode(box, { text: qrData, width: 260, height: 260, correctLevel: QRCode.CorrectLevel.M });
                        }
                        var expiredAt = new Date(<?php echo json_encode(date('c', strtotime((string) ($payment['expired_at'] ?? 'now')))); ?>).getTime();
                        function tick() {
                            var left = expiredAt - Date.now();
                            if (left <= 0) { if (timerEl) timerEl.textContent = 'Waktu pembayaran habis. Silakan checkout ulang.'; clearInterval(iv); return; }
                            var m = Math.floor(left / 60000), s = Math.floor((left % 60000) / 1000);
                            if (timerEl) timerEl.textContent = 'Berlaku hingga ' + new Date(expiredAt).toLocaleString('id-ID') + ' (sisa ' + m + ' menit ' + s + ' detik)';
                        }
                        tick();
                        var iv = setInterval(tick, 1000);
                        setTimeout(function () { window.location.reload(); }, 20000);
                    })();
                    </script>

                <?php else: ?>
                    <div class="card-product p-5 text-center">
                        <div style="width:64px;height:64px;margin:0 auto 16px;border-radius:50%;background:var(--color-warning-soft);display:flex;align-items:center;justify-content:center">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="#8a6d1a" stroke-width="2"/><path d="M12 7v5l3 3" stroke="#8a6d1a" stroke-width="2" stroke-linecap="round"/></svg>
                        </div>
                        <h1 class="fs-3 mb-2">Menunggu Pembayaran</h1>
                        <p class="text-muted">Silakan selesaikan pembayaran melalui QRIS.</p>

                        <div class="divider"></div>

                        <div class="qris-box">
                            <div class="qris-mock">
                                <div id="qrisPlaceholder" style="width:200px;height:200px;margin:0 auto">
                                    <svg viewBox="0 0 200 200" width="200" height="200" role="img" aria-label="Kode QRIS">
                                        <rect width="200" height="200" fill="#fff"/>
                                        <?php
                                        $cells = 25;
                                        $cell = 200 / $cells;
                                        srand(crc32($payment['provider_transaction_id'] ?? ''));
                                        for ($y = 0; $y < $cells; $y++) {
                                            for ($x = 0; $x < $cells; $x++) {
                                                $isFinder = ($x < 7 && $y < 7) || ($x >= $cells - 7 && $y < 7) || ($x < 7 && $y >= $cells - 7);
                                                if ($isFinder) continue;
                                                if (rand(0, 1) === 1) {
                                                    echo '<rect x="' . round($x * $cell, 2) . '" y="' . round($y * $cell, 2) . '" width="' . round($cell, 2) . '" height="' . round($cell, 2) . '" fill="#1a2332"/>';
                                                }
                                            }
                                        }
                                        foreach ([[0, 0], [$cells - 7, 0], [0, $cells - 7]] as $finder) {
                                            $fx = $finder[0]; $fy = $finder[1];
                                            echo '<rect x="' . round($fx * $cell, 2) . '" y="' . round($fy * $cell, 2) . '" width="' . round(7 * $cell, 2) . '" height="' . round(7 * $cell, 2) . '" fill="#1a2332"/>';
                                            echo '<rect x="' . round(($fx + 1) * $cell, 2) . '" y="' . round(($fy + 1) * $cell, 2) . '" width="' . round(5 * $cell, 2) . '" height="' . round(5 * $cell, 2) . '" fill="#fff"/>';
                                            echo '<rect x="' . round(($fx + 2) * $cell, 2) . '" y="' . round(($fy + 2) * $cell, 2) . '" width="' . round(3 * $cell, 2) . '" height="' . round(3 * $cell, 2) . '" fill="#1a2332"/>';
                                        }
                                        ?>
                                    </svg>
                                </div>
                            </div>
                            <p class="small text-muted mb-3">Pindai kode QRIS di atas dengan aplikasi e-wallet atau mobile banking Anda.</p>
                            <p class="small mb-0">
                                Berlaku sampai: <strong class="mono"><?php echo e(tgl_jam_id($payment['expired_at'])); ?></strong>
                            </p>
                        </div>

                        <div class="divider"></div>

                        <div class="text-start small mb-4">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Nomor pesanan</span>
                                <strong class="mono"><?php echo e($order['order_number']); ?></strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Total</span>
                                <strong class="fs-5"><?php echo rupiah($order['total']); ?></strong>
                            </div>
                        </div>

                        <div class="d-flex flex-column gap-2">
                            <button class="btn btn-outline-ink" onclick="window.location.reload()">
                                Periksa Status Pembayaran
                            </button>
                            <a href="<?php echo app_url('orders.php'); ?>" class="btn btn-outline-ink">Kembali ke Pesanan</a>
                        </div>

                        <!-- Auto-poll status -->
                        <div data-payment-poll="<?php echo e($order['order_number']); ?>"></div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="col-lg-5">
            <div class="card-product p-4">
                <h2 class="fs-6 mb-3">Detail Pesanan</h2>
                <div class="d-flex flex-column gap-2">
                    <?php foreach ($orderItems as $item): ?>
                        <div class="d-flex justify-content-between gap-2">
                            <span class="small"><?php echo e($item['product_name_snapshot']); ?></span>
                            <span class="small fw-medium"><?php echo rupiah($item['price']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="divider"></div>
                <div class="d-flex justify-content-between">
                    <span class="fw-semibold">Total</span>
                    <strong><?php echo rupiah($order['total']); ?></strong>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    window.appConfig = { apiUrl: <?php echo json_encode(app_url('api/')); ?> };
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
