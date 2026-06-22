<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Facades\DB;

class ProjectDuplicator
{
    public function duplicate(Project $source, array $options): Project
    {
        return DB::transaction(function () use ($source, $options): Project {
            $copyBudget = (bool) ($options['copy_budget'] ?? true);
            $copyPartners = (bool) ($options['copy_partners'] ?? true);

            $project = Project::create([
                'workspace_id' => $source->workspace_id,
                'name' => trim($options['name']),
                'acronym' => null,
                'grant_ref' => null,
                'ka_action' => $source->ka_action,
                'description' => $source->description,
                'status' => 'writing',
                'total_budget' => $copyBudget ? $source->total_budget : 0,
                'approved_budget' => null,
                'first_tranche_pct' => $source->first_tranche_pct ?? 80,
                'withholding_tax_rate' => $source->withholding_tax_rate ?? 10,
                'expense_prefix' => $source->expense_prefix ?: 'EXP',
                'expense_pad_length' => $source->expense_pad_length ?: 3,
                'partner_orgs' => $copyPartners ? $source->partner_orgs : [],
                'partner_org' => $copyPartners ? $source->partner_org : null,
                'action_data' => $copyBudget ? $source->action_data : null,
            ]);

            if ($options['copy_application'] ?? true) {
                foreach ($source->applicationSections()->orderBy('sort_order')->get() as $section) {
                    $project->applicationSections()->create([
                        'title' => $section->title,
                        'content' => $section->content,
                        'char_limit' => $section->char_limit,
                        'category' => $section->category,
                        'sort_order' => $section->sort_order,
                    ]);
                }
            }

            if ($copyBudget) {
                $this->copyBudgetStructure($source, $project);
            }

            return $project->fresh();
        });
    }

    private function copyBudgetStructure(Project $source, Project $project): void
    {
        $targetLines = $project->budgetLines()->get()->keyBy('title');

        foreach ($source->budgetLines()->orderBy('sort_order')->get() as $sourceLine) {
            $attributes = [
                'emoji' => $sourceLine->emoji,
                'color' => $sourceLine->color,
                'background_color' => $sourceLine->background_color,
                'allocated_budget' => $sourceLine->allocated_budget,
                'sort_order' => $sourceLine->sort_order,
            ];

            if ($targetLine = $targetLines->get($sourceLine->title)) {
                $targetLine->update($attributes);
            } else {
                $project->budgetLines()->create(['title' => $sourceLine->title, ...$attributes]);
            }
        }
    }
}
