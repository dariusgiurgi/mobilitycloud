<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublicBlockLike extends Model
{
    protected $fillable = ['user_id', 'public_content_block_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function block(): BelongsTo
    {
        return $this->belongsTo(PublicContentBlock::class, 'public_content_block_id');
    }
}
