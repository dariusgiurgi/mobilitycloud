@php
    $user = auth()->user();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Account suspended · MobilityCloud</title>
    <style>
        :root {
            color-scheme: dark;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            background:
                radial-gradient(circle at top left, rgba(251, 191, 36, 0.18), transparent 34rem),
                radial-gradient(circle at bottom right, rgba(99, 102, 241, 0.16), transparent 32rem),
                #020617;
            color: #f8fafc;
        }

        main {
            display: flex;
            min-height: 100vh;
            align-items: center;
            justify-content: center;
            padding: 3rem 1.5rem;
        }

        .card {
            width: 100%;
            max-width: 42rem;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.10);
            border-radius: 1.5rem;
            background: rgba(255, 255, 255, 0.045);
            box-shadow: 0 24px 80px rgba(0, 0, 0, 0.35);
            backdrop-filter: blur(18px);
        }

        .card-header {
            border-bottom: 1px solid rgba(255, 255, 255, 0.10);
            background: rgba(255, 255, 255, 0.035);
            padding: 2rem;
        }

        .eyebrow {
            margin: 0;
            color: #fcd34d;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.28em;
            text-transform: uppercase;
        }

        h1 {
            margin: 0.85rem 0 0;
            font-size: clamp(1.8rem, 5vw, 2.35rem);
            line-height: 1.1;
            letter-spacing: -0.035em;
        }

        .card-body {
            display: grid;
            gap: 1.5rem;
            padding: 2rem;
        }

        p {
            margin: 0;
            color: #cbd5e1;
            line-height: 1.7;
        }

        .notice {
            border: 1px solid rgba(252, 211, 77, 0.28);
            border-radius: 1rem;
            background: rgba(252, 211, 77, 0.10);
            padding: 1.15rem;
        }

        .notice strong,
        .email {
            color: #ffffff;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        a,
        button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 2.85rem;
            border-radius: 0.85rem;
            padding: 0.75rem 1.15rem;
            font: inherit;
            font-size: 0.92rem;
            font-weight: 700;
            text-decoration: none;
            transition: transform 150ms ease, background-color 150ms ease, border-color 150ms ease;
        }

        a:hover,
        button:hover {
            transform: translateY(-1px);
        }

        .primary {
            border: 0;
            background: #fcd34d;
            color: #020617;
        }

        .secondary {
            border: 1px solid rgba(255, 255, 255, 0.16);
            background: transparent;
            color: #ffffff;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <main>
        <section class="card">
            <div class="card-header">
                <p class="eyebrow">Account access paused</p>
                <h1>Your MobilityCloud account is currently suspended.</h1>
            </div>

            <div class="card-body">
                <p>
                    Access to the platform modules is temporarily disabled for this account. This usually happens when an administrator needs to review billing, workspace access, security, or account configuration.
                </p>

                <div class="notice">
                    <p><strong>What you can do now</strong></p>
                    @if($user?->suspension_category)
                        <p>
                            Review category:
                            <strong>{{ \App\Filament\Resources\PlatformUsers\PlatformUserResource::suspensionCategoryOptions()[$user->suspension_category] ?? str($user->suspension_category)->replace('_', ' ')->title() }}</strong>.
                        </p>
                    @endif
                    <p>
                        Contact support and include the email address connected to this account:
                        <span class="email">{{ $user?->email }}</span>.
                    </p>
                </div>

                <div class="actions">
                    <a
                        href="mailto:contact@xeotype.com?subject=MobilityCloud%20suspended%20account"
                        class="primary"
                    >
                        Contact support
                    </a>

                    <a
                        href="{{ route('account.suspended.logout') }}"
                        class="secondary"
                    >
                        Sign out
                    </a>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
