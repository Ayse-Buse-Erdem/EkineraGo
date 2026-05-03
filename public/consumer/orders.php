<?php

require_once __DIR__ . '/../../app/bootstrap.php';

ConsumerMiddleware::handle();

if (isset($_GET['review_order_item_id'])) {
    $controller = new ReviewController();
    $controller->create();
    exit;
}

$orderService = new OrderService();
$reviewService = new ReviewService();

$userId = (int) currentUserId();
$orders = $orderService->getConsumerOrders($userId);

$pageTitle = 'Siparişlerim';
$bodyClass = 'page-consumer-orders';

require APP_PATH . '/Views/layouts/header.php';

if (!function_exists('consumer_order_unit_label')) {
    function consumer_order_unit_label(string $unit): string
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

if (!function_exists('consumer_order_money')) {
    function consumer_order_money(float $amount): string
    {
        if (function_exists('formatMoney')) {
            return formatMoney($amount);
        }

        return number_format($amount, 2, ',', '.') . ' TL';
    }
}

if (!function_exists('consumer_order_date')) {
    function consumer_order_date(?string $date): string
    {
        if (!$date) {
            return '-';
        }

        $timestamp = strtotime($date);

        if (!$timestamp) {
            return $date;
        }

        return date('d.m.Y H:i', $timestamp);
    }
}

if (!function_exists('consumer_order_producer_name')) {
    function consumer_order_producer_name(array $order): string
    {
        if (!empty($order['store_name'])) {
            return $order['store_name'];
        }

        if (!empty($order['producer_name'])) {
            return $order['producer_name'];
        }

        return 'Üretici';
    }
}

if (!function_exists('consumer_order_status_class')) {
    function consumer_order_status_class(OrderService $orderService, string $status): string
    {
        if (method_exists($orderService, 'statusBadgeClass')) {
            return $orderService->statusBadgeClass($status);
        }

        return 'badge-muted';
    }
}

if (!function_exists('consumer_order_payment_class')) {
    function consumer_order_payment_class(OrderService $orderService, string $status): string
    {
        if (method_exists($orderService, 'paymentBadgeClass')) {
            return $orderService->paymentBadgeClass($status);
        }

        return 'badge-muted';
    }
}

if (!function_exists('consumer_order_status_label')) {
    function consumer_order_status_label(OrderService $orderService, array $order, string $status): string
    {
        if (!empty($order['order_status_label'])) {
            return $order['order_status_label'];
        }

        if (method_exists($orderService, 'statusLabel')) {
            return $orderService->statusLabel($status);
        }

        return $status ?: 'Durum Yok';
    }
}

if (!function_exists('consumer_order_payment_label')) {
    function consumer_order_payment_label(OrderService $orderService, array $order, string $status): string
    {
        if (!empty($order['payment_status_label'])) {
            return $order['payment_status_label'];
        }

        if (method_exists($orderService, 'paymentStatusLabel')) {
            return $orderService->paymentStatusLabel($status);
        }

        return $status ?: 'Ödeme Yok';
    }
}

$deliveredStatus = defined('ORDER_STATUS_DELIVERED') ? ORDER_STATUS_DELIVERED : 'delivered';

$totalSpent = 0;
$activeOrderCount = 0;
$deliveredOrderCount = 0;

foreach ($orders as $order) {
    $totalSpent += (float) ($order['total_amount'] ?? 0);

    $orderStatus = $order['order_status'] ?? '';

    if ($orderStatus === $deliveredStatus) {
        $deliveredOrderCount++;
    } elseif (!in_array($orderStatus, ['cancelled', 'canceled'], true)) {
        $activeOrderCount++;
    }
}
?>

<main class="consumer-orders-page">
    <section class="orders-hero">
        <div class="orders-hero-bg orders-blob-one"></div>
        <div class="orders-hero-bg orders-blob-two"></div>

        <div class="orders-hero-inner">
            <nav class="orders-breadcrumb" aria-label="Sayfa yolu">
                <a href="<?= e(url('index.php')) ?>">Ana Sayfa</a>
                <span>/</span>
                <a href="<?= e(url('consumer/dashboard.php')) ?>">Tüketici Paneli</a>
                <span>/</span>
                <strong>Siparişlerim</strong>
            </nav>

            <div class="orders-hero-content">
                <div class="orders-hero-copy">
                    <span class="orders-eyebrow">
                        Sipariş Takibi
                    </span>

                    <h1>Siparişlerim</h1>

                    <p>
                        Oluşturduğun siparişleri, ürün kalemlerini, ödeme durumunu ve takip numaralarını
                        buradan düzenli şekilde izleyebilirsin.
                    </p>

                    <?php if (!empty($orders)): ?>
                        <div class="orders-hero-stats">
                            <span>📦 <?= e((string) count($orders)) ?> sipariş</span>
                            <span>🚚 <?= e((string) $activeOrderCount) ?> aktif</span>
                            <span>✅ <?= e((string) $deliveredOrderCount) ?> teslim edildi</span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="orders-hero-card">
                    <div class="hero-card-icon">📦</div>

                    <h2>Sipariş sürecini tek ekrandan takip et</h2>

                    <p>
                        Üretici, ödeme, kargo ve ürün detayları her sipariş kartında ayrı ayrı gösterilir.
                    </p>

                    <div class="hero-mini-list">
                        <span>💳 Ödeme durumu</span>
                        <span>🚚 Kargo takibi</span>
                        <span>⭐ Yorum işlemi</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="orders-shell">
        <?php if (empty($orders)): ?>
            <section class="orders-empty-card glass-card">
                <div class="empty-icon">🧺</div>

                <span class="orders-eyebrow light">Henüz Sipariş Yok</span>

                <h2>Henüz sipariş oluşturmadın.</h2>

                <p>
                    Ürünleri inceleyip sepete ekledikten sonra checkout adımından ilk siparişini oluşturabilirsin.
                </p>

                <div class="empty-actions">
                    <a class="orders-btn orders-btn-primary" href="<?= e(url('products.php')) ?>">
                        Ürünleri İncele
                    </a>

                    <a class="orders-btn orders-btn-light" href="<?= e(url('consumer/dashboard.php')) ?>">
                        Panele Dön
                    </a>
                </div>
            </section>
        <?php else: ?>
            <section class="orders-top glass-card">
                <div>
                    <span class="section-kicker">Genel Özet</span>
                    <h2>Sipariş geçmişin</h2>
                    <p>Aktif, teslim edilen ve geçmiş siparişlerini buradan kontrol edebilirsin.</p>
                </div>

                <div class="orders-top-total">
                    <span>Toplam Harcama</span>
                    <strong><?= e(consumer_order_money($totalSpent)) ?></strong>
                </div>
            </section>

            <section class="orders-list">
                <?php foreach ($orders as $order): ?>
                    <?php
                        $items = $order['items'] ?? [];
                        $shipment = $order['shipment'] ?? null;
                        $orderStatus = $order['order_status'] ?? '';
                        $paymentStatus = $order['payment_status'] ?? '';
                        $producerName = consumer_order_producer_name($order);

                        $trackingNo = $order['tracking_no']
                            ?? ($shipment['tracking_no'] ?? '');

                        $orderNo = $order['order_no'] ?? 'Sipariş';
                    ?>

                    <article class="order-card glass-card">
                        <div class="order-header">
                            <div class="order-title-area">
                                <span class="order-icon">📦</span>

                                <div>
                                    <h2><?= e($orderNo) ?></h2>

                                    <p>
                                        Üretici:
                                        <strong><?= e($producerName) ?></strong>
                                    </p>

                                    <p>
                                        Tarih:
                                        <?= e(consumer_order_date($order['created_at'] ?? null)) ?>
                                    </p>
                                </div>
                            </div>

                            <div class="order-status-area">
                                <span class="order-badge <?= e(consumer_order_status_class($orderService, $orderStatus)) ?>">
                                    <?= e(consumer_order_status_label($orderService, $order, $orderStatus)) ?>
                                </span>

                                <span class="order-badge <?= e(consumer_order_payment_class($orderService, $paymentStatus)) ?>">
                                    <?= e(consumer_order_payment_label($orderService, $order, $paymentStatus)) ?>
                                </span>
                            </div>
                        </div>

                        <div class="order-summary-grid">
                            <div>
                                <span>Toplam</span>
                                <strong><?= e(consumer_order_money((float) ($order['total_amount'] ?? 0))) ?></strong>
                            </div>

                            <div>
                                <span>Ara Toplam</span>
                                <strong><?= e(consumer_order_money((float) ($order['subtotal'] ?? 0))) ?></strong>
                            </div>

                            <div>
                                <span>Kargo</span>
                                <strong><?= e(consumer_order_money((float) ($order['shipping_fee'] ?? 0))) ?></strong>
                            </div>

                            <div>
                                <span>Takip No</span>
                                <strong><?= e($trackingNo ?: '-') ?></strong>
                            </div>
                        </div>

                        <div class="order-items">
                            <div class="order-section-title">
                                <span>🧺</span>

                                <div>
                                    <h3>Ürünler</h3>
                                    <p>Bu siparişe ait ürün kalemleri.</p>
                                </div>
                            </div>

                            <?php if (empty($items)): ?>
                                <div class="soft-empty">
                                    Bu sipariş için ürün kalemi bulunamadı.
                                </div>
                            <?php else: ?>
                                <div class="order-item-list">
                                    <?php foreach ($items as $item): ?>
                                        <?php
                                            $unit = consumer_order_unit_label($item['unit_type_snapshot'] ?? 'kg');
                                            $quantity = (float) ($item['quantity'] ?? 0);
                                            $unitPrice = (float) ($item['unit_price'] ?? 0);
                                            $totalPrice = (float) ($item['total_price'] ?? ($quantity * $unitPrice));

                                            $orderItemId = (int) ($item['id'] ?? 0);
                                            $productId = (int) ($item['product_id'] ?? 0);

                                            $isDelivered = $orderStatus === $deliveredStatus;

                                            $hasReview = $orderItemId > 0
                                                ? $reviewService->hasReviewForOrderItem($orderItemId)
                                                : false;

                                            $reviewCreateUrl = url('consumer/orders.php?review_order_item_id=' . $orderItemId);

                                            $reviewViewUrl = $productId > 0
                                                ? url('product-detail.php?id=' . $productId . '#reviews')
                                                : url('consumer/orders.php');
                                        ?>

                                        <div class="order-item">
                                            <div class="order-item-main">
                                                <span class="item-leaf">🌱</span>

                                                <div>
                                                    <strong><?= e($item['product_title_snapshot'] ?? 'Ürün') ?></strong>

                                                    <p>
                                                        <?= e((string) $quantity) ?>
                                                        <?= e($unit) ?>
                                                        ·
                                                        <?= e(consumer_order_money($unitPrice)) ?>
                                                        /
                                                        <?= e($unit) ?>
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="order-item-actions">
                                                <strong><?= e(consumer_order_money($totalPrice)) ?></strong>

                                                <?php if ($isDelivered && !$hasReview): ?>
                                                    <a class="small-link" href="<?= e($reviewCreateUrl) ?>">
                                                        Yorum Yap
                                                    </a>
                                                <?php elseif ($isDelivered && $hasReview): ?>
                                                    <span class="review-done">
                                                        Yorum Yapıldı
                                                    </span>

                                                    <a class="small-link secondary-link" href="<?= e($reviewViewUrl) ?>">
                                                        Yorumu Gör
                                                    </a>
                                                <?php else: ?>
                                                    <span class="muted-action">
                                                        Teslim edilince yorum yapılır
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($shipment): ?>
                            <div class="shipment-box">
                                <div class="shipment-title">
                                    <span>🚚</span>
                                    <strong>Kargo Bilgisi</strong>
                                </div>

                                <div class="shipment-grid">
                                    <div>
                                        <span>Firma</span>
                                        <strong><?= e($shipment['cargo_company'] ?: 'Demo Kargo') ?></strong>
                                    </div>

                                    <div>
                                        <span>Durum</span>
                                        <strong><?= e($shipment['shipment_status'] ?? '-') ?></strong>
                                    </div>

                                    <div>
                                        <span>Takip No</span>
                                        <strong><?= e($shipment['tracking_no'] ?? '-') ?></strong>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </section>
</main>

<style>
    :root {
        --orders-green-950: #102f1a;
        --orders-green-900: #163d22;
        --orders-green-800: #245c2f;
        --orders-green-700: #2f7d3d;
        --orders-green-600: #3f9650;
        --orders-green-100: #eaf6e8;
        --orders-green-50: #f6fbf4;
        --orders-cream: #fffaf1;
        --orders-yellow: #f2bf4d;
        --orders-red: #9b111e;
        --orders-text: #1e2b21;
        --orders-muted: #687669;
        --orders-border: rgba(47, 125, 61, .14);
        --orders-shadow: 0 24px 70px rgba(31, 79, 43, .12);
        --orders-radius-lg: 28px;
    }

    body.page-consumer-orders {
        background:
            radial-gradient(circle at 14% 12%, rgba(196, 231, 177, .48), transparent 28%),
            radial-gradient(circle at 88% 16%, rgba(242, 191, 77, .16), transparent 24%),
            linear-gradient(180deg, #f8fbf2 0%, #f3f8ed 48%, #ffffff 100%);
    }

    .consumer-orders-page {
        overflow: hidden;
    }

    .orders-hero {
        position: relative;
        min-height: 390px;
        padding: 34px 18px 94px;
        background:
            radial-gradient(circle at 82% 18%, rgba(242, 191, 77, .30), transparent 26%),
            radial-gradient(circle at 12% 78%, rgba(255, 255, 255, .16), transparent 24%),
            linear-gradient(135deg, rgba(36, 92, 47, .97), rgba(47, 125, 61, .87));
        color: #ffffff;
    }

    .orders-hero::after {
        content: '';
        position: absolute;
        inset: auto 0 -1px;
        height: 90px;
        background: linear-gradient(180deg, rgba(246, 251, 244, 0), #f6fbf4 82%);
        pointer-events: none;
    }

    .orders-hero-inner,
    .orders-shell {
        width: min(1180px, calc(100% - 32px));
        margin: 0 auto;
    }

    .orders-hero-inner {
        position: relative;
        z-index: 2;
    }

    .orders-hero-bg {
        position: absolute;
        border-radius: 999px;
        filter: blur(2px);
        opacity: .45;
        pointer-events: none;
    }

    .orders-blob-one {
        width: 230px;
        height: 230px;
        right: 10%;
        top: 42px;
        background: rgba(242, 191, 77, .28);
    }

    .orders-blob-two {
        width: 150px;
        height: 150px;
        left: 8%;
        bottom: 36px;
        background: rgba(255, 255, 255, .20);
    }

    .orders-breadcrumb {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 9px;
        font-size: 14px;
        margin-bottom: 32px;
        color: rgba(255, 255, 255, .76);
    }

    .orders-breadcrumb a {
        color: #ffffff;
        text-decoration: none;
        font-weight: 800;
    }

    .orders-breadcrumb strong {
        color: #ffffff;
        font-weight: 900;
    }

    .orders-hero-content {
        display: grid;
        grid-template-columns: minmax(0, 1.25fr) minmax(320px, .75fr);
        gap: 24px;
        align-items: center;
    }

    .orders-eyebrow,
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

    .orders-eyebrow {
        background: rgba(255, 255, 255, .16);
        border: 1px solid rgba(255, 255, 255, .28);
        color: #ffffff;
    }

    .orders-eyebrow.light,
    .section-kicker {
        background: var(--orders-green-100);
        color: var(--orders-green-800);
        border-color: transparent;
    }

    .orders-hero-copy h1 {
        margin: 17px 0 12px;
        font-size: clamp(36px, 5vw, 62px);
        line-height: 1.03;
        letter-spacing: -.045em;
    }

    .orders-hero-copy p {
        max-width: 700px;
        margin: 0;
        color: rgba(255, 255, 255, .86);
        font-size: 17px;
        line-height: 1.7;
    }

    .orders-hero-stats {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 18px;
    }

    .orders-hero-stats span {
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

    .orders-hero-card {
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

    .orders-hero-card h2 {
        margin: 0 0 10px;
        font-size: 25px;
        letter-spacing: -.03em;
    }

    .orders-hero-card p {
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

    .orders-shell {
        position: relative;
        z-index: 3;
        margin-top: -64px;
        padding-bottom: 54px;
    }

    .glass-card {
        background: rgba(255, 255, 255, .92);
        border: 1px solid rgba(255, 255, 255, .72);
        border-radius: var(--orders-radius-lg);
        box-shadow: var(--orders-shadow);
        backdrop-filter: blur(16px);
    }

    .orders-top {
        margin-bottom: 22px;
        padding: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
    }

    .orders-top h2,
    .order-card h2,
    .orders-empty-card h2 {
        margin: 10px 0 6px;
        color: var(--orders-green-900);
        letter-spacing: -.03em;
    }

    .orders-top p,
    .order-header p,
    .order-section-title p,
    .order-item p,
    .orders-empty-card p {
        margin: 0;
        color: var(--orders-muted);
        line-height: 1.6;
    }

    .orders-top-total {
        display: grid;
        justify-items: end;
        gap: 4px;
        min-width: 180px;
    }

    .orders-top-total span {
        color: var(--orders-muted);
        font-size: 13px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .orders-top-total strong {
        color: var(--orders-green-800);
        font-size: 28px;
        line-height: 1;
    }

    .orders-list {
        display: grid;
        gap: 22px;
    }

    .order-card {
        padding: 20px;
    }

    .order-header {
        display: flex;
        justify-content: space-between;
        gap: 18px;
        align-items: flex-start;
        border-bottom: 1px solid rgba(47, 125, 61, .10);
        padding-bottom: 16px;
        margin-bottom: 16px;
    }

    .order-title-area {
        display: flex;
        gap: 13px;
        align-items: flex-start;
    }

    .order-icon {
        width: 48px;
        height: 48px;
        display: grid;
        place-items: center;
        border-radius: 17px;
        background: var(--orders-green-100);
        flex: 0 0 auto;
        font-size: 22px;
    }

    .order-title-area h2 {
        margin-top: 0;
        font-size: 28px;
    }

    .order-title-area strong {
        color: var(--orders-green-900);
    }

    .order-status-area {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .order-badge {
        display: inline-flex;
        align-items: center;
        padding: 8px 11px;
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

    .badge-danger {
        background: #ffe8e8;
        color: #9b111e;
    }

    .badge-muted {
        background: #edf1ea;
        color: #526052;
    }

    .order-summary-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 14px;
        margin-bottom: 20px;
    }

    .order-summary-grid div {
        padding: 15px;
        border-radius: 18px;
        background: #fbfdf8;
        border: 1px solid var(--orders-border);
    }

    .order-summary-grid span,
    .order-summary-grid strong {
        display: block;
    }

    .order-summary-grid span {
        color: var(--orders-muted);
        font-size: 12px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: 7px;
    }

    .order-summary-grid strong {
        color: var(--orders-green-900);
        overflow-wrap: anywhere;
    }

    .order-items {
        margin-top: 4px;
    }

    .order-section-title {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 14px;
    }

    .order-section-title > span {
        width: 42px;
        height: 42px;
        display: grid;
        place-items: center;
        border-radius: 15px;
        background: var(--orders-green-100);
        flex: 0 0 auto;
    }

    .order-section-title h3 {
        margin: 0 0 4px;
        color: var(--orders-green-900);
        font-size: 22px;
        letter-spacing: -.02em;
    }

    .order-item-list {
        display: grid;
        gap: 12px;
    }

    .order-item {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        align-items: center;
        padding: 15px;
        border-radius: 20px;
        background: #fbfdf8;
        border: 1px solid var(--orders-border);
        transition: transform .18s ease, box-shadow .18s ease;
    }

    .order-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 16px 34px rgba(31, 79, 43, .10);
    }

    .order-item-main {
        display: flex;
        gap: 12px;
        align-items: center;
        min-width: 0;
    }

    .item-leaf {
        width: 42px;
        height: 42px;
        display: grid;
        place-items: center;
        border-radius: 15px;
        background: var(--orders-green-100);
        flex: 0 0 auto;
    }

    .order-item-main strong {
        display: block;
        color: var(--orders-green-900);
        font-size: 16px;
    }

    .order-item-main p {
        margin-top: 5px;
    }

    .order-item-actions {
        display: grid;
        gap: 8px;
        justify-items: end;
        min-width: 140px;
        text-align: right;
    }

    .order-item-actions strong {
        color: var(--orders-green-800);
        font-size: 18px;
    }

    .small-link {
        color: var(--orders-green-700);
        font-weight: 900;
        text-decoration: none;
        white-space: nowrap;
    }

    .small-link:hover {
        text-decoration: underline;
    }

    .secondary-link {
        font-size: 13px;
    }

    .review-done {
        display: inline-block;
        padding: 7px 10px;
        border-radius: 999px;
        background: #e7f7e8;
        color: #236b2c;
        font-size: 13px;
        font-weight: 900;
        white-space: nowrap;
    }

    .muted-action {
        color: #718071;
        font-size: 14px;
        text-align: right;
        font-weight: 800;
    }

    .shipment-box {
        margin-top: 18px;
        padding: 16px;
        border-radius: 22px;
        background: #fff8df;
        border: 1px solid rgba(242, 191, 77, .35);
    }

    .shipment-title {
        display: flex;
        align-items: center;
        gap: 9px;
        margin-bottom: 13px;
        color: #7a5400;
    }

    .shipment-title span {
        width: 36px;
        height: 36px;
        display: grid;
        place-items: center;
        border-radius: 13px;
        background: #ffffff;
    }

    .shipment-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
    }

    .shipment-grid div {
        padding: 13px;
        border-radius: 16px;
        background: #ffffff;
        border: 1px solid rgba(242, 191, 77, .24);
    }

    .shipment-grid span,
    .shipment-grid strong {
        display: block;
    }

    .shipment-grid span {
        color: #8a6200;
        font-size: 12px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: 6px;
    }

    .shipment-grid strong {
        color: #7a5400;
        overflow-wrap: anywhere;
    }

    .soft-empty {
        padding: 16px;
        border-radius: 18px;
        background: #fbfdf8;
        border: 1px dashed rgba(47, 125, 61, .24);
        color: var(--orders-muted);
        font-weight: 800;
    }

    .orders-empty-card {
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
        background: var(--orders-green-100);
        font-size: 36px;
    }

    .orders-empty-card h2 {
        margin-top: 18px;
        font-size: 34px;
    }

    .orders-empty-card p {
        max-width: 520px;
        margin: 0 auto 22px;
    }

    .empty-actions {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 10px;
    }

    .orders-btn {
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

    .orders-btn:hover {
        transform: translateY(-2px);
    }

    .orders-btn-primary {
        background: linear-gradient(135deg, var(--orders-green-700), var(--orders-green-900));
        color: #ffffff;
        box-shadow: 0 16px 32px rgba(47, 125, 61, .24);
    }

    .orders-btn-light {
        background: var(--orders-green-50);
        color: var(--orders-green-800);
        border: 1px solid var(--orders-border);
    }

    @media (max-width: 1100px) {
        .orders-hero-content {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 900px) {
        .orders-top,
        .order-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .orders-top-total {
            justify-items: start;
        }

        .order-status-area {
            justify-content: flex-start;
        }

        .order-summary-grid,
        .shipment-grid {
            grid-template-columns: 1fr 1fr;
        }

        .order-item {
            align-items: flex-start;
            flex-direction: column;
        }

        .order-item-actions {
            justify-items: start;
            text-align: left;
        }
    }

    @media (max-width: 720px) {
        .orders-hero {
            min-height: 440px;
            padding-top: 24px;
        }

        .orders-hero-inner,
        .orders-shell {
            width: min(100% - 22px, 1180px);
        }

        .orders-breadcrumb {
            font-size: 13px;
            margin-bottom: 24px;
        }

        .orders-hero-copy p {
            font-size: 15px;
        }

        .orders-shell {
            margin-top: -52px;
        }

        .orders-top,
        .order-card,
        .orders-hero-card,
        .orders-empty-card {
            padding: 14px;
            border-radius: 23px;
        }

        .order-title-area {
            flex-direction: column;
        }

        .order-summary-grid,
        .shipment-grid {
            grid-template-columns: 1fr;
        }

        .order-item-main {
            align-items: flex-start;
        }

        .orders-empty-card h2 {
            font-size: 28px;
        }

        .empty-actions .orders-btn {
            width: 100%;
        }
    }
</style>

<?php require APP_PATH . '/Views/layouts/footer.php'; ?>