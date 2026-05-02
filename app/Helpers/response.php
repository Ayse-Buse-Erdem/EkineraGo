<?php

declare(strict_types=1);

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path): never
    {
        header('Location: ' . url($path));
        exit;
    }
}

if (!function_exists('redirect_to')) {
    function redirect_to(string $fullUrl): never
    {
        header('Location: ' . $fullUrl);
        exit;
    }
}

if (!function_exists('back')) {
    function back(): never
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? url('/');
        header('Location: ' . $referer);
        exit;
    }
}

if (!function_exists('json_response')) {
    function json_response(array $data = [], int $statusCode = 200): never
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

if (!function_exists('abort_response')) {
    function abort_response(int $statusCode = 404, string $message = 'Sayfa bulunamadı.'): never
    {
        http_response_code($statusCode);

        echo '<h1>' . e((string) $statusCode) . '</h1>';
        echo '<p>' . e($message) . '</p>';

        exit;
    }
}

if (!function_exists('is_post')) {
    function is_post(): bool
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    }
}

if (!function_exists('is_get')) {
    function is_get(): bool
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET';
    }
}

if (!function_exists('input')) {
    function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }
}

if (!function_exists('post')) {
    function post(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }
}

if (!function_exists('get')) {
    function get(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }
}