<?php

namespace App\Filament\Resources\PublicBlockReports\Tables;

use App\Models\PublicBlockReport;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PublicBlockReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('block.title')
                    ->label('Block')
                    ->wrap()
                    ->limit(50)
                    ->weight('medium')
                    ->description(fn (PublicBlockReport $r) => $r->block
                        ? 'by '.$r->block->displayAuthorName()
                        : null),

                TextColumn::make('reason')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => PublicBlockReport::REASONS[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'copyright' => 'danger',
                        'offensive' => 'danger',
                        'inaccurate' => 'warning',
                        'spam' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('details')
                    ->limit(60)
                    ->wrap()
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('user.name')
                    ->label('Reported by')
                    ->toggleable(),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ucfirst($state))
                    ->color(fn (string $state) => match ($state) {
                        'pending' => 'warning',
                        'reviewed' => 'success',
                        'dismissed' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('Reported')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'reviewed' => 'Reviewed',
                        'dismissed' => 'Dismissed',
                    ])
                    ->default('pending'),

                SelectFilter::make('reason')->options(PublicBlockReport::REASONS),
            ])
            ->recordActions([
                // Vezi continutul blocului raportat.
                Action::make('view')
                    ->label('View block')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading(fn (PublicBlockReport $r) => $r->block?->title ?? 'Block')
                    ->modalContent(fn (PublicBlockReport $r) => view('filament.modals.report-block-preview', ['block' => $r->block]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),

                ActionGroup::make([
                    // Keep — pastreaza blocul, marcheaza raportul ca revizuit.
                    Action::make('keep')
                        ->label('Keep block')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalDescription('The block stays public. This report will be marked as reviewed.')
                        ->action(function (PublicBlockReport $r) {
                            $r->update(['status' => PublicBlockReport::STATUS_REVIEWED]);
                            Notification::make()->title('Block kept')->success()->send();
                        }),

                    // Hide — ascunde blocul din biblioteca (ramane in DB).
                    Action::make('hide')
                        ->label('Hide block')
                        ->icon('heroicon-o-eye-slash')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalDescription('The block will be hidden from the public library but kept in the database. The report will be marked as reviewed.')
                        ->action(function (PublicBlockReport $r) {
                            $r->block?->update(['is_hidden' => true]);
                            $r->update(['status' => PublicBlockReport::STATUS_REVIEWED]);
                            Notification::make()->title('Block hidden')->success()->send();
                        }),

                    // Delete — sterge definitiv blocul public.
                    Action::make('delete_block')
                        ->label('Delete block')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalDescription('The public block will be permanently deleted. This cannot be undone.')
                        ->action(function (PublicBlockReport $r) {
                            $r->block?->delete(); // cascada sterge si like-urile/raportarile
                            Notification::make()->title('Block deleted')->success()->send();
                        }),

                    // Dismiss — respinge raportul (neintemeiat).
                    Action::make('dismiss')
                        ->label('Dismiss report')
                        ->icon('heroicon-o-x-mark')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->modalDescription('This report will be dismissed. The block stays public.')
                        ->action(function (PublicBlockReport $r) {
                            $r->update(['status' => PublicBlockReport::STATUS_DISMISSED]);
                            Notification::make()->title('Report dismissed')->success()->send();
                        }),
                ])
                    ->label('Actions')
                    ->button(),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No reports')
            ->emptyStateDescription('Reported public blocks will appear here for review.');
    }
}
