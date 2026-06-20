<?php

namespace App\Filament\Pages;

use App\Support\AuthorizesWorkspaceManagement;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class ManageCurrencies extends Page
{
    use AuthorizesWorkspaceManagement;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyEuro;

    protected static ?string $navigationLabel = 'Currencies';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $title = 'Currencies';

    protected string $view = 'filament.pages.manage-currencies';

    public array $rows = [];

    public string $newCode = '';

    public $newRate = null;

    public function mount(): void
    {
        $currencies = Filament::getTenant()?->currencies ?? [];
        $this->rows = [];
        foreach ($currencies as $code => $rate) {
            // accepta format scalar {"RON":5.07} sau array {"RON":{"rate":5.07}}
            $r = is_array($rate) ? ($rate['rate'] ?? 1) : $rate;
            $this->rows[] = ['code' => $code, 'rate' => (float) $r];
        }
    }

    public function addCurrency(): void
    {
        $this->authorizeWorkspaceManagement();
        $code = strtoupper(trim($this->newCode));
        $rate = (float) $this->newRate;

        if (strlen($code) < 2 || strlen($code) > 5 || $rate <= 0) {
            return;
        }
        if ($code === 'EUR') {
            $this->newCode = '';
            $this->newRate = null;

            return; // EUR e moneda de baza, rate 1 implicit
        }
        // evita duplicat
        foreach ($this->rows as $row) {
            if ($row['code'] === $code) {
                $this->newCode = '';
                $this->newRate = null;

                return;
            }
        }

        $this->rows[] = ['code' => $code, 'rate' => $rate];
        $this->newCode = '';
        $this->newRate = null;
        $this->persist();
    }

    public function updateRate(int $index, $value): void
    {
        $this->authorizeWorkspaceManagement();
        if (isset($this->rows[$index])) {
            $this->rows[$index]['rate'] = (float) $value;
            $this->persist();
        }
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
        $tenant->currencies = $currencies;
        $tenant->save();
    }
}
