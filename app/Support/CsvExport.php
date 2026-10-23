<?php

namespace App\Support;

use RuntimeException;

class CsvExport
{
    /** @param resource $stream */
    public static function writeRow($stream, array $values, string $delimiter = ','): void
    {
        // An empty escape parameter uses doubled quotes, preserving embedded quotes,
        // backslashes and line breaks without creating extra spreadsheet cells.
        if (fputcsv($stream, array_map(self::textValue(...), $values), $delimiter, '"', '') === false) {
            throw new RuntimeException('Could not write the CSV export.');
        }
    }

    private static function textValue(mixed $value): string
    {
        $text = (string) ($value ?? '');

        // Only the exported copy changes. Include leading whitespace and locale
        // variants; stored data and JSON remain available in their original form.
        return preg_match('/^[\s\p{Z}\x00-\x20\x{FEFF}]*[=+\-@＝＋－＠\t\r\n]/u', $text)
            ? "'".$text : $text;
    }
}
