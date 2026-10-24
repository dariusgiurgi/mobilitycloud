@once
<style>
    .fi-panel-platform {
        --mc-canvas: #f4f6fa; --mc-surface: #fff; --mc-line: #e4e9f1;
        --mc-ink: #192640; --mc-muted: #66748b; --mc-accent: #4f46e5;
        background: var(--mc-canvas); color: var(--mc-ink);
    }
    .dark .fi-panel-platform {
        --mc-canvas: #101722; --mc-surface: #182231; --mc-line: #2c384b;
        --mc-ink: #edf2fc; --mc-muted: #a0afc4; --mc-accent: #a5b4fc;
    }
    .fi-panel-platform .fi-topbar { background: var(--mc-surface); box-shadow: none; border-bottom: 1px solid var(--mc-line); }
    .fi-panel-platform .fi-main { padding-top: 1.8rem; padding-bottom: 2.5rem; }
    .fi-panel-platform .fi-header-heading { font-size: clamp(1.65rem, 2.3vw, 2rem); letter-spacing: -.035em; color: var(--mc-ink); line-height: 1.2; }
    .fi-panel-platform .fi-header-subheading { max-width: 60rem; color: var(--mc-muted); font-size: .875rem; line-height: 1.65; }
    .fi-panel-platform .fi-breadcrumbs { font-size: .75rem; }
    .fi-panel-platform .fi-page-content { gap: 1.5rem; }
    .fi-panel-platform .fi-main-sidebar { background: #142039; border-right: 1px solid #23324c; }
    .fi-panel-platform .fi-sidebar-header { background: #142039; box-shadow: none; border-bottom: 1px solid #29364d; }
    .fi-panel-platform .fi-sidebar-header .fi-logo { color: #fff; font-size: 1.1rem; letter-spacing: -.025em; }
    .fi-panel-platform .fi-sidebar-nav { padding: 1.25rem .85rem; gap: 1.65rem; overflow-y: auto; scrollbar-width: thin; scrollbar-color: #455674 #142039; }
    .fi-panel-platform .fi-sidebar-nav-groups { gap: 1.65rem; }
    .fi-panel-platform .fi-sidebar-group-label { color: #99a9c3; font-size: .64rem; letter-spacing: .1em; text-transform: uppercase; font-weight: 700; }
    .fi-panel-platform .fi-sidebar-group-btn { padding-inline: .7rem; margin-bottom: .4rem; }
    .fi-panel-platform .fi-sidebar-group-items { gap: .25rem; }
    .fi-panel-platform .fi-sidebar-item-btn { border-radius: .65rem; min-height: 2.65rem; padding: .7rem; gap: .75rem; transition: background .15s; }
    .fi-panel-platform .fi-sidebar-item-label { color: #c6d0e2; font-size: .825rem; font-weight: 500; }
    .fi-panel-platform .fi-sidebar-item .fi-icon { color: #95a6c3; width: 1.2rem; height: 1.2rem; }
    .fi-panel-platform .fi-sidebar-item-btn:hover { background: #22314c; }
    .fi-panel-platform .fi-sidebar-item.fi-active > .fi-sidebar-item-btn { background: #343d75; box-shadow: inset 3px 0 0 #a5b4fc; }
    .fi-panel-platform .fi-sidebar-item.fi-active .fi-sidebar-item-label,
    .fi-panel-platform .fi-sidebar-item.fi-active .fi-icon { color: #fff; }
    .fi-panel-platform .fi-sidebar-header .fi-icon-btn .fi-icon { color: #c6d0e2; }
    .fi-panel-platform .fi-sidebar-footer { border-top: 1px solid #29364d; }
    .fi-panel-platform .fi-section, .fi-panel-platform .fi-ta-ctn, .fi-panel-platform .fi-wi-stats-overview-stat {
        background: var(--mc-surface); border-radius: 1rem; box-shadow: 0 3px 14px rgb(20 32 57 / 3%); border: 1px solid var(--mc-line); outline: none;
    }
    .fi-panel-platform .fi-section-header { padding: 1.25rem 1.5rem; }
    .fi-panel-platform .fi-section-header-heading { font-size: .95rem; letter-spacing: -.015em; }
    .fi-panel-platform .fi-section-header-description { color: var(--mc-muted); font-size: .8rem; line-height: 1.55; }
    .fi-panel-platform .fi-input-wrp { border-radius: .6rem; box-shadow: none; border: 1px solid var(--mc-line); }
    .fi-panel-platform .fi-input-wrp.fi-invalid { border-color: #dc2626; }
    .fi-panel-platform .fi-input-wrp:focus-within { border-color: var(--mc-accent); outline: 2px solid color-mix(in srgb, var(--mc-accent) 15%, transparent); }
    .fi-panel-platform .fi-btn { border-radius: .6rem; font-weight: 600; }
    .fi-panel-platform .fi-badge { border-radius: .4rem; font-weight: 600; }
    .fi-panel-platform .fi-ta-header-toolbar { padding: 1rem 1.25rem; gap: .75rem; }
    .fi-panel-platform .fi-ta-search-field { width: min(100%, 26rem); }
    .fi-panel-platform .fi-resource-platform-users .fi-ta-header-toolbar > .fi-ta-actions:not(:has(button, a)) { display: none; }
    .fi-panel-platform .fi-resource-platform-users .fi-ta-header-toolbar > div:has(> .fi-ta-search-field) { flex: 1; min-width: 0; margin-inline-start: 0; }
    .fi-panel-platform .fi-resource-platform-users .fi-ta-search-field { flex: 1; width: 100%; max-width: 30rem; margin-inline-end: auto; }
    .fi-panel-platform .fi-ta-header-cell { background: var(--mc-canvas); padding-block: .85rem; }
    .fi-panel-platform .fi-ta-header-cell-label { color: var(--mc-muted); font-size: .7rem; font-weight: 650; }
    .fi-panel-platform .fi-ta-cell { padding-block: .95rem; }
    .fi-panel-platform .fi-ta-row:hover { background: color-mix(in srgb, var(--mc-accent) 3%, var(--mc-surface)); }
    .fi-panel-platform .fi-ta-text-item-description { color: var(--mc-muted); font-size: .75rem; margin-top: .2rem; }
    .fi-panel-platform .fi-pagination { padding: 1rem 1.25rem; }
    .fi-panel-platform .fi-wi-stats-overview-stat { padding: 1.4rem; }
    .fi-panel-platform .fi-wi-stats-overview-stat-value { font-size: 2rem; letter-spacing: -.045em; color: var(--mc-ink); }
    .fi-panel-platform .fi-wi-stats-overview-stat-label { color: var(--mc-muted); font-size: .8rem; }
    .fi-panel-platform .fi-wi-stats-overview-stat-description { font-size: .75rem; }
    .fi-panel-platform .fi-resource-list-records-page.fi-resource-platform-users .fi-tabs {
        display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: .75rem;
        width: 100%; padding: 0; border: 0; background: transparent; box-shadow: none; overflow: visible;
    }
    .fi-panel-platform .fi-resource-list-records-page.fi-resource-platform-users .fi-tabs-item {
        display: grid; grid-template-columns: 1fr auto; gap: .85rem .35rem; padding: 1rem;
        min-width: 0; border: 1px solid var(--mc-line); border-radius: .85rem; background: var(--mc-surface);
        text-align: left; box-shadow: 0 2px 6px rgb(20 32 57 / 2%); transition: border-color .15s, background .15s;
    }
    .fi-panel-platform .fi-resource-list-records-page.fi-resource-platform-users .fi-tabs-item > .fi-icon { grid-row: 1; grid-column: 1; color: var(--mc-muted); width: 1.25rem; height: 1.25rem; }
    .fi-panel-platform .fi-resource-list-records-page.fi-resource-platform-users .fi-tabs-item > .fi-badge { grid-row: 1; grid-column: 2; padding: 0; background: transparent; box-shadow: none; font-size: 1.2rem; color: var(--mc-ink); font-weight: 700; }
    .fi-panel-platform .fi-resource-list-records-page.fi-resource-platform-users .fi-tabs-item-label { grid-row: 2; grid-column: 1 / -1; white-space: normal; color: var(--mc-muted); font-size: .78rem; }
    .fi-panel-platform .fi-resource-list-records-page.fi-resource-platform-users .fi-tabs-item.fi-active { background: color-mix(in srgb, var(--mc-accent) 7%, var(--mc-surface)); border-color: var(--mc-accent); box-shadow: 0 0 0 1px var(--mc-accent); }
    .fi-panel-platform .fi-resource-list-records-page.fi-resource-platform-users .fi-tabs-item.fi-active > .fi-icon,
    .fi-panel-platform .fi-resource-list-records-page.fi-resource-platform-users .fi-tabs-item.fi-active .fi-tabs-item-label { color: var(--mc-accent); }
    .fi-panel-platform .fi-tabs-item:focus-visible { outline: 2px solid var(--mc-accent); outline-offset: 3px; }
    .mc-account-person { display: flex; align-items: center; gap: .8rem; padding: .2rem 0; }
    .mc-account-avatar { display: grid; place-items: center; flex: none; width: 2.4rem; height: 2.4rem; border-radius: .7rem; background: color-mix(in srgb, var(--mc-accent) 10%, var(--mc-surface)); color: var(--mc-accent); font-size: .75rem; font-weight: 750; }
    .mc-account-identity { min-width: 0; }
    .mc-account-name { color: var(--mc-ink); font-size: .85rem; font-weight: 650; }
    .mc-account-email, .mc-account-org { color: var(--mc-muted); font-size: .73rem; margin-top: .15rem; overflow-wrap: anywhere; }
    .mc-account-org { font-size: .68rem; }
    .mc-admin-context { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .6rem 1.5rem; background: var(--mc-surface); border-bottom: 1px solid var(--mc-line); font-size: .7rem; color: var(--mc-muted); }
    .mc-admin-context strong { color: var(--mc-ink); font-weight: 650; }
    .mc-admin-context span { display: inline-flex; align-items: center; gap: .5rem; }
    .mc-admin-context svg { width: 1rem; height: 1rem; color: var(--mc-accent); }
    .mc-admin-context-role { border: 1px solid var(--mc-line); border-radius: 999px; padding: .2rem .6rem; font-size: .65rem; }
    .mc-admin-context-warning { color: #b45309; font-weight: 700; }
    .fi-panel-platform .mc-platform-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .fi-panel-platform .mc-platform-card { padding: 1.2rem; border-color: var(--mc-line); border-radius: .8rem; }
    .fi-panel-platform .mc-platform-title { font-size: .85rem; }
    .fi-panel-platform .mc-platform-muted { color: var(--mc-muted); font-size: .76rem; }
    .fi-panel-platform .mc-platform-link { background: var(--mc-canvas); border-color: var(--mc-line); padding: .8rem; }
    .fi-panel-platform .mc-platform-row { align-items: center; padding: .9rem 0; }
    @media (max-width: 1279px) {
        .fi-panel-platform .fi-resource-list-records-page.fi-resource-platform-users .fi-tabs { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }
    @media (max-width: 639px) {
        .fi-panel-platform .fi-main { padding-top: 1.25rem; }
        .fi-panel-platform .fi-resource-list-records-page.fi-resource-platform-users .fi-tabs { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .6rem; }
        .fi-panel-platform .fi-resource-list-records-page.fi-resource-platform-users .fi-tabs-item { padding: .8rem; }
        .fi-panel-platform .mc-platform-grid { grid-template-columns: 1fr; }
        .fi-panel-platform .fi-ta-search-field { width: 100%; }
        .fi-panel-platform .fi-ta-header-toolbar { flex-wrap: wrap; }
        .fi-panel-platform .fi-resource-platform-users .fi-ta-header-toolbar > div:has(> .fi-ta-search-field) { flex-wrap: wrap; gap: .75rem; }
        .fi-panel-platform .fi-resource-platform-users .fi-ta-search-field { flex-basis: 100%; max-width: none; }
        .mc-admin-context { padding: .55rem 1rem; }
        .mc-admin-context-detail { display: none !important; }
    }
    @media (prefers-reduced-motion: reduce) {
        .fi-panel-platform .fi-sidebar-item-btn, .fi-panel-platform .fi-tabs-item { transition: none; }
    }
</style>
@endonce
