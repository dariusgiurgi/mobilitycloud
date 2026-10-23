<?php

namespace App\Services;

use App\Models\BudgetTransfer;
use App\Models\MobilityFeedbackCampaign;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

class ProjectArchiveSnapshot
{
    /** Capture database records before doing any storage or PDF work. */
    public function capture(Project $project, array $included): array
    {
        return DB::transaction(function () use ($project, $included): array {
            $project = $project->fresh();
            $relations = ['ownerAccount', 'members:id,name,email'];
            if ($included['application']) {
                $relations['applicationSections'] = fn ($q) => $q->orderBy('sort_order')->orderBy('id');
            }
            if ($included['budget'] || $included['agreements']) {
                $relations['budgetLines'] = fn ($q) => $q->orderBy('sort_order')->orderBy('id');
                $relations['budgetLines.expenses'] = fn ($q) => $q->withTrashed()->orderBy('expense_date')->orderBy('id');
            }
            if ($included['participants'] || $included['generated_records']) {
                $relations['participants'] = fn ($q) => $q->orderBy('complete_name')->orderBy('last_name');
                $relations[] = 'participants.attachments';
                $relations[] = 'participants.mobilities';
            }
            if ($included['mobility'] || $included['dissemination'] || $included['participants'] || $included['feedback']) {
                $relations['mobilities'] = fn ($q) => $q->orderBy('sort_order')->orderBy('id');
            }
            if ($included['generated_records'] || $included['project_files'] || $included['mobility'] || $included['dissemination']) {
                $relations['documents'] = fn ($q) => $q->orderBy('category')->orderBy('document_date')->orderBy('title')->orderBy('id');
            }
            if ($included['project_data']) {
                $relations['tasks'] = fn ($q) => $q->orderBy('due_date')->orderBy('id');
            }
            $project->load($relations);
            foreach ($project->getRelations()['participants'] ?? [] as $participant) {
                $participant->setRelation('project', $project);
            }
            $campaigns = $included['feedback']
                ? MobilityFeedbackCampaign::query()->with(['mobility', 'responses' => fn ($q) => $q->latest('submitted_at')])
                    ->whereHas('mobility', fn ($q) => $q->where('project_id', $project->id))
                    ->orderBy('project_mobility_id')->orderBy('id')->get()
                : collect();
            foreach ($campaigns as $campaign) {
                $campaign->mobility->setRelation('project', $project);
            }

            return [
                'project' => $project,
                'captured_at' => now()->toIso8601String(),
                'campaigns' => $campaigns,
                'transfers' => $included['budget'] ? BudgetTransfer::where('project_id', $project->id)->orderBy('id')->get()->toArray() : [],
                'activity_max_id' => $included['project_data'] ? (int) $project->activityLogs()->max('id') : 0,
            ];
        });
    }
}
