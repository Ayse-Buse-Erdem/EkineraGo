<?php

require_once __DIR__ . '/../../app/bootstrap.php';

$returnTo = trim((string) ($_POST['return_to'] ?? ''));

function favorite_fail(array $payload, int $statusCode = 400): void
{
    global $returnTo;

    if ($returnTo !== '') {
        if (function_exists('flash_error')) {
            flash_error($payload['message'] ?? 'Favori işlemi başarısız oldu.');
        }

        redirect($returnTo);
    }

    json_response($payload, $statusCode);
}

if (!isLoggedIn()) {
    favorite_fail([
        'success' => false,
        'message' => 'Favori işlemi için giriş yapmalısınız.',
    ], 401);
}

if (!isConsumer()) {
    favorite_fail([
        'success' => false,
        'message' => 'Favori işlemini sadece tüketici hesapları yapabilir.',
    ], 403);
}

if (!is_post()) {
    favorite_fail([
        'success' => false,
        'message' => 'Bu endpoint sadece POST isteği kabul eder.',
    ], 405);
}

if (!verify_csrf()) {
    favorite_fail([
        'success' => false,
        'message' => 'CSRF doğrulaması başarısız.',
    ], 419);
}

$productId = (int) ($_POST['product_id'] ?? 0);

if ($productId <= 0) {
    favorite_fail([
        'success' => false,
        'message' => 'Geçerli bir ürün ID değeri gönderilmelidir.',
    ], 422);
}

$product = Product::findById($productId);

if (!$product || ($product['status'] ?? '') === PRODUCT_STATUS_DELETED) {
    favorite_fail([
        'success' => false,
        'message' => 'Ürün bulunamadı.',
    ], 404);
}

try {
    $isFavorited = Favorite::toggle((int) currentUserId(), $productId);

    Product::updateFavoriteCount($productId);

    $message = $isFavorited
        ? 'Ürün favorilere eklendi.'
        : 'Ürün favorilerden çıkarıldı.';

    if ($returnTo !== '') {
        if (function_exists('flash_success')) {
            flash_success($message);
        }

        redirect($returnTo);
    }

    json_response([
        'success' => true,
        'message' => $message,
        'data' => [
            'product_id' => $productId,
            'is_favorited' => $isFavorited,
            'favorite_count' => Favorite::countByProductId($productId),
        ],
    ]);
} catch (Throwable $e) {
    favorite_fail([
        'success' => false,
        'message' => 'Favori işlemi sırasında bir hata oluştu.',
    ], 500);
}