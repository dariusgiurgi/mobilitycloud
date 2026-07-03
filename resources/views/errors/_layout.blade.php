@php
    $user = auth()->user();
    $status = $status ?? 500;
    $eyebrow = $eyebrow ?? 'Something needs attention';
    $title = $title ?? 'We could not complete this request.';
    $message = $message ?? 'The platform could not load this page. Please try again or contact support if the problem persists.';
    $accent = $accent ?? '#6366f1';
    $supportEmail = config('mobilitycloud.emails.support', 'support@mobilitycloud.eu');
    $loginUrl = \Illuminate\Support\Facades\Route::has('filament.admin.auth.login')
        ? route('filament.admin.auth.login')
        : url('/app/login');
    $homeUrl = $loginUrl;

    if ($user?->isPlatformAdmin() && \Illuminate\Support\Facades\Route::has('filament.platform.pages.dashboard')) {
        $homeUrl = route('filament.platform.pages.dashboard');
    } elseif ($user?->currentWorkspace) {
        $homeUrl = \App\Filament\Pages\Dashboard::getUrl(panel: 'admin', tenant: $user->currentWorkspace);
    }
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $status }} · MobilityCloud</title>
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
                radial-gradient(circle at top left, color-mix(in srgb, {{ $accent }} 22%, transparent), transparent 34rem),
                radial-gradient(circle at bottom right, rgba(99, 102, 241, 0.18), transparent 32rem),
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
            max-width: 44rem;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.10);
            border-radius: 1.5rem;
            background: rgba(255, 255, 255, 0.052);
            box-shadow: 0 24px 80px rgba(0, 0, 0, 0.35);
            backdrop-filter: blur(18px);
        }

        .header {
            display: flex;
            gap: 1.1rem;
            align-items: flex-start;
            border-bottom: 1px solid rgba(255, 255, 255, 0.10);
            background: rgba(255, 255, 255, 0.035);
            padding: 2rem;
        }

        .status {
            display: grid;
            flex: 0 0 auto;
            width: 3.25rem;
            height: 3.25rem;
            place-items: center;
            border: 1px solid color-mix(in srgb, {{ $accent }} 50%, transparent);
            border-radius: 1.05rem;
            background: color-mix(in srgb, {{ $accent }} 18%, transparent);
            color: #ffffff;
            font-weight: 800;
        }

        .eyebrow {
            margin: 0;
            color: color-mix(in srgb, {{ $accent }} 72%, #ffffff);
            font-size: 0.78rem;
            font-weight: 800;
            letter-spacing: 0.22em;
            text-transform: uppercase;
        }

        h1 {
            margin: 0.75rem 0 0;
            font-size: clamp(1.8rem, 5vw, 2.45rem);
            line-height: 1.08;
            letter-spacing: -0.04em;
        }

        .body {
            display: grid;
            gap: 1.35rem;
            padding: 2rem;
        }

        p {
            margin: 0;
            color: #cbd5e1;
            line-height: 1.7;
        }

        .notice {
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 1rem;
            background: rgba(15, 23, 42, 0.72);
            padding: 1.1rem;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 2.85rem;
            border-radius: 0.85rem;
            padding: 0.75rem 1.15rem;
            font-size: 0.92rem;
            font-weight: 750;
            text-decoration: none;
            transition: transform 150ms ease, background-color 150ms ease, border-color 150ms ease;
        }

        a:hover {
            transform: translateY(-1px);
        }

        .primary {
            border: 0;
            background: {{ $accent }};
            color: #ffffff;
        }

        .secondary {
            border: 1px solid rgba(255, 255, 255, 0.16);
            background: transparent;
            color: #ffffff;
        }

        @media (max-width: 640px) {
            .header {
                display: grid;
            }
        }
    </style>
</head>
<body>
    <main>
        <section class="card" aria-labelledby="error-title">
            <div class="header">
                <div class="status">{{ $status }}</div>
                <div>
                    <p class="eyebrow">{{ $eyebrow }}</p>
                    <h1 id="error-title">{{ $title }}</h1>
                </div>
            </div>

            <div class="body">
                <p>{{ $message }}</p>

                @isset($details)
                    <div class="notice">
                        <p>{{ $details }}</p>
                    </div>
                @endisset

                <div class="actions">
                    <a href="{{ $homeUrl }}" class="primary">{{ $user ? 'Back to dashboard' : 'Sign in' }}</a>
                    <a href="mailto:{{ $supportEmail }}?subject=MobilityCloud%20support%20request%20{{ $status }}" class="secondary">Contact support</a>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
