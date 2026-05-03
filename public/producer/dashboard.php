<?php

require_once __DIR__ . '/../../app/bootstrap.php';

ProducerMiddleware::handle();

$controller = new ProducerController();

$producerId = (int) currentUserId();
$data = $controller->dashboardData($producerId);

$summary = $data['summary'] ?? [];
$latestProducts = $data['latest_products'] ?? [];

$pageTitle = 'Üretici Paneli';
$bodyClass = 'page-producer-dashboard';

$user = currentUser();

if (!function_exists('producer_dashboard_product_status_label')) {
    function producer_dashboard_product_status_label(string $status): string
    {
        return match ($status) {
            'active' => 'Aktif',
            'draft' => 'Taslak',
            'sold_out' => 'Stokta Yok',
            'paused' => 'Pasif',
            'deleted' => 'Silindi',
            default => 'Bilinmiyor',
        };
    }
}

if (!function_exists('producer_dashboard_product_status_badge')) {
    function producer_dashboard_product_status_badge(string $status): string
    {
        return match ($status) {
            'active' => 'badge-success',
            'draft' => 'badge-info',
            'sold_out' => 'badge-warning',
            'paused' => 'badge-muted',
            'deleted' => 'badge-danger',
            default => 'badge-muted',
        };
    }
}

if (!function_exists('producer_dashboard_unit_label')) {
    function producer_dashboard_unit_label(string $unit): string
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

if (!function_exists('producer_dashboard_money')) {
    function producer_dashboard_money(float $amount): string
    {
        if (function_exists('formatMoney')) {
            return formatMoney($amount);
        }

        return number_format($amount, 2, ',', '.') . ' TL';
    }
}

if (!function_exists('producer_dashboard_number')) {
    function producer_dashboard_number(float $number): string
    {
        return rtrim(rtrim(number_format($number, 2, ',', '.'), '0'), ',');
    }
}

$totalProducts = (int) ($summary['total_products'] ?? 0);
$soldOutProducts = (int) ($summary['sold_out_products'] ?? 0);
$totalOrders = (int) ($summary['total_orders'] ?? 0);
$totalRevenue = (float) ($summary['total_revenue'] ?? 0);

require APP_PATH . '/Views/layouts/header.php';
?>

<main class="producer-dashboard-page">
    <section class="producer-dashboard-hero">
        <div class="producer-hero-bg producer-blob-one"></div>
        <div class="producer-hero-bg producer-blob-two"></div>

        <div class="producer-dashboard-inner">
            <nav class="producer-breadcrumb" aria-label="Sayfa yolu">
                <a href="<?= e(url('index.php')) ?>">Ana Sayfa</a>
                <span>/</span>
                <strong>Üretici Paneli</strong>
            </nav>

            <div class="producer-hero-content">
                <div class="producer-hero-copy">
                    <span class="producer-eyebrow">
                        EkineraGo Üretici Alanı
                    </span>

                    <h1>
                        Hoş geldin,
                        <span><?= e($user['full_name'] ?? 'Üretici') ?></span>
                    </h1>

                    <p>
                        Buradan ürünlerini yönetebilir, yeni ürün ekleyebilir, stok durumlarını
                        kontrol edebilir ve gelen siparişlerini takip edebilirsin.
                    </p>

                    <div class="producer-hero-actions">
                        <a class="producer-btn producer-btn-primary" href="<?= e(url('producer/product-create.php')) ?>">
                            Yeni Ürün Ekle
                        </a>

                        <a class="producer-btn producer-btn-glass" href="<?= e(url('producer/orders.php')) ?>">
                            Siparişleri Gör
                        </a>
                    </div>

                    <div class="producer-hero-stats">
                        <span>🧺 <?= e((string) $totalProducts) ?> ürün</span>
                        <span>📦 <?= e((string) $totalOrders) ?> sipariş</span>
                        <span>💰 <?= e(producer_dashboard_money($totalRevenue)) ?></span>
                    </div>
                </div>

                <div class="producer-hero-card">
                    <div class="hero-card-icon">👨‍🌾</div>

                    <h2>Ürünlerini doğrudan tüketiciye ulaştır</h2>

                    <p>
                        Ürün, stok, sipariş ve gelir yönetimini tek panelden düzenli şekilde takip et.
                    </p>

                    <div class="hero-mini-list">
                        <span>🌱 Ürün yönetimi</span>
                        <span>📦 Sipariş takibi</span>
                        <span>💳 Gelir özeti</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="producer-dashboard-shell">
        <section class="producer-stats-grid">
            <article class="producer-stat-card glass-card">
                <span class="stat-icon">🧺</span>

                <div>
                    <strong><?= e((string) $totalProducts) ?></strong>
                    <p>Toplam Ürün</p>
                </div>
            </article>

            <article class="producer-stat-card glass-card">
                <span class="stat-icon">⚠️</span>

                <div>
                    <strong><?= e((string) $soldOutProducts) ?></strong>
                    <p>Stokta Olmayan</p>
                </div>
            </article>

            <article class="producer-stat-card glass-card">
                <span class="stat-icon">📦</span>

                <div>
                    <strong><?= e((string) $totalOrders) ?></strong>
                    <p>Toplam Sipariş</p>
                </div>
            </article>

            <article class="producer-stat-card glass-card">
                <span class="stat-icon">💰</span>

                <div>
                    <strong><?= e(producer_dashboard_money($totalRevenue)) ?></strong>
                    <p>Toplam Gelir</p>
                </div>
            </article>
        </section>

        <section class="dashboard-section-head">
            <div>
                <span class="section-kicker">Hızlı İşlemler</span>
                <h2>Bugün ne yapmak istiyorsun?</h2>
                <p>Ürün, sipariş ve müşteri iletişimi işlemlerine hızlıca ulaşabilirsin.</p>
            </div>
        </section>

        <section class="producer-action-grid">
            <article class="producer-action-card glass-card">
                <div class="action-icon">🧺</div>

                <h3>Ürünlerim</h3>

                <p>
                    Aktif ürünlerini listele, stok bilgilerini kontrol et, düzenle veya pasifleştir.
                </p>

                <a class="producer-btn producer-btn-primary" href="<?= e(url('producer/products.php')) ?>">
                    Ürünleri Gör
                </a>
            </article>

            <article class="producer-action-card glass-card">
                <div class="action-icon">➕</div>

                <h3>Yeni Ürün Ekle</h3>

                <p>
                    Ürün adı, kategori, fiyat, stok, hasat tarihi ve ön sipariş bilgilerini gir.
                </p>

                <a class="producer-btn producer-btn-primary" href="<?= e(url('producer/product-create.php')) ?>">
                    Ürün Ekle
                </a>
            </article>

            <article class="producer-action-card glass-card">
                <div class="action-icon">📦</div>

                <h3>Gelen Siparişler</h3>

                <p>
                    Yeni siparişleri, ödeme durumlarını ve teslimat süreçlerini takip et.
                </p>

                <a class="producer-btn producer-btn-light" href="<?= e(url('producer/orders.php')) ?>">
                    Siparişlere Git
                </a>
            </article>

            <article class="producer-action-card glass-card">
                <div class="action-icon">❔</div>

                <h3>Ürün Soruları</h3>

                <p>
                    Tüketicilerden gelen ürün sorularını görüntüle ve cevapla.
                </p>

                <a class="producer-btn producer-btn-light" href="<?= e(url('producer/questions.php')) ?>">
                    Soruları Gör
                </a>
            </article>
        </section>

        <section class="producer-lower-grid">
            <section class="latest-products-card glass-card">
                <div class="section-title-row">
                    <div>
                        <span class="section-kicker">Ürün Akışı</span>

                        <h2>Son Eklenen Ürünler</h2>

                        <p>
                            En son oluşturduğun veya güncellediğin ürünler burada görünür.
                        </p>
                    </div>

                    <a class="producer-btn producer-btn-light" href="<?= e(url('producer/products.php')) ?>">
                        Tümünü Gör
                    </a>
                </div>

                <?php if (empty($latestProducts)): ?>
                    <div class="empty-state">
                        <span>🌱</span>

                        <h3>Henüz ürün yok</h3>

                        <p>
                            İlk ürününü ekleyerek üretici panelini kullanmaya başlayabilirsin.
                        </p>

                        <a class="producer-btn producer-btn-primary" href="<?= e(url('producer/product-create.php')) ?>">
                            İlk Ürünü Ekle
                        </a>
                    </div>
                <?php else: ?>
                    <div class="latest-products-list">
                        <?php foreach ($latestProducts as $product): ?>
                            <?php
                                $status = $product['status'] ?? 'draft';
                                $unit = producer_dashboard_unit_label($product['unit_type'] ?? 'kg');
                                $productId = (int) ($product['id'] ?? 0);
                                $stockQuantity = (float) ($product['stock_quantity'] ?? 0);
                                $price = (float) ($product['price'] ?? 0);
                            ?>

                            <article class="latest-product-item">
                                <div class="product-mini-icon">
                                    🌿
                                </div>

                                <div class="product-main-info">
                                    <h3><?= e($product['title'] ?? 'Ürün') ?></h3>

                                    <p>
                                        <?= e($product['category_name'] ?? 'Kategori yok') ?>
                                    </p>
                                </div>

                                <div class="product-price-info">
                                    <span>Fiyat</span>
                                    <strong><?= e(producer_dashboard_money($price)) ?> / <?= e($unit) ?></strong>
                                </div>

                                <div class="product-stock-info">
                                    <span>Stok</span>
                                    <strong><?= e(producer_dashboard_number($stockQuantity)) ?> <?= e($unit) ?></strong>
                                </div>

                                <div class="product-status-info">
                                    <span class="producer-badge <?= e(producer_dashboard_product_status_badge($status)) ?>">
                                        <?= e(producer_dashboard_product_status_label($status)) ?>
                                    </span>
                                </div>

                                <div class="product-action-info">
                                    <a class="small-link" href="<?= e(url('producer/product-edit.php?id=' . $productId)) ?>">
                                        Düzenle
                                    </a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <aside class="producer-guide-card glass-card">
                <div class="section-heading">
                    <span class="section-icon">🧭</span>

                    <div>
                        <h2>Üretici Akışı</h2>
                        <p>Paneli düzenli kullanmak için önerilen adımlar.</p>
                    </div>
                </div>

                <div class="producer-step-list">
                    <div class="producer-step">
                        <span>1</span>

                        <div>
                            <strong>Ürünlerini güncel tut</strong>
                            <p>Stok, fiyat ve durum bilgilerini düzenli kontrol et.</p>
                        </div>
                    </div>

                    <div class="producer-step">
                        <span>2</span>

                        <div>
                            <strong>Siparişleri takip et</strong>
                            <p>Gelen siparişlerin durumunu zamanında güncelle.</p>
                        </div>
                    </div>

                    <div class="producer-step">
                        <span>3</span>

                        <div>
                            <strong>Soruları cevapla</strong>
                            <p>Tüketici güveni için ürün sorularına açıklayıcı cevap ver.</p>
                        </div>
                    </div>
                </div>

                <div class="producer-side-actions">
                    <a class="producer-btn producer-btn-light full" href="<?= e(url('producer/profile.php')) ?>">
                        Profilimi Düzenle
                    </a>

                    <a class="producer-btn producer-btn-light full" href="<?= e(url('producer/notifications.php')) ?>">
                        Bildirimler
                    </a>
                </div>
            </aside>
        </section>
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
        --producer-red: #9b111e;
        --producer-text: #1e2b21;
        --producer-muted: #687669;
        --producer-border: rgba(47, 125, 61, .14);
        --producer-shadow: 0 24px 70px rgba(31, 79, 43, .12);
        --producer-radius-lg: 28px;
    }

    body.page-producer-dashboard {
        background:
            radial-gradient(circle at 14% 12%, rgba(196, 231, 177, .48), transparent 28%),
            radial-gradient(circle at 88% 16%, rgba(242, 191, 77, .16), transparent 24%),
            linear-gradient(180deg, #f8fbf2 0%, #f3f8ed 48%, #ffffff 100%);
    }

    .producer-dashboard-page {
        overflow: hidden;
    }

    .producer-dashboard-hero {
        position: relative;
        min-height: 390px;
        padding: 34px 18px 94px;
        background:
            radial-gradient(circle at 82% 18%, rgba(242, 191, 77, .30), transparent 26%),
            radial-gradient(circle at 12% 78%, rgba(255, 255, 255, .16), transparent 24%),
            linear-gradient(135deg, rgba(36, 92, 47, .97), rgba(47, 125, 61, .87));
        color: #ffffff;
    }

    .producer-dashboard-hero::after {
        content: '';
        position: absolute;
        inset: auto 0 -1px;
        height: 90px;
        background: linear-gradient(180deg, rgba(246, 251, 244, 0), #f6fbf4 82%);
        pointer-events: none;
    }

    .producer-dashboard-inner,
    .producer-dashboard-shell {
        width: min(1180px, calc(100% - 32px));
        margin: 0 auto;
    }

    .producer-dashboard-inner {
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
        top: 42px;
        background: rgba(242, 191, 77, .28);
    }

    .producer-blob-two {
        width: 150px;
        height: 150px;
        left: 8%;
        bottom: 36px;
        background: rgba(255, 255, 255, .20);
    }

    .producer-breadcrumb {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 9px;
        font-size: 14px;
        margin-bottom: 32px;
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
        grid-template-columns: minmax(0, 1.25fr) minmax(320px, .75fr);
        gap: 24px;
        align-items: center;
    }

    .producer-eyebrow,
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

    .producer-eyebrow {
        background: rgba(255, 255, 255, .16);
        border: 1px solid rgba(255, 255, 255, .28);
        color: #ffffff;
    }

    .section-kicker {
        background: var(--producer-green-100);
        color: var(--producer-green-800);
    }

    .producer-hero-copy h1 {
        margin: 17px 0 12px;
        font-size: clamp(36px, 5vw, 62px);
        line-height: 1.03;
        letter-spacing: -.045em;
    }

    .producer-hero-copy h1 span {
        color: #fff7d6;
    }

    .producer-hero-copy p {
        max-width: 700px;
        margin: 0;
        color: rgba(255, 255, 255, .86);
        font-size: 17px;
        line-height: 1.7;
    }

    .producer-hero-actions,
    .producer-hero-stats {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .producer-hero-actions {
        margin-top: 22px;
    }

    .producer-hero-stats {
        margin-top: 18px;
    }

    .producer-hero-stats span {
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

    .producer-hero-card {
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

    .producer-hero-card h2 {
        margin: 0 0 10px;
        font-size: 25px;
        letter-spacing: -.03em;
    }

    .producer-hero-card p {
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

    .producer-dashboard-shell {
        position: relative;
        z-index: 3;
        margin-top: -64px;
        padding-bottom: 54px;
    }

    .glass-card {
        background: rgba(255, 255, 255, .92);
        border: 1px solid rgba(255, 255, 255, .72);
        border-radius: var(--producer-radius-lg);
        box-shadow: var(--producer-shadow);
        backdrop-filter: blur(16px);
    }

    .producer-stats-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
        margin-bottom: 22px;
    }

    .producer-stat-card {
        display: flex;
        align-items: center;
        gap: 13px;
        padding: 18px;
    }

    .stat-icon,
    .action-icon,
    .section-icon,
    .product-mini-icon {
        width: 48px;
        height: 48px;
        display: grid;
        place-items: center;
        border-radius: 17px;
        background: var(--producer-green-100);
        flex: 0 0 auto;
        font-size: 22px;
    }

    .producer-stat-card strong {
        display: block;
        color: var(--producer-green-900);
        font-size: 21px;
        letter-spacing: -.02em;
    }

    .producer-stat-card p {
        margin: 4px 0 0;
        color: var(--producer-muted);
        font-size: 13px;
        font-weight: 800;
    }

    .dashboard-section-head {
        margin: 8px 0 18px;
    }

    .dashboard-section-head h2 {
        margin: 12px 0 6px;
        color: var(--producer-green-900);
        font-size: 30px;
        letter-spacing: -.035em;
    }

    .dashboard-section-head p {
        margin: 0;
        color: var(--producer-muted);
        line-height: 1.6;
    }

    .producer-action-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 18px;
        margin-bottom: 22px;
    }

    .producer-action-card {
        padding: 20px;
        display: flex;
        flex-direction: column;
        min-height: 280px;
        transition: transform .2s ease, box-shadow .2s ease;
    }

    .producer-action-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 28px 68px rgba(31, 79, 43, .15);
    }

    .producer-action-card h3 {
        margin: 18px 0 9px;
        color: var(--producer-green-900);
        font-size: 22px;
        letter-spacing: -.02em;
    }

    .producer-action-card p {
        margin: 0 0 18px;
        color: var(--producer-muted);
        line-height: 1.6;
        flex: 1;
    }

    .producer-btn {
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
        white-space: nowrap;
    }

    .producer-btn:hover {
        transform: translateY(-2px);
    }

    .producer-btn.full {
        width: 100%;
    }

    .producer-btn-primary {
        background: linear-gradient(135deg, var(--producer-green-700), var(--producer-green-900));
        color: #ffffff;
        box-shadow: 0 16px 32px rgba(47, 125, 61, .24);
    }

    .producer-btn-light {
        background: var(--producer-green-50);
        color: var(--producer-green-800);
        border: 1px solid var(--producer-border);
    }

    .producer-btn-glass {
        background: rgba(255, 255, 255, .16);
        color: #ffffff;
        border: 1px solid rgba(255, 255, 255, .28);
    }

    .producer-lower-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.4fr) minmax(320px, .6fr);
        gap: 22px;
        align-items: start;
    }

    .latest-products-card,
    .producer-guide-card {
        padding: 20px;
    }

    .section-title-row {
        display: flex;
        justify-content: space-between;
        gap: 18px;
        align-items: flex-start;
        margin-bottom: 18px;
        padding-bottom: 16px;
        border-bottom: 1px solid rgba(47, 125, 61, .10);
    }

    .section-title-row h2,
    .section-heading h2 {
        margin: 10px 0 6px;
        color: var(--producer-green-900);
        font-size: 27px;
        letter-spacing: -.03em;
    }

    .section-title-row p,
    .section-heading p {
        margin: 0;
        color: var(--producer-muted);
        line-height: 1.6;
    }

    .latest-products-list {
        display: grid;
        gap: 12px;
    }

    .latest-product-item {
        display: grid;
        grid-template-columns: 48px minmax(0, 1fr) minmax(120px, auto) minmax(100px, auto) auto auto;
        gap: 14px;
        align-items: center;
        padding: 14px;
        border-radius: 20px;
        background: #fbfdf8;
        border: 1px solid var(--producer-border);
        transition: transform .18s ease, box-shadow .18s ease;
    }

    .latest-product-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 16px 34px rgba(31, 79, 43, .10);
    }

    .product-main-info h3 {
        margin: 0 0 5px;
        color: var(--producer-green-900);
        font-size: 17px;
    }

    .product-main-info p,
    .product-price-info span,
    .product-stock-info span {
        margin: 0;
        color: var(--producer-muted);
        font-size: 13px;
        font-weight: 800;
    }

    .product-price-info,
    .product-stock-info {
        display: grid;
        gap: 5px;
    }

    .product-price-info strong,
    .product-stock-info strong {
        color: var(--producer-green-900);
        font-size: 14px;
        white-space: nowrap;
    }

    .producer-badge {
        display: inline-flex;
        align-items: center;
        padding: 7px 10px;
        border-radius: 999px;
        font-size: 13px;
        font-weight: 900;
        white-space: nowrap;
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

    .badge-danger {
        background: #ffe8e8;
        color: #9b111e;
    }

    .small-link {
        color: var(--producer-green-700);
        font-weight: 900;
        text-decoration: none;
        white-space: nowrap;
    }

    .small-link:hover {
        text-decoration: underline;
    }

    .empty-state {
        display: grid;
        place-items: center;
        gap: 8px;
        text-align: center;
        padding: 34px 18px;
        border-radius: 22px;
        background: #fbfdf8;
        border: 1px dashed rgba(47, 125, 61, .24);
    }

    .empty-state span {
        font-size: 36px;
    }

    .empty-state h3 {
        margin: 0;
        color: var(--producer-green-900);
        font-size: 22px;
    }

    .empty-state p {
        margin: 0 0 10px;
        color: var(--producer-muted);
        font-weight: 800;
        line-height: 1.6;
    }

    .section-heading {
        display: flex;
        align-items: flex-start;
        gap: 13px;
        margin-bottom: 18px;
        padding-bottom: 16px;
        border-bottom: 1px solid rgba(47, 125, 61, .10);
    }

    .section-heading h2 {
        margin-top: 0;
    }

    .producer-step-list {
        display: grid;
        gap: 12px;
    }

    .producer-step {
        display: grid;
        grid-template-columns: 42px minmax(0, 1fr);
        gap: 12px;
        padding: 14px;
        border-radius: 20px;
        background: #fbfdf8;
        border: 1px solid var(--producer-border);
    }

    .producer-step > span {
        width: 42px;
        height: 42px;
        display: grid;
        place-items: center;
        border-radius: 15px;
        background: linear-gradient(135deg, var(--producer-green-700), var(--producer-green-900));
        color: #ffffff;
        font-weight: 900;
    }

    .producer-step strong {
        color: var(--producer-green-900);
    }

    .producer-step p {
        margin: 5px 0 0;
        color: var(--producer-muted);
        line-height: 1.55;
    }

    .producer-side-actions {
        display: grid;
        gap: 10px;
        margin-top: 16px;
    }

    @media (max-width: 1160px) {
        .producer-action-grid,
        .producer-stats-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .producer-hero-content,
        .producer-lower-grid {
            grid-template-columns: 1fr;
        }

        .latest-product-item {
            grid-template-columns: 48px minmax(0, 1fr) auto;
        }

        .product-price-info,
        .product-stock-info,
        .product-status-info {
            grid-column: 2 / -1;
        }

        .product-action-info {
            grid-column: 3 / 4;
            grid-row: 1 / 2;
        }
    }

    @media (max-width: 720px) {
        .producer-dashboard-hero {
            min-height: 430px;
            padding-top: 24px;
        }

        .producer-dashboard-inner,
        .producer-dashboard-shell {
            width: min(100% - 22px, 1180px);
        }

        .producer-breadcrumb {
            font-size: 13px;
            margin-bottom: 24px;
        }

        .producer-hero-copy p {
            font-size: 15px;
        }

        .producer-dashboard-shell {
            margin-top: -52px;
        }

        .producer-stats-grid,
        .producer-action-grid {
            grid-template-columns: 1fr;
        }

        .producer-stat-card,
        .producer-action-card,
        .latest-products-card,
        .producer-guide-card,
        .producer-hero-card {
            border-radius: 23px;
        }

        .producer-stat-card,
        .producer-action-card,
        .latest-products-card,
        .producer-guide-card,
        .producer-hero-card {
            padding: 14px;
        }

        .producer-action-card {
            min-height: auto;
        }

        .section-title-row {
            flex-direction: column;
        }

        .producer-hero-actions .producer-btn,
        .producer-side-actions .producer-btn,
        .section-title-row .producer-btn {
            width: 100%;
        }

        .latest-product-item {
            grid-template-columns: 1fr;
        }

        .product-price-info,
        .product-stock-info,
        .product-status-info,
        .product-action-info {
            grid-column: auto;
            grid-row: auto;
        }

        .product-action-info .small-link {
            display: inline-flex;
            justify-content: center;
            width: 100%;
            min-height: 42px;
            align-items: center;
            border-radius: 14px;
            background: var(--producer-green-50);
            border: 1px solid var(--producer-border);
        }

        .dashboard-section-head h2,
        .section-title-row h2,
        .section-heading h2 {
            font-size: 25px;
        }
    }
</style>

<?php require APP_PATH . '/Views/layouts/footer.php'; ?>