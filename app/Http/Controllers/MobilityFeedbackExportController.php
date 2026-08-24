<?php

namespace App\Http\Controllers;

use App\Models\MobilityFeedbackCampaign;
use App\Services\MobilityFeedbackReportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MobilityFeedbackExportController extends Controller
{
    public function pdf(Request $request, MobilityFeedbackCampaign $campaign, MobilityFeedbackReportService $reports)
    {
        $campaign->loadMissing('mobility.project');
        abort_unless($campaign->canBeAccessedBy($request->user()), 403);

        return response($reports->output($campaign), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$reports->filename($campaign).'"',
        ]);
    }

    public function download(Request $request, MobilityFeedbackCampaign $campaign): StreamedResponse
    {
        $campaign->loadMissing('mobility.project', 'responses');
        abort_unless($campaign->canBeAccessedBy($request->user()), 403);

        $questions = $campaign->questions();
        $filename = 'anonymous-feedback-'.str($campaign->title)->slug().'.csv';

        return response()->streamDownload(function () use ($campaign, $questions): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, array_merge(['Submitted at'], array_map(
                fn (array $question): string => (string) ($question['label'] ?? 'Question'),
                $questions,
            )));

            foreach ($campaign->responses->sortBy('submitted_at') as $response) {
                $row = [$response->submitted_at?->format('Y-m-d H:i')];
                foreach ($questions as $question) {
                    $answer = data_get($response->answers, (string) ($question['id'] ?? ''));
                    $row[] = is_array($answer) ? implode(' | ', $answer) : $answer;
                }
                fputcsv($output, $row);
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
