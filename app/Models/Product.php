<?php

class Product
{
    public static function create(array $data): int
    {
        $title = trim($data['title']);
        $slug = $data['slug'] ?? self::uniqueSlug((int) $data['producer_id'], $title);

        $stmt = db()->prepare("
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
                min_preorder_unit,
                status
            ) VALUES (
                :producer_id,
                :category_id,
                :title,
                :slug,
                :description,
                :unit_type,
                :price,
                :stock_quantity,
                :harvest_date,
                :is_preorder_enabled,
                :preorder_deadline,
                :min_preorder_quantity,
                :min_preorder_unit,
                :status
            )
        ");

        $stmt->execute([
            'producer_id' => $data['producer_id'],
            'category_id' => $data['category_id'],
            'title' => $title,
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'unit_type' => $data['unit_type'] ?? UNIT_KG,
            'price' => $data['price'],
            'stock_quantity' => $data['stock_quantity'] ?? 0,
            'harvest_date' => $data['harvest_date'] ?? null,
            'is_preorder_enabled' => !empty($data['is_preorder_enabled']) ? 1 : 0,
            'preorder_deadline' => $data['preorder_deadline'] ?? null,
            'min_preorder_quantity' => $data['min_preorder_quantity'] ?? null,
            'min_preorder_unit' => $data['min_preorder_unit'] ?? UNIT_KG,
            'status' => $data['status'] ?? PRODUCT_STATUS_ACTIVE,
        ]);

        return (int) db()->lastInsertId();
    }

    public static function update(int $productId, int $producerId, array $data): void
    {
        $stmt = db()->prepare("
            UPDATE products
            SET
                category_id = :category_id,
                title = :title,
                description = :description,
                unit_type = :unit_type,
                price = :price,
                stock_quantity = :stock_quantity,
                harvest_date = :harvest_date,
                is_preorder_enabled = :is_preorder_enabled,
                preorder_deadline = :preorder_deadline,
                min_preorder_quantity = :min_preorder_quantity,
                min_preorder_unit = :min_preorder_unit,
                status = :status,
                updated_at = NOW()
            WHERE id = :id
              AND producer_id = :producer_id
              AND deleted_at IS NULL
            LIMIT 1
        ");

        $stmt->execute([
            'id' => $productId,
            'producer_id' => $producerId,
            'category_id' => $data['category_id'],
            'title' => trim($data['title']),
            'description' => $data['description'] ?? null,
            'unit_type' => $data['unit_type'] ?? UNIT_KG,
            'price' => $data['price'],
            'stock_quantity' => $data['stock_quantity'] ?? 0,
            'harvest_date' => $data['harvest_date'] ?? null,
            'is_preorder_enabled' => !empty($data['is_preorder_enabled']) ? 1 : 0,
            'preorder_deadline' => $data['preorder_deadline'] ?? null,
            'min_preorder_quantity' => $data['min_preorder_quantity'] ?? null,
            'min_preorder_unit' => $data['min_preorder_unit'] ?? UNIT_KG,
            'status' => $data['status'] ?? PRODUCT_STATUS_ACTIVE,
        ]);
    }

    public static function findById(int $id): ?array
    {
        $stmt = db()->prepare("
            SELECT
                p.*,
                c.name AS category_name,
                u.full_name AS producer_name,
                pp.store_name,
                pp.slug AS producer_slug,
                pr.name AS province_name,
                d.name AS district_name
            FROM products p
            JOIN categories c ON c.id = p.category_id
            JOIN users u ON u.id = p.producer_id
            LEFT JOIN producer_profiles pp ON pp.user_id = u.id
            LEFT JOIN provinces pr ON pr.id = u.province_id
            LEFT JOIN districts d ON d.id = u.district_id
            WHERE p.id = :id
              AND p.deleted_at IS NULL
            LIMIT 1
        ");

        $stmt->execute([
            'id' => $id,
        ]);

        $product = $stmt->fetch();

        return $product ?: null;
    }

    public static function findByProducerAndSlug(int $producerId, string $slug): ?array
    {
        $stmt = db()->prepare("
            SELECT *
            FROM products
            WHERE producer_id = :producer_id
              AND slug = :slug
              AND deleted_at IS NULL
            LIMIT 1
        ");

        $stmt->execute([
            'producer_id' => $producerId,
            'slug' => $slug,
        ]);

        $product = $stmt->fetch();

        return $product ?: null;
    }

    public static function getByProducerId(int $producerId): array
    {
        $stmt = db()->prepare("
            SELECT
                p.*,
                c.name AS category_name,
                (
                    SELECT pi.image_path
                    FROM product_images pi
                    WHERE pi.product_id = p.id
                    ORDER BY pi.is_cover DESC, pi.sort_order ASC, pi.id ASC
                    LIMIT 1
                ) AS cover_image
            FROM products p
            JOIN categories c ON c.id = p.category_id
            WHERE p.producer_id = :producer_id
              AND p.deleted_at IS NULL
            ORDER BY p.created_at DESC
        ");

        $stmt->execute([
            'producer_id' => $producerId,
        ]);

        return $stmt->fetchAll();
    }

    public static function search(array $filters = []): array
    {
        $where = [
            "p.status = 'active'",
            "p.deleted_at IS NULL",
        ];

        $params = [];

        if (!empty($filters['q'])) {
            $where[] = "p.title LIKE :q";
            $params['q'] = '%' . trim($filters['q']) . '%';
        }

        if (!empty($filters['category_id'])) {
            $where[] = "p.category_id = :category_id";
            $params['category_id'] = (int) $filters['category_id'];
        }

        if (!empty($filters['province_id'])) {
            $where[] = "u.province_id = :province_id";
            $params['province_id'] = (int) $filters['province_id'];
        }

        if (!empty($filters['district_id'])) {
            $where[] = "u.district_id = :district_id";
            $params['district_id'] = (int) $filters['district_id'];
        }

        if (isset($filters['min_price']) && $filters['min_price'] !== '') {
            $where[] = "p.price >= :min_price";
            $params['min_price'] = (float) $filters['min_price'];
        }

        if (isset($filters['max_price']) && $filters['max_price'] !== '') {
            $where[] = "p.price <= :max_price";
            $params['max_price'] = (float) $filters['max_price'];
        }

        if (!empty($filters['in_stock'])) {
            $where[] = "p.stock_quantity > 0";
        }

        if (!empty($filters['preorder'])) {
            $where[] = "p.is_preorder_enabled = 1";
        }

        // ProductService bu değeri giriş yapan tüketici için gönderiyor.
        // Misafir/üretici/admin için 0 kalır; böylece kalp boş görünür.
        $favoriteUserId = !empty($filters['favorite_user_id'])
            ? (int) $filters['favorite_user_id']
            : 0;

        $params['favorite_user_id'] = $favoriteUserId;

        $sort = $filters['sort'] ?? 'newest';

        $orderBy = match ($sort) {
            'price_asc' => 'p.price ASC',
            'price_desc' => 'p.price DESC',
            'rating_desc' => 'p.average_rating DESC',
            'harvest_asc' => 'p.harvest_date ASC',
            default => 'p.created_at DESC',
        };

        $sql = "
            SELECT
                p.*,
                c.name AS category_name,
                u.full_name AS producer_name,
                pp.store_name,
                pr.name AS province_name,
                d.name AS district_name,
                CASE
                    WHEN f.user_id IS NULL THEN 0
                    ELSE 1
                END AS is_favorited,
                (
                    SELECT pi.image_path
                    FROM product_images pi
                    WHERE pi.product_id = p.id
                    ORDER BY pi.is_cover DESC, pi.sort_order ASC, pi.id ASC
                    LIMIT 1
                ) AS cover_image
            FROM products p
            JOIN categories c ON c.id = p.category_id
            JOIN users u ON u.id = p.producer_id
            LEFT JOIN producer_profiles pp ON pp.user_id = u.id
            LEFT JOIN provinces pr ON pr.id = u.province_id
            LEFT JOIN districts d ON d.id = u.district_id
            LEFT JOIN favorites f
                ON f.product_id = p.id
                AND f.user_id = :favorite_user_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY {$orderBy}
        ";

        $stmt = db()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function softDelete(int $productId, int $producerId): void
    {
        $stmt = db()->prepare("
            UPDATE products
            SET status = 'deleted', deleted_at = NOW(), updated_at = NOW()
            WHERE id = :id
              AND producer_id = :producer_id
            LIMIT 1
        ");

        $stmt->execute([
            'id' => $productId,
            'producer_id' => $producerId,
        ]);
    }

    public static function updateStatus(int $productId, int $producerId, string $status): void
    {
        $allowedStatuses = [
            PRODUCT_STATUS_DRAFT,
            PRODUCT_STATUS_ACTIVE,
            PRODUCT_STATUS_SOLD_OUT,
            PRODUCT_STATUS_PAUSED,
            PRODUCT_STATUS_DELETED,
        ];

        if (!in_array($status, $allowedStatuses, true)) {
            throw new InvalidArgumentException('Geçersiz ürün durumu.');
        }

        $stmt = db()->prepare("
            UPDATE products
            SET status = :status, updated_at = NOW()
            WHERE id = :id
              AND producer_id = :producer_id
            LIMIT 1
        ");

        $stmt->execute([
            'id' => $productId,
            'producer_id' => $producerId,
            'status' => $status,
        ]);
    }

    public static function decreaseStock(int $productId, float $quantity): void
    {
        $stmt = db()->prepare("
            UPDATE products
            SET
                stock_quantity = stock_quantity - :quantity_update,
                status = CASE
                    WHEN stock_quantity - :quantity_status <= 0 THEN 'sold_out'
                    ELSE status
                END,
                updated_at = NOW()
            WHERE id = :id
            AND stock_quantity >= :quantity_where
            AND deleted_at IS NULL
            LIMIT 1
        ");

        $stmt->execute([
            'id' => $productId,
            'quantity_update' => $quantity,
            'quantity_status' => $quantity,
            'quantity_where' => $quantity,
        ]);

        if ($stmt->rowCount() === 0) {
            throw new RuntimeException('Stok yetersiz veya ürün bulunamadı.');
        }
    }

    public static function increaseStock(int $productId, float $quantity): void
    {
        $stmt = db()->prepare("
            UPDATE products
            SET
                stock_quantity = stock_quantity + :quantity,
                status = CASE
                    WHEN status = 'sold_out' THEN 'active'
                    ELSE status
                END,
                updated_at = NOW()
            WHERE id = :id
              AND deleted_at IS NULL
            LIMIT 1
        ");

        $stmt->execute([
            'id' => $productId,
            'quantity' => $quantity,
        ]);
    }

    public static function updateRating(int $productId, float $averageRating, int $ratingCount): void
    {
        $stmt = db()->prepare("
            UPDATE products
            SET average_rating = :average_rating,
                rating_count = :rating_count,
                updated_at = NOW()
            WHERE id = :id
            LIMIT 1
        ");

        $stmt->execute([
            'id' => $productId,
            'average_rating' => $averageRating,
            'rating_count' => $ratingCount,
        ]);
    }

    public static function incrementViewCount(int $productId): void
    {
        $stmt = db()->prepare("
            UPDATE products
            SET view_count = view_count + 1
            WHERE id = :id
            LIMIT 1
        ");

        $stmt->execute([
            'id' => $productId,
        ]);
    }

    public static function updateFavoriteCount(int $productId): void
    {
        $count = Favorite::countByProductId($productId);

        $stmt = db()->prepare("
            UPDATE products
            SET favorite_count = :favorite_count,
                updated_at = NOW()
            WHERE id = :id
            LIMIT 1
        ");

        $stmt->execute([
            'id' => $productId,
            'favorite_count' => $count,
        ]);
    }

    public static function uniqueSlug(int $producerId, string $title): string
    {
        $baseSlug = slugify($title);
        $slug = $baseSlug;
        $counter = 1;

        while (self::findByProducerAndSlug($producerId, $slug) !== null) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    
}
