<?php
/**
 * Model ProductFile.
 * File digital disimpan di private storage, tidak bisa diakses langsung.
 */

class ProductFile extends Model
{
    protected string $table = 'product_files';

    /**
     * Folder penyimpanan file digital (di luar document root).
     */
    public function storagePath(): string
    {
        return dirname(__DIR__) . '/storage/private/products';
    }

    public function findByProduct(int $productId): array
    {
        return Database::select(
            "SELECT * FROM product_files WHERE product_id = :pid AND status = 'active'
             ORDER BY created_at DESC",
            [':pid' => $productId]
        );
    }

    public function findActiveByProduct(int $productId): ?array
    {
        return Database::selectOne(
            "SELECT * FROM product_files WHERE product_id = :pid AND status = 'active'
             ORDER BY created_at DESC LIMIT 1",
            [':pid' => $productId]
        );
    }

    /**
     * Simpan metadata file hasil upload.
     */
    public function create(array $data): string
    {
        return Database::insert(
            "INSERT INTO product_files (product_id, original_name, stored_name, file_path,
                file_size, mime_type, version, status, created_at)
             VALUES (:pid, :oname, :sname, :fpath, :fsize, :mime, :ver, 'active', datetime('now'))",
            [
                ':pid' => $data['product_id'],
                ':oname' => $data['original_name'],
                ':sname' => $data['stored_name'],
                ':fpath' => $data['file_path'],
                ':fsize' => $data['file_size'],
                ':mime' => $data['mime_type'] ?? null,
                ':ver' => $data['version'] ?? '1.0',
            ]
        );
    }

    /**
     * Validasi dan proses upload file digital.
     * - cek MIME
     * - cek extension
     * - batasi ukuran
     * - rename nama storage (tidak pakai original filename)
     */
    public function handleUpload(int $productId, array $file): array
    {
        $allowedMime = [
            'application/pdf' => 'pdf',
            'application/zip' => 'zip',
            'application/x-zip-compressed' => 'zip',
            'application/x-rar-compressed' => 'rar',
            'application/vnd.rar' => 'rar',
            'application/x-7z-compressed' => '7z',
            'application/octet-stream' => null, // dicek extension-nya
            'text/plain' => 'txt',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/vnd.ms-powerpoint' => 'ppt',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'image/png' => 'png',
            'image/jpeg' => 'jpeg',
            'image/webp' => 'webp',
        ];

        $allowedExt = ['pdf','zip','rar','7z','txt','xls','xlsx','ppt','pptx','doc','docx','png','jpg','jpeg','webp'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Gagal mengunggah file');
        }

        if ($file['size'] > 524288000) { // 500 MB
            throw new RuntimeException('Ukuran file melebihi 500MB');
        }

        $origName = basename($file['name']);
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExt, true)) {
            throw new RuntimeException("Ekstensi .{$ext} tidak diizinkan");
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file($file['tmp_name']);

        $mimeOk = isset($allowedMime[$mime]) || $mime === 'application/octet-stream';
        if (!$mimeOk) {
            throw new RuntimeException("Tipe file tidak diizinkan: {$mime}");
        }

        // Nama storage acak, tidak memakai original filename
        $storedName = bin2hex(random_bytes(12)) . '.' . $ext;
        $dir = $this->storagePath();
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $destination = $dir . '/' . $storedName;
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new RuntimeException('Gagal menyimpan file ke storage');
        }

        return [
            'original_name' => $origName,
            'stored_name' => $storedName,
            'file_path' => $destination,
            'file_size' => $file['size'],
            'mime_type' => $mime,
            'ext' => $ext,
        ];
    }
}
