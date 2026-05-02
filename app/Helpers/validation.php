<?php

declare(strict_types=1);

if (!function_exists('validate_required')) {
    function validate_required(array $data, array $fields): array
    {
        $errors = [];

        foreach ($fields as $field => $label) {
            $value = $data[$field] ?? null;

            if ($value === null || trim((string) $value) === '') {
                $errors[$field][] = $label . ' alanı zorunludur.';
            }
        }

        return $errors;
    }
}

if (!function_exists('validate_email')) {
    function validate_email(?string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

if (!function_exists('validate_min_length')) {
    function validate_min_length(?string $value, int $min): bool
    {
        return mb_strlen(trim((string) $value), 'UTF-8') >= $min;
    }
}

if (!function_exists('validate_numeric_min')) {
    function validate_numeric_min(mixed $value, float $min): bool
    {
        return is_numeric($value) && (float) $value >= $min;
    }
}

if (!function_exists('merge_errors')) {
    function merge_errors(array ...$errorSets): array
    {
        $merged = [];

        foreach ($errorSets as $errors) {
            foreach ($errors as $field => $messages) {
                foreach ($messages as $message) {
                    $merged[$field][] = $message;
                }
            }
        }

        return $merged;
    }
}