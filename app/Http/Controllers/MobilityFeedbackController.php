<?php

namespace App\Http\Controllers;

use App\Models\FeedbackForm;
use App\Models\MobilityFeedbackCampaign;
use App\Models\MobilityFeedbackResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MobilityFeedbackController extends Controller
{
    public function show(string $token): View
    {
        $campaign = MobilityFeedbackCampaign::query()
            ->with('mobility.project')
            ->where('public_token', $token)
            ->firstOrFail();

        return view('public.mobility-feedback', [
            'campaign' => $campaign,
            'mobility' => $campaign->mobility,
            'project' => $campaign->mobility?->project,
            'questions' => $campaign->questions(),
            'closed' => ! $campaign->hasActiveLink(),
        ]);
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        return DB::transaction(function () use ($request, $token): RedirectResponse {
            $campaign = MobilityFeedbackCampaign::query()
                ->where('public_token', $token)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($campaign->hasActiveLink(), 404);

            $questions = $campaign->questions();
            abort_if($questions === [], 404);

            $validated = $request->validate($this->answerRules($questions), [], [
                'answers' => 'answers',
            ]);

            $answers = [];
            foreach ($questions as $question) {
                $id = (string) ($question['id'] ?? '');
                if ($id === '') {
                    continue;
                }

                $answers[$id] = data_get($validated, 'answers.'.$id);
            }

            MobilityFeedbackResponse::create([
                'mobility_feedback_campaign_id' => $campaign->id,
                'answers' => $answers,
                'submitted_at' => now(),
            ]);

            return redirect()
                ->route('public.mobility-feedback.show', $token)
                ->with('submitted', true);
        }, attempts: 3);
    }

    /** @param array<int, array<string, mixed>> $questions */
    private function answerRules(array $questions): array
    {
        $rules = ['answers' => ['required', 'array']];

        foreach ($questions as $question) {
            $id = (string) ($question['id'] ?? '');
            $type = (string) ($question['type'] ?? '');
            if ($id === '' || ! array_key_exists($type, FeedbackForm::QUESTION_TYPES)) {
                continue;
            }

            $field = 'answers.'.$id;
            $required = ! empty($question['required']) ? 'required' : 'nullable';
            $options = collect($question['options'] ?? [])
                ->filter(fn ($option): bool => is_string($option) && filled($option))
                ->values()
                ->all();

            $rules[$field] = match ($type) {
                'rating' => [$required, 'integer', 'min:1', 'max:5'],
                'single_choice' => [$required, 'string', 'max:255', Rule::in($options)],
                'multiple_choice' => [$required, 'array', 'max:20'],
                'yes_no' => [$required, Rule::in(['yes', 'no'])],
                'short_text' => [$required, 'string', 'max:500'],
                'long_text' => [$required, 'string', 'max:5000'],
            };

            if ($type === 'multiple_choice') {
                $rules[$field.'.*'] = ['string', 'max:255', Rule::in($options)];
            }
        }

        return $rules;
    }
}
