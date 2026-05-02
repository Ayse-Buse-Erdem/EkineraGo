<?php

declare(strict_types=1);

if (!function_exists('generateOrderNo')) {
    function generateOrderNo(): string
    {
        return 'EKG-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
    }
}

if (!function_exists('generateTrackingNo')) {
    function generateTrackingNo(): string
    {
        return 'TRK-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
    }
}