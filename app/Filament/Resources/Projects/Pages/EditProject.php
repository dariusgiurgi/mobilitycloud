<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProject extends EditRecord
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Save / Cancel pulled up here, next to the destructive actions.
            // formId('form') lets the Save button submit the page form even
            // though the header sits outside the <form> element.
            $this->getSaveFormAction()->formId('form'),
            $this->getCancelFormAction(),
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
                ->color('gray'),
        ];
    }

    // Remove the default Save / Cancel from the bottom of the form.
    protected function getFormActions(): array
    {
        return [];
    }
}
