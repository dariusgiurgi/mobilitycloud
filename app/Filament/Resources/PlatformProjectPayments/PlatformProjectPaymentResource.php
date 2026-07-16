<?php

namespace App\Filament\Resources\PlatformProjectPayments;

use App\Filament\Resources\PlatformProjectPayments\Pages\ListPlatformProjectPayments;
use App\Filament\Resources\PlatformUsers\PlatformUserResource;
use App\Models\Project;
use App\Models\User;
use App\Support\PlatformAudit;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PlatformProjectPaymentResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static string|\UnitEnum|null $navigationGroup = 'Billing & access';

    protected static ?string $navigationLabel = 'Project payments';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'project payment';

    protected static ?string $pluralModelLabel = 'project payments';

    public static function canAccess(): bool
    {
        return auth()->user()?->isPlatformAdmin() ?? false;
    }

    public static function isScopedToTenant(): bool
    {
        return false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with('ownerAccount')
                ->where(function (Builder $query): void {
                    $query
                        ->whereNotNull('approved_grant_amount')
                        ->orWhereIn('invoice_status', [
                            Project::INVOICE_PENDING,
                            Project::INVOICE_SENT,
                            Project::INVOICE_PAID,
                            Project::INVOICE_OVERDUE,
                        ]);
                }))
            ->columns([
                TextColumn::make('name')
                    ->label('Project')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Project $record): string => $record->ownerAccount?->email ?: 'No owner'),
                TextColumn::make('ownerAccount.billing_name')
                    ->label('Bill to')
                    ->state(fn (Project $record): string => $record->ownerAccount?->billing_name ?: 'Missing billing details')
                    ->description(fn (Project $record): ?string => $record->ownerAccount?->billing_vat ?: $record->ownerAccount?->billing_country)
                    ->color(fn (Project $record): string => $record->ownerAccount?->hasBillingDetails() ? 'gray' : 'danger')
                    ->wrap(),
                TextColumn::make('approved_grant_amount')
                    ->label('Approved grant')
                    ->money('EUR')
                    ->sortable(),
                TextColumn::make('activation_fee_amount')
                    ->label('Fee to invoice')
                    ->money('EUR')
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('invoice_status')
                    ->label('Invoice')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => Project::invoiceStatusOptions()[$state ?: Project::INVOICE_NOT_REQUIRED] ?? ucfirst((string) $state))
                    ->color(fn (Project $record): string => match (true) {
                        $record->hasPaymentOverdue() => 'danger',
                        $record->invoice_status === Project::INVOICE_PAID => 'success',
                        $record->invoice_status === Project::INVOICE_SENT => 'info',
                        default => 'warning',
                    })
                    ->description(fn (Project $record): ?string => $record->invoice_number ? 'Invoice '.$record->invoice_number : null),
                TextColumn::make('invoice_due_at')
                    ->label('Due')
                    ->dateTime('d M Y')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('payment_confirmed_at')
                    ->label('Paid at')
                    ->dateTime('d M Y')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Project status')
                    ->badge()
                    ->color(fn (Project $record): string => $record->statusEnum()->getColor() ?: 'gray')
                    ->state(fn (Project $record): string => $record->statusEnum()->getLabel()),
            ])
            ->filters([
                SelectFilter::make('invoice_status')
                    ->label('Invoice status')
                    ->options(Project::invoiceStatusOptions()),
                Filter::make('needs_invoice')
                    ->label('Needs invoice')
                    ->query(fn (Builder $query): Builder => $query->whereIn('invoice_status', [Project::INVOICE_PENDING, Project::INVOICE_OVERDUE])),
                Filter::make('overdue')
                    ->label('Overdue')
                    ->query(fn (Builder $query): Builder => $query
                        ->where(function (Builder $query): void {
                            $query
                                ->where('invoice_status', Project::INVOICE_OVERDUE)
                                ->orWhere(function (Builder $query): void {
                                    $query
                                        ->whereIn('invoice_status', [Project::INVOICE_PENDING, Project::INVOICE_SENT])
                                        ->whereNotNull('invoice_due_at')
                                        ->where('invoice_due_at', '<', now());
                                });
                        })),
                Filter::make('missing_billing')
                    ->label('Missing billing details')
                    ->query(fn (Builder $query): Builder => $query->whereHas('ownerAccount', fn (Builder $query): Builder => $query
                        ->where(function (Builder $query): void {
                            $query
                                ->whereNull('billing_name')->orWhere('billing_name', '')
                                ->orWhereNull('billing_country')->orWhere('billing_country', '')
                                ->orWhereNull('billing_address')->orWhere('billing_address', '');
                        }))),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('billingDetails')
                        ->label('View billing details')
                        ->icon('heroicon-o-building-office-2')
                        ->color('gray')
                        ->modalHeading(fn (Project $record): string => 'Billing details · '.$record->name)
                        ->modalContent(fn (Project $record) => view('filament.modals.project-payment-billing-details', [
                            'project' => $record->loadMissing('ownerAccount'),
                            'billing' => $record->ownerAccount?->billingDetailsForDisplay() ?? [],
                        ]))
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Close'),
                    Action::make('editInvoice')
                        ->label('Edit amount / invoice')
                        ->icon('heroicon-o-pencil-square')
                        ->color('info')
                        ->fillForm(fn (Project $record): array => [
                            'approved_grant_amount' => $record->approvedGrantAmount(),
                            'invoice_status' => $record->invoice_status ?: Project::INVOICE_PENDING,
                            'invoice_number' => $record->invoice_number,
                            'invoice_due_at' => $record->invoice_due_at,
                        ])
                        ->form(self::invoiceForm())
                        ->action(fn (Project $record, array $data): null => self::updateInvoice($record, $data, 'project.invoice_updated')),
                    Action::make('markSent')
                        ->label('Mark invoice sent')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('warning')
                        ->fillForm(fn (Project $record): array => [
                            'invoice_number' => $record->invoice_number,
                            'invoice_due_at' => $record->invoice_due_at ?: now()->addDays(14),
                        ])
                        ->form([
                            TextInput::make('invoice_number')
                                ->label('Invoice number')
                                ->maxLength(255),
                            DateTimePicker::make('invoice_due_at')
                                ->label('Payment due date')
                                ->required()
                                ->seconds(false),
                        ])
                        ->action(function (Project $record, array $data): void {
                            self::updateInvoice($record, [
                                'approved_grant_amount' => $record->approvedGrantAmount(),
                                'invoice_status' => Project::INVOICE_SENT,
                                'invoice_number' => $data['invoice_number'] ?? null,
                                'invoice_due_at' => $data['invoice_due_at'] ?? now()->addDays(14),
                            ], 'project.invoice_sent');
                        }),
                    Action::make('markPaid')
                        ->label('Mark payment received')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading(fn (Project $record): string => 'Mark '.$record->name.' as paid?')
                        ->modalDescription('This confirms the manual fiscal invoice payment and immediately unlocks implementation modules for the project.')
                        ->action(function (Project $record): void {
                            self::updateInvoice($record, [
                                'approved_grant_amount' => $record->approvedGrantAmount(),
                                'invoice_status' => Project::INVOICE_PAID,
                                'invoice_number' => $record->invoice_number,
                                'invoice_due_at' => $record->invoice_due_at,
                            ], 'project.invoice_paid');
                        }),
                    Action::make('openAccount')
                        ->label('Open account')
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->url(fn (Project $record): ?string => $record->ownerAccount
                            ? PlatformUserResource::getUrl('edit', ['record' => $record->ownerAccount], panel: 'platform')
                            : null),
                ]),
            ])
            ->defaultSort('invoice_due_at');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPlatformProjectPayments::route('/'),
        ];
    }

    protected static function invoiceForm(): array
    {
        return [
            TextInput::make('approved_grant_amount')
                ->label('Approved grant amount')
                ->numeric()
                ->prefix('€')
                ->minValue(1)
                ->required(),
            Select::make('invoice_status')
                ->label('Invoice status')
                ->options(Project::invoiceStatusOptions())
                ->required()
                ->native(false),
            TextInput::make('invoice_number')
                ->label('Invoice number')
                ->maxLength(255),
            DateTimePicker::make('invoice_due_at')
                ->label('Payment due date')
                ->seconds(false),
        ];
    }

    protected static function updateInvoice(Project $project, array $data, string $auditAction): null
    {
        $amount = round(max(0, (float) ($data['approved_grant_amount'] ?? $project->approvedGrantAmount())), 2);
        $status = $data['invoice_status'] ?? Project::INVOICE_PENDING;

        if ($amount <= 0) {
            Notification::make()
                ->title('Approved grant amount is required')
                ->danger()
                ->send();

            return null;
        }

        $attributes = [
            'approved_budget' => $amount,
            'approved_grant_amount' => $amount,
            'approved_grant_currency' => 'EUR',
            'approved_declared_at' => $project->approved_declared_at ?: now(),
            'approved_declared_by' => $project->approved_declared_by ?: $project->owner_id,
            'activation_fee_amount' => Project::calculateActivationFee($amount),
            'activation_fee_currency' => 'EUR',
            'invoice_status' => $status,
            'invoice_number' => filled($data['invoice_number'] ?? null) ? trim((string) $data['invoice_number']) : null,
            'invoice_due_at' => $data['invoice_due_at'] ?? $project->invoice_due_at,
        ];

        if ($status === Project::INVOICE_SENT) {
            $attributes['invoice_sent_at'] = $project->invoice_sent_at ?: now();
            $attributes['payment_confirmed_at'] = null;
            $attributes['payment_confirmed_by'] = null;
            if ($project->status === 'payment_overdue') {
                $attributes['status'] = 'approved';
            }
        } elseif ($status === Project::INVOICE_PAID) {
            $attributes['status'] = in_array($project->status, ['approved', 'payment_overdue'], true) ? 'active' : $project->status;
            $attributes['payment_confirmed_at'] = now();
            $attributes['payment_confirmed_by'] = auth()->id();
        } elseif ($status === Project::INVOICE_OVERDUE) {
            $attributes['status'] = 'payment_overdue';
            $attributes['payment_confirmed_at'] = null;
            $attributes['payment_confirmed_by'] = null;
        } else {
            if (! $project->statusEnum()->isManagementStage() || $project->status === 'payment_overdue') {
                $attributes['status'] = 'approved';
            }
            $attributes['payment_confirmed_at'] = null;
            $attributes['payment_confirmed_by'] = null;
        }

        $project->update($attributes);

        PlatformAudit::log($auditAction, 'Updated project payment for '.$project->name, $project, [
            'account' => $project->ownerAccount?->email,
            'invoice_status' => $status,
            'approved_grant_amount' => $amount,
            'activation_fee_amount' => $attributes['activation_fee_amount'],
            'invoice_due_at' => $project->invoice_due_at?->toISOString(),
        ]);

        Notification::make()
            ->title('Project payment updated')
            ->body($project->name.' · '.(Project::invoiceStatusOptions()[$status] ?? $status))
            ->success()
            ->send();

        return null;
    }
}
