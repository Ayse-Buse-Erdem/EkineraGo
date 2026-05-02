<?php

$user = currentUser();

$currentScript = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

$isActive = function (string $path) use ($currentScript): string {
    $needle = '/' . ltrim($path, '/');

    return substr($currentScript, -strlen($needle)) === $needle ? ' active' : '';
};

$unreadNotificationCount = 0;
$pendingQuestionCount = 0;
$userProfilePhoto = null;

if ($user) {
    $userId = (int) ($user['id'] ?? currentUserId());

    try {
        if (class_exists('Notification')) {
            $unreadNotificationCount = Notification::unreadCount($userId);
        }

        if (($user['role'] ?? '') === ROLE_PRODUCER && class_exists('ProductQuestionService')) {
            $questionService = new ProductQuestionService();
            $pendingQuestionCount = $questionService->countPendingByProducerId($userId);
        }

        $photoStatement = db()->prepare("
            SELECT profile_photo
            FROM users
            WHERE id = :id
            LIMIT 1
        ");

        $photoStatement->execute([
            'id' => $userId,
        ]);

        $userProfilePhoto = $photoStatement->fetchColumn() ?: null;
    } catch (Throwable $e) {
        $unreadNotificationCount = 0;
        $pendingQuestionCount = 0;
        $userProfilePhoto = $user['profile_photo'] ?? null;
    }
}

$profileUrl = 'index.php';

if ($user) {
    if (($user['role'] ?? '') === ROLE_CONSUMER) {
        $profileUrl = 'consumer/profile.php';
    } elseif (($user['role'] ?? '') === ROLE_PRODUCER) {
        $profileUrl = 'producer/profile.php';
    } elseif (($user['role'] ?? '') === ROLE_ADMIN) {
        $profileUrl = 'admin/dashboard.php';
    }
}

$userInitial = 'K';

if ($user && !empty($user['full_name'])) {
    if (function_exists('mb_substr') && function_exists('mb_strtoupper')) {
        $userInitial = mb_strtoupper(mb_substr((string) $user['full_name'], 0, 1, 'UTF-8'), 'UTF-8');
    } else {
        $userInitial = strtoupper(substr((string) $user['full_name'], 0, 1));
    }
}

?>

<header class="app-navbar">
    <a class="app-brand" href="<?= e(url('index.php')) ?>">
        EkineraGo
    </a>

    <nav class="app-nav-links">
        <a class="nav-link<?= $isActive('index.php') ?>" href="<?= e(url('index.php')) ?>">
            Ana Sayfa
        </a>

        <a class="nav-link<?= $isActive('products.php') ?>" href="<?= e(url('products.php')) ?>">
            Ürünler
        </a>

        <a class="nav-link<?= $isActive('producers.php') ?>" href="<?= e(url('producers.php')) ?>">
            Üreticiler
        </a>

        <?php if ($user): ?>
            <?php if (($user['role'] ?? '') === ROLE_PRODUCER): ?>
                <a class="nav-link<?= $isActive('producer/dashboard.php') ?>" href="<?= e(url('producer/dashboard.php')) ?>">
                    Üretici Paneli
                </a>

                <a class="nav-link<?= $isActive('producer/products.php') ?>" href="<?= e(url('producer/products.php')) ?>">
                    Ürünlerim
                </a>

                <a class="nav-link<?= $isActive('producer/product-create.php') ?>" href="<?= e(url('producer/product-create.php')) ?>">
                    Ürün Ekle
                </a>

                <a class="nav-link<?= $isActive('producer/orders.php') ?>" href="<?= e(url('producer/orders.php')) ?>">
                    Siparişler
                </a>

                <a class="nav-link<?= $isActive('producer/questions.php') ?>" href="<?= e(url('producer/questions.php')) ?>">
                    Ürün Soruları

                    <?php if ($pendingQuestionCount > 0): ?>
                        <span class="nav-badge">
                            <?= e((string) $pendingQuestionCount) ?>
                        </span>
                    <?php endif; ?>
                </a>

                <a class="nav-link<?= $isActive('producer/notifications.php') ?>" href="<?= e(url('producer/notifications.php')) ?>">
                    Bildirimler

                    <?php if ($unreadNotificationCount > 0): ?>
                        <span class="nav-badge">
                            <?= e((string) $unreadNotificationCount) ?>
                        </span>
                    <?php endif; ?>
                </a>
            <?php endif; ?>

            <?php if (($user['role'] ?? '') === ROLE_CONSUMER): ?>
                <a class="nav-link<?= $isActive('consumer/dashboard.php') ?>" href="<?= e(url('consumer/dashboard.php')) ?>">
                    Tüketici Paneli
                </a>

                <a class="nav-link<?= $isActive('cart.php') ?>" href="<?= e(url('cart.php')) ?>">
                    Sepet
                </a>

                <a class="nav-link<?= $isActive('consumer/orders.php') ?>" href="<?= e(url('consumer/orders.php')) ?>">
                    Siparişlerim
                </a>

                <a class="nav-link<?= $isActive('consumer/wallet.php') ?>" href="<?= e(url('consumer/wallet.php')) ?>">
                    Bakiye
                </a>

                <a class="nav-link<?= $isActive('consumer/favorites.php') ?>" href="<?= e(url('consumer/favorites.php')) ?>">
                    Favoriler
                </a>

                <a class="nav-link<?= $isActive('consumer/notifications.php') ?>" href="<?= e(url('consumer/notifications.php')) ?>">
                    Bildirimler

                    <?php if ($unreadNotificationCount > 0): ?>
                        <span class="nav-badge">
                            <?= e((string) $unreadNotificationCount) ?>
                        </span>
                    <?php endif; ?>
                </a>
            <?php endif; ?>

            <a class="nav-user nav-user-link<?= $isActive($profileUrl) ?>" href="<?= e(url($profileUrl)) ?>">
                <?php if (!empty($userProfilePhoto)): ?>
                    <img
                        src="<?= e(url($userProfilePhoto)) ?>"
                        alt="<?= e($user['full_name'] ?? 'Profil') ?>"
                        class="nav-user-avatar"
                    >
                <?php else: ?>
                    <span class="nav-user-avatar nav-user-avatar-placeholder">
                        <?= e($userInitial) ?>
                    </span>
                <?php endif; ?>

                <span class="nav-user-name">
                    <?= e($user['full_name'] ?? 'Kullanıcı') ?>
                </span>
            </a>

            <a class="nav-link nav-logout" href="<?= e(url('logout.php')) ?>">
                Çıkış
            </a>
        <?php else: ?>
            <a class="nav-link<?= $isActive('login.php') ?>" href="<?= e(url('login.php')) ?>">
                Giriş
            </a>

            <a class="nav-link nav-register<?= $isActive('register.php') ?>" href="<?= e(url('register.php')) ?>">
                Kayıt Ol
            </a>
        <?php endif; ?>
    </nav>
</header>

<style>
    .nav-link {
        position: relative;
    }

    .nav-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 20px;
        height: 20px;
        padding: 0 6px;
        margin-left: 6px;
        border-radius: 999px;
        background: #e85d3f;
        color: #ffffff;
        font-size: 12px;
        font-weight: 800;
        line-height: 1;
    }

    .nav-link.active .nav-badge,
    .nav-link:hover .nav-badge {
        background: #ffffff;
        color: #245c2f;
    }

    .nav-user {
        color: #526052;
        font-size: 14px;
        padding: 6px 9px;
        border-radius: 999px;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .nav-user-link {
        text-decoration: none;
        font-weight: 700;
        transition: background 0.2s ease, color 0.2s ease;
    }

    .nav-user-link:hover,
    .nav-user-link.active {
        background: #e8f3e9;
        color: #2f7d3d;
    }

    .nav-user-avatar {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #e8f3e9;
        flex: 0 0 auto;
    }

    .nav-user-avatar-placeholder {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #e8f3e9;
        color: #2f7d3d;
        font-size: 13px;
        font-weight: 900;
    }

    .nav-user-name {
        max-width: 110px;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    @media (max-width: 768px) {
        .nav-user-name {
            max-width: 180px;
        }
    }
</style>