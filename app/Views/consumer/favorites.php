<?php require APP_PATH . '/Views/layouts/header.php'; ?>

<main class="container">
    <section class="card">
        <div class="favorites-header">
            <div>
                <h1>Favorilerim</h1>
                <p>Favoriye eklediğin ürünleri buradan görebilirsin.</p>
            </div>

            <a class="btn" href="<?= e(url('products.php')) ?>">
                Ürünlere Dön
            </a>
        </div>

        <?php if (empty($favorites)): ?>
            <div class="empty-state">
                <h2>Henüz favori ürünün yok</h2>
                <p>Ürün detay sayfasından favoriye ekleme yapınca burada görünecek.</p>

                <a class="btn" href="<?= e(url('products.php')) ?>">
                    Ürünleri Keşfet
                </a>
            </div>
        <?php else: ?>
            <div class="favorites-grid">
                <?php foreach ($favorites as $product): ?>
                    <?php
                        $unit = match ($product['unit_type'] ?? 'kg') {
                            'kg' => 'kg',
                            'piece' => 'adet',
                            'bunch' => 'demet',
                            'box' => 'kasa',
                            default => $product['unit_type'] ?? 'kg',
                        };

                        $stockQuantity = (float) ($product['stock_quantity'] ?? 0);
                        $isAvailable = ($product['status'] ?? '') === 'active' && $stockQuantity > 0;
                    ?>

                    <article class="favorite-card">
                        <a href="<?= e(url('product-detail.php?id=' . $product['id'])) ?>">
                            <?php if (!empty($product['cover_image'])): ?>
                                <img
                                    src="<?= e(url($product['cover_image'])) ?>"
                                    alt="<?= e($product['title']) ?>"
                                    class="favorite-image"
                                >
                            <?php else: ?>
                                <div class="favorite-image favorite-image-placeholder">
                                    Ürün Fotoğrafı
                                </div>
                            <?php endif; ?>
                        </a>

                        <div class="favorite-content">
                            <span class="badge <?= $isAvailable ? 'badge-success' : 'badge-warning' ?>">
                                <?= $isAvailable ? 'Stokta Var' : 'Stokta Yok' ?>
                            </span>

                            <h2>
                                <a href="<?= e(url('product-detail.php?id=' . $product['id'])) ?>">
                                    <?= e($product['title']) ?>
                                </a>
                            </h2>

                            <p>
                                <?= e($product['store_name'] ?: ($product['producer_name'] ?? 'Üretici')) ?>
                                ·
                                <?= e($product['province_name'] ?? '-') ?>
                                /
                                <?= e($product['district_name'] ?? '-') ?>
                            </p>

                            <p class="price">
                                <?= e(formatMoney($product['price'])) ?> / <?= e($unit) ?>
                            </p>

                            <p>
                                Stok: <?= e((string) $stockQuantity) ?> <?= e($unit) ?>
                            </p>

                            <div class="favorite-actions">
                                <a class="btn btn-secondary" href="<?= e(url('product-detail.php?id=' . $product['id'])) ?>">
                                    Ürünü Gör
                                </a>

                                <form method="POST" action="<?= e(url('api/favorite-toggle.php')) ?>">
                                    <?= csrf_field() ?>

                                    <input type="hidden" name="product_id" value="<?= e((string) $product['id']) ?>">
                                    <input type="hidden" name="return_to" value="consumer/favorites.php">

                                    <button class="btn btn-danger" type="submit">
                                        Favoriden Çıkar
                                    </button>
                                </form>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<style>
    .favorites-header {
        display: flex;
        justify-content: space-between;
        gap: 20px;
        align-items: flex-start;
        margin-bottom: 24px;
    }

    .favorites-header h1 {
        margin-bottom: 8px;
    }

    .favorites-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 20px;
    }

    .favorite-card {
        border: 1px solid #e7eee3;
        border-radius: 18px;
        overflow: hidden;
        background: #fff;
        box-shadow: 0 8px 22px rgba(0, 0, 0, .06);
    }

    .favorite-image {
        width: 100%;
        height: 190px;
        object-fit: cover;
        display: block;
        background: #edf6ed;
    }

    .favorite-image-placeholder {
        display: flex;
        align-items: center;
        justify-content: center;
        color: #2e7d32;
        font-weight: 700;
    }

    .favorite-content {
        padding: 16px;
    }

    .favorite-content h2 {
        font-size: 22px;
        margin: 12px 0 8px;
    }

    .favorite-content h2 a {
        color: #174d25;
        text-decoration: none;
    }

    .price {
        font-size: 20px;
        font-weight: 700;
        color: #2e7d32;
    }

    .badge {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 999px;
        font-size: 13px;
        font-weight: 700;
    }

    .badge-success {
        background: #e8f5e9;
        color: #2e7d32;
    }

    .badge-warning {
        background: #fff3cd;
        color: #856404;
    }

    .favorite-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 14px;
    }

    .favorite-actions form {
        margin: 0;
    }

    .btn-danger {
        background: #b42318;
        color: #fff;
    }

    .btn-danger:hover {
        background: #8f1d14;
    }

    .empty-state {
        text-align: center;
        padding: 42px 20px;
        background: #f7fbf5;
        border-radius: 16px;
    }

    @media (max-width: 1000px) {
        .favorites-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 700px) {
        .favorites-header {
            flex-direction: column;
        }

        .favorites-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<?php require APP_PATH . '/Views/layouts/footer.php'; ?>