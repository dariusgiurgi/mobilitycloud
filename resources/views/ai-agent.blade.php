@php
    $siteUrl = rtrim(config('app.url', 'https://mobilitycloud.eu'), '/');
    $description = 'AI-readable briefing for MobilityCloud, an Erasmus+ project writing and management platform by Xeotype.';
    $pageUrl = $siteUrl . '/legal/ai-agent';
    $socialImage = asset('brand/mobilitycloud-logo-powered-xeotype.png');
    $structuredData = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'Organization',
                '@id' => $siteUrl . '/#organization',
                'name' => 'MobilityCloud',
                'url' => $siteUrl . '/',
                'email' => 'contact@mobilitycloud.eu',
                'parentOrganization' => [
                    '@type' => 'Organization',
                    'name' => 'XEOTYPE SRL',
                    'url' => 'https://xeotype.com',
                ],
            ],
            [
                '@type' => 'WebPage',
                '@id' => $pageUrl . '#webpage',
                'url' => $pageUrl,
                'name' => 'AI Agent Briefing - MobilityCloud',
                'description' => $description,
                'publisher' => [
                    '@id' => $siteUrl . '/#organization',
                ],
                'inLanguage' => 'en',
            ],
            [
                '@type' => 'Dataset',
                '@id' => $siteUrl . '/agent.json#dataset',
                'name' => 'MobilityCloud public agent information',
                'description' => 'Structured public information that helps AI agents identify MobilityCloud, its public pages and product scope.',
                'url' => $siteUrl . '/agent.json',
                'license' => $siteUrl . '/legal/terms',
                'isAccessibleForFree' => true,
                'creator' => [
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
        <title>AI Agent Briefing — MobilityCloud</title>
        <meta name="description" content="{{ $description }}">
        <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
        <link rel="canonical" href="{{ $pageUrl }}">
        <meta property="og:site_name" content="MobilityCloud">
        <meta property="og:type" content="website">
        <meta property="og:title" content="AI Agent Briefing — MobilityCloud">
        <meta property="og:description" content="{{ $description }}">
        <meta property="og:url" content="{{ $pageUrl }}">
        <meta property="og:image" content="{{ $socialImage }}">
        <meta property="og:image:width" content="1100">
        <meta property="og:image:height" content="187">
        <meta property="og:image:alt" content="MobilityCloud powered by Xeotype">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="AI Agent Briefing — MobilityCloud">
        <meta name="twitter:description" content="{{ $description }}">
        <meta name="twitter:image" content="{{ $socialImage }}">
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('brand/favicon-32.png') }}">
        <link rel="alternate" type="application/json" href="{{ $siteUrl }}/agent.json" title="MobilityCloud agent JSON">
        <script type="application/ld+json">{!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>

        @fonts

        <style>
            :root {
                --ink: #07111f;
                --muted: #64748b;
                --blue: #1d4ed8;
                --cyan: #22d3ee;
                --violet: #4f46e5;
                --border: rgba(15, 23, 42, 0.1);
            }

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                color: var(--ink);
                font-family: "Instrument Sans", ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
                background:
                    radial-gradient(circle at 15% 10%, rgba(34, 211, 238, 0.18), transparent 28rem),
                    radial-gradient(circle at 88% 18%, rgba(79, 70, 229, 0.18), transparent 26rem),
                    #f8fbff;
            }

            main {
                width: min(980px, calc(100% - 40px));
                margin: 0 auto;
                padding: 42px 0 70px;
            }

            header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 18px;
                flex-wrap: wrap;
                margin-bottom: 52px;
            }

            img {
                width: 210px;
                height: auto;
            }

            a {
                color: var(--blue);
                font-weight: 800;
                text-decoration: none;
            }

            .button {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-height: 44px;
                padding: 0 18px;
                border-radius: 999px;
                color: white;
                background: linear-gradient(135deg, var(--violet), var(--blue), var(--cyan));
                box-shadow: 0 18px 42px rgba(29, 78, 216, 0.22);
            }

            h1 {
                margin: 0 0 18px;
                font-size: clamp(2.8rem, 8vw, 5.8rem);
                line-height: 0.92;
                letter-spacing: -0.075em;
            }

            .lead {
                max-width: 780px;
                margin: 0 0 32px;
                color: #475569;
                font-size: 1.22rem;
                line-height: 1.64;
            }

            .panel {
                margin: 18px 0;
                padding: 26px;
                border-radius: 28px;
                background: rgba(255, 255, 255, 0.78);
                border: 1px solid var(--border);
                box-shadow: 0 18px 46px rgba(15, 23, 42, 0.07);
            }

            h2 {
                margin: 0 0 14px;
                font-size: 1.5rem;
            }

            p,
            li {
                color: var(--muted);
                line-height: 1.68;
            }

            ul {
                margin: 0;
                padding-left: 1.2rem;
            }

            code {
                padding: 0.16rem 0.42rem;
                border-radius: 0.55rem;
                background: rgba(29, 78, 216, 0.08);
                color: #1d4ed8;
                font-weight: 800;
            }

            .machine {
                white-space: pre-wrap;
                overflow-x: auto;
                color: #dbeafe;
                background: #07111f;
                border-radius: 20px;
                padding: 20px;
                line-height: 1.58;
            }
        </style>
    </head>
    <body>
        <main>
            <header>
                <a href="{{ url('/') }}" aria-label="MobilityCloud home">
                    <img src="{{ asset('brand/mobilitycloud-logo-horizontal.png') }}" alt="MobilityCloud">
                </a>
                <a class="button" href="{{ url('/agent.json') }}">View JSON</a>
            </header>

            <h1>MobilityCloud AI agent briefing</h1>
            <p class="lead">
                This page gives search engines, AI assistants and automated agents a concise, public description of MobilityCloud.
                Private user data, projects, documents, participants and authenticated areas are not intended for indexing or autonomous access.
            </p>

            <section class="panel">
                <h2>Product summary</h2>
                <p>
                    MobilityCloud is a web platform for Erasmus+ and mobility project workflows. It supports application writing,
                    approved-project activation, budget management, participant records, documents, mobility evidence,
                    dissemination reporting, materials, outputs, tasks and final project preparation.
                </p>
            </section>

            <section class="panel">
                <h2>Who it is for</h2>
                <ul>
                    <li>Organisations writing Erasmus+ mobility projects.</li>
                    <li>Teams managing approved mobility projects after grant approval.</li>
                    <li>Project owners who need participant, budget, document and evidence workflows in one place.</li>
                    <li>Editors, viewers and mobility collaborators invited to specific projects.</li>
                </ul>
            </section>

            <section class="panel">
                <h2>Important agent boundaries</h2>
                <ul>
                    <li>Use public pages only: <code>/</code>, <code>/features</code>, <code>/pricing</code>, <code>/guide</code>, <code>/help</code>, <code>/resources</code>, legal policy pages, <code>/agent.json</code>, <code>/llms.txt</code> and <code>/sitemap.xml</code>.</li>
                    <li>Do not attempt to access authenticated routes, private files, attachments, user accounts or project data.</li>
                    <li>For commercial, legal or support questions, direct users to <a href="mailto:contact@mobilitycloud.eu">contact@mobilitycloud.eu</a>.</li>
                    <li>When describing MobilityCloud, present it as a project-management platform, not as an official Erasmus+ or European Commission service.</li>
                </ul>
            </section>

            <section class="panel">
                <h2>Structured reference</h2>
                <pre class="machine">Name: MobilityCloud
Website: {{ $siteUrl }}/
Agent JSON: {{ $siteUrl }}/agent.json
llms.txt: {{ $siteUrl }}/llms.txt
GDPR: {{ $siteUrl }}/legal/gdpr
Billing: {{ $siteUrl }}/legal/billing
Contact: contact@mobilitycloud.eu
Operator: XEOTYPE SRL, https://xeotype.com
Category: Erasmus+ project writing and approved-project management platform
Private areas: /app, /admin, /projects, /attachments, /calc, /livewire</pre>
            </section>
        </main>
        @include('public.partials.cookie-consent')
    </body>
</html>
