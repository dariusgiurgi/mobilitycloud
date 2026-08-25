<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use App\Models\Project;
use App\Models\ProjectMobility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ParticipantRegistrationController extends Controller
{
    public function show(string $token): View
    {
        [$project, $lockedMobility] = $this->registrationContext($token);

        return view('public.participant-registration', [
            'project' => $project,
            'organisations' => $this->organisations($project),
            'mobilities' => $project?->mobilities()->orderBy('sort_order')->orderBy('id')->get() ?? collect(),
            'lockedMobility' => $lockedMobility,
            'registrationToken' => $token,
            'closed' => ! $this->hasActiveRegistrationLink($project, $lockedMobility)
                || $project->operationalModulesLockedUntilPayment(),
        ]);
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        return DB::transaction(function () use ($request, $token): RedirectResponse {
            [$project, $lockedMobility] = $this->registrationContext($token, lockForUpdate: true);

            abort_unless(
                $this->hasActiveRegistrationLink($project, $lockedMobility)
                && ! $project->operationalModulesLockedUntilPayment(),
                404
            );

            $organisations = $this->organisations($project);
            abort_if($organisations === [], 422, 'The project does not have organisations configured yet.');

            $mobilityIds = $lockedMobility
                ? [$lockedMobility->id]
                : array_map('intval', (array) $request->input('mobility_ids', []));
            $availableMobilityIds = $project->mobilities()->pluck('id')->map(fn ($id): int => (int) $id)->all();

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
                'mobility_ids' => [$lockedMobility ? 'nullable' : (count($availableMobilityIds) > 0 ? 'required' : 'nullable'), 'array'],
                'mobility_ids.*' => ['integer', Rule::in($availableMobilityIds)],
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

            $participant = Participant::create($data);

            if ($mobilityIds !== []) {
                abort_unless(collect($mobilityIds)->every(fn (int $mobilityId): bool => in_array($mobilityId, $availableMobilityIds, true)), 404);

                $participant->mobilities()->sync(collect($mobilityIds)->unique()->mapWithKeys(fn (int $mobilityId): array => [
                    $mobilityId => ['role' => 'participant', 'status' => 'planned'],
                ])->all());
            }

            return redirect()
                ->route('public.participant-registration.show', $token)
                ->with('status', 'Thank you. Your participant form has been submitted.');
        }, attempts: 3);
    }

    /** @return array{0: ?Project, 1: ?ProjectMobility} */
    private function registrationContext(string $token, bool $lockForUpdate = false): array
    {
        $projectQuery = Project::query()->where('participant_registration_token', $token);
        $project = $lockForUpdate ? $projectQuery->lockForUpdate()->first() : $projectQuery->first();

        if ($project) {
            return [$project, null];
        }

        $mobilityQuery = ProjectMobility::query()
            ->with('project')
            ->where('participant_registration_token', $token);
        $mobility = $lockForUpdate ? $mobilityQuery->lockForUpdate()->first() : $mobilityQuery->first();

        return [$mobility?->project, $mobility];
    }

    private function hasActiveRegistrationLink(?Project $project, ?ProjectMobility $lockedMobility): bool
    {
        if (! $project) {
            return false;
        }

        return $lockedMobility
            ? $lockedMobility->hasActiveParticipantRegistrationLink()
            : $project->hasActiveParticipantRegistrationLink();
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
