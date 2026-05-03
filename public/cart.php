<?php

require_once __DIR__ . '/../app/bootstrap.php';

ConsumerMiddleware::handle();

$userId = (int) currentUserId();
$cartService = new CartService();

if (!function_exists('cart_unit_label')) {
    function cart_unit_label(string $unit): string
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

if (!function_exists('cart_format_money')) {
    function cart_format_money(float $amount): string
    {
        if (function_exists('formatMoney')) {
            return formatMoney($amount);
        }

        return number_format($amount, 2, ',', '.') . ' TL';
    }
}

if (!function_exists('cart_number')) {
    function cart_number(float $number): string
    {
        return rtrim(rtrim(number_format($number, 2, ',', '.'), '0'), ',');
    }
}

if (!function_exists('cart_build_producer_groups')) {
    function cart_build_producer_groups(array $cart): array
    {
        $items = $cart['items'] ?? [];
        $producerGroups = $cart['producer_groups'] ?? [];

        if (!empty($producerGroups) || empty($items)) {
            return $producerGroups;
        }

        $groups = [];

        foreach ($items as $item) {
            $producerId = (int) ($item['producer_id'] ?? 0);
            $producerName = $item['producer_name'] ?? 'Üretici';

            if (!isset($groups[$producerId])) {
                $groups[$producerId] = [
                    'producer_id' => $producerId,
                    'producer_name' => $producerName,
                    'subtotal' => 0,
                    'items' => [],
                ];
            }

            $quantity = (float) ($item['quantity'] ?? 0);
            $price = (float) ($item['price'] ?? 0);

            $groups[$producerId]['subtotal'] += $quantity * $price;
            $groups[$producerId]['items'][] = $item;
        }

        return $groups;
    }
}

if (!function_exists('cart_total_quantity')) {
    function cart_total_quantity(array $items): float
    {
        $totalQuantity = 0;

        foreach ($items as $item) {
            $totalQuantity += (float) ($item['quantity'] ?? 0);
        }

        return $totalQuantity;
    }
}

if (!function_exists('cart_render_hero_stats')) {
    function cart_render_hero_stats(array $items, array $producerGroups, float $cartTotal): string
    {
        if (empty($items)) {
            return '';
        }

        ob_start();
        ?>
        <div class="cart-hero-stats">
            <span>🧺 <?= e((string) count($items)) ?> ürün</span>
            <span>👨‍🌾 <?= e((string) count($producerGroups)) ?> üretici</span>
            <span>💳 <?= e(cart_format_money($cartTotal)) ?></span>
        </div>
        <?php

        return ob_get_clean();
    }
}

if (!function_exists('cart_render_dynamic_area')) {
    function cart_render_dynamic_area(array $items, array $producerGroups, float $cartTotal): string
    {
        $totalQuantity = cart_total_quantity($items);

        ob_start();
        ?>

        <?php if (empty($items)): ?>
            <section class="cart-empty-card glass-card">
                <div class="empty-icon">🧺</div>

                <span class="cart-eyebrow light">Sepetin Boş</span>

                <h2>Henüz sepete ürün eklemedin.</h2>

                <p>
                    Yerel üreticilerin taze ürünlerini inceleyip doğrudan kaynaktan alışverişe başlayabilirsin.
                </p>

                <div class="empty-actions">
                    <a class="cart-btn cart-btn-primary" href="<?= e(url('products.php')) ?>">
                        Ürünleri İncele
                    </a>

                    <a class="cart-btn cart-btn-light" href="<?= e(url('producers.php')) ?>">
                        Üreticileri Gör
                    </a>
                </div>
            </section>
        <?php else: ?>
            <div class="cart-top-actions glass-card">
                <div>
                    <h2>Sepetindeki Ürünler</h2>

                    <p>
                        Her üreticiden seçilen ürünler sipariş sırasında ayrı sipariş grubuna dönüşür.
                    </p>
                </div>

                <form
                    method="POST"
                    action="<?= e(url('cart.php')) ?>"
                    data-cart-ajax="true"
                    data-confirm="Sepeti tamamen temizlemek istediğine emin misin?"
                >
                    <?= csrf_field() ?>

                    <input type="hidden" name="_action" value="clear">

                    <button class="cart-btn cart-btn-danger-light" type="submit">
                        Sepeti Temizle
                    </button>
                </form>
            </div>

            <div class="cart-layout">
                <div class="cart-groups">
                    <?php foreach ($producerGroups as $group): ?>
                        <section class="cart-group glass-card">
                            <div class="group-heading">
                                <div class="group-title">
                                    <span class="group-icon">👨‍🌾</span>

                                    <div>
                                        <h2><?= e($group['producer_name'] ?? 'Üretici') ?></h2>

                                        <p>
                                            Bu üreticiden seçilen ürünler checkout sırasında ayrı siparişe dönüşür.
                                        </p>
                                    </div>
                                </div>

                                <div class="group-subtotal">
                                    <span>Ara Toplam</span>
                                    <strong><?= e(cart_format_money((float) ($group['subtotal'] ?? 0))) ?></strong>
                                </div>
                            </div>

                            <div class="cart-item-list">
                                <?php foreach (($group['items'] ?? []) as $item): ?>
                                    <?php
                                        $unit = cart_unit_label($item['unit_type'] ?? 'kg');
                                        $quantity = (float) ($item['quantity'] ?? 0);
                                        $price = (float) ($item['price'] ?? 0);
                                        $itemTotal = $price * $quantity;
                                        $productId = (int) ($item['product_id'] ?? 0);
                                        $stockQuantity = (float) ($item['stock_quantity'] ?? 0);

                                        $imagePath = $item['cover_image']
                                            ?? $item['cover_image_path']
                                            ?? $item['image_path']
                                            ?? '';
                                    ?>

                                    <article class="cart-item">
                                        <a
                                            class="cart-item-image"
                                            href="<?= e(url('product-detail.php?id=' . $productId)) ?>"
                                            aria-label="<?= e($item['title'] ?? 'Ürün') ?>"
                                        >
                                            <?php if (!empty($imagePath)): ?>
                                                <img
                                                    src="<?= e(url($imagePath)) ?>"
                                                    alt="<?= e($item['title'] ?? 'Ürün') ?>"
                                                >
                                            <?php else: ?>
                                                <div class="cart-image-placeholder">
                                                    <span>🥬</span>
                                                </div>
                                            <?php endif; ?>
                                        </a>

                                        <div class="cart-item-info">
                                            <h3>
                                                <a href="<?= e(url('product-detail.php?id=' . $productId)) ?>">
                                                    <?= e($item['title'] ?? 'Ürün') ?>
                                                </a>
                                            </h3>

                                            <div class="item-meta">
                                                <span>
                                                    <?= e(cart_format_money($price)) ?> / <?= e($unit) ?>
                                                </span>

                                                <span>
                                                    Stok:
                                                    <?= e(cart_number($stockQuantity)) ?>
                                                    <?= e($unit) ?>
                                                </span>
                                            </div>

                                            <a class="small-link" href="<?= e(url('product-detail.php?id=' . $productId)) ?>">
                                                Ürün detayına git
                                            </a>
                                        </div>

                                        <form
                                            method="POST"
                                            action="<?= e(url('cart.php')) ?>"
                                            class="quantity-form"
                                            data-cart-ajax="true"
                                        >
                                            <?= csrf_field() ?>

                                            <input type="hidden" name="_action" value="update">
                                            <input type="hidden" name="cart_item_id" value="<?= e((string) $item['id']) ?>">

                                            <label for="quantity-<?= e((string) $item['id']) ?>">
                                                Miktar
                                            </label>

                                            <div class="quantity-control">
                                                <button
                                                    type="button"
                                                    class="quantity-btn"
                                                    data-quantity-action="decrease"
                                                    aria-label="Miktarı azalt"
                                                >
                                                    −
                                                </button>

                                                <input
                                                    id="quantity-<?= e((string) $item['id']) ?>"
                                                    type="number"
                                                    name="quantity"
                                                    step="0.01"
                                                    min="0.01"
                                                    <?= $stockQuantity > 0 ? 'max="' . e((string) $stockQuantity) . '"' : '' ?>
                                                    value="<?= e((string) $quantity) ?>"
                                                    required
                                                >

                                                <button
                                                    type="button"
                                                    class="quantity-btn"
                                                    data-quantity-action="increase"
                                                    aria-label="Miktarı artır"
                                                >
                                                    +
                                                </button>
                                            </div>

                                            <button class="cart-mini-btn" type="submit">
                                                Güncelle
                                            </button>
                                        </form>

                                        <div class="item-total">
                                            <span>Toplam</span>

                                            <strong><?= e(cart_format_money($itemTotal)) ?></strong>

                                            <form
                                                method="POST"
                                                action="<?= e(url('cart.php')) ?>"
                                                data-cart-ajax="true"
                                                data-confirm="Bu ürünü sepetten kaldırmak istediğine emin misin?"
                                            >
                                                <?= csrf_field() ?>

                                                <input type="hidden" name="_action" value="remove">
                                                <input type="hidden" name="cart_item_id" value="<?= e((string) $item['id']) ?>">

                                                <button class="remove-btn" type="submit">
                                                    Kaldır
                                                </button>
                                            </form>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endforeach; ?>
                </div>

                <aside class="cart-summary glass-card">
                    <div class="summary-header">
                        <span>💳</span>

                        <div>
                            <h2>Sepet Özeti</h2>
                            <p>Siparişe geçmeden önce sepetini kontrol et.</p>
                        </div>
                    </div>

                    <div class="summary-box">
                        <div class="summary-row">
                            <span>Ürün Çeşidi</span>
                            <strong><?= e((string) count($items)) ?></strong>
                        </div>

                        <div class="summary-row">
                            <span>Toplam Miktar</span>
                            <strong><?= e(cart_number($totalQuantity)) ?></strong>
                        </div>

                        <div class="summary-row">
                            <span>Üretici Sayısı</span>
                            <strong><?= e((string) count($producerGroups)) ?></strong>
                        </div>

                        <div class="summary-total">
                            <span>Genel Toplam</span>
                            <strong><?= e(cart_format_money($cartTotal)) ?></strong>
                        </div>
                    </div>

                    <div class="summary-note">
                        <span>🌿</span>

                        <p>
                            Ürünler doğrudan üreticiden geldiği için her üretici grubu ayrı takip edilir.
                        </p>
                    </div>

                    <div class="summary-actions">
                        <a class="cart-btn cart-btn-primary full" href="<?= e(url('checkout.php')) ?>">
                            Siparişe Geç
                        </a>

                        <a class="cart-btn cart-btn-light full" href="<?= e(url('products.php')) ?>">
                            Alışverişe Devam Et
                        </a>
                    </div>
                </aside>
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
    $result = [
        'success' => false,
        'message' => 'Geçersiz sepet işlemi.',
    ];

    if ($action === 'update') {
        $cartItemId = (int) ($_POST['cart_item_id'] ?? 0);
        $quantity = (float) ($_POST['quantity'] ?? 0);

        $result = $cartService->updateItem($userId, $cartItemId, $quantity);
    } elseif ($action === 'remove') {
        $cartItemId = (int) ($_POST['cart_item_id'] ?? 0);

        $result = $cartService->removeItem($userId, $cartItemId);
    } elseif ($action === 'clear') {
        $result = $cartService->clearCart($userId);
    }

    $cart = $cartService->getActiveCart($userId) ?: [];
    $items = $cart['items'] ?? [];
    $producerGroups = cart_build_producer_groups($cart);
    $cartTotal = (float) ($cart['total'] ?? 0);

    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode([
            'success' => (bool) ($result['success'] ?? false),
            'message' => $result['message'] ?? '',
            'html' => cart_render_dynamic_area($items, $producerGroups, $cartTotal),
            'heroStatsHtml' => cart_render_hero_stats($items, $producerGroups, $cartTotal),
            'itemCount' => count($items),
            'producerCount' => count($producerGroups),
            'cartTotal' => cart_format_money($cartTotal),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        exit;
    }

    if ($result['success']) {
        flash_success($result['message']);
    } else {
        flash_error($result['message']);
    }

    redirect('cart.php');
}

$cart = $cartService->getActiveCart($userId) ?: [];
$items = $cart['items'] ?? [];
$producerGroups = cart_build_producer_groups($cart);
$cartTotal = (float) ($cart['total'] ?? 0);

$pageTitle = 'Sepetim';
$bodyClass = 'page-cart';

require APP_PATH . '/Views/layouts/header.php';
?>

<main class="cart-page">
    <section class="cart-hero">
        <div class="cart-hero-bg cart-blob-one"></div>
        <div class="cart-hero-bg cart-blob-two"></div>

        <div class="cart-hero-inner">
            <nav class="cart-breadcrumb" aria-label="Sayfa yolu">
                <a href="<?= e(url('index.php')) ?>">Ana Sayfa</a>
                <span>/</span>
                <a href="<?= e(url('products.php')) ?>">Ürünler</a>
                <span>/</span>
                <strong>Sepetim</strong>
            </nav>

            <div class="cart-hero-content">
                <span class="cart-eyebrow">Alışveriş Sepeti</span>

                <h1>Sepetim</h1>

                <p>
                    Sepetindeki ürünleri üretici bazlı görüntüleyebilir, miktarları güncelleyebilir
                    ve sipariş adımına geçebilirsin.
                </p>

                <div id="cart-hero-stats-wrap">
                    <?= cart_render_hero_stats($items, $producerGroups, $cartTotal) ?>
                </div>
            </div>
        </div>
    </section>

    <section class="cart-shell">
        <div id="cart-ajax-message" class="cart-ajax-message" hidden></div>

        <div id="cart-dynamic-area">
            <?= cart_render_dynamic_area($items, $producerGroups, $cartTotal) ?>
        </div>
    </section>
</main>

<style>
    :root {
        --cart-green-950: #102f1a;
        --cart-green-900: #163d22;
        --cart-green-800: #245c2f;
        --cart-green-700: #2f7d3d;
        --cart-green-600: #3f9650;
        --cart-green-100: #eaf6e8;
        --cart-green-50: #f6fbf4;
        --cart-cream: #fffaf1;
        --cart-yellow: #f2bf4d;
        --cart-red: #9b111e;
        --cart-text: #1e2b21;
        --cart-muted: #687669;
        --cart-border: rgba(47, 125, 61, .14);
        --cart-shadow: 0 24px 70px rgba(31, 79, 43, .12);
        --cart-radius-lg: 28px;
    }

    body.page-cart {
        background:
            radial-gradient(circle at 15% 12%, rgba(196, 231, 177, .50), transparent 28%),
            radial-gradient(circle at 88% 18%, rgba(242, 191, 77, .14), transparent 24%),
            linear-gradient(180deg, #f8fbf2 0%, #f3f8ed 48%, #ffffff 100%);
    }

    .cart-page {
        overflow: hidden;
    }

    .cart-hero {
        position: relative;
        min-height: 310px;
        padding: 34px 18px 86px;
        background:
            radial-gradient(circle at 82% 18%, rgba(242, 191, 77, .30), transparent 26%),
            radial-gradient(circle at 12% 78%, rgba(255, 255, 255, .16), transparent 24%),
            linear-gradient(135deg, rgba(36, 92, 47, .97), rgba(47, 125, 61, .87));
        color: #ffffff;
    }

    .cart-hero::after {
        content: '';
        position: absolute;
        inset: auto 0 -1px;
        height: 86px;
        background: linear-gradient(180deg, rgba(246, 251, 244, 0), #f6fbf4 82%);
        pointer-events: none;
    }

    .cart-hero-inner,
    .cart-shell {
        width: min(1180px, calc(100% - 32px));
        margin: 0 auto;
    }

    .cart-hero-inner {
        position: relative;
        z-index: 2;
    }

    .cart-hero-bg {
        position: absolute;
        border-radius: 999px;
        filter: blur(2px);
        opacity: .45;
        pointer-events: none;
    }

    .cart-blob-one {
        width: 220px;
        height: 220px;
        right: 10%;
        top: 42px;
        background: rgba(242, 191, 77, .28);
    }

    .cart-blob-two {
        width: 145px;
        height: 145px;
        left: 8%;
        bottom: 34px;
        background: rgba(255, 255, 255, .20);
    }

    .cart-breadcrumb {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 9px;
        font-size: 14px;
        margin-bottom: 32px;
        color: rgba(255, 255, 255, .76);
    }

    .cart-breadcrumb a {
        color: #ffffff;
        text-decoration: none;
        font-weight: 800;
    }

    .cart-breadcrumb strong {
        color: #ffffff;
        font-weight: 900;
    }

    .cart-hero-content {
        max-width: 760px;
    }

    .cart-eyebrow {
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

    .cart-eyebrow.light {
        color: var(--cart-green-800);
        background: var(--cart-green-100);
        border-color: transparent;
    }

    .cart-hero-content h1 {
        margin: 16px 0 12px;
        font-size: clamp(38px, 5vw, 62px);
        line-height: 1.03;
        letter-spacing: -.045em;
    }

    .cart-hero-content p {
        max-width: 680px;
        margin: 0;
        color: rgba(255, 255, 255, .86);
        font-size: 17px;
        line-height: 1.7;
    }

    .cart-hero-stats {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 18px;
    }

    .cart-hero-stats span {
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

    .cart-shell {
        position: relative;
        z-index: 3;
        margin-top: -60px;
        padding-bottom: 54px;
    }

    .glass-card {
        background: rgba(255, 255, 255, .92);
        border: 1px solid rgba(255, 255, 255, .72);
        border-radius: var(--cart-radius-lg);
        box-shadow: var(--cart-shadow);
        backdrop-filter: blur(16px);
    }

    .cart-ajax-message {
        margin-bottom: 16px;
        padding: 14px 16px;
        border-radius: 18px;
        font-weight: 900;
        box-shadow: 0 12px 30px rgba(31, 79, 43, .10);
    }

    .cart-ajax-message.success {
        background: #e7f7e8;
        color: #236b2c;
        border: 1px solid rgba(35, 107, 44, .14);
    }

    .cart-ajax-message.error {
        background: #fdeaea;
        color: var(--cart-red);
        border: 1px solid rgba(155, 17, 30, .14);
    }

    .cart-top-actions {
        margin-bottom: 22px;
        padding: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
    }

    .cart-top-actions h2,
    .cart-summary h2,
    .group-heading h2,
    .cart-empty-card h2 {
        margin: 0;
        color: var(--cart-green-900);
        letter-spacing: -.03em;
    }

    .cart-top-actions p,
    .cart-summary p,
    .group-heading p,
    .cart-empty-card p,
    .summary-note p {
        margin: 6px 0 0;
        color: var(--cart-muted);
        line-height: 1.6;
    }

    .cart-layout {
        display: grid;
        grid-template-columns: minmax(0, 1.8fr) minmax(320px, .8fr);
        gap: 22px;
        align-items: start;
    }

    .cart-groups {
        display: grid;
        gap: 22px;
    }

    .cart-group {
        padding: 18px;
    }

    .group-heading {
        display: flex;
        justify-content: space-between;
        gap: 18px;
        align-items: flex-start;
        border-bottom: 1px solid rgba(47, 125, 61, .10);
        padding-bottom: 16px;
        margin-bottom: 16px;
    }

    .group-title {
        display: flex;
        gap: 13px;
        align-items: flex-start;
    }

    .group-icon {
        width: 46px;
        height: 46px;
        display: grid;
        place-items: center;
        border-radius: 16px;
        background: var(--cart-green-100);
        flex: 0 0 auto;
        font-size: 21px;
    }

    .group-subtotal {
        display: grid;
        justify-items: end;
        gap: 4px;
        white-space: nowrap;
    }

    .group-subtotal span,
    .item-total span {
        color: var(--cart-muted);
        font-size: 12px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .group-subtotal strong,
    .item-total strong {
        color: var(--cart-green-800);
        font-size: 21px;
    }

    .cart-item-list {
        display: grid;
        gap: 13px;
    }

    .cart-item {
        display: grid;
        grid-template-columns: 112px minmax(0, 1fr) 190px 150px;
        gap: 16px;
        align-items: center;
        padding: 14px;
        border-radius: 22px;
        background: #fbfdf8;
        border: 1px solid var(--cart-border);
        transition: transform .2s ease, box-shadow .2s ease, opacity .2s ease;
    }

    .cart-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 18px 36px rgba(31, 79, 43, .10);
    }

    .cart-item-image {
        display: block;
        width: 112px;
        height: 96px;
        overflow: hidden;
        border-radius: 18px;
        background: var(--cart-green-100);
        text-decoration: none;
    }

    .cart-item-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        transition: transform .35s ease;
    }

    .cart-item:hover .cart-item-image img {
        transform: scale(1.04);
    }

    .cart-image-placeholder {
        width: 100%;
        height: 100%;
        display: grid;
        place-items: center;
        background:
            radial-gradient(circle at 30% 25%, rgba(255, 255, 255, .75), transparent 28%),
            linear-gradient(135deg, #e8f3e9, #f8fbf6);
        color: var(--cart-green-700);
        font-size: 34px;
    }

    .cart-item-info h3 {
        margin: 0 0 8px;
        font-size: 21px;
        letter-spacing: -.02em;
    }

    .cart-item-info h3 a {
        color: var(--cart-green-900);
        text-decoration: none;
    }

    .cart-item-info h3 a:hover {
        color: var(--cart-green-700);
    }

    .item-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 9px;
    }

    .item-meta span {
        display: inline-flex;
        padding: 7px 10px;
        border-radius: 999px;
        background: #ffffff;
        border: 1px solid rgba(47, 125, 61, .10);
        color: var(--cart-muted);
        font-size: 13px;
        font-weight: 800;
    }

    .small-link {
        color: var(--cart-green-700);
        font-weight: 900;
        text-decoration: none;
        font-size: 14px;
    }

    .small-link:hover {
        text-decoration: underline;
    }

    .quantity-form {
        display: grid;
        gap: 9px;
    }

    .quantity-form label {
        color: var(--cart-green-900);
        font-weight: 900;
        font-size: 13px;
    }

    .quantity-control {
        display: grid;
        grid-template-columns: 38px minmax(70px, 1fr) 38px;
        align-items: center;
        gap: 7px;
        padding: 7px;
        border-radius: 17px;
        background: #ffffff;
        border: 1px solid rgba(47, 125, 61, .14);
    }

    .quantity-control input {
        width: 100%;
        min-width: 0;
        padding: 10px 8px;
        border: 0;
        outline: 0;
        border-radius: 12px;
        background: var(--cart-green-50);
        color: var(--cart-text);
        text-align: center;
        font-weight: 900;
    }

    .quantity-btn {
        width: 38px;
        height: 38px;
        border: 0;
        border-radius: 12px;
        background: var(--cart-green-50);
        color: var(--cart-green-800);
        font-size: 19px;
        font-weight: 900;
        cursor: pointer;
        transition: background .18s ease, transform .18s ease;
    }

    .quantity-btn:hover {
        background: var(--cart-green-100);
        transform: translateY(-1px);
    }

    .cart-mini-btn,
    .remove-btn {
        border: 0;
        background: transparent;
        font-family: inherit;
        cursor: pointer;
        font-weight: 900;
        padding: 0;
        text-align: left;
    }

    .cart-mini-btn {
        color: var(--cart-green-700);
    }

    .remove-btn {
        color: var(--cart-red);
    }

    .cart-mini-btn:hover,
    .remove-btn:hover {
        text-decoration: underline;
    }

    .item-total {
        display: grid;
        gap: 7px;
        justify-items: end;
        text-align: right;
    }

    .cart-summary {
        position: sticky;
        top: 22px;
        padding: 20px;
    }

    .summary-header {
        display: flex;
        align-items: flex-start;
        gap: 13px;
        margin-bottom: 18px;
        padding-bottom: 16px;
        border-bottom: 1px solid rgba(47, 125, 61, .10);
    }

    .summary-header > span {
        width: 46px;
        height: 46px;
        display: grid;
        place-items: center;
        border-radius: 16px;
        background: var(--cart-green-100);
        font-size: 21px;
        flex: 0 0 auto;
    }

    .summary-box {
        padding: 14px;
        border-radius: 22px;
        background: #fbfdf8;
        border: 1px solid var(--cart-border);
    }

    .summary-row,
    .summary-total {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        padding: 13px 0;
        border-bottom: 1px solid rgba(47, 125, 61, .10);
    }

    .summary-row span,
    .summary-total span {
        color: var(--cart-muted);
        font-weight: 800;
    }

    .summary-row strong {
        color: var(--cart-green-900);
    }

    .summary-total {
        border-bottom: none;
        padding-bottom: 0;
        align-items: flex-end;
    }

    .summary-total strong {
        color: var(--cart-green-800);
        font-size: 26px;
        line-height: 1;
        letter-spacing: -.03em;
    }

    .summary-note {
        display: grid;
        grid-template-columns: 38px 1fr;
        gap: 10px;
        align-items: flex-start;
        margin-top: 14px;
        padding: 13px;
        border-radius: 18px;
        background: #fff8df;
        border: 1px solid rgba(242, 191, 77, .35);
    }

    .summary-note span {
        width: 38px;
        height: 38px;
        display: grid;
        place-items: center;
        border-radius: 14px;
        background: #ffffff;
    }

    .summary-note p {
        margin: 0;
        color: #7a5400;
        font-weight: 800;
        font-size: 14px;
    }

    .summary-actions {
        display: grid;
        gap: 10px;
        margin-top: 16px;
    }

    .cart-btn {
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

    .cart-btn:hover {
        transform: translateY(-2px);
    }

    .cart-btn.full {
        width: 100%;
    }

    .cart-btn-primary {
        background: linear-gradient(135deg, var(--cart-green-700), var(--cart-green-900));
        color: #ffffff;
        box-shadow: 0 16px 32px rgba(47, 125, 61, .24);
    }

    .cart-btn-light {
        background: var(--cart-green-50);
        color: var(--cart-green-800);
        border: 1px solid var(--cart-border);
    }

    .cart-btn-danger-light {
        background: #fdeaea;
        color: var(--cart-red);
        border: 1px solid rgba(155, 17, 30, .14);
    }

    .cart-empty-card {
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
        background: var(--cart-green-100);
        font-size: 36px;
    }

    .cart-empty-card h2 {
        margin: 18px 0 10px;
        font-size: 34px;
    }

    .cart-empty-card p {
        max-width: 520px;
        margin: 0 auto 22px;
    }

    .empty-actions {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 10px;
    }

    .cart-loading {
        pointer-events: none;
        opacity: .72;
    }

    @media (max-width: 1100px) {
        .cart-layout {
            grid-template-columns: 1fr;
        }

        .cart-summary {
            position: static;
        }
    }

    @media (max-width: 900px) {
        .cart-item {
            grid-template-columns: 96px minmax(0, 1fr);
        }

        .cart-item-image {
            width: 96px;
            height: 86px;
        }

        .quantity-form,
        .item-total {
            grid-column: 1 / -1;
        }

        .item-total {
            justify-items: start;
            text-align: left;
            padding-top: 10px;
            border-top: 1px solid rgba(47, 125, 61, .10);
        }
    }

    @media (max-width: 720px) {
        .cart-hero {
            min-height: 315px;
            padding-top: 24px;
        }

        .cart-hero-inner,
        .cart-shell {
            width: min(100% - 22px, 1180px);
        }

        .cart-breadcrumb {
            font-size: 13px;
            margin-bottom: 24px;
        }

        .cart-hero-content p {
            font-size: 15px;
        }

        .cart-shell {
            margin-top: -48px;
        }

        .cart-top-actions,
        .group-heading {
            flex-direction: column;
            align-items: flex-start;
        }

        .cart-group,
        .cart-summary,
        .cart-top-actions,
        .cart-empty-card {
            padding: 14px;
            border-radius: 23px;
        }

        .group-subtotal {
            justify-items: start;
        }

        .cart-item {
            grid-template-columns: 1fr;
        }

        .cart-item-image {
            width: 100%;
            height: 190px;
        }

        .quantity-control {
            grid-template-columns: 42px minmax(80px, 1fr) 42px;
        }

        .quantity-btn {
            width: 42px;
            height: 42px;
        }

        .cart-empty-card h2 {
            font-size: 28px;
        }

        .empty-actions .cart-btn {
            width: 100%;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const dynamicArea = document.getElementById('cart-dynamic-area');
        const heroStatsWrap = document.getElementById('cart-hero-stats-wrap');
        const messageBox = document.getElementById('cart-ajax-message');

        let autoSubmitTimer = null;
        let activeRequestController = null;

        function showCartMessage(type, message) {
            if (!messageBox) {
                return;
            }

            messageBox.hidden = false;
            messageBox.className = 'cart-ajax-message ' + type;
            messageBox.textContent = message || '';

            window.setTimeout(function () {
                messageBox.hidden = true;
                messageBox.textContent = '';
                messageBox.className = 'cart-ajax-message';
            }, 2600);
        }

        async function submitCartForm(form, showSuccessMessage = true) {
            if (!form || form.dataset.loading === 'true') {
                return;
            }

            const confirmText = form.getAttribute('data-confirm');

            if (confirmText && !window.confirm(confirmText)) {
                return;
            }

            form.dataset.loading = 'true';
            dynamicArea.classList.add('cart-loading');

            const submitButtons = form.querySelectorAll('button');
            submitButtons.forEach(function (button) {
                button.disabled = true;
            });

            if (activeRequestController) {
                activeRequestController.abort();
            }

            activeRequestController = new AbortController();

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    signal: activeRequestController.signal
                });

                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.message || 'Sepet işlemi tamamlanamadı.');
                }

                if (typeof result.html === 'string') {
                    dynamicArea.innerHTML = result.html;
                }

                if (heroStatsWrap && typeof result.heroStatsHtml === 'string') {
                    heroStatsWrap.innerHTML = result.heroStatsHtml;
                }

                if (showSuccessMessage) {
                    showCartMessage(result.success ? 'success' : 'error', result.message);
                }
            } catch (error) {
                if (error.name !== 'AbortError') {
                    showCartMessage('error', error.message || 'Sepet güncellenirken bir hata oluştu.');
                }
            } finally {
                dynamicArea.classList.remove('cart-loading');

                submitButtons.forEach(function (button) {
                    button.disabled = false;
                });

                form.dataset.loading = 'false';
                activeRequestController = null;
            }
        }

        function autoSubmitQuantityForm(form) {
            window.clearTimeout(autoSubmitTimer);

            autoSubmitTimer = window.setTimeout(function () {
                submitCartForm(form, false);
            }, 350);
        }

        document.addEventListener('submit', function (event) {
            const form = event.target.closest('form[data-cart-ajax="true"]');

            if (!form) {
                return;
            }

            event.preventDefault();
            submitCartForm(form, true);
        });

        document.addEventListener('click', function (event) {
            const button = event.target.closest('[data-quantity-action]');

            if (!button) {
                return;
            }

            const form = button.closest('.quantity-form');
            const input = form ? form.querySelector('input[name="quantity"]') : null;

            if (!form || !input) {
                return;
            }

            const action = button.getAttribute('data-quantity-action');
            const step = parseFloat(input.getAttribute('step') || '1');
            const min = parseFloat(input.getAttribute('min') || '0.01');
            const max = parseFloat(input.getAttribute('max') || '999999');
            const current = parseFloat(input.value || '0');

            let nextValue = action === 'increase'
                ? current + step
                : current - step;

            nextValue = Math.max(min, Math.min(max, nextValue));
            input.value = Number(nextValue.toFixed(2));

            autoSubmitQuantityForm(form);
        });

        document.addEventListener('change', function (event) {
            const input = event.target.closest('.quantity-form input[name="quantity"]');

            if (!input) {
                return;
            }

            const form = input.closest('.quantity-form');

            if (!form) {
                return;
            }

            const min = parseFloat(input.getAttribute('min') || '0.01');
            const max = parseFloat(input.getAttribute('max') || '999999');
            let value = parseFloat(input.value || min);

            value = Math.max(min, Math.min(max, value));
            input.value = Number(value.toFixed(2));

            autoSubmitQuantityForm(form);
        });
    });
</script>

<?php require APP_PATH . '/Views/layouts/footer.php'; ?>