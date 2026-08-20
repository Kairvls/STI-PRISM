@extends('layouts.accounting-layout')

@section('title', 'Accounting Reports')

@section('content')
<div class="acc-page fade-in">
    <div>
        <p class="text-sm leading-6 text-gray-500">Live counts from Accounting workflow tables.</p>
    </div>

    <div class="mt-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="pm-kpi-card slide-up" style="animation-delay:.04s">
            <p class="text-xs font-semibold text-gray-500">ATP pending review</p>
            <p class="mt-2 text-2xl font-bold text-blue-600">{{ $metrics['atp_pending'] }}</p>
        </div>
        <div class="pm-kpi-card slide-up" style="animation-delay:.08s">
            <p class="text-xs font-semibold text-gray-500">Request Checks pending</p>
            <p class="mt-2 text-2xl font-bold text-blue-600">{{ $metrics['rfc_pending'] }}</p>
        </div>
        <div class="pm-kpi-card slide-up" style="animation-delay:.12s">
            <p class="text-xs font-semibold text-gray-500">Funds awaiting release</p>
            <p class="mt-2 text-2xl font-bold text-blue-600">{{ $metrics['funds_awaiting'] }}</p>
        </div>
        <div class="pm-kpi-card slide-up" style="animation-delay:.16s">
            <p class="text-xs font-semibold text-gray-500">Liquidations pending</p>
            <p class="mt-2 text-2xl font-bold text-slate-700">{{ $metrics['liq_pending'] }}</p>
        </div>
        <div class="pm-kpi-card slide-up" style="animation-delay:.2s">
            <p class="text-xs font-semibold text-gray-500">Approved ATP</p>
            <p class="mt-2 text-2xl font-bold text-blue-600">{{ $metrics['atp_approved'] }}</p>
        </div>
        <div class="pm-kpi-card slide-up" style="animation-delay:.24s">
            <p class="text-xs font-semibold text-gray-500">Approved Request Checks</p>
            <p class="mt-2 text-2xl font-bold text-blue-600">{{ $metrics['rfc_approved'] }}</p>
        </div>
        <div class="pm-kpi-card slide-up" style="animation-delay:.28s">
            <p class="text-xs font-semibold text-gray-500">Approved liquidations</p>
            <p class="mt-2 text-2xl font-bold text-blue-600">{{ $metrics['liq_approved'] }}</p>
        </div>
        <div class="pm-kpi-card slide-up" style="animation-delay:.32s">
            <p class="text-xs font-semibold text-gray-500">Items requiring revision</p>
            <p class="mt-2 text-2xl font-bold text-slate-700">{{ $metrics['needs_revision'] }}</p>
        </div>
    </div>
</div>
@endsection
