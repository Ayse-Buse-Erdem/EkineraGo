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
            'active' => 'Satışta',
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

if (!function_exists('detail_initial')) {
    function detail_initial(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return 'E';
        }

        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, 1, 'UTF-8');
        }

        return substr($value, 0, 1);
    }
}
?>

<main class="product-detail-page">
    <?php if (!$product): ?>
        <section class="detail-empty-wrap">
            <div class="detail-empty-card">
                <div class="empty-icon">🌿</div>
                <span class="eyebrow">Ürün bulunamadı</span>

                <h1>Aradığın ürün şu anda görünmüyor.</h1>

                <p>
                    Ürün silinmiş, pasifleştirilmiş veya bağlantı hatalı olabilir.
                    Taze ürünleri keşfetmeye devam edebilirsin.
                </p>

                <a class="detail-btn detail-btn-primary" href="<?= e(url('products.php')) ?>">
                    Ürünlere Dön
                </a>
            </div>
        </section>
    <?php else: ?>
        <?php
            $productId = (int) ($product['id'] ?? $productId);
            $producerId = (int) ($product['producer_id'] ?? 0);

            $unit = detail_unit_label($product['unit_type'] ?? 'kg');
            $status = $product['status'] ?? 'active';
            $producerName = $product['store_name'] ?: ($product['producer_name'] ?? 'Üretici');
            $images = $product['images'] ?? [];
            $coverImage = $product['cover_image']['image_path'] ?? null;
            $stockQuantity = (float) ($product['stock_quantity'] ?? 0);

            $roleConsumer = defined('ROLE_CONSUMER') ? ROLE_CONSUMER : 'consumer';
            $productActiveStatus = defined('PRODUCT_STATUS_ACTIVE') ? PRODUCT_STATUS_ACTIVE : 'active';

            $isConsumerUser = $user && (($user['role'] ?? '') === $roleConsumer);
            $consumerId = $isConsumerUser ? (int) currentUserId() : 0;

            $isFavorited = !empty($data['isFavorited'])
                || !empty($data['is_favorited'])
                || !empty($product['is_favorited']);

            $reviews = $product['reviews'] ?? ($data['reviews'] ?? []);
            $reviewableOrderItems = [];

            if (class_exists('ReviewService')) {
                $reviewService = new ReviewService();

                $reviews = $reviewService->getVisibleByProductId($productId, 30);

                if ($isConsumerUser) {
                    $reviewableOrderItems = $reviewService->getReviewableOrderItemsForProduct($consumerId, $productId);
                }
            }

            $questions = [];

            if (class_exists('ProductQuestionService')) {
                $questionService = new ProductQuestionService();

                if (method_exists($questionService, 'getPublicQuestionsByProductId')) {
                    $questions = $questionService->getPublicQuestionsByProductId($productId);
                }
            }

            $reviewStoreUrl = url('consumer/orders.php?review_order_item_id=0');
            $questionStoreUrl = url('api/product-question-store.php');

            $ratingValue = (float) ($product['average_rating'] ?? 0);
            $ratingText = number_format($ratingValue, 1, ',', '.');
            $ratingCount = (int) ($product['rating_count'] ?? count($reviews));

            $categoryName = $product['category_name'] ?? 'Kategori yok';
            $provinceName = $product['province_name'] ?? '-';
            $districtName = $product['district_name'] ?? '-';

            $harvestDate = !empty($product['harvest_date'])
                ? date('d.m.Y', strtotime($product['harvest_date']))
                : '-';

            $hasStock = $status === $productActiveStatus && $stockQuantity > 0;
            $stockText = rtrim(rtrim(number_format($stockQuantity, 2, ',', '.'), '0'), ',');
        ?>

        <section class="detail-hero">
            <div class="detail-hero-bg detail-blob-one"></div>
            <div class="detail-hero-bg detail-blob-two"></div>

            <div class="detail-hero-inner">
                <nav class="detail-breadcrumb" aria-label="Sayfa yolu">
                    <a href="<?= e(url('index.php')) ?>">Ana Sayfa</a>
                    <span>/</span>
                    <a href="<?= e(url('products.php')) ?>">Ürünler</a>
                    <span>/</span>
                    <strong><?= e($product['title'] ?? 'Ürün') ?></strong>
                </nav>

                <div class="detail-hero-copy">
                    <span class="eyebrow">Taze Ürün, Doğrudan Kaynak</span>

                    <h1><?= e($product['title'] ?? 'Ürün') ?></h1>

                    <p>
                        <?= e($producerName) ?> tarafından listelenen bu ürünü doğrudan üretici
                        kaynağından inceleyebilir, sepete ekleyebilir ve ürün hakkında soru sorabilirsin.
                    </p>
                </div>
            </div>
        </section>

        <section class="detail-shell">
            <div class="detail-layout">
                <div class="gallery-card glass-card">
                    <div class="main-image-wrap">
                        <?php if ($coverImage): ?>
                            <img
                                id="detail-main-image"
                                class="product-image-large"
                                src="<?= e(url($coverImage)) ?>"
                                alt="<?= e($product['title'] ?? 'Ürün') ?>"
                            >
                        <?php else: ?>
                            <div id="detail-main-image-placeholder" class="product-image-large placeholder">
                                <span>🌱</span>
                                Ürün Fotoğrafı
                            </div>
                        <?php endif; ?>

                        <div class="image-floating-badges">
                            <span class="detail-badge <?= e(detail_status_badge($status)) ?>">
                                <?= e(detail_status_label($status)) ?>
                            </span>

                            <?php if (!empty($product['is_preorder_enabled'])): ?>
                                <span class="detail-badge badge-info">
                                    Ön Sipariş
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!empty($images)): ?>
                        <div class="image-thumbs" aria-label="Ürün görselleri">
                            <?php foreach ($images as $index => $image): ?>
                                <button
                                    type="button"
                                    class="thumb-button <?= $index === 0 ? 'is-active' : '' ?>"
                                    data-image-src="<?= e(url($image['image_path'])) ?>"
                                    aria-label="Ürün görseli <?= e((string) ($index + 1)) ?>"
                                >
                                    <img
                                        src="<?= e(url($image['image_path'])) ?>"
                                        alt="<?= e($product['title'] ?? 'Ürün') ?>"
                                    >
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <aside class="buy-card glass-card">
                    <div class="buy-topline">
                        <div>
                            <span class="detail-badge <?= e(detail_status_badge($status)) ?>">
                                <?= e(detail_status_label($status)) ?>
                            </span>

                            <?php if (!empty($product['is_preorder_enabled'])): ?>
                                <span class="detail-badge badge-info">
                                    Ön Siparişe Açık
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="rating-pill">
                            <span>⭐</span>
                            <strong><?= e($ratingText) ?></strong>
                            <small><?= e((string) $ratingCount) ?> yorum</small>
                        </div>
                    </div>

                    <h2><?= e($product['title'] ?? 'Ürün') ?></h2>

                    <a class="producer-mini" href="<?= e(url('producer-detail.php?id=' . $producerId)) ?>">
                        <span class="producer-avatar">
                            <?= e(detail_initial($producerName)) ?>
                        </span>

                        <span>
                            <strong><?= e($producerName) ?></strong>
                            <small><?= e($provinceName) ?> / <?= e($districtName) ?></small>
                        </span>
                    </a>

                    <div class="price-panel">
                        <span>Ürün fiyatı</span>
                        <strong><?= e(formatMoney($product['price'] ?? 0)) ?></strong>
                        <small>/ <?= e($unit) ?></small>
                    </div>

                    <div class="quick-meta-grid">
                        <div>
                            <span>Stok</span>
                            <strong><?= e($stockText) ?> <?= e($unit) ?></strong>
                        </div>

                        <div>
                            <span>Hasat</span>
                            <strong><?= e($harvestDate) ?></strong>
                        </div>

                        <div>
                            <span>Kategori</span>
                            <strong><?= e($categoryName) ?></strong>
                        </div>

                        <div>
                            <span>Konum</span>
                            <strong><?= e($districtName) ?></strong>
                        </div>
                    </div>

                    <?php if (!empty($product['is_preorder_enabled'])): ?>
                        <div class="preorder-box">
                            <div>
                                <strong>Ön Sipariş Bilgisi</strong>

                                <p>
                                    Son tarih:
                                    <?= !empty($product['preorder_deadline'])
                                        ? e(date('d.m.Y', strtotime($product['preorder_deadline'])))
                                        : '-'
                                    ?>
                                </p>
                            </div>

                            <span>
                                Min.
                                <?= e((string) ($product['min_preorder_quantity'] ?? '-')) ?>
                                <?= e($unit) ?>
                            </span>
                        </div>
                    <?php endif; ?>

                    <div class="detail-actions">
                        <?php if (!$user): ?>
                            <a class="detail-btn detail-btn-primary full" href="<?= e(url('login.php')) ?>">
                                Sepete Eklemek İçin Giriş Yap
                            </a>
                        <?php elseif ($isConsumerUser): ?>
                            <?php if ($hasStock): ?>
                                <form method="POST" action="<?= e(url('product-detail.php?id=' . $productId)) ?>" class="add-cart-form">
                                    <?= csrf_field() ?>

                                    <input type="hidden" name="product_id" value="<?= e((string) $productId) ?>">

                                    <label for="quantity">Miktar Seç</label>

                                    <div class="quantity-control">
                                        <button type="button" class="quantity-btn" data-quantity-action="decrease">
                                            −
                                        </button>

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

                                        <button type="button" class="quantity-btn" data-quantity-action="increase">
                                            +
                                        </button>

                                        <span><?= e($unit) ?></span>
                                    </div>

                                    <button class="detail-btn detail-btn-primary full" type="submit">
                                        Sepete Ekle
                                    </button>
                                </form>
                            <?php elseif (!empty($product['is_preorder_enabled'])): ?>
                                <button class="detail-btn detail-btn-primary full" type="button" disabled>
                                    Ön Sipariş Yakında Aktif
                                </button>
                            <?php else: ?>
                                <button class="detail-btn detail-btn-primary full" type="button" disabled>
                                    Stokta Yok
                                </button>
                            <?php endif; ?>

                            <form method="POST" action="<?= e(url('api/favorite-toggle.php')) ?>" class="favorite-form">
                                <?= csrf_field() ?>

                                <input type="hidden" name="product_id" value="<?= e((string) $productId) ?>">
                                <input type="hidden" name="return_to" value="product-detail.php?id=<?= e((string) $productId) ?>">

                                <button class="detail-btn detail-btn-ghost full" type="submit">
                                    <?= $isFavorited ? '♥ Favoriden Çıkar' : '♡ Favoriye Ekle' ?>
                                </button>
                            </form>
                        <?php else: ?>
                            <button class="detail-btn detail-btn-primary full" type="button" disabled>
                                Sadece Tüketiciler Satın Alabilir
                            </button>
                        <?php endif; ?>

                        <div class="secondary-action-grid">
                            <a class="detail-btn detail-btn-light" href="<?= e(url('producer-detail.php?id=' . $producerId)) ?>">
                                Üretici Profili
                            </a>

                            <a class="detail-btn detail-btn-light" href="<?= e(url('products.php')) ?>">
                                Ürünlere Dön
                            </a>
                        </div>
                    </div>
                </aside>
            </div>

            <section class="description-card glass-card">
                <div class="section-heading">
                    <span class="section-icon">🧺</span>

                    <div>
                        <h2>Ürün Açıklaması</h2>
                        <p>Üreticinin ürün hakkında paylaştığı detaylar.</p>
                    </div>
                </div>

                <p class="description-text">
                    <?= nl2br(e($product['description'] ?? 'Bu ürün için açıklama girilmemiş.')) ?>
                </p>
            </section>

            <section class="content-card glass-card reviews-box" id="reviews">
                <div class="section-heading section-heading-spaced">
                    <div class="heading-left">
                        <span class="section-icon">⭐</span>

                        <div>
                            <h2>Yorumlar</h2>
                            <p>Bu ürünü teslim alan tüketicilerin değerlendirmeleri.</p>
                        </div>
                    </div>

                    <span class="count-pill"><?= e((string) count($reviews)) ?> yorum</span>
                </div>

                <?php if ($isConsumerUser): ?>
                    <?php if (!empty($reviewableOrderItems)): ?>
                        <div class="inline-form-box">
                            <h3>Bu Ürüne Yorum Yaz</h3>

                            <div id="product-review-message" class="ajax-message" hidden></div>

                            <form
                                method="POST"
                                action="<?= e($reviewStoreUrl) ?>"
                                id="product-review-form"
                                class="ajax-form"
                            >
                                <?= csrf_field() ?>

                                <input type="hidden" name="product_id" value="<?= e((string) $productId) ?>">

                                <?php if (count($reviewableOrderItems) === 1): ?>
                                    <input
                                        type="hidden"
                                        name="order_item_id"
                                        value="<?= e((string) $reviewableOrderItems[0]['order_item_id']) ?>"
                                    >

                                    <p class="helper-text">
                                        Sipariş No:
                                        <strong><?= e($reviewableOrderItems[0]['order_no'] ?? '-') ?></strong>
                                    </p>
                                <?php else: ?>
                                    <div class="form-group">
                                        <label for="review_order_item_id">Yorum yapılacak sipariş</label>

                                        <select id="review_order_item_id" name="order_item_id" required>
                                            <option value="">Sipariş seç</option>

                                            <?php foreach ($reviewableOrderItems as $reviewableItem): ?>
                                                <option value="<?= e((string) $reviewableItem['order_item_id']) ?>">
                                                    <?= e($reviewableItem['order_no'] ?? 'Sipariş') ?>
                                                    -
                                                    <?= !empty($reviewableItem['order_created_at'])
                                                        ? e(date('d.m.Y', strtotime($reviewableItem['order_created_at'])))
                                                        : ''
                                                    ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                <?php endif; ?>

                                <div class="form-group">
                                    <label for="product-review-rating">Puan</label>

                                    <select id="product-review-rating" name="rating" required>
                                        <option value="">Puan seç</option>
                                        <option value="5">5 - Çok iyi</option>
                                        <option value="4">4 - İyi</option>
                                        <option value="3">3 - Orta</option>
                                        <option value="2">2 - Kötü</option>
                                        <option value="1">1 - Çok kötü</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="product-review-comment">Yorum</label>

                                    <textarea
                                        id="product-review-comment"
                                        name="comment"
                                        rows="4"
                                        maxlength="1000"
                                        placeholder="Ürünün tazeliği, paketleme ve teslimat deneyimini yaz..."
                                    ></textarea>
                                </div>

                                <button class="detail-btn detail-btn-primary" type="submit" id="product-review-submit">
                                    Yorumu Gönder
                                </button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="inline-info-box">
                            Bu ürüne yorum yazabilmek için ürünü satın almış ve siparişinin teslim edilmiş olması gerekir.
                        </div>
                    <?php endif; ?>
                <?php elseif (!$user): ?>
                    <div class="inline-info-box">
                        Yorum yazmak için
                        <a href="<?= e(url('login.php')) ?>">giriş yapmalısın</a>.
                    </div>
                <?php endif; ?>

                <?php if (empty($reviews)): ?>
                    <div class="soft-empty" id="empty-reviews-text">
                        <span>💬</span>
                        <p>Bu ürün için henüz görünür yorum yok.</p>
                    </div>
                <?php else: ?>
                    <div class="review-list" id="review-list">
                        <?php foreach ($reviews as $review): ?>
                            <article class="review-item">
                                <div class="item-avatar">
                                    <?= e(detail_initial($review['consumer_name'] ?? 'Tüketici')) ?>
                                </div>

                                <div>
                                    <strong>
                                        <?= e($review['consumer_name'] ?? 'Tüketici') ?>
                                        <span>⭐ <?= e((string) ($review['rating'] ?? 0)) ?></span>
                                    </strong>

                                    <p><?= nl2br(e($review['comment'] ?? '')) ?></p>

                                    <small>
                                        <?= !empty($review['created_at'])
                                            ? e(date('d.m.Y H:i', strtotime($review['created_at'])))
                                            : ''
                                        ?>
                                    </small>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="content-card glass-card questions-box" id="questions">
                <div class="section-heading section-heading-spaced">
                    <div class="heading-left">
                        <span class="section-icon">❔</span>

                        <div>
                            <h2>Satıcıya Sorular</h2>
                            <p>Ürünle ilgili merak ettiklerini üreticiye sorabilirsin.</p>
                        </div>
                    </div>

                    <span class="count-pill"><?= e((string) count($questions)) ?> soru</span>
                </div>

                <?php if ($isConsumerUser): ?>
                    <div class="inline-form-box">
                        <h3>Satıcıya Soru Sor</h3>

                        <div id="product-question-message" class="ajax-message" hidden></div>

                        <form
                            method="POST"
                            action="<?= e($questionStoreUrl) ?>"
                            id="product-question-form"
                            class="ajax-form"
                        >
                            <?= csrf_field() ?>

                            <input type="hidden" name="product_id" value="<?= e((string) $productId) ?>">
                            <input type="hidden" name="producer_id" value="<?= e((string) $producerId) ?>">

                            <div class="form-group">
                                <label for="product-question-text">Sorun</label>

                                <textarea
                                    id="product-question-text"
                                    name="question"
                                    rows="4"
                                    maxlength="1000"
                                    placeholder="Örneğin: Ürün ne zaman hasat edildi? Kargo hangi gün çıkar?"
                                    required
                                ></textarea>
                            </div>

                            <button class="detail-btn detail-btn-primary" type="submit" id="product-question-submit">
                                Soruyu Gönder
                            </button>
                        </form>
                    </div>
                <?php elseif (!$user): ?>
                    <div class="inline-info-box">
                        Satıcıya soru sormak için
                        <a href="<?= e(url('login.php')) ?>">giriş yapmalısın</a>.
                    </div>
                <?php elseif ($producerId === (int) currentUserId()): ?>
                    <div class="inline-info-box">
                        Bu ürün sana ait. Gelen soruları üretici panelinden cevaplayacaksın.
                    </div>
                <?php endif; ?>

                <?php if (empty($questions)): ?>
                    <div class="soft-empty" id="empty-questions-text">
                        <span>🌾</span>
                        <p>Bu ürün için henüz soru sorulmamış.</p>
                    </div>
                <?php else: ?>
                    <div class="question-list" id="question-list">
                        <?php foreach ($questions as $question): ?>
                            <article class="question-item">
                                <div class="question-content">
                                    <div class="item-avatar">
                                        <?= e(detail_initial($question['consumer_name'] ?? 'Tüketici')) ?>
                                    </div>

                                    <div>
                                        <strong>
                                            <?= e($question['consumer_name'] ?? 'Tüketici') ?> sordu:
                                        </strong>

                                        <p><?= nl2br(e($question['question'] ?? '')) ?></p>

                                        <small>
                                            <?= !empty($question['created_at'])
                                                ? e(date('d.m.Y H:i', strtotime($question['created_at'])))
                                                : ''
                                            ?>
                                        </small>
                                    </div>
                                </div>

                                <?php if (!empty($question['answer'])): ?>
                                    <div class="answer-content">
                                        <strong><?= e($producerName) ?> cevapladı:</strong>

                                        <p><?= nl2br(e($question['answer'])) ?></p>

                                        <small>
                                            <?= !empty($question['answered_at'])
                                                ? e(date('d.m.Y H:i', strtotime($question['answered_at'])))
                                                : ''
                                            ?>
                                        </small>
                                    </div>
                                <?php else: ?>
                                    <div class="answer-pending">
                                        Üretici cevabı bekleniyor.
                                    </div>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </section>
    <?php endif; ?>
</main>

<style>
    :root {
        --detail-green-900: #163d22;
        --detail-green-800: #245c2f;
        --detail-green-700: #2f7d3d;
        --detail-green-600: #3f9650;
        --detail-green-100: #eaf6e8;
        --detail-green-50: #f6fbf4;
        --detail-cream: #fffaf1;
        --detail-yellow: #f2bf4d;
        --detail-text: #1e2b21;
        --detail-muted: #687669;
        --detail-border: rgba(47, 125, 61, .14);
        --detail-shadow: 0 24px 70px rgba(31, 79, 43, .12);
        --detail-radius-lg: 28px;
    }

    body.page-product-detail {
        background:
            radial-gradient(circle at 15% 12%, rgba(196, 231, 177, .55), transparent 28%),
            linear-gradient(180deg, #f8fbf2 0%, #f3f8ed 48%, #ffffff 100%);
    }

    .product-detail-page {
        overflow: hidden;
    }

    .detail-hero {
        position: relative;
        min-height: 260px;
        padding: 34px 18px 74px;
        background:
            radial-gradient(circle at 82% 18%, rgba(242, 191, 77, .30), transparent 26%),
            radial-gradient(circle at 12% 78%, rgba(255, 255, 255, .16), transparent 24%),
            linear-gradient(135deg, rgba(36, 92, 47, .96), rgba(47, 125, 61, .86));
        color: #ffffff;
    }

    .detail-hero::after {
        content: '';
        position: absolute;
        inset: auto 0 -1px;
        height: 78px;
        background: linear-gradient(180deg, rgba(246, 251, 244, 0), #f6fbf4 82%);
        pointer-events: none;
    }

    .detail-hero-inner,
    .detail-shell,
    .detail-empty-wrap {
        width: min(1180px, calc(100% - 32px));
        margin: 0 auto;
    }

    .detail-hero-inner {
        position: relative;
        z-index: 2;
    }

    .detail-hero-bg {
        position: absolute;
        border-radius: 999px;
        filter: blur(2px);
        opacity: .45;
        pointer-events: none;
    }

    .detail-blob-one {
        width: 220px;
        height: 220px;
        right: 10%;
        top: 34px;
        background: rgba(242, 191, 77, .28);
    }

    .detail-blob-two {
        width: 140px;
        height: 140px;
        left: 8%;
        bottom: 26px;
        background: rgba(255, 255, 255, .20);
    }

    .detail-breadcrumb {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 9px;
        font-size: 14px;
        margin-bottom: 32px;
        color: rgba(255, 255, 255, .76);
    }

    .detail-breadcrumb a {
        color: #ffffff;
        text-decoration: none;
        font-weight: 700;
    }

    .detail-breadcrumb strong {
        color: #ffffff;
        font-weight: 800;
    }

    .detail-hero-copy {
        max-width: 720px;
    }

    .eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 13px;
        border-radius: 999px;
        background: rgba(255, 255, 255, .16);
        border: 1px solid rgba(255, 255, 255, .28);
        color: inherit;
        font-size: 13px;
        font-weight: 800;
        letter-spacing: .04em;
        text-transform: uppercase;
    }

    .detail-hero-copy h1 {
        margin: 18px 0 12px;
        font-size: clamp(34px, 5vw, 58px);
        line-height: 1.03;
        letter-spacing: -.04em;
    }

    .detail-hero-copy p {
        max-width: 650px;
        margin: 0;
        color: rgba(255, 255, 255, .86);
        font-size: 17px;
        line-height: 1.7;
    }

    .detail-shell {
        position: relative;
        z-index: 3;
        margin-top: -54px;
        padding-bottom: 54px;
    }

    .glass-card {
        background: rgba(255, 255, 255, .92);
        border: 1px solid rgba(255, 255, 255, .72);
        border-radius: var(--detail-radius-lg);
        box-shadow: var(--detail-shadow);
        backdrop-filter: blur(16px);
    }

    .detail-layout {
        display: grid;
        grid-template-columns: minmax(0, 1.08fr) minmax(360px, .92fr);
        gap: 24px;
        align-items: start;
    }

    .gallery-card,
    .buy-card,
    .description-card,
    .content-card {
        padding: 18px;
    }

    .main-image-wrap {
        position: relative;
        overflow: hidden;
        border-radius: 24px;
        background: var(--detail-green-100);
    }

    .product-image-large {
        width: 100%;
        height: clamp(340px, 43vw, 545px);
        border-radius: 24px;
        object-fit: cover;
        display: block;
        transition: transform .45s ease, opacity .25s ease;
    }

    .main-image-wrap:hover .product-image-large {
        transform: scale(1.025);
    }

    .product-image-large.placeholder {
        background:
            radial-gradient(circle at 30% 25%, rgba(255, 255, 255, .75), transparent 28%),
            linear-gradient(135deg, #e8f3e9, #f8fbf6);
        color: var(--detail-green-700);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 12px;
        font-weight: 900;
        font-size: 22px;
    }

    .product-image-large.placeholder span {
        font-size: 52px;
    }

    .image-floating-badges {
        position: absolute;
        top: 16px;
        left: 16px;
        right: 16px;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        z-index: 2;
    }

    .image-thumbs {
        margin-top: 14px;
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 10px;
    }

    .thumb-button {
        border: 2px solid transparent;
        background: transparent;
        padding: 0;
        border-radius: 16px;
        overflow: hidden;
        cursor: pointer;
        box-shadow: 0 8px 24px rgba(31, 79, 43, .08);
        transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease;
    }

    .thumb-button:hover,
    .thumb-button.is-active {
        transform: translateY(-2px);
        border-color: var(--detail-green-700);
        box-shadow: 0 14px 28px rgba(31, 79, 43, .16);
    }

    .thumb-button img {
        width: 100%;
        height: 82px;
        object-fit: cover;
        display: block;
    }

    .buy-card {
        position: sticky;
        top: 22px;
    }

    .buy-topline,
    .section-heading-spaced,
    .heading-left {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
    }

    .heading-left {
        justify-content: flex-start;
    }

    .detail-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 11px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 900;
        line-height: 1;
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

    .rating-pill,
    .count-pill {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 9px 12px;
        border-radius: 999px;
        background: var(--detail-cream);
        border: 1px solid rgba(242, 191, 77, .32);
        color: var(--detail-green-900);
        white-space: nowrap;
    }

    .rating-pill small {
        color: var(--detail-muted);
        font-weight: 700;
    }

    .count-pill {
        font-size: 13px;
        font-weight: 900;
    }

    .buy-card h2 {
        margin: 18px 0 14px;
        color: var(--detail-green-900);
        font-size: clamp(28px, 4vw, 42px);
        line-height: 1.08;
        letter-spacing: -.035em;
    }

    .producer-mini {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 13px;
        border-radius: 18px;
        background: var(--detail-green-50);
        border: 1px solid var(--detail-border);
        color: var(--detail-text);
        text-decoration: none;
        transition: transform .2s ease, box-shadow .2s ease;
    }

    .producer-mini:hover {
        transform: translateY(-2px);
        box-shadow: 0 16px 36px rgba(31, 79, 43, .10);
    }

    .producer-avatar,
    .item-avatar {
        width: 46px;
        height: 46px;
        border-radius: 15px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        color: #ffffff;
        background: linear-gradient(135deg, var(--detail-green-700), var(--detail-green-600));
        font-weight: 900;
        box-shadow: 0 12px 25px rgba(47, 125, 61, .24);
    }

    .producer-mini strong,
    .producer-mini small {
        display: block;
    }

    .producer-mini strong {
        color: var(--detail-green-900);
        font-size: 15px;
    }

    .producer-mini small {
        margin-top: 3px;
        color: var(--detail-muted);
        font-weight: 700;
    }

    .price-panel {
        margin: 18px 0;
        padding: 18px;
        border-radius: 22px;
        background:
            radial-gradient(circle at 85% 18%, rgba(242, 191, 77, .22), transparent 30%),
            linear-gradient(135deg, var(--detail-green-900), var(--detail-green-700));
        color: #ffffff;
        overflow: hidden;
    }

    .price-panel span,
    .price-panel small {
        color: rgba(255, 255, 255, .76);
        font-weight: 800;
    }

    .price-panel strong {
        display: inline-block;
        margin: 7px 6px 0 0;
        font-size: 34px;
        line-height: 1;
        letter-spacing: -.03em;
    }

    .quick-meta-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }

    .quick-meta-grid div {
        padding: 14px;
        border-radius: 17px;
        background: #fbfdf8;
        border: 1px solid var(--detail-border);
    }

    .quick-meta-grid span,
    .quick-meta-grid strong {
        display: block;
    }

    .quick-meta-grid span {
        color: var(--detail-muted);
        font-size: 12px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: 6px;
    }

    .quick-meta-grid strong {
        color: var(--detail-green-900);
        font-size: 15px;
    }

    .preorder-box {
        margin-top: 14px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding: 15px;
        border-radius: 18px;
        background: #fff8df;
        border: 1px solid rgba(242, 191, 77, .35);
    }

    .preorder-box strong {
        color: #7a5400;
    }

    .preorder-box p {
        margin: 5px 0 0;
        color: #8a6200;
        font-weight: 700;
    }

    .preorder-box span {
        padding: 9px 12px;
        border-radius: 999px;
        background: #ffffff;
        color: #7a5400;
        font-weight: 900;
        white-space: nowrap;
    }

    .detail-actions {
        margin-top: 18px;
        display: grid;
        gap: 12px;
    }

    .add-cart-form {
        display: grid;
        gap: 11px;
    }

    .add-cart-form label,
    .ajax-form label {
        color: var(--detail-green-900);
        font-weight: 900;
    }

    .quantity-control {
        display: grid;
        grid-template-columns: 42px minmax(80px, 1fr) 42px auto;
        align-items: center;
        gap: 8px;
        padding: 8px;
        border-radius: 18px;
        background: var(--detail-green-50);
        border: 1px solid var(--detail-border);
    }

    .quantity-control input {
        width: 100%;
        padding: 12px;
        border: 0;
        outline: 0;
        border-radius: 13px;
        background: #ffffff;
        color: var(--detail-text);
        text-align: center;
        font-weight: 900;
    }

    .quantity-control span {
        color: var(--detail-muted);
        font-weight: 900;
        padding-right: 8px;
    }

    .quantity-btn {
        width: 42px;
        height: 42px;
        border: 0;
        border-radius: 13px;
        background: #ffffff;
        color: var(--detail-green-800);
        font-size: 20px;
        font-weight: 900;
        cursor: pointer;
        box-shadow: 0 8px 18px rgba(31, 79, 43, .08);
    }

    .quantity-btn:hover {
        background: var(--detail-green-100);
    }

    .favorite-form {
        margin: 0;
    }

    .secondary-action-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }

    .detail-btn {
        min-height: 46px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 12px 18px;
        border: 0;
        border-radius: 15px;
        text-decoration: none;
        font-weight: 900;
        cursor: pointer;
        transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
        font-family: inherit;
        font-size: 14px;
    }

    .detail-btn:hover {
        transform: translateY(-2px);
    }

    .detail-btn.full {
        width: 100%;
    }

    .detail-btn-primary {
        background: linear-gradient(135deg, var(--detail-green-700), var(--detail-green-900));
        color: #ffffff;
        box-shadow: 0 16px 32px rgba(47, 125, 61, .24);
    }

    .detail-btn-ghost {
        background: #ffffff;
        color: var(--detail-green-800);
        border: 1px solid var(--detail-border);
    }

    .detail-btn-light {
        background: var(--detail-green-50);
        color: var(--detail-green-800);
        border: 1px solid var(--detail-border);
    }

    .detail-btn:disabled,
    button:disabled {
        opacity: .65;
        cursor: not-allowed;
        transform: none;
    }

    .description-card,
    .content-card {
        margin-top: 22px;
    }

    .section-heading {
        display: flex;
        align-items: flex-start;
        gap: 13px;
        margin-bottom: 18px;
        padding-bottom: 16px;
        border-bottom: 1px solid rgba(47, 125, 61, .10);
    }

    .section-heading h2,
    .section-heading p {
        margin: 0;
    }

    .section-heading h2 {
        color: var(--detail-green-900);
        font-size: 25px;
        letter-spacing: -.02em;
    }

    .section-heading p,
    .description-text,
    .helper-text,
    .inline-info-box,
    .review-item p,
    .question-item p,
    .soft-empty p {
        color: var(--detail-muted);
        line-height: 1.7;
    }

    .section-icon {
        width: 44px;
        height: 44px;
        border-radius: 15px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--detail-green-100);
        font-size: 21px;
        flex: 0 0 auto;
    }

    .description-text {
        margin: 0;
        font-size: 16px;
    }

    .inline-form-box,
    .inline-info-box {
        padding: 17px;
        border-radius: 20px;
        background: #fbfdf8;
        border: 1px solid var(--detail-border);
        margin-bottom: 18px;
    }

    .inline-form-box h3 {
        margin: 0 0 14px;
        color: var(--detail-green-900);
        font-size: 20px;
    }

    .inline-info-box a {
        color: var(--detail-green-800);
        font-weight: 900;
    }

    .ajax-form {
        display: grid;
        gap: 14px;
    }

    .form-group {
        display: grid;
        gap: 8px;
    }

    .ajax-form select,
    .ajax-form textarea {
        width: 100%;
        padding: 13px 14px;
        border: 1px solid rgba(47, 125, 61, .18);
        border-radius: 15px;
        background: #ffffff;
        color: var(--detail-text);
        font-family: inherit;
        outline: none;
        transition: border-color .18s ease, box-shadow .18s ease;
    }

    .ajax-form select:focus,
    .ajax-form textarea:focus {
        border-color: var(--detail-green-700);
        box-shadow: 0 0 0 4px rgba(47, 125, 61, .10);
    }

    .ajax-message {
        margin-bottom: 14px;
        padding: 12px 14px;
        border-radius: 14px;
        font-weight: 800;
    }

    .ajax-message.success {
        background: #e7f7e8;
        color: #236b2c;
    }

    .ajax-message.error {
        background: #fdeaea;
        color: #9b111e;
    }

    .review-list,
    .question-list {
        display: grid;
        gap: 14px;
    }

    .review-item,
    .question-item {
        padding: 16px;
        border-radius: 20px;
        background: #fbfdf8;
        border: 1px solid var(--detail-border);
    }

    .review-item {
        display: grid;
        grid-template-columns: 46px 1fr;
        gap: 13px;
        align-items: flex-start;
    }

    .review-item strong,
    .question-item strong {
        color: var(--detail-green-900);
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
    }

    .review-item strong span {
        color: #9a6a00;
        font-size: 13px;
    }

    .review-item p,
    .question-item p {
        margin: 8px 0;
    }

    .review-item small,
    .question-item small,
    .answer-content small {
        color: #839184;
        font-size: 13px;
        font-weight: 700;
    }

    .question-content {
        display: grid;
        grid-template-columns: 46px 1fr;
        gap: 13px;
        align-items: flex-start;
    }

    .answer-content {
        margin-top: 14px;
        margin-left: 59px;
        padding: 15px;
        border-radius: 18px;
        background: #eef8ef;
        border-left: 4px solid var(--detail-green-700);
    }

    .answer-content strong {
        color: var(--detail-green-800);
    }

    .answer-pending {
        margin-top: 14px;
        margin-left: 59px;
        color: var(--detail-muted);
        font-size: 14px;
        font-weight: 900;
        padding: 12px 14px;
        border-radius: 15px;
        background: #f1f5ee;
    }

    .soft-empty {
        display: grid;
        place-items: center;
        gap: 8px;
        padding: 28px 18px;
        border-radius: 20px;
        background: #fbfdf8;
        border: 1px dashed rgba(47, 125, 61, .24);
        text-align: center;
    }

    .soft-empty span {
        font-size: 32px;
    }

    .soft-empty p {
        margin: 0;
        font-weight: 800;
    }

    .detail-empty-wrap {
        min-height: 72vh;
        display: grid;
        place-items: center;
        padding: 54px 0;
    }

    .detail-empty-card {
        width: min(620px, 100%);
        padding: 42px;
        border-radius: 30px;
        text-align: center;
        background: #ffffff;
        box-shadow: var(--detail-shadow);
        border: 1px solid var(--detail-border);
    }

    .detail-empty-card .eyebrow {
        color: var(--detail-green-800);
        background: var(--detail-green-100);
        border-color: transparent;
    }

    .empty-icon {
        width: 76px;
        height: 76px;
        margin: 0 auto 14px;
        display: grid;
        place-items: center;
        border-radius: 24px;
        background: var(--detail-green-100);
        font-size: 36px;
    }

    .detail-empty-card h1 {
        margin: 18px 0 10px;
        color: var(--detail-green-900);
        font-size: 34px;
        letter-spacing: -.03em;
    }

    .detail-empty-card p {
        margin: 0 auto 22px;
        max-width: 480px;
        color: var(--detail-muted);
        line-height: 1.7;
    }

    @media (max-width: 980px) {
        .detail-layout {
            grid-template-columns: 1fr;
        }

        .buy-card {
            position: static;
        }

        .detail-shell {
            margin-top: -42px;
        }
    }

    @media (max-width: 720px) {
        .detail-hero {
            min-height: 240px;
            padding-top: 24px;
        }

        .detail-hero-inner,
        .detail-shell,
        .detail-empty-wrap {
            width: min(100% - 22px, 1180px);
        }

        .detail-breadcrumb {
            font-size: 13px;
            margin-bottom: 24px;
        }

        .detail-hero-copy p {
            font-size: 15px;
        }

        .gallery-card,
        .buy-card,
        .description-card,
        .content-card {
            padding: 13px;
            border-radius: 23px;
        }

        .product-image-large {
            height: 295px;
            border-radius: 19px;
        }

        .main-image-wrap {
            border-radius: 19px;
        }

        .image-thumbs {
            grid-template-columns: repeat(4, 1fr);
        }

        .thumb-button img {
            height: 68px;
        }

        .buy-topline,
        .section-heading-spaced {
            align-items: flex-start;
            flex-direction: column;
        }

        .quick-meta-grid,
        .secondary-action-grid {
            grid-template-columns: 1fr;
        }

        .quantity-control {
            grid-template-columns: 40px minmax(70px, 1fr) 40px auto;
        }

        .section-heading h2 {
            font-size: 22px;
        }

        .review-item,
        .question-content {
            grid-template-columns: 1fr;
        }

        .item-avatar {
            width: 42px;
            height: 42px;
        }

        .answer-content,
        .answer-pending {
            margin-left: 0;
        }

        .detail-empty-card {
            padding: 28px 18px;
        }

        .detail-empty-card h1 {
            font-size: 28px;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const currentUserName = <?= json_encode($user['full_name'] ?? 'Tüketici', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

        function showMessage(element, type, message) {
            if (!element) {
                return;
            }

            element.hidden = false;
            element.className = 'ajax-message ' + type;
            element.textContent = message;
        }

        function clearMessage(element) {
            if (!element) {
                return;
            }

            element.hidden = true;
            element.className = 'ajax-message';
            element.textContent = '';
        }

        function escapeHtml(value) {
            return String(value || '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }

        const mainImage = document.getElementById('detail-main-image');
        const thumbButtons = document.querySelectorAll('.thumb-button');

        thumbButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                if (!mainImage) {
                    return;
                }

                const imageSrc = button.getAttribute('data-image-src');

                if (!imageSrc) {
                    return;
                }

                thumbButtons.forEach(function (item) {
                    item.classList.remove('is-active');
                });

                button.classList.add('is-active');
                mainImage.style.opacity = '0';

                window.setTimeout(function () {
                    mainImage.src = imageSrc;
                    mainImage.style.opacity = '1';
                }, 140);
            });
        });

        const quantityInput = document.getElementById('quantity');
        const quantityButtons = document.querySelectorAll('[data-quantity-action]');

        quantityButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                if (!quantityInput) {
                    return;
                }

                const action = button.getAttribute('data-quantity-action');
                const step = parseFloat(quantityInput.getAttribute('step') || '1');
                const min = parseFloat(quantityInput.getAttribute('min') || '0.01');
                const max = parseFloat(quantityInput.getAttribute('max') || '999999');
                const current = parseFloat(quantityInput.value || '0');

                let nextValue = action === 'increase' ? current + step : current - step;

                nextValue = Math.max(min, Math.min(max, nextValue));
                quantityInput.value = Number(nextValue.toFixed(2));
            });
        });

        const reviewForm = document.getElementById('product-review-form');

        if (reviewForm) {
            const reviewMessage = document.getElementById('product-review-message');
            const reviewSubmit = document.getElementById('product-review-submit');
            let reviewList = document.getElementById('review-list');
            const emptyReviewsText = document.getElementById('empty-reviews-text');

            reviewForm.addEventListener('submit', async function (event) {
                event.preventDefault();

                clearMessage(reviewMessage);

                if (reviewSubmit) {
                    reviewSubmit.disabled = true;
                    reviewSubmit.textContent = 'Gönderiliyor...';
                }

                try {
                    const formData = new FormData(reviewForm);
                    const rating = formData.get('rating');
                    const comment = formData.get('comment');
                    const selectedOrderItemId = formData.get('order_item_id');

                    const response = await fetch(reviewForm.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });

                    const result = await response.json();

                    if (!result.success) {
                        throw new Error(result.message || 'Yorum gönderilemedi.');
                    }

                    showMessage(reviewMessage, 'success', result.message || 'Yorum başarıyla gönderildi.');

                    if (emptyReviewsText) {
                        emptyReviewsText.remove();
                    }

                    if (!reviewList) {
                        reviewList = document.createElement('div');
                        reviewList.id = 'review-list';
                        reviewList.className = 'review-list';
                        document.getElementById('reviews').appendChild(reviewList);
                    }

                    const article = document.createElement('article');
                    article.className = 'review-item';
                    article.innerHTML = `
                        <div class="item-avatar">${escapeHtml(currentUserName).charAt(0) || 'T'}</div>
                        <div>
                            <strong>${escapeHtml(currentUserName)} <span>⭐ ${escapeHtml(rating)}</span></strong>
                            <p>${escapeHtml(comment).replaceAll('\n', '<br>')}</p>
                            <small>Az önce</small>
                        </div>
                    `;

                    reviewList.prepend(article);
                    reviewForm.reset();

                    const orderSelect = reviewForm.querySelector('select[name="order_item_id"]');

                    if (orderSelect && selectedOrderItemId) {
                        orderSelect.querySelector('option[value="' + selectedOrderItemId + '"]')?.remove();
                    }

                    const hiddenOrderItem = reviewForm.querySelector('input[name="order_item_id"][type="hidden"]');

                    if (hiddenOrderItem) {
                        reviewForm.style.display = 'none';
                    }

                    if (orderSelect && orderSelect.options.length <= 1) {
                        reviewForm.style.display = 'none';
                    }
                } catch (error) {
                    showMessage(reviewMessage, 'error', error.message || 'Yorum gönderilirken bir hata oluştu.');
                } finally {
                    if (reviewSubmit) {
                        reviewSubmit.disabled = false;
                        reviewSubmit.textContent = 'Yorumu Gönder';
                    }
                }
            });
        }

        const questionForm = document.getElementById('product-question-form');

        if (questionForm) {
            const questionMessage = document.getElementById('product-question-message');
            const questionSubmit = document.getElementById('product-question-submit');
            let questionList = document.getElementById('question-list');
            const emptyQuestionsText = document.getElementById('empty-questions-text');

            questionForm.addEventListener('submit', async function (event) {
                event.preventDefault();

                clearMessage(questionMessage);

                if (questionSubmit) {
                    questionSubmit.disabled = true;
                    questionSubmit.textContent = 'Gönderiliyor...';
                }

                try {
                    const formData = new FormData(questionForm);
                    const question = formData.get('question');

                    const response = await fetch(questionForm.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });

                    const result = await response.json();

                    if (!result.success) {
                        throw new Error(result.message || 'Soru gönderilemedi.');
                    }

                    showMessage(questionMessage, 'success', result.message || 'Sorun üreticiye gönderildi.');

                    if (emptyQuestionsText) {
                        emptyQuestionsText.remove();
                    }

                    if (!questionList) {
                        questionList = document.createElement('div');
                        questionList.id = 'question-list';
                        questionList.className = 'question-list';
                        document.getElementById('questions').appendChild(questionList);
                    }

                    const article = document.createElement('article');
                    article.className = 'question-item';
                    article.innerHTML = `
                        <div class="question-content">
                            <div class="item-avatar">${escapeHtml(currentUserName).charAt(0) || 'T'}</div>
                            <div>
                                <strong>${escapeHtml(currentUserName)} sordu:</strong>
                                <p>${escapeHtml(question).replaceAll('\n', '<br>')}</p>
                                <small>Az önce</small>
                            </div>
                        </div>
                        <div class="answer-pending">
                            Üretici cevabı bekleniyor.
                        </div>
                    `;

                    questionList.prepend(article);
                    questionForm.reset();
                } catch (error) {
                    showMessage(
                        questionMessage,
                        'error',
                        error.message || 'Soru gönderilirken bir hata oluştu. Bir sonraki adımda soru API dosyasını ekleyeceğiz.'
                    );
                } finally {
                    if (questionSubmit) {
                        questionSubmit.disabled = false;
                        questionSubmit.textContent = 'Soruyu Gönder';
                    }
                }
            });
        }
    });
</script>

<?php require APP_PATH . '/Views/layouts/footer.php'; ?>