<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Enums\ProjectStatus;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Participant;
use App\Models\ProjectApplicationSection;
use App\Services\ProjectDocumentChecklist;
use App\Support\AuthorizesProjectManagement;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

class ViewProjectOverview extends Page
{
    use AuthorizesProjectManagement;
    use InteractsWithRecord;

    protected static string $resource = ProjectResource::class;

    protected string $view = 'filament.pages.view-project-overview';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function getTitle(): string
    {
        return $this->record->name;
    }

    public function getStatusEnum(): ProjectStatus
    {
        return $this->record->statusEnum();
    }

    public function getSectionCount(): int
    {
        return ProjectApplicationSection::where('project_id', $this->record->id)->count();
    }

    public function getApplicationSummary(): array
    {
        $sections = ProjectApplicationSection::query()
            ->where('project_id', $this->record->id)
            ->get();
        $completed = $sections->filter(fn (ProjectApplicationSection $section): bool => filled(trim(strip_tags($section->content ?? ''))))->count();
        $total = $sections->count();

        return [
            'total' => $total,
            'completed' => $completed,
            'progress' => $total > 0 ? (int) round($completed / $total * 100) : 0,
        ];
    }

    public function getParticipantSummary(): array
    {
        $participants = Participant::query()
            ->where('project_id', $this->record->id)
            ->with('attachments')
            ->get();
        $complete = $participants->filter->hasCompleteDocs()->count();

        return [
            'total' => $participants->count(),
            'complete' => $complete,
            'incomplete' => $participants->count() - $complete,
        ];
    }

    public function getDocumentSummary(): array
    {
        if (! $this->record->isManagementStage()) {
            return [
                'complete' => 0,
                'issues' => 0,
                'files' => $this->record->documents()->count(),
                'checklist_applies' => false,
            ];
        }

        $checklist = app(ProjectDocumentChecklist::class)->build($this->record);

        return [
            'complete' => $checklist['complete'],
            'issues' => $checklist['attention'] + $checklist['missing'],
            'files' => $this->record->documents()->count(),
            'checklist_applies' => true,
        ];
    }

    public function getRecentActivity()
    {
        return $this->record->activityLogs()
            ->with('user')
            ->latest()
            ->limit(12)
            ->get();
    }

    public function getModuleUrls(): array
    {
        return [
            'application' => ProjectResource::getUrl('write', ['record' => $this->record]),
            'budget' => ProjectResource::getUrl($this->record->isWritingStage() ? 'estimate' : 'board', ['record' => $this->record]),
            'participants' => ProjectResource::getUrl('participants', ['record' => $this->record]),
            'documents' => ProjectResource::getUrl('documents', ['record' => $this->record]),
            'settings' => ProjectResource::getUrl('edit', ['record' => $this->record]),
        ];
    }

    public function getNextStep(): array
    {
        $urls = $this->getModuleUrls();

        return match ($this->record->statusEnum()) {
            ProjectStatus::Writing => [
                'eyebrow' => 'Recommended next step',
                'title' => 'Continue writing the application',
                'description' => 'Complete the application sections and confirm the grant estimate before submission.',
                'label' => 'Open application',
                'url' => $urls['application'],
                'icon' => 'heroicon-o-pencil-square',
            ],
            ProjectStatus::Submitted => [
                'eyebrow' => 'Current position',
                'title' => 'Awaiting the funding decision',
                'description' => 'Keep the submitted version unchanged. Record the result using the status actions when it arrives.',
                'label' => null,
                'url' => null,
                'icon' => 'heroicon-o-clock',
            ],
            ProjectStatus::Rejected, ProjectStatus::Revise => [
                'eyebrow' => 'Recommended next step',
                'title' => 'Prepare the application revision',
                'description' => 'Review the feedback and update the application before submitting it again.',
                'label' => 'Open application',
                'url' => $urls['application'],
                'icon' => 'heroicon-o-arrow-path',
            ],
            ProjectStatus::Approved => [
                'eyebrow' => 'Recommended next step',
                'title' => 'Review the approved budget',
                'description' => 'Confirm the grant and budget baskets before starting project implementation.',
                'label' => 'Open budget',
                'url' => $urls['budget'],
                'icon' => 'heroicon-o-banknotes',
            ],
            ProjectStatus::Active => [
                'eyebrow' => 'Recommended next step',
                'title' => 'Keep implementation records current',
                'description' => 'Review participant files, expenses and signed project documents as delivery progresses.',
                'label' => 'Review participants',
                'url' => $urls['participants'],
                'icon' => 'heroicon-o-clipboard-document-check',
            ],
            ProjectStatus::Completed => [
                'eyebrow' => 'Recommended next step',
                'title' => 'Complete the final project file',
                'description' => 'Resolve remaining checklist items and keep the signed records together.',
                'label' => 'Review documents',
                'url' => $urls['documents'],
                'icon' => 'heroicon-o-archive-box',
            ],
        };
    }

    public function transitionTo(string $target): void
    {
        $this->authorizeProjectManagement();
        $targetEnum = ProjectStatus::tryFrom($target);
        if (! $targetEnum) {
            return;
        }

        $current = $this->record->statusEnum();

        if (! $current->canTransitionTo($targetEnum)) {
            Notification::make()
                ->title('That status change is not allowed from '.$current->getLabel())
                ->danger()
                ->send();

            return;
        }

        $this->record->status = $targetEnum->value;
        $this->record->save();

        // The magic moment: approval pre-fills the grant and seeds the baskets
        // from the budget estimate (only where not already set, so manual edits
        // are never overwritten).
        if ($targetEnum === ProjectStatus::Approved) {
            $this->applyEstimateToBudget();
        }

        $this->record->refresh();

        Notification::make()
            ->title('Status updated to '.$targetEnum->getLabel())
            ->success()
            ->send();
    }

    protected function applyEstimateToBudget(): void
    {
        $estimate = $this->record->action_data['estimate'] ?? null;
        if (! is_array($estimate)) {
            return;
        }

        $lines = $estimate['lines'] ?? [];
        $total = (float) ($estimate['total'] ?? 0);

        if ($total <= 0) {
            return;
        }

        // Pre-fill grant figures only when still empty.
        $dirty = false;
        if ((float) $this->record->approved_budget <= 0) {
            $this->record->approved_budget = $total;
            $dirty = true;
        }
        if ((float) $this->record->total_budget <= 0) {
            $this->record->total_budget = $total;
            $dirty = true;
        }
        if ($dirty) {
            $this->record->save();
        }

        // Map estimate lines onto the default baskets by title.
        $map = [
            'Travel' => (float) ($lines['travel'] ?? 0),
            'Individual Support' => (float) ($lines['is'] ?? 0),
            'Organisational Support' => (float) ($lines['os'] ?? 0),
            'Inclusion Support' => (float) ($lines['inclusion'] ?? 0),
        ];

        foreach ($this->record->budgetLines as $line) {
            if (array_key_exists($line->title, $map) && (float) $line->allocated_budget <= 0) {
                $line->allocated_budget = $map[$line->title];
                $line->save();
            }
        }

        Notification::make()
            ->title('Grant and baskets pre-filled from your estimate')
            ->body('Review them in the Budget board; adjust the confirmed grant in Settings if needed.')
            ->success()
            ->send();
    }
}
