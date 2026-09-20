<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receiving Report {{ $rr->receiving_report_form_number ?: ('RR-'.$rr->receiving_report_id) }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        html, body {
            margin: 0;
            padding: 0;
            background: #f3f4f6;
            color: #111;
        }
        .actions {
            position: sticky;
            top: 0;
            z-index: 20;
            display: flex;
            gap: 8px;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
        }
        .actions-left,
        .actions-right {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 8px 14px;
            background: #0025cc;
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            font-size: 13px;
            border: 0;
            cursor: pointer;
            font-family: inherit;
        }
        .btn-icon { width: 36px; height: 36px; padding: 0; gap: 0; }
        .btn svg { width: 18px; height: 18px; flex-shrink: 0; }
        .btn-ghost {
            background: #fff;
            color: #111;
            border: 1px solid #d1d5db;
        }
        .print-stage {
            padding: 24px 16px 40px;
        }
        /* Preview modal / hidden iframe — hide chrome */
        html.embedded-preview .actions { display: none !important; }
        html.embedded-preview, html.embedded-preview body { background: #fff; }
        html.embedded-preview .print-stage { padding: 0; }

        @media print {
            html, body {
                background: #fff !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .actions { display: none !important; }
            .print-stage { padding: 0 !important; }
            .rr-print-sheet {
                box-shadow: none !important;
                max-width: none !important;
                width: 100% !important;
                margin: 0 !important;
            }
            @page { size: A4 portrait; margin: 8mm; }
        }
    </style>
    <script>
        if (window.self !== window.top) {
            document.documentElement.classList.add('embedded-preview');
        }

        function receivingPrintGoBack() {
            if (window.history.length > 1) {
                window.history.back();
                return;
            }

            var ref = document.referrer || '';
            if (ref.indexOf(window.location.origin) === 0) {
                window.location.href = ref;
                return;
            }

            if (window.opener && !window.opener.closed) {
                window.close();
                return;
            }

            window.location.href = '/receiving/delivered-items';
        }
    </script>
</head>
<body>
    <div class="actions print-hidden">
        <div class="actions-left">
            <button type="button" class="btn btn-ghost" onclick="receivingPrintGoBack()" title="Back" aria-label="Back">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Back
            </button>
        </div>
        <div class="actions-right">
            <button type="button" class="btn btn-icon" onclick="window.print()" title="Print" aria-label="Print">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            </button>
        </div>
    </div>

    <div class="print-stage">
        @include('partials.receiving-report-paper', [
            'editable' => false,
            'rr' => $rr,
            'rows' => $rows,
            'officerName' => $officerName,
            'printId' => 'rr-print-'.($rr->receiving_report_id ?? 'sheet'),
            'printClass' => 'shadow-none',
        ])
    </div>
</body>
</html>
