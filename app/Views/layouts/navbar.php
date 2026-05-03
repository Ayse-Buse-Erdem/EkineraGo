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
$notificationUrl = 'login.php';
$walletUrl = 'login.php';

if ($user) {
    if (($user['role'] ?? '') === ROLE_CONSUMER) {
        $profileUrl = 'consumer/profile.php';
        $notificationUrl = 'consumer/notifications.php';
        $walletUrl = 'consumer/wallet.php';
    } elseif (($user['role'] ?? '') === ROLE_PRODUCER) {
        $profileUrl = 'producer/profile.php';
        $notificationUrl = 'producer/notifications.php';
        $walletUrl = 'producer/dashboard.php';
    } elseif (($user['role'] ?? '') === ROLE_ADMIN) {
        $profileUrl = 'admin/dashboard.php';
        $notificationUrl = 'admin/dashboard.php';
        $walletUrl = 'admin/dashboard.php';
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
    <div class="app-navbar-inner">

        <div class="app-navbar-left">
            <button
                class="app-menu-toggle"
                type="button"
                aria-label="Menüyü aç veya kapat"
                aria-expanded="false"
                data-menu-toggle
            >
                <span></span>
                <span></span>
                <span></span>
            </button>

            <a class="app-brand" href="<?= e(url('index.php')) ?>" aria-label="EkineraGo Ana Sayfa">
                <span class="brand-logo" aria-hidden="true">
                    <span class="brand-leaf brand-leaf-left"></span>
                    <span class="brand-leaf brand-leaf-right"></span>
                </span>

                <span class="brand-text">EkineraGo</span>
            </a>
        </div>

        <nav class="app-top-actions" aria-label="Üst Menü">
            <a class="top-action top-action-text<?= $isActive('index.php') ?>" href="<?= e(url('index.php')) ?>">
                Ana Sayfa
            </a>

            <?php if ($user): ?>

                <?php if (($user['role'] ?? '') === ROLE_CONSUMER): ?>
                    <a class="top-action top-action-icon<?= $isActive('cart.php') ?>" href="<?= e(url('cart.php')) ?>" title="Sepet">
                        <span class="action-icon">🛒</span>
                    </a>
                <?php endif; ?>

                <a class="top-action top-action-icon<?= $isActive($walletUrl) ?>" href="<?= e(url($walletUrl)) ?>" title="Bakiye">
                    <span class="action-icon">👛</span>
                </a>

                <a class="top-action top-action-icon<?= $isActive($notificationUrl) ?>" href="<?= e(url($notificationUrl)) ?>" title="Bildirimler">
                    <span class="action-icon">🔔</span>

                    <?php if ($unreadNotificationCount > 0): ?>
                        <span class="nav-badge nav-badge-floating">
                            <?= e((string) $unreadNotificationCount) ?>
                        </span>
                    <?php endif; ?>
                </a>

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

                <a class="top-action top-action-icon top-action-logout" href="<?= e(url('logout.php')) ?>" title="Çıkış">
                    <span class="action-icon">↪</span>
                </a>

            <?php else: ?>

                <a class="top-action top-action-text<?= $isActive('login.php') ?>" href="<?= e(url('login.php')) ?>">
                    Giriş Yap
                </a>

                <a class="top-action top-action-register<?= $isActive('register.php') ?>" href="<?= e(url('register.php')) ?>">
                    Kayıt Ol
                </a>

            <?php endif; ?>
        </nav>

    </div>
</header>

<div class="menu-backdrop" data-menu-backdrop></div>

<aside class="hamburger-menu" data-navbar-menu>
    <div class="hamburger-menu-header">
        <div>
            <span class="menu-small-title">EkineraGo</span>
            <h3>Menü</h3>
        </div>

        <button class="menu-close-button" type="button" aria-label="Menüyü kapat" data-menu-close>
            ×
        </button>
    </div>

    <div class="hamburger-menu-content">

        <div class="menu-group">
            <span class="menu-group-title">Keşfet</span>

            <a class="menu-link<?= $isActive('products.php') ?>" href="<?= e(url('products.php')) ?>">
                <span>🥬</span>
                Ürünler
            </a>

            <a class="menu-link<?= $isActive('producers.php') ?>" href="<?= e(url('producers.php')) ?>">
                <span>🚜</span>
                Üreticiler
            </a>
        </div>

        <?php if ($user): ?>

            <?php if (($user['role'] ?? '') === ROLE_CONSUMER): ?>
                <div class="menu-group">
                    <span class="menu-group-title">Tüketici İşlemleri</span>

                    <a class="menu-link<?= $isActive('consumer/dashboard.php') ?>" href="<?= e(url('consumer/dashboard.php')) ?>">
                        <span>🏠</span>
                        Tüketici Paneli
                    </a>

                    <a class="menu-link<?= $isActive('consumer/orders.php') ?>" href="<?= e(url('consumer/orders.php')) ?>">
                        <span>📦</span>
                        Siparişlerim
                    </a>

                    <a class="menu-link<?= $isActive('consumer/favorites.php') ?>" href="<?= e(url('consumer/favorites.php')) ?>">
                        <span>🤍</span>
                        Favoriler
                    </a>
                </div>
            <?php endif; ?>

            <?php if (($user['role'] ?? '') === ROLE_PRODUCER): ?>
                <div class="menu-group">
                    <span class="menu-group-title">Üretici İşlemleri</span>

                    <a class="menu-link<?= $isActive('producer/dashboard.php') ?>" href="<?= e(url('producer/dashboard.php')) ?>">
                        <span>📊</span>
                        Üretici Paneli
                    </a>

                    <a class="menu-link<?= $isActive('producer/products.php') ?>" href="<?= e(url('producer/products.php')) ?>">
                        <span>🥕</span>
                        Ürünlerim
                    </a>

                    <a class="menu-link<?= $isActive('producer/product-create.php') ?>" href="<?= e(url('producer/product-create.php')) ?>">
                        <span>➕</span>
                        Ürün Ekle
                    </a>

                    <a class="menu-link<?= $isActive('producer/orders.php') ?>" href="<?= e(url('producer/orders.php')) ?>">
                        <span>📦</span>
                        Siparişler
                    </a>

                    <a class="menu-link<?= $isActive('producer/questions.php') ?>" href="<?= e(url('producer/questions.php')) ?>">
                        <span>💬</span>
                        Ürün Soruları

                        <?php if ($pendingQuestionCount > 0): ?>
                            <span class="nav-badge">
                                <?= e((string) $pendingQuestionCount) ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </div>
            <?php endif; ?>

            <?php if (($user['role'] ?? '') === ROLE_ADMIN): ?>
                <div class="menu-group">
                    <span class="menu-group-title">Yönetim</span>

                    <a class="menu-link<?= $isActive('admin/dashboard.php') ?>" href="<?= e(url('admin/dashboard.php')) ?>">
                        <span>⚙️</span>
                        Admin Paneli
                    </a>
                </div>
            <?php endif; ?>

        <?php else: ?>

            <div class="menu-group">
                <span class="menu-group-title">Hesap</span>

                <a class="menu-link<?= $isActive('login.php') ?>" href="<?= e(url('login.php')) ?>">
                    <span>👤</span>
                    Giriş Yap
                </a>

                <a class="menu-link<?= $isActive('register.php') ?>" href="<?= e(url('register.php')) ?>">
                    <span>✨</span>
                    Kayıt Ol
                </a>
            </div>

        <?php endif; ?>

    </div>
</aside>

<style>
    .app-navbar {
        position: sticky;
        top: 0;
        z-index: 100;
        background: rgba(255, 255, 255, 0.94);
        backdrop-filter: blur(14px);
        border-bottom: 1px solid #e4efdf;
        box-shadow: 0 10px 30px rgba(36, 92, 47, 0.08);
    }

    .app-navbar-inner {
        width: 100%;
        min-height: 74px;
        display: flex;
        align-items: center;
        gap: 18px;
        padding: 12px 34px;
        box-sizing: border-box;
    }

    .app-navbar-left {
        display: inline-flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
        flex: 0 0 auto;
    }

    .app-top-actions {
        display: inline-flex;
        align-items: center;
        justify-content: flex-end;
        gap: 8px;
        min-width: 0;
        margin-left: auto;
        flex: 1;
    }

    .app-brand {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
        color: #245c2f;
        font-size: 27px;
        font-weight: 900;
        letter-spacing: -0.04em;
        white-space: nowrap;
    }

    .brand-logo {
        position: relative;
        width: 38px;
        height: 38px;
        border-radius: 14px;
        background:
            radial-gradient(circle at 30% 25%, #ffffff 0 14%, transparent 15%),
            linear-gradient(135deg, #e7f8df 0%, #a9dba5 48%, #70b976 100%);
        box-shadow: 0 10px 24px rgba(47, 125, 61, 0.20);
        overflow: hidden;
        flex: 0 0 auto;
    }

    .brand-logo::after {
        content: "";
        position: absolute;
        left: 18px;
        top: 9px;
        width: 3px;
        height: 22px;
        border-radius: 999px;
        background: #2f7d3d;
        transform: rotate(28deg);
    }

    .brand-leaf {
        position: absolute;
        width: 20px;
        height: 11px;
        background: #2f7d3d;
        z-index: 2;
    }

    .brand-leaf-left {
        left: 6px;
        top: 12px;
        border-radius: 20px 20px 4px 20px;
        transform: rotate(-28deg);
    }

    .brand-leaf-right {
        right: 6px;
        top: 17px;
        border-radius: 20px 20px 20px 4px;
        background: #4fa35a;
        transform: rotate(26deg);
    }

    .brand-text {
        line-height: 1;
    }

    .app-menu-toggle {
        width: 44px;
        height: 44px;
        border: none;
        border-radius: 15px;
        background: #eef8ec;
        color: #245c2f;
        cursor: pointer;
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 5px;
        box-shadow: 0 8px 18px rgba(47, 125, 61, 0.10);
        transition: 0.2s ease;
        flex: 0 0 auto;
    }

    .app-menu-toggle:hover {
        background: #dff1dc;
        transform: translateY(-1px);
    }

    .app-menu-toggle span {
        width: 21px;
        height: 2px;
        border-radius: 999px;
        background: #245c2f;
        transition: 0.2s ease;
    }

    .app-menu-toggle.is-open span:nth-child(1) {
        transform: translateY(7px) rotate(45deg);
    }

    .app-menu-toggle.is-open span:nth-child(2) {
        opacity: 0;
    }

    .app-menu-toggle.is-open span:nth-child(3) {
        transform: translateY(-7px) rotate(-45deg);
    }

    .top-action {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 42px;
        text-decoration: none;
        color: #526052;
        font-size: 14px;
        font-weight: 850;
        border-radius: 999px;
        transition: 0.2s ease;
        white-space: nowrap;
    }

    .top-action-text {
        padding: 0 20px;
        background: #f2f8f0;
        border: 1px solid #e3efdf;
    }

    .top-action-icon {
        width: 42px;
        height: 42px;
        background: #f2f8f0;
        border: 1px solid #e3efdf;
    }

    .top-action:hover,
    .top-action.active {
        background: #e8f3e9;
        color: #2f7d3d;
        transform: translateY(-1px);
    }

    .top-action-register {
        padding: 0 18px;
        background: #2f7d3d;
        color: #ffffff;
        box-shadow: 0 10px 22px rgba(47, 125, 61, 0.18);
    }

    .top-action-register:hover,
    .top-action-register.active {
        background: #245c2f;
        color: #ffffff;
    }

    .top-action-logout {
        color: #9b3434;
        background: #fff1f1;
        border-color: #ffdada;
    }

    .top-action-logout:hover {
        background: #ffe5e5;
        color: #9b3434;
    }

    .action-icon {
        font-size: 18px;
        line-height: 1;
    }

    .nav-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 20px;
        height: 20px;
        padding: 0 6px;
        margin-left: auto;
        border-radius: 999px;
        background: #e85d3f;
        color: #ffffff;
        font-size: 12px;
        font-weight: 900;
        line-height: 1;
    }

    .nav-badge-floating {
        position: absolute;
        top: -5px;
        right: -5px;
        min-width: 19px;
        height: 19px;
        margin-left: 0;
        border: 2px solid #ffffff;
        font-size: 11px;
    }

    .nav-user {
        color: #526052;
        font-size: 14px;
        padding: 6px 11px 6px 7px;
        border-radius: 999px;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #f2f8f0;
        border: 1px solid #e3efdf;
    }

    .nav-user-link {
        text-decoration: none;
        font-weight: 850;
        transition: background 0.2s ease, color 0.2s ease, transform 0.2s ease;
    }

    .nav-user-link:hover,
    .nav-user-link.active {
        background: #e8f3e9;
        color: #2f7d3d;
        transform: translateY(-1px);
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
        max-width: 118px;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .menu-backdrop {
        position: fixed;
        inset: 0;
        z-index: 9998;
        background: rgba(21, 38, 23, 0.48);
        opacity: 0;
        visibility: hidden;
        transition: 0.22s ease;
    }

    .menu-backdrop.is-open {
        opacity: 1;
        visibility: visible;
    }

    .hamburger-menu {
        position: fixed;
        left: 0;
        top: 0;
        width: min(88vw, 380px);
        height: 100vh;
        height: 100dvh;
        z-index: 9999;
        background:
            radial-gradient(circle at top left, rgba(176, 221, 166, 0.45), transparent 38%),
            linear-gradient(180deg, #ffffff 0%, #f8fcf5 100%);
        box-shadow: 24px 0 70px rgba(20, 46, 24, 0.24);
        transform: translateX(-105%);
        transition: transform 0.28s ease;
        display: flex;
        flex-direction: column;
        border-right: 1px solid #e4efdf;
        overflow: hidden;
    }

    .hamburger-menu.is-open {
        transform: translateX(0);
    }

    .hamburger-menu-header {
        flex: 0 0 auto;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 28px 24px 22px;
        border-bottom: 1px solid #e4efdf;
        background: rgba(255, 255, 255, 0.76);
        backdrop-filter: blur(12px);
    }

    .menu-small-title {
        display: block;
        color: #4f9b57;
        font-size: 13px;
        font-weight: 900;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        margin-bottom: 4px;
    }

    .hamburger-menu-header h3 {
        margin: 0;
        color: #245c2f;
        font-size: 30px;
        letter-spacing: -0.04em;
    }

    .menu-close-button {
        width: 42px;
        height: 42px;
        border: none;
        border-radius: 15px;
        background: #eef8ec;
        color: #245c2f;
        font-size: 30px;
        line-height: 1;
        cursor: pointer;
        transition: 0.2s ease;
    }

    .menu-close-button:hover {
        background: #dff1dc;
        transform: rotate(4deg);
    }

    .hamburger-menu-content {
        flex: 1 1 auto;
        padding: 20px 18px 28px;
        overflow-y: auto;
    }

    .menu-group {
        display: flex;
        flex-direction: column;
        gap: 9px;
        margin-bottom: 24px;
    }

    .menu-group-title {
        padding: 0 8px;
        color: #8a988a;
        font-size: 12px;
        font-weight: 900;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .menu-link {
        display: flex;
        align-items: center;
        gap: 12px;
        min-height: 50px;
        padding: 0 15px;
        border-radius: 18px;
        color: #526052;
        font-size: 15px;
        font-weight: 850;
        text-decoration: none;
        background: rgba(255, 255, 255, 0.78);
        border: 1px solid #edf5e9;
        box-shadow: 0 8px 20px rgba(36, 92, 47, 0.05);
        transition: 0.2s ease;
    }

    .menu-link:hover,
    .menu-link.active {
        background: #e8f3e9;
        color: #2f7d3d;
        border-color: #d5ead0;
        transform: translateX(3px);
    }

    .menu-link span:first-child {
        width: 25px;
        text-align: center;
        font-size: 18px;
    }

    @media (max-width: 900px) {
        .app-navbar-inner {
            padding: 11px 18px;
            gap: 12px;
        }

        .app-brand {
            font-size: 24px;
        }

        .brand-logo {
            width: 35px;
            height: 35px;
            border-radius: 13px;
        }

        .nav-user {
            padding: 5px;
        }

        .nav-user-name {
            display: none;
        }
    }

    @media (max-width: 560px) {
        .app-navbar-inner {
            padding: 10px 12px;
        }

        .app-navbar-left {
            gap: 8px;
        }

        .app-brand {
            font-size: 21px;
        }

        .brand-logo {
            width: 32px;
            height: 32px;
        }

        .app-menu-toggle,
        .top-action-icon {
            width: 38px;
            height: 38px;
            border-radius: 13px;
        }

        .top-action-text {
            min-height: 38px;
            padding: 0 12px;
            font-size: 13px;
        }

        .app-top-actions {
            gap: 5px;
        }

        .top-action-register {
            padding: 0 12px;
            min-height: 38px;
            font-size: 13px;
        }
    }

    @media (max-width: 420px) {
        .brand-text {
            display: none;
        }

        .top-action-register {
            display: none;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const toggleButton = document.querySelector('[data-menu-toggle]');
        const closeButton = document.querySelector('[data-menu-close]');
        const navbarMenu = document.querySelector('[data-navbar-menu]');
        const menuBackdrop = document.querySelector('[data-menu-backdrop]');

        if (!toggleButton || !navbarMenu || !menuBackdrop) {
            return;
        }

        function openMenu() {
            navbarMenu.classList.add('is-open');
            menuBackdrop.classList.add('is-open');
            toggleButton.classList.add('is-open');
            toggleButton.setAttribute('aria-expanded', 'true');
            document.body.style.overflow = 'hidden';
        }

        function closeMenu() {
            navbarMenu.classList.remove('is-open');
            menuBackdrop.classList.remove('is-open');
            toggleButton.classList.remove('is-open');
            toggleButton.setAttribute('aria-expanded', 'false');
            document.body.style.overflow = '';
        }

        toggleButton.addEventListener('click', function () {
            if (navbarMenu.classList.contains('is-open')) {
                closeMenu();
            } else {
                openMenu();
            }
        });

        if (closeButton) {
            closeButton.addEventListener('click', closeMenu);
        }

        menuBackdrop.addEventListener('click', closeMenu);

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeMenu();
            }
        });
    });
</script>