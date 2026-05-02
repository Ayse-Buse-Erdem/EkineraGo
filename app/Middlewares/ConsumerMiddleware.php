<?php

declare(strict_types=1);

if (!class_exists('ConsumerMiddleware')) {
    final class ConsumerMiddleware
    {
        public static function handle(): void
        {
            requireConsumer();
        }
    }
}