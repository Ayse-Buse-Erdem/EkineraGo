<?php

class FavoriteService
{
    public function getUserFavorites(int $userId): array
    {
        $stmt = db()->prepare("
            SELECT
                f.created_at AS favorited_at,
                p.id,
                p.title,
                p.slug,
                p.description,
                p.price,
                p.unit_type,
                p.stock_quantity,
                p.status,
                p.producer_id,
                p.average_rating,
                p.rating_count,
                c.name AS category_name,
                u.full_name AS producer_name,
                pp.store_name,
                pr.name AS province_name,
                d.name AS district_name,
                (
                    SELECT pi.image_path
                    FROM product_images pi
                    WHERE pi.product_id = p.id
                    ORDER BY pi.is_cover DESC, pi.sort_order ASC, pi.id ASC
                    LIMIT 1
                ) AS cover_image
            FROM favorites f
            JOIN products p ON p.id = f.product_id
            JOIN users u ON u.id = p.producer_id
            LEFT JOIN producer_profiles pp ON pp.user_id = u.id
            LEFT JOIN categories c ON c.id = p.category_id
            LEFT JOIN provinces pr ON pr.id = u.province_id
            LEFT JOIN districts d ON d.id = u.district_id
            WHERE f.user_id = :user_id
              AND p.status != 'deleted'
            ORDER BY f.created_at DESC
        ");

        $stmt->execute([
            'user_id' => $userId,
        ]);

        return $stmt->fetchAll();
    }

    public function isFavorited(int $userId, int $productId): bool
    {
        $stmt = db()->prepare("
            SELECT 1
            FROM favorites
            WHERE user_id = :user_id
              AND product_id = :product_id
            LIMIT 1
        ");

        $stmt->execute([
            'user_id' => $userId,
            'product_id' => $productId,
        ]);

        return (bool) $stmt->fetchColumn();
    }

    public function toggle(int $userId, int $productId): array
    {
        if ($productId <= 0) {
            return [
                'success' => false,
                'favorited' => false,
                'message' => 'Geçersiz ürün.',
            ];
        }

        if (!$this->productExists($productId)) {
            return [
                'success' => false,
                'favorited' => false,
                'message' => 'Ürün bulunamadı.',
            ];
        }

        if ($this->isFavorited($userId, $productId)) {
            $this->remove($userId, $productId);

            return [
                'success' => true,
                'favorited' => false,
                'message' => 'Ürün favorilerden çıkarıldı.',
            ];
        }

        $this->add($userId, $productId);

        return [
            'success' => true,
            'favorited' => true,
            'message' => 'Ürün favorilere eklendi.',
        ];
    }

    public function add(int $userId, int $productId): void
    {
        $stmt = db()->prepare("
            INSERT IGNORE INTO favorites (user_id, product_id)
            VALUES (:user_id, :product_id)
        ");

        $stmt->execute([
            'user_id' => $userId,
            'product_id' => $productId,
        ]);
    }

    public function remove(int $userId, int $productId): void
    {
        $stmt = db()->prepare("
            DELETE FROM favorites
            WHERE user_id = :user_id
              AND product_id = :product_id
        ");

        $stmt->execute([
            'user_id' => $userId,
            'product_id' => $productId,
        ]);
    }

    private function productExists(int $productId): bool
    {
        $stmt = db()->prepare("
            SELECT 1
            FROM products
            WHERE id = :id
            LIMIT 1
        ");

        $stmt->execute([
            'id' => $productId,
        ]);

        return (bool) $stmt->fetchColumn();
    }
}