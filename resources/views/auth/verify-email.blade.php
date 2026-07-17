@php
    $supportEmail = config('mobilitycloud.emails.support', 'contact@mobilitycloud.eu');
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify email · MobilityCloud</title>
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
                radial-gradient(circle at top left, rgba(99, 102, 241, 0.22), transparent 34rem),
                radial-gradient(circle at bottom right, rgba(14, 165, 233, 0.18), transparent 32rem),
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
            color: #93c5fd;
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
            border: 1px solid rgba(147, 197, 253, 0.28);
            border-radius: 1rem;
            background: rgba(147, 197, 253, 0.10);
            padding: 1.15rem;
        }

        .notice strong,
        .email {
            color: #ffffff;
        }

        .status {
            border: 1px solid rgba(74, 222, 128, 0.28);
            border-radius: 1rem;
            background: rgba(74, 222, 128, 0.10);
            color: #bbf7d0;
            padding: 1rem 1.15rem;
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
            background: #6366f1;
            color: #ffffff;
            cursor: pointer;
        }

        .secondary {
            border: 1px solid rgba(255, 255, 255, 0.16);
            background: transparent;
            color: #ffffff;
            cursor: pointer;
        }

        .muted {
            color: #94a3b8;
            font-size: 0.92rem;
        }
    </style>
</head>
<body>
    <main>
        <section class="card">
            <div class="card-header">
                <p class="eyebrow">Email verification required</p>
                <h1>Confirm your email before entering MobilityCloud.</h1>
            </div>

            <div class="card-body">
                @if (session('status') === 'verification-link-sent')
                    <p class="status">
                        A fresh verification link was sent. Please check your inbox and spam folder.
                    </p>
                @elseif (session('status'))
                    <p class="status">{{ session('status') }}</p>
                @endif

                <p>
                    We sent a confirmation link to
                    <span class="email">{{ $email }}</span>.
                    This protects project access, invitations and billing data from accounts created with the wrong address.
                </p>

                <div class="notice">
                    <p><strong>Next step</strong></p>
                    <p>
                        Open the verification email and click the confirmation link. After verification, you will be sent back to the page you were trying to open.
                    </p>
                </div>

                <div class="actions">
                    <form method="POST" action="{{ route('verification.send') }}">
                        @csrf
                        <button type="submit" class="primary">Resend verification email</button>
                    </form>

                    <form method="POST" action="{{ route('verification.logout') }}">
                        @csrf
                        <button type="submit" class="secondary">Sign out</button>
                    </form>

                    <a
                        href="mailto:{{ $supportEmail }}?subject=MobilityCloud%20email%20verification"
                        class="secondary"
                    >
                        Contact support
                    </a>
                </div>

                <p class="muted">
                    If the email address is wrong, sign out and create the account again with the correct address.
                </p>
            </div>
        </section>
    </main>
</body>
</html>
