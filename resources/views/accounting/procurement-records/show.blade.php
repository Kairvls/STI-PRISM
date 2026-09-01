@extends('layouts.accounting-layout')

@section('title', 'Compiled Record')

@section('content')
@php
    use App\Support\ProcurementPaymentPath;
    $status = str_replace('_', ' ', ucfirst($package->package_status));
    $statusClass = match ($package->package_status) {
        'submitted_to_accounting' => 'pr-badge--amber',
        'forwarded_to_president' => 'pr-badge--green',
        default => '',
    };
@endphp

@include('partials.procurement-records-ui')

<div class="pr-module acc-page fade-in">
    @if(session('success'))
        <div class="acc-note acc-note-success mb-4">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="acc-note acc-note-error mb-4">{{ session('error') }}</div>
    @endif

    <div class="pr-detail-head">
        <div class="flex items-start gap-3">
            <a href="{{ route('accounting.procurement-records.index') }}" class="pr-back" aria-label="Back to compiled records">
                <i data-lucide="arrow-left"></i>
            </a>
            <div>
                <p class="pr-hero-kicker">Compiled Record</p>
                <h1 class="pr-hero-title">{{ $atp->authority_purchase_form_number ?? 'Compiled Record' }}</h1>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <span class="pr-badge pr-badge--blue">{{ ProcurementPaymentPath::label($package->package_payment_path) }}</span>
                    <span class="pr-badge {{ $statusClass }}">{{ $status }}</span>
                </div>
            </div>
        </div>

        @if($package->package_status === 'submitted_to_accounting')
            <form method="POST" action="{{ route('accounting.procurement-records.forward', $package->package_id) }}" onsubmit="return confirm('Forward this record to the President?')">
                @csrf
                <button type="submit" class="pr-forward-btn">
                    <i data-lucide="send"></i>
                    Forward to President
                </button>
            </form>
        @endif
    </div>

    @include('partials.procurement-record-documents', ['documentRows' => $documentRows ?? []])
</div>
@endsection
