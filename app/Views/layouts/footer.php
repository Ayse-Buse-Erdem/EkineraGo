<footer class="app-footer">
    <div class="app-footer-inner">

        <div class="footer-brand">
            <div class="footer-logo">
                <span></span>
            </div>

            <div>
                <strong>EkineraGo</strong>
                <p>Taze ürün, doğrudan kaynak.</p>
            </div>
        </div>

        <div class="footer-links">
            <a href="<?= e(url('index.php')) ?>">Ana Sayfa</a>
            <a href="<?= e(url('products.php')) ?>">Ürünler</a>
            <a href="<?= e(url('producers.php')) ?>">Üreticiler</a>
        </div>

        <div class="footer-copy">
            © <?= date('Y') ?> EkineraGo
        </div>

    </div>
</footer>

<style>
    .app-footer {
        margin-top: 80px;
        background:
            radial-gradient(circle at top left, rgba(176, 221, 166, 0.28), transparent 32%),
            #ffffff;
        border-top: 1px solid #e4efdf;
    }

    .app-footer-inner {
        max-width: 1180px;
        margin: 0 auto;
        padding: 28px 34px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 22px;
        flex-wrap: wrap;
        color: #526052;
    }

    .footer-brand {
        display: inline-flex;
        align-items: center;
        gap: 12px;
    }

    .footer-logo {
        width: 42px;
        height: 42px;
        border-radius: 16px;
        background:
            radial-gradient(circle at 30% 25%, #ffffff 0 14%, transparent 15%),
            linear-gradient(135deg, #e7f8df 0%, #a9dba5 48%, #70b976 100%);
        box-shadow: 0 10px 22px rgba(47, 125, 61, 0.15);
        position: relative;
    }

    .footer-logo span {
        position: absolute;
        left: 10px;
        top: 15px;
        width: 22px;
        height: 12px;
        background: #2f7d3d;
        border-radius: 20px 20px 4px 20px;
        transform: rotate(-24deg);
    }

    .footer-brand strong {
        display: block;
        color: #245c2f;
        font-size: 20px;
        font-weight: 900;
        letter-spacing: -0.03em;
    }

    .footer-brand p {
        margin: 3px 0 0;
        color: #6a786a;
        font-size: 14px;
    }

    .footer-links {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .footer-links a {
        text-decoration: none;
        color: #526052;
        font-weight: 800;
        font-size: 14px;
        padding: 9px 12px;
        border-radius: 999px;
        transition: 0.2s ease;
    }

    .footer-links a:hover {
        background: #e8f3e9;
        color: #2f7d3d;
    }

    .footer-copy {
        font-size: 14px;
        color: #7b887b;
        font-weight: 700;
    }

    @media (max-width: 768px) {
        .app-footer-inner {
            flex-direction: column;
            text-align: center;
            padding: 28px 20px;
        }

        .footer-brand {
            flex-direction: column;
        }

        .footer-links {
            justify-content: center;
        }
    }
</style>

</body>
</html>