<?php

require_once __DIR__ . '/../../app/bootstrap.php';

ProducerMiddleware::handle();

$producerId = (int) currentUserId();
$questionService = new ProductQuestionService();

$isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';

$status = trim((string) ($_GET['status'] ?? ''));
$allowedStatuses = ['', 'pending', 'answered', 'hidden'];

if (!in_array($status, $allowedStatuses, true)) {
    $status = '';
}

$focusQuestionId = (int) ($_GET['question_id'] ?? 0);

if (!function_exists('producer_questions_json')) {
    function producer_questions_json(array $payload): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

if (!function_exists('producer_question_status_label')) {
    function producer_question_status_label(string $status): string
    {
        return match ($status) {
            'pending' => 'Cevap Bekliyor',
            'answered' => 'Cevaplandı',
            'hidden' => 'Gizlendi',
            'deleted' => 'Silindi',
            default => 'Bilinmiyor',
        };
    }
}

if (!function_exists('producer_question_status_badge')) {
    function producer_question_status_badge(string $status): string
    {
        return match ($status) {
            'pending' => 'badge-warning',
            'answered' => 'badge-success',
            'hidden' => 'badge-muted',
            'deleted' => 'badge-danger',
            default => 'badge-muted',
        };
    }
}

if (!function_exists('producer_question_unit_label')) {
    function producer_question_unit_label(string $unit): string
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

if (!function_exists('producer_questions_money')) {
    function producer_questions_money(float $amount): string
    {
        if (function_exists('formatMoney')) {
            return formatMoney($amount);
        }

        return number_format($amount, 2, ',', '.') . ' TL';
    }
}

if (!function_exists('producer_questions_date')) {
    function producer_questions_date(?string $date): string
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

if (!function_exists('producer_questions_stats')) {
    function producer_questions_stats(array $questions): array
    {
        $stats = [
            'total' => count($questions),
            'pending' => 0,
            'answered' => 0,
            'hidden' => 0,
        ];

        foreach ($questions as $question) {
            $questionStatus = (string) ($question['status'] ?? 'pending');

            if (isset($stats[$questionStatus])) {
                $stats[$questionStatus]++;
            }
        }

        return $stats;
    }
}

if (!function_exists('producer_questions_render_hero_stats')) {
    function producer_questions_render_hero_stats(array $allQuestions): string
    {
        if (empty($allQuestions)) {
            return '';
        }

        $stats = producer_questions_stats($allQuestions);

        ob_start();
        ?>
        <div class="producer-questions-hero-stats">
            <span>❔ <?= e((string) $stats['total']) ?> soru</span>
            <span>⏳ <?= e((string) $stats['pending']) ?> cevap bekliyor</span>
            <span>✅ <?= e((string) $stats['answered']) ?> cevaplandı</span>
            <span>🙈 <?= e((string) $stats['hidden']) ?> gizlendi</span>
        </div>
        <?php

        return ob_get_clean();
    }
}

if (!function_exists('producer_questions_filter_url')) {
    function producer_questions_filter_url(string $status): string
    {
        if ($status === '') {
            return url('producer/questions.php');
        }

        return url('producer/questions.php?status=' . urlencode($status));
    }
}

if (!function_exists('producer_questions_render_dynamic_area')) {
    function producer_questions_render_dynamic_area(
        array $questions,
        array $allQuestions,
        string $status,
        int $pendingCount,
        int $focusQuestionId
    ): string {
        $stats = producer_questions_stats($allQuestions);

        ob_start();
        ?>

        <?php if (empty($allQuestions)): ?>
            <section class="producer-questions-empty-card glass-card">
                <div class="empty-icon">❔</div>

                <span class="producer-questions-eyebrow light">Henüz Soru Yok</span>

                <h2>Henüz ürün sorusu gelmedi.</h2>

                <p>
                    Tüketiciler ürün detay sayfalarından soru sorduğunda burada listelenecek.
                </p>

                <div class="empty-actions">
                    <a class="producer-questions-btn producer-questions-btn-primary" href="<?= e(url('producer/products.php')) ?>">
                        Ürünlerime Git
                    </a>

                    <a class="producer-questions-btn producer-questions-btn-light" href="<?= e(url('producer/dashboard.php')) ?>">
                        Panele Dön
                    </a>
                </div>
            </section>
        <?php else: ?>
            <section class="producer-questions-top glass-card">
                <div>
                    <span class="section-kicker">Soru Özeti</span>

                    <h2>Ürün soruları</h2>

                    <p>
                        Tüketicilerden gelen soruları cevaplayabilir, cevaplananları takip edebilir
                        ve ürün detayına hızlıca geçebilirsin.
                    </p>
                </div>

                <div class="producer-questions-total">
                    <span>Cevap Bekleyen</span>
                    <strong><?= e((string) $pendingCount) ?></strong>
                </div>
            </section>

            <section class="producer-questions-summary-grid">
                <article class="producer-question-stat-card glass-card">
                    <span>❔</span>

                    <div>
                        <strong><?= e((string) $stats['total']) ?></strong>
                        <p>Toplam Soru</p>
                    </div>
                </article>

                <article class="producer-question-stat-card glass-card">
                    <span>⏳</span>

                    <div>
                        <strong><?= e((string) $stats['pending']) ?></strong>
                        <p>Cevap Bekleyen</p>
                    </div>
                </article>

                <article class="producer-question-stat-card glass-card">
                    <span>✅</span>

                    <div>
                        <strong><?= e((string) $stats['answered']) ?></strong>
                        <p>Cevaplanan</p>
                    </div>
                </article>

                <article class="producer-question-stat-card glass-card">
                    <span>🙈</span>

                    <div>
                        <strong><?= e((string) $stats['hidden']) ?></strong>
                        <p>Gizlenen</p>
                    </div>
                </article>
            </section>

            <section class="producer-questions-filter-card glass-card">
                <div class="producer-questions-filter-links">
                    <a
                        href="<?= e(producer_questions_filter_url('')) ?>"
                        class="producer-question-filter-link <?= $status === '' ? 'active' : '' ?>"
                        data-question-filter=""
                    >
                        Tümü
                    </a>

                    <a
                        href="<?= e(producer_questions_filter_url('pending')) ?>"
                        class="producer-question-filter-link <?= $status === 'pending' ? 'active' : '' ?>"
                        data-question-filter="pending"
                    >
                        Cevap Bekleyenler
                    </a>

                    <a
                        href="<?= e(producer_questions_filter_url('answered')) ?>"
                        class="producer-question-filter-link <?= $status === 'answered' ? 'active' : '' ?>"
                        data-question-filter="answered"
                    >
                        Cevaplananlar
                    </a>

                    <a
                        href="<?= e(producer_questions_filter_url('hidden')) ?>"
                        class="producer-question-filter-link <?= $status === 'hidden' ? 'active' : '' ?>"
                        data-question-filter="hidden"
                    >
                        Gizlenenler
                    </a>
                </div>

                <div class="producer-question-search-box">
                    <span>🔎</span>

                    <input
                        type="search"
                        id="producer-question-search"
                        placeholder="Ürün, tüketici veya soru içinde ara..."
                        autocomplete="off"
                    >
                </div>
            </section>

            <?php if (empty($questions)): ?>
                <section class="producer-questions-empty-card glass-card soft-empty-state">
                    <div class="empty-icon">🔎</div>

                    <span class="producer-questions-eyebrow light">Sonuç Yok</span>

                    <h2>Bu filtrede soru bulunamadı.</h2>

                    <p>
                        Başka bir durum filtresi seçerek diğer soruları görüntüleyebilirsin.
                    </p>
                </section>
            <?php else: ?>
                <section class="producer-question-list" id="producer-question-list">
                    <?php foreach ($questions as $question): ?>
                        <?php
                            $questionId = (int) ($question['id'] ?? 0);
                            $productId = (int) ($question['product_id'] ?? 0);
                            $questionStatus = (string) ($question['status'] ?? 'pending');
                            $unit = producer_question_unit_label((string) ($question['product_unit_type'] ?? 'kg'));
                            $isFocused = $focusQuestionId > 0 && $focusQuestionId === $questionId;
                            $answer = trim((string) ($question['answer'] ?? ''));

                            $productTitle = $question['product_title'] ?? 'Ürün';
                            $consumerName = $question['consumer_name'] ?? 'Tüketici';
                            $questionText = $question['question'] ?? '';
                            $searchText = strtolower(trim($productTitle . ' ' . $consumerName . ' ' . $questionText . ' ' . $answer));
                        ?>

                        <article
                            class="producer-question-card glass-card <?= $isFocused ? 'focused' : '' ?>"
                            id="question-<?= e((string) $questionId) ?>"
                            data-question-card="<?= e((string) $questionId) ?>"
                            data-status="<?= e($questionStatus) ?>"
                            data-search="<?= e($searchText) ?>"
                        >
                            <div class="question-card-header">
                                <div class="question-title-area">
                                    <span class="question-icon">❔</span>

                                    <div>
                                        <h2><?= e($productTitle) ?></h2>

                                        <p>
                                            Soran:
                                            <strong><?= e($consumerName) ?></strong>
                                            ·
                                            <?= e(producer_questions_date($question['created_at'] ?? null)) ?>
                                        </p>

                                        <p>
                                            Ürün fiyatı:
                                            <strong>
                                                <?= e(producer_questions_money((float) ($question['product_price'] ?? 0))) ?>
                                                /
                                                <?= e($unit) ?>
                                            </strong>
                                        </p>
                                    </div>
                                </div>

                                <div class="question-status-area">
                                    <span class="producer-question-badge <?= e(producer_question_status_badge($questionStatus)) ?>">
                                        <?= e(producer_question_status_label($questionStatus)) ?>
                                    </span>

                                    <?php if ($productId > 0): ?>
                                        <a
                                            class="small-link"
                                            href="<?= e(url('product-detail.php?id=' . $productId . '#questions')) ?>"
                                            target="_blank"
                                        >
                                            Üründe Gör
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="question-body-box">
                                <strong>Tüketici sorusu:</strong>

                                <p><?= nl2br(e($questionText)) ?></p>
                            </div>

                            <div class="answer-area" data-answer-area="<?= e((string) $questionId) ?>">
                                <?php if ($answer !== ''): ?>
                                    <div class="answer-box">
                                        <strong>Senin cevabın:</strong>

                                        <p><?= nl2br(e($answer)) ?></p>

                                        <span>
                                            <?= e(producer_questions_date($question['answered_at'] ?? null)) ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php if ($questionStatus !== 'hidden' && $questionStatus !== 'deleted'): ?>
                                <form
                                    method="POST"
                                    action="<?= e(url('api/product-question-answer.php')) ?>"
                                    class="answer-form"
                                    data-answer-form="true"
                                >
                                    <?= csrf_field() ?>

                                    <input type="hidden" name="question_id" value="<?= e((string) $questionId) ?>">

                                    <div class="form-group">
                                        <label for="answer-<?= e((string) $questionId) ?>">
                                            <?= $answer !== '' ? 'Cevabı Güncelle' : 'Cevap Yaz' ?>
                                        </label>

                                        <textarea
                                            id="answer-<?= e((string) $questionId) ?>"
                                            name="answer"
                                            rows="4"
                                            maxlength="1000"
                                            placeholder="Tüketicinin sorusuna açık ve net cevap ver..."
                                            required
                                        ><?= e($answer) ?></textarea>
                                    </div>

                                    <button class="producer-questions-btn producer-questions-btn-primary" type="submit">
                                        <?= $answer !== '' ? 'Cevabı Güncelle' : 'Cevabı Gönder' ?>
                                    </button>
                                </form>
                            <?php else: ?>
                                <div class="inline-info-box">
                                    Bu soru gizlendiği veya silindiği için cevaplanamaz.
                                </div>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </section>

                <div class="producer-questions-no-result" id="producer-questions-no-result" hidden>
                    <span>🔎</span>
                    <strong>Aramana uygun soru bulunamadı.</strong>
                    <p>Farklı bir kelime deneyebilirsin.</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php

        return ob_get_clean();
    }
}

$allQuestions = $questionService->getProducerQuestions($producerId, '');
$questions = $questionService->getProducerQuestions($producerId, $status);
$pendingCount = $questionService->countPendingByProducerId($producerId);

if ($isAjax && isset($_GET['partial'])) {
    producer_questions_json([
        'success' => true,
        'html' => producer_questions_render_dynamic_area($questions, $allQuestions, $status, $pendingCount, $focusQuestionId),
        'heroStatsHtml' => producer_questions_render_hero_stats($allQuestions),
        'status' => $status,
        'pendingCount' => $pendingCount,
    ]);
}

$pageTitle = 'Ürün Soruları';
$bodyClass = 'page-producer-questions';

require APP_PATH . '/Views/layouts/header.php';
?>

<main class="producer-questions-page">
    <section class="producer-questions-hero">
        <div class="producer-questions-hero-bg questions-blob-one"></div>
        <div class="producer-questions-hero-bg questions-blob-two"></div>

        <div class="producer-questions-hero-inner">
            <nav class="producer-questions-breadcrumb" aria-label="Sayfa yolu">
                <a href="<?= e(url('index.php')) ?>">Ana Sayfa</a>
                <span>/</span>
                <a href="<?= e(url('producer/dashboard.php')) ?>">Üretici Paneli</a>
                <span>/</span>
                <strong>Ürün Soruları</strong>
            </nav>

            <div class="producer-questions-hero-content">
                <div class="producer-questions-hero-copy">
                    <span class="producer-questions-eyebrow">
                        Soru Cevap Merkezi
                    </span>

                    <h1>Ürün Soruları</h1>

                    <p>
                        Tüketicilerin ürünlerin hakkında sorduğu soruları buradan cevaplayabilir,
                        cevaplarını güncelleyebilir ve ürün detayına hızlıca geçebilirsin.
                    </p>

                    <div id="producer-questions-hero-stats-wrap">
                        <?= producer_questions_render_hero_stats($allQuestions) ?>
                    </div>
                </div>

                <div class="producer-questions-hero-card">
                    <div class="hero-card-icon">❔</div>

                    <h2>Hızlı cevap, güçlü güven</h2>

                    <p>
                        Ürün sorularına açıklayıcı cevap vermek tüketici kararını hızlandırır.
                    </p>

                    <div class="hero-mini-list">
                        <span>⏳ Bekleyenler</span>
                        <span>✅ Cevaplananlar</span>
                        <span>🌱 Ürün bağlantısı</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="producer-questions-shell">
        <div id="producer-questions-message" class="producer-questions-message" hidden></div>

        <div id="producer-questions-dynamic-area">
            <?= producer_questions_render_dynamic_area($questions, $allQuestions, $status, $pendingCount, $focusQuestionId) ?>
        </div>
    </section>
</main>

<style>
    :root {
        --questions-green-950: #102f1a;
        --questions-green-900: #163d22;
        --questions-green-800: #245c2f;
        --questions-green-700: #2f7d3d;
        --questions-green-600: #3f9650;
        --questions-green-100: #eaf6e8;
        --questions-green-50: #f6fbf4;
        --questions-cream: #fffaf1;
        --questions-yellow: #f2bf4d;
        --questions-red: #9b111e;
        --questions-text: #1e2b21;
        --questions-muted: #687669;
        --questions-border: rgba(47, 125, 61, .14);
        --questions-shadow: 0 24px 70px rgba(31, 79, 43, .12);
        --questions-radius-lg: 28px;
    }

    body.page-producer-questions {
        background:
            radial-gradient(circle at 14% 12%, rgba(196, 231, 177, .48), transparent 28%),
            radial-gradient(circle at 88% 16%, rgba(242, 191, 77, .16), transparent 24%),
            linear-gradient(180deg, #f8fbf2 0%, #f3f8ed 48%, #ffffff 100%);
    }

    .producer-questions-page {
        overflow: hidden;
    }

    .producer-questions-hero {
        position: relative;
        min-height: 390px;
        padding: 34px 18px 94px;
        background:
            radial-gradient(circle at 82% 18%, rgba(242, 191, 77, .30), transparent 26%),
            radial-gradient(circle at 12% 78%, rgba(255, 255, 255, .16), transparent 24%),
            linear-gradient(135deg, rgba(36, 92, 47, .97), rgba(47, 125, 61, .87));
        color: #ffffff;
    }

    .producer-questions-hero::after {
        content: '';
        position: absolute;
        inset: auto 0 -1px;
        height: 90px;
        background: linear-gradient(180deg, rgba(246, 251, 244, 0), #f6fbf4 82%);
        pointer-events: none;
    }

    .producer-questions-hero-inner,
    .producer-questions-shell {
        width: min(1180px, calc(100% - 32px));
        margin: 0 auto;
    }

    .producer-questions-hero-inner {
        position: relative;
        z-index: 2;
    }

    .producer-questions-hero-bg {
        position: absolute;
        border-radius: 999px;
        filter: blur(2px);
        opacity: .45;
        pointer-events: none;
    }

    .questions-blob-one {
        width: 230px;
        height: 230px;
        right: 10%;
        top: 42px;
        background: rgba(242, 191, 77, .28);
    }

    .questions-blob-two {
        width: 150px;
        height: 150px;
        left: 8%;
        bottom: 36px;
        background: rgba(255, 255, 255, .20);
    }

    .producer-questions-breadcrumb {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 9px;
        font-size: 14px;
        margin-bottom: 32px;
        color: rgba(255, 255, 255, .76);
    }

    .producer-questions-breadcrumb a {
        color: #ffffff;
        text-decoration: none;
        font-weight: 800;
    }

    .producer-questions-breadcrumb strong {
        color: #ffffff;
        font-weight: 900;
    }

    .producer-questions-hero-content {
        display: grid;
        grid-template-columns: minmax(0, 1.25fr) minmax(320px, .75fr);
        gap: 24px;
        align-items: center;
    }

    .producer-questions-eyebrow,
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

    .producer-questions-eyebrow {
        background: rgba(255, 255, 255, .16);
        border: 1px solid rgba(255, 255, 255, .28);
        color: #ffffff;
    }

    .producer-questions-eyebrow.light,
    .section-kicker {
        background: var(--questions-green-100);
        color: var(--questions-green-800);
        border-color: transparent;
    }

    .producer-questions-hero-copy h1 {
        margin: 17px 0 12px;
        font-size: clamp(36px, 5vw, 62px);
        line-height: 1.03;
        letter-spacing: -.045em;
    }

    .producer-questions-hero-copy p {
        max-width: 700px;
        margin: 0;
        color: rgba(255, 255, 255, .86);
        font-size: 17px;
        line-height: 1.7;
    }

    .producer-questions-hero-stats {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 18px;
    }

    .producer-questions-hero-stats span {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 9px 12px;
        border-radius: 999px;
        background: rgba(255, 255, 255, .16);
        border: 1px solid rgba(255, 255, 255, .24);
        color: #ffffff;
        font-size: 13px;
        font-weight: 900;
    }

    .producer-questions-hero-card {
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

    .producer-questions-hero-card h2 {
        margin: 0 0 10px;
        font-size: 25px;
        letter-spacing: -.03em;
    }

    .producer-questions-hero-card p {
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

    .producer-questions-shell {
        position: relative;
        z-index: 3;
        margin-top: -64px;
        padding-bottom: 54px;
    }

    .glass-card {
        background: rgba(255, 255, 255, .92);
        border: 1px solid rgba(255, 255, 255, .72);
        border-radius: var(--questions-radius-lg);
        box-shadow: var(--questions-shadow);
        backdrop-filter: blur(16px);
    }

    .producer-questions-message {
        margin-bottom: 16px;
        padding: 14px 16px;
        border-radius: 18px;
        font-weight: 900;
        box-shadow: 0 12px 30px rgba(31, 79, 43, .10);
        white-space: pre-line;
    }

    .producer-questions-message.success {
        background: #e7f7e8;
        color: #236b2c;
        border: 1px solid rgba(35, 107, 44, .14);
    }

    .producer-questions-message.error {
        background: #fdeaea;
        color: var(--questions-red);
        border: 1px solid rgba(155, 17, 30, .14);
    }

    .producer-questions-top {
        margin-bottom: 18px;
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 18px;
    }

    .producer-questions-top h2,
    .producer-questions-empty-card h2 {
        margin: 10px 0 6px;
        color: var(--questions-green-900);
        letter-spacing: -.03em;
    }

    .producer-questions-top p,
    .producer-questions-empty-card p {
        margin: 0;
        color: var(--questions-muted);
        line-height: 1.6;
    }

    .producer-questions-total {
        display: grid;
        justify-items: end;
        gap: 4px;
        min-width: 170px;
    }

    .producer-questions-total span {
        color: var(--questions-muted);
        font-size: 13px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .producer-questions-total strong {
        color: var(--questions-green-800);
        font-size: 34px;
        line-height: 1;
    }

    .producer-questions-summary-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
        margin-bottom: 22px;
    }

    .producer-question-stat-card {
        display: flex;
        align-items: center;
        gap: 13px;
        padding: 18px;
    }

    .producer-question-stat-card > span,
    .question-icon {
        width: 48px;
        height: 48px;
        display: grid;
        place-items: center;
        border-radius: 17px;
        background: var(--questions-green-100);
        flex: 0 0 auto;
        font-size: 22px;
    }

    .producer-question-stat-card strong {
        display: block;
        color: var(--questions-green-900);
        font-size: 21px;
    }

    .producer-question-stat-card p {
        margin: 4px 0 0;
        color: var(--questions-muted);
        font-size: 13px;
        font-weight: 800;
    }

    .producer-questions-filter-card {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(260px, .55fr);
        gap: 14px;
        align-items: center;
        padding: 16px;
        margin-bottom: 22px;
    }

    .producer-questions-filter-links {
        display: flex;
        flex-wrap: wrap;
        gap: 9px;
    }

    .producer-question-filter-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 42px;
        padding: 9px 13px;
        border-radius: 999px;
        background: var(--questions-green-50);
        color: var(--questions-green-800);
        border: 1px solid var(--questions-border);
        text-decoration: none;
        font-weight: 900;
        transition: transform .18s ease, background .18s ease;
    }

    .producer-question-filter-link:hover,
    .producer-question-filter-link.active {
        background: var(--questions-green-800);
        color: #ffffff;
        transform: translateY(-1px);
    }

    .producer-question-search-box {
        display: grid;
        grid-template-columns: 42px minmax(0, 1fr);
        align-items: center;
        gap: 8px;
        padding: 7px;
        border-radius: 17px;
        background: var(--questions-green-50);
        border: 1px solid var(--questions-border);
    }

    .producer-question-search-box span {
        width: 42px;
        height: 42px;
        display: grid;
        place-items: center;
        border-radius: 14px;
        background: #ffffff;
    }

    .producer-question-search-box input {
        width: 100%;
        border: 0;
        border-radius: 14px;
        padding: 13px 14px;
        font: inherit;
        font-weight: 800;
        outline: none;
        background: #ffffff;
        color: var(--questions-text);
        box-sizing: border-box;
    }

    .producer-question-list {
        display: grid;
        gap: 18px;
    }

    .producer-question-card {
        padding: 20px;
        border-left: 6px solid var(--questions-green-700);
        transition: transform .18s ease, box-shadow .18s ease, opacity .18s ease;
    }

    .producer-question-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 26px 60px rgba(31, 79, 43, .14);
    }

    .producer-question-card.focused {
        box-shadow: 0 0 0 4px rgba(47, 125, 61, .14), var(--questions-shadow);
    }

    .producer-question-card.is-hidden {
        display: none;
    }

    .producer-question-card.is-loading {
        opacity: .68;
        pointer-events: none;
    }

    .question-card-header {
        display: flex;
        justify-content: space-between;
        gap: 18px;
        align-items: flex-start;
        border-bottom: 1px solid rgba(47, 125, 61, .10);
        padding-bottom: 16px;
        margin-bottom: 16px;
    }

    .question-title-area {
        display: flex;
        gap: 13px;
        align-items: flex-start;
    }

    .question-title-area h2 {
        margin: 0 0 6px;
        color: var(--questions-green-900);
        font-size: 28px;
        letter-spacing: -.03em;
    }

    .question-title-area p {
        margin: 0;
        color: var(--questions-muted);
        line-height: 1.6;
    }

    .question-title-area strong {
        color: var(--questions-green-900);
    }

    .question-status-area {
        display: grid;
        justify-items: end;
        gap: 9px;
    }

    .producer-question-badge {
        display: inline-flex;
        align-items: center;
        padding: 8px 11px;
        border-radius: 999px;
        font-size: 13px;
        font-weight: 900;
        white-space: nowrap;
    }

    .badge-success {
        background: #e7f7e8;
        color: #236b2c;
    }

    .badge-warning {
        background: #fff5d6;
        color: #8a6200;
    }

    .badge-danger {
        background: #ffe8e8;
        color: #9b111e;
    }

    .badge-muted {
        background: #edf1ea;
        color: #526052;
    }

    .small-link {
        color: var(--questions-green-700);
        font-weight: 900;
        text-decoration: none;
        white-space: nowrap;
    }

    .small-link:hover {
        text-decoration: underline;
    }

    .question-body-box {
        padding: 16px;
        border-radius: 20px;
        background: #fbfdf8;
        border: 1px solid var(--questions-border);
        margin-bottom: 14px;
    }

    .question-body-box strong,
    .answer-box strong,
    .answer-form label {
        color: var(--questions-green-900);
        font-weight: 900;
    }

    .question-body-box p,
    .answer-box p {
        margin: 8px 0 0;
        color: var(--questions-muted);
        line-height: 1.65;
    }

    .answer-box {
        padding: 16px;
        border-radius: 20px;
        background: #eef8ef;
        border-left: 5px solid var(--questions-green-700);
        margin-bottom: 14px;
    }

    .answer-box span {
        display: inline-flex;
        margin-top: 8px;
        color: #718071;
        font-size: 14px;
        font-weight: 800;
    }

    .answer-form {
        display: grid;
        gap: 12px;
    }

    .form-group {
        display: grid;
        gap: 8px;
    }

    .answer-form textarea {
        width: 100%;
        padding: 13px 14px;
        border: 1px solid rgba(47, 125, 61, .18);
        border-radius: 15px;
        font: inherit;
        color: var(--questions-text);
        outline: none;
        resize: vertical;
        min-height: 120px;
        box-sizing: border-box;
    }

    .answer-form textarea:focus {
        border-color: var(--questions-green-700);
        box-shadow: 0 0 0 4px rgba(47, 125, 61, .10);
    }

    .inline-info-box {
        padding: 14px;
        border-radius: 18px;
        background: var(--questions-green-50);
        border: 1px solid var(--questions-border);
        color: var(--questions-muted);
        font-weight: 900;
    }

    .producer-questions-empty-card {
        max-width: 720px;
        margin: 0 auto;
        padding: 42px;
        text-align: center;
    }

    .soft-empty-state {
        margin-top: 0;
    }

    .empty-icon {
        width: 78px;
        height: 78px;
        margin: 0 auto 14px;
        display: grid;
        place-items: center;
        border-radius: 25px;
        background: var(--questions-green-100);
        font-size: 36px;
    }

    .producer-questions-empty-card h2 {
        margin-top: 18px;
        font-size: 34px;
    }

    .producer-questions-empty-card p {
        max-width: 520px;
        margin: 0 auto 22px;
    }

    .empty-actions {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 10px;
    }

    .producer-questions-no-result {
        margin-top: 18px;
        padding: 28px;
        border-radius: 24px;
        background: #ffffff;
        border: 1px dashed rgba(47, 125, 61, .24);
        text-align: center;
        color: var(--questions-muted);
    }

    .producer-questions-no-result span {
        display: block;
        font-size: 34px;
        margin-bottom: 8px;
    }

    .producer-questions-no-result strong {
        display: block;
        color: var(--questions-green-900);
        font-size: 20px;
        margin-bottom: 5px;
    }

    .producer-questions-no-result p {
        margin: 0;
    }

    .producer-questions-btn {
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

    .producer-questions-btn:hover {
        transform: translateY(-2px);
    }

    .producer-questions-btn-primary {
        background: linear-gradient(135deg, var(--questions-green-700), var(--questions-green-900));
        color: #ffffff;
        box-shadow: 0 16px 32px rgba(47, 125, 61, .24);
    }

    .producer-questions-btn-light {
        background: var(--questions-green-50);
        color: var(--questions-green-800);
        border: 1px solid var(--questions-border);
    }

    .questions-loading {
        pointer-events: none;
        opacity: .72;
    }

    @media (max-width: 1100px) {
        .producer-questions-hero-content {
            grid-template-columns: 1fr;
        }

        .producer-questions-summary-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .producer-questions-filter-card {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 800px) {
        .producer-questions-top,
        .question-card-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .producer-questions-total {
            justify-items: start;
        }

        .question-status-area {
            justify-items: start;
        }
    }

    @media (max-width: 720px) {
        .producer-questions-hero {
            min-height: 430px;
            padding-top: 24px;
        }

        .producer-questions-hero-inner,
        .producer-questions-shell {
            width: min(100% - 22px, 1180px);
        }

        .producer-questions-breadcrumb {
            font-size: 13px;
            margin-bottom: 24px;
        }

        .producer-questions-hero-copy p {
            font-size: 15px;
        }

        .producer-questions-shell {
            margin-top: -52px;
        }

        .producer-questions-top,
        .producer-question-card,
        .producer-questions-hero-card,
        .producer-questions-empty-card,
        .producer-question-stat-card,
        .producer-questions-filter-card {
            padding: 14px;
            border-radius: 23px;
        }

        .producer-questions-summary-grid {
            grid-template-columns: 1fr;
        }

        .question-title-area {
            flex-direction: column;
        }

        .producer-questions-empty-card h2 {
            font-size: 28px;
        }

        .empty-actions .producer-questions-btn,
        .answer-form .producer-questions-btn {
            width: 100%;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const dynamicArea = document.getElementById('producer-questions-dynamic-area');
        const heroStatsWrap = document.getElementById('producer-questions-hero-stats-wrap');
        const messageBox = document.getElementById('producer-questions-message');

        let currentStatus = '<?= e($status) ?>';

        function showQuestionsMessage(type, message) {
            if (!messageBox) {
                return;
            }

            messageBox.hidden = false;
            messageBox.className = 'producer-questions-message ' + type;
            messageBox.textContent = message || '';

            window.setTimeout(function () {
                messageBox.hidden = true;
                messageBox.textContent = '';
                messageBox.className = 'producer-questions-message';
            }, 3000);
        }

        function buildPartialUrl(status) {
            const url = new URL('<?= e(url('producer/questions.php')) ?>', window.location.origin);

            url.searchParams.set('partial', '1');

            if (status) {
                url.searchParams.set('status', status);
            }

            return url.toString();
        }

        async function refreshQuestions(status, successMessage) {
            if (dynamicArea) {
                dynamicArea.classList.add('questions-loading');
            }

            try {
                const response = await fetch(buildPartialUrl(status), {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Sorular yenilenemedi.');
                }

                currentStatus = result.status || '';

                if (dynamicArea && typeof result.html === 'string') {
                    dynamicArea.innerHTML = result.html;
                }

                if (heroStatsWrap && typeof result.heroStatsHtml === 'string') {
                    heroStatsWrap.innerHTML = result.heroStatsHtml;
                }

                if (successMessage) {
                    showQuestionsMessage('success', successMessage);
                }

                applyQuestionSearch();
                updateAddressBar(currentStatus);
            } catch (error) {
                showQuestionsMessage('error', error.message || 'Sorular yenilenirken bir hata oluştu.');
            } finally {
                if (dynamicArea) {
                    dynamicArea.classList.remove('questions-loading');
                }
            }
        }

        function updateAddressBar(status) {
            const url = new URL(window.location.href);

            url.searchParams.delete('partial');

            if (status) {
                url.searchParams.set('status', status);
            } else {
                url.searchParams.delete('status');
            }

            window.history.replaceState({}, '', url.toString());
        }

        function applyQuestionSearch() {
            const searchInput = document.getElementById('producer-question-search');
            const cards = document.querySelectorAll('[data-question-card]');
            const noResult = document.getElementById('producer-questions-no-result');

            const searchValue = searchInput ? searchInput.value.trim().toLowerCase() : '';
            let visibleCount = 0;

            cards.forEach(function (card) {
                const cardText = (card.getAttribute('data-search') || '').toLowerCase();
                const matchesSearch = searchValue === '' || cardText.includes(searchValue);

                if (matchesSearch) {
                    card.classList.remove('is-hidden');
                    visibleCount++;
                } else {
                    card.classList.add('is-hidden');
                }
            });

            if (noResult) {
                noResult.hidden = visibleCount !== 0;
            }
        }

        document.addEventListener('click', function (event) {
            const filterLink = event.target.closest('[data-question-filter]');

            if (!filterLink) {
                return;
            }

            event.preventDefault();

            const status = filterLink.getAttribute('data-question-filter') || '';

            refreshQuestions(status, null);
        });

        document.addEventListener('input', function (event) {
            if (event.target && event.target.id === 'producer-question-search') {
                applyQuestionSearch();
            }
        });

        document.addEventListener('submit', async function (event) {
            const form = event.target.closest('[data-answer-form="true"]');

            if (!form) {
                return;
            }

            event.preventDefault();

            const card = form.closest('[data-question-card]');
            const submitButton = form.querySelector('button[type="submit"]');
            const originalButtonText = submitButton ? submitButton.textContent : '';

            if (card) {
                card.classList.add('is-loading');
            }

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = 'Gönderiliyor...';
            }

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
                    throw new Error(result.message || 'Cevap gönderilemedi.');
                }

                await refreshQuestions(currentStatus, result.message || 'Cevabınız başarıyla gönderildi.');
            } catch (error) {
                showQuestionsMessage('error', error.message || 'Cevap gönderilirken bir hata oluştu.');

                if (card) {
                    card.classList.remove('is-loading');
                }
            } finally {
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.textContent = originalButtonText;
                }
            }
        });

        const focusedQuestion = document.querySelector('.producer-question-card.focused');

        if (focusedQuestion) {
            focusedQuestion.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }
    });
</script>

<?php require APP_PATH . '/Views/layouts/footer.php'; ?>