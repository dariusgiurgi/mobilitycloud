@php
    $emails = $emails ?? config('mobilitycloud.emails');
    $company = $company ?? config('mobilitycloud.company');
    $siteUrl = rtrim(config('app.url', 'https://mobilitycloud.eu'), '/');
    $pageUrl = $siteUrl . '/resources/' . $slug;
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
                '@type' => 'Article',
                '@id' => $pageUrl . '#article',
                'headline' => $resource['title'],
                'description' => $resource['description'],
                'url' => $pageUrl,
                'mainEntityOfPage' => $pageUrl,
                'author' => ['@id' => $siteUrl . '/#organization'],
                'publisher' => ['@id' => $siteUrl . '/#organization'],
                'inLanguage' => 'en',
            ],
            [
                '@type' => 'FAQPage',
                '@id' => $pageUrl . '#faq',
                'mainEntity' => collect($resource['faqs'])->map(fn ($faq) => [
                    '@type' => 'Question',
                    'name' => $faq['q'],
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => $faq['a'],
                    ],
                ])->all(),
            ],
            [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => $siteUrl . '/'],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'Resources', 'item' => $siteUrl . '/resources'],
                    ['@type' => 'ListItem', 'position' => 3, 'name' => $resource['title'], 'item' => $pageUrl],
                ],
            ],
        ],
    ];
@endphp

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $resource['title'] }} · MobilityCloud</title>
    <meta name="description" content="{{ $resource['description'] }}">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <link rel="canonical" href="{{ $pageUrl }}">
    <meta property="og:site_name" content="MobilityCloud">
    <meta property="og:type" content="article">
    <meta property="og:title" content="{{ $resource['title'] }} · MobilityCloud">
    <meta property="og:description" content="{{ $resource['description'] }}">
    <meta property="og:url" content="{{ $pageUrl }}">
    <meta property="og:image" content="{{ $socialImage }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $resource['title'] }} · MobilityCloud">
    <meta name="twitter:description" content="{{ $resource['description'] }}">
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
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        * { box-sizing:border-box; }
        body {
            margin:0;
            color:var(--mc-ink);
            background:
                radial-gradient(circle at 6% -12%, rgba(56,213,255,.22), transparent 28rem),
                radial-gradient(circle at 94% 0%, rgba(79,70,229,.16), transparent 28rem),
                #f8fafc;
            -webkit-font-smoothing:antialiased;
        }
        a { color:var(--mc-indigo); font-weight:780; text-decoration:none; }
        a:hover { text-decoration:underline; }
        .shell { width:min(1040px, calc(100% - 36px)); margin:0 auto; }
        .topbar { position:sticky; top:0; z-index:10; border-bottom:1px solid rgba(226,232,240,.84); background:rgba(255,255,255,.86); backdrop-filter:blur(18px); }
        .nav { min-height:74px; display:flex; align-items:center; justify-content:space-between; gap:1rem; }
        .brand img { width:236px; max-width:100%; display:block; }
        .links { display:flex; align-items:center; gap:.38rem; flex-wrap:wrap; }
        .links a { text-decoration:none; color:#475569; font-size:.88rem; font-weight:820; padding:.55rem .72rem; border-radius:999px; }
        .links a:hover, .links a.active { color:var(--mc-indigo); background:#eef2ff; }
        main { padding:52px 0 70px; }
        .card { overflow:hidden; border:1px solid rgba(148,163,184,.22); border-radius:34px; background:rgba(255,255,255,.86); box-shadow:0 24px 70px rgba(15,23,42,.08); }
        .hero { padding:42px; border-bottom:1px solid var(--mc-line); background:linear-gradient(135deg,#eef2ff 0%,#fff 58%,#effcff 100%); }
        .kicker { display:inline-flex; width:max-content; max-width:100%; padding:.48rem .72rem; border-radius:999px; background:#eef2ff; color:var(--mc-indigo); font-size:.73rem; font-weight:950; text-transform:uppercase; letter-spacing:.08em; }
        h1 { max-width:13ch; margin:1rem 0 0; font-size:clamp(2.65rem, 6.5vw, 5.5rem); line-height:.94; letter-spacing:-.075em; }
        .lead { max-width:760px; margin:1rem 0 0; color:#52627a; font-size:1.08rem; line-height:1.68; }
        .content { display:grid; grid-template-columns:minmax(0,1fr) 280px; gap:2rem; padding:36px 42px 42px; }
        article { min-width:0; }
        article section { padding-bottom:1.6rem; margin-bottom:1.6rem; border-bottom:1px solid #edf2f7; }
        article section:last-child { border-bottom:0; margin-bottom:0; padding-bottom:0; }
        h2 { margin:0 0 .65rem; font-size:1.55rem; letter-spacing:-.04em; }
        p { margin:.55rem 0 0; color:#475569; line-height:1.72; }
        .copy-grid { display:grid; gap:1rem; }
        .copy-card { overflow:hidden; border:1px solid rgba(148,163,184,.24); border-radius:22px; background:linear-gradient(180deg,#fbfdff,#fff); box-shadow:0 14px 32px rgba(15,23,42,.04); }
        .copy-card-header { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:.85rem 1rem; border-bottom:1px solid #edf2f7; }
        .copy-card-header h3 { margin:0; font-size:.98rem; letter-spacing:-.02em; }
        .copy-button { appearance:none; border:1px solid rgba(79,70,229,.18); border-radius:999px; background:#eef2ff; color:var(--mc-indigo); cursor:pointer; font:inherit; font-size:.78rem; font-weight:900; padding:.48rem .72rem; white-space:nowrap; }
        .copy-button:hover { background:#e0e7ff; }
        .copy-text { width:100%; min-height:138px; resize:vertical; border:0; outline:0; display:block; padding:1rem; color:#334155; background:transparent; font:inherit; font-size:.92rem; line-height:1.62; }
        .copy-note { margin-top:.85rem; color:#64748b; font-size:.9rem; }
        .faq { display:grid; gap:.85rem; }
        .faq-item { padding:1rem; border:1px solid rgba(148,163,184,.22); border-radius:20px; background:#fbfdff; }
        .faq-item h3 { margin:0; font-size:1rem; letter-spacing:-.02em; }
        .aside { align-self:start; position:sticky; top:104px; display:grid; gap:1rem; }
        .aside-card { padding:1rem; border:1px solid rgba(148,163,184,.22); border-radius:22px; background:#fbfdff; }
        .aside-card img { width:180px; max-width:100%; display:block; margin-bottom:.75rem; }
        .aside-card h3 { margin:0; font-size:1rem; }
        .aside-card p { font-size:.9rem; line-height:1.55; }
        .button { display:inline-flex; align-items:center; justify-content:center; min-height:42px; margin-top:.8rem; padding:.7rem 1rem; border-radius:14px; color:white; background:linear-gradient(135deg,var(--mc-indigo),#315eff); font-weight:880; text-decoration:none; box-shadow:0 15px 34px rgba(79,70,229,.22); }
        .footer { border-top:1px solid var(--mc-line); padding:34px 0; color:#64748b; background:white; }
        .footer-row { display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
        .footer img { width:220px; max-width:100%; display:block; }
        .footer-links { display:flex; gap:.85rem; flex-wrap:wrap; }
        .footer-links a { color:#64748b; text-decoration:none; font-weight:750; font-size:.86rem; }
        .footer-links a:hover { color:var(--mc-indigo); }
        @media (max-width:860px) {
            .nav { min-height:auto; padding:14px 0; align-items:flex-start; flex-direction:column; }
            .brand img { width:205px; }
            .content { grid-template-columns:1fr; padding:28px 22px 32px; }
            .aside { position:static; }
            .hero { padding:32px 22px; }
            h1 { font-size:clamp(2.45rem, 12vw, 3.5rem); }
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
        </nav>
    </div>
</header>

<main>
    <div class="shell">
        <div class="card">
            <section class="hero">
                <span class="kicker">{{ $resource['eyebrow'] }}</span>
                <h1>{{ $resource['hero'] }}</h1>
                <p class="lead">{{ $resource['intro'] }}</p>
            </section>

            <div class="content">
                <article>
                    @foreach($resource['sections'] as $section)
                        <section>
                            <h2>{{ $section['title'] }}</h2>
                            <p>{{ $section['body'] }}</p>
                        </section>
                    @endforeach

                    @if(! empty($resource['copy_blocks']))
                        <section>
                            <h2>Ready-to-use sharing texts</h2>
                            <p>Copy, adapt and publish these texts when MobilityCloud is relevant for your audience.</p>
                            <div class="copy-grid" data-copy-grid>
                                @foreach($resource['copy_blocks'] as $block)
                                    <div class="copy-card">
                                        <div class="copy-card-header">
                                            <h3>{{ $block['label'] }}</h3>
                                            <button class="copy-button" type="button" data-copy-button>Copy text</button>
                                        </div>
                                        <textarea class="copy-text" readonly data-copy-text>{{ $block['text'] }}</textarea>
                                    </div>
                                @endforeach
                            </div>
                            <p class="copy-note">Please keep the independence note where relevant: MobilityCloud is independent software powered by Xeotype and is not an official Erasmus+ service.</p>
                        </section>
                    @endif

                    <section>
                        <h2>Common questions</h2>
                        <div class="faq">
                            @foreach($resource['faqs'] as $faq)
                                <div class="faq-item">
                                    <h3>{{ $faq['q'] }}</h3>
                                    <p>{{ $faq['a'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </section>
                </article>

                <aside class="aside" aria-label="Related links">
                    <div class="aside-card">
                        <img src="{{ asset('brand/mobilitycloud-logo-horizontal.png') }}" alt="MobilityCloud">
                        <h3>About MobilityCloud</h3>
                        <p>MobilityCloud helps organisations write Erasmus+ applications and manage approved mobility projects in one structured platform.</p>
                        <a class="button" href="{{ route('marketing.features') }}">Explore features</a>
                    </div>
                    <div class="aside-card">
                        <h3>Important note</h3>
                        <p>MobilityCloud is independent software powered by Xeotype. It does not replace official Erasmus+ guidance, accounting advice or National Agency decisions.</p>
                    </div>
                    <div class="aside-card">
                        <h3>Need help?</h3>
                        <p>For support, onboarding, billing or partnership questions, contact the MobilityCloud team.</p>
                        <a href="mailto:{{ $emails['contact'] ?? 'contact@mobilitycloud.eu' }}">{{ $emails['contact'] ?? 'contact@mobilitycloud.eu' }}</a>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</main>

<footer class="footer">
    <div class="shell footer-row">
        <a href="{{ route('marketing.home') }}" aria-label="MobilityCloud homepage">
            <img src="{{ asset('brand/mobilitycloud-logo-powered-xeotype.png') }}" alt="MobilityCloud powered by Xeotype">
        </a>
        <nav class="footer-links" aria-label="Footer navigation">
            <a href="{{ route('marketing.resources') }}">Resources</a>
            <a href="{{ route('legal.terms') }}">Terms</a>
            <a href="{{ route('legal.privacy') }}">Privacy</a>
            <a href="{{ route('legal.cookies') }}">Cookies</a>
            <a href="{{ route('legal.security') }}">Security</a>
            <a href="{{ route('legal.gdpr') }}">GDPR</a>
            <a href="{{ route('legal.billing') }}">Billing</a>
            <a href="{{ route('legal.ai-agent') }}">AI agent information</a>
            <a href="#" data-cookie-settings>Cookie settings</a>
        </nav>
    </div>
</footer>
@include('public.partials.cookie-consent')
<script>
    document.querySelectorAll('[data-copy-button]').forEach((button) => {
        button.addEventListener('click', async () => {
            const card = button.closest('.copy-card');
            const text = card?.querySelector('[data-copy-text]')?.value ?? '';
            const originalLabel = button.textContent;

            try {
                await navigator.clipboard.writeText(text);
                button.textContent = 'Copied';
            } catch (error) {
                card?.querySelector('[data-copy-text]')?.select();
                button.textContent = 'Select text';
            }

            window.setTimeout(() => {
                button.textContent = originalLabel;
            }, 1600);
        });
    });
</script>
</body>
</html>
