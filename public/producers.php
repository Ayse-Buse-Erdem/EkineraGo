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
        return $producer['store_name'] ?: ($producer['full_name'] ?? 'Üretici');
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
?>

<main class="container">
    <section class="card page-heading">
        <h1>Üreticiler</h1>

        <p>
            Yerel üreticileri inceleyebilir, profillerinden ürünlerine ulaşabilir ve
            üretici bilgilerini görüntüleyebilirsin.
        </p>
    </section>

    <section class="card filter-box">
        <h2>Üretici Ara</h2>

        <form method="GET" action="<?= e(url('producers.php')) ?>" class="filter-form">
            <input
                type="text"
                name="q"
                placeholder="Üretici veya çiftlik adı ara..."
                value="<?= e((string) ($filters['q'] ?? '')) ?>"
            >

            <input
                type="text"
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

            <input
                type="text"
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

            <button class="btn" type="submit">
                Ara
            </button>

            <a class="btn btn-secondary" href="<?= e(url('producers.php')) ?>">
                Temizle
            </a>
        </form>
    </section>

    <?php if (empty($producers)): ?>
        <section class="card empty-state">
            <h2>Üretici bulunamadı</h2>

            <p>
                Arama kriterlerine uygun aktif üretici bulunamadı.
            </p>

            <a class="btn" href="<?= e(url('producers.php')) ?>">
                Tüm Üreticileri Göster
            </a>
        </section>
    <?php else: ?>
        <section class="producer-grid">
            <?php foreach ($producers as $producer): ?>
                <?php
                    $producerName = producer_display_name($producer);
                    $logoUrl = producer_logo_url($producer['logo_path'] ?? null);
                    $producerId = (int) ($producer['user_id'] ?? 0);
                    $activeProductCount = (int) ($producer['active_product_count'] ?? 0);
                ?>

                <article class="card producer-card">
                    <?php if ($logoUrl): ?>
                        <img
                            class="producer-logo"
                            src="<?= e($logoUrl) ?>"
                            alt="<?= e($producerName) ?>"
                        >
                    <?php else: ?>
                        <div class="producer-logo-placeholder">
                            Logo
                        </div>
                    <?php endif; ?>

                    <h2><?= e($producerName) ?></h2>

                    <p>
                        <?= e($producer['province_name'] ?? '-') ?>
                        /
                        <?= e($producer['district_name'] ?? '-') ?>
                    </p>

                    <p>
                        <?= e($producer['description'] ?: 'Bu üretici henüz açıklama eklememiş.') ?>
                    </p>

                    <div class="producer-meta">
                        <span><?= e(producer_rating_text($producer)) ?></span>
                        <span>Aktif ürün: <?= e((string) $activeProductCount) ?></span>

                        <?php if (($producer['verification_status'] ?? '') === 'verified'): ?>
                            <span class="verified">Doğrulanmış</span>
                        <?php endif; ?>
                    </div>

                    <div class="producer-actions">
                        <a class="btn btn-secondary" href="<?= e(url('producer-detail.php?id=' . $producerId)) ?>">
                            Profili Gör
                        </a>
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
    .filter-box h2,
    .producer-card h2,
    .empty-state h2 {
        margin-top: 0;
        color: #245c2f;
    }

    .page-heading p,
    .producer-card p,
    .empty-state p {
        color: #526052;
        line-height: 1.5;
    }

    .filter-box {
        margin-bottom: 22px;
    }

    .filter-form {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr auto auto;
        gap: 12px;
        align-items: center;
    }

    .filter-form input {
        width: 100%;
        padding: 11px;
        border: 1px solid #d5dccf;
        border-radius: 9px;
    }

    .producer-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 22px;
    }

    .producer-logo,
    .producer-logo-placeholder {
        width: 86px;
        height: 86px;
        border-radius: 50%;
        margin-bottom: 16px;
    }

    .producer-logo {
        object-fit: cover;
        display: block;
    }

    .producer-logo-placeholder {
        background: #e8f3e9;
        color: #2f7d3d;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
    }

    .producer-meta {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin: 16px 0;
    }

    .producer-meta span {
        padding: 7px 10px;
        border-radius: 999px;
        background: #f8fbf6;
        color: #526052;
        font-weight: bold;
        font-size: 14px;
    }

    .producer-meta .verified {
        background: #e7f7e8;
        color: #236b2c;
    }

    .producer-actions {
        margin-top: 16px;
    }

    .empty-state {
        text-align: center;
        padding: 34px;
    }

    @media (max-width: 1000px) {
        .filter-form {
            grid-template-columns: 1fr 1fr;
        }

        .producer-grid {
            grid-template-columns: 1fr 1fr;
        }
    }

    @media (max-width: 700px) {
        .filter-form,
        .producer-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<?php require APP_PATH . '/Views/layouts/footer.php'; ?>
