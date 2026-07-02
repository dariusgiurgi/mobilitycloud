<?php

namespace App\Filament\Resources\PlatformActivities\Pages;

use App\Filament\Resources\PlatformActivities\PlatformActivityResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewPlatformActivity extends ViewRecord
{
    protected static string $resource = PlatformActivityResource::class;

    public function getTitle(): string
    {
        return PlatformActivityResource::actionLabel($this->record->action);
    }

    public function getSubheading(): ?string
    {
        return 'Audit event #'.$this->record->id.' · '.$this->record->created_at?->format('d M Y, H:i:s');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('backToActivity')
                ->label('Back to activity center')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn (): string => PlatformActivityResource::getUrl('index', panel: 'platform')),
        ];
    }
}
