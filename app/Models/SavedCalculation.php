<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedCalculation extends Model
{
    protected $fillable = [
        'workspace_id', 'created_by', 'name', 'type', 'inputs', 'results',
    ];

    protected $casts = [
        'inputs' => 'array',
        'results' => 'array',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
