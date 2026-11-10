<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print {{ strtoupper(($type ?? 'doc') === 'liq' ? 'LR' : ($type ?? 'DOC')) }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @php
        $paper = $paper ?? null;
        // Match Purchaser / Accounting browser print orientation.
        $pageSize = match ($paper) {
            'rfc', 'liq' => 'A4 landscape',
            'atp', 'rr', 'dv' => 'A4 portrait',
            default => 'A4 portrait',
        };
        $sheetSelector = match ($paper) {
            'atp' => '.atp-print-sheet',
            'rfc' => '.rfc-print-sheet',
            'rr' => '.rr-print-sheet',
            'liq' => '.liq-print-sheet',
            'dv' => '.dv-print-sheet',
            default => null,
        };
    @endphp
    <style>
        html, body {
            margin: 0;
            padding: 0;
            background: #fff;
        }

        @page {
            size: {{ $pageSize }};
            margin: 8mm;
        }

        /* Same sheet sizing cues as purchaser-print-sheet-helper / accounting print. */
        .atp-print-sheet,
        .rr-print-sheet,
        .dv-print-sheet {
            width: 100%;
            max-width: 100%;
            box-shadow: none !important;
            background: #fff !important;
        }

        .rfc-print-sheet,
        .liq-print-sheet {
            width: 297mm;
            max-width: 100%;
            min-height: 0;
            height: auto;
            box-shadow: none !important;
            background: #fff !important;
        }

        @media print {
            html, body {
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
                height: auto !important;
                overflow: visible !important;
            }

            .print-hidden {
                display: none !important;
            }

            .atp-print-sheet,
            .rr-print-sheet {
                padding: 6mm 12mm 12mm !important;
                margin: 0 !important;
                min-height: 0 !important;
                height: auto !important;
            }

            .rfc-print-sheet {
                padding: 10mm 14mm !important;
                margin: 0 !important;
                min-height: 0 !important;
                height: auto !important;
            }

            .liq-print-sheet {
                padding: 8mm 10mm !important;
                margin: 0 !important;
            }

            .dv-print-sheet {
                padding: 6mm 10mm !important;
                margin: 0 !important;
                min-height: 0 !important;
                height: auto !important;
            }
        }
    </style>
    @include('partials.print-fit-page')
</head>
<body class="bg-white p-0">

@if($paper === 'atp' && !empty($atp))
    @include('partials.authority-to-purchase-paper', [
        'editable' => false,
        'atp' => $atp,
        'items' => collect($items ?? [])->values(),
        'printId' => 'shared-print-atp',
        'printClass' => 'shadow-none',
    ])
@elseif($paper === 'rfc' && !empty($rfc))
    @include('partials.request-check-paper', [
        'editable' => false,
        'rfc' => $rfc,
        'printId' => 'shared-print-rfc',
        'printClass' => 'shadow-none',
    ])
@elseif($paper === 'rr' && !empty($rr))
    @include('partials.receiving-report-paper', [
        'editable' => false,
        'rr' => $rr,
        'rows' => collect($rows ?? [])->values(),
        'showOrCard' => false,
        'showItemPhotos' => false,
        'printId' => 'shared-print-rr',
        'printClass' => 'shadow-none',
    ])
@elseif($paper === 'liq' && !empty($liq))
    @include('partials.liquidation-report-paper', [
        'editable' => false,
        'liq' => $liq,
        'rows' => collect($rows ?? [])->values(),
        'printId' => 'shared-print-liq',
        'printClass' => 'shadow-none',
    ])
@elseif($paper === 'dv' && !empty($dv))
    @include('partials.disbursement-voucher-paper', [
        'editable' => false,
        'dv' => $dv,
        'printId' => 'shared-print-dv',
        'printClass' => 'shadow-none',
    ])
@else
    <p style="padding:2rem;font-family:sans-serif;">Nothing to print.</p>
@endif

<script>
    window.__sharedPrintStarted = true;
    window.addEventListener('load', function () {
        var selector = @json($sheetSelector);
        var sheet = selector ? document.querySelector(selector) : null;
        try {
            if (sheet && window.fitPrintPage) {
                window.fitPrintPage(sheet);
            }
        } catch (e) {}

        // Prefer CSS name-stack centering for print (avoids left-pinned overlays).
        try {
            if (window.clearSignaturePixelPins) {
                window.clearSignaturePixelPins(document);
            }
            if (window.scanSignatureOverlayTrim) {
                window.scanSignatureOverlayTrim(document);
            }
        } catch (e) {}

        setTimeout(function () {
            try {
                if (window.clearSignaturePixelPins) {
                    window.clearSignaturePixelPins(document);
                }
            } catch (e) {}
            try { window.focus(); window.print(); } catch (e) {}
        }, 360);
    });
</script>
</body>
</html>
