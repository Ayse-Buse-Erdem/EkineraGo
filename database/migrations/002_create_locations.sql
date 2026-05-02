USE ekinerago;

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS provinces (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plate_code INT UNSIGNED NOT NULL UNIQUE,
    name VARCHAR(80) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS districts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    province_id INT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,

    CONSTRAINT fk_districts_province
        FOREIGN KEY (province_id) REFERENCES provinces(id)
        ON DELETE CASCADE,

    UNIQUE KEY uq_district_province_name (province_id, name)
);

CREATE TABLE IF NOT EXISTS neighborhoods (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    district_id INT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,

    CONSTRAINT fk_neighborhoods_district
        FOREIGN KEY (district_id) REFERENCES districts(id)
        ON DELETE CASCADE,

    UNIQUE KEY uq_neighborhood_district_name (district_id, name)
);

CREATE TABLE IF NOT EXISTS addresses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    label VARCHAR(80) NOT NULL DEFAULT 'Adres',
    recipient_name VARCHAR(120) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    province_id INT UNSIGNED NOT NULL,
    district_id INT UNSIGNED NOT NULL,
    neighborhood_id INT UNSIGNED NULL,
    address_line TEXT NOT NULL,
    latitude DECIMAL(10,7) NULL,
    longitude DECIMAL(10,7) NULL,
    is_default BOOLEAN NOT NULL DEFAULT FALSE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_addresses_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_addresses_province
        FOREIGN KEY (province_id) REFERENCES provinces(id),

    CONSTRAINT fk_addresses_district
        FOREIGN KEY (district_id) REFERENCES districts(id),

    CONSTRAINT fk_addresses_neighborhood
        FOREIGN KEY (neighborhood_id) REFERENCES neighborhoods(id)
        ON DELETE SET NULL,

    INDEX idx_addresses_user (user_id),
    INDEX idx_addresses_location (province_id, district_id)
);