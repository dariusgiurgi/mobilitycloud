<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetLine extends Model
{
    protected $fillable = [
        'project_id', 'title', 'emoji', 'color', 'background_color',
        'allocated_budget', 'sort_order',
    ];

    protected $casts = [
        'allocated_budget' => 'decimal:2',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function getSpentAttribute(): float
    {
        return (float) $this->expenses->sum('amount_eur');
    }

    public function getRemainingAttribute(): float
    {
        return (float) $this->allocated_budget - $this->spent;
    }
}
