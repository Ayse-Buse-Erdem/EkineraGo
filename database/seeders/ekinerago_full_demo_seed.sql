/*
  EkineraGo kapsamlı demo/sample seed dosyası
  Kullanım: phpMyAdmin > ekinerago veritabanı > SQL sekmesi > bu dosyayı içe aktar/çalıştır.

  Demo hesap şifresi: 123456

  Bu dosya şunları ekler/günceller:
  - Sebze / Meyve / Diğer ana kategorileri ve alt kategoriler
  - İl/ilçe örnekleri
  - Admin, tüketici ve üretici demo kullanıcıları
  - Üretici profilleri, tüketici profilleri, adresler, cüzdanlar
  - Ürünler, stok hareketleri, ürün görselleri
  - Sepet, sipariş, sipariş kalemleri, kargo kayıtları
  - Wallet hareketleri
  - Review/yorum, favori, takip, bildirim, stok alarmı, product view
  - Ön sipariş, talep/yanıt, kampanya, gönderim bölgeleri
  - Mahalle sepeti, performans ve audit log örnekleri
*/

CREATE DATABASE IF NOT EXISTS ekinerago CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ekinerago;
SET NAMES utf8mb4;

/* Güvenli küçük şema güncellemeleri */
ALTER TABLE categories
  MODIFY type ENUM('vegetable', 'fruit', 'other') NOT NULL DEFAULT 'other';

ALTER TABLE products
  MODIFY stock_quantity DECIMAL(12,3) NOT NULL DEFAULT 0.000,
  MODIFY min_preorder_quantity DECIMAL(12,3) NULL;

SET @has_min_preorder_unit := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'products'
    AND COLUMN_NAME = 'min_preorder_unit'
);
SET @sql_min_preorder_unit := IF(
  @has_min_preorder_unit = 0,
  'ALTER TABLE products ADD COLUMN min_preorder_unit ENUM(''kg'', ''g'', ''piece'') NULL DEFAULT ''kg'' AFTER min_preorder_quantity',
  'SELECT ''min_preorder_unit already exists'' AS info'
);
PREPARE stmt_min_preorder_unit FROM @sql_min_preorder_unit;
EXECUTE stmt_min_preorder_unit;
DEALLOCATE PREPARE stmt_min_preorder_unit;

ALTER TABLE product_inventory_movements
  MODIFY quantity DECIMAL(12,3) NOT NULL;
ALTER TABLE cart_items
  MODIFY quantity DECIMAL(12,3) NOT NULL;
ALTER TABLE order_items
  MODIFY quantity DECIMAL(12,3) NOT NULL;
ALTER TABLE preorder_reservations
  MODIFY quantity DECIMAL(12,3) NOT NULL;
ALTER TABLE demand_requests
  MODIFY desired_quantity DECIMAL(12,3) NULL;
ALTER TABLE demand_responses
  MODIFY available_quantity DECIMAL(12,3) NULL;
ALTER TABLE producer_bulk_shipping_rules
  MODIFY min_total_quantity DECIMAL(12,3) NOT NULL;
ALTER TABLE neighborhood_baskets
  MODIFY target_quantity DECIMAL(12,3) NOT NULL,
  MODIFY current_quantity DECIMAL(12,3) NOT NULL DEFAULT 0.000;
ALTER TABLE neighborhood_basket_members
  MODIFY quantity DECIMAL(12,3) NOT NULL;

/* Konum örnekleri */
INSERT INTO provinces (id, plate_code, name) VALUES
  (6, 6, 'Ankara'),
  (7, 7, 'Antalya'),
  (16, 16, 'Bursa'),
  (34, 34, 'İstanbul'),
  (35, 35, 'İzmir'),
  (41, 41, 'Kocaeli')
ON DUPLICATE KEY UPDATE
  plate_code = VALUES(plate_code),
  name = VALUES(name);

INSERT INTO districts (id, province_id, name) VALUES
  (6001, 6, 'Çankaya'),
  (7001, 7, 'Kumluca'),
  (7002, 7, 'Alanya'),
  (1601, 16, 'Nilüfer'),
  (3401, 34, 'Kadıköy'),
  (3501, 35, 'Seferihisar'),
  (4101, 41, 'İzmit')
ON DUPLICATE KEY UPDATE
  province_id = VALUES(province_id),
  name = VALUES(name);

/* Kategoriler: ana ve alt kategoriler */
INSERT INTO categories (id, parent_id, name, slug, type, is_active) VALUES
  (1, NULL, 'Sebze', 'sebze', 'vegetable', TRUE),
  (2, NULL, 'Meyve', 'meyve', 'fruit', TRUE),
  (3, NULL, 'Diğer', 'diger', 'other', TRUE)
ON DUPLICATE KEY UPDATE
  parent_id = VALUES(parent_id),
  name = VALUES(name),
  slug = VALUES(slug),
  type = VALUES(type),
  is_active = TRUE;

INSERT INTO categories (id, parent_id, name, slug, type, is_active) VALUES
  (101, 1, 'Domates', 'domates', 'vegetable', TRUE),
  (102, 1, 'Biber', 'biber', 'vegetable', TRUE),
  (103, 1, 'Patates', 'patates', 'vegetable', TRUE),
  (104, 1, 'Soğan', 'sogan', 'vegetable', TRUE),
  (105, 1, 'Salatalık', 'salatalik', 'vegetable', TRUE),
  (106, 1, 'Havuç', 'havuc', 'vegetable', TRUE),
  (107, 1, 'Marul', 'marul', 'vegetable', TRUE),
  (108, 1, 'Kapya Biber', 'kapya-biber', 'vegetable', TRUE),
  (201, 2, 'Elma', 'elma', 'fruit', TRUE),
  (202, 2, 'Armut', 'armut', 'fruit', TRUE),
  (203, 2, 'Çilek', 'cilek', 'fruit', TRUE),
  (204, 2, 'Mandalina', 'mandalina', 'fruit', TRUE),
  (205, 2, 'Portakal', 'portakal', 'fruit', TRUE),
  (206, 2, 'Üzüm', 'uzum', 'fruit', TRUE),
  (207, 2, 'Limon', 'limon', 'fruit', TRUE),
  (301, 3, 'Bal', 'bal', 'other', TRUE),
  (302, 3, 'Yumurta', 'yumurta', 'other', TRUE),
  (303, 3, 'Zeytin / Zeytinyağı', 'zeytin-zeytinyagi', 'other', TRUE),
  (304, 3, 'Süt Ürünleri', 'sut-urunleri', 'other', TRUE),
  (305, 3, 'Bakliyat', 'bakliyat', 'other', TRUE),
  (306, 3, 'Diğer Ürün', 'diger-urun', 'other', TRUE)
ON DUPLICATE KEY UPDATE
  parent_id = VALUES(parent_id),
  name = VALUES(name),
  slug = VALUES(slug),
  type = VALUES(type),
  is_active = TRUE;

/* Kullanıcılar — tüm demo hesapların şifresi: 123456 */
INSERT INTO users (
  role, full_name, email, password_hash, phone, whatsapp_phone,
  profile_photo, province_id, district_id, neighborhood_id, address_text,
  latitude, longitude, status, email_verified_at, last_login_at
) VALUES
  ('admin', 'Demo Admin', 'admin@demo.com', '$2y$12$pxDUzCKrBelOzw1zCfV6X./969R.jmu6sgPPxD1vywmmf.PykMQru', '5550000000', NULL, NULL, 41, 4101, NULL, 'İzmit / Kocaeli', NULL, NULL, 'active', NOW(), NOW()),
  ('consumer', 'Demo Tüketici', 'consumer@demo.com', '$2y$12$pxDUzCKrBelOzw1zCfV6X./969R.jmu6sgPPxD1vywmmf.PykMQru', '5551111111', '5551111111', NULL, 34, 3401, NULL, 'Kadıköy / İstanbul', NULL, NULL, 'active', NOW(), NOW()),
  ('consumer', 'Ayşe Yılmaz', 'ayse@demo.com', '$2y$12$pxDUzCKrBelOzw1zCfV6X./969R.jmu6sgPPxD1vywmmf.PykMQru', '5552222222', '5552222222', NULL, 34, 3401, NULL, 'Kadıköy / İstanbul', NULL, NULL, 'active', NOW(), NOW()),
  ('consumer', 'Mehmet Kaya', 'mehmet@demo.com', '$2y$12$pxDUzCKrBelOzw1zCfV6X./969R.jmu6sgPPxD1vywmmf.PykMQru', '5553333333', '5553333333', NULL, 6, 6001, NULL, 'Çankaya / Ankara', NULL, NULL, 'active', NOW(), NOW()),
  ('consumer', 'Zeynep Demir', 'zeynep@demo.com', '$2y$12$pxDUzCKrBelOzw1zCfV6X./969R.jmu6sgPPxD1vywmmf.PykMQru', '5554444444', '5554444444', NULL, 35, 3501, NULL, 'Seferihisar / İzmir', NULL, NULL, 'active', NOW(), NOW()),
  ('producer', 'Ahmet Çiftçi', 'producer@demo.com', '$2y$12$pxDUzCKrBelOzw1zCfV6X./969R.jmu6sgPPxD1vywmmf.PykMQru', '5555555555', '5555555555', NULL, 7, 7001, NULL, 'Kumluca / Antalya', NULL, NULL, 'active', NOW(), NOW()),
  ('producer', 'Elif Ege', 'ege@demo.com', '$2y$12$pxDUzCKrBelOzw1zCfV6X./969R.jmu6sgPPxD1vywmmf.PykMQru', '5556666666', '5556666666', NULL, 35, 3501, NULL, 'Seferihisar / İzmir', NULL, NULL, 'active', NOW(), NOW()),
  ('producer', 'Mustafa Bahçıvan', 'bursa@demo.com', '$2y$12$pxDUzCKrBelOzw1zCfV6X./969R.jmu6sgPPxD1vywmmf.PykMQru', '5557777777', '5557777777', NULL, 16, 1601, NULL, 'Nilüfer / Bursa', NULL, NULL, 'active', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  role = VALUES(role),
  full_name = VALUES(full_name),
  phone = VALUES(phone),
  whatsapp_phone = VALUES(whatsapp_phone),
  province_id = VALUES(province_id),
  district_id = VALUES(district_id),
  address_text = VALUES(address_text),
  status = VALUES(status),
  email_verified_at = VALUES(email_verified_at),
  last_login_at = VALUES(last_login_at);

/* Profiller */
INSERT INTO consumer_profiles (user_id, default_address_id, bio)
SELECT id, 9101, 'Demo tüketici hesabı: sepet, sipariş, favori ve yorum testleri için.' FROM users WHERE email = 'consumer@demo.com'
ON DUPLICATE KEY UPDATE default_address_id = VALUES(default_address_id), bio = VALUES(bio);

INSERT INTO consumer_profiles (user_id, default_address_id, bio)
SELECT id, 9102, 'İstanbul Kadıköy’de yaşayan demo tüketici.' FROM users WHERE email = 'ayse@demo.com'
ON DUPLICATE KEY UPDATE default_address_id = VALUES(default_address_id), bio = VALUES(bio);

INSERT INTO consumer_profiles (user_id, default_address_id, bio)
SELECT id, 9103, 'Ankara için taze ürün talebi oluşturan demo tüketici.' FROM users WHERE email = 'mehmet@demo.com'
ON DUPLICATE KEY UPDATE default_address_id = VALUES(default_address_id), bio = VALUES(bio);

INSERT INTO consumer_profiles (user_id, default_address_id, bio)
SELECT id, 9104, 'Ön sipariş ve mahalle sepeti testleri için demo tüketici.' FROM users WHERE email = 'zeynep@demo.com'
ON DUPLICATE KEY UPDATE default_address_id = VALUES(default_address_id), bio = VALUES(bio);

INSERT INTO producer_profiles (
  user_id, store_name, slug, description, logo_path, cover_photo_path,
  contact_email, contact_phone, contact_whatsapp, verification_status,
  shipping_note
)
SELECT id, 'Kumluca Bereket Çiftliği', 'kumluca-bereket-ciftligi',
       'Antalya Kumluca’da domates, biber ve çilek üretimi yapan aile çiftliği. Hasat sonrası hızlı gönderim yapar.',
       NULL, NULL, 'producer@demo.com', '5555555555', '5555555555', 'verified',
       'Antalya içi ertesi gün, şehir dışı soğuk zincirli kargo.'
FROM users WHERE email = 'producer@demo.com'
ON DUPLICATE KEY UPDATE
  store_name = VALUES(store_name), slug = VALUES(slug), description = VALUES(description),
  contact_email = VALUES(contact_email), contact_phone = VALUES(contact_phone),
  contact_whatsapp = VALUES(contact_whatsapp), verification_status = VALUES(verification_status),
  shipping_note = VALUES(shipping_note);

INSERT INTO producer_profiles (
  user_id, store_name, slug, description, logo_path, cover_photo_path,
  contact_email, contact_phone, contact_whatsapp, verification_status,
  shipping_note
)
SELECT id, 'Ege Mandalina Bahçesi', 'ege-mandalina-bahcesi',
       'İzmir Seferihisar’da mandalina, zeytin ve zeytinyağı üreten küçük üretici.',
       NULL, NULL, 'ege@demo.com', '5556666666', '5556666666', 'verified',
       'İzmir içi elden teslim, diğer illere haftada iki gün kargo.'
FROM users WHERE email = 'ege@demo.com'
ON DUPLICATE KEY UPDATE
  store_name = VALUES(store_name), slug = VALUES(slug), description = VALUES(description),
  contact_email = VALUES(contact_email), contact_phone = VALUES(contact_phone),
  contact_whatsapp = VALUES(contact_whatsapp), verification_status = VALUES(verification_status),
  shipping_note = VALUES(shipping_note);

INSERT INTO producer_profiles (
  user_id, store_name, slug, description, logo_path, cover_photo_path,
  contact_email, contact_phone, contact_whatsapp, verification_status,
  shipping_note
)
SELECT id, 'Bursa Doğal Bahçe', 'bursa-dogal-bahce',
       'Bursa Nilüfer’de elma, armut, yumurta ve doğal ürünler sunan üretici.',
       NULL, NULL, 'bursa@demo.com', '5557777777', '5557777777', 'pending',
       'Bursa ve çevre illere haftalık toplu gönderim.'
FROM users WHERE email = 'bursa@demo.com'
ON DUPLICATE KEY UPDATE
  store_name = VALUES(store_name), slug = VALUES(slug), description = VALUES(description),
  contact_email = VALUES(contact_email), contact_phone = VALUES(contact_phone),
  contact_whatsapp = VALUES(contact_whatsapp), verification_status = VALUES(verification_status),
  shipping_note = VALUES(shipping_note);

/* Adresler */
INSERT INTO addresses (id, user_id, label, recipient_name, phone, province_id, district_id, neighborhood_id, address_line, is_default)
SELECT 9101, id, 'Ev', 'Demo Tüketici', '5551111111', 34, 3401, NULL, 'Moda Mahallesi, Kadıköy / İstanbul', TRUE FROM users WHERE email = 'consumer@demo.com'
ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), recipient_name = VALUES(recipient_name), phone = VALUES(phone), province_id = VALUES(province_id), district_id = VALUES(district_id), address_line = VALUES(address_line), is_default = TRUE;

INSERT INTO addresses (id, user_id, label, recipient_name, phone, province_id, district_id, neighborhood_id, address_line, is_default)
SELECT 9102, id, 'Ev', 'Ayşe Yılmaz', '5552222222', 34, 3401, NULL, 'Caferağa Mahallesi, Kadıköy / İstanbul', TRUE FROM users WHERE email = 'ayse@demo.com'
ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), recipient_name = VALUES(recipient_name), phone = VALUES(phone), province_id = VALUES(province_id), district_id = VALUES(district_id), address_line = VALUES(address_line), is_default = TRUE;

INSERT INTO addresses (id, user_id, label, recipient_name, phone, province_id, district_id, neighborhood_id, address_line, is_default)
SELECT 9103, id, 'Ofis', 'Mehmet Kaya', '5553333333', 6, 6001, NULL, 'Kızılay, Çankaya / Ankara', TRUE FROM users WHERE email = 'mehmet@demo.com'
ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), recipient_name = VALUES(recipient_name), phone = VALUES(phone), province_id = VALUES(province_id), district_id = VALUES(district_id), address_line = VALUES(address_line), is_default = TRUE;

INSERT INTO addresses (id, user_id, label, recipient_name, phone, province_id, district_id, neighborhood_id, address_line, is_default)
SELECT 9104, id, 'Ev', 'Zeynep Demir', '5554444444', 35, 3501, NULL, 'Sığacık, Seferihisar / İzmir', TRUE FROM users WHERE email = 'zeynep@demo.com'
ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), recipient_name = VALUES(recipient_name), phone = VALUES(phone), province_id = VALUES(province_id), district_id = VALUES(district_id), address_line = VALUES(address_line), is_default = TRUE;

/* Cüzdanlar */
INSERT INTO wallets (user_id, balance)
SELECT id, 0.00 FROM users WHERE email = 'admin@demo.com'
ON DUPLICATE KEY UPDATE balance = VALUES(balance);
INSERT INTO wallets (user_id, balance)
SELECT id, 1250.00 FROM users WHERE email = 'consumer@demo.com'
ON DUPLICATE KEY UPDATE balance = VALUES(balance);
INSERT INTO wallets (user_id, balance)
SELECT id, 850.00 FROM users WHERE email = 'ayse@demo.com'
ON DUPLICATE KEY UPDATE balance = VALUES(balance);
INSERT INTO wallets (user_id, balance)
SELECT id, 620.00 FROM users WHERE email = 'mehmet@demo.com'
ON DUPLICATE KEY UPDATE balance = VALUES(balance);
INSERT INTO wallets (user_id, balance)
SELECT id, 730.00 FROM users WHERE email = 'zeynep@demo.com'
ON DUPLICATE KEY UPDATE balance = VALUES(balance);
INSERT INTO wallets (user_id, balance)
SELECT id, 3250.00 FROM users WHERE email = 'producer@demo.com'
ON DUPLICATE KEY UPDATE balance = VALUES(balance);
INSERT INTO wallets (user_id, balance)
SELECT id, 1840.00 FROM users WHERE email = 'ege@demo.com'
ON DUPLICATE KEY UPDATE balance = VALUES(balance);
INSERT INTO wallets (user_id, balance)
SELECT id, 970.00 FROM users WHERE email = 'bursa@demo.com'
ON DUPLICATE KEY UPDATE balance = VALUES(balance);

/* Ürünler */
INSERT INTO products (
  id, producer_id, category_id, title, slug, description, unit_type,
  price, stock_quantity, harvest_date, is_preorder_enabled, preorder_deadline,
  min_preorder_quantity, min_preorder_unit, status, view_count, favorite_count
)
SELECT 9201, u.id, c.id, 'Kumluca Köy Domatesi', 'kumluca-koy-domatesi',
       'Sabah hasat edilmiş, iri ve sulu Kumluca domatesi.', 'kg',
       45.00, 250.750, DATE_SUB(CURDATE(), INTERVAL 2 DAY), FALSE, NULL,
       NULL, 'kg', 'active', 76, 3
FROM users u JOIN categories c ON c.slug = 'domates'
WHERE u.email = 'producer@demo.com'
ON DUPLICATE KEY UPDATE
  category_id = VALUES(category_id), title = VALUES(title), description = VALUES(description),
  unit_type = VALUES(unit_type), price = VALUES(price), stock_quantity = VALUES(stock_quantity),
  harvest_date = VALUES(harvest_date), is_preorder_enabled = VALUES(is_preorder_enabled),
  preorder_deadline = VALUES(preorder_deadline), min_preorder_quantity = VALUES(min_preorder_quantity),
  min_preorder_unit = VALUES(min_preorder_unit), status = VALUES(status),
  view_count = VALUES(view_count), favorite_count = VALUES(favorite_count);

INSERT INTO products (
  id, producer_id, category_id, title, slug, description, unit_type,
  price, stock_quantity, harvest_date, is_preorder_enabled, preorder_deadline,
  min_preorder_quantity, min_preorder_unit, status, view_count, favorite_count
)
SELECT 9202, u.id, c.id, 'Kapya Biber', 'kapya-biber',
       'Dolmalık ve közlemelik, kırmızı kapya biber.', 'kg',
       38.00, 120.500, DATE_SUB(CURDATE(), INTERVAL 1 DAY), FALSE, NULL,
       NULL, 'kg', 'active', 44, 2
FROM users u JOIN categories c ON c.slug = 'kapya-biber'
WHERE u.email = 'producer@demo.com'
ON DUPLICATE KEY UPDATE
  category_id = VALUES(category_id), title = VALUES(title), description = VALUES(description),
  unit_type = VALUES(unit_type), price = VALUES(price), stock_quantity = VALUES(stock_quantity),
  harvest_date = VALUES(harvest_date), is_preorder_enabled = VALUES(is_preorder_enabled),
  preorder_deadline = VALUES(preorder_deadline), min_preorder_quantity = VALUES(min_preorder_quantity),
  min_preorder_unit = VALUES(min_preorder_unit), status = VALUES(status),
  view_count = VALUES(view_count), favorite_count = VALUES(favorite_count);

INSERT INTO products (
  id, producer_id, category_id, title, slug, description, unit_type,
  price, stock_quantity, harvest_date, is_preorder_enabled, preorder_deadline,
  min_preorder_quantity, min_preorder_unit, status, view_count, favorite_count
)
SELECT 9203, u.id, c.id, 'Ön Sipariş Kumluca Çileği', 'on-siparis-kumluca-cilegi',
       'Hasat tarihi yaklaştığında gönderilecek ön sipariş çilek.', 'kg',
       85.00, 80.000, DATE_ADD(CURDATE(), INTERVAL 45 DAY), TRUE, DATE_ADD(CURDATE(), INTERVAL 30 DAY),
       500.000, 'g', 'active', 121, 5
FROM users u JOIN categories c ON c.slug = 'cilek'
WHERE u.email = 'producer@demo.com'
ON DUPLICATE KEY UPDATE
  category_id = VALUES(category_id), title = VALUES(title), description = VALUES(description),
  unit_type = VALUES(unit_type), price = VALUES(price), stock_quantity = VALUES(stock_quantity),
  harvest_date = VALUES(harvest_date), is_preorder_enabled = VALUES(is_preorder_enabled),
  preorder_deadline = VALUES(preorder_deadline), min_preorder_quantity = VALUES(min_preorder_quantity),
  min_preorder_unit = VALUES(min_preorder_unit), status = VALUES(status),
  view_count = VALUES(view_count), favorite_count = VALUES(favorite_count);

INSERT INTO products (
  id, producer_id, category_id, title, slug, description, unit_type,
  price, stock_quantity, harvest_date, is_preorder_enabled, preorder_deadline,
  min_preorder_quantity, min_preorder_unit, status, view_count, favorite_count
)
SELECT 9204, u.id, c.id, 'Seferihisar Mandalinası', 'seferihisar-mandalinasi',
       'Kokulu, ince kabuklu yerli mandalina.', 'kg',
       32.00, 310.000, DATE_SUB(CURDATE(), INTERVAL 5 DAY), FALSE, NULL,
       NULL, 'kg', 'active', 93, 4
FROM users u JOIN categories c ON c.slug = 'mandalina'
WHERE u.email = 'ege@demo.com'
ON DUPLICATE KEY UPDATE
  category_id = VALUES(category_id), title = VALUES(title), description = VALUES(description),
  unit_type = VALUES(unit_type), price = VALUES(price), stock_quantity = VALUES(stock_quantity),
  harvest_date = VALUES(harvest_date), is_preorder_enabled = VALUES(is_preorder_enabled),
  preorder_deadline = VALUES(preorder_deadline), min_preorder_quantity = VALUES(min_preorder_quantity),
  min_preorder_unit = VALUES(min_preorder_unit), status = VALUES(status),
  view_count = VALUES(view_count), favorite_count = VALUES(favorite_count);

INSERT INTO products (
  id, producer_id, category_id, title, slug, description, unit_type,
  price, stock_quantity, harvest_date, is_preorder_enabled, preorder_deadline,
  min_preorder_quantity, min_preorder_unit, status, view_count, favorite_count
)
SELECT 9205, u.id, c.id, 'Soğuk Sıkım Zeytinyağı', 'soguk-sikim-zeytinyagi',
       '1 litrelik cam şişede erken hasat zeytinyağı.', 'piece',
       280.00, 55.000, DATE_SUB(CURDATE(), INTERVAL 40 DAY), FALSE, NULL,
       NULL, 'piece', 'active', 68, 6
FROM users u JOIN categories c ON c.slug = 'zeytin-zeytinyagi'
WHERE u.email = 'ege@demo.com'
ON DUPLICATE KEY UPDATE
  category_id = VALUES(category_id), title = VALUES(title), description = VALUES(description),
  unit_type = VALUES(unit_type), price = VALUES(price), stock_quantity = VALUES(stock_quantity),
  harvest_date = VALUES(harvest_date), is_preorder_enabled = VALUES(is_preorder_enabled),
  preorder_deadline = VALUES(preorder_deadline), min_preorder_quantity = VALUES(min_preorder_quantity),
  min_preorder_unit = VALUES(min_preorder_unit), status = VALUES(status),
  view_count = VALUES(view_count), favorite_count = VALUES(favorite_count);

INSERT INTO products (
  id, producer_id, category_id, title, slug, description, unit_type,
  price, stock_quantity, harvest_date, is_preorder_enabled, preorder_deadline,
  min_preorder_quantity, min_preorder_unit, status, view_count, favorite_count
)
SELECT 9206, u.id, c.id, 'Bursa Bahçe Elması', 'bursa-bahce-elmasi',
       'İlaçsız yetiştirilmiş karışık boy bahçe elması.', 'kg',
       34.00, 210.000, DATE_SUB(CURDATE(), INTERVAL 8 DAY), FALSE, NULL,
       NULL, 'kg', 'active', 39, 1
FROM users u JOIN categories c ON c.slug = 'elma'
WHERE u.email = 'bursa@demo.com'
ON DUPLICATE KEY UPDATE
  category_id = VALUES(category_id), title = VALUES(title), description = VALUES(description),
  unit_type = VALUES(unit_type), price = VALUES(price), stock_quantity = VALUES(stock_quantity),
  harvest_date = VALUES(harvest_date), is_preorder_enabled = VALUES(is_preorder_enabled),
  preorder_deadline = VALUES(preorder_deadline), min_preorder_quantity = VALUES(min_preorder_quantity),
  min_preorder_unit = VALUES(min_preorder_unit), status = VALUES(status),
  view_count = VALUES(view_count), favorite_count = VALUES(favorite_count);

INSERT INTO products (
  id, producer_id, category_id, title, slug, description, unit_type,
  price, stock_quantity, harvest_date, is_preorder_enabled, preorder_deadline,
  min_preorder_quantity, min_preorder_unit, status, view_count, favorite_count
)
SELECT 9207, u.id, c.id, 'Köy Yumurtası', 'koy-yumurtasi',
       'Gezen tavuk yumurtası. Stok testleri için şu an tükenmiş görünecek.', 'piece',
       7.50, 0.000, CURDATE(), FALSE, NULL,
       NULL, 'piece', 'sold_out', 22, 1
FROM users u JOIN categories c ON c.slug = 'yumurta'
WHERE u.email = 'bursa@demo.com'
ON DUPLICATE KEY UPDATE
  category_id = VALUES(category_id), title = VALUES(title), description = VALUES(description),
  unit_type = VALUES(unit_type), price = VALUES(price), stock_quantity = VALUES(stock_quantity),
  harvest_date = VALUES(harvest_date), is_preorder_enabled = VALUES(is_preorder_enabled),
  preorder_deadline = VALUES(preorder_deadline), min_preorder_quantity = VALUES(min_preorder_quantity),
  min_preorder_unit = VALUES(min_preorder_unit), status = VALUES(status),
  view_count = VALUES(view_count), favorite_count = VALUES(favorite_count);

INSERT INTO products (
  id, producer_id, category_id, title, slug, description, unit_type,
  price, stock_quantity, harvest_date, is_preorder_enabled, preorder_deadline,
  min_preorder_quantity, min_preorder_unit, status, view_count, favorite_count
)
SELECT 9208, u.id, c.id, 'Çam Balı Kavanoz', 'cam-bali-kavanoz',
       '850 gramlık doğal çam balı kavanozu.', 'piece',
       220.00, 40.000, DATE_SUB(CURDATE(), INTERVAL 90 DAY), FALSE, NULL,
       NULL, 'piece', 'active', 57, 4
FROM users u JOIN categories c ON c.slug = 'bal'
WHERE u.email = 'producer@demo.com'
ON DUPLICATE KEY UPDATE
  category_id = VALUES(category_id), title = VALUES(title), description = VALUES(description),
  unit_type = VALUES(unit_type), price = VALUES(price), stock_quantity = VALUES(stock_quantity),
  harvest_date = VALUES(harvest_date), is_preorder_enabled = VALUES(is_preorder_enabled),
  preorder_deadline = VALUES(preorder_deadline), min_preorder_quantity = VALUES(min_preorder_quantity),
  min_preorder_unit = VALUES(min_preorder_unit), status = VALUES(status),
  view_count = VALUES(view_count), favorite_count = VALUES(favorite_count);

/* Ürün stok hareketleri */
INSERT INTO product_inventory_movements (product_id, movement_type, quantity, order_item_id, note)
SELECT p.id, 'initial', p.stock_quantity, NULL, 'Demo başlangıç stoğu'
FROM products p
WHERE p.id BETWEEN 9201 AND 9208
  AND NOT EXISTS (
    SELECT 1 FROM product_inventory_movements pim
    WHERE pim.product_id = p.id AND pim.movement_type = 'initial'
  );

/* Ürün görsel kayıtları: dosyalar yoksa sadece path kaydıdır, sayfa kırılmazsa placeholder gibi çalışır */
INSERT INTO product_images (product_id, image_path, sort_order, is_cover)
SELECT p.id, CONCAT('uploads/products/demo-', p.slug, '.jpg'), 1, TRUE
FROM products p
WHERE p.id BETWEEN 9201 AND 9208
  AND NOT EXISTS (SELECT 1 FROM product_images pi WHERE pi.product_id = p.id);

/* Aktif sepet örnekleri */
INSERT INTO carts (id, user_id, status)
SELECT 9301, id, 'active' FROM users WHERE email = 'ayse@demo.com'
ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), status = VALUES(status);

INSERT INTO cart_items (id, cart_id, product_id, quantity)
SELECT 9301, 9301, p.id, 3.500
FROM products p JOIN users u ON u.id = p.producer_id
WHERE u.email = 'producer@demo.com' AND p.slug = 'kumluca-koy-domatesi'
ON DUPLICATE KEY UPDATE quantity = VALUES(quantity);

INSERT INTO cart_items (id, cart_id, product_id, quantity)
SELECT 9302, 9301, p.id, 1.000
FROM products p JOIN users u ON u.id = p.producer_id
WHERE u.email = 'ege@demo.com' AND p.slug = 'soguk-sikim-zeytinyagi'
ON DUPLICATE KEY UPDATE quantity = VALUES(quantity);

/* Siparişler */
INSERT INTO orders (
  id, order_no, consumer_id, producer_id, address_id, order_type,
  subtotal, shipping_fee, discount_total, total_amount,
  payment_method, payment_status, order_status, customer_note, producer_note, tracking_no, created_at
)
SELECT 9401, 'EKG-DEMO-9401', c.id, p.id, 9102, 'normal',
       256.00, 25.00, 0.00, 281.00,
       'virtual_balance', 'paid', 'delivered', 'Domatesler sert olsun lütfen.', 'Teslim edildi.', 'TRK-DEMO-9401', DATE_SUB(NOW(), INTERVAL 14 DAY)
FROM users c JOIN users p ON p.email = 'producer@demo.com'
WHERE c.email = 'ayse@demo.com'
ON DUPLICATE KEY UPDATE
  consumer_id = VALUES(consumer_id), producer_id = VALUES(producer_id), address_id = VALUES(address_id),
  subtotal = VALUES(subtotal), shipping_fee = VALUES(shipping_fee), total_amount = VALUES(total_amount),
  payment_status = VALUES(payment_status), order_status = VALUES(order_status), tracking_no = VALUES(tracking_no);

INSERT INTO orders (
  id, order_no, consumer_id, producer_id, address_id, order_type,
  subtotal, shipping_fee, discount_total, total_amount,
  payment_method, payment_status, order_status, customer_note, producer_note, tracking_no, created_at
)
SELECT 9402, 'EKG-DEMO-9402', c.id, p.id, 9103, 'normal',
       160.00, 30.00, 0.00, 190.00,
       'virtual_balance', 'paid', 'shipped', 'Mandalinalar ofise teslim.', 'Kargoya verildi.', 'TRK-DEMO-9402', DATE_SUB(NOW(), INTERVAL 4 DAY)
FROM users c JOIN users p ON p.email = 'ege@demo.com'
WHERE c.email = 'mehmet@demo.com'
ON DUPLICATE KEY UPDATE
  consumer_id = VALUES(consumer_id), producer_id = VALUES(producer_id), address_id = VALUES(address_id),
  subtotal = VALUES(subtotal), shipping_fee = VALUES(shipping_fee), total_amount = VALUES(total_amount),
  payment_status = VALUES(payment_status), order_status = VALUES(order_status), tracking_no = VALUES(tracking_no);

INSERT INTO orders (
  id, order_no, consumer_id, producer_id, address_id, order_type,
  subtotal, shipping_fee, discount_total, total_amount,
  payment_method, payment_status, order_status, customer_note, producer_note, tracking_no, created_at
)
SELECT 9403, 'EKG-DEMO-9403', c.id, p.id, 9101, 'normal',
       220.00, 20.00, 0.00, 240.00,
       'virtual_balance', 'paid', 'delivered', 'Bal kırılmadan paketlensin.', 'Cam kavanoz korumalı gönderildi.', 'TRK-DEMO-9403', DATE_SUB(NOW(), INTERVAL 21 DAY)
FROM users c JOIN users p ON p.email = 'producer@demo.com'
WHERE c.email = 'consumer@demo.com'
ON DUPLICATE KEY UPDATE
  consumer_id = VALUES(consumer_id), producer_id = VALUES(producer_id), address_id = VALUES(address_id),
  subtotal = VALUES(subtotal), shipping_fee = VALUES(shipping_fee), total_amount = VALUES(total_amount),
  payment_status = VALUES(payment_status), order_status = VALUES(order_status), tracking_no = VALUES(tracking_no);

INSERT INTO orders (
  id, order_no, consumer_id, producer_id, address_id, order_type,
  subtotal, shipping_fee, discount_total, total_amount,
  payment_method, payment_status, order_status, customer_note, producer_note, tracking_no, created_at
)
SELECT 9404, 'EKG-DEMO-9404', c.id, p.id, 9104, 'preorder',
       212.50, 15.00, 0.00, 227.50,
       'virtual_balance', 'paid', 'pending', 'Çilek hasat zamanı haber verilsin.', 'Ön sipariş alındı.', 'TRK-DEMO-9404', DATE_SUB(NOW(), INTERVAL 2 DAY)
FROM users c JOIN users p ON p.email = 'producer@demo.com'
WHERE c.email = 'zeynep@demo.com'
ON DUPLICATE KEY UPDATE
  consumer_id = VALUES(consumer_id), producer_id = VALUES(producer_id), address_id = VALUES(address_id),
  subtotal = VALUES(subtotal), shipping_fee = VALUES(shipping_fee), total_amount = VALUES(total_amount),
  payment_status = VALUES(payment_status), order_status = VALUES(order_status), tracking_no = VALUES(tracking_no);

/* Sipariş kalemleri */
INSERT INTO order_items (id, order_id, product_id, product_title_snapshot, unit_type_snapshot, quantity, unit_price, total_price, harvest_date_snapshot)
SELECT 9501, 9401, p.id, p.title, p.unit_type, 4.000, 45.00, 180.00, p.harvest_date
FROM products p JOIN users u ON u.id = p.producer_id
WHERE u.email = 'producer@demo.com' AND p.slug = 'kumluca-koy-domatesi'
ON DUPLICATE KEY UPDATE product_id = VALUES(product_id), product_title_snapshot = VALUES(product_title_snapshot), quantity = VALUES(quantity), unit_price = VALUES(unit_price), total_price = VALUES(total_price);

INSERT INTO order_items (id, order_id, product_id, product_title_snapshot, unit_type_snapshot, quantity, unit_price, total_price, harvest_date_snapshot)
SELECT 9502, 9401, p.id, p.title, p.unit_type, 2.000, 38.00, 76.00, p.harvest_date
FROM products p JOIN users u ON u.id = p.producer_id
WHERE u.email = 'producer@demo.com' AND p.slug = 'kapya-biber'
ON DUPLICATE KEY UPDATE product_id = VALUES(product_id), product_title_snapshot = VALUES(product_title_snapshot), quantity = VALUES(quantity), unit_price = VALUES(unit_price), total_price = VALUES(total_price);

INSERT INTO order_items (id, order_id, product_id, product_title_snapshot, unit_type_snapshot, quantity, unit_price, total_price, harvest_date_snapshot)
SELECT 9503, 9402, p.id, p.title, p.unit_type, 5.000, 32.00, 160.00, p.harvest_date
FROM products p JOIN users u ON u.id = p.producer_id
WHERE u.email = 'ege@demo.com' AND p.slug = 'seferihisar-mandalinasi'
ON DUPLICATE KEY UPDATE product_id = VALUES(product_id), product_title_snapshot = VALUES(product_title_snapshot), quantity = VALUES(quantity), unit_price = VALUES(unit_price), total_price = VALUES(total_price);

INSERT INTO order_items (id, order_id, product_id, product_title_snapshot, unit_type_snapshot, quantity, unit_price, total_price, harvest_date_snapshot)
SELECT 9504, 9403, p.id, p.title, p.unit_type, 1.000, 220.00, 220.00, p.harvest_date
FROM products p JOIN users u ON u.id = p.producer_id
WHERE u.email = 'producer@demo.com' AND p.slug = 'cam-bali-kavanoz'
ON DUPLICATE KEY UPDATE product_id = VALUES(product_id), product_title_snapshot = VALUES(product_title_snapshot), quantity = VALUES(quantity), unit_price = VALUES(unit_price), total_price = VALUES(total_price);

INSERT INTO order_items (id, order_id, product_id, product_title_snapshot, unit_type_snapshot, quantity, unit_price, total_price, harvest_date_snapshot)
SELECT 9505, 9404, p.id, p.title, p.unit_type, 2.500, 85.00, 212.50, p.harvest_date
FROM products p JOIN users u ON u.id = p.producer_id
WHERE u.email = 'producer@demo.com' AND p.slug = 'on-siparis-kumluca-cilegi'
ON DUPLICATE KEY UPDATE product_id = VALUES(product_id), product_title_snapshot = VALUES(product_title_snapshot), quantity = VALUES(quantity), unit_price = VALUES(unit_price), total_price = VALUES(total_price);

/* Kargo kayıtları */
INSERT INTO shipments (id, order_id, cargo_company, tracking_no, shipment_status, shipped_at, delivered_at)
VALUES
  (9601, 9401, 'EkineraGo Yerel Kurye', 'TRK-DEMO-9401', 'delivered', DATE_SUB(NOW(), INTERVAL 13 DAY), DATE_SUB(NOW(), INTERVAL 12 DAY)),
  (9602, 9402, 'Demo Kargo', 'TRK-DEMO-9402', 'in_transit', DATE_SUB(NOW(), INTERVAL 2 DAY), NULL),
  (9603, 9403, 'EkineraGo Yerel Kurye', 'TRK-DEMO-9403', 'delivered', DATE_SUB(NOW(), INTERVAL 20 DAY), DATE_SUB(NOW(), INTERVAL 19 DAY)),
  (9604, 9404, NULL, 'TRK-DEMO-9404', 'not_shipped', NULL, NULL)
ON DUPLICATE KEY UPDATE
  order_id = VALUES(order_id), cargo_company = VALUES(cargo_company), shipment_status = VALUES(shipment_status),
  shipped_at = VALUES(shipped_at), delivered_at = VALUES(delivered_at);

/* Cüzdan hareketleri */
INSERT INTO wallet_transactions (id, user_id, transaction_type, amount, balance_after, order_id, description, created_at)
SELECT 9701, id, 'deposit', 1500.00, 1500.00, NULL, 'Demo bakiye yükleme', DATE_SUB(NOW(), INTERVAL 30 DAY)
FROM users WHERE email = 'ayse@demo.com'
ON DUPLICATE KEY UPDATE amount = VALUES(amount), balance_after = VALUES(balance_after), description = VALUES(description);

INSERT INTO wallet_transactions (id, user_id, transaction_type, amount, balance_after, order_id, description, created_at)
SELECT 9702, id, 'purchase', -281.00, 1219.00, 9401, 'Kumluca Bereket Çiftliği siparişi', DATE_SUB(NOW(), INTERVAL 14 DAY)
FROM users WHERE email = 'ayse@demo.com'
ON DUPLICATE KEY UPDATE amount = VALUES(amount), balance_after = VALUES(balance_after), order_id = VALUES(order_id), description = VALUES(description);

INSERT INTO wallet_transactions (id, user_id, transaction_type, amount, balance_after, order_id, description, created_at)
SELECT 9703, id, 'producer_income', 281.00, 281.00, 9401, 'Demo sipariş üretici geliri', DATE_SUB(NOW(), INTERVAL 14 DAY)
FROM users WHERE email = 'producer@demo.com'
ON DUPLICATE KEY UPDATE amount = VALUES(amount), balance_after = VALUES(balance_after), order_id = VALUES(order_id), description = VALUES(description);

INSERT INTO wallet_transactions (id, user_id, transaction_type, amount, balance_after, order_id, description, created_at)
SELECT 9704, id, 'deposit', 1000.00, 1000.00, NULL, 'Demo bakiye yükleme', DATE_SUB(NOW(), INTERVAL 10 DAY)
FROM users WHERE email = 'mehmet@demo.com'
ON DUPLICATE KEY UPDATE amount = VALUES(amount), balance_after = VALUES(balance_after), description = VALUES(description);

INSERT INTO wallet_transactions (id, user_id, transaction_type, amount, balance_after, order_id, description, created_at)
SELECT 9705, id, 'purchase', -190.00, 810.00, 9402, 'Ege Mandalina Bahçesi siparişi', DATE_SUB(NOW(), INTERVAL 4 DAY)
FROM users WHERE email = 'mehmet@demo.com'
ON DUPLICATE KEY UPDATE amount = VALUES(amount), balance_after = VALUES(balance_after), order_id = VALUES(order_id), description = VALUES(description);

INSERT INTO wallet_transactions (id, user_id, transaction_type, amount, balance_after, order_id, description, created_at)
SELECT 9706, id, 'purchase', -240.00, 760.00, 9403, 'Çam balı siparişi', DATE_SUB(NOW(), INTERVAL 21 DAY)
FROM users WHERE email = 'consumer@demo.com'
ON DUPLICATE KEY UPDATE amount = VALUES(amount), balance_after = VALUES(balance_after), order_id = VALUES(order_id), description = VALUES(description);

/* Yorumlar */
INSERT INTO reviews (order_item_id, consumer_id, producer_id, product_id, rating, comment, status, created_at)
SELECT oi.id, o.consumer_id, o.producer_id, oi.product_id, 5,
       'Domatesler çok tazeydi, paketleme de güzeldi.', 'visible', DATE_SUB(NOW(), INTERVAL 11 DAY)
FROM order_items oi JOIN orders o ON o.id = oi.order_id
WHERE oi.id = 9501
ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment), status = VALUES(status);

INSERT INTO reviews (order_item_id, consumer_id, producer_id, product_id, rating, comment, status, created_at)
SELECT oi.id, o.consumer_id, o.producer_id, oi.product_id, 4,
       'Biberler lezzetliydi, birkaç tanesi ezilmişti ama genel olarak iyi.', 'visible', DATE_SUB(NOW(), INTERVAL 11 DAY)
FROM order_items oi JOIN orders o ON o.id = oi.order_id
WHERE oi.id = 9502
ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment), status = VALUES(status);

INSERT INTO reviews (order_item_id, consumer_id, producer_id, product_id, rating, comment, status, created_at)
SELECT oi.id, o.consumer_id, o.producer_id, oi.product_id, 5,
       'Balın aroması çok güzel, tekrar alacağım.', 'visible', DATE_SUB(NOW(), INTERVAL 18 DAY)
FROM order_items oi JOIN orders o ON o.id = oi.order_id
WHERE oi.id = 9504
ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment), status = VALUES(status);

/* Ürün ve üretici puanlarını yorumlardan hesapla */
UPDATE products p
LEFT JOIN (
  SELECT product_id, AVG(rating) AS avg_rating, COUNT(*) AS rating_count
  FROM reviews
  WHERE status = 'visible' AND product_id IS NOT NULL
  GROUP BY product_id
) r ON r.product_id = p.id
SET p.average_rating = COALESCE(r.avg_rating, 0.00),
    p.rating_count = COALESCE(r.rating_count, 0)
WHERE p.id BETWEEN 9201 AND 9208;

UPDATE producer_profiles pp
LEFT JOIN (
  SELECT producer_id, AVG(rating) AS avg_rating, COUNT(*) AS rating_count
  FROM reviews
  WHERE status = 'visible'
  GROUP BY producer_id
) r ON r.producer_id = pp.user_id
LEFT JOIN (
  SELECT producer_id, COUNT(*) AS total_orders, SUM(total_amount) AS total_sales
  FROM orders
  WHERE payment_status = 'paid'
  GROUP BY producer_id
) o ON o.producer_id = pp.user_id
SET pp.average_rating = COALESCE(r.avg_rating, 0.00),
    pp.rating_count = COALESCE(r.rating_count, 0),
    pp.total_orders = COALESCE(o.total_orders, 0),
    pp.total_sales_amount = COALESCE(o.total_sales, 0.00)
WHERE pp.user_id IN (SELECT id FROM users WHERE email IN ('producer@demo.com', 'ege@demo.com', 'bursa@demo.com'));

/* Favoriler */
INSERT INTO favorites (user_id, product_id, created_at)
SELECT u.id, p.id, DATE_SUB(NOW(), INTERVAL 7 DAY)
FROM users u JOIN products p ON p.slug = 'kumluca-koy-domatesi'
WHERE u.email = 'ayse@demo.com'
ON DUPLICATE KEY UPDATE created_at = VALUES(created_at);

INSERT INTO favorites (user_id, product_id, created_at)
SELECT u.id, p.id, DATE_SUB(NOW(), INTERVAL 6 DAY)
FROM users u JOIN products p ON p.slug = 'on-siparis-kumluca-cilegi'
WHERE u.email = 'mehmet@demo.com'
ON DUPLICATE KEY UPDATE created_at = VALUES(created_at);

INSERT INTO favorites (user_id, product_id, created_at)
SELECT u.id, p.id, DATE_SUB(NOW(), INTERVAL 3 DAY)
FROM users u JOIN products p ON p.slug = 'soguk-sikim-zeytinyagi'
WHERE u.email = 'consumer@demo.com'
ON DUPLICATE KEY UPDATE created_at = VALUES(created_at);

/* Üretici takipleri */
INSERT INTO producer_follows (consumer_id, producer_id, created_at)
SELECT c.id, p.id, DATE_SUB(NOW(), INTERVAL 20 DAY)
FROM users c JOIN users p ON p.email = 'producer@demo.com'
WHERE c.email = 'ayse@demo.com'
ON DUPLICATE KEY UPDATE created_at = VALUES(created_at);

INSERT INTO producer_follows (consumer_id, producer_id, created_at)
SELECT c.id, p.id, DATE_SUB(NOW(), INTERVAL 12 DAY)
FROM users c JOIN users p ON p.email = 'ege@demo.com'
WHERE c.email = 'mehmet@demo.com'
ON DUPLICATE KEY UPDATE created_at = VALUES(created_at);

INSERT INTO producer_follows (consumer_id, producer_id, created_at)
SELECT c.id, p.id, DATE_SUB(NOW(), INTERVAL 8 DAY)
FROM users c JOIN users p ON p.email = 'producer@demo.com'
WHERE c.email = 'zeynep@demo.com'
ON DUPLICATE KEY UPDATE created_at = VALUES(created_at);

/* Bildirimler */
INSERT INTO notifications (id, user_id, type, title, message, data_json, is_read, created_at)
SELECT 9801, id, 'order_status_changed', 'Siparişiniz teslim edildi', 'EKG-DEMO-9401 numaralı sipariş teslim edildi. Yorum bırakabilirsiniz.', JSON_OBJECT('order_id', 9401), FALSE, DATE_SUB(NOW(), INTERVAL 11 DAY)
FROM users WHERE email = 'ayse@demo.com'
ON DUPLICATE KEY UPDATE title = VALUES(title), message = VALUES(message), data_json = VALUES(data_json), is_read = VALUES(is_read);

INSERT INTO notifications (id, user_id, type, title, message, data_json, is_read, created_at)
SELECT 9802, id, 'new_product_from_followed_producer', 'Takip ettiğiniz üreticide yeni ürün', 'Kumluca Bereket Çiftliği ön sipariş çilek açtı.', JSON_OBJECT('product_id', 9203), FALSE, DATE_SUB(NOW(), INTERVAL 3 DAY)
FROM users WHERE email = 'zeynep@demo.com'
ON DUPLICATE KEY UPDATE title = VALUES(title), message = VALUES(message), data_json = VALUES(data_json), is_read = VALUES(is_read);

INSERT INTO notifications (id, user_id, type, title, message, data_json, is_read, created_at)
SELECT 9803, id, 'order_status_changed', 'Yeni siparişiniz var', 'EKG-DEMO-9404 numaralı ön sipariş üretici panelinde bekliyor.', JSON_OBJECT('order_id', 9404), FALSE, DATE_SUB(NOW(), INTERVAL 2 DAY)
FROM users WHERE email = 'producer@demo.com'
ON DUPLICATE KEY UPDATE title = VALUES(title), message = VALUES(message), data_json = VALUES(data_json), is_read = VALUES(is_read);

/* Stok gelince haber ver */
INSERT INTO restock_alerts (id, user_id, product_id, status, created_at)
SELECT 9901, u.id, p.id, 'waiting', DATE_SUB(NOW(), INTERVAL 1 DAY)
FROM users u JOIN products p ON p.slug = 'koy-yumurtasi'
WHERE u.email = 'consumer@demo.com'
ON DUPLICATE KEY UPDATE status = VALUES(status), created_at = VALUES(created_at);

/* Ürün görüntülenme kayıtları */
INSERT INTO product_views (id, product_id, user_id, ip_hash, user_agent, created_at)
SELECT 10001, p.id, u.id, SHA2('127.0.0.1-ayse-domates', 256), 'Demo Browser', DATE_SUB(NOW(), INTERVAL 5 DAY)
FROM products p JOIN users u ON u.email = 'ayse@demo.com'
WHERE p.slug = 'kumluca-koy-domatesi'
ON DUPLICATE KEY UPDATE created_at = VALUES(created_at);

INSERT INTO product_views (id, product_id, user_id, ip_hash, user_agent, created_at)
SELECT 10002, p.id, u.id, SHA2('127.0.0.1-mehmet-cilek', 256), 'Demo Browser', DATE_SUB(NOW(), INTERVAL 4 DAY)
FROM products p JOIN users u ON u.email = 'mehmet@demo.com'
WHERE p.slug = 'on-siparis-kumluca-cilegi'
ON DUPLICATE KEY UPDATE created_at = VALUES(created_at);

/* Ön sipariş rezervasyonları */
INSERT INTO preorder_reservations (id, user_id, product_id, quantity, expected_harvest_date, status, order_id, created_at)
SELECT 10101, u.id, p.id, 2.500, p.harvest_date, 'pending', 9404, DATE_SUB(NOW(), INTERVAL 2 DAY)
FROM users u JOIN products p ON p.slug = 'on-siparis-kumluca-cilegi'
WHERE u.email = 'zeynep@demo.com'
ON DUPLICATE KEY UPDATE quantity = VALUES(quantity), expected_harvest_date = VALUES(expected_harvest_date), status = VALUES(status), order_id = VALUES(order_id);

INSERT INTO preorder_reservations (id, user_id, product_id, quantity, expected_harvest_date, status, order_id, created_at)
SELECT 10102, u.id, p.id, 3.000, p.harvest_date, 'approved', NULL, DATE_SUB(NOW(), INTERVAL 1 DAY)
FROM users u JOIN products p ON p.slug = 'on-siparis-kumluca-cilegi'
WHERE u.email = 'mehmet@demo.com'
ON DUPLICATE KEY UPDATE quantity = VALUES(quantity), expected_harvest_date = VALUES(expected_harvest_date), status = VALUES(status), order_id = VALUES(order_id);

/* Talep ve üretici yanıtları */
INSERT INTO demand_requests (id, consumer_id, category_id, product_name, province_id, district_id, desired_quantity, unit_type, note, status, expires_at, created_at)
SELECT 10201, u.id, c.id, 'Avokado', 6, 6001, 5.000, 'kg', 'Ankara Çankaya için haftaya teslim avokado arıyorum.', 'responded', DATE_ADD(NOW(), INTERVAL 20 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY)
FROM users u LEFT JOIN categories c ON c.slug = 'meyve'
WHERE u.email = 'mehmet@demo.com'
ON DUPLICATE KEY UPDATE product_name = VALUES(product_name), desired_quantity = VALUES(desired_quantity), note = VALUES(note), status = VALUES(status), expires_at = VALUES(expires_at);

INSERT INTO demand_responses (id, demand_request_id, producer_id, product_id, message, offered_price, available_quantity, status, created_at)
SELECT 10251, 10201, u.id, NULL, 'Önümüzdeki hafta 5 kg avokado tedarik edebiliriz.', 95.00, 5.000, 'sent', DATE_SUB(NOW(), INTERVAL 2 DAY)
FROM users u WHERE u.email = 'ege@demo.com'
ON DUPLICATE KEY UPDATE message = VALUES(message), offered_price = VALUES(offered_price), available_quantity = VALUES(available_quantity), status = VALUES(status);

/* Kampanyalar ve kampanya ürünleri */
INSERT INTO campaigns (id, producer_id, title, description, campaign_type, discount_value, min_quantity, min_order_amount, starts_at, ends_at, status)
SELECT 10301, id, '10 kg üzeri domateste %10 indirim', 'Mahalle sepeti ve toplu alımlar için demo kampanya.', 'percentage_discount', 10.00, 10.000, NULL, DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_ADD(NOW(), INTERVAL 20 DAY), 'active'
FROM users WHERE email = 'producer@demo.com'
ON DUPLICATE KEY UPDATE title = VALUES(title), description = VALUES(description), campaign_type = VALUES(campaign_type), discount_value = VALUES(discount_value), min_quantity = VALUES(min_quantity), starts_at = VALUES(starts_at), ends_at = VALUES(ends_at), status = VALUES(status);

INSERT INTO campaigns (id, producer_id, title, description, campaign_type, discount_value, min_quantity, min_order_amount, starts_at, ends_at, status)
SELECT 10302, id, 'Zeytinyağında kargo bedava', '2 şişe ve üzeri zeytinyağında ücretsiz gönderim.', 'free_shipping', NULL, 2.000, NULL, DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_ADD(NOW(), INTERVAL 15 DAY), 'active'
FROM users WHERE email = 'ege@demo.com'
ON DUPLICATE KEY UPDATE title = VALUES(title), description = VALUES(description), campaign_type = VALUES(campaign_type), discount_value = VALUES(discount_value), min_quantity = VALUES(min_quantity), starts_at = VALUES(starts_at), ends_at = VALUES(ends_at), status = VALUES(status);

INSERT INTO campaign_products (campaign_id, product_id)
SELECT 10301, p.id FROM products p WHERE p.slug = 'kumluca-koy-domatesi'
ON DUPLICATE KEY UPDATE product_id = VALUES(product_id);

INSERT INTO campaign_products (campaign_id, product_id)
SELECT 10302, p.id FROM products p WHERE p.slug = 'soguk-sikim-zeytinyagi'
ON DUPLICATE KEY UPDATE product_id = VALUES(product_id);

/* Gönderim bölgeleri */
INSERT INTO producer_shipping_regions (id, producer_id, province_id, district_id, shipping_price, min_order_amount, is_active)
SELECT 10401, id, 34, 3401, 45.00, 250.00, TRUE FROM users WHERE email = 'producer@demo.com'
ON DUPLICATE KEY UPDATE province_id = VALUES(province_id), district_id = VALUES(district_id), shipping_price = VALUES(shipping_price), min_order_amount = VALUES(min_order_amount), is_active = TRUE;

INSERT INTO producer_shipping_regions (id, producer_id, province_id, district_id, shipping_price, min_order_amount, is_active)
SELECT 10402, id, 6, 6001, 65.00, 300.00, TRUE FROM users WHERE email = 'ege@demo.com'
ON DUPLICATE KEY UPDATE province_id = VALUES(province_id), district_id = VALUES(district_id), shipping_price = VALUES(shipping_price), min_order_amount = VALUES(min_order_amount), is_active = TRUE;

INSERT INTO producer_bulk_shipping_rules (id, producer_id, min_total_quantity, unit_type, ships_all_turkey, shipping_price, note, is_active)
SELECT 10451, id, 100.000, 'kg', TRUE, 750.00, '100 kg üzeri toplu gönderimde tüm Türkiye için sabit demo kargo.', TRUE
FROM users WHERE email = 'producer@demo.com'
ON DUPLICATE KEY UPDATE min_total_quantity = VALUES(min_total_quantity), unit_type = VALUES(unit_type), ships_all_turkey = VALUES(ships_all_turkey), shipping_price = VALUES(shipping_price), note = VALUES(note), is_active = TRUE;

/* Mahalle sepeti */
INSERT INTO neighborhood_baskets (id, producer_id, product_id, creator_user_id, title, province_id, district_id, neighborhood_id, target_quantity, current_quantity, unit_type, status, expires_at, order_id)
SELECT 10501, prod.id, p.id, creator.id, 'Kadıköy Domates Mahalle Sepeti', 34, 3401, NULL, 100.000, 42.000, 'kg', 'open', DATE_ADD(NOW(), INTERVAL 7 DAY), NULL
FROM users prod
JOIN products p ON p.producer_id = prod.id AND p.slug = 'kumluca-koy-domatesi'
JOIN users creator ON creator.email = 'ayse@demo.com'
WHERE prod.email = 'producer@demo.com'
ON DUPLICATE KEY UPDATE product_id = VALUES(product_id), creator_user_id = VALUES(creator_user_id), title = VALUES(title), target_quantity = VALUES(target_quantity), current_quantity = VALUES(current_quantity), status = VALUES(status), expires_at = VALUES(expires_at);

INSERT INTO neighborhood_basket_members (id, basket_id, user_id, quantity, status)
SELECT 10551, 10501, id, 15.000, 'active' FROM users WHERE email = 'ayse@demo.com'
ON DUPLICATE KEY UPDATE quantity = VALUES(quantity), status = VALUES(status);

INSERT INTO neighborhood_basket_members (id, basket_id, user_id, quantity, status)
SELECT 10552, 10501, id, 12.000, 'active' FROM users WHERE email = 'mehmet@demo.com'
ON DUPLICATE KEY UPDATE quantity = VALUES(quantity), status = VALUES(status);

INSERT INTO neighborhood_basket_members (id, basket_id, user_id, quantity, status)
SELECT 10553, 10501, id, 15.000, 'active' FROM users WHERE email = 'zeynep@demo.com'
ON DUPLICATE KEY UPDATE quantity = VALUES(quantity), status = VALUES(status);

INSERT INTO neighborhood_basket_payments (id, basket_member_id, wallet_transaction_id, amount, status)
VALUES
  (10601, 10551, NULL, 675.00, 'pending'),
  (10602, 10552, NULL, 540.00, 'pending'),
  (10603, 10553, NULL, 675.00, 'pending')
ON DUPLICATE KEY UPDATE amount = VALUES(amount), status = VALUES(status);

/* Performans ve audit örnekleri */
INSERT INTO producer_performance_daily (id, producer_id, report_date, total_orders, total_revenue, total_products_sold, product_view_count, profile_view_count, favorite_count, new_follower_count)
SELECT 10701, id, CURDATE(), 3, 748.50, 10.500, 42, 18, 5, 2
FROM users WHERE email = 'producer@demo.com'
ON DUPLICATE KEY UPDATE total_orders = VALUES(total_orders), total_revenue = VALUES(total_revenue), total_products_sold = VALUES(total_products_sold), product_view_count = VALUES(product_view_count), profile_view_count = VALUES(profile_view_count), favorite_count = VALUES(favorite_count), new_follower_count = VALUES(new_follower_count);

INSERT INTO audit_logs (id, user_id, action, entity_type, entity_id, description, ip_address, created_at)
SELECT 10801, id, 'demo_seed_run', 'database', NULL, 'Kapsamlı demo seed dosyası çalıştırıldı.', '127.0.0.1', NOW()
FROM users WHERE email = 'admin@demo.com'
ON DUPLICATE KEY UPDATE description = VALUES(description), created_at = VALUES(created_at);

/* Son rapor */
SELECT 'EkineraGo demo seed tamamlandı. Demo hesap şifresi: 123456' AS message;
SELECT role, full_name, email FROM users WHERE email LIKE '%@demo.com' ORDER BY role, email;
SELECT id, name, slug, type FROM categories ORDER BY id;
