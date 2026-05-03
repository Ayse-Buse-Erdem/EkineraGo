<?php

require_once __DIR__ . '/../../app/bootstrap.php';

ConsumerMiddleware::handle();

$userId = (int) currentUserId();
$pdo = db();

$pageTitle = 'Profilim';
$bodyClass = 'page-consumer-profile';

$errors = [];

if (!function_exists('consumer_profile_str_len')) {
    function consumer_profile_str_len(string $value): int
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($value, 'UTF-8');
        }

        return strlen($value);
    }
}

if (!function_exists('consumer_profile_initial')) {
    function consumer_profile_initial(?string $name): string
    {
        $name = trim((string) $name);

        if ($name === '') {
            return 'K';
        }

        if (function_exists('mb_substr') && function_exists('mb_strtoupper')) {
            return mb_strtoupper(mb_substr($name, 0, 1, 'UTF-8'), 'UTF-8');
        }

        return strtoupper(substr($name, 0, 1));
    }
}

if (!function_exists('consumer_profile_photo_url')) {
    function consumer_profile_photo_url(?string $path): string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return '';
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return url($path);
    }
}

if (!function_exists('consumer_profile_table_columns')) {
    function consumer_profile_table_columns(PDO $pdo): array
    {
        try {
            $statement = $pdo->query("SHOW COLUMNS FROM users");
            $columns = $statement->fetchAll(PDO::FETCH_COLUMN);

            return array_flip($columns ?: []);
        } catch (Throwable $exception) {
            return [];
        }
    }
}

if (!function_exists('consumer_profile_has_column')) {
    function consumer_profile_has_column(array $columns, string $column): bool
    {
        return isset($columns[$column]);
    }
}

if (!function_exists('save_consumer_profile_photo')) {
    function save_consumer_profile_photo(array $file): array
    {
        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;

        if ($error === UPLOAD_ERR_NO_FILE) {
            return [
                'success' => true,
                'path' => null,
                'message' => null,
            ];
        }

        if ($error !== UPLOAD_ERR_OK) {
            return [
                'success' => false,
                'path' => null,
                'message' => 'Profil fotoğrafı yüklenirken bir hata oluştu.',
            ];
        }

        $maxSize = 5 * 1024 * 1024;

        if (($file['size'] ?? 0) > $maxSize) {
            return [
                'success' => false,
                'path' => null,
                'message' => 'Profil fotoğrafı en fazla 5 MB olabilir.',
            ];
        }

        $tmpName = $file['tmp_name'] ?? '';

        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            return [
                'success' => false,
                'path' => null,
                'message' => 'Profil fotoğrafı geçerli şekilde yüklenemedi.',
            ];
        }

        $mimeType = '';

        if (function_exists('mime_content_type')) {
            $mimeType = (string) mime_content_type($tmpName);
        }

        $allowedMimeTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        if (!array_key_exists($mimeType, $allowedMimeTypes)) {
            return [
                'success' => false,
                'path' => null,
                'message' => 'Profil fotoğrafı JPG, PNG veya WEBP formatında olmalıdır.',
            ];
        }

        $extension = $allowedMimeTypes[$mimeType];
        $uploadDirectory = dirname(__DIR__) . '/uploads/users';

        if (!is_dir($uploadDirectory)) {
            mkdir($uploadDirectory, 0777, true);
        }

        if (!is_writable($uploadDirectory)) {
            return [
                'success' => false,
                'path' => null,
                'message' => 'uploads/users klasörü yazılabilir değil.',
            ];
        }

        $fileName = 'consumer_' . date('YmdHis') . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
        $targetPath = $uploadDirectory . '/' . $fileName;

        if (!move_uploaded_file($tmpName, $targetPath)) {
            return [
                'success' => false,
                'path' => null,
                'message' => 'Profil fotoğrafı klasöre taşınamadı.',
            ];
        }

        return [
            'success' => true,
            'path' => 'uploads/users/' . $fileName,
            'message' => null,
        ];
    }
}

$columns = consumer_profile_table_columns($pdo);

try {
    $userStatement = $pdo->prepare("
        SELECT *
        FROM users
        WHERE id = :id
        LIMIT 1
    ");

    $userStatement->execute([
        'id' => $userId,
    ]);

    $profileUser = $userStatement->fetch(PDO::FETCH_ASSOC) ?: currentUser();
} catch (Throwable $exception) {
    $profileUser = currentUser();
}

try {
    $provinceStatement = $pdo->query("
        SELECT id, name
        FROM provinces
        ORDER BY name ASC
    ");

    $provinces = $provinceStatement->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $exception) {
    $provinces = [];
}

try {
    $districtStatement = $pdo->query("
        SELECT id, province_id, name
        FROM districts
        ORDER BY name ASC
    ");

    $districts = $districtStatement->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $exception) {
    $districts = [];
}

if (is_post()) {
    require_csrf();

    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $whatsappPhone = trim((string) ($_POST['whatsapp_phone'] ?? ''));
    $provinceId = (int) ($_POST['province_id'] ?? 0);
    $districtId = (int) ($_POST['district_id'] ?? 0);
    $addressText = trim((string) ($_POST['address_text'] ?? ''));
    $bio = trim((string) ($_POST['bio'] ?? ''));
    $removeProfilePhoto = ($_POST['remove_profile_photo'] ?? '') === '1';

    $provinceId = $provinceId > 0 ? $provinceId : null;
    $districtId = $districtId > 0 ? $districtId : null;

    if ($fullName === '') {
        $errors[] = 'Ad soyad alanı boş bırakılamaz.';
    }

    if (consumer_profile_str_len($fullName) > 120) {
        $errors[] = 'Ad soyad en fazla 120 karakter olabilir.';
    }

    if ($phone !== '' && consumer_profile_str_len($phone) > 30) {
        $errors[] = 'Telefon en fazla 30 karakter olabilir.';
    }

    if ($whatsappPhone !== '' && consumer_profile_str_len($whatsappPhone) > 30) {
        $errors[] = 'WhatsApp telefon en fazla 30 karakter olabilir.';
    }

    if ($addressText !== '' && consumer_profile_str_len($addressText) > 1000) {
        $errors[] = 'Adres en fazla 1000 karakter olabilir.';
    }

    if ($bio !== '' && consumer_profile_str_len($bio) > 255) {
        $errors[] = 'Hakkımda alanı en fazla 255 karakter olabilir.';
    }

    if ($provinceId === null) {
        $districtId = null;
    }

    if ($provinceId !== null) {
        $provinceExists = false;

        foreach ($provinces as $province) {
            if ((int) $province['id'] === $provinceId) {
                $provinceExists = true;
                break;
            }
        }

        if (!$provinceExists) {
            $errors[] = 'Seçilen il geçerli değil.';
        }
    }

    if ($provinceId !== null && $districtId !== null) {
        $districtExists = false;

        foreach ($districts as $district) {
            if (
                (int) $district['id'] === $districtId
                && (int) $district['province_id'] === $provinceId
            ) {
                $districtExists = true;
                break;
            }
        }

        if (!$districtExists) {
            $errors[] = 'Seçilen ilçe seçilen ile ait değil.';
        }
    }

    $photoUploadResult = save_consumer_profile_photo($_FILES['profile_photo'] ?? []);

    if (!$photoUploadResult['success']) {
        $errors[] = $photoUploadResult['message'];
    }

    if (empty($errors)) {
        $updates = [];
        $params = [
            'id' => $userId,
        ];

        $candidateUpdates = [
            'full_name' => $fullName,
            'phone' => $phone !== '' ? $phone : null,
            'whatsapp_phone' => $whatsappPhone !== '' ? $whatsappPhone : null,
            'province_id' => $provinceId,
            'district_id' => $districtId,
            'address_text' => $addressText !== '' ? $addressText : null,
            'bio' => $bio !== '' ? $bio : null,
        ];

        foreach ($candidateUpdates as $column => $value) {
            if (consumer_profile_has_column($columns, $column)) {
                $updates[] = "{$column} = :{$column}";
                $params[$column] = $value;
            }
        }

        $photoColumn = null;

        foreach (['profile_photo_path', 'profile_photo', 'avatar_path'] as $possiblePhotoColumn) {
            if (consumer_profile_has_column($columns, $possiblePhotoColumn)) {
                $photoColumn = $possiblePhotoColumn;
                break;
            }
        }

        if ($photoColumn !== null) {
            if ($removeProfilePhoto) {
                $updates[] = "{$photoColumn} = :{$photoColumn}";
                $params[$photoColumn] = null;
            } elseif (!empty($photoUploadResult['path'])) {
                $updates[] = "{$photoColumn} = :{$photoColumn}";
                $params[$photoColumn] = $photoUploadResult['path'];
            }
        }

        if (consumer_profile_has_column($columns, 'updated_at')) {
            $updates[] = "updated_at = NOW()";
        }

        if (!empty($updates)) {
            $sql = "
                UPDATE users
                SET " . implode(', ', $updates) . "
                WHERE id = :id
                LIMIT 1
            ";

            $updateStatement = $pdo->prepare($sql);
            $updateStatement->execute($params);
        }

        flash_success('Profil bilgilerin başarıyla güncellendi.');
        redirect('consumer/profile.php');
    }
}

$photoPath = $profileUser['profile_photo_path']
    ?? $profileUser['profile_photo']
    ?? $profileUser['avatar_path']
    ?? '';

$selectedProvinceId = (int) ($profileUser['province_id'] ?? 0);
$selectedDistrictId = (int) ($profileUser['district_id'] ?? 0);

require APP_PATH . '/Views/layouts/header.php';
?>

<main class="consumer-profile-page">
    <section class="profile-hero">
        <div class="profile-hero-bg profile-blob-one"></div>
        <div class="profile-hero-bg profile-blob-two"></div>

        <div class="profile-hero-inner">
            <nav class="profile-breadcrumb" aria-label="Sayfa yolu">
                <a href="<?= e(url('index.php')) ?>">Ana Sayfa</a>
                <span>/</span>
                <a href="<?= e(url('consumer/dashboard.php')) ?>">Tüketici Paneli</a>
                <span>/</span>
                <strong>Profilim</strong>
            </nav>

            <div class="profile-hero-content">
                <div class="profile-avatar-large">
                    <?php if (!empty($photoPath)): ?>
                        <img src="<?= e(consumer_profile_photo_url($photoPath)) ?>" alt="<?= e($profileUser['full_name'] ?? 'Profil') ?>">
                    <?php else: ?>
                        <span><?= e(consumer_profile_initial($profileUser['full_name'] ?? 'K')) ?></span>
                    <?php endif; ?>
                </div>

                <div>
                    <span class="profile-eyebrow">Tüketici Profili</span>

                    <h1><?= e($profileUser['full_name'] ?? 'Tüketici') ?></h1>

                    <p>
                        Profil bilgilerini, iletişim detaylarını ve teslimat adresini buradan güncelleyebilirsin.
                    </p>

                    <div class="profile-hero-actions">
                        <a class="profile-btn profile-btn-glass" href="<?= e(url('consumer/dashboard.php')) ?>">
                            Panele Dön
                        </a>

                        <a class="profile-btn profile-btn-glass" href="<?= e(url('products.php')) ?>">
                            Ürünleri İncele
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="profile-shell">
        <?php if (!empty($errors)): ?>
            <div class="profile-alert error">
                <strong>Profil güncellenemedi.</strong>

                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="profile-layout">
            <aside class="profile-side-card glass-card">
                <div class="side-avatar">
                    <?php if (!empty($photoPath)): ?>
                        <img src="<?= e(consumer_profile_photo_url($photoPath)) ?>" alt="<?= e($profileUser['full_name'] ?? 'Profil') ?>">
                    <?php else: ?>
                        <span><?= e(consumer_profile_initial($profileUser['full_name'] ?? 'K')) ?></span>
                    <?php endif; ?>
                </div>

                <h2><?= e($profileUser['full_name'] ?? 'Tüketici') ?></h2>

                <p><?= e($profileUser['email'] ?? 'E-posta bilgisi yok') ?></p>

                <div class="profile-mini-list">
                    <a href="<?= e(url('consumer/orders.php')) ?>">
                        <span>📦</span>
                        Siparişlerim
                    </a>

                    <a href="<?= e(url('consumer/favorites.php')) ?>">
                        <span>♥</span>
                        Favorilerim
                    </a>

                    <a href="<?= e(url('consumer/wallet.php')) ?>">
                        <span>💳</span>
                        Sanal Bakiye
                    </a>
                </div>
            </aside>

            <section class="profile-form-card glass-card">
                <div class="section-heading">
                    <span class="section-icon">👤</span>

                    <div>
                        <h2>Profil Bilgileri</h2>
                        <p>Hesabında görünecek temel bilgileri düzenle.</p>
                    </div>
                </div>

                <form method="POST" action="<?= e(url('consumer/profile.php')) ?>" enctype="multipart/form-data" class="profile-form">
                    <?= csrf_field() ?>

                    <div class="form-grid">
                        <div class="form-group full">
                            <label for="profile_photo">Profil Fotoğrafı</label>

                            <div class="file-input-box">
                                <input
                                    type="file"
                                    id="profile_photo"
                                    name="profile_photo"
                                    accept="image/jpeg,image/png,image/webp"
                                >

                                <small>JPG, PNG veya WEBP yükleyebilirsin. Maksimum 5 MB.</small>
                            </div>

                            <?php if (!empty($photoPath)): ?>
                                <label class="remove-photo-check">
                                    <input type="checkbox" name="remove_profile_photo" value="1">
                                    Mevcut profil fotoğrafını kaldır
                                </label>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="full_name">Ad Soyad</label>

                            <input
                                type="text"
                                id="full_name"
                                name="full_name"
                                value="<?= e($_POST['full_name'] ?? ($profileUser['full_name'] ?? '')) ?>"
                                maxlength="120"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="email">E-posta</label>

                            <input
                                type="email"
                                id="email"
                                value="<?= e($profileUser['email'] ?? '') ?>"
                                disabled
                            >

                            <small>E-posta adresi bu ekrandan değiştirilemez.</small>
                        </div>

                        <div class="form-group">
                            <label for="phone">Telefon</label>

                            <input
                                type="text"
                                id="phone"
                                name="phone"
                                value="<?= e($_POST['phone'] ?? ($profileUser['phone'] ?? '')) ?>"
                                maxlength="30"
                                placeholder="05xx xxx xx xx"
                            >
                        </div>

                        <div class="form-group">
                            <label for="whatsapp_phone">WhatsApp Telefon</label>

                            <input
                                type="text"
                                id="whatsapp_phone"
                                name="whatsapp_phone"
                                value="<?= e($_POST['whatsapp_phone'] ?? ($profileUser['whatsapp_phone'] ?? '')) ?>"
                                maxlength="30"
                                placeholder="05xx xxx xx xx"
                            >
                        </div>

                        <div class="form-group">
                            <label for="province_id">İl</label>

                            <select id="province_id" name="province_id">
                                <option value="">İl seç</option>

                                <?php foreach ($provinces as $province): ?>
                                    <?php
                                        $provinceValue = (int) $province['id'];
                                        $currentProvince = (int) ($_POST['province_id'] ?? $selectedProvinceId);
                                    ?>

                                    <option
                                        value="<?= e((string) $provinceValue) ?>"
                                        <?= $provinceValue === $currentProvince ? 'selected' : '' ?>
                                    >
                                        <?= e($province['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="district_id">İlçe</label>

                            <select id="district_id" name="district_id">
                                <option value="">İlçe seç</option>

                                <?php foreach ($districts as $district): ?>
                                    <?php
                                        $districtValue = (int) $district['id'];
                                        $currentDistrict = (int) ($_POST['district_id'] ?? $selectedDistrictId);
                                    ?>

                                    <option
                                        value="<?= e((string) $districtValue) ?>"
                                        data-province-id="<?= e((string) $district['province_id']) ?>"
                                        <?= $districtValue === $currentDistrict ? 'selected' : '' ?>
                                    >
                                        <?= e($district['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group full">
                            <label for="address_text">Adres</label>

                            <textarea
                                id="address_text"
                                name="address_text"
                                rows="4"
                                maxlength="1000"
                                placeholder="Mahalle, cadde, sokak, bina/no gibi teslimat adresini yaz..."
                            ><?= e($_POST['address_text'] ?? ($profileUser['address_text'] ?? '')) ?></textarea>
                        </div>

                        <div class="form-group full">
                            <label for="bio">Hakkımda</label>

                            <textarea
                                id="bio"
                                name="bio"
                                rows="3"
                                maxlength="255"
                                placeholder="Kısa bir profil notu yazabilirsin..."
                            ><?= e($_POST['bio'] ?? ($profileUser['bio'] ?? '')) ?></textarea>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button class="profile-btn profile-btn-primary" type="submit">
                            Profili Kaydet
                        </button>

                        <a class="profile-btn profile-btn-light" href="<?= e(url('consumer/dashboard.php')) ?>">
                            Vazgeç
                        </a>
                    </div>
                </form>
            </section>
        </div>
    </section>
</main>

<style>
    :root {
        --profile-green-950: #102f1a;
        --profile-green-900: #163d22;
        --profile-green-800: #245c2f;
        --profile-green-700: #2f7d3d;
        --profile-green-600: #3f9650;
        --profile-green-100: #eaf6e8;
        --profile-green-50: #f6fbf4;
        --profile-cream: #fffaf1;
        --profile-yellow: #f2bf4d;
        --profile-red: #9b111e;
        --profile-text: #1e2b21;
        --profile-muted: #687669;
        --profile-border: rgba(47, 125, 61, .14);
        --profile-shadow: 0 24px 70px rgba(31, 79, 43, .12);
        --profile-radius-lg: 28px;
    }

    body.page-consumer-profile {
        background:
            radial-gradient(circle at 14% 12%, rgba(196, 231, 177, .48), transparent 28%),
            radial-gradient(circle at 88% 16%, rgba(242, 191, 77, .16), transparent 24%),
            linear-gradient(180deg, #f8fbf2 0%, #f3f8ed 48%, #ffffff 100%);
    }

    .consumer-profile-page {
        overflow: hidden;
    }

    .profile-hero {
        position: relative;
        min-height: 340px;
        padding: 34px 18px 92px;
        background:
            radial-gradient(circle at 82% 18%, rgba(242, 191, 77, .30), transparent 26%),
            radial-gradient(circle at 12% 78%, rgba(255, 255, 255, .16), transparent 24%),
            linear-gradient(135deg, rgba(36, 92, 47, .97), rgba(47, 125, 61, .87));
        color: #ffffff;
    }

    .profile-hero::after {
        content: '';
        position: absolute;
        inset: auto 0 -1px;
        height: 88px;
        background: linear-gradient(180deg, rgba(246, 251, 244, 0), #f6fbf4 82%);
        pointer-events: none;
    }

    .profile-hero-inner,
    .profile-shell {
        width: min(1180px, calc(100% - 32px));
        margin: 0 auto;
    }

    .profile-hero-inner {
        position: relative;
        z-index: 2;
    }

    .profile-hero-bg {
        position: absolute;
        border-radius: 999px;
        filter: blur(2px);
        opacity: .45;
        pointer-events: none;
    }

    .profile-blob-one {
        width: 230px;
        height: 230px;
        right: 10%;
        top: 42px;
        background: rgba(242, 191, 77, .28);
    }

    .profile-blob-two {
        width: 150px;
        height: 150px;
        left: 8%;
        bottom: 36px;
        background: rgba(255, 255, 255, .20);
    }

    .profile-breadcrumb {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 9px;
        font-size: 14px;
        margin-bottom: 32px;
        color: rgba(255, 255, 255, .76);
    }

    .profile-breadcrumb a {
        color: #ffffff;
        text-decoration: none;
        font-weight: 800;
    }

    .profile-breadcrumb strong {
        color: #ffffff;
        font-weight: 900;
    }

    .profile-hero-content {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr);
        align-items: center;
        gap: 22px;
        max-width: 850px;
    }

    .profile-avatar-large,
    .side-avatar {
        display: grid;
        place-items: center;
        overflow: hidden;
        background: rgba(255, 255, 255, .16);
        border: 1px solid rgba(255, 255, 255, .32);
        box-shadow: 0 22px 50px rgba(16, 47, 26, .24);
    }

    .profile-avatar-large {
        width: 132px;
        height: 132px;
        border-radius: 36px;
    }

    .profile-avatar-large img,
    .side-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .profile-avatar-large span,
    .side-avatar span {
        color: #ffffff;
        font-size: 56px;
        font-weight: 900;
    }

    .profile-eyebrow {
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

    .profile-hero-content h1 {
        margin: 16px 0 12px;
        font-size: clamp(36px, 5vw, 58px);
        line-height: 1.03;
        letter-spacing: -.045em;
    }

    .profile-hero-content p {
        max-width: 650px;
        margin: 0;
        color: rgba(255, 255, 255, .86);
        font-size: 17px;
        line-height: 1.7;
    }

    .profile-hero-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 20px;
    }

    .profile-shell {
        position: relative;
        z-index: 3;
        margin-top: -62px;
        padding-bottom: 54px;
    }

    .glass-card {
        background: rgba(255, 255, 255, .92);
        border: 1px solid rgba(255, 255, 255, .72);
        border-radius: var(--profile-radius-lg);
        box-shadow: var(--profile-shadow);
        backdrop-filter: blur(16px);
    }

    .profile-layout {
        display: grid;
        grid-template-columns: minmax(280px, .72fr) minmax(0, 1.55fr);
        gap: 22px;
        align-items: start;
    }

    .profile-side-card,
    .profile-form-card {
        padding: 20px;
    }

    .profile-side-card {
        position: sticky;
        top: 22px;
        text-align: center;
    }

    .side-avatar {
        width: 120px;
        height: 120px;
        margin: 0 auto 16px;
        border-radius: 34px;
        background: linear-gradient(135deg, var(--profile-green-700), var(--profile-green-900));
    }

    .side-avatar span {
        font-size: 48px;
    }

    .profile-side-card h2 {
        margin: 0 0 6px;
        color: var(--profile-green-900);
        font-size: 25px;
        letter-spacing: -.03em;
    }

    .profile-side-card p {
        margin: 0;
        color: var(--profile-muted);
        word-break: break-word;
    }

    .profile-mini-list {
        display: grid;
        gap: 10px;
        margin-top: 18px;
        text-align: left;
    }

    .profile-mini-list a {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 13px;
        border-radius: 17px;
        background: var(--profile-green-50);
        border: 1px solid var(--profile-border);
        color: var(--profile-green-900);
        text-decoration: none;
        font-weight: 900;
        transition: transform .18s ease, box-shadow .18s ease;
    }

    .profile-mini-list a:hover {
        transform: translateY(-2px);
        box-shadow: 0 16px 32px rgba(31, 79, 43, .10);
    }

    .profile-mini-list span {
        width: 36px;
        height: 36px;
        display: grid;
        place-items: center;
        border-radius: 13px;
        background: var(--profile-green-100);
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
        background: var(--profile-green-100);
        font-size: 21px;
        flex: 0 0 auto;
    }

    .section-heading h2 {
        margin: 0 0 5px;
        color: var(--profile-green-900);
        font-size: 28px;
        letter-spacing: -.03em;
    }

    .section-heading p {
        margin: 0;
        color: var(--profile-muted);
        line-height: 1.6;
    }

    .profile-form {
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
        color: var(--profile-green-900);
        font-weight: 900;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 13px 14px;
        border: 1px solid rgba(47, 125, 61, .18);
        border-radius: 15px;
        background: #ffffff;
        color: var(--profile-text);
        font-family: inherit;
        outline: none;
        transition: border-color .18s ease, box-shadow .18s ease;
        box-sizing: border-box;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        border-color: var(--profile-green-700);
        box-shadow: 0 0 0 4px rgba(47, 125, 61, .10);
    }

    .form-group input:disabled {
        background: #f1f5ee;
        color: var(--profile-muted);
    }

    .form-group small {
        color: var(--profile-muted);
        font-weight: 700;
        line-height: 1.5;
    }

    .file-input-box {
        padding: 14px;
        border-radius: 18px;
        background: var(--profile-green-50);
        border: 1px dashed rgba(47, 125, 61, .26);
    }

    .file-input-box input {
        background: #ffffff;
    }

    .remove-photo-check {
        display: inline-flex !important;
        align-items: center;
        gap: 8px;
        color: var(--profile-red) !important;
        font-size: 14px;
    }

    .remove-photo-check input {
        width: auto;
    }

    .form-actions {
        display: flex;
        justify-content: flex-end;
        flex-wrap: wrap;
        gap: 10px;
        padding-top: 18px;
        border-top: 1px solid rgba(47, 125, 61, .10);
    }

    .profile-btn {
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

    .profile-btn:hover {
        transform: translateY(-2px);
    }

    .profile-btn-primary {
        background: linear-gradient(135deg, var(--profile-green-700), var(--profile-green-900));
        color: #ffffff;
        box-shadow: 0 16px 32px rgba(47, 125, 61, .24);
    }

    .profile-btn-light {
        background: var(--profile-green-50);
        color: var(--profile-green-800);
        border: 1px solid var(--profile-border);
    }

    .profile-btn-glass {
        background: rgba(255, 255, 255, .16);
        color: #ffffff;
        border: 1px solid rgba(255, 255, 255, .28);
    }

    .profile-alert {
        margin-bottom: 18px;
        padding: 16px 18px;
        border-radius: 20px;
        font-weight: 800;
    }

    .profile-alert.error {
        background: #fdeaea;
        color: var(--profile-red);
        border: 1px solid rgba(155, 17, 30, .14);
    }

    .profile-alert strong {
        display: block;
        margin-bottom: 8px;
    }

    .profile-alert ul {
        margin: 0;
        padding-left: 18px;
    }

    @media (max-width: 980px) {
        .profile-layout {
            grid-template-columns: 1fr;
        }

        .profile-side-card {
            position: static;
        }
    }

    @media (max-width: 720px) {
        .profile-hero {
            min-height: 430px;
            padding-top: 24px;
        }

        .profile-hero-inner,
        .profile-shell {
            width: min(100% - 22px, 1180px);
        }

        .profile-breadcrumb {
            font-size: 13px;
            margin-bottom: 24px;
        }

        .profile-hero-content {
            grid-template-columns: 1fr;
        }

        .profile-avatar-large {
            width: 108px;
            height: 108px;
            border-radius: 30px;
        }

        .profile-avatar-large span {
            font-size: 44px;
        }

        .profile-hero-content p {
            font-size: 15px;
        }

        .profile-shell {
            margin-top: -52px;
        }

        .profile-side-card,
        .profile-form-card {
            padding: 14px;
            border-radius: 23px;
        }

        .form-grid {
            grid-template-columns: 1fr;
        }

        .section-heading h2 {
            font-size: 24px;
        }

        .form-actions {
            justify-content: stretch;
        }

        .form-actions .profile-btn,
        .profile-hero-actions .profile-btn {
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

        const districtOptions = Array.from(districtSelect.querySelectorAll('option'));

        function filterDistricts() {
            const selectedProvinceId = provinceSelect.value;

            districtOptions.forEach(function (option) {
                const optionProvinceId = option.getAttribute('data-province-id');

                if (!optionProvinceId) {
                    option.hidden = false;
                    return;
                }

                option.hidden = selectedProvinceId === '' || optionProvinceId !== selectedProvinceId;
            });

            const selectedOption = districtSelect.options[districtSelect.selectedIndex];

            if (selectedOption && selectedOption.hidden) {
                districtSelect.value = '';
            }
        }

        provinceSelect.addEventListener('change', filterDistricts);
        filterDistricts();
    });
</script>

<?php require APP_PATH . '/Views/layouts/footer.php'; ?>