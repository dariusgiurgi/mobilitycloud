@php
    $page = $page ?? 'home';
    $emails = $emails ?? config('mobilitycloud.emails');
    $company = $company ?? config('mobilitycloud.company');
    $xeotypeUrl = 'https://xeotype.com';
    $siteUrl = rtrim(config('app.url', url('/')), '/');
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
        'pricing' => 'Use MobilityCloud free until approval, then pay 1% of the approved grant after the first instalment arrives.',
        'guide' => 'A practical guide for starting, writing, approving and managing your Erasmus+ mobility project in MobilityCloud.',
        'help' => 'Answers to the most common MobilityCloud account, billing, project, participant, document and mobility questions.',
        'contact' => 'Contact MobilityCloud for support, billing and partnership questions.',
    ];

    $nav = [
        'features' => ['label' => 'Features', 'url' => route('marketing.features')],
        'demo' => ['label' => 'Live demo', 'url' => url('/demo')],
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
        ['q' => 'Can I use MobilityCloud before my project is approved?', 'a' => 'Yes. All application writing and planning work is free until your project is approved. No card is required.'],
        ['q' => 'When do I pay?', 'a' => 'When you declare the approved grant, every implementation module unlocks immediately. MobilityCloud then issues a manual fiscal invoice that you can pay after your first grant instalment arrives.'],
        ['q' => 'What is the administration fee?', 'a' => 'The current launch fee is 1% of the exact approved grant value per project.'],
        ['q' => 'Can collaborators access only one project?', 'a' => 'Yes. Invitations are project-based, and access can be limited by role, including view-only and mobility-focused access.'],
        ['q' => 'Is MobilityCloud an official Erasmus+ tool?', 'a' => 'No. MobilityCloud is an independent project management platform that helps organise Erasmus+ workflows. Official decisions remain with the National Agency and programme rules.'],
        ['q' => 'Who develops MobilityCloud?', 'a' => 'MobilityCloud is powered by Xeotype, the company behind the platform design, development and operations.'],
    ];

    $structuredData = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'Organization',
                '@id' => $siteUrl . '/#organization',
                'name' => 'MobilityCloud',
                'url' => $siteUrl . '/',
                'logo' => asset('brand/mobilitycloud-logo-powered-xeotype.png'),
                'email' => $emails['contact'] ?? 'contact@mobilitycloud.eu',
                'parentOrganization' => [
                    '@type' => 'Organization',
                    'name' => 'XEOTYPE SRL',
                    'url' => $xeotypeUrl,
                ],
            ],
            [
                '@type' => 'WebSite',
                '@id' => $siteUrl . '/#website',
                'url' => $siteUrl . '/',
                'name' => 'MobilityCloud',
                'description' => $descriptions[$page] ?? $descriptions['home'],
                'publisher' => [
                    '@id' => $siteUrl . '/#organization',
                ],
                'inLanguage' => 'en',
            ],
            [
                '@type' => 'SoftwareApplication',
                '@id' => $siteUrl . '/#software',
                'name' => 'MobilityCloud',
                'applicationCategory' => 'BusinessApplication',
                'operatingSystem' => 'Web',
                'url' => $siteUrl . '/',
                'description' => 'A professional Erasmus+ project writing and approved-project management platform.',
                'publisher' => [
                    '@id' => $siteUrl . '/#organization',
                ],
            ],
        ],
    ];

    if ($page === 'help') {
        $structuredData['@graph'][] = [
            '@type' => 'FAQPage',
            '@id' => $siteUrl . '/help#faq',
            'mainEntity' => collect($faq)->map(fn ($item) => [
                '@type' => 'Question',
                'name' => $item['q'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $item['a'],
                ],
            ])->all(),
        ];
    }
@endphp

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $titles[$page] ?? $titles['home'] }}</title>
    <meta name="description" content="{{ $descriptions[$page] ?? $descriptions['home'] }}">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="alternate" type="application/json" href="{{ url('/agent.json') }}" title="MobilityCloud agent JSON">
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
    <script type="application/ld+json">{!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
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
        .mc-chip-success { background:#dcfce7; color:#15803d; }
        .mc-preview-project { display:grid; grid-template-columns:32px 1fr auto; gap:.6rem; align-items:center; padding:.65rem .7rem; border:1px solid #e2e8f0; border-radius:15px; background:#f8fafc; }
        .mc-project-mark { width:32px; height:32px; display:grid; place-items:center; border-radius:10px; background:linear-gradient(135deg,#4338ca,#38bdf8); color:white; font-size:.63rem; font-weight:950; }
        .mc-preview-project strong, .mc-preview-project span { display:block; }
        .mc-preview-project strong { font-size:.73rem; letter-spacing:-.02em; }
        .mc-preview-project > div:nth-child(2) span { margin-top:.12rem; color:#64748b; font-size:.61rem; }
        .mc-stage-chip { padding:.28rem .4rem; border-radius:999px; color:#a16207; background:#fef3c7; font-size:.59rem !important; font-weight:850; }
        .mc-writing-layout { display:grid; grid-template-columns:105px 1fr; gap:.65rem; margin-top:.7rem; }
        .mc-writing-nav { display:grid; align-content:start; gap:.27rem; padding:.38rem; border:1px solid #e2e8f0; border-radius:15px; background:#fbfdff; }
        .mc-writing-nav span { display:flex; align-items:center; gap:.32rem; min-width:0; padding:.37rem; border-radius:9px; color:#64748b; font-size:.6rem; font-weight:760; white-space:nowrap; }
        .mc-writing-nav i { width:15px; height:15px; flex:none; display:grid; place-items:center; border-radius:999px; color:#94a3b8; background:#e2e8f0; font-style:normal; font-size:.53rem; font-weight:900; }
        .mc-writing-nav .is-done i { color:#15803d; background:#dcfce7; }
        .mc-writing-nav .is-active { color:#4338ca; background:#eef2ff; }
        .mc-writing-nav .is-active i { color:white; background:#4f46e5; }
        .mc-writing-editor { min-width:0; padding:.7rem; border:1px solid #dbe3f0; border-radius:15px; background:white; box-shadow:0 7px 18px rgba(15,23,42,.035); }
        .mc-editor-head { display:flex; align-items:start; justify-content:space-between; gap:.5rem; }
        .mc-editor-head b, .mc-editor-head span { display:block; }
        .mc-editor-head b { color:#1e293b; font-size:.68rem; letter-spacing:-.015em; }
        .mc-editor-head div > span { margin-top:.16rem; color:#94a3b8; font-size:.55rem; }
        .mc-saved { color:#15803d !important; font-size:.57rem !important; font-weight:850; }
        .mc-writing-editor > p { margin:.62rem 0 0; color:#475569; font-size:.63rem; line-height:1.5; }
        .mc-editor-lines { display:grid; gap:.28rem; margin-top:.5rem; }
        .mc-editor-lines i { display:block; height:4px; border-radius:99px; background:#e2e8f0; }
        .mc-editor-lines i:nth-child(2) { width:82%; }
        .mc-editor-lines i:nth-child(3) { width:64%; }
        .mc-writing-editor .mc-bar { margin-top:.55rem; height:5px; }
        .mc-budget-summary { display:grid; grid-template-columns:repeat(3,1fr); gap:.45rem; }
        .mc-budget-summary > div { padding:.6rem; border:1px solid #e2e8f0; border-radius:13px; background:#f8fafc; }
        .mc-budget-summary span, .mc-budget-summary strong { display:block; }
        .mc-budget-summary span { color:#64748b; font-size:.54rem; font-weight:760; }
        .mc-budget-summary strong { margin-top:.18rem; color:#1e293b; font-size:.77rem; letter-spacing:-.025em; }
        .mc-budget-table { margin-top:.72rem; overflow:hidden; border:1px solid #e2e8f0; border-radius:15px; background:white; }
        .mc-budget-row { display:grid; grid-template-columns:1.55fr .8fr .75fr .52fr; gap:.25rem; align-items:center; padding:.48rem .55rem; border-top:1px solid #edf2f7; color:#475569; font-size:.58rem; }
        .mc-budget-row:first-child { border-top:0; }
        .mc-budget-row strong { color:#334155; font-size:.59rem; letter-spacing:-.015em; }
        .mc-budget-row em { justify-self:start; padding:.15rem .28rem; border-radius:999px; color:#15803d; background:#dcfce7; font-size:.51rem; font-style:normal; font-weight:850; white-space:nowrap; }
        .mc-budget-head { color:#94a3b8; background:#f8fafc; font-size:.52rem; font-weight:900; text-transform:uppercase; letter-spacing:.04em; }
        .mc-basket-dot { display:inline-block; width:7px; height:7px; margin-right:.28rem; border-radius:99px; background:#60a5fa; }
        .mc-basket-dot.support { background:#34d399; }.mc-basket-dot.course { background:#a78bfa; }
        .mc-spend-note { display:flex; align-items:center; gap:.55rem; margin-top:.68rem; color:#64748b; font-size:.59rem; font-weight:760; }
        .mc-spend-note .mc-bar { flex:1; margin:0; height:6px; }
        .mc-mobility-meta { display:flex; align-items:center; gap:.45rem; padding:.55rem .65rem; border:1px solid #e2e8f0; border-radius:13px; background:#f8fafc; color:#64748b; font-size:.58rem; }
        .mc-mobility-meta b { color:#334155; font-size:.59rem; }.mc-mobility-meta .mc-saved { margin-left:auto; white-space:nowrap; }
        .mc-evidence-list { display:grid; gap:.4rem; margin-top:.72rem; }
        .mc-evidence-day { display:grid; grid-template-columns:27px 1fr 19px; gap:.48rem; align-items:center; padding:.48rem .55rem; border:1px solid #e2e8f0; border-radius:13px; background:white; }
        .mc-day-number { width:27px; height:27px; display:grid; place-items:center; border-radius:9px; color:#64748b; background:#f1f5f9; font-size:.57rem; font-weight:900; }
        .mc-evidence-day b, .mc-evidence-day small { display:block; }.mc-evidence-day b { color:#334155; font-size:.65rem; letter-spacing:-.015em; }.mc-evidence-day small { margin-top:.12rem; color:#94a3b8; font-size:.53rem; }
        .mc-evidence-day > i { width:19px; height:19px; display:grid; place-items:center; border-radius:99px; color:#15803d; background:#dcfce7; font-size:.62rem; font-style:normal; font-weight:950; }
        .mc-evidence-day.is-active { border-color:#c7d2fe; background:#f8faff; }.mc-evidence-day.is-active .mc-day-number { color:white; background:#4f46e5; }.mc-evidence-day.is-active > i { color:#4f46e5; background:#eef2ff; }
        .mc-evidence-footer { display:flex; align-items:center; gap:.5rem; margin-top:.72rem; padding:.52rem .62rem; border-radius:13px; color:#64748b; background:#f8fafc; font-size:.58rem; }.mc-evidence-footer b { color:#334155; }
        .mc-mini-stack { display:flex; }.mc-mini-stack i { width:20px; height:25px; margin-right:-7px; border:2px solid white; border-radius:7px; background:linear-gradient(135deg,#c7d2fe,#bae6fd); }.mc-mini-stack i:nth-child(2) { background:linear-gradient(135deg,#bbf7d0,#bae6fd); }.mc-mini-stack i:nth-child(3) { background:linear-gradient(135deg,#fde68a,#ddd6fe); }

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

        /* Public guide: content lives in public/partials/guide-content.blade.php. */
        .mc-guide-hero { display:grid; grid-template-columns:1.05fr .7fr; gap:1rem; align-items:stretch; padding:66px 0 30px; }
        .mc-guide-search { position:relative; max-width:650px; margin-top:1.25rem; }.mc-guide-search > label { display:block; margin-bottom:.38rem; color:#334155; font-size:.72rem; font-weight:900; }.mc-guide-search-field { display:flex; align-items:center; gap:.55rem; padding:.34rem .42rem .34rem .75rem; border:1px solid #cbd8e8; border-radius:14px; background:white; box-shadow:0 10px 28px rgba(15,23,42,.055); }.mc-guide-search-field > span { color:#4f46e5; font-size:1.1rem; font-weight:950; }.mc-guide-search-field input { width:100%; min-width:0; border:0; outline:0; color:#1e293b; background:transparent; font:inherit; font-size:.83rem; }.mc-guide-search-field input::placeholder { color:#94a3b8; }.mc-guide-search-field button { flex:none; border:0; border-radius:9px; padding:.38rem .5rem; color:#4338ca; background:#eef2ff; cursor:pointer; font:inherit; font-size:.64rem; font-weight:900; }.mc-guide-search-field button:hover { background:#e0e7ff; }.mc-guide-search > p { margin-top:.38rem; color:#64748b; font-size:.65rem; line-height:1.45; }.mc-guide-search-results { position:absolute; z-index:8; right:0; left:0; margin-top:.45rem; padding:.7rem; border:1px solid #cbd8e8; border-radius:15px; background:white; box-shadow:0 20px 48px rgba(15,23,42,.15); }.mc-guide-search-summary { margin:0 0 .4rem !important; color:#64748b !important; font-size:.61rem !important; font-weight:850; text-transform:uppercase; letter-spacing:.06em; }.mc-guide-search-list { display:grid; gap:.28rem; }.mc-guide-search-list a { display:flex; align-items:center; justify-content:space-between; gap:.7rem; padding:.48rem .52rem; border-radius:10px; color:#334155; text-decoration:none; }.mc-guide-search-list a:hover { color:#4338ca; background:#eef2ff; }.mc-guide-search-list b { font-size:.72rem; }.mc-guide-search-list span, .mc-guide-search-results small { color:#64748b; font-size:.62rem; line-height:1.4; }.mc-guide-search-list span { flex:none; color:#4f46e5; }
        .mc-guide-hero-pills { display:flex; flex-wrap:wrap; gap:.45rem; margin-top:1.2rem; }.mc-guide-hero-pills span { padding:.45rem .6rem; border:1px solid #dbe5f1; border-radius:999px; color:#475569; background:white; font-size:.72rem; font-weight:780; }
        .mc-guide-route { padding:1.2rem; border:1px solid rgba(99,102,241,.18); border-radius:27px; color:white; background:linear-gradient(145deg,#102a55,#173e79 58%,#3b56d4); box-shadow:0 22px 56px rgba(30,64,175,.18); }
        .mc-guide-route .mc-card-label { color:#bfdbfe; }.mc-route-steps { display:grid; gap:.35rem; margin-top:.8rem; }.mc-route-steps a { display:flex; align-items:center; gap:.55rem; padding:.42rem; border-radius:11px; color:white; text-decoration:none; font-size:.72rem; font-weight:800; }.mc-route-steps a:hover { background:rgba(255,255,255,.12); }.mc-route-steps b { width:24px; height:24px; display:grid; place-items:center; border-radius:8px; color:#312e81; background:#dbeafe; font-size:.58rem; }.mc-guide-route-note { margin:.9rem 0 0; color:#dbeafe; font-size:.69rem; line-height:1.45; }
        .mc-guide-start { padding:28px 0 54px; }.mc-guide-start-head { display:flex; align-items:end; justify-content:space-between; gap:1rem; }.mc-guide-start-head h2 { max-width:620px; font-size:clamp(1.7rem,3vw,2.75rem); letter-spacing:-.055em; }.mc-guide-start-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:.75rem; margin-top:1rem; }.mc-guide-start-grid article { display:grid; grid-template-columns:34px 1fr; gap:.7rem; padding:1rem; border:1px solid #e2e8f0; border-radius:19px; background:white; }.mc-guide-start-grid strong { width:34px; height:34px; display:grid; place-items:center; border-radius:11px; color:#4338ca; background:#eef2ff; font-size:.78rem; }.mc-guide-start-grid h3 { font-size:.86rem; letter-spacing:-.02em; }.mc-guide-start-grid p { margin-top:.32rem; color:#64748b; font-size:.76rem; line-height:1.5; }
        .mc-guide-layout { display:grid; grid-template-columns:215px minmax(0,1fr); gap:3rem; padding-bottom:72px; }.mc-guide-toc { align-self:start; position:sticky; top:98px; padding:.75rem; border:1px solid #e2e8f0; border-radius:18px; background:rgba(255,255,255,.88); box-shadow:0 14px 34px rgba(15,23,42,.05); }.mc-guide-toc > p { padding:.15rem .35rem .5rem; color:#64748b; font-size:.67rem; font-weight:900; text-transform:uppercase; letter-spacing:.08em; }.mc-guide-toc > a { display:flex; align-items:center; gap:.45rem; padding:.47rem .35rem; border-radius:10px; color:#475569; text-decoration:none; font-size:.73rem; font-weight:780; }.mc-guide-toc > a:hover { color:#4338ca; background:#eef2ff; }.mc-guide-toc > a > span { color:#94a3b8; font-size:.58rem; font-weight:950; }.mc-guide-toc-help { display:block !important; margin-top:.55rem; padding-top:.75rem !important; border-top:1px solid #e2e8f0; font-size:.67rem !important; line-height:1.4; }.mc-guide-toc-help b { color:#4f46e5; }
        .mc-guide-chapters { display:grid; gap:0; }.mc-guide-chapter { position:relative; display:grid; grid-template-columns:54px minmax(0,1fr); gap:1rem; padding:0 0 50px; }.mc-guide-chapter:not(:last-child)::before { content:""; position:absolute; top:54px; bottom:0; left:25px; width:2px; background:linear-gradient(#c7d2fe,#e2e8f0); }.mc-guide-chapter-number { position:relative; z-index:1; width:52px; height:52px; display:grid; place-items:center; border:1px solid #c7d2fe; border-radius:17px; color:#4338ca; background:#f5f7ff; font-size:.74rem; font-weight:950; }.mc-guide-chapter-content { min-width:0; padding-bottom:4px; }.mc-guide-chapter h2 { margin-top:.35rem; max-width:740px; font-size:clamp(1.8rem,3vw,2.9rem); letter-spacing:-.06em; line-height:1.02; }.mc-guide-intro { max-width:720px; margin-top:.72rem; color:#52647d; line-height:1.65; font-size:.97rem; }.mc-guide-list { display:grid; gap:.62rem; margin:1.1rem 0 1.2rem; padding:0; counter-reset:item; }.mc-guide-list li { display:grid; grid-template-columns:24px minmax(0,1fr); column-gap:.55rem; align-items:start; list-style:none; color:#475569; font-size:.84rem; line-height:1.48; }.mc-guide-list li::before { content:counter(item); counter-increment:item; width:23px; height:23px; display:grid; place-items:center; border-radius:8px; color:#4338ca; background:#eef2ff; font-size:.61rem; font-weight:950; }.mc-guide-list li b { color:#1e293b; }.mc-guide-list li span { grid-column:2; margin-top:.08rem; }.mc-guide-list.compact { margin:0; }
        .mc-sidebar-guide-screen { display:grid; grid-template-columns:175px minmax(0,1fr); margin:1.1rem 0 1rem; overflow:hidden; border:1px solid #cbd8e8; border-radius:20px; background:linear-gradient(135deg,#eff6ff,#f8fbff); box-shadow:0 16px 38px rgba(15,23,42,.065); }.mc-sidebar-guide-brand { display:flex; align-items:center; gap:.4rem; padding:.7rem; color:white; background:#102a55; }.mc-sidebar-guide-brand > span { width:25px; height:25px; display:grid; place-items:center; border-radius:8px; color:#102a55; background:#b9ecff; font-size:.56rem; font-weight:950; }.mc-sidebar-guide-brand b { font-size:.62rem; }.mc-sidebar-guide-brand i { display:grid; place-items:center; width:20px; height:20px; margin-left:auto; border-radius:7px; color:#dbeafe; background:rgba(255,255,255,.1); font-size:1rem; font-style:normal; }.mc-sidebar-guide-links { display:grid; grid-column:1; gap:.22rem; padding:.55rem; color:#dbeafe; background:#102a55; }.mc-sidebar-guide-links > div { display:flex; align-items:center; gap:.45rem; padding:.43rem; border-radius:8px; font-size:.62rem; }.mc-sidebar-guide-links > div.is-active { color:white; background:rgba(255,255,255,.14); }.mc-sidebar-guide-links span { width:17px; height:17px; display:grid; place-items:center; color:#b9ecff; font-size:.7rem; }.mc-sidebar-guide-links b { font-weight:760; }.mc-sidebar-guide-screen > p { align-self:center; grid-column:2; grid-row:1 / span 2; max-width:390px; padding:1rem 1.2rem; color:#52647d; font-size:.78rem; line-height:1.55; }.mc-sidebar-guide-screen > p span { display:inline-block; margin-right:.35rem; padding:.2rem .35rem; border-radius:7px; color:#4338ca; background:#e0e7ff; font-size:.56rem; font-weight:950; text-transform:uppercase; letter-spacing:.06em; }.mc-workspace-menu-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.6rem; }.mc-workspace-menu-card { display:grid; grid-template-columns:32px minmax(0,1fr); gap:.6rem; padding:.78rem; border:1px solid #e0e8f3; border-radius:15px; background:white; }.mc-workspace-menu-icon { width:31px; height:31px; display:grid; place-items:center; border-radius:10px; color:#4338ca; background:#eef2ff; font-size:.78rem; font-weight:950; }.mc-workspace-menu-card h3 { color:#1e293b; font-size:.75rem; letter-spacing:-.02em; }.mc-workspace-menu-card p { margin-top:.22rem; color:#64748b; font-size:.64rem; line-height:1.45; }.mc-workspace-menu-card small { display:block; margin-top:.45rem; padding-top:.4rem; border-top:1px solid #edf2f7; color:#475569; font-size:.59rem; line-height:1.45; }.mc-workspace-menu-card small b { color:#4338ca; }
        .mc-guide-shot { margin:1.2rem 0; overflow:hidden; border:1px solid #d7e1ee; border-radius:22px; background:white; box-shadow:0 18px 45px rgba(15,23,42,.09); }.mc-guide-shot img { width:100%; height:auto; }.mc-guide-shot figcaption { display:flex; gap:.5rem; padding:.72rem .85rem; color:#64748b; background:#fbfdff; font-size:.72rem; line-height:1.45; }.mc-guide-shot figcaption span { flex:none; color:#4f46e5; font-weight:900; text-transform:uppercase; letter-spacing:.06em; font-size:.59rem; }
        .mc-guide-callout { display:grid; grid-template-columns:105px 1fr; gap:.75rem; margin-top:1rem; padding:.8rem .9rem; border-radius:15px; font-size:.76rem; line-height:1.5; }.mc-guide-callout b { font-size:.7rem; text-transform:uppercase; letter-spacing:.06em; }.mc-guide-callout p { margin:0; }.mc-guide-callout.info { color:#1e40af; background:#eff6ff; border:1px solid #bfdbfe; }.mc-guide-callout.warning { color:#92400e; background:#fffbeb; border:1px solid #fde68a; }.mc-guide-callout.important { color:#5b21b6; background:#f5f3ff; border:1px solid #ddd6fe; }
        .mc-guide-split-visual { display:grid; grid-template-columns:.85fr 1.15fr; gap:1rem; align-items:center; margin:1.15rem 0; }.mc-guide-ui-card { padding:.9rem; overflow:hidden; border:1px solid #dbe5f1; border-radius:19px; background:linear-gradient(145deg,#fff,#f8fbff); box-shadow:0 14px 34px rgba(15,23,42,.06); }.mc-guide-ui-title { display:flex; align-items:center; gap:.5rem; }.mc-ui-icon { width:25px; height:25px; display:grid; place-items:center; border-radius:8px; color:white; background:#4f46e5; font-weight:900; }.mc-guide-ui-title b { font-size:.8rem; }.mc-guide-ui-title em { margin-left:auto; color:#64748b; font-size:.6rem; font-style:normal; }.mc-guide-ui-card label { display:grid; gap:.27rem; margin-top:.75rem; color:#64748b; font-size:.58rem; font-weight:820; }.mc-guide-ui-card label i { padding:.48rem .55rem; border:1px solid #dbe5f1; border-radius:9px; color:#334155; background:white; font-size:.68rem; font-style:normal; font-weight:700; }.mc-guide-ui-two { display:grid; grid-template-columns:1fr 1fr; gap:.45rem; }.mc-guide-ui-footer { display:flex; justify-content:space-between; gap:.5rem; margin-top:.8rem; padding-top:.7rem; border-top:1px solid #e2e8f0; color:#64748b; font-size:.59rem; }.mc-guide-ui-footer b { padding:.35rem .5rem; border-radius:8px; color:white; background:#4f46e5; }
        .mc-writing-guide-screen { display:grid; grid-template-columns:135px 1fr; margin:1.1rem 0; overflow:hidden; border:1px solid #d8e2ee; border-radius:20px; background:#fff; box-shadow:0 16px 38px rgba(15,23,42,.065); }.mc-writing-guide-screen aside { display:grid; align-content:start; gap:.34rem; padding:.85rem .6rem; border-right:1px solid #e2e8f0; background:#fbfdff; }.mc-writing-guide-screen aside b { margin-bottom:.25rem; color:#334155; font-size:.66rem; }.mc-writing-guide-screen aside span { padding:.4rem; border-radius:8px; color:#64748b; font-size:.62rem; font-weight:750; }.mc-writing-guide-screen aside .done { color:#15803d; }.mc-writing-guide-screen aside .active { color:#4338ca; background:#eef2ff; }.mc-writing-guide-screen > div { padding:1rem; }.mc-guide-screen-top { display:flex; align-items:center; justify-content:space-between; gap:.5rem; color:#1e293b; font-size:.75rem; font-weight:850; }.mc-guide-screen-top em { color:#15803d; font-size:.58rem; font-style:normal; }.mc-writing-guide-screen > div > p { margin:.85rem 0; color:#475569; font-size:.73rem; line-height:1.55; }.mc-guide-screen-lines { display:grid; gap:.45rem; }.mc-guide-screen-lines i { display:block; height:7px; border-radius:99px; background:#e2e8f0; }.mc-guide-screen-lines i:nth-child(2) { width:91%; }.mc-guide-screen-lines i:nth-child(3) { width:76%; }.mc-guide-screen-lines i:nth-child(4) { width:62%; }.mc-writing-guide-screen footer { display:flex; justify-content:space-between; gap:.5rem; margin-top:1rem; padding-top:.7rem; border-top:1px solid #e2e8f0; color:#64748b; font-size:.62rem; }.mc-writing-guide-screen footer b { color:#4338ca; }
        .mc-table-decision { display:grid; grid-template-columns:1fr 1fr; gap:.65rem; margin:1.15rem 0 .2rem; }.mc-table-decision > div { padding:.8rem .9rem; border:1px solid #dbe5f1; border-radius:16px; background:#fbfdff; }.mc-table-decision > div:last-child { border-color:#c7d2fe; background:#f5f7ff; }.mc-table-decision span { display:inline-block; padding:.22rem .36rem; border-radius:7px; color:#475569; background:#eef2f7; font-size:.56rem; font-weight:900; letter-spacing:.05em; text-transform:uppercase; }.mc-table-decision > div:last-child span { color:#4338ca; background:#e0e7ff; }.mc-table-decision b { display:block; margin-top:.5rem; color:#1e293b; font-size:.73rem; }.mc-table-decision p { margin-top:.25rem; color:#64748b; font-size:.64rem; line-height:1.45; }.mc-table-model-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.65rem; margin-top:1.1rem; }.mc-table-model-grid article { padding:.85rem; border:1px solid #dbe5f1; border-radius:17px; background:white; box-shadow:0 10px 25px rgba(15,23,42,.04); }.mc-table-model-grid article:last-child { grid-column:1/-1; }.mc-table-model-grid h3 { color:#1e293b; font-size:.8rem; letter-spacing:-.025em; }.mc-table-model-grid > article > p { min-height:40px; margin-top:.28rem; color:#64748b; font-size:.64rem; line-height:1.45; }.mc-table-model-preview { display:flex; flex-wrap:wrap; gap:.3rem; margin-top:.7rem; padding-top:.65rem; border-top:1px solid #edf2f7; }.mc-table-model-preview span { padding:.28rem .36rem; border:1px solid #dbe5f1; border-radius:7px; color:#475569; background:#f8fafc; font-size:.57rem; font-weight:760; }
        .mc-guide-approval-flow { display:grid; grid-template-columns:1fr auto 1fr auto 1fr; gap:.45rem; align-items:center; margin:1.15rem 0; padding:1rem; border-radius:20px; color:white; background:linear-gradient(135deg,#102a55,#3156bf); }.mc-guide-approval-flow > div { min-height:112px; padding:.75rem; border:1px solid rgba(255,255,255,.17); border-radius:14px; background:rgba(255,255,255,.09); }.mc-guide-approval-flow span { width:22px; height:22px; display:grid; place-items:center; border-radius:7px; color:#312e81; background:#dbeafe; font-size:.59rem; font-weight:950; }.mc-guide-approval-flow b, .mc-guide-approval-flow small { display:block; }.mc-guide-approval-flow b { margin-top:.55rem; font-size:.69rem; }.mc-guide-approval-flow small { margin-top:.25rem; color:#dbeafe; font-size:.58rem; line-height:1.4; }.mc-guide-approval-flow > i { color:#bae6fd; font-size:1rem; font-style:normal; }
        .mc-guide-module-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:.6rem; margin:1rem 0; }.mc-guide-module-grid article { padding:.75rem; border:1px solid #e2e8f0; border-radius:15px; background:white; }.mc-guide-module-grid article > span { width:25px; height:25px; display:grid; place-items:center; border-radius:8px; color:#4338ca; background:#eef2ff; font-size:.72rem; font-weight:950; }.mc-guide-module-grid h3 { margin-top:.5rem; font-size:.78rem; }.mc-guide-module-grid p { margin-top:.25rem; color:#64748b; font-size:.67rem; line-height:1.45; }.mc-guide-module-grid b { display:block; margin-top:.5rem; color:#15803d; font-size:.6rem; }.mc-evidence-guide-screen { display:grid; gap:.45rem; margin-top:1rem; padding:.8rem; border:1px solid #d8e2ee; border-radius:18px; background:#fbfdff; }.mc-evidence-guide-head { display:flex; justify-content:space-between; gap:.5rem; padding:.25rem .2rem .55rem; color:#334155; font-size:.72rem; }.mc-evidence-guide-head span { color:#64748b; font-size:.62rem; }.mc-evidence-guide-day { display:grid; grid-template-columns:28px 1fr auto; gap:.5rem; align-items:center; padding:.55rem; border:1px solid #e2e8f0; border-radius:11px; background:white; }.mc-evidence-guide-day > i { width:28px; height:28px; display:grid; place-items:center; border-radius:8px; color:#15803d; background:#dcfce7; font-size:.61rem; font-style:normal; font-weight:900; }.mc-evidence-guide-day b, .mc-evidence-guide-day span { display:block; }.mc-evidence-guide-day b { font-size:.65rem; }.mc-evidence-guide-day span { margin-top:.12rem; color:#94a3b8; font-size:.54rem; }.mc-evidence-guide-day em { padding:.25rem .35rem; border-radius:99px; color:#15803d; background:#dcfce7; font-size:.54rem; font-style:normal; font-weight:850; }.mc-evidence-guide-day.pending { border-color:#c7d2fe; }.mc-evidence-guide-day.pending > i { color:#4338ca; background:#eef2ff; }.mc-evidence-guide-day.pending em { color:#4338ca; background:#eef2ff; }
        .mc-final-checklist { display:grid; gap:.5rem; margin-top:1rem; }.mc-final-checklist > div { display:grid; grid-template-columns:25px 1fr; gap:.55rem; padding:.65rem .7rem; border:1px solid #e2e8f0; border-radius:13px; background:white; }.mc-final-checklist > div > span { width:23px; height:23px; display:grid; place-items:center; border-radius:99px; color:#15803d; background:#dcfce7; font-size:.66rem; font-weight:950; }.mc-final-checklist b, .mc-final-checklist small { display:block; }.mc-final-checklist b { color:#334155; font-size:.71rem; }.mc-final-checklist small { margin-top:.12rem; color:#64748b; font-size:.62rem; line-height:1.42; }.mc-guide-final-banner { display:flex; justify-content:space-between; gap:1rem; margin-top:1rem; padding:1.1rem; overflow:hidden; border-radius:20px; color:white; background:radial-gradient(circle at 96% 0,rgba(56,213,255,.35),transparent 13rem),linear-gradient(135deg,#102a55,#1d4ed8); }.mc-guide-final-banner .mc-card-label { color:#bfdbfe; }.mc-guide-final-banner h3 { margin-top:.35rem; font-size:1.1rem; letter-spacing:-.035em; }.mc-guide-final-banner p:last-child { margin-top:.4rem; max-width:560px; color:#dbeafe; font-size:.71rem; line-height:1.5; }.mc-guide-final-banner > span { width:38px; height:38px; display:grid; place-items:center; border-radius:13px; color:#312e81; background:#dbeafe; font-size:1.2rem; }
        .mc-control-legend { display:flex; flex-wrap:wrap; gap:.38rem; margin:1rem 0; }.mc-control-legend span { padding:.34rem .48rem; border-radius:999px; font-size:.61rem; font-weight:850; }.mc-control-legend .navigate { color:#1d4ed8; background:#dbeafe; }.mc-control-legend .create { color:#4338ca; background:#e0e7ff; }.mc-control-legend .upload { color:#0369a1; background:#cffafe; }.mc-control-legend .important { color:#a16207; background:#fef3c7; }.mc-control-legend .danger { color:#b91c1c; background:#fee2e2; }.mc-control-groups { display:grid; gap:.75rem; }.mc-control-group { overflow:hidden; border:1px solid #dbe5f1; border-radius:18px; background:white; box-shadow:0 10px 28px rgba(15,23,42,.045); }.mc-control-group header { padding:.85rem .95rem; border-bottom:1px solid #e2e8f0; background:linear-gradient(100deg,#fbfdff,#f5f7ff); }.mc-control-group header p { color:#6366f1; font-size:.58rem; font-weight:900; letter-spacing:.07em; text-transform:uppercase; }.mc-control-group header h3 { margin-top:.22rem; color:#1e293b; font-size:.92rem; letter-spacing:-.025em; }.mc-control-table { display:grid; }.mc-control-row { display:grid; grid-template-columns:27px minmax(145px,.72fr) minmax(0,1.25fr); gap:.7rem; align-items:start; padding:.72rem .9rem; border-top:1px solid #edf2f7; }.mc-control-row:first-child { border-top:0; }.mc-control-kind { width:25px; height:25px; display:grid; place-items:center; border-radius:8px; color:#475569; background:#f1f5f9; font-size:.67rem; font-weight:950; }.mc-control-kind.navigate, .mc-control-kind.view, .mc-control-kind.search, .mc-control-kind.settings, .mc-control-kind.library { color:#1d4ed8; background:#dbeafe; }.mc-control-kind.create { color:#4338ca; background:#e0e7ff; }.mc-control-kind.upload { color:#0369a1; background:#cffafe; }.mc-control-kind.export, .mc-control-kind.review { color:#15803d; background:#dcfce7; }.mc-control-kind.manage, .mc-control-kind.edit { color:#7c3aed; background:#ede9fe; }.mc-control-kind.important { color:#a16207; background:#fef3c7; }.mc-control-kind.danger { color:#b91c1c; background:#fee2e2; }.mc-control-row h4 { color:#1e293b; font-size:.71rem; line-height:1.35; }.mc-control-row p { margin-top:.15rem; color:#64748b; font-size:.65rem; line-height:1.43; }.mc-control-row small { align-self:center; padding-left:.7rem; border-left:1px solid #e2e8f0; color:#475569; font-size:.62rem; line-height:1.46; }

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
            .mc-guide-layout { grid-template-columns:1fr; gap:1.3rem; }.mc-guide-toc { position:static; display:flex; flex-wrap:wrap; gap:.22rem; }.mc-guide-toc > p, .mc-guide-toc-help { width:100%; }.mc-guide-toc > a { padding:.42rem; }.mc-guide-toc-help { display:none !important; }
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
            .mc-guide-hero { grid-template-columns:1fr; padding:42px 0 22px; }.mc-guide-search-list a { align-items:flex-start; flex-direction:column; gap:.15rem; }.mc-guide-start { padding:24px 0 38px; }.mc-guide-start-head { display:block; }.mc-guide-start-grid { grid-template-columns:1fr; }.mc-guide-chapter { grid-template-columns:38px minmax(0,1fr); gap:.7rem; padding-bottom:38px; }.mc-guide-chapter:not(:last-child)::before { left:18px; top:40px; }.mc-guide-chapter-number { width:38px; height:38px; border-radius:13px; font-size:.63rem; }.mc-guide-chapter h2 { font-size:1.75rem; }.mc-guide-intro { font-size:.88rem; }.mc-guide-shot { border-radius:16px; }.mc-guide-callout { grid-template-columns:1fr; gap:.28rem; }.mc-guide-split-visual, .mc-writing-guide-screen, .mc-sidebar-guide-screen { grid-template-columns:1fr; }.mc-sidebar-guide-brand { grid-column:1; }.mc-sidebar-guide-links { grid-column:1; grid-row:auto; }.mc-sidebar-guide-screen > p { grid-column:1; grid-row:auto; padding:1rem; }.mc-workspace-menu-grid { grid-template-columns:1fr; }.mc-writing-guide-screen aside { border-right:0; border-bottom:1px solid #e2e8f0; grid-template-columns:repeat(3,1fr); }.mc-writing-guide-screen aside b { grid-column:1/-1; }.mc-guide-approval-flow { grid-template-columns:1fr; }.mc-guide-approval-flow > i { justify-self:center; transform:rotate(90deg); }.mc-guide-module-grid { grid-template-columns:1fr; }.mc-evidence-guide-head { align-items:flex-start; flex-direction:column; }.mc-evidence-guide-day { grid-template-columns:26px 1fr; }.mc-evidence-guide-day em { grid-column:2; justify-self:start; }.mc-guide-final-banner { align-items:start; }.mc-guide-ui-two { grid-template-columns:1fr; }.mc-control-row { grid-template-columns:25px 1fr; gap:.5rem; }.mc-control-row small { grid-column:2; padding:.35rem 0 0; border:0; border-top:1px solid #e2e8f0; }
            .mc-table-model-grid, .mc-table-decision { grid-template-columns:1fr; }
            .mc-table-model-grid article:last-child { grid-column:auto; }
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
                    <p class="mc-lead">Write, plan and prepare your Erasmus+ project for free. After approval, declare the grant and move straight into budgets, participants, documents and evidence.</p>
                    <div class="mc-hero-actions">
                        <a class="mc-btn primary" href="{{ $registerUrl }}">Start free</a>
                        <a class="mc-btn ghost" href="{{ url('/demo') }}">Explore live demo</a>
                    </div>
                    <div class="mc-trust" aria-label="Key platform notes">
                        <span class="mc-pill">✓ Free until approval</span>
                        <span class="mc-pill">✓ 1% after first instalment</span>
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
                        <h2 style="margin-top:.9rem;">Use everything free before approval. Pay only after success.</h2>
                        <p class="mc-note">No card. No upfront payment. When a project is approved and the grant amount is declared, every implementation module unlocks immediately and the administration fee is calculated from that approved grant.</p>
                        <div class="mc-price">1% <small>of approved grant</small></div>
                        <p class="mc-note">A manual fiscal invoice is issued after approval and can be paid after the first grant instalment arrives.</p>
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
                <h1 class="mc-title" style="margin-top:1rem;max-width:12ch;">Free until approval. One simple fee after success.</h1>
                <p class="mc-lead">Prepare the whole project without an upfront payment. If it is approved, MobilityCloud charges 1% of the approved grant.</p>

                <div class="mc-price-wrap" style="margin-top:2rem;">
                    <article class="mc-price-card featured">
                        <p class="mc-eyebrow">Project administration fee</p>
                        <div class="mc-price">1% <small>of the approved grant</small></div>
                        <p class="mc-note">The fee is calculated from the exact approved grant amount declared by the project owner.</p>
                        <div class="mc-price-list">
                            <span>No card or payment required before approval</span>
                            <span>Manual fiscal invoice issued after approval</span>
                            <span>Every implementation module unlocks immediately after the approved grant is declared</span>
                            <span>Pay the invoice after the first grant instalment arrives</span>
                            <span>Unlimited or partner access can be granted manually by MobilityCloud</span>
                        </div>
                    </article>
                    <article class="mc-price-card">
                        <h3>Before approval</h3>
                        <p class="mc-note">Use the writing, planning and collaboration tools free of charge to prepare the project. Collaborators can be invited to work on the application.</p>
                        <h3 style="margin-top:1.4rem;">After approval</h3>
                        <p class="mc-note">Declare the approved amount and continue immediately with every module: budget, participants, documents, mobility and final preparation. The fiscal invoice can be paid after your first grant instalment arrives.</p>
                        <div style="margin-top:1.3rem;">
                            <a class="mc-btn primary" href="{{ $registerUrl }}">Start now</a>
                        </div>
                    </article>
                </div>
            </section>
        @elseif($page === 'guide')
            @include('public.partials.guide-content')
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
                    <a href="{{ url('/demo') }}">Live demo</a>
                    <a href="{{ route('marketing.pricing') }}">Pricing</a>
                    <a href="{{ route('marketing.guide') }}">Guide</a>
                    <a href="{{ route('marketing.help') }}">Help</a>
                    <a href="{{ route('marketing.resources') }}">Resources</a>
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
                    <a href="{{ route('legal.gdpr') }}">GDPR & Data Processing</a>
                    <a href="{{ route('legal.billing') }}">Billing & Invoices</a>
                    <a href="{{ route('legal.ai-agent') }}">AI agent information</a>
                    <a href="#" data-cookie-settings>Cookie settings</a>
                </div>
            </div>
            <div class="mc-legal-line">
                <span>© {{ now()->year }} MobilityCloud. Powered by <a href="{{ $xeotypeUrl }}" target="_blank" rel="noopener" style="color:var(--mc-indigo);text-decoration:none;font-weight:850;">Xeotype</a>.</span>
                <span>{{ $company['legal_name'] ?: 'XEOTYPE SRL' }} · {{ $company['email'] ?: $emails['contact'] }}</span>
            </div>
        </div>
    </footer>
    @include('public.partials.cookie-consent')
</body>
</html>
