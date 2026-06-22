<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Expense;
use App\Models\Participant;
use App\Models\Project;
use App\Models\ProjectDocument;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class GlobalSearch extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;

    protected static ?string $navigationLabel = 'Search';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Search workspace';

    protected string $view = 'filament.pages.global-search';

    public string $search = '';

    public function getSubheading(): ?string
    {
        return 'Find accessible projects, participants, expenses and documents from one place.';
    }

    public function getResultsProperty(): array
    {
        $term = trim($this->search);
        if (mb_strlen($term) < 2) {
            return ['projects' => collect(), 'participants' => collect(), 'expenses' => collect(), 'documents' => collect()];
        }

        $like = '%'.$term.'%';
        $workspace = Filament::getTenant();
        $accessible = fn (Builder $query): Builder => $query
            ->where('workspace_id', $workspace?->id)
            ->accessibleTo(auth()->user(), $workspace);

        $projects = Project::query()
            ->where('workspace_id', $workspace?->id)
            ->accessibleTo(auth()->user(), $workspace)
            ->where(fn (Builder $query) => $query->where('name', 'like', $like)
                ->orWhere('acronym', 'like', $like)
                ->orWhere('grant_ref', 'like', $like))
            ->orderBy('name')->limit(8)->get()
            ->map(fn (Project $project): array => [
                'title' => $project->name,
                'detail' => collect([$project->acronym, $project->grant_ref])->filter()->join(' · ') ?: ucfirst($project->status),
                'url' => ProjectResource::getUrl('overview', ['record' => $project]),
            ]);

        $participants = Participant::query()
            ->whereHas('project', $accessible)
            ->where(fn (Builder $query) => $query->where('first_name', 'like', $like)
                ->orWhere('last_name', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('partner_organisation', 'like', $like))
            ->with('project')->orderBy('last_name')->limit(8)->get()
            ->map(fn (Participant $participant): array => [
                'title' => $participant->fullName(),
                'detail' => $participant->project->name.' · '.($participant->email ?: $participant->partner_organisation ?: 'Participant'),
                'url' => ProjectResource::getUrl('participants', ['record' => $participant->project]),
            ]);

        $expenses = Expense::query()
            ->whereHas('budgetLine.project', $accessible)
            ->where(fn (Builder $query) => $query->where('description', 'like', $like)
                ->orWhere('reference_nr', 'like', $like)
                ->orWhere('attachment_name', 'like', $like))
            ->with('budgetLine.project')->latest('expense_date')->limit(8)->get()
            ->map(fn (Expense $expense): array => [
                'title' => $expense->description ?: ($expense->reference_nr ?: 'Expense #'.$expense->id),
                'detail' => $expense->budgetLine->project->name.' · '.number_format((float) $expense->amount, 2).' '.($expense->currency ?: 'EUR'),
                'url' => ProjectResource::getUrl('board', ['record' => $expense->budgetLine->project]),
            ]);

        $documents = ProjectDocument::query()
            ->whereHas('project', $accessible)
            ->where(fn (Builder $query) => $query->where('title', 'like', $like)
                ->orWhere('file_name', 'like', $like)
                ->orWhere('signed_name', 'like', $like)
                ->orWhere('category', 'like', $like))
            ->with('project')->latest()->limit(8)->get()
            ->map(fn (ProjectDocument $document): array => [
                'title' => $document->title ?: ($document->file_name ?: 'Project document'),
                'detail' => $document->project->name.' · '.str($document->type)->replace('_', ' ')->title(),
                'url' => ProjectResource::getUrl('documents', ['record' => $document->project]),
            ]);

        return compact('projects', 'participants', 'expenses', 'documents');
    }

    public function getResultCountProperty(): int
    {
        return collect($this->results)->sum(fn ($items): int => $items->count());
    }
}
