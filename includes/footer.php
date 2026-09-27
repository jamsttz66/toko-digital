<?php
/**
 * Footer global customer.
 */
?>
</main>

<footer class="footer">
    <div class="container-narrow">
        <div class="row gy-4">
            <div class="col-lg-4">
                <h5>Toko Digital</h5>
                <p class="mb-0">Produk digital yang bisa langsung digunakan. Bayar dengan QRIS, akses produk setelah pembayaran berhasil.</p>
            </div>
            <div class="col-6 col-lg-2">
                <h5>Produk</h5>
                <ul>
                    <li><a href="<?php echo app_url('produk.php'); ?>">Semua Produk</a></li>
                    <li><a href="<?php echo app_url('kategori.php'); ?>">Kategori</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-2">
                <h5>Akun</h5>
                <ul>
                    <li><a href="<?php echo app_url('library.php'); ?>">Produk Saya</a></li>
                    <li><a href="<?php echo app_url('orders.php'); ?>">Pesanan</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-2">
                <h5>Bantuan</h5>
                <ul>
                    <li><a href="<?php echo app_url('faq.php'); ?>">FAQ</a></li>
                    <li><a href="<?php echo app_url('contact.php'); ?>">Kontak</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-2">
                <h5>Pembayaran</h5>
                <ul>
                    <li>QRIS</li>
                    <li>Otomatis 24/7</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom d-flex flex-column flex-md-row justify-content-between gap-2">
            <span>© <?php echo date('Y'); ?> Toko Digital. Semua hak dilindungi.</span>
            <span>Pengiriman digital otomatis • Tidak perlu menunggu admin</span>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo asset('js/app.js'); ?>"></script>
</body>
</html>
