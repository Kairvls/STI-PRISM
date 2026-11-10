@extends('layouts.app')

@section('main-pad', 'p-0')
@section('main-bg', 'bg-slate-200')

@php
    $sharedPrintType = strtolower((string) ($type ?? ($card['type'] ?? 'doc')));
    if ($sharedPrintType === 'lr') {
        $sharedPrintType = 'liq';
    }
    $sharedFallbackPage = match ($sharedPrintType) {
        'ris', 'rfc', 'liq' => 'A4 landscape',
        default => 'A4 portrait',
    };
@endphp
@push('head')
<style>
    /*
     * Fallback only (File → Print). Print / Ctrl+P use the dedicated form sheet
     * so output matches Purchaser / Accounting browser print.
     */
    @media print {
        @page { size: {{ $sharedFallbackPage }}; margin: 8mm; }
        html, body {
            background: #fff !important;
            overflow: visible !important;
            height: auto !important;
        }
        #sidebar,
        #sidebarOverlay,
        .shared-doc-toolbar,
        .print-hidden {
            display: none !important;
        }
        .shared-doc-screen,
        .shared-doc-preview,
        main {
            background: #fff !important;
            padding: 0 !important;
            margin: 0 !important;
            border: 0 !important;
            box-shadow: none !important;
            border-radius: 0 !important;
            max-width: none !important;
            overflow: visible !important;
        }
        .ris-document,
        .atp-print-sheet,
        .rfc-print-sheet,
        .rr-print-sheet,
        .liq-print-sheet,
        .dv-print-sheet {
            width: 100% !important;
            max-width: none !important;
            margin: 0 !important;
            box-shadow: none !important;
        }
    }
</style>
@endpush

@section('content')
@php
    $typeKey = strtolower((string) ($type ?? ($card['type'] ?? 'doc')));
    $typeLabel = strtoupper($typeKey === 'liq' ? 'LR' : $typeKey);
    $label = (string) ($card['label'] ?? ($typeLabel.' document'));
    $status = (string) ($card['status'] ?? '');
    $paper = $paper ?? null;
    $printUrl = $printUrl ?? route('messages.documents.print', ['type' => $typeKey, 'id' => (int) ($card['id'] ?? 0)]);
@endphp

<div class="shared-doc-screen min-h-screen bg-slate-200/80 px-3 py-5 sm:px-6 sm:py-8">
    <div class="shared-doc-toolbar mx-auto mb-4 flex max-w-[1100px] flex-wrap items-center justify-between gap-3 print-hidden">
        <div class="min-w-0">
            <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Shared {{ $typeLabel }}</p>
            <h1 class="truncate text-lg font-semibold text-slate-900">{{ $label }}</h1>
            @if($status !== '')
                <p class="mt-1 text-sm text-slate-600">{{ $status }}</p>
            @endif
        </div>
        <div class="flex items-center gap-2">
            <button
                type="button"
                id="sharedDocPrintBtn"
                data-print-url="{{ $printUrl }}"
                class="inline-flex h-9 items-center rounded-lg border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
            >
                Print
            </button>
            <button
                type="button"
                onclick="window.close()"
                class="inline-flex h-9 items-center rounded-lg border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
            >
                Close tab
            </button>
        </div>
    </div>

    <div class="shared-doc-preview mx-auto max-w-[1100px] overflow-x-auto rounded-xl border border-slate-300 bg-slate-100 p-3 shadow-sm sm:p-5">
        @if($paper === 'ris' && !empty($ris))
            @include('partials.ris-document-paper-styles', [
                'ris' => $ris,
                'risItems' => collect($risItems ?? [])->values(),
                'presidentName' => $presidentName ?? null,
                'isScreenPreview' => true,
            ])
        @elseif($paper === 'atp' && !empty($atp))
            @include('partials.authority-to-purchase-paper', [
                'editable' => false,
                'atp' => $atp,
                'items' => collect($items ?? [])->values(),
                'printId' => 'shared-atp-'.$atp->authority_purchase_id,
            ])
        @elseif($paper === 'rfc' && !empty($rfc))
            @include('partials.request-check-paper', [
                'editable' => false,
                'rfc' => $rfc,
                'printId' => 'shared-rfc-'.$rfc->request_check_id,
            ])
        @elseif($paper === 'rr' && !empty($rr))
            @include('partials.receiving-report-paper', [
                'editable' => false,
                'rr' => $rr,
                'rows' => collect($rows ?? [])->values(),
                'showOrCard' => false,
                'showItemPhotos' => false,
                'printId' => 'shared-rr-'.$rr->receiving_report_id,
            ])
        @elseif($paper === 'liq' && !empty($liq))
            @include('partials.liquidation-report-paper', [
                'editable' => false,
                'liq' => $liq,
                'rows' => collect($rows ?? [])->values(),
                'printId' => 'shared-liq-'.$liq->liquidation_report_id,
            ])
        @elseif($paper === 'dv' && !empty($dv))
            @include('partials.disbursement-voucher-paper', [
                'editable' => false,
                'dv' => $dv,
                'printId' => 'shared-dv-'.$dv->dv_id,
            ])
        @else
            <div class="rounded-xl border border-dashed border-slate-300 bg-white px-4 py-10 text-center text-sm text-slate-500">
                This shared document could not be rendered as a form.
            </div>
        @endif
    </div>
</div>

<script>
(function () {
    const btn = document.getElementById('sharedDocPrintBtn');
    if (!btn) return;

    // Same pattern as Purchaser RIS: print the dedicated form sheet in a hidden iframe
    // (never window.print() on this screen tab — that keeps SHARED / Print / Close chrome).
    window.printSharedDocumentSheet = function () {
        const url = btn.getAttribute('data-print-url');
        if (!url) return;

        let iframe = document.getElementById('sharedDocPrintFrame');
        if (!iframe) {
            iframe = document.createElement('iframe');
            iframe.id = 'sharedDocPrintFrame';
            iframe.setAttribute('title', 'Print document');
            iframe.setAttribute('aria-hidden', 'true');
            // Off-screen but real page size so signature centering can measure (0×0 pins left).
            iframe.style.cssText = 'position:fixed;left:-100vw;top:0;width:210mm;height:297mm;border:0;opacity:0;pointer-events:none;';
            document.body.appendChild(iframe);
        }

        // auto=1 → print sheet calls window.print() itself (same as Purchaser form output).
        // Fallback: if the sheet does not auto-print, trigger from the parent after load.
        let printed = false;
        const tryPrint = function () {
            if (printed || !iframe.contentWindow) return;
            printed = true;
            try {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
            } catch (e) {}
        };

        iframe.onload = function () {
            // Give the print sheet time to auto-print; only fall back if it did not.
            setTimeout(function () {
                try {
                    if (iframe.contentWindow && !iframe.contentWindow.__sharedPrintStarted) {
                        tryPrint();
                    }
                } catch (e) {
                    tryPrint();
                }
            }, 900);
        };
        const join = url.indexOf('?') >= 0 ? '&' : '?';
        iframe.src = url + join + 'auto=1&ts=' + Date.now();
    };

    btn.addEventListener('click', function (e) {
        e.preventDefault();
        window.printSharedDocumentSheet();
    });

    // Ctrl/Cmd+P → dedicated sheet (not this screen page).
    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && String(e.key || '').toLowerCase() === 'p') {
            e.preventDefault();
            e.stopPropagation();
            window.printSharedDocumentSheet();
        }
    }, true);
})();
</script>
@endsection
