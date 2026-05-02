<?php

require_once __DIR__ . '/../app/bootstrap.php';

if (is_post()) {
    ConsumerMiddleware::handle();
    require_csrf();

    $productId = (int) ($_POST['product_id'] ?? 0);
    $quantity = (float) ($_POST['quantity'] ?? 0);

    $cartService = new CartService();
    $result = $cartService->addItem((int) currentUserId(), $productId, $quantity);

    if ($result['success']) {
        flash_success($result['message']);
    } else {
        flash_error($result['message']);
    }

    redirect('product-detail.php?id=' . $productId);
}

$controller = new ProductController();

$productId = (int) ($_GET['id'] ?? 0);
$data = $controller->publicDetailData($productId);

$product = $data['product'] ?? null;
$user = currentUser();

$pageTitle = $product ? ($product['title'] ?? 'Ürün Detayı') : 'Ürün Bulunamadı';
$bodyClass = 'page-product-detail';

require APP_PATH . '/Views/layouts/header.php';

if (!function_exists('detail_unit_label')) {
    function detail_unit_label(string $unit): string
    {
        return match ($unit) {
            'kg' => 'kg',
            'piece' => 'adet',
            'bunch' => 'demet',
            'box' => 'kasa',
            default => $unit,
        };
    }
}

if (!function_exists('detail_status_label')) {
    function detail_status_label(string $status): string
    {
        return match ($status) {
            'active' => 'Aktif',
            'draft' => 'Taslak',
            'sold_out' => 'Stokta Yok',
            'paused' => 'Pasif',
            default => 'Bilinmiyor',
        };
    }
}

if (!function_exists('detail_status_badge')) {
    function detail_status_badge(string $status): string
    {
        return match ($status) {
            'active' => 'badge-success',
            'sold_out' => 'badge-warning',
            'paused', 'draft' => 'badge-muted',
            default => 'badge-muted',
        };
    }
}
?>

<main class="container">
    <?php if (!$product): ?>
        <section class="card empty-state">
            <h1>Ürün bulunamadı</h1>

            <p>
                Aradığın ürün silinmiş, pasifleştirilmiş veya hiç oluşturulmamış olabilir.
            </p>

            <a class="btn" href="<?= e(url('products.php')) ?>">
                Ürünlere Dön
            </a>
        </section>
    <?php else: ?>
        <?php
            $unit = detail_unit_label($product['unit_type'] ?? 'kg');
            $status = $product['status'] ?? 'active';
            $producerName = $product['store_name'] ?: ($product['producer_name'] ?? 'Üretici');
            $images = $product['images'] ?? [];
            $coverImage = $product['cover_image']['image_path'] ?? null;
            $reviews = $product['reviews'] ?? [];
            $stockQuantity = (float) ($product['stock_quantity'] ?? 0);
        ?>

        <section class="detail-layout">
            <div class="card">
                <?php if ($coverImage): ?>
                    <img
                        class="product-image-large"
                        src="<?= e(url($coverImage)) ?>"
                        alt="<?= e($product['title'] ?? 'Ürün') ?>"
                    >
                <?php else: ?>
                    <div class="product-image-large placeholder">
                        Ürün Fotoğrafı
                    </div>
                <?php endif; ?>

                <?php if (!empty($images)): ?>
                    <div class="image-thumbs">
                        <?php foreach ($images as $image): ?>
                            <img
                                src="<?= e(url($image['image_path'])) ?>"
                                alt="<?= e($product['title'] ?? 'Ürün') ?>"
                            >
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <aside class="card product-info">
                <span class="badge <?= e(detail_status_badge($status)) ?>">
                    <?= e(detail_status_label($status)) ?>
                </span>

                <?php if (!empty($product['is_preorder_enabled'])): ?>
                    <span class="badge badge-info">
                        Ön Siparişe Açık
                    </span>
                <?php endif; ?>

                <h1><?= e($product['title'] ?? 'Ürün') ?></h1>

                <p class="producer-name">
                    <?= e($producerName) ?>
                    ·
                    <?= e($product['province_name'] ?? '-') ?>
                    /
                    <?= e($product['district_name'] ?? '-') ?>
                </p>

                <p class="description">
                    <?= nl2br(e($product['description'] ?? 'Bu ürün için açıklama girilmemiş.')) ?>
                </p>

                <div class="price-box">
                    <?= e(formatMoney($product['price'] ?? 0)) ?> / <?= e($unit) ?>
                </div>

                <div class="meta-grid">
                    <div>
                        <strong>Stok</strong>
                        <span><?= e((string) $stockQuantity) ?> <?= e($unit) ?></span>
                    </div>

                    <div>
                        <strong>Hasat</strong>
                        <span>
                            <?= !empty($product['harvest_date'])
                                ? e(date('d.m.Y', strtotime($product['harvest_date'])))
                                : '-'
                            ?>
                        </span>
                    </div>

                    <div>
                        <strong>Puan</strong>
                        <span>
                            ⭐ <?= e((string) ($product['average_rating'] ?? '0.00')) ?>
                            /
                            <?= e((string) ($product['rating_count'] ?? 0)) ?> yorum
                        </span>
                    </div>

                    <div>
                        <strong>Kategori</strong>
                        <span><?= e($product['category_name'] ?? '-') ?></span>
                    </div>
                </div>

                <?php if (!empty($product['is_preorder_enabled'])): ?>
                    <div class="preorder-box">
                        <strong>Ön Sipariş Bilgisi</strong>

                        <p>
                            Son tarih:
                            <?= !empty($product['preorder_deadline'])
                                ? e(date('d.m.Y', strtotime($product['preorder_deadline'])))
                                : '-'
                            ?>
                        </p>

                        <p>
                            Minimum miktar:
                            <?= e((string) ($product['min_preorder_quantity'] ?? '-')) ?>
                            <?= e($unit) ?>
                        </p>
                    </div>
                <?php endif; ?>

                <div class="detail-actions">
                    <?php if (!$user): ?>
                        <a class="btn" href="<?= e(url('login.php')) ?>">
                            Sepete Eklemek İçin Giriş Yap
                        </a>
                    <?php elseif (($user['role'] ?? '') === ROLE_CONSUMER): ?>
                        <?php if ($status === PRODUCT_STATUS_ACTIVE && $stockQuantity > 0): ?>
                            <form method="POST" action="<?= e(url('product-detail.php?id=' . $product['id'])) ?>" class="add-cart-form">
                                <?= csrf_field() ?>

                                <input type="hidden" name="product_id" value="<?= e((string) $product['id']) ?>">

                                <label for="quantity">
                                    Miktar
                                </label>

                                <div class="quantity-row">
                                    <input
                                        type="number"
                                        id="quantity"
                                        name="quantity"
                                        step="0.01"
                                        min="0.01"
                                        max="<?= e((string) $stockQuantity) ?>"
                                        value="1"
                                        required
                                    >

                                    <span><?= e($unit) ?></span>
                                </div>

                                <button class="btn" type="submit">
                                    Sepete Ekle
                                </button>
                            </form>
                        <?php elseif (!empty($product['is_preorder_enabled'])): ?>
                            <button class="btn" type="button" disabled>
                                Ön Sipariş Yakında Aktif
                            </button>
                        <?php else: ?>
                            <button class="btn" type="button" disabled>
                                Stokta Yok
                            </button>
                        <?php endif; ?>

                        <form method="POST" action="<?= e(url('api/favorite-toggle.php')) ?>">
                            <?= csrf_field() ?>

                            <input type="hidden" name="product_id" value="<?= e((string) $product['id']) ?>">
                            <input type="hidden" name="return_to" value="product-detail.php?id=<?= e((string) $product['id']) ?>">

                            <button class="btn btn-secondary" type="submit">
                                <?= !empty($isFavorited) ? 'Favoriden Çıkar' : 'Favoriye Ekle' ?>
                            </button>
                        </form>                      
                    <?php else: ?>
                        <button class="btn" type="button" disabled>
                            Sadece Tüketiciler Satın Alabilir
                        </button>
                    <?php endif; ?>

                    <a class="btn btn-secondary" href="<?= e(url('producer-detail.php?id=' . $product['producer_id'])) ?>">
                        Üretici Profili
                    </a>

                    <a class="btn btn-secondary" href="<?= e(url('products.php')) ?>">
                        Ürünlere Dön
                    </a>
                </div>
            </aside>
        </section>

        <section class="card reviews-box">
            <h2>Yorumlar</h2>

            <?php if (empty($reviews)): ?>
                <p>
                    Bu ürün için henüz görünür yorum yok.
                </p>
            <?php else: ?>
                <div class="review-list">
                    <?php foreach ($reviews as $review): ?>
                        <article class="review-item">
                            <strong>
                                <?= e($review['consumer_name'] ?? 'Tüketici') ?>
                                ·
                                ⭐ <?= e((string) ($review['rating'] ?? 0)) ?>
                            </strong>

                            <p><?= nl2br(e($review['comment'] ?? '')) ?></p>

                            <span>
                                <?= !empty($review['created_at'])
                                    ? e(date('d.m.Y H:i', strtotime($review['created_at'])))
                                    : ''
                                ?>
                            </span>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</main>

<style>
    .detail-layout {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 22px;
    }

    .product-image-large {
        width: 100%;
        min-height: 420px;
        max-height: 520px;
        border-radius: 16px;
        object-fit: cover;
        display: block;
    }

    .product-image-large.placeholder {
        background: #e8f3e9;
        color: #2f7d3d;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
    }

    .image-thumbs {
        margin-top: 14px;
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 10px;
    }

    .image-thumbs img {
        width: 100%;
        height: 80px;
        object-fit: cover;
        border-radius: 10px;
    }

    .product-info h1 {
        margin: 14px 0 8px;
        color: #245c2f;
        font-size: 36px;
    }

    .producer-name,
    .description,
    .reviews-box p,
    .preorder-box p,
    .empty-state p {
        color: #526052;
        line-height: 1.6;
    }

    .price-box {
        margin: 20px 0;
        font-size: 28px;
        font-weight: bold;
        color: #245c2f;
    }

    .meta-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px;
        margin-bottom: 22px;
    }

    .meta-grid div,
    .preorder-box {
        padding: 14px;
        border-radius: 12px;
        background: #f8fbf6;
    }

    .meta-grid strong,
    .meta-grid span {
        display: block;
    }

    .meta-grid strong,
    .preorder-box strong {
        color: #245c2f;
        margin-bottom: 5px;
    }

    .meta-grid span {
        color: #526052;
    }

    .badge {
        display: inline-block;
        padding: 5px 9px;
        border-radius: 999px;
        font-size: 13px;
        font-weight: bold;
        margin-right: 6px;
    }

    .badge-success {
        background: #e7f7e8;
        color: #236b2c;
    }

    .badge-warning {
        background: #fff5d6;
        color: #8a6200;
    }

    .badge-info {
        background: #e8f1ff;
        color: #1f4e8c;
    }

    .badge-muted {
        background: #edf1ea;
        color: #526052;
    }

    .detail-actions {
        margin-top: 18px;
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        align-items: flex-start;
    }

    .add-cart-form {
        width: 100%;
        padding: 16px;
        border-radius: 14px;
        background: #f8fbf6;
        display: grid;
        gap: 10px;
        margin-bottom: 8px;
    }

    .add-cart-form label {
        color: #245c2f;
        font-weight: bold;
    }

    .quantity-row {
        display: flex;
        gap: 10px;
        align-items: center;
    }

    .quantity-row input {
        width: 130px;
        padding: 11px;
        border: 1px solid #d5dccf;
        border-radius: 9px;
    }

    .quantity-row span {
        color: #526052;
        font-weight: bold;
    }

    button:disabled {
        opacity: .65;
        cursor: not-allowed;
    }

    .reviews-box {
        margin-top: 22px;
    }

    .reviews-box h2,
    .empty-state h1 {
        margin-top: 0;
        color: #245c2f;
    }

    .review-list {
        display: grid;
        gap: 14px;
    }

    .review-item {
        padding: 16px;
        border-radius: 12px;
        background: #f8fbf6;
    }

    .review-item strong {
        color: #245c2f;
    }

    .review-item span {
        color: #718071;
        font-size: 14px;
    }

    .empty-state {
        text-align: center;
        padding: 36px;
    }

    @media (max-width: 900px) {
        .detail-layout {
            grid-template-columns: 1fr;
        }

        .product-image-large {
            min-height: 260px;
        }

        .meta-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<?php require APP_PATH . '/Views/layouts/footer.php'; ?>