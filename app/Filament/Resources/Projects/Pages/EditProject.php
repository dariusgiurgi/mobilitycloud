<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Expense;
use App\Support\AuthorizesProjectManagement;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EditProject extends EditRecord
{
    use AuthorizesProjectManagement;

    protected static string $resource = ProjectResource::class;

    protected array $previousCurrencyRates = [];

    private bool $isAutosaving = false;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        ProjectResource::ensureProjectAccountTenant($this->record, 'edit');
        abort_unless($this->record->canAccessProjectModule(auth()->user(), 'edit'), 404);

        $this->touchProjectCollaboration('edit');
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                DeleteAction::make()
                    ->label('Archive project')
                    ->icon('heroicon-o-archive-box')
                    ->modalHeading('Archive this project?')
                    ->modalDescription('The project will be removed from active views but can be restored later.')
                    ->successNotificationTitle('Project archived'),
            ])
                ->label('More actions')
                ->icon('heroicon-m-ellipsis-horizontal')
                ->button()
                ->color('gray')
                ->visible(fn (): bool => $this->record->canManageLifecycleBy(auth()->user())),
        ];
    }

    // Remove the default Save / Cancel from the bottom of the form.
    protected function getFormActions(): array
    {
        return [];
    }

    public function updated(string $propertyName, mixed $value = null): void
    {
        if (! str_starts_with($propertyName, 'data.')) {
            return;
        }

        if ($this->isAutosaving || ! isset($this->record)) {
            return;
        }

        $this->isAutosaving = true;

        try {
            $this->save(shouldRedirect: false, shouldSendSavedNotification: false);

            Notification::make()
                ->title('Project settings saved automatically')
                ->success()
                ->send();
        } catch (ValidationException $exception) {
            throw $exception;
        } finally {
            $this->isAutosaving = false;
        }
    }

    protected function beforeSave(): void
    {
        $this->authorizeManagementModuleMutation('edit', 'project-settings', 'Project settings');

        $this->previousCurrencyRates = $this->record->currencyRates();
    }

    protected function afterSave(): void
    {
        $current = $this->record->fresh();
        $newRates = $current->currencyRates();

        if ($this->previousCurrencyRates === $newRates) {
            return;
        }

        $updated = $this->recalculateExpenses($newRates);

        Notification::make()
            ->title('Project currencies updated')
            ->body($updated === 0
                ? 'No existing project expenses needed recalculation.'
                : $updated.' existing '.str('expense')->plural($updated).' recalculated for this project.')
            ->success()
            ->send();
    }

    private function recalculateExpenses(array $rates): int
    {
        $expenses = Expense::query()
            ->whereHas('budgetLine', fn ($query) => $query->where('project_id', $this->record->id))
            ->where('currency', '!=', 'EUR')
            ->get();

        DB::transaction(function () use ($expenses, $rates): void {
            foreach ($expenses as $expense) {
                $currency = strtoupper((string) $expense->currency);
                $rate = $rates[$currency] ?? null;

                if (! is_numeric($rate) || (float) $rate <= 0) {
                    $expense->exchange_rate = null;
                    $expense->amount_eur = 0;
                    $expense->saveQuietly();

                    continue;
                }

                $expense->exchange_rate = (float) $rate;
                $expense->amount_eur = round((float) $expense->amount / (float) $rate, 2);
                $expense->saveQuietly();
            }
        });

        return $expenses->count();
    }
}
