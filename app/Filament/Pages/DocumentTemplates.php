<?php

namespace App\Filament\Pages;

use App\Support\PlatformAccess;
use App\Support\PlanCatalog;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
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

        $settings = $user->document_settings ?? [];

        if ($this->logo) {
            if (! empty($settings['logo_path'])) {
                Storage::disk('local')->delete($settings['logo_path']);
            }
            $settings['logo_path'] = $this->logo->store('account-branding/'.$user->id, 'local');
        }

        $user->document_settings = [
            ...$settings,
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
        $user->save();
        $this->logo = null;
        Notification::make()->title('Document template updated')->success()->send();
    }

    public function removeLogo(): void
    {
        $user = auth()->user();
        abort_unless($user, 403);

        $settings = $user->document_settings ?? [];
        if (! empty($settings['logo_path'])) {
            Storage::disk('local')->delete($settings['logo_path']);
        }
        unset($settings['logo_path']);
        $user->update(['document_settings' => $settings]);
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
