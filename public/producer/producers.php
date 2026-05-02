<?php

require_once __DIR__ . '/../app/bootstrap.php';

$pdo = db();

$filters = [
    'q' => trim((string) ($_GET['q'] ?? '')),
    'province_id' => (int) ($_GET['province_id'] ?? 0),
    'district_id' => (int) ($_GET['district_id'] ?? 0),
    'sort' => trim((string) ($_GET['sort'] ?? 'name_asc')),
];

if ($filters['province_id'] <= 0) {
    $filters['province_id'] = 0;
    $filters['district_id'] = 0;
}

if ($filters['district_id'] <= 0) {
    $filters['district_id'] = 0;
}

$allowedSorts = [
    'name_asc',
    'rating_desc',
    'products_desc',
    'newest',
];

if (!in_array($filters['sort'], $allowedSorts, true)) {
    $filters['sort'] = 'name_asc';
}

$orderBy = "COALESCE(pp.store_name, u.full_name) ASC";

if ($filters['sort'] === 'rating_desc') {
    $orderBy = "pp.average_rating DESC, pp.rating_count DESC, COALESCE(pp.store_name, u.full_name) ASC";
} elseif ($filters['sort'] === 'products_desc') {
    $orderBy = "active_product_count DESC, COALESCE(pp.store_name, u.full_name) ASC";
} elseif ($filters['sort'] === 'newest') {
    $orderBy = "u.created_at DESC";
}

$where = [
    "u.role = 'producer'",
    "u.status = 'active'",
];

$params = [];

if ($filters['q'] !== '') {
    $where[] = "(
        u.full_name LIKE :q
        OR pp.store_name LIKE :q
        OR pp.description LIKE :q
    )";

    $params['q'] = '%' . $filters['q'] . '%';
}

if ($filters['province_id'] > 0) {
    $where[] = "u.province_id = :province_id";
    $params['province_id'] = $filters['province_id'];
}

if ($filters['district_id'] > 0) {
    $where[] = "u.district_id = :district_id";
    $params['district_id'] = $filters['district_id'];
}

$whereSql = implode(' AND ', $where);

$producerSql = "
    SELECT
        u.id AS user_id,
        u.full_name,
        u.email,
        u.phone,
        u.whatsapp_phone,
        u.profile_photo,
        u.province_id,
        u.district_id,
        u.created_at,

        pp.store_name,
        pp.slug,
        pp.description,
        pp.logo_path,
        pp.cover_photo_path,
        pp.contact_email,
        pp.contact_phone,
        pp.contact_whatsapp,
        pp.verification_status,
        pp.average_rating,
        pp.rating_count,
        pp.total_orders,
        pp.total_sales_amount,
        pp.shipping_note,

        pr.name AS province_name,
        d.name AS district_name,

        COALESCE(product_counts.active_product_count, 0) AS active_product_count
    FROM users u
    LEFT JOIN producer_profiles pp ON pp.user_id = u.id
    LEFT JOIN provinces pr ON pr.id = u.province_id
    LEFT JOIN districts d ON d.id = u.district_id
    LEFT JOIN (
        SELECT
            producer_id,
            COUNT(*) AS active_product_count
        FROM products
        WHERE status = 'active'
        GROUP BY producer_id
    ) product_counts ON product_counts.producer_id = u.id
    WHERE {$whereSql}
    ORDER BY {$orderBy}
";

$producerStatement = $pdo->prepare($producerSql);
$producerStatement->execute($params);
$producers = $producerStatement->fetchAll();

try {
    $provinces = $pdo->query("
        SELECT id, name
        FROM provinces
        ORDER BY name ASC
    ")->fetchAll();
} catch (Throwable $e) {
    $provinces = [];
}

$districts = [];

if ($filters['province_id'] > 0) {
    try {
        $districtStatement = $pdo->prepare("
            SELECT id, name
            FROM districts
            WHERE province_id = :province_id
            ORDER BY name ASC
        ");

        $districtStatement->execute([
            'province_id' => $filters['province_id'],
        ]);

        $districts = $districtStatement->fetchAll();
    } catch (Throwable $e) {
        $districts = [];
    }
}

if (!function_exists('producer_selected')) {
    function producer_selected($current, $expected): string
    {
        return (string) $current === (string) $expected ? 'selected' : '';
    }
}

if (!function_exists('producer_checked')) {
    function producer_checked($current, $expected): string
    {
        return (string) $current === (string) $expected ? 'checked' : '';
    }
}

if (!function_exists('producer_display_name')) {
    function producer_display_name(array $producer): string
    {
        $storeName = trim((string) ($producer['store_name'] ?? ''));

        if ($storeName !== '') {
            return $storeName;
        }

        return trim((string) ($producer['full_name'] ?? '')) ?: 'Üretici';
    }
}

if (!function_exists('producer_image_url')) {
    function producer_image_url(?string $path): string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        return url($path);
    }
}

if (!function_exists('producer_initial')) {
    function producer_initial(string $name): string
    {
        $name = trim($name);

        if ($name === '') {
            return 'Ü';
        }

        if (function_exists('mb_substr') && function_exists('mb_strtoupper')) {
            return mb_strtoupper(mb_substr($name, 0, 1, 'UTF-8'), 'UTF-8');
        }

        return strtoupper(substr($name, 0, 1));
    }
}

if (!function_exists('producer_rating_label')) {
    function producer_rating_label(array $producer): string
    {
        $rating = (float) ($producer['average_rating'] ?? 0);
        $count = (int) ($producer['rating_count'] ?? 0);

        if ($count <= 0) {
            return 'Henüz puan yok';
        }

        return '⭐ ' . number_format($rating, 1, ',', '.') . ' / ' . $count . ' yorum';
    }
}

if (!function_exists('producer_location_label')) {
    function producer_location_label(array $producer): string
    {
        $province = trim((string) ($producer['province_name'] ?? ''));
        $district = trim((string) ($producer['district_name'] ?? ''));

        if ($province === '') {
            return 'Konum eklenmedi';
        }

        if ($district !== '') {
            return $province . ' / ' . $district;
        }

        return $province;
    }
}

$pageTitle = 'Üreticiler';
$bodyClass = 'page-producers';

require APP_PATH . '/Views/layouts/header.php';

?>

<section class="page-header card producers-hero">
    <div>
        <p class="eyebrow">Yerel Üreticiler</p>
        <h1>Üreticiler</h1>
        <p>
            İl ve ilçe seçerek sana yakın üreticileri bulabilir, üretici profillerinden ürünlerini inceleyebilirsin.
        </p>
    </div>

    <div class="producer-count-box">
        <strong><?= e((string) count($producers)) ?></strong>
        <span>üretici bulundu</span>
    </div>
</section>

<section class="card filter-card">
    <h2>Üretici Ara</h2>

    <form method="get" action="<?= e(url('producers.php')) ?>" class="producer-filter-form">
        <div class="form-group">
            <label for="q">Arama</label>
            <input
                type="text"
                id="q"
                name="q"
                value="<?= e($filters['q']) ?>"
                placeholder="Üretici, çiftlik veya market adı"
            >
        </div>

        <div class="form-group">
            <label for="province_id">İl</label>
            <select id="province_id" name="province_id">
                <option value="">Tüm iller</option>

                <?php foreach ($provinces as $province): ?>
                    <option
                        value="<?= e((string) $province['id']) ?>"
                        <?= producer_selected($filters['province_id'], $province['id']) ?>
                    >
                        <?= e($province['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="district_id">İlçe</label>
            <select id="district_id" name="district_id">
                <option value="">Tüm ilçeler</option>

                <?php foreach ($districts as $district): ?>
                    <option
                        value="<?= e((string) $district['id']) ?>"
                        <?= producer_selected($filters['district_id'], $district['id']) ?>
                    >
                        <?= e($district['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="sort">Sıralama</label>
            <select id="sort" name="sort">
                <option value="name_asc" <?= producer_selected($filters['sort'], 'name_asc') ?>>
                    Ada göre
                </option>
                <option value="rating_desc" <?= producer_selected($filters['sort'], 'rating_desc') ?>>
                    En yüksek puan
                </option>
                <option value="products_desc" <?= producer_selected($filters['sort'], 'products_desc') ?>>
                    En çok ürün
                </option>
                <option value="newest" <?= producer_selected($filters['sort'], 'newest') ?>>
                    En yeni
                </option>
            </select>
        </div>

        <div class="filter-actions">
            <button type="submit" class="btn">
                Filtrele
            </button>

            <a href="<?= e(url('producers.php')) ?>" class="btn btn-secondary">
                Temizle
            </a>
        </div>
    </form>

    <?php if ($filters['province_id'] > 0 || $filters['district_id'] > 0 || $filters['q'] !== ''): ?>
        <div class="active-filter-note">
            Seçili filtrelere göre üreticiler listeleniyor.
        </div>
    <?php endif; ?>
</section>

<section class="producer-list">
    <?php if (empty($producers)): ?>
        <div class="card empty-state">
            <h2>Üretici bulunamadı</h2>
            <p>
                Seçtiğin il / ilçe veya arama kelimesine uygun aktif üretici bulunamadı.
            </p>

            <a href="<?= e(url('producers.php')) ?>" class="btn btn-secondary">
                Tüm Üreticileri Göster
            </a>
        </div>
    <?php else: ?>
        <div class="producer-grid">
            <?php foreach ($producers as $producer): ?>
                <?php
                    $displayName = producer_display_name($producer);
                    $imagePath = $producer['logo_path'] ?: ($producer['profile_photo'] ?? null);
                ?>

                <article class="card producer-card">
                    <div class="producer-card-head">
                        <?php if (!empty($imagePath)): ?>
                            <img
                                src="<?= e(producer_image_url($imagePath)) ?>"
                                alt="<?= e($displayName) ?>"
                                class="producer-logo-small"
                            >
                        <?php else: ?>
                            <div class="producer-logo-small producer-logo-placeholder">
                                <?= e(producer_initial($displayName)) ?>
                            </div>
                        <?php endif; ?>

                        <div class="producer-card-title">
                            <h2><?= e($displayName) ?></h2>

                            <p>
                                <?= e(producer_location_label($producer)) ?>
                            </p>
                        </div>
                    </div>

                    <p class="producer-description">
                        <?= e(trim((string) ($producer['description'] ?? '')) ?: 'Bu üretici henüz açıklama eklememiş.') ?>
                    </p>

                    <div class="producer-meta">
                        <span><?= e(producer_rating_label($producer)) ?></span>
                        <span>Aktif ürün: <?= e((string) ((int) ($producer['active_product_count'] ?? 0))) ?></span>
                        <span>Sipariş: <?= e((string) ((int) ($producer['total_orders'] ?? 0))) ?></span>

                        <?php if (($producer['verification_status'] ?? '') === 'verified'): ?>
                            <span class="producer-verified">
                                Doğrulanmış
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="producer-card-actions">
                        <a
                            class="btn"
                            href="<?= e(url('producer-detail.php?id=' . (int) $producer['user_id'])) ?>"
                        >
                            Profili Gör
                        </a>

                        <a
                            class="btn btn-secondary"
                            href="<?= e(url('products.php?producer_id=' . (int) $producer['user_id'])) ?>"
                        >
                            Ürünleri Gör
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<style>
    .producers-hero {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 24px;
    }

    .producers-hero h1 {
        margin: 0 0 10px;
        color: #245c2f;
        font-size: 38px;
    }

    .producers-hero p {
        margin: 0;
        color: #526052;
        line-height: 1.6;
    }

    .eyebrow {
        margin: 0 0 8px !important;
        color: #2f7d3d !important;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .08em;
        font-size: 12px;
    }

    .producer-count-box {
        min-width: 150px;
        border-radius: 18px;
        background: #e8f3e9;
        color: #245c2f;
        padding: 18px;
        text-align: center;
    }

    .producer-count-box strong {
        display: block;
        font-size: 34px;
        line-height: 1;
    }

    .producer-count-box span {
        display: block;
        margin-top: 6px;
        font-weight: 800;
        font-size: 14px;
    }

    .filter-card {
        margin-top: 24px;
    }

    .filter-card h2 {
        margin-top: 0;
        color: #245c2f;
    }

    .producer-filter-form {
        display: grid;
        grid-template-columns: 1.5fr 1fr 1fr 1fr auto;
        gap: 14px;
        align-items: end;
    }

    .form-group {
        display: grid;
        gap: 7px;
    }

    .form-group label {
        font-weight: 800;
        color: #245c2f;
    }

    .form-group input,
    .form-group select {
        width: 100%;
        border: 1px solid #dce6d9;
        border-radius: 10px;
        padding: 12px 13px;
        font: inherit;
        background: #ffffff;
        color: #1f2d1f;
    }

    .form-group input:focus,
    .form-group select:focus {
        outline: none;
        border-color: #2f7d3d;
        box-shadow: 0 0 0 3px rgba(47, 125, 61, .12);
    }

    .filter-actions {
        display: flex;
        gap: 8px;
    }

    .active-filter-note {
        margin-top: 14px;
        padding: 10px 12px;
        border-radius: 10px;
        background: #f2f6ef;
        color: #526052;
        font-weight: 700;
    }

    .producer-list {
        margin-top: 24px;
    }

    .producer-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 18px;
    }

    .producer-card {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .producer-card-head {
        display: flex;
        gap: 14px;
        align-items: center;
    }

    .producer-logo-small {
        width: 66px;
        height: 66px;
        border-radius: 50%;
        object-fit: cover;
        background: #e8f3e9;
        border: 3px solid #e8f3e9;
        flex: 0 0 auto;
    }

    .producer-logo-placeholder {
        display: flex;
        align-items: center;
        justify-content: center;
        color: #2f7d3d;
        font-weight: 900;
        font-size: 24px;
    }

    .producer-card-title h2 {
        margin: 0;
        color: #245c2f;
        font-size: 22px;
    }

    .producer-card-title p {
        margin: 5px 0 0;
        color: #526052;
        font-weight: 700;
    }

    .producer-description {
        margin: 0;
        color: #526052;
        line-height: 1.55;
    }

    .producer-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: auto;
    }

    .producer-meta span {
        display: inline-flex;
        padding: 6px 9px;
        border-radius: 999px;
        background: #f2f6ef;
        color: #526052;
        font-weight: 700;
        font-size: 13px;
    }

    .producer-meta .producer-verified {
        background: #e8f3e9;
        color: #2f7d3d;
    }

    .producer-card-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .empty-state {
        text-align: center;
    }

    .empty-state h2 {
        color: #245c2f;
    }

    .empty-state p {
        color: #526052;
    }

    @media (max-width: 1100px) {
        .producer-filter-form {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .filter-actions {
            grid-column: 1 / -1;
        }
    }

    @media (max-width: 700px) {
        .producers-hero {
            flex-direction: column;
            align-items: flex-start;
        }

        .producer-filter-form {
            grid-template-columns: 1fr;
        }

        .filter-actions {
            flex-direction: column;
        }

        .producer-count-box {
            width: 100%;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const provinceSelect = document.getElementById('province_id');
        const districtSelect = document.getElementById('district_id');

        if (!provinceSelect || !districtSelect) {
            return;
        }

        provinceSelect.addEventListener('change', function () {
            const provinceId = provinceSelect.value;

            districtSelect.innerHTML = '<option value="">İlçeler yükleniyor...</option>';

            if (!provinceId) {
                districtSelect.innerHTML = '<option value="">Tüm ilçeler</option>';
                return;
            }

            fetch('<?= e(url('api/district-list.php')) ?>?province_id=' + encodeURIComponent(provinceId), {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('İlçe listesi alınamadı.');
                    }

                    return response.json();
                })
                .then(function (result) {
                    districtSelect.innerHTML = '<option value="">Tüm ilçeler</option>';

                    if (!result.success || !Array.isArray(result.data)) {
                        districtSelect.innerHTML = '<option value="">İlçe bulunamadı</option>';
                        return;
                    }

                    result.data.forEach(function (district) {
                        const option = document.createElement('option');

                        option.value = district.id;
                        option.textContent = district.name;

                        districtSelect.appendChild(option);
                    });
                })
                .catch(function () {
                    districtSelect.innerHTML = '<option value="">İlçeler alınamadı</option>';
                });
        });
    });
</script>

<?php require APP_PATH . '/Views/layouts/footer.php'; ?>