USE ekinerago;

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS campaigns (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    producer_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(160) NOT NULL,
    description TEXT NULL,
    campaign_type ENUM(
        'percentage_discount',
        'fixed_discount',
        'bulk_discount',
        'free_shipping'
    ) NOT NULL,
    discount_value DECIMAL(12,2) NULL,
    min_quantity DECIMAL(10,2) NULL,
    min_order_amount DECIMAL(12,2) NULL,
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NOT NULL,
    status ENUM('draft', 'active', 'expired', 'cancelled') NOT NULL DEFAULT 'draft',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_campaigns_producer
        FOREIGN KEY (producer_id) REFERENCES users(id)
        ON DELETE CASCADE,

    INDEX idx_campaigns_producer (producer_id),
    INDEX idx_campaigns_status_dates (status, starts_at, ends_at)
);

CREATE TABLE IF NOT EXISTS campaign_products (
    campaign_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,

    PRIMARY KEY (campaign_id, product_id),

    CONSTRAINT fk_campaign_products_campaign
        FOREIGN KEY (campaign_id) REFERENCES campaigns(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_campaign_products_product
        FOREIGN KEY (product_id) REFERENCES products(id)
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS producer_shipping_regions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    producer_id BIGINT UNSIGNED NOT NULL,
    province_id INT UNSIGNED NOT NULL,
    district_id INT UNSIGNED NULL,
    shipping_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    min_order_amount DECIMAL(12,2) NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_shipping_regions_producer
        FOREIGN KEY (producer_id) REFERENCES users(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_shipping_regions_province
        FOREIGN KEY (province_id) REFERENCES provinces(id),

    CONSTRAINT fk_shipping_regions_district
        FOREIGN KEY (district_id) REFERENCES districts(id)
        ON DELETE SET NULL,

    INDEX idx_shipping_regions_producer (producer_id),
    INDEX idx_shipping_regions_location (province_id, district_id)
);

CREATE TABLE IF NOT EXISTS producer_bulk_shipping_rules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    producer_id BIGINT UNSIGNED NOT NULL,
    min_total_quantity DECIMAL(10,2) NOT NULL,
    unit_type ENUM('kg', 'piece', 'bunch', 'box') NOT NULL DEFAULT 'kg',
    ships_all_turkey BOOLEAN NOT NULL DEFAULT FALSE,
    shipping_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    note TEXT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_bulk_shipping_producer
        FOREIGN KEY (producer_id) REFERENCES users(id)
        ON DELETE CASCADE,

    INDEX idx_bulk_shipping_producer (producer_id)
);