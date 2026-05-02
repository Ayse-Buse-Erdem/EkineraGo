<?php

class FavoriteController
{
    public function index(): void
    {
        ConsumerMiddleware::handle();

        $pageTitle = 'Favorilerim';
        $bodyClass = 'page-consumer-favorites';

        $favorites = Favorite::getByUserId((int) currentUserId());

        require APP_PATH . '/Views/consumer/favorites.php';
    }

    public function toggle(): void
    {
        ConsumerMiddleware::handle();

        if (!is_post()) {
            json_response([
                'success' => false,
                'message' => 'Sadece POST isteği kabul edilir.',
            ], 405);
        }

        if (!verify_csrf()) {
            json_response([
                'success' => false,
                'message' => 'CSRF doğrulaması başarısız.',
            ], 419);
        }

        $productId = (int) ($_POST['product_id'] ?? 0);

        if ($productId <= 0) {
            json_response([
                'success' => false,
                'message' => 'Geçerli bir ürün ID değeri gönderilmelidir.',
            ], 422);
        }

        $product = Product::findById($productId);

        if (!$product || ($product['status'] ?? '') === PRODUCT_STATUS_DELETED) {
            json_response([
                'success' => false,
                'message' => 'Ürün bulunamadı.',
            ], 404);
        }

        try {
            $isFavorited = Favorite::toggle((int) currentUserId(), $productId);
            Product::updateFavoriteCount($productId);

            json_response([
                'success' => true,
                'message' => $isFavorited ? 'Ürün favorilere eklendi.' : 'Ürün favorilerden çıkarıldı.',
                'data' => [
                    'product_id' => $productId,
                    'is_favorited' => $isFavorited,
                    'favorite_count' => Favorite::countByProductId($productId),
                ],
            ]);
        } catch (Throwable $e) {
            json_response([
                'success' => false,
                'message' => 'Favori işlemi sırasında bir hata oluştu.',
            ], 500);
        }
    }
}