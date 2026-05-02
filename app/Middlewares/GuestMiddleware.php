<?php

declare(strict_types=1);

if (!class_exists('GuestMiddleware')) {
    final class GuestMiddleware
    {
        public static function handle(): void
        {
            requireGuest();
        }
    }
}

