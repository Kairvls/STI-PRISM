@extends('layouts.accounting-layout')

@section('title', $title ?? 'Package reference')

@push('styles')
@include('partials.ris-signature-overlay-styles')
<style>
    .ris-document {
        width: 100%;
        max-width: 960px;
        min-height: 0;
        padding: 0.28in 0.24in;
        margin: 0 auto;
        background: white;
        position: relative;
        box-sizing: border-box;
        font-family: Arial, Helvetica, sans-serif;
    }
    .ris-document .header { position: relative; margin-top: 24px; margin-bottom: 10px; text-align: center; }
    .ris-document .school { font-size: 17px; font-weight: 700; letter-spacing: 0.5px; }
    .ris-document .title { margin-top: 8px; font-family: Georgia, 'Times New Roman', serif; font-size: 18px; font-weight: 800; letter-spacing: 1px; }
    .ris-document .number { position: absolute; right: 0; bottom: -4px; font-size: 12px; }
    .ris-document .line { display: inline-block; min-width: 150px; border-bottom: 1px solid #111827; text-align: center; }
    .ris-document table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .ris-document .ris-table th,
    .ris-document .ris-table td { border: 2px solid #374151; height: 36px; min-height: 36px; padding: 8px 6px; font-size: 11px; vertical-align: middle; word-break: break-word; overflow-wrap: anywhere; }
    .ris-document .ris-table th { text-align: center; font-weight: 700; }
    .ris-document .purpose { margin-top: 8px; font-size: 15px; font-weight: 700; }
    .ris-document .signatures { margin-top: 28px; display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; font-size: 14px; }
    .ris-document .signature-box { position: relative; }
    .ris-document .signature-line { position: relative; border-bottom: 1px solid #111827; min-height: 20px; text-align: center; font-size: 12px; }
</style>
@endpush

@section('content')
@php
    $rfcId = (int) ($rfcId ?? optional($rfc)->request_check_id ?? 0);
    $package = $package ?? ['atps' => collect(), 'risList' => collect(), 'rfc' => $rfc ?? null];
    $atps = collect($package['atps'] ?? []);
    $risList = collect($package['risList'] ?? []);
    $backDv = request()->query('edit_dv')
        ? route('accounting.dv.index', ['edit_dv' => (int) request()->query('edit_dv'), 'status' => 'incoming'])
        : route('accounting.dv.index', ['rfc' => $rfcId, 'status' => 'incoming']);
@endphp

@include('accounting.partials.flash')

<div class="acx mx-auto max-w-5xl space-y-4">
    <div class="print-hidden flex flex-wrap items-start justify-between gap-3">
        <div>
            <a href="{{ $backDv }}" class="text-sm text-slate-500 hover:text-[#0025cc]">← Back to Disbursement requests</a>
            <h1 class="acx-h mt-2 text-2xl font-semibold">{{ $title }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $subtitle ?? 'Approved request document for Disbursement Voucher reference' }}</p>
        </div>
        <button
            type="button"
            id="pkg-print-btn"
            class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
            <i data-lucide="printer" class="h-4 w-4"></i>
            Print
        </button>
    </div>

    <div class="print-hidden flex flex-wrap gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm">
        <span class="self-center text-xs font-medium uppercase tracking-wide text-slate-400">Request</span>
        <a
            href="{{ route('accounting.dv.package.rfc', $rfcId) }}{{ request()->query('edit_dv') ? '?edit_dv='.(int) request()->query('edit_dv') : '' }}"
            class="rounded-lg px-2.5 py-1 font-medium {{ ($docType ?? '') === 'rfc' ? 'bg-white text-[#0025cc] shadow-sm ring-1 ring-slate-200' : 'text-slate-600 hover:bg-white hover:text-slate-900' }}"
        >RFC</a>
        @foreach($atps as $atpRow)
            <a
                href="{{ route('accounting.dv.package.atp', [$rfcId, $atpRow->authority_purchase_id]) }}{{ request()->query('edit_dv') ? '?edit_dv='.(int) request()->query('edit_dv') : '' }}"
                class="rounded-lg px-2.5 py-1 font-medium {{ ($docType ?? '') === 'atp' && (int) ($atp->authority_purchase_id ?? 0) === (int) $atpRow->authority_purchase_id ? 'bg-white text-[#0025cc] shadow-sm ring-1 ring-slate-200' : 'text-slate-600 hover:bg-white hover:text-slate-900' }}"
            >{{ $atpRow->authority_purchase_form_number ?: 'ATP' }}</a>
        @endforeach
        @foreach($risList as $risRow)
            <a
                href="{{ route('accounting.dv.package.ris', [$rfcId, $risRow->ris_id]) }}{{ request()->query('edit_dv') ? '?edit_dv='.(int) request()->query('edit_dv') : '' }}"
                class="rounded-lg px-2.5 py-1 font-medium {{ ($docType ?? '') === 'ris' && (int) ($ris->ris_id ?? 0) === (int) $risRow->ris_id ? 'bg-white text-[#0025cc] shadow-sm ring-1 ring-slate-200' : 'text-slate-600 hover:bg-white hover:text-slate-900' }}"
            >Canvas + RIS {{ \App\Support\RisWorkflow::formNumber($risRow) }}</a>
        @endforeach
    </div>

    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white p-3 sm:p-5">
        @if(($docType ?? '') === 'rfc')
            @include('partials.request-check-paper', [
                'editable' => false,
                'rfc' => $rfc,
                'printId' => 'pkg-print-sheet',
            ])
        @elseif(($docType ?? '') === 'atp')
            @include('partials.authority-to-purchase-paper', [
                'editable' => false,
                'atp' => $atp,
                'items' => $items ?? collect(),
                'printId' => 'pkg-print-sheet',
            ])
        @elseif(($docType ?? '') === 'ris')
            @include('partials.ris-document-paper', [
                'ris' => $ris,
                'risItems' => $risItems ?? collect(),
                'presidentName' => 'President',
                'isScreenPreview' => true,
                'printId' => 'pkg-print-sheet',
            ])
        @endif
    </div>

    @if(($docType ?? '') === 'ris' && isset($attachments) && $attachments->isNotEmpty())
        <div class="print-hidden rounded-xl border border-slate-200 bg-white px-4 py-3">
            <h2 class="text-sm font-semibold text-slate-900">Canvas</h2>
            <ul class="mt-2 space-y-1.5">
                @foreach($attachments as $file)
                    <li>
                        <a
                            href="{{ route('accounting.dv.package.ris.attachment', [$rfcId, $ris->ris_id, $file->ris_attachment_id]) }}"
                            target="_blank"
                            class="text-[15px] font-semibold text-[#0025cc] hover:underline"
                        >{{ $file->ris_attachment_original_name }}</a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(($docType ?? '') === 'rfc' && isset($attachments) && $attachments->isNotEmpty())
        <div class="print-hidden rounded-xl border border-slate-200 bg-white px-4 py-3">
            <h2 class="text-sm font-semibold text-slate-900">RFC attachments</h2>
            <ul class="mt-2 space-y-1.5 text-sm text-slate-600">
                @foreach($attachments as $file)
                    <li>{{ $file->request_check_attachment_original_name }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</div>

@include('accounting.partials.print-form')
<script>
(function () {
    var PAGE = @json(($docType ?? '') === 'ris' || ($docType ?? '') === 'rfc' ? 'landscape' : 'portrait');

    function findSheet() {
        return document.getElementById('pkg-print-sheet')
            || document.querySelector('.rfc-print-sheet, .atp-print-sheet, .ris-document, .dv-print-sheet');
    }

    function printPackageDoc(e) {
        if (e) e.preventDefault();
        var sheet = findSheet();
        if (typeof window.accountingPrintForm === 'function' && sheet) {
            if (window.accountingPrintForm({ sheet: sheet, page: PAGE })) {
                return;
            }
        }
        if (!sheet) {
            window.print();
            return;
        }
        // Fallback mount if the shared helper is unavailable.
        var existing = document.getElementById('accounting-print-mount');
        if (existing) existing.remove();
        var mount = document.createElement('div');
        mount.id = 'accounting-print-mount';
        mount.className = 'accounting-print-mount';
        mount.setAttribute('data-page', PAGE);
        var clone = sheet.cloneNode(true);
        clone.removeAttribute('id');
        clone.classList.add('accounting-print-sheet');
        mount.appendChild(clone);
        document.body.appendChild(mount);
        window.print();
        window.setTimeout(function () {
            if (mount.parentNode) mount.parentNode.removeChild(mount);
        }, 1500);
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (window.lucide) lucide.createIcons();
        var btn = document.getElementById('pkg-print-btn');
        if (btn) btn.addEventListener('click', printPackageDoc);
    });
})();
</script>
@endsection
