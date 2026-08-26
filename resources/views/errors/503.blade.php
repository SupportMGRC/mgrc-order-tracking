@php
    $errorCode  = $errorCode  ?? null;
    $errorLabel = $errorLabel ?? 'Service temporarily unavailable';
    $reference  = $reference  ?? null;
    $endpoint   = $endpoint   ?? '/'.ltrim(request()->path(), '/');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>503 Service Unavailable &middot; TRACOM</title>
    <style>
        :root {
            --ink:     #10151f;
            --body:    #4a5265;
            --faint:   #8b93a5;
            --rule:    #e1e5ec;
            --hair:    #eef1f5;
            --amber:   #f7b84b;
            --surface: #ffffff;
            --canvas:  #f4f6f8;
            --mono: ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas,
                    "Liberation Mono", monospace;
            --sans: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto,
                    Helvetica, Arial, sans-serif;
        }

        *, *::before, *::after { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 20px;
            background: var(--canvas);
            color: var(--ink);
            font-family: var(--sans);
            font-size: 15px;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        .panel {
            width: 100%;
            max-width: 620px;
            background: var(--surface);
            border: 1px solid var(--rule);
            border-radius: 4px;
            overflow: hidden;
        }

        /* Status bar --------------------------------------------------- */

        .status {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 13px 28px;
            border-bottom: 1px solid var(--rule);
            background: #fbfcfd;
            font-family: var(--mono);
            font-size: 11.5px;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: var(--faint);
        }

        .status .dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--amber);
            flex: none;
        }

        .status .code { color: var(--ink); font-weight: 600; }
        .status .spacer { margin-left: auto; }

        /* Body --------------------------------------------------------- */

        .content { padding: 30px 28px 26px; }

        .content h1 {
            margin: 0 0 16px;
            font-size: 20px;
            font-weight: 600;
            letter-spacing: -.01em;
            line-height: 1.35;
        }

        .content p {
            margin: 0 0 13px;
            color: var(--body);
            font-size: 14.5px;
        }

        .content p:last-of-type { margin-bottom: 0; }

        /* Diagnostic manifest ------------------------------------------ */

        .manifest {
            margin: 24px 0 0;
            border-top: 1px solid var(--rule);
            font-family: var(--mono);
            font-size: 12.5px;
        }

        .manifest .row {
            display: flex;
            gap: 18px;
            padding: 9px 0;
            border-bottom: 1px solid var(--hair);
        }

        .manifest .row:last-child { border-bottom: 0; }

        .manifest dt {
            flex: none;
            width: 96px;
            color: var(--faint);
            letter-spacing: .04em;
            text-transform: uppercase;
            font-size: 11px;
            padding-top: 1px;
        }

        .manifest dd {
            margin: 0;
            color: var(--ink);
            word-break: break-all;
        }

        .manifest dd .ref {
            font-weight: 600;
            border-bottom: 2px solid var(--amber);
            padding-bottom: 1px;
        }

        /* Actions ------------------------------------------------------ */

        .actions {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 18px 28px;
            border-top: 1px solid var(--rule);
            background: #fbfcfd;
        }

        .actions .btn {
            display: inline-block;
            padding: 8px 17px;
            border: 1px solid var(--ink);
            border-radius: 3px;
            background: var(--ink);
            color: #fff;
            font-family: var(--sans);
            font-size: 13.5px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
        }

        .actions .btn:hover { background: #232c3d; border-color: #232c3d; }

        .actions .btn:focus-visible {
            outline: 2px solid var(--amber);
            outline-offset: 2px;
        }

        @media (max-width: 520px) {
            .content, .status, .actions { padding-left: 20px; padding-right: 20px; }
            .manifest .row { flex-direction: column; gap: 3px; }
            .manifest dt { width: auto; }
            .actions { flex-direction: column; align-items: flex-start; gap: 11px; }
        }
    </style>
</head>
<body>
    <main class="panel">

        <div class="status">
            <span class="dot" aria-hidden="true"></span>
            <span class="code">HTTP 503</span>
            <span>Service Unavailable</span>
            <span class="spacer">TRACOM</span>
        </div>

        <div class="content">
            <h1>
                @if ($errorCode)
                    Database connection limit reached
                @else
                    Scheduled maintenance in progress
                @endif
            </h1>

            @if ($errorCode)
                <p>
                    The database rejected this request. No records were created,
                    modified or deleted.
                </p>
                <p>
                    Wait 30 seconds, then retry. If the error continues, report the
                    reference below to IT.
                </p>
            @else
                <p>
                    TRACOM is offline for scheduled maintenance.
                </p>
                <p>
                    Retry after the maintenance window. Contact IT if access remains
                    unavailable.
                </p>
            @endif

            <dl class="manifest">
                @if ($errorCode)
                    <div class="row">
                        <dt>Fault</dt>
                        <dd>MySQL {{ $errorCode }} &mdash; {{ $errorLabel }}</dd>
                    </div>
                    <div class="row">
                        <dt>Layer</dt>
                        <dd>Database connection</dd>
                    </div>
                @endif
                <div class="row">
                    <dt>Endpoint</dt>
                    <dd>{{ $endpoint }}</dd>
                </div>
                <div class="row">
                    <dt>Time</dt>
                    <dd>{{ now()->format('d M Y H:i:s') }} ({{ now()->format('T P') }})</dd>
                </div>
                @if ($reference)
                    <div class="row">
                        <dt>Reference</dt>
                        <dd><span class="ref">{{ $reference }}</span></dd>
                    </div>
                @endif
            </dl>
        </div>

        <div class="actions">
            <a class="btn" href="{{ url()->current() }}">Retry request</a>
        </div>

    </main>
</body>
</html>