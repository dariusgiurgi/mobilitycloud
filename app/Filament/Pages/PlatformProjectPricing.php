<?php

namespace App\Filament\Pages;

use App\Services\ProjectPricing;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class PlatformProjectPricing extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static string|\UnitEnum|null $navigationGroup = 'Billing & access';

    protected static ?string $navigationLabel = 'Project pricing';

    protected static ?string $title = 'Project pricing';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.platform-project-pricing';

    public static function canAccess(): bool
    {
        return auth()->user()?->isPlatformAdmin() ?? false;
    }

    protected function getHeaderActions(): array
    {
        return [Action::make('globalFee')
            ->label('Edit global fee')
            ->modalDescription('Applies automatically to accounts using the global fee. Fees already agreed on approved projects and custom account tariffs are preserved.')
            ->fillForm(fn () => ['percentage' => app(ProjectPricing::class)->globalPercentage()])
            ->schema([TextInput::make('percentage')->label('Global fee')->suffix('%')->numeric()->minValue(0.01)->maxValue(100)->step(0.01)->rules(['decimal:0,2'])->required()])
            ->action(function (array $data): void {
                app(ProjectPricing::class)->setGlobal($data['percentage']);
                Notification::make()->title('Global fee updated')->success()->send();
            })];
    }
}
