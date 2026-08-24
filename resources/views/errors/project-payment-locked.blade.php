<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Exports locked - MobilityCloud</title>
    <style>
        :root {
            color-scheme: light dark;
            --bg: #f8fafc;
            --card: #ffffff;
            --text: #0f172a;
            --muted: #64748b;
            --border: rgba(15, 23, 42, .1);
            --primary: #4f46e5;
            --warning: #ea580c;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #020617;
                --card: #0f172a;
                --text: #f8fafc;
                --muted: #cbd5e1;
                --border: rgba(255, 255, 255, .12);
            }
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at 15% 10%, rgba(79, 70, 229, .18), transparent 28rem),
                radial-gradient(circle at 85% 80%, rgba(14, 165, 233, .18), transparent 26rem),
                var(--bg);
        }

        .card {
            width: min(680px, 100%);
            padding: 34px;
            border: 1px solid var(--border);
            border-radius: 28px;
            background: color-mix(in srgb, var(--card) 94%, transparent);
            box-shadow: 0 24px 60px rgba(15, 23, 42, .18);
        }

        .badge {
            width: 56px;
            height: 56px;
            display: grid;
            place-items: center;
            border-radius: 18px;
            color: var(--warning);
            background: rgba(251, 146, 60, .14);
            font-size: 26px;
            margin-bottom: 18px;
        }

        h1 {
            margin: 0;
            font-size: clamp(30px, 5vw, 48px);
            letter-spacing: -.04em;
            line-height: 1;
        }

        p {
            margin: 16px 0 0;
            color: var(--muted);
            font-size: 17px;
            line-height: 1.65;
        }

        .actions {
            margin-top: 26px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            padding: 10px 18px;
            border-radius: 999px;
            text-decoration: none;
            font-weight: 750;
        }

        .primary { color: white; background: var(--primary); }
        .secondary { color: var(--text); border: 1px solid var(--border); }
    </style>
</head>
<body>
    <main class="card">
        <div class="badge">🔒</div>
        <h1>Exports unlock after payment is confirmed.</h1>
        <p>
            You can continue preparing the budget and project documents. Participant management, mobility evidence,
            generated files, signed-copy downloads and final archives become available after support marks the fiscal invoice as paid.
        </p>
        @isset($project)
            <p>
                Project: <strong>{{ $project->name }}</strong>
                @if($project->invoice_due_at)
                    · Payment due by {{ $project->invoice_due_at->format('d M Y') }}
                @endif
            </p>
        @endisset
        <div class="actions">
            <a class="primary" href="{{ url('/app') }}">Back to platform</a>
            <a class="secondary" href="mailto:contact@mobilitycloud.eu">Contact support</a>
        </div>
    </main>
</body>
</html>
