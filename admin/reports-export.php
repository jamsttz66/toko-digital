<?php
/**
 * Admin: export laporan penjualan ke CSV.
 *
 * Query-nya sama persis dengan reports.php supaya angka di layar dan di file
 * tidak pernah beda. Output: ringkasan, produk terlaris, per kategori, harian.
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/autoload.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_admin();

$from = clean(input('from', date('Y-m-01')));
$to = clean(input('to', date('Y-m-d')));

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
    $_SESSION['flash'] = 'Rentang tanggal tidak valid';
    redirect(app_url('admin/reports.php'));
}

$params = [':from' => $from, ':to' => $to];

$summary = Database::selectOne(
    "SELECT COUNT(*) AS total_orders,
            SUM(CASE WHEN status IN ('paid','completed') THEN 1 ELSE 0 END) AS paid_orders,
            SUM(CASE WHEN status IN ('paid','completed') THEN total ELSE 0 END) AS revenue,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_orders
     FROM orders
     WHERE date(created_at) BETWEEN :from AND :to",
    $params
);

$topProducts = Database::select(
    "SELECT oi.product_name_snapshot AS name,
            COUNT(*) AS sold,
            SUM(oi.subtotal) AS revenue
     FROM order_items oi
     INNER JOIN orders o ON o.id = oi.order_id
     WHERE o.status IN ('paid','completed')
       AND date(o.created_at) BETWEEN :from AND :to
     GROUP BY oi.product_id, oi.product_name_snapshot
     ORDER BY sold DESC",
    $params
);

$categorySales = Database::select(
    "SELECT COALESCE(c.name, '(tanpa kategori)') AS category_name,
            COUNT(oi.id) AS sold,
            SUM(oi.subtotal) AS revenue
     FROM order_items oi
     INNER JOIN orders o ON o.id = oi.order_id
     INNER JOIN products p ON p.id = oi.product_id
     LEFT JOIN categories c ON c.id = p.category_id
     WHERE o.status IN ('paid','completed')
       AND date(o.created_at) BETWEEN :from AND :to
     GROUP BY c.id, c.name
     ORDER BY revenue DESC",
    $params
);

$daily = Database::select(
    "SELECT date(o.created_at) AS day,
            COUNT(*) AS orders,
            SUM(o.total) AS revenue
     FROM orders o
     WHERE o.status IN ('paid','completed')
       AND date(o.created_at) BETWEEN :from AND :to
     GROUP BY date(o.created_at)
     ORDER BY day ASC",
    $params
);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="laporan-' . $from . '_' . $to . '.csv"');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF"); // BOM supaya Excel baca UTF-8

$row = static function (array $cells) use ($out): void {
    fputcsv($out, $cells);
};

$row(['Laporan penjualan', $from, 's/d', $to]);
$row([]);
$row(['Ringkasan']);
$row(['Total order', (int) ($summary['total_orders'] ?? 0)]);
$row(['Order berbayar', (int) ($summary['paid_orders'] ?? 0)]);
$row(['Order pending', (int) ($summary['pending_orders'] ?? 0)]);
$row(['Pendapatan', (float) ($summary['revenue'] ?? 0)]);
$row([]);

$row(['Produk terlaris']);
$row(['Produk', 'Terjual', 'Pendapatan']);
foreach ($topProducts as $p) {
    $row([$p['name'], (int) $p['sold'], (float) $p['revenue']]);
}
$row([]);

$row(['Penjualan per kategori']);
$row(['Kategori', 'Terjual', 'Pendapatan']);
foreach ($categorySales as $c) {
    $row([$c['category_name'], (int) $c['sold'], (float) $c['revenue']]);
}
$row([]);

$row(['Penjualan harian']);
$row(['Tanggal', 'Order', 'Pendapatan']);
foreach ($daily as $d) {
    $row([$d['day'], (int) $d['orders'], (float) $d['revenue']]);
}

fclose($out);
exit;
