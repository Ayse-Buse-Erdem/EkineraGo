<?php require APP_PATH . '/Views/layouts/header.php'; ?>

<?php
if (!function_exists('favorite_unit_label')) {
    function favorite_unit_label(string $unit): string
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

if (!function_exists('favorite_money')) {
    function favorite_money(float $amount): string
    {
        if (function_exists('formatMoney')) {
            return formatMoney($amount);
        }

        return number_format($amount, 2, ',', '.') . ' TL';
    }
}

if (!function_exists('favorite_number')) {
    function favorite_number(float $number): string
    {
        return rtrim(rtrim(number_format($number, 2, ',', '.'), '0'), ',');
    }
}

if (!function_exists('favorite_date')) {
    function favorite_date(?string $date): string
    {
        if (!$date) {
            return '-';
        }

        $timestamp = strtotime($date);

        if (!$timestamp) {
            return $date;
        }

        return date('d.m.Y', $timestamp);
    }
}

$favorites = $favorites ?? [];

$totalFavorites = count($favorites);
$availableCount = 0;
$outOfStockCount = 0;
$totalFavoriteValue = 0;

foreach ($favorites as $product) {
    $stockQuantity = (float) ($product['stock_quantity'] ?? 0);
    $status = $product['status'] ?? '';

    if ($status === 'active' && $stockQuantity > 0) {
        $availableCount++;
    } else {
        $outOfStockCount++;
    }

    $totalFavoriteValue += (float) ($product['price'] ?? 0);
}
?>

<main class="consumer-favorites-page">
    <section class="favorites-hero">
        <div class="favorites-hero-bg favorites-blob-one"></div>
        <div class="favorites-hero-bg favorites-blob-two"></div>

        <div class="favorites-hero-inner">
            <nav class="favorites-breadcrumb" aria-label="Sayfa yolu">
                <a href="<?= e(url('index.php')) ?>">Ana Sayfa</a>
                <span>/</span>
                <a href="<?= e(url('consumer/dashboard.php')) ?>">Tüketici Paneli</a>
                <span>/</span>
                <strong>Favorilerim</strong>
            </nav>

            <div class="favorites-hero-content">
                <div class="favorites-hero-copy">
                    <span class="favorites-eyebrow">
                        Kaydedilen Ürünler
                    </span>

                    <h1>Favorilerim</h1>

                    <p>
                        Beğendiğin ürünleri burada saklayabilir, stok durumunu takip edebilir
                        ve istediğin ürüne hızlıca geri dönebilirsin.
                    </p>

                    <?php if (!empty($favorites)): ?>
                        <div class="favorites-hero-stats" id="favorites-hero-stats">
                            <span>♥ <?= e((string) $totalFavorites) ?> favori</span>
                            <span>🌱 <?= e((string) $availableCount) ?> stokta</span>
                            <span>⚠️ <?= e((string) $outOfStockCount) ?> stokta yok</span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="favorites-hero-card">
                    <div class="hero-card-icon">♥</div>

                    <h2>Ürünleri kaybetme</h2>

                    <p>
                        Favoriye aldığın ürünleri tek ekranda gör, üretici bilgilerine ulaş
                        ve tekrar incelemek için detay sayfasına geç.
                    </p>

                    <div class="hero-mini-list">
                        <span>🧺 Hızlı erişim</span>
                        <span>📍 Üretici bilgisi</span>
                        <span>⭐ Puan takibi</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="favorites-shell">
        <div id="favorite-ajax-message" class="favorite-ajax-message" hidden></div>

        <?php if (empty($favorites)): ?>
            <section class="favorites-empty-card glass-card" id="favorites-empty-state">
                <div class="empty-icon">♥</div>

                <span class="favorites-eyebrow light">Favori Listen Boş</span>

                <h2>Henüz favori ürünün yok.</h2>

                <p>
                    Ürün detay sayfasından favoriye ekleme yaptığında ürünler burada görünecek.
                </p>

                <div class="empty-actions">
                    <a class="favorites-btn favorites-btn-primary" href="<?= e(url('products.php')) ?>">
                        Ürünleri Keşfet
                    </a>

                    <a class="favorites-btn favorites-btn-light" href="<?= e(url('consumer/dashboard.php')) ?>">
                        Panele Dön
                    </a>
                </div>
            </section>
        <?php else: ?>
            <section class="favorites-top glass-card" id="favorites-top">
                <div>
                    <span class="section-kicker">Favori Özeti</span>

                    <h2>Kaydettiğin ürünler</h2>

                    <p>
                        Favorilerinden ürün çıkarabilir veya ürün detayına giderek stok ve üretici bilgilerini inceleyebilirsin.
                    </p>
                </div>

                <div class="favorites-top-total">
                    <span>Ortalama Ürün Fiyatı</span>

                    <strong>
                        <?= e(favorite_money($totalFavorites > 0 ? $totalFavoriteValue / $totalFavorites : 0)) ?>
                    </strong>
                </div>
            </section>

            <section class="favorites-grid" id="favorites-grid">
                <?php foreach ($favorites as $product): ?>
                    <?php
                        $productId = (int) ($product['id'] ?? 0);
                        $unit = favorite_unit_label($product['unit_type'] ?? 'kg');
                        $stockQuantity = (float) ($product['stock_quantity'] ?? 0);
                        $status = $product['status'] ?? '';
                        $isAvailable = $status === 'active' && $stockQuantity > 0;

                        $producerName = $product['store_name'] ?: ($product['producer_name'] ?? 'Üretici');
                        $provinceName = $product['province_name'] ?? '-';
                        $districtName = $product['district_name'] ?? '-';

                        $rating = (float) ($product['average_rating'] ?? 0);
                        $ratingCount = (int) ($product['rating_count'] ?? 0);

                        $coverImage = $product['cover_image'] ?? '';
                    ?>

                    <article class="favorite-card glass-card" data-favorite-card="<?= e((string) $productId) ?>">
                        <a class="favorite-image-link" href="<?= e(url('product-detail.php?id=' . $productId)) ?>">
                            <?php if (!empty($coverImage)): ?>
                                <img
                                    src="<?= e(url($coverImage)) ?>"
                                    alt="<?= e($product['title'] ?? 'Ürün') ?>"
                                    class="favorite-image"
                                >
                            <?php else: ?>
                                <div class="favorite-image favorite-image-placeholder">
                                    <span>🥬</span>
                                    <strong>Ürün Fotoğrafı</strong>
                                </div>
                            <?php endif; ?>

                            <div class="image-badges">
                                <span class="favorite-badge <?= $isAvailable ? 'badge-success' : 'badge-warning' ?>">
                                    <?= $isAvailable ? 'Stokta Var' : 'Stokta Yok' ?>
                                </span>

                                <?php if (!empty($product['category_name'])): ?>
                                    <span class="favorite-badge badge-light">
                                        <?= e($product['category_name']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </a>

                        <div class="favorite-content">
                            <div class="favorite-title-row">
                                <h2>
                                    <a href="<?= e(url('product-detail.php?id=' . $productId)) ?>">
                                        <?= e($product['title'] ?? 'Ürün') ?>
                                    </a>
                                </h2>

                                <form method="POST" action="<?= e(url('api/favorite-toggle.php')) ?>" class="favorite-remove-form">
                                    <?= csrf_field() ?>

                                    <input type="hidden" name="product_id" value="<?= e((string) $productId) ?>">

                                    <button
                                        class="favorite-heart-btn"
                                        type="submit"
                                        aria-label="Favoriden çıkar"
                                        title="Favoriden çıkar"
                                    >
                                        ♥
                                    </button>
                                </form>
                            </div>

                            <div class="producer-line">
                                <span>👨‍🌾</span>

                                <p>
                                    <strong><?= e($producerName) ?></strong>
                                    <small><?= e($provinceName) ?> / <?= e($districtName) ?></small>
                                </p>
                            </div>

                            <div class="favorite-price-row">
                                <strong><?= e(favorite_money((float) ($product['price'] ?? 0))) ?></strong>
                                <span>/ <?= e($unit) ?></span>
                            </div>

                            <div class="favorite-meta-grid">
                                <div>
                                    <span>Stok</span>
                                    <strong><?= e(favorite_number($stockQuantity)) ?> <?= e($unit) ?></strong>
                                </div>

                                <div>
                                    <span>Puan</span>
                                    <strong>
                                        <?= $ratingCount > 0 ? e(number_format($rating, 1, ',', '.')) . ' ⭐' : 'Yok' ?>
                                    </strong>
                                </div>

                                <div>
                                    <span>Yorum</span>
                                    <strong><?= e((string) $ratingCount) ?></strong>
                                </div>

                                <div>
                                    <span>Eklenme</span>
                                    <strong><?= e(favorite_date($product['favorited_at'] ?? null)) ?></strong>
                                </div>
                            </div>

                            <div class="favorite-actions">
                                <a class="favorites-btn favorites-btn-primary full" href="<?= e(url('product-detail.php?id=' . $productId)) ?>">
                                    Ürünü Gör
                                </a>

                                <a class="favorites-btn favorites-btn-light full" href="<?= e(url('products.php')) ?>">
                                    Benzer Ürünler
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </section>
</main>

<style>
    :root {
        --favorites-green-950: #102f1a;
        --favorites-green-900: #163d22;
        --favorites-green-800: #245c2f;
        --favorites-green-700: #2f7d3d;
        --favorites-green-600: #3f9650;
        --favorites-green-100: #eaf6e8;
        --favorites-green-50: #f6fbf4;
        --favorites-cream: #fffaf1;
        --favorites-yellow: #f2bf4d;
        --favorites-red: #b42318;
        --favorites-text: #1e2b21;
        --favorites-muted: #687669;
        --favorites-border: rgba(47, 125, 61, .14);
        --favorites-shadow: 0 24px 70px rgba(31, 79, 43, .12);
        --favorites-radius-lg: 28px;
    }

    body.page-consumer-favorites {
        background:
            radial-gradient(circle at 14% 12%, rgba(196, 231, 177, .48), transparent 28%),
            radial-gradient(circle at 88% 16%, rgba(242, 191, 77, .16), transparent 24%),
            linear-gradient(180deg, #f8fbf2 0%, #f3f8ed 48%, #ffffff 100%);
    }

    .consumer-favorites-page {
        overflow: hidden;
    }

    .favorites-hero {
        position: relative;
        min-height: 390px;
        padding: 34px 18px 94px;
        background:
            radial-gradient(circle at 82% 18%, rgba(242, 191, 77, .30), transparent 26%),
            radial-gradient(circle at 12% 78%, rgba(255, 255, 255, .16), transparent 24%),
            linear-gradient(135deg, rgba(36, 92, 47, .97), rgba(47, 125, 61, .87));
        color: #ffffff;
    }

    .favorites-hero::after {
        content: '';
        position: absolute;
        inset: auto 0 -1px;
        height: 90px;
        background: linear-gradient(180deg, rgba(246, 251, 244, 0), #f6fbf4 82%);
        pointer-events: none;
    }

    .favorites-hero-inner,
    .favorites-shell {
        width: min(1180px, calc(100% - 32px));
        margin: 0 auto;
    }

    .favorites-hero-inner {
        position: relative;
        z-index: 2;
    }

    .favorites-hero-bg {
        position: absolute;
        border-radius: 999px;
        filter: blur(2px);
        opacity: .45;
        pointer-events: none;
    }

    .favorites-blob-one {
        width: 230px;
        height: 230px;
        right: 10%;
        top: 42px;
        background: rgba(242, 191, 77, .28);
    }

    .favorites-blob-two {
        width: 150px;
        height: 150px;
        left: 8%;
        bottom: 36px;
        background: rgba(255, 255, 255, .20);
    }

    .favorites-breadcrumb {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 9px;
        font-size: 14px;
        margin-bottom: 32px;
        color: rgba(255, 255, 255, .76);
    }

    .favorites-breadcrumb a {
        color: #ffffff;
        text-decoration: none;
        font-weight: 800;
    }

    .favorites-breadcrumb strong {
        color: #ffffff;
        font-weight: 900;
    }

    .favorites-hero-content {
        display: grid;
        grid-template-columns: minmax(0, 1.25fr) minmax(320px, .75fr);
        gap: 24px;
        align-items: center;
    }

    .favorites-eyebrow,
    .section-kicker {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 13px;
        border-radius: 999px;
        font-size: 13px;
        font-weight: 900;
        letter-spacing: .04em;
        text-transform: uppercase;
    }

    .favorites-eyebrow {
        background: rgba(255, 255, 255, .16);
        border: 1px solid rgba(255, 255, 255, .28);
        color: #ffffff;
    }

    .favorites-eyebrow.light,
    .section-kicker {
        background: var(--favorites-green-100);
        color: var(--favorites-green-800);
        border-color: transparent;
    }

    .favorites-hero-copy h1 {
        margin: 17px 0 12px;
        font-size: clamp(36px, 5vw, 62px);
        line-height: 1.03;
        letter-spacing: -.045em;
    }

    .favorites-hero-copy p {
        max-width: 700px;
        margin: 0;
        color: rgba(255, 255, 255, .86);
        font-size: 17px;
        line-height: 1.7;
    }

    .favorites-hero-stats {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 18px;
    }

    .favorites-hero-stats span {
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

    .favorites-hero-card {
        padding: 22px;
        border-radius: 30px;
        background: rgba(255, 255, 255, .14);
        border: 1px solid rgba(255, 255, 255, .28);
        box-shadow: 0 22px 58px rgba(16, 47, 26, .22);
        backdrop-filter: blur(18px);
    }

    .hero-card-icon {
        width: 60px;
        height: 60px;
        display: grid;
        place-items: center;
        border-radius: 20px;
        background: rgba(255, 255, 255, .18);
        font-size: 28px;
        margin-bottom: 16px;
    }

    .favorites-hero-card h2 {
        margin: 0 0 10px;
        font-size: 25px;
        letter-spacing: -.03em;
    }

    .favorites-hero-card p {
        margin: 0;
        color: rgba(255, 255, 255, .82);
        line-height: 1.6;
    }

    .hero-mini-list {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 16px;
    }

    .hero-mini-list span {
        display: inline-flex;
        padding: 8px 10px;
        border-radius: 999px;
        background: rgba(255, 255, 255, .16);
        border: 1px solid rgba(255, 255, 255, .20);
        font-size: 12px;
        font-weight: 900;
    }

    .favorites-shell {
        position: relative;
        z-index: 3;
        margin-top: -64px;
        padding-bottom: 54px;
    }

    .glass-card {
        background: rgba(255, 255, 255, .92);
        border: 1px solid rgba(255, 255, 255, .72);
        border-radius: var(--favorites-radius-lg);
        box-shadow: var(--favorites-shadow);
        backdrop-filter: blur(16px);
    }

    .favorite-ajax-message {
        margin-bottom: 16px;
        padding: 14px 16px;
        border-radius: 18px;
        font-weight: 900;
        box-shadow: 0 12px 30px rgba(31, 79, 43, .10);
    }

    .favorite-ajax-message.success {
        background: #e7f7e8;
        color: #236b2c;
        border: 1px solid rgba(35, 107, 44, .14);
    }

    .favorite-ajax-message.error {
        background: #fdeaea;
        color: var(--favorites-red);
        border: 1px solid rgba(180, 35, 24, .14);
    }

    .favorites-top {
        margin-bottom: 22px;
        padding: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
    }

    .favorites-top h2,
    .favorites-empty-card h2 {
        margin: 10px 0 6px;
        color: var(--favorites-green-900);
        letter-spacing: -.03em;
    }

    .favorites-top p,
    .favorites-empty-card p {
        margin: 0;
        color: var(--favorites-muted);
        line-height: 1.6;
    }

    .favorites-top-total {
        display: grid;
        justify-items: end;
        gap: 4px;
        min-width: 210px;
    }

    .favorites-top-total span {
        color: var(--favorites-muted);
        font-size: 13px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .favorites-top-total strong {
        color: var(--favorites-green-800);
        font-size: 28px;
        line-height: 1;
    }

    .favorites-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 20px;
    }

    .favorite-card {
        overflow: hidden;
        transition: transform .2s ease, box-shadow .2s ease, opacity .2s ease;
    }

    .favorite-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 28px 68px rgba(31, 79, 43, .15);
    }

    .favorite-card.removing {
        opacity: .45;
        pointer-events: none;
        transform: scale(.98);
    }

    .favorite-image-link {
        display: block;
        position: relative;
        height: 220px;
        overflow: hidden;
        background: var(--favorites-green-100);
        text-decoration: none;
    }

    .favorite-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        transition: transform .35s ease;
    }

    .favorite-card:hover .favorite-image {
        transform: scale(1.04);
    }

    .favorite-image-placeholder {
        display: grid;
        place-items: center;
        align-content: center;
        gap: 8px;
        background:
            radial-gradient(circle at 30% 25%, rgba(255, 255, 255, .75), transparent 28%),
            linear-gradient(135deg, #e8f3e9, #f8fbf6);
        color: var(--favorites-green-700);
        font-weight: 900;
    }

    .favorite-image-placeholder span {
        font-size: 44px;
    }

    .image-badges {
        position: absolute;
        top: 13px;
        left: 13px;
        right: 13px;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .favorite-badge {
        display: inline-flex;
        align-items: center;
        padding: 8px 11px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 900;
        box-shadow: 0 10px 22px rgba(31, 79, 43, .10);
    }

    .badge-success {
        background: #e7f7e8;
        color: #236b2c;
    }

    .badge-warning {
        background: #fff5d6;
        color: #8a6200;
    }

    .badge-light {
        background: rgba(255, 255, 255, .92);
        color: var(--favorites-green-800);
    }

    .favorite-content {
        padding: 17px;
    }

    .favorite-title-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 42px;
        gap: 10px;
        align-items: start;
    }

    .favorite-title-row h2 {
        margin: 0;
        font-size: 22px;
        line-height: 1.18;
        letter-spacing: -.02em;
    }

    .favorite-title-row h2 a {
        color: var(--favorites-green-900);
        text-decoration: none;
    }

    .favorite-title-row h2 a:hover {
        color: var(--favorites-green-700);
    }

    .favorite-remove-form {
        margin: 0;
    }

    .favorite-heart-btn {
        width: 42px;
        height: 42px;
        display: grid;
        place-items: center;
        border: 0;
        border-radius: 15px;
        background: #fdeaea;
        color: var(--favorites-red);
        font-size: 20px;
        font-weight: 900;
        cursor: pointer;
        transition: transform .18s ease, background .18s ease;
    }

    .favorite-heart-btn:hover {
        transform: translateY(-2px);
        background: #ffdada;
    }

    .producer-line {
        display: grid;
        grid-template-columns: 38px minmax(0, 1fr);
        gap: 10px;
        align-items: center;
        margin-top: 14px;
        padding: 12px;
        border-radius: 17px;
        background: var(--favorites-green-50);
        border: 1px solid var(--favorites-border);
    }

    .producer-line > span {
        width: 38px;
        height: 38px;
        display: grid;
        place-items: center;
        border-radius: 14px;
        background: var(--favorites-green-100);
    }

    .producer-line p {
        margin: 0;
    }

    .producer-line strong,
    .producer-line small {
        display: block;
    }

    .producer-line strong {
        color: var(--favorites-green-900);
    }

    .producer-line small {
        margin-top: 3px;
        color: var(--favorites-muted);
        font-weight: 800;
    }

    .favorite-price-row {
        display: flex;
        align-items: baseline;
        gap: 5px;
        margin: 15px 0 12px;
    }

    .favorite-price-row strong {
        color: var(--favorites-green-800);
        font-size: 26px;
        line-height: 1;
        letter-spacing: -.03em;
    }

    .favorite-price-row span {
        color: var(--favorites-muted);
        font-weight: 900;
    }

    .favorite-meta-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }

    .favorite-meta-grid div {
        padding: 12px;
        border-radius: 16px;
        background: #fbfdf8;
        border: 1px solid var(--favorites-border);
    }

    .favorite-meta-grid span,
    .favorite-meta-grid strong {
        display: block;
    }

    .favorite-meta-grid span {
        color: var(--favorites-muted);
        font-size: 12px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: 5px;
    }

    .favorite-meta-grid strong {
        color: var(--favorites-green-900);
        font-size: 14px;
    }

    .favorite-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-top: 15px;
    }

    .favorites-btn {
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

    .favorites-btn:hover {
        transform: translateY(-2px);
    }

    .favorites-btn.full {
        width: 100%;
    }

    .favorites-btn-primary {
        background: linear-gradient(135deg, var(--favorites-green-700), var(--favorites-green-900));
        color: #ffffff;
        box-shadow: 0 16px 32px rgba(47, 125, 61, .24);
    }

    .favorites-btn-light {
        background: var(--favorites-green-50);
        color: var(--favorites-green-800);
        border: 1px solid var(--favorites-border);
    }

    .favorites-empty-card {
        max-width: 720px;
        margin: 0 auto;
        padding: 42px;
        text-align: center;
    }

    .empty-icon {
        width: 78px;
        height: 78px;
        margin: 0 auto 14px;
        display: grid;
        place-items: center;
        border-radius: 25px;
        background: var(--favorites-green-100);
        color: var(--favorites-red);
        font-size: 36px;
    }

    .favorites-empty-card h2 {
        margin-top: 18px;
        font-size: 34px;
    }

    .favorites-empty-card p {
        max-width: 520px;
        margin: 0 auto 22px;
    }

    .empty-actions {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 10px;
    }

    @media (max-width: 1100px) {
        .favorites-hero-content {
            grid-template-columns: 1fr;
        }

        .favorites-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 760px) {
        .favorites-hero {
            min-height: 430px;
            padding-top: 24px;
        }

        .favorites-hero-inner,
        .favorites-shell {
            width: min(100% - 22px, 1180px);
        }

        .favorites-breadcrumb {
            font-size: 13px;
            margin-bottom: 24px;
        }

        .favorites-hero-copy p {
            font-size: 15px;
        }

        .favorites-shell {
            margin-top: -52px;
        }

        .favorites-top {
            flex-direction: column;
            align-items: flex-start;
        }

        .favorites-top-total {
            justify-items: start;
        }

        .favorites-grid {
            grid-template-columns: 1fr;
        }

        .favorites-top,
        .favorite-card,
        .favorites-hero-card,
        .favorites-empty-card {
            border-radius: 23px;
        }

        .favorites-top,
        .favorites-empty-card {
            padding: 14px;
        }

        .favorite-actions {
            grid-template-columns: 1fr;
        }

        .favorites-empty-card h2 {
            font-size: 28px;
        }

        .empty-actions .favorites-btn {
            width: 100%;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const messageBox = document.getElementById('favorite-ajax-message');
        const favoritesGrid = document.getElementById('favorites-grid');
        const favoritesTop = document.getElementById('favorites-top');
        const heroStats = document.getElementById('favorites-hero-stats');

        function showFavoriteMessage(type, message) {
            if (!messageBox) {
                return;
            }

            messageBox.hidden = false;
            messageBox.className = 'favorite-ajax-message ' + type;
            messageBox.textContent = message || '';

            window.setTimeout(function () {
                messageBox.hidden = true;
                messageBox.textContent = '';
                messageBox.className = 'favorite-ajax-message';
            }, 2600);
        }

        function renderEmptyState() {
            if (!favoritesGrid) {
                return;
            }

            favoritesGrid.outerHTML = `
                <section class="favorites-empty-card glass-card" id="favorites-empty-state">
                    <div class="empty-icon">♥</div>
                    <span class="favorites-eyebrow light">Favori Listen Boş</span>
                    <h2>Henüz favori ürünün yok.</h2>
                    <p>Ürün detay sayfasından favoriye ekleme yaptığında ürünler burada görünecek.</p>
                    <div class="empty-actions">
                        <a class="favorites-btn favorites-btn-primary" href="<?= e(url('products.php')) ?>">Ürünleri Keşfet</a>
                        <a class="favorites-btn favorites-btn-light" href="<?= e(url('consumer/dashboard.php')) ?>">Panele Dön</a>
                    </div>
                </section>
            `;

            if (favoritesTop) {
                favoritesTop.remove();
            }

            if (heroStats) {
                heroStats.remove();
            }
        }

        document.addEventListener('submit', async function (event) {
            const form = event.target.closest('.favorite-remove-form');

            if (!form) {
                return;
            }

            event.preventDefault();

            const card = form.closest('[data-favorite-card]');
            const button = form.querySelector('button');

            if (!card) {
                return;
            }

            if (!window.confirm('Bu ürünü favorilerinden çıkarmak istediğine emin misin?')) {
                return;
            }

            card.classList.add('removing');

            if (button) {
                button.disabled = true;
            }

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Favori işlemi tamamlanamadı.');
                }

                card.remove();
                showFavoriteMessage('success', result.message || 'Ürün favorilerden çıkarıldı.');

                const remainingCards = document.querySelectorAll('[data-favorite-card]').length;

                if (remainingCards === 0) {
                    renderEmptyState();
                }
            } catch (error) {
                card.classList.remove('removing');

                if (button) {
                    button.disabled = false;
                }

                showFavoriteMessage('error', error.message || 'Favori işlemi sırasında bir hata oluştu.');
            }
        });
    });
</script>

<?php require APP_PATH . '/Views/layouts/footer.php'; ?>