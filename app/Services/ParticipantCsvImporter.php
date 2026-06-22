<?php

namespace App\Services;

use App\Models\Participant;
use App\Models\Project;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ParticipantCsvImporter
{
    private const MAX_ROWS = 1000;

    public function import(Project $project, string $path): int
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw ValidationException::withMessages(['importFile' => 'The CSV file could not be read.']);
        }

        try {
            [$headers, $delimiter] = $this->readHeaders($handle);
            $rows = $this->readRows($handle, $headers, $delimiter);
        } finally {
            fclose($handle);
        }

        DB::transaction(function () use ($project, $rows): void {
            foreach ($rows as $row) {
                $project->participants()->create($row);
            }
        });

        return count($rows);
    }

    private function readHeaders($handle): array
    {
        $line = fgets($handle);
        if ($line === false) {
            throw ValidationException::withMessages(['importFile' => 'The CSV file is empty.']);
        }

        $commaRow = str_getcsv($line, ',');
        $semicolonRow = str_getcsv($line, ';');
        $delimiter = count($semicolonRow) > count($commaRow) ? ';' : ',';
        $row = $delimiter === ';' ? $semicolonRow : $commaRow;

        $headers = array_map(function ($value): string {
            $value = preg_replace('/^\xEF\xBB\xBF/', '', (string) $value);

            return mb_strtolower(trim($value, " \t\n\r\0\x0B\""));
        }, $row);

        foreach (['first name', 'last name'] as $required) {
            if (! in_array($required, $headers, true)) {
                throw ValidationException::withMessages([
                    'importFile' => 'Missing required column: '.ucfirst($required).'.',
                ]);
            }
        }

        return [$headers, $delimiter];
    }

    private function readRows($handle, array $headers, string $delimiter): array
    {
        $rows = [];
        $line = 1;

        while (($values = fgetcsv($handle, null, $delimiter)) !== false) {
            $line++;
            if ($this->isBlankRow($values)) {
                continue;
            }
            if (count($rows) >= self::MAX_ROWS) {
                throw ValidationException::withMessages([
                    'importFile' => 'The file may contain at most '.self::MAX_ROWS.' participants.',
                ]);
            }

            $values = array_pad($values, count($headers), null);
            $row = array_combine($headers, array_slice($values, 0, count($headers)));
            $rows[] = $this->validateRow($row, $line);
        }

        if ($rows === []) {
            throw ValidationException::withMessages(['importFile' => 'The CSV file contains no participants.']);
        }

        return $rows;
    }

    private function validateRow(array $row, int $line): array
    {
        $role = $this->roleKey($row['role'] ?? '');
        $data = [
            'first_name' => trim((string) ($row['first name'] ?? '')),
            'last_name' => trim((string) ($row['last name'] ?? '')),
            'partner_organisation' => $this->nullable($row['organisation'] ?? null),
            'role' => $role,
            'country' => $this->nullable($row['country'] ?? null),
            'birth_date' => $this->date($row['birth date'] ?? null),
            'nationality' => $this->nullable($row['nationality'] ?? null),
            'gender' => $this->nullable($row['gender'] ?? null),
            'email' => $this->nullable($row['email'] ?? null),
            'phone' => $this->nullable($row['phone'] ?? null),
            'address' => $this->nullable($row['address'] ?? null),
            'medical_conditions' => $this->nullable($row['medical conditions'] ?? null),
            'allergies' => $this->nullable($row['allergies'] ?? null),
            'dietary_restrictions' => $this->nullable($row['dietary restrictions'] ?? null),
            'special_needs' => $this->nullable($row['special needs'] ?? null),
            'fewer_opportunities' => $this->boolean($row['fewer opportunities'] ?? null),
            'guardian_name' => $this->nullable($row['guardian name'] ?? null),
            'guardian_contact' => $this->nullable($row['guardian contact'] ?? null),
            'gdpr_consented_at' => $this->date($row['gdpr consent date'] ?? null),
        ];

        $validator = Validator::make($data, [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'partner_organisation' => ['nullable', 'string', 'max:255'],
            'role' => ['required', Rule::in(array_keys(Participant::ROLES))],
            'country' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date_format:Y-m-d'],
            'nationality' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', Rule::in(['female', 'male', 'other', 'undisclosed'])],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'medical_conditions' => ['nullable', 'string', 'max:1000'],
            'allergies' => ['nullable', 'string', 'max:1000'],
            'dietary_restrictions' => ['nullable', 'string', 'max:1000'],
            'special_needs' => ['nullable', 'string', 'max:1000'],
            'fewer_opportunities' => ['boolean'],
            'guardian_name' => ['nullable', 'string', 'max:255'],
            'guardian_contact' => ['nullable', 'string', 'max:255'],
            'gdpr_consented_at' => ['nullable', 'date_format:Y-m-d'],
        ], [
            'birth_date.date_format' => 'Birth date must use YYYY-MM-DD, DD.MM.YYYY or DD/MM/YYYY.',
            'gdpr_consented_at.date_format' => 'GDPR consent date must use YYYY-MM-DD, DD.MM.YYYY or DD/MM/YYYY.',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages([
                'importFile' => 'Row '.$line.': '.$validator->errors()->first(),
            ]);
        }

        return $validator->validated();
    }

    private function roleKey(?string $value): string
    {
        $value = mb_strtolower(trim((string) $value));
        if ($value === '') {
            return 'participant';
        }

        foreach (Participant::ROLES as $key => $label) {
            if ($value === mb_strtolower($key) || $value === mb_strtolower($label)) {
                return $key;
            }
        }

        return $value;
    }

    private function boolean(?string $value): bool
    {
        return in_array(mb_strtolower(trim((string) $value)), ['1', 'yes', 'true', 'da'], true);
    }

    private function date(?string $value): ?string
    {
        $value = $this->nullable($value);
        if ($value === null) {
            return null;
        }

        if (preg_match('/^\d{1,5}$/', $value)) {
            $serial = (int) $value;
            if ($serial > 0) {
                return (new DateTimeImmutable('1899-12-30'))
                    ->modify('+'.$serial.' days')
                    ->format('Y-m-d');
            }
        }

        foreach (['Y-m-d', 'd.m.Y', 'd/m/Y', 'd-m-Y', 'm/d/Y'] as $format) {
            $date = DateTimeImmutable::createFromFormat('!'.$format, $value);
            $errors = DateTimeImmutable::getLastErrors();

            if ($date && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
                return $date->format('Y-m-d');
            }
        }

        return $value;
    }

    private function nullable(?string $value): ?string
    {
        $value = trim((string) $value);

        if (preg_match('/^\'[=+\-@]/', $value)) {
            $value = substr($value, 1);
        }

        return $value === '' ? null : $value;
    }

    private function isBlankRow(array $row): bool
    {
        return collect($row)->every(fn ($value) => trim((string) $value) === '');
    }
}
