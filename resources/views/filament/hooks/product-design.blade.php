<style>
    .fi-panel-admin {
        --mc-surface: #fff;
        --mc-border: #e2e8f0;
        --mc-muted: #526175;
        --mc-accent: #4f46e5;
        --mc-radius: .9rem;
        background: #f6f8fc;
    }
    .dark .fi-panel-admin { --mc-surface: #111827; --mc-border: #334155; --mc-muted: #a8b5c7; --mc-accent: #a5b4fc; background: #0f172a; }
    .fi-panel-admin .fi-page-main, .fi-panel-admin .fi-page-content { min-width: 0; }
    .fi-panel-admin .fi-page-content { gap: 1.5rem; }
    .fi-panel-admin .fi-header-heading { letter-spacing: -.025em; overflow-wrap: anywhere; }
    .fi-panel-admin .fi-header-subheading { font-size: .875rem; line-height: 1.6; }
    .fi-panel-admin .fi-section, .fi-panel-admin .fi-ta-ctn { min-width: 0; max-width: 100%; border-radius: var(--mc-radius); }
    .fi-panel-admin .fi-section-header-text-ctn { min-width: 0; }
    .fi-panel-admin .fi-section-header-description, .fi-panel-admin .fi-fo-field-wrp-helper-text { font-size: .875rem; line-height: 1.6; }
    .fi-panel-admin .fi-btn { min-height: 2.5rem; border-radius: .65rem; }
    .fi-panel-admin :is(.mc-muted, .mc-overview-muted) { color: var(--mc-muted); }
    .fi-panel-admin :is(.mc-project-card, .mc-overview-card, .mc-focus-card) { background: var(--mc-surface); border-color: var(--mc-border); border-radius: var(--mc-radius); }
    .fi-panel-admin :is(a, button, select, input, textarea):focus-visible { outline: 2px solid var(--mc-accent); outline-offset: 3px; }
    .fi-panel-admin .fi-page-sub-navigation-tabs { width: 100%; max-width: 100%; flex-wrap: wrap; gap: .25rem; padding: .4rem; border-radius: var(--mc-radius); background: var(--mc-surface); }
    .fi-panel-admin .fi-page-sub-navigation-tabs .fi-tabs-item { flex: 1 0 auto; min-height: 2.75rem; justify-content: center; border-radius: .6rem; }
    .fi-panel-admin .fi-page-sub-navigation-tabs .fi-tabs-item.fi-active { box-shadow: inset 0 -2px var(--mc-accent); }
    .fi-panel-admin .mc-task-check { width: 2rem; height: 2rem; }
    @media (max-width: 767px) {
        .fi-panel-admin .fi-page-header-main-ctn { gap: 1.25rem; padding-block: 1.25rem; }
        .fi-panel-admin .fi-page-sub-navigation-dropdown > .fi-dropdown-trigger > .fi-btn { min-height: 3rem; border-color: var(--mc-accent); }
        .fi-panel-admin .mc-focus-head > .fi-btn { width: 100%; }
    }
    @media (prefers-reduced-motion: reduce) {
        .fi-panel-admin :is(.mc-project-card, .mc-overview-card, .mc-final-row) { transition: none; transform: none; }
    }
</style>
