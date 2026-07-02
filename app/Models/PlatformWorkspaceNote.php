<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformWorkspaceNote extends Model
{
    protected $fillable = [
        'workspace_id', 'author_id', 'category', 'body', 'is_pinned',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public static function categoryOptions(): array
    {
        return [
            'support' => 'Support',
            'billing' => 'Billing',
            'commercial' => 'Commercial',
            'onboarding' => 'Onboarding',
            'security' => 'Security',
            'urgent' => 'Urgent',
        ];
    }
}
