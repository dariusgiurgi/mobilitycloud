<?php

namespace App\Filament\Resources\PlatformWorkspaces\RelationManagers;

use App\Models\PlatformSubscriptionEvent;
use App\Support\PlatformAudit;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SubscriptionEventsRelationManager extends RelationManager
{
    protected static string $relationship = 'subscriptionEvents';

    protected static ?string $title = 'Subscription timeline';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('event_type')
                ->options(PlatformSubscriptionEvent::typeOptions())
                ->default('manual_note')
                ->required(),
            TextInput::make('summary')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('summary')
            ->columns([
                TextColumn::make('event_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => PlatformSubscriptionEvent::typeOptions()[$state] ?? ucfirst(str_replace('_', ' ', $state)))
                    ->color(fn (string $state): string => match ($state) {
                        'expired', 'suspended' => 'danger',
                        'trial_extended', 'trial_updated', 'demo_enabled', 'demo_reset', 'demo_reset_configured' => 'warning',
                        'activated', 'reactivated' => 'success',
                        'plan_changed', 'status_changed' => 'info',
                        'billing_updated' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('summary')
                    ->wrap()
                    ->searchable(),
                TextColumn::make('actor.name')
                    ->label('Actor')
                    ->placeholder('System')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('When')
                    ->since()
                    ->sortable()
                    ->description(fn (PlatformSubscriptionEvent $record): string => $record->created_at?->format('d M Y, H:i') ?? ''),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->label('Add timeline note')
                    ->mutateDataUsing(function (array $data): array {
                        $data['actor_id'] = auth()->id();

                        return $data;
                    })
                    ->after(function (PlatformSubscriptionEvent $record): void {
                        PlatformAudit::log('subscription_event.created', 'Added subscription timeline note for '.$record->workspace?->name, $record->workspace, [
                            'event_id' => $record->id,
                            'event_type' => $record->event_type,
                        ]);
                    }),
            ]);
    }
}
