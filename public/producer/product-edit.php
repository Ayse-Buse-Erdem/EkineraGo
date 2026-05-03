<?php

require_once __DIR__ . '/../../app/bootstrap.php';

ProducerMiddleware::handle();

$controller = new ProductController();
$productService = new ProductService();

$productId = (int) ($_GET['id'] ?? ($_POST['product_id'] ?? 0));
$producerId = (int) currentUserId();
$isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';

if (!function_exists('product_edit_json')) {
    function product_edit_json(array $payload): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

if (!function_exists('product_edit_flat_errors')) {
    function product_edit_flat_errors(array $errors): string
    {
        $messages = [];

        foreach ($errors as $fieldErrors) {
            foreach ((array) $fieldErrors as $message) {
                $messages[] = $message;
            }
        }

        return implode("\n", $messages);
    }
}

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

if (!function_exists('product_edit_checked')) {
    function product_edit_checked(mixed $value): string
    {
        return !empty($value) ? 'checked' : '';
    }
}

if (!function_exists('product_edit_error')) {
    function product_edit_error(array $formErrors, string $key): string
    {
        if (empty($formErrors[$key][0])) {
            return '';
        }

        return '<div class="field-error" data-error-for="' . e($key) . '">' . e($formErrors[$key][0]) . '</div>';
    }
}

if (!function_exists('product_edit_money')) {
    function product_edit_money(float $amount): string
    {
        if (function_exists('formatMoney')) {
            return formatMoney($amount);
        }

        return number_format($amount, 2, ',', '.') . ' TL';
    }
}

if (!function_exists('product_edit_number')) {
    function product_edit_number(float $number): string
    {
        return rtrim(rtrim(number_format($number, 3, ',', '.'), '0'), ',');
    }
}

if (!function_exists('product_edit_unit_label')) {
    function product_edit_unit_label(string $unit): string
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

if (!function_exists('product_edit_status_label')) {
    function product_edit_status_label(string $status): string
    {
        return match ($status) {
            'active' => 'Aktif',
            'draft' => 'Taslak',
            'paused' => 'Pasif',
            'sold_out' => 'Stokta Yok',
            'deleted' => 'Silindi',
            default => 'Bilinmiyor',
        };
    }
}

if (!function_exists('product_edit_status_badge')) {
    function product_edit_status_badge(string $status): string
    {
        return match ($status) {
            'active' => 'badge-success',
            'draft' => 'badge-info',
            'paused' => 'badge-muted',
            'sold_out' => 'badge-warning',
            'deleted' => 'badge-danger',
            default => 'badge-muted',
        };
    }
}

if (!function_exists('product_edit_date')) {
    function product_edit_date(?string $date): string
    {
        if (!$date) {
            return '-';
        }

        $timestamp = strtotime($date);

        if (!$timestamp) {
            return $date;
        }

        return date('d.m.Y H:i', $timestamp);
    }
}

if (!function_exists('product_edit_date_short')) {
    function product_edit_date_short(?string $date): string
    {
        if (!$date) {
            return '-';
        }

        $timestamp = strtotime($date);

        if (!$timestamp) {
            return $date;
        }

        return date('d.m.Y', $timestamp);
    }
}

if (!function_exists('product_edit_cover_image')) {
    function product_edit_cover_image(array $images): string
    {
        foreach ($images as $image) {
            if (!empty($image['is_cover'])) {
                return $image['image_path'] ?? '';
            }
        }

        return $images[0]['image_path'] ?? '';
    }
}

if (!function_exists('product_edit_payload')) {
    function product_edit_payload(array $product, array $images): array
    {
        $coverImage = product_edit_cover_image($images);

        return [
            'title' => $product['title'] ?? 'Ürün',
            'category' => $product['category_name'] ?? 'Kategori yok',
            'description' => $product['description'] ?? '',
            'price' => product_edit_money((float) ($product['price'] ?? 0)),
            'stock' => product_edit_number((float) ($product['stock_quantity'] ?? 0)) . ' ' . product_edit_unit_label($product['unit_type'] ?? 'kg'),
            'unit' => product_edit_unit_label($product['unit_type'] ?? 'kg'),
            'status' => product_edit_status_label($product['status'] ?? 'draft'),
            'status_class' => product_edit_status_badge($product['status'] ?? 'draft'),
            'harvest_date' => product_edit_date_short($product['harvest_date'] ?? null),
            'preorder' => !empty($product['is_preorder_enabled']) ? 'Açık' : 'Kapalı',
            'cover_image_url' => $coverImage ? url($coverImage) : '',
        ];
    }
}

if (!function_exists('product_edit_render_image_panel')) {
    function product_edit_render_image_panel(array $product, array $images, int $productId): string
    {
        ob_start();
        ?>

        <div class="image-panel-header">
            <div>
                <span class="section-kicker">Görsel ve Meta</span>
                <h2>Ürün Görselleri</h2>
                <p>Yeni görsel yüklersen liste otomatik güncellenir.</p>
            </div>

            <a class="edit-btn edit-btn-light" href="<?= e(url('product-detail.php?id=' . $productId)) ?>">
                Public Gör
            </a>
        </div>

        <?php if (empty($images)): ?>
            <div class="image-empty">
                <span>🖼️</span>
                <strong>Henüz ürün fotoğrafı yok.</strong>
                <p>Yeni görsel seçip ürünü güncellediğinde burada görünecek.</p>
            </div>
        <?php else: ?>
            <div class="image-list">
                <?php foreach ($images as $image): ?>
                    <div class="image-item">
                        <img src="<?= e(url($image['image_path'])) ?>" alt="<?= e($product['title'] ?? 'Ürün') ?>">

                        <?php if (!empty($image['is_cover'])): ?>
                            <span class="image-label cover">Kapak Görseli</span>
                        <?php else: ?>
                            <span class="image-label">Ürün Görseli</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="product-meta-box">
            <h3>Ürün Bilgisi</h3>

            <div class="meta-row">
                <span>ID</span>
                <strong><?= e((string) $productId) ?></strong>
            </div>

            <div class="meta-row">
                <span>Slug</span>
                <strong><?= e($product['slug'] ?? '-') ?></strong>
            </div>

            <div class="meta-row">
                <span>Durum</span>
                <strong>
                    <em class="product-badge <?= e(product_edit_status_badge($product['status'] ?? 'draft')) ?>">
                        <?= e(product_edit_status_label($product['status'] ?? 'draft')) ?>
                    </em>
                </strong>
            </div>

            <div class="meta-row">
                <span>Oluşturulma</span>
                <strong><?= e(product_edit_date($product['created_at'] ?? null)) ?></strong>
            </div>

            <div class="meta-row">
                <span>Güncelleme</span>
                <strong><?= e(product_edit_date($product['updated_at'] ?? null)) ?></strong>
            </div>
        </div>

        <?php

        return ob_get_clean();
    }
}

if (is_post()) {
    require_csrf();

    $productId = (int) ($_POST['product_id'] ?? 0);
    $result = $productService->updateProduct($producerId, $productId, $_POST, $_FILES);

    if ($isAjax) {
        if (!$result['success']) {
            product_edit_json([
                'success' => false,
                'message' => product_edit_flat_errors($result['errors'] ?? []) ?: 'Ürün güncellenemedi.',
                'errors' => $result['errors'] ?? [],
            ]);
        }

        $updatedData = $controller->editData($productId, $producerId);
        $updatedProduct = $updatedData['product'];
        $updatedImages = $updatedData['images'] ?? [];

        product_edit_json([
            'success' => true,
            'message' => $result['message'] ?? 'Ürün başarıyla güncellendi.',
            'product' => product_edit_payload($updatedProduct, $updatedImages),
            'imagePanelHtml' => product_edit_render_image_panel($updatedProduct, $updatedImages, $productId),
        ]);
    }

    if (!$result['success']) {
        set_old($_POST);
        set_errors($result['errors'] ?? []);
        flash_error('Ürün güncellenemedi.');
        redirect('producer/product-edit.php?id=' . $productId);
    }

    flash_success($result['message'] ?? 'Ürün başarıyla güncellendi.');
    redirect('producer/products.php');
}

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

$coverImage = product_edit_cover_image($images);
$productPayload = product_edit_payload($product, $images);

require APP_PATH . '/Views/layouts/header.php';
?>

<main class="product-edit-page">
    <section class="product-edit-hero">
        <div class="product-edit-hero-bg edit-blob-one"></div>
        <div class="product-edit-hero-bg edit-blob-two"></div>

        <div class="product-edit-hero-inner">
            <nav class="product-edit-breadcrumb" aria-label="Sayfa yolu">
                <a href="<?= e(url('index.php')) ?>">Ana Sayfa</a>
                <span>/</span>
                <a href="<?= e(url('producer/dashboard.php')) ?>">Üretici Paneli</a>
                <span>/</span>
                <a href="<?= e(url('producer/products.php')) ?>">Ürünlerim</a>
                <span>/</span>
                <strong>Ürün Düzenle</strong>
            </nav>

            <div class="product-edit-hero-content">
                <div class="product-edit-hero-copy">
                    <span class="product-edit-eyebrow">
                        Ürün Güncelleme
                    </span>

                    <h1 id="hero-product-title">
                        <?= e($product['title'] ?? 'Ürün Düzenle') ?>
                    </h1>

                    <p>
                        Ürün bilgilerini, fiyatı, stok miktarını, hasat tarihini, ön sipariş ayarlarını
                        ve görselleri AJAX ile sayfa yenilenmeden güncelleyebilirsin.
                    </p>

                    <div class="product-edit-hero-actions">
                        <a class="edit-btn edit-btn-glass" href="<?= e(url('producer/products.php')) ?>">
                            Ürünlerime Dön
                        </a>

                        <a class="edit-btn edit-btn-glass" href="<?= e(url('product-detail.php?id=' . $productId)) ?>">
                            Public Sayfada Gör
                        </a>
                    </div>
                </div>

                <div class="product-edit-hero-card">
                    <div class="hero-card-icon">🧺</div>

                    <h2>Güncel bilgi, güçlü satış</h2>

                    <p>
                        Stok, fiyat ve açıklama bilgilerini güncel tutarak tüketici güvenini artırabilirsin.
                    </p>

                    <div class="hero-mini-list">
                        <span>🌱 Stok</span>
                        <span>💰 Fiyat</span>
                        <span>📦 Durum</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="product-edit-shell">
        <?php if (!empty($formErrors['general'])): ?>
            <div class="edit-message error">
                <?= e($formErrors['general'][0]) ?>
            </div>
        <?php endif; ?>

        <div id="product-edit-message" class="edit-message" hidden></div>

        <section class="product-edit-layout">
            <section class="product-edit-form-card glass-card">
                <div class="section-heading">
                    <span class="section-icon">✏️</span>

                    <div>
                        <h2>Ürün Bilgileri</h2>
                        <p>Form AJAX ile gönderilir; hata varsa alanların altında gösterilir.</p>
                    </div>
                </div>

                <form
                    method="POST"
                    action="<?= e(url('producer/product-edit.php?id=' . $productId)) ?>"
                    enctype="multipart/form-data"
                    class="product-edit-form"
                    id="product-edit-form"
                    novalidate
                >
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
                                required
                            >

                            <?= product_edit_error($formErrors, 'title') ?>
                        </div>

                        <div class="form-group">
                            <label for="category_id">Kategori</label>

                            <select id="category_id" name="category_id" required>
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

                            <?= product_edit_error($formErrors, 'category_id') ?>
                        </div>

                        <div class="form-group">
                            <label for="price">Fiyat</label>

                            <div class="input-with-suffix">
                                <input
                                    type="number"
                                    id="price"
                                    name="price"
                                    step="0.01"
                                    min="0.01"
                                    value="<?= e((string) product_edit_value('price', $product)) ?>"
                                    placeholder="35.00"
                                    required
                                >

                                <span>TL</span>
                            </div>

                            <?= product_edit_error($formErrors, 'price') ?>
                        </div>

                        <div class="form-group">
                            <label for="unit_type">Birim</label>

                            <?php $selectedUnit = (string) product_edit_value('unit_type', $product, 'kg'); ?>

                            <select id="unit_type" name="unit_type" required>
                                <option value="kg" <?= product_edit_selected($selectedUnit, 'kg') ?>>kg</option>
                                <option value="piece" <?= product_edit_selected($selectedUnit, 'piece') ?>>adet</option>
                                <option value="bunch" <?= product_edit_selected($selectedUnit, 'bunch') ?>>demet</option>
                                <option value="box" <?= product_edit_selected($selectedUnit, 'box') ?>>kasa</option>
                            </select>

                            <?= product_edit_error($formErrors, 'unit_type') ?>
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
                                required
                            >

                            <small>Üst sınır yoktur. Gram hassasiyeti için 0.001 adım desteklenir.</small>

                            <?= product_edit_error($formErrors, 'stock_quantity') ?>
                        </div>

                        <div class="form-group">
                            <label for="status">Durum</label>

                            <?php $selectedStatus = (string) product_edit_value('status', $product, 'active'); ?>

                            <select id="status" name="status" required>
                                <option value="active" <?= product_edit_selected($selectedStatus, 'active') ?>>Aktif</option>
                                <option value="draft" <?= product_edit_selected($selectedStatus, 'draft') ?>>Taslak</option>
                                <option value="paused" <?= product_edit_selected($selectedStatus, 'paused') ?>>Pasif</option>
                                <option value="sold_out" <?= product_edit_selected($selectedStatus, 'sold_out') ?>>Stokta Yok</option>
                            </select>

                            <?= product_edit_error($formErrors, 'status') ?>
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

                            <small id="harvestHelp">
                                Normal üründe hasat tarihi bugünden en fazla 1 yıl önce olabilir.
                            </small>

                            <?= product_edit_error($formErrors, 'harvest_date') ?>
                        </div>

                        <div class="form-group">
                            <label for="image">Yeni Ürün Fotoğrafı</label>

                            <div class="file-box">
                                <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp,image/*">
                                <small>Yeni görsel yüklersen mevcut görsellerin yanına eklenir.</small>
                            </div>

                            <?= product_edit_error($formErrors, 'image') ?>
                        </div>

                        <div class="form-group full">
                            <label class="switch-line">
                                <input
                                    type="checkbox"
                                    id="is_preorder_enabled"
                                    name="is_preorder_enabled"
                                    value="1"
                                    <?= product_edit_checked(product_edit_value('is_preorder_enabled', $product)) ?>
                                >

                                <span></span>

                                <strong>Ön siparişe açık</strong>
                            </label>

                            <small>Ürün henüz hasat edilmediyse tüketicilerden ön talep toplayabilirsin.</small>
                        </div>

                        <div class="form-group preorder-field">
                            <label for="preorder_deadline">Ön Sipariş Son Tarihi</label>

                            <input
                                type="date"
                                id="preorder_deadline"
                                name="preorder_deadline"
                                value="<?= e((string) product_edit_value('preorder_deadline', $product)) ?>"
                                min="<?= e($today) ?>"
                                max="<?= e($oneYearAfter) ?>"
                            >

                            <?= product_edit_error($formErrors, 'preorder_deadline') ?>
                        </div>

                        <div class="form-group preorder-field">
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

                            <?= product_edit_error($formErrors, 'min_preorder_quantity') ?>
                            <?= product_edit_error($formErrors, 'min_preorder_unit') ?>
                        </div>

                        <div class="form-group full">
                            <label for="description">Açıklama</label>

                            <textarea
                                id="description"
                                name="description"
                                rows="5"
                                placeholder="Ürün açıklaması..."
                            ><?= e((string) product_edit_value('description', $product)) ?></textarea>

                            <?= product_edit_error($formErrors, 'description') ?>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button class="edit-btn edit-btn-primary" type="submit" id="product-edit-submit">
                            Ürünü Güncelle
                        </button>

                        <a class="edit-btn edit-btn-light" href="<?= e(url('producer/products.php')) ?>">
                            Ürünlerime Dön
                        </a>

                        <a class="edit-btn edit-btn-light" href="<?= e(url('product-detail.php?id=' . $productId)) ?>">
                            Public Sayfada Gör
                        </a>
                    </div>
                </form>
            </section>

            <aside class="product-side-column">
                <section class="product-preview-card glass-card">
                    <div class="preview-image" id="preview-image">
                        <?php if (!empty($coverImage)): ?>
                            <img src="<?= e(url($coverImage)) ?>" alt="<?= e($product['title'] ?? 'Ürün') ?>">
                        <?php else: ?>
                            <span>🥬</span>
                            <strong>Ürün Fotoğrafı</strong>
                        <?php endif; ?>
                    </div>

                    <div class="preview-body">
                        <span class="preview-badge <?= e($productPayload['status_class']) ?>" id="preview-status">
                            <?= e($productPayload['status']) ?>
                        </span>

                        <h2 id="preview-title"><?= e($productPayload['title']) ?></h2>

                        <p id="preview-description">
                            <?= e($productPayload['description'] ?: 'Ürün açıklaması burada görünecek.') ?>
                        </p>

                        <div class="preview-price">
                            <strong id="preview-price"><?= e($productPayload['price']) ?></strong>
                            <span id="preview-unit">/ <?= e($productPayload['unit']) ?></span>
                        </div>

                        <div class="preview-meta-grid">
                            <div>
                                <span>Stok</span>
                                <strong id="preview-stock"><?= e($productPayload['stock']) ?></strong>
                            </div>

                            <div>
                                <span>Kategori</span>
                                <strong id="preview-category"><?= e($productPayload['category']) ?></strong>
                            </div>

                            <div>
                                <span>Hasat</span>
                                <strong id="preview-harvest"><?= e($productPayload['harvest_date']) ?></strong>
                            </div>

                            <div>
                                <span>Ön Sipariş</span>
                                <strong id="preview-preorder"><?= e($productPayload['preorder']) ?></strong>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="image-panel glass-card" id="product-image-panel">
                    <?= product_edit_render_image_panel($product, $images, $productId) ?>
                </section>
            </aside>
        </section>
    </section>
</main>

<style>
    :root {
        --edit-green-950: #102f1a;
        --edit-green-900: #163d22;
        --edit-green-800: #245c2f;
        --edit-green-700: #2f7d3d;
        --edit-green-600: #3f9650;
        --edit-green-100: #eaf6e8;
        --edit-green-50: #f6fbf4;
        --edit-cream: #fffaf1;
        --edit-yellow: #f2bf4d;
        --edit-red: #9b111e;
        --edit-text: #1e2b21;
        --edit-muted: #687669;
        --edit-border: rgba(47, 125, 61, .14);
        --edit-shadow: 0 24px 70px rgba(31, 79, 43, .12);
        --edit-radius-lg: 28px;
    }

    body.page-product-edit {
        background:
            radial-gradient(circle at 14% 12%, rgba(196, 231, 177, .48), transparent 28%),
            radial-gradient(circle at 88% 16%, rgba(242, 191, 77, .16), transparent 24%),
            linear-gradient(180deg, #f8fbf2 0%, #f3f8ed 48%, #ffffff 100%);
    }

    .product-edit-page {
        overflow: hidden;
    }

    .product-edit-hero {
        position: relative;
        min-height: 390px;
        padding: 34px 18px 94px;
        background:
            radial-gradient(circle at 82% 18%, rgba(242, 191, 77, .30), transparent 26%),
            radial-gradient(circle at 12% 78%, rgba(255, 255, 255, .16), transparent 24%),
            linear-gradient(135deg, rgba(36, 92, 47, .97), rgba(47, 125, 61, .87));
        color: #ffffff;
    }

    .product-edit-hero::after {
        content: '';
        position: absolute;
        inset: auto 0 -1px;
        height: 90px;
        background: linear-gradient(180deg, rgba(246, 251, 244, 0), #f6fbf4 82%);
        pointer-events: none;
    }

    .product-edit-hero-inner,
    .product-edit-shell {
        width: min(1180px, calc(100% - 32px));
        margin: 0 auto;
    }

    .product-edit-hero-inner {
        position: relative;
        z-index: 2;
    }

    .product-edit-hero-bg {
        position: absolute;
        border-radius: 999px;
        filter: blur(2px);
        opacity: .45;
        pointer-events: none;
    }

    .edit-blob-one {
        width: 230px;
        height: 230px;
        right: 10%;
        top: 42px;
        background: rgba(242, 191, 77, .28);
    }

    .edit-blob-two {
        width: 150px;
        height: 150px;
        left: 8%;
        bottom: 36px;
        background: rgba(255, 255, 255, .20);
    }

    .product-edit-breadcrumb {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 9px;
        font-size: 14px;
        margin-bottom: 32px;
        color: rgba(255, 255, 255, .76);
    }

    .product-edit-breadcrumb a {
        color: #ffffff;
        text-decoration: none;
        font-weight: 800;
    }

    .product-edit-breadcrumb strong {
        color: #ffffff;
        font-weight: 900;
    }

    .product-edit-hero-content {
        display: grid;
        grid-template-columns: minmax(0, 1.25fr) minmax(320px, .75fr);
        gap: 24px;
        align-items: center;
    }

    .product-edit-eyebrow,
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

    .product-edit-eyebrow {
        background: rgba(255, 255, 255, .16);
        border: 1px solid rgba(255, 255, 255, .28);
        color: #ffffff;
    }

    .section-kicker {
        background: var(--edit-green-100);
        color: var(--edit-green-800);
        border-color: transparent;
    }

    .product-edit-hero-copy h1 {
        margin: 17px 0 12px;
        font-size: clamp(36px, 5vw, 62px);
        line-height: 1.03;
        letter-spacing: -.045em;
    }

    .product-edit-hero-copy p {
        max-width: 700px;
        margin: 0;
        color: rgba(255, 255, 255, .86);
        font-size: 17px;
        line-height: 1.7;
    }

    .product-edit-hero-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 22px;
    }

    .product-edit-hero-card {
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

    .product-edit-hero-card h2 {
        margin: 0 0 10px;
        font-size: 25px;
        letter-spacing: -.03em;
    }

    .product-edit-hero-card p {
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

    .product-edit-shell {
        position: relative;
        z-index: 3;
        margin-top: -64px;
        padding-bottom: 54px;
    }

    .glass-card {
        background: rgba(255, 255, 255, .92);
        border: 1px solid rgba(255, 255, 255, .72);
        border-radius: var(--edit-radius-lg);
        box-shadow: var(--edit-shadow);
        backdrop-filter: blur(16px);
    }

    .edit-message {
        margin-bottom: 16px;
        padding: 14px 16px;
        border-radius: 18px;
        font-weight: 900;
        white-space: pre-line;
        box-shadow: 0 12px 30px rgba(31, 79, 43, .10);
    }

    .edit-message.success {
        background: #e7f7e8;
        color: #236b2c;
        border: 1px solid rgba(35, 107, 44, .14);
    }

    .edit-message.error {
        background: #fdeaea;
        color: var(--edit-red);
        border: 1px solid rgba(155, 17, 30, .14);
    }

    .product-edit-layout {
        display: grid;
        grid-template-columns: minmax(0, 1.35fr) minmax(330px, .65fr);
        gap: 22px;
        align-items: start;
    }

    .product-edit-form-card,
    .product-preview-card,
    .image-panel {
        padding: 20px;
    }

    .product-side-column {
        display: grid;
        gap: 22px;
        position: sticky;
        top: 22px;
    }

    .section-heading {
        display: flex;
        align-items: flex-start;
        gap: 13px;
        margin-bottom: 18px;
        padding-bottom: 16px;
        border-bottom: 1px solid rgba(47, 125, 61, .10);
    }

    .section-icon {
        width: 46px;
        height: 46px;
        display: grid;
        place-items: center;
        border-radius: 16px;
        background: var(--edit-green-100);
        font-size: 21px;
        flex: 0 0 auto;
    }

    .section-heading h2 {
        margin: 0 0 5px;
        color: var(--edit-green-900);
        font-size: 28px;
        letter-spacing: -.03em;
    }

    .section-heading p {
        margin: 0;
        color: var(--edit-muted);
        line-height: 1.6;
    }

    .product-edit-form {
        display: grid;
        gap: 20px;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }

    .form-group {
        display: grid;
        gap: 8px;
    }

    .form-group.full {
        grid-column: 1 / -1;
    }

    .form-group label {
        color: var(--edit-green-900);
        font-weight: 900;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        border: 1px solid rgba(47, 125, 61, .18);
        border-radius: 15px;
        padding: 13px 14px;
        font: inherit;
        background: #ffffff;
        color: var(--edit-text);
        box-sizing: border-box;
        outline: none;
        transition: border-color .18s ease, box-shadow .18s ease;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        border-color: var(--edit-green-700);
        box-shadow: 0 0 0 4px rgba(47, 125, 61, .10);
    }

    .form-group small {
        color: var(--edit-muted);
        font-weight: 700;
        line-height: 1.5;
    }

    .input-with-suffix,
    .inline-fields {
        display: grid;
        gap: 8px;
        align-items: center;
    }

    .input-with-suffix {
        grid-template-columns: minmax(0, 1fr) 54px;
        padding: 7px;
        border-radius: 17px;
        background: var(--edit-green-50);
        border: 1px solid var(--edit-border);
    }

    .input-with-suffix input {
        border: 0;
    }

    .input-with-suffix span {
        color: var(--edit-green-800);
        font-weight: 900;
        text-align: center;
    }

    .inline-fields {
        grid-template-columns: minmax(0, 1fr) 120px;
    }

    .file-box {
        padding: 14px;
        border-radius: 18px;
        background: var(--edit-green-50);
        border: 1px dashed rgba(47, 125, 61, .26);
    }

    .file-box input {
        background: #ffffff;
    }

    .switch-line {
        display: inline-flex !important;
        align-items: center;
        gap: 11px;
        padding: 14px;
        border-radius: 18px;
        background: var(--edit-green-50);
        border: 1px solid var(--edit-border);
        cursor: pointer;
    }

    .switch-line input {
        display: none;
    }

    .switch-line span {
        position: relative;
        width: 52px;
        height: 30px;
        border-radius: 999px;
        background: #d8e1d6;
        transition: background .2s ease;
    }

    .switch-line span::after {
        content: '';
        position: absolute;
        top: 4px;
        left: 4px;
        width: 22px;
        height: 22px;
        border-radius: 999px;
        background: #ffffff;
        box-shadow: 0 4px 12px rgba(0, 0, 0, .18);
        transition: transform .2s ease;
    }

    .switch-line input:checked + span {
        background: var(--edit-green-700);
    }

    .switch-line input:checked + span::after {
        transform: translateX(22px);
    }

    .preorder-field.is-hidden {
        display: none;
    }

    .field-error {
        color: var(--edit-red);
        font-size: 13px;
        font-weight: 900;
        line-height: 1.4;
    }

    .form-actions {
        display: flex;
        justify-content: flex-end;
        flex-wrap: wrap;
        gap: 10px;
        padding-top: 18px;
        border-top: 1px solid rgba(47, 125, 61, .10);
    }

    .edit-btn {
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
        white-space: nowrap;
    }

    .edit-btn:hover {
        transform: translateY(-2px);
    }

    .edit-btn-primary {
        background: linear-gradient(135deg, var(--edit-green-700), var(--edit-green-900));
        color: #ffffff;
        box-shadow: 0 16px 32px rgba(47, 125, 61, .24);
    }

    .edit-btn-light {
        background: var(--edit-green-50);
        color: var(--edit-green-800);
        border: 1px solid var(--edit-border);
    }

    .edit-btn-glass {
        background: rgba(255, 255, 255, .16);
        color: #ffffff;
        border: 1px solid rgba(255, 255, 255, .28);
    }

    .edit-btn:disabled {
        opacity: .68;
        cursor: not-allowed;
        transform: none;
    }

    .preview-image {
        height: 240px;
        margin: -20px -20px 0;
        display: grid;
        place-items: center;
        align-content: center;
        gap: 8px;
        background:
            radial-gradient(circle at 30% 25%, rgba(255, 255, 255, .75), transparent 28%),
            linear-gradient(135deg, #e8f3e9, #f8fbf6);
        color: var(--edit-green-700);
        font-weight: 900;
        overflow: hidden;
        border-radius: var(--edit-radius-lg) var(--edit-radius-lg) 0 0;
    }

    .preview-image span {
        font-size: 48px;
    }

    .preview-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .preview-body {
        padding-top: 18px;
    }

    .preview-badge,
    .product-badge {
        display: inline-flex;
        padding: 8px 11px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 900;
        font-style: normal;
        white-space: nowrap;
    }

    .badge-success {
        background: #e7f7e8;
        color: #236b2c;
    }

    .badge-info {
        background: #e8f1ff;
        color: #1f4e8c;
    }

    .badge-warning {
        background: #fff5d6;
        color: #8a6200;
    }

    .badge-muted {
        background: #edf1ea;
        color: #526052;
    }

    .badge-danger {
        background: #ffe8e8;
        color: #9b111e;
    }

    .preview-body h2 {
        margin: 14px 0 8px;
        color: var(--edit-green-900);
        font-size: 26px;
        letter-spacing: -.03em;
    }

    .preview-body p {
        margin: 0;
        color: var(--edit-muted);
        line-height: 1.6;
    }

    .preview-price {
        display: flex;
        align-items: baseline;
        gap: 5px;
        margin: 16px 0 14px;
    }

    .preview-price strong {
        color: var(--edit-green-800);
        font-size: 28px;
        letter-spacing: -.03em;
    }

    .preview-price span {
        color: var(--edit-muted);
        font-weight: 900;
    }

    .preview-meta-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }

    .preview-meta-grid div,
    .meta-row {
        padding: 12px;
        border-radius: 16px;
        background: #fbfdf8;
        border: 1px solid var(--edit-border);
    }

    .preview-meta-grid span,
    .preview-meta-grid strong,
    .meta-row span,
    .meta-row strong {
        display: block;
    }

    .preview-meta-grid span,
    .meta-row span {
        margin-bottom: 5px;
        color: var(--edit-muted);
        font-size: 12px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .preview-meta-grid strong,
    .meta-row strong {
        color: var(--edit-green-900);
        font-size: 14px;
        overflow-wrap: anywhere;
    }

    .image-panel-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 14px;
        margin-bottom: 16px;
        padding-bottom: 16px;
        border-bottom: 1px solid rgba(47, 125, 61, .10);
    }

    .image-panel-header h2 {
        margin: 10px 0 5px;
        color: var(--edit-green-900);
        font-size: 25px;
        letter-spacing: -.03em;
    }

    .image-panel-header p {
        margin: 0;
        color: var(--edit-muted);
        line-height: 1.5;
    }

    .image-empty {
        display: grid;
        place-items: center;
        text-align: center;
        gap: 8px;
        padding: 28px 18px;
        border-radius: 20px;
        background: #fbfdf8;
        border: 1px dashed rgba(47, 125, 61, .24);
        color: var(--edit-muted);
    }

    .image-empty span {
        font-size: 34px;
    }

    .image-empty strong {
        color: var(--edit-green-900);
    }

    .image-empty p {
        margin: 0;
    }

    .image-list {
        display: grid;
        gap: 14px;
    }

    .image-item {
        position: relative;
        overflow: hidden;
        border-radius: 18px;
        border: 1px solid var(--edit-border);
        background: #ffffff;
    }

    .image-item img {
        width: 100%;
        height: 170px;
        object-fit: cover;
        display: block;
    }

    .image-label {
        position: absolute;
        left: 12px;
        top: 12px;
        padding: 7px 10px;
        border-radius: 999px;
        background: rgba(255, 255, 255, .92);
        color: var(--edit-green-800);
        font-size: 12px;
        font-weight: 900;
        box-shadow: 0 8px 20px rgba(31, 79, 43, .10);
    }

    .image-label.cover {
        background: #e7f7e8;
        color: #236b2c;
    }

    .product-meta-box {
        margin-top: 18px;
        padding-top: 18px;
        border-top: 1px solid rgba(47, 125, 61, .10);
    }

    .product-meta-box h3 {
        margin: 0 0 12px;
        color: var(--edit-green-900);
        font-size: 22px;
        letter-spacing: -.02em;
    }

    .product-meta-box {
        display: grid;
        gap: 10px;
    }

    .form-loading {
        pointer-events: none;
        opacity: .72;
    }

    @media (max-width: 1060px) {
        .product-edit-hero-content,
        .product-edit-layout {
            grid-template-columns: 1fr;
        }

        .product-side-column {
            position: static;
        }
    }

    @media (max-width: 720px) {
        .product-edit-hero {
            min-height: 430px;
            padding-top: 24px;
        }

        .product-edit-hero-inner,
        .product-edit-shell {
            width: min(100% - 22px, 1180px);
        }

        .product-edit-breadcrumb {
            font-size: 13px;
            margin-bottom: 24px;
        }

        .product-edit-hero-copy p {
            font-size: 15px;
        }

        .product-edit-shell {
            margin-top: -52px;
        }

        .product-edit-form-card,
        .product-preview-card,
        .image-panel,
        .product-edit-hero-card {
            padding: 14px;
            border-radius: 23px;
        }

        .form-grid,
        .inline-fields {
            grid-template-columns: 1fr;
        }

        .section-heading h2 {
            font-size: 24px;
        }

        .form-actions,
        .product-edit-hero-actions {
            justify-content: stretch;
        }

        .form-actions .edit-btn,
        .product-edit-hero-actions .edit-btn,
        .image-panel-header .edit-btn {
            width: 100%;
        }

        .image-panel-header {
            flex-direction: column;
        }

        .preview-image {
            margin: -14px -14px 0;
            border-radius: 23px 23px 0 0;
        }

        .preview-meta-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('product-edit-form');
        const submitButton = document.getElementById('product-edit-submit');
        const messageBox = document.getElementById('product-edit-message');

        const preorderCheckbox = document.getElementById('is_preorder_enabled');
        const preorderFields = document.querySelectorAll('.preorder-field');
        const harvestInput = document.getElementById('harvest_date');
        const harvestHelp = document.getElementById('harvestHelp');
        const preorderDeadline = document.getElementById('preorder_deadline');

        const imageInput = document.getElementById('image');
        const previewImage = document.getElementById('preview-image');

        const titleInput = document.getElementById('title');
        const categorySelect = document.getElementById('category_id');
        const priceInput = document.getElementById('price');
        const unitSelect = document.getElementById('unit_type');
        const stockInput = document.getElementById('stock_quantity');
        const statusSelect = document.getElementById('status');
        const descriptionInput = document.getElementById('description');

        const heroTitle = document.getElementById('hero-product-title');
        const previewTitle = document.getElementById('preview-title');
        const previewDescription = document.getElementById('preview-description');
        const previewPrice = document.getElementById('preview-price');
        const previewUnit = document.getElementById('preview-unit');
        const previewStock = document.getElementById('preview-stock');
        const previewCategory = document.getElementById('preview-category');
        const previewHarvest = document.getElementById('preview-harvest');
        const previewPreorder = document.getElementById('preview-preorder');
        const previewStatus = document.getElementById('preview-status');
        const imagePanel = document.getElementById('product-image-panel');

        function showMessage(type, message) {
            if (!messageBox) {
                return;
            }

            messageBox.hidden = false;
            messageBox.className = 'edit-message ' + type;
            messageBox.textContent = message || '';

            window.setTimeout(function () {
                messageBox.hidden = true;
                messageBox.textContent = '';
                messageBox.className = 'edit-message';
            }, 3200);
        }

        function clearFieldErrors() {
            form.querySelectorAll('.field-error').forEach(function (item) {
                item.remove();
            });
        }

        function showFieldErrors(errors) {
            Object.keys(errors || {}).forEach(function (field) {
                const input = form.querySelector('[name="' + field + '"]');
                const firstMessage = Array.isArray(errors[field]) ? errors[field][0] : errors[field];

                if (!input || !firstMessage) {
                    return;
                }

                const group = input.closest('.form-group');

                if (!group) {
                    return;
                }

                const error = document.createElement('div');
                error.className = 'field-error';
                error.setAttribute('data-error-for', field);
                error.textContent = firstMessage;

                group.appendChild(error);
            });
        }

        function formatMoney(value) {
            const number = parseFloat(value || '0');

            return number.toLocaleString('tr-TR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }) + ' TL';
        }

        function unitLabel(value) {
            const labels = {
                kg: 'kg',
                piece: 'adet',
                bunch: 'demet',
                box: 'kasa'
            };

            return labels[value] || value || 'kg';
        }

        function statusLabel(value) {
            const labels = {
                active: 'Aktif',
                draft: 'Taslak',
                paused: 'Pasif',
                sold_out: 'Stokta Yok'
            };

            return labels[value] || 'Aktif';
        }

        function statusClass(value) {
            const classes = {
                active: 'badge-success',
                draft: 'badge-info',
                paused: 'badge-muted',
                sold_out: 'badge-warning'
            };

            return classes[value] || 'badge-muted';
        }

        function updatePreviewFromForm() {
            const selectedCategory = categorySelect.options[categorySelect.selectedIndex];

            if (heroTitle) {
                heroTitle.textContent = titleInput.value.trim() || 'Ürün Düzenle';
            }

            previewTitle.textContent = titleInput.value.trim() || 'Ürün adı burada görünecek';
            previewDescription.textContent = descriptionInput.value.trim() || 'Ürün açıklaması burada görünecek.';
            previewPrice.textContent = formatMoney(priceInput.value);
            previewUnit.textContent = '/ ' + unitLabel(unitSelect.value);
            previewStock.textContent = (stockInput.value || '0') + ' ' + unitLabel(unitSelect.value);
            previewCategory.textContent = selectedCategory && selectedCategory.value ? selectedCategory.textContent.trim() : 'Seçilmedi';
            previewHarvest.textContent = harvestInput.value || '-';
            previewPreorder.textContent = preorderCheckbox.checked ? 'Açık' : 'Kapalı';

            previewStatus.textContent = statusLabel(statusSelect.value);
            previewStatus.className = 'preview-badge ' + statusClass(statusSelect.value);
        }

        function updatePreviewFromServer(product) {
            if (!product) {
                return;
            }

            if (heroTitle) {
                heroTitle.textContent = product.title || 'Ürün Düzenle';
            }

            previewTitle.textContent = product.title || 'Ürün';
            previewDescription.textContent = product.description || 'Ürün açıklaması burada görünecek.';
            previewPrice.textContent = product.price || '0,00 TL';
            previewUnit.textContent = '/ ' + (product.unit || 'kg');
            previewStock.textContent = product.stock || '0 kg';
            previewCategory.textContent = product.category || 'Kategori yok';
            previewHarvest.textContent = product.harvest_date || '-';
            previewPreorder.textContent = product.preorder || 'Kapalı';

            previewStatus.textContent = product.status || 'Aktif';
            previewStatus.className = 'preview-badge ' + (product.status_class || 'badge-muted');

            if (product.cover_image_url) {
                previewImage.innerHTML = '<img src="' + product.cover_image_url + '" alt="' + (product.title || 'Ürün') + '">';
            }
        }

        function syncDateRules() {
            if (!preorderCheckbox || !harvestInput) {
                return;
            }

            const enabled = preorderCheckbox.checked;

            preorderFields.forEach(function (field) {
                field.classList.toggle('is-hidden', !enabled);
            });

            if (enabled) {
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

            updatePreviewFromForm();
        }

        [titleInput, categorySelect, priceInput, unitSelect, stockInput, statusSelect, harvestInput, descriptionInput].forEach(function (input) {
            if (!input) {
                return;
            }

            input.addEventListener('input', updatePreviewFromForm);
            input.addEventListener('change', updatePreviewFromForm);
        });

        if (preorderCheckbox) {
            preorderCheckbox.addEventListener('change', syncDateRules);
        }

        if (imageInput && previewImage) {
            imageInput.addEventListener('change', function () {
                const file = imageInput.files && imageInput.files[0];

                if (!file) {
                    updatePreviewFromForm();
                    return;
                }

                const reader = new FileReader();

                reader.onload = function (event) {
                    previewImage.innerHTML = '<img src="' + event.target.result + '" alt="Ürün önizleme">';
                };

                reader.readAsDataURL(file);
            });
        }

        if (form) {
            form.addEventListener('submit', async function (event) {
                event.preventDefault();

                clearFieldErrors();

                const originalButtonText = submitButton ? submitButton.textContent : '';

                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.textContent = 'Güncelleniyor...';
                }

                form.classList.add('form-loading');

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });

                    const result = await response.json();

                    if (!response.ok || !result.success) {
                        showFieldErrors(result.errors || {});
                        throw new Error(result.message || 'Ürün güncellenemedi.');
                    }

                    showMessage('success', result.message || 'Ürün başarıyla güncellendi.');

                    updatePreviewFromServer(result.product);

                    if (imagePanel && typeof result.imagePanelHtml === 'string') {
                        imagePanel.innerHTML = result.imagePanelHtml;
                    }

                    if (imageInput) {
                        imageInput.value = '';
                    }
                } catch (error) {
                    showMessage('error', error.message || 'Ürün güncellenirken bir hata oluştu.');
                } finally {
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.textContent = originalButtonText;
                    }

                    form.classList.remove('form-loading');
                }
            });
        }

        syncDateRules();
        updatePreviewFromForm();
    });
</script>

<?php require APP_PATH . '/Views/layouts/footer.php'; ?>
<?php clear_old(); ?>