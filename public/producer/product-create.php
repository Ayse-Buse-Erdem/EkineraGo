<?php

require_once __DIR__ . '/../../app/bootstrap.php';

ProducerMiddleware::handle();

$controller = new ProductController();
$productService = new ProductService();

$userId = (int) currentUserId();
$isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';

if (!function_exists('product_create_json')) {
    function product_create_json(array $payload): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

if (!function_exists('product_create_flat_errors')) {
    function product_create_flat_errors(array $errors): string
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

if (!function_exists('product_create_error')) {
    function product_create_error(array $formErrors, string $key): string
    {
        if (empty($formErrors[$key][0])) {
            return '';
        }

        return '<div class="field-error" data-error-for="' . e($key) . '">' . e($formErrors[$key][0]) . '</div>';
    }
}

if (!function_exists('product_create_old')) {
    function product_create_old(string $key, mixed $default = ''): string
    {
        return e((string) old($key, $default));
    }
}

if (is_post()) {
    require_csrf();

    $result = $productService->createProduct($userId, $_POST, $_FILES);

    if ($isAjax) {
        if (!$result['success']) {
            product_create_json([
                'success' => false,
                'message' => product_create_flat_errors($result['errors'] ?? []) ?: 'Ürün oluşturulamadı.',
                'errors' => $result['errors'] ?? [],
            ]);
        }

        product_create_json([
            'success' => true,
            'message' => $result['message'] ?? 'Ürün başarıyla oluşturuldu.',
            'product_id' => $result['product_id'] ?? null,
            'redirect' => url('producer/products.php'),
        ]);
    }

    if (!$result['success']) {
        set_old($_POST);
        set_errors($result['errors'] ?? []);
        flash_error('Ürün oluşturulamadı.');
        redirect('producer/product-create.php');
    }

    flash_success($result['message'] ?? 'Ürün başarıyla oluşturuldu.');
    redirect('producer/products.php');
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
?>

<main class="product-create-page">
    <section class="product-create-hero">
        <div class="product-create-hero-bg create-blob-one"></div>
        <div class="product-create-hero-bg create-blob-two"></div>

        <div class="product-create-hero-inner">
            <nav class="product-create-breadcrumb" aria-label="Sayfa yolu">
                <a href="<?= e(url('index.php')) ?>">Ana Sayfa</a>
                <span>/</span>
                <a href="<?= e(url('producer/dashboard.php')) ?>">Üretici Paneli</a>
                <span>/</span>
                <a href="<?= e(url('producer/products.php')) ?>">Ürünlerim</a>
                <span>/</span>
                <strong>Ürün Ekle</strong>
            </nav>

            <div class="product-create-hero-content">
                <div class="product-create-hero-copy">
                    <span class="product-create-eyebrow">Yeni Ürün</span>

                    <h1>Yeni Ürün Ekle</h1>

                    <p>
                        Ürün adı, kategori, fiyat, stok, hasat tarihi, ön sipariş ve ürün fotoğrafı
                        bilgilerini girerek ürününü EkineraGo’da yayınlayabilirsin.
                    </p>

                    <div class="product-create-hero-actions">
                        <a class="create-btn create-btn-glass" href="<?= e(url('producer/products.php')) ?>">
                            Ürünlerime Dön
                        </a>

                        <a class="create-btn create-btn-glass" href="<?= e(url('producer/dashboard.php')) ?>">
                            Panele Dön
                        </a>
                    </div>
                </div>

                <div class="product-create-hero-card">
                    <div class="hero-card-icon">🌱</div>

                    <h2>Doğru bilgi, güvenli alışveriş</h2>

                    <p>
                        Açıklayıcı ürün bilgisi ve güncel stok bilgisi tüketici güvenini artırır.
                    </p>

                    <div class="hero-mini-list">
                        <span>🧺 Ürün bilgisi</span>
                        <span>💰 Fiyat</span>
                        <span>📦 Stok</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="product-create-shell">
        <?php if (!empty($formErrors['general'])): ?>
            <div class="create-message error">
                <?= e($formErrors['general'][0]) ?>
            </div>
        <?php endif; ?>

        <div id="product-create-message" class="create-message" hidden></div>

        <div class="product-create-layout">
            <section class="product-create-form-card glass-card">
                <div class="section-heading">
                    <span class="section-icon">🧺</span>

                    <div>
                        <h2>Ürün Bilgileri</h2>
                        <p>Form AJAX ile gönderilir; hata varsa sayfa yenilenmeden alanların altında gösterilir.</p>
                    </div>
                </div>

                <form
                    method="post"
                    action="<?= e(url('producer/product-create.php')) ?>"
                    enctype="multipart/form-data"
                    class="product-create-form"
                    id="product-create-form"
                    novalidate
                >
                    <?= csrf_field() ?>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="title">Ürün Adı</label>

                            <input
                                type="text"
                                id="title"
                                name="title"
                                value="<?= product_create_old('title') ?>"
                                placeholder="Örn: Organik Domates"
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
                                        value="<?= e((string) $category['id']) ?>"
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

                            <div class="input-with-suffix">
                                <input
                                    type="number"
                                    id="price"
                                    name="price"
                                    step="0.01"
                                    min="0.01"
                                    value="<?= product_create_old('price') ?>"
                                    placeholder="0.00"
                                    required
                                >

                                <span>TL</span>
                            </div>

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
                                value="<?= product_create_old('stock_quantity') ?>"
                                placeholder="Örn: 25"
                                required
                            >

                            <small>Gram hassasiyeti için 0.001 adım desteklenir.</small>

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
                                value="<?= product_create_old('harvest_date') ?>"
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

                            <?= product_create_error($formErrors, 'harvest_date') ?>
                        </div>

                        <div class="form-group">
                            <label for="image">Ürün Fotoğrafı</label>

                            <div class="file-box">
                                <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp,image/*">
                                <small>JPG, PNG veya WEBP görsel yükleyebilirsin.</small>
                            </div>

                            <?= product_create_error($formErrors, 'image') ?>
                        </div>

                        <div class="form-group full">
                            <label class="switch-line">
                                <input
                                    type="checkbox"
                                    id="is_preorder_enabled"
                                    name="is_preorder_enabled"
                                    value="1"
                                    <?= old('is_preorder_enabled') ? 'checked' : '' ?>
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
                                value="<?= product_create_old('preorder_deadline') ?>"
                                min="<?= e($today) ?>"
                                max="<?= e($oneYearAfter) ?>"
                            >

                            <?= product_create_error($formErrors, 'preorder_deadline') ?>
                        </div>

                        <div class="form-group preorder-field">
                            <label for="min_preorder_quantity">Minimum Ön Sipariş Miktarı</label>

                            <div class="inline-fields">
                                <input
                                    type="number"
                                    id="min_preorder_quantity"
                                    name="min_preorder_quantity"
                                    step="0.001"
                                    min="0"
                                    value="<?= product_create_old('min_preorder_quantity') ?>"
                                    placeholder="Örn: 5"
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

                            <textarea
                                id="description"
                                name="description"
                                rows="5"
                                placeholder="Ürünün tazeliği, üretim şekli, saklama önerisi veya teslimat notunu yaz..."
                            ><?= product_create_old('description') ?></textarea>

                            <?= product_create_error($formErrors, 'description') ?>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="create-btn create-btn-primary" id="product-create-submit">
                            Ürünü Kaydet
                        </button>

                        <a href="<?= e(url('producer/products.php')) ?>" class="create-btn create-btn-light">
                            Ürünlerime Dön
                        </a>
                    </div>
                </form>
            </section>

            <aside class="product-preview-card glass-card">
                <div class="preview-image" id="preview-image">
                    <span>🥬</span>
                    <strong>Ürün Fotoğrafı</strong>
                </div>

                <div class="preview-body">
                    <span class="preview-badge" id="preview-status">Aktif</span>

                    <h2 id="preview-title">Ürün adı burada görünecek</h2>

                    <p id="preview-description">
                        Ürün açıklamasını yazdıkça burada kısa bir önizleme göreceksin.
                    </p>

                    <div class="preview-price">
                        <strong id="preview-price">0,00 TL</strong>
                        <span id="preview-unit">/ kg</span>
                    </div>

                    <div class="preview-meta-grid">
                        <div>
                            <span>Stok</span>
                            <strong id="preview-stock">0 kg</strong>
                        </div>

                        <div>
                            <span>Kategori</span>
                            <strong id="preview-category">Seçilmedi</strong>
                        </div>

                        <div>
                            <span>Hasat</span>
                            <strong id="preview-harvest">-</strong>
                        </div>

                        <div>
                            <span>Ön Sipariş</span>
                            <strong id="preview-preorder">Kapalı</strong>
                        </div>
                    </div>

                    <div class="preview-note">
                        <span>🌿</span>
                        <p>Bu kart tüketicinin ürün detayına gitmeden önce göreceği ilk izlenime benzer.</p>
                    </div>
                </div>
            </aside>
        </div>
    </section>
</main>

<style>
    :root {
        --create-green-950: #102f1a;
        --create-green-900: #163d22;
        --create-green-800: #245c2f;
        --create-green-700: #2f7d3d;
        --create-green-600: #3f9650;
        --create-green-100: #eaf6e8;
        --create-green-50: #f6fbf4;
        --create-cream: #fffaf1;
        --create-yellow: #f2bf4d;
        --create-red: #9b111e;
        --create-text: #1e2b21;
        --create-muted: #687669;
        --create-border: rgba(47, 125, 61, .14);
        --create-shadow: 0 24px 70px rgba(31, 79, 43, .12);
        --create-radius-lg: 28px;
    }

    body.page-product-create {
        background:
            radial-gradient(circle at 14% 12%, rgba(196, 231, 177, .48), transparent 28%),
            radial-gradient(circle at 88% 16%, rgba(242, 191, 77, .16), transparent 24%),
            linear-gradient(180deg, #f8fbf2 0%, #f3f8ed 48%, #ffffff 100%);
    }

    .product-create-page {
        overflow: hidden;
    }

    .product-create-hero {
        position: relative;
        min-height: 390px;
        padding: 34px 18px 94px;
        background:
            radial-gradient(circle at 82% 18%, rgba(242, 191, 77, .30), transparent 26%),
            radial-gradient(circle at 12% 78%, rgba(255, 255, 255, .16), transparent 24%),
            linear-gradient(135deg, rgba(36, 92, 47, .97), rgba(47, 125, 61, .87));
        color: #ffffff;
    }

    .product-create-hero::after {
        content: '';
        position: absolute;
        inset: auto 0 -1px;
        height: 90px;
        background: linear-gradient(180deg, rgba(246, 251, 244, 0), #f6fbf4 82%);
        pointer-events: none;
    }

    .product-create-hero-inner,
    .product-create-shell {
        width: min(1180px, calc(100% - 32px));
        margin: 0 auto;
    }

    .product-create-hero-inner {
        position: relative;
        z-index: 2;
    }

    .product-create-hero-bg {
        position: absolute;
        border-radius: 999px;
        filter: blur(2px);
        opacity: .45;
        pointer-events: none;
    }

    .create-blob-one {
        width: 230px;
        height: 230px;
        right: 10%;
        top: 42px;
        background: rgba(242, 191, 77, .28);
    }

    .create-blob-two {
        width: 150px;
        height: 150px;
        left: 8%;
        bottom: 36px;
        background: rgba(255, 255, 255, .20);
    }

    .product-create-breadcrumb {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 9px;
        font-size: 14px;
        margin-bottom: 32px;
        color: rgba(255, 255, 255, .76);
    }

    .product-create-breadcrumb a {
        color: #ffffff;
        text-decoration: none;
        font-weight: 800;
    }

    .product-create-breadcrumb strong {
        color: #ffffff;
        font-weight: 900;
    }

    .product-create-hero-content {
        display: grid;
        grid-template-columns: minmax(0, 1.25fr) minmax(320px, .75fr);
        gap: 24px;
        align-items: center;
    }

    .product-create-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 13px;
        border-radius: 999px;
        background: rgba(255, 255, 255, .16);
        border: 1px solid rgba(255, 255, 255, .28);
        color: #ffffff;
        font-size: 13px;
        font-weight: 900;
        letter-spacing: .04em;
        text-transform: uppercase;
    }

    .product-create-hero-copy h1 {
        margin: 17px 0 12px;
        font-size: clamp(36px, 5vw, 62px);
        line-height: 1.03;
        letter-spacing: -.045em;
    }

    .product-create-hero-copy p {
        max-width: 700px;
        margin: 0;
        color: rgba(255, 255, 255, .86);
        font-size: 17px;
        line-height: 1.7;
    }

    .product-create-hero-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 22px;
    }

    .product-create-hero-card {
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

    .product-create-hero-card h2 {
        margin: 0 0 10px;
        font-size: 25px;
        letter-spacing: -.03em;
    }

    .product-create-hero-card p {
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

    .product-create-shell {
        position: relative;
        z-index: 3;
        margin-top: -64px;
        padding-bottom: 54px;
    }

    .glass-card {
        background: rgba(255, 255, 255, .92);
        border: 1px solid rgba(255, 255, 255, .72);
        border-radius: var(--create-radius-lg);
        box-shadow: var(--create-shadow);
        backdrop-filter: blur(16px);
    }

    .create-message {
        margin-bottom: 16px;
        padding: 14px 16px;
        border-radius: 18px;
        font-weight: 900;
        white-space: pre-line;
        box-shadow: 0 12px 30px rgba(31, 79, 43, .10);
    }

    .create-message.success {
        background: #e7f7e8;
        color: #236b2c;
        border: 1px solid rgba(35, 107, 44, .14);
    }

    .create-message.error {
        background: #fdeaea;
        color: var(--create-red);
        border: 1px solid rgba(155, 17, 30, .14);
    }

    .product-create-layout {
        display: grid;
        grid-template-columns: minmax(0, 1.35fr) minmax(330px, .65fr);
        gap: 22px;
        align-items: start;
    }

    .product-create-form-card,
    .product-preview-card {
        padding: 20px;
    }

    .product-preview-card {
        position: sticky;
        top: 22px;
        overflow: hidden;
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
        background: var(--create-green-100);
        font-size: 21px;
        flex: 0 0 auto;
    }

    .section-heading h2 {
        margin: 0 0 5px;
        color: var(--create-green-900);
        font-size: 28px;
        letter-spacing: -.03em;
    }

    .section-heading p {
        margin: 0;
        color: var(--create-muted);
        line-height: 1.6;
    }

    .product-create-form {
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
        color: var(--create-green-900);
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
        color: var(--create-text);
        box-sizing: border-box;
        outline: none;
        transition: border-color .18s ease, box-shadow .18s ease;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        border-color: var(--create-green-700);
        box-shadow: 0 0 0 4px rgba(47, 125, 61, .10);
    }

    .form-group small {
        color: var(--create-muted);
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
        background: var(--create-green-50);
        border: 1px solid var(--create-border);
    }

    .input-with-suffix input {
        border: 0;
    }

    .input-with-suffix span {
        color: var(--create-green-800);
        font-weight: 900;
        text-align: center;
    }

    .inline-fields {
        grid-template-columns: minmax(0, 1fr) 120px;
    }

    .file-box {
        padding: 14px;
        border-radius: 18px;
        background: var(--create-green-50);
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
        background: var(--create-green-50);
        border: 1px solid var(--create-border);
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
        background: var(--create-green-700);
    }

    .switch-line input:checked + span::after {
        transform: translateX(22px);
    }

    .preorder-field.is-hidden {
        display: none;
    }

    .field-error {
        color: var(--create-red);
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

    .create-btn {
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

    .create-btn:hover {
        transform: translateY(-2px);
    }

    .create-btn-primary {
        background: linear-gradient(135deg, var(--create-green-700), var(--create-green-900));
        color: #ffffff;
        box-shadow: 0 16px 32px rgba(47, 125, 61, .24);
    }

    .create-btn-light {
        background: var(--create-green-50);
        color: var(--create-green-800);
        border: 1px solid var(--create-border);
    }

    .create-btn-glass {
        background: rgba(255, 255, 255, .16);
        color: #ffffff;
        border: 1px solid rgba(255, 255, 255, .28);
    }

    .create-btn:disabled {
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
        color: var(--create-green-700);
        font-weight: 900;
        overflow: hidden;
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

    .preview-badge {
        display: inline-flex;
        padding: 8px 11px;
        border-radius: 999px;
        background: var(--create-green-100);
        color: var(--create-green-800);
        font-size: 12px;
        font-weight: 900;
    }

    .preview-body h2 {
        margin: 14px 0 8px;
        color: var(--create-green-900);
        font-size: 26px;
        letter-spacing: -.03em;
    }

    .preview-body p {
        margin: 0;
        color: var(--create-muted);
        line-height: 1.6;
    }

    .preview-price {
        display: flex;
        align-items: baseline;
        gap: 5px;
        margin: 16px 0 14px;
    }

    .preview-price strong {
        color: var(--create-green-800);
        font-size: 28px;
        letter-spacing: -.03em;
    }

    .preview-price span {
        color: var(--create-muted);
        font-weight: 900;
    }

    .preview-meta-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }

    .preview-meta-grid div {
        padding: 12px;
        border-radius: 16px;
        background: #fbfdf8;
        border: 1px solid var(--create-border);
    }

    .preview-meta-grid span,
    .preview-meta-grid strong {
        display: block;
    }

    .preview-meta-grid span {
        margin-bottom: 5px;
        color: var(--create-muted);
        font-size: 12px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .preview-meta-grid strong {
        color: var(--create-green-900);
        font-size: 14px;
    }

    .preview-note {
        display: grid;
        grid-template-columns: 38px 1fr;
        gap: 10px;
        align-items: flex-start;
        margin-top: 14px;
        padding: 13px;
        border-radius: 18px;
        background: #fff8df;
        border: 1px solid rgba(242, 191, 77, .35);
    }

    .preview-note span {
        width: 38px;
        height: 38px;
        display: grid;
        place-items: center;
        border-radius: 14px;
        background: #ffffff;
    }

    .preview-note p {
        margin: 0;
        color: #7a5400;
        font-size: 14px;
        font-weight: 800;
    }

    .form-loading {
        pointer-events: none;
        opacity: .72;
    }

    @media (max-width: 1060px) {
        .product-create-hero-content,
        .product-create-layout {
            grid-template-columns: 1fr;
        }

        .product-preview-card {
            position: static;
        }
    }

    @media (max-width: 720px) {
        .product-create-hero {
            min-height: 430px;
            padding-top: 24px;
        }

        .product-create-hero-inner,
        .product-create-shell {
            width: min(100% - 22px, 1180px);
        }

        .product-create-breadcrumb {
            font-size: 13px;
            margin-bottom: 24px;
        }

        .product-create-hero-copy p {
            font-size: 15px;
        }

        .product-create-shell {
            margin-top: -52px;
        }

        .product-create-form-card,
        .product-preview-card,
        .product-create-hero-card {
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
        .product-create-hero-actions {
            justify-content: stretch;
        }

        .form-actions .create-btn,
        .product-create-hero-actions .create-btn {
            width: 100%;
        }

        .preview-image {
            margin: -14px -14px 0;
        }

        .preview-meta-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('product-create-form');
        const submitButton = document.getElementById('product-create-submit');
        const messageBox = document.getElementById('product-create-message');

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

        const previewTitle = document.getElementById('preview-title');
        const previewDescription = document.getElementById('preview-description');
        const previewPrice = document.getElementById('preview-price');
        const previewUnit = document.getElementById('preview-unit');
        const previewStock = document.getElementById('preview-stock');
        const previewCategory = document.getElementById('preview-category');
        const previewHarvest = document.getElementById('preview-harvest');
        const previewPreorder = document.getElementById('preview-preorder');
        const previewStatus = document.getElementById('preview-status');

        function showMessage(type, message) {
            if (!messageBox) {
                return;
            }

            messageBox.hidden = false;
            messageBox.className = 'create-message ' + type;
            messageBox.textContent = message || '';

            window.setTimeout(function () {
                messageBox.hidden = true;
                messageBox.textContent = '';
                messageBox.className = 'create-message';
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

        function updatePreview() {
            const selectedCategory = categorySelect.options[categorySelect.selectedIndex];

            previewTitle.textContent = titleInput.value.trim() || 'Ürün adı burada görünecek';
            previewDescription.textContent = descriptionInput.value.trim() || 'Ürün açıklamasını yazdıkça burada kısa bir önizleme göreceksin.';
            previewPrice.textContent = formatMoney(priceInput.value);
            previewUnit.textContent = '/ ' + unitLabel(unitSelect.value);
            previewStock.textContent = (stockInput.value || '0') + ' ' + unitLabel(unitSelect.value);
            previewCategory.textContent = selectedCategory && selectedCategory.value ? selectedCategory.textContent.trim() : 'Seçilmedi';
            previewHarvest.textContent = harvestInput.value || '-';
            previewPreorder.textContent = preorderCheckbox.checked ? 'Açık' : 'Kapalı';
            previewStatus.textContent = statusLabel(statusSelect.value);
        }

        function syncPreorderFields() {
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

            updatePreview();
        }

        [titleInput, categorySelect, priceInput, unitSelect, stockInput, statusSelect, harvestInput, descriptionInput].forEach(function (input) {
            if (!input) {
                return;
            }

            input.addEventListener('input', updatePreview);
            input.addEventListener('change', updatePreview);
        });

        if (preorderCheckbox) {
            preorderCheckbox.addEventListener('change', syncPreorderFields);
        }

        if (imageInput && previewImage) {
            imageInput.addEventListener('change', function () {
                const file = imageInput.files && imageInput.files[0];

                if (!file) {
                    previewImage.innerHTML = '<span>🥬</span><strong>Ürün Fotoğrafı</strong>';
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
                    submitButton.textContent = 'Kaydediliyor...';
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
                        throw new Error(result.message || 'Ürün oluşturulamadı.');
                    }

                    showMessage('success', result.message || 'Ürün başarıyla oluşturuldu.');

                    window.setTimeout(function () {
                        window.location.href = result.redirect || '<?= e(url('producer/products.php')) ?>';
                    }, 850);
                } catch (error) {
                    showMessage('error', error.message || 'Ürün oluşturulurken bir hata oluştu.');
                } finally {
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.textContent = originalButtonText;
                    }

                    form.classList.remove('form-loading');
                }
            });
        }

        syncPreorderFields();
        updatePreview();
    });
</script>

<?php require APP_PATH . '/Views/layouts/footer.php'; ?>