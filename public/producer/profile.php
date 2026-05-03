<?php

require_once __DIR__ . '/../../app/bootstrap.php';

ProducerMiddleware::handle();

$userId = (int) currentUserId();
$pdo = db();
$isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';

$errors = [];

if (!function_exists('producer_profile_len')) {
    function producer_profile_len(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }
}

if (!function_exists('producer_profile_initial')) {
    function producer_profile_initial(?string $name): string
    {
        $name = trim((string) $name);

        if ($name === '') {
            return 'Ü';
        }

        return function_exists('mb_substr')
            ? mb_strtoupper(mb_substr($name, 0, 1, 'UTF-8'), 'UTF-8')
            : strtoupper(substr($name, 0, 1));
    }
}

if (!function_exists('producer_profile_image_url')) {
    function producer_profile_image_url(?string $path): string
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

if (!function_exists('producer_profile_json')) {
    function producer_profile_json(array $payload): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

if (!function_exists('producer_profile_upload_image')) {
    function producer_profile_upload_image(array $file, string $folder): ?string
    {
        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;

        if ($error === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Görsel yüklenirken bir hata oluştu.');
        }

        $maxSize = 5 * 1024 * 1024;

        if (($file['size'] ?? 0) > $maxSize) {
            throw new RuntimeException('Görsel en fazla 5 MB olabilir.');
        }

        $tmpName = $file['tmp_name'] ?? '';

        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            throw new RuntimeException('Yüklenen görsel geçerli değil.');
        }

        $mimeType = function_exists('mime_content_type') ? (string) mime_content_type($tmpName) : '';

        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        if (!isset($allowed[$mimeType])) {
            throw new RuntimeException('Sadece JPG, PNG veya WEBP görsel yükleyebilirsin.');
        }

        $uploadDirectory = dirname(__DIR__) . '/uploads/' . trim($folder, '/');

        if (!is_dir($uploadDirectory)) {
            mkdir($uploadDirectory, 0777, true);
        }

        if (!is_writable($uploadDirectory)) {
            throw new RuntimeException('Upload klasörü yazılabilir değil: public/uploads/' . trim($folder, '/'));
        }

        $extension = $allowed[$mimeType];
        $fileName = trim($folder, '/') . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
        $targetPath = $uploadDirectory . '/' . $fileName;

        if (!move_uploaded_file($tmpName, $targetPath)) {
            throw new RuntimeException('Görsel klasöre taşınamadı.');
        }

        return 'uploads/' . trim($folder, '/') . '/' . $fileName;
    }
}

if (!function_exists('producer_profile_unique_slug')) {
    function producer_profile_unique_slug(string $storeName, int $userId, ?string $currentSlug = null): string
    {
        $baseSlug = function_exists('slugify')
            ? slugify($storeName)
            : strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', trim($storeName)));

        $baseSlug = trim($baseSlug, '-');

        if ($baseSlug === '') {
            $baseSlug = 'uretici-' . $userId;
        }

        $slug = $currentSlug ?: $baseSlug;
        $counter = 1;

        while (true) {
            $stmt = db()->prepare("
                SELECT user_id
                FROM producer_profiles
                WHERE slug = :slug
                  AND user_id <> :user_id
                LIMIT 1
            ");

            $stmt->execute([
                'slug' => $slug,
                'user_id' => $userId,
            ]);

            if (!$stmt->fetch()) {
                return $slug;
            }

            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
    }
}

if (!function_exists('producer_profile_fetch')) {
    function producer_profile_fetch(PDO $pdo, int $userId): array|false
    {
        $stmt = $pdo->prepare("
            SELECT
                u.id,
                u.full_name,
                u.email,
                u.phone,
                u.whatsapp_phone,
                u.profile_photo,
                u.province_id,
                u.district_id,
                u.address_text,
                u.created_at,
                pp.store_name,
                pp.slug,
                pp.description,
                pp.logo_path,
                pp.cover_photo_path,
                pp.contact_email,
                pp.contact_phone,
                pp.contact_whatsapp,
                pp.shipping_note,
                pp.average_rating,
                pp.rating_count,
                pp.total_orders,
                pp.total_sales_amount,
                pp.verification_status,
                pr.name AS province_name,
                d.name AS district_name
            FROM users u
            LEFT JOIN producer_profiles pp ON pp.user_id = u.id
            LEFT JOIN provinces pr ON pr.id = u.province_id
            LEFT JOIN districts d ON d.id = u.district_id
            WHERE u.id = :id
              AND u.role = 'producer'
            LIMIT 1
        ");

        $stmt->execute([
            'id' => $userId,
        ]);

        return $stmt->fetch();
    }
}

if (!function_exists('producer_profile_payload')) {
    function producer_profile_payload(array $profile): array
    {
        $storeName = $profile['store_name'] ?: ($profile['full_name'] ?? 'Üretici');
        $location = 'Konum bilgisi henüz eklenmedi';

        if (!empty($profile['province_name'])) {
            $location = $profile['province_name'];

            if (!empty($profile['district_name'])) {
                $location .= ' / ' . $profile['district_name'];
            }
        }

        $ratingText = 'Henüz puan yok';

        if ((int) ($profile['rating_count'] ?? 0) > 0) {
            $ratingText = '⭐ ' . number_format((float) ($profile['average_rating'] ?? 0), 1, ',', '.')
                . ' / ' . (int) $profile['rating_count'] . ' yorum';
        }

        return [
            'store_name' => $storeName,
            'full_name' => $profile['full_name'] ?? '',
            'email' => $profile['email'] ?? '',
            'location' => $location,
            'rating_text' => $ratingText,
            'total_orders' => (string) ($profile['total_orders'] ?? 0),
            'profile_photo_url' => producer_profile_image_url($profile['profile_photo'] ?? ''),
            'logo_url' => producer_profile_image_url($profile['logo_path'] ?? ''),
            'cover_url' => producer_profile_image_url($profile['cover_photo_path'] ?? ''),
            'initial' => producer_profile_initial($storeName),
        ];
    }
}

/*
|--------------------------------------------------------------------------
| AJAX / Normal Profil Güncelleme
|--------------------------------------------------------------------------
*/

if (is_post()) {
    require_csrf();

    $fullName = trim((string) post('full_name', ''));
    $phone = trim((string) post('phone', ''));
    $whatsappPhone = trim((string) post('whatsapp_phone', ''));
    $profilePhotoRemove = post('remove_profile_photo', '') === '1';

    $storeName = trim((string) post('store_name', ''));
    $description = trim((string) post('description', ''));
    $contactEmail = trim((string) post('contact_email', ''));
    $contactPhone = trim((string) post('contact_phone', ''));
    $contactWhatsapp = trim((string) post('contact_whatsapp', ''));
    $shippingNote = trim((string) post('shipping_note', ''));

    $provinceId = (int) post('province_id', 0);
    $districtId = (int) post('district_id', 0);
    $addressText = trim((string) post('address_text', ''));

    $provinceId = $provinceId > 0 ? $provinceId : null;
    $districtId = $districtId > 0 ? $districtId : null;

    $removeLogo = post('remove_logo', '') === '1';
    $removeCover = post('remove_cover', '') === '1';

    if ($fullName === '') {
        $errors[] = 'Ad soyad boş bırakılamaz.';
    }

    if ($storeName === '') {
        $errors[] = 'Market / çiftlik adı boş bırakılamaz.';
    }

    if ($fullName !== '' && producer_profile_len($fullName) > 120) {
        $errors[] = 'Ad soyad en fazla 120 karakter olabilir.';
    }

    if ($storeName !== '' && producer_profile_len($storeName) > 160) {
        $errors[] = 'Market / çiftlik adı en fazla 160 karakter olabilir.';
    }

    if ($phone !== '' && producer_profile_len($phone) > 30) {
        $errors[] = 'Telefon en fazla 30 karakter olabilir.';
    }

    if ($whatsappPhone !== '' && producer_profile_len($whatsappPhone) > 30) {
        $errors[] = 'WhatsApp telefonu en fazla 30 karakter olabilir.';
    }

    if ($contactEmail !== '' && !filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'İletişim e-postası geçerli değil.';
    }

    if ($provinceId === null) {
        $districtId = null;
    }

    if ($provinceId !== null) {
        $provinceCheck = $pdo->prepare("
            SELECT id
            FROM provinces
            WHERE id = :id
            LIMIT 1
        ");

        $provinceCheck->execute([
            'id' => $provinceId,
        ]);

        if (!$provinceCheck->fetch()) {
            $errors[] = 'Seçilen il geçerli değil.';
        }
    }

    if ($provinceId !== null && $districtId !== null) {
        $districtCheck = $pdo->prepare("
            SELECT id
            FROM districts
            WHERE id = :district_id
              AND province_id = :province_id
            LIMIT 1
        ");

        $districtCheck->execute([
            'district_id' => $districtId,
            'province_id' => $provinceId,
        ]);

        if (!$districtCheck->fetch()) {
            $errors[] = 'Seçilen ilçe seçilen ile ait değil.';
        }
    }

    $profilePhotoUpload = null;
    $logoUpload = null;
    $coverUpload = null;

    if (empty($errors)) {
        try {
            if (isset($_FILES['profile_photo']) && is_array($_FILES['profile_photo'])) {
                $profilePhotoUpload = producer_profile_upload_image($_FILES['profile_photo'], 'users');
            }

            if (isset($_FILES['logo_path']) && is_array($_FILES['logo_path'])) {
                $logoUpload = producer_profile_upload_image($_FILES['logo_path'], 'producers');
            }

            if (isset($_FILES['cover_photo_path']) && is_array($_FILES['cover_photo_path'])) {
                $coverUpload = producer_profile_upload_image($_FILES['cover_photo_path'], 'producers');
            }
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }

    if (!empty($errors)) {
        if ($isAjax) {
            producer_profile_json([
                'success' => false,
                'message' => implode("\n", $errors),
                'errors' => $errors,
            ]);
        }

        flash_error(implode('<br>', $errors));
    } else {
        try {
            $pdo->beginTransaction();

            $currentStmt = $pdo->prepare("
                SELECT
                    u.profile_photo,
                    pp.slug,
                    pp.logo_path,
                    pp.cover_photo_path
                FROM users u
                LEFT JOIN producer_profiles pp ON pp.user_id = u.id
                WHERE u.id = :id
                  AND u.role = 'producer'
                LIMIT 1
            ");

            $currentStmt->execute([
                'id' => $userId,
            ]);

            $current = $currentStmt->fetch();

            $profilePhotoToSave = $current['profile_photo'] ?? null;
            $logoToSave = $current['logo_path'] ?? null;
            $coverToSave = $current['cover_photo_path'] ?? null;

            if ($profilePhotoRemove) {
                $profilePhotoToSave = null;
            }

            if ($removeLogo) {
                $logoToSave = null;
            }

            if ($removeCover) {
                $coverToSave = null;
            }

            if ($profilePhotoUpload !== null) {
                $profilePhotoToSave = $profilePhotoUpload;
            }

            if ($logoUpload !== null) {
                $logoToSave = $logoUpload;
            }

            if ($coverUpload !== null) {
                $coverToSave = $coverUpload;
            }

            $slug = producer_profile_unique_slug($storeName, $userId, $current['slug'] ?? null);

            $updateUser = $pdo->prepare("
                UPDATE users
                SET
                    full_name = :full_name,
                    phone = :phone,
                    whatsapp_phone = :whatsapp_phone,
                    profile_photo = :profile_photo,
                    province_id = :province_id,
                    district_id = :district_id,
                    address_text = :address_text,
                    updated_at = NOW()
                WHERE id = :id
                  AND role = 'producer'
                LIMIT 1
            ");

            $updateUser->execute([
                'full_name' => $fullName,
                'phone' => $phone !== '' ? $phone : null,
                'whatsapp_phone' => $whatsappPhone !== '' ? $whatsappPhone : null,
                'profile_photo' => $profilePhotoToSave,
                'province_id' => $provinceId,
                'district_id' => $districtId,
                'address_text' => $addressText !== '' ? $addressText : null,
                'id' => $userId,
            ]);

            $upsertProducer = $pdo->prepare("
                INSERT INTO producer_profiles (
                    user_id,
                    store_name,
                    slug,
                    description,
                    logo_path,
                    cover_photo_path,
                    contact_email,
                    contact_phone,
                    contact_whatsapp,
                    shipping_note,
                    created_at,
                    updated_at
                ) VALUES (
                    :user_id,
                    :store_name,
                    :slug,
                    :description,
                    :logo_path,
                    :cover_photo_path,
                    :contact_email,
                    :contact_phone,
                    :contact_whatsapp,
                    :shipping_note,
                    NOW(),
                    NOW()
                )
                ON DUPLICATE KEY UPDATE
                    store_name = VALUES(store_name),
                    slug = VALUES(slug),
                    description = VALUES(description),
                    logo_path = VALUES(logo_path),
                    cover_photo_path = VALUES(cover_photo_path),
                    contact_email = VALUES(contact_email),
                    contact_phone = VALUES(contact_phone),
                    contact_whatsapp = VALUES(contact_whatsapp),
                    shipping_note = VALUES(shipping_note),
                    updated_at = NOW()
            ");

            $upsertProducer->execute([
                'user_id' => $userId,
                'store_name' => $storeName,
                'slug' => $slug,
                'description' => $description !== '' ? $description : null,
                'logo_path' => $logoToSave,
                'cover_photo_path' => $coverToSave,
                'contact_email' => $contactEmail !== '' ? $contactEmail : null,
                'contact_phone' => $contactPhone !== '' ? $contactPhone : null,
                'contact_whatsapp' => $contactWhatsapp !== '' ? $contactWhatsapp : null,
                'shipping_note' => $shippingNote !== '' ? $shippingNote : null,
            ]);

            $pdo->commit();

            if (isset($_SESSION['user'])) {
                $_SESSION['user']['full_name'] = $fullName;
                $_SESSION['user']['profile_photo'] = $profilePhotoToSave;
            }

            $updatedProfile = producer_profile_fetch($pdo, $userId);

            if ($isAjax) {
                producer_profile_json([
                    'success' => true,
                    'message' => 'Üretici profilin başarıyla güncellendi.',
                    'profile' => $updatedProfile ? producer_profile_payload($updatedProfile) : null,
                ]);
            }

            flash_success('Üretici profilin başarıyla güncellendi.');
            redirect('producer/profile.php');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($isAjax) {
                producer_profile_json([
                    'success' => false,
                    'message' => 'Üretici profili güncellenirken bir hata oluştu.',
                ]);
            }

            flash_error('Üretici profili güncellenirken bir hata oluştu.');
            redirect('producer/profile.php');
        }
    }
}

/*
|--------------------------------------------------------------------------
| Profil Verilerini Çek
|--------------------------------------------------------------------------
*/

$profile = producer_profile_fetch($pdo, $userId);

if (!$profile) {
    if ($isAjax) {
        producer_profile_json([
            'success' => false,
            'message' => 'Üretici profili bulunamadı.',
        ]);
    }

    flash_error('Üretici profili bulunamadı.');
    redirect('producer/dashboard.php');
}

$provinces = [];

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

if (!empty($profile['province_id'])) {
    $districtStmt = $pdo->prepare("
        SELECT id, name
        FROM districts
        WHERE province_id = :province_id
        ORDER BY name ASC
    ");

    $districtStmt->execute([
        'province_id' => (int) $profile['province_id'],
    ]);

    $districts = $districtStmt->fetchAll();
}

$pageTitle = 'Üretici Profilim';
$bodyClass = 'page-producer-profile';

require APP_PATH . '/Views/layouts/header.php';

$payload = producer_profile_payload($profile);
?>

<main class="producer-profile-page">
    <section class="producer-profile-hero" id="producer-profile-hero">
        <?php if (!empty($profile['cover_photo_path'])): ?>
            <img
                src="<?= e(producer_profile_image_url($profile['cover_photo_path'])) ?>"
                alt="Kapak görseli"
                class="producer-cover-bg"
                id="producer-cover-bg"
            >
        <?php else: ?>
            <img
                src=""
                alt=""
                class="producer-cover-bg"
                id="producer-cover-bg"
                hidden
            >
        <?php endif; ?>

        <div class="producer-hero-overlay"></div>
        <div class="producer-hero-bg producer-blob-one"></div>
        <div class="producer-hero-bg producer-blob-two"></div>

        <div class="producer-profile-inner">
            <nav class="producer-breadcrumb" aria-label="Sayfa yolu">
                <a href="<?= e(url('index.php')) ?>">Ana Sayfa</a>
                <span>/</span>
                <a href="<?= e(url('producer/dashboard.php')) ?>">Üretici Paneli</a>
                <span>/</span>
                <strong>Profilim</strong>
            </nav>

            <div class="producer-hero-content">
                <div class="producer-avatar-large" id="producer-hero-avatar">
                    <?php if (!empty($profile['logo_path'])): ?>
                        <img src="<?= e(producer_profile_image_url($profile['logo_path'])) ?>" alt="<?= e($payload['store_name']) ?>">
                    <?php elseif (!empty($profile['profile_photo'])): ?>
                        <img src="<?= e(producer_profile_image_url($profile['profile_photo'])) ?>" alt="<?= e($profile['full_name'] ?? 'Üretici') ?>">
                    <?php else: ?>
                        <span><?= e($payload['initial']) ?></span>
                    <?php endif; ?>
                </div>

                <div>
                    <span class="producer-eyebrow">Üretici Profilim</span>

                    <h1 id="producer-hero-title"><?= e($payload['store_name']) ?></h1>

                    <p>
                        Profilini, konumunu, iletişim bilgilerini, logo ve kapak görselini buradan güncelleyebilirsin.
                    </p>

                    <div class="producer-location-line" id="producer-hero-location">
                        <?= e($payload['location']) ?>
                    </div>

                    <div class="producer-hero-actions">
                        <a class="producer-btn producer-btn-glass" href="<?= e(url('producer/dashboard.php')) ?>">
                            Panele Dön
                        </a>

                        <a class="producer-btn producer-btn-glass" href="<?= e(url('producer-detail.php?id=' . $userId)) ?>">
                            Public Profili Gör
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="producer-profile-shell">
        <div id="producer-profile-message" class="producer-profile-message" hidden></div>

        <div class="producer-profile-grid">
            <aside class="producer-summary-card glass-card">
                <div class="summary-avatar" id="producer-summary-avatar">
                    <?php if (!empty($profile['logo_path'])): ?>
                        <img src="<?= e(producer_profile_image_url($profile['logo_path'])) ?>" alt="<?= e($payload['store_name']) ?>">
                    <?php elseif (!empty($profile['profile_photo'])): ?>
                        <img src="<?= e(producer_profile_image_url($profile['profile_photo'])) ?>" alt="<?= e($profile['full_name'] ?? 'Üretici') ?>">
                    <?php else: ?>
                        <span><?= e($payload['initial']) ?></span>
                    <?php endif; ?>
                </div>

                <h2 id="producer-summary-store-name"><?= e($profile['store_name'] ?: '-') ?></h2>

                <p id="producer-summary-email"><?= e($profile['email'] ?: '-') ?></p>

                <div class="summary-list">
                    <div class="summary-row">
                        <span>Yetkili</span>
                        <strong id="producer-summary-full-name"><?= e($profile['full_name'] ?: '-') ?></strong>
                    </div>

                    <div class="summary-row">
                        <span>İl / İlçe</span>
                        <strong id="producer-summary-location"><?= e($payload['location']) ?></strong>
                    </div>

                    <div class="summary-row">
                        <span>Ortalama Puan</span>
                        <strong id="producer-summary-rating"><?= e($payload['rating_text']) ?></strong>
                    </div>

                    <div class="summary-row">
                        <span>Toplam Sipariş</span>
                        <strong id="producer-summary-orders"><?= e((string) ($profile['total_orders'] ?? 0)) ?></strong>
                    </div>
                </div>

                <div class="profile-actions">
                    <a class="producer-btn producer-btn-light full" href="<?= e(url('producer/dashboard.php')) ?>">
                        Panele Dön
                    </a>

                    <a class="producer-btn producer-btn-light full" href="<?= e(url('producer-detail.php?id=' . $userId)) ?>">
                        Public Profili Gör
                    </a>
                </div>
            </aside>

            <section class="producer-form-card glass-card">
                <div class="section-heading">
                    <span class="section-icon">👨‍🌾</span>

                    <div>
                        <h2>Bilgilerimi Güncelle</h2>
                        <p>Değişiklikleri kaydettiğinde form AJAX ile gönderilir, sayfa yenilenmez.</p>
                    </div>
                </div>

                <form
                    method="post"
                    action="<?= e(url('producer/profile.php')) ?>"
                    enctype="multipart/form-data"
                    class="producer-profile-form"
                    id="producer-profile-form"
                >
                    <?= csrf_field() ?>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="profile_photo">Kişisel Profil Fotoğrafı</label>

                            <div class="file-box">
                                <input type="file" id="profile_photo" name="profile_photo" accept="image/jpeg,image/png,image/webp">
                                <small>Üst menüde görünebilir. JPG, PNG veya WEBP, en fazla 5 MB.</small>
                            </div>

                            <?php if (!empty($profile['profile_photo'])): ?>
                                <label class="checkbox-line" id="remove-profile-photo-line">
                                    <input type="checkbox" name="remove_profile_photo" value="1">
                                    Kişisel fotoğrafı kaldır
                                </label>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="logo_path">Market / Çiftlik Logosu</label>

                            <div class="file-box">
                                <input type="file" id="logo_path" name="logo_path" accept="image/jpeg,image/png,image/webp">
                                <small>Public üretici profilinde gösterilir.</small>
                            </div>

                            <?php if (!empty($profile['logo_path'])): ?>
                                <label class="checkbox-line" id="remove-logo-line">
                                    <input type="checkbox" name="remove_logo" value="1">
                                    Logoyu kaldır
                                </label>
                            <?php endif; ?>
                        </div>

                        <div class="form-group full">
                            <label for="cover_photo_path">Kapak Görseli</label>

                            <div class="file-box">
                                <input type="file" id="cover_photo_path" name="cover_photo_path" accept="image/jpeg,image/png,image/webp">
                                <small>Profilin üst alanında geniş görsel olarak görünür.</small>
                            </div>

                            <?php if (!empty($profile['cover_photo_path'])): ?>
                                <label class="checkbox-line" id="remove-cover-line">
                                    <input type="checkbox" name="remove_cover" value="1">
                                    Kapak görselini kaldır
                                </label>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="full_name">Yetkili Ad Soyad</label>
                            <input
                                type="text"
                                id="full_name"
                                name="full_name"
                                value="<?= e($profile['full_name'] ?? '') ?>"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="store_name">Market / Çiftlik Adı</label>
                            <input
                                type="text"
                                id="store_name"
                                name="store_name"
                                value="<?= e($profile['store_name'] ?? '') ?>"
                                required
                            >
                        </div>

                        <div class="form-group full">
                            <label for="email">Hesap E-postası</label>
                            <input type="email" id="email" value="<?= e($profile['email'] ?? '') ?>" disabled>
                            <small>Hesap e-postası bu sayfadan değiştirilmiyor.</small>
                        </div>

                        <div class="form-group">
                            <label for="phone">Kişisel Telefon</label>
                            <input
                                type="text"
                                id="phone"
                                name="phone"
                                value="<?= e($profile['phone'] ?? '') ?>"
                                placeholder="05xx xxx xx xx"
                            >
                        </div>

                        <div class="form-group">
                            <label for="whatsapp_phone">Kişisel WhatsApp</label>
                            <input
                                type="text"
                                id="whatsapp_phone"
                                name="whatsapp_phone"
                                value="<?= e($profile['whatsapp_phone'] ?? '') ?>"
                                placeholder="05xx xxx xx xx"
                            >
                        </div>

                        <div class="form-group">
                            <label for="contact_email">İletişim E-postası</label>
                            <input
                                type="email"
                                id="contact_email"
                                name="contact_email"
                                value="<?= e($profile['contact_email'] ?? '') ?>"
                                placeholder="ornek@mail.com"
                            >
                        </div>

                        <div class="form-group">
                            <label for="contact_phone">İletişim Telefonu</label>
                            <input
                                type="text"
                                id="contact_phone"
                                name="contact_phone"
                                value="<?= e($profile['contact_phone'] ?? '') ?>"
                                placeholder="05xx xxx xx xx"
                            >
                        </div>

                        <div class="form-group full">
                            <label for="contact_whatsapp">İletişim WhatsApp</label>
                            <input
                                type="text"
                                id="contact_whatsapp"
                                name="contact_whatsapp"
                                value="<?= e($profile['contact_whatsapp'] ?? '') ?>"
                                placeholder="05xx xxx xx xx"
                            >
                        </div>

                        <div class="form-group">
                            <label for="province_id">İl</label>
                            <select id="province_id" name="province_id">
                                <option value="">İl seç</option>

                                <?php foreach ($provinces as $province): ?>
                                    <option
                                        value="<?= e((string) $province['id']) ?>"
                                        <?= ((int) ($profile['province_id'] ?? 0) === (int) $province['id']) ? 'selected' : '' ?>
                                    >
                                        <?= e($province['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="district_id">İlçe</label>
                            <select id="district_id" name="district_id">
                                <option value="">Önce il seç</option>

                                <?php foreach ($districts as $district): ?>
                                    <option
                                        value="<?= e((string) $district['id']) ?>"
                                        <?= ((int) ($profile['district_id'] ?? 0) === (int) $district['id']) ? 'selected' : '' ?>
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
                                rows="3"
                                placeholder="Mahalle, cadde, sokak, bina no..."
                            ><?= e($profile['address_text'] ?? '') ?></textarea>
                        </div>

                        <div class="form-group full">
                            <label for="description">Üretici Açıklaması</label>
                            <textarea
                                id="description"
                                name="description"
                                rows="4"
                                placeholder="Çiftliğini, ürünlerini, üretim şeklini anlat..."
                            ><?= e($profile['description'] ?? '') ?></textarea>
                        </div>

                        <div class="form-group full">
                            <label for="shipping_note">Gönderim / Teslimat Notu</label>
                            <textarea
                                id="shipping_note"
                                name="shipping_note"
                                rows="3"
                                placeholder="Hangi bölgelere gönderim yapıyorsun, teslimat günlerin neler?"
                            ><?= e($profile['shipping_note'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="producer-btn producer-btn-primary" id="profile-submit-button">
                            Profilimi Kaydet
                        </button>

                        <a class="producer-btn producer-btn-light" href="<?= e(url('producer/dashboard.php')) ?>">
                            Panele Dön
                        </a>
                    </div>
                </form>
            </section>
        </div>
    </section>
</main>

<style>
    :root {
        --producer-green-950: #102f1a;
        --producer-green-900: #163d22;
        --producer-green-800: #245c2f;
        --producer-green-700: #2f7d3d;
        --producer-green-600: #3f9650;
        --producer-green-100: #eaf6e8;
        --producer-green-50: #f6fbf4;
        --producer-cream: #fffaf1;
        --producer-yellow: #f2bf4d;
        --producer-red: #9b111e;
        --producer-text: #1e2b21;
        --producer-muted: #687669;
        --producer-border: rgba(47, 125, 61, .14);
        --producer-shadow: 0 24px 70px rgba(31, 79, 43, .12);
        --producer-radius-lg: 28px;
    }

    body.page-producer-profile {
        background:
            radial-gradient(circle at 14% 12%, rgba(196, 231, 177, .48), transparent 28%),
            radial-gradient(circle at 88% 16%, rgba(242, 191, 77, .16), transparent 24%),
            linear-gradient(180deg, #f8fbf2 0%, #f3f8ed 48%, #ffffff 100%);
    }

    .producer-profile-page {
        overflow: hidden;
    }

    .producer-profile-hero {
        position: relative;
        min-height: 380px;
        padding: 34px 18px 92px;
        background:
            radial-gradient(circle at 82% 18%, rgba(242, 191, 77, .30), transparent 26%),
            radial-gradient(circle at 12% 78%, rgba(255, 255, 255, .16), transparent 24%),
            linear-gradient(135deg, rgba(36, 92, 47, .97), rgba(47, 125, 61, .87));
        color: #ffffff;
        overflow: hidden;
    }

    .producer-profile-hero::after {
        content: '';
        position: absolute;
        inset: auto 0 -1px;
        height: 88px;
        background: linear-gradient(180deg, rgba(246, 251, 244, 0), #f6fbf4 82%);
        pointer-events: none;
    }

    .producer-cover-bg {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        opacity: .22;
        filter: saturate(1.1);
    }

    .producer-hero-overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(16, 47, 26, .72), rgba(47, 125, 61, .64));
    }

    .producer-profile-inner,
    .producer-profile-shell {
        width: min(1180px, calc(100% - 32px));
        margin: 0 auto;
    }

    .producer-profile-inner {
        position: relative;
        z-index: 2;
    }

    .producer-hero-bg {
        position: absolute;
        border-radius: 999px;
        filter: blur(2px);
        opacity: .45;
        pointer-events: none;
    }

    .producer-blob-one {
        width: 230px;
        height: 230px;
        right: 10%;
        top: 42px;
        background: rgba(242, 191, 77, .28);
    }

    .producer-blob-two {
        width: 150px;
        height: 150px;
        left: 8%;
        bottom: 36px;
        background: rgba(255, 255, 255, .20);
    }

    .producer-breadcrumb {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 9px;
        font-size: 14px;
        margin-bottom: 32px;
        color: rgba(255, 255, 255, .76);
    }

    .producer-breadcrumb a {
        color: #ffffff;
        text-decoration: none;
        font-weight: 800;
    }

    .producer-breadcrumb strong {
        color: #ffffff;
        font-weight: 900;
    }

    .producer-hero-content {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr);
        align-items: center;
        gap: 22px;
        max-width: 930px;
    }

    .producer-avatar-large,
    .summary-avatar {
        display: grid;
        place-items: center;
        overflow: hidden;
        background: rgba(255, 255, 255, .16);
        border: 1px solid rgba(255, 255, 255, .32);
        box-shadow: 0 22px 50px rgba(16, 47, 26, .24);
    }

    .producer-avatar-large {
        width: 132px;
        height: 132px;
        border-radius: 36px;
    }

    .producer-avatar-large img,
    .summary-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .producer-avatar-large span,
    .summary-avatar span {
        color: #ffffff;
        font-size: 56px;
        font-weight: 900;
    }

    .producer-eyebrow {
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

    .producer-hero-content h1 {
        margin: 16px 0 12px;
        font-size: clamp(36px, 5vw, 58px);
        line-height: 1.03;
        letter-spacing: -.045em;
    }

    .producer-hero-content p {
        max-width: 690px;
        margin: 0;
        color: rgba(255, 255, 255, .86);
        font-size: 17px;
        line-height: 1.7;
    }

    .producer-location-line {
        margin-top: 14px;
        display: inline-flex;
        padding: 9px 13px;
        border-radius: 999px;
        background: rgba(255, 255, 255, .16);
        border: 1px solid rgba(255, 255, 255, .24);
        color: #ffffff;
        font-weight: 900;
        font-size: 14px;
    }

    .producer-hero-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 20px;
    }

    .producer-profile-shell {
        position: relative;
        z-index: 3;
        margin-top: -62px;
        padding-bottom: 54px;
    }

    .glass-card {
        background: rgba(255, 255, 255, .92);
        border: 1px solid rgba(255, 255, 255, .72);
        border-radius: var(--producer-radius-lg);
        box-shadow: var(--producer-shadow);
        backdrop-filter: blur(16px);
    }

    .producer-profile-message {
        margin-bottom: 18px;
        padding: 15px 17px;
        border-radius: 18px;
        font-weight: 900;
        box-shadow: 0 12px 30px rgba(31, 79, 43, .10);
        white-space: pre-line;
    }

    .producer-profile-message.success {
        background: #e7f7e8;
        color: #236b2c;
        border: 1px solid rgba(35, 107, 44, .14);
    }

    .producer-profile-message.error {
        background: #fdeaea;
        color: var(--producer-red);
        border: 1px solid rgba(155, 17, 30, .14);
    }

    .producer-profile-grid {
        display: grid;
        grid-template-columns: minmax(290px, .72fr) minmax(0, 1.55fr);
        gap: 22px;
        align-items: start;
    }

    .producer-summary-card,
    .producer-form-card {
        padding: 20px;
    }

    .producer-summary-card {
        position: sticky;
        top: 22px;
        text-align: center;
    }

    .summary-avatar {
        width: 120px;
        height: 120px;
        margin: 0 auto 16px;
        border-radius: 34px;
        background: linear-gradient(135deg, var(--producer-green-700), var(--producer-green-900));
    }

    .summary-avatar span {
        font-size: 48px;
    }

    .producer-summary-card h2 {
        margin: 0 0 6px;
        color: var(--producer-green-900);
        font-size: 25px;
        letter-spacing: -.03em;
    }

    .producer-summary-card > p {
        margin: 0;
        color: var(--producer-muted);
        word-break: break-word;
    }

    .summary-list {
        display: grid;
        gap: 10px;
        margin-top: 18px;
        text-align: left;
    }

    .summary-row {
        padding: 14px;
        border-radius: 17px;
        background: var(--producer-green-50);
        border: 1px solid var(--producer-border);
        display: grid;
        gap: 6px;
    }

    .summary-row span {
        color: var(--producer-muted);
        font-size: 13px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .summary-row strong {
        color: var(--producer-green-900);
        word-break: break-word;
        line-height: 1.5;
    }

    .profile-actions {
        display: grid;
        gap: 10px;
        margin-top: 18px;
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
        background: var(--producer-green-100);
        font-size: 21px;
        flex: 0 0 auto;
    }

    .section-heading h2 {
        margin: 0 0 5px;
        color: var(--producer-green-900);
        font-size: 28px;
        letter-spacing: -.03em;
    }

    .section-heading p {
        margin: 0;
        color: var(--producer-muted);
        line-height: 1.6;
    }

    .producer-profile-form {
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
        color: var(--producer-green-900);
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
        color: var(--producer-text);
        box-sizing: border-box;
        outline: none;
        transition: border-color .18s ease, box-shadow .18s ease;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        border-color: var(--producer-green-700);
        box-shadow: 0 0 0 4px rgba(47, 125, 61, .10);
    }

    .form-group input:disabled {
        background: #f2f5ef;
        color: var(--producer-muted);
        cursor: not-allowed;
    }

    .form-group small {
        color: var(--producer-muted);
        line-height: 1.5;
        font-weight: 700;
    }

    .file-box {
        padding: 14px;
        border-radius: 18px;
        background: var(--producer-green-50);
        border: 1px dashed rgba(47, 125, 61, .26);
    }

    .file-box input {
        background: #ffffff;
    }

    .checkbox-line {
        display: inline-flex !important;
        align-items: center;
        gap: 8px;
        color: var(--producer-red) !important;
        font-size: 14px;
        font-weight: 900 !important;
    }

    .checkbox-line input {
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

    .producer-btn {
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

    .producer-btn:hover {
        transform: translateY(-2px);
    }

    .producer-btn.full {
        width: 100%;
    }

    .producer-btn-primary {
        background: linear-gradient(135deg, var(--producer-green-700), var(--producer-green-900));
        color: #ffffff;
        box-shadow: 0 16px 32px rgba(47, 125, 61, .24);
    }

    .producer-btn-light {
        background: var(--producer-green-50);
        color: var(--producer-green-800);
        border: 1px solid var(--producer-border);
    }

    .producer-btn-glass {
        background: rgba(255, 255, 255, .16);
        color: #ffffff;
        border: 1px solid rgba(255, 255, 255, .28);
    }

    .producer-btn:disabled {
        opacity: .68;
        cursor: not-allowed;
        transform: none;
    }

    .profile-loading {
        pointer-events: none;
        opacity: .72;
    }

    @media (max-width: 980px) {
        .producer-profile-grid {
            grid-template-columns: 1fr;
        }

        .producer-summary-card {
            position: static;
        }
    }

    @media (max-width: 720px) {
        .producer-profile-hero {
            min-height: 430px;
            padding-top: 24px;
        }

        .producer-profile-inner,
        .producer-profile-shell {
            width: min(100% - 22px, 1180px);
        }

        .producer-breadcrumb {
            font-size: 13px;
            margin-bottom: 24px;
        }

        .producer-hero-content {
            grid-template-columns: 1fr;
        }

        .producer-avatar-large {
            width: 108px;
            height: 108px;
            border-radius: 30px;
        }

        .producer-avatar-large span {
            font-size: 44px;
        }

        .producer-hero-content p {
            font-size: 15px;
        }

        .producer-profile-shell {
            margin-top: -52px;
        }

        .producer-summary-card,
        .producer-form-card {
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

        .form-actions .producer-btn,
        .producer-hero-actions .producer-btn {
            width: 100%;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('producer-profile-form');
        const submitButton = document.getElementById('profile-submit-button');
        const messageBox = document.getElementById('producer-profile-message');

        const heroTitle = document.getElementById('producer-hero-title');
        const heroLocation = document.getElementById('producer-hero-location');
        const heroAvatar = document.getElementById('producer-hero-avatar');

        const summaryAvatar = document.getElementById('producer-summary-avatar');
        const summaryStoreName = document.getElementById('producer-summary-store-name');
        const summaryFullName = document.getElementById('producer-summary-full-name');
        const summaryLocation = document.getElementById('producer-summary-location');
        const summaryRating = document.getElementById('producer-summary-rating');
        const summaryOrders = document.getElementById('producer-summary-orders');
        const coverBg = document.getElementById('producer-cover-bg');

        const provinceSelect = document.getElementById('province_id');
        const districtSelect = document.getElementById('district_id');

        function escapeHtml(value) {
            return String(value || '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }

        function showMessage(type, message) {
            if (!messageBox) {
                return;
            }

            messageBox.hidden = false;
            messageBox.className = 'producer-profile-message ' + type;
            messageBox.textContent = message || '';

            window.setTimeout(function () {
                messageBox.hidden = true;
                messageBox.textContent = '';
                messageBox.className = 'producer-profile-message';
            }, 3200);
        }

        function avatarHtml(imageUrl, initial, alt) {
            if (imageUrl) {
                return '<img src="' + escapeHtml(imageUrl) + '" alt="' + escapeHtml(alt || 'Üretici') + '">';
            }

            return '<span>' + escapeHtml(initial || 'Ü') + '</span>';
        }

        function updateProfilePreview(profile) {
            if (!profile) {
                return;
            }

            if (heroTitle) {
                heroTitle.textContent = profile.store_name || 'Üretici';
            }

            if (heroLocation) {
                heroLocation.textContent = profile.location || 'Konum bilgisi henüz eklenmedi';
            }

            if (summaryStoreName) {
                summaryStoreName.textContent = profile.store_name || '-';
            }

            if (summaryFullName) {
                summaryFullName.textContent = profile.full_name || '-';
            }

            if (summaryLocation) {
                summaryLocation.textContent = profile.location || '-';
            }

            if (summaryRating) {
                summaryRating.textContent = profile.rating_text || 'Henüz puan yok';
            }

            if (summaryOrders) {
                summaryOrders.textContent = profile.total_orders || '0';
            }

            const avatarImage = profile.logo_url || profile.profile_photo_url || '';

            if (heroAvatar) {
                heroAvatar.innerHTML = avatarHtml(avatarImage, profile.initial, profile.store_name);
            }

            if (summaryAvatar) {
                summaryAvatar.innerHTML = avatarHtml(avatarImage, profile.initial, profile.store_name);
            }

            if (coverBg) {
                if (profile.cover_url) {
                    coverBg.src = profile.cover_url;
                    coverBg.hidden = false;
                } else {
                    coverBg.src = '';
                    coverBg.hidden = true;
                }
            }
        }

        if (provinceSelect && districtSelect) {
            provinceSelect.addEventListener('change', function () {
                const provinceId = provinceSelect.value;

                districtSelect.innerHTML = '<option value="">İlçeler yükleniyor...</option>';

                if (!provinceId) {
                    districtSelect.innerHTML = '<option value="">Önce il seç</option>';
                    return;
                }

                fetch('<?= e(url('api/district-list.php')) ?>?province_id=' + encodeURIComponent(provinceId), {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                    .then(function (response) {
                        return response.json();
                    })
                    .then(function (result) {
                        districtSelect.innerHTML = '<option value="">İlçe seç</option>';

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
        }

        if (form) {
            form.addEventListener('submit', async function (event) {
                event.preventDefault();

                const originalButtonText = submitButton ? submitButton.textContent : '';

                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.textContent = 'Kaydediliyor...';
                }

                form.classList.add('profile-loading');

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
                        throw new Error(result.message || 'Profil güncellenemedi.');
                    }

                    showMessage('success', result.message || 'Profil başarıyla güncellendi.');
                    updateProfilePreview(result.profile);

                    form.querySelectorAll('input[type="file"]').forEach(function (input) {
                        input.value = '';
                    });

                    form.querySelectorAll('input[type="checkbox"]').forEach(function (input) {
                        input.checked = false;
                    });
                } catch (error) {
                    showMessage('error', error.message || 'Profil güncellenirken bir hata oluştu.');
                } finally {
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.textContent = originalButtonText;
                    }

                    form.classList.remove('profile-loading');
                }
            });
        }
    });
</script>

<?php require APP_PATH . '/Views/layouts/footer.php'; ?>