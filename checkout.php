<?php
/**
 * Checkout (PRD section 18)
 * URL: /checkout.php
 */
require_once __DIR__ . '/includes/header.php';

$cartModel = new Cart();
$cart = $cartModel->current();
$items = $cartModel->items((int) $cart['id']);
$subtotal = $cartModel->total((int) $cart['id']);

if (!$items) {
    redirect(app_url('cart.php'));
}

$user = current_user();

$pageTitle = 'Checkout';
?>

<div class="container-narrow py-4">
    <div class="page-header">
        <h1>Checkout</h1>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <form id="checkoutForm" class="card-product p-4">
                <h2 class="fs-5 mb-3">Informasi Customer</h2>

                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label" for="name">Nama lengkap</label>
                        <input type="text" class="form-control" id="name" name="name" required
                               value="<?php echo e($user['name'] ?? ''); ?>" placeholder="Nama Anda">
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required
                               value="<?php echo e($user['email'] ?? ''); ?>" placeholder="email@contoh.com">
                        <div class="form-text">Link download produk dikirim ke email ini.</div>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="phone">Nomor WhatsApp</label>
                        <input type="tel" class="form-control" id="phone" name="phone"
                               value="<?php echo e($user['phone'] ?? ''); ?>" placeholder="08xxxxxxxxxx">
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="voucher_code">Kode voucher <span class="text-muted fw-normal">(opsional)</span></label>
                        <input type="text" class="form-control" id="voucher_code" name="voucher_code"
                               placeholder="MASUKKAN KODE" style="text-transform: uppercase">
                    </div>
                </div>

                <div class="divider"></div>

                <h2 class="fs-5 mb-3">Pembayaran</h2>

                <div class="d-flex align-items-center gap-3 p-3 border border-line rounded" style="border-radius: 8px">
                    <div style="width:44px;height:44px;flex-shrink:0">
                        <svg viewBox="0 0 44 44" fill="none" aria-hidden="true">
                            <rect x="2" y="2" width="40" height="40" rx="6" fill="#e8f0ec"/>
                            <rect x="10" y="10" width="10" height="10" rx="2" fill="#2f5d50"/>
                            <rect x="24" y="10" width="10" height="10" rx="2" fill="#2f5d50" opacity=".5"/>
                            <rect x="10" y="24" width="10" height="10" rx="2" fill="#2f5d50" opacity=".5"/>
                            <rect x="24" y="24" width="10" height="10" rx="2" fill="#2f5d50"/>
                        </svg>
                    </div>
                    <div>
                        <strong class="d-block">QRIS</strong>
                        <span class="small text-muted">Bayar dengan scan kode QR. Bisa pakai e-wallet atau mobile banking apa saja.</span>
                    </div>
                </div>

                <?php echo csrf_field(); ?>

                <button type="submit" class="btn btn-primary btn-lg w-100 mt-4" id="checkoutBtn">
                    Lanjut ke Pembayaran
                </button>

                <p class="small text-muted mt-3 mb-0">
                    Dengan melanjutkan, Anda menyetujui kebijakan pembelian produk digital.
                </p>
            </form>
        </div>

        <div class="col-lg-5">
            <div class="card-product p-4">
                <h2 class="fs-5 mb-3">Order Summary</h2>

                <div class="d-flex flex-column gap-3 mb-3">
                    <?php foreach ($items as $item): ?>
                        <div class="d-flex justify-content-between gap-2">
                            <span class="small"><?php echo e($item['name']); ?></span>
                            <span class="small fw-medium"><?php echo rupiah($item['price_snapshot']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="d-flex justify-content-between mb-2 pt-3 border-top border-line">
                    <span class="text-muted small">Subtotal</span>
                    <strong><?php echo rupiah($subtotal); ?></strong>
                </div>

                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">Diskon</span>
                    <strong id="discountDisplay"><?php echo rupiah(0); ?></strong>
                </div>

                <div class="d-flex justify-content-between mb-4 pt-3 border-top border-line">
                    <span class="fw-semibold">Total</span>
                    <strong class="fs-5" id="totalDisplay"><?php echo rupiah($subtotal); ?></strong>
                </div>

                <div class="alert-custom p-3">
                    <p class="small text-muted mb-0">
                        <strong class="text-ink">Pengiriman otomatis.</strong> Produk tersedia di akun Anda setelah pembayaran berhasil. Tidak perlu menunggu admin.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    window.appConfig = { apiUrl: <?php echo json_encode(app_url('api/')); ?> };

    document.getElementById('checkoutForm').addEventListener('submit', function (e) {
        e.preventDefault();

        var btn = document.getElementById('checkoutBtn');
        btn.disabled = true;
        btn.textContent = 'Memproses...';

        fetch(window.appConfig.apiUrl + 'checkout.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(new FormData(this))
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.success) {
                    window.location.href = data.data.redirect;
                } else {
                    window.showToast(data.message || 'Gagal memproses checkout');
                    btn.disabled = false;
                    btn.textContent = 'Lanjut ke Pembayaran';
                }
            })
            .catch(function () {
                window.showToast('Koneksi bermasalah');
                btn.disabled = false;
                btn.textContent = 'Lanjut ke Pembayaran';
            });
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
