<?php

class ProducerController
{
    public function publicIndexData(array $filters = []): array
    {
        return [
            'producers' => $this->searchProducers($filters),
            'filters' => $filters,
            'provinces' => $this->getProvinces(),
            'districts' => $this->getDistricts(),
        ];
    }

    public function publicDetailData(int $producerId): array
    {
        $producer = $this->findProducerDetail($producerId);

        if (!$producer) {
            return [
                'producer' => null,
                'products' => [],
                'reviews' => [],
            ];
        }

        return [
            'producer' => $producer,
            'products' => Product::getByProducerId((int) $producer['user_id']),
            'reviews' => class_exists('Review')
                ? Review::getVisibleByProducerId((int) $producer['user_id'])
                : [],
        ];
    }

    public function dashboardData(int $producerId): array
    {
        ProducerMiddleware::handle();

        return [
            'summary' => $this->producerSummary($producerId),
            'latest_products' => $this->latestProducts($producerId),
        ];
    }

    private function searchProducers(array $filters = []): array
    {
        $where = [
            "u.role = 'producer'",
            "u.deleted_at IS NULL",
            "u.status = 'active'",
        ];

        // HY093 hatasını önlemek için named parameter yerine
        // positional placeholder (?) kullanıyoruz.
        $params = [];

        if (!empty($filters['q'])) {
            $where[] = "(pp.store_name LIKE ? OR u.full_name LIKE ?)";

            $search = '%' . trim($filters['q']) . '%';

            $params[] = $search;
            $params[] = $search;
        }

        // Yeni sistem: İl adıyla arama.
        // Örnek: ?province_name=Antalya
        if (!empty($filters['province_name'])) {
            $where[] = "pr.name LIKE ?";
            $params[] = trim($filters['province_name']) . '%';
        }

        // Yeni sistem: İlçe adıyla arama.
        // Örnek: ?district_name=Kumluca
        if (!empty($filters['district_name'])) {
            $where[] = "d.name LIKE ?";
            $params[] = trim($filters['district_name']) . '%';
        }

        // Eski ID sistemini bozmayalım.
        // Formu isimli aramaya çevirsek bile eski linkler çalışmaya devam eder.
        if (!empty($filters['province_id'])) {
            $where[] = "u.province_id = ?";
            $params[] = (int) $filters['province_id'];
        }

        if (!empty($filters['district_id'])) {
            $where[] = "u.district_id = ?";
            $params[] = (int) $filters['district_id'];
        }

        $sql = "
            SELECT
                u.id AS user_id,
                u.full_name,
                u.email,
                u.phone,
                u.whatsapp_phone,
                u.province_id,
                u.district_id,
                pp.store_name,
                pp.slug,
                pp.description,
                pp.logo_path,
                pp.cover_photo_path,
                pp.average_rating,
                pp.rating_count,
                pp.total_orders,
                pp.total_sales_amount,
                pp.verification_status,
                pr.name AS province_name,
                d.name AS district_name,
                (
                    SELECT COUNT(*)
                    FROM products p
                    WHERE p.producer_id = u.id
                      AND p.status = 'active'
                      AND p.deleted_at IS NULL
                ) AS active_product_count
            FROM users u
            JOIN producer_profiles pp ON pp.user_id = u.id
            LEFT JOIN provinces pr ON pr.id = u.province_id
            LEFT JOIN districts d ON d.id = u.district_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY pp.average_rating DESC, pp.created_at DESC
        ";

        $stmt = db()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    private function getProvinces(): array
    {
        try {
            $stmt = db()->query("
                SELECT id, name
                FROM provinces
                ORDER BY name ASC
            ");

            return $stmt->fetchAll();
        } catch (Throwable $e) {
            return [];
        }
    }

    private function getDistricts(): array
    {
        try {
            $stmt = db()->query("
                SELECT id, name
                FROM districts
                ORDER BY name ASC
            ");

            return $stmt->fetchAll();
        } catch (Throwable $e) {
            return [];
        }
    }

    private function findProducerDetail(int $producerId): ?array
    {
        if ($producerId <= 0) {
            return null;
        }

        $stmt = db()->prepare("
            SELECT
                u.id AS user_id,
                u.full_name,
                u.email,
                u.phone,
                u.whatsapp_phone,
                u.profile_photo,
                u.province_id,
                u.district_id,
                u.address_text,
                u.created_at AS user_created_at,
                pp.store_name,
                pp.slug,
                pp.description,
                pp.logo_path,
                pp.cover_photo_path,
                pp.contact_email,
                pp.contact_phone,
                pp.contact_whatsapp,
                pp.average_rating,
                pp.rating_count,
                pp.total_orders,
                pp.total_sales_amount,
                pp.shipping_note,
                pp.verification_status,
                pr.name AS province_name,
                d.name AS district_name,
                (
                    SELECT COUNT(*)
                    FROM products p
                    WHERE p.producer_id = u.id
                      AND p.status = 'active'
                      AND p.deleted_at IS NULL
                ) AS active_product_count
            FROM users u
            JOIN producer_profiles pp ON pp.user_id = u.id
            LEFT JOIN provinces pr ON pr.id = u.province_id
            LEFT JOIN districts d ON d.id = u.district_id
            WHERE u.id = :producer_id
              AND u.role = 'producer'
              AND u.deleted_at IS NULL
            LIMIT 1
        ");

        $stmt->execute([
            'producer_id' => $producerId,
        ]);

        $producer = $stmt->fetch();

        return $producer ?: null;
    }

    private function producerSummary(int $producerId): array
    {
        $productCount = 0;
        $soldOutCount = 0;
        $totalOrders = 0;
        $totalRevenue = 0.0;

        try {
            $stmt = db()->prepare("
                SELECT
                    COUNT(*) AS total_products,
                    SUM(CASE WHEN status = 'sold_out' THEN 1 ELSE 0 END) AS sold_out_products
                FROM products
                WHERE producer_id = :producer_id
                  AND deleted_at IS NULL
            ");

            $stmt->execute([
                'producer_id' => $producerId,
            ]);

            $productRow = $stmt->fetch();

            $productCount = (int) ($productRow['total_products'] ?? 0);
            $soldOutCount = (int) ($productRow['sold_out_products'] ?? 0);
        } catch (Throwable $e) {
            $productCount = 0;
            $soldOutCount = 0;
        }

        try {
            $stmt = db()->prepare("
                SELECT
                    COUNT(*) AS total_orders,
                    COALESCE(SUM(total_amount), 0) AS total_revenue
                FROM orders
                WHERE producer_id = :producer_id
                  AND payment_status = 'paid'
            ");

            $stmt->execute([
                'producer_id' => $producerId,
            ]);

            $orderRow = $stmt->fetch();

            $totalOrders = (int) ($orderRow['total_orders'] ?? 0);
            $totalRevenue = (float) ($orderRow['total_revenue'] ?? 0);
        } catch (Throwable $e) {
            $totalOrders = 0;
            $totalRevenue = 0.0;
        }

        return [
            'total_products' => $productCount,
            'sold_out_products' => $soldOutCount,
            'total_orders' => $totalOrders,
            'total_revenue' => $totalRevenue,
        ];
    }

    private function latestProducts(int $producerId): array
    {
        try {
            $stmt = db()->prepare("
                SELECT
                    p.*,
                    c.name AS category_name
                FROM products p
                JOIN categories c ON c.id = p.category_id
                WHERE p.producer_id = :producer_id
                  AND p.deleted_at IS NULL
                ORDER BY p.created_at DESC
                LIMIT 5
            ");

            $stmt->execute([
                'producer_id' => $producerId,
            ]);

            return $stmt->fetchAll();
        } catch (Throwable $e) {
            return [];
        }
    }
}