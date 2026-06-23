<?php

namespace App\Filament\Pages;

use App\Models\Expense;
use App\Support\AuthorizesWorkspaceManagement;
use App\Support\PlatformAccess;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;

class ManageCurrencies extends Page
{
    use AuthorizesWorkspaceManagement;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyEuro;

    protected static ?string $navigationLabel = 'Currencies';

    protected static string|\UnitEnum|null $navigationGroup = 'Workspace settings';

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'Currencies';

    protected string $view = 'filament.pages.manage-currencies';

    public array $rows = [];

    public static function canAccess(): bool
    {
        return PlatformAccess::usesWorkspaceInterface();
    }

    public string $newCode = '';

    public $newRate = null;

    public function mount(): void
    {
        $currencies = Filament::getTenant()?->currencies ?? [];
        $this->rows = [];
        foreach ($currencies as $code => $rate) {
            // accepta format scalar {"RON":5.07} sau array {"RON":{"rate":5.07}}
            $r = is_array($rate) ? ($rate['rate'] ?? 1) : $rate;
            $code = strtoupper((string) $code);

            if ($code === 'EUR' || ! is_numeric($r) || (float) $r <= 0) {
                continue;
            }

            $this->rows[] = ['code' => $code, 'rate' => (float) $r];
        }

        $this->sortRows();
    }

    public function addCurrency(): void
    {
        $this->authorizeWorkspaceManagement();
        $this->resetErrorBag();

        $data = $this->validate([
            'newCode' => ['required', 'string', 'size:3', 'regex:/^[A-Za-z]{3}$/'],
            'newRate' => ['required', 'numeric', 'gt:0', 'lte:1000000'],
        ], [
            'newCode.size' => 'Use the 3-letter currency code, for example RON or USD.',
            'newCode.regex' => 'The currency code may contain letters only.',
            'newRate.gt' => 'The exchange rate must be greater than zero.',
        ]);

        $code = strtoupper(trim($data['newCode']));
        $rate = (float) $data['newRate'];

        if ($code === 'EUR') {
            $this->addError('newCode', 'EUR is already the workspace base currency.');

            return;
        }

        foreach ($this->rows as $row) {
            if ($row['code'] === $code) {
                $this->addError('newCode', $code.' is already configured.');

                return;
            }
        }

        $this->rows[] = ['code' => $code, 'rate' => $rate];
        $this->sortRows();
        $this->newCode = '';
        $this->newRate = null;
        $this->persist();
        $updated = $this->recalculateExpenses($code, $rate);

        Notification::make()
            ->title($code.' added')
            ->body($this->recalculationMessage($updated))
            ->success()
            ->send();
    }

    public function updateRate(int $index, $value): void
    {
        $this->authorizeWorkspaceManagement();
        $errorKey = 'rows.'.$index.'.rate';
        $this->resetErrorBag($errorKey);

        if (! isset($this->rows[$index])) {
            return;
        }

        if (! is_numeric($value) || (float) $value <= 0 || (float) $value > 1000000) {
            $this->addError($errorKey, 'Enter a rate greater than zero.');

            return;
        }

        $this->rows[$index]['rate'] = (float) $value;
        $this->persist();
        $updated = $this->recalculateExpenses($this->rows[$index]['code'], (float) $value);

        Notification::make()
            ->title($this->rows[$index]['code'].' rate updated')
            ->body($this->recalculationMessage($updated))
            ->success()
            ->send();
    }

    public function removeCurrency(int $index): void
    {
        $this->authorizeWorkspaceManagement();
        if (isset($this->rows[$index])) {
            unset($this->rows[$index]);
            $this->rows = array_values($this->rows);
            $this->persist();
        }
    }

    private function persist(): void
    {
        $currencies = [];
        foreach ($this->rows as $row) {
            if (! empty($row['code']) && $row['rate'] > 0) {
                $currencies[strtoupper($row['code'])] = (float) $row['rate'];
            }
        }
        $tenant = Filament::getTenant();
        if (! $tenant) {
            return;
        }

        $tenant->currencies = $currencies;
        $tenant->save();
    }

    public function getSubheading(): ?string
    {
        return 'Manual exchange rates used to convert project expenses into EUR.';
    }

    private function sortRows(): void
    {
        usort($this->rows, fn (array $a, array $b): int => $a['code'] <=> $b['code']);
    }

    private function recalculateExpenses(string $currency, float $rate): int
    {
        $workspaceId = Filament::getTenant()?->id;
        if (! $workspaceId || $rate <= 0) {
            return 0;
        }

        $expenses = Expense::query()
            ->where('currency', $currency)
            ->whereHas('budgetLine.project', fn ($query) => $query->where('workspace_id', $workspaceId))
            ->get();

        DB::transaction(function () use ($expenses, $rate): void {
            foreach ($expenses as $expense) {
                $expense->exchange_rate = $rate;
                $expense->amount_eur = round((float) $expense->amount / $rate, 2);
                $expense->saveQuietly();
            }
        });

        return $expenses->count();
    }

    private function recalculationMessage(int $updated): string
    {
        if ($updated === 0) {
            return 'No existing expenses use this currency.';
        }

        return $updated.' existing '.str('expense')->plural($updated).' recalculated.';
    }
}
