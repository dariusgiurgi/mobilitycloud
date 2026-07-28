@php
    $page = $page ?? 'home';
    $emails = $emails ?? config('mobilitycloud.emails');
    $company = $company ?? config('mobilitycloud.company');
    $xeotypeUrl = 'https://xeotype.com';
    $platformUrl = url('/app/login');
    $registerUrl = url('/app/register');

    $titles = [
        'home' => 'MobilityCloud · Erasmus+ project management platform',
        'features' => 'Features · MobilityCloud',
        'pricing' => 'Pricing · MobilityCloud',
        'guide' => 'Guide · MobilityCloud',
        'help' => 'Help Center · MobilityCloud',
        'contact' => 'Contact · MobilityCloud',
    ];

    $descriptions = [
        'home' => 'MobilityCloud helps Erasmus+ teams write, approve, manage and archive mobility projects in one structured platform.',
        'features' => 'Explore the MobilityCloud modules for writing, budget, participants, documents, mobility evidence and collaboration.',
        'pricing' => 'Use MobilityCloud before approval and pay the administration fee only after your project is approved.',
        'guide' => 'A practical guide for starting, writing, approving and managing your Erasmus+ mobility project in MobilityCloud.',
        'help' => 'Answers to the most common MobilityCloud account, billing, project, participant, document and mobility questions.',
        'contact' => 'Contact MobilityCloud for support, billing and partnership questions.',
    ];

    $nav = [
        'features' => ['label' => 'Features', 'url' => route('marketing.features')],
        'pricing' => ['label' => 'Pricing', 'url' => route('marketing.pricing')],
        'guide' => ['label' => 'Guide', 'url' => route('marketing.guide')],
        'help' => ['label' => 'Help', 'url' => route('marketing.help')],
        'contact' => ['label' => 'Contact', 'url' => route('marketing.contact')],
    ];

    $modules = [
        ['icon' => '✍️', 'title' => 'Writing', 'body' => 'Official-style application sections, writing library and exports.'],
        ['icon' => '💶', 'title' => 'Budget', 'body' => 'Budget baskets, expenses, evidence files and clear spending status.'],
        ['icon' => '👥', 'title' => 'Participants', 'body' => 'Manual entry, CSV import and public self-registration links.'],
        ['icon' => '📁', 'title' => 'Documents', 'body' => 'Generated records, signed copies and visual file management.'],
        ['icon' => '🧭', 'title' => 'Mobility', 'body' => 'Daily evidence, outputs, dissemination reports and media links.'],
        ['icon' => '✅', 'title' => 'Tasks', 'body' => 'Project priorities, assignments and readiness reminders.'],
    ];

    $featureRows = [
        [
            'eyebrow' => 'Application writing',
            'title' => 'Write the project with the right structure from the beginning.',
            'body' => 'MobilityCloud keeps application sections, guidance, tables and exports together so the writing phase does not become a maze of files.',
            'points' => ['Official-style sections', 'Writing library', 'PDF and Word exports'],
            'preview' => 'writing',
        ],
        [
            'eyebrow' => 'Implementation management',
            'title' => 'After approval, move from writing to delivery without rebuilding everything.',
            'body' => 'Budgets, participants, documents and mobility evidence stay connected to the same project record.',
            'points' => ['Budget baskets', 'Participant register', 'Evidence by day'],
            'preview' => 'budget',
        ],
        [
            'eyebrow' => 'Final archive',
            'title' => 'Keep proof, documents and outputs organised for final reporting.',
            'body' => 'The platform is designed around the practical evidence Erasmus+ teams need when projects become operational.',
            'points' => ['Signed documents', 'Photos and links', 'Materials and outputs'],
            'preview' => 'mobility',
        ],
    ];

    $guideSteps = [
        ['step' => '01', 'title' => 'Create your account', 'body' => 'Verify your email and complete billing details before creating your own projects.'],
        ['step' => '02', 'title' => 'Start a project', 'body' => 'Create an application from a template or start manually for already-approved projects.'],
        ['step' => '03', 'title' => 'Write and review', 'body' => 'Use the writing workspace, library, readiness checks and exports before submission.'],
        ['step' => '04', 'title' => 'Declare approval', 'body' => 'When the project is approved, declare the exact approved grant amount.'],
        ['step' => '05', 'title' => 'Manage implementation', 'body' => 'Unlock budget, participants, documents, mobility evidence and task workflows.'],
        ['step' => '06', 'title' => 'Prepare final files', 'body' => 'Keep documents, proof and outputs organised for final reporting and archiving.'],
    ];

    $faq = [
        ['q' => 'Can I use MobilityCloud before my project is approved?', 'a' => 'Yes. The writing, planning and support tools are designed to help before approval. The administration fee applies after approval.'],
        ['q' => 'When do I pay?', 'a' => 'After you mark a project as approved and declare the approved grant amount. MobilityCloud issues a manual fiscal invoice.'],
        ['q' => 'What is the administration fee?', 'a' => 'The current model is 1% of the approved grant, with a minimum fee of €100 per approved project.'],
        ['q' => 'Can collaborators access only one project?', 'a' => 'Yes. Invitations are project-based, and access can be limited by role, including view-only and mobility-focused access.'],
        ['q' => 'Is MobilityCloud an official Erasmus+ tool?', 'a' => 'No. MobilityCloud is an independent project management platform that helps organise Erasmus+ workflows. Official decisions remain with the National Agency and programme rules.'],
        ['q' => 'Who develops MobilityCloud?', 'a' => 'MobilityCloud is powered by Xeotype, the company behind the platform design, development and operations.'],
    ];
@endphp

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $titles[$page] ?? $titles['home'] }}</title>
    <meta name="description" content="{{ $descriptions[$page] ?? $descriptions['home'] }}">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:site_name" content="MobilityCloud">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $titles[$page] ?? $titles['home'] }}">
    <meta property="og:description" content="{{ $descriptions[$page] ?? $descriptions['home'] }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('brand/mobilitycloud-logo-powered-xeotype.png') }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $titles[$page] ?? $titles['home'] }}">
    <meta name="twitter:description" content="{{ $descriptions[$page] ?? $descriptions['home'] }}">
    <meta name="twitter:image" content="{{ asset('brand/mobilitycloud-logo-powered-xeotype.png') }}">
    <link rel="icon" type="image/png" href="{{ asset('brand/favicon-64.png') }}">
    <style>
        :root {
            color-scheme: light;
            --mc-blue:#123469;
            --mc-blue-2:#1d4ed8;
            --mc-cyan:#38d5ff;
            --mc-indigo:#4f46e5;
            --mc-indigo-dark:#4338ca;
            --mc-ink:#07111f;
            --mc-muted:#64748b;
            --mc-soft:#f6f8fc;
            --mc-line:#e2e8f0;
            --mc-card:#ffffff;
            --mc-success:#059669;
            --mc-warning:#f59e0b;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        * { box-sizing:border-box; }
        html { scroll-behavior:smooth; }
        body {
            margin:0;
            color:var(--mc-ink);
            background:
                radial-gradient(circle at 5% -10%, rgba(56,213,255,.28), transparent 27rem),
                radial-gradient(circle at 92% 4%, rgba(79,70,229,.18), transparent 28rem),
                linear-gradient(180deg, #fbfdff 0%, #f7f9fd 52%, #ffffff 100%);
            -webkit-font-smoothing:antialiased;
            overflow-x:hidden;
        }

        a { color:inherit; }
        img { max-width:100%; display:block; }
        .mc-shell { width:min(1180px, calc(100% - 36px)); margin:0 auto; }
        .mc-header {
            position:sticky;
            top:0;
            z-index:20;
            border-bottom:1px solid rgba(226,232,240,.74);
            background:rgba(255,255,255,.82);
            backdrop-filter:blur(18px);
        }
        .mc-nav {
            height:74px;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:1rem;
        }
        .mc-brand { display:flex; align-items:center; text-decoration:none; min-width:0; }
        .mc-brand img { width:236px; height:auto; }
        .mc-links { display:flex; align-items:center; gap:.4rem; }
        .mc-links a {
            text-decoration:none;
            color:#475569;
            font-size:.88rem;
            font-weight:720;
            padding:.55rem .7rem;
            border-radius:999px;
        }
        .mc-links a:hover, .mc-links a.is-active { color:var(--mc-indigo); background:#eef2ff; }
        .mc-actions { display:flex; align-items:center; gap:.55rem; }
        .mc-btn {
            display:inline-flex;
            align-items:center;
            justify-content:center;
            gap:.45rem;
            min-height:42px;
            padding:.72rem 1rem;
            border-radius:14px;
            border:1px solid transparent;
            text-decoration:none;
            font-weight:850;
            font-size:.9rem;
            line-height:1;
            transition:.16s transform,.16s box-shadow,.16s background,.16s border-color;
        }
        .mc-btn:hover { transform:translateY(-1px); }
        .mc-btn.primary { color:white; background:linear-gradient(135deg,var(--mc-indigo),#315eff); box-shadow:0 15px 34px rgba(79,70,229,.24); }
        .mc-btn.primary:hover { background:linear-gradient(135deg,var(--mc-indigo-dark),#2450e9); }
        .mc-btn.ghost { color:#334155; background:white; border-color:rgba(148,163,184,.28); }
        .mc-btn.ghost:hover { border-color:rgba(79,70,229,.35); box-shadow:0 10px 24px rgba(15,23,42,.06); }
        .mc-btn.subtle { color:var(--mc-indigo); background:#eef2ff; }
        .mc-mobile-link { display:none; }

        .mc-hero {
            padding:72px 0 54px;
            display:grid;
            grid-template-columns:minmax(0,1.04fr) minmax(420px,.96fr);
            gap:2.4rem;
            align-items:center;
        }
        .mc-kicker {
            display:inline-flex;
            align-items:center;
            gap:.5rem;
            width:max-content;
            max-width:100%;
            min-width:0;
            padding:.48rem .72rem;
            border:1px solid rgba(79,70,229,.16);
            border-radius:999px;
            background:rgba(238,242,255,.82);
            color:var(--mc-indigo);
            font-size:.75rem;
            font-weight:900;
            text-transform:uppercase;
            letter-spacing:.075em;
        }
        .mc-kicker::before {
            content:"";
            width:.52rem;
            height:.52rem;
            flex:none;
            border-radius:999px;
            background:linear-gradient(135deg,var(--mc-cyan),var(--mc-indigo));
            box-shadow:0 0 0 5px rgba(56,213,255,.12);
        }
        h1, h2, h3, p { margin:0; }
        .mc-title {
            margin-top:1.1rem;
            font-size:clamp(2.65rem, 6.2vw, 5.9rem);
            line-height:.95;
            letter-spacing:-.074em;
            max-width:12ch;
        }
        .mc-title span { color:var(--mc-indigo); }
        .mc-lead {
            margin-top:1.2rem;
            max-width:42rem;
            color:#52627a;
            font-size:clamp(1rem, 1.7vw, 1.24rem);
            line-height:1.62;
        }
        .mc-hero-actions { margin-top:1.55rem; display:flex; flex-wrap:wrap; gap:.75rem; }
        .mc-trust { margin-top:1.35rem; display:flex; flex-wrap:wrap; gap:.55rem; }
        .mc-pill {
            display:inline-flex;
            align-items:center;
            gap:.45rem;
            padding:.5rem .7rem;
            border-radius:999px;
            border:1px solid rgba(148,163,184,.18);
            background:rgba(255,255,255,.74);
            color:#475569;
            font-size:.78rem;
            font-weight:720;
        }
        .mc-visual {
            position:relative;
            padding:1rem;
        }
        .mc-app-frame {
            border:1px solid rgba(148,163,184,.24);
            border-radius:30px;
            background:rgba(255,255,255,.9);
            box-shadow:0 35px 100px rgba(15,23,42,.14);
            overflow:hidden;
        }
        .mc-browser {
            height:46px;
            display:flex;
            align-items:center;
            gap:.4rem;
            padding:0 1rem;
            border-bottom:1px solid rgba(226,232,240,.78);
            background:#f8fafc;
        }
        .mc-dot { width:10px; height:10px; border-radius:999px; background:#cbd5e1; }
        .mc-dot:nth-child(1) { background:#ff5f57; }
        .mc-dot:nth-child(2) { background:#ffbd2e; }
        .mc-dot:nth-child(3) { background:#28c840; }
        .mc-url { margin-left:.6rem; color:#64748b; font-size:.74rem; font-weight:750; }
        .mc-preview-body { display:grid; grid-template-columns:86px 1fr; min-height:430px; }
        .mc-sidebar {
            padding:1rem .65rem;
            border-right:1px solid rgba(226,232,240,.8);
            background:linear-gradient(180deg,#ffffff,#f8fafc);
            display:grid;
            gap:.45rem;
            align-content:start;
        }
        .mc-side-item {
            width:42px;
            height:42px;
            margin:0 auto;
            display:grid;
            place-items:center;
            border-radius:15px;
            color:#94a3b8;
            background:white;
            border:1px solid rgba(226,232,240,.68);
            font-size:1rem;
        }
        .mc-side-item.active { color:var(--mc-indigo); background:#eef2ff; border-color:#c7d2fe; }
        .mc-board { padding:1.05rem; background:#fbfdff; }
        .mc-board-head { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:1rem; }
        .mc-board-title { font-size:1.28rem; letter-spacing:-.04em; font-weight:900; }
        .mc-board-subtitle { margin-top:.24rem; color:#64748b; font-size:.78rem; }
        .mc-mini-button { padding:.55rem .7rem; border-radius:12px; background:var(--mc-indigo); color:white; font-size:.72rem; font-weight:850; }
        .mc-module-grid { display:grid; grid-template-columns:1.1fr .9fr; gap:.75rem; }
        .mc-screen-card {
            padding:.9rem;
            border:1px solid rgba(226,232,240,.92);
            border-radius:20px;
            background:white;
            box-shadow:0 8px 24px rgba(15,23,42,.045);
        }
        .mc-screen-card.large { grid-row:span 2; }
        .mc-card-label { color:#64748b; font-size:.66rem; text-transform:uppercase; font-weight:900; letter-spacing:.06em; }
        .mc-card-value { margin-top:.28rem; font-size:1.35rem; font-weight:950; letter-spacing:-.04em; }
        .mc-bar { height:7px; border-radius:999px; background:#e2e8f0; overflow:hidden; margin-top:.65rem; }
        .mc-bar span { display:block; height:100%; border-radius:inherit; background:linear-gradient(90deg,var(--mc-indigo),var(--mc-cyan)); }
        .mc-list { margin-top:.7rem; display:grid; gap:.45rem; }
        .mc-list-row { display:flex; align-items:center; justify-content:space-between; gap:.5rem; padding:.5rem; border-radius:12px; background:#f8fafc; color:#475569; font-size:.72rem; }
        .mc-mascot {
            position:absolute;
            right:-4px;
            bottom:-12px;
            width:132px;
            filter:drop-shadow(0 18px 26px rgba(37,99,235,.22));
        }

        .mc-section { padding:58px 0; }
        .mc-section-head { display:flex; align-items:end; justify-content:space-between; gap:1rem; margin-bottom:1.35rem; }
        .mc-section h2 {
            font-size:clamp(2rem, 4.2vw, 3.9rem);
            line-height:.98;
            letter-spacing:-.065em;
            max-width:780px;
        }
        .mc-section-copy {
            color:#64748b;
            line-height:1.62;
            font-size:1rem;
            max-width:42rem;
        }
        .mc-grid { display:grid; gap:1rem; }
        .mc-grid.three { grid-template-columns:repeat(3,minmax(0,1fr)); }
        .mc-grid.two { grid-template-columns:repeat(2,minmax(0,1fr)); }
        .mc-feature-card, .mc-soft-card, .mc-price-card, .mc-faq-card {
            border:1px solid rgba(148,163,184,.2);
            border-radius:26px;
            background:rgba(255,255,255,.84);
            box-shadow:0 18px 48px rgba(15,23,42,.055);
        }
        .mc-feature-card { padding:1.18rem; min-height:210px; }
        .mc-feature-icon {
            width:46px;
            height:46px;
            display:grid;
            place-items:center;
            border-radius:16px;
            background:#eef2ff;
            font-size:1.25rem;
        }
        .mc-feature-card h3 { margin-top:.9rem; font-size:1rem; letter-spacing:-.025em; }
        .mc-feature-card p { margin-top:.45rem; color:#64748b; line-height:1.55; font-size:.9rem; }
        .mc-split {
            display:grid;
            grid-template-columns:minmax(0,.92fr) minmax(360px,1.08fr);
            gap:1.2rem;
            align-items:center;
            margin-top:1rem;
        }
        .mc-split:nth-child(even) { grid-template-columns:minmax(360px,1.08fr) minmax(0,.92fr); }
        .mc-split:nth-child(even) .mc-split-copy { order:2; }
        .mc-split-copy, .mc-soft-card { padding:1.35rem; }
        .mc-eyebrow { color:var(--mc-indigo); font-weight:950; text-transform:uppercase; letter-spacing:.08em; font-size:.72rem; }
        .mc-split-copy h2 { margin-top:.45rem; font-size:clamp(1.8rem, 3.3vw, 3.2rem); }
        .mc-split-copy p { margin-top:.8rem; color:#64748b; line-height:1.62; }
        .mc-checks { display:grid; gap:.52rem; margin-top:1rem; }
        .mc-checks span { display:flex; align-items:center; gap:.5rem; color:#334155; font-size:.9rem; font-weight:720; }
        .mc-checks span::before { content:"✓"; display:grid; place-items:center; width:20px; height:20px; border-radius:999px; background:#dcfce7; color:#16a34a; font-size:.75rem; font-weight:950; }
        .mc-preview {
            min-height:315px;
            padding:1rem;
            border:1px solid rgba(148,163,184,.2);
            border-radius:28px;
            background:linear-gradient(135deg,#ffffff,#f8fbff);
            box-shadow:0 22px 60px rgba(15,23,42,.08);
            overflow:hidden;
        }
        .mc-preview-top { display:flex; align-items:center; justify-content:space-between; gap:.8rem; margin-bottom:.85rem; }
        .mc-preview-title { font-weight:950; letter-spacing:-.03em; }
        .mc-preview-chip { padding:.38rem .52rem; border-radius:999px; background:#eef2ff; color:var(--mc-indigo); font-size:.68rem; font-weight:850; }
        .mc-lines { display:grid; gap:.55rem; }
        .mc-line { height:42px; border-radius:14px; background:#f1f5f9; border:1px solid #e2e8f0; overflow:hidden; position:relative; }
        .mc-line::after { content:""; position:absolute; left:12px; top:14px; width:var(--w, 72%); height:10px; border-radius:999px; background:#cbd5e1; }
        .mc-table { display:grid; gap:.55rem; }
        .mc-table-row { display:grid; grid-template-columns:1.2fr .7fr .7fr; gap:.45rem; align-items:center; }
        .mc-table-row span { min-height:34px; border-radius:12px; background:#f8fafc; border:1px solid #e2e8f0; }
        .mc-gallery { display:grid; grid-template-columns:1fr 1fr; gap:.65rem; }
        .mc-photo { min-height:88px; border-radius:18px; background:linear-gradient(135deg,rgba(79,70,229,.12),rgba(56,213,255,.22)); border:1px solid rgba(99,102,241,.13); }

        .mc-workflow { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:.85rem; }
        .mc-step {
            padding:1rem;
            border-radius:24px;
            border:1px solid rgba(148,163,184,.2);
            background:white;
        }
        .mc-step b { display:block; color:var(--mc-indigo); font-size:.75rem; margin-bottom:.6rem; }
        .mc-step h3 { font-size:.98rem; letter-spacing:-.02em; }
        .mc-step p { margin-top:.45rem; color:#64748b; line-height:1.5; font-size:.86rem; }

        .mc-price-wrap { display:grid; grid-template-columns:minmax(0,.92fr) minmax(330px,.55fr); gap:1rem; align-items:stretch; }
        .mc-price-card { padding:1.5rem; position:relative; overflow:hidden; }
        .mc-price-card.featured { border-color:rgba(79,70,229,.24); background:linear-gradient(145deg,#ffffff,#f1f6ff); }
        .mc-price { margin-top:.65rem; font-size:clamp(2.2rem,5vw,4.2rem); letter-spacing:-.07em; font-weight:950; }
        .mc-price small { font-size:1rem; color:#64748b; letter-spacing:0; font-weight:750; }
        .mc-note { margin-top:1rem; color:#64748b; line-height:1.6; font-size:.92rem; }
        .mc-price-list { margin-top:1.1rem; display:grid; gap:.65rem; }
        .mc-price-list span { display:flex; gap:.55rem; align-items:flex-start; color:#334155; font-size:.92rem; }
        .mc-price-list span::before { content:""; width:8px; height:8px; border-radius:999px; flex:none; margin-top:.45rem; background:var(--mc-indigo); }

        .mc-guide-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.9rem; }
        .mc-guide-step {
            display:grid;
            grid-template-columns:54px 1fr;
            gap:.85rem;
            padding:1rem;
            border:1px solid rgba(148,163,184,.2);
            border-radius:24px;
            background:white;
        }
        .mc-guide-step strong {
            width:54px;
            height:54px;
            display:grid;
            place-items:center;
            border-radius:18px;
            background:#eef2ff;
            color:var(--mc-indigo);
            font-size:.9rem;
        }
        .mc-guide-step h3 { font-size:1rem; letter-spacing:-.02em; }
        .mc-guide-step p { margin-top:.35rem; color:#64748b; line-height:1.5; font-size:.88rem; }

        .mc-faq-card { padding:1.05rem; }
        .mc-faq-card h3 { font-size:1rem; letter-spacing:-.02em; }
        .mc-faq-card p { margin-top:.55rem; color:#64748b; line-height:1.55; font-size:.9rem; }

        .mc-contact-grid { display:grid; grid-template-columns:1fr .85fr; gap:1rem; }
        .mc-contact-card { padding:1.25rem; border:1px solid rgba(148,163,184,.2); border-radius:28px; background:white; box-shadow:0 18px 48px rgba(15,23,42,.055); }
        .mc-contact-row { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:.9rem 0; border-top:1px solid #e2e8f0; }
        .mc-contact-row:first-of-type { border-top:0; }
        .mc-contact-row span { color:#64748b; font-size:.86rem; }
        .mc-contact-row a { color:var(--mc-indigo); font-weight:850; text-decoration:none; overflow-wrap:anywhere; }
        .mc-xeotype { background:linear-gradient(145deg,#081526,#102b52); color:white; }
        .mc-xeotype p { color:#cbd5e1; }
        .mc-xeotype a { color:white; }

        .mc-cta {
            margin:46px 0 0;
            padding:1.5rem;
            border-radius:30px;
            color:white;
            background:
                radial-gradient(circle at 90% 15%, rgba(56,213,255,.32), transparent 19rem),
                linear-gradient(135deg,#07111f,#123469 56%,#1d4ed8);
            display:grid;
            grid-template-columns:1fr auto;
            gap:1rem;
            align-items:center;
            box-shadow:0 30px 80px rgba(18,52,105,.2);
        }
        .mc-cta h2 { font-size:clamp(1.6rem,3.2vw,3.1rem); letter-spacing:-.055em; }
        .mc-cta p { margin-top:.5rem; color:#dbeafe; max-width:42rem; line-height:1.55; }
        .mc-cta .mc-btn.ghost { color:white; background:rgba(255,255,255,.12); border-color:rgba(255,255,255,.18); }

        .mc-footer {
            margin-top:64px;
            padding:34px 0;
            border-top:1px solid rgba(226,232,240,.9);
            background:white;
        }
        .mc-footer-grid { display:grid; grid-template-columns:1.2fr repeat(3,.55fr); gap:1.4rem; align-items:start; }
        .mc-footer-logo { width:270px; }
        .mc-footer p { margin-top:.8rem; color:#64748b; line-height:1.55; font-size:.9rem; max-width:26rem; }
        .mc-footer h4 { margin:0 0:.6rem; font-size:.76rem; text-transform:uppercase; letter-spacing:.08em; color:#334155; }
        .mc-footer a { display:block; color:#64748b; text-decoration:none; font-size:.88rem; padding:.22rem 0; }
        .mc-footer a:hover { color:var(--mc-indigo); }
        .mc-legal-line { margin-top:1.5rem; color:#94a3b8; font-size:.8rem; display:flex; justify-content:space-between; gap:1rem; flex-wrap:wrap; }

        @media (max-width:1020px) {
            .mc-hero, .mc-split, .mc-split:nth-child(even), .mc-price-wrap, .mc-contact-grid, .mc-cta { grid-template-columns:1fr; }
            .mc-split:nth-child(even) .mc-split-copy { order:0; }
            .mc-grid.three, .mc-workflow { grid-template-columns:repeat(2,minmax(0,1fr)); }
            .mc-preview-body { grid-template-columns:66px 1fr; }
            .mc-mascot { width:112px; }
        }
        @media (max-width:820px) {
            .mc-links { display:none; }
            .mc-mobile-link { display:inline-flex; }
            .mc-brand img { width:205px; }
            .mc-actions .ghost { display:none; }
            .mc-hero { padding-top:42px; }
            .mc-title { max-width:11ch; }
        }
        @media (max-width:640px) {
            .mc-shell { width:min(100% - 24px,1180px); }
            .mc-nav { height:66px; }
            .mc-brand img { width:164px; }
            .mc-actions .primary { padding:.66rem .78rem; font-size:.82rem; min-height:38px; }
            .mc-hero { gap:1rem; overflow:hidden; }
            .mc-kicker {
                display:flex;
                width:100%;
                font-size:.66rem;
                letter-spacing:.055em;
                white-space:nowrap;
                overflow:hidden;
                text-overflow:ellipsis;
            }
            .mc-kicker span {
                min-width:0;
                overflow:hidden;
                text-overflow:ellipsis;
            }
            .mc-title { font-size:clamp(2.35rem, 11.8vw, 3.15rem); line-height:1; max-width:11.5ch; letter-spacing:-.065em; }
            .mc-lead { max-width:100%; font-size:1rem; line-height:1.55; }
            .mc-preview-body { grid-template-columns:1fr; }
            .mc-sidebar { display:none; }
            .mc-module-grid, .mc-grid.two, .mc-grid.three, .mc-workflow, .mc-guide-grid, .mc-footer-grid { grid-template-columns:1fr; }
            .mc-section { padding:40px 0; }
            .mc-section-head { align-items:start; flex-direction:column; }
            .mc-app-frame { border-radius:24px; }
            .mc-board-head { flex-direction:column; }
            .mc-mascot { display:none; }
            .mc-contact-row { align-items:flex-start; flex-direction:column; gap:.25rem; }
        }
    </style>
</head>
<body>
    <header class="mc-header">
        <div class="mc-shell mc-nav">
            <a class="mc-brand" href="{{ route('marketing.home') }}" aria-label="MobilityCloud homepage">
                <img src="{{ asset('brand/mobilitycloud-logo-powered-xeotype.png') }}" alt="MobilityCloud powered by Xeotype">
            </a>

            <nav class="mc-links" aria-label="Main navigation">
                @foreach($nav as $key => $item)
                    <a href="{{ $item['url'] }}" @class(['is-active' => $page === $key])>{{ $item['label'] }}</a>
                @endforeach
            </nav>

            <div class="mc-actions">
                <a class="mc-btn ghost" href="{{ $platformUrl }}">Sign in</a>
                <a class="mc-btn primary" href="{{ $registerUrl }}">Start now</a>
            </div>
        </div>
    </header>

    <main>
        @if($page === 'home')
            <section class="mc-shell mc-hero">
                <div>
                    <span class="mc-kicker"><span>Erasmus+ mobility projects, finally organised</span></span>
                    <h1 class="mc-title">From application to final archive, in one <span>cloud</span>.</h1>
                    <p class="mc-lead">MobilityCloud helps teams write Erasmus+ projects, manage approved budgets, collect participant data, organise documents and keep mobility evidence ready for reporting.</p>
                    <div class="mc-hero-actions">
                        <a class="mc-btn primary" href="{{ $registerUrl }}">Create your account</a>
                        <a class="mc-btn ghost" href="{{ route('marketing.features') }}">Explore features</a>
                    </div>
                    <div class="mc-trust" aria-label="Key platform notes">
                        <span class="mc-pill">✓ Free before approval</span>
                        <span class="mc-pill">✓ Pay after grant approval</span>
                        <span class="mc-pill">✓ Powered by <a href="{{ $xeotypeUrl }}" target="_blank" rel="noopener" style="color:var(--mc-indigo);text-decoration:none;">Xeotype</a></span>
                    </div>
                </div>

                <div class="mc-visual">
                    @include('public.partials.product-preview')
                    <img class="mc-mascot" src="{{ asset('brand/mobi-laptop.png') }}" alt="MobilityCloud assistant working on a laptop">
                </div>
            </section>

            <section class="mc-shell mc-section" id="modules">
                <div class="mc-section-head">
                    <div>
                        <span class="mc-kicker"><span>What the platform covers</span></span>
                        <h2 style="margin-top:.9rem;">The operational backbone for mobility projects.</h2>
                    </div>
                    <p class="mc-section-copy">Instead of spreading work across folders, spreadsheets, email threads and unsigned documents, MobilityCloud keeps each project in one structured workspace.</p>
                </div>

                <div class="mc-grid three">
                    @foreach($modules as $module)
                        <article class="mc-feature-card">
                            <div class="mc-feature-icon">{{ $module['icon'] }}</div>
                            <h3>{{ $module['title'] }}</h3>
                            <p>{{ $module['body'] }}</p>
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="mc-shell mc-section">
                <div class="mc-section-head">
                    <div>
                        <span class="mc-kicker"><span>A calmer workflow</span></span>
                        <h2 style="margin-top:.9rem;">Built around how projects actually move.</h2>
                    </div>
                </div>
                <div class="mc-workflow">
                    <article class="mc-step"><b>01</b><h3>Write</h3><p>Use application sections, library text and exports while the project is still being prepared.</p></article>
                    <article class="mc-step"><b>02</b><h3>Approve</h3><p>Declare the approved amount once the grant is confirmed and lock the writing phase.</p></article>
                    <article class="mc-step"><b>03</b><h3>Manage</h3><p>Track budget, participants, documents, mobility evidence and day-to-day tasks.</p></article>
                    <article class="mc-step"><b>04</b><h3>Archive</h3><p>Keep evidence and outputs tidy for final reporting and future audits.</p></article>
                </div>
            </section>

            <section class="mc-shell mc-section">
                <div class="mc-price-wrap">
                    <article class="mc-price-card featured">
                        <span class="mc-kicker"><span>Pricing model</span></span>
                        <h2 style="margin-top:.9rem;">Use it before approval. Pay after success.</h2>
                        <p class="mc-note">MobilityCloud is designed to support application writing without forcing payment upfront. When a project is approved, the administration fee is calculated from the approved grant.</p>
                        <div class="mc-price">1% <small>of approved grant</small></div>
                        <p class="mc-note">Minimum administration fee: <strong>€100</strong>. Fiscal invoices are currently handled manually.</p>
                    </article>
                    <article class="mc-price-card">
                        <h3>Included before approval</h3>
                        <div class="mc-price-list">
                            <span>Application writing tools</span>
                            <span>Writing library and help content</span>
                            <span>Individual Support calculator</span>
                            <span>Tasks and planning support</span>
                        </div>
                        <div style="margin-top:1.25rem;">
                            <a class="mc-btn subtle" href="{{ route('marketing.pricing') }}">View pricing details</a>
                        </div>
                    </article>
                </div>
            </section>
        @elseif($page === 'features')
            <section class="mc-shell mc-section">
                <span class="mc-kicker"><span>Features</span></span>
                <h1 class="mc-title" style="margin-top:1rem;max-width:13ch;">Every project module in one flow.</h1>
                <p class="mc-lead">MobilityCloud follows the natural project lifecycle: application, approval, implementation, evidence and final preparation.</p>

                @foreach($featureRows as $row)
                    <div class="mc-split">
                        <div class="mc-split-copy">
                            <p class="mc-eyebrow">{{ $row['eyebrow'] }}</p>
                            <h2>{{ $row['title'] }}</h2>
                            <p>{{ $row['body'] }}</p>
                            <div class="mc-checks">
                                @foreach($row['points'] as $point)
                                    <span>{{ $point }}</span>
                                @endforeach
                            </div>
                        </div>
                        @include('public.partials.module-preview', ['type' => $row['preview']])
                    </div>
                @endforeach

                <div class="mc-grid three" style="margin-top:1.2rem;">
                    @foreach($modules as $module)
                        <article class="mc-feature-card">
                            <div class="mc-feature-icon">{{ $module['icon'] }}</div>
                            <h3>{{ $module['title'] }}</h3>
                            <p>{{ $module['body'] }}</p>
                        </article>
                    @endforeach
                </div>
            </section>
        @elseif($page === 'pricing')
            <section class="mc-shell mc-section">
                <span class="mc-kicker"><span>Pricing</span></span>
                <h1 class="mc-title" style="margin-top:1rem;max-width:12ch;">Simple pricing for approved projects.</h1>
                <p class="mc-lead">The current MobilityCloud model is intentionally easy to explain: prepare before approval, pay after the project is approved.</p>

                <div class="mc-price-wrap" style="margin-top:2rem;">
                    <article class="mc-price-card featured">
                        <p class="mc-eyebrow">Project administration fee</p>
                        <div class="mc-price">1% <small>of the approved grant</small></div>
                        <p class="mc-note">Minimum fee: <strong>€100</strong>. The fee is calculated from the exact approved grant amount declared by the project owner.</p>
                        <div class="mc-price-list">
                            <span>No online payment required at launch</span>
                            <span>Manual fiscal invoice issued after approval</span>
                            <span>Implementation modules unlock after payment confirmation</span>
                            <span>Unlimited or partner access can be granted manually by MobilityCloud</span>
                        </div>
                    </article>
                    <article class="mc-price-card">
                        <h3>Before approval</h3>
                        <p class="mc-note">Use the writing and planning tools to prepare the project. Collaborators can be invited to work on the application.</p>
                        <h3 style="margin-top:1.4rem;">After approval</h3>
                        <p class="mc-note">Declare the approved amount, receive the manual fiscal invoice, then continue with budget, participants, documents and mobility management.</p>
                        <div style="margin-top:1.3rem;">
                            <a class="mc-btn primary" href="{{ $registerUrl }}">Start now</a>
                        </div>
                    </article>
                </div>
            </section>
        @elseif($page === 'guide')
            <section class="mc-shell mc-section">
                <span class="mc-kicker"><span>Guide</span></span>
                <h1 class="mc-title" style="margin-top:1rem;max-width:12ch;">How to use MobilityCloud.</h1>
                <p class="mc-lead">A practical launch guide for moving from first account setup to implementation-ready project management.</p>

                <div class="mc-guide-grid" style="margin-top:2rem;">
                    @foreach($guideSteps as $item)
                        <article class="mc-guide-step">
                            <strong>{{ $item['step'] }}</strong>
                            <div>
                                <h3>{{ $item['title'] }}</h3>
                                <p>{{ $item['body'] }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @elseif($page === 'help')
            <section class="mc-shell mc-section">
                <span class="mc-kicker"><span>Help Center</span></span>
                <h1 class="mc-title" style="margin-top:1rem;max-width:13ch;">Answers before you get stuck.</h1>
                <p class="mc-lead">Short explanations for the most common account, billing, project access and implementation questions.</p>

                <div class="mc-grid two" style="margin-top:2rem;">
                    @foreach($faq as $item)
                        <article class="mc-faq-card">
                            <h3>{{ $item['q'] }}</h3>
                            <p>{{ $item['a'] }}</p>
                        </article>
                    @endforeach
                </div>
            </section>
        @elseif($page === 'contact')
            <section class="mc-shell mc-section">
                <span class="mc-kicker"><span>Contact</span></span>
                <h1 class="mc-title" style="margin-top:1rem;max-width:12ch;">Talk to the team behind the cloud.</h1>
                <p class="mc-lead">For support, billing, partnerships or onboarding questions, contact MobilityCloud directly.</p>

                <div class="mc-contact-grid" style="margin-top:2rem;">
                    <article class="mc-contact-card">
                        <h2 style="font-size:2rem;letter-spacing:-.045em;">MobilityCloud contact channels</h2>
                        <div style="margin-top:1rem;">
                            <div class="mc-contact-row"><span>General contact</span><a href="mailto:{{ $emails['contact'] }}">{{ $emails['contact'] }}</a></div>
                            <div class="mc-contact-row"><span>Support</span><a href="mailto:{{ $emails['support'] }}">{{ $emails['support'] }}</a></div>
                            <div class="mc-contact-row"><span>Billing</span><a href="mailto:{{ $emails['billing'] }}">{{ $emails['billing'] }}</a></div>
                        </div>
                        <div style="margin-top:1.2rem;">
                            <a class="mc-btn primary" href="mailto:{{ $emails['contact'] }}?subject=MobilityCloud%20inquiry">Send an email</a>
                        </div>
                    </article>
                    <article class="mc-contact-card mc-xeotype">
                        <img src="{{ asset('brand/mobilitycloud-logo-powered-xeotype.png') }}" alt="MobilityCloud powered by Xeotype" style="width:320px;max-width:100%;filter:brightness(1.3);">
                        <h2 style="margin-top:1rem;font-size:2rem;letter-spacing:-.045em;">Powered by Xeotype</h2>
                        <p style="margin-top:.75rem;line-height:1.6;">MobilityCloud is designed, built and operated by Xeotype. For company context and future product work, visit the Xeotype website.</p>
                        <div style="margin-top:1.2rem;">
                            <a class="mc-btn ghost" href="{{ $xeotypeUrl }}" target="_blank" rel="noopener">Visit Xeotype</a>
                        </div>
                    </article>
                </div>
            </section>
        @endif

        <section class="mc-shell">
            <div class="mc-cta">
                <div>
                    <h2>Ready to bring your next Erasmus+ project into one place?</h2>
                    <p>Create an account, verify your email and start preparing your first project in MobilityCloud.</p>
                </div>
                <div style="display:flex;gap:.65rem;flex-wrap:wrap;">
                    <a class="mc-btn primary" href="{{ $registerUrl }}">Start now</a>
                    <a class="mc-btn ghost" href="{{ $platformUrl }}">Sign in</a>
                </div>
            </div>
        </section>
    </main>

    <footer class="mc-footer">
        <div class="mc-shell">
            <div class="mc-footer-grid">
                <div>
                    <img class="mc-footer-logo" src="{{ asset('brand/mobilitycloud-logo-powered-xeotype.png') }}" alt="MobilityCloud powered by Xeotype">
                    <p>MobilityCloud is an independent platform for organising Erasmus+ mobility project workflows. It does not replace official programme guidance or National Agency decisions.</p>
                </div>
                <div>
                    <h4>Product</h4>
                    <a href="{{ route('marketing.features') }}">Features</a>
                    <a href="{{ route('marketing.pricing') }}">Pricing</a>
                    <a href="{{ route('marketing.guide') }}">Guide</a>
                    <a href="{{ route('marketing.help') }}">Help</a>
                </div>
                <div>
                    <h4>Platform</h4>
                    <a href="{{ $registerUrl }}">Create account</a>
                    <a href="{{ $platformUrl }}">Sign in</a>
                    <a href="{{ route('marketing.contact') }}">Contact</a>
                    <a href="{{ $xeotypeUrl }}" target="_blank" rel="noopener">Xeotype</a>
                </div>
                <div>
                    <h4>Legal</h4>
                    <a href="{{ route('legal.terms') }}">Terms of Service</a>
                    <a href="{{ route('legal.privacy') }}">Privacy Policy</a>
                    <a href="{{ route('legal.cookies') }}">Cookie Policy</a>
                    <a href="{{ route('legal.security') }}">Security</a>
                </div>
            </div>
            <div class="mc-legal-line">
                <span>© {{ now()->year }} MobilityCloud. Powered by <a href="{{ $xeotypeUrl }}" target="_blank" rel="noopener" style="color:var(--mc-indigo);text-decoration:none;font-weight:850;">Xeotype</a>.</span>
                <span>{{ $company['legal_name'] ?: 'XEOTYPE SRL' }} · {{ $company['email'] ?: $emails['contact'] }}</span>
            </div>
        </div>
    </footer>
</body>
</html>
