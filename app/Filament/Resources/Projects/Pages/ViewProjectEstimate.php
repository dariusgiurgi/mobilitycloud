<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Pages\IndividualSupportCalculator;
use App\Filament\Resources\Projects\ProjectResource;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

class ViewProjectEstimate extends Page
{
    use InteractsWithRecord;

    protected static string $resource = ProjectResource::class;
    protected string $view = 'filament.pages.view-project-estimate';

    // Inputs (persisted in project action_data['estimate']['inputs'])
    public int $persons = 1;          // total persons → Individual Support + Travel
    public int $participants = 1;     // participants only → Organisational Support
    public int $days = 7;             // activity days (excl. travel)
    public int $travelDays = 2;
    public bool $includeTravelDaysInIS = true;
    public float $isRate = 79;        // €/person/day (country-dependent)
    public int $travelBandIndex = 2;  // distance band
    public bool $greenTravel = true;
    public float $osRate = 100;       // €/participant
    public bool $includeOS = true;
    public float $inclusionOrgTotal = 0; // flat inclusion support for organisations

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        $in = $this->record->action_data['estimate']['inputs'] ?? null;
        if (is_array($in)) {
            $this->persons = (int) ($in['persons'] ?? $this->persons);
            $this->participants = (int) ($in['participants'] ?? $this->participants);
            $this->days = (int) ($in['days'] ?? $this->days);
            $this->travelDays = (int) ($in['travelDays'] ?? $this->travelDays);
            $this->includeTravelDaysInIS = (bool) ($in['includeTravelDaysInIS'] ?? $this->includeTravelDaysInIS);
            $this->isRate = (float) ($in['isRate'] ?? $this->isRate);
            $this->travelBandIndex = (int) ($in['travelBandIndex'] ?? $this->travelBandIndex);
            $this->greenTravel = (bool) ($in['greenTravel'] ?? $this->greenTravel);
            $this->osRate = (float) ($in['osRate'] ?? $this->osRate);
            $this->includeOS = (bool) ($in['includeOS'] ?? $this->includeOS);
            $this->inclusionOrgTotal = (float) ($in['inclusionOrgTotal'] ?? $this->inclusionOrgTotal);
        }
    }

    public function getTitle(): string
    {
        return $this->record->name . ' — Budget estimate';
    }

    public function getBands(): array
    {
        return IndividualSupportCalculator::TRAVEL_BANDS;
    }

    // ─── Computed lines ───
    public function getIsTotalProperty(): float
    {
        $totalDays = $this->days + ($this->includeTravelDaysInIS ? $this->travelDays : 0);
        return round(max(0, $this->persons) * max(0, $totalDays) * $this->isRate, 2);
    }

    public function getTravelPerPersonProperty(): float
    {
        $band = IndividualSupportCalculator::TRAVEL_BANDS[$this->travelBandIndex]
            ?? IndividualSupportCalculator::TRAVEL_BANDS[0];
        return (float) ($this->greenTravel ? $band['green'] : $band['standard']);
    }

    public function getTravelTotalProperty(): float
    {
        return round(max(0, $this->persons) * $this->travelPerPerson, 2);
    }

    public function getOsTotalProperty(): float
    {
        return $this->includeOS ? round(max(0, $this->participants) * $this->osRate, 2) : 0.0;
    }

    public function getInclusionTotalProperty(): float
    {
        return round(max(0, $this->inclusionOrgTotal), 2);
    }

    public function getGrandTotalProperty(): float
    {
        return round($this->isTotal + $this->travelTotal + $this->osTotal + $this->inclusionTotal, 2);
    }

    /**
     * Livewire 3 lifecycle hook — fired after any bound property changes.
     * Signature must accept the property name and value.
     */
    public function updated($property = null, $value = null): void
    {
        $this->persist();
    }

    protected function persist(): void
    {
        $data = $this->record->action_data ?? [];

        $data['estimate'] = [
            'inputs' => [
                'persons' => $this->persons,
                'participants' => $this->participants,
                'days' => $this->days,
                'travelDays' => $this->travelDays,
                'includeTravelDaysInIS' => $this->includeTravelDaysInIS,
                'isRate' => $this->isRate,
                'travelBandIndex' => $this->travelBandIndex,
                'greenTravel' => $this->greenTravel,
                'osRate' => $this->osRate,
                'includeOS' => $this->includeOS,
                'inclusionOrgTotal' => $this->inclusionOrgTotal,
            ],
            'lines' => [
                'travel'    => $this->travelTotal,
                'is'        => $this->isTotal,
                'os'        => $this->osTotal,
                'inclusion' => $this->inclusionTotal,
            ],
            'total' => $this->grandTotal,
            'updated_at' => now()->toIso8601String(),
        ];

        $this->record->action_data = $data;
        $this->record->save();
    }
}
