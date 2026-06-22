@php($docAccent = ($workspace ?? $project->workspace ?? null)?->documentSetting('accent_color', '#4f46e5') ?: '#4f46e5')
@page { margin: 72px 38px 54px; }
.mc-doc-header {
    position: fixed;
    top: -54px;
    left: 0;
    right: 0;
    height: 42px;
    border-bottom: 2px solid {{ $docAccent }};
}
.mc-doc-brand {
    position: absolute;
    top: 0;
    left: 0;
    color: {{ $docAccent }};
    font-size: 10px;
    font-weight: bold;
    letter-spacing: .08em;
    text-transform: uppercase;
}
.mc-doc-logo { max-height: 30px; max-width: 130px; }
.mc-doc-context {
    position: absolute;
    top: 0;
    right: 0;
    color: #64748b;
    font-size: 8px;
    text-align: right;
}
.mc-doc-title { margin-bottom: 18px; }
.mc-doc-title h1 { color: #1e293b; }
.mc-doc-footer {
    position: fixed;
    bottom: -38px;
    left: 0;
    right: 0;
    border-top: 1px solid #e2e8f0;
    padding-top: 7px;
    color: #94a3b8;
    font-size: 8px;
}
.mc-doc-footer-left { position: absolute; left: 0; }
.mc-doc-footer-right { position: absolute; right: 0; }
.mc-doc-page:after { content: counter(page); }
