<?php

namespace App\Services;

use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Builder;

class WorkspaceReportService
{
    public function build(?Workspace $workspace, User $user, array $filters = []): array
    {
        $start = filled($filters['start'] ?? null) ? $filters['start'] : null;
        $end = filled($filters['end'] ?? null) ? $filters['end'] : null;
        $status = $filters['status'] ?? 'all';

        $projects = ($workspace?->projects() ?? Project::query())
            ->visibleToAccount($user)
            ->when($status !== 'all', fn (Builder $query) => $query->where('status', $status))
            ->withCount('participants')
            ->with(['budgetLines.expenses' => fn ($query) => $query
                ->when($start, fn ($query) => $query->whereDate('expense_date', '>=', $start))
                ->when($end, fn ($query) => $query->whereDate('expense_date', '<=', $end))])
            ->orderBy('name')
            ->get();

        $rows = $projects->map(function ($project): array {
            $expenses = $project->budgetLines->flatMap->expenses;
            $funding = (float) $project->effective_budget;
            $spent = (float) $expenses->sum('amount_eur');

            return [
                'id' => $project->id,
                'project' => $project->name,
                'acronym' => $project->acronym,
                'status' => $project->status,
                'funding' => $funding,
                'spent' => $spent,
                'remaining' => $funding - $spent,
                'expenses' => $expenses->count(),
                'missing_evidence' => $expenses->filter(fn ($expense): bool => ! $expense->attachmentExists())->count(),
                'participants' => $project->participants_count,
                'start_date' => $project->start_date?->toDateString(),
                'end_date' => $project->end_date?->toDateString(),
            ];
        });

        $categories = $projects->flatMap(fn ($project) => $project->budgetLines->map(fn ($line): array => [
            'category' => $line->title,
            'allocated' => (float) $line->allocated_budget,
            'spent' => (float) $line->expenses->sum('amount_eur'),
        ]))->groupBy('category')->map(fn ($items, string $category): array => [
            'category' => $category,
            'allocated' => (float) $items->sum('allocated'),
            'spent' => (float) $items->sum('spent'),
        ])->sortByDesc('spent')->values();

        return [
            'rows' => $rows,
            'categories' => $categories,
            'totals' => [
                'projects' => $rows->count(),
                'funding' => (float) $rows->sum('funding'),
                'spent' => (float) $rows->sum('spent'),
                'remaining' => (float) $rows->sum('remaining'),
                'participants' => (int) $rows->sum('participants'),
                'expenses' => (int) $rows->sum('expenses'),
                'missing_evidence' => (int) $rows->sum('missing_evidence'),
            ],
            'filters' => compact('start', 'end', 'status'),
        ];
    }
}
