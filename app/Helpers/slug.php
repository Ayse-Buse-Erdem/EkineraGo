<?php

declare(strict_types=1);

if (!function_exists('slugify')) {
    function slugify(string $text): string
    {
        $text = trim($text);

        $search = ['Ç', 'Ğ', 'İ', 'I', 'Ö', 'Ş', 'Ü', 'ç', 'ğ', 'ı', 'i', 'ö', 'ş', 'ü'];
        $replace = ['c', 'g', 'i', 'i', 'o', 's', 'u', 'c', 'g', 'i', 'i', 'o', 's', 'u'];

        $text = str_replace($search, $replace, $text);
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[^a-z0-9]+/u', '-', $text);
        $text = trim($text, '-');

        return $text ?: 'ekinerago';
    }
}