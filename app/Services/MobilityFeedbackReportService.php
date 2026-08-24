<?php

namespace App\Services;

use App\Models\MobilityFeedbackCampaign;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class MobilityFeedbackReportService
{
    public function output(MobilityFeedbackCampaign $campaign): string
    {
        $campaign->loadMissing(['mobility.project', 'responses' => fn ($query) => $query->latest('submitted_at')]);

        return Pdf::loadView('pdf.mobility-feedback-report', [
            'campaign' => $campaign,
            'mobility' => $campaign->mobility,
            'project' => $campaign->mobility?->project,
            'analytics' => app(MobilityFeedbackAnalytics::class)->forCampaign($campaign),
        ])->setPaper('a4', 'portrait')->output();
    }

    public function filename(MobilityFeedbackCampaign $campaign): string
    {
        return 'anonymous-feedback-'.(Str::slug($campaign->title) ?: 'report').'.pdf';
    }
}
