<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use App\Models\Project;
use App\Models\ProjectMobility;
use App\Support\ProjectOrganisations;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ParticipantRegistrationController extends Controller
{
    public function show(string $token): View
    {
        [$project, $lockedMobility] = $this->registrationContext($token);
        $mobilities = $project?->mobilities()->with('project')->orderBy('sort_order')->orderBy('id')->get() ?? collect();
        $organisations = $project
            ? ($lockedMobility
                ? ProjectOrganisations::forMobility($lockedMobility)
                : ProjectOrganisations::forProject($project))
            : [];

        return view('public.participant-registration', [
            'project' => $project,
            'organisations' => collect($organisations)->map(fn (array $organisation): array => [
                ...$organisation,
                'label' => $organisation['name'].($organisation['country'] ? ' · '.$organisation['country'] : ''),
            ])->all(),
            'mobilities' => $mobilities,
            'mobilityEligibility' => $mobilities->mapWithKeys(fn (ProjectMobility $mobility): array => [
                $mobility->id => ProjectOrganisations::namesForMobility($mobility),
            ])->all(),
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

            $organisations = $lockedMobility
                ? ProjectOrganisations::forMobility($lockedMobility)
                : ProjectOrganisations::forProject($project);
            abort_if($organisations === [], 422, 'The project does not have organisations configured yet.');

            $availableMobilityIds = $project->mobilities()->pluck('id')->map(fn ($id): int => (int) $id)->all();

            $data = $request->validate([
                'complete_name' => ['required', 'string', 'max:255'],
                'partner_organisation' => ['required', 'string', 'max:255', Rule::in(array_column($organisations, 'name'))],
                'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
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

            $mobilityIds = $lockedMobility
                ? [$lockedMobility->id]
                : collect($data['mobility_ids'] ?? [])->map(fn ($id): int => (int) $id)->unique()->values()->all();
            $selectedMobilities = $lockedMobility
                ? collect([$lockedMobility])
                : $project->mobilities()->with('project')->whereKey($mobilityIds)->get();
            $ineligibleMobilities = $selectedMobilities
                ->reject(fn (ProjectMobility $mobility): bool => ProjectOrganisations::mobilityAllows($mobility, $data['partner_organisation']))
                ->pluck('name')
                ->values();

            if ($ineligibleMobilities->isNotEmpty()) {
                throw ValidationException::withMessages([
                    $lockedMobility ? 'partner_organisation' : 'mobility_ids' => 'The selected organisation does not participate in: '.$ineligibleMobilities->join(', ').'. Choose only mobilities available for this organisation.',
                ]);
            }

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
}
