<?php
/**
 * Seeder: data dummy kategori, produk, admin, voucher.
 * Jalankan: php database/seed.php
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/autoload.php';

echo "Menyiapkan database...\n";

// Pastikan schema terbuat (SQLite autobootstrap via Database::pdo())
Database::pdo();

echo "Membuat kategori...\n";
$categories = [
    ['name' => 'Template', 'description' => 'Template siap pakai untuk produktivitas dan bisnis'],
    ['name' => 'E-book', 'description' => 'Panduan dan buku digital'],
    ['name' => 'Design Assets', 'description' => 'Aset desain untuk kreator'],
    ['name' => 'Productivity', 'description' => 'Alat dan sistem produktivitas'],
    ['name' => 'Business', 'description' => 'Materi untuk pengembangan bisnis'],
    ['name' => 'Education', 'description' => 'Materi pembelajaran'],
    ['name' => 'Software', 'description' => 'Perangkat lunak dan tools'],
    ['name' => 'Presets', 'description' => 'Preset editing foto dan video'],
];

$categoryModel = new Category();
$catIds = [];
foreach ($categories as $cat) {
    $existing = $categoryModel->findBySlug(slugify($cat['name']));
    if ($existing) {
        $catIds[$cat['name']] = (int) $existing['id'];
    } else {
        $catIds[$cat['name']] = (int) $categoryModel->create($cat);
    }
}
echo "  " . count($catIds) . " kategori siap\n";

echo "Membuat produk...\n";
$products = [
    [
        'category' => 'Productivity',
        'name' => 'Notion Productivity Workspace',
        'short_description' => 'Sistem manajemen tugas dan catatan terstruktur untuk individu dan tim kecil.',
        'description' => 'Workspace siap pakai untuk mengatur tugas, catatan, dan target. Dilengkapi dashboard utama, database tugas, dan sistem tracking harian.\n\nIsi paket:\n- Dashboard utama\n- Database tugas dengan prioritas\n- Template catatan rapat\n- Sistem tracking kebiasaan\n\nCara menggunakan:\n1. Buka link duplicate yang dikirim setelah pembelian\n2. Klik "Duplicate" di pojok kanan atas\n3. Mulai gunakan langsung',
        'price' => 79000,
        'compare_price' => 149000,
        'product_type' => 'template',
        'featured' => 1,
    ],
    [
        'category' => 'Business',
        'name' => 'Business Proposal Template',
        'short_description' => 'Template proposal bisnis profesional yang sudah teruji dipakai klien.',
        'description' => 'Template proposal bisnis dengan struktur jelas, cocok untuk agensi, freelancer, dan UMKM.\n\nIsi paket:\n- Cover proposal\n- Tentang kami\n- Lingkup pekerjaan\n- Rincian biaya\n- Syarat dan ketentuan\n\nFormat: DOCX dan Google Docs',
        'price' => 99000,
        'compare_price' => 199000,
        'product_type' => 'template',
        'featured' => 1,
    ],
    [
        'category' => 'Design Assets',
        'name' => 'Social Media Content Template',
        'short_description' => '30 template desain konten Instagram dan Facebook yang siap diedit.',
        'description' => 'Kumpulan template konten media sosial dengan komposisi warna dan tipografi yang konsisten.\n\nIsi paket:\n- 30 template Instagram feed\n- 10 template story\n- Palet warna\n- Tipografi panduan\n\nFormat: PSD dan Canva',
        'price' => 49000,
        'compare_price' => 99000,
        'product_type' => 'asset',
        'featured' => 1,
    ],
    [
        'category' => 'Productivity',
        'name' => 'Digital Planner',
        'short_description' => 'Perencana harian, mingguan, dan bulanan dalam format PDF yang dapat dianotasi.',
        'description' => 'Rencanakan hari, minggu, dan bulan dengan planner digital yang dapat dipakai di tablet atau di-print.\n\nIsi paket:\n- Halaman harian\n- Halaman mingguan\n- Halaman bulanan\n- Tracker target tahunan\n\nFormat: PDF',
        'price' => 49000,
        'product_type' => 'document',
        'featured' => 0,
    ],
    [
        'category' => 'Business',
        'name' => 'E-book Panduan Bisnis Digital',
        'short_description' => 'Panduan membangun bisnis produk digital dari nol sampai dapat pelanggan pertama.',
        'description' => 'E-book 120 halaman membahas riset produk, pembuatan, penjualan, dan operasional bisnis digital.\n\nIsi paket:\n- 8 bab materi\n- Template perencanaan\n- Studi kasus\n\nFormat: PDF',
        'price' => 99000,
        'product_type' => 'ebook',
        'featured' => 1,
    ],
    [
        'category' => 'Design Assets',
        'name' => 'Resume & Portfolio Template',
        'short_description' => 'Template CV dan portofolio minimalis untuk melamar kerja atau klien.',
        'description' => 'Template resume profesional dengan tata letak bersih dan mudah diedit.\n\nIsi paket:\n- 3 variasi resume\n- Template cover letter\n- Versi bahasa Indonesia dan Inggris\n\nFormat: DOCX, INDD',
        'price' => 49000,
        'compare_price' => 99000,
        'product_type' => 'template',
        'featured' => 0,
    ],
    [
        'category' => 'Business',
        'name' => 'Presentation Template',
        'description' => 'Template presentasi bisnis dengan 40 slide siap pakai.',
        'short_description' => '40 slide presentasi bisnis dengan layout editable penuh.',
        'description' => 'Template presentasi untuk pitching, reporting, dan training.\n\nIsi paket:\n- 40 slide layout\n- Diagram dan infografis\n- Icon set\n\nFormat: PPTX',
        'price' => 99000,
        'product_type' => 'template',
        'featured' => 0,
    ],
    [
        'category' => 'Business',
        'name' => 'Spreadsheet Financial Planner',
        'short_description' => 'Spreadsheet pencatat keuangan usaha dengan formula otomatis.',
        'description' => 'Pencatatan keuangan UMKM dengan formula otomatis: laba rugi, arus kas, dan proyeksi.\n\nIsi paket:\n- Sheet pendapatan\n- Sheet pengeluaran\n- Sheet laba rugi\n- Dashboard ringkasan\n\nFormat: XLSX',
        'price' => 79000,
        'compare_price' => 159000,
        'product_type' => 'template',
        'featured' => 1,
    ],
];

$productModel = new Product();
$count = 0;
foreach ($products as $p) {
    $slug = slugify($p['name']);
    $existing = $productModel->findBySlug($slug);
    if ($existing) {
        continue;
    }

    $productModel->create([
        'category_id' => $catIds[$p['category']] ?? null,
        'name' => $p['name'],
        'slug' => $slug,
        'short_description' => $p['short_description'] ?? null,
        'description' => $p['description'] ?? null,
        'price' => $p['price'],
        'compare_price' => $p['compare_price'] ?? null,
        'product_type' => $p['product_type'],
        'featured' => $p['featured'] ?? 0,
    ]);
    $count++;
}
echo "  {$count} produk dibuat\n";

echo "Membuat admin default...\n";
$userModel = new User();
$adminEmail = 'admin@tokodigital.test';
$admin = $userModel->findByEmail($adminEmail);
if (!$admin) {
    $userModel->create([
        'name' => 'Admin',
        'email' => $adminEmail,
        'password' => password_hash('admin123', PASSWORD_DEFAULT),
        'role' => 'super_admin',
    ]);
    echo "  Admin dibuat -> email: {$adminEmail} password: admin123\n";
} else {
    echo "  Admin sudah ada\n";
}

echo "Membuat voucher contoh...\n";
$voucherModel = new Voucher();
if (!$voucherModel->findByCode('HEMAT10')) {
    $voucherModel->create([
        'code' => 'HEMAT10',
        'type' => 'percentage',
        'value' => 10,
        'min_purchase' => 50000,
        'usage_limit' => 100,
        'start_at' => date('Y-01-01 00:00:00'),
        'end_at' => date('Y-12-31 23:59:59'),
        'status' => 'active',
    ]);
    echo "  Voucher HEMAT10 (diskon 10%) dibuat\n";
}

echo "\nSelesai. Data dummy siap.\n";
echo "Storage: " . dirname(__DIR__) . "/storage/database.sqlite\n";
