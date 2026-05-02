<?php

require_once __DIR__ . '/../../app/bootstrap.php';

ProducerMiddleware::handle();

$userId = (int) currentUserId();
$pdo = db();

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

/*
|--------------------------------------------------------------------------
| Profil Güncelleme
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

    if (empty($errors)) {
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

            flash_success('Üretici profilin başarıyla güncellendi.');
            redirect('producer/profile.php');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            flash_error('Üretici profili güncellenirken bir hata oluştu.');
            redirect('producer/profile.php');
        }
    } else {
        flash_error(implode('<br>', $errors));
    }
}

/*
|--------------------------------------------------------------------------
| Profil Verilerini Çek
|--------------------------------------------------------------------------
*/

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

$profile = $stmt->fetch();

if (!$profile) {
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

?>

<section class="profile-page">
    <div class="producer-cover card">
        <?php if (!empty($profile['cover_photo_path'])): ?>
            <img
                src="<?= e(producer_profile_image_url($profile['cover_photo_path'])) ?>"
                alt="Kapak görseli"
                class="producer-cover-image"
            >
        <?php endif; ?>

        <div class="producer-cover-content">
            <div class="producer-logo-wrap">
                <?php if (!empty($profile['logo_path'])): ?>
                    <img
                        src="<?= e(producer_profile_image_url($profile['logo_path'])) ?>"
                        alt="<?= e($profile['store_name'] ?? 'Üretici Logosu') ?>"
                        class="producer-logo"
                    >
                <?php elseif (!empty($profile['profile_photo'])): ?>
                    <img
                        src="<?= e(producer_profile_image_url($profile['profile_photo'])) ?>"
                        alt="<?= e($profile['full_name'] ?? 'Üretici') ?>"
                        class="producer-logo"
                    >
                <?php else: ?>
                    <div class="producer-logo producer-logo-placeholder">
                        <?= e(producer_profile_initial($profile['store_name'] ?: $profile['full_name'])) ?>
                    </div>
                <?php endif; ?>
            </div>

            <div>
                <p class="eyebrow">Üretici Profilim</p>
                <h1><?= e($profile['store_name'] ?: ($profile['full_name'] ?? 'Üretici')) ?></h1>
                <p>
                    Profilini, konumunu, iletişim bilgilerini, logo ve kapak görselini buradan güncelleyebilirsin.
                </p>

                <div class="profile-location-line">
                    <?php if (!empty($profile['province_name'])): ?>
                        <?= e($profile['province_name']) ?>
                        <?php if (!empty($profile['district_name'])): ?>
                            / <?= e($profile['district_name']) ?>
                        <?php endif; ?>
                    <?php else: ?>
                        Konum bilgisi henüz eklenmedi
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="profile-grid">
        <aside class="card profile-summary-card">
            <h2>Profil Özeti</h2>

            <div class="summary-row">
                <span>Market / Çiftlik</span>
                <strong><?= e($profile['store_name'] ?: '-') ?></strong>
            </div>

            <div class="summary-row">
                <span>Yetkili</span>
                <strong><?= e($profile['full_name'] ?: '-') ?></strong>
            </div>

            <div class="summary-row">
                <span>E-posta</span>
                <strong><?= e($profile['email'] ?: '-') ?></strong>
            </div>

            <div class="summary-row">
                <span>İl / İlçe</span>
                <strong>
                    <?php if (!empty($profile['province_name'])): ?>
                        <?= e($profile['province_name']) ?>
                        <?php if (!empty($profile['district_name'])): ?>
                            / <?= e($profile['district_name']) ?>
                        <?php endif; ?>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </strong>
            </div>

            <div class="summary-row">
                <span>Ortalama Puan</span>
                <strong>
                    <?php if ((int) ($profile['rating_count'] ?? 0) > 0): ?>
                        ⭐ <?= e(number_format((float) $profile['average_rating'], 1, ',', '.')) ?>
                        / <?= e((string) $profile['rating_count']) ?> yorum
                    <?php else: ?>
                        Henüz puan yok
                    <?php endif; ?>
                </strong>
            </div>

            <div class="summary-row">
                <span>Toplam Sipariş</span>
                <strong><?= e((string) ($profile['total_orders'] ?? 0)) ?></strong>
            </div>

            <div class="profile-actions">
                <a class="btn btn-secondary" href="<?= e(url('producer/dashboard.php')) ?>">
                    Panele Dön
                </a>

                <a class="btn btn-secondary" href="<?= e(url('producer-detail.php?id=' . $userId)) ?>">
                    Public Profili Gör
                </a>
            </div>
        </aside>

        <div class="card profile-form-card">
            <h2>Bilgilerimi Güncelle</h2>

            <form
                method="post"
                action="<?= e(url('producer/profile.php')) ?>"
                enctype="multipart/form-data"
                class="profile-form"
            >
                <?= csrf_field() ?>

                <div class="form-row">
                    <div class="form-group">
                        <label for="profile_photo">Kişisel Profil Fotoğrafı</label>
                        <input type="file" id="profile_photo" name="profile_photo" accept="image/jpeg,image/png,image/webp">
                        <small>Üst menüde görünebilir. JPG, PNG veya WEBP, en fazla 5 MB.</small>

                        <?php if (!empty($profile['profile_photo'])): ?>
                            <label class="checkbox-line">
                                <input type="checkbox" name="remove_profile_photo" value="1">
                                Kişisel fotoğrafı kaldır
                            </label>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="logo_path">Market / Çiftlik Logosu</label>
                        <input type="file" id="logo_path" name="logo_path" accept="image/jpeg,image/png,image/webp">
                        <small>Public üretici profilinde gösterilir.</small>

                        <?php if (!empty($profile['logo_path'])): ?>
                            <label class="checkbox-line">
                                <input type="checkbox" name="remove_logo" value="1">
                                Logoyu kaldır
                            </label>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label for="cover_photo_path">Kapak Görseli</label>
                    <input type="file" id="cover_photo_path" name="cover_photo_path" accept="image/jpeg,image/png,image/webp">
                    <small>Profilin üst alanında geniş görsel olarak görünür.</small>

                    <?php if (!empty($profile['cover_photo_path'])): ?>
                        <label class="checkbox-line">
                            <input type="checkbox" name="remove_cover" value="1">
                            Kapak görselini kaldır
                        </label>
                    <?php endif; ?>
                </div>

                <div class="form-row">
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
                </div>

                <div class="form-group">
                    <label for="email">Hesap E-postası</label>
                    <input type="email" id="email" value="<?= e($profile['email'] ?? '') ?>" disabled>
                    <small>Hesap e-postası bu sayfadan değiştirilmiyor.</small>
                </div>

                <div class="form-row">
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
                </div>

                <div class="form-row">
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
                </div>

                <div class="form-group">
                    <label for="contact_whatsapp">İletişim WhatsApp</label>
                    <input
                        type="text"
                        id="contact_whatsapp"
                        name="contact_whatsapp"
                        value="<?= e($profile['contact_whatsapp'] ?? '') ?>"
                        placeholder="05xx xxx xx xx"
                    >
                </div>

                <div class="form-row">
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
                </div>

                <div class="form-group">
                    <label for="address_text">Adres</label>
                    <textarea
                        id="address_text"
                        name="address_text"
                        rows="3"
                        placeholder="Mahalle, cadde, sokak, bina no..."
                    ><?= e($profile['address_text'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label for="description">Üretici Açıklaması</label>
                    <textarea
                        id="description"
                        name="description"
                        rows="4"
                        placeholder="Çiftliğini, ürünlerini, üretim şeklini anlat..."
                    ><?= e($profile['description'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label for="shipping_note">Gönderim / Teslimat Notu</label>
                    <textarea
                        id="shipping_note"
                        name="shipping_note"
                        rows="3"
                        placeholder="Hangi bölgelere gönderim yapıyorsun, teslimat günlerin neler?"
                    ><?= e($profile['shipping_note'] ?? '') ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn">
                        Profilimi Kaydet
                    </button>

                    <a class="btn btn-secondary" href="<?= e(url('producer/dashboard.php')) ?>">
                        Panele Dön
                    </a>
                </div>
            </form>
        </div>
    </div>
</section>

<style>
    .profile-page {
        display: grid;
        gap: 24px;
    }

    .producer-cover {
        position: relative;
        overflow: hidden;
        min-height: 260px;
        padding: 0;
        background: linear-gradient(135deg, #e8f3e9, #ffffff);
    }

    .producer-cover-image {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        opacity: .32;
    }

    .producer-cover-content {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        gap: 24px;
        padding: 36px;
        min-height: 260px;
    }

    .producer-logo {
        width: 122px;
        height: 122px;
        border-radius: 50%;
        object-fit: cover;
        border: 5px solid #ffffff;
        box-shadow: 0 10px 28px rgba(35, 78, 46, .18);
        background: #e8f3e9;
    }

    .producer-logo-placeholder {
        display: flex;
        align-items: center;
        justify-content: center;
        color: #2f7d3d;
        font-size: 44px;
        font-weight: 900;
    }

    .producer-cover h1 {
        margin: 0 0 10px;
        color: #245c2f;
        font-size: 38px;
    }

    .producer-cover p {
        margin: 0;
        color: #334033;
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

    .profile-location-line {
        margin-top: 12px;
        display: inline-flex;
        padding: 8px 12px;
        border-radius: 999px;
        background: #e8f3e9;
        color: #245c2f;
        font-weight: 800;
        font-size: 14px;
    }

    .profile-grid {
        display: grid;
        grid-template-columns: 360px minmax(0, 1fr);
        gap: 24px;
        align-items: start;
    }

    .profile-summary-card h2,
    .profile-form-card h2 {
        margin-top: 0;
        color: #245c2f;
    }

    .summary-row {
        padding: 14px 0;
        border-bottom: 1px solid #edf1ea;
        display: grid;
        gap: 6px;
    }

    .summary-row span {
        color: #718071;
        font-size: 13px;
        font-weight: 700;
    }

    .summary-row strong {
        color: #1f2d1f;
        word-break: break-word;
        line-height: 1.5;
    }

    .profile-form {
        display: grid;
        gap: 16px;
    }

    .form-row {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
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
    .form-group select,
    .form-group textarea {
        width: 100%;
        border: 1px solid #dce6d9;
        border-radius: 10px;
        padding: 12px 13px;
        font: inherit;
        background: #ffffff;
        color: #1f2d1f;
    }

    .form-group input[type="file"] {
        padding: 10px;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #2f7d3d;
        box-shadow: 0 0 0 3px rgba(47, 125, 61, .12);
    }

    .form-group input:disabled {
        background: #f2f5ef;
        color: #718071;
        cursor: not-allowed;
    }

    .form-group small {
        color: #718071;
        line-height: 1.4;
    }

    .checkbox-line {
        display: inline-flex !important;
        align-items: center;
        gap: 8px;
        margin-top: 6px;
        color: #526052 !important;
        font-weight: 700 !important;
    }

    .checkbox-line input {
        width: auto;
    }

    .profile-actions,
    .form-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 18px;
    }

    @media (max-width: 900px) {
        .profile-grid {
            grid-template-columns: 1fr;
        }

        .producer-cover-content {
            align-items: flex-start;
        }
    }

    @media (max-width: 640px) {
        .producer-cover-content {
            flex-direction: column;
            padding: 26px;
        }

        .form-row {
            grid-template-columns: 1fr;
        }

        .producer-cover h1 {
            font-size: 30px;
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
    });
</script>

<?php require APP_PATH . '/Views/layouts/footer.php'; ?>