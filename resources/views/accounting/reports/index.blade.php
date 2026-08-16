@extends('layouts.accounting-layout')

@section('title', 'Accounting Reports')

@section('content')
<div class="fade-in">
    <h1 class="text-2xl font-bold tracking-tight text-gray-900">Reports</h1>
    <p class="mt-1 text-sm text-gray-500">Live counts from Accounting workflow tables.</p>
</div>
<div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @foreach ([
        'ATP pending review' => $metrics['atp_pending'],
        'Request Checks pending' => $metrics['rfc_pending'],
        'Funds awaiting release' => $metrics['funds_awaiting'],
        'Liquidations pending' => $metrics['liq_pending'],
        'Approved ATP' => $metrics['atp_approved'],
        'Approved Request Checks' => $metrics['rfc_approved'],
        'Approved liquidations' => $metrics['liq_approved'],
        'Items requiring revision' => $metrics['needs_revision'],
    ] as $label => $value)
        <div class="rounded-xl border border-gray-200 bg-white p-5 card-hover slide-up">
            <p class="text-xs font-medium text-gray-500">{{ $label }}</p>
            <p class="mt-3 text-3xl font-bold text-gray-900">{{ $value }}</p>
        </div>
    @endforeach
</div>
@endsection
