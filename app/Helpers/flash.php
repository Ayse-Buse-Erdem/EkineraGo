<?php

declare(strict_types=1);

if (!function_exists('flash')) {
    function flash(string $key, string $message): void
    {
        $_SESSION['_flash'][$key] = $message;
    }
}

if (!function_exists('flash_get')) {
    function flash_get(string $key): ?string
    {
        if (!isset($_SESSION['_flash'][$key])) {
            return null;
        }

        $message = $_SESSION['_flash'][$key];
        unset($_SESSION['_flash'][$key]);

        return $message;
    }
}

if (!function_exists('flash_has')) {
    function flash_has(string $key): bool
    {
        return isset($_SESSION['_flash'][$key]);
    }
}

if (!function_exists('flash_success')) {
    function flash_success(string $message): void
    {
        flash('success', $message);
    }
}

if (!function_exists('flash_error')) {
    function flash_error(string $message): void
    {
        flash('error', $message);
    }
}

if (!function_exists('flash_warning')) {
    function flash_warning(string $message): void
    {
        flash('warning', $message);
    }
}

if (!function_exists('flash_info')) {
    function flash_info(string $message): void
    {
        flash('info', $message);
    }
}

if (!function_exists('set_old')) {
    function set_old(array $data): void
    {
        $_SESSION['_old'] = $data;
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = ''): mixed
    {
        return $_SESSION['_old'][$key] ?? $default;
    }
}

if (!function_exists('clear_old')) {
    function clear_old(): void
    {
        unset($_SESSION['_old']);
    }
}

if (!function_exists('set_errors')) {
    function set_errors(array $errors): void
    {
        $_SESSION['_errors'] = $errors;
    }
}

if (!function_exists('errors')) {
    function errors(): array
    {
        $errors = $_SESSION['_errors'] ?? [];
        unset($_SESSION['_errors']);

        return $errors;
    }
}

if (!function_exists('first_error')) {
    function first_error(string $field): ?string
    {
        $errors = $_SESSION['_errors'] ?? [];

        return $errors[$field][0] ?? null;
    }
}