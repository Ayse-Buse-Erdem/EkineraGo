<?php

require_once __DIR__ . '/../../app/bootstrap.php';

ProducerMiddleware::handle();

$userId = (int) currentUserId();
$isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';

if (!function_exists('producer_notification_json')) {
    function producer_notification_json(array $payload): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

if (!function_exists('producer_notification_data_array')) {
    function producer_notification_data_array(array $notification): array
    {
        $json = $notification['data_json'] ?? null;

        if (!$json) {
            return [];
        }

        if (is_array($json)) {
            return $json;
        }

        $decoded = json_decode((string) $json, true);

        return is_array($decoded) ? $decoded : [];
    }
}

if (!function_exists('producer_notification_safe_target')) {
    function producer_notification_safe_target(string $target): string
    {
        $target = trim($target);

        if ($target === '') {
            return 'producer/notifications.php';
        }

        if (
            str_starts_with($target, 'http://') ||
            str_starts_with($target, 'https://') ||
            str_starts_with($target, '//') ||
            str_contains($target, '\\')
        ) {
            return 'producer/notifications.php';
        }

        $target = ltrim($target, '/');

        return $target !== '' ? $target : 'producer/notifications.php';
    }
}

if (!function_exists('producer_notification_target_path')) {
    function producer_notification_target_path(array $notification): string
    {
        $data = producer_notification_data_array($notification);
        $type = (string) ($notification['type'] ?? '');

        if (!empty($data['url'])) {
            return producer_notification_safe_target((string) $data['url']);
        }

        if ($type === 'new_product_question' && !empty($data['question_id'])) {
            return 'producer/questions.php?question_id=' . (int) $data['question_id'];
        }

        if ($type === 'new_review' && !empty($data['product_id'])) {
            return 'product-detail.php?id=' . (int) $data['product_id'] . '#reviews';
        }

        if (!empty($data['product_id'])) {
            return 'product-detail.php?id=' . (int) $data['product_id'];
        }

        if (!empty($data['order_id']) || str_contains($type, 'order')) {
            return 'producer/orders.php';
        }

        return 'producer/notifications.php';
    }
}

if (!function_exists('producer_notification_type_label')) {
    function producer_notification_type_label(string $type): string
    {
        return match ($type) {
            'new_order' => 'Yeni Sipariş',
            'order_created' => 'Sipariş',
            'order_status_changed' => 'Sipariş Durumu',
            'new_review' => 'Yeni Yorum',
            'new_product_question' => 'Ürün Sorusu',
            'product_question_answered' => 'Soru Cevabı',
            'restock_alert' => 'Stok Bildirimi',
            'favorite_product_updated' => 'Favori Ürün',
            default => 'Bildirim',
        };
    }
}

if (!function_exists('producer_notification_badge_class')) {
    function producer_notification_badge_class(string $type): string
    {
        return match ($type) {
            'new_order', 'order_created', 'new_review' => 'badge-success',
            'order_status_changed', 'new_product_question', 'product_question_answered' => 'badge-info',
            'restock_alert' => 'badge-warning',
            default => 'badge-muted',
        };
    }
}

if (!function_exists('producer_notification_icon')) {
    function producer_notification_icon(string $type): string
    {
        return match ($type) {
            'new_order', 'order_created', 'order_status_changed' => '📦',
            'new_review' => '⭐',
            'new_product_question', 'product_question_answered' => '❔',
            'restock_alert' => '🌱',
            'favorite_product_updated' => '♥',
            default => '🔔',
        };
    }
}

if (!function_exists('producer_notification_date')) {
    function producer_notification_date(?string $date): string
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

if (!function_exists('producer_notification_stats')) {
    function producer_notification_stats(array $notifications): array
    {
        $stats = [
            'total' => count($notifications),
            'unread' => 0,
            'orders' => 0,
            'questions' => 0,
            'reviews' => 0,
        ];

        foreach ($notifications as $notification) {
            $type = (string) ($notification['type'] ?? '');

            if (empty($notification['is_read'])) {
                $stats['unread']++;
            }

            if (str_contains($type, 'order')) {
                $stats['orders']++;
            }

            if (str_contains($type, 'question')) {
                $stats['questions']++;
            }

            if (str_contains($type, 'review')) {
                $stats['reviews']++;
            }
        }

        return $stats;
    }
}

if (!function_exists('producer_notification_render_hero_stats')) {
    function producer_notification_render_hero_stats(array $notifications, int $unreadCount): string
    {
        if (empty($notifications)) {
            return '';
        }

        $stats = producer_notification_stats($notifications);

        ob_start();
        ?>
        <div class="producer-notifications-hero-stats">
            <span>🔔 <?= e((string) $stats['total']) ?> bildirim</span>
            <span>🟢 <?= e((string) $unreadCount) ?> okunmamış</span>
            <span>📦 <?= e((string) $stats['orders']) ?> sipariş</span>
            <span>❔ <?= e((string) $stats['questions']) ?> soru</span>
        </div>
        <?php

        return ob_get_clean();
    }
}

if (!function_exists('producer_notification_render_dynamic_area')) {
    function producer_notification_render_dynamic_area(array $notifications, int $unreadCount): string
    {
        $stats = producer_notification_stats($notifications);

        ob_start();
        ?>

        <?php if (empty($notifications)): ?>
            <section class="producer-notifications-empty-card glass-card">
                <div class="empty-icon">🔔</div>

                <span class="producer-notifications-eyebrow light">Bildirim Yok</span>

                <h2>Henüz bildirimin yok.</h2>

                <p>
                    Ürünlerine soru sorulduğunda, yorum geldiğinde veya yeni sipariş oluştuğunda
                    bildirimlerin burada görünecek.
                </p>

                <div class="empty-actions">
                    <a class="producer-notifications-btn producer-notifications-btn-primary" href="<?= e(url('producer/products.php')) ?>">
                        Ürünlerime Git
                    </a>

                    <a class="producer-notifications-btn producer-notifications-btn-light" href="<?= e(url('producer/dashboard.php')) ?>">
                        Panele Dön
                    </a>
                </div>
            </section>
        <?php else: ?>
            <section class="producer-notifications-top glass-card">
                <div>
                    <span class="section-kicker">Bildirim Özeti</span>

                    <h2>Üretici bildirimlerin</h2>

                    <p>
                        Yeni siparişleri, ürün sorularını, yorumları ve sistem mesajlarını buradan takip edebilirsin.
                    </p>
                </div>

                <div class="producer-notifications-top-actions">
                    <div class="unread-counter">
                        <span>Okunmamış</span>
                        <strong><?= e((string) $unreadCount) ?></strong>
                    </div>

                    <?php if ($unreadCount > 0): ?>
                        <form
                            method="POST"
                            action="<?= e(url('producer/notifications.php')) ?>"
                            data-producer-notification-ajax="true"
                        >
                            <?= csrf_field() ?>

                            <input type="hidden" name="_action" value="read_all">

                            <button class="producer-notifications-btn producer-notifications-btn-light" type="submit">
                                Tümünü Okundu Yap
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </section>

            <section class="producer-notifications-summary-grid">
                <article class="producer-notification-stat-card glass-card">
                    <span>🔔</span>

                    <div>
                        <strong><?= e((string) $stats['total']) ?></strong>
                        <p>Toplam Bildirim</p>
                    </div>
                </article>

                <article class="producer-notification-stat-card glass-card">
                    <span>🟢</span>

                    <div>
                        <strong><?= e((string) $unreadCount) ?></strong>
                        <p>Okunmamış</p>
                    </div>
                </article>

                <article class="producer-notification-stat-card glass-card">
                    <span>📦</span>

                    <div>
                        <strong><?= e((string) $stats['orders']) ?></strong>
                        <p>Sipariş Bildirimi</p>
                    </div>
                </article>

                <article class="producer-notification-stat-card glass-card">
                    <span>❔</span>

                    <div>
                        <strong><?= e((string) $stats['questions']) ?></strong>
                        <p>Ürün Sorusu</p>
                    </div>
                </article>
            </section>

            <section class="producer-notification-list">
                <?php foreach ($notifications as $notification): ?>
                    <?php
                        $isRead = !empty($notification['is_read']);
                        $type = (string) ($notification['type'] ?? '');
                        $targetPath = producer_notification_target_path($notification);
                    ?>

                    <article class="producer-notification-card glass-card <?= $isRead ? 'is-read' : 'is-unread' ?>">
                        <div class="producer-notification-icon">
                            <?= e(producer_notification_icon($type)) ?>
                        </div>

                        <div class="producer-notification-content">
                            <div class="producer-notification-title-row">
                                <span class="producer-notification-badge <?= e(producer_notification_badge_class($type)) ?>">
                                    <?= e(producer_notification_type_label($type)) ?>
                                </span>

                                <?php if (!$isRead): ?>
                                    <span class="unread-pill">Okunmamış</span>
                                <?php else: ?>
                                    <span class="read-pill">Okundu</span>
                                <?php endif; ?>
                            </div>

                            <h2><?= e($notification['title'] ?? 'Bildirim') ?></h2>

                            <p>
                                <?= nl2br(e($notification['message'] ?? '')) ?>
                            </p>

                            <span class="producer-notification-date">
                                <?= e(producer_notification_date($notification['created_at'] ?? null)) ?>
                            </span>
                        </div>

                        <div class="producer-notification-actions">
                            <form method="POST" action="<?= e(url('producer/notifications.php')) ?>">
                                <?= csrf_field() ?>

                                <input type="hidden" name="_action" value="open">
                                <input type="hidden" name="notification_id" value="<?= e((string) $notification['id']) ?>">
                                <input type="hidden" name="target" value="<?= e($targetPath) ?>">

                                <button class="producer-notifications-btn producer-notifications-btn-primary full" type="submit">
                                    İlgili Kısma Git
                                </button>
                            </form>

                            <?php if (!$isRead): ?>
                                <form
                                    method="POST"
                                    action="<?= e(url('producer/notifications.php')) ?>"
                                    data-producer-notification-ajax="true"
                                >
                                    <?= csrf_field() ?>

                                    <input type="hidden" name="_action" value="read">
                                    <input type="hidden" name="notification_id" value="<?= e((string) $notification['id']) ?>">

                                    <button class="producer-notifications-btn producer-notifications-btn-light full" type="submit">
                                        Okundu Yap
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="already-read-label">
                                    Bu bildirim okundu.
                                </span>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <?php

        return ob_get_clean();
    }
}

if (is_post()) {
    require_csrf();

    $action = $_POST['_action'] ?? '';
    $success = false;
    $message = 'Geçersiz bildirim işlemi.';

    if ($action === 'open') {
        $notificationId = (int) ($_POST['notification_id'] ?? 0);
        $target = producer_notification_safe_target((string) ($_POST['target'] ?? 'producer/notifications.php'));

        if ($notificationId > 0) {
            try {
                Notification::markAsRead($userId, $notificationId);
            } catch (Throwable $e) {
                flash_error('Bildirim açılırken bir hata oluştu.');
                redirect('producer/notifications.php');
            }
        }

        redirect($target);
    }

    if ($action === 'read') {
        $notificationId = (int) ($_POST['notification_id'] ?? 0);

        if ($notificationId <= 0) {
            $message = 'Geçerli bir bildirim bulunamadı.';
        } else {
            try {
                Notification::markAsRead($userId, $notificationId);
                $success = true;
                $message = 'Bildirim okundu olarak işaretlendi.';
            } catch (Throwable $e) {
                $message = 'Bildirim güncellenirken bir hata oluştu.';
            }
        }
    } elseif ($action === 'read_all') {
        try {
            Notification::markAllAsRead($userId);
            $success = true;
            $message = 'Tüm bildirimler okundu olarak işaretlendi.';
        } catch (Throwable $e) {
            $message = 'Bildirimler güncellenirken bir hata oluştu.';
        }
    }

    $notifications = Notification::getByUserId($userId);
    $unreadCount = Notification::unreadCount($userId);

    if ($isAjax) {
        producer_notification_json([
            'success' => $success,
            'message' => $message,
            'html' => producer_notification_render_dynamic_area($notifications, $unreadCount),
            'heroStatsHtml' => producer_notification_render_hero_stats($notifications, $unreadCount),
            'unreadCount' => $unreadCount,
        ]);
    }

    if ($success) {
        flash_success($message);
    } else {
        flash_error($message);
    }

    redirect('producer/notifications.php');
}

$notifications = Notification::getByUserId($userId);
$unreadCount = Notification::unreadCount($userId);

$pageTitle = 'Üretici Bildirimleri';
$bodyClass = 'page-producer-notifications';

require APP_PATH . '/Views/layouts/header.php';
?>

<main class="producer-notifications-page">
    <section class="producer-notifications-hero">
        <div class="producer-notifications-hero-bg notifications-blob-one"></div>
        <div class="producer-notifications-hero-bg notifications-blob-two"></div>

        <div class="producer-notifications-hero-inner">
            <nav class="producer-notifications-breadcrumb" aria-label="Sayfa yolu">
                <a href="<?= e(url('index.php')) ?>">Ana Sayfa</a>
                <span>/</span>
                <a href="<?= e(url('producer/dashboard.php')) ?>">Üretici Paneli</a>
                <span>/</span>
                <strong>Bildirimler</strong>
            </nav>

            <div class="producer-notifications-hero-content">
                <div class="producer-notifications-hero-copy">
                    <span class="producer-notifications-eyebrow">
                        Bildirim Merkezi
                    </span>

                    <h1>Üretici Bildirimleri</h1>

                    <p>
                        Yeni siparişler, ürün yorumları, tüketici soruları ve sistem mesajlarını
                        tek ekrandan takip edebilirsin.
                    </p>

                    <div id="producer-notifications-hero-stats-wrap">
                        <?= producer_notification_render_hero_stats($notifications, $unreadCount) ?>
                    </div>
                </div>

                <div class="producer-notifications-hero-card">
                    <div class="hero-card-icon">🔔</div>

                    <h2>Gelişmeleri kaçırma</h2>

                    <p>
                        Ürünlerinle ilgili tüm önemli hareketler burada toplanır ve hızlıca ilgili sayfaya gidebilirsin.
                    </p>

                    <div class="hero-mini-list">
                        <span>📦 Sipariş</span>
                        <span>❔ Ürün sorusu</span>
                        <span>⭐ Yorum</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="producer-notifications-shell">
        <div id="producer-notifications-message" class="producer-notifications-message" hidden></div>

        <div id="producer-notifications-dynamic-area">
            <?= producer_notification_render_dynamic_area($notifications, $unreadCount) ?>
        </div>
    </section>
</main>

<style>
    :root {
        --producer-notifications-green-950: #102f1a;
        --producer-notifications-green-900: #163d22;
        --producer-notifications-green-800: #245c2f;
        --producer-notifications-green-700: #2f7d3d;
        --producer-notifications-green-600: #3f9650;
        --producer-notifications-green-100: #eaf6e8;
        --producer-notifications-green-50: #f6fbf4;
        --producer-notifications-cream: #fffaf1;
        --producer-notifications-yellow: #f2bf4d;
        --producer-notifications-red: #9b111e;
        --producer-notifications-text: #1e2b21;
        --producer-notifications-muted: #687669;
        --producer-notifications-border: rgba(47, 125, 61, .14);
        --producer-notifications-shadow: 0 24px 70px rgba(31, 79, 43, .12);
        --producer-notifications-radius-lg: 28px;
    }

    body.page-producer-notifications {
        background:
            radial-gradient(circle at 14% 12%, rgba(196, 231, 177, .48), transparent 28%),
            radial-gradient(circle at 88% 16%, rgba(242, 191, 77, .16), transparent 24%),
            linear-gradient(180deg, #f8fbf2 0%, #f3f8ed 48%, #ffffff 100%);
    }

    .producer-notifications-page {
        overflow: hidden;
    }

    .producer-notifications-hero {
        position: relative;
        min-height: 390px;
        padding: 34px 18px 94px;
        background:
            radial-gradient(circle at 82% 18%, rgba(242, 191, 77, .30), transparent 26%),
            radial-gradient(circle at 12% 78%, rgba(255, 255, 255, .16), transparent 24%),
            linear-gradient(135deg, rgba(36, 92, 47, .97), rgba(47, 125, 61, .87));
        color: #ffffff;
    }

    .producer-notifications-hero::after {
        content: '';
        position: absolute;
        inset: auto 0 -1px;
        height: 90px;
        background: linear-gradient(180deg, rgba(246, 251, 244, 0), #f6fbf4 82%);
        pointer-events: none;
    }

    .producer-notifications-hero-inner,
    .producer-notifications-shell {
        width: min(1180px, calc(100% - 32px));
        margin: 0 auto;
    }

    .producer-notifications-hero-inner {
        position: relative;
        z-index: 2;
    }

    .producer-notifications-hero-bg {
        position: absolute;
        border-radius: 999px;
        filter: blur(2px);
        opacity: .45;
        pointer-events: none;
    }

    .notifications-blob-one {
        width: 230px;
        height: 230px;
        right: 10%;
        top: 42px;
        background: rgba(242, 191, 77, .28);
    }

    .notifications-blob-two {
        width: 150px;
        height: 150px;
        left: 8%;
        bottom: 36px;
        background: rgba(255, 255, 255, .20);
    }

    .producer-notifications-breadcrumb {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 9px;
        font-size: 14px;
        margin-bottom: 32px;
        color: rgba(255, 255, 255, .76);
    }

    .producer-notifications-breadcrumb a {
        color: #ffffff;
        text-decoration: none;
        font-weight: 800;
    }

    .producer-notifications-breadcrumb strong {
        color: #ffffff;
        font-weight: 900;
    }

    .producer-notifications-hero-content {
        display: grid;
        grid-template-columns: minmax(0, 1.25fr) minmax(320px, .75fr);
        gap: 24px;
        align-items: center;
    }

    .producer-notifications-eyebrow,
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

    .producer-notifications-eyebrow {
        background: rgba(255, 255, 255, .16);
        border: 1px solid rgba(255, 255, 255, .28);
        color: #ffffff;
    }

    .producer-notifications-eyebrow.light,
    .section-kicker {
        background: var(--producer-notifications-green-100);
        color: var(--producer-notifications-green-800);
        border-color: transparent;
    }

    .producer-notifications-hero-copy h1 {
        margin: 17px 0 12px;
        font-size: clamp(36px, 5vw, 62px);
        line-height: 1.03;
        letter-spacing: -.045em;
    }

    .producer-notifications-hero-copy p {
        max-width: 700px;
        margin: 0;
        color: rgba(255, 255, 255, .86);
        font-size: 17px;
        line-height: 1.7;
    }

    .producer-notifications-hero-stats {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 18px;
    }

    .producer-notifications-hero-stats span {
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

    .producer-notifications-hero-card {
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

    .producer-notifications-hero-card h2 {
        margin: 0 0 10px;
        font-size: 25px;
        letter-spacing: -.03em;
    }

    .producer-notifications-hero-card p {
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

    .producer-notifications-shell {
        position: relative;
        z-index: 3;
        margin-top: -64px;
        padding-bottom: 54px;
    }

    .glass-card {
        background: rgba(255, 255, 255, .92);
        border: 1px solid rgba(255, 255, 255, .72);
        border-radius: var(--producer-notifications-radius-lg);
        box-shadow: var(--producer-notifications-shadow);
        backdrop-filter: blur(16px);
    }

    .producer-notifications-message {
        margin-bottom: 16px;
        padding: 14px 16px;
        border-radius: 18px;
        font-weight: 900;
        box-shadow: 0 12px 30px rgba(31, 79, 43, .10);
    }

    .producer-notifications-message.success {
        background: #e7f7e8;
        color: #236b2c;
        border: 1px solid rgba(35, 107, 44, .14);
    }

    .producer-notifications-message.error {
        background: #fdeaea;
        color: var(--producer-notifications-red);
        border: 1px solid rgba(155, 17, 30, .14);
    }

    .producer-notifications-top {
        margin-bottom: 18px;
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 18px;
    }

    .producer-notifications-top h2,
    .producer-notifications-empty-card h2 {
        margin: 10px 0 6px;
        color: var(--producer-notifications-green-900);
        letter-spacing: -.03em;
    }

    .producer-notifications-top p,
    .producer-notifications-empty-card p {
        margin: 0;
        color: var(--producer-notifications-muted);
        line-height: 1.6;
    }

    .producer-notifications-top-actions {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .unread-counter {
        display: grid;
        justify-items: end;
        gap: 4px;
        padding: 12px 14px;
        border-radius: 18px;
        background: var(--producer-notifications-green-50);
        border: 1px solid var(--producer-notifications-border);
    }

    .unread-counter span {
        color: var(--producer-notifications-muted);
        font-size: 12px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .unread-counter strong {
        color: var(--producer-notifications-green-800);
        font-size: 28px;
        line-height: 1;
    }

    .producer-notifications-summary-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
        margin-bottom: 22px;
    }

    .producer-notification-stat-card {
        display: flex;
        align-items: center;
        gap: 13px;
        padding: 18px;
    }

    .producer-notification-stat-card > span,
    .producer-notification-icon {
        width: 48px;
        height: 48px;
        display: grid;
        place-items: center;
        border-radius: 17px;
        background: var(--producer-notifications-green-100);
        flex: 0 0 auto;
        font-size: 22px;
    }

    .producer-notification-stat-card strong {
        display: block;
        color: var(--producer-notifications-green-900);
        font-size: 21px;
    }

    .producer-notification-stat-card p {
        margin: 4px 0 0;
        color: var(--producer-notifications-muted);
        font-size: 13px;
        font-weight: 800;
    }

    .producer-notification-list {
        display: grid;
        gap: 16px;
    }

    .producer-notification-card {
        display: grid;
        grid-template-columns: 48px minmax(0, 1fr) minmax(180px, auto);
        gap: 16px;
        align-items: center;
        padding: 18px;
        border-left: 6px solid transparent;
        transition: transform .18s ease, box-shadow .18s ease, opacity .18s ease;
    }

    .producer-notification-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 26px 60px rgba(31, 79, 43, .14);
    }

    .producer-notification-card.is-unread {
        border-left-color: var(--producer-notifications-green-700);
    }

    .producer-notification-card.is-read {
        opacity: .82;
    }

    .producer-notification-title-row {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        align-items: center;
        margin-bottom: 10px;
    }

    .producer-notification-badge,
    .unread-pill,
    .read-pill {
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

    .unread-pill {
        background: var(--producer-notifications-green-100);
        color: var(--producer-notifications-green-800);
    }

    .read-pill {
        background: #edf1ea;
        color: #526052;
    }

    .producer-notification-content h2 {
        margin: 0 0 8px;
        color: var(--producer-notifications-green-900);
        font-size: 23px;
        letter-spacing: -.02em;
    }

    .producer-notification-content p {
        margin: 0 0 10px;
        color: var(--producer-notifications-muted);
        line-height: 1.6;
    }

    .producer-notification-date {
        color: #718071;
        font-size: 14px;
        font-weight: 800;
    }

    .producer-notification-actions {
        display: grid;
        gap: 9px;
        justify-items: stretch;
    }

    .producer-notification-actions form {
        margin: 0;
    }

    .already-read-label {
        display: inline-flex;
        justify-content: center;
        padding: 10px 12px;
        border-radius: 14px;
        background: #edf1ea;
        color: #526052;
        font-weight: 900;
        font-size: 13px;
        text-align: center;
    }

    .producer-notifications-btn {
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

    .producer-notifications-btn:hover {
        transform: translateY(-2px);
    }

    .producer-notifications-btn.full {
        width: 100%;
    }

    .producer-notifications-btn-primary {
        background: linear-gradient(135deg, var(--producer-notifications-green-700), var(--producer-notifications-green-900));
        color: #ffffff;
        box-shadow: 0 16px 32px rgba(47, 125, 61, .24);
    }

    .producer-notifications-btn-light {
        background: var(--producer-notifications-green-50);
        color: var(--producer-notifications-green-800);
        border: 1px solid var(--producer-notifications-border);
    }

    .producer-notifications-empty-card {
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
        background: var(--producer-notifications-green-100);
        font-size: 36px;
    }

    .producer-notifications-empty-card h2 {
        margin-top: 18px;
        font-size: 34px;
    }

    .producer-notifications-empty-card p {
        max-width: 520px;
        margin: 0 auto 22px;
    }

    .empty-actions {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 10px;
    }

    .producer-notifications-loading {
        pointer-events: none;
        opacity: .72;
    }

    @media (max-width: 1100px) {
        .producer-notifications-hero-content {
            grid-template-columns: 1fr;
        }

        .producer-notifications-summary-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 900px) {
        .producer-notifications-top {
            flex-direction: column;
            align-items: flex-start;
        }

        .producer-notifications-top-actions,
        .unread-counter {
            justify-content: flex-start;
            justify-items: start;
        }

        .producer-notification-card {
            grid-template-columns: 48px minmax(0, 1fr);
            align-items: flex-start;
        }

        .producer-notification-actions {
            grid-column: 1 / -1;
            grid-template-columns: 1fr 1fr;
        }
    }

    @media (max-width: 720px) {
        .producer-notifications-hero {
            min-height: 430px;
            padding-top: 24px;
        }

        .producer-notifications-hero-inner,
        .producer-notifications-shell {
            width: min(100% - 22px, 1180px);
        }

        .producer-notifications-breadcrumb {
            font-size: 13px;
            margin-bottom: 24px;
        }

        .producer-notifications-hero-copy p {
            font-size: 15px;
        }

        .producer-notifications-shell {
            margin-top: -52px;
        }

        .producer-notifications-top,
        .producer-notification-card,
        .producer-notifications-hero-card,
        .producer-notifications-empty-card,
        .producer-notification-stat-card {
            padding: 14px;
            border-radius: 23px;
        }

        .producer-notifications-summary-grid,
        .producer-notification-actions {
            grid-template-columns: 1fr;
        }

        .producer-notification-card {
            grid-template-columns: 1fr;
        }

        .producer-notification-icon {
            width: 44px;
            height: 44px;
        }

        .producer-notifications-empty-card h2 {
            font-size: 28px;
        }

        .empty-actions .producer-notifications-btn,
        .producer-notifications-top-actions .producer-notifications-btn {
            width: 100%;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const dynamicArea = document.getElementById('producer-notifications-dynamic-area');
        const heroStatsWrap = document.getElementById('producer-notifications-hero-stats-wrap');
        const messageBox = document.getElementById('producer-notifications-message');

        function showProducerNotificationMessage(type, message) {
            if (!messageBox) {
                return;
            }

            messageBox.hidden = false;
            messageBox.className = 'producer-notifications-message ' + type;
            messageBox.textContent = message || '';

            window.setTimeout(function () {
                messageBox.hidden = true;
                messageBox.textContent = '';
                messageBox.className = 'producer-notifications-message';
            }, 2600);
        }

        document.addEventListener('submit', async function (event) {
            const form = event.target.closest('form[data-producer-notification-ajax="true"]');

            if (!form) {
                return;
            }

            event.preventDefault();

            const buttons = form.querySelectorAll('button');

            buttons.forEach(function (button) {
                button.disabled = true;
            });

            if (dynamicArea) {
                dynamicArea.classList.add('producer-notifications-loading');
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
                    throw new Error(result.message || 'Bildirim işlemi tamamlanamadı.');
                }

                if (dynamicArea && typeof result.html === 'string') {
                    dynamicArea.innerHTML = result.html;
                }

                if (heroStatsWrap && typeof result.heroStatsHtml === 'string') {
                    heroStatsWrap.innerHTML = result.heroStatsHtml;
                }

                showProducerNotificationMessage('success', result.message || 'Bildirim güncellendi.');
            } catch (error) {
                showProducerNotificationMessage('error', error.message || 'Bildirim güncellenirken bir hata oluştu.');
            } finally {
                buttons.forEach(function (button) {
                    button.disabled = false;
                });

                if (dynamicArea) {
                    dynamicArea.classList.remove('producer-notifications-loading');
                }
            }
        });
    });
</script>

<?php require APP_PATH . '/Views/layouts/footer.php'; ?>