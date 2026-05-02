<?php

require_once __DIR__ . '/../app/bootstrap.php';

$producerId = (int) ($_GET['id'] ?? 0);

if ($producerId <= 0) {
    flash_error('Geçersiz üretici bilgisi.');
    redirect('producers.php');
}

$controller = new ProducerController();
$data = $controller->publicDetailData($producerId);

$producer = $data['producer'] ?? null;
$products = $data['products'] ?? [];
$reviews = $data['reviews'] ?? [];

if (!$producer) {
    flash_error('Üretici bulunamadı.');
    redirect('producers.php');
}

$pageTitle = ($producer['store_name'] ?? $producer['full_name'] ?? 'Üretici') . ' - Üretici Profili';
$bodyClass = 'page-producer-detail';

require APP_PATH . '/Views/layouts/header.php';

if (!function_exists('producer_detail_name')) {
    function producer_detail_name(array $producer): string
    {
        return $producer['store_name'] ?: ($producer['full_name'] ?? 'Üretici');
    }
}

if (!function_exists('producer_detail_location')) {
    function producer_detail_location(array $producer): string
    {
        $province = $producer['province_name'] ?? '';
        $district = $producer['district_name'] ?? '';

        if ($province && $district) {
            return $province . ' / ' . $district;
        }

        return $province ?: ($district ?: 'Konum bilgisi yok');
    }
}

if (!function_exists('producer_detail_rating')) {
    function producer_detail_rating(array $producer): string
    {
        $rating = (float) ($producer['average_rating'] ?? 0);
        $count = (int) ($producer['rating_count'] ?? 0);

        if ($count <= 0) {
            return 'Henüz puan yok';
        }

        return '⭐ ' . number_format($rating, 1, ',', '.') . ' / 5 - ' . $count . ' yorum';
    }
}

if (!function_exists('producer_detail_image_url')) {
    function producer_detail_image_url(?string $path): string
    {
        if (!$path) {
            return '';
        }

        return url($path);
    }
}

if (!function_exists('producer_detail_money')) {
    function producer_detail_money(float $amount): string
    {
        if (function_exists('formatMoney')) {
            return formatMoney($amount);
        }

        return number_format($amount, 2, ',', '.') . ' TL';
    }
}

if (!function_exists('producer_detail_unit_label')) {
    function producer_detail_unit_label(string $unit): string
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
?>

<main class="container">
    <section class="card producer-detail-card">
        <?php if (!empty($producer['cover_photo_path'])): ?>
            <div class="producer-cover">
                <img
                    src="<?= e(producer_detail_image_url($producer['cover_photo_path'])) ?>"
                    alt="<?= e(producer_detail_name($producer)) ?>"
                >
            </div>
        <?php endif; ?>

        <div class="producer-profile-header">
            <div class="producer-logo producer-detail-logo">
                <?php if (!empty($producer['logo_path'])): ?>
                    <img
                        src="<?= e(producer_detail_image_url($producer['logo_path'])) ?>"
                        alt="<?= e(producer_detail_name($producer)) ?>"
                    >
                <?php else: ?>
                    <span><?= e(mb_substr(producer_detail_name($producer), 0, 1, 'UTF-8')) ?></span>
                <?php endif; ?>
            </div>

            <div>
                <h1><?= e(producer_detail_name($producer)) ?></h1>

                <p class="muted">
                    <?= e(producer_detail_location($producer)) ?>
                </p>

                <p><?= e(producer_detail_rating($producer)) ?></p>

                <?php if (($producer['verification_status'] ?? '') === 'verified'): ?>
                    <span class="badge badge-success">Doğrulanmış Üretici</span>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($producer['description'])): ?>
            <section class="producer-description">
                <h2>Üretici Hakkında</h2>
                <p><?= nl2br(e($producer['description'])) ?></p>
            </section>
        <?php endif; ?>

        <section class="producer-stats">
            <div class="stat-card">
                <strong><?= (int) ($producer['active_product_count'] ?? 0) ?></strong>
                <span>Aktif Ürün</span>
            </div>

            <div class="stat-card">
                <strong><?= (int) ($producer['total_orders'] ?? 0) ?></strong>
                <span>Toplam Sipariş</span>
            </div>

            <div class="stat-card">
                <strong><?= e(producer_detail_rating($producer)) ?></strong>
                <span>Puan</span>
            </div>
        </section>

        <section class="producer-contact">
            <h2>İletişim</h2>

            <ul>
                <?php if (!empty($producer['contact_email']) || !empty($producer['email'])): ?>
                    <li>E-posta: <?= e($producer['contact_email'] ?: $producer['email']) ?></li>
                <?php endif; ?>

                <?php if (!empty($producer['contact_phone']) || !empty($producer['phone'])): ?>
                    <li>Telefon: <?= e($producer['contact_phone'] ?: $producer['phone']) ?></li>
                <?php endif; ?>

                <?php if (!empty($producer['contact_whatsapp']) || !empty($producer['whatsapp_phone'])): ?>
                    <li>WhatsApp: <?= e($producer['contact_whatsapp'] ?: $producer['whatsapp_phone']) ?></li>
                <?php endif; ?>

                <?php if (!empty($producer['shipping_note'])): ?>
                    <li>Gönderim Notu: <?= nl2br(e($producer['shipping_note'])) ?></li>
                <?php endif; ?>
            </ul>
        </section>
    </section>

    <section class="card producer-products-section">
        <h2>Üreticinin Ürünleri</h2>

        <?php if (empty($products)): ?>
            <div class="empty-state">
                Bu üreticinin aktif ürünü bulunmuyor.
            </div>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($products as $product): ?>
                    <article class="card product-card">
                        <?php if (!empty($product['cover_image'])): ?>
                            <img
                                class="product-image"
                                src="<?= e(url($product['cover_image'])) ?>"
                                alt="<?= e($product['title'] ?? 'Ürün') ?>"
                            >
                        <?php endif; ?>

                        <h3><?= e($product['title'] ?? 'Ürün') ?></h3>

                        <?php if (!empty($product['category_name'])): ?>
                            <p class="muted"><?= e($product['category_name']) ?></p>
                        <?php endif; ?>

                        <p>
                            <strong><?= e(producer_detail_money((float) ($product['price'] ?? 0))) ?></strong>
                            / <?= e(producer_detail_unit_label($product['unit_type'] ?? 'kg')) ?>
                        </p>

                        <p>
                            Stok:
                            <?= e((string) ($product['stock_quantity'] ?? 0)) ?>
                            <?= e(producer_detail_unit_label($product['unit_type'] ?? 'kg')) ?>
                        </p>

                        <a class="btn" href="<?= e(url('product-detail.php?id=' . (int) $product['id'])) ?>">
                            Ürünü Gör
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="card producer-reviews-section">
        <h2>Yorumlar</h2>

        <?php if (empty($reviews)): ?>
            <div class="empty-state">
                Bu üretici için henüz yorum yok.
            </div>
        <?php else: ?>
            <div class="review-list">
                <?php foreach ($reviews as $review): ?>
                    <article class="review-card">
                        <strong>⭐ <?= (int) ($review['rating'] ?? 0) ?>/5</strong>

                        <?php if (!empty($review['consumer_name'])): ?>
                            <p class="muted"><?= e($review['consumer_name']) ?></p>
                        <?php endif; ?>

                        <?php if (!empty($review['product_title'])): ?>
                            <p class="muted">Ürün: <?= e($review['product_title']) ?></p>
                        <?php endif; ?>

                        <?php if (!empty($review['comment'])): ?>
                            <p><?= nl2br(e($review['comment'])) ?></p>
                        <?php endif; ?>

                        <?php if (!empty($review['created_at'])): ?>
                            <small><?= e($review['created_at']) ?></small>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <div class="page-actions">
        <a class="btn btn-secondary" href="<?= e(url('producers.php')) ?>">
            Üreticilere Dön
        </a>
    </div>
</main>

<?php require APP_PATH . '/Views/layouts/footer.php'; ?>
