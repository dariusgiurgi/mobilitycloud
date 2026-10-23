<?php

namespace Tests\Unit;

use App\Support\CsvExport;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CsvExportTest extends TestCase
{
    public static function formulaPrefixes(): array
    {
        return array_map(fn ($prefix) => [$prefix.'1+1'], [
            '=', '+', '-', '@', "\t", "\r", "\n", '＝', '＋', '－', '＠',
            '  =', " \t=", "\u{00A0}=", "\u{FEFF}=", "\0=",
        ]);
    }

    #[DataProvider('formulaPrefixes')]
    public function test_formula_like_fields_remain_one_literal_cell(string $value): void
    {
        $stream = fopen('php://temp', 'w+b');
        CsvExport::writeRow($stream, [$value, 'next cell']);
        rewind($stream);
        $row = fgetcsv($stream, null, ',', '"', '');
        $this->assertSame(["'".$value, 'next cell'], $row);
        $this->assertSame($value, CsvExport::unescapeText($row[0]));
        $this->assertFalse(fgetcsv($stream, null, ',', '"', ''));
        fclose($stream);
    }

    public function test_ordinary_values_and_delimiters_round_trip_without_modification(): void
    {
        $values = ['România, școală', "Text \\\";=1+1\nsecond line", "Already 'quoted'", '', '  ordinary text', 42, null];
        foreach ([',', ';'] as $delimiter) {
            $stream = fopen('php://temp', 'w+b');
            CsvExport::writeRow($stream, $values, $delimiter);
            rewind($stream);
            $this->assertSame(['România, școală', "Text \\\";=1+1\nsecond line", "Already 'quoted'", '', '  ordinary text', '42', ''], fgetcsv($stream, null, $delimiter, '"', ''));
            $this->assertFalse(fgetcsv($stream, null, $delimiter, '"', ''));
            fclose($stream);
        }
    }

    public function test_ordinary_and_repeated_apostrophes_are_preserved(): void
    {
        foreach (["'O'Connor", "''=1+1", "'ordinary text", ''] as $value) {
            $this->assertSame($value, CsvExport::unescapeText($value));
        }
    }
}
