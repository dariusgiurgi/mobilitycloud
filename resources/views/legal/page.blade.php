<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · MobilityCloud</title>
    <style>
        :root { color-scheme: light; font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        body { margin:0; background:#f8fafc; color:#0f172a; }
        main { max-width:860px; margin:0 auto; padding:56px 20px 72px; }
        nav { display:flex; gap:14px; flex-wrap:wrap; margin-bottom:34px; font-size:14px; }
        nav a { color:#4f46e5; text-decoration:none; font-weight:650; }
        .card { background:white; border:1px solid #e2e8f0; border-radius:28px; box-shadow:0 24px 70px rgba(15,23,42,.08); overflow:hidden; }
        .hero { padding:34px 38px; border-bottom:1px solid #e2e8f0; background:linear-gradient(135deg,#eef2ff,#ffffff); }
        .content { padding:34px 38px; display:grid; gap:24px; line-height:1.7; }
        h1 { margin:0; font-size:clamp(34px,5vw,54px); line-height:1; letter-spacing:-.05em; }
        h2 { margin:0 0 8px; font-size:20px; letter-spacing:-.02em; }
        p { margin:0; color:#475569; }
        .badge { display:inline-flex; width:max-content; padding:7px 10px; border-radius:999px; background:#fff7ed; color:#c2410c; font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; margin-bottom:18px; }
        .muted { color:#64748b; font-size:14px; }
        .company { display:grid; gap:6px; padding:18px; border-radius:18px; background:#f8fafc; border:1px solid #e2e8f0; }
        footer { margin-top:22px; color:#64748b; font-size:13px; text-align:center; }
        @media (max-width:640px) { .hero,.content { padding:26px 22px; } }
    </style>
</head>
<body>
<main>
    <nav aria-label="Legal pages">
        <a href="{{ url('/') }}">MobilityCloud</a>
        <a href="{{ route('legal.terms') }}">Terms</a>
        <a href="{{ route('legal.privacy') }}">Privacy</a>
        <a href="{{ route('legal.cookies') }}">Cookies</a>
        <a href="{{ route('legal.security') }}">Security</a>
        <a href="mailto:{{ $emails['contact'] }}">Contact</a>
    </nav>

    <article class="card">
        <section class="hero">
            @if (blank($company['legal_name']) || blank($company['registration_number']) || blank($company['address']))
                <span class="badge">Draft pending company details</span>
            @else
                <span class="badge" style="background:#ecfdf5;color:#047857;">Official company details</span>
            @endif
            <h1>{{ $title }}</h1>
            <p class="muted" style="margin-top:16px;">Last updated: {{ now()->format('d M Y') }}</p>
        </section>

        <section class="content">
            @if ($type === 'terms')
                <div>
                    <h2>Use of the platform</h2>
                    <p>MobilityCloud helps users prepare, manage and organise Erasmus+ mobility project information. The platform does not replace official programme guidance, National Agency decisions, accounting advice or legal advice.</p>
                </div>
                <div>
                    <h2>Accounts and project information</h2>
                    <p>Users are responsible for keeping account details accurate, maintaining the confidentiality of login credentials, and verifying all project, budget and participant information before official submission or reporting.</p>
                </div>
                <div>
                    <h2>Fees after approval</h2>
                    <p>Where a project activation fee applies, it is calculated from the approved grant amount declared by the project owner. Fiscal invoicing and payment terms are handled manually until online payments are introduced.</p>
                </div>
            @elseif ($type === 'privacy')
                <div>
                    <h2>Personal data we process</h2>
                    <p>MobilityCloud may store account details, billing details, project data, participant data, uploaded documents, operational logs and communication records needed to provide the service.</p>
                </div>
                <div>
                    <h2>Purpose of processing</h2>
                    <p>Data is processed to authenticate users, provide project management features, send operational emails, support billing, maintain security and improve the reliability of the platform.</p>
                </div>
                <div>
                    <h2>Retention and support</h2>
                    <p>Operational records are retained only as needed for the service, legal obligations and security. Users may contact support for access, correction or deletion requests, subject to legal retention requirements.</p>
                </div>
            @elseif ($type === 'cookies')
                <div>
                    <h2>Essential cookies</h2>
                    <p>The platform uses essential cookies and session storage for login, security, preferences and application functionality. These are required for the service to work correctly.</p>
                </div>
                <div>
                    <h2>Analytics and marketing</h2>
                    <p>No optional analytics or marketing cookies should be enabled until a consent mechanism and provider list are configured.</p>
                </div>
            @else
                <div>
                    <h2>Security model</h2>
                    <p>MobilityCloud uses authenticated access, role-based permissions, private file delivery, server firewalling, encrypted HTTPS traffic, operational backups and admin audit trails.</p>
                </div>
                <div>
                    <h2>Reporting issues</h2>
                    <p>Please report suspected security or data protection issues immediately to {{ $emails['support'] }} with enough context for investigation.</p>
                </div>
            @endif

            <div class="company">
                <strong>Company details</strong>
                <span>{{ $company['legal_name'] ?: $company['name'] ?: 'To be completed' }}</span>
                <span>{{ $company['registration_number'] ? 'Registration: '.$company['registration_number'] : 'Registration: to be completed' }}</span>
                <span>{{ $company['vat_number'] ? 'VAT: '.$company['vat_number'] : 'VAT: to be completed' }}</span>
                <span>{{ $company['address'] ?: 'Address: to be completed' }}</span>
                <span>Contact: <a href="mailto:{{ $company['email'] ?: $emails['contact'] }}">{{ $company['email'] ?: $emails['contact'] }}</a></span>
            </div>
        </section>
    </article>

    @if (blank($company['legal_name']) || blank($company['registration_number']) || blank($company['address']))
        <footer>
            These pages are prepared for launch review and must be finalised with the company details before public release.
        </footer>
    @endif
</main>
</body>
</html>
