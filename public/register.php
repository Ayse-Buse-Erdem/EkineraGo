<?php

require_once __DIR__ . '/../app/bootstrap.php';

GuestMiddleware::handle();

$controller = new AuthController();

if (is_post()) {
    $controller->register();
}

$formErrors = errors();
$selectedRole = old('role', ROLE_CONSUMER);

$pageTitle = 'Kayıt Ol';
$bodyClass = 'page-auth page-register';

require APP_PATH . '/Views/layouts/header.php';

?>

<main class="register-page">
    <section class="register-wrapper">

        <div class="register-info">
            <span class="register-mini-title">EkineraGo</span>

            <h1>Aracısız alışverişe katıl</h1>

            <p>
                Taze ürünlere doğrudan ulaşmak veya kendi ürünlerini tüketiciyle
                buluşturmak için EkineraGo hesabını oluştur.
            </p>

            <div class="register-info-card">
                <div class="info-card-icon">🌿</div>

                <div>
                    <strong>Taze ürün, doğrudan kaynak</strong>
                    <span>
                        Üretici ile tüketiciyi aynı platformda buluşturan sade,
                        güvenilir ve yerel odaklı alışveriş deneyimi.
                    </span>
                </div>
            </div>

            <div class="register-stats">
                <div>
                    <strong>🚜</strong>
                    <span>Yerel üretici</span>
                </div>

                <div>
                    <strong>🛒</strong>
                    <span>Kolay sipariş</span>
                </div>

                <div>
                    <strong>💚</strong>
                    <span>Doğrudan iletişim</span>
                </div>
            </div>
        </div>

        <div class="register-box">
            <div class="register-box-header">
                <span class="register-icon">✨</span>

                <div>
                    <h2>Kayıt Ol</h2>
                    <p>Hesap tipini seç ve bilgilerini gir.</p>
                </div>
            </div>

            <?php if (!empty($formErrors['general'])): ?>
                <div class="auth-alert auth-alert-error">
                    <?= e($formErrors['general'][0]) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= e(url('register.php')) ?>" class="register-form">
                <?= csrf_field() ?>

                <div class="role-selector">
                    <label class="role-card <?= $selectedRole === ROLE_CONSUMER ? 'active' : '' ?>">
                        <input
                            type="radio"
                            name="role"
                            value="<?= e(ROLE_CONSUMER) ?>"
                            <?= $selectedRole === ROLE_CONSUMER ? 'checked' : '' ?>
                        >

                        <span class="role-icon">🛒</span>

                        <span>
                            <strong>Tüketici</strong>
                            <small>Taze ürünleri incele ve sipariş ver.</small>
                        </span>
                    </label>

                    <label class="role-card <?= $selectedRole === ROLE_PRODUCER ? 'active' : '' ?>">
                        <input
                            type="radio"
                            name="role"
                            value="<?= e(ROLE_PRODUCER) ?>"
                            <?= $selectedRole === ROLE_PRODUCER ? 'checked' : '' ?>
                        >

                        <span class="role-icon">🚜</span>

                        <span>
                            <strong>Üretici</strong>
                            <small>Ürünlerini doğrudan tüketiciye sun.</small>
                        </span>
                    </label>
                </div>

                <?php if (!empty($formErrors['role'])): ?>
                    <div class="form-error">
                        <?= e($formErrors['role'][0]) ?>
                    </div>
                <?php endif; ?>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="full_name">Ad Soyad</label>

                        <input
                            type="text"
                            name="full_name"
                            id="full_name"
                            placeholder="Adın ve soyadın"
                            value="<?= e((string) old('full_name')) ?>"
                        >

                        <?php if (!empty($formErrors['full_name'])): ?>
                            <div class="form-error">
                                <?= e($formErrors['full_name'][0]) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="email">E-posta</label>

                        <input
                            type="email"
                            name="email"
                            id="email"
                            placeholder="ornek@mail.com"
                            value="<?= e((string) old('email')) ?>"
                        >

                        <?php if (!empty($formErrors['email'])): ?>
                            <div class="form-error">
                                <?= e($formErrors['email'][0]) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="phone">Telefon</label>

                        <input
                            type="text"
                            name="phone"
                            id="phone"
                            placeholder="05xx xxx xx xx"
                            value="<?= e((string) old('phone')) ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="whatsapp_phone">WhatsApp Telefon</label>

                        <input
                            type="text"
                            name="whatsapp_phone"
                            id="whatsapp_phone"
                            placeholder="05xx xxx xx xx"
                            value="<?= e((string) old('whatsapp_phone')) ?>"
                        >
                    </div>
                </div>

                <div
                    class="producer-fields <?= $selectedRole === ROLE_PRODUCER ? 'is-visible' : '' ?>"
                    id="producerFields"
                >
                    <div class="producer-fields-title">
                        <span>🚜</span>

                        <div>
                            <strong>Üretici Bilgileri</strong>
                            <small>Bu alanlar sadece üretici hesabı için kullanılır.</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="store_name">Market / Çiftlik Adı</label>

                        <input
                            type="text"
                            name="store_name"
                            id="store_name"
                            placeholder="Örn: Yeşil Vadi Çiftliği"
                            value="<?= e((string) old('store_name')) ?>"
                        >

                        <?php if (!empty($formErrors['store_name'])): ?>
                            <div class="form-error">
                                <?= e($formErrors['store_name'][0]) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="description">Üretici Açıklaması</label>

                        <textarea
                            name="description"
                            id="description"
                            rows="3"
                            placeholder="Ürünlerin, üretim şeklin veya çiftliğin hakkında kısa bilgi yaz."
                        ><?= e((string) old('description')) ?></textarea>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="password">Şifre</label>

                        <input
                            type="password"
                            name="password"
                            id="password"
                            placeholder="Şifreni oluştur"
                        >

                        <?php if (!empty($formErrors['password'])): ?>
                            <div class="form-error">
                                <?= e($formErrors['password'][0]) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="password_confirmation">Şifre Tekrarı</label>

                        <input
                            type="password"
                            name="password_confirmation"
                            id="password_confirmation"
                            placeholder="Şifreni tekrar gir"
                        >

                        <?php if (!empty($formErrors['password_confirmation'])): ?>
                            <div class="form-error">
                                <?= e($formErrors['password_confirmation'][0]) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <button type="submit" class="register-submit-button">
                    Kayıt Ol
                </button>
            </form>

            <div class="register-bottom-link">
                Zaten hesabın var mı?
                <a href="<?= e(url('login.php')) ?>">Giriş yap</a>
            </div>
        </div>

    </section>
</main>

<style>
    .register-page {
        min-height: calc(100vh - 74px);
        padding: 54px 20px 72px;
        background:
            radial-gradient(circle at top left, rgba(176, 221, 166, 0.38), transparent 34%),
            radial-gradient(circle at bottom right, rgba(255, 221, 156, 0.25), transparent 34%),
            linear-gradient(135deg, #f8fcf5 0%, #eef8ec 100%);
    }

    .register-wrapper {
        width: min(100%, 1180px);
        margin: 0 auto;
        display: grid;
        grid-template-columns: 0.9fr 1.1fr;
        gap: 36px;
        align-items: start;
    }

    .register-info {
        padding: 30px;
        align-self: start;
    }

    .register-mini-title {
        display: inline-flex;
        align-items: center;
        padding: 8px 16px;
        margin-bottom: 18px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.82);
        border: 1px solid #d7ead2;
        color: #2f7d3d;
        font-weight: 900;
        letter-spacing: 0.05em;
    }

    .register-info h1 {
        margin: 0;
        color: #245c2f;
        font-size: clamp(38px, 5vw, 64px);
        line-height: 1.02;
        letter-spacing: -0.06em;
    }

    .register-info p {
        max-width: 620px;
        margin: 18px 0 0;
        color: #667366;
        font-size: 18px;
        line-height: 1.75;
    }

    .register-info-card {
        margin-top: 28px;
        max-width: 540px;
        display: flex;
        gap: 16px;
        padding: 18px;
        border-radius: 24px;
        background: rgba(255, 255, 255, 0.82);
        border: 1px solid rgba(215, 234, 210, 0.95);
        box-shadow: 0 16px 36px rgba(36, 92, 47, 0.08);
    }

    .info-card-icon {
        width: 52px;
        height: 52px;
        flex: 0 0 auto;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 18px;
        background: #e8f3e9;
        font-size: 24px;
    }

    .register-info-card strong {
        display: block;
        color: #245c2f;
        font-size: 18px;
        margin-bottom: 5px;
    }

    .register-info-card span {
        display: block;
        color: #6d7b6d;
        line-height: 1.55;
        font-size: 14px;
    }

    .register-stats {
        margin-top: 18px;
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
        max-width: 540px;
    }

    .register-stats div {
        padding: 16px 12px;
        border-radius: 20px;
        background: rgba(255, 255, 255, 0.72);
        border: 1px solid rgba(215, 234, 210, 0.9);
        text-align: center;
    }

    .register-stats strong {
        display: block;
        font-size: 24px;
        margin-bottom: 7px;
    }

    .register-stats span {
        display: block;
        color: #526052;
        font-size: 13px;
        font-weight: 800;
    }

    .register-box {
        width: 100%;
        background: rgba(255, 255, 255, 0.94);
        border: 1px solid #dfeedd;
        border-radius: 30px;
        padding: 30px;
        box-shadow: 0 28px 80px rgba(36, 92, 47, 0.15);
        backdrop-filter: blur(16px);
        align-self: start;
    }

    .register-box-header {
        display: flex;
        align-items: center;
        gap: 14px;
        margin-bottom: 22px;
    }

    .register-icon {
        width: 54px;
        height: 54px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 18px;
        background: #e8f3e9;
        font-size: 24px;
        box-shadow: 0 12px 28px rgba(47, 125, 61, 0.12);
    }

    .register-box-header h2 {
        margin: 0;
        color: #245c2f;
        font-size: 30px;
        letter-spacing: -0.04em;
    }

    .register-box-header p {
        margin: 4px 0 0;
        color: #667366;
        font-size: 14px;
    }

    .auth-alert {
        padding: 12px 14px;
        border-radius: 14px;
        margin-bottom: 14px;
        font-weight: 800;
        font-size: 14px;
    }

    .auth-alert-error {
        background: #ffe8e8;
        color: #9b111e;
        border: 1px solid #ffd0d0;
    }

    .register-form {
        display: grid;
        gap: 16px;
    }

    .role-selector {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }

    .role-card {
        position: relative;
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 15px;
        border-radius: 20px;
        background: #f8fcf5;
        border: 1px solid #dfeedd;
        cursor: pointer;
        transition: 0.2s ease;
    }

    .role-card:hover,
    .role-card.active {
        background: #e8f3e9;
        border-color: #a9dba5;
        transform: translateY(-1px);
    }

    .role-card input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .role-icon {
        width: 44px;
        height: 44px;
        flex: 0 0 auto;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 15px;
        background: #ffffff;
        font-size: 21px;
        box-shadow: 0 8px 18px rgba(36, 92, 47, 0.07);
    }

    .role-card strong {
        display: block;
        color: #245c2f;
        font-size: 15px;
    }

    .role-card small {
        display: block;
        color: #6d7b6d;
        font-size: 12px;
        line-height: 1.45;
        margin-top: 2px;
    }

    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px;
    }

    .form-group label {
        display: block;
        margin-bottom: 7px;
        color: #28452d;
        font-weight: 900;
        font-size: 14px;
    }

    .form-error {
        margin-top: 7px;
        color: #b00020;
        font-size: 13px;
        font-weight: 700;
    }

    .producer-fields {
        display: none;
        padding: 18px;
        border-radius: 24px;
        background: #f6fbf4;
        border: 1px solid #dcebd8;
        box-shadow: inset 0 0 0 1px rgba(255,255,255,0.5);
    }

    .producer-fields.is-visible {
        display: grid;
        gap: 15px;
    }

    .producer-fields-title {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .producer-fields-title > span {
        width: 42px;
        height: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 15px;
        background: #ffffff;
        font-size: 21px;
        box-shadow: 0 8px 18px rgba(36, 92, 47, 0.07);
    }

    .producer-fields-title strong {
        display: block;
        color: #245c2f;
        font-size: 16px;
    }

    .producer-fields-title small {
        display: block;
        color: #6d7b6d;
        font-size: 13px;
        margin-top: 2px;
    }

    .register-submit-button {
        width: 100%;
        min-height: 50px;
        border: none;
        border-radius: 16px;
        background: #2f7d3d;
        color: #ffffff;
        font-size: 15px;
        font-weight: 900;
        cursor: pointer;
        box-shadow: 0 16px 34px rgba(47, 125, 61, 0.22);
        transition: 0.2s ease;
    }

    .register-submit-button:hover {
        background: #245c2f;
        transform: translateY(-2px);
    }

    .register-bottom-link {
        margin-top: 20px;
        text-align: center;
        color: #667366;
        font-weight: 700;
    }

    .register-bottom-link a {
        color: #2f7d3d;
        font-weight: 900;
        text-decoration: none;
    }

    .register-bottom-link a:hover {
        text-decoration: underline;
    }

    @media (max-width: 980px) {
        .register-wrapper {
            grid-template-columns: 1fr;
        }

        .register-info {
            padding: 10px 4px;
            text-align: center;
        }

        .register-info p,
        .register-info-card,
        .register-stats {
            margin-left: auto;
            margin-right: auto;
        }

        .register-info-card {
            text-align: left;
        }
    }

    @media (max-width: 680px) {
        .register-page {
            padding: 34px 14px 54px;
        }

        .register-box {
            padding: 22px;
            border-radius: 24px;
        }

        .role-selector,
        .form-grid,
        .register-stats {
            grid-template-columns: 1fr;
        }

        .register-box-header h2 {
            font-size: 26px;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const producerValue = <?= json_encode(ROLE_PRODUCER) ?>;
        const roleInputs = document.querySelectorAll('input[name="role"]');
        const producerFields = document.getElementById('producerFields');
        const roleCards = document.querySelectorAll('.role-card');

        function updateRoleView() {
            let selectedValue = '';

            roleInputs.forEach(function (input) {
                if (input.checked) {
                    selectedValue = input.value;
                }
            });

            if (producerFields) {
                producerFields.classList.toggle('is-visible', selectedValue === producerValue);
            }

            roleCards.forEach(function (card) {
                const input = card.querySelector('input[name="role"]');

                if (input) {
                    card.classList.toggle('active', input.checked);
                }
            });
        }

        roleInputs.forEach(function (input) {
            input.addEventListener('change', updateRoleView);
        });

        updateRoleView();
    });
</script>

<?php require APP_PATH . '/Views/layouts/footer.php'; ?>

<?php clear_old(); ?>