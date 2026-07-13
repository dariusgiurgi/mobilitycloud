<?php

namespace App\Filament\Pages;

use App\Models\SavedCalculation;
use App\Support\PlanCatalog;
use App\Support\PlatformAccess;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class IndividualSupportCalculator extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;

    protected static ?string $navigationLabel = 'Individual Support';

    protected static string|\UnitEnum|null $navigationGroup = 'Planning tools';

    protected static ?int $navigationSort = 20;

    protected static ?string $title = 'Individual Support Calculator';

    protected string $view = 'filament.pages.individual-support-calculator';

    public static function shouldRegisterNavigation(): bool
    {
        return PlatformAccess::canPreview(PlanCatalog::MODULE_CALCULATOR);
    }

    public static function canAccess(): bool
    {
        return PlatformAccess::canPreview(PlanCatalog::MODULE_CALCULATOR);
    }

    public const TRAVEL_BANDS = [
        ['label' => '0–9 km',        'green' => 0,    'standard' => 0],
        ['label' => '10–99 km',      'green' => 56,   'standard' => 28],
        ['label' => '100–499 km',    'green' => 285,  'standard' => 211],
        ['label' => '500–1999 km',   'green' => 417,  'standard' => 309],
        ['label' => '2000–2999 km',  'green' => 535,  'standard' => 395],
        ['label' => '3000–3999 km',  'green' => 785,  'standard' => 580],
        ['label' => '4000–7999 km',  'green' => 1188, 'standard' => 1188],
        ['label' => '8000+ km',      'green' => 1735, 'standard' => 1735],
    ];

    public $participants = 1;

    public $days = 7;

    public $isRate = 79;

    public $travelDays = 2;

    public $isTravelDaysIncluded = true;

    public $travelBandIndex = 2;

    public $greenTravel = false;

    public $osRate = 100;

    public $includeOS = true;

    // Save state
    public bool $showSaveModal = false;

    public string $saveName = '';

    public function mount(): void
    {
        $this->ensureCalculatorState();
    }

    public function hydrate(): void
    {
        $this->ensureCalculatorState();
    }

    public function getEligibleDaysProperty(): int
    {
        return $this->intValue('days', 7, 1)
            + ($this->boolValue('isTravelDaysIncluded', true) ? $this->intValue('travelDays', 2, 0) : 0);
    }

    public function getIsTotalProperty(): float
    {
        return round($this->intValue('participants', 1, 1) * $this->eligibleDays * $this->floatValue('isRate', 79, 0), 2);
    }

    public function getTravelPerParticipantProperty(): float
    {
        $band = self::TRAVEL_BANDS[$this->intValue('travelBandIndex', 2, 0)] ?? self::TRAVEL_BANDS[0];

        return (float) ($this->boolValue('greenTravel', false) ? $band['green'] : $band['standard']);
    }

    public function getTravelTotalProperty(): float
    {
        return round($this->intValue('participants', 1, 1) * $this->travelPerParticipant, 2);
    }

    public function getOsTotalProperty(): float
    {
        return $this->boolValue('includeOS', true)
            ? round($this->intValue('participants', 1, 1) * $this->floatValue('osRate', 100, 0), 2)
            : 0.0;
    }

    public function getGrandTotalProperty(): float
    {
        return round($this->isTotal + $this->travelTotal + $this->osTotal, 2);
    }

    // ─── Saved calculations ───
    public function getSavedProperty()
    {
        return SavedCalculation::query()
            ->where('created_by', auth()->id())
            ->whereNull('workspace_id')
            ->where('type', 'individual_support')
            ->latest()
            ->get();
    }

    public function openSave(): void
    {
        $this->saveName = '';
        $this->showSaveModal = true;
    }

    public function saveCalculation(): void
    {
        abort_unless(auth()->check() && static::canAccess() && ! PlatformAccess::isReadOnly(), 403);

        $this->validate(['saveName' => 'required|min:2|max:255']);

        SavedCalculation::create([
            'workspace_id' => null,
            'created_by' => auth()->id(),
            'name' => $this->saveName,
            'type' => 'individual_support',
            'inputs' => [
                'participants' => $this->intValue('participants', 1, 1),
                'days' => $this->intValue('days', 7, 1),
                'isRate' => $this->floatValue('isRate', 79, 0),
                'travelDays' => $this->intValue('travelDays', 2, 0),
                'isTravelDaysIncluded' => $this->boolValue('isTravelDaysIncluded', true),
                'travelBandIndex' => $this->intValue('travelBandIndex', 2, 0),
                'greenTravel' => $this->boolValue('greenTravel', false),
                'osRate' => $this->floatValue('osRate', 100, 0),
                'includeOS' => $this->boolValue('includeOS', true),
            ],
            'results' => [
                'is' => $this->isTotal,
                'travel' => $this->travelTotal,
                'os' => $this->osTotal,
                'total' => $this->grandTotal,
            ],
        ]);

        $this->showSaveModal = false;
        Notification::make()->title('Calculation saved')->success()->send();
    }

    public function loadCalculation(int $id): void
    {
        $calc = SavedCalculation::query()
            ->where('created_by', auth()->id())
            ->whereNull('workspace_id')
            ->where('type', 'individual_support')
            ->find($id);

        if (! $calc) {
            return;
        }

        $in = $calc->inputs;
        $this->participants = $in['participants'] ?? 1;
        $this->days = $in['days'] ?? 7;
        $this->isRate = $in['isRate'] ?? 79;
        $this->travelDays = $in['travelDays'] ?? 2;
        $this->isTravelDaysIncluded = $in['isTravelDaysIncluded'] ?? true;
        $this->travelBandIndex = $in['travelBandIndex'] ?? 2;
        $this->greenTravel = $in['greenTravel'] ?? false;
        $this->osRate = $in['osRate'] ?? 100;
        $this->includeOS = $in['includeOS'] ?? true;

        Notification::make()->title('Loaded: '.$calc->name)->success()->send();
    }

    public function deleteCalculation(int $id): void
    {
        abort_unless(auth()->check() && static::canAccess() && ! PlatformAccess::isReadOnly(), 403);

        SavedCalculation::query()
            ->where('created_by', auth()->id())
            ->whereNull('workspace_id')
            ->where('type', 'individual_support')
            ->whereKey($id)
            ->delete();

        Notification::make()->title('Deleted')->send();
    }

    private function ensureCalculatorState(): void
    {
        foreach ($this->calculatorDefaults() as $property => $default) {
            if (! isset($this->{$property}) || $this->{$property} === '') {
                $this->{$property} = $default;
            }
        }
    }

    private function calculatorDefaults(): array
    {
        return [
            'participants' => 1,
            'days' => 7,
            'isRate' => 79,
            'travelDays' => 2,
            'isTravelDaysIncluded' => true,
            'travelBandIndex' => 2,
            'greenTravel' => false,
            'osRate' => 100,
            'includeOS' => true,
        ];
    }

    private function intValue(string $property, int $default, int $min = 0): int
    {
        $value = isset($this->{$property}) ? $this->{$property} : $default;

        if ($value === '' || $value === null || ! is_numeric($value)) {
            $value = $default;
        }

        return max($min, (int) $value);
    }

    private function floatValue(string $property, float $default, float $min = 0): float
    {
        $value = isset($this->{$property}) ? $this->{$property} : $default;

        if ($value === '' || $value === null || ! is_numeric($value)) {
            $value = $default;
        }

        return max($min, (float) $value);
    }

    private function boolValue(string $property, bool $default): bool
    {
        $value = isset($this->{$property}) ? $this->{$property} : $default;

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }
}
