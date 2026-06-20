<?php

namespace App\Services;

use App\Models\Participant;
use App\Models\Project;
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
            $headers = $this->readHeaders($handle);
            $rows = $this->readRows($handle, $headers);
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
        $row = fgetcsv($handle);
        if (! is_array($row)) {
            throw ValidationException::withMessages(['importFile' => 'The CSV file is empty.']);
        }

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

        return $headers;
    }

    private function readRows($handle, array $headers): array
    {
        $rows = [];
        $line = 1;

        while (($values = fgetcsv($handle)) !== false) {
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
            'birth_date' => $this->nullable($row['birth date'] ?? null),
            'nationality' => $this->nullable($row['nationality'] ?? null),
            'gender' => $this->nullable($row['gender'] ?? null),
            'email' => $this->nullable($row['email'] ?? null),
            'phone' => $this->nullable($row['phone'] ?? null),
            'fewer_opportunities' => $this->boolean($row['fewer opportunities'] ?? null),
            'gdpr_consented_at' => $this->nullable($row['gdpr consent date'] ?? null),
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
            'fewer_opportunities' => ['boolean'],
            'gdpr_consented_at' => ['nullable', 'date_format:Y-m-d'],
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

    private function nullable(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function isBlankRow(array $row): bool
    {
        return collect($row)->every(fn ($value) => trim((string) $value) === '');
    }
}
