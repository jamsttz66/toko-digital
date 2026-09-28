<?php
/**
 * PaymentService.
 *
 * Tanggung jawab:
 * - membuat order + payment transaction
 * - memilih gateway berdasarkan konfigurasi
 * - harga selalu dihitung server-side (PRD business rule #1, #2)
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/autoload.php';
require_once __DIR__ . '/PaymentGatewayInterface.php';
require_once __DIR__ . '/DummyQrisGateway.php';
require_once __DIR__ . '/MidtransGateway.php';

class PaymentService
{
    private PaymentGatewayInterface $gateway;

    public function __construct()
    {
        $provider = strtolower((string) env('PAYMENT_PROVIDER', 'dummy'));

        $this->gateway = match ($provider) {
            'midtrans' => new MidtransGateway(),
            default => new DummyQrisGateway(),
        };
    }

    public function gateway(): PaymentGatewayInterface
    {
        return $this->gateway;
    }

    /**
     * Buat order + payment dari cart.
     *
     * @return array{order: array, payment: array}
     */
    public function createOrderFromCart(
        array $cart,
        array $customer,
        ?string $voucherCode = null
    ): array {
        $db = Database::pdo();
        $db->beginTransaction();

        try {
            $items = (new Cart())->items((int) $cart['id']);

            if (!$items) {
                throw new RuntimeException('Keranjang masih kosong');
            }

            $subtotal = 0.0;
            $productIds = [];

            foreach ($items as $item) {
                // Ambil harga terbaru dari database (jangan percaya frontend)
                $current = Database::selectOne(
                    "SELECT id, name, price, status FROM products WHERE id = :id",
                    [':id' => $item['product_id']]
                );

                if (!$current || $current['status'] !== 'active') {
                    throw new RuntimeException('Produk tidak lagi tersedia: ' . $item['name']);
                }

                $subtotal += (float) $current['price'];
                $productIds[] = $current['id'];
            }

            // Validasi voucher server-side
            $discount = 0.0;
            $voucherRow = null;
            if ($voucherCode) {
                $voucherRow = (new Voucher())->findByCode($voucherCode);
                if (!$voucherRow) {
                    throw new RuntimeException('Voucher tidak ditemukan');
                }
                $v = (new Voucher())->validate($voucherCode, $subtotal);
                if (!$v['valid']) {
                    throw new RuntimeException($v['message']);
                }
                $discount = $v['discount'];
            }

            $total = max(0.0, $subtotal - $discount);

            // Buat order
            $orderModel = new Order();
            $orderNumber = $orderModel->generateOrderNumber();
            $orderId = $orderModel->create([
                'order_number' => $orderNumber,
                'user_id' => $_SESSION['user']['id'] ?? null,
                'customer_name' => $customer['name'],
                'customer_email' => $customer['email'],
                'customer_phone' => $customer['phone'] ?? null,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'voucher_code' => $voucherCode,
            ]);

            // Buat order items dengan snapshot
            foreach ($items as $item) {
                $current = Database::selectOne(
                    "SELECT name, price FROM products WHERE id = :id",
                    [':id' => $item['product_id']]
                );
                $orderModel->addItem((int) $orderId, $current, (float) $current['price']);
            }

            // Buat payment transaction via gateway
            $txn = $this->gateway->createTransaction([
                'order_number' => $orderNumber,
                'total' => $total,
            ]);

            $paymentId = (new Payment())->create([
                'order_id' => $orderId,
                'provider' => env('PAYMENT_PROVIDER', 'dummy'),
                'provider_transaction_id' => $txn['transaction_id'],
                'amount' => $total,
                'payment_url' => $txn['payment_url'],
                'qr_string' => $txn['qr_string'],
                'expired_at' => $txn['expired_at'],
                'raw_response' => $txn['raw'] ?? null,
            ]);

            if ($voucherRow) {
                (new Voucher())->incrementUsage((int) $voucherRow['id']);
            }

            // Kosongkan cart
            (new Cart())->clear((int) $cart['id']);

            $db->commit();

            return [
                'order' => [
                    'id' => (int) $orderId,
                    'order_number' => $orderNumber,
                    'total' => $total,
                ],
                'payment' => [
                    'id' => (int) $paymentId,
                    'transaction_id' => $txn['transaction_id'],
                    'qr_string' => $txn['qr_string'],
                    'expired_at' => $txn['expired_at'],
                ],
            ];
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }
}
