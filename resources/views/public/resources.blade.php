@php
    $emails = $emails ?? config('mobilitycloud.emails');
    $company = $company ?? config('mobilitycloud.company');
    $siteUrl = rtrim(config('app.url', 'https://mobilitycloud.eu'), '/');
    $pageUrl = $siteUrl . '/resources';
    $description = 'Practical public resources about Erasmus+ project management, mobility documents, final report evidence and budget tracking with MobilityCloud.';
    $socialImage = asset('brand/mobilitycloud-logo-powered-xeotype.png');
    $structuredData = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'Organization',
                '@id' => $siteUrl . '/#organization',
                'name' => 'MobilityCloud',
                'url' => $siteUrl . '/',
                'logo' => $socialImage,
                'email' => $emails['contact'] ?? 'contact@mobilitycloud.eu',
            ],
            [
                '@type' => 'CollectionPage',
                '@id' => $pageUrl . '#webpage',
                'url' => $pageUrl,
                'name' => 'Erasmus+ resources · MobilityCloud',
                'description' => $description,
                'isPartOf' => ['@id' => $siteUrl . '/#website'],
                'publisher' => ['@id' => $siteUrl . '/#organization'],
                'inLanguage' => 'en',
            ],
            [
                '@type' => 'ItemList',
                'name' => 'MobilityCloud Erasmus+ resource articles',
                'itemListElement' => collect($pages)->keys()->values()->map(fn ($slug, $index) => [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'url' => $siteUrl . '/resources/' . $slug,
                    'name' => $pages[$slug]['title'],
                ])->all(),
            ],
        ],
    ];
@endphp

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Erasmus+ resources · MobilityCloud</title>
    <meta name="description" content="{{ $description }}">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <link rel="canonical" href="{{ $pageUrl }}">
    <meta property="og:site_name" content="MobilityCloud">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Erasmus+ resources · MobilityCloud">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $pageUrl }}">
    <meta property="og:image" content="{{ $socialImage }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Erasmus+ resources · MobilityCloud">
    <meta name="twitter:description" content="{{ $description }}">
    <meta name="twitter:image" content="{{ $socialImage }}">
    <link rel="icon" type="image/png" href="{{ asset('brand/favicon-64.png') }}">
    <script type="application/ld+json">{!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    <style>
        :root {
            color-scheme: light;
            --mc-blue:#123469;
            --mc-indigo:#4f46e5;
            --mc-cyan:#38d5ff;
            --mc-ink:#07111f;
            --mc-muted:#64748b;
            --mc-line:#e2e8f0;
            --mc-soft:#f8fafc;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        * { box-sizing:border-box; }
        body {
            margin:0;
            color:var(--mc-ink);
            background:
                radial-gradient(circle at 6% -12%, rgba(56,213,255,.24), transparent 28rem),
                radial-gradient(circle at 94% 0%, rgba(79,70,229,.16), transparent 28rem),
                linear-gradient(180deg,#fbfdff,#f8fafc 56%,#fff);
            -webkit-font-smoothing:antialiased;
        }
        a { color:inherit; }
        .shell { width:min(1120px, calc(100% - 36px)); margin:0 auto; }
        .topbar { position:sticky; top:0; z-index:10; border-bottom:1px solid rgba(226,232,240,.84); background:rgba(255,255,255,.86); backdrop-filter:blur(18px); }
        .nav { min-height:74px; display:flex; align-items:center; justify-content:space-between; gap:1rem; }
        .brand img { width:236px; max-width:100%; display:block; }
        .links { display:flex; align-items:center; gap:.38rem; flex-wrap:wrap; }
        .links a { text-decoration:none; color:#475569; font-size:.88rem; font-weight:820; padding:.55rem .72rem; border-radius:999px; }
        .links a:hover, .links a.active { color:var(--mc-indigo); background:#eef2ff; }
        .button { display:inline-flex; align-items:center; justify-content:center; min-height:42px; padding:.7rem 1rem; border-radius:14px; text-decoration:none; color:white; background:linear-gradient(135deg,var(--mc-indigo),#315eff); font-weight:880; box-shadow:0 15px 34px rgba(79,70,229,.22); }
        main { padding:60px 0 70px; }
        .kicker { display:inline-flex; padding:.48rem .72rem; border-radius:999px; background:#eef2ff; color:var(--mc-indigo); font-size:.73rem; font-weight:950; text-transform:uppercase; letter-spacing:.08em; }
        h1 { max-width:12ch; margin:1rem 0 0; font-size:clamp(2.6rem, 7vw, 5.7rem); line-height:.94; letter-spacing:-.075em; }
        .lead { max-width:760px; margin:1rem 0 0; color:#52627a; font-size:1.12rem; line-height:1.68; }
        .resource-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:1rem; margin-top:2.2rem; }
        .resource-card { position:relative; min-height:310px; display:flex; flex-direction:column; justify-content:space-between; padding:1.45rem; border:1px solid rgba(148,163,184,.22); border-radius:30px; background:rgba(255,255,255,.82); box-shadow:0 20px 56px rgba(15,23,42,.07); overflow:hidden; text-decoration:none; }
        .resource-card::before { content:""; position:absolute; inset:0 0 auto; height:5px; background:linear-gradient(90deg,var(--mc-indigo),var(--mc-cyan)); }
        .resource-card h2 { margin:.75rem 0 0; font-size:2.05rem; line-height:1; letter-spacing:-.055em; }
        .resource-card p { margin:.85rem 0 0; color:var(--mc-muted); line-height:1.62; }
        .resource-card span { width:max-content; max-width:100%; color:var(--mc-indigo); font-size:.72rem; font-weight:950; text-transform:uppercase; letter-spacing:.08em; }
        .resource-card strong { margin-top:1rem; color:var(--mc-indigo); font-size:.92rem; }
        .footer { border-top:1px solid var(--mc-line); padding:34px 0; color:#64748b; background:white; }
        .footer-row { display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
        .footer img { width:220px; max-width:100%; display:block; }
        .footer-links { display:flex; gap:.85rem; flex-wrap:wrap; }
        .footer-links a { color:#64748b; text-decoration:none; font-weight:750; font-size:.86rem; }
        .footer-links a:hover { color:var(--mc-indigo); }
        @media (max-width:820px) {
            .nav { min-height:auto; padding:14px 0; align-items:flex-start; flex-direction:column; }
            .brand img { width:205px; }
            .resource-grid { grid-template-columns:1fr; }
            h1 { font-size:clamp(2.5rem, 12vw, 3.5rem); }
        }
    </style>
</head>
<body>
<header class="topbar">
    <div class="shell nav">
        <a class="brand" href="{{ route('marketing.home') }}" aria-label="MobilityCloud homepage">
            <img src="{{ asset('brand/mobilitycloud-logo-powered-xeotype.png') }}" alt="MobilityCloud powered by Xeotype">
        </a>
        <nav class="links" aria-label="Main navigation">
            <a href="{{ route('marketing.features') }}">Features</a>
            <a href="{{ route('marketing.pricing') }}">Pricing</a>
            <a href="{{ route('marketing.guide') }}">Guide</a>
            <a class="active" href="{{ route('marketing.resources') }}">Resources</a>
            <a href="{{ route('marketing.contact') }}">Contact</a>
            <a class="button" href="{{ url('/app/register') }}">Start now</a>
        </nav>
    </div>
</header>

<main>
    <section class="shell">
        <span class="kicker">Public resources</span>
        <h1>Erasmus+ project knowledge, structured.</h1>
        <p class="lead">Short public guides for teams searching for better ways to organise Erasmus+ project writing, approved budgets, mobility documents, evidence and reporting preparation.</p>

        <div class="resource-grid">
            @foreach($pages as $slug => $item)
                <a class="resource-card" href="{{ route('marketing.resource', ['slug' => $slug]) }}">
                    <div>
                        <span>{{ $item['eyebrow'] }}</span>
                        <h2>{{ $item['title'] }}</h2>
                        <p>{{ $item['description'] }}</p>
                    </div>
                    <strong>Read resource →</strong>
                </a>
            @endforeach
        </div>
    </section>
</main>

<footer class="footer">
    <div class="shell footer-row">
        <a href="{{ route('marketing.home') }}" aria-label="MobilityCloud homepage">
            <img src="{{ asset('brand/mobilitycloud-logo-powered-xeotype.png') }}" alt="MobilityCloud powered by Xeotype">
        </a>
        <nav class="footer-links" aria-label="Footer navigation">
            <a href="{{ route('legal.terms') }}">Terms</a>
            <a href="{{ route('legal.privacy') }}">Privacy</a>
            <a href="{{ route('legal.cookies') }}">Cookies</a>
            <a href="{{ route('legal.security') }}">Security</a>
            <a href="{{ route('legal.gdpr') }}">GDPR</a>
            <a href="{{ route('legal.billing') }}">Billing</a>
            <a href="{{ route('legal.ai-agent') }}">AI agent information</a>
            <a href="#" data-cookie-settings>Cookie settings</a>
            <a href="mailto:{{ $emails['contact'] ?? 'contact@mobilitycloud.eu' }}">Contact</a>
        </nav>
    </div>
</footer>
@include('public.partials.cookie-consent')
</body>
</html>
