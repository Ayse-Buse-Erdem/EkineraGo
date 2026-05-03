<?php

require_once __DIR__ . '/../app/bootstrap.php';

$pageTitle = 'Ana Sayfa';
$bodyClass = 'page-home';

require APP_PATH . '/Views/layouts/header.php';

$user = currentUser();
?>

<main class="home-page">

    <section class="home-hero-section">
        <div class="home-hero-bg home-hero-bg-one"></div>
        <div class="home-hero-bg home-hero-bg-two"></div>

        <div class="container home-hero-container">

            <div class="hero-image-card">
                <img
                    src="<?= e(asset('images/ana-resim.jpg')) ?>"
                    alt="Taze sebze ve meyveler"
                    class="hero-main-image"
                >

                <div class="floating-badge badge-one">
                    <span>🌿</span>
                    Taze Ürün
                </div>

                <div class="floating-badge badge-two">
                    <span>🚜</span>
                    Doğrudan Üretici
                </div>
            </div>

            <div class="hero-content">
                <span class="hero-mini-title">EkineraGo</span>

                <h1>Taze Ürün, Doğrudan Kaynak</h1>

                <p>
                    Yerel üreticiler ile tüketicileri aracısız buluşturan EkineraGo,
                    üreticinin emeğini değerinde sunmasını ve tüketicinin taze ürüne
                    güvenilir şekilde ulaşmasını sağlar.
                </p>

                <?php if ($user): ?>
                    <div class="welcome-box">
                        Hoş geldin,
                        <strong><?= e($user['full_name'] ?? 'Kullanıcı') ?></strong>
                    </div>
                <?php endif; ?>

                <div class="producer-box">
                    <div>
                        <span class="producer-label">Üretici misin?</span>

                        <h2>Ürünlerini doğrudan tüketiciye ulaştır.</h2>

                        <p>
                            Kendi ürünlerini listeleyebilir, siparişlerini takip edebilir
                            ve tüketiciyle doğrudan iletişim kurabilirsin.
                        </p>
                    </div>

                    <?php if (!$user): ?>
                        <a class="producer-button" href="<?= e(url('register.php')) ?>">
                            Üretici Olarak Kayıt Ol
                        </a>
                    <?php elseif (($user['role'] ?? '') === ROLE_PRODUCER): ?>
                        <a class="producer-button" href="<?= e(url('producer/dashboard.php')) ?>">
                            Üretici Paneline Git
                        </a>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </section>

</main>

<style>
    :root {
        --green-dark: #245c2f;
        --green-main: #3f8f46;
        --green-soft: #eaf6e8;
        --green-light: #f6fbf3;
        --text-main: #263326;
        --text-muted: #647064;
        --white: #ffffff;
        --shadow-soft: 0 24px 70px rgba(36, 92, 47, 0.14);
        --shadow-card: 0 18px 45px rgba(36, 92, 47, 0.10);
    }

    .home-page {
        min-height: calc(100vh - 90px);
        background:
            radial-gradient(circle at top left, rgba(150, 210, 153, 0.35), transparent 34%),
            linear-gradient(135deg, #f8fcf5 0%, #eef8ec 100%);
        overflow: hidden;
    }

    .home-hero-section {
        position: relative;
        padding: 48px 0 64px;
    }

    .home-hero-bg {
        position: absolute;
        border-radius: 999px;
        filter: blur(2px);
        opacity: 0.65;
        z-index: 0;
        animation: floatShape 8s ease-in-out infinite;
    }

    .home-hero-bg-one {
        width: 260px;
        height: 260px;
        left: -80px;
        top: 80px;
        background: rgba(152, 210, 145, 0.55);
    }

    .home-hero-bg-two {
        width: 320px;
        height: 320px;
        right: -120px;
        bottom: 40px;
        background: rgba(255, 217, 145, 0.42);
        animation-delay: 1.5s;
    }

    .home-hero-container {
        position: relative;
        z-index: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 34px;
        text-align: center;
    }

    .hero-image-card {
        position: relative;
        width: min(100%, 720px);
        height: 390px;
        border-radius: 36px;
        overflow: hidden;
        background: var(--white);
        box-shadow: var(--shadow-soft);
        border: 1px solid rgba(209, 232, 202, 0.95);
        animation: imageReveal 850ms ease both;
    }

    .hero-main-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        transform: scale(1.04);
        animation: imageMove 8s ease-in-out infinite alternate;
    }

    .hero-image-card::after {
        content: "";
        position: absolute;
        inset: 0;
        background:
            linear-gradient(to bottom, rgba(255,255,255,0.04), rgba(36,92,47,0.16));
        pointer-events: none;
    }

    .floating-badge {
        position: absolute;
        z-index: 2;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 15px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.88);
        color: var(--green-dark);
        font-weight: 700;
        font-size: 14px;
        box-shadow: 0 12px 28px rgba(36, 92, 47, 0.16);
        backdrop-filter: blur(10px);
        animation: badgeFloat 4s ease-in-out infinite;
    }

    .badge-one {
        left: 22px;
        top: 22px;
    }

    .badge-two {
        right: 22px;
        bottom: 22px;
        animation-delay: 1s;
    }

    .hero-content {
        width: min(100%, 860px);
    }

    .hero-mini-title {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 8px 16px;
        margin-bottom: 16px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.78);
        border: 1px solid rgba(195, 225, 188, 0.95);
        color: var(--green-main);
        font-weight: 700;
        letter-spacing: 0.04em;
    }

    .hero-content h1 {
        margin: 0;
        color: var(--green-dark);
        font-size: clamp(34px, 5vw, 62px);
        line-height: 1.04;
        letter-spacing: -0.055em;
        font-weight: 800;
    }

    .hero-content > p {
        max-width: 760px;
        margin: 18px auto 0;
        color: var(--text-muted);
        font-size: 18px;
        line-height: 1.75;
    }

    .welcome-box {
        width: fit-content;
        margin: 20px auto 0;
        padding: 12px 18px;
        border-radius: 18px;
        background: rgba(255, 255, 255, 0.82);
        border: 1px solid rgba(205, 229, 199, 0.9);
        color: var(--green-dark);
        box-shadow: var(--shadow-card);
    }

    .producer-box {
        margin: 32px auto 0;
        max-width: 780px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 22px;
        padding: 26px;
        border-radius: 30px;
        background: rgba(255, 255, 255, 0.84);
        border: 1px solid rgba(205, 229, 199, 0.95);
        box-shadow: var(--shadow-card);
        text-align: left;
    }

    .producer-label {
        display: inline-block;
        margin-bottom: 8px;
        color: var(--green-main);
        font-size: 14px;
        font-weight: 800;
    }

    .producer-box h2 {
        margin: 0 0 10px;
        color: var(--green-dark);
        font-size: 27px;
        line-height: 1.2;
    }

    .producer-box p {
        margin: 0;
        color: var(--text-muted);
        font-size: 16px;
        line-height: 1.6;
    }

    .producer-button {
        flex: 0 0 auto;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 48px;
        padding: 0 22px;
        border-radius: 999px;
        background: var(--green-dark);
        color: var(--white);
        text-decoration: none;
        font-weight: 800;
        box-shadow: 0 14px 28px rgba(36, 92, 47, 0.22);
        transition: transform 0.22s ease, box-shadow 0.22s ease, background 0.22s ease;
    }

    .producer-button:hover {
        transform: translateY(-2px);
        background: #1d4b27;
        box-shadow: 0 18px 36px rgba(36, 92, 47, 0.28);
    }

    @keyframes imageReveal {
        from {
            opacity: 0;
            transform: translateY(24px) scale(0.96);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    @keyframes imageMove {
        from {
            transform: scale(1.04) translateX(-8px);
        }

        to {
            transform: scale(1.1) translateX(8px);
        }
    }

    @keyframes badgeFloat {
        0%, 100% {
            transform: translateY(0);
        }

        50% {
            transform: translateY(-8px);
        }
    }

    @keyframes floatShape {
        0%, 100% {
            transform: translateY(0) scale(1);
        }

        50% {
            transform: translateY(18px) scale(1.05);
        }
    }

    @media (max-width: 768px) {
        .home-hero-section {
            padding: 28px 0 48px;
        }

        .hero-image-card {
            height: 290px;
            border-radius: 28px;
        }

        .floating-badge {
            font-size: 12px;
            padding: 8px 12px;
        }

        .hero-content > p {
            font-size: 16px;
        }

        .producer-box {
            flex-direction: column;
            align-items: stretch;
            text-align: center;
            padding: 22px;
            border-radius: 24px;
        }

        .producer-button {
            width: 100%;
        }
    }

    @media (max-width: 480px) {
        .hero-image-card {
            height: 235px;
            border-radius: 22px;
        }

        .badge-one {
            left: 12px;
            top: 12px;
        }

        .badge-two {
            right: 12px;
            bottom: 12px;
        }

        .producer-box h2 {
            font-size: 22px;
        }
    }
</style>

<?php require APP_PATH . '/Views/layouts/footer.php'; ?>