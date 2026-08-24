<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MobilityFeedbackResponse extends Model
{
    protected $fillable = [
        'mobility_feedback_campaign_id', 'answers', 'submitted_at',
    ];

    protected $casts = [
        'answers' => 'array',
        'submitted_at' => 'datetime',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(MobilityFeedbackCampaign::class, 'mobility_feedback_campaign_id');
    }
}
