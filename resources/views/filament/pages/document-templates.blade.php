<x-filament-panels::page>
    <x-ui-polish />
    <style>
        .mc-template{display:grid;grid-template-columns:minmax(0,1fr) minmax(300px,.8fr);gap:1rem;align-items:start}.mc-template-form{display:grid;grid-template-columns:1fr 1fr;gap:.85rem}.mc-template-field label{display:block;margin-bottom:.3rem;color:#64748b;font-size:.66rem;font-weight:700;text-transform:uppercase}.mc-template-field input{width:100%;padding:.55rem .68rem;border:1px solid rgba(100,116,139,.28);border-radius:.5rem;background:transparent;font-size:.78rem}.mc-template-full{grid-column:1/-1}.mc-paper{background:white;color:#172033;min-height:430px;padding:2rem;box-shadow:0 12px 32px rgba(15,23,42,.12);border-radius:.25rem;font-family:Georgia,serif}.mc-paper-head{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding-bottom:.7rem;border-bottom:3px solid var(--accent)}.mc-paper-logo{max-width:100px;max-height:42px}.mc-paper-title{margin-top:2rem;font-size:1.25rem;font-weight:700}.mc-paper-line{height:7px;background:#e2e8f0;border-radius:4px;margin-top:.6rem}.mc-paper-footer{margin-top:7rem;padding-top:.55rem;border-top:1px solid #cbd5e1;color:#64748b;font:10px Arial,sans-serif}@media(max-width:900px){.mc-template{grid-template-columns:1fr}}@media(max-width:560px){.mc-template-form{grid-template-columns:1fr}.mc-template-full{grid-column:auto}}
    </style>
    <div class="mc-template">
        <x-filament::section heading="Document identity" description="Changes apply to newly generated PDFs; uploaded documents are not altered.">
            <div class="mc-template-form">
                <div class="mc-template-field mc-template-full"><label>Brand / organisation name</label><input wire:model.live.debounce.300ms="brandName" class="text-gray-950 dark:text-white">@error('brandName')<p style="color:#dc2626;font-size:.7rem;">{{ $message }}</p>@enderror</div>
                <div class="mc-template-field mc-template-full" style="padding-top:.3rem;border-top:1px solid rgba(100,116,139,.14);"><label>Legal organisation name</label><input wire:model="legalName" class="text-gray-950 dark:text-white">@error('legalName')<p style="color:#dc2626;font-size:.7rem;">{{ $message }}</p>@enderror</div>
                <div class="mc-template-field"><label>VAT / registration number</label><input wire:model="vatNumber" class="text-gray-950 dark:text-white" placeholder="Registration or VAT code"></div>
                <div class="mc-template-field"><label>Registered address</label><input wire:model="legalAddress" class="text-gray-950 dark:text-white" placeholder="Full legal address"></div>
                <div class="mc-template-field"><label>Header text</label><input wire:model.live.debounce.300ms="headerText" class="text-gray-950 dark:text-white"></div>
                <div class="mc-template-field"><label>Footer text</label><input wire:model.live.debounce.300ms="footerText" class="text-gray-950 dark:text-white"></div>
                <div class="mc-template-field"><label>Default signatory</label><input wire:model="signatoryName" class="text-gray-950 dark:text-white" placeholder="Full name"></div>
                <div class="mc-template-field"><label>Signatory role</label><input wire:model="signatoryRole" class="text-gray-950 dark:text-white" placeholder="Legal representative"></div>
                <div class="mc-template-field"><label>Accent colour</label><input type="color" wire:model.live="accentColor" style="height:38px;padding:.2rem;"></div>
                <div class="mc-template-field"><label>Logo (PNG or JPG)</label><input type="file" wire:model="logo" accept="image/png,image/jpeg" style="border:0;padding:.35rem 0;">@error('logo')<p style="color:#dc2626;font-size:.7rem;">{{ $message }}</p>@enderror</div>
                <div class="mc-template-full" style="display:flex;gap:.5rem;align-items:center;"><x-filament::button wire:click="save" wire:loading.attr="disabled" wire:target="save" icon="heroicon-o-check">Save template</x-filament::button>@if(\Filament\Facades\Filament::getTenant()->document_logo_path)<x-filament::button wire:click="removeLogo" wire:confirm="Remove the document logo?" color="gray" icon="heroicon-o-trash">Remove logo</x-filament::button>@endif</div>
            </div>
        </x-filament::section>

        <div class="mc-paper" style="--accent:{{ preg_match('/^#[0-9A-Fa-f]{6}$/',$accentColor) ? $accentColor : '#4f46e5' }}">
            <div class="mc-paper-head">
                <div>@if(\Filament\Facades\Filament::getTenant()->documentLogoDataUri())<img src="{{ \Filament\Facades\Filament::getTenant()->documentLogoDataUri() }}" class="mc-paper-logo">@else<strong style="color:var(--accent);font-family:Arial,sans-serif;">{{ $brandName ?: 'Organisation' }}</strong>@endif</div>
                <span style="color:#64748b;font:10px Arial,sans-serif;">{{ $headerText }}</span>
            </div>
            <div class="mc-paper-title">Project Document</div>
            <div style="font:11px Arial,sans-serif;color:#64748b;margin-top:.3rem;">Project name · Reference number</div>
            @foreach([92,76,88,65,82] as $width)<div class="mc-paper-line" style="width:{{ $width }}%;"></div>@endforeach
            <div class="mc-paper-footer">{{ $footerText ?: 'Generated with MobilityCloud' }} <span style="float:right;">Page 1</span></div>
        </div>
    </div>
</x-filament-panels::page>
