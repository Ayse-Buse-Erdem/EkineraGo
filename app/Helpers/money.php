<?php

declare(strict_types=1);

if (!function_exists('formatMoney')) {
    function formatMoney(float|int|string $amount): string
    {
        return number_format((float) $amount, 2, ',', '.') . ' TL';
    }
}

if (!function_exists('toDecimal')) {
    function toDecimal(float|int|string $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }
}