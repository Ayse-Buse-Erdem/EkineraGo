<?php

require_once __DIR__ . '/../../app/bootstrap.php';

ProducerMiddleware::handle();

$controller = new ProductController();

if (is_post()) {
    $controller->update();
}

$productId = (int) ($_GET['id'] ?? 0);
$producerId = (int) currentUserId();

$data = $controller->editData($productId, $producerId);

$product = $data['product'];
$images = $data['images'] ?? [];
$categories = $data['categories'] ?? [];
$formErrors = errors();

$pageTitle = 'Ürün Düzenle';
$bodyClass = 'page-product-edit';

$today = date('Y-m-d');
$oneYearAgo = date('Y-m-d', strtotime('-1 year'));
$oneYearAfter = date('Y-m-d', strtotime('+1 year'));

require APP_PATH . '/Views/layouts/header.php';

if (!function_exists('product_edit_value')) {
    function product_edit_value(string $key, array $product, mixed $default = ''): mixed
    {
        return old($key, $product[$key] ?? $default);
    }
}

if (!function_exists('product_edit_selected')) {
    function product_edit_selected(string $current, string $expected): string
    {
        return $current === $expected ? 'selected' : '';
    }
}
?>

<main class="container">
    <section class="card page-heading">
        <h1>Ürün Düzenle</h1>

        <p>
            Ürün bilgilerini güncelleyebilir, stok miktarını değiştirebilir veya ürün durumunu yönetebilirsin.
        </p>
    </section>

    <?php if (!empty($formErrors['general'])): ?>
        <section class="card form-alert">
            <?= e($formErrors['general'][0]) ?>
        </section>
    <?php endif; ?>

    <section class="edit-layout">
        <div class="card">
            <form method="POST" action="<?= e(url('producer/product-edit.php?id=' . $productId)) ?>" enctype="multipart/form-data" class="product-form">
                <?= csrf_field() ?>

                <input type="hidden" name="product_id" value="<?= e((string) $productId) ?>">

                <div class="form-grid">
                    <div class="form-group">
                        <label for="title">Ürün Adı</label>
                        <input
                            type="text"
                            id="title"
                            name="title"
                            value="<?= e((string) product_edit_value('title', $product)) ?>"
                            placeholder="Örn: Kumluca Domatesi"
                        >
                        <?php if (!empty($formErrors['title'])): ?>
                            <div class="field-error"><?= e($formErrors['title'][0]) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="category_id">Kategori</label>
                        <select id="category_id" name="category_id">
                            <option value="">Kategori seç</option>

                            <?php foreach ($categories as $category): ?>
                                <?php
                                    $selectedCategory = (string) product_edit_value(
                                        'category_id',
                                        $product,
                                        $product['category_id'] ?? ''
                                    );
                                ?>
                                <option
                                    value="<?= e((string) $category['id']) ?>"
                                    <?= $selectedCategory === (string) $category['id'] ? 'selected' : '' ?>
                                >
                                    <?= e($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!empty($formErrors['category_id'])): ?>
                            <div class="field-error"><?= e($formErrors['category_id'][0]) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="price">Fiyat</label>
                        <input
                            type="number"
                            id="price"
                            name="price"
                            step="0.01"
                            min="0"
                            value="<?= e((string) product_edit_value('price', $product)) ?>"
                            placeholder="35.00"
                        >
                        <?php if (!empty($formErrors['price'])): ?>
                            <div class="field-error"><?= e($formErrors['price'][0]) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="unit_type">Birim</label>
                        <?php $selectedUnit = (string) product_edit_value('unit_type', $product, 'kg'); ?>
                        <select id="unit_type" name="unit_type">
                            <option value="kg" <?= product_edit_selected($selectedUnit, 'kg') ?>>kg</option>
                            <option value="piece" <?= product_edit_selected($selectedUnit, 'piece') ?>>adet</option>
                            <option value="bunch" <?= product_edit_selected($selectedUnit, 'bunch') ?>>demet</option>
                            <option value="box" <?= product_edit_selected($selectedUnit, 'box') ?>>kasa</option>
                        </select>
                        <?php if (!empty($formErrors['unit_type'])): ?>
                            <div class="field-error"><?= e($formErrors['unit_type'][0]) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="stock_quantity">Stok Miktarı</label>
                        <input
                            type="number"
                            id="stock_quantity"
                            name="stock_quantity"
                            step="0.001"
                            min="0"
                            value="<?= e((string) product_edit_value('stock_quantity', $product)) ?>"
                            placeholder="100"
                        >
                        <small>Üst sınır yoktur. Gram hassasiyeti için 0.001 adım desteklenir.</small>
                        <?php if (!empty($formErrors['stock_quantity'])): ?>
                            <div class="field-error"><?= e($formErrors['stock_quantity'][0]) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="status">Durum</label>
                        <?php $selectedStatus = (string) product_edit_value('status', $product, 'active'); ?>
                        <select id="status" name="status">
                            <option value="active" <?= product_edit_selected($selectedStatus, 'active') ?>>Aktif</option>
                            <option value="draft" <?= product_edit_selected($selectedStatus, 'draft') ?>>Taslak</option>
                            <option value="paused" <?= product_edit_selected($selectedStatus, 'paused') ?>>Pasif</option>
                            <option value="sold_out" <?= product_edit_selected($selectedStatus, 'sold_out') ?>>Stokta Yok</option>
                        </select>
                        <?php if (!empty($formErrors['status'])): ?>
                            <div class="field-error"><?= e($formErrors['status'][0]) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="harvest_date">Hasat Tarihi</label>
                        <input
                            type="date"
                            id="harvest_date"
                            name="harvest_date"
                            value="<?= e((string) product_edit_value('harvest_date', $product)) ?>"
                            min="<?= e($oneYearAgo) ?>"
                            max="<?= e($today) ?>"
                            data-normal-min="<?= e($oneYearAgo) ?>"
                            data-normal-max="<?= e($today) ?>"
                            data-preorder-min="<?= e($today) ?>"
                            data-preorder-max="<?= e($oneYearAfter) ?>"
                        >
                        <small id="harvestHelp">Normal üründe hasat tarihi bugünden en fazla 1 yıl önce olabilir.</small>
                        <?php if (!empty($formErrors['harvest_date'])): ?>
                            <div class="field-error"><?= e($formErrors['harvest_date'][0]) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="image">Yeni Ürün Fotoğrafı</label>
                        <input type="file" id="image" name="image" accept="image/*">
                    </div>

                    <div class="form-group full checkbox-group">
                        <label>
                            <input
                                type="checkbox"
                                id="is_preorder_enabled"
                                name="is_preorder_enabled"
                                value="1"
                                <?= product_edit_value('is_preorder_enabled', $product) ? 'checked' : '' ?>
                            >
                            Ön siparişe açık
                        </label>
                    </div>

                    <div class="form-group">
                        <label for="preorder_deadline">Ön Sipariş Son Tarihi</label>
                        <input
                            type="date"
                            id="preorder_deadline"
                            name="preorder_deadline"
                            value="<?= e((string) product_edit_value('preorder_deadline', $product)) ?>"
                            min="<?= e($today) ?>"
                            max="<?= e($oneYearAfter) ?>"
                        >
                        <?php if (!empty($formErrors['preorder_deadline'])): ?>
                            <div class="field-error"><?= e($formErrors['preorder_deadline'][0]) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="min_preorder_quantity">Minimum Ön Sipariş Miktarı</label>

                        <?php $selectedPreorderUnit = (string) product_edit_value('min_preorder_unit', $product, 'kg'); ?>

                        <div class="inline-fields">
                            <input
                                type="number"
                                id="min_preorder_quantity"
                                name="min_preorder_quantity"
                                step="0.001"
                                min="0"
                                value="<?= e((string) product_edit_value('min_preorder_quantity', $product)) ?>"
                                placeholder="Örn: 2"
                            >

                            <select id="min_preorder_unit" name="min_preorder_unit">
                                <option value="kg" <?= product_edit_selected($selectedPreorderUnit, 'kg') ?>>kg</option>
                                <option value="g" <?= product_edit_selected($selectedPreorderUnit, 'g') ?>>g</option>
                                <option value="piece" <?= product_edit_selected($selectedPreorderUnit, 'piece') ?>>adet</option>
                            </select>
                        </div>

                        <?php if (!empty($formErrors['min_preorder_quantity'])): ?>
                            <div class="field-error"><?= e($formErrors['min_preorder_quantity'][0]) ?></div>
                        <?php endif; ?>

                        <?php if (!empty($formErrors['min_preorder_unit'])): ?>
                            <div class="field-error"><?= e($formErrors['min_preorder_unit'][0]) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group full">
                        <label for="description">Açıklama</label>
                        <textarea id="description" name="description" rows="4" placeholder="Ürün açıklaması..."><?= e((string) product_edit_value('description', $product)) ?></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <button class="btn" type="submit">
                        Ürünü Güncelle
                    </button>

                    <a class="btn btn-secondary" href="<?= e(url('producer/products.php')) ?>">
                        Ürünlerime Dön
                    </a>

                    <a class="btn btn-secondary" href="<?= e(url('product-detail.php?id=' . $productId)) ?>">
                        Public Sayfada Gör
                    </a>
                </div>
            </form>
        </div>

        <aside class="card image-panel">
            <h2>Ürün Görselleri</h2>

            <?php if (empty($images)): ?>
                <div class="image-empty">
                    Henüz ürün fotoğrafı yok.
                </div>
            <?php else: ?>
                <div class="image-list">
                    <?php foreach ($images as $image): ?>
                        <div class="image-item">
                            <img src="<?= e(url($image['image_path'])) ?>" alt="<?= e($product['title'] ?? 'Ürün') ?>">

                            <?php if (!empty($image['is_cover'])): ?>
                                <span>Kapak Görseli</span>
                            <?php else: ?>
                                <span>Ürün Görseli</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="product-meta-box">
                <h3>Ürün Bilgisi</h3>

                <p>
                    <strong>ID:</strong>
                    <?= e((string) $productId) ?>
                </p>

                <p>
                    <strong>Slug:</strong>
                    <?= e($product['slug'] ?? '-') ?>
                </p>

                <p>
                    <strong>Oluşturulma:</strong>
                    <?= !empty($product['created_at']) ? e(date('d.m.Y H:i', strtotime($product['created_at']))) : '-' ?>
                </p>
            </div>
        </aside>
    </section>
</main>

<style>
    .page-heading {
        margin-bottom: 22px;
    }

    .page-heading h1,
    .image-panel h2,
    .product-meta-box h3 {
        margin-top: 0;
        color: #245c2f;
    }

    .page-heading p {
        color: #526052;
        line-height: 1.5;
    }

    .form-alert {
        margin-bottom: 22px;
        background: #ffe8e8;
        color: #9b111e;
        font-weight: bold;
    }

    .edit-layout {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 22px;
    }

    .product-form label {
        display: block;
        margin-bottom: 7px;
        font-weight: bold;
        color: #245c2f;
    }

    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }

    .form-group.full {
        grid-column: 1 / -1;
    }

    .product-form input,
    .product-form select,
    .product-form textarea {
        width: 100%;
        padding: 11px;
        border: 1px solid #d5dccf;
        border-radius: 9px;
        font-family: Arial, sans-serif;
    }

    .checkbox-group label {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .checkbox-group input {
        width: auto;
    }

    .inline-fields {
        display: grid;
        grid-template-columns: 1fr 120px;
        gap: 8px;
    }

    .field-error {
        margin-top: 6px;
        color: #9b111e;
        font-size: 14px;
    }

    .form-actions {
        margin-top: 22px;
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }

    .image-empty {
        padding: 18px;
        border-radius: 12px;
        background: #f8fbf6;
        color: #526052;
    }

    .image-list {
        display: grid;
        gap: 14px;
    }

    .image-item {
        border: 1px solid #edf1ea;
        border-radius: 12px;
        padding: 10px;
    }

    .image-item img {
        width: 100%;
        height: 160px;
        object-fit: cover;
        border-radius: 10px;
        display: block;
        margin-bottom: 8px;
    }

    .image-item span {
        color: #526052;
        font-size: 14px;
        font-weight: bold;
    }

    .product-meta-box {
        margin-top: 22px;
        padding-top: 18px;
        border-top: 1px solid #edf1ea;
    }

    .product-meta-box p {
        color: #526052;
        line-height: 1.5;
    }

    @media (max-width: 1000px) {
        .edit-layout {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<script>
(function () {
    const preorderCheckbox = document.getElementById('is_preorder_enabled');
    const harvestInput = document.getElementById('harvest_date');
    const harvestHelp = document.getElementById('harvestHelp');
    const preorderDeadline = document.getElementById('preorder_deadline');

    function syncDateRules() {
        if (!preorderCheckbox || !harvestInput) {
            return;
        }

        if (preorderCheckbox.checked) {
            harvestInput.min = harvestInput.dataset.preorderMin;
            harvestInput.max = harvestInput.dataset.preorderMax;

            if (harvestHelp) {
                harvestHelp.textContent = 'Ön siparişte hasat tarihi bugünden en fazla 1 yıl sonrası olabilir.';
            }

            if (preorderDeadline) {
                preorderDeadline.required = true;
            }
        } else {
            harvestInput.min = harvestInput.dataset.normalMin;
            harvestInput.max = harvestInput.dataset.normalMax;

            if (harvestHelp) {
                harvestHelp.textContent = 'Normal üründe hasat tarihi bugünden en fazla 1 yıl önce olabilir.';
            }

            if (preorderDeadline) {
                preorderDeadline.required = false;
            }
        }
    }

    preorderCheckbox?.addEventListener('change', syncDateRules);
    syncDateRules();
})();
</script>

<?php require APP_PATH . '/Views/layouts/footer.php'; ?>
<?php clear_old(); ?>