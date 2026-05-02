USE ekinerago;

SET NAMES utf8mb4;

-- Demo şifre: password

INSERT INTO users (
    role,
    full_name,
    email,
    password_hash,
    phone,
    whatsapp_phone,
    profile_photo,
    province_id,
    district_id,
    neighborhood_id,
    address_text,
    latitude,
    longitude,
    status,
    email_verified_at
)
VALUES
(
    'admin',
    'Admin Kullanıcı',
    'admin@ekinerago.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.',
    '5550000000',
    NULL,
    NULL,
    (SELECT id FROM provinces WHERE name = 'Kocaeli' LIMIT 1),
    (SELECT d.id FROM districts d JOIN provinces p ON p.id = d.province_id WHERE p.name = 'Kocaeli' AND d.name = 'İzmit' LIMIT 1),
    NULL,
    'İzmit / Kocaeli',
    NULL,
    NULL,
    'active',
    NOW()
),
(
    'consumer',
    'Demo Tüketici',
    'consumer@ekinerago.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.',
    '5551111111',
    '5551111111',
    NULL,
    (SELECT id FROM provinces WHERE name = 'İstanbul' LIMIT 1),
    (SELECT d.id FROM districts d JOIN provinces p ON p.id = d.province_id WHERE p.name = 'İstanbul' AND d.name = 'Kadıköy' LIMIT 1),
    NULL,
    'Kadıköy / İstanbul',
    NULL,
    NULL,
    'active',
    NOW()
)
ON DUPLICATE KEY UPDATE
    role = VALUES(role),
    full_name = VALUES(full_name),
    phone = VALUES(phone),
    whatsapp_phone = VALUES(whatsapp_phone),
    province_id = VALUES(province_id),
    district_id = VALUES(district_id),
    address_text = VALUES(address_text),
    status = VALUES(status),
    email_verified_at = VALUES(email_verified_at);

INSERT INTO wallets (user_id, balance)
SELECT id, 0.00
FROM users
WHERE email = 'admin@ekinerago.com'
ON DUPLICATE KEY UPDATE
    balance = VALUES(balance);

INSERT INTO wallets (user_id, balance)
SELECT id, 1000.00
FROM users
WHERE email = 'consumer@ekinerago.com'
ON DUPLICATE KEY UPDATE
    balance = VALUES(balance);

INSERT INTO consumer_profiles (user_id, default_address_id, bio)
SELECT id, NULL, 'Demo tüketici hesabı'
FROM users
WHERE email = 'consumer@ekinerago.com'
ON DUPLICATE KEY UPDATE
    bio = VALUES(bio);