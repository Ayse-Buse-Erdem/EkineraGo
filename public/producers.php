<?php

require_once __DIR__ . '/../app/bootstrap.php';

$controller = new ProducerController();

$data = $controller->publicIndexData($_GET);

$producers = $data['producers'] ?? [];
$filters = $data['filters'] ?? [];
$provinces = $data['provinces'] ?? [];
$districts = $data['districts'] ?? [];

$pageTitle = 'Üreticiler';
$bodyClass = 'page-producers';

require APP_PATH . '/Views/layouts/header.php';

if (!function_exists('producer_display_name')) {
    function producer_display_name(array $producer): string
    {
        return !empty($producer['store_name'])
            ? (string) $producer['store_name']
            : (string) ($producer['full_name'] ?? 'Üretici');
    }
}

if (!function_exists('producer_logo_url')) {
    function producer_logo_url(?string $path): string
    {
        if (!$path) {
            return '';
        }

        return url($path);
    }
}

if (!function_exists('producer_rating_text')) {
    function producer_rating_text(array $producer): string
    {
        $rating = (float) ($producer['average_rating'] ?? 0);
        $count = (int) ($producer['rating_count'] ?? 0);

        if ($count <= 0) {
            return 'Henüz puan yok';
        }

        return '⭐ ' . number_format($rating, 1, ',', '.') . ' / ' . $count . ' yorum';
    }
}

if (!function_exists('producer_initial')) {
    function producer_initial(string $name): string
    {
        if (function_exists('mb_substr') && function_exists('mb_strtoupper')) {
            return mb_strtoupper(mb_substr($name, 0, 1, 'UTF-8'), 'UTF-8');
        }

        return strtoupper(substr($name, 0, 1));
    }
}

?>

<main class="producers-page">

    <section class="producers-hero">
        <div class="producers-hero-inner">

            <div class="producers-hero-content">
                <span class="producers-mini-title">Yerel Üreticiler</span>

                <h1>Üreticileri keşfet, ürüne doğrudan kaynaktan ulaş</h1>

                <p>
                    EkineraGo ile yerel üreticileri inceleyebilir, profillerinden ürünlerine
                    ulaşabilir ve aracısız alışveriş deneyimini başlatabilirsin.
                </p>

                <div class="hero-search-card">
                    <form method="GET" action="<?= e(url('producers.php')) ?>" class="hero-search-form">
                        <input
                            type="text"
                            name="q"
                            placeholder="Üretici, çiftlik veya market adı ara..."
                            value="<?= e((string) ($filters['q'] ?? '')) ?>"
                        >

                        <button type="submit">
                            Üretici Ara
                        </button>
                    </form>
                </div>
            </div>

            <div class="producers-hero-visual">
                <div class="hero-visual-card">
                    <span class="hero-visual-icon">🚜</span>
                    <strong><?= count($producers) ?></strong>
                    <small>Aktif üretici listeleniyor</small>
                </div>

                <div class="hero-floating-card card-one">
                    <span>🌿</span>
                    Yerel üretici
                </div>

                <div class="hero-floating-card card-two">
                    <span>🥬</span>
                    Doğrudan ürün
                </div>
            </div>

        </div>
    </section>

    <section class="producers-content">

        <aside class="producer-filter-panel">
            <div class="filter-title">
                <span>🔎</span>

                <div>
                    <h2>Filtrele</h2>
                    <p>Üreticileri konuma veya isme göre bul.</p>
                </div>
            </div>

            <form method="GET" action="<?= e(url('producers.php')) ?>" class="producer-filter-form">

                <div class="form-group">
                    <label for="q">Üretici Ara</label>

                    <input
                        type="text"
                        id="q"
                        name="q"
                        placeholder="Çiftlik, market veya üretici adı..."
                        value="<?= e((string) ($filters['q'] ?? '')) ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="province_name">İl</label>

                    <input
                        type="text"
                        id="province_name"
                        name="province_name"
                        list="province-list"
                        placeholder="İl adı yazın"
                        autocomplete="off"
                        value="<?= e((string) ($filters['province_name'] ?? '')) ?>"
                    >

                    <datalist id="province-list">
                        <?php foreach ($provinces as $province): ?>
                            <option value="<?= e((string) ($province['name'] ?? '')) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>

                <div class="form-group">
                    <label for="district_name">İlçe</label>

                    <input
                        type="text"
                        id="district_name"
                        name="district_name"
                        list="district-list"
                        placeholder="İlçe adı yazın"
                        autocomplete="off"
                        value="<?= e((string) ($filters['district_name'] ?? '')) ?>"
                    >

                    <datalist id="district-list">
                        <?php foreach ($districts as $district): ?>
                            <option value="<?= e((string) ($district['name'] ?? '')) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>

                <div class="filter-actions">
                    <button class="filter-submit" type="submit">
                        Filtrele
                    </button>

                    <a class="filter-clear" href="<?= e(url('producers.php')) ?>">
                        Temizle
                    </a>
                </div>

            </form>
        </aside>

        <section class="producer-results">

            <div class="results-header">
                <div>
                    <span class="results-label">Üretici Listesi</span>
                    <h2><?= count($producers) ?> üretici bulundu</h2>
                </div>

                <a class="results-all-link" href="<?= e(url('products.php')) ?>">
                    Ürünleri Gör
                </a>
            </div>

            <?php if (empty($producers)): ?>
                <div class="empty-state">
                    <div class="empty-icon">🚜</div>

                    <h3>Üretici bulunamadı</h3>

                    <p>
                        Arama kriterlerine uygun aktif üretici bulunamadı.
                        Filtreleri temizleyerek tekrar deneyebilirsin.
                    </p>

                    <a href="<?= e(url('producers.php')) ?>">
                        Tüm Üreticileri Göster
                    </a>
                </div>
            <?php else: ?>

                <div class="producer-grid">
                    <?php foreach ($producers as $producer): ?>
                        <?php
                            $producerName = producer_display_name($producer);
                            $logoUrl = producer_logo_url($producer['logo_path'] ?? null);
                            $producerId = (int) ($producer['user_id'] ?? 0);
                            $activeProductCount = (int) ($producer['active_product_count'] ?? 0);
                            $provinceName = $producer['province_name'] ?? '-';
                            $districtName = $producer['district_name'] ?? '-';
                            $description = !empty($producer['description'])
                                ? (string) $producer['description']
                                : 'Bu üretici henüz açıklama eklememiş.';
                        ?>

                        <article class="producer-card">

                            <div class="producer-card-top">
                                <?php if ($logoUrl): ?>
                                    <img
                                        class="producer-logo"
                                        src="<?= e($logoUrl) ?>"
                                        alt="<?= e($producerName) ?>"
                                    >
                                <?php else: ?>
                                    <div class="producer-logo-placeholder">
                                        <?= e(producer_initial($producerName)) ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (($producer['verification_status'] ?? '') === 'verified'): ?>
                                    <span class="verified-badge">
                                        ✓ Doğrulanmış
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="producer-card-body">
                                <h3>
                                    <a href="<?= e(url('producer-detail.php?id=' . $producerId)) ?>">
                                        <?= e($producerName) ?>
                                    </a>
                                </h3>

                                <div class="producer-location">
                                    <span>📍</span>
                                    <?= e($provinceName) ?> / <?= e($districtName) ?>
                                </div>

                                <p>
                                    <?= e($description) ?>
                                </p>

                                <div class="producer-meta">
                                    <span>
                                        <?= e(producer_rating_text($producer)) ?>
                                    </span>

                                    <span>
                                        🧺 Aktif ürün: <?= e((string) $activeProductCount) ?>
                                    </span>
                                </div>

                                <div class="producer-actions">
                                    <a class="producer-primary-btn" href="<?= e(url('producer-detail.php?id=' . $producerId)) ?>">
                                        Profili Gör
                                    </a>

                                    <a class="producer-secondary-btn" href="<?= e(url('products.php?producer_id=' . $producerId)) ?>">
                                        Ürünleri
                                    </a>
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
    .producers-page {
        min-height: calc(100vh - 74px);
        background:
            radial-gradient(circle at top left, rgba(176, 221, 166, 0.36), transparent 34%),
            radial-gradient(circle at bottom right, rgba(255, 221, 156, 0.22), transparent 36%),
            linear-gradient(135deg, #f8fcf5 0%, #eef8ec 100%);
        padding-bottom: 72px;
    }

    .producers-hero {
        padding: 58px 24px 38px;
    }

    .producers-hero-inner {
        width: min(100%, 1180px);
        margin: 0 auto;
        display: grid;
        grid-template-columns: 1.2fr 0.8fr;
        gap: 36px;
        align-items: center;
    }

    .producers-mini-title {
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

    .producers-hero-content h1 {
        margin: 0;
        max-width: 780px;
        color: #245c2f;
        font-size: clamp(38px, 5vw, 68px);
        line-height: 1.02;
        letter-spacing: -0.06em;
    }

    .producers-hero-content p {
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
        min-width: 142px;
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

    .producers-hero-visual {
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
        animation: producerFloat 4s ease-in-out infinite;
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

    .producers-content {
        width: min(100%, 1180px);
        margin: 0 auto;
        padding: 0 24px;
        display: grid;
        grid-template-columns: 320px minmax(0, 1fr);
        gap: 24px;
        align-items: start;
    }

    .producer-filter-panel {
        position: static;
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

    .producer-filter-form {
        display: grid;
        gap: 14px;
    }

    .producer-filter-form label {
        display: block;
        margin-bottom: 7px;
        color: #28452d;
        font-weight: 900;
        font-size: 14px;
    }

    .producer-filter-form input {
        min-height: 46px;
        border-radius: 15px;
        background: #ffffff;
        border-color: #dcebd8;
    }

    .filter-actions {
        margin-top: 4px;
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

    .producer-results {
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

    .producer-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 22px;
    }

    .producer-card {
        position: relative;
        overflow: hidden;
        border-radius: 28px;
        background: rgba(255, 255, 255, 0.94);
        border: 1px solid #dfeedd;
        box-shadow: 0 18px 50px rgba(36, 92, 47, 0.10);
        transition: transform 0.22s ease, box-shadow 0.22s ease;
    }

    .producer-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 24px 66px rgba(36, 92, 47, 0.15);
    }

    .producer-card-top {
        position: relative;
        min-height: 142px;
        padding: 24px;
        background:
            radial-gradient(circle at top left, rgba(255, 255, 255, 0.78), transparent 34%),
            linear-gradient(135deg, #e8f3e9 0%, #cbeac8 100%);
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 16px;
    }

    .producer-logo,
    .producer-logo-placeholder {
        width: 92px;
        height: 92px;
        border-radius: 28px;
        border: 4px solid rgba(255, 255, 255, 0.82);
        box-shadow: 0 14px 32px rgba(36, 92, 47, 0.16);
        flex: 0 0 auto;
    }

    .producer-logo {
        object-fit: cover;
        display: block;
        background: #ffffff;
    }

    .producer-logo-placeholder {
        display: flex;
        align-items: center;
        justify-content: center;
        background: #ffffff;
        color: #2f7d3d;
        font-size: 34px;
        font-weight: 900;
    }

    .verified-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 34px;
        padding: 0 12px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.92);
        color: #236b2c;
        font-size: 13px;
        font-weight: 900;
        box-shadow: 0 10px 24px rgba(36, 92, 47, 0.10);
    }

    .producer-card-body {
        padding: 22px;
    }

    .producer-card h3 {
        margin: 0 0 9px;
        color: #245c2f;
        font-size: 25px;
        line-height: 1.2;
        letter-spacing: -0.04em;
    }

    .producer-card h3 a {
        color: inherit;
        text-decoration: none;
    }

    .producer-location {
        display: flex;
        align-items: center;
        gap: 7px;
        color: #526052;
        font-weight: 800;
        font-size: 14px;
        margin-bottom: 13px;
    }

    .producer-card p {
        margin: 0;
        color: #667366;
        line-height: 1.65;
        font-size: 14px;
        min-height: 68px;
    }

    .producer-meta {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin: 17px 0 0;
    }

    .producer-meta span {
        display: inline-flex;
        align-items: center;
        min-height: 30px;
        padding: 0 11px;
        border-radius: 999px;
        background: #f6fbf4;
        border: 1px solid #e1efdd;
        color: #526052;
        font-weight: 900;
        font-size: 13px;
    }

    .producer-actions {
        margin-top: 18px;
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 10px;
    }

    .producer-primary-btn,
    .producer-secondary-btn {
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

    .producer-primary-btn {
        background: #2f7d3d;
        color: #ffffff;
        box-shadow: 0 14px 28px rgba(47, 125, 61, 0.18);
    }

    .producer-secondary-btn {
        background: #e8f3e9;
        color: #2f7d3d;
    }

    .producer-primary-btn:hover,
    .producer-secondary-btn:hover {
        transform: translateY(-1px);
    }

    .producer-primary-btn:hover {
        background: #245c2f;
    }

    .producer-secondary-btn:hover {
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

    @keyframes producerFloat {
        0%, 100% {
            transform: translateY(0);
        }

        50% {
            transform: translateY(-8px);
        }
    }

    @media (max-width: 1080px) {
        .producers-hero-inner {
            grid-template-columns: 1fr;
        }

        .producers-hero-visual {
            display: none;
        }

        .producers-content {
            grid-template-columns: 1fr;
        }

        .producer-filter-form {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .filter-actions {
            grid-column: span 3;
        }
    }

    @media (max-width: 780px) {
        .producers-hero {
            padding: 42px 16px 28px;
        }

        .producers-content {
            padding: 0 16px;
        }

        .producers-hero-content {
            text-align: center;
        }

        .producers-hero-content p {
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

        .producer-filter-form,
        .producer-grid {
            grid-template-columns: 1fr;
        }

        .filter-actions {
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
        .producers-hero-content h1 {
            font-size: 38px;
        }

        .producers-hero-content p {
            font-size: 16px;
        }

        .producer-filter-panel,
        .producer-card,
        .empty-state {
            border-radius: 24px;
        }

        .producer-card-top {
            min-height: 132px;
            padding: 20px;
        }

        .producer-logo,
        .producer-logo-placeholder {
            width: 82px;
            height: 82px;
            border-radius: 24px;
        }

        .producer-actions {
            grid-template-columns: 1fr;
        }

        .producer-card p {
            min-height: auto;
        }
    }
</style>

<?php require APP_PATH . '/Views/layouts/footer.php'; ?>