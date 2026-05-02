<?php

require_once __DIR__ . '/../../app/bootstrap.php';

ProducerMiddleware::handle();

$controller = new ProductController();

if (is_post()) {
    $controller->store();
}

$data = $controller->createData();
$categories = $data['categories'] ?? [];
$formErrors = errors();

$pageTitle = 'Ürün Ekle';
$bodyClass = 'page-product-create';

$today = date('Y-m-d');
$oneYearAgo = date('Y-m-d', strtotime('-1 year'));
$oneYearAfter = date('Y-m-d', strtotime('+1 year'));

require APP_PATH . '/Views/layouts/header.php';

if (!function_exists('product_create_error')) {
    function product_create_error(array $formErrors, string $key): string
    {
        if (empty($formErrors[$key][0])) {
            return '';
        }

        return '<div class="field-error">' . e($formErrors[$key][0]) . '</div>';
    }
}

?>

<section class="page-section product-form-page">
    <div class="container">
        <div class="page-header">
            <h1>Yeni Ürün Ekle</h1>
            <p>Ürün adı, kategori, fiyat, stok, hasat tarihi ve ürün fotoğrafı bilgilerini girerek yeni ürününü yayınlayabilirsin.</p>
        </div>

        <?php if (!empty($formErrors['general'])): ?>
            <div class="alert alert-danger"><?= e($formErrors['general'][0]) ?></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" class="form-card product-form" id="productForm">
            <?= csrf_field() ?>

            <div class="form-grid">
                <div class="form-group">
                    <label for="title">Ürün Adı</label>
                    <input
                        type="text"
                        id="title"
                        name="title"
                        value="<?= e((string) old('title')) ?>"
                        required
                    >
                    <?= product_create_error($formErrors, 'title') ?>
                </div>

                <div class="form-group">
                    <label for="category_id">Kategori</label>
                    <select id="category_id" name="category_id" required>
                        <option value="">Kategori seç</option>
                        <?php foreach ($categories as $category): ?>
                            <option
                                value="<?= (int) $category['id'] ?>"
                                <?= (string) old('category_id') === (string) $category['id'] ? 'selected' : '' ?>
                            >
                                <?= e($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?= product_create_error($formErrors, 'category_id') ?>
                </div>

                <div class="form-group">
                    <label for="price">Fiyat</label>
                    <input
                        type="number"
                        id="price"
                        name="price"
                        step="0.01"
                        min="0.01"
                        value="<?= e((string) old('price')) ?>"
                        required
                    >
                    <?= product_create_error($formErrors, 'price') ?>
                </div>

                <div class="form-group">
                    <label for="unit_type">Birim</label>
                    <select id="unit_type" name="unit_type" required>
                        <option value="kg" <?= old('unit_type', 'kg') === 'kg' ? 'selected' : '' ?>>kg</option>
                        <option value="piece" <?= old('unit_type') === 'piece' ? 'selected' : '' ?>>adet</option>
                        <option value="bunch" <?= old('unit_type') === 'bunch' ? 'selected' : '' ?>>demet</option>
                        <option value="box" <?= old('unit_type') === 'box' ? 'selected' : '' ?>>kasa</option>
                    </select>
                    <?= product_create_error($formErrors, 'unit_type') ?>
                </div>

                <div class="form-group">
                    <label for="stock_quantity">Stok Miktarı</label>
                    <input
                        type="number"
                        id="stock_quantity"
                        name="stock_quantity"
                        step="0.001"
                        min="0"
                        value="<?= e((string) old('stock_quantity')) ?>"
                        required
                    >
                    <small>Üst sınır yoktur. Gram hassasiyeti için 0.001 adım desteklenir.</small>
                    <?= product_create_error($formErrors, 'stock_quantity') ?>
                </div>

                <div class="form-group">
                    <label for="status">Durum</label>
                    <select id="status" name="status">
                        <option value="active" <?= old('status', 'active') === 'active' ? 'selected' : '' ?>>Aktif</option>
                        <option value="draft" <?= old('status') === 'draft' ? 'selected' : '' ?>>Taslak</option>
                        <option value="paused" <?= old('status') === 'paused' ? 'selected' : '' ?>>Pasif</option>
                        <option value="sold_out" <?= old('status') === 'sold_out' ? 'selected' : '' ?>>Stokta Yok</option>
                    </select>
                    <?= product_create_error($formErrors, 'status') ?>
                </div>

                <div class="form-group">
                    <label for="harvest_date">Hasat Tarihi</label>
                    <input
                        type="date"
                        id="harvest_date"
                        name="harvest_date"
                        value="<?= e((string) old('harvest_date')) ?>"
                        min="<?= e($oneYearAgo) ?>"
                        max="<?= e($today) ?>"
                        data-normal-min="<?= e($oneYearAgo) ?>"
                        data-normal-max="<?= e($today) ?>"
                        data-preorder-min="<?= e($today) ?>"
                        data-preorder-max="<?= e($oneYearAfter) ?>"
                    >
                    <small id="harvestHelp">Normal üründe hasat tarihi bugünden en fazla 1 yıl önce olabilir.</small>
                    <?= product_create_error($formErrors, 'harvest_date') ?>
                </div>

                <div class="form-group">
                    <label for="image">Ürün Fotoğrafı</label>
                    <input type="file" id="image" name="image" accept="image/*">
                </div>

                <div class="form-group full checkbox-group">
                    <label>
                        <input
                            type="checkbox"
                            id="is_preorder_enabled"
                            name="is_preorder_enabled"
                            value="1"
                            <?= old('is_preorder_enabled') ? 'checked' : '' ?>
                        >
                        Ön siparişe açık
                    </label>
                </div>

                <div class="form-group preorder-field">
                    <label for="preorder_deadline">Ön Sipariş Son Tarihi</label>
                    <input
                        type="date"
                        id="preorder_deadline"
                        name="preorder_deadline"
                        value="<?= e((string) old('preorder_deadline')) ?>"
                        min="<?= e($today) ?>"
                        max="<?= e($oneYearAfter) ?>"
                    >
                    <?= product_create_error($formErrors, 'preorder_deadline') ?>
                </div>

                <div class="form-group preorder-field">
                    <label for="min_preorder_quantity">Minimum Ön Sipariş Miktarı</label>
                    <div class="inline-fields" style="display:flex; gap:8px; align-items:flex-start;">
                        <input
                            type="number"
                            id="min_preorder_quantity"
                            name="min_preorder_quantity"
                            step="0.001"
                            min="0"
                            value="<?= e((string) old('min_preorder_quantity')) ?>"
                        >

                        <select id="min_preorder_unit" name="min_preorder_unit">
                            <option value="kg" <?= old('min_preorder_unit', 'kg') === 'kg' ? 'selected' : '' ?>>kg</option>
                            <option value="g" <?= old('min_preorder_unit') === 'g' ? 'selected' : '' ?>>g</option>
                            <option value="piece" <?= old('min_preorder_unit') === 'piece' ? 'selected' : '' ?>>adet</option>
                        </select>
                    </div>
                    <?= product_create_error($formErrors, 'min_preorder_quantity') ?>
                    <?= product_create_error($formErrors, 'min_preorder_unit') ?>
                </div>

                <div class="form-group full">
                    <label for="description">Açıklama</label>
                    <textarea id="description" name="description" rows="5"><?= e((string) old('description')) ?></textarea>
                    <?= product_create_error($formErrors, 'description') ?>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Ürünü Kaydet</button>
                <a href="<?= e(url('producer/products.php')) ?>" class="btn btn-secondary">Ürünlerime Dön</a>
            </div>
        </form>
    </div>
</section>

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
            harvestHelp.textContent = 'Ön siparişte hasat tarihi bugünden en fazla 1 yıl sonrası olabilir.';
            if (preorderDeadline) {
                preorderDeadline.required = true;
            }
        } else {
            harvestInput.min = harvestInput.dataset.normalMin;
            harvestInput.max = harvestInput.dataset.normalMax;
            harvestHelp.textContent = 'Normal üründe hasat tarihi bugünden en fazla 1 yıl önce olabilir.';
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
