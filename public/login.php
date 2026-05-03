<?php

require_once __DIR__ . '/../app/bootstrap.php';

// GuestMiddleware::handle();

$controller = new AuthController();

if (is_post()) {
    $controller->login();
}

$formErrors = errors();

$pageTitle = 'Giriş Yap';
$bodyClass = 'page-auth page-login';

require APP_PATH . '/Views/layouts/header.php';

?>

<main class="auth-page">
    <section class="auth-wrapper">

        <div class="auth-info">
            <span class="auth-mini-title">EkineraGo</span>

            <h1>Tekrar hoş geldin</h1>

            <p>
                Hesabına giriş yaparak taze ürünleri inceleyebilir,
                sepetini yönetebilir ve sipariş sürecini kolayca takip edebilirsin.
            </p>

            <div class="auth-feature-list">
                <div class="auth-feature">
                    <span>🌿</span>
                    <div>
                        <strong>Taze Ürün</strong>
                        <small>Üreticiden doğrudan ürünlere ulaş.</small>
                    </div>
                </div>

                <div class="auth-feature">
                    <span>🚜</span>
                    <div>
                        <strong>Yerel Üretici</strong>
                        <small>Üreticilerle aracısız bağlantı kur.</small>
                    </div>
                </div>

                <div class="auth-feature">
                    <span>🛒</span>
                    <div>
                        <strong>Kolay Sipariş</strong>
                        <small>Ürünleri sepetine ekle ve süreci takip et.</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="auth-box">
            <div class="auth-box-header">
                <span class="auth-icon">👤</span>

                <div>
                    <h2>Giriş Yap</h2>
                    <p>EkineraGo hesabına giriş yap.</p>
                </div>
            </div>

            <?php if ($message = flash_get('success')): ?>
                <div class="auth-alert auth-alert-success">
                    <?= e($message) ?>
                </div>
            <?php endif; ?>

            <?php if ($message = flash_get('error')): ?>
                <div class="auth-alert auth-alert-error">
                    <?= e($message) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($formErrors['general'])): ?>
                <div class="auth-alert auth-alert-error">
                    <?= e($formErrors['general'][0]) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= e(url('login.php')) ?>" class="auth-form">
                <?= csrf_field() ?>

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

                <div class="form-group">
                    <label for="password">Şifre</label>

                    <input
                        type="password"
                        name="password"
                        id="password"
                        placeholder="Şifreni gir"
                    >

                    <?php if (!empty($formErrors['password'])): ?>
                        <div class="form-error">
                            <?= e($formErrors['password'][0]) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="auth-submit-button">
                    Giriş Yap
                </button>
            </form>

            <div class="auth-bottom-link">
                Hesabın yok mu?
                <a href="<?= e(url('register.php')) ?>">Kayıt ol</a>
            </div>
        </div>

    </section>
</main>

<style>
    .auth-page {
        min-height: calc(100vh - 74px);
        padding: 56px 20px 70px;
        background:
            radial-gradient(circle at top left, rgba(176, 221, 166, 0.38), transparent 34%),
            radial-gradient(circle at bottom right, rgba(255, 221, 156, 0.28), transparent 34%),
            linear-gradient(135deg, #f8fcf5 0%, #eef8ec 100%);
    }

    .auth-wrapper {
        width: min(100%, 1120px);
        margin: 0 auto;
        display: grid;
        grid-template-columns: 1.1fr 0.9fr;
        gap: 34px;
        align-items: center;
    }

    .auth-info {
        padding: 34px;
    }

    .auth-mini-title {
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

    .auth-info h1 {
        margin: 0;
        color: #245c2f;
        font-size: clamp(38px, 5vw, 66px);
        line-height: 1.02;
        letter-spacing: -0.06em;
    }

    .auth-info p {
        max-width: 620px;
        margin: 18px 0 0;
        color: #667366;
        font-size: 18px;
        line-height: 1.75;
    }

    .auth-feature-list {
        margin-top: 30px;
        display: grid;
        gap: 14px;
    }

    .auth-feature {
        display: flex;
        align-items: center;
        gap: 14px;
        max-width: 500px;
        padding: 15px;
        border-radius: 20px;
        background: rgba(255, 255, 255, 0.78);
        border: 1px solid rgba(215, 234, 210, 0.9);
        box-shadow: 0 10px 26px rgba(36, 92, 47, 0.07);
    }

    .auth-feature > span {
        width: 44px;
        height: 44px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 15px;
        background: #eef8ec;
        font-size: 21px;
    }

    .auth-feature strong {
        display: block;
        color: #245c2f;
        font-size: 15px;
    }

    .auth-feature small {
        display: block;
        margin-top: 3px;
        color: #6d7b6d;
        font-size: 13px;
        line-height: 1.4;
    }

    .auth-box {
        width: 100%;
        background: rgba(255, 255, 255, 0.92);
        border: 1px solid #dfeedd;
        border-radius: 30px;
        padding: 30px;
        box-shadow: 0 28px 80px rgba(36, 92, 47, 0.15);
        backdrop-filter: blur(16px);
    }

    .auth-box-header {
        display: flex;
        align-items: center;
        gap: 14px;
        margin-bottom: 22px;
    }

    .auth-icon {
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

    .auth-box-header h2 {
        margin: 0;
        color: #245c2f;
        font-size: 30px;
        letter-spacing: -0.04em;
    }

    .auth-box-header p {
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

    .auth-alert-success {
        background: #e7f7e8;
        color: #236b2c;
        border: 1px solid #cdeccd;
    }

    .auth-alert-error {
        background: #ffe8e8;
        color: #9b111e;
        border: 1px solid #ffd0d0;
    }

    .auth-form {
        display: grid;
        gap: 16px;
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

    .auth-submit-button {
        width: 100%;
        min-height: 48px;
        margin-top: 4px;
        border: none;
        border-radius: 15px;
        background: #2f7d3d;
        color: #ffffff;
        font-size: 15px;
        font-weight: 900;
        cursor: pointer;
        box-shadow: 0 16px 34px rgba(47, 125, 61, 0.22);
        transition: 0.2s ease;
    }

    .auth-submit-button:hover {
        background: #245c2f;
        transform: translateY(-2px);
    }

    .auth-bottom-link {
        margin-top: 20px;
        text-align: center;
        color: #667366;
        font-weight: 700;
    }

    .auth-bottom-link a {
        color: #2f7d3d;
        font-weight: 900;
        text-decoration: none;
    }

    .auth-bottom-link a:hover {
        text-decoration: underline;
    }

    @media (max-width: 900px) {
        .auth-wrapper {
            grid-template-columns: 1fr;
        }

        .auth-info {
            padding: 10px 4px;
            text-align: center;
        }

        .auth-info p {
            margin-left: auto;
            margin-right: auto;
        }

        .auth-feature {
            max-width: 100%;
            text-align: left;
        }
    }

    @media (max-width: 520px) {
        .auth-page {
            padding: 34px 14px 54px;
        }

        .auth-box {
            padding: 22px;
            border-radius: 24px;
        }

        .auth-box-header h2 {
            font-size: 26px;
        }
    }
</style>

<?php require APP_PATH . '/Views/layouts/footer.php'; ?>

<?php clear_old(); ?>