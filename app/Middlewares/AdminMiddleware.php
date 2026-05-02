<?php

declare(strict_types=1);

if (!class_exists('AdminMiddleware')) {
    final class AdminMiddleware
    {
        public static function handle(): void
        {
            requireAdmin();
        }
    }
}