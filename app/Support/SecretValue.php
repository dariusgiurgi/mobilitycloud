<?php

namespace App\Support;

class SecretValue
{
    public static function resolve(mixed $value, mixed $filePath): ?string
    {
        if (is_string($value) && trim($value) !== '') {
            return $value;
        }

        if (! is_string($filePath) || trim($filePath) === '' || ! is_readable($filePath)) {
            return null;
        }

        $secret = trim((string) file_get_contents($filePath));

        return $secret !== '' ? $secret : null;
    }
}
