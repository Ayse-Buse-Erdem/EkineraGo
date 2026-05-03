<?php

require_once __DIR__ . '/../../app/bootstrap.php';

ProducerMiddleware::handle();

$userId = (int) currentUserId();
$controller = new ProductController();
$productService = new ProductService();

if (!function_exists('producer_products_json')) {
    function producer_products_json(array $payload): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

if (!function_exists('producer_product_status_label')) {
    function producer_product_status_label(string $status): string
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

if (!function_exists('producer_product_status_badge')) {
    function producer_product_status_badge(string $status): string
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

if (!function_exists('producer_unit_label')) {
    function producer_unit_label(string $unit): string
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

if (!function_exists('producer_products_money')) {
    function producer_products_money(float $amount): string
    {
        if (function_exists('formatMoney')) {
            return formatMoney($amount);
        }

        return number_format($amount, 2, ',', '.') . ' TL';
    }
}

if (!function_exists('producer_products_number')) {
    function producer_products_number(float $number): string
    {
        return rtrim(rtrim(number_format($number, 2, ',', '.'), '0'), ',');
    }
}

if (!function_exists('producer_products_date')) {
    function producer_products_date(?string $date): string
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

if (!function_exists('producer_products_stats')) {
    function producer_products_stats(array $products): array
    {
        $stats = [
            'total' => count($products),
            'active' => 0,
            'paused' => 0,
            'draft' => 0,
            'sold_out' => 0,
            'preorder' => 0,
            'stock_total' => 0,
            'stock_value' => 0,
        ];

        foreach ($products as $product) {
            $status = (string) ($product['status'] ?? 'draft');
            $stock = (float) ($product['stock_quantity'] ?? 0);
            $price = (float) ($product['price'] ?? 0);

            if (isset($stats[$status])) {
                $stats[$status]++;
            }

            if (!empty($product['is_preorder_enabled'])) {
                $stats['preorder']++;
            }

            $stats['stock_total'] += $stock;
            $stats['stock_value'] += $stock * $price;
        }

        return $stats;
    }
}

if (!function_exists('producer_products_fetch_products')) {
    function producer_products_fetch_products(ProductController $controller, int $producerId): array
    {
        $data = $controller->producerIndexData($producerId);

        return $data['products'] ?? [];
    }
}

if (!function_exists('producer_products_render_hero_stats')) {
    function producer_products_render_hero_stats(array $products): string
    {
        if (empty($products)) {
            return '';
        }

        $stats = producer_products_stats($products);

        ob_start();
        ?>
        <div class="producer-products-hero-stats">
            <span>🧺 <?= e((string) $stats['total']) ?> ürün</span>
            <span>🌱 <?= e((string) $stats['active']) ?> aktif</span>
            <span>⚠️ <?= e((string) $stats['sold_out']) ?> stokta yok</span>
            <span>💰 <?= e(producer_products_money((float) $stats['stock_value'])) ?></span>
        </div>
        <?php

        return ob_get_clean();
    }
}

if (!function_exists('producer_products_render_dynamic_area')) {
    function producer_products_render_dynamic_area(array $products): string
    {
        $stats = producer_products_stats($products);

        ob_start();
        ?>

        <?php if (empty($products)): ?>
            <section class="products-empty-card glass-card">
                <div class="empty-icon">🌱</div>

                <span class="producer-products-eyebrow light">Henüz Ürün Yok</span>

                <h2>Henüz ürün eklenmedi.</h2>

                <p>
                    İlk ürününü ekleyerek üretici mini-marketini oluşturmaya başlayabilirsin.
                </p>

                <div class="empty-actions">
                    <a class="producer-products-btn producer-products-btn-primary" href="<?= e(url('producer/product-create.php')) ?>">
                        Ürün Ekle
                    </a>

                    <a class="producer-products-btn producer-products-btn-light" href="<?= e(url('producer/dashboard.php')) ?>">
                        Panele Dön
                    </a>
                </div>
            </section>
        <?php else: ?>
            <section class="products-top glass-card">
                <div>
                    <span class="section-kicker">Ürün Özeti</span>

                    <h2>Ürün yönetimi</h2>

                    <p>
                        Ürünlerini görüntüleyebilir, public detay sayfasına gidebilir,
                        durumunu değiştirebilir veya silebilirsin.
                    </p>
                </div>

                <a class="producer-products-btn producer-products-btn-primary" href="<?= e(url('producer/product-create.php')) ?>">
                    Yeni Ürün Ekle
                </a>
            </section>

            <section class="products-summary-grid">
                <article class="product-stat-card glass-card">
                    <span>🧺</span>

                    <div>
                        <strong><?= e((string) $stats['total']) ?></strong>
                        <p>Toplam Ürün</p>
                    </div>
                </article>

                <article class="product-stat-card glass-card">
                    <span>🌱</span>

                    <div>
                        <strong><?= e((string) $stats['active']) ?></strong>
                        <p>Aktif Ürün</p>
                    </div>
                </article>

                <article class="product-stat-card glass-card">
                    <span>⏸️</span>

                    <div>
                        <strong><?= e((string) $stats['paused']) ?></strong>
                        <p>Pasif Ürün</p>
                    </div>
                </article>

                <article class="product-stat-card glass-card">
                    <span>⚠️</span>

                    <div>
                        <strong><?= e((string) $stats['sold_out']) ?></strong>
                        <p>Stokta Yok</p>
                    </div>
                </article>
            </section>

            <section class="products-control-card glass-card">
                <div class="products-filter-row">
                    <div class="search-box">
                        <span>🔎</span>

                        <input
                            type="search"
                            id="producer-product-search"
                            placeholder="Ürün adı veya kategori ara..."
                            autocomplete="off"
                        >
                    </div>

                    <select id="producer-product-status-filter">
                        <option value="">Tüm durumlar</option>
                        <option value="active">Aktif</option>
                        <option value="paused">Pasif</option>
                        <option value="draft">Taslak</option>
                        <option value="sold_out">Stokta Yok</option>
                    </select>
                </div>
            </section>

            <section class="products-grid" id="producer-products-grid">
                <?php foreach ($products as $product): ?>
                    <?php
                        $productId = (int) ($product['id'] ?? 0);
                        $status = (string) ($product['status'] ?? 'draft');
                        $unit = producer_unit_label($product['unit_type'] ?? 'kg');
                        $stock = (float) ($product['stock_quantity'] ?? 0);
                        $price = (float) ($product['price'] ?? 0);
                        $coverImage = $product['cover_image']
                            ?? $product['cover_image_path']
                            ?? $product['image_path']
                            ?? '';
                        $title = $product['title'] ?? 'Ürün';
                        $category = $product['category_name'] ?? '-';
                        $searchText = strtolower(trim($title . ' ' . $category));
                    ?>

                    <article
                        class="producer-product-card glass-card"
                        data-product-card="<?= e((string) $productId) ?>"
                        data-status="<?= e($status) ?>"
                        data-search="<?= e($searchText) ?>"
                    >
                        <a class="product-image-link" href="<?= e(url('product-detail.php?id=' . $productId)) ?>">
                            <?php if (!empty($coverImage)): ?>
                                <img
                                    src="<?= e(url($coverImage)) ?>"
                                    alt="<?= e($title) ?>"
                                >
                            <?php else: ?>
                                <div class="product-image-placeholder">
                                    <span>🥬</span>
                                    <strong>Ürün Fotoğrafı</strong>
                                </div>
                            <?php endif; ?>

                            <div class="product-image-badges">
                                <span class="product-badge <?= e(producer_product_status_badge($status)) ?>">
                                    <?= e(producer_product_status_label($status)) ?>
                                </span>

                                <?php if (!empty($product['is_preorder_enabled'])): ?>
                                    <span class="product-badge badge-info">
                                        Ön Sipariş
                                    </span>
                                <?php endif; ?>
                            </div>
                        </a>

                        <div class="product-card-body">
                            <div class="product-title-row">
                                <div>
                                    <h2>
                                        <a href="<?= e(url('product-detail.php?id=' . $productId)) ?>">
                                            <?= e($title) ?>
                                        </a>
                                    </h2>

                                    <p><?= e($category) ?></p>
                                </div>

                                <span class="mini-status-dot <?= e($status) ?>"></span>
                            </div>

                            <div class="product-price-row">
                                <strong><?= e(producer_products_money($price)) ?></strong>
                                <span>/ <?= e($unit) ?></span>
                            </div>

                            <div class="product-meta-grid">
                                <div>
                                    <span>Stok</span>
                                    <strong><?= e(producer_products_number($stock)) ?> <?= e($unit) ?></strong>
                                </div>

                                <div>
                                    <span>Hasat</span>
                                    <strong><?= e(producer_products_date($product['harvest_date'] ?? null)) ?></strong>
                                </div>

                                <div>
                                    <span>Puan</span>
                                    <strong>
                                        <?php if ((int) ($product['rating_count'] ?? 0) > 0): ?>
                                            <?= e(number_format((float) ($product['average_rating'] ?? 0), 1, ',', '.')) ?> ⭐
                                        <?php else: ?>
                                            Yok
                                        <?php endif; ?>
                                    </strong>
                                </div>

                                <div>
                                    <span>Favori</span>
                                    <strong><?= e((string) ($product['favorite_count'] ?? 0)) ?></strong>
                                </div>
                            </div>

                            <div class="product-action-grid">
                                <a class="producer-products-btn producer-products-btn-primary full" href="<?= e(url('producer/product-edit.php?id=' . $productId)) ?>">
                                    Düzenle
                                </a>

                                <a class="producer-products-btn producer-products-btn-light full" href="<?= e(url('product-detail.php?id=' . $productId)) ?>">
                                    Gör
                                </a>
                            </div>

                            <div class="inline-actions">
                                <?php if ($status === 'active'): ?>
                                    <form
                                        method="POST"
                                        action="<?= e(url('producer/products.php')) ?>"
                                        data-product-ajax="true"
                                    >
                                        <?= csrf_field() ?>

                                        <input type="hidden" name="_action" value="change_status">
                                        <input type="hidden" name="product_id" value="<?= e((string) $productId) ?>">
                                        <input type="hidden" name="status" value="paused">

                                        <button class="text-action" type="submit">
                                            Pasifleştir
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form
                                        method="POST"
                                        action="<?= e(url('producer/products.php')) ?>"
                                        data-product-ajax="true"
                                    >
                                        <?= csrf_field() ?>

                                        <input type="hidden" name="_action" value="change_status">
                                        <input type="hidden" name="product_id" value="<?= e((string) $productId) ?>">
                                        <input type="hidden" name="status" value="active">

                                        <button class="text-action" type="submit">
                                            Aktif Yap
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <form
                                    method="POST"
                                    action="<?= e(url('producer/products.php')) ?>"
                                    data-product-ajax="true"
                                    data-confirm="Bu ürünü silmek istediğine emin misin?"
                                >
                                    <?= csrf_field() ?>

                                    <input type="hidden" name="_action" value="delete">
                                    <input type="hidden" name="product_id" value="<?= e((string) $productId) ?>">

                                    <button class="text-action danger" type="submit">
                                        Sil
                                    </button>
                                </form>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>

            <div class="products-no-result" id="products-no-result" hidden>
                <span>🔎</span>
                <strong>Aramana uygun ürün bulunamadı.</strong>
                <p>Farklı bir kelime veya durum filtresi deneyebilirsin.</p>
            </div>
        <?php endif; ?>

        <?php

        return ob_get_clean();
    }
}

$isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';

if (is_post()) {
    require_csrf();

    $action = $_POST['_action'] ?? '';
    $productId = (int) ($_POST['product_id'] ?? 0);

    $result = [
        'success' => false,
        'message' => 'Geçersiz ürün işlemi.',
    ];

    if ($action === 'delete') {
        $result = $productService->deleteProduct($userId, $productId);
    } elseif ($action === 'change_status') {
        $status = trim((string) ($_POST['status'] ?? ''));
        $result = $productService->changeStatus($userId, $productId, $status);
    }

    $products = producer_products_fetch_products($controller, $userId);

    if ($isAjax) {
        producer_products_json([
            'success' => (bool) ($result['success'] ?? false),
            'message' => $result['message'] ?? '',
            'html' => producer_products_render_dynamic_area($products),
            'heroStatsHtml' => producer_products_render_hero_stats($products),
        ]);
    }

    if ($result['success']) {
        flash_success($result['message']);
    } else {
        flash_error($result['message']);
    }

    redirect('producer/products.php');
}

$products = producer_products_fetch_products($controller, $userId);

$pageTitle = 'Ürünlerim';
$bodyClass = 'page-producer-products';

require APP_PATH . '/Views/layouts/header.php';
?>

<main class="producer-products-page">
    <section class="producer-products-hero">
        <div class="producer-products-hero-bg products-blob-one"></div>
        <div class="producer-products-hero-bg products-blob-two"></div>

        <div class="producer-products-hero-inner">
            <nav class="producer-products-breadcrumb" aria-label="Sayfa yolu">
                <a href="<?= e(url('index.php')) ?>">Ana Sayfa</a>
                <span>/</span>
                <a href="<?= e(url('producer/dashboard.php')) ?>">Üretici Paneli</a>
                <span>/</span>
                <strong>Ürünlerim</strong>
            </nav>

            <div class="producer-products-hero-content">
                <div class="producer-products-hero-copy">
                    <span class="producer-products-eyebrow">
                        Ürün Yönetimi
                    </span>

                    <h1>Ürünlerim</h1>

                    <p>
                        Eklediğin ürünleri buradan görüntüleyebilir, düzenleyebilir,
                        pasifleştirebilir veya silebilirsin.
                    </p>

                    <div id="producer-products-hero-stats-wrap">
                        <?= producer_products_render_hero_stats($products) ?>
                    </div>
                </div>

                <div class="producer-products-hero-card">
                    <div class="hero-card-icon">🧺</div>

                    <h2>Mini-marketini yönet</h2>

                    <p>
                        Ürün, stok, fiyat ve satış durumlarını tek ekrandan hızlıca kontrol et.
                    </p>

                    <div class="hero-mini-list">
                        <span>🌱 Stok takibi</span>
                        <span>💰 Fiyat yönetimi</span>
                        <span>📦 Satış durumu</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="producer-products-shell">
        <div id="producer-products-message" class="producer-products-message" hidden></div>

        <div id="producer-products-dynamic-area">
            <?= producer_products_render_dynamic_area($products) ?>
        </div>
    </section>
</main>

<style>
    :root {
        --products-green-950: #102f1a;
        --products-green-900: #163d22;
        --products-green-800: #245c2f;
        --products-green-700: #2f7d3d;
        --products-green-600: #3f9650;
        --products-green-100: #eaf6e8;
        --products-green-50: #f6fbf4;
        --products-cream: #fffaf1;
        --products-yellow: #f2bf4d;
        --products-red: #9b111e;
        --products-text: #1e2b21;
        --products-muted: #687669;
        --products-border: rgba(47, 125, 61, .14);
        --products-shadow: 0 24px 70px rgba(31, 79, 43, .12);
        --products-radius-lg: 28px;
    }

    body.page-producer-products {
        background:
            radial-gradient(circle at 14% 12%, rgba(196, 231, 177, .48), transparent 28%),
            radial-gradient(circle at 88% 16%, rgba(242, 191, 77, .16), transparent 24%),
            linear-gradient(180deg, #f8fbf2 0%, #f3f8ed 48%, #ffffff 100%);
    }

    .producer-products-page {
        overflow: hidden;
    }

    .producer-products-hero {
        position: relative;
        min-height: 390px;
        padding: 34px 18px 94px;
        background:
            radial-gradient(circle at 82% 18%, rgba(242, 191, 77, .30), transparent 26%),
            radial-gradient(circle at 12% 78%, rgba(255, 255, 255, .16), transparent 24%),
            linear-gradient(135deg, rgba(36, 92, 47, .97), rgba(47, 125, 61, .87));
        color: #ffffff;
    }

    .producer-products-hero::after {
        content: '';
        position: absolute;
        inset: auto 0 -1px;
        height: 90px;
        background: linear-gradient(180deg, rgba(246, 251, 244, 0), #f6fbf4 82%);
        pointer-events: none;
    }

    .producer-products-hero-inner,
    .producer-products-shell {
        width: min(1180px, calc(100% - 32px));
        margin: 0 auto;
    }

    .producer-products-hero-inner {
        position: relative;
        z-index: 2;
    }

    .producer-products-hero-bg {
        position: absolute;
        border-radius: 999px;
        filter: blur(2px);
        opacity: .45;
        pointer-events: none;
    }

    .products-blob-one {
        width: 230px;
        height: 230px;
        right: 10%;
        top: 42px;
        background: rgba(242, 191, 77, .28);
    }

    .products-blob-two {
        width: 150px;
        height: 150px;
        left: 8%;
        bottom: 36px;
        background: rgba(255, 255, 255, .20);
    }

    .producer-products-breadcrumb {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 9px;
        font-size: 14px;
        margin-bottom: 32px;
        color: rgba(255, 255, 255, .76);
    }

    .producer-products-breadcrumb a {
        color: #ffffff;
        text-decoration: none;
        font-weight: 800;
    }

    .producer-products-breadcrumb strong {
        color: #ffffff;
        font-weight: 900;
    }

    .producer-products-hero-content {
        display: grid;
        grid-template-columns: minmax(0, 1.25fr) minmax(320px, .75fr);
        gap: 24px;
        align-items: center;
    }

    .producer-products-eyebrow,
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

    .producer-products-eyebrow {
        background: rgba(255, 255, 255, .16);
        border: 1px solid rgba(255, 255, 255, .28);
        color: #ffffff;
    }

    .producer-products-eyebrow.light,
    .section-kicker {
        background: var(--products-green-100);
        color: var(--products-green-800);
        border-color: transparent;
    }

    .producer-products-hero-copy h1 {
        margin: 17px 0 12px;
        font-size: clamp(36px, 5vw, 62px);
        line-height: 1.03;
        letter-spacing: -.045em;
    }

    .producer-products-hero-copy p {
        max-width: 700px;
        margin: 0;
        color: rgba(255, 255, 255, .86);
        font-size: 17px;
        line-height: 1.7;
    }

    .producer-products-hero-stats {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 18px;
    }

    .producer-products-hero-stats span {
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

    .producer-products-hero-card {
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

    .producer-products-hero-card h2 {
        margin: 0 0 10px;
        font-size: 25px;
        letter-spacing: -.03em;
    }

    .producer-products-hero-card p {
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

    .producer-products-shell {
        position: relative;
        z-index: 3;
        margin-top: -64px;
        padding-bottom: 54px;
    }

    .glass-card {
        background: rgba(255, 255, 255, .92);
        border: 1px solid rgba(255, 255, 255, .72);
        border-radius: var(--products-radius-lg);
        box-shadow: var(--products-shadow);
        backdrop-filter: blur(16px);
    }

    .producer-products-message {
        margin-bottom: 16px;
        padding: 14px 16px;
        border-radius: 18px;
        font-weight: 900;
        box-shadow: 0 12px 30px rgba(31, 79, 43, .10);
    }

    .producer-products-message.success {
        background: #e7f7e8;
        color: #236b2c;
        border: 1px solid rgba(35, 107, 44, .14);
    }

    .producer-products-message.error {
        background: #fdeaea;
        color: var(--products-red);
        border: 1px solid rgba(155, 17, 30, .14);
    }

    .products-top {
        margin-bottom: 18px;
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 18px;
    }

    .products-top h2,
    .products-empty-card h2 {
        margin: 10px 0 6px;
        color: var(--products-green-900);
        letter-spacing: -.03em;
    }

    .products-top p,
    .products-empty-card p {
        margin: 0;
        color: var(--products-muted);
        line-height: 1.6;
    }

    .products-summary-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
        margin-bottom: 22px;
    }

    .product-stat-card {
        display: flex;
        align-items: center;
        gap: 13px;
        padding: 18px;
    }

    .product-stat-card > span {
        width: 48px;
        height: 48px;
        display: grid;
        place-items: center;
        border-radius: 17px;
        background: var(--products-green-100);
        flex: 0 0 auto;
        font-size: 22px;
    }

    .product-stat-card strong {
        display: block;
        color: var(--products-green-900);
        font-size: 21px;
    }

    .product-stat-card p {
        margin: 4px 0 0;
        color: var(--products-muted);
        font-size: 13px;
        font-weight: 800;
    }

    .products-control-card {
        padding: 16px;
        margin-bottom: 22px;
    }

    .products-filter-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 220px;
        gap: 12px;
        align-items: center;
    }

    .search-box {
        display: grid;
        grid-template-columns: 42px minmax(0, 1fr);
        align-items: center;
        gap: 8px;
        padding: 7px;
        border-radius: 17px;
        background: var(--products-green-50);
        border: 1px solid var(--products-border);
    }

    .search-box span {
        width: 42px;
        height: 42px;
        display: grid;
        place-items: center;
        border-radius: 14px;
        background: #ffffff;
    }

    .search-box input,
    .products-filter-row select {
        width: 100%;
        border: 1px solid rgba(47, 125, 61, .18);
        border-radius: 14px;
        padding: 13px 14px;
        font: inherit;
        font-weight: 800;
        outline: none;
        background: #ffffff;
        color: var(--products-text);
        box-sizing: border-box;
    }

    .search-box input {
        border: 0;
    }

    .products-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 20px;
    }

    .producer-product-card {
        overflow: hidden;
        transition: transform .2s ease, box-shadow .2s ease, opacity .2s ease;
    }

    .producer-product-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 28px 68px rgba(31, 79, 43, .15);
    }

    .producer-product-card.is-hidden {
        display: none;
    }

    .producer-product-card.is-loading {
        opacity: .55;
        pointer-events: none;
        transform: scale(.98);
    }

    .product-image-link {
        position: relative;
        display: block;
        height: 220px;
        overflow: hidden;
        background: var(--products-green-100);
        text-decoration: none;
    }

    .product-image-link img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        transition: transform .35s ease;
    }

    .producer-product-card:hover .product-image-link img {
        transform: scale(1.04);
    }

    .product-image-placeholder {
        width: 100%;
        height: 100%;
        display: grid;
        place-items: center;
        align-content: center;
        gap: 8px;
        background:
            radial-gradient(circle at 30% 25%, rgba(255, 255, 255, .75), transparent 28%),
            linear-gradient(135deg, #e8f3e9, #f8fbf6);
        color: var(--products-green-700);
        font-weight: 900;
    }

    .product-image-placeholder span {
        font-size: 44px;
    }

    .product-image-badges {
        position: absolute;
        top: 13px;
        left: 13px;
        right: 13px;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .product-badge {
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

    .product-card-body {
        padding: 17px;
    }

    .product-title-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 14px;
        gap: 10px;
        align-items: start;
    }

    .product-title-row h2 {
        margin: 0;
        font-size: 22px;
        line-height: 1.18;
        letter-spacing: -.02em;
    }

    .product-title-row h2 a {
        color: var(--products-green-900);
        text-decoration: none;
    }

    .product-title-row h2 a:hover {
        color: var(--products-green-700);
    }

    .product-title-row p {
        margin: 6px 0 0;
        color: var(--products-muted);
        font-weight: 800;
    }

    .mini-status-dot {
        width: 13px;
        height: 13px;
        margin-top: 5px;
        border-radius: 999px;
        background: #c7d0c4;
    }

    .mini-status-dot.active {
        background: #2f7d3d;
    }

    .mini-status-dot.paused {
        background: #8a6200;
    }

    .mini-status-dot.sold_out {
        background: #9b111e;
    }

    .mini-status-dot.draft {
        background: #1f4e8c;
    }

    .product-price-row {
        display: flex;
        align-items: baseline;
        gap: 5px;
        margin: 15px 0 12px;
    }

    .product-price-row strong {
        color: var(--products-green-800);
        font-size: 26px;
        line-height: 1;
        letter-spacing: -.03em;
    }

    .product-price-row span {
        color: var(--products-muted);
        font-weight: 900;
    }

    .product-meta-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }

    .product-meta-grid div {
        padding: 12px;
        border-radius: 16px;
        background: #fbfdf8;
        border: 1px solid var(--products-border);
    }

    .product-meta-grid span,
    .product-meta-grid strong {
        display: block;
    }

    .product-meta-grid span {
        color: var(--products-muted);
        font-size: 12px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: 5px;
    }

    .product-meta-grid strong {
        color: var(--products-green-900);
        font-size: 14px;
    }

    .product-action-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-top: 15px;
    }

    .inline-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        align-items: center;
        justify-content: center;
        margin-top: 13px;
        padding-top: 13px;
        border-top: 1px solid rgba(47, 125, 61, .10);
    }

    .inline-actions form {
        margin: 0;
    }

    .text-action {
        border: 0;
        background: transparent;
        color: var(--products-green-700);
        font: inherit;
        font-weight: 900;
        cursor: pointer;
        padding: 0;
    }

    .text-action:hover {
        text-decoration: underline;
    }

    .text-action.danger {
        color: var(--products-red);
    }

    .producer-products-btn {
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

    .producer-products-btn:hover {
        transform: translateY(-2px);
    }

    .producer-products-btn.full {
        width: 100%;
    }

    .producer-products-btn-primary {
        background: linear-gradient(135deg, var(--products-green-700), var(--products-green-900));
        color: #ffffff;
        box-shadow: 0 16px 32px rgba(47, 125, 61, .24);
    }

    .producer-products-btn-light {
        background: var(--products-green-50);
        color: var(--products-green-800);
        border: 1px solid var(--products-border);
    }

    .products-empty-card {
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
        background: var(--products-green-100);
        font-size: 36px;
    }

    .products-empty-card h2 {
        margin-top: 18px;
        font-size: 34px;
    }

    .products-empty-card p {
        max-width: 520px;
        margin: 0 auto 22px;
    }

    .empty-actions {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 10px;
    }

    .products-no-result {
        margin-top: 18px;
        padding: 28px;
        border-radius: 24px;
        background: #ffffff;
        border: 1px dashed rgba(47, 125, 61, .24);
        text-align: center;
        color: var(--products-muted);
    }

    .products-no-result span {
        display: block;
        font-size: 34px;
        margin-bottom: 8px;
    }

    .products-no-result strong {
        display: block;
        color: var(--products-green-900);
        font-size: 20px;
        margin-bottom: 5px;
    }

    .products-no-result p {
        margin: 0;
    }

    .products-loading {
        pointer-events: none;
        opacity: .72;
    }

    @media (max-width: 1100px) {
        .producer-products-hero-content {
            grid-template-columns: 1fr;
        }

        .products-summary-grid,
        .products-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 760px) {
        .producer-products-hero {
            min-height: 430px;
            padding-top: 24px;
        }

        .producer-products-hero-inner,
        .producer-products-shell {
            width: min(100% - 22px, 1180px);
        }

        .producer-products-breadcrumb {
            font-size: 13px;
            margin-bottom: 24px;
        }

        .producer-products-hero-copy p {
            font-size: 15px;
        }

        .producer-products-shell {
            margin-top: -52px;
        }

        .products-top {
            flex-direction: column;
            align-items: flex-start;
        }

        .products-top .producer-products-btn {
            width: 100%;
        }

        .products-summary-grid,
        .products-grid,
        .products-filter-row {
            grid-template-columns: 1fr;
        }

        .products-top,
        .product-stat-card,
        .products-control-card,
        .producer-product-card,
        .producer-products-hero-card,
        .products-empty-card {
            border-radius: 23px;
        }

        .products-top,
        .product-stat-card,
        .products-control-card,
        .products-empty-card {
            padding: 14px;
        }

        .product-action-grid {
            grid-template-columns: 1fr;
        }

        .products-empty-card h2 {
            font-size: 28px;
        }

        .empty-actions .producer-products-btn {
            width: 100%;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const dynamicArea = document.getElementById('producer-products-dynamic-area');
        const heroStatsWrap = document.getElementById('producer-products-hero-stats-wrap');
        const messageBox = document.getElementById('producer-products-message');

        function showProductsMessage(type, message) {
            if (!messageBox) {
                return;
            }

            messageBox.hidden = false;
            messageBox.className = 'producer-products-message ' + type;
            messageBox.textContent = message || '';

            window.setTimeout(function () {
                messageBox.hidden = true;
                messageBox.textContent = '';
                messageBox.className = 'producer-products-message';
            }, 2600);
        }

        function applyProductFilters() {
            const searchInput = document.getElementById('producer-product-search');
            const statusFilter = document.getElementById('producer-product-status-filter');
            const cards = document.querySelectorAll('[data-product-card]');
            const noResult = document.getElementById('products-no-result');

            const searchValue = searchInput ? searchInput.value.trim().toLowerCase() : '';
            const statusValue = statusFilter ? statusFilter.value : '';

            let visibleCount = 0;

            cards.forEach(function (card) {
                const cardText = (card.getAttribute('data-search') || '').toLowerCase();
                const cardStatus = card.getAttribute('data-status') || '';

                const matchesSearch = searchValue === '' || cardText.includes(searchValue);
                const matchesStatus = statusValue === '' || cardStatus === statusValue;

                if (matchesSearch && matchesStatus) {
                    card.classList.remove('is-hidden');
                    visibleCount++;
                } else {
                    card.classList.add('is-hidden');
                }
            });

            if (noResult) {
                noResult.hidden = visibleCount !== 0;
            }
        }

        async function submitProductForm(form) {
            if (!form || form.dataset.loading === 'true') {
                return;
            }

            const confirmText = form.getAttribute('data-confirm');

            if (confirmText && !window.confirm(confirmText)) {
                return;
            }

            const card = form.closest('[data-product-card]');
            const buttons = form.querySelectorAll('button');

            form.dataset.loading = 'true';

            if (card) {
                card.classList.add('is-loading');
            }

            if (dynamicArea) {
                dynamicArea.classList.add('products-loading');
            }

            buttons.forEach(function (button) {
                button.disabled = true;
            });

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
                    throw new Error(result.message || 'Ürün işlemi tamamlanamadı.');
                }

                if (dynamicArea && typeof result.html === 'string') {
                    dynamicArea.innerHTML = result.html;
                }

                if (heroStatsWrap && typeof result.heroStatsHtml === 'string') {
                    heroStatsWrap.innerHTML = result.heroStatsHtml;
                }

                showProductsMessage('success', result.message || 'Ürün işlemi tamamlandı.');
                applyProductFilters();
            } catch (error) {
                showProductsMessage('error', error.message || 'Ürün işlemi sırasında bir hata oluştu.');

                if (card) {
                    card.classList.remove('is-loading');
                }
            } finally {
                if (dynamicArea) {
                    dynamicArea.classList.remove('products-loading');
                }

                buttons.forEach(function (button) {
                    button.disabled = false;
                });

                form.dataset.loading = 'false';
            }
        }

        document.addEventListener('submit', function (event) {
            const form = event.target.closest('form[data-product-ajax="true"]');

            if (!form) {
                return;
            }

            event.preventDefault();
            submitProductForm(form);
        });

        document.addEventListener('input', function (event) {
            if (event.target && event.target.id === 'producer-product-search') {
                applyProductFilters();
            }
        });

        document.addEventListener('change', function (event) {
            if (event.target && event.target.id === 'producer-product-status-filter') {
                applyProductFilters();
            }
        });
    });
</script>

<?php require APP_PATH . '/Views/layouts/footer.php'; ?>