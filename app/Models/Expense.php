<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Expense extends Model
{
    use SoftDeletes;

    public const CONVENTION_BASE_REQUIRED_FIELDS = [
        'convention_number', 'contract_date', 'provider_name', 'provider_address',
        'provider_id_number', 'gross_amount', 'currency',
    ];

    public const CONVENTION_TYPES = [
        'service_agreement' => 'Service agreement',
        'copyright_assignment' => 'Copyright assignment agreement',
    ];

    public const ACCEPTANCE_STATUSES = [
        'accepted_without_reservations' => 'Accepted without reservations',
        'accepted_with_reservations' => 'Accepted with reservations',
    ];

    public const PAYMENT_STATUSES = [
        'scheduled' => 'Scheduled for payment',
        'paid' => 'Paid',
    ];

    public const PAYMENT_METHODS = [
        'bank_transfer' => 'Bank transfer',
        'cash' => 'Cash',
        'other' => 'Other',
    ];

    protected $fillable = [
        'budget_line_id', 'reference_nr', 'description', 'expense_date',
        'amount', 'currency', 'exchange_rate', 'amount_eur',
        'is_civil_convention', 'convention_data',
        'attachment_path', 'attachment_disk', 'attachment_name', 'notes',
        'position', 'created_by',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'amount_eur' => 'decimal:2',
        'is_civil_convention' => 'boolean',
        'convention_data' => 'array',
    ];

    public function budgetLine(): BelongsTo
    {
        return $this->belongsTo(BudgetLine::class);
    }

    public function attachmentExists(): bool
    {
        return $this->attachment_path
            && Storage::disk($this->attachment_disk ?: 'local')->exists($this->attachment_path);
    }

    public function hasCompleteConventionData(): bool
    {
        $data = $this->convention_data ?? [];

        $required = array_merge(self::CONVENTION_BASE_REQUIRED_FIELDS, match ($data['agreement_type'] ?? 'service_agreement') {
            'copyright_assignment' => [
                'work_description', 'rights_scope', 'use_methods',
                'rights_duration', 'rights_territory',
            ],
            default => ['service_description', 'service_start_date', 'service_end_date'],
        });

        return collect($required)
            ->every(fn (string $field) => filled($data[$field] ?? null));
    }

    public function hasCompleteAcceptanceData(): bool
    {
        $data = $this->convention_data ?? [];

        return collect(['acceptance_date', 'acceptance_deliverables', 'acceptance_status'])
            ->every(fn (string $field) => filled($data[$field] ?? null));
    }

    public function hasCompletePaymentData(): bool
    {
        $data = $this->convention_data ?? [];

        return collect(['payment_date', 'payment_method', 'payment_status'])
            ->every(fn (string $field) => filled($data[$field] ?? null));
    }

    public function conventionSignedCopy(string $kind): array
    {
        $kind = in_array($kind, ['agreement', 'acceptance', 'payment'], true) ? $kind : 'agreement';
        $data = $this->convention_data ?? [];

        return [
            'path' => $data[$kind.'_signed_path'] ?? null,
            'disk' => $data[$kind.'_signed_disk'] ?? 'local',
            'name' => $data[$kind.'_signed_name'] ?? null,
            'size' => (int) ($data[$kind.'_signed_size'] ?? 0),
            'at' => $data[$kind.'_signed_at'] ?? null,
        ];
    }

    public function hasConventionSignedCopy(string $kind): bool
    {
        $copy = $this->conventionSignedCopy($kind);

        return filled($copy['path']) && Storage::disk($copy['disk'])->exists($copy['path']);
    }

    protected static function booted(): void
    {
        static::deleting(function (Expense $expense): void {
            if ($expense->attachmentExists()) {
                Storage::disk($expense->attachment_disk ?: 'local')->delete($expense->attachment_path);
            }

            foreach (['agreement', 'acceptance', 'payment'] as $kind) {
                if ($expense->hasConventionSignedCopy($kind)) {
                    $copy = $expense->conventionSignedCopy($kind);
                    Storage::disk($copy['disk'])->delete($copy['path']);
                }
            }
        });
    }
}
