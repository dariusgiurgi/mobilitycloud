@php
    $companyName = $company['legal_name'] ?: $company['name'] ?: 'XEOTYPE SRL';
    $companyEmail = $company['email'] ?: $emails['contact'];
    $supportEmail = $emails['support'] ?: $emails['contact'];
    $billingEmail = $emails['billing'] ?: $emails['contact'];
    $contactEmail = $emails['contact'];
    $effectiveDate = '28 Jul 2026';
    $xeotypeUrl = 'https://xeotype.com';
    $siteUrl = rtrim(config('app.url', 'https://mobilitycloud.eu'), '/');
    $pageUrl = url()->current();
    $description = $title . ' for MobilityCloud, the Erasmus+ mobility project management platform powered by Xeotype.';
    $socialImage = asset('brand/mobilitycloud-logo-powered-xeotype.png');
    $structuredData = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'Organization',
                '@id' => $siteUrl . '/#organization',
                'name' => 'MobilityCloud',
                'url' => $siteUrl . '/',
                'email' => $contactEmail,
                'legalName' => $companyName,
                'parentOrganization' => [
                    '@type' => 'Organization',
                    'name' => 'XEOTYPE SRL',
                    'url' => $xeotypeUrl,
                ],
            ],
            [
                '@type' => 'WebPage',
                '@id' => $pageUrl . '#webpage',
                'url' => $pageUrl,
                'name' => $title . ' · MobilityCloud',
                'description' => $description,
                'isPartOf' => [
                    '@id' => $siteUrl . '/#website',
                ],
                'publisher' => [
                    '@id' => $siteUrl . '/#organization',
                ],
                'inLanguage' => 'en',
            ],
            [
                '@type' => 'WebSite',
                '@id' => $siteUrl . '/#website',
                'url' => $siteUrl . '/',
                'name' => 'MobilityCloud',
                'publisher' => [
                    '@id' => $siteUrl . '/#organization',
                ],
                'inLanguage' => 'en',
            ],
        ],
    ];
@endphp

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · MobilityCloud</title>
    <meta name="description" content="{{ $description }}">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <link rel="canonical" href="{{ $pageUrl }}">
    <meta property="og:site_name" content="MobilityCloud">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $title }} · MobilityCloud">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $pageUrl }}">
    <meta property="og:image" content="{{ $socialImage }}">
    <meta property="og:image:width" content="1100">
    <meta property="og:image:height" content="187">
    <meta property="og:image:alt" content="MobilityCloud powered by Xeotype">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title }} · MobilityCloud">
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
            background:
                radial-gradient(circle at 5% -10%, rgba(56,213,255,.22), transparent 28rem),
                radial-gradient(circle at 95% 0%, rgba(79,70,229,.16), transparent 26rem),
                #f8fafc;
            color:var(--mc-ink);
            -webkit-font-smoothing:antialiased;
        }
        a { color:var(--mc-indigo); text-decoration:none; font-weight:750; }
        a:hover { text-decoration:underline; }
        .shell { width:min(1080px, calc(100% - 36px)); margin:0 auto; }
        .topbar {
            position:sticky;
            top:0;
            z-index:10;
            border-bottom:1px solid rgba(226,232,240,.86);
            background:rgba(255,255,255,.86);
            backdrop-filter:blur(18px);
        }
        .nav { min-height:74px; display:flex; align-items:center; justify-content:space-between; gap:1rem; }
        .brand img { width:236px; max-width:100%; }
        .links { display:flex; align-items:center; gap:.45rem; flex-wrap:wrap; }
        .links a {
            color:#475569;
            padding:.52rem .7rem;
            border-radius:999px;
            font-size:.88rem;
            font-weight:820;
        }
        .links a.active { color:var(--mc-indigo); background:#eef2ff; text-decoration:none; }
        main { padding:54px 0 72px; }
        .card {
            background:white;
            border:1px solid rgba(148,163,184,.22);
            border-radius:30px;
            box-shadow:0 24px 70px rgba(15,23,42,.08);
            overflow:hidden;
        }
        .hero {
            padding:38px 42px;
            border-bottom:1px solid var(--mc-line);
            background:linear-gradient(135deg,#eef2ff 0%,#ffffff 58%,#effcff 100%);
        }
        .badge {
            display:inline-flex;
            align-items:center;
            gap:.45rem;
            width:max-content;
            max-width:100%;
            padding:.48rem .72rem;
            border-radius:999px;
            background:#eef2ff;
            color:var(--mc-indigo);
            font-size:.72rem;
            font-weight:950;
            text-transform:uppercase;
            letter-spacing:.08em;
        }
        .badge::before {
            content:"";
            width:.5rem;
            height:.5rem;
            border-radius:999px;
            background:linear-gradient(135deg,var(--mc-cyan),var(--mc-indigo));
            box-shadow:0 0 0 5px rgba(56,213,255,.13);
        }
        h1 {
            margin:1rem 0 0;
            font-size:clamp(2.4rem,5.4vw,5.1rem);
            line-height:.96;
            letter-spacing:-.07em;
            max-width:12ch;
        }
        .intro {
            margin-top:1rem;
            color:#52627a;
            line-height:1.65;
            font-size:1rem;
            max-width:48rem;
        }
        .meta {
            margin-top:1rem;
            display:flex;
            flex-wrap:wrap;
            gap:.6rem;
            color:#64748b;
            font-size:.88rem;
        }
        .meta span {
            padding:.45rem .65rem;
            border-radius:999px;
            background:rgba(255,255,255,.7);
            border:1px solid rgba(148,163,184,.18);
        }
        .content {
            display:grid;
            grid-template-columns:260px minmax(0,1fr);
            gap:2rem;
            padding:34px 42px 42px;
            line-height:1.72;
        }
        .toc {
            position:sticky;
            top:104px;
            align-self:start;
            padding:1rem;
            border:1px solid var(--mc-line);
            border-radius:22px;
            background:#fbfdff;
        }
        .toc strong {
            display:block;
            margin-bottom:.55rem;
            font-size:.75rem;
            text-transform:uppercase;
            letter-spacing:.08em;
            color:#475569;
        }
        .toc a {
            display:block;
            padding:.34rem 0;
            color:#64748b;
            font-size:.86rem;
            font-weight:720;
        }
        .legal-body { display:grid; gap:1.55rem; min-width:0; }
        section.legal-section {
            padding-bottom:1.3rem;
            border-bottom:1px solid #edf2f7;
            scroll-margin-top:102px;
        }
        section.legal-section:last-child { border-bottom:0; padding-bottom:0; }
        h2 { margin:0 0 .55rem; font-size:1.35rem; letter-spacing:-.035em; }
        h3 { margin:1rem 0 .35rem; font-size:1rem; letter-spacing:-.02em; }
        p { margin:.55rem 0 0; color:#475569; }
        ul, ol { margin:.65rem 0 0; padding-left:1.15rem; color:#475569; }
        li { margin:.38rem 0; }
        .callout {
            padding:1rem;
            border-radius:20px;
            border:1px solid #c7d2fe;
            background:linear-gradient(135deg,#eef2ff,#ffffff);
            color:#334155;
        }
        .callout.warning {
            border-color:#fed7aa;
            background:linear-gradient(135deg,#fff7ed,#ffffff);
        }
        .company {
            display:grid;
            gap:.38rem;
            padding:1rem;
            border-radius:20px;
            background:#f8fafc;
            border:1px solid var(--mc-line);
        }
        .company span { color:#475569; }
        .footer-note {
            margin-top:22px;
            color:#64748b;
            text-align:center;
            font-size:.86rem;
        }
        @media (max-width:860px) {
            .nav { align-items:flex-start; flex-direction:column; padding:14px 0; }
            .links { gap:.2rem; }
            .brand img { width:210px; }
            .content { grid-template-columns:1fr; padding:26px 22px 32px; }
            .toc { position:static; }
            .hero { padding:30px 22px; }
        }
    </style>
</head>
<body>
<header class="topbar">
    <div class="shell nav">
        <a class="brand" href="{{ url('/') }}" aria-label="MobilityCloud homepage">
            <img src="{{ asset('brand/mobilitycloud-logo-powered-xeotype.png') }}" alt="MobilityCloud powered by Xeotype">
        </a>
        <nav class="links" aria-label="Legal pages">
            <a href="{{ url('/') }}">Home</a>
            <a href="{{ route('legal.terms') }}" @class(['active' => $type === 'terms'])>Terms</a>
            <a href="{{ route('legal.privacy') }}" @class(['active' => $type === 'privacy'])>Privacy</a>
            <a href="{{ route('legal.cookies') }}" @class(['active' => $type === 'cookies'])>Cookies</a>
            <a href="{{ route('legal.security') }}" @class(['active' => $type === 'security'])>Security</a>
            <a href="{{ route('legal.gdpr') }}" @class(['active' => $type === 'gdpr'])>GDPR</a>
            <a href="{{ route('legal.billing') }}" @class(['active' => $type === 'billing'])>Billing</a>
            <a href="{{ route('marketing.contact') }}">Contact</a>
        </nav>
    </div>
</header>

<main>
    <div class="shell">
        <article class="card">
            <section class="hero">
                <span class="badge">Official policy</span>
                <h1>{{ $title }}</h1>
                <p class="intro">
                    These terms describe how MobilityCloud is provided and how project, billing, participant,
                    document and mobility evidence workflows are handled. MobilityCloud is powered by
                    <a href="{{ $xeotypeUrl }}" target="_blank" rel="noopener">Xeotype</a>.
                </p>
                <div class="meta">
                    <span>Effective date: {{ $effectiveDate }}</span>
                    <span>Last updated: {{ $effectiveDate }}</span>
                    <span>Contact: {{ $contactEmail }}</span>
                </div>
            </section>

            <div class="content">
                <aside class="toc" aria-label="On this page">
                    <strong>On this page</strong>
                    @if ($type === 'terms')
                        <a href="#scope">Scope</a>
                        <a href="#accounts">Accounts</a>
                        <a href="#projects">Projects and collaboration</a>
                        <a href="#billing">Fees and invoices</a>
                        <a href="#content">Content and files</a>
                        <a href="#limits">Availability and liability</a>
                        <a href="#termination">Suspension and termination</a>
                        <a href="#law">Governing law</a>
                    @elseif ($type === 'privacy')
                        <a href="#controller">Controller</a>
                        <a href="#data">Data categories</a>
                        <a href="#purposes">Purposes and legal bases</a>
                        <a href="#participants">Participant data</a>
                        <a href="#sharing">Sharing and processors</a>
                        <a href="#retention">Retention</a>
                        <a href="#rights">Your rights</a>
                        <a href="#security">Security and incidents</a>
                    @elseif ($type === 'cookies')
                        <a href="#what">What cookies are</a>
                        <a href="#essential">Essential cookies</a>
                        <a href="#optional">Optional categories</a>
                        <a href="#consent">Consent and settings</a>
                        <a href="#providers">Third-party providers</a>
                        <a href="#control">How to control cookies</a>
                    @elseif ($type === 'security')
                        <a href="#commitment">Security commitment</a>
                        <a href="#access">Access control</a>
                        <a href="#files">Files and data</a>
                        <a href="#operations">Operations</a>
                        <a href="#user">User responsibilities</a>
                        <a href="#incidents">Incidents</a>
                        <a href="#reporting">Report an issue</a>
                    @elseif ($type === 'gdpr')
                        <a href="#scope">Scope</a>
                        <a href="#roles">Controller and processor roles</a>
                        <a href="#instructions">Processing instructions</a>
                        <a href="#data">Data categories</a>
                        <a href="#subprocessors">Subprocessors</a>
                        <a href="#security">Security measures</a>
                        <a href="#rights">Rights requests</a>
                        <a href="#transfers">Transfers</a>
                        <a href="#retention">Retention and deletion</a>
                        <a href="#breaches">Breach assistance</a>
                        <a href="#responsibilities">User responsibilities</a>
                    @else
                        <a href="#model">Pricing model</a>
                        <a href="#declaration">Approved grant declaration</a>
                        <a href="#invoices">Manual fiscal invoices</a>
                        <a href="#payment">Payment and access</a>
                        <a href="#unlimited">Unlimited access</a>
                        <a href="#billing-data">Billing data</a>
                        <a href="#corrections">Corrections and disputes</a>
                        <a href="#taxes">Taxes and records</a>
                        <a href="#changes">Changes</a>
                    @endif
                </aside>

                <div class="legal-body">
                    @if ($type === 'terms')
                        <section id="scope" class="legal-section">
                            <h2>1. Scope and relationship with MobilityCloud</h2>
                            <p>These Terms of Service govern access to and use of MobilityCloud, an independent software platform for preparing, organising and managing Erasmus+ mobility project workflows.</p>
                            <p>MobilityCloud is not an official Erasmus+ platform, not a National Agency system, and not a substitute for the Erasmus+ Programme Guide, official application forms, grant agreements, accounting advice, tax advice or legal advice.</p>
                            <div class="callout">
                                Users remain responsible for verifying all project information before submitting official applications, reports, budgets, supporting documents or evidence to any public authority, funder, partner organisation or auditor.
                            </div>
                        </section>

                        <section id="accounts" class="legal-section">
                            <h2>2. Accounts, verification and profile information</h2>
                            <ul>
                                <li>Users must provide accurate account information and keep their email address active.</li>
                                <li>Email verification may be required before access to certain features is granted.</li>
                                <li>Project owners must complete billing details before creating projects that may later generate a manual fiscal invoice.</li>
                                <li>Users are responsible for protecting login credentials, devices and browser sessions.</li>
                                <li>Administrators may suspend or restrict accounts in cases of suspected abuse, security risk, unpaid overdue invoices, invalid information or operational necessity.</li>
                            </ul>
                        </section>

                        <section id="projects" class="legal-section">
                            <h2>3. Projects, invitations and collaboration</h2>
                            <p>Projects belong to the account that creates them. Collaborators may be invited to individual projects and may receive editor, viewer or module-specific access, depending on the role selected by the project owner.</p>
                            <p>The project owner is responsible for access decisions, partner permissions, participant information, uploaded materials and the accuracy of declarations made in the project.</p>
                            <h3>Application and approved stages</h3>
                            <ul>
                                <li>Before approval, writing and planning tools may be used to prepare the project.</li>
                                <li>After approval, the project owner may declare the exact approved grant amount.</li>
                                <li>After the approved grant is declared, the writing module may become read-only and implementation modules may become available.</li>
                                <li>The approved grant amount is used to calculate the administration fee and should not be changed without support intervention.</li>
                            </ul>
                        </section>

                        <section id="billing" class="legal-section">
                            <h2>4. Fees, invoices and payment terms</h2>
                            <p>The current MobilityCloud model allows use of writing and planning tools before approval. When a project is marked as approved and the approved grant amount is declared, implementation modules unlock immediately so the team can start work without waiting for online payment.</p>
                            <p>Where an administration fee applies, it is currently calculated as 1% of the approved grant amount per approved project, unless another written agreement or unlimited access arrangement applies.</p>
                            <ul>
                                <li>Invoices are issued manually as fiscal invoices by {{ $companyName }}.</li>
                                <li>The invoice can be paid after the project owner receives the first grant instalment. The due date is shown on the fiscal invoice or payment notice.</li>
                                <li>Implementation access is available immediately after the approved grant is declared and remains available while the invoice is being handled.</li>
                                <li>If payment becomes overdue, MobilityCloud may suspend or restrict implementation access until payment is confirmed.</li>
                                <li>Unlimited accounts or manually approved partner accounts may be exempt from project administration fees.</li>
                                <li>Prices, fee models and included features may change for future projects, but already-issued invoices remain governed by their invoice terms.</li>
                            </ul>
                        </section>

                        <section id="content" class="legal-section">
                            <h2>5. User content, files and intellectual property</h2>
                            <p>Users keep ownership of the project data, text, documents, images, participant information, evidence links and files they upload or create in MobilityCloud. By using the service, users grant MobilityCloud the limited right to host, process, display, transmit, back up and secure that content as needed to provide the platform.</p>
                            <p>Users must not upload unlawful content, malicious files, content they are not authorised to process, or personal data for which they have no valid legal basis. Users are responsible for copyright, image rights, participant notices, consent where required, and partner permissions.</p>
                            <p>The MobilityCloud software, interface, brand, logos, templates, non-user documentation, product design and platform structure remain the property of {{ $companyName }} or its licensors.</p>
                        </section>

                        <section id="limits" class="legal-section">
                            <h2>6. Availability, changes and liability limits</h2>
                            <p>MobilityCloud is provided as an operational software service. The platform may be updated, improved, temporarily unavailable, or limited for maintenance, security, abuse prevention or infrastructure reasons.</p>
                            <p>To the maximum extent permitted by applicable law, MobilityCloud is not liable for unsuccessful grant applications, rejected reports, errors in official forms, accounting treatment, tax treatment, partner disputes, funder decisions, loss caused by incorrect user data, or third-party service failures outside MobilityCloud’s reasonable control.</p>
                            <p>Nothing in these terms excludes liability that cannot be excluded under applicable law.</p>
                        </section>

                        <section id="termination" class="legal-section">
                            <h2>7. Suspension, termination and deletion</h2>
                            <ul>
                                <li>Users may request account or project deletion, subject to legal retention, billing, audit, fraud prevention and security obligations.</li>
                                <li>MobilityCloud may suspend accounts for security issues, serious misuse, suspected unlawful content, overdue payment, or breach of these terms.</li>
                                <li>When access is suspended, users may be shown a contact route instead of the normal platform modules.</li>
                                <li>Deleted files or projects may remain in backups for a limited retention period before automatic backup rotation removes them.</li>
                            </ul>
                        </section>

                        <section id="law" class="legal-section">
                            <h2>8. Governing law, disputes and contact</h2>
                            <p>These terms are governed by Romanian law, unless mandatory consumer or data protection rules provide otherwise. The parties will first try to resolve disputes by contacting {{ $contactEmail }}.</p>
                            <p>Operational, billing and legal notices may be sent by email to the addresses associated with the account or to {{ $companyEmail }}.</p>
                            <p>MobilityCloud may update these terms when the product, billing model, legal requirements or operational practices change. Material changes may be announced on the platform or by email.</p>
                        </section>
                    @elseif ($type === 'privacy')
                        <section id="controller" class="legal-section">
                            <h2>1. Controller and contact</h2>
                            <p>{{ $companyName }} operates MobilityCloud and acts as data controller for account, billing, platform administration and support data. In some project contexts, the project owner may also act as an independent controller for participant and project data entered into the platform.</p>
                            <p>For privacy requests, contact <a href="mailto:{{ $companyEmail }}">{{ $companyEmail }}</a> or <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>.</p>
                        </section>

                        <section id="data" class="legal-section">
                            <h2>2. Categories of personal data</h2>
                            <ul>
                                <li>Account data: name, email, password hash, verification status, role, language or interface preferences.</li>
                                <li>Billing data: legal billing name, address, country, tax/VAT identifiers, invoice status and payment records.</li>
                                <li>Project data: project names, application text, budgets, tasks, collaborators, partner organisations, project settings and activity logs.</li>
                                <li>Participant data: complete name, organisation, email, phone, country, travel or mobility information, optional identification documents and related forms where uploaded.</li>
                                <li>Files and evidence: documents, signed copies, receipts, photos, materials, dissemination evidence, links, comments and metadata.</li>
                                <li>Technical data: IP address, device/browser information, login/session logs, audit logs, error logs, security events and email delivery events.</li>
                                <li>Support data: messages, requests, administrative notes and communications needed to resolve issues.</li>
                            </ul>
                        </section>

                        <section id="purposes" class="legal-section">
                            <h2>3. Purposes and legal bases</h2>
                            <p>MobilityCloud processes personal data only where there is a valid purpose and legal basis, including:</p>
                            <ul>
                                <li>contract performance: creating accounts, authenticating users, providing project management features, exports, files and collaboration;</li>
                                <li>legitimate interests: platform security, fraud prevention, audit logs, product reliability, support, abuse prevention and service improvement;</li>
                                <li>legal obligations: invoicing, tax, accounting, legal retention and compliance requests;</li>
                                <li>consent, where required: optional communications, optional cookies, or specific participant data processing arranged by the project owner;</li>
                                <li>public interest or contractual project obligations, where applicable to the project owner’s Erasmus+ or grant-management activity.</li>
                            </ul>
                        </section>

                        <section id="participants" class="legal-section">
                            <h2>4. Participant data and partner organisations</h2>
                            <p>Project owners are responsible for ensuring that participants and partner organisations receive appropriate privacy information before their data is entered into MobilityCloud. This includes data collected through participant registration links, manual entry, CSV import, uploaded files or mobility evidence.</p>
                            <p>Where participant data includes minors, sensitive details, identification documents, images or special categories of data, the project owner must ensure that the processing is lawful, proportionate, necessary and supported by the correct notices, consents or other legal basis.</p>
                            <div class="callout warning">
                                MobilityCloud provides the technical storage and workflow tools. It does not decide what participant data a project owner is legally allowed or required to collect for a specific Erasmus+ project.
                            </div>
                        </section>

                        <section id="sharing" class="legal-section">
                            <h2>5. Sharing, processors and international transfers</h2>
                            <p>Data may be shared with authorised project collaborators, platform administrators, hosting providers, email delivery providers, backup/storage providers, support providers and professional advisers, only as needed for the service, security, billing or legal compliance.</p>
                            <p>MobilityCloud is hosted on European infrastructure where practicable. If a provider processes data outside the European Economic Area, appropriate safeguards such as contractual protections, transfer mechanisms or equivalent safeguards should be used where required by applicable law.</p>
                            <p>MobilityCloud does not sell personal data.</p>
                        </section>

                        <section id="retention" class="legal-section">
                            <h2>6. Retention and deletion</h2>
                            <ul>
                                <li>Account data is retained while the account is active and for a reasonable period after closure where needed for security, billing or legal reasons.</li>
                                <li>Invoice and accounting records may be retained for statutory accounting and tax periods.</li>
                                <li>Project files are retained while the project is active or until deletion is requested and permitted.</li>
                                <li>Audit, security and access logs may be retained to investigate misuse, protect the service and prove administrative actions.</li>
                                <li>Backups may retain deleted data temporarily until backup rotation removes it.</li>
                            </ul>
                        </section>

                        <section id="rights" class="legal-section">
                            <h2>7. Data subject rights</h2>
                            <p>Depending on applicable law and the context of processing, individuals may have rights to access, correction, deletion, restriction, objection, portability, withdrawal of consent and complaint to a supervisory authority.</p>
                            <p>Requests can be sent to <a href="mailto:{{ $companyEmail }}">{{ $companyEmail }}</a>. MobilityCloud may need to verify identity and may refer project-specific participant requests to the relevant project owner when that owner controls the data.</p>
                            <p>Individuals in Romania may also contact the Romanian National Supervisory Authority for Personal Data Processing (ANSPDCP).</p>
                        </section>

                        <section id="security" class="legal-section">
                            <h2>8. Security, incidents and automated decisions</h2>
                            <p>MobilityCloud uses technical and organisational measures such as authenticated access, role-based permissions, HTTPS, private file delivery, server firewalling, backups, audit trails and administrative access controls.</p>
                            <p>No system can be guaranteed perfectly secure. Suspected incidents should be reported immediately to <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>. Where legally required, MobilityCloud will assess and handle breach notification obligations.</p>
                            <p>MobilityCloud does not use personal data for decisions that produce legal or similarly significant effects solely by automated means.</p>
                        </section>
                    @elseif ($type === 'cookies')
                        <section id="what" class="legal-section">
                            <h2>1. What cookies and similar technologies are</h2>
                            <p>Cookies are small files stored by a browser. Similar technologies include local storage, session storage and security tokens. They help a website remember sessions, preferences and security state.</p>
                        </section>

                        <section id="essential" class="legal-section">
                            <h2>2. Essential cookies used by MobilityCloud</h2>
                            <p>MobilityCloud uses essential cookies and similar technologies that are required for the platform to work. These may include:</p>
                            <ul>
                                <li>authentication/session cookies that keep users signed in;</li>
                                <li>CSRF/security tokens that protect forms and requests;</li>
                                <li>email verification, password reset and invitation flow tokens;</li>
                                <li>temporary state needed for file uploads, exports and platform interactions.</li>
                            </ul>
                            <p>Essential cookies do not require optional consent because the service cannot function securely without them.</p>
                        </section>

                        <section id="optional" class="legal-section">
                            <h2>3. Optional cookie categories</h2>
                            <p>MobilityCloud separates optional cookies and similar technologies into clear categories so users can decide what is acceptable for them:</p>
                            <ul>
                                <li><strong>Preferences:</strong> interface choices such as dismissed notices, layout choices, language preferences or public-site settings.</li>
                                <li><strong>Analytics:</strong> privacy-conscious traffic measurement, page-performance insight, public-page usage and conversion measurement used to improve MobilityCloud.</li>
                                <li><strong>Marketing:</strong> future campaign measurement, embedded media, partner campaign attribution or remarketing technologies, only if such tools are intentionally activated.</li>
                            </ul>
                            <p>Optional categories should not be loaded before consent is given for the relevant category. Refusing optional cookies should not prevent access to the public website or essential platform functions.</p>
                        </section>

                        <section id="consent" class="legal-section">
                            <h2>4. Consent and settings</h2>
                            <p>The public MobilityCloud website may show a cookie banner that allows users to accept all optional cookies, reject optional cookies or customise their choices by category.</p>
                            <p>The selected choice is stored in the browser as <strong>mc_cookie_consent</strong> for up to 180 days, unless the user clears cookies earlier. This consent record stores the selected categories, a version number and the update time. It is used only to remember the user’s cookie choice.</p>
                            <p>Users can reopen the cookie panel through the Cookie settings link where available. Essential cookies remain active because they are needed for security, login, form protection and core platform operation.</p>
                        </section>

                        <section id="providers" class="legal-section">
                            <h2>5. Analytics, marketing and third-party providers</h2>
                            <p>At launch, MobilityCloud should not enable optional analytics, advertising or marketing cookies unless the consent mechanism, provider purpose, category and retention information are configured.</p>
                            <p>MobilityCloud may use <strong>Google Analytics 4</strong> for public website analytics after the user accepts the Analytics category. Google Analytics is intended to help understand public-page traffic, popular resources, source channels and conversion paths such as visits to pricing, contact or account creation pages.</p>
                            <p>Google Analytics should be configured with analytics consent denied by default, advertising storage denied, ad personalisation disabled and Google signals disabled unless a separate legal and product decision is made later. The Google tag may be present on public pages to apply the consent state correctly, but analytics storage and measurement remain denied until the user accepts Analytics cookies. If the user rejects Analytics cookies, analytics storage remains denied and existing Google Analytics cookies are removed where technically possible.</p>
                            <p>If optional product analytics, chat widgets, embedded media, payment widgets, advertising pixels or marketing tools are introduced later, this Cookie Policy should be updated before those tools are activated. Where required, those tools should load only after the relevant cookie category has been accepted.</p>
                        </section>

                        <section id="control" class="legal-section">
                            <h2>6. How users can control cookies</h2>
                            <p>Users can control cookies through browser settings. Blocking essential cookies may prevent login, file uploads, exports, password reset, email verification or other platform features from working correctly.</p>
                            <p><a href="#" data-cookie-settings>Open Cookie settings</a> to review or change the optional categories stored by this browser.</p>
                            <p>For questions about cookies, contact <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>.</p>
                        </section>
                    @elseif ($type === 'security')
                        <section id="commitment" class="legal-section">
                            <h2>1. Security commitment</h2>
                            <p>MobilityCloud is designed to protect project data, participant records, uploaded evidence, billing information and account access through a practical security model suitable for a hosted project management platform.</p>
                        </section>

                        <section id="access" class="legal-section">
                            <h2>2. Access control and administrator safeguards</h2>
                            <ul>
                                <li>Users authenticate with individual accounts.</li>
                                <li>Email verification may be required before normal platform use.</li>
                                <li>Project access is invitation-based and scoped to specific projects.</li>
                                <li>Roles can limit editing, viewing or module-specific access.</li>
                                <li>Administrative panels are separated from user project modules.</li>
                                <li>Impersonation and sensitive administrative actions should require a reason and audit logging.</li>
                            </ul>
                        </section>

                        <section id="files" class="legal-section">
                            <h2>3. Files, documents and evidence</h2>
                            <p>Uploaded files are intended to be delivered through controlled application routes instead of public directory listing. Users should avoid uploading unnecessary sensitive data and should delete files that are no longer needed.</p>
                            <p>Project owners are responsible for reviewing permissions when inviting collaborators, facilitators, partner organisations or external contributors.</p>
                        </section>

                        <section id="operations" class="legal-section">
                            <h2>4. Operational controls</h2>
                            <ul>
                                <li>HTTPS should be enforced for public access.</li>
                                <li>Server firewall rules should restrict unnecessary inbound ports.</li>
                                <li>Backups should be monitored, rotated and stored with appropriate access restrictions.</li>
                                <li>Error logs and audit logs should be reviewed when incidents or unusual behaviour occur.</li>
                                <li>Production configuration should avoid debug output and should protect secrets, database credentials and mail credentials.</li>
                            </ul>
                        </section>

                        <section id="user" class="legal-section">
                            <h2>5. User responsibilities</h2>
                            <ul>
                                <li>Use strong, unique passwords and protect devices.</li>
                                <li>Do not share accounts between people.</li>
                                <li>Remove collaborators who no longer need access.</li>
                                <li>Verify participant links before sending them publicly.</li>
                                <li>Do not upload malware, unlawful files or unnecessary special-category data.</li>
                                <li>Report suspicious activity immediately.</li>
                            </ul>
                        </section>

                        <section id="incidents" class="legal-section">
                            <h2>6. Security incidents and limitations</h2>
                            <p>If MobilityCloud identifies a suspected security incident, it will investigate, contain the issue where practicable, preserve relevant logs, communicate with affected users where appropriate, and assess any legal notification obligations.</p>
                            <p>No online service can guarantee absolute security. Users should maintain their own copies of critical official files and should not rely on MobilityCloud as the sole archive for statutory or funder records.</p>
                        </section>

                        <section id="reporting" class="legal-section">
                            <h2>7. Reporting vulnerabilities or suspected misuse</h2>
                            <p>Please report suspected vulnerabilities, unauthorised access, exposed data, phishing, malware or account compromise to <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>. Include the affected URL, account email, project name where relevant, screenshots if safe, and steps to reproduce.</p>
                            <p>Do not access, modify, delete, download or disclose data that does not belong to you while investigating a potential vulnerability.</p>
                        </section>
                    @elseif ($type === 'gdpr')
                        <section id="scope" class="legal-section">
                            <h2>1. Scope of this Data Processing Summary</h2>
                            <p>This page explains the intended GDPR and data-processing position for MobilityCloud. It is designed to help users, partners, participants and automated agents understand the main roles, data flows and responsibilities around the platform.</p>
                            <div class="callout">
                                This public summary does not replace a signed data processing agreement where one is legally required. Project owners, public institutions, NGOs or partner organisations may request a project-specific data processing review by contacting <a href="mailto:{{ $companyEmail }}">{{ $companyEmail }}</a>.
                            </div>
                        </section>

                        <section id="roles" class="legal-section">
                            <h2>2. Controller and processor roles</h2>
                            <p>{{ $companyName }} operates MobilityCloud and generally acts as controller for account administration, billing, security, product operations, platform support, audit logs and legal compliance.</p>
                            <p>For project content, participant records, uploaded evidence, dissemination materials, partner data and documents entered by a user, the project owner is usually the organisation deciding why and how that data is processed. In those cases, {{ $companyName }} may act as a platform provider and processor, processing the data according to the project owner’s instructions and the functional choices made inside MobilityCloud.</p>
                            <p>Some situations may involve independent controller responsibilities, joint responsibilities or processor relationships depending on the customer type, contract, grant agreement, participant context and applicable law. Users remain responsible for obtaining their own advice where necessary.</p>
                        </section>

                        <section id="instructions" class="legal-section">
                            <h2>3. Processing instructions</h2>
                            <p>MobilityCloud processes project and participant content only to provide the platform, including authentication, project access, collaboration, participant intake links, document handling, mobility evidence, budgets, exports, final archive preparation, support, backup, security and administrative functionality.</p>
                            <ul>
                                <li>Project owners decide what project data is entered, which collaborators are invited and which participant fields are collected.</li>
                                <li>Collaborators should only access projects and modules for which they have a legitimate project role.</li>
                                <li>MobilityCloud may process logs, metadata and support context to keep the service secure and reliable.</li>
                                <li>MobilityCloud may refuse instructions that appear unlawful, unsafe, technically impossible or incompatible with platform security.</li>
                            </ul>
                        </section>

                        <section id="data" class="legal-section">
                            <h2>4. Data categories and data subjects</h2>
                            <p>Depending on how a project is configured, MobilityCloud may process:</p>
                            <ul>
                                <li>user and collaborator data, including names, emails, roles, verification status, session activity and administrative notes;</li>
                                <li>billing data, including legal entity details, tax identifiers, invoice status, approved grant values and payment state;</li>
                                <li>project data, including application text, language settings, budgets, tasks, activities, documents, generated files and final reporting materials;</li>
                                <li>participant data, including complete name, organisation, email, phone, mobility details, optional identification files, notes and participant intake submissions;</li>
                                <li>evidence data, including photos, links, videos, materials, outputs, dissemination records, receipts and supporting files;</li>
                                <li>technical data, including IP addresses, audit trails, device/browser metadata, error logs and security events.</li>
                            </ul>
                            <p>Data subjects may include account users, project owners, collaborators, participants, facilitators, partner organisation representatives, suppliers, trainers, volunteers, support contacts and invoice contacts.</p>
                        </section>

                        <section id="subprocessors" class="legal-section">
                            <h2>5. Subprocessors and service providers</h2>
                            <p>MobilityCloud may use carefully selected providers for hosting, server infrastructure, email delivery, storage, backups, security monitoring, error logging, support communication and professional services. These providers are used only to the extent necessary to operate, secure, support and improve the platform.</p>
                            <p>A current subprocessor list or provider summary can be requested from <a href="mailto:{{ $companyEmail }}">{{ $companyEmail }}</a>. Where legally required, MobilityCloud will take reasonable steps to ensure appropriate contractual protections with subprocessors.</p>
                            <p>MobilityCloud does not sell project data, participant data or account data.</p>
                        </section>

                        <section id="security" class="legal-section">
                            <h2>6. Security measures</h2>
                            <p>MobilityCloud applies technical and organisational measures appropriate for a hosted project platform, including individual accounts, email verification, role-based project access, controlled file delivery, HTTPS, server firewalling, backup practices, administrative separation, audit logs, password hashing and operational monitoring.</p>
                            <p>Users must also maintain security on their side: strong passwords, secure devices, careful sharing of participant intake links, removal of old collaborators and avoidance of unnecessary sensitive uploads.</p>
                        </section>

                        <section id="rights" class="legal-section">
                            <h2>7. Assistance with data subject rights</h2>
                            <p>Where MobilityCloud acts as processor for project data, it will reasonably assist the relevant project owner with access, rectification, deletion, restriction, portability, objection or consent-withdrawal requests, taking account of the nature of the processing and the information available to MobilityCloud.</p>
                            <p>Requests about account, billing or platform-administration data can be sent directly to <a href="mailto:{{ $companyEmail }}">{{ $companyEmail }}</a>. Requests about participant or project content may need to be handled by the project owner as the primary organisation responsible for that data.</p>
                        </section>

                        <section id="transfers" class="legal-section">
                            <h2>8. International transfers</h2>
                            <p>MobilityCloud aims to use European infrastructure where practicable. If a provider processes personal data outside the European Economic Area, appropriate safeguards should be used where required by GDPR or other applicable law, such as contractual protections, transfer mechanisms, risk assessment and supplementary measures where necessary.</p>
                        </section>

                        <section id="retention" class="legal-section">
                            <h2>9. Retention, return and deletion</h2>
                            <ul>
                                <li>Project data is retained while the project is active or until deletion is requested and permitted.</li>
                                <li>Billing and invoice records may be retained for statutory tax and accounting periods.</li>
                                <li>Security, audit and system logs may be retained for abuse prevention, troubleshooting, incident investigation and legal proof.</li>
                                <li>Backups may contain deleted data for a limited period until normal backup rotation removes it.</li>
                                <li>Where feasible, project owners can export or download relevant project files before deletion.</li>
                            </ul>
                        </section>

                        <section id="breaches" class="legal-section">
                            <h2>10. Personal data breach assistance</h2>
                            <p>If MobilityCloud becomes aware of a suspected personal data breach affecting project data, it will assess the situation, take reasonable containment steps, preserve relevant information and notify affected project owners or users where legally required or operationally appropriate.</p>
                            <p>Project owners are responsible for assessing their own notification obligations toward participants, partners, funders, National Agencies or supervisory authorities where they act as controller.</p>
                        </section>

                        <section id="responsibilities" class="legal-section">
                            <h2>11. Project owner responsibilities</h2>
                            <p>Project owners must ensure that their use of MobilityCloud is lawful. This includes providing privacy notices, choosing appropriate participant fields, avoiding unnecessary sensitive data, obtaining consent where required, respecting image rights and copyright, controlling collaborator access, and complying with Erasmus+, employment, child protection, accounting and data-protection obligations that apply to their organisation.</p>
                        </section>
                    @else
                        <section id="model" class="legal-section">
                            <h2>1. Pricing model</h2>
                            <p>MobilityCloud currently uses a manual project-activation and invoice model. The platform can be used for writing and planning before approval. When a project is approved, the project owner declares the exact approved grant amount and the relevant implementation modules unlock immediately.</p>
                            <p>Where a project administration fee applies, the standard launch model is 1% of the approved grant value per approved project, unless a different written agreement, unlimited access arrangement or manual owner decision applies.</p>
                        </section>

                        <section id="declaration" class="legal-section">
                            <h2>2. Approved grant declaration</h2>
                            <p>The approved grant value must be declared accurately when a project is marked as approved. This declaration is used to calculate the platform administration fee and to prepare manual invoice handling.</p>
                            <ul>
                                <li>The declared approved grant should match the amount approved by the funder or National Agency.</li>
                                <li>Users should not estimate, inflate, reduce or manipulate the value to change the platform fee.</li>
                                <li>After declaration, changes may require support or administrator intervention.</li>
                                <li>MobilityCloud may request clarification if a value appears incorrect or inconsistent with project context.</li>
                            </ul>
                        </section>

                        <section id="invoices" class="legal-section">
                            <h2>3. Manual fiscal invoices</h2>
                            <p>MobilityCloud does not currently process online card payments inside the platform. Fiscal invoices are handled manually by {{ $companyName }} using the billing details provided by the account owner.</p>
                            <p>The platform may show the invoice state for internal administration, such as pending invoice, invoice sent, paid, overdue, waived or unlimited access. The official fiscal invoice remains the document issued externally by {{ $companyName }} or its accounting process.</p>
                        </section>

                        <section id="payment" class="legal-section">
                            <h2>4. Payment, due dates and access</h2>
                            <p>After the approved grant is declared, implementation modules unlock immediately so the team can start managing participants, documents, budget evidence, mobility evidence and final reporting preparation without waiting for the invoice payment to be completed.</p>
                            <ul>
                                <li>The invoice can be paid after the first grant instalment arrives; its payment deadline is the due date written on the invoice or payment notice.</li>
                                <li>All implementation modules are available immediately after grant declaration while the invoice is being handled.</li>
                                <li>An overdue invoice remains visible for billing follow-up, but it does not automatically interrupt implementation access.</li>
                                <li>When an administrator confirms payment, the platform records the payment status and closes the billing follow-up.</li>
                            </ul>
                        </section>

                        <section id="unlimited" class="legal-section">
                            <h2>5. Unlimited, partner or manually approved access</h2>
                            <p>Some accounts may be manually marked as unlimited, partner, internal, demo or otherwise exempt from project administration fees. These accounts may receive full access without per-project invoicing, depending on {{ $companyName }}’s internal decision or written agreement.</p>
                            <p>Unlimited access is not a public entitlement unless expressly granted. {{ $companyName }} may review, change or revoke manual access settings where necessary for security, misuse prevention, commercial reasons or operational correction.</p>
                        </section>

                        <section id="billing-data" class="legal-section">
                            <h2>6. Billing data requirements</h2>
                            <p>Users who create projects must provide accurate billing information before project creation or before a project can be activated for approved management. Required billing details may include legal name, organisation name, fiscal code/VAT number, registration details, address, country and invoice contact email.</p>
                            <p>Incorrect or incomplete billing information can delay invoice issuing, block project creation, prevent activation or require administrator correction.</p>
                        </section>

                        <section id="corrections" class="legal-section">
                            <h2>7. Corrections, disputes and support</h2>
                            <p>If the approved grant amount, billing data, invoice status or payment state is incorrect, the user should contact <a href="mailto:{{ $billingEmail }}">{{ $billingEmail }}</a> as soon as possible. MobilityCloud may ask for supporting information before changing values that affect billing or access.</p>
                            <p>Invoice disputes should be raised before the due date when possible. Raising a dispute does not automatically cancel the invoice, but {{ $companyName }} will review reasonable correction requests.</p>
                        </section>

                        <section id="taxes" class="legal-section">
                            <h2>8. Taxes, VAT and accounting records</h2>
                            <p>Taxes, VAT treatment, invoice wording and accounting records are handled according to applicable law and the fiscal information available at the time of issuing. Users are responsible for ensuring that the billing information they provide is correct for their organisation or legal entity.</p>
                            <p>MobilityCloud is not accounting software and does not provide tax advice. Users should keep their own fiscal records and consult their accountant where necessary.</p>
                        </section>

                        <section id="changes" class="legal-section">
                            <h2>9. Changes to pricing and billing operations</h2>
                            <p>{{ $companyName }} may change pricing, fee percentages, minimum fees, access rules, billing flows or included features for future projects. Already-issued invoices remain governed by their invoice terms and any written agreement in place at the time.</p>
                            <p>Questions about commercial access, partnerships, manual invoice handling or special arrangements should be sent to <a href="mailto:{{ $billingEmail }}">{{ $billingEmail }}</a>.</p>
                        </section>
                    @endif

                    <section class="legal-section">
                        <h2>Company details</h2>
                        <div class="company">
                            <strong>{{ $companyName }}</strong>
                            <span>Registration number: {{ $company['registration_number'] ?: 'J24/1044/2023' }}</span>
                            <span>VAT/CIF: {{ $company['vat_number'] ?: 'RO48497754' }}</span>
                            <span>Address: {{ $company['address'] ?: 'Municipiul Sighetu Marmației, Strada Dragoș Vodă, Nr. 185, Județ Maramureș, Romania' }}</span>
                            <span>Country: {{ $company['country'] ?: 'Romania' }}</span>
                            <span>Company contact: <a href="mailto:{{ $companyEmail }}">{{ $companyEmail }}</a></span>
                            <span>MobilityCloud contact: <a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a></span>
                            <span>Billing: <a href="mailto:{{ $billingEmail }}">{{ $billingEmail }}</a></span>
                        </div>
                    </section>

                    <section class="legal-section">
                        <h2>Legal review note</h2>
                        <p>These documents are prepared as launch-ready platform policies. Because legal requirements can depend on the exact customer type, data flows, payment model, processors and jurisdictions involved, {{ $companyName }} should periodically review them with qualified legal counsel.</p>
                    </section>
                </div>
            </div>
        </article>

        <p class="footer-note">
            © {{ now()->year }} MobilityCloud. Powered by <a href="{{ $xeotypeUrl }}" target="_blank" rel="noopener">Xeotype</a>.
            <span aria-hidden="true"> · </span><a href="#" data-cookie-settings>Cookie settings</a>
        </p>
    </div>
</main>
@include('public.partials.cookie-consent')
</body>
</html>
