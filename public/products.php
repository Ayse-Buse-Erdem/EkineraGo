<?php

require_once __DIR__ . '/../app/bootstrap.php';

/*
 * Bu sayfa public ürün listeleme sayfasıdır.
 * Burada ProducerMiddleware kullanılmaz; tüketici ve misafir kullanıcılar da ürünleri görebilmelidir.
 */
$controller = new ProductController();
$data = $controller->publicIndexData($_GET);

$products = $data['products'] ?? [];
$categories = $data['categories'] ?? [];
$filters = $data['filters'] ?? $_GET;

/*
 * Controller kategorileri/lokasyonları dönmezse sayfa yine çalışsın diye güvenli fallback.
 */
if (empty($categories)) {
    try {
        $categories = db()->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name ASC")->fetchAll();
    } catch (Throwable $e) {
        $categories = [];
    }
}

try {
    $provinces = db()->query("SELECT id, name FROM provinces ORDER BY name ASC")->fetchAll();
} catch (Throwable $e) {
    $provinces = [];
}

try {
    $districts = db()->query("SELECT id, province_id, name FROM districts ORDER BY name ASC")->fetchAll();
} catch (Throwable $e) {
    $districts = [];
}

$pageTitle = 'Ürünler';
$bodyClass = 'page-products';

require APP_PATH . '/Views/layouts/header.php';

if (!function_exists('product_public_value')) {
    function product_public_value(array $filters, string $key, mixed $default = ''): mixed
    {
        return $filters[$key] ?? $default;
    }
}

if (!function_exists('product_public_selected')) {
    function product_public_selected(mixed $current, mixed $expected): string
    {
        return (string) $current === (string) $expected ? 'selected' : '';
    }
}

if (!function_exists('product_public_checked')) {
    function product_public_checked(array $filters, string $key): string
    {
        return !empty($filters[$key]) ? 'checked' : '';
    }
}

if (!function_exists('product_public_unit_label')) {
    function product_public_unit_label(?string $unit): string
    {
        return match ($unit) {
            'kg' => 'kg',
            'g' => 'g',
            'piece' => 'adet',
            'bunch' => 'demet',
            'box' => 'kasa',
            default => $unit ?: 'kg',
        };
    }
}

if (!function_exists('product_public_money')) {
    function product_public_money(float $amount): string
    {
        return function_exists('formatMoney')
            ? formatMoney($amount)
            : number_format($amount, 2, ',', '.') . ' TL';
    }
}

if (!function_exists('product_public_location')) {
    function product_public_location(array $product): string
    {
        $province = $product['province_name'] ?? '';
        $district = $product['district_name'] ?? '';

        if ($province && $district) {
            return $province . ' / ' . $district;
        }

        return $province ?: ($district ?: 'Konum bilgisi yok');
    }
}

if (!function_exists('product_public_image')) {
    function product_public_image(array $product): ?string
    {
        return $product['cover_image']
            ?? $product['image_path']
            ?? $product['product_image']
            ?? null;
    }
}

if (!function_exists('product_public_producer_name')) {
    function product_public_producer_name(array $product): string
    {
        return $product['store_name']
            ?? $product['producer_name']
            ?? $product['full_name']
            ?? 'Üretici';
    }
}

if (!function_exists('product_public_current_return_url')) {
    function product_public_current_return_url(): string
    {
        $query = $_SERVER['QUERY_STRING'] ?? '';

        return 'products.php' . ($query !== '' ? '?' . $query : '');
    }
}

if (!function_exists('product_public_favorite_button')) {
    function product_public_favorite_button(array $product): string
    {
        $productId = (int) ($product['id'] ?? 0);

        if ($productId <= 0) {
            return '';
        }

        $isFavorited = !empty($product['is_favorited']);
        $returnTo = product_public_current_return_url();

        if (!isLoggedIn()) {
            return '
                <a class="favorite-icon-btn"
                   href="' . e(url('login.php')) . '"
                   title="Favoriye eklemek için giriş yap"
                   aria-label="Favoriye eklemek için giriş yap">
                    ♡
                </a>
            ';
        }

        if (!isConsumer()) {
            return '';
        }

        return '
            <form method="POST"
                  action="' . e(url('api/favorite-toggle.php')) . '"
                  class="favorite-icon-form"
                  data-favorite-form="1">
                ' . csrf_field() . '
                <input type="hidden" name="product_id" value="' . e((string) $productId) . '">
                <input type="hidden" name="return_to" value="' . e($returnTo) . '">
                <button type="submit"
                        class="favorite-icon-btn ' . ($isFavorited ? 'is-active' : '') . '"
                        data-favorite-button="1"
                        data-favorited="' . ($isFavorited ? '1' : '0') . '"
                        title="' . ($isFavorited ? 'Favorilerden çıkar' : 'Favorilere ekle') . '"
                        aria-label="' . ($isFavorited ? 'Favorilerden çıkar' : 'Favorilere ekle') . '">
                    ' . ($isFavorited ? '♥' : '♡') . '
                </button>
            </form>
        ';
    }
}

?>

<main class="products-page">

    <section class="products-hero">
        <div class="products-hero-inner">
            <div class="products-hero-content">
                <span class="products-mini-title">EkineraGo Pazarı</span>

                <h1>Taze ürünleri doğrudan üreticiden keşfet</h1>

                <p>
                    Yerel üreticilerin aktif ürünlerini incele, kategoriye ve konuma göre filtrele,
                    sana en yakın taze ürünlere hızlıca ulaş.
                </p>

                <div class="hero-search-card">
                    <form method="GET" action="<?= e(url('products.php')) ?>" class="hero-search-form">
                        <input
                            type="text"
                            name="q"
                            value="<?= e((string) product_public_value($filters, 'q')) ?>"
                            placeholder="Domates, elma, bal, patates..."
                        >

                        <button type="submit">
                            Ürün Ara
                        </button>
                    </form>
                </div>
            </div>

            <div class="products-hero-visual">
                <div class="hero-visual-card">
                    <span class="hero-visual-icon">🥬</span>
                    <strong><?= count($products) ?></strong>
                    <small>Aktif ürün listeleniyor</small>
                </div>

                <div class="hero-floating-card card-one">
                    <span>🚜</span>
                    Doğrudan üretici
                </div>

                <div class="hero-floating-card card-two">
                    <span>🌿</span>
                    Taze kaynak
                </div>
            </div>
        </div>
    </section>

    <section class="products-content">

        <aside class="products-filter-panel">
            <div class="filter-title">
                <span>🔎</span>
                <div>
                    <h2>Filtrele</h2>
                    <p>Aradığın ürünü daha hızlı bul.</p>
                </div>
            </div>

            <form method="GET" action="<?= e(url('products.php')) ?>" class="product-filter-form">
                <div class="filter-grid">

                    <div class="form-group filter-wide">
                        <label for="q">Ürün Ara</label>

                        <input
                            type="text"
                            id="q"
                            name="q"
                            value="<?= e((string) product_public_value($filters, 'q')) ?>"
                            placeholder="Domates, elma, bal..."
                        >
                    </div>

                    <div class="form-group">
                        <label for="category_id">Kategori</label>

                        <select id="category_id" name="category_id">
                            <option value="">Tüm kategoriler</option>

                            <?php foreach ($categories as $category): ?>
                                <option
                                    value="<?= e((string) $category['id']) ?>"
                                    <?= product_public_selected(product_public_value($filters, 'category_id'), $category['id']) ?>
                                >
                                    <?= e($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="province_id">İl</label>

                        <select id="province_id" name="province_id">
                            <option value="">Tüm iller</option>

                            <?php foreach ($provinces as $province): ?>
                                <option
                                    value="<?= e((string) $province['id']) ?>"
                                    <?= product_public_selected(product_public_value($filters, 'province_id'), $province['id']) ?>
                                >
                                    <?= e($province['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="district_id">İlçe</label>

                        <select id="district_id" name="district_id">
                            <option value="">Tüm ilçeler</option>

                            <?php foreach ($districts as $district): ?>
                                <option
                                    value="<?= e((string) $district['id']) ?>"
                                    data-province-id="<?= e((string) ($district['province_id'] ?? 0)) ?>"
                                    <?= product_public_selected(product_public_value($filters, 'district_id'), $district['id']) ?>
                                >
                                    <?= e($district['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="min_price">Min Fiyat</label>

                        <input
                            type="number"
                            id="min_price"
                            name="min_price"
                            step="0.01"
                            min="0"
                            value="<?= e((string) product_public_value($filters, 'min_price')) ?>"
                            placeholder="0"
                        >
                    </div>

                    <div class="form-group">
                        <label for="max_price">Max Fiyat</label>

                        <input
                            type="number"
                            id="max_price"
                            name="max_price"
                            step="0.01"
                            min="0"
                            value="<?= e((string) product_public_value($filters, 'max_price')) ?>"
                            placeholder="100"
                        >
                    </div>

                    <div class="form-group">
                        <label for="sort">Sıralama</label>

                        <select id="sort" name="sort">
                            <option value="newest" <?= product_public_selected(product_public_value($filters, 'sort', 'newest'), 'newest') ?>>
                                En yeni
                            </option>

                            <option value="price_asc" <?= product_public_selected(product_public_value($filters, 'sort'), 'price_asc') ?>>
                                Fiyat artan
                            </option>

                            <option value="price_desc" <?= product_public_selected(product_public_value($filters, 'sort'), 'price_desc') ?>>
                                Fiyat azalan
                            </option>

                            <option value="rating_desc" <?= product_public_selected(product_public_value($filters, 'sort'), 'rating_desc') ?>>
                                En yüksek puan
                            </option>

                            <option value="harvest_asc" <?= product_public_selected(product_public_value($filters, 'sort'), 'harvest_asc') ?>>
                                Hasat tarihi yakın
                            </option>
                        </select>
                    </div>

                    <div class="checkbox-filters filter-wide">
                        <label>
                            <input
                                type="checkbox"
                                name="in_stock"
                                value="1"
                                <?= product_public_checked($filters, 'in_stock') ?>
                            >
                            <span>Stokta olanlar</span>
                        </label>

                        <label>
                            <input
                                type="checkbox"
                                name="preorder"
                                value="1"
                                <?= product_public_checked($filters, 'preorder') ?>
                            >
                            <span>Ön siparişe açık olanlar</span>
                        </label>
                    </div>

                </div>

                <div class="filter-actions">
                    <button class="filter-submit" type="submit">
                        Filtrele
                    </button>

                    <a class="filter-clear" href="<?= e(url('products.php')) ?>">
                        Temizle
                    </a>
                </div>
            </form>
        </aside>

        <section class="products-results">

            <div class="results-header">
                <div>
                    <span class="results-label">Ürün Listesi</span>
                    <h2><?= count($products) ?> ürün bulundu</h2>
                </div>

                <a class="results-all-link" href="<?= e(url('producers.php')) ?>">
                    Üreticileri Gör
                </a>
            </div>

            <?php if (empty($products)): ?>
                <div class="empty-state">
                    <div class="empty-icon">🧺</div>

                    <h3>Ürün bulunamadı</h3>

                    <p>
                        Arama kriterlerine uygun aktif ürün bulunamadı.
                        Filtreleri temizleyerek tekrar deneyebilirsin.
                    </p>

                    <a href="<?= e(url('products.php')) ?>">
                        Filtreleri Temizle
                    </a>
                </div>
            <?php else: ?>

                <div class="products-grid">
                    <?php foreach ($products as $product): ?>
                        <?php
                            $productId = (int) ($product['id'] ?? 0);
                            $producerId = (int) ($product['producer_id'] ?? $product['user_id'] ?? 0);
                            $imagePath = product_public_image($product);
                            $unitLabel = product_public_unit_label($product['unit_type'] ?? 'kg');
                            $stockQuantity = (float) ($product['stock_quantity'] ?? 0);
                            $price = (float) ($product['price'] ?? 0);
                            $averageRating = (float) ($product['average_rating'] ?? 0);
                            $ratingCount = (int) ($product['rating_count'] ?? 0);
                        ?>

                        <article class="product-card">
                            <?= product_public_favorite_button($product) ?>

                            <a class="product-image" href="<?= e(url('product-detail.php?id=' . $productId)) ?>">
                                <?php if ($imagePath): ?>
                                    <img
                                        src="<?= e(url($imagePath)) ?>"
                                        alt="<?= e($product['title'] ?? 'Ürün') ?>"
                                    >
                                <?php else: ?>
                                    <span class="product-image-placeholder">
                                        🥬
                                        <small>Ürün görseli yok</small>
                                    </span>
                                <?php endif; ?>

                                <?php if ($stockQuantity <= 0): ?>
                                    <span class="image-status soldout">
                                        Stokta Yok
                                    </span>
                                <?php elseif (!empty($product['is_preorder_enabled'])): ?>
                                    <span class="image-status preorder">
                                        Ön Sipariş
                                    </span>
                                <?php endif; ?>
                            </a>

                            <div class="product-card-body">

                                <div class="product-badges">
                                    <?php if (!empty($product['category_name'])): ?>
                                        <span class="product-badge">
                                            <?= e($product['category_name']) ?>
                                        </span>
                                    <?php endif; ?>

                                    <span class="product-badge soft">
                                        <?= e(product_public_location($product)) ?>
                                    </span>
                                </div>

                                <h3>
                                    <a href="<?= e(url('product-detail.php?id=' . $productId)) ?>">
                                        <?= e($product['title'] ?? 'Ürün') ?>
                                    </a>
                                </h3>

                                <div class="producer-line">
                                    <span>🚜</span>
                                    <strong><?= e(product_public_producer_name($product)) ?></strong>
                                </div>

                                <div class="product-meta">
                                    <?php if (!empty($product['harvest_date'])): ?>
                                        <span>
                                            🌿 Hasat:
                                            <?= e(date('d.m.Y', strtotime($product['harvest_date']))) ?>
                                        </span>
                                    <?php endif; ?>

                                    <span>
                                        ⭐ <?= e(number_format($averageRating, 1, ',', '.')) ?>
                                        / <?= e((string) $ratingCount) ?> yorum
                                    </span>
                                </div>

                                <div class="price-stock-row">
                                    <div class="price-box">
                                        <strong><?= e(product_public_money($price)) ?></strong>
                                        <span>/ <?= e($unitLabel) ?></span>
                                    </div>

                                    <div class="stock-box">
                                        <small>Stok</small>
                                        <strong>
                                            <?= e(number_format($stockQuantity, 3, ',', '.')) ?>
                                            <?= e($unitLabel) ?>
                                        </strong>
                                    </div>
                                </div>

                                <div class="card-actions">
                                    <a class="product-primary-btn" href="<?= e(url('product-detail.php?id=' . $productId)) ?>">
                                        Ürünü Gör
                                    </a>

                                    <?php if ($producerId > 0): ?>
                                        <a class="product-secondary-btn" href="<?= e(url('producer-detail.php?id=' . $producerId)) ?>">
                                            Üretici
                                        </a>
                                    <?php endif; ?>
                                </div>

                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

            <?php endif; ?>
        </section>

    </section>
</main>

<style>
    .products-page {
        min-height: calc(100vh - 74px);
        background:
            radial-gradient(circle at top left, rgba(176, 221, 166, 0.36), transparent 34%),
            radial-gradient(circle at bottom right, rgba(255, 221, 156, 0.22), transparent 36%),
            linear-gradient(135deg, #f8fcf5 0%, #eef8ec 100%);
        padding-bottom: 72px;
    }

    .products-hero {
        padding: 58px 24px 38px;
    }

    .products-hero-inner {
        width: min(100%, 1180px);
        margin: 0 auto;
        display: grid;
        grid-template-columns: 1.2fr 0.8fr;
        gap: 36px;
        align-items: center;
    }

    .products-mini-title {
        display: inline-flex;
        align-items: center;
        padding: 8px 16px;
        margin-bottom: 16px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.82);
        border: 1px solid #d7ead2;
        color: #2f7d3d;
        font-weight: 900;
        letter-spacing: 0.05em;
    }

    .products-hero-content h1 {
        margin: 0;
        max-width: 760px;
        color: #245c2f;
        font-size: clamp(38px, 5vw, 68px);
        line-height: 1.02;
        letter-spacing: -0.06em;
    }

    .products-hero-content p {
        max-width: 720px;
        margin: 18px 0 0;
        color: #667366;
        font-size: 18px;
        line-height: 1.75;
    }

    .hero-search-card {
        max-width: 670px;
        margin-top: 28px;
        padding: 10px;
        border-radius: 24px;
        background: rgba(255, 255, 255, 0.82);
        border: 1px solid rgba(215, 234, 210, 0.95);
        box-shadow: 0 18px 45px rgba(36, 92, 47, 0.10);
    }

    .hero-search-form {
        display: flex;
        gap: 10px;
    }

    .hero-search-form input {
        height: 52px;
        border-radius: 17px;
        background: #ffffff;
    }

    .hero-search-form button {
        flex: 0 0 auto;
        min-width: 132px;
        border: none;
        border-radius: 17px;
        background: #2f7d3d;
        color: #ffffff;
        font-weight: 900;
        cursor: pointer;
        box-shadow: 0 14px 28px rgba(47, 125, 61, 0.18);
        transition: 0.2s ease;
    }

    .hero-search-form button:hover {
        background: #245c2f;
        transform: translateY(-1px);
    }

    .products-hero-visual {
        position: relative;
        min-height: 260px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .hero-visual-card {
        width: 250px;
        height: 250px;
        border-radius: 42px;
        background:
            radial-gradient(circle at top left, rgba(255, 255, 255, 0.9), transparent 36%),
            linear-gradient(135deg, #e8f3e9 0%, #bfe4bb 100%);
        border: 1px solid rgba(215, 234, 210, 0.95);
        box-shadow: 0 26px 70px rgba(36, 92, 47, 0.14);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .hero-visual-icon {
        width: 70px;
        height: 70px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 24px;
        background: rgba(255, 255, 255, 0.85);
        font-size: 36px;
        margin-bottom: 16px;
    }

    .hero-visual-card strong {
        color: #245c2f;
        font-size: 54px;
        line-height: 1;
    }

    .hero-visual-card small {
        margin-top: 8px;
        color: #526052;
        font-weight: 800;
    }

    .hero-floating-card {
        position: absolute;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 16px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.9);
        border: 1px solid #dcebd8;
        box-shadow: 0 14px 32px rgba(36, 92, 47, 0.12);
        color: #245c2f;
        font-weight: 900;
        animation: productFloat 4s ease-in-out infinite;
    }

    .card-one {
        left: 8px;
        top: 26px;
    }

    .card-two {
        right: 6px;
        bottom: 34px;
        animation-delay: 1.2s;
    }

    .products-content {
        width: min(100%, 1180px);
        margin: 0 auto;
        padding: 0 24px;
        display: grid;
        grid-template-columns: 320px minmax(0, 1fr);
        gap: 24px;
        align-items: start;
    }

    .products-filter-panel {
        position: sticky;
        top: 96px;
        border-radius: 30px;
        padding: 22px;
        background: rgba(255, 255, 255, 0.92);
        border: 1px solid #dfeedd;
        box-shadow: 0 18px 50px rgba(36, 92, 47, 0.10);
        backdrop-filter: blur(14px);
    }

    .filter-title {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 18px;
    }

    .filter-title > span {
        width: 46px;
        height: 46px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 16px;
        background: #e8f3e9;
        font-size: 22px;
    }

    .filter-title h2 {
        margin: 0;
        color: #245c2f;
        font-size: 25px;
        letter-spacing: -0.04em;
    }

    .filter-title p {
        margin: 3px 0 0;
        color: #6d7b6d;
        font-size: 13px;
        font-weight: 700;
    }

    .filter-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 14px;
    }

    .product-filter-form label {
        display: block;
        margin-bottom: 7px;
        color: #28452d;
        font-weight: 900;
        font-size: 14px;
    }

    .product-filter-form input,
    .product-filter-form select {
        min-height: 46px;
        border-radius: 15px;
        background: #ffffff;
        border-color: #dcebd8;
    }

    .checkbox-filters {
        display: grid;
        gap: 10px;
        margin-top: 2px;
    }

    .checkbox-filters label {
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px;
        border-radius: 16px;
        background: #f6fbf4;
        border: 1px solid #e1efdd;
        color: #526052;
        font-weight: 800;
        cursor: pointer;
    }

    .checkbox-filters input {
        width: 18px;
        height: 18px;
        flex: 0 0 auto;
        accent-color: #2f7d3d;
    }

    .filter-actions {
        margin-top: 18px;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }

    .filter-submit,
    .filter-clear {
        min-height: 46px;
        border-radius: 15px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        font-weight: 900;
        border: none;
        cursor: pointer;
        transition: 0.2s ease;
    }

    .filter-submit {
        background: #2f7d3d;
        color: #ffffff;
        box-shadow: 0 14px 28px rgba(47, 125, 61, 0.18);
    }

    .filter-clear {
        background: #e8f3e9;
        color: #2f7d3d;
    }

    .filter-submit:hover,
    .filter-clear:hover {
        transform: translateY(-1px);
    }

    .filter-submit:hover {
        background: #245c2f;
    }

    .filter-clear:hover {
        background: #d8ebdb;
    }

    .products-results {
        min-width: 0;
    }

    .results-header {
        margin-bottom: 18px;
        padding: 20px 22px;
        border-radius: 26px;
        background: rgba(255, 255, 255, 0.86);
        border: 1px solid #dfeedd;
        box-shadow: 0 14px 36px rgba(36, 92, 47, 0.08);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
    }

    .results-label {
        display: block;
        margin-bottom: 4px;
        color: #2f7d3d;
        font-size: 13px;
        font-weight: 900;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }

    .results-header h2 {
        margin: 0;
        color: #245c2f;
        font-size: 26px;
        letter-spacing: -0.04em;
    }

    .results-all-link {
        flex: 0 0 auto;
        min-height: 42px;
        padding: 0 16px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        background: #e8f3e9;
        color: #2f7d3d;
        text-decoration: none;
        font-weight: 900;
        transition: 0.2s ease;
    }

    .results-all-link:hover {
        background: #d8ebdb;
        transform: translateY(-1px);
    }

    .products-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 22px;
    }

    .product-card {
        position: relative;
        overflow: hidden;
        border-radius: 28px;
        background: rgba(255, 255, 255, 0.94);
        border: 1px solid #dfeedd;
        box-shadow: 0 18px 50px rgba(36, 92, 47, 0.10);
        transition: transform 0.22s ease, box-shadow 0.22s ease;
    }

    .product-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 24px 66px rgba(36, 92, 47, 0.15);
    }

    .product-image {
        position: relative;
        display: block;
        height: 220px;
        overflow: hidden;
        background:
            radial-gradient(circle at top left, rgba(176, 221, 166, 0.38), transparent 40%),
            #f6fbf4;
        color: #526052;
        text-decoration: none;
    }

    .product-image img {
        width: 100%;
        height: 100%;
        display: block;
        object-fit: cover;
        transition: transform 0.35s ease;
    }

    .product-card:hover .product-image img {
        transform: scale(1.06);
    }

    .product-image-placeholder {
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        gap: 8px;
        font-size: 46px;
        color: #2f7d3d;
        font-weight: 900;
    }

    .product-image-placeholder small {
        font-size: 14px;
        color: #6d7b6d;
    }

    .image-status {
        position: absolute;
        left: 16px;
        bottom: 16px;
        padding: 8px 12px;
        border-radius: 999px;
        font-size: 13px;
        font-weight: 900;
        box-shadow: 0 10px 24px rgba(20, 46, 24, 0.13);
    }

    .image-status.preorder {
        background: rgba(255, 245, 214, 0.96);
        color: #7a5700;
    }

    .image-status.soldout {
        background: rgba(255, 232, 232, 0.96);
        color: #9b111e;
    }

    .favorite-icon-form,
    .product-card > .favorite-icon-btn {
        position: absolute;
        top: 16px;
        right: 16px;
        z-index: 20;
        margin: 0;
    }

    .favorite-icon-btn {
        width: 44px;
        height: 44px;
        border: none;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.94);
        color: #2f7d3d;
        box-shadow: 0 10px 24px rgba(20, 46, 24, 0.14);
        font-size: 27px;
        line-height: 44px;
        text-align: center;
        cursor: pointer;
        text-decoration: none;
        transition: transform 0.16s ease, background 0.16s ease, color 0.16s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .favorite-icon-btn:hover {
        transform: scale(1.08);
        background: #fff4f4;
        color: #d62828;
    }

    .favorite-icon-btn.is-active {
        background: #fff0f0;
        color: #d62828;
    }

    .favorite-icon-btn.is-active:hover {
        background: #ffe2e2;
    }

    .favorite-icon-btn.is-loading {
        opacity: 0.65;
        pointer-events: none;
    }

    .product-card-body {
        padding: 20px;
    }

    .product-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 13px;
    }

    .product-badge {
        display: inline-flex;
        align-items: center;
        min-height: 28px;
        padding: 0 10px;
        border-radius: 999px;
        background: #e8f3e9;
        color: #245c2f;
        font-size: 12px;
        font-weight: 900;
    }

    .product-badge.soft {
        background: #f4f9f2;
        color: #526052;
    }

    .product-card h3 {
        margin: 0 0 11px;
        color: #245c2f;
        font-size: 23px;
        line-height: 1.2;
        letter-spacing: -0.035em;
    }

    .product-card h3 a {
        color: inherit;
        text-decoration: none;
    }

    .producer-line {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #526052;
        font-size: 14px;
        margin-bottom: 12px;
    }

    .producer-line strong {
        color: #245c2f;
    }

    .product-meta {
        display: grid;
        gap: 6px;
        color: #6d7b6d;
        font-size: 13px;
        line-height: 1.5;
        margin-bottom: 16px;
    }

    .price-stock-row {
        display: grid;
        grid-template-columns: 1.1fr 0.9fr;
        gap: 12px;
        margin-top: 12px;
    }

    .price-box,
    .stock-box {
        padding: 14px;
        border-radius: 20px;
        background: #f6fbf4;
        border: 1px solid #e1efdd;
    }

    .price-box strong {
        display: block;
        color: #245c2f;
        font-size: 22px;
        line-height: 1.1;
    }

    .price-box span,
    .stock-box small {
        color: #6d7b6d;
        font-size: 13px;
        font-weight: 800;
    }

    .stock-box strong {
        display: block;
        margin-top: 5px;
        color: #245c2f;
        font-size: 14px;
    }

    .card-actions {
        margin-top: 16px;
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 10px;
    }

    .product-primary-btn,
    .product-secondary-btn {
        min-height: 45px;
        padding: 0 16px;
        border-radius: 15px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        font-weight: 900;
        transition: 0.2s ease;
    }

    .product-primary-btn {
        background: #2f7d3d;
        color: #ffffff;
        box-shadow: 0 14px 28px rgba(47, 125, 61, 0.18);
    }

    .product-secondary-btn {
        background: #e8f3e9;
        color: #2f7d3d;
    }

    .product-primary-btn:hover,
    .product-secondary-btn:hover {
        transform: translateY(-1px);
    }

    .product-primary-btn:hover {
        background: #245c2f;
    }

    .product-secondary-btn:hover {
        background: #d8ebdb;
    }

    .empty-state {
        min-height: 360px;
        border-radius: 30px;
        padding: 42px 24px;
        background: rgba(255, 255, 255, 0.9);
        border: 1px solid #dfeedd;
        box-shadow: 0 18px 50px rgba(36, 92, 47, 0.10);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        text-align: center;
    }

    .empty-icon {
        width: 76px;
        height: 76px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 26px;
        background: #e8f3e9;
        font-size: 38px;
        margin-bottom: 18px;
    }

    .empty-state h3 {
        margin: 0;
        color: #245c2f;
        font-size: 30px;
        letter-spacing: -0.04em;
    }

    .empty-state p {
        max-width: 460px;
        margin: 12px auto 20px;
        color: #667366;
        line-height: 1.65;
    }

    .empty-state a {
        min-height: 44px;
        padding: 0 18px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #2f7d3d;
        color: #ffffff;
        text-decoration: none;
        font-weight: 900;
    }

    .favorite-toast {
        position: fixed;
        right: 22px;
        bottom: 22px;
        z-index: 9999;
        padding: 13px 17px;
        border-radius: 16px;
        background: #245c2f;
        color: #ffffff;
        box-shadow: 0 16px 40px rgba(0, 0, 0, 0.18);
        font-weight: 900;
        opacity: 0;
        transform: translateY(10px);
        transition: opacity 0.2s ease, transform 0.2s ease;
    }

    .favorite-toast.is-visible {
        opacity: 1;
        transform: translateY(0);
    }

    .favorite-toast.is-error {
        background: #9b111e;
    }

    @keyframes productFloat {
        0%, 100% {
            transform: translateY(0);
        }

        50% {
            transform: translateY(-8px);
        }
    }

    @media (max-width: 1080px) {
        .products-hero-inner {
            grid-template-columns: 1fr;
        }

        .products-hero-visual {
            display: none;
        }

        .products-content {
            grid-template-columns: 1fr;
        }

        .products-filter-panel {
            position: static;
        }

        .filter-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .filter-wide {
            grid-column: span 2;
        }
    }

    @media (max-width: 780px) {
        .products-hero {
            padding: 42px 16px 28px;
        }

        .products-content {
            padding: 0 16px;
        }

        .products-hero-content {
            text-align: center;
        }

        .products-hero-content p {
            margin-left: auto;
            margin-right: auto;
        }

        .hero-search-card {
            margin-left: auto;
            margin-right: auto;
        }

        .hero-search-form {
            flex-direction: column;
        }

        .hero-search-form button {
            min-height: 48px;
        }

        .filter-grid,
        .products-grid {
            grid-template-columns: 1fr;
        }

        .filter-wide {
            grid-column: auto;
        }

        .results-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .results-all-link {
            width: 100%;
        }
    }

    @media (max-width: 520px) {
        .products-hero-content h1 {
            font-size: 38px;
        }

        .products-hero-content p {
            font-size: 16px;
        }

        .products-filter-panel,
        .product-card,
        .empty-state {
            border-radius: 24px;
        }

        .price-stock-row,
        .card-actions {
            grid-template-columns: 1fr;
        }

        .product-image {
            height: 190px;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const provinceSelect = document.getElementById('province_id');
        const districtSelect = document.getElementById('district_id');

        if (provinceSelect && districtSelect) {
            const districtOptions = Array.from(districtSelect.options);

            function filterDistricts() {
                const provinceId = provinceSelect.value;

                districtOptions.forEach(function (option) {
                    if (!option.value) {
                        option.hidden = false;
                        return;
                    }

                    const optionProvinceId = option.getAttribute('data-province-id');
                    option.hidden = provinceId !== '' && optionProvinceId !== provinceId;
                });

                const selectedOption = districtSelect.options[districtSelect.selectedIndex];

                if (selectedOption && selectedOption.hidden) {
                    districtSelect.value = '';
                }
            }

            provinceSelect.addEventListener('change', filterDistricts);
            filterDistricts();
        }

        const favoriteForms = document.querySelectorAll('[data-favorite-form="1"]');
        let toastTimer = null;

        function showFavoriteToast(message, isError) {
            let toast = document.querySelector('.favorite-toast');

            if (!toast) {
                toast = document.createElement('div');
                toast.className = 'favorite-toast';
                document.body.appendChild(toast);
            }

            toast.textContent = message;
            toast.classList.toggle('is-error', Boolean(isError));
            toast.classList.add('is-visible');

            if (toastTimer) {
                clearTimeout(toastTimer);
            }

            toastTimer = setTimeout(function () {
                toast.classList.remove('is-visible');
            }, 1800);
        }

        favoriteForms.forEach(function (form) {
            form.addEventListener('submit', async function (event) {
                event.preventDefault();

                const button = form.querySelector('[data-favorite-button="1"]');

                if (!button || button.disabled) {
                    return;
                }

                const formData = new FormData(form);

                formData.delete('return_to');

                button.disabled = true;
                button.classList.add('is-loading');

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        credentials: 'same-origin'
                    });

                    const result = await response.json();

                    if (!response.ok || !result.success) {
                        throw new Error(result.message || 'Favori işlemi başarısız oldu.');
                    }

                    const isFavorited = Boolean(result.data && result.data.is_favorited);

                    button.classList.toggle('is-active', isFavorited);
                    button.dataset.favorited = isFavorited ? '1' : '0';
                    button.textContent = isFavorited ? '♥' : '♡';

                    const label = isFavorited ? 'Favorilerden çıkar' : 'Favorilere ekle';

                    button.setAttribute('title', label);
                    button.setAttribute('aria-label', label);

                    showFavoriteToast(
                        result.message || (isFavorited ? 'Ürün favorilere eklendi.' : 'Ürün favorilerden çıkarıldı.'),
                        false
                    );
                } catch (error) {
                    showFavoriteToast(error.message || 'Favori işlemi sırasında bir hata oluştu.', true);
                } finally {
                    button.disabled = false;
                    button.classList.remove('is-loading');
                }
            });
        });
    });
</script>

<?php require APP_PATH . '/Views/layouts/footer.php'; ?>