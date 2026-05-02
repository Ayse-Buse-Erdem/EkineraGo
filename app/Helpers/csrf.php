<?php

declare(strict_types=1);

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_csrf_token" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('verify_csrf')) {
    function verify_csrf(?string $token = null): bool
    {
        $sessionToken = $_SESSION['_csrf_token'] ?? null;
        $requestToken = $token ?? ($_POST['_csrf_token'] ?? null);

        if (!$sessionToken || !$requestToken) {
            return false;
        }

        return hash_equals($sessionToken, $requestToken);
    }
}

if (!function_exists('require_csrf')) {
    function require_csrf(): void
    {
        if (!verify_csrf()) {
            flash_error('Güvenlik doğrulaması başarısız. Lütfen tekrar deneyin.');
            back();
        }
    }
}