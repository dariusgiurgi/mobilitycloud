<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectApplicationSection extends Model
{
    protected $fillable = [
        'project_id', 'question_key', 'title', 'content', 'review_status',
        'internal_notes', 'char_limit', 'category', 'sort_order',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function getCharCountAttribute(): int
    {
        return mb_strlen(strip_tags($this->content ?? ''));
    }

    public function getWordCountAttribute(): int
    {
        $text = trim(strip_tags($this->content ?? ''));

        return $text === '' ? 0 : count(preg_split('/\s+/', $text));
    }
}
