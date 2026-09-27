<?php
/**
 * API: Cart (PRD section 56)
 *
 * Endpoint: POST /api/cart.php
 * Action: add | remove | clear
 *
 * Selalu memakai CSRF token.
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/autoload.php';

if (!is_post()) {
    json_error('Metode tidak diizinkan', [], 405);
}

if (!csrf_verify(input('csrf_token'))) {
    json_error('Token tidak valid', [], 419);
}

$action = input('action', '');
$productId = (int) input('product_id', 0);

$cartModel = new Cart();
$cart = $cartModel->current();

switch ($action) {
    case 'add':
        if ($productId <= 0) {
            json_error('Produk tidak valid');
        }

        // Produk digital: cek apakah sudah ada di cart
        foreach ($cartModel->items((int) $cart['id']) as $item) {
            if ((int) $item['product_id'] === $productId) {
                json_error('Produk sudah ada di keranjang');
            }
        }

        try {
            $cartModel->addItem((int) $cart['id'], $productId);
        } catch (Throwable $e) {
            json_error($e->getMessage());
        }

        json_success('Produk ditambahkan ke keranjang', [
            'count' => $cartModel->countItems((int) $cart['id']),
        ]);

        break;

    case 'remove':
        if ($productId <= 0) {
            json_error('Produk tidak valid');
        }

        $cartModel->removeItem((int) $cart['id'], $productId);

        json_success('Produk dihapus dari keranjang', [
            'count' => $cartModel->countItems((int) $cart['id']),
        ]);

        break;

    case 'clear':
        $cartModel->clear((int) $cart['id']);
        json_success('Keranjang dikosongkan');

        break;

    default:
        json_error('Action tidak dikenal', [], 400);
}
