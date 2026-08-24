<?php

namespace App\Filament\Resources\Projects\Schemas;

use App\Models\Project;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('How would you like to start?')
                ->description('Choose the shortest route. You can add the remaining project details later.')
                ->visible(fn (string $operation): bool => $operation === 'create')
                ->schema([
                    Radio::make('project_entry_mode')
                        ->label('Project type')
                        ->options([
                            'application' => 'I am preparing a new application',
                            'approved' => 'I already have an approved project',
                        ])
                        ->descriptions([
                            'application' => 'Start in Writing. Choose the official application template there.',
                            'approved' => 'Skip Writing and start directly with project implementation.',
                        ])
                        ->default('application')
                        ->live()
                        ->required()
                        ->dehydrated(),
                ]),

            Section::make('Project details')
                ->description(fn (string $operation): string => $operation === 'create'
                    ? 'Start with the essentials. Everything else can be completed from Project settings.'
                    : 'The basic information used across the workspace and generated documents.')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Project name')
                        ->required()
                        ->live(onBlur: true)
                        ->maxLength(255)
                        ->columnSpanFull(),
                    TextInput::make('acronym')
                        ->label('Short name / acronym')
                        ->live(onBlur: true)
                        ->maxLength(255),
                    TextInput::make('grant_ref')
                        ->label('Project code / grant reference')
                        ->live(onBlur: true)
                        ->maxLength(255)
                        ->required(fn (callable $get, string $operation): bool => $operation === 'create' && $get('project_entry_mode') === 'approved')
                        ->visible(fn (callable $get, string $operation): bool => $operation !== 'create' || $get('project_entry_mode') === 'approved')
                        ->placeholder('Added after approval'),
                    Textarea::make('description')
                        ->label('What is this project about?')
                        ->rows(3)
                        ->live(onBlur: true)
                        ->placeholder('One or two sentences are enough for now.')
                        ->columnSpanFull(),
                ]),

            Section::make('Approval details')
                ->description('These details create an approved implementation project. They are locked once saved.')
                ->visible(fn (callable $get, string $operation): bool => $operation === 'create' && $get('project_entry_mode') === 'approved')
                ->columns(2)
                ->schema([
                    TextInput::make('approved_grant_declaration')
                        ->label('Approved grant amount')
                        ->numeric()
                        ->prefix('€')
                        ->minValue(1)
                        ->required()
                        ->helperText(fn (): string => auth()->user()?->isUnlimitedAccount()
                            ? 'This amount is locked after creation. Unlimited accounts do not generate administration fees.'
                            : 'This amount is locked after creation and is used to calculate the platform administration fee.'),
                    FileUpload::make('approved_grant_proof_upload')
                        ->label('Approval document')
                        ->disk('local')
                        ->directory('project-approval-proofs/pending')
                        ->visibility('private')
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp'])
                        ->maxSize(10240)
                        ->required()
                        ->helperText('Upload the approval letter or approved budget. PDF or image, maximum 10 MB.'),
                ]),

            self::timelineAndMobilities(),

            Section::make('Involved organisations')
                ->description('Add the coordinator and partners when you need them for participant grouping and attendance sheets.')
                ->schema([
                    Repeater::make('partner_orgs')
                        ->hiddenLabel()
                        ->live()
                        ->schema([
                            TextInput::make('name')->label('Organisation name')->required()->live(onBlur: true)->maxLength(255)->columnSpan(2),
                            TextInput::make('country')->live(onBlur: true)->maxLength(100),
                            TextInput::make('oid')->label('OID')->live(onBlur: true)->maxLength(50)->placeholder('E00000000'),
                            Toggle::make('is_coordinator')->label('Coordinator')->live()->inline(false)->columnSpan(1),
                        ])
                        ->columns(5)
                        ->addActionLabel('Add organisation')
                        ->itemLabel(fn (array $state): ?string => ($state['name'] ?? null).(! empty($state['is_coordinator']) ? ' — Coordinator' : ''))
                        ->collapsible()
                        ->collapsed()
                        ->reorderable()
                        ->defaultItems(0)
                        ->columnSpanFull(),
                ]),

            self::approvalAndInvoice(),

            Section::make('Operational finance settings')
                ->description('Used by generated project documents. Approved grant values are managed in the approval record above.')
                ->visible(fn (string $operation): bool => $operation !== 'create')
                ->columns(2)
                ->schema([
                    TextInput::make('first_tranche_pct')->label('1st tranche (%)')->numeric()->live(onBlur: true)->default(80)->suffix('%')->minValue(0)->maxValue(100),
                    TextInput::make('withholding_tax_rate')->label('Withholding tax (%)')->numeric()->live(onBlur: true)->default(10)->suffix('%')->minValue(0)->maxValue(100)->helperText('Applied when civil convention payment statements are generated.'),
                ]),

            Section::make('Project currencies')
                ->description('EUR is the base currency. Add only currencies used by this project.')
                ->visible(fn (string $operation): bool => $operation !== 'create')
                ->schema([
                    Repeater::make('currencies')
                        ->hiddenLabel()
                        ->live()
                        ->schema([
                            TextInput::make('code')->label('Currency code')->placeholder('RON')->required()->live(onBlur: true)->maxLength(3)->minLength(3)->regex('/^[A-Za-z]{3}$/')->dehydrateStateUsing(fn (?string $state): string => Str::upper(trim((string) $state)))->helperText('Use ISO code, e.g. RON or USD. EUR is already included.'),
                            TextInput::make('rate')->label('Rate')->numeric()->required()->live(onBlur: true)->minValue(0.000001)->maxValue(1000000)->helperText('How many units equal 1 EUR. Example: 1 EUR = 5.07 RON.'),
                        ])
                        ->columns(2)
                        ->addActionLabel('Add currency')
                        ->defaultItems(0)
                        ->reorderable(false)
                        ->itemLabel(fn (array $state): ?string => isset($state['code']) ? Str::upper((string) $state['code']) : 'Currency')
                        ->dehydrateStateUsing(fn (?array $state): array => collect($state ?? [])
                            ->map(function (array $row): ?array {
                                $code = Str::upper(trim((string) ($row['code'] ?? '')));
                                $rate = $row['rate'] ?? null;

                                return $code === '' || $code === 'EUR' || strlen($code) !== 3 || ! is_numeric($rate) || (float) $rate <= 0
                                    ? null
                                    : ['code' => $code, 'rate' => (float) $rate];
                            })
                            ->filter()->unique('code')->values()->all())
                        ->columnSpanFull(),
                ]),

            Section::make('Advanced controls')
                ->description('Usually these can be left as they are.')
                ->visible(fn (string $operation): bool => $operation !== 'create')
                ->columns(2)
                ->collapsible()
                ->collapsed()
                ->schema([
                    TextInput::make('expense_prefix')->label('Expense prefix')->default('EXP')->live(onBlur: true)->maxLength(20)->regex('/^[A-Za-z0-9_-]+$/')->dehydrateStateUsing(fn (?string $state): string => Str::upper(trim($state ?: 'EXP')))->helperText('Letters, numbers, hyphens and underscores only.'),
                    Select::make('expense_pad_length')->label('Expense number padding')->options([2 => '2 digits', 3 => '3 digits', 4 => '4 digits', 5 => '5 digits', 6 => '6 digits'])->default(3)->live()->native(false),
                ]),
        ]);
    }

    private static function timelineAndMobilities(): Section
    {
        return Section::make('Timeline and mobilities')
            ->description('Add dates only after the project is approved. Each mobility has its own period.')
            ->visible(fn (callable $get, string $operation, ?Project $record): bool => $operation === 'create'
                ? $get('project_entry_mode') === 'approved'
                : $record?->isManagementStage() === true)
            ->columns(2)
            ->schema([
                DatePicker::make('start_date')->label('Project start')->live()->required(fn (string $operation): bool => $operation === 'create'),
                DatePicker::make('end_date')->label('Project end')->live()->afterOrEqual('start_date')->required(fn (string $operation): bool => $operation === 'create'),
                Repeater::make('mobilities')
                    ->relationship()
                    ->hiddenLabel()
                    ->schema([
                        TextInput::make('name')->label('Mobility name')->required()->maxLength(255)->placeholder('e.g. VET group — Porto'),
                        DatePicker::make('start_date')->label('Start date')->required()->live(),
                        DatePicker::make('end_date')->label('End date')->required()->live()->afterOrEqual('start_date'),
                        TextInput::make('destination_country')->label('Destination country')->maxLength(100)->placeholder('Optional'),
                        TextInput::make('host_organisation')->label('Host organisation')->maxLength(255)->placeholder('Optional'),
                    ])
                    ->columns(2)
                    ->addActionLabel('Add another mobility')
                    ->defaultItems(0)
                    ->minItems(fn (string $operation): ?int => $operation === 'create' ? 1 : null)
                    ->maxItems(10)
                    ->orderColumn('sort_order')
                    ->itemLabel(fn (array $state): string => $state['name'] ?: 'New mobility')
                    ->collapsible()
                    ->helperText('You can add up to 10 mobilities to one project. Add only the trips you will manage separately.')
                    ->columnSpanFull(),
            ]);
    }

    private static function approvalAndInvoice(): Section
    {
        return Section::make('Approval and invoice')
            ->description('This information is recorded once and is read-only for your project team.')
            ->visible(fn (string $operation): bool => $operation !== 'create')
            ->schema([
                Placeholder::make('approval_and_invoice_summary')
                    ->hiddenLabel()
                    ->content(function (?Project $record): HtmlString {
                        if (! $record?->hasDeclaredApprovedGrant()) {
                            return new HtmlString('<div class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700"><strong>Approval not recorded yet.</strong><br>When you receive the funding decision, use <strong>Mark as approved</strong> from Overview. The timeline opens immediately afterwards.</div>');
                        }

                        $status = e(Project::invoiceStatusOptions()[$record->invoice_status] ?? 'Pending');
                        $grant = number_format((float) $record->approved_grant_amount, 2, '.', ',').' €';
                        $fee = number_format((float) $record->activation_fee_amount, 2, '.', ',').' €';
                        $code = e($record->grant_ref ?: 'Not recorded');
                        $proof = e($record->approved_grant_proof_original_name ?: 'Not uploaded');
                        $invoice = e($record->invoice_number ?: 'Will be added when issued');

                        return new HtmlString("<div class=\"grid gap-3 md:grid-cols-2\"><div class=\"rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-950\"><strong>Approval recorded</strong><dl class=\"mt-2 space-y-1\"><div><dt class=\"inline text-emerald-700\">Approved grant:</dt> <dd class=\"inline font-semibold\">{$grant}</dd></div><div><dt class=\"inline text-emerald-700\">Project code:</dt> <dd class=\"inline\">{$code}</dd></div><div><dt class=\"inline text-emerald-700\">Document:</dt> <dd class=\"inline\">{$proof}</dd></div></dl></div><div class=\"rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-950\"><strong>Invoice and payment</strong><dl class=\"mt-2 space-y-1\"><div><dt class=\"inline text-blue-700\">Platform fee:</dt> <dd class=\"inline font-semibold\">{$fee}</dd></div><div><dt class=\"inline text-blue-700\">Status:</dt> <dd class=\"inline\">{$status}</dd></div><div><dt class=\"inline text-blue-700\">Invoice:</dt> <dd class=\"inline\">{$invoice}</dd></div></dl><p class=\"mt-3 text-blue-800\">You can continue managing the project while the invoice is being handled.</p></div></div>");
                    })
                    ->columnSpanFull(),
            ]);
    }
}
