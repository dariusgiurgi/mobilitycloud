@once
    <style>
        .mc-modal-backdrop { position:fixed;inset:0;z-index:60;display:flex;align-items:center;justify-content:center;padding:1rem;background:rgba(15,23,42,.58);backdrop-filter:blur(2px); }
        .mc-modal-backdrop.mc-modal-top { align-items:flex-start;overflow-y:auto;padding-top:2.5rem;padding-bottom:2.5rem; }
        .mc-modal-panel { width:min(520px,100%);max-height:calc(100vh - 2rem);overflow-y:auto;border:1px solid rgba(148,163,184,.22);border-radius:.9rem;background:#fff;color:#18181b;box-shadow:0 24px 70px rgba(15,23,42,.28); }
        .mc-modal-panel-wide { width:min(760px,100%); }
        .mc-modal-body { padding:1.35rem; }
        .mc-modal-heading { margin:0;color:#18181b;font-size:1rem;font-weight:700;line-height:1.35; }
        .mc-modal-description { margin:.3rem 0 1.1rem;color:#64748b;font-size:.75rem;line-height:1.55; }
        .mc-modal-actions { display:flex;align-items:center;justify-content:flex-end;gap:.5rem;margin-top:1.25rem;flex-wrap:wrap; }
        .mc-table-scroll { overflow-x:auto;-webkit-overflow-scrolling:touch;scrollbar-gutter:stable; }
        .mc-empty-state { padding:2.5rem 1.25rem;text-align:center; }
        .mc-live-status { position:fixed;right:1rem;bottom:1rem;z-index:90;display:inline-flex;align-items:center;gap:.45rem;padding:.55rem .75rem;border:1px solid rgba(99,102,241,.22);border-radius:9999px;background:#fff;color:#4f46e5;box-shadow:0 10px 30px rgba(15,23,42,.14);font-size:.7rem;font-weight:650; }
        .mc-live-status-dot { width:7px;height:7px;border:2px solid rgba(99,102,241,.3);border-top-color:#4f46e5;border-radius:9999px;animation:mc-spin .7s linear infinite; }
        .dark .mc-modal-panel,.dark .mc-live-status { background:#18212f;color:#f4f4f5;border-color:rgba(255,255,255,.12); }
        .dark .mc-modal-heading { color:#f8fafc; }
        .dark .mc-modal-description { color:#94a3b8; }
        @keyframes mc-spin { to { transform:rotate(360deg); } }
        @media (max-width:640px) {
            .mc-modal-backdrop,.mc-modal-backdrop.mc-modal-top { align-items:flex-end;padding:.5rem; }
            .mc-modal-panel,.mc-modal-panel-wide { width:100%;max-height:calc(100vh - 1rem);border-radius:.85rem; }
            .mc-modal-body { padding:1.05rem; }
            .mc-modal-actions > * { flex:1;justify-content:center; }
            .mc-live-status { right:.65rem;bottom:.65rem; }
        }
    </style>
@endonce

<div wire:loading.delay.longer class="mc-live-status" role="status" aria-live="polite">
    <span class="mc-live-status-dot"></span>
    Updating…
</div>
