<?php

class ReviewService
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        if ($pdo instanceof PDO) {
            $this->pdo = $pdo;
            return;
        }

        if (function_exists('db')) {
            $this->pdo = db();
            return;
        }

        if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
            $this->pdo = $GLOBALS['pdo'];
            return;
        }

        throw new RuntimeException('PDO bağlantısı bulunamadı.');
    }

    public function canReview(int $consumerId, int $orderItemId): array
    {
        if ($consumerId <= 0) {
            return [
                'success' => false,
                'message' => 'Yorum yapabilmek için giriş yapmalısınız.',
            ];
        }

        if ($orderItemId <= 0) {
            return [
                'success' => false,
                'message' => 'Geçerli bir sipariş ürünü seçilmedi.',
            ];
        }

        $stmt = $this->pdo->prepare("
            SELECT
                oi.id AS order_item_id,
                oi.order_id,
                oi.product_id,
                oi.product_title_snapshot,
                o.order_no,
                o.consumer_id,
                o.producer_id,
                o.order_status
            FROM order_items oi
            INNER JOIN orders o ON o.id = oi.order_id
            WHERE oi.id = ?
            LIMIT 1
        ");

        $stmt->execute([$orderItemId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return [
                'success' => false,
                'message' => 'Sipariş ürünü bulunamadı.',
            ];
        }

        if ((int) $row['consumer_id'] !== $consumerId) {
            return [
                'success' => false,
                'message' => 'Bu sipariş ürünü size ait değil.',
            ];
        }

        $deliveredStatus = defined('ORDER_STATUS_DELIVERED') ? ORDER_STATUS_DELIVERED : 'delivered';

        if (($row['order_status'] ?? '') !== $deliveredStatus) {
            return [
                'success' => false,
                'message' => 'Sadece teslim edilmiş sipariş ürünlerine yorum yapabilirsiniz.',
            ];
        }

        if ($this->hasReviewForOrderItem($orderItemId)) {
            return [
                'success' => false,
                'message' => 'Bu sipariş ürünü için daha önce yorum yapılmış.',
            ];
        }

        return [
            'success' => true,
            'message' => 'Yorum yapılabilir.',
            'data' => $row,
        ];
    }

    public function createReview(int $consumerId, array $input): array
    {
        $orderItemId = (int) ($input['order_item_id'] ?? 0);
        $rating = (int) ($input['rating'] ?? 0);
        $comment = trim((string) ($input['comment'] ?? ''));

        $errors = [];

        if ($orderItemId <= 0) {
            $errors['order_item_id'][] = 'Geçerli bir sipariş ürünü seçilmedi.';
        }

        if ($rating < 1 || $rating > 5) {
            $errors['rating'][] = 'Puan 1 ile 5 arasında olmalıdır.';
        }

        if (mb_strlen($comment, 'UTF-8') > 1000) {
            $errors['comment'][] = 'Yorum en fazla 1000 karakter olabilir.';
        }

        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => 'Lütfen formdaki hataları düzeltin.',
                'errors' => $errors,
            ];
        }

        $canReview = $this->canReview($consumerId, $orderItemId);

        if (!$canReview['success']) {
            return [
                'success' => false,
                'message' => $canReview['message'] ?? 'Bu ürün için yorum yapılamaz.',
                'errors' => [],
            ];
        }

        $reviewData = $canReview['data'];
        $producerId = (int) $reviewData['producer_id'];
        $productId = isset($reviewData['product_id']) && $reviewData['product_id'] !== null
            ? (int) $reviewData['product_id']
            : null;

        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare("
                INSERT INTO reviews (
                    order_item_id,
                    consumer_id,
                    producer_id,
                    product_id,
                    rating,
                    comment,
                    status
                ) VALUES (?, ?, ?, ?, ?, ?, 'visible')
            ");

            $stmt->execute([
                $orderItemId,
                $consumerId,
                $producerId,
                $productId,
                $rating,
                $comment !== '' ? $comment : null,
            ]);

            $this->updateProductRating($productId);
            $this->updateProducerRating($producerId);

            $this->pdo->commit();

            return [
                'success' => true,
                'message' => 'Yorum başarıyla oluşturuldu.',
            ];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            if ($e instanceof PDOException && $e->getCode() === '23000') {
                return [
                    'success' => false,
                    'message' => 'Bu sipariş ürünü için daha önce yorum yapılmış.',
                    'errors' => [],
                ];
            }

            return [
                'success' => false,
                'message' => 'Yorum kaydedilirken bir hata oluştu: ' . $e->getMessage(),
                'errors' => [],
            ];
        }
    }

    public function hasReviewForOrderItem(int $orderItemId): bool
    {
        if ($orderItemId <= 0) {
            return false;
        }

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM reviews
            WHERE order_item_id = ?
              AND status <> 'deleted'
        ");

        $stmt->execute([$orderItemId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    private function updateProductRating(?int $productId): void
    {
        if ($productId === null || $productId <= 0) {
            return;
        }

        $stmt = $this->pdo->prepare("
            SELECT
                COALESCE(AVG(rating), 0) AS average_rating,
                COUNT(*) AS rating_count
            FROM reviews
            WHERE product_id = ?
              AND status = 'visible'
        ");

        $stmt->execute([$productId]);
        $ratingData = $stmt->fetch(PDO::FETCH_ASSOC) ?: [
            'average_rating' => 0,
            'rating_count' => 0,
        ];

        $update = $this->pdo->prepare("
            UPDATE products
            SET average_rating = ?,
                rating_count = ?,
                updated_at = NOW()
            WHERE id = ?
            LIMIT 1
        ");

        $update->execute([
            round((float) $ratingData['average_rating'], 2),
            (int) $ratingData['rating_count'],
            $productId,
        ]);
    }

    private function updateProducerRating(int $producerId): void
    {
        if ($producerId <= 0) {
            return;
        }

        $stmt = $this->pdo->prepare("
            SELECT
                COALESCE(AVG(rating), 0) AS average_rating,
                COUNT(*) AS rating_count
            FROM reviews
            WHERE producer_id = ?
              AND status = 'visible'
        ");

        $stmt->execute([$producerId]);
        $ratingData = $stmt->fetch(PDO::FETCH_ASSOC) ?: [
            'average_rating' => 0,
            'rating_count' => 0,
        ];

        $update = $this->pdo->prepare("
            UPDATE producer_profiles
            SET average_rating = ?,
                rating_count = ?,
                updated_at = NOW()
            WHERE user_id = ?
            LIMIT 1
        ");

        $update->execute([
            round((float) $ratingData['average_rating'], 2),
            (int) $ratingData['rating_count'],
            $producerId,
        ]);
    }
}
