@extends('layouts.supply-officer-layout')

@section('title', 'Canvas + RIS')

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
    $formNo = \App\Support\RisWorkflow::formNumber($ris);
    $requestNo = trim((string) ($ris->ris_request_no ?? ''));
@endphp

<div class="mx-auto max-w-5xl space-y-4">
    <div class="print-hidden flex flex-wrap items-start justify-between gap-3">
        <div>
            <a href="{{ route('supply-officer.incoming-ris') }}" class="text-sm text-gray-500 hover:text-[#0025cc]">← Back to Incoming RIS</a>
            <h1 class="mt-2 text-2xl font-semibold text-gray-950">{{ $formNo }}</h1>
            <p class="mt-1 text-sm text-gray-500">Approved Canvas + RIS — reference while creating Authority to Purchase</p>
            @if ($requestNo !== '')
                <div class="mt-2">
                    @include('partials.procurement-request-no', [
                        'requestNo' => $requestNo,
                        'inline' => true,
                        'showLabel' => true,
                    ])
                </div>
            @endif
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button
                type="button"
                onclick="window.print()"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
                <i data-lucide="printer" class="h-4 w-4"></i>
                Print
            </button>
            <a
                href="{{ route('supply-officer.atp.index', ['selected_ris' => $ris->ris_id]) }}"
                class="inline-flex items-center gap-2 rounded-lg bg-[#0025cc] px-3 py-2 text-sm font-semibold text-white hover:bg-[#001db3]"
            >
                <i data-lucide="file-plus-2" class="h-4 w-4"></i>
                Create ATP
            </a>
        </div>
    </div>

    <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white p-3 sm:p-5">
        @include('partials.ris-document-paper', [
            'ris' => $ris,
            'risItems' => $risItems ?? collect(),
            'presidentName' => 'President',
            'isScreenPreview' => true,
            'printId' => 'supply-ris-print-sheet',
        ])
    </div>

    @if (isset($attachments) && $attachments->isNotEmpty())
        <div class="print-hidden rounded-xl border border-gray-200 bg-white px-4 py-3">
            <h2 class="text-sm font-semibold text-gray-900">Canvas</h2>
            <ul class="mt-2 space-y-1.5">
                @foreach ($attachments as $file)
                    <li>
                        <a
                            href="{{ route('supply-officer.incoming-ris.attachment', [$ris->ris_id, $file->ris_attachment_id]) }}"
                            target="_blank"
                            class="text-[15px] font-semibold text-[#0025cc] hover:underline"
                        >{{ $file->ris_attachment_original_name }}</a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
@endsection
