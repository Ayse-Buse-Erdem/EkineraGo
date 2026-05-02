<?php

declare(strict_types=1);

if (!class_exists('AuthMiddleware')) {
    final class AuthMiddleware
    {
        public static function handle(): void
        {
            requireLogin();
        }
    }
}