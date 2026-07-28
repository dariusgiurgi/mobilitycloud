@php
    $companyName = $company['legal_name'] ?: $company['name'] ?: 'XEOTYPE SRL';
    $companyEmail = $company['email'] ?: $emails['contact'];
    $supportEmail = $emails['support'] ?: $emails['contact'];
    $billingEmail = $emails['billing'] ?: $emails['contact'];
    $contactEmail = $emails['contact'];
    $effectiveDate = '28 Jul 2026';
    $xeotypeUrl = 'https://xeotype.com';
@endphp

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · MobilityCloud</title>
    <meta name="description" content="{{ $title }} for MobilityCloud, the Erasmus+ mobility project management platform powered by Xeotype.">
    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="icon" type="image/png" href="{{ asset('brand/favicon-64.png') }}">
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
                        <a href="#preferences">Preferences</a>
                        <a href="#third-party">Third-party cookies</a>
                        <a href="#control">How to control cookies</a>
                    @else
                        <a href="#commitment">Security commitment</a>
                        <a href="#access">Access control</a>
                        <a href="#files">Files and data</a>
                        <a href="#operations">Operations</a>
                        <a href="#user">User responsibilities</a>
                        <a href="#incidents">Incidents</a>
                        <a href="#reporting">Report an issue</a>
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
                            <p>Where an administration fee applies, it is currently calculated as 1% of the approved grant amount, with a minimum fee of €100 per approved project, unless another written agreement or unlimited access arrangement applies.</p>
                            <ul>
                                <li>Invoices are issued manually as fiscal invoices by {{ $companyName }}.</li>
                                <li>Payment is due by the due date shown on the fiscal invoice or payment notice.</li>
                                <li>Access may remain active until the due date even if the invoice has not yet been paid.</li>
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

                        <section id="preferences" class="legal-section">
                            <h2>3. Preference storage</h2>
                            <p>The platform may store interface preferences such as theme, layout state, dismissed notices, filters or user experience settings. These preferences are used to make the platform easier to use and are not used for behavioural advertising.</p>
                        </section>

                        <section id="third-party" class="legal-section">
                            <h2>4. Analytics, marketing and third-party cookies</h2>
                            <p>At launch, MobilityCloud should not enable optional analytics, advertising or marketing cookies unless a consent mechanism and a clear provider list are configured.</p>
                            <p>If optional analytics, product analytics, chat widgets, embedded media, payment widgets or marketing tools are introduced later, this Cookie Policy should be updated before those tools are activated.</p>
                        </section>

                        <section id="control" class="legal-section">
                            <h2>5. How users can control cookies</h2>
                            <p>Users can control cookies through browser settings. Blocking essential cookies may prevent login, file uploads, exports, password reset, email verification or other platform features from working correctly.</p>
                            <p>For questions about cookies, contact <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>.</p>
                        </section>
                    @else
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
        </p>
    </div>
</main>
</body>
</html>
