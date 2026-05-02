USE ekinerago;

SET NAMES utf8mb4;

-- Demo üretici kullanıcısı
-- Şifre: password

INSERT INTO users (
    role,
    full_name,
    email,
    password_hash,
    phone,
    whatsapp_phone,
    province_id,
    district_id,
    address_text,
    status,
    email_verified_at
)
SELECT
    'producer',
    'Ekinera Demo Üretici',
    'producer@ekinerago.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.',
    '5552222222',
    '5552222222',
    p.id,
    d.id,
    'Demo üretici adresi',
    'active',
    NOW()
FROM provinces p
JOIN districts d ON d.province_id = p.id
WHERE p.name = 'İstanbul'
LIMIT 1
ON DUPLICATE KEY UPDATE
    full_name = VALUES(full_name),
    phone = VALUES(phone),
    whatsapp_phone = VALUES(whatsapp_phone),
    province_id = VALUES(province_id),
    district_id = VALUES(district_id),
    address_text = VALUES(address_text),
    status = VALUES(status);

-- Üretici profili
INSERT INTO producer_profiles (
    user_id,
    store_name,
    slug,
    description,
    logo_path,
    cover_photo_path,
    contact_email,
    contact_phone,
    contact_whatsapp,
    verification_status,
    average_rating,
    rating_count,
    total_orders,
    total_sales_amount,
    shipping_note
)
SELECT
    id,
    'Ekinera Demo Çiftliği',
    'ekinera-demo-ciftligi',
    'Doğrudan üreticiden tüketiciye taze meyve ve sebze satışı yapan demo üretici hesabı.',
    NULL,
    NULL,
    'producer@ekinerago.com',
    '5552222222',
    '5552222222',
    'verified',
    0.00,
    0,
    0,
    0.00,
    'Tüm Türkiye il ve ilçelerine gönderim yapılabilir.'
FROM users
WHERE email = 'producer@ekinerago.com'
ON DUPLICATE KEY UPDATE
    store_name = VALUES(store_name),
    description = VALUES(description),
    contact_email = VALUES(contact_email),
    contact_phone = VALUES(contact_phone),
    contact_whatsapp = VALUES(contact_whatsapp),
    verification_status = VALUES(verification_status),
    shipping_note = VALUES(shipping_note);

-- Üretici cüzdanı
INSERT INTO wallets (user_id, balance)
SELECT id, 0.00
FROM users
WHERE email = 'producer@ekinerago.com'
ON DUPLICATE KEY UPDATE
    balance = VALUES(balance);

-- Toplu gönderim kuralı
INSERT INTO producer_bulk_shipping_rules (
    producer_id,
    min_total_quantity,
    unit_type,
    ships_all_turkey,
    shipping_price,
    note,
    is_active
)
SELECT
    u.id,
    100.00,
    'kg',
    TRUE,
    150.00,
    '100 kg üzeri toplu alımda Türkiye geneline gönderim yapılabilir.',
    TRUE
FROM users u
WHERE u.email = 'producer@ekinerago.com'
AND NOT EXISTS (
    SELECT 1
    FROM producer_bulk_shipping_rules r
    WHERE r.producer_id = u.id
);

-- Üreticinin tüm il ve ilçelere gönderim yapabilmesi
INSERT INTO producer_shipping_regions (
    producer_id,
    province_id,
    district_id,
    shipping_price,
    min_order_amount,
    is_active
)
SELECT
    u.id,
    p.id,
    d.id,
    50.00,
    250.00,
    TRUE
FROM users u
JOIN provinces p
JOIN districts d ON d.province_id = p.id
WHERE u.email = 'producer@ekinerago.com'
AND NOT EXISTS (
    SELECT 1
    FROM producer_shipping_regions psr
    WHERE psr.producer_id = u.id
    AND psr.province_id = p.id
    AND psr.district_id = d.id
);