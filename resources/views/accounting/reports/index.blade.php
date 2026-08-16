@extends('layouts.accounting-layout')

@section('title', 'Accounting Reports')

@section('content')
<div class="acc-page fade-in">
    <div class="acc-page-header">
        <div>
            <p class="acc-page-kicker">Insights</p>
            <h1 class="acc-page-title">Reports</h1>
            <p class="acc-page-subtitle">Live counts from Accounting workflow tables.</p>
        </div>
    </div>

    <div class="acc-stat-grid">
        @foreach ([
            ['ATP pending review', $metrics['atp_pending'], 'warn'],
            ['Request Checks pending', $metrics['rfc_pending'], 'warn'],
            ['Funds awaiting release', $metrics['funds_awaiting'], 'ok'],
            ['Liquidations pending', $metrics['liq_pending'], 'warn'],
            ['Approved ATP', $metrics['atp_approved'], 'ok'],
            ['Approved Request Checks', $metrics['rfc_approved'], 'ok'],
            ['Approved liquidations', $metrics['liq_approved'], 'ok'],
            ['Items requiring revision', $metrics['needs_revision'], 'info'],
        ] as $i => [$label, $value, $tone])
            <div class="acc-stat-card slide-up" style="animation-delay:{{ 0.04 * ($i + 1) }}s; padding-right: 0.9rem;">
                <p class="acc-stat-label is-{{ $tone }}">{{ $label }}</p>
                <p class="acc-stat-value">{{ $value }}</p>
            </div>
        @endforeach
    </div>
</div>
@endsection
