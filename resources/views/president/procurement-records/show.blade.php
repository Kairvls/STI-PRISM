@extends('layouts.president-layout')

@section('title', 'Procurement Record')

@section('content')
@php
    use App\Support\ProcurementPaymentPath;
    use App\Support\ProcurementRecordCompiler;
    $prHeading = ProcurementRecordCompiler::prDisplayNumber($atp)
        ?: (($atp->authority_purchase_request_no ?? null)
            ?: \App\Support\ProcurementRequestNumber::resolveFromAtp((int) ($atp->authority_purchase_id ?? 0))
            ?: 'Procurement Record');
    $atpForm = trim((string) ($atp->authority_purchase_form_number ?? ''));
@endphp

@include('partials.procurement-records-ui')

<div class="pr-module fade-in">
    <div class="pr-detail-head">
        <div class="flex items-start gap-3">
            <a href="{{ route('president.procurement-records.index') }}" class="pr-back" aria-label="Back to compiled records">
                <i data-lucide="arrow-left"></i>
            </a>
            <div>
                <p class="pr-hero-kicker">Compiled Record</p>
                <h1 class="pr-hero-title" style="color:#1d4ed8;">{{ $prHeading }}</h1>
                @if($atpForm !== '')
                    <p class="mt-1 text-sm font-medium text-slate-700">{{ $atpForm }}</p>
                @endif
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <span class="pr-badge pr-badge--blue">{{ ProcurementPaymentPath::label($package->package_payment_path) }}</span>
                    <span class="pr-badge pr-badge--green">Archived</span>
                </div>
            </div>
        </div>
    </div>

    @include('partials.procurement-record-documents', ['documentRows' => $documentRows ?? []])
</div>
@endsection
