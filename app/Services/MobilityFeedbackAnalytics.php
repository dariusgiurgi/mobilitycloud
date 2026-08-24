<?php

namespace App\Services;

use App\Models\MobilityFeedbackCampaign;
use Illuminate\Support\Collection;

class MobilityFeedbackAnalytics
{
    /**
     * Build a presentation-ready, anonymous report for one campaign.
     *
     * Responses stay grouped under their question. This deliberately does not
     * expose a response id, timestamp, participant, or any other identifier.
     */
    public function forCampaign(MobilityFeedbackCampaign $campaign): array
    {
        $campaign->loadMissing(['responses' => fn ($query) => $query->latest('submitted_at')]);
        $responses = $campaign->responses;

        $questions = collect($campaign->questions())
            ->filter(fn (array $question): bool => filled($question['id'] ?? null))
            ->values();

        $questionReports = $questions->map(function (array $question) use ($responses): array {
            $type = $question['type'] ?? 'short_text';
            $values = $this->answersFor($responses, (string) $question['id'], $type);

            $report = [
                'id' => (string) $question['id'],
                'type' => $type,
                'label' => $question['label'] ?? 'Untitled question',
                'help' => $question['help'] ?? null,
                'answer_count' => $values->count(),
            ];

            if ($type === 'rating') {
                $ratings = $values->filter(fn ($value): bool => is_numeric($value))
                    ->map(fn ($value): int => max(1, min(5, (int) $value)))
                    ->values();
                $count = $ratings->count();

                return array_merge($report, [
                    'answer_count' => $count,
                    'average' => $count ? round((float) $ratings->avg(), 1) : null,
                    'distribution' => collect(range(5, 1))->map(fn (int $score): array => [
                        'score' => $score,
                        'count' => $ratings->filter(fn (int $rating): bool => $rating === $score)->count(),
                        'percent' => $count ? round($ratings->filter(fn (int $rating): bool => $rating === $score)->count() / $count * 100) : 0,
                    ])->all(),
                ]);
            }

            if (in_array($type, ['single_choice', 'multiple_choice', 'yes_no'], true)) {
                $options = $type === 'yes_no'
                    ? ['Yes', 'No']
                    : collect($question['options'] ?? [])->filter()->values()->all();
                $count = $values->count();

                return array_merge($report, [
                    'options' => collect($options)->map(function (string $option) use ($values, $type, $count): array {
                        $matches = $values->filter(function ($value) use ($option, $type): bool {
                            $selected = $type === 'multiple_choice' ? (array) $value : [$value];

                            return collect($selected)->contains(fn ($answer): bool => strcasecmp(trim((string) $answer), $option) === 0);
                        })->count();

                        return [
                            'label' => $option,
                            'count' => $matches,
                            'percent' => $count ? round($matches / $count * 100) : 0,
                        ];
                    })->all(),
                ]);
            }

            return array_merge($report, [
                'answers' => $values
                    ->map(fn ($value): string => trim((string) $value))
                    ->filter()
                    ->values()
                    ->all(),
            ]);
        })->all();

        $ratingReports = collect($questionReports)->where('type', 'rating');
        $ratingAnswers = $ratingReports->sum('answer_count');
        $weightedScore = $ratingReports->sum(fn (array $report): float => ((float) ($report['average'] ?? 0)) * $report['answer_count']);

        return [
            'response_count' => $responses->count(),
            'question_count' => count($questionReports),
            'overall_rating' => $ratingAnswers ? round($weightedScore / $ratingAnswers, 1) : null,
            'questions' => $questionReports,
        ];
    }

    private function answersFor(Collection $responses, string $questionId, string $type): Collection
    {
        return $responses->map(fn ($response) => data_get($response->answers, $questionId))
            ->filter(function ($value) use ($type): bool {
                if ($type === 'multiple_choice') {
                    return is_array($value) && count($value) > 0;
                }

                return $value !== null && trim((string) $value) !== '';
            })
            ->values();
    }
}
