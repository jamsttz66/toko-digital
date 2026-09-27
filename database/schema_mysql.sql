-- ============================================================================
-- Toko Digital - Schema MySQL 8.x / MariaDB
-- E-commerce produk digital + QRIS + delivery otomatis (PRD v1.0)
-- ============================================================================

SET NAMES utf8mb4;

-- users
CREATE TABLE IF NOT EXISTS `users` (
    `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`              VARCHAR(150) NOT NULL,
    `email`             VARCHAR(190) NOT NULL,
    `password`          VARCHAR(255) NOT NULL,
    `role`              ENUM('customer','admin','super_admin') NOT NULL DEFAULT 'customer',
    `phone`             VARCHAR(30) NULL,
    `status`            ENUM('active','blocked') NOT NULL DEFAULT 'active',
    `email_verified_at` DATETIME NULL,
    `created_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `users_email_unique` (`email`),
    KEY `users_role_index` (`role`),
    KEY `users_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- categories
CREATE TABLE IF NOT EXISTS `categories` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(150) NOT NULL,
    `slug`        VARCHAR(180) NOT NULL,
    `description` TEXT NULL,
    `image`       VARCHAR(255) NULL,
    `status`      ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `categories_slug_unique` (`slug`),
    KEY `categories_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- products
CREATE TABLE IF NOT EXISTS `products` (
    `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `category_id`       BIGINT UNSIGNED NULL,
    `name`              VARCHAR(200) NOT NULL,
    `slug`              VARCHAR(220) NOT NULL,
    `short_description` VARCHAR(500) NULL,
    `description`       LONGTEXT NULL,
    `price`             DECIMAL(15,2) NOT NULL DEFAULT 0,
    `compare_price`     DECIMAL(15,2) NULL,
    `product_type`      ENUM('ebook','template','asset','software','document','other')
                        NOT NULL DEFAULT 'other',
    `thumbnail`         VARCHAR(255) NULL,
    `status`            ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `featured`          TINYINT(1) NOT NULL DEFAULT 0,
    `created_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `products_slug_unique` (`slug`),
    KEY `products_category_id_index` (`category_id`),
    KEY `products_status_index` (`status`),
    KEY `products_featured_index` (`featured`),
    KEY `products_created_at_index` (`created_at`),
    CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`)
        REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- product_files (file digital disimpan di private storage)
CREATE TABLE IF NOT EXISTS `product_files` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id`    BIGINT UNSIGNED NOT NULL,
    `original_name` VARCHAR(255) NOT NULL,
    `stored_name`   VARCHAR(255) NOT NULL,
    `file_path`     VARCHAR(500) NOT NULL,
    `file_size`     BIGINT NOT NULL DEFAULT 0,
    `mime_type`     VARCHAR(150) NULL,
    `version`       VARCHAR(50) NULL DEFAULT '1.0',
    `status`        ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `product_files_product_id_index` (`product_id`),
    CONSTRAINT `fk_product_files_product` FOREIGN KEY (`product_id`)
        REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- carts
CREATE TABLE IF NOT EXISTS `carts` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`    BIGINT UNSIGNED NULL,
    `session_id` VARCHAR(128) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `carts_user_id_index` (`user_id`),
    KEY `carts_session_id_index` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- cart_items
CREATE TABLE IF NOT EXISTS `cart_items` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `cart_id`        BIGINT UNSIGNED NOT NULL,
    `product_id`     BIGINT UNSIGNED NOT NULL,
    `quantity`       INT NOT NULL DEFAULT 1,
    `price_snapshot` DECIMAL(15,2) NOT NULL,
    `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `cart_items_cart_product_unique` (`cart_id`, `product_id`),
    KEY `cart_items_product_id_index` (`product_id`),
    CONSTRAINT `fk_cart_items_cart` FOREIGN KEY (`cart_id`)
        REFERENCES `carts` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cart_items_product` FOREIGN KEY (`product_id`)
        REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- orders
CREATE TABLE IF NOT EXISTS `orders` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_number`   VARCHAR(50) NOT NULL,
    `user_id`        BIGINT UNSIGNED NULL,
    `customer_name`  VARCHAR(150) NOT NULL,
    `customer_email` VARCHAR(190) NOT NULL,
    `customer_phone` VARCHAR(30) NULL,
    `subtotal`       DECIMAL(15,2) NOT NULL DEFAULT 0,
    `discount`       DECIMAL(15,2) NOT NULL DEFAULT 0,
    `total`          DECIMAL(15,2) NOT NULL DEFAULT 0,
    `voucher_code`   VARCHAR(100) NULL,
    `status`         ENUM('pending','paid','processing','completed','cancelled','expired','refunded')
                     NOT NULL DEFAULT 'pending',
    `paid_at`        DATETIME NULL,
    `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `orders_order_number_unique` (`order_number`),
    KEY `orders_user_id_index` (`user_id`),
    KEY `orders_status_index` (`status`),
    KEY `orders_created_at_index` (`created_at`),
    CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- order_items (snapshot nama & harga agar histori tidak berubah)
CREATE TABLE IF NOT EXISTS `order_items` (
    `id`                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id`              BIGINT UNSIGNED NOT NULL,
    `product_id`            BIGINT UNSIGNED NULL,
    `product_name_snapshot` VARCHAR(200) NOT NULL,
    `price`                 DECIMAL(15,2) NOT NULL,
    `quantity`              INT NOT NULL DEFAULT 1,
    `subtotal`              DECIMAL(15,2) NOT NULL,
    `created_at`            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `order_items_order_id_index` (`order_id`),
    KEY `order_items_product_id_index` (`product_id`),
    CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`)
        REFERENCES `orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`)
        REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- payments
CREATE TABLE IF NOT EXISTS `payments` (
    `id`                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id`              BIGINT UNSIGNED NOT NULL,
    `provider`              VARCHAR(50) NOT NULL,
    `provider_transaction_id` VARCHAR(255) NULL,
    `payment_method`        VARCHAR(50) NOT NULL DEFAULT 'qris',
    `amount`                DECIMAL(15,2) NOT NULL,
    `status`                ENUM('pending','paid','failed','expired','refunded')
                            NOT NULL DEFAULT 'pending',
    `payment_url`           TEXT NULL,
    `qr_string`             TEXT NULL,
    `expired_at`            DATETIME NULL,
    `paid_at`               DATETIME NULL,
    `raw_response`          LONGTEXT NULL,
    `created_at`            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `payments_order_id_index` (`order_id`),
    KEY `payments_provider_transaction_id_index` (`provider_transaction_id`),
    KEY `payments_status_index` (`status`),
    CONSTRAINT `fk_payments_order` FOREIGN KEY (`order_id`)
        REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- payment_events (webhook idempotency: event_id unik)
CREATE TABLE IF NOT EXISTS `payment_events` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `provider`        VARCHAR(50) NOT NULL,
    `event_id`        VARCHAR(255) NOT NULL,
    `order_id`        BIGINT UNSIGNED NULL,
    `event_type`      VARCHAR(100) NULL,
    `payload`         LONGTEXT NULL,
    `signature_valid` TINYINT(1) NOT NULL DEFAULT 0,
    `processed`       TINYINT(1) NOT NULL DEFAULT 0,
    `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `payment_events_event_id_unique` (`event_id`),
    KEY `payment_events_order_id_index` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- deliveries
CREATE TABLE IF NOT EXISTS `deliveries` (
    `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id`         BIGINT UNSIGNED NOT NULL,
    `order_item_id`    BIGINT UNSIGNED NOT NULL,
    `product_id`       BIGINT UNSIGNED NOT NULL,
    `status`           ENUM('pending','ready','delivered','failed','revoked')
                       NOT NULL DEFAULT 'pending',
    `generated_at`     DATETIME NULL,
    `last_download_at` DATETIME NULL,
    `download_count`   INT NOT NULL DEFAULT 0,
    `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `deliveries_order_id_index` (`order_id`),
    KEY `deliveries_order_item_id_index` (`order_item_id`),
    KEY `deliveries_product_id_index` (`product_id`),
    KEY `deliveries_status_index` (`status`),
    CONSTRAINT `fk_deliveries_order` FOREIGN KEY (`order_id`)
        REFERENCES `orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_deliveries_order_item` FOREIGN KEY (`order_item_id`)
        REFERENCES `order_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- download_tokens (hash token, bukan plaintext)
CREATE TABLE IF NOT EXISTS `download_tokens` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `delivery_id`    BIGINT UNSIGNED NOT NULL,
    `user_id`        BIGINT UNSIGNED NULL,
    `token_hash`     VARCHAR(255) NOT NULL,
    `expires_at`     DATETIME NOT NULL,
    `max_downloads`  INT NULL,
    `download_count` INT NOT NULL DEFAULT 0,
    `last_used_at`   DATETIME NULL,
    `revoked_at`     DATETIME NULL,
    `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `download_tokens_token_hash_unique` (`token_hash`),
    KEY `download_tokens_delivery_id_index` (`delivery_id`),
    KEY `download_tokens_user_id_index` (`user_id`),
    CONSTRAINT `fk_download_tokens_delivery` FOREIGN KEY (`delivery_id`)
        REFERENCES `deliveries` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_download_tokens_user` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- vouchers
CREATE TABLE IF NOT EXISTS `vouchers` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `code`         VARCHAR(100) NOT NULL,
    `type`         ENUM('fixed','percentage') NOT NULL,
    `value`        DECIMAL(15,2) NOT NULL,
    `min_purchase` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `max_discount` DECIMAL(15,2) NULL,
    `usage_limit`  INT NULL,
    `used_count`   INT NOT NULL DEFAULT 0,
    `start_at`     DATETIME NOT NULL,
    `end_at`       DATETIME NOT NULL,
    `status`       ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `vouchers_code_unique` (`code`),
    KEY `vouchers_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- audit_logs
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`     BIGINT UNSIGNED NULL,
    `action`      VARCHAR(100) NOT NULL,
    `entity_type` VARCHAR(100) NULL,
    `entity_id`   BIGINT UNSIGNED NULL,
    `description` TEXT NULL,
    `ip_address`  VARCHAR(45) NULL,
    `user_agent`  TEXT NULL,
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `audit_logs_user_id_index` (`user_id`),
    KEY `audit_logs_action_index` (`action`),
    KEY `audit_logs_created_at_index` (`created_at`),
    CONSTRAINT `fk_audit_logs_user` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
