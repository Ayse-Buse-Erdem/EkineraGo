<?php

require_once __DIR__ . '/../app/bootstrap.php';

$producerId = (int) ($_GET['id'] ?? 0);

if ($producerId <= 0) {
    flash_error('Geçersiz üretici bilgisi.');
    redirect('producers.php');
}

$controller = new ProducerController();
$data = $controller->publicDetailData($producerId);

$producer = $data['producer'] ?? null;
$products = $data['products'] ?? [];
$reviews = $data['reviews'] ?? [];

if (!$producer) {
    flash_error('Üretici bulunamadı.');
    redirect('producers.php');
}

$pageTitle = ($producer['store_name'] ?? $producer['full_name'] ?? 'Üretici') . ' - Üretici Profili';
$bodyClass = 'page-producer-detail';

require APP_PATH . '/Views/layouts/header.php';

if (!function_exists('producer_detail_name')) {
    function producer_detail_name(array $producer): string
    {
        return $producer['store_name'] ?: ($producer['full_name'] ?? 'Üretici');
    }
}

if (!function_exists('producer_detail_location')) {
    function producer_detail_location(array $producer): string
    {
        $province = $producer['province_name'] ?? '';
        $district = $producer['district_name'] ?? '';

        if ($province && $district) {
            return $province . ' / ' . $district;
        }

        return $province ?: ($district ?: 'Konum bilgisi yok');
    }
}

if (!function_exists('producer_detail_rating')) {
    function producer_detail_rating(array $producer): string
    {
        $rating = (float) ($producer['average_rating'] ?? 0);
        $count = (int) ($producer['rating_count'] ?? 0);

        if ($count <= 0) {
            return 'Henüz puan yok';
        }

        return number_format($rating, 1, ',', '.') . ' / 5';
    }
}

if (!function_exists('producer_detail_rating_count')) {
    function producer_detail_rating_count(array $producer): string
    {
        $count = (int) ($producer['rating_count'] ?? 0);

        if ($count <= 0) {
            return 'Yorum yok';
        }

        return $count . ' yorum';
    }
}

if (!function_exists('producer_detail_image_url')) {
    function producer_detail_image_url(?string $path): string
    {
        if (!$path) {
            return '';
        }

        return url($path);
    }
}

if (!function_exists('producer_detail_money')) {
    function producer_detail_money(float $amount): string
    {
        if (function_exists('formatMoney')) {
            return formatMoney($amount);
        }

        return number_format($amount, 2, ',', '.') . ' TL';
    }
}

if (!function_exists('producer_detail_unit_label')) {
    function producer_detail_unit_label(string $unit): string
    {
        return match ($unit) {
            'kg' => 'kg',
            'g' => 'g',
            'piece' => 'adet',
            'bunch' => 'demet',
            'box' => 'kasa',
            default => $unit,
        };
    }
}

if (!function_exists('producer_detail_initial')) {
    function producer_detail_initial(string $value): string
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

if (!function_exists('producer_detail_date')) {
    function producer_detail_date(?string $date): string
    {
        if (!$date) {
            return '';
        }

        $timestamp = strtotime($date);

        if (!$timestamp) {
            return $date;
        }

        return date('d.m.Y H:i', $timestamp);
    }
}

$producerName = producer_detail_name($producer);
$location = producer_detail_location($producer);
$isVerified = ($producer['verification_status'] ?? '') === 'verified';

$coverPhoto = $producer['cover_photo_path'] ?? '';
$logoPhoto = $producer['logo_path'] ?? '';

$activeProductCount = (int) ($producer['active_product_count'] ?? count($products));
$totalOrders = (int) ($producer['total_orders'] ?? 0);
$ratingText = producer_detail_rating($producer);
$ratingCountText = producer_detail_rating_count($producer);

$contactEmail = $producer['contact_email'] ?: ($producer['email'] ?? '');
$contactPhone = $producer['contact_phone'] ?: ($producer['phone'] ?? '');
$contactWhatsapp = $producer['contact_whatsapp'] ?: ($producer['whatsapp_phone'] ?? '');
$shippingNote = $producer['shipping_note'] ?? '';
?>

<main class="producer-detail-page">
    <section class="producer-hero">
        <div class="producer-hero-bg producer-blob-one"></div>
        <div class="producer-hero-bg producer-blob-two"></div>

        <div class="producer-hero-inner">
            <nav class="producer-breadcrumb" aria-label="Sayfa yolu">
                <a href="<?= e(url('index.php')) ?>">Ana Sayfa</a>
                <span>/</span>
                <a href="<?= e(url('producers.php')) ?>">Üreticiler</a>
                <span>/</span>
                <strong><?= e($producerName) ?></strong>
            </nav>

            <div class="producer-hero-content">
                <div class="producer-logo-wrap">
                    <div class="producer-logo-large">
                        <?php if (!empty($logoPhoto)): ?>
                            <img
                                src="<?= e(producer_detail_image_url($logoPhoto)) ?>"
                                alt="<?= e($producerName) ?>"
                            >
                        <?php else: ?>
                            <span><?= e(producer_detail_initial($producerName)) ?></span>
                        <?php endif; ?>
                    </div>

                    <?php if ($isVerified): ?>
                        <span class="verified-mark">✓</span>
                    <?php endif; ?>
                </div>

                <div class="producer-hero-copy">
                    <span class="producer-eyebrow">
                        <?= $isVerified ? 'Doğrulanmış Üretici' : 'EkineraGo Üreticisi' ?>
                    </span>

                    <h1><?= e($producerName) ?></h1>

                    <p>
                        <?= e($location) ?> konumundaki üreticinin ürünlerini, iletişim bilgilerini
                        ve tüketici yorumlarını buradan inceleyebilirsin.
                    </p>

                    <div class="hero-meta-row">
                        <span>📍 <?= e($location) ?></span>
                        <span>⭐ <?= e($ratingText) ?></span>
                        <span>🧺 <?= e((string) $activeProductCount) ?> aktif ürün</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="producer-detail-shell">
        <div class="producer-main-grid">
            <section class="producer-profile-card glass-card">
                <div class="producer-cover-area">
                    <?php if (!empty($coverPhoto)): ?>
                        <img
                            src="<?= e(producer_detail_image_url($coverPhoto)) ?>"
                            alt="<?= e($producerName) ?>"
                        >
                    <?php else: ?>
                        <div class="producer-cover-placeholder">
                            <span>🌿</span>
                            <strong>Taze Ürün, Doğrudan Kaynak</strong>
                        </div>
                    <?php endif; ?>

                    <div class="cover-overlay-badges">
                        <?php if ($isVerified): ?>
                            <span class="producer-badge badge-success">Doğrulanmış Üretici</span>
                        <?php endif; ?>

                        <span class="producer-badge badge-light">
                            <?= e($activeProductCount . ' Aktif Ürün') ?>
                        </span>
                    </div>
                </div>

                <div class="about-section">
                    <div class="section-heading">
                        <span class="section-icon">👨‍🌾</span>

                        <div>
                            <h2>Üretici Hakkında</h2>
                            <p>Üreticinin kendisi ve satış süreci hakkında paylaştığı bilgiler.</p>
                        </div>
                    </div>

                    <?php if (!empty($producer['description'])): ?>
                        <p class="description-text">
                            <?= nl2br(e($producer['description'])) ?>
                        </p>
                    <?php else: ?>
                        <p class="description-text muted-text">
                            Bu üretici henüz açıklama eklememiş.
                        </p>
                    <?php endif; ?>
                </div>
            </section>

            <aside class="producer-side-card glass-card">
                <div class="side-title">
                    <h2>Üretici Özeti</h2>
                    <p>Profil, puan ve satış bilgileri.</p>
                </div>

                <div class="stat-grid">
                    <div class="stat-card">
                        <strong><?= e((string) $activeProductCount) ?></strong>
                        <span>Aktif Ürün</span>
                    </div>

                    <div class="stat-card">
                        <strong><?= e((string) $totalOrders) ?></strong>
                        <span>Toplam Sipariş</span>
                    </div>

                    <div class="stat-card">
                        <strong><?= e($ratingText) ?></strong>
                        <span><?= e($ratingCountText) ?></span>
                    </div>
                </div>

                <div class="contact-box">
                    <h3>İletişim</h3>

                    <?php if (!$contactEmail && !$contactPhone && !$contactWhatsapp && !$shippingNote): ?>
                        <div class="soft-info">
                            İletişim bilgisi henüz eklenmemiş.
                        </div>
                    <?php else: ?>
                        <ul>
                            <?php if (!empty($contactEmail)): ?>
                                <li>
                                    <span>✉️</span>
                                    <div>
                                        <strong>E-posta</strong>
                                        <p><?= e($contactEmail) ?></p>
                                    </div>
                                </li>
                            <?php endif; ?>

                            <?php if (!empty($contactPhone)): ?>
                                <li>
                                    <span>📞</span>
                                    <div>
                                        <strong>Telefon</strong>
                                        <p><?= e($contactPhone) ?></p>
                                    </div>
                                </li>
                            <?php endif; ?>

                            <?php if (!empty($contactWhatsapp)): ?>
                                <li>
                                    <span>💬</span>
                                    <div>
                                        <strong>WhatsApp</strong>
                                        <p><?= e($contactWhatsapp) ?></p>
                                    </div>
                                </li>
                            <?php endif; ?>

                            <?php if (!empty($shippingNote)): ?>
                                <li>
                                    <span>🚚</span>
                                    <div>
                                        <strong>Gönderim Notu</strong>
                                        <p><?= nl2br(e($shippingNote)) ?></p>
                                    </div>
                                </li>
                            <?php endif; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <div class="side-actions">
                    <a class="detail-btn detail-btn-primary full" href="#producer-products">
                        Ürünleri Gör
                    </a>

                    <a class="detail-btn detail-btn-light full" href="<?= e(url('producers.php')) ?>">
                        Üreticilere Dön
                    </a>
                </div>
            </aside>
        </div>

        <section class="producer-products-card glass-card" id="producer-products">
            <div class="section-heading section-heading-spaced">
                <div class="heading-left">
                    <span class="section-icon">🧺</span>

                    <div>
                        <h2>Üreticinin Ürünleri</h2>
                        <p>Bu üreticinin EkineraGo üzerinde satışta olan ürünleri.</p>
                    </div>
                </div>

                <span class="count-pill"><?= e((string) count($products)) ?> ürün</span>
            </div>

            <?php if (empty($products)): ?>
                <div class="empty-state-modern">
                    <span>🌱</span>
                    <h3>Aktif ürün bulunmuyor</h3>
                    <p>Bu üretici henüz aktif ürün eklememiş.</p>
                </div>
            <?php else: ?>
                <div class="product-grid-modern">
                    <?php foreach ($products as $product): ?>
                        <?php
                            $productId = (int) ($product['id'] ?? 0);
                            $productTitle = $product['title'] ?? 'Ürün';
                            $productImage = $product['cover_image'] ?? ($product['cover_image_path'] ?? '');
                            $unitLabel = producer_detail_unit_label($product['unit_type'] ?? 'kg');
                            $stockQuantity = $product['stock_quantity'] ?? 0;
                        ?>

                        <article class="product-card-modern">
                            <a class="product-image-link" href="<?= e(url('product-detail.php?id=' . $productId)) ?>">
                                <?php if (!empty($productImage)): ?>
                                    <img
                                        src="<?= e(url($productImage)) ?>"
                                        alt="<?= e($productTitle) ?>"
                                    >
                                <?php else: ?>
                                    <div class="product-image-placeholder">
                                        <span>🥬</span>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($product['category_name'])): ?>
                                    <span class="product-category-badge">
                                        <?= e($product['category_name']) ?>
                                    </span>
                                <?php endif; ?>
                            </a>

                            <div class="product-card-body">
                                <h3><?= e($productTitle) ?></h3>

                                <div class="product-price-row">
                                    <strong><?= e(producer_detail_money((float) ($product['price'] ?? 0))) ?></strong>
                                    <span>/ <?= e($unitLabel) ?></span>
                                </div>

                                <div class="product-info-row">
                                    <span>Stok</span>
                                    <strong>
                                        <?= e((string) $stockQuantity) ?>
                                        <?= e($unitLabel) ?>
                                    </strong>
                                </div>

                                <a class="detail-btn detail-btn-primary full" href="<?= e(url('product-detail.php?id=' . $productId)) ?>">
                                    Ürünü Gör
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="producer-reviews-card glass-card">
            <div class="section-heading section-heading-spaced">
                <div class="heading-left">
                    <span class="section-icon">⭐</span>

                    <div>
                        <h2>Yorumlar</h2>
                        <p>Tüketicilerin bu üretici ve ürünleri hakkındaki değerlendirmeleri.</p>
                    </div>
                </div>

                <span class="count-pill"><?= e((string) count($reviews)) ?> yorum</span>
            </div>

            <?php if (empty($reviews)): ?>
                <div class="empty-state-modern">
                    <span>💬</span>
                    <h3>Henüz yorum yok</h3>
                    <p>Bu üretici için henüz tüketici yorumu bulunmuyor.</p>
                </div>
            <?php else: ?>
                <div class="review-list-modern">
                    <?php foreach ($reviews as $review): ?>
                        <article class="review-card-modern">
                            <div class="review-avatar">
                                <?= e(producer_detail_initial($review['consumer_name'] ?? 'Tüketici')) ?>
                            </div>

                            <div class="review-content">
                                <div class="review-top">
                                    <strong>
                                        <?= e($review['consumer_name'] ?? 'Tüketici') ?>
                                    </strong>

                                    <span>⭐ <?= e((string) ((int) ($review['rating'] ?? 0))) ?>/5</span>
                                </div>

                                <?php if (!empty($review['product_title'])): ?>
                                    <p class="review-product">
                                        Ürün:
                                        <strong><?= e($review['product_title']) ?></strong>
                                    </p>
                                <?php endif; ?>

                                <?php if (!empty($review['comment'])): ?>
                                    <p class="review-comment">
                                        <?= nl2br(e($review['comment'])) ?>
                                    </p>
                                <?php endif; ?>

                                <?php if (!empty($review['created_at'])): ?>
                                    <small><?= e(producer_detail_date($review['created_at'])) ?></small>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <div class="bottom-actions">
            <a class="detail-btn detail-btn-light" href="<?= e(url('producers.php')) ?>">
                Üreticilere Dön
            </a>

            <a class="detail-btn detail-btn-primary" href="<?= e(url('products.php')) ?>">
                Ürünleri İncele
            </a>
        </div>
    </section>
</main>

<style>
    :root {
        --producer-green-950: #102f1a;
        --producer-green-900: #163d22;
        --producer-green-800: #245c2f;
        --producer-green-700: #2f7d3d;
        --producer-green-600: #3f9650;
        --producer-green-100: #eaf6e8;
        --producer-green-50: #f6fbf4;
        --producer-cream: #fffaf1;
        --producer-yellow: #f2bf4d;
        --producer-text: #1e2b21;
        --producer-muted: #687669;
        --producer-border: rgba(47, 125, 61, .14);
        --producer-shadow: 0 24px 70px rgba(31, 79, 43, .12);
        --producer-radius-lg: 28px;
    }

    body.page-producer-detail {
        background:
            radial-gradient(circle at 16% 12%, rgba(196, 231, 177, .50), transparent 28%),
            radial-gradient(circle at 88% 18%, rgba(242, 191, 77, .14), transparent 24%),
            linear-gradient(180deg, #f8fbf2 0%, #f3f8ed 48%, #ffffff 100%);
    }

    .producer-detail-page {
        overflow: hidden;
    }

    .producer-hero {
        position: relative;
        min-height: 350px;
        padding: 34px 18px 92px;
        background:
            radial-gradient(circle at 82% 18%, rgba(242, 191, 77, .30), transparent 26%),
            radial-gradient(circle at 12% 78%, rgba(255, 255, 255, .16), transparent 24%),
            linear-gradient(135deg, rgba(36, 92, 47, .97), rgba(47, 125, 61, .87));
        color: #ffffff;
    }

    .producer-hero::after {
        content: '';
        position: absolute;
        inset: auto 0 -1px;
        height: 90px;
        background: linear-gradient(180deg, rgba(246, 251, 244, 0), #f6fbf4 82%);
        pointer-events: none;
    }

    .producer-hero-inner,
    .producer-detail-shell {
        width: min(1180px, calc(100% - 32px));
        margin: 0 auto;
    }

    .producer-hero-inner {
        position: relative;
        z-index: 2;
    }

    .producer-hero-bg {
        position: absolute;
        border-radius: 999px;
        filter: blur(2px);
        opacity: .45;
        pointer-events: none;
    }

    .producer-blob-one {
        width: 230px;
        height: 230px;
        right: 10%;
        top: 44px;
        background: rgba(242, 191, 77, .28);
    }

    .producer-blob-two {
        width: 150px;
        height: 150px;
        left: 8%;
        bottom: 38px;
        background: rgba(255, 255, 255, .20);
    }

    .producer-breadcrumb {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 9px;
        font-size: 14px;
        margin-bottom: 36px;
        color: rgba(255, 255, 255, .76);
    }

    .producer-breadcrumb a {
        color: #ffffff;
        text-decoration: none;
        font-weight: 800;
    }

    .producer-breadcrumb strong {
        color: #ffffff;
        font-weight: 900;
    }

    .producer-hero-content {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr);
        align-items: center;
        gap: 24px;
        max-width: 900px;
    }

    .producer-logo-wrap {
        position: relative;
        width: 142px;
        height: 142px;
        flex: 0 0 auto;
    }

    .producer-logo-large {
        width: 142px;
        height: 142px;
        border-radius: 38px;
        overflow: hidden;
        background: rgba(255, 255, 255, .16);
        border: 1px solid rgba(255, 255, 255, .32);
        box-shadow: 0 22px 50px rgba(16, 47, 26, .26);
        display: grid;
        place-items: center;
    }

    .producer-logo-large img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .producer-logo-large span {
        color: #ffffff;
        font-size: 58px;
        font-weight: 900;
    }

    .verified-mark {
        position: absolute;
        right: -6px;
        bottom: -6px;
        width: 44px;
        height: 44px;
        display: grid;
        place-items: center;
        border-radius: 16px;
        background: var(--producer-yellow);
        color: var(--producer-green-950);
        font-weight: 1000;
        box-shadow: 0 14px 30px rgba(0, 0, 0, .18);
        border: 3px solid rgba(255, 255, 255, .9);
    }

    .producer-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 13px;
        border-radius: 999px;
        background: rgba(255, 255, 255, .16);
        border: 1px solid rgba(255, 255, 255, .28);
        color: #ffffff;
        font-size: 13px;
        font-weight: 900;
        letter-spacing: .04em;
        text-transform: uppercase;
    }

    .producer-hero-copy h1 {
        margin: 16px 0 12px;
        font-size: clamp(36px, 5vw, 62px);
        line-height: 1.03;
        letter-spacing: -.045em;
    }

    .producer-hero-copy p {
        max-width: 680px;
        margin: 0;
        color: rgba(255, 255, 255, .86);
        font-size: 17px;
        line-height: 1.7;
    }

    .hero-meta-row {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 18px;
    }

    .hero-meta-row span {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 9px 12px;
        border-radius: 999px;
        background: rgba(255, 255, 255, .16);
        border: 1px solid rgba(255, 255, 255, .24);
        color: #ffffff;
        font-size: 13px;
        font-weight: 900;
    }

    .producer-detail-shell {
        position: relative;
        z-index: 3;
        margin-top: -66px;
        padding-bottom: 54px;
    }

    .glass-card {
        background: rgba(255, 255, 255, .92);
        border: 1px solid rgba(255, 255, 255, .72);
        border-radius: var(--producer-radius-lg);
        box-shadow: var(--producer-shadow);
        backdrop-filter: blur(16px);
    }

    .producer-main-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.08fr) minmax(340px, .92fr);
        gap: 24px;
        align-items: start;
    }

    .producer-profile-card,
    .producer-side-card,
    .producer-products-card,
    .producer-reviews-card {
        padding: 18px;
    }

    .producer-cover-area {
        position: relative;
        overflow: hidden;
        min-height: 320px;
        border-radius: 24px;
        background: var(--producer-green-100);
    }

    .producer-cover-area img {
        width: 100%;
        height: 360px;
        object-fit: cover;
        display: block;
        transition: transform .45s ease;
    }

    .producer-cover-area:hover img {
        transform: scale(1.025);
    }

    .producer-cover-placeholder {
        min-height: 360px;
        display: grid;
        place-items: center;
        align-content: center;
        gap: 12px;
        background:
            radial-gradient(circle at 25% 20%, rgba(255, 255, 255, .72), transparent 28%),
            linear-gradient(135deg, #e8f3e9, #f8fbf6);
        color: var(--producer-green-700);
        text-align: center;
        font-size: 18px;
    }

    .producer-cover-placeholder span {
        font-size: 56px;
    }

    .cover-overlay-badges {
        position: absolute;
        top: 16px;
        left: 16px;
        right: 16px;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        z-index: 2;
    }

    .producer-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 900;
        line-height: 1;
        box-shadow: 0 10px 24px rgba(31, 79, 43, .12);
    }

    .badge-success {
        background: #e7f7e8;
        color: #236b2c;
    }

    .badge-light {
        background: rgba(255, 255, 255, .92);
        color: var(--producer-green-800);
    }

    .about-section {
        padding: 22px 4px 4px;
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
        color: var(--producer-green-900);
        font-size: 25px;
        letter-spacing: -.02em;
    }

    .section-heading p,
    .description-text,
    .muted-text,
    .side-title p,
    .contact-box p,
    .empty-state-modern p,
    .review-comment,
    .review-product {
        color: var(--producer-muted);
        line-height: 1.7;
    }

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

    .section-icon {
        width: 44px;
        height: 44px;
        border-radius: 15px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--producer-green-100);
        font-size: 21px;
        flex: 0 0 auto;
    }

    .description-text {
        margin: 0;
        font-size: 16px;
    }

    .producer-side-card {
        position: sticky;
        top: 22px;
    }

    .side-title {
        margin-bottom: 16px;
    }

    .side-title h2 {
        margin: 0 0 6px;
        color: var(--producer-green-900);
        font-size: 28px;
        letter-spacing: -.03em;
    }

    .side-title p {
        margin: 0;
    }

    .stat-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 10px;
    }

    .stat-card {
        padding: 16px;
        border-radius: 19px;
        background:
            radial-gradient(circle at 88% 20%, rgba(242, 191, 77, .18), transparent 28%),
            #fbfdf8;
        border: 1px solid var(--producer-border);
    }

    .stat-card strong,
    .stat-card span {
        display: block;
    }

    .stat-card strong {
        color: var(--producer-green-900);
        font-size: 25px;
        line-height: 1.1;
    }

    .stat-card span {
        margin-top: 7px;
        color: var(--producer-muted);
        font-size: 13px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .contact-box {
        margin-top: 16px;
        padding: 16px;
        border-radius: 22px;
        background: var(--producer-green-50);
        border: 1px solid var(--producer-border);
    }

    .contact-box h3 {
        margin: 0 0 13px;
        color: var(--producer-green-900);
        font-size: 21px;
    }

    .contact-box ul {
        list-style: none;
        padding: 0;
        margin: 0;
        display: grid;
        gap: 11px;
    }

    .contact-box li {
        display: grid;
        grid-template-columns: 38px 1fr;
        gap: 10px;
        align-items: flex-start;
        padding: 12px;
        border-radius: 17px;
        background: #ffffff;
        border: 1px solid rgba(47, 125, 61, .10);
    }

    .contact-box li > span {
        width: 38px;
        height: 38px;
        display: grid;
        place-items: center;
        border-radius: 14px;
        background: var(--producer-green-100);
    }

    .contact-box strong {
        color: var(--producer-green-900);
    }

    .contact-box p {
        margin: 3px 0 0;
        word-break: break-word;
    }

    .soft-info {
        padding: 14px;
        border-radius: 16px;
        color: var(--producer-muted);
        background: #ffffff;
        font-weight: 800;
    }

    .side-actions {
        margin-top: 16px;
        display: grid;
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
        background: linear-gradient(135deg, var(--producer-green-700), var(--producer-green-900));
        color: #ffffff;
        box-shadow: 0 16px 32px rgba(47, 125, 61, .24);
    }

    .detail-btn-light {
        background: var(--producer-green-50);
        color: var(--producer-green-800);
        border: 1px solid var(--producer-border);
    }

    .producer-products-card,
    .producer-reviews-card {
        margin-top: 22px;
    }

    .count-pill {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 9px 12px;
        border-radius: 999px;
        background: var(--producer-cream);
        border: 1px solid rgba(242, 191, 77, .32);
        color: var(--producer-green-900);
        white-space: nowrap;
        font-size: 13px;
        font-weight: 900;
    }

    .product-grid-modern {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 16px;
    }

    .product-card-modern {
        overflow: hidden;
        border-radius: 24px;
        background: #ffffff;
        border: 1px solid var(--producer-border);
        box-shadow: 0 14px 36px rgba(31, 79, 43, .08);
        transition: transform .2s ease, box-shadow .2s ease;
    }

    .product-card-modern:hover {
        transform: translateY(-4px);
        box-shadow: 0 24px 48px rgba(31, 79, 43, .13);
    }

    .product-image-link {
        position: relative;
        display: block;
        height: 190px;
        overflow: hidden;
        background: var(--producer-green-100);
    }

    .product-image-link img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        transition: transform .35s ease;
    }

    .product-card-modern:hover .product-image-link img {
        transform: scale(1.04);
    }

    .product-image-placeholder {
        width: 100%;
        height: 100%;
        display: grid;
        place-items: center;
        background:
            radial-gradient(circle at 30% 25%, rgba(255, 255, 255, .75), transparent 28%),
            linear-gradient(135deg, #e8f3e9, #f8fbf6);
        color: var(--producer-green-700);
        font-size: 46px;
    }

    .product-category-badge {
        position: absolute;
        left: 12px;
        top: 12px;
        padding: 7px 10px;
        border-radius: 999px;
        background: rgba(255, 255, 255, .92);
        color: var(--producer-green-800);
        font-size: 12px;
        font-weight: 900;
        box-shadow: 0 8px 20px rgba(31, 79, 43, .12);
    }

    .product-card-body {
        padding: 15px;
    }

    .product-card-body h3 {
        margin: 0 0 10px;
        color: var(--producer-green-900);
        font-size: 20px;
        letter-spacing: -.02em;
    }

    .product-price-row {
        display: flex;
        flex-wrap: wrap;
        align-items: baseline;
        gap: 5px;
        margin-bottom: 10px;
    }

    .product-price-row strong {
        color: var(--producer-green-800);
        font-size: 22px;
    }

    .product-price-row span {
        color: var(--producer-muted);
        font-weight: 800;
    }

    .product-info-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 11px 12px;
        margin-bottom: 13px;
        border-radius: 15px;
        background: var(--producer-green-50);
        border: 1px solid var(--producer-border);
    }

    .product-info-row span {
        color: var(--producer-muted);
        font-size: 13px;
        font-weight: 900;
    }

    .product-info-row strong {
        color: var(--producer-green-900);
        font-size: 14px;
    }

    .review-list-modern {
        display: grid;
        gap: 14px;
    }

    .review-card-modern {
        display: grid;
        grid-template-columns: 50px 1fr;
        gap: 14px;
        padding: 16px;
        border-radius: 21px;
        background: #fbfdf8;
        border: 1px solid var(--producer-border);
    }

    .review-avatar {
        width: 50px;
        height: 50px;
        display: grid;
        place-items: center;
        flex: 0 0 auto;
        border-radius: 17px;
        color: #ffffff;
        background: linear-gradient(135deg, var(--producer-green-700), var(--producer-green-600));
        font-weight: 900;
        box-shadow: 0 12px 25px rgba(47, 125, 61, .24);
    }

    .review-top {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: center;
        flex-wrap: wrap;
    }

    .review-top strong {
        color: var(--producer-green-900);
        font-size: 16px;
    }

    .review-top span {
        display: inline-flex;
        align-items: center;
        padding: 7px 10px;
        border-radius: 999px;
        background: #fff8df;
        color: #8a6200;
        font-size: 13px;
        font-weight: 900;
    }

    .review-product {
        margin: 7px 0 0;
        font-size: 14px;
    }

    .review-product strong {
        color: var(--producer-green-800);
    }

    .review-comment {
        margin: 9px 0;
    }

    .review-card-modern small {
        color: #839184;
        font-size: 13px;
        font-weight: 700;
    }

    .empty-state-modern {
        display: grid;
        place-items: center;
        gap: 8px;
        padding: 34px 18px;
        border-radius: 22px;
        background: #fbfdf8;
        border: 1px dashed rgba(47, 125, 61, .24);
        text-align: center;
    }

    .empty-state-modern span {
        font-size: 34px;
    }

    .empty-state-modern h3 {
        margin: 0;
        color: var(--producer-green-900);
        font-size: 21px;
    }

    .empty-state-modern p {
        margin: 0;
        font-weight: 800;
    }

    .bottom-actions {
        margin-top: 22px;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        flex-wrap: wrap;
    }

    @media (max-width: 1080px) {
        .product-grid-modern {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 980px) {
        .producer-main-grid {
            grid-template-columns: 1fr;
        }

        .producer-side-card {
            position: static;
        }

        .producer-detail-shell {
            margin-top: -50px;
        }
    }

    @media (max-width: 720px) {
        .producer-hero {
            min-height: 400px;
            padding-top: 24px;
        }

        .producer-hero-inner,
        .producer-detail-shell {
            width: min(100% - 22px, 1180px);
        }

        .producer-breadcrumb {
            font-size: 13px;
            margin-bottom: 24px;
        }

        .producer-hero-content {
            grid-template-columns: 1fr;
            gap: 16px;
        }

        .producer-logo-wrap,
        .producer-logo-large {
            width: 112px;
            height: 112px;
        }

        .producer-logo-large {
            border-radius: 30px;
        }

        .producer-logo-large span {
            font-size: 44px;
        }

        .verified-mark {
            width: 38px;
            height: 38px;
            border-radius: 14px;
        }

        .producer-hero-copy p {
            font-size: 15px;
        }

        .producer-profile-card,
        .producer-side-card,
        .producer-products-card,
        .producer-reviews-card {
            padding: 13px;
            border-radius: 23px;
        }

        .producer-cover-area,
        .producer-cover-area img,
        .producer-cover-placeholder {
            min-height: 260px;
            height: 260px;
            border-radius: 19px;
        }

        .section-heading-spaced {
            align-items: flex-start;
            flex-direction: column;
        }

        .section-heading h2 {
            font-size: 22px;
        }

        .product-grid-modern {
            grid-template-columns: 1fr;
        }

        .review-card-modern {
            grid-template-columns: 1fr;
        }

        .bottom-actions {
            justify-content: stretch;
        }

        .bottom-actions .detail-btn {
            width: 100%;
        }
    }
</style>

<?php require APP_PATH . '/Views/layouts/footer.php'; ?>