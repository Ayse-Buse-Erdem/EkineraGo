<?php

require_once __DIR__ . '/../../app/bootstrap.php';

ConsumerMiddleware::handle();

$userId = (int) currentUserId();

if (!function_exists('notification_data_array')) {
    function notification_data_array(array $notification): array
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

if (!function_exists('notification_safe_target')) {
    function notification_safe_target(string $target): string
    {
        $target = trim($target);

        if ($target === '') {
            return 'consumer/notifications.php';
        }

        if (
            str_starts_with($target, 'http://') ||
            str_starts_with($target, 'https://') ||
            str_starts_with($target, '//') ||
            str_contains($target, '\\')
        ) {
            return 'consumer/notifications.php';
        }

        $target = ltrim($target, '/');

        return $target !== '' ? $target : 'consumer/notifications.php';
    }
}

if (!function_exists('notification_target_path')) {
    function notification_target_path(array $notification): string
    {
        $data = notification_data_array($notification);

        if (!empty($data['url'])) {
            return notification_safe_target((string) $data['url']);
        }

        $type = (string) ($notification['type'] ?? '');

        if (!empty($data['product_id'])) {
            if ($type === 'product_question_answered') {
                return 'product-detail.php?id=' . (int) $data['product_id'] . '#questions';
            }

            if ($type === 'new_review') {
                return 'product-detail.php?id=' . (int) $data['product_id'] . '#reviews';
            }

            return 'product-detail.php?id=' . (int) $data['product_id'];
        }

        if (!empty($data['order_id']) || str_contains($type, 'order')) {
            return 'consumer/orders.php';
        }

        return 'consumer/notifications.php';
    }
}

if (!function_exists('notification_type_label')) {
    function notification_type_label(string $type): string
    {
        return match ($type) {
            'order_created' => 'Sipariş',
            'order_status_changed' => 'Sipariş Durumu',
            'new_order' => 'Yeni Sipariş',
            'restock_alert' => 'Stok Bildirimi',
            'favorite_product_updated' => 'Favori Ürün',
            'new_review' => 'Yeni Yorum',
            'new_product_question' => 'Ürün Sorusu',
            'product_question_answered' => 'Soru Cevabı',
            default => 'Bildirim',
        };
    }
}

if (!function_exists('notification_badge_class')) {
    function notification_badge_class(string $type): string
    {
        return match ($type) {
            'order_created', 'new_order', 'new_review', 'product_question_answered' => 'badge-success',
            'order_status_changed', 'new_product_question' => 'badge-info',
            'restock_alert' => 'badge-warning',
            default => 'badge-muted',
        };
    }
}

if (!function_exists('notification_icon')) {
    function notification_icon(string $type): string
    {
        return match ($type) {
            'order_created', 'order_status_changed', 'new_order' => '📦',
            'restock_alert' => '🌱',
            'favorite_product_updated' => '♥',
            'new_review' => '⭐',
            'new_product_question', 'product_question_answered' => '❔',
            default => '🔔',
        };
    }
}

if (!function_exists('notification_date')) {
    function notification_date(?string $date): string
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

if (!function_exists('notification_stats')) {
    function notification_stats(array $notifications): array
    {
        $stats = [
            'total' => count($notifications),
            'unread' => 0,
            'orders' => 0,
            'questions' => 0,
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
        }

        return $stats;
    }
}

if (!function_exists('notification_render_hero_stats')) {
    function notification_render_hero_stats(array $notifications, int $unreadCount): string
    {
        if (empty($notifications)) {
            return '';
        }

        $stats = notification_stats($notifications);

        ob_start();
        ?>
        <div class="notifications-hero-stats" id="notifications-hero-stats">
            <span>🔔 <?= e((string) $stats['total']) ?> bildirim</span>
            <span>🟢 <?= e((string) $unreadCount) ?> okunmamış</span>
            <span>📦 <?= e((string) $stats['orders']) ?> sipariş bildirimi</span>
        </div>
        <?php

        return ob_get_clean();
    }
}

if (!function_exists('notification_render_dynamic_area')) {
    function notification_render_dynamic_area(array $notifications, int $unreadCount): string
    {
        $stats = notification_stats($notifications);

        ob_start();
        ?>

        <?php if (empty($notifications)): ?>
            <section class="notifications-empty-card glass-card">
                <div class="empty-icon">🔔</div>

                <span class="notifications-eyebrow light">Bildirim Yok</span>

                <h2>Henüz bildirimin yok.</h2>

                <p>
                    Sipariş oluşturduğunda, soru cevabı aldığında veya sipariş durumun güncellendiğinde
                    bildirimlerin burada görünecek.
                </p>

                <div class="empty-actions">
                    <a class="notifications-btn notifications-btn-primary" href="<?= e(url('products.php')) ?>">
                        Ürünleri İncele
                    </a>

                    <a class="notifications-btn notifications-btn-light" href="<?= e(url('consumer/dashboard.php')) ?>">
                        Panele Dön
                    </a>
                </div>
            </section>
        <?php else: ?>
            <section class="notifications-top glass-card">
                <div>
                    <span class="section-kicker">Bildirim Özeti</span>

                    <h2>Güncel bildirimlerin</h2>

                    <p>
                        Sipariş, ürün sorusu, favori ürün ve sistem bildirimlerini buradan takip edebilirsin.
                    </p>
                </div>

                <div class="notifications-top-actions">
                    <div class="unread-counter">
                        <span>Okunmamış</span>
                        <strong><?= e((string) $unreadCount) ?></strong>
                    </div>

                    <?php if ($unreadCount > 0): ?>
                        <form
                            method="POST"
                            action="<?= e(url('consumer/notifications.php')) ?>"
                            data-notification-ajax="true"
                        >
                            <?= csrf_field() ?>

                            <input type="hidden" name="_action" value="read_all">

                            <button class="notifications-btn notifications-btn-light" type="submit">
                                Tümünü Okundu Yap
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </section>

            <section class="notifications-summary-grid">
                <article class="notification-stat-card glass-card">
                    <span>🔔</span>
                    <div>
                        <strong><?= e((string) $stats['total']) ?></strong>
                        <p>Toplam Bildirim</p>
                    </div>
                </article>

                <article class="notification-stat-card glass-card">
                    <span>🟢</span>
                    <div>
                        <strong><?= e((string) $unreadCount) ?></strong>
                        <p>Okunmamış</p>
                    </div>
                </article>

                <article class="notification-stat-card glass-card">
                    <span>📦</span>
                    <div>
                        <strong><?= e((string) $stats['orders']) ?></strong>
                        <p>Sipariş Bildirimi</p>
                    </div>
                </article>

                <article class="notification-stat-card glass-card">
                    <span>❔</span>
                    <div>
                        <strong><?= e((string) $stats['questions']) ?></strong>
                        <p>Soru Bildirimi</p>
                    </div>
                </article>
            </section>

            <section class="notification-list">
                <?php foreach ($notifications as $notification): ?>
                    <?php
                        $isRead = !empty($notification['is_read']);
                        $type = (string) ($notification['type'] ?? '');
                        $targetPath = notification_target_path($notification);
                    ?>

                    <article class="notification-card glass-card <?= $isRead ? 'is-read' : 'is-unread' ?>">
                        <div class="notification-icon">
                            <?= e(notification_icon($type)) ?>
                        </div>

                        <div class="notification-content">
                            <div class="notification-title-row">
                                <span class="notification-badge <?= e(notification_badge_class($type)) ?>">
                                    <?= e(notification_type_label($type)) ?>
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

                            <span class="notification-date">
                                <?= e(notification_date($notification['created_at'] ?? null)) ?>
                            </span>
                        </div>

                        <div class="notification-actions">
                            <form method="POST" action="<?= e(url('consumer/notifications.php')) ?>">
                                <?= csrf_field() ?>

                                <input type="hidden" name="_action" value="open">
                                <input type="hidden" name="notification_id" value="<?= e((string) $notification['id']) ?>">
                                <input type="hidden" name="target" value="<?= e($targetPath) ?>">

                                <button class="notifications-btn notifications-btn-primary full" type="submit">
                                    İlgili Kısma Git
                                </button>
                            </form>

                            <?php if (!$isRead): ?>
                                <form
                                    method="POST"
                                    action="<?= e(url('consumer/notifications.php')) ?>"
                                    data-notification-ajax="true"
                                >
                                    <?= csrf_field() ?>

                                    <input type="hidden" name="_action" value="read">
                                    <input type="hidden" name="notification_id" value="<?= e((string) $notification['id']) ?>">

                                    <button class="notifications-btn notifications-btn-light full" type="submit">
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

$isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';

if (is_post()) {
    require_csrf();

    $action = $_POST['_action'] ?? '';
    $success = false;
    $message = 'Geçersiz bildirim işlemi.';

    if ($action === 'open') {
        $notificationId = (int) ($_POST['notification_id'] ?? 0);
        $target = notification_safe_target((string) ($_POST['target'] ?? 'consumer/notifications.php'));

        if ($notificationId > 0) {
            try {
                Notification::markAsRead($userId, $notificationId);
            } catch (Throwable $e) {
                flash_error('Bildirim açılırken bir hata oluştu.');
                redirect('consumer/notifications.php');
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
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode([
            'success' => $success,
            'message' => $message,
            'html' => notification_render_dynamic_area($notifications, $unreadCount),
            'heroStatsHtml' => notification_render_hero_stats($notifications, $unreadCount),
            'unreadCount' => $unreadCount,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        exit;
    }

    if ($success) {
        flash_success($message);
    } else {
        flash_error($message);
    }

    redirect('consumer/notifications.php');
}

$notifications = Notification::getByUserId($userId);
$unreadCount = Notification::unreadCount($userId);

$pageTitle = 'Bildirimler';
$bodyClass = 'page-consumer-notifications';

require APP_PATH . '/Views/layouts/header.php';
?>

<main class="consumer-notifications-page">
    <section class="notifications-hero">
        <div class="notifications-hero-bg notifications-blob-one"></div>
        <div class="notifications-hero-bg notifications-blob-two"></div>

        <div class="notifications-hero-inner">
            <nav class="notifications-breadcrumb" aria-label="Sayfa yolu">
                <a href="<?= e(url('index.php')) ?>">Ana Sayfa</a>
                <span>/</span>
                <a href="<?= e(url('consumer/dashboard.php')) ?>">Tüketici Paneli</a>
                <span>/</span>
                <strong>Bildirimler</strong>
            </nav>

            <div class="notifications-hero-content">
                <div class="notifications-hero-copy">
                    <span class="notifications-eyebrow">
                        Bildirim Merkezi
                    </span>

                    <h1>Bildirimler</h1>

                    <p>
                        Sipariş durumları, ürün soruları, cevaplar, stok uyarıları ve sistem mesajlarını
                        tek ekrandan takip edebilirsin.
                    </p>

                    <div id="notifications-hero-stats-wrap">
                        <?= notification_render_hero_stats($notifications, $unreadCount) ?>
                    </div>
                </div>

                <div class="notifications-hero-card">
                    <div class="hero-card-icon">🔔</div>

                    <h2>Gelişmeleri kaçırma</h2>

                    <p>
                        Ürünler, siparişler ve satıcı cevaplarıyla ilgili tüm önemli güncellemeler burada toplanır.
                    </p>

                    <div class="hero-mini-list">
                        <span>📦 Sipariş</span>
                        <span>❔ Soru cevabı</span>
                        <span>🌱 Stok uyarısı</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="notifications-shell">
        <div id="notification-ajax-message" class="notification-ajax-message" hidden></div>

        <div id="notifications-dynamic-area">
            <?= notification_render_dynamic_area($notifications, $unreadCount) ?>
        </div>
    </section>
</main>

<style>
    :root {
        --notifications-green-950: #102f1a;
        --notifications-green-900: #163d22;
        --notifications-green-800: #245c2f;
        --notifications-green-700: #2f7d3d;
        --notifications-green-600: #3f9650;
        --notifications-green-100: #eaf6e8;
        --notifications-green-50: #f6fbf4;
        --notifications-cream: #fffaf1;
        --notifications-yellow: #f2bf4d;
        --notifications-red: #9b111e;
        --notifications-text: #1e2b21;
        --notifications-muted: #687669;
        --notifications-border: rgba(47, 125, 61, .14);
        --notifications-shadow: 0 24px 70px rgba(31, 79, 43, .12);
        --notifications-radius-lg: 28px;
    }

    body.page-consumer-notifications {
        background:
            radial-gradient(circle at 14% 12%, rgba(196, 231, 177, .48), transparent 28%),
            radial-gradient(circle at 88% 16%, rgba(242, 191, 77, .16), transparent 24%),
            linear-gradient(180deg, #f8fbf2 0%, #f3f8ed 48%, #ffffff 100%);
    }

    .consumer-notifications-page {
        overflow: hidden;
    }

    .notifications-hero {
        position: relative;
        min-height: 390px;
        padding: 34px 18px 94px;
        background:
            radial-gradient(circle at 82% 18%, rgba(242, 191, 77, .30), transparent 26%),
            radial-gradient(circle at 12% 78%, rgba(255, 255, 255, .16), transparent 24%),
            linear-gradient(135deg, rgba(36, 92, 47, .97), rgba(47, 125, 61, .87));
        color: #ffffff;
    }

    .notifications-hero::after {
        content: '';
        position: absolute;
        inset: auto 0 -1px;
        height: 90px;
        background: linear-gradient(180deg, rgba(246, 251, 244, 0), #f6fbf4 82%);
        pointer-events: none;
    }

    .notifications-hero-inner,
    .notifications-shell {
        width: min(1180px, calc(100% - 32px));
        margin: 0 auto;
    }

    .notifications-hero-inner {
        position: relative;
        z-index: 2;
    }

    .notifications-hero-bg {
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

    .notifications-breadcrumb {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 9px;
        font-size: 14px;
        margin-bottom: 32px;
        color: rgba(255, 255, 255, .76);
    }

    .notifications-breadcrumb a {
        color: #ffffff;
        text-decoration: none;
        font-weight: 800;
    }

    .notifications-breadcrumb strong {
        color: #ffffff;
        font-weight: 900;
    }

    .notifications-hero-content {
        display: grid;
        grid-template-columns: minmax(0, 1.25fr) minmax(320px, .75fr);
        gap: 24px;
        align-items: center;
    }

    .notifications-eyebrow,
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

    .notifications-eyebrow {
        background: rgba(255, 255, 255, .16);
        border: 1px solid rgba(255, 255, 255, .28);
        color: #ffffff;
    }

    .notifications-eyebrow.light,
    .section-kicker {
        background: var(--notifications-green-100);
        color: var(--notifications-green-800);
        border-color: transparent;
    }

    .notifications-hero-copy h1 {
        margin: 17px 0 12px;
        font-size: clamp(36px, 5vw, 62px);
        line-height: 1.03;
        letter-spacing: -.045em;
    }

    .notifications-hero-copy p {
        max-width: 700px;
        margin: 0;
        color: rgba(255, 255, 255, .86);
        font-size: 17px;
        line-height: 1.7;
    }

    .notifications-hero-stats {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 18px;
    }

    .notifications-hero-stats span {
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

    .notifications-hero-card {
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

    .notifications-hero-card h2 {
        margin: 0 0 10px;
        font-size: 25px;
        letter-spacing: -.03em;
    }

    .notifications-hero-card p {
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

    .notifications-shell {
        position: relative;
        z-index: 3;
        margin-top: -64px;
        padding-bottom: 54px;
    }

    .glass-card {
        background: rgba(255, 255, 255, .92);
        border: 1px solid rgba(255, 255, 255, .72);
        border-radius: var(--notifications-radius-lg);
        box-shadow: var(--notifications-shadow);
        backdrop-filter: blur(16px);
    }

    .notification-ajax-message {
        margin-bottom: 16px;
        padding: 14px 16px;
        border-radius: 18px;
        font-weight: 900;
        box-shadow: 0 12px 30px rgba(31, 79, 43, .10);
    }

    .notification-ajax-message.success {
        background: #e7f7e8;
        color: #236b2c;
        border: 1px solid rgba(35, 107, 44, .14);
    }

    .notification-ajax-message.error {
        background: #fdeaea;
        color: var(--notifications-red);
        border: 1px solid rgba(155, 17, 30, .14);
    }

    .notifications-top {
        margin-bottom: 18px;
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 18px;
    }

    .notifications-top h2,
    .notifications-empty-card h2 {
        margin: 10px 0 6px;
        color: var(--notifications-green-900);
        letter-spacing: -.03em;
    }

    .notifications-top p,
    .notifications-empty-card p {
        margin: 0;
        color: var(--notifications-muted);
        line-height: 1.6;
    }

    .notifications-top-actions {
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
        background: var(--notifications-green-50);
        border: 1px solid var(--notifications-border);
    }

    .unread-counter span {
        color: var(--notifications-muted);
        font-size: 12px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .unread-counter strong {
        color: var(--notifications-green-800);
        font-size: 28px;
        line-height: 1;
    }

    .notifications-summary-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
        margin-bottom: 22px;
    }

    .notification-stat-card {
        display: flex;
        align-items: center;
        gap: 13px;
        padding: 18px;
    }

    .notification-stat-card > span,
    .notification-icon {
        width: 48px;
        height: 48px;
        display: grid;
        place-items: center;
        border-radius: 17px;
        background: var(--notifications-green-100);
        flex: 0 0 auto;
        font-size: 22px;
    }

    .notification-stat-card strong {
        display: block;
        color: var(--notifications-green-900);
        font-size: 21px;
    }

    .notification-stat-card p {
        margin: 4px 0 0;
        color: var(--notifications-muted);
        font-size: 13px;
        font-weight: 800;
    }

    .notification-list {
        display: grid;
        gap: 16px;
    }

    .notification-card {
        display: grid;
        grid-template-columns: 48px minmax(0, 1fr) minmax(180px, auto);
        gap: 16px;
        align-items: center;
        padding: 18px;
        border-left: 6px solid transparent;
        transition: transform .18s ease, box-shadow .18s ease, opacity .18s ease;
    }

    .notification-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 26px 60px rgba(31, 79, 43, .14);
    }

    .notification-card.is-unread {
        border-left-color: var(--notifications-green-700);
    }

    .notification-card.is-read {
        opacity: .82;
    }

    .notification-title-row {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        align-items: center;
        margin-bottom: 10px;
    }

    .notification-badge,
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
        background: var(--notifications-green-100);
        color: var(--notifications-green-800);
    }

    .read-pill {
        background: #edf1ea;
        color: #526052;
    }

    .notification-content h2 {
        margin: 0 0 8px;
        color: var(--notifications-green-900);
        font-size: 23px;
        letter-spacing: -.02em;
    }

    .notification-content p {
        margin: 0 0 10px;
        color: var(--notifications-muted);
        line-height: 1.6;
    }

    .notification-date {
        color: #718071;
        font-size: 14px;
        font-weight: 800;
    }

    .notification-actions {
        display: grid;
        gap: 9px;
        justify-items: stretch;
    }

    .notification-actions form {
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

    .notifications-btn {
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

    .notifications-btn:hover {
        transform: translateY(-2px);
    }

    .notifications-btn.full {
        width: 100%;
    }

    .notifications-btn-primary {
        background: linear-gradient(135deg, var(--notifications-green-700), var(--notifications-green-900));
        color: #ffffff;
        box-shadow: 0 16px 32px rgba(47, 125, 61, .24);
    }

    .notifications-btn-light {
        background: var(--notifications-green-50);
        color: var(--notifications-green-800);
        border: 1px solid var(--notifications-border);
    }

    .notifications-empty-card {
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
        background: var(--notifications-green-100);
        font-size: 36px;
    }

    .notifications-empty-card h2 {
        margin-top: 18px;
        font-size: 34px;
    }

    .notifications-empty-card p {
        max-width: 520px;
        margin: 0 auto 22px;
    }

    .empty-actions {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 10px;
    }

    .notifications-loading {
        pointer-events: none;
        opacity: .72;
    }

    @media (max-width: 1100px) {
        .notifications-hero-content {
            grid-template-columns: 1fr;
        }

        .notifications-summary-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 900px) {
        .notifications-top {
            flex-direction: column;
            align-items: flex-start;
        }

        .notifications-top-actions,
        .unread-counter {
            justify-content: flex-start;
            justify-items: start;
        }

        .notification-card {
            grid-template-columns: 48px minmax(0, 1fr);
            align-items: flex-start;
        }

        .notification-actions {
            grid-column: 1 / -1;
            grid-template-columns: 1fr 1fr;
        }
    }

    @media (max-width: 720px) {
        .notifications-hero {
            min-height: 430px;
            padding-top: 24px;
        }

        .notifications-hero-inner,
        .notifications-shell {
            width: min(100% - 22px, 1180px);
        }

        .notifications-breadcrumb {
            font-size: 13px;
            margin-bottom: 24px;
        }

        .notifications-hero-copy p {
            font-size: 15px;
        }

        .notifications-shell {
            margin-top: -52px;
        }

        .notifications-top,
        .notification-card,
        .notifications-hero-card,
        .notifications-empty-card,
        .notification-stat-card {
            padding: 14px;
            border-radius: 23px;
        }

        .notifications-summary-grid,
        .notification-actions {
            grid-template-columns: 1fr;
        }

        .notification-card {
            grid-template-columns: 1fr;
        }

        .notification-icon {
            width: 44px;
            height: 44px;
        }

        .notifications-empty-card h2 {
            font-size: 28px;
        }

        .empty-actions .notifications-btn,
        .notifications-top-actions .notifications-btn {
            width: 100%;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const dynamicArea = document.getElementById('notifications-dynamic-area');
        const heroStatsWrap = document.getElementById('notifications-hero-stats-wrap');
        const messageBox = document.getElementById('notification-ajax-message');

        function showNotificationMessage(type, message) {
            if (!messageBox) {
                return;
            }

            messageBox.hidden = false;
            messageBox.className = 'notification-ajax-message ' + type;
            messageBox.textContent = message || '';

            window.setTimeout(function () {
                messageBox.hidden = true;
                messageBox.textContent = '';
                messageBox.className = 'notification-ajax-message';
            }, 2600);
        }

        document.addEventListener('submit', async function (event) {
            const form = event.target.closest('form[data-notification-ajax="true"]');

            if (!form) {
                return;
            }

            event.preventDefault();

            const buttons = form.querySelectorAll('button');

            buttons.forEach(function (button) {
                button.disabled = true;
            });

            if (dynamicArea) {
                dynamicArea.classList.add('notifications-loading');
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

                showNotificationMessage('success', result.message || 'Bildirim güncellendi.');
            } catch (error) {
                showNotificationMessage('error', error.message || 'Bildirim güncellenirken bir hata oluştu.');
            } finally {
                buttons.forEach(function (button) {
                    button.disabled = false;
                });

                if (dynamicArea) {
                    dynamicArea.classList.remove('notifications-loading');
                }
            }
        });
    });
</script>

<?php require APP_PATH . '/Views/layouts/footer.php'; ?>