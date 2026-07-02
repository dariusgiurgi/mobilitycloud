<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformSupportNote extends Model
{
    protected $fillable = [
        'user_id', 'author_id', 'category', 'body', 'is_pinned',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
            'bug' => 'Bug',
            'onboarding' => 'Onboarding',
            'security' => 'Security',
            'urgent' => 'Urgent',
        ];
    }
}
