<?php
/**
 * Secure Download (PRD section 26-27)
 *
 * URL: /download.php?token=...
 *
 * File TIDAK bisa diakses langsung. Server memvalidasi token:
 *   hash token -> cari di db -> cek expiry -> cek revoked
 *   -> cek limit -> cari file -> record download -> stream file
 *
 * Physical path tidak pernah diexpose ke customer.
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/autoload.php';
require_once __DIR__ . '/services/PaymentGatewayInterface.php';
require_once __DIR__ . '/services/DummyQrisGateway.php';
require_once __DIR__ . '/services/DeliveryService.php';

$token = (string) input('token', '');

if ($token === '') {
    http_response_code(400);
    exit('Token download diperlukan');
}

$service = new DeliveryService();
$data = $service->validateToken($token);

// Kasus invalid
if (!$data) {
    log_error('download', 'Token tidak ditemukan');
    http_response_code(403);
    exit('Link download tidak valid');
}

if (isset($data['invalid'])) {
    $message = match ($data['invalid']) {
        'expired' => 'Link download sudah kedaluwarsa',
        'revoked' => 'Akses produk telah dicabut',
        'limit' => 'Batas download telah tercapai',
        'file_missing' => 'File produk tidak tersedia',
        default => 'Link download tidak valid',
    };

    log_error('download', "Token ditolak: {$message}");
    http_response_code(410);
    exit($message);
}

// Validasi kepemilikan jika token terikat user
if (!empty($data['user_id'])) {
    $loggedIn = $_SESSION['user']['id'] ?? null;
    if ($loggedIn === null || (int) $loggedIn !== (int) $data['user_id']) {
        http_response_code(403);
        exit('Akses ditolak');
    }
}

$filePath = $data['file_path'];
$fileName = $data['original_name'] ?? 'produk.zip';
$fileSize = (int) $data['file_size'];
$mime = $data['mime_type'] ?? 'application/octet-stream';

if (!is_file($filePath) || !is_readable($filePath)) {
    log_error('download', 'File tidak bisa dibaca', ['path_hash' => md5($filePath)]);
    http_response_code(404);
    exit('File tidak ditemukan');
}

// Catat download
$service->recordDownload((int) $data['token_id'], (int) $data['delivery_id']);

// Stream file tanpa expose physical path
header('Content-Description: File Transfer');
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . basename($fileName) . '"');
header('Content-Transfer-Encoding: binary');
header('Cache-Control: private, no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

if ($fileSize > 0) {
    header('Accept-Ranges: bytes');
    header('Content-Length: ' . $fileSize);
}

// Bersihkan buffer output sebelum stream
while (ob_get_level() > 0) {
    ob_end_clean();
}

readfile($filePath);
exit;
