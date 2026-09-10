-- =====================================================================
-- Inventory & E-Commerce Management System — Full Database Schema
-- Target: MySQL 8.x (utf8mb4)
-- This mirrors the Laravel migrations exactly. Run once to create the DB.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS inventory_ecommerce
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE inventory_ecommerce;

SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- USERS (extended with security fields)
-- ---------------------------------------------------------------------
CREATE TABLE users (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name              VARCHAR(255) NOT NULL,
    email             VARCHAR(255) NOT NULL UNIQUE,
    email_verified_at TIMESTAMP NULL DEFAULT NULL,
    password          VARCHAR(255) NOT NULL,          -- bcrypt hash, never plaintext
    remember_token    VARCHAR(100) NULL,

    mfa_secret        VARCHAR(255) NULL,               -- TOTP secret for MFA
    mfa_enabled       TINYINT(1) NOT NULL DEFAULT 0,
    is_active         TINYINT(1) NOT NULL DEFAULT 1,   -- admin can disable a compromised account
    last_login_at     TIMESTAMP NULL DEFAULT NULL,
    last_login_ip     VARCHAR(45) NULL,                -- 45 chars fits IPv6

    created_at        TIMESTAMP NULL DEFAULT NULL,
    updated_at        TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- ROLES & PERMISSIONS (Spatie-style; simplified manual version shown
-- here for clarity if you are NOT using spatie/laravel-permission)
-- ---------------------------------------------------------------------
CREATE TABLE roles (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL UNIQUE,           -- admin, staff, customer
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB;

CREATE TABLE permissions (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(150) NOT NULL UNIQUE,           -- e.g. product.create
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB;

CREATE TABLE role_permission (
    role_id       BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Links a user to a role (many-to-many, though typically one active role per user)
CREATE TABLE role_user (
    user_id BIGINT UNSIGNED NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (user_id, role_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- CATEGORIES (self-referencing for subcategories)
-- ---------------------------------------------------------------------
CREATE TABLE categories (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(255) NOT NULL,
    slug        VARCHAR(255) NOT NULL UNIQUE,
    description TEXT NULL,
    parent_id   BIGINT UNSIGNED NULL,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    created_at  TIMESTAMP NULL DEFAULT NULL,
    updated_at  TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- PRODUCTS
-- ---------------------------------------------------------------------
CREATE TABLE products (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id BIGINT UNSIGNED NOT NULL,
    sku         VARCHAR(100) NOT NULL UNIQUE,
    name        VARCHAR(255) NOT NULL,
    slug        VARCHAR(255) NOT NULL UNIQUE,
    description TEXT NULL,
    price       DECIMAL(10,2) NOT NULL,                -- DECIMAL, never FLOAT, for money
    cost_price  DECIMAL(10,2) NULL,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    created_at  TIMESTAMP NULL DEFAULT NULL,
    updated_at  TIMESTAMP NULL DEFAULT NULL,
    deleted_at  TIMESTAMP NULL DEFAULT NULL,            -- soft delete: keeps order history intact
    FOREIGN KEY (category_id) REFERENCES categories(id)
        ON DELETE RESTRICT,                             -- can't delete a category with products
    INDEX idx_products_category_active (category_id, is_active)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- PRODUCT IMAGES
-- ---------------------------------------------------------------------
CREATE TABLE product_images (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    path       VARCHAR(255) NOT NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- INVENTORY (current stock snapshot — 1:1 with products)
-- ---------------------------------------------------------------------
CREATE TABLE inventory (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id        BIGINT UNSIGNED NOT NULL UNIQUE,
    quantity_on_hand  INT UNSIGNED NOT NULL DEFAULT 0,
    reorder_level     INT UNSIGNED NOT NULL DEFAULT 10, -- triggers low-stock alert
    created_at        TIMESTAMP NULL DEFAULT NULL,
    updated_at        TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- STOCK MOVEMENTS (append-only audit ledger)
-- ---------------------------------------------------------------------
CREATE TABLE stock_movements (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id   BIGINT UNSIGNED NOT NULL,
    type         ENUM('stock_in','stock_out','adjustment') NOT NULL,
    quantity     INT NOT NULL,                          -- +ve for in, -ve for out/adjustment-down
    reason       VARCHAR(255) NULL,
    performed_by BIGINT UNSIGNED NOT NULL,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (performed_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_stock_movements_product_date (product_id, created_at)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- CARTS & CART ITEMS
-- ---------------------------------------------------------------------
CREATE TABLE carts (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    BIGINT UNSIGNED NOT NULL UNIQUE,         -- one active cart per user
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE cart_items (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cart_id    BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    quantity   INT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_cart_product (cart_id, product_id)  -- no duplicate product rows in same cart
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- WISHLISTS
-- ---------------------------------------------------------------------
CREATE TABLE wishlists (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_user_product_wishlist (user_id, product_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- ORDERS & ORDER ITEMS
-- ---------------------------------------------------------------------
CREATE TABLE orders (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id          BIGINT UNSIGNED NOT NULL,
    order_number     VARCHAR(50) NOT NULL UNIQUE,       -- e.g. ORD-2026-00001
    status           ENUM('pending','processing','shipped','completed','cancelled')
                          NOT NULL DEFAULT 'pending',
    total_amount     DECIMAL(10,2) NOT NULL,
    shipping_name    VARCHAR(255) NOT NULL,
    shipping_phone   VARCHAR(30) NOT NULL,
    shipping_address TEXT NOT NULL,
    created_at       TIMESTAMP NULL DEFAULT NULL,
    updated_at       TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_orders_status (status)
) ENGINE=InnoDB;

CREATE TABLE order_items (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id   BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    quantity   INT UNSIGNED NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,                  -- price snapshot at time of order
    subtotal   DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- PAYMENTS
-- ---------------------------------------------------------------------
CREATE TABLE payments (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id        BIGINT UNSIGNED NOT NULL,
    method          ENUM('cod','online_simulation') NOT NULL,
    status          ENUM('pending','paid','failed') NOT NULL DEFAULT 'pending',
    transaction_ref VARCHAR(100) NULL UNIQUE,
    amount          DECIMAL(10,2) NOT NULL,
    created_at      TIMESTAMP NULL DEFAULT NULL,
    updated_at      TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- ACTIVITY LOGS (security audit trail)
-- ---------------------------------------------------------------------
CREATE TABLE activity_logs (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     BIGINT UNSIGNED NULL,
    action      VARCHAR(150) NOT NULL,                  -- e.g. "login.failed", "product.deleted"
    ip_address  VARCHAR(45) NULL,
    user_agent  TEXT NULL,
    description JSON NULL,                              -- old/new values, affected record id, etc.
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_activity_logs_user_date (user_id, created_at),
    INDEX idx_activity_logs_action (action)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- SEED DATA — default roles (run once after table creation)
-- =====================================================================
INSERT INTO roles (name, created_at, updated_at) VALUES
    ('admin', NOW(), NOW()),
    ('staff', NOW(), NOW()),
    ('customer', NOW(), NOW());
