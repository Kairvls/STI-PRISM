@extends('layouts.accounting-layout')

@section('title', 'View RIS')

@push('styles')
@include('partials.ris-signature-overlay-styles')
<style>
    .ris-document {
        width: 11in;
        min-height: 8.5in;
        padding: 0.35in;
        background: white;
        position: relative;
    }
    .ris-document .header { position: relative; margin-top: 24px; margin-bottom: 10px; text-align: center; }
    .ris-document .school { font-size: 20px; font-weight: 700; letter-spacing: 0.5px; }
    .ris-document .title { margin-top: 8px; font-family: Georgia, 'Times New Roman', serif; font-size: 22px; font-weight: 800; letter-spacing: 1px; }
    .ris-document .number { position: absolute; right: 0; bottom: -4px; font-size: 14px; }
    .ris-document .line { display: inline-block; min-width: 130px; border-bottom: 1px solid #111827; text-align: center; }
    .ris-document table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .ris-document .ris-table th,
    .ris-document .ris-table td { border: 2px solid #374151; height: 28px; padding: 3px 6px; font-size: 13px; vertical-align: top; }
    .ris-document .ris-table th { text-align: center; font-weight: 700; }
    .ris-document .item-col { width: 20%; }
    .ris-document .brand-col { width: 10%; }
    .ris-document .unit-col { width: 7%; }
    .ris-document .qty-col { width: 9%; }
    .ris-document .cost-col { width: 12%; }
    .ris-document .amount-col { width: 14%; }
    .ris-document .purpose { margin-top: 8px; font-size: 15px; font-weight: 700; }
    .ris-document .purpose-row-1 { display: flex; align-items: flex-end; gap: 12px; }
    .ris-document .purpose-label { flex-shrink: 0; line-height: 28px; }
    .ris-document .purpose-line {
        flex: 1;
        min-height: 28px;
        border-bottom: 1px solid #6b7280;
        font-weight: 400;
        line-height: 28px;
        white-space: nowrap;
        overflow: hidden;
    }
    .ris-document .purpose-line-2 { display: block; width: 100%; margin-top: 8px; flex: none; }
    .ris-document .signatures { margin-top: 28px; display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; font-size: 14px; }
    .ris-document .signature-box { position: relative; }
    .ris-document .signature-box p { margin: 0 0 6px; }
    .ris-document .signature-line { position: relative; border-bottom: 1px solid #111827; min-height: 20px; text-align: center; font-size: 12px; }
    .ris-document .signature-name-wrapper { position: relative; display: inline-block; width: 100%; text-align: center; }
    .ris-document .signature-name { font-size: 11px; text-transform: none; letter-spacing: 0; }
    .ris-document .signature-position { font-size: 10px; color: #4b5563; margin-top: 1px; }
    .ris-document .signature-name-wrapper .signature-image,
    .ris-document .signature-line .signature-image {
        max-height: 36px;
        width: auto;
        position: absolute;
        left: 50%;
        transform: translateX(-50%);
        bottom: 100%;
        margin-bottom: -8px;
        z-index: 10;
        pointer-events: none;
    }
    .ris-document .signature-line .signature-image { max-height: 32px; max-width: 90%; bottom: 12px; }
    .ris-document .signature-line .signature-name { display: block; line-height: 20px; }
    .ris-document .date-row { margin-top: 12px; display: grid; grid-template-columns: 40px 1fr; gap: 6px; align-items: end; }
</style>
@endpush

@section('content')
@include('accounting.partials.flash')

<div class="acc-page acc-page--review fade-in">
    <div class="acc-review-head">
        <div>
            <a href="{{ $backUrl }}" class="acc-back" data-tip="Back" aria-label="Back">
                <i data-lucide="arrow-left" class="h-4 w-4"></i>
            </a>
            <div class="mt-1 flex flex-wrap items-center gap-2">
                <h1 class="acc-page-title">{{ $ris->ris_form_number ?: ('RIS #'.$ris->ris_id) }}</h1>
                @include('accounting.partials.status-badge', ['status' => \App\Support\RisWorkflow::statusLabel($ris)])
            </div>
            <p class="acc-page-subtitle">Approved RIS and supporting documents. Read-only for Accounting review.</p>
        </div>
        <div class="acc-actions">
            <button type="button" class="icon-btn" onclick="window.accountingPrintForm({ page: 'landscape', sheetId: 'acc-print-sheet' })" data-tip="Print RIS" aria-label="Print RIS">
                <i data-lucide="printer" class="h-4 w-4"></i>
            </button>
        </div>
    </div>

    <div class="acc-review-body">
        <div class="acc-review-grid">
            <div>
                <div class="acc-viewer">
                    <div class="acc-viewer-stage">
                        <div class="acc-viewer-fit">
                            @include('partials.ris-document-paper', [
                                'ris' => $ris,
                                'risItems' => $risItems,
                                'presidentName' => $presidentName ?? 'President',
                                'isScreenPreview' => true,
                                'printId' => 'acc-print-sheet',
                            ])
                        </div>
                    </div>
                </div>
                @if ($attachments->isNotEmpty())
                    <div class="acc-attachments">
                        <h3>Supporting documents</h3>
                        <ul>
                            @foreach ($attachments as $file)
                                <li>
                                    <a href="{{ route('accounting.ris.attachment', [$ris->ris_id, $file->ris_attachment_id]) }}">
                                        {{ $file->ris_attachment_original_name }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    <div class="acc-attachments">
                        <h3>Supporting documents</h3>
                        <p class="text-sm text-slate-500">No supporting documents attached to this RIS.</p>
                    </div>
                @endif
            </div>
            <div class="acc-side-stack">
                @include('accounting.partials.related-docs', ['chain' => $chain, 'current' => 'ris'])
                @include('accounting.partials.history', ['history' => $history])
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.lucide) lucide.createIcons();
    });
</script>
@endsection
