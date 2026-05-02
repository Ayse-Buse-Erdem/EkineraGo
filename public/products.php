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

<main class="container">
    <section class="card page-heading">
        <h1>Ürünler</h1>

        <p>
            Üreticilerin aktif ürünlerini arayabilir, kategori/konum/fiyat filtresiyle listeleyebilir ve ürün detaylarını inceleyebilirsin.
        </p>
    </section>

    <section class="card filter-card">
        <form method="GET" action="<?= e(url('products.php')) ?>" class="product-filter-form">
            <div class="filter-grid">
                <div class="form-group">
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
                        <option value="newest" <?= product_public_selected(product_public_value($filters, 'sort', 'newest'), 'newest') ?>>En yeni</option>
                        <option value="price_asc" <?= product_public_selected(product_public_value($filters, 'sort'), 'price_asc') ?>>Fiyat artan</option>
                        <option value="price_desc" <?= product_public_selected(product_public_value($filters, 'sort'), 'price_desc') ?>>Fiyat azalan</option>
                        <option value="rating_desc" <?= product_public_selected(product_public_value($filters, 'sort'), 'rating_desc') ?>>En yüksek puan</option>
                        <option value="harvest_asc" <?= product_public_selected(product_public_value($filters, 'sort'), 'harvest_asc') ?>>Hasat tarihi yakın</option>
                    </select>
                </div>

                <div class="form-group checkbox-filters">
                    <label>
                        <input type="checkbox" name="in_stock" value="1" <?= product_public_checked($filters, 'in_stock') ?>>
                        Stokta olanlar
                    </label>

                    <label>
                        <input type="checkbox" name="preorder" value="1" <?= product_public_checked($filters, 'preorder') ?>>
                        Ön siparişe açık olanlar
                    </label>
                </div>
            </div>

            <div class="filter-actions">
                <button class="btn" type="submit">Filtrele</button>
                <a class="btn btn-secondary" href="<?= e(url('products.php')) ?>">Temizle</a>
            </div>
        </form>
    </section>

    <?php if (empty($products)): ?>
        <section class="card empty-state">
            Arama kriterlerine uygun aktif ürün bulunamadı.
        </section>
    <?php else: ?>
        <section class="products-grid">

            <?php foreach ($products as $product): ?>
                <?php
                    $productId = (int) ($product['id'] ?? 0);
                    $producerId = (int) ($product['producer_id'] ?? $product['user_id'] ?? 0);
                    $imagePath = product_public_image($product);
                    $unitLabel = product_public_unit_label($product['unit_type'] ?? 'kg');
                    $stockQuantity = (float) ($product['stock_quantity'] ?? 0);
                ?>

                <article class="card product-card">
                    <?= product_public_favorite_button($product) ?>

                    <?php if ($imagePath): ?>
                        <a class="product-image" href="<?= e(url('product-detail.php?id=' . $productId)) ?>">
                            <img src="<?= e(url($imagePath)) ?>" alt="<?= e($product['title'] ?? 'Ürün') ?>">
                        </a>
                    <?php else: ?>
                        <a class="product-image product-image-empty" href="<?= e(url('product-detail.php?id=' . $productId)) ?>">
                            <span>Ürün Görseli Yok</span>
                        </a>
                    <?php endif; ?>

                    <div class="product-card-body">
                        <div class="product-badges">
                            <?php if (!empty($product['category_name'])): ?>
                                <span class="badge"><?= e($product['category_name']) ?></span>
                            <?php endif; ?>

                            <?php if (!empty($product['is_preorder_enabled'])): ?>
                                <span class="badge preorder-badge">Ön Sipariş</span>
                            <?php endif; ?>

                            <?php if ($stockQuantity <= 0): ?>
                                <span class="badge soldout-badge">Stokta Yok</span>
                            <?php endif; ?>
                        </div>

                        <h2>
                            <a href="<?= e(url('product-detail.php?id=' . $productId)) ?>">
                                <?= e($product['title'] ?? 'Ürün') ?>
                            </a>
                        </h2>

                        <p class="producer-name">
                            <?= e(product_public_producer_name($product)) ?>
                        </p>

                        <p class="muted">
                            <?= e(product_public_location($product)) ?>
                        </p>

                        <p class="price-line">
                            <strong><?= e(product_public_money((float) ($product['price'] ?? 0))) ?></strong>
                            / <?= e($unitLabel) ?>
                        </p>

                        <p class="stock-line">
                            Stok:
                            <?= e(number_format($stockQuantity, 3, ',', '.')) ?>
                            <?= e($unitLabel) ?>
                        </p>

                        <?php if (!empty($product['harvest_date'])): ?>
                            <p class="muted">
                                Hasat: <?= e(date('d.m.Y', strtotime($product['harvest_date']))) ?>
                            </p>
                        <?php endif; ?>

                        <p class="muted">
                            ⭐ <?= e(number_format((float) ($product['average_rating'] ?? 0), 1, ',', '.')) ?>
                            / <?= (int) ($product['rating_count'] ?? 0) ?> yorum
                        </p>

                        <div class="card-actions">
                            <a class="btn" href="<?= e(url('product-detail.php?id=' . $productId)) ?>">
                                Ürünü Gör
                            </a>

                            <?php if ($producerId > 0): ?>
                                <a class="btn btn-secondary" href="<?= e(url('producer-detail.php?id=' . $producerId)) ?>">
                                    Üretici Profili
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</main>

<style>
    .page-heading {
        margin-bottom: 22px;
    }

    .page-heading h1,
    .filter-card h2,
    .product-card h2 {
        margin-top: 0;
        color: #245c2f;
    }

    .page-heading p {
        color: #526052;
        line-height: 1.5;
    }

    .filter-card {
        margin-bottom: 22px;
    }

    .product-filter-form label {
        display: block;
        margin-bottom: 7px;
        font-weight: bold;
        color: #245c2f;
    }

    .filter-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
    }

    .product-filter-form input,
    .product-filter-form select {
        width: 100%;
        padding: 11px;
        border: 1px solid #d5dccf;
        border-radius: 9px;
        font-family: Arial, sans-serif;
    }

    .checkbox-filters {
        display: flex;
        flex-direction: column;
        gap: 10px;
        justify-content: end;
    }

    .checkbox-filters label {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 0;
        font-weight: normal;
        color: #526052;
    }

    .checkbox-filters input {
        width: auto;
    }

    .filter-actions {
        margin-top: 18px;
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }

    .empty-state {
        color: #526052;
        line-height: 1.5;
    }

    .products-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 22px;
    }

    .product-card {
        position: relative;
        overflow: hidden;
        padding: 0;
    }

    .favorite-icon-form {
        position: absolute;
        top: 14px;
        right: 14px;
        z-index: 20;
        margin: 0;
    }

    .favorite-icon-btn {
        position: absolute;
        top: 14px;
        right: 14px;
        z-index: 20;
        width: 42px;
        height: 42px;
        border: 0;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.95);
        color: #2f7d3b;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        font-size: 25px;
        line-height: 42px;
        text-align: center;
        cursor: pointer;
        text-decoration: none;
        transition: transform 0.15s ease, background 0.15s ease, color 0.15s ease;
    }

    .favorite-icon-form .favorite-icon-btn {
        position: static;
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

    .favorite-toast {
        position: fixed;
        right: 22px;
        bottom: 22px;
        z-index: 9999;
        padding: 12px 16px;
        border-radius: 12px;
        background: #245c2f;
        color: #ffffff;
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.18);
        font-weight: bold;
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

    .product-image {
        display: block;
        height: 190px;
        background: #f8fbf6;
        color: #526052;
        text-decoration: none;
    }

    .product-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .product-image-empty {
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
    }

    .product-card-body {
        padding: 18px;
    }

    .product-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 12px;
    }

    .badge {
        display: inline-block;
        padding: 5px 9px;
        border-radius: 999px;
        background: #eef6e8;
        color: #245c2f;
        font-size: 13px;
        font-weight: bold;
    }

    .preorder-badge {
        background: #fff3cd;
        color: #7a5700;
    }

    .soldout-badge {
        background: #ffe8e8;
        color: #9b111e;
    }

    .product-card h2 {
        font-size: 20px;
        margin-bottom: 8px;
    }

    .product-card h2 a {
        color: inherit;
        text-decoration: none;
    }

    .producer-name {
        font-weight: bold;
        color: #245c2f;
        margin-bottom: 8px;
    }

    .muted,
    .stock-line {
        color: #526052;
        line-height: 1.5;
    }

    .price-line {
        color: #245c2f;
        font-size: 18px;
    }

    .card-actions {
        margin-top: 16px;
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    @media (max-width: 1100px) {
        .filter-grid,
        .products-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 768px) {
        .filter-grid,
        .products-grid {
            grid-template-columns: 1fr;
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

            // Normal form gönderiminde sayfaya geri dönmek için return_to var.
            // AJAX gönderiminde bunu kaldırıyoruz; böylece API JSON döner ve sayfa yenilenmez.
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

                showFavoriteToast(result.message || (isFavorited ? 'Ürün favorilere eklendi.' : 'Ürün favorilerden çıkarıldı.'), false);
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
