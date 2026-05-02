USE ekinerago;

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS preorder_reservations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    expected_harvest_date DATE NULL,
    status ENUM(
        'pending',
        'approved',
        'harvest_ready',
        'converted_to_order',
        'cancelled'
    ) NOT NULL DEFAULT 'pending',
    order_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_preorders_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_preorders_product
        FOREIGN KEY (product_id) REFERENCES products(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_preorders_order
        FOREIGN KEY (order_id) REFERENCES orders(id)
        ON DELETE SET NULL,

    INDEX idx_preorders_user (user_id),
    INDEX idx_preorders_product (product_id),
    INDEX idx_preorders_status (status)
);

CREATE TABLE IF NOT EXISTS demand_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    consumer_id BIGINT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NULL,
    product_name VARCHAR(160) NOT NULL,
    province_id INT UNSIGNED NOT NULL,
    district_id INT UNSIGNED NULL,
    desired_quantity DECIMAL(10,2) NULL,
    unit_type ENUM('kg', 'piece', 'bunch', 'box') NOT NULL DEFAULT 'kg',
    note TEXT NULL,
    status ENUM('open', 'responded', 'closed', 'expired') NOT NULL DEFAULT 'open',
    expires_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_demand_consumer
        FOREIGN KEY (consumer_id) REFERENCES users(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_demand_category
        FOREIGN KEY (category_id) REFERENCES categories(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_demand_province
        FOREIGN KEY (province_id) REFERENCES provinces(id),

    CONSTRAINT fk_demand_district
        FOREIGN KEY (district_id) REFERENCES districts(id)
        ON DELETE SET NULL,

    INDEX idx_demand_location (province_id, district_id),
    INDEX idx_demand_product_name (product_name),
    INDEX idx_demand_status (status)
);

CREATE TABLE IF NOT EXISTS demand_responses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    demand_request_id BIGINT UNSIGNED NOT NULL,
    producer_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NULL,
    message TEXT NULL,
    offered_price DECIMAL(12,2) NULL,
    available_quantity DECIMAL(10,2) NULL,
    status ENUM('sent', 'accepted', 'rejected') NOT NULL DEFAULT 'sent',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_demand_responses_request
        FOREIGN KEY (demand_request_id) REFERENCES demand_requests(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_demand_responses_producer
        FOREIGN KEY (producer_id) REFERENCES users(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_demand_responses_product
        FOREIGN KEY (product_id) REFERENCES products(id)
        ON DELETE SET NULL,

    INDEX idx_demand_responses_request (demand_request_id),
    INDEX idx_demand_responses_producer (producer_id)
);