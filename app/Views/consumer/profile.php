<?php

require_once __DIR__ . '/../../app/bootstrap.php';

ConsumerMiddleware::handle();

$userId = (int) currentUserId();
$pdo = db();

$errors = [];

function profile_image_url(?string $path): string
{
    if (!$path) {
        return '';
    }

    return url($path);
}

function profile_initial(?string $name): string
{
    $name = trim((string) $name);

    if ($name === '') {
        return 'K';
    }

    return mb_strtoupper(mb_substr($name, 0, 1));
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
    $provinceId = (int) post('province_id', 0);
    $districtId = (int) post('district_id', 0);
    $addressText = trim((string) post('address_text', ''));
    $bio = trim((string) post('bio', ''));

    $provinceId = $provinceId > 0 ? $provinceId : null;
    $districtId = $districtId > 0 ? $districtId : null;

    if ($fullName === '') {
        $errors[] = 'Ad soyad alanı boş bırakılamaz.';
    }

    if (mb_strlen($fullName) > 120) {
        $errors[] = 'Ad soyad en fazla 120 karakter olabilir.';
    }

    if ($phone !== '' && mb_strlen($phone) > 30) {
        $errors[] = 'Telefon numarası en fazla 30 karakter olabilir.';
    }

    if ($whatsappPhone !== '' && mb_strlen($whatsappPhone) > 30) {
        $errors[] = 'WhatsApp numarası en fazla 30 karakter olabilir.';
    }

    if ($addressText !== '' && mb_strlen($addressText) > 1000) {
        $errors[] = 'Adres en fazla 1000 karakter olabilir.';
    }

    if ($bio !== '' && mb_strlen($bio) > 255) {
        $errors[] = 'Hakkımda alanı en fazla 255 karakter olabilir.';
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

    $uploadedProfilePhoto = null;
    $shouldRemovePhoto = post('remove_profile_photo', '') === '1';

    if (
        isset($_FILES['profile_photo'])
        && is_array($_FILES['profile_photo'])
        && ($_FILES['profile_photo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE
    ) {
        if (($_FILES['profile_photo']['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            $errors[] = 'Profil fotoğrafı yüklenirken bir hata oluştu.';
        } else {
            $uploadedProfilePhoto = upload_image($_FILES['profile_photo'], 'users');

            if ($uploadedProfilePhoto === null) {
                $errors[] = 'Profil fotoğrafı geçerli değil. Lütfen jpg, jpeg, png veya webp formatında bir görsel yükleyin.';
            }
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $currentPhotoStatement = $pdo->prepare("
                SELECT profile_photo
                FROM users
                WHERE id = :id
                  AND role = 'consumer'
                LIMIT 1
            ");

            $currentPhotoStatement->execute([
                'id' => $userId,
            ]);

            $currentPhoto = $currentPhotoStatement->fetchColumn();

            $profilePhotoToSave = $currentPhoto ?: null;

            if ($shouldRemovePhoto) {
                $profilePhotoToSave = null;
            }

            if ($uploadedProfilePhoto !== null) {
                $profilePhotoToSave = $uploadedProfilePhoto;
            }

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
                  AND role = 'consumer'
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

            $upsertProfile = $pdo->prepare("
                INSERT INTO consumer_profiles (
                    user_id,
                    bio,
                    created_at,
                    updated_at
                ) VALUES (
                    :user_id,
                    :bio,
                    NOW(),
                    NOW()
                )
                ON DUPLICATE KEY UPDATE
                    bio = VALUES(bio),
                    updated_at = NOW()
            ");

            $upsertProfile->execute([
                'user_id' => $userId,
                'bio' => $bio !== '' ? $bio : null,
            ]);

            $pdo->commit();

            if (isset($_SESSION['user'])) {
                $_SESSION['user']['full_name'] = $fullName;
            }

            flash_success('Profil bilgilerin başarıyla güncellendi.');
            redirect('consumer/profile.php');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            flash_error('Profil güncellenirken bir hata oluştu.');
            redirect('consumer/profile.php');
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

$profileStatement = $pdo->prepare("
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
        cp.bio,
        p.name AS province_name,
        d.name AS district_name
    FROM users u
    LEFT JOIN consumer_profiles cp ON cp.user_id = u.id
    LEFT JOIN provinces p ON p.id = u.province_id
    LEFT JOIN districts d ON d.id = u.district_id
    WHERE u.id = :id
      AND u.role = 'consumer'
    LIMIT 1
");

$profileStatement->execute([
    'id' => $userId,
]);

$profile = $profileStatement->fetch();

if (!$profile) {
    flash_error('Profil bilgileri bulunamadı.');
    redirect('index.php');
}

$provinceStatement = $pdo->query("
    SELECT id, name
    FROM provinces
    ORDER BY name ASC
");

$provinces = $provinceStatement->fetchAll();

$districts = [];

if (!empty($profile['province_id'])) {
    $districtStatement = $pdo->prepare("
        SELECT id, name
        FROM districts
        WHERE province_id = :province_id
        ORDER BY name ASC
    ");

    $districtStatement->execute([
        'province_id' => (int) $profile['province_id'],
    ]);

    $districts = $districtStatement->fetchAll();
}

$pageTitle = 'Profilim';
$bodyClass = 'page-consumer-profile';

require APP_PATH . '/Views/layouts/header.php';

?>

<section class="profile-page">
    <div class="profile-hero card">
        <div class="profile-main-info">
            <div class="profile-photo-wrap">
                <?php if (!empty($profile['profile_photo'])): ?>
                    <img
                        src="<?= e(profile_image_url($profile['profile_photo'])) ?>"
                        alt="<?= e($profile['full_name'] ?? 'Profil Fotoğrafı') ?>"
                        class="profile-photo"
                    >
                <?php else: ?>
                    <div class="profile-photo profile-photo-placeholder">
                        <?= e(profile_initial($profile['full_name'] ?? 'K')) ?>
                    </div>
                <?php endif; ?>
            </div>

            <div>
                <p class="eyebrow">Tüketici Profili</p>
                <h1><?= e($profile['full_name'] ?? 'Profilim') ?></h1>

                <p>
                    Profil bilgilerini, iletişim bilgilerini, konumunu, teslimat adresini ve profil fotoğrafını buradan güncelleyebilirsin.
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
                <span>Ad Soyad</span>
                <strong><?= e($profile['full_name'] ?? '-') ?></strong>
            </div>

            <div class="summary-row">
                <span>E-posta</span>
                <strong><?= e($profile['email'] ?? '-') ?></strong>
            </div>

            <div class="summary-row">
                <span>Telefon</span>
                <strong><?= e($profile['phone'] ?: '-') ?></strong>
            </div>

            <div class="summary-row">
                <span>WhatsApp</span>
                <strong><?= e($profile['whatsapp_phone'] ?: '-') ?></strong>
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
                <span>Adres</span>
                <strong><?= e($profile['address_text'] ?: '-') ?></strong>
            </div>

            <div class="summary-row">
                <span>Hakkımda</span>
                <strong><?= e($profile['bio'] ?: '-') ?></strong>
            </div>

            <div class="summary-row">
                <span>Üyelik Tarihi</span>
                <strong>
                    <?= !empty($profile['created_at']) ? e(date('d.m.Y', strtotime((string) $profile['created_at']))) : '-' ?>
                </strong>
            </div>

            <div class="profile-actions">
                <a class="btn btn-secondary" href="<?= e(url('consumer/dashboard.php')) ?>">
                    Panele Dön
                </a>

                <a class="btn btn-secondary" href="<?= e(url('consumer/orders.php')) ?>">
                    Siparişlerim
                </a>
            </div>
        </aside>

        <div class="card profile-form-card">
            <h2>Bilgilerimi Güncelle</h2>

            <form
                method="post"
                action="<?= e(url('consumer/profile.php')) ?>"
                enctype="multipart/form-data"
                class="profile-form"
            >
                <?= csrf_field() ?>

                <div class="form-group">
                    <label for="profile_photo">Profil Fotoğrafı</label>

                    <input
                        type="file"
                        id="profile_photo"
                        name="profile_photo"
                        accept="image/jpeg,image/png,image/webp"
                    >

                    <small>
                        JPG, PNG veya WEBP yükleyebilirsin. Yeni fotoğraf seçmezsen mevcut fotoğrafın korunur.
                    </small>

                    <?php if (!empty($profile['profile_photo'])): ?>
                        <label class="checkbox-line">
                            <input type="checkbox" name="remove_profile_photo" value="1">
                            Mevcut profil fotoğrafımı kaldır
                        </label>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="full_name">Ad Soyad</label>
                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        value="<?= e($profile['full_name'] ?? '') ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="email">E-posta</label>
                    <input
                        type="email"
                        id="email"
                        value="<?= e($profile['email'] ?? '') ?>"
                        disabled
                    >
                    <small>
                        E-posta güvenlik için bu sayfadan değiştirilmiyor.
                    </small>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Telefon</label>
                        <input
                            type="text"
                            id="phone"
                            name="phone"
                            value="<?= e($profile['phone'] ?? '') ?>"
                            placeholder="05xx xxx xx xx"
                        >
                    </div>

                    <div class="form-group">
                        <label for="whatsapp_phone">WhatsApp Telefon</label>
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
                        <select
                            id="district_id"
                            name="district_id"
                            data-selected="<?= e((string) ($profile['district_id'] ?? '')) ?>"
                        >
                            <option value="">İlçe seç</option>

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
                    <label for="address_text">Teslimat Adresi</label>
                    <textarea
                        id="address_text"
                        name="address_text"
                        rows="4"
                        placeholder="Mahalle, cadde, sokak, bina no, daire no..."
                    ><?= e($profile['address_text'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label for="bio">Hakkımda</label>
                    <textarea
                        id="bio"
                        name="bio"
                        rows="3"
                        maxlength="255"
                        placeholder="Kendin hakkında kısa bir bilgi yazabilirsin."
                    ><?= e($profile['bio'] ?? '') ?></textarea>
                    <small>En fazla 255 karakter.</small>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn">
                        Bilgilerimi Kaydet
                    </button>

                    <a class="btn btn-secondary" href="<?= e(url('index.php')) ?>">
                        Ana Sayfa
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

    .profile-hero {
        padding: 30px;
    }

    .profile-main-info {
        display: flex;
        align-items: center;
        gap: 22px;
    }

    .profile-photo-wrap {
        flex: 0 0 auto;
    }

    .profile-photo {
        width: 112px;
        height: 112px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid #e8f3e9;
        box-shadow: 0 10px 24px rgba(35, 78, 46, 0.13);
    }

    .profile-photo-placeholder {
        display: flex;
        align-items: center;
        justify-content: center;
        background: #e8f3e9;
        color: #2f7d3d;
        font-size: 42px;
        font-weight: 900;
    }

    .profile-hero h1 {
        margin: 0 0 10px;
        color: #245c2f;
        font-size: 36px;
    }

    .profile-hero p {
        margin: 0;
        color: #526052;
        line-height: 1.6;
    }

    .profile-location-line {
        margin-top: 12px;
        display: inline-flex;
        align-items: center;
        padding: 8px 12px;
        border-radius: 999px;
        background: #e8f3e9;
        color: #245c2f;
        font-weight: 800;
        font-size: 14px;
    }

    .eyebrow {
        margin: 0 0 8px !important;
        color: #2f7d3d !important;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .08em;
        font-size: 12px;
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

    .profile-actions,
    .form-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 18px;
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

    @media (max-width: 900px) {
        .profile-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 640px) {
        .profile-main-info {
            flex-direction: column;
            align-items: flex-start;
        }

        .form-row {
            grid-template-columns: 1fr;
        }

        .profile-hero h1 {
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

            districtSelect.innerHTML = '<option value="">İlçe seç</option>';

            if (!provinceId) {
                return;
            }

            fetch('<?= e(url('api/district-list.php')) ?>?province_id=' + encodeURIComponent(provinceId), {
                headers: {
                    'Accept': 'application/json'
                }
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (result) {
                    if (!result.success || !Array.isArray(result.data)) {
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
                    districtSelect.innerHTML = '<option value="">İlçe alınamadı</option>';
                });
        });
    });
</script>

<?php require APP_PATH . '/Views/layouts/footer.php'; ?>
