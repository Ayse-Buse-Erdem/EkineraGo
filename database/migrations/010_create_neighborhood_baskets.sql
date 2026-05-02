USE ekinerago;

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS neighborhood_baskets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    producer_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    creator_user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(160) NOT NULL,
    province_id INT UNSIGNED NOT NULL,
    district_id INT UNSIGNED NULL,
    neighborhood_id INT UNSIGNED NULL,
    target_quantity DECIMAL(10,2) NOT NULL,
    current_quantity DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    unit_type ENUM('kg', 'piece', 'bunch', 'box') NOT NULL DEFAULT 'kg',
    status ENUM('open', 'ready_to_order', 'ordered', 'cancelled', 'expired') NOT NULL DEFAULT 'open',
    expires_at DATETIME NULL,
    order_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_nb_producer
        FOREIGN KEY (producer_id) REFERENCES users(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_nb_product
        FOREIGN KEY (product_id) REFERENCES products(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_nb_creator
        FOREIGN KEY (creator_user_id) REFERENCES users(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_nb_province
        FOREIGN KEY (province_id) REFERENCES provinces(id),

    CONSTRAINT fk_nb_district
        FOREIGN KEY (district_id) REFERENCES districts(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_nb_neighborhood
        FOREIGN KEY (neighborhood_id) REFERENCES neighborhoods(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_nb_order
        FOREIGN KEY (order_id) REFERENCES orders(id)
        ON DELETE SET NULL,

    INDEX idx_nb_location (province_id, district_id, neighborhood_id),
    INDEX idx_nb_status (status),
    INDEX idx_nb_product (product_id)
);

CREATE TABLE IF NOT EXISTS neighborhood_basket_members (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    basket_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    status ENUM('active', 'cancelled', 'paid') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_nbm_basket
        FOREIGN KEY (basket_id) REFERENCES neighborhood_baskets(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_nbm_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,

    UNIQUE KEY uq_nbm_basket_user (basket_id, user_id),
    INDEX idx_nbm_basket (basket_id)
);

CREATE TABLE IF NOT EXISTS neighborhood_basket_payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    basket_member_id BIGINT UNSIGNED NOT NULL,
    wallet_transaction_id BIGINT UNSIGNED NULL,
    amount DECIMAL(12,2) NOT NULL,
    status ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_nbp_member
        FOREIGN KEY (basket_member_id) REFERENCES neighborhood_basket_members(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_nbp_wallet_transaction
        FOREIGN KEY (wallet_transaction_id) REFERENCES wallet_transactions(id)
        ON DELETE SET NULL,

    INDEX idx_nbp_member (basket_member_id),
    INDEX idx_nbp_status (status)
);

CREATE TABLE IF NOT EXISTS producer_performance_daily (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    producer_id BIGINT UNSIGNED NOT NULL,
    report_date DATE NOT NULL,
    total_orders INT UNSIGNED NOT NULL DEFAULT 0,
    total_revenue DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total_products_sold DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    product_view_count INT UNSIGNED NOT NULL DEFAULT 0,
    profile_view_count INT UNSIGNED NOT NULL DEFAULT 0,
    favorite_count INT UNSIGNED NOT NULL DEFAULT 0,
    new_follower_count INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_performance_producer
        FOREIGN KEY (producer_id) REFERENCES users(id)
        ON DELETE CASCADE,

    UNIQUE KEY uq_performance_producer_date (producer_id, report_date)
);

CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    action VARCHAR(120) NOT NULL,
    entity_type VARCHAR(120) NULL,
    entity_id BIGINT UNSIGNED NULL,
    description TEXT NULL,
    ip_address VARCHAR(45) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_audit_logs_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE SET NULL,

    INDEX idx_audit_action (action),
    INDEX idx_audit_entity (entity_type, entity_id)
);