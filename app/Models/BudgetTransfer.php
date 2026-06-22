<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetTransfer extends Model
{
    protected $fillable = [
        'project_id', 'from_budget_line_id', 'to_budget_line_id',
        'amount', 'reason', 'status', 'created_by', 'reversed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'reversed_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function fromLine(): BelongsTo
    {
        return $this->belongsTo(BudgetLine::class, 'from_budget_line_id');
    }

    public function toLine(): BelongsTo
    {
        return $this->belongsTo(BudgetLine::class, 'to_budget_line_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
