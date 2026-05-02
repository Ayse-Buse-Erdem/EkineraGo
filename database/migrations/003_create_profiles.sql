USE ekinerago;

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS consumer_profiles (
    user_id BIGINT UNSIGNED PRIMARY KEY,
    default_address_id BIGINT UNSIGNED NULL,
    bio VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_consumer_profiles_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS producer_profiles (
    user_id BIGINT UNSIGNED PRIMARY KEY,
    store_name VARCHAR(160) NOT NULL,
    slug VARCHAR(180) NOT NULL UNIQUE,
    description TEXT NULL,
    logo_path VARCHAR(255) NULL,
    cover_photo_path VARCHAR(255) NULL,
    contact_email VARCHAR(160) NULL,
    contact_phone VARCHAR(30) NULL,
    contact_whatsapp VARCHAR(30) NULL,
    verification_status ENUM('pending', 'verified', 'rejected') NOT NULL DEFAULT 'pending',
    average_rating DECIMAL(3,2) NOT NULL DEFAULT 0.00,
    rating_count INT UNSIGNED NOT NULL DEFAULT 0,
    total_orders INT UNSIGNED NOT NULL DEFAULT 0,
    total_sales_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    shipping_note TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_producer_profiles_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,

    INDEX idx_producer_rating (average_rating),
    INDEX idx_producer_verification (verification_status)
);