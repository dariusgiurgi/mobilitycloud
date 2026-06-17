<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Enums\ProjectStatus;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\ProjectApplicationSection;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

class ViewProjectOverview extends Page
{
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

    public function getModuleUrls(): array
    {
        return [
            'application' => ProjectResource::getUrl('write', ['record' => $this->record]),
            'budget'      => ProjectResource::getUrl('board', ['record' => $this->record]),
            'settings'    => ProjectResource::getUrl('edit',  ['record' => $this->record]),
        ];
    }

    public function transitionTo(string $target): void
    {
        $targetEnum = ProjectStatus::tryFrom($target);
        if (! $targetEnum) {
            return;
        }

        $current = $this->record->statusEnum();

        if (! $current->canTransitionTo($targetEnum)) {
            Notification::make()
                ->title('That status change is not allowed from ' . $current->getLabel())
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
            ->title('Status updated to ' . $targetEnum->getLabel())
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
            'Travel'                 => (float) ($lines['travel'] ?? 0),
            'Individual Support'     => (float) ($lines['is'] ?? 0),
            'Organisational Support' => (float) ($lines['os'] ?? 0),
            'Inclusion Support'      => (float) ($lines['inclusion'] ?? 0),
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
