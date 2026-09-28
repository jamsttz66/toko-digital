# Toko Digital — Status Project

**Path:** `/root/toko-digital`
**Server:** `php -S 127.0.0.1:8080` (PID 41069, jalan dari /root/toko-digital)
**URL:** http://localhost:8080
**DB:** SQLite `storage/database.sqlite` (DB_DRIVER=sqlite)
**Git:** working tree akan di-commit setelah Midtrans + fitur admin.

## Akses Login
- **Admin:** `admin@tokodigital.test` / `admin123` (role: super_admin)
- **Customer:** `testuser@example.com` (lihat database/seed.php untuk password)

## Yang Sudah Jadi (semua diuji render HTTP 200)

### Pembayaran Midtrans Snap (sandbox)
- Provider `midtrans` di `services/MidtransGateway.php`.
- Checkout membuat token Snap sungguhan ke `app.sandbox.midtrans.com`.
- Webhook `api/payment-webhook.php` memverifikasi
  `SHA512(order_id + status_code + gross_amount + ServerKey)`.
- Signature palsu ditolak. Settlement membuat order `paid` + delivery.
- Header webhook dibaca lewat `getallheaders()` (php -S tidak mengisi `HTTP_*`).
- Key ada di `.env` (`MIDTRANS_SERVER_KEY` / `MIDTRANS_CLIENT_KEY`), tidak di git.
- Admin settings bisa ganti provider dummy/midtrans dan kedua key.

### Admin tambahan
- Pagination prev/next: orders, payments, deliveries, users.
- Kolom tanggal orders: `dd/mm/YYYY HH:mm`.
- Konfirmasi pembayaran manual di detail order (cadangan jika webhook gagal).
- Export CSV laporan: `admin/reports-export.php` (filter tanggal yang sama).

### Customer storefront (commit 8a3e1c9, "E2E tested")
index, produk, produk-detail, kategori, cart, checkout, payment, profile,
orders, order-detail, library/download, login, register, logout, contact, faq.

### Admin panel (commit e9ab456 — LENGKAP)
Dashboard, login, products (list/create/edit/delete), categories
(list/create/edit/delete), orders (list + detail + filter status), payments,
deliveries (+ action: ready/delivered/revoke), users (+ suspend/activate),
vouchers (list/create/edit/delete), reports (filter tanggal, top produk,
per kategori, harian), audit-logs, settings (super_admin only, edit .env:
payment + mail).

### Backend
- **classes/**: Cart, Category, Delivery, Order, Payment, Product, ProductFile,
  User, Voucher (extends abstract Model)
- **services/**: PaymentService, WebhookService, DeliveryService, EmailService,
  DummyQrisGateway, PaymentGatewayInterface
- **api/**: cart, checkout, payment-status, payment-webhook
- **database/**: schema_mysql.sql, schema_sqlite.sql, seed.php
- **tools/simulate-webhook.php** — simulasi webhook QRIS

## Bug Yang Pernah Ditemukan & Diperbaiki
1. `Voucher::update()` corrupt (`$data['.php']`, `compare()`, `$transact` tak
   terdefinisi) → diperbaiki (commit 059611e).
2. `admin/partials/header.php` include path salah: `__DIR__ . '/../includes/'`
   → harusnya `'/../../includes/'` karena partials ada di admin/partials/.
   Akibatnya SEMUA halaman admin HTTP 500 (commit e9ab456).
3. `audit-logs.php` instansiasi `new Model()` padahal Model abstract →
   diganti `Database::selectOne`.
4. Sidebar menu link dashboard ke `index.php` padahal filenya `dashboard.php`.
5. Direktori sampah `admin/{partials}` (typo brace dari shell lama) sudah dihapus.

## Yang Masih Bisa Dikerjakan (opsional)
- Email service gagal dikirim (log: "Gagal mengirim email") — MAIL_* kosong.
- URL notifikasi Midtrans harus publik (bukan localhost) supaya sandbox
  benar-benar memanggil webhook. Tes lokal sudah lewat payload bertanda tangan.

## Cara Lanjut
Buka chat baru, bilang "lanjutkan toko-digital". Cek `git log` & file ini.
