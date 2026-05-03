<?php

require_once __DIR__ . '/../../app/bootstrap.php';

ProducerMiddleware::handle();

$orderService = new OrderService();
$producerId = (int) currentUserId();

if (!function_exists('producer_orders_json')) {
    function producer_orders_json(array $payload): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

if (!function_exists('producer_order_unit_label')) {
    function producer_order_unit_label(string $unit): string
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

if (!function_exists('producer_orders_money')) {
    function producer_orders_money(float $amount): string
    {
        if (function_exists('formatMoney')) {
            return formatMoney($amount);
        }

        return number_format($amount, 2, ',', '.') . ' TL';
    }
}

if (!function_exists('producer_orders_date')) {
    function producer_orders_date(?string $date): string
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

if (!function_exists('producer_orders_status_value')) {
    function producer_orders_status_value(string $constantName, string $fallback): string
    {
        return defined($constantName) ? constant($constantName) : $fallback;
    }
}

if (!function_exists('producer_orders_status_options')) {
    function producer_orders_status_options(): array
    {
        return [
            producer_orders_status_value('ORDER_STATUS_PENDING', 'pending') => 'Sipariş Alındı',
            producer_orders_status_value('ORDER_STATUS_CONFIRMED', 'confirmed') => 'Onaylandı',
            producer_orders_status_value('ORDER_STATUS_PREPARING', 'preparing') => 'Hazırlanıyor',
            producer_orders_status_value('ORDER_STATUS_SHIPPED', 'shipped') => 'Kargoya Verildi',
            producer_orders_status_value('ORDER_STATUS_DELIVERED', 'delivered') => 'Teslim Edildi',
            producer_orders_status_value('ORDER_STATUS_CANCELLED', 'cancelled') => 'İptal Edildi',
        ];
    }
}

if (!function_exists('producer_orders_status_label')) {
    function producer_orders_status_label(OrderService $orderService, array $order, string $status): string
    {
        if (!empty($order['order_status_label'])) {
            return $order['order_status_label'];
        }

        if (method_exists($orderService, 'statusLabel')) {
            return $orderService->statusLabel($status);
        }

        return producer_orders_status_options()[$status] ?? ($status ?: 'Durum Yok');
    }
}

if (!function_exists('producer_orders_payment_label')) {
    function producer_orders_payment_label(OrderService $orderService, array $order, string $status): string
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

if (!function_exists('producer_orders_status_class')) {
    function producer_orders_status_class(OrderService $orderService, string $status): string
    {
        if (method_exists($orderService, 'statusBadgeClass')) {
            return $orderService->statusBadgeClass($status);
        }

        return match ($status) {
            producer_orders_status_value('ORDER_STATUS_DELIVERED', 'delivered') => 'badge-success',
            producer_orders_status_value('ORDER_STATUS_SHIPPED', 'shipped') => 'badge-info',
            producer_orders_status_value('ORDER_STATUS_CANCELLED', 'cancelled') => 'badge-danger',
            producer_orders_status_value('ORDER_STATUS_PREPARING', 'preparing') => 'badge-warning',
            default => 'badge-muted',
        };
    }
}

if (!function_exists('producer_orders_payment_class')) {
    function producer_orders_payment_class(OrderService $orderService, string $status): string
    {
        if (method_exists($orderService, 'paymentBadgeClass')) {
            return $orderService->paymentBadgeClass($status);
        }

        return match ($status) {
            'paid', 'completed', 'success' => 'badge-success',
            'pending' => 'badge-warning',
            'failed', 'cancelled', 'canceled' => 'badge-danger',
            default => 'badge-muted',
        };
    }
}

if (!function_exists('producer_orders_stats')) {
    function producer_orders_stats(array $orders): array
    {
        $delivered = producer_orders_status_value('ORDER_STATUS_DELIVERED', 'delivered');
        $cancelled = producer_orders_status_value('ORDER_STATUS_CANCELLED', 'cancelled');
        $shipped = producer_orders_status_value('ORDER_STATUS_SHIPPED', 'shipped');

        $stats = [
            'total' => count($orders),
            'active' => 0,
            'shipped' => 0,
            'delivered' => 0,
            'cancelled' => 0,
            'revenue' => 0,
        ];

        foreach ($orders as $order) {
            $status = (string) ($order['order_status'] ?? '');

            $stats['revenue'] += (float) ($order['total_amount'] ?? 0);

            if ($status === $delivered) {
                $stats['delivered']++;
            } elseif ($status === $cancelled || $status === 'canceled') {
                $stats['cancelled']++;
            } else {
                $stats['active']++;
            }

            if ($status === $shipped) {
                $stats['shipped']++;
            }
        }

        return $stats;
    }
}

if (!function_exists('producer_orders_render_hero_stats')) {
    function producer_orders_render_hero_stats(array $orders): string
    {
        if (empty($orders)) {
            return '';
        }

        $stats = producer_orders_stats($orders);

        ob_start();
        ?>
        <div class="producer-orders-hero-stats">
            <span>📦 <?= e((string) $stats['total']) ?> sipariş</span>
            <span>🚚 <?= e((string) $stats['active']) ?> aktif</span>
            <span>✅ <?= e((string) $stats['delivered']) ?> teslim</span>
            <span>💰 <?= e(producer_orders_money((float) $stats['revenue'])) ?></span>
        </div>
        <?php

        return ob_get_clean();
    }
}

if (!function_exists('producer_orders_render_dynamic_area')) {
    function producer_orders_render_dynamic_area(array $orders, OrderService $orderService): string
    {
        $stats = producer_orders_stats($orders);
        $statusOptions = producer_orders_status_options();

        ob_start();
        ?>

        <?php if (empty($orders)): ?>
            <section class="producer-orders-empty-card glass-card">
                <div class="empty-icon">📦</div>

                <span class="producer-orders-eyebrow light">Henüz Sipariş Yok</span>

                <h2>Henüz gelen sipariş yok.</h2>

                <p>
                    Ürünlerin satın alındığında siparişler bu sayfada listelenecek.
                </p>

                <div class="empty-actions">
                    <a class="producer-orders-btn producer-orders-btn-primary" href="<?= e(url('producer/products.php')) ?>">
                        Ürünlerime Git
                    </a>

                    <a class="producer-orders-btn producer-orders-btn-light" href="<?= e(url('producer/dashboard.php')) ?>">
                        Panele Dön
                    </a>
                </div>
            </section>
        <?php else: ?>
            <section class="producer-orders-top glass-card">
                <div>
                    <span class="section-kicker">Sipariş Özeti</span>

                    <h2>Gelen siparişler</h2>

                    <p>
                        Tüketicilerden gelen siparişleri görüntüleyebilir ve sipariş durumlarını AJAX ile güncelleyebilirsin.
                    </p>
                </div>

                <div class="producer-orders-total">
                    <span>Toplam Sipariş Tutarı</span>
                    <strong><?= e(producer_orders_money((float) $stats['revenue'])) ?></strong>
                </div>
            </section>

            <section class="producer-orders-summary-grid">
                <article class="producer-order-stat-card glass-card">
                    <span>📦</span>

                    <div>
                        <strong><?= e((string) $stats['total']) ?></strong>
                        <p>Toplam Sipariş</p>
                    </div>
                </article>

                <article class="producer-order-stat-card glass-card">
                    <span>🚚</span>

                    <div>
                        <strong><?= e((string) $stats['active']) ?></strong>
                        <p>Aktif Sipariş</p>
                    </div>
                </article>

                <article class="producer-order-stat-card glass-card">
                    <span>✅</span>

                    <div>
                        <strong><?= e((string) $stats['delivered']) ?></strong>
                        <p>Teslim Edilen</p>
                    </div>
                </article>

                <article class="producer-order-stat-card glass-card">
                    <span>❌</span>

                    <div>
                        <strong><?= e((string) $stats['cancelled']) ?></strong>
                        <p>İptal Edilen</p>
                    </div>
                </article>
            </section>

            <section class="producer-orders-control-card glass-card">
                <div class="producer-orders-filter-row">
                    <div class="producer-orders-search-box">
                        <span>🔎</span>

                        <input
                            type="search"
                            id="producer-order-search"
                            placeholder="Sipariş no veya tüketici adı ara..."
                            autocomplete="off"
                        >
                    </div>

                    <select id="producer-order-status-filter">
                        <option value="">Tüm durumlar</option>

                        <?php foreach ($statusOptions as $statusValue => $statusLabel): ?>
                            <option value="<?= e($statusValue) ?>">
                                <?= e($statusLabel) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </section>

            <section class="producer-orders-list" id="producer-orders-list">
                <?php foreach ($orders as $order): ?>
                    <?php
                        $items = $order['items'] ?? [];
                        $shipment = $order['shipment'] ?? null;

                        $orderStatus = (string) ($order['order_status'] ?? '');
                        $paymentStatus = (string) ($order['payment_status'] ?? '');

                        $orderId = (int) ($order['id'] ?? 0);
                        $orderNo = $order['order_no'] ?? 'Sipariş';
                        $consumerName = $order['consumer_name'] ?? 'Tüketici';

                        $trackingNo = $order['tracking_no']
                            ?? ($shipment['tracking_no'] ?? '-');

                        $searchText = strtolower(trim($orderNo . ' ' . $consumerName));
                    ?>

                    <article
                        class="producer-order-card glass-card"
                        data-order-card="<?= e((string) $orderId) ?>"
                        data-status="<?= e($orderStatus) ?>"
                        data-search="<?= e($searchText) ?>"
                    >
                        <div class="producer-order-header">
                            <div class="producer-order-title-area">
                                <span class="producer-order-icon">📦</span>

                                <div>
                                    <h2><?= e($orderNo) ?></h2>

                                    <p>
                                        Tüketici:
                                        <strong><?= e($consumerName) ?></strong>
                                    </p>

                                    <p>
                                        Tarih:
                                        <?= e(producer_orders_date($order['created_at'] ?? null)) ?>
                                    </p>
                                </div>
                            </div>

                            <div class="producer-order-status-area">
                                <span class="producer-order-badge <?= e(producer_orders_status_class($orderService, $orderStatus)) ?>">
                                    <?= e(producer_orders_status_label($orderService, $order, $orderStatus)) ?>
                                </span>

                                <span class="producer-order-badge <?= e(producer_orders_payment_class($orderService, $paymentStatus)) ?>">
                                    <?= e(producer_orders_payment_label($orderService, $order, $paymentStatus)) ?>
                                </span>
                            </div>
                        </div>

                        <div class="producer-order-summary-grid">
                            <div>
                                <span>Toplam</span>
                                <strong><?= e(producer_orders_money((float) ($order['total_amount'] ?? 0))) ?></strong>
                            </div>

                            <div>
                                <span>Ara Toplam</span>
                                <strong><?= e(producer_orders_money((float) ($order['subtotal'] ?? 0))) ?></strong>
                            </div>

                            <div>
                                <span>Kargo</span>
                                <strong><?= e(producer_orders_money((float) ($order['shipping_fee'] ?? 0))) ?></strong>
                            </div>

                            <div>
                                <span>Takip No</span>
                                <strong><?= e($trackingNo ?: '-') ?></strong>
                            </div>
                        </div>

                        <div class="producer-status-update-box">
                            <form
                                method="POST"
                                action="<?= e(url('producer/orders.php')) ?>"
                                class="producer-status-form"
                                data-order-ajax="true"
                            >
                                <?= csrf_field() ?>

                                <input type="hidden" name="order_id" value="<?= e((string) $orderId) ?>">

                                <div class="status-form-field">
                                    <label for="order_status_<?= e((string) $orderId) ?>">
                                        Sipariş Durumu
                                    </label>

                                    <select id="order_status_<?= e((string) $orderId) ?>" name="order_status">
                                        <?php foreach ($statusOptions as $statusValue => $statusLabel): ?>
                                            <option
                                                value="<?= e($statusValue) ?>"
                                                <?= $orderStatus === $statusValue ? 'selected' : '' ?>
                                            >
                                                <?= e($statusLabel) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <button class="producer-orders-btn producer-orders-btn-primary" type="submit">
                                    Durumu Güncelle
                                </button>
                            </form>
                        </div>

                        <div class="producer-order-items">
                            <div class="order-section-title">
                                <span>🧺</span>

                                <div>
                                    <h3>Ürünler</h3>
                                    <p>Bu siparişte yer alan ürün kalemleri.</p>
                                </div>
                            </div>

                            <?php if (empty($items)): ?>
                                <div class="soft-empty">
                                    Bu sipariş için ürün kalemi bulunamadı.
                                </div>
                            <?php else: ?>
                                <div class="producer-order-item-list">
                                    <?php foreach ($items as $item): ?>
                                        <?php
                                            $unit = producer_order_unit_label($item['unit_type_snapshot'] ?? 'kg');
                                            $quantity = (float) ($item['quantity'] ?? 0);
                                            $unitPrice = (float) ($item['unit_price'] ?? 0);
                                            $totalPrice = (float) ($item['total_price'] ?? ($quantity * $unitPrice));
                                        ?>

                                        <div class="producer-order-item">
                                            <div class="producer-order-item-main">
                                                <span class="item-leaf">🌱</span>

                                                <div>
                                                    <strong><?= e($item['product_title_snapshot'] ?? 'Ürün') ?></strong>

                                                    <p>
                                                        <?= e((string) $quantity) ?>
                                                        <?= e($unit) ?>
                                                        ·
                                                        <?= e(producer_orders_money($unitPrice)) ?>
                                                        /
                                                        <?= e($unit) ?>
                                                    </p>
                                                </div>
                                            </div>

                                            <strong><?= e(producer_orders_money($totalPrice)) ?></strong>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($shipment): ?>
                            <div class="producer-shipment-box">
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

            <div class="producer-orders-no-result" id="producer-orders-no-result" hidden>
                <span>🔎</span>
                <strong>Aramana uygun sipariş bulunamadı.</strong>
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

    $orderId = (int) ($_POST['order_id'] ?? 0);
    $status = trim((string) ($_POST['order_status'] ?? ''));

    $result = $orderService->updateStatus($producerId, $orderId, $status);
    $orders = $orderService->getProducerOrders($producerId);

    if ($isAjax) {
        producer_orders_json([
            'success' => (bool) ($result['success'] ?? false),
            'message' => $result['message'] ?? '',
            'html' => producer_orders_render_dynamic_area($orders, $orderService),
            'heroStatsHtml' => producer_orders_render_hero_stats($orders),
        ]);
    }

    if ($result['success']) {
        flash_success($result['message']);
    } else {
        flash_error($result['message']);
    }

    redirect('producer/orders.php');
}

$orders = $orderService->getProducerOrders($producerId);

$pageTitle = 'Gelen Siparişler';
$bodyClass = 'page-producer-orders';

require APP_PATH . '/Views/layouts/header.php';
?>

<main class="producer-orders-page">
    <section class="producer-orders-hero">
        <div class="producer-orders-hero-bg orders-blob-one"></div>
        <div class="producer-orders-hero-bg orders-blob-two"></div>

        <div class="producer-orders-hero-inner">
            <nav class="producer-orders-breadcrumb" aria-label="Sayfa yolu">
                <a href="<?= e(url('index.php')) ?>">Ana Sayfa</a>
                <span>/</span>
                <a href="<?= e(url('producer/dashboard.php')) ?>">Üretici Paneli</a>
                <span>/</span>
                <strong>Gelen Siparişler</strong>
            </nav>

            <div class="producer-orders-hero-content">
                <div class="producer-orders-hero-copy">
                    <span class="producer-orders-eyebrow">
                        Sipariş Yönetimi
                    </span>

                    <h1>Gelen Siparişler</h1>

                    <p>
                        Tüketicilerden gelen siparişleri buradan görüntüleyebilir, ürün kalemlerini inceleyebilir
                        ve sipariş durumlarını sayfa yenilenmeden güncelleyebilirsin.
                    </p>

                    <div id="producer-orders-hero-stats-wrap">
                        <?= producer_orders_render_hero_stats($orders) ?>
                    </div>
                </div>

                <div class="producer-orders-hero-card">
                    <div class="hero-card-icon">📦</div>

                    <h2>Siparişleri düzenli takip et</h2>

                    <p>
                        Hazırlık, kargo ve teslimat aşamalarını güncel tutarak tüketicinin süreci net görmesini sağla.
                    </p>

                    <div class="hero-mini-list">
                        <span>🧺 Ürün kalemleri</span>
                        <span>🚚 Kargo takibi</span>
                        <span>✅ Durum güncelleme</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="producer-orders-shell">
        <div id="producer-orders-message" class="producer-orders-message" hidden></div>

        <div id="producer-orders-dynamic-area">
            <?= producer_orders_render_dynamic_area($orders, $orderService) ?>
        </div>
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

    body.page-producer-orders {
        background:
            radial-gradient(circle at 14% 12%, rgba(196, 231, 177, .48), transparent 28%),
            radial-gradient(circle at 88% 16%, rgba(242, 191, 77, .16), transparent 24%),
            linear-gradient(180deg, #f8fbf2 0%, #f3f8ed 48%, #ffffff 100%);
    }

    .producer-orders-page {
        overflow: hidden;
    }

    .producer-orders-hero {
        position: relative;
        min-height: 390px;
        padding: 34px 18px 94px;
        background:
            radial-gradient(circle at 82% 18%, rgba(242, 191, 77, .30), transparent 26%),
            radial-gradient(circle at 12% 78%, rgba(255, 255, 255, .16), transparent 24%),
            linear-gradient(135deg, rgba(36, 92, 47, .97), rgba(47, 125, 61, .87));
        color: #ffffff;
    }

    .producer-orders-hero::after {
        content: '';
        position: absolute;
        inset: auto 0 -1px;
        height: 90px;
        background: linear-gradient(180deg, rgba(246, 251, 244, 0), #f6fbf4 82%);
        pointer-events: none;
    }

    .producer-orders-hero-inner,
    .producer-orders-shell {
        width: min(1180px, calc(100% - 32px));
        margin: 0 auto;
    }

    .producer-orders-hero-inner {
        position: relative;
        z-index: 2;
    }

    .producer-orders-hero-bg {
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

    .producer-orders-breadcrumb {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 9px;
        font-size: 14px;
        margin-bottom: 32px;
        color: rgba(255, 255, 255, .76);
    }

    .producer-orders-breadcrumb a {
        color: #ffffff;
        text-decoration: none;
        font-weight: 800;
    }

    .producer-orders-breadcrumb strong {
        color: #ffffff;
        font-weight: 900;
    }

    .producer-orders-hero-content {
        display: grid;
        grid-template-columns: minmax(0, 1.25fr) minmax(320px, .75fr);
        gap: 24px;
        align-items: center;
    }

    .producer-orders-eyebrow,
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

    .producer-orders-eyebrow {
        background: rgba(255, 255, 255, .16);
        border: 1px solid rgba(255, 255, 255, .28);
        color: #ffffff;
    }

    .producer-orders-eyebrow.light,
    .section-kicker {
        background: var(--orders-green-100);
        color: var(--orders-green-800);
        border-color: transparent;
    }

    .producer-orders-hero-copy h1 {
        margin: 17px 0 12px;
        font-size: clamp(36px, 5vw, 62px);
        line-height: 1.03;
        letter-spacing: -.045em;
    }

    .producer-orders-hero-copy p {
        max-width: 700px;
        margin: 0;
        color: rgba(255, 255, 255, .86);
        font-size: 17px;
        line-height: 1.7;
    }

    .producer-orders-hero-stats {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 18px;
    }

    .producer-orders-hero-stats span {
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

    .producer-orders-hero-card {
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

    .producer-orders-hero-card h2 {
        margin: 0 0 10px;
        font-size: 25px;
        letter-spacing: -.03em;
    }

    .producer-orders-hero-card p {
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

    .producer-orders-shell {
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

    .producer-orders-message {
        margin-bottom: 16px;
        padding: 14px 16px;
        border-radius: 18px;
        font-weight: 900;
        box-shadow: 0 12px 30px rgba(31, 79, 43, .10);
    }

    .producer-orders-message.success {
        background: #e7f7e8;
        color: #236b2c;
        border: 1px solid rgba(35, 107, 44, .14);
    }

    .producer-orders-message.error {
        background: #fdeaea;
        color: var(--orders-red);
        border: 1px solid rgba(155, 17, 30, .14);
    }

    .producer-orders-top {
        margin-bottom: 18px;
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 18px;
    }

    .producer-orders-top h2,
    .producer-orders-empty-card h2 {
        margin: 10px 0 6px;
        color: var(--orders-green-900);
        letter-spacing: -.03em;
    }

    .producer-orders-top p,
    .producer-orders-empty-card p {
        margin: 0;
        color: var(--orders-muted);
        line-height: 1.6;
    }

    .producer-orders-total {
        display: grid;
        justify-items: end;
        gap: 4px;
        min-width: 200px;
    }

    .producer-orders-total span {
        color: var(--orders-muted);
        font-size: 13px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .producer-orders-total strong {
        color: var(--orders-green-800);
        font-size: 28px;
        line-height: 1;
    }

    .producer-orders-summary-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
        margin-bottom: 22px;
    }

    .producer-order-stat-card {
        display: flex;
        align-items: center;
        gap: 13px;
        padding: 18px;
    }

    .producer-order-stat-card > span,
    .producer-order-icon,
    .order-section-title > span {
        width: 48px;
        height: 48px;
        display: grid;
        place-items: center;
        border-radius: 17px;
        background: var(--orders-green-100);
        flex: 0 0 auto;
        font-size: 22px;
    }

    .producer-order-stat-card strong {
        display: block;
        color: var(--orders-green-900);
        font-size: 21px;
    }

    .producer-order-stat-card p {
        margin: 4px 0 0;
        color: var(--orders-muted);
        font-size: 13px;
        font-weight: 800;
    }

    .producer-orders-control-card {
        padding: 16px;
        margin-bottom: 22px;
    }

    .producer-orders-filter-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 240px;
        gap: 12px;
        align-items: center;
    }

    .producer-orders-search-box {
        display: grid;
        grid-template-columns: 42px minmax(0, 1fr);
        align-items: center;
        gap: 8px;
        padding: 7px;
        border-radius: 17px;
        background: var(--orders-green-50);
        border: 1px solid var(--orders-border);
    }

    .producer-orders-search-box span {
        width: 42px;
        height: 42px;
        display: grid;
        place-items: center;
        border-radius: 14px;
        background: #ffffff;
    }

    .producer-orders-search-box input,
    .producer-orders-filter-row select {
        width: 100%;
        border: 1px solid rgba(47, 125, 61, .18);
        border-radius: 14px;
        padding: 13px 14px;
        font: inherit;
        font-weight: 800;
        outline: none;
        background: #ffffff;
        color: var(--orders-text);
        box-sizing: border-box;
    }

    .producer-orders-search-box input {
        border: 0;
    }

    .producer-orders-list {
        display: grid;
        gap: 22px;
    }

    .producer-order-card {
        padding: 20px;
        border-left: 6px solid var(--orders-green-700);
        transition: transform .18s ease, box-shadow .18s ease, opacity .18s ease;
    }

    .producer-order-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 26px 60px rgba(31, 79, 43, .14);
    }

    .producer-order-card.is-hidden {
        display: none;
    }

    .producer-order-card.is-loading {
        opacity: .65;
        pointer-events: none;
    }

    .producer-order-header {
        display: flex;
        justify-content: space-between;
        gap: 18px;
        align-items: flex-start;
        border-bottom: 1px solid rgba(47, 125, 61, .10);
        padding-bottom: 16px;
        margin-bottom: 16px;
    }

    .producer-order-title-area {
        display: flex;
        gap: 13px;
        align-items: flex-start;
    }

    .producer-order-title-area h2 {
        margin: 0 0 6px;
        color: var(--orders-green-900);
        font-size: 28px;
        letter-spacing: -.03em;
    }

    .producer-order-title-area p {
        margin: 0;
        color: var(--orders-muted);
        line-height: 1.6;
    }

    .producer-order-title-area strong {
        color: var(--orders-green-900);
    }

    .producer-order-status-area {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .producer-order-badge {
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

    .producer-order-summary-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 14px;
        margin-bottom: 20px;
    }

    .producer-order-summary-grid div {
        padding: 15px;
        border-radius: 18px;
        background: #fbfdf8;
        border: 1px solid var(--orders-border);
    }

    .producer-order-summary-grid span,
    .producer-order-summary-grid strong {
        display: block;
    }

    .producer-order-summary-grid span {
        color: var(--orders-muted);
        font-size: 12px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: 7px;
    }

    .producer-order-summary-grid strong {
        color: var(--orders-green-900);
        overflow-wrap: anywhere;
    }

    .producer-status-update-box {
        padding: 16px;
        border-radius: 22px;
        background: var(--orders-green-50);
        border: 1px solid var(--orders-border);
        margin-bottom: 20px;
    }

    .producer-status-form {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 12px;
        align-items: end;
    }

    .status-form-field {
        display: grid;
        gap: 8px;
    }

    .status-form-field label {
        color: var(--orders-green-900);
        font-weight: 900;
    }

    .status-form-field select {
        width: 100%;
        padding: 13px 14px;
        border: 1px solid rgba(47, 125, 61, .18);
        border-radius: 15px;
        background: #ffffff;
        color: var(--orders-text);
        font: inherit;
        font-weight: 800;
        outline: none;
    }

    .producer-order-items {
        margin-top: 4px;
    }

    .order-section-title {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 14px;
    }

    .order-section-title h3 {
        margin: 0 0 4px;
        color: var(--orders-green-900);
        font-size: 22px;
        letter-spacing: -.02em;
    }

    .order-section-title p {
        margin: 0;
        color: var(--orders-muted);
        line-height: 1.6;
    }

    .producer-order-item-list {
        display: grid;
        gap: 12px;
    }

    .producer-order-item {
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

    .producer-order-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 16px 34px rgba(31, 79, 43, .10);
    }

    .producer-order-item-main {
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

    .producer-order-item-main strong,
    .producer-order-item > strong {
        color: var(--orders-green-900);
    }

    .producer-order-item-main p {
        margin: 5px 0 0;
        color: var(--orders-muted);
        line-height: 1.5;
    }

    .producer-shipment-box {
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

    .producer-orders-empty-card {
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

    .producer-orders-empty-card h2 {
        margin-top: 18px;
        font-size: 34px;
    }

    .producer-orders-empty-card p {
        max-width: 520px;
        margin: 0 auto 22px;
    }

    .empty-actions {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 10px;
    }

    .producer-orders-no-result {
        margin-top: 18px;
        padding: 28px;
        border-radius: 24px;
        background: #ffffff;
        border: 1px dashed rgba(47, 125, 61, .24);
        text-align: center;
        color: var(--orders-muted);
    }

    .producer-orders-no-result span {
        display: block;
        font-size: 34px;
        margin-bottom: 8px;
    }

    .producer-orders-no-result strong {
        display: block;
        color: var(--orders-green-900);
        font-size: 20px;
        margin-bottom: 5px;
    }

    .producer-orders-no-result p {
        margin: 0;
    }

    .producer-orders-btn {
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

    .producer-orders-btn:hover {
        transform: translateY(-2px);
    }

    .producer-orders-btn-primary {
        background: linear-gradient(135deg, var(--orders-green-700), var(--orders-green-900));
        color: #ffffff;
        box-shadow: 0 16px 32px rgba(47, 125, 61, .24);
    }

    .producer-orders-btn-light {
        background: var(--orders-green-50);
        color: var(--orders-green-800);
        border: 1px solid var(--orders-border);
    }

    .orders-loading {
        pointer-events: none;
        opacity: .72;
    }

    @media (max-width: 1100px) {
        .producer-orders-hero-content {
            grid-template-columns: 1fr;
        }

        .producer-orders-summary-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 900px) {
        .producer-orders-top,
        .producer-order-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .producer-orders-total {
            justify-items: start;
        }

        .producer-order-status-area {
            justify-content: flex-start;
        }

        .producer-order-summary-grid,
        .shipment-grid {
            grid-template-columns: 1fr 1fr;
        }

        .producer-status-form {
            grid-template-columns: 1fr;
        }

        .producer-order-item {
            align-items: flex-start;
            flex-direction: column;
        }
    }

    @media (max-width: 720px) {
        .producer-orders-hero {
            min-height: 430px;
            padding-top: 24px;
        }

        .producer-orders-hero-inner,
        .producer-orders-shell {
            width: min(100% - 22px, 1180px);
        }

        .producer-orders-breadcrumb {
            font-size: 13px;
            margin-bottom: 24px;
        }

        .producer-orders-hero-copy p {
            font-size: 15px;
        }

        .producer-orders-shell {
            margin-top: -52px;
        }

        .producer-orders-top,
        .producer-order-card,
        .producer-orders-hero-card,
        .producer-orders-empty-card,
        .producer-order-stat-card,
        .producer-orders-control-card {
            padding: 14px;
            border-radius: 23px;
        }

        .producer-orders-summary-grid,
        .producer-order-summary-grid,
        .shipment-grid,
        .producer-orders-filter-row {
            grid-template-columns: 1fr;
        }

        .producer-order-title-area {
            flex-direction: column;
        }

        .producer-order-item-main {
            align-items: flex-start;
        }

        .producer-orders-empty-card h2 {
            font-size: 28px;
        }

        .empty-actions .producer-orders-btn,
        .producer-orders-top .producer-orders-btn,
        .producer-status-form .producer-orders-btn {
            width: 100%;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const dynamicArea = document.getElementById('producer-orders-dynamic-area');
        const heroStatsWrap = document.getElementById('producer-orders-hero-stats-wrap');
        const messageBox = document.getElementById('producer-orders-message');

        function showOrdersMessage(type, message) {
            if (!messageBox) {
                return;
            }

            messageBox.hidden = false;
            messageBox.className = 'producer-orders-message ' + type;
            messageBox.textContent = message || '';

            window.setTimeout(function () {
                messageBox.hidden = true;
                messageBox.textContent = '';
                messageBox.className = 'producer-orders-message';
            }, 2600);
        }

        function applyOrderFilters() {
            const searchInput = document.getElementById('producer-order-search');
            const statusFilter = document.getElementById('producer-order-status-filter');
            const cards = document.querySelectorAll('[data-order-card]');
            const noResult = document.getElementById('producer-orders-no-result');

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

        async function submitOrderForm(form) {
            if (!form || form.dataset.loading === 'true') {
                return;
            }

            const card = form.closest('[data-order-card]');
            const buttons = form.querySelectorAll('button');

            form.dataset.loading = 'true';

            if (card) {
                card.classList.add('is-loading');
            }

            if (dynamicArea) {
                dynamicArea.classList.add('orders-loading');
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
                    throw new Error(result.message || 'Sipariş durumu güncellenemedi.');
                }

                if (dynamicArea && typeof result.html === 'string') {
                    dynamicArea.innerHTML = result.html;
                }

                if (heroStatsWrap && typeof result.heroStatsHtml === 'string') {
                    heroStatsWrap.innerHTML = result.heroStatsHtml;
                }

                showOrdersMessage('success', result.message || 'Sipariş durumu güncellendi.');
                applyOrderFilters();
            } catch (error) {
                showOrdersMessage('error', error.message || 'Sipariş güncellenirken bir hata oluştu.');

                if (card) {
                    card.classList.remove('is-loading');
                }
            } finally {
                if (dynamicArea) {
                    dynamicArea.classList.remove('orders-loading');
                }

                buttons.forEach(function (button) {
                    button.disabled = false;
                });

                form.dataset.loading = 'false';
            }
        }

        document.addEventListener('submit', function (event) {
            const form = event.target.closest('form[data-order-ajax="true"]');

            if (!form) {
                return;
            }

            event.preventDefault();
            submitOrderForm(form);
        });

        document.addEventListener('input', function (event) {
            if (event.target && event.target.id === 'producer-order-search') {
                applyOrderFilters();
            }
        });

        document.addEventListener('change', function (event) {
            if (event.target && event.target.id === 'producer-order-status-filter') {
                applyOrderFilters();
            }
        });
    });
</script>

<?php require APP_PATH . '/Views/layouts/footer.php'; ?>