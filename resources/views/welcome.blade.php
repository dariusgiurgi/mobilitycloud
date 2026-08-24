@php
    $siteUrl = rtrim(config('app.url', 'https://mobilitycloud.eu'), '/');
    $description = 'MobilityCloud is a professional Erasmus+ project platform for application writing, approved-project management, budgets, participants, mobility evidence, documents and final reporting.';
    $keywords = 'MobilityCloud, Erasmus project management, Erasmus+ platform, mobility project management, youth exchange management, Erasmus documents, Erasmus budget tracking, project writing platform';
    $schema = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'Organization',
                '@id' => $siteUrl . '/#organization',
                'name' => 'MobilityCloud',
                'url' => $siteUrl . '/',
                'logo' => $siteUrl . '/brand/mobilitycloud-logo-horizontal.png',
                'email' => 'contact@mobilitycloud.eu',
                'parentOrganization' => [
                    '@type' => 'Organization',
                    'name' => 'XEOTYPE SRL',
                    'url' => 'https://xeotype.com',
                ],
            ],
            [
                '@type' => 'WebSite',
                '@id' => $siteUrl . '/#website',
                'url' => $siteUrl . '/',
                'name' => 'MobilityCloud',
                'description' => $description,
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
                'description' => $description,
                'offers' => [
                    '@type' => 'Offer',
                    'priceCurrency' => 'EUR',
                    'availability' => 'https://schema.org/InStock',
                ],
                'publisher' => [
                    '@id' => $siteUrl . '/#organization',
                ],
            ],
        ],
    ];
@endphp
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>MobilityCloud — Erasmus+ project writing and management platform</title>
        <meta name="description" content="{{ $description }}">
        <meta name="keywords" content="{{ $keywords }}">
        <meta name="author" content="MobilityCloud">
        <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
        <meta name="theme-color" content="#1d4ed8">
        <link rel="canonical" href="{{ $siteUrl }}/">
        <link rel="alternate" href="{{ $siteUrl }}/" hreflang="en">
        <link rel="alternate" href="{{ $siteUrl }}/" hreflang="x-default">

        <meta property="og:type" content="website">
        <meta property="og:site_name" content="MobilityCloud">
        <meta property="og:title" content="MobilityCloud — Erasmus+ project writing and management platform">
        <meta property="og:description" content="{{ $description }}">
        <meta property="og:url" content="{{ $siteUrl }}/">
        <meta property="og:image" content="{{ $siteUrl }}/brand/mobilitycloud-logo-stacked.png">
        <meta property="og:image:alt" content="MobilityCloud logo">
        <meta property="og:locale" content="en_US">

        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="MobilityCloud — Erasmus+ project writing and management platform">
        <meta name="twitter:description" content="{{ $description }}">
        <meta name="twitter:image" content="{{ $siteUrl }}/brand/mobilitycloud-logo-stacked.png">

        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('brand/favicon-32.png') }}">
        <link rel="icon" type="image/png" sizes="48x48" href="{{ asset('brand/favicon-48.png') }}">
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('brand/favicon-180.png') }}">
        <link rel="manifest" href="{{ asset('site.webmanifest') }}">

        <script type="application/ld+json">
            {!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
        </script>

        @fonts

        <style>
            :root {
                --mc-ink: #07111f;
                --mc-muted: #64748b;
                --mc-blue: #1d4ed8;
                --mc-cyan: #22d3ee;
                --mc-violet: #4f46e5;
                --mc-panel: rgba(255, 255, 255, 0.82);
                --mc-border: rgba(15, 23, 42, 0.1);
            }

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                color: var(--mc-ink);
                font-family: "Instrument Sans", ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
                background:
                    radial-gradient(circle at 12% 10%, rgba(34, 211, 238, 0.2), transparent 28rem),
                    radial-gradient(circle at 82% 12%, rgba(79, 70, 229, 0.18), transparent 28rem),
                    linear-gradient(180deg, #f8fbff 0%, #eef6ff 48%, #ffffff 100%);
                min-height: 100vh;
            }

            a {
                color: inherit;
                text-decoration: none;
            }

            .page {
                width: min(1180px, calc(100% - 40px));
                margin: 0 auto;
            }

            .nav {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 28px 0;
                gap: 20px;
            }

            .brand {
                display: inline-flex;
                align-items: center;
                gap: 12px;
            }

            .brand img {
                width: 210px;
                height: auto;
                display: block;
            }

            .nav-actions {
                display: flex;
                align-items: center;
                gap: 12px;
                flex-wrap: wrap;
                justify-content: flex-end;
            }

            .pill {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-height: 44px;
                padding: 0 18px;
                border-radius: 999px;
                border: 1px solid var(--mc-border);
                background: rgba(255, 255, 255, 0.72);
                color: #334155;
                font-weight: 800;
                box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
            }

            .pill.primary {
                border: 0;
                color: white;
                background: linear-gradient(135deg, var(--mc-violet), var(--mc-blue), var(--mc-cyan));
                box-shadow: 0 18px 42px rgba(29, 78, 216, 0.25);
            }

            .hero {
                display: grid;
                grid-template-columns: minmax(0, 1.05fr) minmax(320px, 0.75fr);
                gap: 42px;
                align-items: center;
                padding: 70px 0 58px;
            }

            .eyebrow {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 8px 12px;
                border-radius: 999px;
                background: rgba(29, 78, 216, 0.08);
                color: #1d4ed8;
                font-size: 0.78rem;
                font-weight: 900;
                letter-spacing: 0.16em;
                text-transform: uppercase;
            }

            h1 {
                margin: 22px 0 18px;
                font-size: clamp(3.1rem, 8vw, 6.8rem);
                line-height: 0.9;
                letter-spacing: -0.08em;
            }

            .lead {
                max-width: 720px;
                margin: 0;
                color: #475569;
                font-size: clamp(1.12rem, 2vw, 1.45rem);
                line-height: 1.55;
            }

            .hero-actions {
                display: flex;
                flex-wrap: wrap;
                gap: 14px;
                margin-top: 28px;
            }

            .trust-row {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
                margin-top: 26px;
                color: #475569;
                font-weight: 800;
            }

            .trust-row span {
                padding: 8px 12px;
                border-radius: 999px;
                background: rgba(255, 255, 255, 0.72);
                border: 1px solid var(--mc-border);
            }

            .hero-card {
                position: relative;
                overflow: hidden;
                padding: 28px;
                border-radius: 36px;
                border: 1px solid rgba(29, 78, 216, 0.16);
                background: linear-gradient(160deg, rgba(255,255,255,0.9), rgba(226,241,255,0.72));
                box-shadow: 0 28px 70px rgba(15, 23, 42, 0.12);
            }

            .hero-card::before {
                content: "";
                position: absolute;
                inset: -40% -20% auto auto;
                width: 260px;
                height: 260px;
                border-radius: 999px;
                background: radial-gradient(circle, rgba(34, 211, 238, 0.35), transparent 70%);
            }

            .mascot {
                position: relative;
                width: min(100%, 380px);
                margin: 0 auto 16px;
                filter: drop-shadow(0 22px 34px rgba(29, 78, 216, 0.25));
            }

            .metrics {
                position: relative;
                display: grid;
                gap: 12px;
            }

            .metric {
                padding: 18px;
                border-radius: 22px;
                background: rgba(255, 255, 255, 0.76);
                border: 1px solid var(--mc-border);
            }

            .metric strong {
                display: block;
                font-size: 1.35rem;
            }

            .metric span {
                color: var(--mc-muted);
                font-weight: 700;
            }

            .section {
                padding: 56px 0;
            }

            .section h2 {
                margin: 0 0 14px;
                font-size: clamp(2.1rem, 4vw, 4.2rem);
                line-height: 0.96;
                letter-spacing: -0.06em;
            }

            .section-intro {
                margin: 0 0 26px;
                max-width: 760px;
                color: #64748b;
                font-size: 1.12rem;
                line-height: 1.65;
            }

            .grid {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 18px;
            }

            .feature {
                padding: 24px;
                min-height: 220px;
                border-radius: 28px;
                background: var(--mc-panel);
                border: 1px solid var(--mc-border);
                box-shadow: 0 16px 42px rgba(15, 23, 42, 0.07);
            }

            .feature .icon {
                width: 48px;
                height: 48px;
                display: grid;
                place-items: center;
                border-radius: 16px;
                margin-bottom: 18px;
                background: linear-gradient(135deg, rgba(79,70,229,0.12), rgba(34,211,238,0.16));
                font-size: 1.4rem;
            }

            .feature h3 {
                margin: 0 0 10px;
                font-size: 1.28rem;
                letter-spacing: -0.02em;
            }

            .feature p {
                margin: 0;
                color: #64748b;
                line-height: 1.62;
            }

            .agent-panel {
                display: grid;
                grid-template-columns: minmax(0, 1fr) auto;
                gap: 24px;
                align-items: center;
                padding: 28px;
                border-radius: 32px;
                background: #07111f;
                color: white;
                box-shadow: 0 24px 70px rgba(7, 17, 31, 0.18);
            }

            .agent-panel p {
                margin: 8px 0 0;
                color: #cbd5e1;
                line-height: 1.6;
            }

            .footer {
                display: flex;
                justify-content: space-between;
                gap: 18px;
                flex-wrap: wrap;
                padding: 34px 0 48px;
                color: #64748b;
                border-top: 1px solid var(--mc-border);
            }

            .footer a {
                font-weight: 800;
                color: #1d4ed8;
            }

            @@media (max-width: 900px) {
                .hero,
                .agent-panel {
                    grid-template-columns: 1fr;
                }

                .grid {
                    grid-template-columns: 1fr;
                }

                .nav {
                    align-items: flex-start;
                    flex-direction: column;
                }

                .brand img {
                    width: 180px;
                }
            }

            @@media (max-width: 540px) {
                .page {
                    width: min(100% - 24px, 1180px);
                }

                .nav-actions,
                .hero-actions {
                    width: 100%;
                }

                .pill {
                    width: 100%;
                }

                .hero {
                    padding-top: 34px;
                }
            }
        </style>
    </head>
    <body>
        <header class="page nav">
            <a class="brand" href="{{ url('/') }}" aria-label="MobilityCloud home">
                <img src="{{ asset('brand/mobilitycloud-logo-horizontal.png') }}" alt="MobilityCloud">
            </a>
            <nav class="nav-actions" aria-label="Primary navigation">
                <a class="pill" href="{{ url('/ai-agent') }}">AI agent info</a>
                <a class="pill" href="mailto:contact@mobilitycloud.eu">Contact</a>
                <a class="pill primary" href="{{ url('/app/login') }}">Sign in</a>
            </nav>
        </header>

        <main>
            <section class="page hero">
                <div>
                    <span class="eyebrow">Erasmus+ project operations</span>
                    <h1>Write, approve and manage mobility projects in one place.</h1>
                    <p class="lead">
                        MobilityCloud helps organisations move from Erasmus+ application writing to approved-project implementation:
                        budgets, participants, documents, mobility evidence, dissemination records and final reporting stay connected.
                    </p>
                    <div class="hero-actions">
                        <a class="pill primary" href="{{ url('/app/register') }}">Create account</a>
                        <a class="pill" href="{{ url('/app/login') }}">Open platform</a>
                    </div>
                    <div class="trust-row" aria-label="Key capabilities">
                        <span>Application writing</span>
                        <span>Budget control</span>
                        <span>Mobility evidence</span>
                        <span>Final reporting</span>
                    </div>
                </div>

                <aside class="hero-card" aria-label="MobilityCloud summary">
                    <img class="mascot" src="{{ asset('brand/mobi-laptop.png') }}" alt="MobilityCloud assistant using a laptop">
                    <div class="metrics">
                        <div class="metric">
                            <strong>Writing → implementation</strong>
                            <span>Keep the whole project lifecycle connected.</span>
                        </div>
                        <div class="metric">
                            <strong>Designed for teams</strong>
                            <span>Invite editors, viewers and mobility collaborators.</span>
                        </div>
                        <div class="metric">
                            <strong>Powered by Xeotype</strong>
                            <span>Built by XEOTYPE SRL for structured project work.</span>
                        </div>
                    </div>
                </aside>
            </section>

            <section class="page section" id="features">
                <h2>What MobilityCloud covers</h2>
                <p class="section-intro">
                    The platform is built around the daily reality of mobility projects: writing, documents,
                    participants, travel evidence, budgets and collaboration are treated as one connected workflow.
                </p>
                <div class="grid">
                    <article class="feature">
                        <div class="icon">✍️</div>
                        <h3>Application writing</h3>
                        <p>Structured writing spaces for Erasmus+ project applications, with guided sections, exports and read-only locking after approval.</p>
                    </article>
                    <article class="feature">
                        <div class="icon">💶</div>
                        <h3>Approved budget management</h3>
                        <p>Budget baskets, expenses, evidence files, spending summaries and project activation based on the declared approved grant.</p>
                    </article>
                    <article class="feature">
                        <div class="icon">🧑‍🤝‍🧑</div>
                        <h3>Participants and mobility</h3>
                        <p>Participant forms, partner organisations, mobility day evidence, dissemination reports, materials and outputs.</p>
                    </article>
                    <article class="feature">
                        <div class="icon">📁</div>
                        <h3>Documents</h3>
                        <p>Project files, signed copies, generated attendance records and official document organisation with visual file previews.</p>
                    </article>
                    <article class="feature">
                        <div class="icon">✅</div>
                        <h3>Tasks and readiness</h3>
                        <p>Task tracking, project readiness signals and visible priorities that help teams know what needs attention next.</p>
                    </article>
                    <article class="feature">
                        <div class="icon">🤖</div>
                        <h3>AI-readable context</h3>
                        <p>Public metadata, sitemap, robots rules and an AI agent information page make the product easier to understand and reference.</p>
                    </article>
                </div>
            </section>

            <section class="page section">
                <div class="agent-panel">
                    <div>
                        <h2 style="margin: 0; font-size: clamp(2rem, 4vw, 3.6rem);">For search engines and AI agents</h2>
                        <p>
                            MobilityCloud now exposes a concise agent briefing, structured metadata and crawlable public information.
                            Private application areas remain blocked from indexing.
                        </p>
                    </div>
                    <a class="pill primary" href="{{ url('/ai-agent') }}">Open agent page</a>
                </div>
            </section>
        </main>

        <footer class="page footer">
            <span>© {{ date('Y') }} MobilityCloud. Powered by <a href="https://xeotype.com">Xeotype</a>.</span>
            <span>
                <a href="{{ url('/sitemap.xml') }}">Sitemap</a> ·
                <a href="{{ url('/llms.txt') }}">llms.txt</a> ·
                <a href="mailto:contact@mobilitycloud.eu">contact@mobilitycloud.eu</a>
            </span>
        </footer>
    </body>
</html>
