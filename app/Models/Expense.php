<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'budget_line_id', 'reference_nr', 'description', 'expense_date',
        'amount', 'currency', 'exchange_rate', 'amount_eur',
        'is_civil_convention', 'convention_data',
        'attachment_path', 'attachment_name', 'notes',
        'position', 'created_by',
    ];

    protected $casts = [
        'expense_date'        => 'date',
        'amount'              => 'decimal:2',
        'exchange_rate'       => 'decimal:6',
        'amount_eur'          => 'decimal:2',
        'is_civil_convention' => 'boolean',
        'convention_data'     => 'array',
    ];

    public function budgetLine(): BelongsTo
    {
        return $this->belongsTo(BudgetLine::class);
    }
}
