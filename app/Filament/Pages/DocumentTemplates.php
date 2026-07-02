<?php

namespace App\Filament\Pages;

use App\Support\PlatformAccess;
use App\Support\PlanCatalog;
use BackedEnum;
use Filament\Facades\Filament;
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

    protected static string|\UnitEnum|null $navigationGroup = 'Workspace settings';

    protected static ?int $navigationSort = 35;

    protected static ?string $title = 'Document templates';

    protected string $view = 'filament.pages.document-templates';

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
            && (Filament::getTenant()?->canManageMembersBy(auth()->user()) ?? false);
    }

    public function mount(): void
    {
        $workspace = Filament::getTenant();
        $this->brandName = (string) $workspace->documentSetting('brand_name', $workspace->billing_name ?: $workspace->name);
        $this->legalName = (string) ($workspace->billing_name ?: $workspace->name);
        $this->vatNumber = (string) ($workspace->billing_vat ?: '');
        $this->legalAddress = (string) ($workspace->billing_address ?: '');
        $this->headerText = (string) $workspace->documentSetting('header_text', 'Official project document');
        $this->footerText = (string) $workspace->documentSetting('footer_text', 'Generated with MobilityCloud');
        $this->signatoryName = (string) $workspace->documentSetting('signatory_name', '');
        $this->signatoryRole = (string) $workspace->documentSetting('signatory_role', 'Legal representative');
        $this->accentColor = (string) $workspace->documentSetting('accent_color', '#4f46e5');
    }

    public function getSubheading(): ?string
    {
        return 'Set the identity shared by generated applications, reports, attendance sheets and civil convention documents.';
    }

    public function save(): void
    {
        $workspace = Filament::getTenant();
        abort_unless($workspace?->canManageMembersBy(auth()->user()), 403);
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

        if ($this->logo) {
            if ($workspace->document_logo_path) {
                Storage::disk('local')->delete($workspace->document_logo_path);
            }
            $workspace->document_logo_path = $this->logo->store('workspace-branding/'.$workspace->id, 'local');
        }
        $workspace->document_settings = [
            'brand_name' => trim($data['brandName']),
            'header_text' => trim($data['headerText'] ?? ''),
            'footer_text' => trim($data['footerText'] ?? ''),
            'signatory_name' => trim($data['signatoryName'] ?? ''),
            'signatory_role' => trim($data['signatoryRole'] ?? ''),
            'accent_color' => strtolower($data['accentColor']),
        ];
        $workspace->billing_name = trim($data['legalName']);
        $workspace->billing_vat = trim($data['vatNumber'] ?? '');
        $workspace->billing_address = trim($data['legalAddress'] ?? '');
        $workspace->save();
        $this->logo = null;
        Notification::make()->title('Document template updated')->success()->send();
    }

    public function removeLogo(): void
    {
        $workspace = Filament::getTenant();
        abort_unless($workspace?->canManageMembersBy(auth()->user()), 403);
        if ($workspace->document_logo_path) {
            Storage::disk('local')->delete($workspace->document_logo_path);
        }
        $workspace->update(['document_logo_path' => null]);
        Notification::make()->title('Document logo removed')->success()->send();
    }
}
