USE ekinerago;

SET NAMES utf8mb4;

INSERT INTO products (
    producer_id,
    category_id,
    title,
    slug,
    description,
    unit_type,
    price,
    stock_quantity,
    harvest_date,
    is_preorder_enabled,
    preorder_deadline,
    min_preorder_quantity,
    status
)
SELECT
    u.id,
    c.id,
    'Organik Domates',
    'organik-domates',
    'Tarladan taze toplanmış organik domates.',
    'kg',
    45.00,
    100.00,
    CURDATE(),
    FALSE,
    NULL,
    NULL,
    'active'
FROM users u
JOIN categories c ON c.slug = 'domates'
WHERE u.email = 'producer@ekinerago.com'
ON DUPLICATE KEY UPDATE
    title = VALUES(title),
    description = VALUES(description),
    unit_type = VALUES(unit_type),
    price = VALUES(price),
    stock_quantity = VALUES(stock_quantity),
    harvest_date = VALUES(harvest_date),
    is_preorder_enabled = VALUES(is_preorder_enabled),
    preorder_deadline = VALUES(preorder_deadline),
    min_preorder_quantity = VALUES(min_preorder_quantity),
    status = VALUES(status);

INSERT INTO products (
    producer_id,
    category_id,
    title,
    slug,
    description,
    unit_type,
    price,
    stock_quantity,
    harvest_date,
    is_preorder_enabled,
    preorder_deadline,
    min_preorder_quantity,
    status
)
SELECT
    u.id,
    c.id,
    'Taze Elma',
    'taze-elma',
    'Bahçeden toplanmış taze elma.',
    'kg',
    35.00,
    80.00,
    CURDATE(),
    FALSE,
    NULL,
    NULL,
    'active'
FROM users u
JOIN categories c ON c.slug = 'elma'
WHERE u.email = 'producer@ekinerago.com'
ON DUPLICATE KEY UPDATE
    title = VALUES(title),
    description = VALUES(description),
    unit_type = VALUES(unit_type),
    price = VALUES(price),
    stock_quantity = VALUES(stock_quantity),
    harvest_date = VALUES(harvest_date),
    is_preorder_enabled = VALUES(is_preorder_enabled),
    preorder_deadline = VALUES(preorder_deadline),
    min_preorder_quantity = VALUES(min_preorder_quantity),
    status = VALUES(status);

INSERT INTO products (
    producer_id,
    category_id,
    title,
    slug,
    description,
    unit_type,
    price,
    stock_quantity,
    harvest_date,
    is_preorder_enabled,
    preorder_deadline,
    min_preorder_quantity,
    status
)
SELECT
    u.id,
    c.id,
    'Ön Sipariş Çilek',
    'on-siparis-cilek',
    'Hasat zamanı teslim edilecek ön sipariş çilek.',
    'kg',
    80.00,
    50.00,
    DATE_ADD(CURDATE(), INTERVAL 10 DAY),
    TRUE,
    DATE_ADD(CURDATE(), INTERVAL 5 DAY),
    2.00,
    'active'
FROM users u
JOIN categories c ON c.slug = 'cilek'
WHERE u.email = 'producer@ekinerago.com'
ON DUPLICATE KEY UPDATE
    title = VALUES(title),
    description = VALUES(description),
    unit_type = VALUES(unit_type),
    price = VALUES(price),
    stock_quantity = VALUES(stock_quantity),
    harvest_date = VALUES(harvest_date),
    is_preorder_enabled = VALUES(is_preorder_enabled),
    preorder_deadline = VALUES(preorder_deadline),
    min_preorder_quantity = VALUES(min_preorder_quantity),
    status = VALUES(status);

INSERT INTO product_inventory_movements (
    product_id,
    movement_type,
    quantity,
    order_item_id,
    note
)
SELECT
    p.id,
    'initial',
    p.stock_quantity,
    NULL,
    'Demo başlangıç stoğu'
FROM products p
JOIN users u ON u.id = p.producer_id
WHERE u.email = 'producer@ekinerago.com'
AND NOT EXISTS (
    SELECT 1
    FROM product_inventory_movements pim
    WHERE pim.product_id = p.id
    AND pim.movement_type = 'initial'
);

INSERT INTO product_images (
    product_id,
    image_path,
    sort_order,
    is_cover
)
SELECT
    p.id,
    CASE
        WHEN p.slug = 'organik-domates' THEN 'public/uploads/products/organik-domates.jpg'
        WHEN p.slug = 'taze-elma' THEN 'public/uploads/products/taze-elma.jpg'
        WHEN p.slug = 'on-siparis-cilek' THEN 'public/uploads/products/on-siparis-cilek.jpg'
        ELSE 'public/uploads/products/demo-product.jpg'
    END,
    1,
    TRUE
FROM products p
JOIN users u ON u.id = p.producer_id
WHERE u.email = 'producer@ekinerago.com'
AND NOT EXISTS (
    SELECT 1
    FROM product_images pi
    WHERE pi.product_id = p.id
);