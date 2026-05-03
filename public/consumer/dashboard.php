<?php

require_once __DIR__ . '/../../app/bootstrap.php';

ConsumerMiddleware::handle();

$pageTitle = 'Tüketici Paneli';
$bodyClass = 'page-consumer-dashboard';

require APP_PATH . '/Views/layouts/header.php';

$user = currentUser();

$userName = $user['full_name'] ?? 'Tüketici';

$quickCards = [
    [
        'title' => 'Sepetim',
        'text' => 'Sepetindeki ürünleri görüntüle, miktarları düzenle ve sipariş adımına geç.',
        'icon' => '🧺',
        'url' => url('cart.php'),
        'button' => 'Sepete Git',
        'primary' => true,
    ],
    [
        'title' => 'Siparişlerim',
        'text' => 'Aktif ve geçmiş siparişlerini, durum bilgilerini ve detaylarını takip et.',
        'icon' => '📦',
        'url' => url('consumer/orders.php'),
        'button' => 'Siparişlere Git',
        'primary' => false,
    ],
    [
        'title' => 'Sanal Bakiye',
        'text' => 'Bakiye durumunu, yükleme işlemlerini ve hareket geçmişini incele.',
        'icon' => '💳',
        'url' => url('consumer/wallet.php'),
        'button' => 'Bakiye Sayfası',
        'primary' => false,
    ],
    [
        'title' => 'Favorilerim',
        'text' => 'Kaydettiğin ürünleri tek sayfadan görüntüle ve hızlıca sepete ekle.',
        'icon' => '♥',
        'url' => url('consumer/favorites.php'),
        'button' => 'Favorileri Gör',
        'primary' => false,
    ],
];

$smallActions = [
    [
        'title' => 'Profil Bilgilerim',
        'text' => 'Ad, telefon, adres ve hesap bilgilerini güncelle.',
        'icon' => '👤',
        'url' => url('consumer/profile.php'),
    ],
    [
        'title' => 'Bildirimler',
        'text' => 'Sipariş, ödeme ve üretici bildirimlerini kontrol et.',
        'icon' => '🔔',
        'url' => url('consumer/notifications.php'),
    ],
    [
        'title' => 'Ürünleri Keşfet',
        'text' => 'Taze ürünleri filtrele ve doğrudan üreticiden satın al.',
        'icon' => '🌿',
        'url' => url('products.php'),
    ],
    [
        'title' => 'Üreticiler',
        'text' => 'Yerel üreticileri incele ve ürünlerini görüntüle.',
        'icon' => '👨‍🌾',
        'url' => url('producers.php'),
    ],
];
?>

<main class="consumer-dashboard-page">
    <section class="consumer-dashboard-hero">
        <div class="consumer-hero-bg consumer-blob-one"></div>
        <div class="consumer-hero-bg consumer-blob-two"></div>

        <div class="consumer-dashboard-inner">
            <nav class="consumer-breadcrumb" aria-label="Sayfa yolu">
                <a href="<?= e(url('index.php')) ?>">Ana Sayfa</a>
                <span>/</span>
                <strong>Tüketici Paneli</strong>
            </nav>

            <div class="consumer-hero-content">
                <div class="consumer-hero-copy">
                    <span class="consumer-eyebrow">
                        EkineraGo Tüketici Alanı
                    </span>

                    <h1>
                        Hoş geldin,
                        <span><?= e($userName) ?></span>
                    </h1>

                    <p>
                        Buradan sepetini, siparişlerini, favorilerini, sanal bakiyeni ve bildirimlerini
                        tek bir panel üzerinden kolayca takip edebilirsin.
                    </p>

                    <div class="consumer-hero-actions">
                        <a class="consumer-btn consumer-btn-primary" href="<?= e(url('products.php')) ?>">
                            Ürünleri İncele
                        </a>

                        <a class="consumer-btn consumer-btn-glass" href="<?= e(url('cart.php')) ?>">
                            Sepetime Git
                        </a>
                    </div>
                </div>

                <div class="consumer-hero-card">
                    <div class="hero-card-icon">🛒</div>

                    <h2>Doğrudan üreticiden alışveriş</h2>

                    <p>
                        Aracı olmadan, üreticinin listelediği ürünleri incele ve sipariş sürecini hesabından takip et.
                    </p>

                    <div class="hero-mini-list">
                        <span>🌱 Taze ürün</span>
                        <span>📍 Yerel üretici</span>
                        <span>💳 Sanal bakiye</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="consumer-dashboard-shell">


        <section class="dashboard-section-head">
            <div>
                <h2>Bugün ne yapmak istiyorsun?</h2>
                <p>En çok kullanılan tüketici işlemlerine buradan hızlıca ulaşabilirsin.</p>
            </div>
        </section>

        <section class="dashboard-main-grid">
            <?php foreach ($quickCards as $card): ?>
                <article class="dashboard-action-card glass-card">
                    <div class="action-icon">
                        <?= e($card['icon']) ?>
                    </div>

                    <h3><?= e($card['title']) ?></h3>

                    <p><?= e($card['text']) ?></p>

                    <a
                        class="consumer-btn <?= $card['primary'] ? 'consumer-btn-primary' : 'consumer-btn-light' ?>"
                        href="<?= e($card['url']) ?>"
                    >
                        <?= e($card['button']) ?>
                    </a>
                </article>
            <?php endforeach; ?>
        </section>

        <section class="dashboard-lower-grid">
            <div class="dashboard-panel glass-card">
                <div class="panel-heading">
                    <div>
                        <span class="section-kicker">Hesap İşlemleri</span>
                        <h2>Kısa yollar</h2>
                    </div>
                </div>

                <div class="small-action-list">
                    <?php foreach ($smallActions as $action): ?>
                        <a class="small-action-item" href="<?= e($action['url']) ?>">
                            <span><?= e($action['icon']) ?></span>

                            <div>
                                <strong><?= e($action['title']) ?></strong>
                                <p><?= e($action['text']) ?></p>
                            </div>

                            <em>→</em>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <aside class="dashboard-panel glass-card">
                <div class="panel-heading">
                    <div>
                        <span class="section-kicker">Alışveriş Akışı</span>
                        <h2>Nasıl ilerler?</h2>
                    </div>
                </div>

                <div class="step-list">
                    <div class="step-item">
                        <span>1</span>

                        <div>
                            <strong>Ürünü seç</strong>
                            <p>Ürünler sayfasından kategori, konum ve fiyat filtreleriyle arama yap.</p>
                        </div>
                    </div>

                    <div class="step-item">
                        <span>2</span>

                        <div>
                            <strong>Sepete ekle</strong>
                            <p>Ürün miktarını belirle, sepetinde üretici bazlı toplamları kontrol et.</p>
                        </div>
                    </div>

                    <div class="step-item">
                        <span>3</span>

                        <div>
                            <strong>Siparişi takip et</strong>
                            <p>Siparişlerim sayfasından ödeme, hazırlık ve teslimat durumlarını görüntüle.</p>
                        </div>
                    </div>
                </div>
            </aside>
        </section>
    </section>
</main>

<style>
    :root {
        --consumer-green-950: #102f1a;
        --consumer-green-900: #163d22;
        --consumer-green-800: #245c2f;
        --consumer-green-700: #2f7d3d;
        --consumer-green-600: #3f9650;
        --consumer-green-100: #eaf6e8;
        --consumer-green-50: #f6fbf4;
        --consumer-cream: #fffaf1;
        --consumer-yellow: #f2bf4d;
        --consumer-text: #1e2b21;
        --consumer-muted: #687669;
        --consumer-border: rgba(47, 125, 61, .14);
        --consumer-shadow: 0 24px 70px rgba(31, 79, 43, .12);
        --consumer-radius-lg: 28px;
    }

    body.page-consumer-dashboard {
        background:
            radial-gradient(circle at 14% 12%, rgba(196, 231, 177, .48), transparent 28%),
            radial-gradient(circle at 88% 16%, rgba(242, 191, 77, .16), transparent 24%),
            linear-gradient(180deg, #f8fbf2 0%, #f3f8ed 48%, #ffffff 100%);
    }

    .consumer-dashboard-page {
        overflow: hidden;
    }

    .consumer-dashboard-hero {
        position: relative;
        min-height: 390px;
        padding: 34px 18px 94px;
        background:
            radial-gradient(circle at 82% 18%, rgba(242, 191, 77, .30), transparent 26%),
            radial-gradient(circle at 12% 78%, rgba(255, 255, 255, .16), transparent 24%),
            linear-gradient(135deg, rgba(36, 92, 47, .97), rgba(47, 125, 61, .87));
        color: #ffffff;
    }

    .consumer-dashboard-hero::after {
        content: '';
        position: absolute;
        inset: auto 0 -1px;
        height: 90px;
        background: linear-gradient(180deg, rgba(246, 251, 244, 0), #f6fbf4 82%);
        pointer-events: none;
    }

    .consumer-dashboard-inner,
    .consumer-dashboard-shell {
        width: min(1180px, calc(100% - 32px));
        margin: 0 auto;
    }

    .consumer-dashboard-inner {
        position: relative;
        z-index: 2;
    }

    .consumer-hero-bg {
        position: absolute;
        border-radius: 999px;
        filter: blur(2px);
        opacity: .45;
        pointer-events: none;
    }

    .consumer-blob-one {
        width: 230px;
        height: 230px;
        right: 10%;
        top: 42px;
        background: rgba(242, 191, 77, .28);
    }

    .consumer-blob-two {
        width: 150px;
        height: 150px;
        left: 8%;
        bottom: 36px;
        background: rgba(255, 255, 255, .20);
    }

    .consumer-breadcrumb {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 9px;
        font-size: 14px;
        margin-bottom: 32px;
        color: rgba(255, 255, 255, .76);
    }

    .consumer-breadcrumb a {
        color: #ffffff;
        text-decoration: none;
        font-weight: 800;
    }

    .consumer-breadcrumb strong {
        color: #ffffff;
        font-weight: 900;
    }

    .consumer-hero-content {
        display: grid;
        grid-template-columns: minmax(0, 1.25fr) minmax(320px, .75fr);
        gap: 24px;
        align-items: center;
    }

    .consumer-eyebrow,
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

    .consumer-eyebrow {
        background: rgba(255, 255, 255, .16);
        border: 1px solid rgba(255, 255, 255, .28);
        color: #ffffff;
    }

    .section-kicker {
        background: var(--consumer-green-100);
        color: var(--consumer-green-800);
    }

    .consumer-hero-copy h1 {
        margin: 17px 0 12px;
        font-size: clamp(36px, 5vw, 62px);
        line-height: 1.03;
        letter-spacing: -.045em;
    }

    .consumer-hero-copy h1 span {
        color: #fff7d6;
    }

    .consumer-hero-copy p {
        max-width: 700px;
        margin: 0;
        color: rgba(255, 255, 255, .86);
        font-size: 17px;
        line-height: 1.7;
    }

    .consumer-hero-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 22px;
    }

    .consumer-hero-card {
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

    .consumer-hero-card h2 {
        margin: 0 0 10px;
        font-size: 25px;
        letter-spacing: -.03em;
    }

    .consumer-hero-card p {
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

    .consumer-dashboard-shell {
        position: relative;
        z-index: 3;
        margin-top: -64px;
        padding-bottom: 54px;
    }

    .glass-card {
        background: rgba(255, 255, 255, .92);
        border: 1px solid rgba(255, 255, 255, .72);
        border-radius: var(--consumer-radius-lg);
        box-shadow: var(--consumer-shadow);
        backdrop-filter: blur(16px);
    }

    .dashboard-summary-row {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
        margin-bottom: 22px;
    }

    .summary-card {
        display: flex;
        align-items: center;
        gap: 13px;
        padding: 18px;
    }

    .summary-icon,
    .action-icon {
        width: 48px;
        height: 48px;
        display: grid;
        place-items: center;
        border-radius: 17px;
        background: var(--consumer-green-100);
        flex: 0 0 auto;
        font-size: 22px;
    }

    .summary-card strong {
        display: block;
        color: var(--consumer-green-900);
        font-size: 16px;
    }

    .summary-card p {
        margin: 4px 0 0;
        color: var(--consumer-muted);
        font-size: 13px;
        font-weight: 800;
    }

    .dashboard-section-head {
        margin: 8px 0 18px;
    }

    .dashboard-section-head h2,
    .panel-heading h2 {
        margin: 12px 0 6px;
        color: var(--consumer-green-900);
        font-size: 30px;
        letter-spacing: -.035em;
    }

    .dashboard-section-head p {
        margin: 0;
        color: var(--consumer-muted);
        line-height: 1.6;
    }

    .dashboard-main-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 18px;
    }

    .dashboard-action-card {
        padding: 20px;
        display: flex;
        flex-direction: column;
        min-height: 280px;
        transition: transform .2s ease, box-shadow .2s ease;
    }

    .dashboard-action-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 28px 68px rgba(31, 79, 43, .15);
    }

    .dashboard-action-card h3 {
        margin: 18px 0 9px;
        color: var(--consumer-green-900);
        font-size: 22px;
        letter-spacing: -.02em;
    }

    .dashboard-action-card p {
        margin: 0 0 18px;
        color: var(--consumer-muted);
        line-height: 1.6;
        flex: 1;
    }

    .consumer-btn {
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

    .consumer-btn:hover {
        transform: translateY(-2px);
    }

    .consumer-btn-primary {
        background: linear-gradient(135deg, var(--consumer-green-700), var(--consumer-green-900));
        color: #ffffff;
        box-shadow: 0 16px 32px rgba(47, 125, 61, .24);
    }

    .consumer-btn-light {
        background: var(--consumer-green-50);
        color: var(--consumer-green-800);
        border: 1px solid var(--consumer-border);
    }

    .consumer-btn-glass {
        background: rgba(255, 255, 255, .16);
        color: #ffffff;
        border: 1px solid rgba(255, 255, 255, .28);
    }

    .dashboard-lower-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.15fr) minmax(320px, .85fr);
        gap: 22px;
        margin-top: 22px;
    }

    .dashboard-panel {
        padding: 20px;
    }

    .panel-heading {
        margin-bottom: 16px;
        padding-bottom: 16px;
        border-bottom: 1px solid rgba(47, 125, 61, .10);
    }

    .small-action-list,
    .step-list {
        display: grid;
        gap: 12px;
    }

    .small-action-item {
        display: grid;
        grid-template-columns: 46px minmax(0, 1fr) auto;
        gap: 12px;
        align-items: center;
        padding: 14px;
        border-radius: 20px;
        background: #fbfdf8;
        border: 1px solid var(--consumer-border);
        text-decoration: none;
        transition: transform .18s ease, box-shadow .18s ease;
    }

    .small-action-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 16px 34px rgba(31, 79, 43, .10);
    }

    .small-action-item > span {
        width: 46px;
        height: 46px;
        display: grid;
        place-items: center;
        border-radius: 16px;
        background: var(--consumer-green-100);
        font-size: 20px;
    }

    .small-action-item strong {
        color: var(--consumer-green-900);
    }

    .small-action-item p {
        margin: 4px 0 0;
        color: var(--consumer-muted);
        line-height: 1.5;
    }

    .small-action-item em {
        color: var(--consumer-green-700);
        font-style: normal;
        font-size: 22px;
        font-weight: 900;
    }

    .step-item {
        display: grid;
        grid-template-columns: 42px minmax(0, 1fr);
        gap: 12px;
        padding: 14px;
        border-radius: 20px;
        background: #fbfdf8;
        border: 1px solid var(--consumer-border);
    }

    .step-item > span {
        width: 42px;
        height: 42px;
        display: grid;
        place-items: center;
        border-radius: 15px;
        background: linear-gradient(135deg, var(--consumer-green-700), var(--consumer-green-900));
        color: #ffffff;
        font-weight: 900;
    }

    .step-item strong {
        color: var(--consumer-green-900);
    }

    .step-item p {
        margin: 5px 0 0;
        color: var(--consumer-muted);
        line-height: 1.55;
    }

    @media (max-width: 1100px) {
        .dashboard-main-grid,
        .dashboard-summary-row {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .consumer-hero-content,
        .dashboard-lower-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 720px) {
        .consumer-dashboard-hero {
            min-height: 430px;
            padding-top: 24px;
        }

        .consumer-dashboard-inner,
        .consumer-dashboard-shell {
            width: min(100% - 22px, 1180px);
        }

        .consumer-breadcrumb {
            font-size: 13px;
            margin-bottom: 24px;
        }

        .consumer-hero-copy p {
            font-size: 15px;
        }

        .consumer-dashboard-shell {
            margin-top: -52px;
        }

        .dashboard-summary-row,
        .dashboard-main-grid {
            grid-template-columns: 1fr;
        }

        .summary-card,
        .dashboard-action-card,
        .dashboard-panel,
        .consumer-hero-card {
            border-radius: 23px;
        }

        .dashboard-action-card {
            min-height: auto;
        }

        .consumer-hero-actions .consumer-btn {
            width: 100%;
        }

        .dashboard-section-head h2,
        .panel-heading h2 {
            font-size: 25px;
        }

        .small-action-item {
            grid-template-columns: 42px minmax(0, 1fr);
        }

        .small-action-item em {
            display: none;
        }
    }
</style>

<?php require APP_PATH . '/Views/layouts/footer.php'; ?>