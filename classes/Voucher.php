<?php
/**
 * Model Voucher.
 */

class Voucher extends Model
{
    protected string $table = 'vouchers';

    public function findByCode(string $code): ?array
    {
        return Database::selectOne(
            "SELECT * FROM vouchers WHERE code = :code LIMIT 1",
            [':code' => $code]
        );
    }

    public function create(array $data): string
    {
        return Database::insert(
            "INSERT INTO vouchers (code, type, value, min_purchase, max_discount, usage_limit, used_count,
                start_at, end_at, status, created_at, updated_at)
             VALUES (:code, :type, :value, :min, :maxd, :ulimit, 0,
                :start, :end, :status, datetime('now'), datetime('now'))",
            [
                ':code' => strtoupper($data['code']),
                ':type' => $data['type'],
                ':value' => (float) $data['value'],
                ':min' => (float) ($data['min_purchase'] ?? 0),
                ':maxd' => isset($data['max_discount']) && $data['max_discount'] !== ''
                    ? (float) $data['max_discount'] : null,
                ':ulimit' => isset($data['usage_limit']) && $data['usage_limit'] !== ''
                    ? (int) $data['usage_limit'] : null,
                ':start' => $data['start_at'],
                ':end' => $data['end_at'],
                ':status' => $data['status'] ?? 'active',
            ]
        );
    }

    public function update(int $id, array $data): int
    {
        return Database::execute(
            "UPDATE vouchers SET code = :code, type = :type, value = :value,
                min_purchase = :min, max_discount = :maxd, usage_limit = :ulimit,
                start_at = :start, end_at = :end, status = :status, updated_at = datetime('now')
             WHERE id = :id",
            [
                ':code' => strtoupper($data['code']),
                ':type' => $data['type'],
                ':value' => (float) $data['value'],
                ':min' => (float) ($data['min_purchase'] ?? 0),
                ':maxd' => isset($data['max_discount']) && $data['max_discount'] !== ''
                    ? (float) $data['max_discount'] : null,
                ':ulimit' => isset($data['usage_limit']) && $data['usage_limit'] !== ''
                    ? (int) $data['usage_limit'] : null,
                ':start' => $data['start_at'],
                ':end' => $data['end_at'],
                ':status' => $data['status'] ?? 'active',
                ':id' => $id,
            ]
        );
    }

    /**
     * Validasi voucher secara server-side.
     * Kembalikan array: [valid, discount, message]
     */
    public function validate(string $code, float $subtotal): array
    {
        $voucher = $this->findByCode($code);

        if (!$voucher) {
            return ['valid' => false, 'discount' => 0.0, 'message' => 'Voucher tidak ditemukan'];
        }

        if ($voucher['status'] !== 'active') {
            return ['valid' => false, 'discount' => 0.0, 'message' => 'Voucher sudah tidak aktif'];
        }

        $now = date('Y-m-d H:i:s');
        if ($voucher['start_at'] > $now || $voucher['end_at'] < $now) {
            return ['valid' => false, 'discount' => 0.0, 'message' => 'Voucher sudah kedaluwarsa'];
        }

        if ($voucher['usage_limit'] !== null && (int) $voucher['used_count'] >= (int) $voucher['usage_limit']) {
            return ['valid' => false, 'discount' => 0.0, 'message' => 'Kuota voucher sudah habis'];
        }

        if ($subtotal < (float) $voucher['min_purchase']) {
            return ['valid' => false, 'discount' => 0.0,
                'message' => 'Minimal pembelian ' . rupiah2((float) $voucher['min_purchase'])];
        }

        // Hitung diskon
        $discount = 0.0;
        if ($voucher['type'] === 'fixed') {
            $discount = (float) $voucher['value'];
        } else { // percentage
            $discount = $subtotal * ((float) $voucher['value'] / 100);
            if ($voucher['max_discount'] !== null) {
                $discount = min($discount, (float) $voucher['max_discount']);
            }
        }

        $discount = min($discount, $subtotal);

        return ['valid' => true, 'discount' => round($discount, 2), 'message' => 'Voucher diterapkan'];
    }

    public function incrementUsage(int $voucherId): void
    {
        Database::execute(
            "UPDATE vouchers SET used_count = used_count + 1, updated_at = datetime('now')
             WHERE id = :id",
            [':id' => $voucherId]
        );
    }
}
