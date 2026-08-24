<style>
    .mc-project-start-section .fi-section { border:0; border-radius:1.75rem; background:linear-gradient(135deg,#f8fbff 0%,#f5f3ff 100%); box-shadow:0 14px 36px rgb(79 70 229 / 8%); }
    .mc-project-start-section .fi-section-content-ctn { padding-top:.25rem; }
    .mc-project-entry-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:1rem; }
    .mc-project-entry-card { display:grid; grid-template-columns:auto 1fr auto; align-items:center; gap:.9rem; width:100%; min-height:8.5rem; padding:1.2rem; text-align:left; color:#243047; border:1px solid rgb(148 163 184 / 18%); border-radius:1.4rem; background:rgb(255 255 255 / 78%); box-shadow:0 4px 14px rgb(15 23 42 / 4%); cursor:pointer; transition:transform .16s ease,box-shadow .16s ease,border-color .16s ease,background .16s ease; }
    .mc-project-entry-card:hover { transform:translateY(-2px); border-color:rgb(99 102 241 / 45%); box-shadow:0 14px 24px rgb(79 70 229 / 13%); }
    .mc-project-entry-card.is-selected { color:#312e81; border-color:transparent; background:linear-gradient(135deg,#e0e7ff 0%,#f5f3ff 100%); box-shadow:0 14px 26px rgb(79 70 229 / 19%); }
    .mc-project-entry-card-approved.is-selected { color:#065f46; background:linear-gradient(135deg,#d1fae5 0%,#ecfdf5 100%); box-shadow:0 14px 26px rgb(5 150 105 / 16%); }
    .mc-project-entry-icon { display:grid; place-items:center; width:3rem; height:3rem; border-radius:1rem; font-size:1.35rem; background:rgb(255 255 255 / 78%); }
    .mc-project-entry-copy { display:grid; gap:.3rem; }
    .mc-project-entry-copy strong { font-size:1rem; font-weight:800; }
    .mc-project-entry-copy small { color:#64748b; font-size:.8rem; line-height:1.4; }
    .mc-project-entry-arrow { color:#6366f1; font-size:1.35rem; transition:transform .16s ease; }
    .mc-project-entry-card:hover .mc-project-entry-arrow,.mc-project-entry-card.is-selected .mc-project-entry-arrow { transform:translateX(3px); }
    .mc-project-settings-section .fi-section { border:0; border-radius:1.5rem; background:rgb(248 250 252 / 72%); box-shadow:none; }
    .mc-project-settings-section .fi-section-header { padding-bottom:.5rem; }
    .mc-project-settings-section .fi-section-content-ctn { padding-top:.35rem; }
    .mc-project-details-section .fi-section { background:linear-gradient(135deg,#f8fbff 0%,#f8fafc 100%); }
    .mc-project-timeline-section .fi-section { background:linear-gradient(135deg,#f0fdfa 0%,#f8fafc 100%); }
    .mc-project-approval-section .fi-section,.mc-project-readonly-section .fi-section { background:linear-gradient(135deg,#fffbeb 0%,#f8fafc 100%); }
    @media (max-width:640px) { .mc-project-entry-grid { grid-template-columns:1fr; } }
</style>
