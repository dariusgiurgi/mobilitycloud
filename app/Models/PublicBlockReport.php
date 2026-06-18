<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublicBlockReport extends Model
{
    protected $fillable = [
        'user_id', 'public_content_block_id', 'reason', 'details', 'status',
    ];

    // Motivele de raportare (cheie => eticheta).
    public const REASONS = [
        'spam'      => 'Spam or irrelevant',
        'inaccurate'=> 'Inaccurate or misleading',
        'copyright' => 'Copyright infringement',
        'offensive' => 'Offensive or inappropriate language',
        'other'     => 'Other',
    ];

    // Statusurile de moderare.
    public const STATUS_PENDING   = 'pending';
    public const STATUS_REVIEWED  = 'reviewed';
    public const STATUS_DISMISSED = 'dismissed';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function block(): BelongsTo
    {
        return $this->belongsTo(PublicContentBlock::class, 'public_content_block_id');
    }

    public function reasonLabel(): string
    {
        return self::REASONS[$this->reason] ?? $this->reason;
    }
}