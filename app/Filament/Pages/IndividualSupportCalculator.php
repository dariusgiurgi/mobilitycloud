<?php

namespace App\Filament\Pages;

use App\Models\SavedCalculation;
use App\Support\AuthorizesWorkspaceManagement;
use App\Support\PlanCatalog;
use App\Support\PlatformAccess;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class IndividualSupportCalculator extends Page
{
    use AuthorizesWorkspaceManagement;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;

    protected static ?string $navigationLabel = 'Individual Support Calculator';

    protected static string|\UnitEnum|null $navigationGroup = 'Planning tools';

    protected static ?int $navigationSort = 20;

    protected static ?string $title = 'Individual Support Calculator';

    protected string $view = 'filament.pages.individual-support-calculator';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        return PlatformAccess::canUse(PlanCatalog::MODULE_CALCULATOR);
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

    public int $participants = 1;

    public int $days = 7;

    public float $isRate = 79;

    public int $travelDays = 2;

    public bool $isTravelDaysIncluded = true;

    public int $travelBandIndex = 2;

    public bool $greenTravel = false;

    public float $osRate = 100;

    public bool $includeOS = true;

    // Save state
    public bool $showSaveModal = false;

    public string $saveName = '';

    public function getEligibleDaysProperty(): int
    {
        return $this->days + ($this->isTravelDaysIncluded ? $this->travelDays : 0);
    }

    public function getIsTotalProperty(): float
    {
        return round($this->participants * $this->eligibleDays * $this->isRate, 2);
    }

    public function getTravelPerParticipantProperty(): float
    {
        $band = self::TRAVEL_BANDS[$this->travelBandIndex] ?? self::TRAVEL_BANDS[0];

        return (float) ($this->greenTravel ? $band['green'] : $band['standard']);
    }

    public function getTravelTotalProperty(): float
    {
        return round($this->participants * $this->travelPerParticipant, 2);
    }

    public function getOsTotalProperty(): float
    {
        return $this->includeOS ? round($this->participants * $this->osRate, 2) : 0.0;
    }

    public function getGrandTotalProperty(): float
    {
        return round($this->isTotal + $this->travelTotal + $this->osTotal, 2);
    }

    // ─── Saved calculations ───
    public function getSavedProperty()
    {
        return SavedCalculation::where('workspace_id', Filament::getTenant()?->id)
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
        $this->authorizeWorkspaceManagement();
        $this->validate(['saveName' => 'required|min:2|max:255']);

        SavedCalculation::create([
            'workspace_id' => Filament::getTenant()?->id,
            'created_by' => auth()->id(),
            'name' => $this->saveName,
            'type' => 'individual_support',
            'inputs' => [
                'participants' => $this->participants,
                'days' => $this->days,
                'isRate' => $this->isRate,
                'travelDays' => $this->travelDays,
                'isTravelDaysIncluded' => $this->isTravelDaysIncluded,
                'travelBandIndex' => $this->travelBandIndex,
                'greenTravel' => $this->greenTravel,
                'osRate' => $this->osRate,
                'includeOS' => $this->includeOS,
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
        $calc = SavedCalculation::where('workspace_id', Filament::getTenant()?->id)->find($id);
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
        $this->authorizeWorkspaceManagement();
        SavedCalculation::where('workspace_id', Filament::getTenant()?->id)->where('id', $id)->delete();
        Notification::make()->title('Deleted')->send();
    }
}
