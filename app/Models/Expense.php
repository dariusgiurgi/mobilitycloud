<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Expense extends Model
{
    use SoftDeletes;

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

    protected static function booted(): void
    {
        static::deleting(function (Expense $expense): void {
            if ($expense->attachmentExists()) {
                Storage::disk($expense->attachment_disk ?: 'local')->delete($expense->attachment_path);
            }
        });
    }
}
