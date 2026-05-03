<?php

require_once __DIR__ . '/../../app/bootstrap.php';

ConsumerMiddleware::handle();

$walletService = new WalletService();
$userId = (int) currentUserId();

if (is_post()) {
    require_csrf();

    $amount = (float) ($_POST['amount'] ?? 0);
    $result = $walletService->deposit($userId, $amount);

    if ($result['success']) {
        flash_success($result['message']);
    } else {
        flash_error($result['message']);
    }

    redirect('consumer/wallet.php');
}

$wallet = $walletService->getWallet($userId);
$balance = (float) ($wallet['balance'] ?? 0);
$transactions = $walletService->getTransactions($userId);

$pageTitle = 'Sanal Bakiye';
$bodyClass = 'page-consumer-wallet';

require APP_PATH . '/Views/layouts/header.php';

if (!function_exists('wallet_transaction_label')) {
    function wallet_transaction_label(string $type): string
    {
        return match ($type) {
            'deposit' => 'Yükleme',
            'purchase' => 'Satın Alma',
            'refund' => 'İade',
            'producer_income' => 'Üretici Geliri',
            'basket_payment' => 'Mahalle Sepeti',
            default => 'İşlem',
        };
    }
}

if (!function_exists('wallet_transaction_badge')) {
    function wallet_transaction_badge(string $type): string
    {
        return match ($type) {
            'deposit', 'refund', 'producer_income' => 'badge-success',
            'purchase', 'basket_payment' => 'badge-warning',
            default => 'badge-muted',
        };
    }
}

if (!function_exists('wallet_money')) {
    function wallet_money(float $amount): string
    {
        if (function_exists('formatMoney')) {
            return formatMoney($amount);
        }

        return number_format($amount, 2, ',', '.') . ' TL';
    }
}

if (!function_exists('wallet_date')) {
    function wallet_date(?string $date): string
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

$totalDeposit = 0;
$totalSpent = 0;
$totalRefund = 0;

foreach ($transactions as $transaction) {
    $type = $transaction['transaction_type'] ?? '';
    $amount = (float) ($transaction['amount'] ?? 0);

    if ($type === 'deposit') {
        $totalDeposit += $amount;
    }

    if (in_array($type, ['purchase', 'basket_payment'], true)) {
        $totalSpent += abs($amount);
    }

    if ($type === 'refund') {
        $totalRefund += $amount;
    }
}
?>

<main class="consumer-wallet-page">
    <section class="wallet-hero">
        <div class="wallet-hero-bg wallet-blob-one"></div>
        <div class="wallet-hero-bg wallet-blob-two"></div>

        <div class="wallet-hero-inner">
            <nav class="wallet-breadcrumb" aria-label="Sayfa yolu">
                <a href="<?= e(url('index.php')) ?>">Ana Sayfa</a>
                <span>/</span>
                <a href="<?= e(url('consumer/dashboard.php')) ?>">Tüketici Paneli</a>
                <span>/</span>
                <strong>Sanal Bakiye</strong>
            </nav>

            <div class="wallet-hero-content">
                <div class="wallet-hero-copy">
                    <span class="wallet-eyebrow">
                        Cüzdan ve Bakiye
                    </span>

                    <h1>Sanal Bakiye</h1>

                    <p>
                        Sipariş ödemelerinde kullanacağın sanal bakiyeni buradan görüntüleyebilir,
                        demo bakiye yükleyebilir ve tüm bakiye hareketlerini takip edebilirsin.
                    </p>

                    <div class="wallet-hero-stats">
                        <span>💳 Mevcut: <?= e(wallet_money($balance)) ?></span>
                        <span>⬆️ Yüklenen: <?= e(wallet_money($totalDeposit)) ?></span>
                        <span>🧾 İşlem: <?= e((string) count($transactions)) ?></span>
                    </div>
                </div>

                <div class="wallet-balance-card">
                    <div class="balance-icon">💳</div>

                    <span>Mevcut Bakiye</span>

                    <strong><?= e(wallet_money($balance)) ?></strong>

                    <p>
                        Sipariş oluştururken ödeme tutarı bu bakiyeden düşülür.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="wallet-shell">
        <section class="wallet-summary-grid">
            <article class="wallet-stat-card glass-card">
                <span class="stat-icon">💰</span>

                <div>
                    <strong><?= e(wallet_money($balance)) ?></strong>
                    <p>Mevcut Bakiye</p>
                </div>
            </article>

            <article class="wallet-stat-card glass-card">
                <span class="stat-icon">⬆️</span>

                <div>
                    <strong><?= e(wallet_money($totalDeposit)) ?></strong>
                    <p>Toplam Yükleme</p>
                </div>
            </article>

            <article class="wallet-stat-card glass-card">
                <span class="stat-icon">🛒</span>

                <div>
                    <strong><?= e(wallet_money($totalSpent)) ?></strong>
                    <p>Toplam Harcama</p>
                </div>
            </article>

            <article class="wallet-stat-card glass-card">
                <span class="stat-icon">↩️</span>

                <div>
                    <strong><?= e(wallet_money($totalRefund)) ?></strong>
                    <p>Toplam İade</p>
                </div>
            </article>
        </section>

        <section class="wallet-layout">
            <section class="deposit-card glass-card">
                <div class="section-heading">
                    <span class="section-icon">➕</span>

                    <div>
                        <h2>Bakiye Yükle</h2>
                        <p>Demo kullanım için hesabına sanal bakiye ekleyebilirsin.</p>
                    </div>
                </div>

                <form method="POST" action="<?= e(url('consumer/wallet.php')) ?>" class="wallet-form">
                    <?= csrf_field() ?>

                    <div class="form-group">
                        <label for="amount">Yüklenecek Tutar</label>

                        <div class="amount-input-wrap">
                            <input
                                type="number"
                                id="amount"
                                name="amount"
                                step="0.01"
                                min="1"
                                placeholder="Örn: 500"
                                required
                            >

                            <span>TL</span>
                        </div>
                    </div>

                    <div class="quick-amounts">
                        <button type="button" data-amount="100">100 TL</button>
                        <button type="button" data-amount="250">250 TL</button>
                        <button type="button" data-amount="500">500 TL</button>
                        <button type="button" data-amount="1000">1000 TL</button>
                    </div>

                    <button class="wallet-btn wallet-btn-primary full" type="submit">
                        Bakiye Yükle
                    </button>
                </form>

                <div class="wallet-note">
                    <span>🌿</span>

                    <p>
                        Bu alan demo ödeme akışı içindir. Gerçek banka veya kart işlemi yapılmaz.
                    </p>
                </div>
            </section>

            <aside class="wallet-info-card glass-card">
                <div class="section-heading">
                    <span class="section-icon">🧾</span>

                    <div>
                        <h2>Bakiye Nasıl Kullanılır?</h2>
                        <p>Sepet ve sipariş sürecinde bakiye hareketleri otomatik oluşur.</p>
                    </div>
                </div>

                <div class="wallet-step-list">
                    <div class="wallet-step">
                        <span>1</span>

                        <div>
                            <strong>Bakiye yükle</strong>
                            <p>Demo cüzdanına istediğin tutarda sanal bakiye ekle.</p>
                        </div>
                    </div>

                    <div class="wallet-step">
                        <span>2</span>

                        <div>
                            <strong>Sipariş oluştur</strong>
                            <p>Checkout sırasında ödeme bu bakiyeden düşülür.</p>
                        </div>
                    </div>

                    <div class="wallet-step">
                        <span>3</span>

                        <div>
                            <strong>Hareketleri takip et</strong>
                            <p>Yükleme, satın alma ve iade kayıtları aşağıda listelenir.</p>
                        </div>
                    </div>
                </div>

                <div class="wallet-side-actions">
                    <a class="wallet-btn wallet-btn-light full" href="<?= e(url('cart.php')) ?>">
                        Sepetime Git
                    </a>

                    <a class="wallet-btn wallet-btn-light full" href="<?= e(url('consumer/orders.php')) ?>">
                        Siparişlerim
                    </a>
                </div>
            </aside>
        </section>

        <section class="transactions-card glass-card">
            <div class="section-heading section-heading-spaced">
                <div class="heading-left">
                    <span class="section-icon">📊</span>

                    <div>
                        <h2>Bakiye Hareketleri</h2>
                        <p>Hesabındaki tüm sanal bakiye giriş ve çıkışlarını buradan inceleyebilirsin.</p>
                    </div>
                </div>

                <span class="count-pill"><?= e((string) count($transactions)) ?> işlem</span>
            </div>

            <?php if (empty($transactions)): ?>
                <div class="wallet-empty-state">
                    <span>💳</span>
                    <h3>Henüz bakiye hareketi yok</h3>
                    <p>İlk bakiye yükleme veya satın alma işleminden sonra hareketler burada görünecek.</p>
                </div>
            <?php else: ?>
                <div class="transactions-table-wrap">
                    <table class="transactions-table">
                        <thead>
                            <tr>
                                <th>Tarih</th>
                                <th>İşlem</th>
                                <th>Açıklama</th>
                                <th>Tutar</th>
                                <th>İşlem Sonrası Bakiye</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                                <?php
                                    $type = $transaction['transaction_type'] ?? '';
                                    $amount = (float) ($transaction['amount'] ?? 0);
                                ?>

                                <tr>
                                    <td data-label="Tarih">
                                        <?= e(wallet_date($transaction['created_at'] ?? null)) ?>
                                    </td>

                                    <td data-label="İşlem">
                                        <span class="wallet-badge <?= e(wallet_transaction_badge($type)) ?>">
                                            <?= e(wallet_transaction_label($type)) ?>
                                        </span>
                                    </td>

                                    <td data-label="Açıklama">
                                        <?= e($transaction['description'] ?? '-') ?>
                                    </td>

                                    <td
                                        data-label="Tutar"
                                        class="<?= $amount < 0 ? 'amount-negative' : 'amount-positive' ?>"
                                    >
                                        <?= e(($amount >= 0 ? '+' : '') . wallet_money($amount)) ?>
                                    </td>

                                    <td data-label="İşlem Sonrası Bakiye">
                                        <strong><?= e(wallet_money((float) ($transaction['balance_after'] ?? 0))) ?></strong>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </section>
</main>

<style>
    :root {
        --wallet-green-950: #102f1a;
        --wallet-green-900: #163d22;
        --wallet-green-800: #245c2f;
        --wallet-green-700: #2f7d3d;
        --wallet-green-600: #3f9650;
        --wallet-green-100: #eaf6e8;
        --wallet-green-50: #f6fbf4;
        --wallet-cream: #fffaf1;
        --wallet-yellow: #f2bf4d;
        --wallet-red: #9b111e;
        --wallet-text: #1e2b21;
        --wallet-muted: #687669;
        --wallet-border: rgba(47, 125, 61, .14);
        --wallet-shadow: 0 24px 70px rgba(31, 79, 43, .12);
        --wallet-radius-lg: 28px;
    }

    body.page-consumer-wallet {
        background:
            radial-gradient(circle at 14% 12%, rgba(196, 231, 177, .48), transparent 28%),
            radial-gradient(circle at 88% 16%, rgba(242, 191, 77, .16), transparent 24%),
            linear-gradient(180deg, #f8fbf2 0%, #f3f8ed 48%, #ffffff 100%);
    }

    .consumer-wallet-page {
        overflow: hidden;
    }

    .wallet-hero {
        position: relative;
        min-height: 390px;
        padding: 34px 18px 94px;
        background:
            radial-gradient(circle at 82% 18%, rgba(242, 191, 77, .30), transparent 26%),
            radial-gradient(circle at 12% 78%, rgba(255, 255, 255, .16), transparent 24%),
            linear-gradient(135deg, rgba(36, 92, 47, .97), rgba(47, 125, 61, .87));
        color: #ffffff;
    }

    .wallet-hero::after {
        content: '';
        position: absolute;
        inset: auto 0 -1px;
        height: 90px;
        background: linear-gradient(180deg, rgba(246, 251, 244, 0), #f6fbf4 82%);
        pointer-events: none;
    }

    .wallet-hero-inner,
    .wallet-shell {
        width: min(1180px, calc(100% - 32px));
        margin: 0 auto;
    }

    .wallet-hero-inner {
        position: relative;
        z-index: 2;
    }

    .wallet-hero-bg {
        position: absolute;
        border-radius: 999px;
        filter: blur(2px);
        opacity: .45;
        pointer-events: none;
    }

    .wallet-blob-one {
        width: 230px;
        height: 230px;
        right: 10%;
        top: 42px;
        background: rgba(242, 191, 77, .28);
    }

    .wallet-blob-two {
        width: 150px;
        height: 150px;
        left: 8%;
        bottom: 36px;
        background: rgba(255, 255, 255, .20);
    }

    .wallet-breadcrumb {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 9px;
        font-size: 14px;
        margin-bottom: 32px;
        color: rgba(255, 255, 255, .76);
    }

    .wallet-breadcrumb a {
        color: #ffffff;
        text-decoration: none;
        font-weight: 800;
    }

    .wallet-breadcrumb strong {
        color: #ffffff;
        font-weight: 900;
    }

    .wallet-hero-content {
        display: grid;
        grid-template-columns: minmax(0, 1.25fr) minmax(320px, .75fr);
        gap: 24px;
        align-items: center;
    }

    .wallet-eyebrow {
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

    .wallet-hero-copy h1 {
        margin: 17px 0 12px;
        font-size: clamp(36px, 5vw, 62px);
        line-height: 1.03;
        letter-spacing: -.045em;
    }

    .wallet-hero-copy p {
        max-width: 700px;
        margin: 0;
        color: rgba(255, 255, 255, .86);
        font-size: 17px;
        line-height: 1.7;
    }

    .wallet-hero-stats {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 18px;
    }

    .wallet-hero-stats span {
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

    .wallet-balance-card {
        padding: 24px;
        border-radius: 30px;
        background: rgba(255, 255, 255, .14);
        border: 1px solid rgba(255, 255, 255, .28);
        box-shadow: 0 22px 58px rgba(16, 47, 26, .22);
        backdrop-filter: blur(18px);
    }

    .balance-icon {
        width: 62px;
        height: 62px;
        display: grid;
        place-items: center;
        border-radius: 21px;
        background: rgba(255, 255, 255, .18);
        font-size: 30px;
        margin-bottom: 16px;
    }

    .wallet-balance-card span {
        display: block;
        color: rgba(255, 255, 255, .78);
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .04em;
        font-size: 13px;
    }

    .wallet-balance-card strong {
        display: block;
        margin-top: 8px;
        font-size: clamp(32px, 4vw, 46px);
        line-height: 1;
        letter-spacing: -.04em;
    }

    .wallet-balance-card p {
        margin: 14px 0 0;
        color: rgba(255, 255, 255, .82);
        line-height: 1.6;
    }

    .wallet-shell {
        position: relative;
        z-index: 3;
        margin-top: -64px;
        padding-bottom: 54px;
    }

    .glass-card {
        background: rgba(255, 255, 255, .92);
        border: 1px solid rgba(255, 255, 255, .72);
        border-radius: var(--wallet-radius-lg);
        box-shadow: var(--wallet-shadow);
        backdrop-filter: blur(16px);
    }

    .wallet-summary-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
        margin-bottom: 22px;
    }

    .wallet-stat-card {
        display: flex;
        align-items: center;
        gap: 13px;
        padding: 18px;
    }

    .stat-icon,
    .section-icon {
        width: 48px;
        height: 48px;
        display: grid;
        place-items: center;
        border-radius: 17px;
        background: var(--wallet-green-100);
        flex: 0 0 auto;
        font-size: 22px;
    }

    .wallet-stat-card strong {
        display: block;
        color: var(--wallet-green-900);
        font-size: 20px;
        letter-spacing: -.02em;
    }

    .wallet-stat-card p {
        margin: 4px 0 0;
        color: var(--wallet-muted);
        font-size: 13px;
        font-weight: 800;
    }

    .wallet-layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(320px, .85fr);
        gap: 22px;
        align-items: start;
        margin-bottom: 22px;
    }

    .deposit-card,
    .wallet-info-card,
    .transactions-card {
        padding: 20px;
    }

    .section-heading,
    .section-heading-spaced,
    .heading-left {
        display: flex;
        align-items: flex-start;
        gap: 13px;
    }

    .section-heading,
    .section-heading-spaced {
        margin-bottom: 18px;
        padding-bottom: 16px;
        border-bottom: 1px solid rgba(47, 125, 61, .10);
    }

    .section-heading-spaced {
        align-items: center;
        justify-content: space-between;
    }

    .heading-left {
        align-items: flex-start;
    }

    .section-heading h2,
    .section-heading-spaced h2 {
        margin: 0 0 5px;
        color: var(--wallet-green-900);
        font-size: 27px;
        letter-spacing: -.03em;
    }

    .section-heading p,
    .section-heading-spaced p {
        margin: 0;
        color: var(--wallet-muted);
        line-height: 1.6;
    }

    .wallet-form {
        display: grid;
        gap: 14px;
    }

    .form-group {
        display: grid;
        gap: 8px;
    }

    .form-group label {
        color: var(--wallet-green-900);
        font-weight: 900;
    }

    .amount-input-wrap {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 54px;
        align-items: center;
        padding: 8px;
        border-radius: 18px;
        background: var(--wallet-green-50);
        border: 1px solid var(--wallet-border);
    }

    .amount-input-wrap input {
        width: 100%;
        padding: 14px;
        border: 0;
        outline: 0;
        border-radius: 13px;
        background: #ffffff;
        color: var(--wallet-text);
        font-size: 18px;
        font-weight: 900;
        box-sizing: border-box;
    }

    .amount-input-wrap span {
        text-align: center;
        color: var(--wallet-green-800);
        font-weight: 900;
    }

    .quick-amounts {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 9px;
    }

    .quick-amounts button {
        min-height: 42px;
        border: 1px solid var(--wallet-border);
        border-radius: 14px;
        background: #ffffff;
        color: var(--wallet-green-800);
        font-family: inherit;
        font-weight: 900;
        cursor: pointer;
        transition: transform .18s ease, background .18s ease;
    }

    .quick-amounts button:hover {
        transform: translateY(-2px);
        background: var(--wallet-green-100);
    }

    .wallet-note {
        display: grid;
        grid-template-columns: 38px 1fr;
        gap: 10px;
        align-items: flex-start;
        margin-top: 16px;
        padding: 13px;
        border-radius: 18px;
        background: #fff8df;
        border: 1px solid rgba(242, 191, 77, .35);
    }

    .wallet-note span {
        width: 38px;
        height: 38px;
        display: grid;
        place-items: center;
        border-radius: 14px;
        background: #ffffff;
    }

    .wallet-note p {
        margin: 0;
        color: #7a5400;
        font-weight: 800;
        line-height: 1.55;
        font-size: 14px;
    }

    .wallet-step-list {
        display: grid;
        gap: 12px;
    }

    .wallet-step {
        display: grid;
        grid-template-columns: 42px minmax(0, 1fr);
        gap: 12px;
        padding: 14px;
        border-radius: 20px;
        background: #fbfdf8;
        border: 1px solid var(--wallet-border);
    }

    .wallet-step > span {
        width: 42px;
        height: 42px;
        display: grid;
        place-items: center;
        border-radius: 15px;
        background: linear-gradient(135deg, var(--wallet-green-700), var(--wallet-green-900));
        color: #ffffff;
        font-weight: 900;
    }

    .wallet-step strong {
        color: var(--wallet-green-900);
    }

    .wallet-step p {
        margin: 5px 0 0;
        color: var(--wallet-muted);
        line-height: 1.55;
    }

    .wallet-side-actions {
        display: grid;
        gap: 10px;
        margin-top: 16px;
    }

    .wallet-btn {
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

    .wallet-btn:hover {
        transform: translateY(-2px);
    }

    .wallet-btn.full {
        width: 100%;
    }

    .wallet-btn-primary {
        background: linear-gradient(135deg, var(--wallet-green-700), var(--wallet-green-900));
        color: #ffffff;
        box-shadow: 0 16px 32px rgba(47, 125, 61, .24);
    }

    .wallet-btn-light {
        background: var(--wallet-green-50);
        color: var(--wallet-green-800);
        border: 1px solid var(--wallet-border);
    }

    .count-pill {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 9px 12px;
        border-radius: 999px;
        background: var(--wallet-cream);
        border: 1px solid rgba(242, 191, 77, .32);
        color: var(--wallet-green-900);
        white-space: nowrap;
        font-size: 13px;
        font-weight: 900;
    }

    .transactions-table-wrap {
        overflow-x: auto;
        border-radius: 22px;
        border: 1px solid var(--wallet-border);
        background: #ffffff;
    }

    .transactions-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 760px;
    }

    .transactions-table th,
    .transactions-table td {
        text-align: left;
        padding: 15px;
        border-bottom: 1px solid rgba(47, 125, 61, .10);
        vertical-align: middle;
    }

    .transactions-table th {
        color: var(--wallet-green-900);
        background: var(--wallet-green-50);
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .transactions-table tr:last-child td {
        border-bottom: none;
    }

    .wallet-badge {
        display: inline-flex;
        align-items: center;
        padding: 7px 10px;
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

    .badge-muted {
        background: #edf1ea;
        color: #526052;
    }

    .amount-positive {
        color: #236b2c;
        font-weight: 900;
    }

    .amount-negative {
        color: var(--wallet-red);
        font-weight: 900;
    }

    .wallet-empty-state {
        display: grid;
        place-items: center;
        gap: 8px;
        padding: 34px 18px;
        border-radius: 22px;
        background: #fbfdf8;
        border: 1px dashed rgba(47, 125, 61, .24);
        text-align: center;
    }

    .wallet-empty-state span {
        font-size: 36px;
    }

    .wallet-empty-state h3 {
        margin: 0;
        color: var(--wallet-green-900);
        font-size: 22px;
    }

    .wallet-empty-state p {
        margin: 0;
        color: var(--wallet-muted);
        font-weight: 800;
        line-height: 1.6;
    }

    @media (max-width: 1100px) {
        .wallet-hero-content,
        .wallet-layout {
            grid-template-columns: 1fr;
        }

        .wallet-summary-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 720px) {
        .wallet-hero {
            min-height: 430px;
            padding-top: 24px;
        }

        .wallet-hero-inner,
        .wallet-shell {
            width: min(100% - 22px, 1180px);
        }

        .wallet-breadcrumb {
            font-size: 13px;
            margin-bottom: 24px;
        }

        .wallet-hero-copy p {
            font-size: 15px;
        }

        .wallet-shell {
            margin-top: -52px;
        }

        .wallet-summary-grid {
            grid-template-columns: 1fr;
        }

        .wallet-stat-card,
        .deposit-card,
        .wallet-info-card,
        .transactions-card,
        .wallet-balance-card {
            padding: 14px;
            border-radius: 23px;
        }

        .quick-amounts {
            grid-template-columns: repeat(2, 1fr);
        }

        .section-heading-spaced {
            align-items: flex-start;
            flex-direction: column;
        }

        .section-heading h2,
        .section-heading-spaced h2 {
            font-size: 24px;
        }

        .transactions-table,
        .transactions-table thead,
        .transactions-table tbody,
        .transactions-table th,
        .transactions-table td,
        .transactions-table tr {
            display: block;
            min-width: 0;
        }

        .transactions-table thead {
            display: none;
        }

        .transactions-table-wrap {
            border: 0;
            background: transparent;
            overflow: visible;
        }

        .transactions-table tr {
            margin-bottom: 12px;
            padding: 14px;
            border-radius: 20px;
            background: #fbfdf8;
            border: 1px solid var(--wallet-border);
        }

        .transactions-table td {
            display: flex;
            justify-content: space-between;
            gap: 14px;
            padding: 10px 0;
            border-bottom: 1px solid rgba(47, 125, 61, .10);
            text-align: right;
        }

        .transactions-table td:last-child {
            border-bottom: none;
        }

        .transactions-table td::before {
            content: attr(data-label);
            color: var(--wallet-muted);
            font-weight: 900;
            text-align: left;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const amountInput = document.getElementById('amount');
        const quickAmountButtons = document.querySelectorAll('[data-amount]');

        quickAmountButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                if (!amountInput) {
                    return;
                }

                amountInput.value = button.getAttribute('data-amount') || '';
                amountInput.focus();
            });
        });
    });
</script>

<?php require APP_PATH . '/Views/layouts/footer.php'; ?>