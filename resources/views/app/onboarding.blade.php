<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Welcome to MobilityCloud</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f8fafc;
            --panel: #ffffff;
            --ink: #0f172a;
            --muted: #64748b;
            --line: rgba(148, 163, 184, .26);
            --brand: #4f46e5;
            --brand-dark: #4338ca;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            background:
                radial-gradient(circle at 20% 10%, rgba(79, 70, 229, .12), transparent 30rem),
                radial-gradient(circle at 90% 20%, rgba(14, 165, 233, .1), transparent 28rem),
                var(--bg);
            color: var(--ink);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        .shell { min-height: 100vh; display: grid; grid-template-columns: 248px minmax(0, 1fr); }
        .sidebar {
            padding: 1.25rem;
            border-right: 1px solid var(--line);
            background: rgba(255, 255, 255, .76);
            backdrop-filter: blur(16px);
        }
        .brand { display: flex; align-items: center; gap: .7rem; font-weight: 800; letter-spacing: -.03em; }
        .brand-mark { width: 38px; height: 38px; display: grid; place-items: center; border-radius: .9rem; background: #0f172a; color: white; }
        .nav { margin-top: 2rem; display: grid; gap: .45rem; }
        .nav-item {
            display: flex;
            align-items: center;
            gap: .7rem;
            padding: .72rem .78rem;
            border-radius: .8rem;
            color: #475569;
            text-decoration: none;
            font-size: .88rem;
            font-weight: 650;
        }
        .nav-item.active { background: rgba(79, 70, 229, .1); color: var(--brand-dark); }
        .nav-item.muted { color: #94a3b8; }
        .invite-panel { margin-top: .55rem; display: grid; gap: .55rem; }
        .invite-card {
            padding: .75rem;
            border: 1px solid var(--line);
            border-radius: .85rem;
            background: rgba(255, 255, 255, .76);
        }
        .invite-card strong { display: block; font-size: .8rem; line-height: 1.35; color: #0f172a; }
        .invite-card span { display: block; margin-top: .22rem; font-size: .72rem; color: var(--muted); line-height: 1.4; }
        .invite-card a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-top: .55rem;
            padding: .45rem .6rem;
            border-radius: .6rem;
            background: rgba(79, 70, 229, .1);
            color: var(--brand-dark);
            text-decoration: none;
            font-size: .72rem;
            font-weight: 800;
        }
        .empty-note {
            padding: .75rem;
            border: 1px dashed rgba(148, 163, 184, .44);
            border-radius: .85rem;
            color: var(--muted);
            font-size: .75rem;
            line-height: 1.45;
        }
        .account {
            margin-top: 2rem;
            padding: .9rem;
            border: 1px solid var(--line);
            border-radius: 1rem;
            background: rgba(255, 255, 255, .72);
            font-size: .78rem;
            color: var(--muted);
            overflow-wrap: anywhere;
        }
        main { display: grid; place-items: center; padding: 2rem; }
        .card {
            width: min(880px, 100%);
            padding: 2rem;
            border: 1px solid var(--line);
            border-radius: 1.35rem;
            background: rgba(255, 255, 255, .9);
            box-shadow: 0 24px 70px rgba(15, 23, 42, .08);
        }
        .eyebrow {
            color: var(--brand);
            font-size: .78rem;
            font-weight: 800;
            letter-spacing: .14em;
            text-transform: uppercase;
        }
        h1 {
            margin: .55rem 0 0;
            max-width: 680px;
            font-size: clamp(2rem, 5vw, 3.7rem);
            line-height: .98;
            letter-spacing: -.07em;
        }
        .lead {
            max-width: 650px;
            margin: 1rem 0 0;
            color: var(--muted);
            font-size: 1rem;
            line-height: 1.7;
        }
        .actions { display: flex; flex-wrap: wrap; gap: .75rem; margin-top: 1.5rem; }
        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            padding: .72rem 1rem;
            border-radius: .85rem;
            border: 1px solid transparent;
            font-size: .9rem;
            font-weight: 750;
            text-decoration: none;
            cursor: pointer;
        }
        .button.primary { background: var(--brand); color: white; }
        .button.primary:hover { background: var(--brand-dark); }
        .button.secondary { background: white; color: #334155; border-color: var(--line); }
        .grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .85rem; margin-top: 1.6rem; }
        .tile { padding: 1rem; border: 1px solid var(--line); border-radius: 1rem; background: rgba(248, 250, 252, .85); }
        .tile strong { display: block; font-size: .9rem; }
        .tile span { display: block; margin-top: .35rem; color: var(--muted); font-size: .8rem; line-height: 1.45; }
        .setup-form {
            margin-top: 1.4rem;
            padding: 1rem;
            border: 1px solid var(--line);
            border-radius: 1rem;
            background: rgba(248, 250, 252, .85);
        }
        .setup-form label { display: block; font-size: .78rem; font-weight: 800; color: #334155; }
        .setup-row { display: flex; gap: .65rem; margin-top: .55rem; }
        .setup-row input {
            flex: 1;
            min-width: 0;
            min-height: 44px;
            border: 1px solid var(--line);
            border-radius: .85rem;
            padding: .7rem .85rem;
            font: inherit;
            background: white;
            color: var(--ink);
        }
        .error { margin-top: .45rem; color: #dc2626; font-size: .78rem; }
        .footer-note { margin-top: 1.3rem; color: var(--muted); font-size: .8rem; }
        @media (max-width: 860px) {
            .shell { grid-template-columns: 1fr; }
            .sidebar { border-right: 0; border-bottom: 1px solid var(--line); }
            .nav { grid-template-columns: repeat(2, minmax(0, 1fr)); margin-top: 1rem; }
            .grid { grid-template-columns: 1fr; }
            .setup-row { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="shell">
        <aside class="sidebar">
            <div class="brand">
                <div class="brand-mark">MC</div>
                <div>MobilityCloud</div>
            </div>

            <nav class="nav" aria-label="Account navigation">
                <a class="nav-item active" href="{{ route('app.onboarding') }}">Overview</a>
                <span class="nav-item muted">Invitations by email</span>
                <div class="invite-panel">
                    @forelse($pendingInvitations as $invitation)
                        <div class="invite-card">
                            <strong>{{ $invitation->project?->name ?? $invitation->workspace?->name ?? 'MobilityCloud invitation' }}</strong>
                            <span>
                                {{ $invitation->project ? 'Project access' : 'Organisation access' }}
                                @if($invitation->workspace) · {{ $invitation->workspace->name }} @endif
                            </span>
                            <span>Expires {{ $invitation->expires_at->format('d M Y') }}</span>
                            <a href="{{ route('workspace-invitations.accept', $invitation->token) }}">Accept invitation</a>
                        </div>
                    @empty
                        <div class="empty-note">No active invitations for this email address yet.</div>
                    @endforelse
                </div>
                <form method="POST" action="{{ route('filament.admin.auth.logout') }}">
                    @csrf
                    <button class="nav-item muted" style="width:100%;border:0;background:transparent;text-align:left;cursor:pointer;">Sign out</button>
                </form>
            </nav>

            <div class="account">
                Signed in as<br>
                <strong style="color:#0f172a;">{{ auth()->user()->email }}</strong>
            </div>
        </aside>

        <main>
            <section class="card">
                <div class="eyebrow">Account ready</div>
                <h1>You do not have an organisation yet.</h1>
                <p class="lead">
                    You can either set up your organisation and start owning projects, or wait until someone invites you directly to a project. You are not required to create anything just to use your account.
                </p>

                <div class="actions">
                    <a class="button primary" href="#setup-organisation">Set up your organisation</a>
                    <a class="button secondary" href="mailto:{{ config('mobilitycloud.emails.support', 'contact@mobilitycloud.eu') }}">Contact support</a>
                </div>

                <form id="setup-organisation" class="setup-form" method="POST" action="{{ route('app.organisations.store') }}">
                    @csrf
                    <label for="organisation-name">Organisation name</label>
                    <div class="setup-row">
                        <input id="organisation-name" name="name" value="{{ old('name') }}" placeholder="e.g. Scoala de Jocuri" required maxlength="255">
                        <button class="button primary" type="submit">Create organisation</button>
                    </div>
                    @error('name')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </form>

                <div class="grid">
                    <div class="tile">
                        <strong>Own projects</strong>
                        <span>Create an organisation only if you will manage the subscription and project portfolio.</span>
                    </div>
                    <div class="tile">
                        <strong>Join by invitation</strong>
                        <span>If someone invites you to a project, the access will appear after you accept the email link.</span>
                    </div>
                    <div class="tile">
                        <strong>Simple roles</strong>
                        <span>Project access is handled by invitation: Editor or Viewer.</span>
                    </div>
                </div>

                <p class="footer-note">No sample data is created automatically. Your account stays clean until you decide what to do next.</p>
            </section>
        </main>
    </div>
</body>
</html>
