@php
    $docBrand = $workspace?->documentSetting('brand_name', $workspace?->billing_name ?: $workspace?->name ?: 'MobilityCloud') ?: 'MobilityCloud';
    $docHeader = $workspace?->documentSetting('header_text', $context ?? 'Project document') ?: ($context ?? 'Project document');
    $docFooter = $workspace?->documentSetting('footer_text', 'Generated with MobilityCloud') ?: 'Generated with MobilityCloud';
    $docLogo = $workspace?->documentLogoDataUri();
@endphp
<div class="mc-doc-header">
    <span class="mc-doc-brand">@if($docLogo)<img src="{{ $docLogo }}" class="mc-doc-logo" alt="{{ $docBrand }}">@else{{ $docBrand }}@endif</span>
    <span class="mc-doc-context">{{ $docHeader }} · {{ $context ?? 'Project document' }}</span>
</div>
<div class="mc-doc-footer">
    <span class="mc-doc-footer-left">{{ $footerLeft ?? $docFooter }}</span>
    <span class="mc-doc-footer-right">Page <span class="mc-doc-page"></span></span>
</div>
