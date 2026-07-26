<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ParticipantRegistrationController extends Controller
{
    public function show(string $token): View
    {
        $project = $this->projectForToken($token);

        return view('public.participant-registration', [
            'project' => $project,
            'organisations' => $this->organisations($project),
            'closed' => ! $project?->hasActiveParticipantRegistrationLink(),
        ]);
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        $project = $this->projectForToken($token);

        abort_unless($project?->hasActiveParticipantRegistrationLink(), 404);

        $organisations = $this->organisations($project);
        abort_if($organisations === [], 422, 'The project does not have organisations configured yet.');

        $data = $request->validate([
            'complete_name' => ['required', 'string', 'max:255'],
            'partner_organisation' => ['required', 'string', 'max:255', Rule::in(array_column($organisations, 'name'))],
            'birth_date' => ['nullable', 'date'],
            'nationality' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', Rule::in(['female', 'male', 'other', 'undisclosed'])],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'medical_conditions' => ['nullable', 'string', 'max:1000'],
            'allergies' => ['nullable', 'string', 'max:1000'],
            'dietary_restrictions' => ['nullable', 'string', 'max:1000'],
            'special_needs' => ['nullable', 'string', 'max:1000'],
            'fewer_opportunities' => ['nullable', 'boolean'],
            'guardian_name' => ['nullable', 'string', 'max:255'],
            'guardian_contact' => ['nullable', 'string', 'max:255'],
        ], [], [
            'complete_name' => 'complete name',
            'partner_organisation' => 'organisation',
        ]);

        $data['complete_name'] = trim((string) $data['complete_name']);
        [$data['first_name'], $data['last_name']] = Participant::splitCompleteName($data['complete_name']);
        $data['project_id'] = $project->id;
        $data['role'] = 'participant';
        $data['fewer_opportunities'] = $request->boolean('fewer_opportunities');

        $organisation = collect($organisations)->firstWhere('name', $data['partner_organisation']);
        $data['country'] = Arr::get($organisation, 'country') ?: null;

        Participant::create($data);

        return redirect()
            ->route('public.participant-registration.show', $token)
            ->with('status', 'Thank you. Your participant form has been submitted.');
    }

    private function projectForToken(string $token): ?Project
    {
        return Project::query()
            ->where('participant_registration_token', $token)
            ->first();
    }

    /**
     * @return array<int, array{name: string, label: string, country: ?string}>
     */
    private function organisations(?Project $project): array
    {
        if (! $project) {
            return [];
        }

        return collect($project->partners)
            ->filter(fn (array $partner): bool => filled($partner['name'] ?? null))
            ->map(fn (array $partner): array => [
                'name' => trim((string) $partner['name']),
                'label' => trim((string) $partner['name']).(! empty($partner['country']) ? ' · '.$partner['country'] : ''),
                'country' => filled($partner['country'] ?? null) ? trim((string) $partner['country']) : null,
            ])
            ->values()
            ->all();
    }
}
