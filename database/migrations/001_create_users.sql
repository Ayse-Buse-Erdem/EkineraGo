CREATE DATABASE IF NOT EXISTS ekinerago
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE ekinerago;

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role ENUM('consumer', 'producer', 'admin') NOT NULL DEFAULT 'consumer',
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(30) NULL,
    whatsapp_phone VARCHAR(30) NULL,
    profile_photo VARCHAR(255) NULL,
    province_id INT UNSIGNED NULL,
    district_id INT UNSIGNED NULL,
    neighborhood_id INT UNSIGNED NULL,
    address_text TEXT NULL,
    latitude DECIMAL(10, 7) NULL,
    longitude DECIMAL(10, 7) NULL,
    status ENUM('active', 'pending', 'suspended', 'deleted') NOT NULL DEFAULT 'active',
    email_verified_at DATETIME NULL,
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    INDEX idx_users_role (role),
    INDEX idx_users_location (province_id, district_id),
    INDEX idx_users_status (status)
);

CREATE TABLE IF NOT EXISTS password_resets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_password_resets_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,

    INDEX idx_password_resets_token (token_hash),
    INDEX idx_password_resets_user (user_id)
);