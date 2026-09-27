<?php
/**
 * FAQ
 */
require_once __DIR__ . '/includes/header.php';

$pageTitle = 'FAQ';

$faqs = [
    [
        'q' => 'Berapa lama produk bisa di-download setelah bayar?',
        'a' => 'Produk tersedia di akun Anda beberapa saat setelah pembayaran terverifikasi. Biasanya kurang dari satu menit.',
    ],
    [
        'q' => 'Metode pembayaran apa saja yang didukung?',
        'a' => 'Saat ini QRIS, yang bisa dipakai dengan berbagai e-wallet dan aplikasi mobile banking di Indonesia.',
    ],
    [
        'q' => 'Bagaimana cara membayar dengan QRIS?',
        'a' => 'Setelah checkout, Anda akan melihat kode QRIS. Buka aplikasi e-wallet atau mobile banking Anda, pilih scan, lalu pindai kode tersebut dan konfirmasi pembayaran.',
    ],
    [
        'q' => 'Kode QRIS saya kedaluwarsa. Apa yang harus dilakukan?',
        'a' => 'Kode QRIS berlaku 15 menit. Jika kedaluwarsa, Anda bisa membuat pesanan baru. Pesanan lama yang belum dibayar akan otomatis ditandai kedaluwarsa.',
    ],
    [
        'q' => 'Apakah link download bisa dipakai berulang?',
        'a' => 'Bisa. Produk yang sudah dibeli tetap bisa diakses kembali melalui halaman Produk Saya selama akun Anda aktif.',
    ],
    [
        'q' => 'Saya sudah bayar tapi produk belum tersedia.',
        'a' => 'Tunggu beberapa saat karena sistem perlu memverifikasi pembayaran dari payment gateway. Jika lebih dari 10 menit produk belum muncul, hubungi support dengan melampirkan nomor pesanan.',
    ],
    [
        'q' => 'Apakah pembayaran saya aman?',
        'a' => 'Pembayaran diproses oleh payment gateway resmi. Sistem kami hanya menerima notifikasi pembayaran yang sudah diverifikasi. Kami tidak menyimpan data kartu atau informasi pembayaran sensitif.',
    ],
    [
        'q' => 'Apakah bisa refund?',
        'a' => 'Karena produk digital bisa langsung diunduh, refund hanya diberikan jika file rusak atau tidak bisa diakses dan tidak ada pengganti yang tersedia.',
    ],
    [
        'q' => 'Apakah saya harus daftar akun?',
        'a' => 'Tidak wajib saat checkout, tapi sangat disarankan. Dengan akun, semua produk yang pernah dibeli tersimpan dan bisa diakses kembali kapan saja.',
    ],
];
?>

<div class="container-narrow py-4">
    <div class="page-header">
        <h1>Pertanyaan Umum</h1>
        <p class="text-muted mb-0">Hal yang sering ditanyakan tentang pembelian produk digital.</p>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="accordion" id="faqPage">
                <?php foreach ($faqs as $i => $faq): ?>
                    <div class="accordion-item border-line">
                        <h2 class="accordion-header" id="heading<?php echo $i; ?>">
                            <button class="accordion-button <?php echo $i === 0 ? '' : 'collapsed'; ?>" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $i; ?>"
                                    aria-expanded="<?php echo $i === 0 ? 'true' : 'false'; ?>"
                                    aria-controls="collapse<?php echo $i; ?>">
                                <?php echo e($faq['q']); ?>
                            </button>
                        </h2>
                        <div id="collapse<?php echo $i; ?>" class="accordion-collapse collapse <?php echo $i === 0 ? 'show' : ''; ?>"
                             data-bs-parent="#faqPage">
                            <div class="accordion-body text-muted">
                                <?php echo e($faq['a']); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="card-product p-4 mt-4 text-center">
                <h2 class="fs-6 mb-2">Masih ada pertanyaan?</h2>
                <p class="text-muted small mb-3">Tim support siap membantu masalah Anda.</p>
                <a href="<?php echo app_url('contact.php'); ?>" class="btn btn-outline-ink">Hubungi Kami</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
