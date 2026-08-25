<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Services\StoredFileReplacementService;
use App\Support\PlanCatalog;
use App\Support\PlatformAccess;
use App\Support\StoredFileReference;
use App\Support\StoredFileSwapResult;
use App\Support\UploadedFileSize;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\WithFileUploads;

class DocumentTemplates extends Page
{
    use WithFileUploads;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentDuplicate;

    protected static ?string $navigationLabel = 'Document templates';

    protected static string|\UnitEnum|null $navigationGroup = 'Account settings';

    protected static ?int $navigationSort = 35;

    protected static ?string $title = 'Document templates';

    protected string $view = 'filament.pages.document-templates';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public string $brandName = '';

    public string $legalName = '';

    public string $vatNumber = '';

    public string $legalAddress = '';

    public string $headerText = '';

    public string $footerText = '';

    public string $signatoryName = '';

    public string $signatoryRole = '';

    public string $accentColor = '#4f46e5';

    public $logo = null;

    public static function canAccess(): bool
    {
        return PlatformAccess::canUse(PlanCatalog::MODULE_DOCUMENTS)
            && auth()->check();
    }

    public function mount(): void
    {
        $user = auth()->user();
        $settings = $user?->document_settings ?? [];

        $this->brandName = (string) ($settings['brand_name'] ?? $user?->name ?? 'Organisation');
        $this->legalName = (string) ($settings['legal_name'] ?? $user?->name ?? 'Organisation');
        $this->vatNumber = (string) ($settings['vat_number'] ?? '');
        $this->legalAddress = (string) ($settings['legal_address'] ?? '');
        $this->headerText = (string) ($settings['header_text'] ?? 'Official project document');
        $this->footerText = (string) ($settings['footer_text'] ?? 'Generated with MobilityCloud');
        $this->signatoryName = (string) ($settings['signatory_name'] ?? '');
        $this->signatoryRole = (string) ($settings['signatory_role'] ?? 'Legal representative');
        $this->accentColor = (string) ($settings['accent_color'] ?? '#4f46e5');
    }

    public function getSubheading(): ?string
    {
        return 'Set the identity shared by generated applications, reports, attendance sheets and civil convention documents.';
    }

    public function save(): void
    {
        $user = auth()->user();
        abort_unless($user, 403);

        $data = $this->validate([
            'brandName' => ['required', 'string', 'max:120'],
            'legalName' => ['required', 'string', 'max:255'],
            'vatNumber' => ['nullable', 'string', 'max:80'],
            'legalAddress' => ['nullable', 'string', 'max:500'],
            'headerText' => ['nullable', 'string', 'max:160'],
            'footerText' => ['nullable', 'string', 'max:200'],
            'signatoryName' => ['nullable', 'string', 'max:120'],
            'signatoryRole' => ['nullable', 'string', 'max:120'],
            'accentColor' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
        ]);

        $updatedSettings = [
            'brand_name' => trim($data['brandName']),
            'legal_name' => trim($data['legalName']),
            'vat_number' => trim($data['vatNumber'] ?? ''),
            'legal_address' => trim($data['legalAddress'] ?? ''),
            'header_text' => trim($data['headerText'] ?? ''),
            'footer_text' => trim($data['footerText'] ?? ''),
            'signatory_name' => trim($data['signatoryName'] ?? ''),
            'signatory_role' => trim($data['signatoryRole'] ?? ''),
            'accent_color' => strtolower($data['accentColor']),
        ];

        if ($this->logo) {
            $upload = $this->logo;
            $extension = strtolower($upload->getClientOriginalExtension() ?: 'png');
            $directory = 'account-branding/'.$user->id.'/'.Str::uuid();
            $filename = 'logo.'.$extension;
            $path = $directory.'/'.$filename;

            app(StoredFileReplacementService::class)->replace(
                disk: 'local',
                path: $path,
                write: fn (): string|false => $upload->storeAs($directory, $filename, 'local'),
                swap: function (StoredFileReference $newFile) use ($user, $updatedSettings): StoredFileSwapResult {
                    $lockedUser = User::withTrashed()->whereKey($user->id)->lockForUpdate()->firstOrFail();
                    $settings = $lockedUser->document_settings ?? [];
                    $replacedFile = StoredFileReference::from('local', data_get($settings, 'logo_path'));
                    $lockedUser->forceFill([
                        'document_settings' => [
                            ...$settings,
                            ...$updatedSettings,
                            'logo_path' => $newFile->path,
                        ],
                    ])->save();

                    return new StoredFileSwapResult($lockedUser, $replacedFile);
                },
                expectedSize: UploadedFileSize::read($upload),
            );
        } else {
            $user->forceFill([
                'document_settings' => [
                    ...($user->document_settings ?? []),
                    ...$updatedSettings,
                ],
            ])->save();
        }

        $this->logo = null;
        Notification::make()->title('Document template updated')->success()->send();
    }

    public function removeLogo(): void
    {
        $user = auth()->user();
        abort_unless($user, 403);

        app(StoredFileReplacementService::class)->remove(function () use ($user): StoredFileSwapResult {
            $lockedUser = User::withTrashed()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $settings = $lockedUser->document_settings ?? [];
            $replacedFile = StoredFileReference::from('local', data_get($settings, 'logo_path'));
            unset($settings['logo_path']);
            $lockedUser->update(['document_settings' => $settings]);

            return new StoredFileSwapResult($lockedUser, $replacedFile);
        });
        Notification::make()->title('Document logo removed')->success()->send();
    }

    public function hasLogo(): bool
    {
        return filled(data_get(auth()->user()?->document_settings, 'logo_path'));
    }

    public function logoDataUri(): ?string
    {
        $path = data_get(auth()->user()?->document_settings, 'logo_path');

        if (! $path || ! Storage::disk('local')->exists($path)) {
            return null;
        }

        $mime = Storage::disk('local')->mimeType($path) ?: 'image/png';
        $data = base64_encode(Storage::disk('local')->get($path));

        return "data:{$mime};base64,{$data}";
    }
}
