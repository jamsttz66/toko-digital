<?php
/**
 * EmailService.
 *
 * Mengirim email notifikasi (PRD 38-39). Menggunakan mail() PHP bawaan
 * untuk MVP; bisa diganti ke SMTP/API tanpa mengubah pemanggil.
 */

require_once __DIR__ . '/../includes/functions.php';

class EmailService
{
    private string $from;
    private string $fromName;

    public function __construct()
    {
        $this->from = (string) env('MAIL_FROM', 'noreply@tokodigital.test');
        $this->fromName = (string) env('MAIL_FROM_NAME', 'Toko Digital');
    }

    private function send(string $to, string $subject, string $body): bool
    {
        $headers = [
            'From' => "{$this->fromName} <{$this->from}>",
            'Reply-To' => $this->from,
            'Content-Type' => 'text/html; charset=UTF-8',
            'MIME-Version' => '1.0',
        ];

        $headerStr = '';
        foreach ($headers as $k => $v) {
            $headerStr .= "{$k}: {$v}\r\n";
        }

        $ok = mail($to, $subject, $body, $headerStr);

        if (!$ok) {
            log_error('email', 'Gagal mengirim email', ['to' => $to, 'subject' => $subject]);
        }

        return $ok;
    }

    private function wrap(string $title, string $content): string
    {
        return '<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><title>' . e($title) . '</title></head>
<body style="margin:0;padding:0;background:#f7f7f5;font-family:Helvetica,Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="padding:32px 0;">
<tr><td align="center">
<table width="560" cellpadding="0" cellspacing="0" style="background:#ffffff;
border:1px solid #e5e5e0;border-radius:8px;overflow:hidden;">
<tr><td style="padding:24px 32px;border-bottom:1px solid #e5e5e0;">
<span style="font-size:18px;font-weight:600;color:#1a2332;">' . e($this->fromName) . '</span>
</td></tr>
<tr><td style="padding:32px;color:#2b2b2b;font-size:15px;line-height:1.6;">
' . $content . '
</td></tr>
<tr><td style="padding:20px 32px;border-top:1px solid #e5e5e0;color:#8a8a85;font-size:12px;">
Email ini dikirim otomatis. Jangan balas email ini.
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>';
    }

    private function btn(string $url, string $label): string
    {
        return '<a href="' . e($url) . '" style="display:inline-block;padding:10px 20px;
background:#1a2332;color:#ffffff;text-decoration:none;border-radius:6px;font-weight:500;">'
            . e($label) . '</a>';
    }

    /**
     * Email saat order dibuat (pending payment).
     */
    public function sendOrderCreated(array $order): void
    {
        $subject = 'Pesanan ' . $order['order_number'] . ' diterima';
        $content = '<p>Halo ' . e($order['customer_name']) . ',</p>
<p>Pesanan Anda dengan nomor <strong>' . e($order['order_number']) . '</strong> telah diterima.</p>
<p>Total: <strong>' . rupiah2((float) $order['total']) . '</strong></p>
<p>Silakan selesaikan pembayaran melalui QRIS. Produk akan tersedia otomatis setelah pembayaran berhasil.</p>'
            . $this->btn(app_url('payment.php?tx=' . urlencode($order['order_number'])), 'Lihat Pembayaran');

        $this->send($order['customer_email'], $subject, $this->wrap($subject, $content));
    }

    /**
     * Email saat pembayaran berhasil & produk siap (PRD 39).
     */
    public function sendPaymentSuccess(array $order, array $items): void
    {
        $subject = 'Pesanan ' . $order['order_number'] . ' sudah siap';
        $productList = '';
        foreach ($items as $item) {
            $productList .= '<li>' . e($item['product_name_snapshot']) . '</li>';
        }

        $content = '<p>Halo ' . e($order['customer_name']) . ',</p>
<p>Pembayaran untuk pesanan <strong>' . e($order['order_number']) . '</strong> telah berhasil.</p>
<p><strong>Produk:</strong></p>
<ul>' . $productList . '</ul>
<p><strong>Total:</strong> ' . rupiah2((float) $order['total']) . '</p>
<p><strong>Tanggal:</strong> ' . tgl_jam_id($order['paid_at'] ?? date('Y-m-d H:i:s')) . '</p>
<p>Produk Anda sudah tersedia di akun. Klik tombol di bawah untuk mengunduh.</p>'
            . $this->btn(app_url('library.php'), 'Download Produk') . '
<p style="margin-top:16px;color:#8a8a85;">Jika mengalami kendala, silakan hubungi support.</p>';

        $this->send($order['customer_email'], $subject, $this->wrap($subject, $content));
    }

    /**
     * Email reset password.
     */
    public function sendPasswordReset(string $email, string $name, string $resetUrl): void
    {
        $subject = 'Reset password Anda';
        $content = '<p>Halo ' . e($name) . ',</p>
<p>Kami menerima permintaan untuk reset password akun Anda.</p>
<p>Klik tombol di bawah untuk membuat password baru. Link ini hanya berlaku 1 jam.</p>'
            . $this->btn($resetUrl, 'Reset Password') . '
<p style="margin-top:16px;color:#8a8a85;">Jika Anda tidak meminta reset password, abaikan email ini.</p>';

        $this->send($email, $subject, $this->wrap($subject, $content));
    }
}
