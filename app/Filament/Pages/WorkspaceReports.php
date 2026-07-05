<?php

namespace App\Filament\Pages;

use App\Services\WorkspaceReportService;
use App\Support\PlanCatalog;
use App\Support\PlatformAccess;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class WorkspaceReports extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Portfolio reports';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Portfolio reports';

    protected string $view = 'filament.pages.workspace-reports';

    public string $status = 'all';

    public static function canAccess(): bool
    {
        return PlatformAccess::canUse(PlanCatalog::MODULE_REPORTS);
    }

    public ?string $startDate = null;

    public ?string $endDate = null;

    public function getSubheading(): ?string
    {
        return 'A portfolio-level view of funding, expenditure, participants and missing evidence.';
    }

    public function getReportProperty(): array
    {
        return app(WorkspaceReportService::class)->build(null, auth()->user(), [
            'status' => $this->status,
            'start' => $this->startDate,
            'end' => $this->endDate,
        ]);
    }

    public function getCsvUrlProperty(): string
    {
        return route('workspaces.report.csv', [
            'workspace' => Filament::getTenant(),
            'status' => $this->status,
            'start' => $this->startDate,
            'end' => $this->endDate,
        ]);
    }

    public function clearFilters(): void
    {
        $this->status = 'all';
        $this->startDate = null;
        $this->endDate = null;
    }
}
