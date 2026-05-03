<?php

$pageTitle = $pageTitle ?? APP_NAME;
$bodyClass = $bodyClass ?? '';
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - EkineraGo</title>
    <link rel="stylesheet" href="<?= e(asset('css/layout.css')) ?>">
</head>
<body class="<?= e($bodyClass) ?>">

<?php require APP_PATH . '/Views/layouts/navbar.php'; ?>

<div class="flash-stack" id="flashStack">

    <?php if ($message = flash_get('success')): ?>
        <div class="flash flash-floating flash-success" data-auto-flash>
            <span><?= e($message) ?></span>
            <button type="button" class="flash-close" aria-label="Kapat">×</button>
        </div>
    <?php endif; ?>

    <?php if ($message = flash_get('error')): ?>
        <div class="flash flash-floating flash-error" data-auto-flash>
            <span><?= e($message) ?></span>
            <button type="button" class="flash-close" aria-label="Kapat">×</button>
        </div>
    <?php endif; ?>

    <?php if ($message = flash_get('warning')): ?>
        <div class="flash flash-floating flash-warning" data-auto-flash>
            <span><?= e($message) ?></span>
            <button type="button" class="flash-close" aria-label="Kapat">×</button>
        </div>
    <?php endif; ?>

    <?php if ($message = flash_get('info')): ?>
        <div class="flash flash-floating flash-info" data-auto-flash>
            <span><?= e($message) ?></span>
            <button type="button" class="flash-close" aria-label="Kapat">×</button>
        </div>
    <?php endif; ?>

</div>

<style>
    .flash-stack {
        position: fixed;
        top: 92px;
        left: 50%;
        transform: translateX(-50%);
        width: min(92vw, 560px);
        display: flex;
        flex-direction: column;
        gap: 10px;
        z-index: 9997;
        pointer-events: none;
    }

    .flash-floating {
        margin: 0;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding: 14px 16px;
        border-radius: 16px;
        box-shadow: 0 16px 40px rgba(20, 46, 24, 0.16);
        backdrop-filter: blur(14px);
        pointer-events: auto;
        opacity: 0;
        transform: translateY(-12px) scale(0.98);
        transition: opacity 0.28s ease, transform 0.28s ease;
    }

    .flash-floating.is-visible {
        opacity: 1;
        transform: translateY(0) scale(1);
    }

    .flash-floating.is-hiding {
        opacity: 0;
        transform: translateY(-10px) scale(0.98);
    }

    .flash-floating.flash-success {
        background: rgba(231, 247, 232, 0.94);
        color: #236b2c;
        border: 1px solid #bfe5c2;
    }

    .flash-floating.flash-error {
        background: rgba(255, 232, 232, 0.95);
        color: #9b111e;
        border: 1px solid #ffcaca;
    }

    .flash-floating.flash-warning {
        background: rgba(255, 245, 214, 0.96);
        color: #8a6200;
        border: 1px solid #ffe29a;
    }

    .flash-floating.flash-info {
        background: rgba(232, 241, 255, 0.96);
        color: #1f4e8c;
        border: 1px solid #c8dbff;
    }

    .flash-close {
        width: 30px;
        height: 30px;
        flex: 0 0 auto;
        border: none;
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.5);
        color: inherit;
        font-size: 20px;
        line-height: 1;
        cursor: pointer;
        transition: 0.2s ease;
    }

    .flash-close:hover {
        background: rgba(255, 255, 255, 0.8);
        transform: scale(1.04);
    }

    @media (max-width: 640px) {
        .flash-stack {
            top: 84px;
            width: calc(100vw - 24px);
        }

        .flash-floating {
            padding: 12px 14px;
            border-radius: 14px;
            font-size: 14px;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const flashes = document.querySelectorAll('[data-auto-flash]');

        function hideFlash(flash) {
            if (!flash || flash.classList.contains('is-hiding')) {
                return;
            }

            flash.classList.add('is-hiding');
            flash.classList.remove('is-visible');

            setTimeout(function () {
                flash.remove();
            }, 300);
        }

        flashes.forEach(function (flash, index) {
            requestAnimationFrame(function () {
                setTimeout(function () {
                    flash.classList.add('is-visible');
                }, index * 80);
            });

            const closeButton = flash.querySelector('.flash-close');

            if (closeButton) {
                closeButton.addEventListener('click', function () {
                    hideFlash(flash);
                });
            }

            setTimeout(function () {
                hideFlash(flash);
            }, 3500 + (index * 300));
        });
    });
</script>