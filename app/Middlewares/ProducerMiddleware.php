<?php

declare(strict_types=1);

if (!class_exists('ProducerMiddleware')) {
    final class ProducerMiddleware
    {
        public static function handle(): void
        {
            requireProducer();
        }
    }
}